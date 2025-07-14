<?php

namespace Plugin\Dropshipping\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Plugin\Dropshipping\Models\DropshippingOrder;
use Plugin\Dropshipping\Models\TenantBalance;

class OrderManagementController extends Controller
{
    /**
     * Display all dropshipping orders for admin
     */
    public function index(Request $request)
    {
        try {
            // Initialize collections for aggregating data
            $allOrders = collect();
            $tenantDatabases = $this->getAllTenantDatabases();
            $connectionErrors = [];

            // Add debug info
            Log::info("OrderManagementController: Found " . count($tenantDatabases) . " tenant databases");

            foreach ($tenantDatabases as $tenantDb) {
                try {
                    // Get tenant connection
                    $connectionName = $this->getTenantConnection($tenantDb);

                    if (!$connectionName) {
                        $connectionErrors[] = "Failed to connect to {$tenantDb}";
                        continue;
                    }

                    // Check if dropshipping_orders table exists, create if not
                    if (!DB::connection($connectionName)->getSchemaBuilder()->hasTable('dropshipping_orders')) {
                        Log::info("Creating dropshipping_orders table in {$tenantDb}");
                        $this->createDropshippingOrdersTable($connectionName);
                    }

                    // Query orders from this tenant database
                    $query = DropshippingOrder::on($connectionName);

                    // Apply filters
                    if ($request->filled('status') && $request->status !== 'all') {
                        $query->where('status', $request->status);
                    }

                    if ($request->filled('tenant_id')) {
                        $query->where('tenant_id', $request->tenant_id);
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

                    // Add tenant database info to each order
                    foreach ($orders as $order) {
                        $order->tenant_database = $tenantDb;
                        $order->connection_name = $connectionName;
                    }

                    $allOrders = $allOrders->merge($orders);

                    Log::info("Found " . $orders->count() . " orders in {$tenantDb}");
                } catch (\Exception $e) {
                    Log::error("Error querying tenant database {$tenantDb}: " . $e->getMessage());
                    $connectionErrors[] = "Error in {$tenantDb}: " . $e->getMessage();
                    continue;
                }
            }

            Log::info("Total orders found: " . $allOrders->count());

            // Sort all orders by created_at descending
            $allOrders = $allOrders->sortByDesc('created_at');

            // Manual pagination
            $page = $request->get('page', 1);
            $perPage = 20;
            $offset = ($page - 1) * $perPage;
            $paginatedOrders = $allOrders->slice($offset, $perPage)->values();

            // Create paginator
            $orders = new LengthAwarePaginator(
                $paginatedOrders,
                $allOrders->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            // Calculate statistics
            $stats = [
                'total_orders' => $allOrders->count(),
                'pending_orders' => $allOrders->where('status', 'pending')->count(),
                'approved_orders' => $allOrders->where('status', 'approved')->count(),
                'rejected_orders' => $allOrders->where('status', 'rejected')->count(),
                'shipped_orders' => $allOrders->where('status', 'shipped')->count(),
                'delivered_orders' => $allOrders->where('status', 'delivered')->count(),
            ];

            // Get unique tenants for filter
            $tenants = $allOrders->pluck('tenant_id')->unique()->sort()->values();

            // If no orders found, show debug info
            if ($allOrders->isEmpty()) {
                Log::warning("No orders found. Connection errors: " . implode(', ', $connectionErrors));
                // You can uncomment the line below to show debug info in the view
                // session()->flash('debug_info', 'No orders found. Checked ' . count($tenantDatabases) . ' databases. Errors: ' . implode(', ', $connectionErrors));
            }

            return view('plugin/dropshipping::admin.order-management.index', compact('orders', 'stats', 'tenants'));
        } catch (\Exception $e) {
            Log::error("Error in OrderManagementController index: " . $e->getMessage() . "\n" . $e->getTraceAsString());

            // Return empty data to prevent page crash
            $orders = new LengthAwarePaginator([], 0, 20, 1, ['path' => $request->url()]);
            $stats = [
                'total_orders' => 0,
                'pending_orders' => 0,
                'approved_orders' => 0,
                'rejected_orders' => 0,
                'shipped_orders' => 0,
                'delivered_orders' => 0,
            ];
            $tenants = collect();

            session()->flash('error', 'Error loading orders: ' . $e->getMessage());
            return view('plugin/dropshipping::admin.order-management.index', compact('orders', 'stats', 'tenants'));
        }
    }

    /**
     * Show order details
     */
    public function show($id)
    {
        try {
            Log::info("Attempting to show order with ID: {$id}");

            // Use the simple method with enhanced shipping information loading
            $order = $this->findOrderSimple($id);

            if (!$order) {
                Log::warning("Order {$id} not found in any tenant database");
                return redirect()->route('admin.dropshipping.orders.index')
                    ->with('error', "Order {$id} not found in any tenant database.");
            }

            Log::info("Order {$id} found successfully in database: " . ($order->tenant_database ?? 'unknown'));

            // Add some debug info to the order object
            $order->debug_info = [
                'found_in_database' => $order->tenant_database ?? 'unknown',
                'connection_name' => $order->connection_name ?? 'unknown',
                'has_shipping_info' => isset($order->shipping_info),
                'has_billing_info' => isset($order->billing_info),
                'has_payment_info' => isset($order->payment_info),
                'has_original_order' => isset($order->original_order),
                'shipping_address_id' => $order->original_order->shipping_address ?? 'not available',
                'billing_address_id' => $order->original_order->billing_address ?? 'not available'
            ];

            Log::info("Order {$id} debug info: " . json_encode($order->debug_info));

            try {
                return view('plugin/dropshipping::admin.order-management.show', compact('order'));
            } catch (\Exception $viewException) {
                Log::error("Error loading view for order {$id}: " . $viewException->getMessage());

                // Return a simple debug view instead
                return response()->json([
                    'success' => true,
                    'message' => 'Order found but view failed to load',
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_name,
                    'status' => $order->status,
                    'debug_info' => $order->debug_info,
                    'view_error' => $viewException->getMessage()
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Error in show method for order {$id}: " . $e->getMessage() . "\n" . $e->getTraceAsString());

            return redirect()->route('admin.dropshipping.orders.index')
                ->with('error', "Error loading order details: " . $e->getMessage());
        }
    }

    /**
     * Find order with enhanced information (shipping, billing, payment) - made public for debugging
     */
    public function findOrderInTenantDatabasesEnhanced($orderId)
    {
        try {
            $tenants = DB::connection('mysql')->table('tenants')->get();
            Log::info("Searching for order {$orderId} across " . $tenants->count() . " tenant databases");

            $searchedDatabases = [];
            $connectionErrors = [];

            foreach ($tenants as $tenant) {
                $tenantData = json_decode($tenant->data, true);
                if (isset($tenantData['tenancy_db_name'])) {
                    $database = $tenantData['tenancy_db_name'];
                    $searchedDatabases[] = $database;
                    Log::debug("Checking tenant database: {$database}");

                    try {
                        $connectionName = 'tenant_' . $database;
                        $tenantConfig = config('database.connections.mysql');
                        $tenantConfig['database'] = $database;
                        config(["database.connections.$connectionName" => $tenantConfig]);

                        // Test connection
                        DB::connection($connectionName)->getPdo();
                        Log::debug("Successfully connected to {$database}");

                        // Check if table exists
                        if (!DB::connection($connectionName)->getSchemaBuilder()->hasTable('dropshipping_orders')) {
                            Log::debug("dropshipping_orders table does not exist in {$database}");
                            $connectionErrors[] = "{$database}: table does not exist";
                            continue;
                        }

                        Log::debug("dropshipping_orders table exists in {$database}, searching for order {$orderId}");

                        // First check if order exists with raw query for debugging
                        $orderCount = DB::connection($connectionName)->table('dropshipping_orders')
                            ->where('id', $orderId)
                            ->count();

                        Log::debug("Found {$orderCount} orders with ID {$orderId} in {$database}");

                        if ($orderCount > 0) {
                            // Get the order using raw query first
                            $rawOrder = DB::connection($connectionName)->table('dropshipping_orders')
                                ->where('id', $orderId)
                                ->first();

                            Log::debug("Raw order data: " . json_encode($rawOrder));

                            // Now try with Eloquent
                            $order = DropshippingOrder::on($connectionName)->find($orderId);

                            if ($order) {
                                Log::info("Found order {$orderId} in database {$database}");

                                // Store connection info
                                $order->connection_name = $connectionName;
                                $order->tenant_database = $database;

                                // Load user information from main database
                                if ($order->submitted_by) {
                                    $order->submitted_user = DB::connection('mysql')->table('users')
                                        ->where('id', $order->submitted_by)
                                        ->first();
                                }

                                if ($order->approved_by) {
                                    $order->approved_user = DB::connection('mysql')->table('users')
                                        ->where('id', $order->approved_by)
                                        ->first();
                                }

                                // Get original order details for shipping, billing, payment info
                                if ($order->original_order_id) {
                                    Log::debug("Loading original order details for order {$orderId}");

                                    $originalOrder = DB::connection($connectionName)->table('tl_com_orders')
                                        ->where('id', $order->original_order_id)
                                        ->first();

                                    if ($originalOrder) {
                                        $order->original_order = $originalOrder;

                                        // Get shipping address with related data
                                        if ($originalOrder->shipping_address) {
                                            $order->shipping_info = DB::connection($connectionName)->table('tl_com_customer_address')
                                                ->leftJoin('tl_countries', 'tl_com_customer_address.country_id', '=', 'tl_countries.id')
                                                ->leftJoin('tl_com_state', 'tl_com_customer_address.state_id', '=', 'tl_com_state.id')
                                                ->leftJoin('tl_com_cities', 'tl_com_customer_address.city_id', '=', 'tl_com_cities.id')
                                                ->where('tl_com_customer_address.id', $originalOrder->shipping_address)
                                                ->select(
                                                    'tl_com_customer_address.*',
                                                    'tl_countries.name as country',
                                                    'tl_com_state.name as state',
                                                    'tl_com_cities.name as city'
                                                )
                                                ->first();
                                        }

                                        // Get billing address with related data
                                        if ($originalOrder->billing_address) {
                                            $order->billing_info = DB::connection($connectionName)->table('tl_com_customer_address')
                                                ->leftJoin('tl_countries', 'tl_com_customer_address.country_id', '=', 'tl_countries.id')
                                                ->leftJoin('tl_com_state', 'tl_com_customer_address.state_id', '=', 'tl_com_state.id')
                                                ->leftJoin('tl_com_cities', 'tl_com_customer_address.city_id', '=', 'tl_com_cities.id')
                                                ->where('tl_com_customer_address.id', $originalOrder->billing_address)
                                                ->select(
                                                    'tl_com_customer_address.*',
                                                    'tl_countries.name as country',
                                                    'tl_com_state.name as state',
                                                    'tl_com_cities.name as city'
                                                )
                                                ->first();
                                        }

                                        // Get payment method
                                        if ($originalOrder->payment_method) {
                                            $order->payment_info = DB::connection($connectionName)->table('tl_com_payment_methods')
                                                ->where('id', $originalOrder->payment_method)
                                                ->first();
                                        }

                                        // Get customer info (regular or guest)
                                        if ($originalOrder->customer_id) {
                                            $order->customer_info = DB::connection($connectionName)->table('tl_com_customers')
                                                ->where('id', $originalOrder->customer_id)
                                                ->first();
                                        } else {
                                            // Check for guest customer
                                            $order->guest_customer_info = DB::connection($connectionName)->table('tl_com_guest_customer')
                                                ->where('order_id', $originalOrder->id)
                                                ->first();
                                        }

                                        // Get order products for detailed info
                                        $order->order_products = DB::connection($connectionName)->table('tl_com_ordered_products')
                                            ->where('order_id', $originalOrder->id)
                                            ->get();

                                        Log::info("Successfully loaded enhanced order information for order {$orderId}");
                                    }
                                }

                                return $order;
                            } else {
                                Log::warning("Raw query found order but Eloquent didn't find order {$orderId} in {$database}");
                                $connectionErrors[] = "{$database}: found with raw query but not with Eloquent";
                            }
                        } else {
                            Log::debug("No order with ID {$orderId} found in {$database}");
                        }
                    } catch (\Exception $e) {
                        Log::warning("Error accessing tenant database {$database}: " . $e->getMessage());
                        $connectionErrors[] = "{$database}: " . $e->getMessage();
                        continue;
                    }
                }
            }

            Log::warning("Order {$orderId} not found in any tenant database");
            Log::info("Searched databases: " . implode(', ', $searchedDatabases));
            Log::info("Connection errors: " . implode(', ', $connectionErrors));

            return null;
        } catch (\Exception $e) {
            Log::error("Error in findOrderInTenantDatabasesEnhanced: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Simple order finding method that works (same as the successful simple route)
     */
    private function findOrderSimple($id)
    {
        try {
            Log::info("findOrderSimple: Starting search for order {$id}");
            $tenants = DB::connection('mysql')->table('tenants')->get();
            Log::info("findOrderSimple: Found " . count($tenants) . " tenants");

            foreach ($tenants as $tenant) {
                $tenantData = json_decode($tenant->data, true);
                $database = $tenantData['tenancy_db_name'] ?? null;

                if (!$database) continue;

                try {
                    Log::info("findOrderSimple: Checking database {$database}");
                    $connectionName = 'tenant_' . $database;
                    $tenantConfig = config('database.connections.mysql');
                    $tenantConfig['database'] = $database;
                    config(["database.connections.$connectionName" => $tenantConfig]);

                    // Test connection
                    DB::connection($connectionName)->getPdo();

                    // Check if table exists
                    if (!DB::connection($connectionName)->getSchemaBuilder()->hasTable('dropshipping_orders')) {
                        Log::info("findOrderSimple: dropshipping_orders table not found in {$database}");
                        continue;
                    }

                    // Get the basic order
                    $order = DB::connection($connectionName)->table('dropshipping_orders')
                        ->where('id', $id)
                        ->first();

                    if ($order) {
                        Log::info("findOrderSimple: Found order {$id} in database {$database}");

                        // Convert to object with additional properties
                        $orderObj = (object) $order;
                        $orderObj->connection_name = $connectionName;
                        $orderObj->tenant_database = $database;

                        // Get additional order details if original_order_id exists
                        if ($order->original_order_id) {
                            Log::info("findOrderSimple: Loading original order {$order->original_order_id}");

                            // Get original order details
                            $originalOrder = DB::connection($connectionName)->table('tl_com_orders')
                                ->where('id', $order->original_order_id)
                                ->first();

                            if ($originalOrder) {
                                Log::info("findOrderSimple: Found original order, shipping_address: " . ($originalOrder->shipping_address ?? 'null'));
                                $orderObj->original_order = $originalOrder;

                                // Get shipping address with related data
                                if ($originalOrder->shipping_address) {
                                    Log::info("findOrderSimple: Loading shipping address {$originalOrder->shipping_address}");
                                    $shippingInfo = DB::connection($connectionName)->table('tl_com_customer_address')
                                        ->leftJoin('tl_countries', 'tl_com_customer_address.country_id', '=', 'tl_countries.id')
                                        ->leftJoin('tl_com_state', 'tl_com_customer_address.state_id', '=', 'tl_com_state.id')
                                        ->leftJoin('tl_com_cities', 'tl_com_customer_address.city_id', '=', 'tl_com_cities.id')
                                        ->where('tl_com_customer_address.id', $originalOrder->shipping_address)
                                        ->select(
                                            'tl_com_customer_address.*',
                                            'tl_countries.name as country',
                                            'tl_com_state.name as state',
                                            'tl_com_cities.name as city'
                                        )
                                        ->first();
                                    if ($shippingInfo) {
                                        Log::info("findOrderSimple: Loaded shipping info - City: " . ($shippingInfo->city ?? 'null') . ", State: " . ($shippingInfo->state ?? 'null') . ", Country: " . ($shippingInfo->country ?? 'null'));
                                        $orderObj->shipping_info = $shippingInfo;
                                    } else {
                                        Log::warning("findOrderSimple: No shipping info found for address ID {$originalOrder->shipping_address}");
                                    }
                                }

                                // Get billing address with related data
                                if ($originalOrder->billing_address) {
                                    Log::info("findOrderSimple: Loading billing address {$originalOrder->billing_address}");
                                    $billingInfo = DB::connection($connectionName)->table('tl_com_customer_address')
                                        ->leftJoin('tl_countries', 'tl_com_customer_address.country_id', '=', 'tl_countries.id')
                                        ->leftJoin('tl_com_state', 'tl_com_customer_address.state_id', '=', 'tl_com_state.id')
                                        ->leftJoin('tl_com_cities', 'tl_com_customer_address.city_id', '=', 'tl_com_cities.id')
                                        ->where('tl_com_customer_address.id', $originalOrder->billing_address)
                                        ->select(
                                            'tl_com_customer_address.*',
                                            'tl_countries.name as country',
                                            'tl_com_state.name as state',
                                            'tl_com_cities.name as city'
                                        )
                                        ->first();
                                    if ($billingInfo) {
                                        $orderObj->billing_info = $billingInfo;
                                    }
                                }

                                // Get customer info
                                if ($originalOrder->customer_id) {
                                    $customerInfo = DB::connection($connectionName)->table('tl_com_customers')
                                        ->where('id', $originalOrder->customer_id)
                                        ->first();
                                    if ($customerInfo) {
                                        $orderObj->customer_info = $customerInfo;
                                    }
                                } else {
                                    // Check for guest customer
                                    $guestCustomer = DB::connection($connectionName)->table('tl_com_guest_customer')
                                        ->where('order_id', $originalOrder->id)
                                        ->first();
                                    if ($guestCustomer) {
                                        $orderObj->guest_customer_info = $guestCustomer;
                                    }
                                }

                                // Get order products
                                $orderProducts = DB::connection($connectionName)->table('tl_com_ordered_products')
                                    ->where('order_id', $originalOrder->id)
                                    ->get();
                                if ($orderProducts) {
                                    $orderObj->order_products = $orderProducts;
                                }
                            } else {
                                Log::warning("findOrderSimple: Original order {$order->original_order_id} not found");
                            }
                        } else {
                            Log::info("findOrderSimple: No original_order_id for order {$id}");
                        }

                        return $orderObj;
                    }
                } catch (\Exception $e) {
                    Log::warning("Error accessing tenant database {$database}: " . $e->getMessage());
                    continue;
                }
            }

            Log::warning("findOrderSimple: Order {$id} not found in any tenant database");
            return null;
        } catch (\Exception $e) {
            Log::error("Error in findOrderSimple: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Find an order across all tenant databases
     */
    private function findOrderInTenantDatabases($orderId)
    {
        $tenantDatabases = $this->getAllTenantDatabases();

        foreach ($tenantDatabases as $tenantDb) {
            try {
                $connectionName = 'tenant_' . $tenantDb;
                $tenantConfig = config('database.connections.mysql');
                $tenantConfig['database'] = $tenantDb;
                config(["database.connections.$connectionName" => $tenantConfig]);

                // Test connection
                DB::connection($connectionName)->getPdo();

                // Check if table exists
                if (!DB::connection($connectionName)->getSchemaBuilder()->hasTable('dropshipping_orders')) {
                    continue;
                }

                $order = DropshippingOrder::on($connectionName)->find($orderId);

                if ($order) {
                    // Store the connection name for later use
                    $order->connection_name = $connectionName;

                    // Manually load user relationships from main database
                    if ($order->submitted_by) {
                        $order->submitted_user = DB::connection('mysql')->table('users')
                            ->where('id', $order->submitted_by)
                            ->first();
                    }

                    if ($order->approved_by) {
                        $order->approved_user = DB::connection('mysql')->table('users')
                            ->where('id', $order->approved_by)
                            ->first();
                    }

                    return $order;
                }
            } catch (\Exception $e) {
                // Log error but continue searching other databases
                continue;
            }
        }

        return null;
    }

    /**
     * Approve an order
     */
    public function approve(Request $request, $id)
    {
        try {
            Log::info("Approve method called for order ID: {$id}");

            $request->validate([
                'admin_notes' => 'nullable|string|max:1000',
            ]);

            $order = $this->findOrderSimple($id);

            if (!$order) {
                Log::warning("Order {$id} not found for approval");
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Order not found.']);
                }
                return back()->withErrors(['message' => 'Order not found.']);
            }

            Log::info("Order {$id} found, checking if it can be approved");

            // Convert object to DropshippingOrder model for methods
            $orderModel = new DropshippingOrder();
            $orderModel->fill((array) $order);
            $orderModel->status = $order->status;

            if ($order->status !== 'pending') {
                Log::warning("Order {$id} cannot be approved. Current status: {$order->status}");
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'This order cannot be approved.']);
                }
                return back()->withErrors(['message' => 'This order cannot be approved.']);
            }

            $adminId = Auth::id();
            Log::info("Approving order {$id} by admin {$adminId}");

            // Update the order directly using the tenant connection
            $connectionName = $order->connection_name;
            $updated = DB::connection($connectionName)->table('dropshipping_orders')
                ->where('id', $id)
                ->update([
                    'status' => 'approved',
                    'approved_by' => $adminId,
                    'approved_at' => now(),
                    'admin_notes' => $request->admin_notes,
                    'updated_at' => now()
                ]);

            if ($updated) {
                Log::info("Order {$id} approved successfully");

                // Update tenant balance
                try {
                    $balance = DB::connection($connectionName)->table('tenant_balances')
                        ->where('tenant_id', $order->tenant_id)
                        ->first();

                    if ($balance) {
                        DB::connection($connectionName)->table('tenant_balances')
                            ->where('tenant_id', $order->tenant_id)
                            ->update([
                                'available_balance' => $balance->available_balance + $order->tenant_earning,
                                'pending_balance' => $balance->pending_balance - $order->tenant_earning,
                                'approved_orders' => $balance->approved_orders + 1,
                                'pending_orders' => $balance->pending_orders - 1,
                                'updated_at' => now()
                            ]);
                    }
                } catch (\Exception $balanceError) {
                    Log::warning("Failed to update tenant balance for order {$id}: " . $balanceError->getMessage());
                }

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => "Order {$order->order_number} approved successfully. Tenant balance has been updated."
                    ]);
                }

                return back()->with('success', "Order {$order->order_number} approved successfully. Tenant balance has been updated.");
            } else {
                Log::error("Failed to update order {$id} in database");
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Failed to approve order.']);
                }
                return back()->withErrors(['message' => 'Failed to approve order.']);
            }
        } catch (\Exception $e) {
            Log::error("Error in approve method for order {$id}: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());

            $errorMessage = 'Error approving order: ' . $e->getMessage();

            // Add more specific error context
            if (strpos($e->getMessage(), 'not found') !== false) {
                $errorMessage = "Order with ID {$id} not found. Please check if the order exists.";
            } elseif (strpos($e->getMessage(), 'CSRF') !== false) {
                $errorMessage = "Security token mismatch. Please refresh the page and try again.";
            } elseif (strpos($e->getMessage(), 'Connection') !== false) {
                $errorMessage = "Database connection error. Please contact system administrator.";
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'error_details' => $e->getMessage(),
                    'order_id' => $id
                ]);
            }
            return back()->withErrors(['message' => $errorMessage]);
        }
    }

    /**
     * Reject an order
     */
    public function reject(Request $request, $id)
    {
        try {
            Log::info("Reject method called for order ID: {$id}");

            $request->validate([
                'rejection_reason' => 'nullable|string|max:1000',
                'reason' => 'required_without:rejection_reason|string|max:1000', // For AJAX calls
            ]);

            $order = $this->findOrderSimple($id);

            if (!$order) {
                Log::warning("Order {$id} not found for rejection");
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Order not found.']);
                }
                return back()->withErrors(['message' => 'Order not found.']);
            }

            if ($order->status !== 'pending') {
                Log::warning("Order {$id} cannot be rejected. Current status: {$order->status}");
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Only pending orders can be rejected.']);
                }
                return back()->withErrors(['message' => 'Only pending orders can be rejected.']);
            }

            $adminId = Auth::id();
            $rejectionReason = $request->rejection_reason ?? $request->reason;

            if (empty($rejectionReason)) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Rejection reason is required.']);
                }
                return back()->withErrors(['message' => 'Rejection reason is required.']);
            }

