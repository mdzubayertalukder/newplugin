<?php

namespace Plugin\Dropshipping\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Plugin\Dropshipping\Models\WithdrawalRequest;
use Plugin\Dropshipping\Models\WithdrawalSetting;
use Plugin\Dropshipping\Models\TenantBalance;

class WithdrawalController extends Controller
{
    public function __construct()
    {
        // Force central database connection for admin operations
        $this->middleware(function ($request, $next) {
            // Ensure we're using the central database connection
            $centralConnection = config('tenancy.database.central_connection', 'mysql');
            Config::set('database.default', $centralConnection);
            DB::setDefaultConnection($centralConnection);

            return $next($request);
        });
    }

    /**
     * Display all withdrawal requests
     */
    public function index(Request $request)
    {
        // Explicitly force central database connection for admin operations
        $centralConnection = config('tenancy.database.central_connection', 'mysql');
        Config::set('database.default', $centralConnection);
        DB::setDefaultConnection($centralConnection);

        // Remove eager loading of user relationships to avoid tenant database issues
        $query = WithdrawalRequest::on($centralConnection);

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->byStatus($request->status);
        }

        // Filter by tenant
        if ($request->filled('tenant_id')) {
            $query->forTenant($request->tenant_id);
        }

        // Search by request number or tenant
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'LIKE', "%{$search}%")
                    ->orWhere('tenant_id', 'LIKE', "%{$search}%")
                    ->orWhere('account_holder_name', 'LIKE', "%{$search}%");
            });
        }

        $withdrawals = $query->orderBy('created_at', 'desc')->paginate(20);

        // NOTE: User data loading is temporarily disabled to avoid multi-tenant database conflicts
        // User IDs will be displayed instead of names until this is properly resolved

        // Convert payment_details arrays to strings to prevent view errors
        foreach ($withdrawals as $withdrawal) {
            if (is_array($withdrawal->payment_details)) {
                $details = [];
                if (isset($withdrawal->payment_details['mobile_number'])) {
                    $details[] = $withdrawal->payment_details['mobile_number'];
                }
                if (isset($withdrawal->payment_details['account_holder_name'])) {
                    $details[] = $withdrawal->payment_details['account_holder_name'];
                }
                if (isset($withdrawal->payment_details['account_number'])) {
                    $details[] = $withdrawal->payment_details['account_number'];
                }
                $withdrawal->payment_details_string = implode(', ', array_filter($details));
            } else {
                $withdrawal->payment_details_string = $withdrawal->payment_details;
            }
        }

        // Get statistics using explicit connection
        $stats = [
            'total_requests' => WithdrawalRequest::on($centralConnection)->count(),
            'pending_requests' => WithdrawalRequest::on($centralConnection)->byStatus('pending')->count(),
            'approved_requests' => WithdrawalRequest::on($centralConnection)->byStatus('approved')->count(),
            'rejected_requests' => WithdrawalRequest::on($centralConnection)->byStatus('rejected')->count(),
            'processed_requests' => WithdrawalRequest::on($centralConnection)->byStatus('processed')->count(),
            'total_amount' => WithdrawalRequest::on($centralConnection)->byStatus('processed')->sum('amount'),
            'pending_amount' => WithdrawalRequest::on($centralConnection)->byStatus('pending')->sum('amount'),
        ];

        // Get unique tenants for filter using explicit connection
        $tenants = WithdrawalRequest::on($centralConnection)->select('tenant_id')
            ->distinct()
            ->get()
            ->pluck('tenant_id');

        return view('plugin/dropshipping::admin.withdrawals.index', compact('withdrawals', 'stats', 'tenants'));
    }

    /**
     * Show withdrawal request details
     */
    public function show($id)
    {
        // Explicitly force central database connection for admin operations
        $centralConnection = config('tenancy.database.central_connection', 'mysql');
        Config::set('database.default', $centralConnection);
        DB::setDefaultConnection($centralConnection);

        // Remove user relationship eager loading to avoid tenant database issues
        $withdrawal = WithdrawalRequest::on($centralConnection)->with(['tenantBalance'])->findOrFail($id);

        // Manually load user data from central database to avoid tenant database conflicts
        if ($withdrawal->requested_by) {
            try {
                $withdrawal->requested_user = DB::connection($centralConnection)->table('users')
                    ->where('id', $withdrawal->requested_by)
                    ->first();
            } catch (\Exception $e) {
                // If user lookup fails, just continue without user data
                $withdrawal->requested_user = null;
            }
        }

        if ($withdrawal->processed_by) {
            try {
                $withdrawal->processed_user = DB::connection($centralConnection)->table('users')
                    ->where('id', $withdrawal->processed_by)
                    ->first();
            } catch (\Exception $e) {
                // If user lookup fails, just continue without user data
                $withdrawal->processed_user = null;
            }
        }

        // Convert payment_details arrays to strings for display
        if (is_array($withdrawal->payment_details)) {
            $details = [];
            if (isset($withdrawal->payment_details['mobile_number'])) {
                $details[] = $withdrawal->payment_details['mobile_number'];
            }
            if (isset($withdrawal->payment_details['account_holder_name'])) {
                $details[] = $withdrawal->payment_details['account_holder_name'];
            }
            if (isset($withdrawal->payment_details['account_number'])) {
                $details[] = $withdrawal->payment_details['account_number'];
            }
            $withdrawal->payment_details_string = implode(', ', array_filter($details));
        } else {
            $withdrawal->payment_details_string = $withdrawal->payment_details;
        }

        return view('plugin/dropshipping::admin.withdrawals.show', compact('withdrawal'));
    }

    /**
     * Approve withdrawal request
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $withdrawal = WithdrawalRequest::findOrFail($id);

        if (!$withdrawal->canBeApproved()) {
            return back()->withErrors(['message' => 'This withdrawal request cannot be approved.']);
        }

        // Check if tenant has sufficient balance
        $balance = TenantBalance::forTenant($withdrawal->tenant_id)->first();
        if (!$balance || !$balance->canWithdraw($withdrawal->amount)) {
            return back()->withErrors(['message' => 'Tenant has insufficient balance for this withdrawal.']);
        }

        $adminId = Auth::id();
        $withdrawal->approve($adminId, $request->admin_notes);

        return back()->with('success', "Withdrawal request {$withdrawal->request_number} approved successfully.");
    }

    /**
     * Reject withdrawal request
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $withdrawal = WithdrawalRequest::findOrFail($id);

        if (!$withdrawal->canBeRejected()) {
            return back()->withErrors(['message' => 'This withdrawal request cannot be rejected.']);
        }

        $adminId = Auth::id();
        $withdrawal->reject($adminId, $request->rejection_reason);

        return back()->with('success', "Withdrawal request {$withdrawal->request_number} rejected. Tenant has been notified.");
    }

    /**
     * Mark withdrawal as processed (payment sent)
     */
    public function markAsProcessed(Request $request, $id)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $withdrawal = WithdrawalRequest::findOrFail($id);

        if ($withdrawal->status !== 'approved') {
            return back()->withErrors(['message' => 'Only approved withdrawals can be marked as processed.']);
        }

        // Check tenant balance again
        $balance = TenantBalance::forTenant($withdrawal->tenant_id)->first();
        if (!$balance || !$balance->canWithdraw($withdrawal->amount)) {
            return back()->withErrors(['message' => 'Tenant no longer has sufficient balance for this withdrawal.']);
        }

        $adminId = Auth::id();

        try {
            $withdrawal->markAsProcessed($adminId, $request->admin_notes);
            return back()->with('success', "Withdrawal request {$withdrawal->request_number} marked as processed. Amount deducted from tenant balance.");
        } catch (\Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    /**
     * Bulk operations on withdrawal requests
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:approve,reject,process',
            'withdrawal_ids' => 'required|array',
            'withdrawal_ids.*' => 'exists:withdrawal_requests,id',
            'bulk_notes' => 'nullable|string|max:1000',
            'bulk_rejection_reason' => 'required_if:action,reject|string|max:1000',
        ]);

        $adminId = Auth::id();
        $withdrawalIds = $request->withdrawal_ids;
        $successCount = 0;

        foreach ($withdrawalIds as $withdrawalId) {
            $withdrawal = WithdrawalRequest::find($withdrawalId);
            if (!$withdrawal) continue;

            try {
                switch ($request->action) {
                    case 'approve':
                        if ($withdrawal->canBeApproved()) {
                            $balance = TenantBalance::forTenant($withdrawal->tenant_id)->first();
                            if ($balance && $balance->canWithdraw($withdrawal->amount)) {
                                $withdrawal->approve($adminId, $request->bulk_notes);
                                $successCount++;
                            }
                        }
                        break;

                    case 'reject':
                        if ($withdrawal->canBeRejected()) {
                            $withdrawal->reject($adminId, $request->bulk_rejection_reason);
                            $successCount++;
                        }
                        break;

                    case 'process':
                        if ($withdrawal->status === 'approved') {
                            $balance = TenantBalance::forTenant($withdrawal->tenant_id)->first();
                            if ($balance && $balance->canWithdraw($withdrawal->amount)) {
                                $withdrawal->markAsProcessed($adminId, $request->bulk_notes);
                                $successCount++;
                            }
                        }
                        break;
                }
            } catch (\Exception $e) {
                // Log error but continue with other withdrawals
                continue;
            }
        }

        $action = ucfirst($request->action);
        return back()->with('success', "{$action}d {$successCount} withdrawal requests successfully.");
    }

    /**
     * Show withdrawal settings
     */
    public function settings()
    {
        $settings = WithdrawalSetting::getActive();
        return view('plugin/dropshipping::admin.withdrawals.settings', compact('settings'));
    }

    /**
     * Update withdrawal settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'minimum_withdrawal_amount' => 'required|numeric|min:0',
            'maximum_withdrawal_amount' => 'nullable|numeric|min:0',
            'withdrawal_fee_percentage' => 'required|numeric|min:0|max:100',
            'withdrawal_fee_fixed' => 'required|numeric|min:0',
            'withdrawal_processing_days' => 'required|integer|min:0|max:30',
            'auto_approve_withdrawals' => 'boolean',
            'withdrawal_terms' => 'nullable|string|max:5000',
            'bank_requirements' => 'nullable|string|max:5000',
        ]);

        $settings = WithdrawalSetting::getActive();

        $settings->update([
            'minimum_withdrawal_amount' => $request->minimum_withdrawal_amount,
            'maximum_withdrawal_amount' => $request->maximum_withdrawal_amount,
            'withdrawal_fee_percentage' => $request->withdrawal_fee_percentage,
            'withdrawal_fee_fixed' => $request->withdrawal_fee_fixed,
            'withdrawal_processing_days' => $request->withdrawal_processing_days,
            'auto_approve_withdrawals' => $request->has('auto_approve_withdrawals'),
            'withdrawal_terms' => $request->withdrawal_terms,
            'bank_requirements' => $request->bank_requirements,
        ]);

        return back()->with('success', 'Withdrawal settings updated successfully.');
    }

    /**
     * Export withdrawal requests to CSV
     */
    public function export(Request $request)
    {
        $query = WithdrawalRequest::with(['requestedBy', 'processedBy']);

        // Apply same filters as index
        if ($request->filled('status') && $request->status !== 'all') {
            $query->byStatus($request->status);
        }

        if ($request->filled('tenant_id')) {
            $query->forTenant($request->tenant_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'LIKE', "%{$search}%")
                    ->orWhere('tenant_id', 'LIKE', "%{$search}%")
                    ->orWhere('account_holder_name', 'LIKE', "%{$search}%");
            });
        }

        $withdrawals = $query->orderBy('created_at', 'desc')->get();

        $filename = 'withdrawal-requests-' . date('Y-m-d-H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($withdrawals) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Request Number',
                'Tenant ID',
                'Amount',
                'Status',
                'Bank Name',
                'Account Number',
                'Account Holder Name',
                'Requested At',
                'Processed At',
                'Processed By'
            ]);

            // CSV rows
            foreach ($withdrawals as $withdrawal) {
                fputcsv($file, [
                    $withdrawal->request_number,
                    $withdrawal->tenant_id,
                    $withdrawal->amount,
                    $withdrawal->status,
                    $withdrawal->bank_name,
                    $withdrawal->account_number,
                    $withdrawal->account_holder_name,
                    $withdrawal->requested_at ? $withdrawal->requested_at->format('Y-m-d H:i:s') : '',
                    $withdrawal->processed_at ? $withdrawal->processed_at->format('Y-m-d H:i:s') : '',
                    $withdrawal->processedBy ? $withdrawal->processedBy->name : '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get withdrawal statistics for dashboard
     */
    public function getStatistics()
    {
        $totalRequests = WithdrawalRequest::count();
        $totalProcessed = WithdrawalRequest::where('status', 'processed')->sum('amount');
        $pendingAmount = WithdrawalRequest::where('status', 'pending')->sum('amount');

        // Monthly statistics (last 12 months)
        $monthlyStats = WithdrawalRequest::select(
            DB::raw('YEAR(created_at) as year'),
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as total_requests'),
            DB::raw('SUM(CASE WHEN status = "processed" THEN amount ELSE 0 END) as total_processed')
        )
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return response()->json([
            'total_requests' => $totalRequests,
            'total_processed' => $totalProcessed,
            'pending_amount' => $pendingAmount,
            'monthly_stats' => $monthlyStats,
        ]);
    }
}
