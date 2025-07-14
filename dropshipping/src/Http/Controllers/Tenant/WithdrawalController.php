<?php

namespace Plugin\Dropshipping\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Plugin\Dropshipping\Models\TenantBalance;
use Plugin\Dropshipping\Models\WithdrawalRequest;
use Plugin\Dropshipping\Models\WithdrawalSetting;

class WithdrawalController extends Controller
{
    /**
     * Display withdrawal dashboard
     */
    public function index()
    {
        $tenantId = tenant('id');

        // Get tenant balance
        $balance = TenantBalance::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'available_balance' => 0,
                'pending_balance' => 0,
                'total_earnings' => 0,
                'total_withdrawn' => 0,
                'total_orders' => 0,
                'pending_orders' => 0,
                'approved_orders' => 0
            ]
        );

        // Get withdrawal requests
        $withdrawalRequests = WithdrawalRequest::forTenant($tenantId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get withdrawal settings
        $settings = WithdrawalSetting::getActive();

        return view('plugin/dropshipping::tenant.withdrawals.index', compact('balance', 'withdrawalRequests', 'settings'));
    }

    /**
     * Show withdrawal request form
     */
    public function create()
    {
        $tenantId = tenant('id');

        $balance = TenantBalance::forTenant($tenantId)->first();
        if (!$balance || $balance->available_balance <= 0) {
            return redirect()->route('tenant.dropshipping.withdrawals.index')
                ->withErrors(['message' => 'You have no available balance to withdraw.']);
        }

        $settings = WithdrawalSetting::getActive();

        return view('plugin/dropshipping::tenant.withdrawals.create', compact('balance', 'settings'));
    }

    /**
     * Submit withdrawal request
     */
    public function store(Request $request)
    {
        // Basic validation
        $rules = [
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:bank_transfer,bkash,nogod,rocket,paypal',
            'account_holder_name' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ];

        // Add conditional validation based on payment method
        if ($request->payment_method === 'bank_transfer') {
            $rules['bank_name'] = 'required|string|max:255';
            $rules['account_number'] = 'required|string|max:50';
            $rules['routing_number'] = 'nullable|string|max:20';
        } elseif (in_array($request->payment_method, ['bkash', 'nogod', 'rocket'])) {
            $rules['mobile_number'] = 'required|string|max:20';
        } elseif ($request->payment_method === 'paypal') {
            $rules['paypal_email'] = 'required|email|max:255';
        }

        $request->validate($rules);

        $tenantId = tenant('id');
        $userId = Auth::id();

        // Get tenant balance
        $balance = TenantBalance::forTenant($tenantId)->first();
        if (!$balance) {
            return back()->withErrors(['amount' => 'No balance found for your account.']);
        }

        // Get withdrawal settings
        $settings = WithdrawalSetting::getActive();

        try {
            // Validate withdrawal amount
            $settings->validateAmount($request->amount);

            // Check if tenant has sufficient balance
            if (!$balance->canWithdraw($request->amount)) {
                return back()->withErrors(['amount' => 'Insufficient available balance.']);
            }

            // Check for pending withdrawal requests
            $pendingWithdrawals = WithdrawalRequest::forTenant($tenantId)
                ->byStatus('pending')
                ->sum('amount');

            $availableForWithdrawal = $balance->available_balance - $pendingWithdrawals;

            if ($request->amount > $availableForWithdrawal) {
                return back()->withErrors(['amount' => 'Amount exceeds available balance after considering pending withdrawals.']);
            }

            // Prepare payment details based on payment method
            $paymentDetails = [
                'account_holder_name' => $request->account_holder_name,
            ];

            if ($request->payment_method === 'bank_transfer') {
                $paymentDetails['bank_name'] = $request->bank_name;
                $paymentDetails['account_number'] = $request->account_number;
                $paymentDetails['routing_number'] = $request->routing_number;
            } elseif (in_array($request->payment_method, ['bkash', 'nogod', 'rocket'])) {
                $paymentDetails['mobile_number'] = '+88' . $request->mobile_number;
                $paymentDetails['account_number'] = '+88' . $request->mobile_number;
            } elseif ($request->payment_method === 'paypal') {
                $paymentDetails['paypal_email'] = $request->paypal_email;
                $paymentDetails['account_number'] = $request->paypal_email;
            }

            // Create withdrawal request
            $withdrawal = WithdrawalRequest::create([
                'tenant_id' => $tenantId,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'payment_details' => $paymentDetails,
                'notes' => $request->notes,
                'requested_by' => $userId,
                'status' => 'pending'
            ]);

            return redirect()->route('user.dropshipping.withdrawals.index')
                ->with('success', "Withdrawal request {$withdrawal->request_number} submitted successfully. It will be reviewed by the admin.");
        } catch (\Exception $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    /**
     * Show withdrawal request details
     */
    public function show($id)
    {
        $tenantId = tenant('id');

        $withdrawal = WithdrawalRequest::forTenant($tenantId)
            ->with(['requestedBy', 'processedBy'])
            ->findOrFail($id);

        return view('plugin/dropshipping::tenant.withdrawals.show', compact('withdrawal'));
    }

    /**
     * Cancel withdrawal request (only if pending)
     */
    public function cancel($id)
    {
        $tenantId = tenant('id');

        $withdrawal = WithdrawalRequest::forTenant($tenantId)->findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return back()->withErrors(['message' => 'Only pending withdrawal requests can be cancelled.']);
        }

        $withdrawal->update(['status' => 'cancelled']);

        return back()->with('success', 'Withdrawal request cancelled successfully.');
    }

    /**
     * Get withdrawal information for AJAX requests
     */
    public function getWithdrawalInfo()
    {
        $tenantId = tenant('id');

        $balance = TenantBalance::forTenant($tenantId)->first();
        $settings = WithdrawalSetting::getActive();

        $pendingWithdrawals = WithdrawalRequest::forTenant($tenantId)
            ->byStatus('pending')
            ->sum('amount');

        $availableForWithdrawal = $balance ? $balance->available_balance - $pendingWithdrawals : 0;

        return response()->json([
            'available_balance' => $balance ? $balance->available_balance : 0,
            'pending_withdrawals' => $pendingWithdrawals,
            'available_for_withdrawal' => $availableForWithdrawal,
            'minimum_amount' => $settings->minimum_withdrawal_amount,
            'maximum_amount' => $settings->maximum_withdrawal_amount,
            'fee_percentage' => $settings->withdrawal_fee_percentage,
            'fee_fixed' => $settings->withdrawal_fee_fixed,
            'processing_days' => $settings->withdrawal_processing_days,
        ]);
    }

    /**
     * Calculate withdrawal fee (AJAX)
     */
    public function calculateFee(Request $request)
    {
        $amount = (float) $request->input('amount', 0);
        $settings = WithdrawalSetting::getActive();

        $fee = $settings->calculateFee($amount);
        $netAmount = $settings->calculateNetAmount($amount);

        return response()->json([
            'fee' => number_format($fee, 2),
            'net_amount' => number_format($netAmount, 2),
            'gross_amount' => number_format($amount, 2),
        ]);
    }
}