            Log::info("Rejecting order {$id} by admin {$adminId}");

            // Update the order directly using the tenant connection
            $connectionName = $order->connection_name;
            $updated = DB::connection($connectionName)->table('dropshipping_orders')
                ->where('id', $id)
                ->update([
                    'status' => 'rejected',
                    'approved_by' => $adminId,
                    'rejection_reason' => $rejectionReason,
                    'updated_at' => now()
                ]);

            if ($updated) {
                Log::info("Order {$id} rejected successfully");

                // Update tenant balance - remove pending earning
                try {
                    $balance = DB::connection($connectionName)->table('tenant_balances')
                        ->where('tenant_id', $order->tenant_id)
                        ->first();

                    if ($balance) {
                        DB::connection($connectionName)->table('tenant_balances')
                            ->where('tenant_id', $order->tenant_id)
                            ->update([
                                'pending_balance' => $balance->pending_balance - $order->tenant_earning,
                                'pending_orders' => $balance->pending_orders - 1,
                                'updated_at' => now()
                            ]);
                    }
                } catch (\Exception $balanceError) {
                    Log::warning("Failed to update tenant balance for rejected order {$id}: " . $balanceError->getMessage());
                }

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => "Order {$order->order_number} rejected. Tenant has been notified."
                    ]);
                }

                return back()->with('success', "Order {$order->order_number} rejected. Tenant has been notified.");
            } else {
                Log::error("Failed to update order {$id} in database");
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Failed to reject order.']);
                }
                return back()->withErrors(['message' => 'Failed to reject order.']);
            }
        } catch (\Exception $e) {
            Log::error("Error in reject method for order {$id}: " . $e->getMessage());
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error rejecting order: ' . $e->getMessage()]);
            }
            return back()->withErrors(['message' => 'Error rejecting order: ' . $e->getMessage()]);
        }
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

        $order = $this->findOrderInTenantDatabases($id);

        if (!$order) {
            return back()->withErrors(['message' => 'Order not found.']);
        }

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

        // Use the correct tenant connection for the operation
        $tenantOrder = DropshippingOrder::on($order->connection_name)->find($id);
        $tenantOrder->update($updates);

        return back()->with('success', "Order {$tenantOrder->order_number} status updated to {$request->status}.");
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

    /**
     * Get all tenant database names
     */
    private function getAllTenantDatabases()
    {
        try {
            // Get all tenants from the main database
            $tenants = DB::connection('mysql')->table('tenants')->get();
            $databases = [];

            foreach ($tenants as $tenant) {
                $tenantData = json_decode($tenant->data, true);
                if (isset($tenantData['tenancy_db_name'])) {
                    $databases[] = $tenantData['tenancy_db_name'];
                }
            }

            return $databases;
        } catch (\Exception $e) {
            Log::error("Failed to get tenant databases: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get connection to a specific tenant database
     */
    private function getTenantConnection($databaseName)
    {
        try {
            $connectionName = 'tenant_' . $databaseName;

            // Check if connection already exists
            if (!array_key_exists($connectionName, config('database.connections'))) {
                $tenantConfig = config('database.connections.mysql');
                $tenantConfig['database'] = $databaseName;
                config(["database.connections.$connectionName" => $tenantConfig]);
            }

            // Test the connection
            DB::connection($connectionName)->getPdo();

            return $connectionName;
        } catch (\Exception $e) {
            Log::error("Failed to connect to tenant database {$databaseName}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create dropshipping_orders table in tenant database
     */
    private function createDropshippingOrdersTable($connectionName)
    {
        try {
            DB::connection($connectionName)->statement("
                CREATE TABLE IF NOT EXISTS `dropshipping_orders` (
                    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `tenant_id` varchar(255) NOT NULL,
                    `order_number` varchar(255) NOT NULL,
                    `original_order_id` bigint(20) UNSIGNED NULL,
                    `order_code` varchar(255) NULL,
                    `local_product_id` bigint(20) UNSIGNED NULL,
                    `dropshipping_product_id` bigint(20) UNSIGNED NULL,
                    `product_name` varchar(255) NOT NULL,
                    `product_sku` varchar(255) NULL,
                    `quantity` int(11) NOT NULL,
                    `unit_price` decimal(10,2) NOT NULL,
                    `total_amount` decimal(10,2) NOT NULL,
                    `commission_rate` decimal(5,2) NOT NULL DEFAULT 20.00,
                    `commission_amount` decimal(10,2) NOT NULL,
                    `tenant_earning` decimal(10,2) NOT NULL,
                    `customer_name` varchar(255) NOT NULL,
                    `customer_email` varchar(255) NULL,
                    `customer_phone` varchar(255) NULL,
                    `shipping_address` text NULL,
                    `fulfillment_note` text NULL,
                    `status` enum('pending','approved','rejected','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
                    `admin_notes` text NULL,
                    `rejection_reason` text NULL,
                    `submitted_at` timestamp NULL DEFAULT NULL,
                    `approved_at` timestamp NULL DEFAULT NULL,
                    `shipped_at` timestamp NULL DEFAULT NULL,
                    `delivered_at` timestamp NULL DEFAULT NULL,
                    `submitted_by` bigint(20) UNSIGNED NOT NULL,
                    `approved_by` bigint(20) UNSIGNED NULL,
                    `created_at` timestamp NULL DEFAULT NULL,
                    `updated_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `dropshipping_orders_tenant_id_status_index` (`tenant_id`, `status`),
                    KEY `dropshipping_orders_status_index` (`status`),
                    KEY `dropshipping_orders_created_at_index` (`created_at`),
                    KEY `dropshipping_orders_order_number_index` (`order_number`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            Log::info("Successfully created dropshipping_orders table on connection {$connectionName}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to create dropshipping_orders table on connection {$connectionName}: " . $e->getMessage());
            return false;
        }
    }
}
