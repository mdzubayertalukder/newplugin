<?php

namespace Plugin\Saas\Http\Controllers\Payment;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Plugin\Saas\Http\Controllers\Payment\PaymentController;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;

class MultipurcpayController extends Controller
{
    protected $total_payable_amount;
    protected $api_key;
    protected $currency = "BDT";
    protected $base_url = 'https://aidroppay.xyz/api';

    public function __construct()
    {
        $this->currency = \Plugin\Saas\Repositories\PaymentMethodRepository::configKeyValue(config('saas.payment_methods.multipurcpay'), 'multipurcpay_currency') ?: 'BDT';
        
        $payable_amount = session()->get('payable_amount', 0);
        if ($payable_amount > 0) {
            $this->total_payable_amount = (new PaymentController())->convertCurrency($this->currency, $payable_amount);
        } else {
            $this->total_payable_amount = 0;
        }
        
        $this->api_key = \Plugin\Saas\Repositories\PaymentMethodRepository::configKeyValue(config('saas.payment_methods.multipurcpay'), 'multipurcpay_api_key');
    }

    /**
     * Initial Multipurcpay payment - Auto create charge and redirect
     */
    public function index()
    {
        try {
            // Check if API key is configured
            if (empty($this->api_key)) {
                Log::error('Multipurcpay API key not configured');
                Session::flash('error', 'Multipurcpay API key not configured.');
                return (new PaymentController)->payment_failed();
            }

            // Check if required session data exists
            if (!session()->has('payable_amount') || !session()->has('payment_type') || session()->get('payable_amount') <= 0) {
                Log::warning('Multipurcpay Payment - Missing Session Data', [
                    'payable_amount' => session()->get('payable_amount'),
                    'payment_type' => session()->get('payment_type'),
                    'user_id' => auth()->user() ? auth()->user()->id : 'guest',
                    'all_session_keys' => array_keys(session()->all())
                ]);
                
                Session::flash('error', 'Payment session expired or invalid. Please start the payment process again.');
                return redirect()->route('plugin.saas.user.dashboard');
            }

            Log::info('Multipurcpay Payment - Auto Creating Charge', [
                'amount' => $this->total_payable_amount,
                'currency' => $this->currency,
                'user_id' => auth()->user() ? auth()->user()->id : null,
                'session_data' => [
                    'package_id' => session()->get('package_id'),
                    'plan_id' => session()->get('plan_id'),
                    'store_name' => session()->get('store_name'),
                ]
            ]);

            // Auto create charge and redirect to payment URL
            return $this->createChargeAndRedirect();

        } catch (\Exception $e) {
            Log::error('Multipurcpay Index Error: ' . $e->getMessage());
            Session::flash('error', 'Payment initialization failed: ' . $e->getMessage());
            return (new PaymentController)->payment_failed();
        }
    }

    /**
     * Create charge and redirect to payment URL
     */
    private function createChargeAndRedirect()
    {
        try {
            // Get user details from session
            $user_name = session()->get('name', 'Demo User');
            $user_email = session()->get('email', 'demo@example.com');

            // Prepare payment data
            $payment_data = [
                'full_name' => $user_name,
                'email_mobile' => $user_email,
                'amount' => number_format($this->total_payable_amount, 2, '.', ''),
                'metadata' => [
                    'package_id' => session()->get('package_id'),
                    'plan_id' => session()->get('plan_id'),
                    'store_name' => session()->get('store_name'),
                    'payment_type' => session()->get('payment_type'),
                ],
                'redirect_url' => route('plugin.saas.multipurcpay.success.payment'),
                'return_type' => 'GET',
                'cancel_url' => route('plugin.saas.multipurcpay.cancel.payment'),
                'webhook_url' => route('plugin.saas.multipurcpay.webhook'),
                'currency' => $this->currency,
            ];

            Log::info('Multipurcpay Auto Create Charge Request', [
                'url' => $this->base_url . '/create-charge',
                'data' => $payment_data,
                'api_key' => substr($this->api_key, 0, 10) . '...',
            ]);

            $response = Http::timeout(60)
                ->retry(3, 2000)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'mh-piprapay-api-key' => $this->api_key,
                ])
                ->post($this->base_url . '/create-charge', $payment_data);

            Log::info('Multipurcpay Auto Create Charge Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Check for pp_url (correct key from Aidroppay API)
                if (isset($data['pp_url'])) {
                    // Store payment ID in session for verification
                    if (isset($data['pp_id'])) {
                        session()->put('multipurcpay_payment_id', $data['pp_id']);
                    }

                    Log::info('Multipurcpay Auto Redirect', [
                        'payment_url' => $data['pp_url'],
                        'pp_id' => $data['pp_id'] ?? null,
                    ]);

                    // Redirect directly to payment URL
                    return redirect()->away($data['pp_url']);
                } else {
                    Log::error('Multipurcpay Auto Create Charge - No payment URL', [
                        'response' => $data
                    ]);
                    Session::flash('error', 'Payment URL not found in response. Please try again.');
                    return (new PaymentController)->payment_failed();
                }
            }

