<?php

namespace Plugin\Dropshipping\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Plugin\Dropshipping\Models\DropshippingOrder;
use Plugin\Dropshipping\Models\TenantBalance;
use Plugin\TlcommerceCore\Models\Product;
use Plugin\Dropshipping\Models\DropshippingProduct;
use Plugin\TlcommerceCore\Repositories\OrderRepository;

class OrderManagementController extends Controller
{
    protected $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * Display the order management dashboard
     */
    public function index()
    {
        $tenantId = tenant('id');

        // Get or create tenant balance
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

        // Get recent orders
        $orders = DropshippingOrder::forTenant($tenantId)
            ->with(['localProduct', 'dropshippingProduct'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get statistics
        $stats = [
            'total_orders' => DropshippingOrder::forTenant($tenantId)->count(),
            'pending_orders' => DropshippingOrder::forTenant($tenantId)->byStatus('pending')->count(),
            'approved_orders' => DropshippingOrder::forTenant($tenantId)->byStatus('approved')->count(),
            'rejected_orders' => DropshippingOrder::forTenant($tenantId)->byStatus('rejected')->count(),
            'total_earnings' => $balance->total_earnings,
            'available_balance' => $balance->available_balance,
            'pending_balance' => $balance->pending_balance,
        ];

        return view('plugin/dropshipping::tenant.order-management.index', compact('orders', 'balance', 'stats'));
    }

    /**
     * Show the order submission form
     */
    public function create()
    {
        $tenantId = tenant('id');

        // Get inhouse orders that can be converted to dropshipping orders
        $request = new Request();
        $inhouseOrders = $this->orderRepository->orderList(
            $request,
            config('tlecommercecore.order_type.home_delivery'),
            'inhouse',
            null
        );

        return view('plugin/dropshipping::tenant.order-management.create', compact('inhouseOrders'));
    }

    /**
     * Convert an existing order to dropshipping
     */
    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:tl_com_orders,id',
            'fulfillment_note' => 'nullable|string',
        ]);

        $tenantId = tenant('id');
        $userId = Auth::id();

        // Get the existing order details
        $existingOrder = $this->orderRepository->orderDetails($request->order_id);
        if (!$existingOrder) {
            return back()->withErrors(['order_id' => 'Order not found.']);
        }

        // Check if this order has already been converted to dropshipping
        $existingDropshipOrder = DropshippingOrder::where('original_order_id', $request->order_id)
            ->where('tenant_id', $tenantId)
            ->first();

        if ($existingDropshipOrder) {
            return back()->withErrors(['order_id' => 'This order has already been converted to dropshipping.']);
        }

        // Calculate pricing based on order total
        $totalAmount = $existingOrder->total_payable_amount;
        $commissionRate = 20; // Default 20% commission for tenant
        $commissionAmount = ($totalAmount * $commissionRate) / 100;
        $tenantEarning = $commissionAmount;

        // Get customer information
        $customerName = $existingOrder->customer->name ?? $existingOrder->guestCustomer->name ?? 'Guest Customer';
        $customerEmail = $existingOrder->customer->email ?? $existingOrder->guestCustomer->email ?? '';
        $customerPhone = $existingOrder->customer->phone ?? $existingOrder->guestCustomer->phone ?? '';

        // Create the dropshipping order
        $order = DropshippingOrder::create([
            'tenant_id' => $tenantId,
            'original_order_id' => $request->order_id,
            'order_code' => $existingOrder->order_code,
            'product_name' => "Order #{$existingOrder->order_code} - Multiple Items",
            'product_sku' => $existingOrder->order_code,
            'quantity' => $existingOrder->products->sum('quantity') ?? 1,
            'unit_price' => $totalAmount,
            'total_amount' => $totalAmount,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'tenant_earning' => $tenantEarning,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'customer_phone' => $customerPhone,
            'shipping_address' => $existingOrder->shipping_address ?? '',
            'fulfillment_note' => $request->fulfillment_note,
            'submitted_by' => $userId,
            'status' => 'pending'
        ]);

        // Update tenant balance with pending earning
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

        $balance->addPendingEarning($tenantEarning);

        return redirect()->route('dropshipping.order.management')
            ->with('success', "Order {$order->order_number} converted to dropshipping successfully. Waiting for admin approval.");
    }

    /**
     * Show order details
     */
    public function show($id)
    {
        $tenantId = tenant('id');

        $order = DropshippingOrder::forTenant($tenantId)
            ->with(['localProduct', 'dropshippingProduct', 'submittedBy', 'approvedBy'])
            ->findOrFail($id);

        return view('plugin/dropshipping::tenant.order-management.show', compact('order'));
    }

    /**
     * Cancel an order (if it's still pending)
     */
    public function cancel($id)
    {
        $tenantId = tenant('id');

        $order = DropshippingOrder::forTenant($tenantId)->findOrFail($id);

        if (!$order->canBeCancelled()) {
            return back()->withErrors(['message' => 'This order cannot be cancelled.']);
        }

        $order->update(['status' => 'cancelled']);

        // Update tenant balance - remove pending earning
        $balance = TenantBalance::forTenant($tenantId)->first();
        if ($balance) {
            $balance->decrement('pending_balance', $order->tenant_earning);
            $balance->decrement('pending_orders');
        }

        return back()->with('success', 'Order cancelled successfully.');
    }

    /**
     * Get product details for order form (AJAX)
     */
    public function getProductDetails($productId)
    {
        $tenantId = tenant('id');

        $product = Product::with(['single_price'])->findOrFail($productId);

        // Verify this product was imported by the tenant
        $importHistory = DB::table('dropshipping_product_import_history')
            ->where('tenant_id', $tenantId)
            ->where('local_product_id', $productId)
            ->where('status', 'completed')
            ->first();

        if (!$importHistory) {
            return response()->json(['error' => 'Product not found or not imported by your store'], 404);
        }

        return response()->json([
            'id' => $product->id,
            'name' => $product->translation('name'),
            'sku' => $product->sku,
            'price' => $product->single_price->unit_price ?? 0,
            'description' => $product->translation('description'),
        ]);
    }
}
