<?php

namespace Plugin\Dropshipping\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Plugin\Dropshipping\Models\DropshippingOrder;
use Plugin\Dropshipping\Models\TenantBalance;

class OrderManagementController extends Controller
{
    /**
     * Display all dropshipping orders for admin
     */
    public function index(Request $request)
    {
        $query = DropshippingOrder::with(['localProduct', 'dropshippingProduct', 'submittedBy', 'approvedBy']);

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->byStatus($request->status);
        }

        // Filter by tenant
        if ($request->filled('tenant_id')) {
            $query->forTenant($request->tenant_id);
        }

        // Search by order number or product name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'LIKE', "%{$search}%")
                    ->orWhere('product_name', 'LIKE', "%{$search}%")
                    ->orWhere('customer_name', 'LIKE', "%{$search}%");
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get statistics
        $stats = [
            'total_orders' => DropshippingOrder::count(),
            'pending_orders' => DropshippingOrder::byStatus('pending')->count(),
            'approved_orders' => DropshippingOrder::byStatus('approved')->count(),
            'rejected_orders' => DropshippingOrder::byStatus('rejected')->count(),
            'shipped_orders' => DropshippingOrder::byStatus('shipped')->count(),
            'delivered_orders' => DropshippingOrder::byStatus('delivered')->count(),
        ];

        // Get unique tenants for filter
        $tenants = DropshippingOrder::select('tenant_id')
            ->distinct()
            ->get()
            ->pluck('tenant_id');