            $errorMessage = 'HTTP ' . $response->status();
            $responseData = $response->json();

            if (isset($responseData['message'])) {
                $errorMessage .= ': ' . $responseData['message'];
            } else {
                $errorMessage .= ': ' . $response->body();
            }

            Log::error('Multipurcpay Auto Create Charge Failed', [
                'error' => $errorMessage,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            Session::flash('error', $errorMessage);
            return (new PaymentController)->payment_failed();

        } catch (\Exception $e) {
            Log::error('Multipurcpay Auto Create Charge Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Session::flash('error', 'Connection error: ' . $e->getMessage());
            return (new PaymentController)->payment_failed();
        }
    }

    /**
     * Create Multipurcpay payment charge
     */
    public function createCharge(Request $request)
    {
        try {
            // Validate credentials
            if (empty($this->api_key)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Multipurcpay API key not configured. Please configure API key in payment settings.',
                ]);
            }

            // Get user details from session
            $user_name = session()->get('name', 'Demo User');
            $user_email = session()->get('email', 'demo@example.com');

            // Prepare payment data
            $payment_data = [
                'full_name' => $user_name,
                'email_mobile' => $user_email,
                'amount' => number_format($this->total_payable_amount, 2, '.', ''),
                'metadata' => [
                    'package_id' => session()->get('package_id'),
                    'plan_id' => session()->get('plan_id'),
                    'store_name' => session()->get('store_name'),
                    'payment_type' => session()->get('payment_type'),
                ],
                'redirect_url' => route('plugin.saas.multipurcpay.success.payment'),
                'return_type' => 'GET',
                'cancel_url' => route('plugin.saas.multipurcpay.cancel.payment'),
                'webhook_url' => route('plugin.saas.multipurcpay.webhook'),
                'currency' => $this->currency,
            ];

            Log::info('Multipurcpay Create Charge Request', [
                'url' => $this->base_url . '/create-charge',
                'data' => $payment_data,
                'api_key' => substr($this->api_key, 0, 10) . '...',
            ]);

            $response = Http::timeout(60)
                ->retry(3, 2000)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'mh-piprapay-api-key' => $this->api_key,
                ])
                ->post($this->base_url . '/create-charge', $payment_data);

            Log::info('Multipurcpay Create Charge Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Check for pp_url (correct key from Aidroppay API)
                if (isset($data['pp_url'])) {
                    // Store payment ID in session for verification
                    if (isset($data['pp_id'])) {
                        session()->put('multipurcpay_payment_id', $data['pp_id']);
                    }

                    return response()->json([
                        'success' => true,
                        'payment_url' => $data['pp_url'], // Use pp_url from API response
                        'pp_id' => $data['pp_id'] ?? null,
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Payment URL not found in response. Expected pp_url key. Response: ' . json_encode($data),
                    ]);
                }
            }

            $errorMessage = 'HTTP ' . $response->status();
            $responseData = $response->json();

            if (isset($responseData['message'])) {
                $errorMessage .= ': ' . $responseData['message'];
            } else {
                $errorMessage .= ': ' . $response->body();
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ]);
        } catch (\Exception $e) {
            Log::error('Multipurcpay Create Charge Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Verify Multipurcpay payment
     */
    public function verifyPayment(Request $request)
    {
        try {
            $pp_id = $request->input('pp_id') ?: session()->get('multipurcpay_payment_id');

            if (!$pp_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment ID is required for verification',
                ]);
            }

            Log::info('Multipurcpay Verify Payment Request', [
                'pp_id' => $pp_id,
                'api_key' => substr($this->api_key, 0, 10) . '...',
            ]);

            $response = Http::timeout(60)
                ->retry(3, 2000)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'mh-piprapay-api-key' => $this->api_key,
                ])
                ->post($this->base_url . '/verify-payments', [
                    'pp_id' => $pp_id,
                ]);

            Log::info('Multipurcpay Verify Payment Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['status']) && $data['status'] === 'completed') {
                    // Store payment details in session
                    session()->put('multipurcpay_transaction_id', $data['transaction_id'] ?? null);
                    session()->put('multipurcpay_payment_method', $data['payment_method'] ?? 'Multipurcpay');
                    session()->put('multipurcpay_amount', $data['amount'] ?? null);

                    return response()->json([
                        'success' => true,
                        'status' => 'completed',
                        'data' => $data,
                        'redirect_url' => route('plugin.saas.multipurcpay.success.payment')
                    ]);
                } else {
                    return response()->json([
                        'success' => true,
                        'status' => $data['status'] ?? 'pending',
                        'data' => $data,
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify payment',
                'data' => $response->json()
            ]);
        } catch (\Exception $e) {
            Log::error('Multipurcpay Verify Payment Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Verification error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle webhook from Multipurcpay
     */
    public function webhook(Request $request)
    {
        try {
            // Verify webhook authenticity
            $received_api_key = $request->header('mh-piprapay-api-key')
                ?: $request->header('Mh-Piprapay-Api-Key')
                ?: $request->server('HTTP_MH_PIPRAPAY_API_KEY');

            if ($received_api_key !== $this->api_key) {
                Log::warning('Multipurcpay Webhook - Unauthorized request', [
                    'received_key' => substr($received_api_key, 0, 10) . '...',
                    'expected_key' => substr($this->api_key, 0, 10) . '...',
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized request.'
                ], 401);
            }

            $webhook_data = $request->all();

            Log::info('Multipurcpay Webhook Received', [
                'data' => $webhook_data,
            ]);

            // Process webhook data
            $pp_id = $webhook_data['pp_id'] ?? '';
            $status = $webhook_data['status'] ?? '';

            if ($status === 'completed') {
                // Store payment details for success processing
                session()->put('multipurcpay_payment_id', $pp_id);
                session()->put('multipurcpay_transaction_id', $webhook_data['transaction_id'] ?? null);
                session()->put('multipurcpay_payment_method', $webhook_data['payment_method'] ?? 'Multipurcpay');
                session()->put('multipurcpay_amount', $webhook_data['amount'] ?? null);
            }

            return response()->json([
                'status' => true,
                'message' => 'Webhook received'
            ]);
        } catch (\Exception $e) {
            Log::error('Multipurcpay Webhook Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Webhook processing failed'
            ], 500);
        }
    }

    /**
     * Success Multipurcpay payment
     */
    public function success(Request $request)
    {
        try {

            Log::info('Multipurcpay Payment Success - Session Data', [
                'payment_type' => session()->get('payment_type'),
                'payment_method' => session()->get('payment_method'),
                'payable_amount' => session()->get('payable_amount'),
                'multipurcpay_payment_id' => session()->get('multipurcpay_payment_id'),
                'multipurcpay_transaction_id' => session()->get('multipurcpay_transaction_id'),
                'multipurcpay_amount' => session()->get('multipurcpay_amount'),
                'request_params' => $request->all(),
            ]);

            // Get pp_id from request or session
            $pp_id = $request->input('pp_id') ?: session()->get('multipurcpay_payment_id');

            if (!$pp_id) {
                Log::error('Multipurcpay Payment Success - Missing Payment ID');
                throw new \Exception('Payment verification failed: Missing payment ID');
            }

            // Verify payment status
            $verification_response = Http::timeout(60)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'mh-piprapay-api-key' => $this->api_key,
                ])
                ->post($this->base_url . '/verify-payments', [
                    'pp_id' => $pp_id,
                ]);

            if (!$verification_response->successful()) {
                throw new \Exception('Payment verification failed: Unable to verify payment status');
            }

            $verification_data = $verification_response->json();

            if (!isset($verification_data['status']) || $verification_data['status'] !== 'completed') {
                throw new \Exception('Payment verification failed: Payment not completed');
            }

            // Prepare payment information
            $payment_info = [
                'pp_id' => $pp_id,
                'transaction_id' => $verification_data['transaction_id'] ?? session()->get('multipurcpay_transaction_id'),
                'payment_method' => $verification_data['payment_method'] ?? 'Multipurcpay',
                'amount' => $verification_data['amount'] ?? session()->get('multipurcpay_amount'),
                'currency' => $verification_data['currency'] ?? $this->currency,
                'status' => 'completed',
                'verified_at' => now()->toISOString(),
                'customer_name' => $verification_data['customer_name'] ?? session()->get('name'),
                'customer_email_mobile' => $verification_data['customer_email_mobile'] ?? session()->get('email'),
                'metadata' => $verification_data['metadata'] ?? [],
            ];

            // Clear Multipurcpay specific session data
            session()->forget('multipurcpay_payment_id');
            session()->forget('multipurcpay_transaction_id');
            session()->forget('multipurcpay_amount');

            return (new PaymentController)->payment_success($payment_info);
        } catch (\Exception $e) {
            Log::error('Multipurcpay Payment Success Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return (new PaymentController)->payment_failed();
        }
    }

    /**
     * Cancel Multipurcpay payment
     */
    public function cancel()
    {
        try {

            Log::info('Multipurcpay Payment Cancelled', [
                'user_id' => auth()->user() ? auth()->user()->id : null,
                'session_data' => [
                    'payment_type' => session()->get('payment_type'),
                    'payment_method' => session()->get('payment_method'),
                    'payable_amount' => session()->get('payable_amount'),
                    'multipurcpay_payment_id' => session()->get('multipurcpay_payment_id'),
                ]
            ]);

            // Clear Multipurcpay specific session data
            session()->forget('multipurcpay_payment_id');
            session()->forget('multipurcpay_transaction_id');
            session()->forget('multipurcpay_amount');
            session()->forget('multipurcpay_payment_method');

            // Call the parent payment controller's cancel method
            return (new PaymentController())->payment_cancel();
        } catch (\Exception $e) {
            Log::error('Multipurcpay Payment Cancel Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->user() ? auth()->user()->id : null,
            ]);

            Session::flash('error', 'Payment cancellation failed. Please try again.');
            return Redirect::route('plugin.saas.user.dashboard');
        }
    }
}
