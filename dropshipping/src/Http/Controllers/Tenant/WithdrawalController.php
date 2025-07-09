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
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'account_holder_name' => 'required|string|max:255',
            'bank_code' => 'nullable|string|max:20',
            'swift_code' => 'nullable|string|max:20',
            'additional_details' => 'nullable|string|max:1000',
        ]);

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

            // Create withdrawal request
            $withdrawal = WithdrawalRequest::create([
                'tenant_id' => $tenantId,
                'amount' => $request->amount,
                'bank_name' => $request->bank_name,
                'account_number' => $request->account_number,
                'account_holder_name' => $request->account_holder_name,
                'bank_code' => $request->bank_code,
                'swift_code' => $request->swift_code,
                'additional_details' => $request->additional_details,
                'requested_by' => $userId,
                'status' => 'pending'
            ]);

            return redirect()->route('tenant.dropshipping.withdrawals.index')
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
