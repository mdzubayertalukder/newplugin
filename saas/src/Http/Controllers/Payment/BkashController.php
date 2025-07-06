<?php

namespace Plugin\Saas\Http\Controllers\Payment;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Plugin\Saas\Http\Controllers\Payment\PaymentController;

class BkashController extends Controller
{
    protected $total_payable_amount;
    protected $app_key;
    protected $app_secret;
    protected $username;
    protected $password;
    protected $currency = "BDT";
    protected $base_url;
    protected $sandbox_url = 'https://tokenized.sandbox.bka.sh/v1.2.0-beta';
    protected $live_url = 'https://tokenized.pay.bka.sh/v1.2.0-beta';

    public function __construct()
    {
        $this->currency = \Plugin\Saas\Repositories\PaymentMethodRepository::configKeyValue(config('saas.payment_methods.bkash'), 'bkash_currency');
        $this->total_payable_amount = (new PaymentController())->convertCurrency($this->currency, session()->get('payable_amount'));

        $this->app_key = \Plugin\Saas\Repositories\PaymentMethodRepository::configKeyValue(config('saas.payment_methods.bkash'), 'bkash_app_key');
        $this->app_secret = \Plugin\Saas\Repositories\PaymentMethodRepository::configKeyValue(config('saas.payment_methods.bkash'), 'bkash_app_secret');
        $this->username = \Plugin\Saas\Repositories\PaymentMethodRepository::configKeyValue(config('saas.payment_methods.bkash'), 'bkash_username');
        $this->password = \Plugin\Saas\Repositories\PaymentMethodRepository::configKeyValue(config('saas.payment_methods.bkash'), 'bkash_password');

        $sandbox = \Plugin\Saas\Repositories\PaymentMethodRepository::configKeyValue(config('saas.payment_methods.bkash'), 'sandbox');
        $this->base_url = $sandbox == '1' ? $this->sandbox_url : $this->live_url;
    }

    /**
     * Initial bKash payment
     */
    public function index()
    {
        $data = [
            'currency' => $this->currency,
            'total_payable_amount' => number_format($this->total_payable_amount, 2, '.', ''),
            'app_key' => $this->app_key,
            'base_url' => $this->base_url,
        ];

        return view('plugin/saas::payments.gateways.bkash.index', $data);
    }

    /**
     * Get bKash token
     */
    public function getToken()
    {
        // Validate credentials
        if (empty($this->app_key) || empty($this->app_secret) || empty($this->username) || empty($this->password)) {
            return response()->json([
                'success' => false,
                'message' => 'bKash credentials not configured. Please configure App Key, App Secret, Username and Password in payment settings.',
            ]);
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'username' => $this->username,
                'password' => $this->password,
            ])->post($this->base_url . '/tokenized/checkout/token/grant', [
                'app_key' => $this->app_key,
                'app_secret' => $this->app_secret,
            ]);

            \Illuminate\Support\Facades\Log::info('bKash Token Request (SaaS)', [
                'url' => $this->base_url . '/tokenized/checkout/token/grant',
                'app_key' => $this->app_key,
                'username' => $this->username,
                'response_status' => $response->status(),
                'response_body' => $response->body(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['id_token'])) {
                    return response()->json([
                        'success' => true,
                        'token' => $data['id_token'],
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Token not found in response. Response: ' . json_encode($data),
                    ]);
                }
            }

            $errorMessage = 'HTTP ' . $response->status();
            $responseData = $response->json();

