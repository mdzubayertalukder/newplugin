<?php

namespace Plugin\Saas\Http\Controllers\Payment;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Plugin\Saas\Http\Controllers\Payment\PaymentController;

class MultipurcpayController extends Controller
{
    protected $total_payable_amount;
    protected $api_key;
    protected $currency = "BDT";
    protected $base_url = 'https://aidroppay.xyz/api';

    public function __construct()
    {
        $this->currency = \Plugin\Saas\Repositories\PaymentMethodRepository::configKeyValue(config('saas.payment_methods.multipurcpay'), 'multipurcpay_currency');
        $this->total_payable_amount = (new PaymentController())->convertCurrency($this->currency, session()->get('payable_amount'));
        $this->api_key = \Plugin\Saas\Repositories\PaymentMethodRepository::configKeyValue(config('saas.payment_methods.multipurcpay'), 'multipurcpay_api_key');
    }

    /**
     * Initial Multipurcpay payment
     */
    public function index()
    {
        // REMOVED: Super Admin check - now available for all users
        // Original code had: if (!$this->isSuperAdmin()) { return redirect()->back()->with('error', '...'); }

        $data = [
            'currency' => $this->currency,
            'total_payable_amount' => number_format($this->total_payable_amount, 2, '.', ''),
            'api_key' => $this->api_key,
            'base_url' => $this->base_url,
            'payable_amount' => $this->total_payable_amount,
            'logo' => \Plugin\Saas\Repositories\PaymentMethodRepository::configKeyValue(config('saas.payment_methods.multipurcpay'), 'multipurcpay_logo'),
            'instruction' => \Plugin\Saas\Repositories\PaymentMethodRepository::configKeyValue(config('saas.payment_methods.multipurcpay'), 'multipurcpay_instruction'),
        ];

        return view('plugin/saas::payments.gateways.multipurcpay.index', $data);
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

            // REMOVED: Super Admin check - now available for all users
            // Original code had: if (!$this->isSuperAdmin()) { return response()->json(['success' => false, 'message' => '...']); }

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
                
                if (isset($data['payment_url'])) {
                    // Store payment ID in session for verification
                    if (isset($data['pp_id'])) {
                        session()->put('multipurcpay_payment_id', $data['pp_id']);
                    }

                    return response()->json([
                        'success' => true,
                        'payment