        return view('plugin/dropshipping::admin.order-management.index', compact('orders', 'stats', 'tenants'));
    }

    /**
     * Show order details
     */
    public function show($id)
    {
        $order = DropshippingOrder::with([
            'localProduct',
            'dropshippingProduct',
            'submittedBy',
            'approvedBy'
        ])->findOrFail($id);

        return view('plugin/dropshipping::admin.order-management.show', compact('order'));
    }

    /**
     * Approve an order
     */
    public function approve(Request $request, $id)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $order = DropshippingOrder::findOrFail($id);

        if (!$order->canBeApproved()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'This order cannot be approved.']);
            }
            return back()->withErrors(['message' => 'This order cannot be approved.']);
        }

        $adminId = Auth::id();
        $order->approve($adminId, $request->admin_notes);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Order {$order->order_number} approved successfully. Tenant balance has been updated."
            ]);
        }

        return back()->with('success', "Order {$order->order_number} approved successfully. Tenant balance has been updated.");
    }

    /**
     * Reject an order
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
            'reason' => 'required_without:rejection_reason|string|max:1000', // For AJAX calls
        ]);

        $order = DropshippingOrder::findOrFail($id);

        if ($order->status !== 'pending') {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Only pending orders can be rejected.']);
            }
            return back()->withErrors(['message' => 'Only pending orders can be rejected.']);
        }

        $adminId = Auth::id();
        $rejectionReason = $request->rejection_reason ?? $request->reason;
        $order->reject($adminId, $rejectionReason);

        // Update tenant balance - remove pending earning
        $balance = TenantBalance::forTenant($order->tenant_id)->first();
        if ($balance) {
            $balance->decrement('pending_balance', $order->tenant_earning);
            $balance->decrement('pending_orders');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Order {$order->order_number} rejected. Tenant has been notified."
            ]);
        }

        return back()->with('success', "Order {$order->order_number} rejected. Tenant has been notified.");
    }

    /**
     * Update order status (processing, shipped, delivered)
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:processing,shipped,delivered',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $order = DropshippingOrder::findOrFail($id);

        if (!in_array($order->status, ['approved', 'processing', 'shipped'])) {
            return back()->withErrors(['message' => 'Order status cannot be updated from current state.']);
        }

        $updates = [
            'status' => $request->status,
            'admin_notes' => $request->admin_notes,
        ];

        // Add timestamp for specific statuses
        if ($request->status === 'shipped' && $order->status !== 'shipped') {
            $updates['shipped_at'] = now();
        } elseif ($request->status === 'delivered' && $order->status !== 'delivered') {
            $updates['delivered_at'] = now();
        }

        $order->update($updates);

        return back()->with('success', "Order {$order->order_number} status updated to {$request->status}.");
    }

    /**
     * Bulk operations on orders
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:approve,reject,delete',
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:dropshipping_orders,id',
            'bulk_notes' => 'nullable|string|max:1000',
            'bulk_rejection_reason' => 'required_if:action,reject|string|max:1000',
        ]);

        $adminId = Auth::id();
        $orderIds = $request->order_ids;
        $successCount = 0;

        foreach ($orderIds as $orderId) {
            $order = DropshippingOrder::find($orderId);
            if (!$order) continue;

            try {
                switch ($request->action) {
                    case 'approve':
                        if ($order->canBeApproved()) {
                            $order->approve($adminId, $request->bulk_notes);
                            $successCount++;
                        }
                        break;

                    case 'reject':
                        if ($order->status === 'pending') {
                            $order->reject($adminId, $request->bulk_rejection_reason);

                            // Update tenant balance
                            $balance = TenantBalance::forTenant($order->tenant_id)->first();
                            if ($balance) {
                                $balance->decrement('pending_balance', $order->tenant_earning);
                                $balance->decrement('pending_orders');
                            }
                            $successCount++;
                        }
                        break;

                    case 'delete':
                        if (in_array($order->status, ['cancelled', 'rejected'])) {
                            $order->delete();
                            $successCount++;
                        }
                        break;
                }
            } catch (\Exception $e) {
                // Log error but continue with other orders
                continue;
            }
        }

        $action = ucfirst($request->action);
        return back()->with('success', "{$action}d {$successCount} orders successfully.");
    }

    /**
     * Export orders to CSV
     */
    public function export(Request $request)
    {
        $query = DropshippingOrder::with(['localProduct', 'dropshippingProduct', 'submittedBy']);

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
                $q->where('order_number', 'LIKE', "%{$search}%")
                    ->orWhere('product_name', 'LIKE', "%{$search}%")
                    ->orWhere('customer_name', 'LIKE', "%{$search}%");
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        $filename = 'dropshipping-orders-' . date('Y-m-d-H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');

            // CSV headers
            fputcsv($file, [
                'Order Number',
                'Tenant ID',
                'Product Name',
                'SKU',
                'Quantity',
                'Unit Price',
                'Total Amount',
                'Commission',
                'Tenant Earning',
                'Customer Name',
                'Customer Email',
                'Status',
                'Submitted At',
                'Approved At',
                'Shipped At',
                'Delivered At'
            ]);

            // CSV rows
            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->order_number,
                    $order->tenant_id,
                    $order->product_name,
                    $order->product_sku,
                    $order->quantity,
                    $order->unit_price,
                    $order->total_amount,
                    $order->commission_amount,
                    $order->tenant_earning,
                    $order->customer_name,
                    $order->customer_email,
                    $order->status,
                    $order->submitted_at ? $order->submitted_at->format('Y-m-d H:i:s') : '',
                    $order->approved_at ? $order->approved_at->format('Y-m-d H:i:s') : '',
                    $order->shipped_at ? $order->shipped_at->format('Y-m-d H:i:s') : '',
                    $order->delivered_at ? $order->delivered_at->format('Y-m-d H:i:s') : '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get order statistics for dashboard
     */
    public function getStatistics()
    {
        $totalOrders = DropshippingOrder::count();
        $totalEarnings = DropshippingOrder::where('status', 'approved')->sum('commission_amount');
        $totalTenantEarnings = DropshippingOrder::where('status', 'approved')->sum('tenant_earning');

        // Recent orders (last 30 days)
        $recentOrders = DropshippingOrder::where('created_at', '>=', now()->subDays(30))->count();

        // Orders by status
        $ordersByStatus = DropshippingOrder::select('status')
            ->selectRaw('count(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Monthly statistics (last 12 months)
        $monthlyStats = DropshippingOrder::select(
            DB::raw('YEAR(created_at) as year'),
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as total_orders'),
            DB::raw('SUM(CASE WHEN status = "approved" THEN commission_amount ELSE 0 END) as total_commission')
        )
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return response()->json([
            'total_orders' => $totalOrders,
            'total_earnings' => $totalEarnings,
            'total_tenant_earnings' => $totalTenantEarnings,
            'recent_orders' => $recentOrders,
            'orders_by_status' => $ordersByStatus,
            'monthly_stats' => $monthlyStats,
        ]);
    }
}