            if (isset($responseData['errorMessage'])) {
                $errorMessage .= ': ' . $responseData['errorMessage'];
            } elseif (isset($responseData['message'])) {
                $errorMessage .= ': ' . $responseData['message'];
            } else {
                $errorMessage .= ': ' . $response->body();
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('bKash Token Generation Error (SaaS)', [
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
     * Create bKash payment
     */
    public function createPayment(Request $request)
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'authorization' => $request->token,
                'x-app-key' => $this->app_key,
            ])->post($this->base_url . '/tokenized/checkout/create', [
                'mode' => '0011',
                'payerReference' => 'Payment-' . time(),
                'callbackURL' => route('plugin.saas.bkash.callback'),
                'amount' => number_format($this->total_payable_amount, 2, '.', ''),
                'currency' => $this->currency,
                'intent' => 'sale',
                'merchantInvoiceNumber' => 'Invoice-' . time(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'data' => $data,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Execute bKash payment
     */
    public function executePayment(Request $request)
    {
        try {
            // Log the execute payment request
            \Illuminate\Support\Facades\Log::info('bKash Execute Payment Request', [
                'paymentID' => $request->paymentID,
                'token' => substr($request->token, 0, 20) . '...', // Log partial token for security
            ]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'authorization' => $request->token,
                'x-app-key' => $this->app_key,
            ])->post($this->base_url . '/tokenized/checkout/execute', [
                'paymentID' => $request->paymentID,
            ]);

            // Log the response
            \Illuminate\Support\Facades\Log::info('bKash Execute Payment Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Log the full response for debugging
                \Illuminate\Support\Facades\Log::info('bKash Execute Payment Full Response', [
                    'data' => $data,
                    'paymentID' => $request->paymentID
                ]);

                // Check for bKash error codes first
                if (isset($data['statusCode']) && $data['statusCode'] !== '0000') {
                    $errorMessage = 'bKash Error: ' . ($data['statusMessage'] ?? 'Unknown error');

                    // Handle specific error codes
                    switch ($data['statusCode']) {
                        case '2056':
                            $errorMessage = 'Payment state is invalid. Please try the payment again.';
                            break;
                        case '2057':
                            $errorMessage = 'Payment ID is invalid or expired.';
                            break;
                        case '2058':
                            $errorMessage = 'Payment has already been completed.';
                            break;
                        case '2059':
                            $errorMessage = 'Payment has been cancelled.';
                            break;
                        default:
                            $errorMessage = 'bKash Error: ' . ($data['statusMessage'] ?? 'Unknown error');
                    }

                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage,
                        'data' => $data
                    ]);
                }

                // Validate the response
                if (!isset($data['transactionStatus'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid response from bKash: Missing transaction status',
                        'data' => $data
                    ]);
                }

                if ($data['transactionStatus'] === 'Completed') {
                    // Verify the transaction amount
                    $expectedAmount = number_format($this->total_payable_amount, 2, '.', '');
                    $actualAmount = isset($data['amount']) ? $data['amount'] : null;

                    if ($actualAmount && $actualAmount !== $expectedAmount) {
                        \Illuminate\Support\Facades\Log::warning('bKash Amount Mismatch', [
                            'expected' => $expectedAmount,
                            'actual' => $actualAmount,
                            'paymentID' => $request->paymentID
                        ]);
                    }

                    // Payment successful - store transaction details
                    session()->put('bkash_transaction_id', $data['trxID'] ?? null);
                    session()->put('bkash_payment_id', $data['paymentID'] ?? null);
                    session()->put('bkash_amount', $data['amount'] ?? null);

                    return response()->json([
                        'success' => true,
                        'data' => $data,
                        'redirect_url' => route('plugin.saas.bkash.success.payment')
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Payment not completed. Status: ' . $data['transactionStatus'],
                        'data' => $data
                    ]);
                }
            }

            // Handle unsuccessful response
            $errorData = $response->json();
            $errorMessage = 'Payment execution failed';

            if (isset($errorData['errorMessage'])) {
                $errorMessage .= ': ' . $errorData['errorMessage'];
            } elseif (isset($errorData['message'])) {
                $errorMessage .= ': ' . $errorData['message'];
            } else {
                $errorMessage .= ' (HTTP ' . $response->status() . ')';
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'data' => $errorData
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('bKash Execute Payment Error', [
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
     * bKash payment callback
     */
    public function callback(Request $request)
    {
        if ($request->has('status') && $request->status === 'success') {
            return redirect()->route('plugin.saas.bkash.success.payment');
        }

        return redirect()->route('plugin.saas.bkash.cancel.payment');
    }

    /**
     * Success bKash payment
     */
    public function success()
    {
        try {
            // Log the session data for debugging
            \Illuminate\Support\Facades\Log::info('bKash Payment Success (SaaS) - Session Data', [
                'payment_type' => session()->get('payment_type'),
                'payment_method' => session()->get('payment_method'),
                'payment_method_id' => session()->get('payment_method_id'),
                'payable_amount' => session()->get('payable_amount'),
                'bkash_transaction_id' => session()->get('bkash_transaction_id'),
                'bkash_payment_id' => session()->get('bkash_payment_id'),
                'bkash_amount' => session()->get('bkash_amount'),
            ]);

            // Verify that we have the required transaction data
            $transaction_id = session()->get('bkash_transaction_id');
            $payment_id = session()->get('bkash_payment_id');
            $expected_amount = session()->get('payable_amount');
            $actual_amount = session()->get('bkash_amount');

            if (!$transaction_id || !$payment_id) {
                \Illuminate\Support\Facades\Log::error('bKash Payment Success - Missing Transaction Data', [
                    'transaction_id' => $transaction_id,
                    'payment_id' => $payment_id,
                ]);
                throw new \Exception('Payment verification failed: Missing transaction data');
            }

            // Verify amount if available
            if ($actual_amount && $expected_amount) {
                $expected_formatted = number_format($expected_amount, 2, '.', '');
                if ($actual_amount !== $expected_formatted) {
                    \Illuminate\Support\Facades\Log::warning('bKash Payment Success - Amount Mismatch', [
                        'expected' => $expected_formatted,
                        'actual' => $actual_amount,
                    ]);
                }
            }

            // Get payment information from session
            $payment_info = [
                'transaction_id' => $transaction_id,
                'payment_id' => $payment_id,
                'payment_method' => session()->get('payment_method', 'bKash'),
                'amount' => $actual_amount,
                'status' => 'completed',
                'verified_at' => now()->toISOString()
            ];

            // Clear bKash specific session data
            session()->forget('bkash_transaction_id');
            session()->forget('bkash_payment_id');
            session()->forget('bkash_amount');

            return (new PaymentController)->payment_success($payment_info);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('bKash Payment Success Error (SaaS)', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return (new PaymentController)->payment_failed();
        }
    }

    /**
     * Verify bKash payment status
     */
    public function verifyPayment(Request $request)
    {
        try {
            $this->setCredentials();

            $paymentID = $request->paymentID;

            if (!$paymentID) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment ID is required'
                ]);
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'authorization' => $request->token,
                'x-app-key' => $this->app_key,
            ])->post($this->base_url . '/tokenized/checkout/query', [
                'paymentID' => $paymentID,
            ]);

            \Illuminate\Support\Facades\Log::info('bKash Payment Verification', [
                'paymentID' => $paymentID,
                'response_status' => $response->status(),
                'response_body' => $response->body(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return response()->json([
                    'success' => true,
                    'data' => $data,
                    'status' => $data['transactionStatus'] ?? 'unknown'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify payment',
                'data' => $response->json()
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('bKash Payment Verification Error', [
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
     * Cancel bKash payment
     */
    public function cancel()
    {
        return (new PaymentController)->payment_failed();
    }
}
