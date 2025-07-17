<?php

use Illuminate\Support\Facades\Route;
use Plugin\Dropshipping\Http\Controllers\Admin\WooCommerceConfigController;
use Plugin\Dropshipping\Http\Controllers\Admin\OrderManagementController as AdminOrderController;
use Plugin\Dropshipping\Http\Controllers\Admin\WithdrawalController as AdminWithdrawalController;
use Plugin\Dropshipping\Http\Controllers\Admin\AISettingsController;
use Illuminate\Support\Facades\DB;

/**
 * Dropshipping Admin Routes
 * These routes are for super admin to manage WooCommerce configurations
 */

Route::group(['prefix' => getAdminPrefix(), 'as' => 'admin.dropshipping.', 'middleware' => ['auth']], function () {
    // Main Dropshipping Dashboard
    Route::get('/dropshipping', [WooCommerceConfigController::class, 'dashboard'])->name('dashboard');

    // WooCommerce Configuration Management
    Route::get('/dropshipping/woocommerce-config', [WooCommerceConfigController::class, 'index'])->name('woocommerce.index');
    Route::get('/dropshipping/woocommerce-config/create', [WooCommerceConfigController::class, 'create'])->name('woocommerce.create');
    Route::post('/dropshipping/woocommerce-config', [WooCommerceConfigController::class, 'store'])->name('woocommerce.store');
    Route::get('/dropshipping/woocommerce-config/{id}/edit', [WooCommerceConfigController::class, 'edit'])->name('woocommerce.edit');
    Route::put('/dropshipping/woocommerce-config/{id}', [WooCommerceConfigController::class, 'update'])->name('woocommerce.update');
    Route::delete('/dropshipping/woocommerce-config/{id}', [WooCommerceConfigController::class, 'destroy'])->name('woocommerce.destroy');

    // WooCommerce Actions
    Route::post('/dropshipping/woocommerce-config/test-connection', [WooCommerceConfigController::class, 'testConnection'])->name('woocommerce.test');
    Route::post('/dropshipping/woocommerce-config/{id}/sync-products', [WooCommerceConfigController::class, 'syncProducts'])->name('woocommerce.sync');

    // DEBUG: Manual sync test route (temporary)
    Route::get('/dropshipping/debug/sync/{id}', function ($id) {
        try {
            $config = DB::table('dropshipping_woocommerce_configs')->where('id', $id)->first();

            if (!$config) {
                return response()->json(['error' => 'Configuration not found']);
            }

            // Test API connection
            $apiService = new \Plugin\Dropshipping\Services\WooCommerceApiService();
            $apiService->setCredentials($config->store_url, $config->consumer_key, $config->consumer_secret);

            $connectionTest = $apiService->testConnection();
            if (!$connectionTest['success']) {
                return response()->json(['error' => 'Connection failed: ' . $connectionTest['message']]);
            }

            // Try to get products
            $result = $apiService->getProducts(1, 5); // Just 5 products for testing

            if (!$result['success']) {
                return response()->json(['error' => 'Failed to fetch products: ' . $result['message']]);
            }

            $products = $result['products'];
            $savedCount = 0;

            foreach ($products as $product) {
                // Helper function to convert empty strings to null for numeric fields
                $cleanPrice = function ($value) {
                    if ($value === '' || $value === null) {
                        return null;
                    }
                    return is_numeric($value) ? (float)$value : null;
                };

                // Helper function to convert empty strings to 0 for quantity fields
                $cleanQuantity = function ($value) {
                    if ($value === '' || $value === null) {
                        return 0;
                    }
                    return is_numeric($value) ? (int)$value : 0;
                };

                $productData = [
                    'woocommerce_config_id' => $config->id,
                    'woocommerce_product_id' => $product['id'],
                    'name' => $product['name'],
                    'slug' => $product['slug'],
                    'description' => $product['description'] ?? '',
                    'short_description' => $product['short_description'] ?? '',
                    'price' => $cleanPrice($product['price']),
                    'regular_price' => $cleanPrice($product['regular_price']),
                    'sale_price' => $cleanPrice($product['sale_price']),
                    'sku' => $product['sku'] ?? '',
                    'stock_quantity' => $cleanQuantity($product['stock_quantity']),
                    'stock_status' => $product['stock_status'] ?? 'instock',
                    'categories' => json_encode($product['categories'] ?? []),
                    'tags' => json_encode($product['tags'] ?? []),
                    'images' => json_encode($product['images'] ?? []),
                    'attributes' => json_encode($product['attributes'] ?? []),
                    'status' => $product['status'] ?? 'publish',
                    'featured' => $product['featured'] ?? false,
                    'date_created' => isset($product['date_created']) ? date('Y-m-d H:i:s', strtotime($product['date_created'])) : now(),
                    'date_modified' => isset($product['date_modified']) ? date('Y-m-d H:i:s', strtotime($product['date_modified'])) : now(),
                    'last_synced_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                // Check if product already exists
                $existing = DB::table('dropshipping_products')
                    ->where('woocommerce_config_id', $config->id)
                    ->where('woocommerce_product_id', $product['id'])
                    ->first();

                if (!$existing) {
                    DB::table('dropshipping_products')->insert($productData);
                    $savedCount++;
                }
            }

            // Update product count
            $totalProducts = DB::table('dropshipping_products')->where('woocommerce_config_id', $config->id)->count();
            DB::table('dropshipping_woocommerce_configs')
                ->where('id', $config->id)
                ->update(['total_products' => $totalProducts]);

            return response()->json([
                'success' => true,
                'message' => "Sync test completed! Saved {$savedCount} new products. Total: {$totalProducts}",
                'total_products_available' => $result['total_products'] ?? 'Unknown',
                'products_fetched' => count($products),
                'products_saved' => $savedCount,
                'total_in_db' => $totalProducts
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    })->name('debug.sync');

    // Detailed debug route for order relationships and customer data
    Route::get('/dropshipping/debug/order-details/{database}/{id}', function ($database, $id) {
        try {
            $connectionName = 'tenant_' . $database;
            $tenantConfig = config('database.connections.mysql');
            $tenantConfig['database'] = $database;
            config(["database.connections.$connectionName" => $tenantConfig]);

            // Test connection
            DB::connection($connectionName)->getPdo();

            $debugInfo = [];

            // 1. Get the dropshipping order
            $dropshippingOrder = DB::connection($connectionName)->table('dropshipping_orders')
                ->where('id', $id)
                ->first();

            $debugInfo['dropshipping_order'] = $dropshippingOrder ? (array) $dropshippingOrder : 'Not found';

            if ($dropshippingOrder && $dropshippingOrder->original_order_id) {
                // 2. Get the original order
                $originalOrder = DB::connection($connectionName)->table('tl_com_orders')
                    ->where('id', $dropshippingOrder->original_order_id)
                    ->first();

                $debugInfo['original_order'] = $originalOrder ? (array) $originalOrder : 'Not found';

                if ($originalOrder) {
                    // 3. Get shipping address
                    if ($originalOrder->shipping_address) {
                        $shippingAddress = DB::connection($connectionName)->table('tl_com_customer_address')
                            ->where('id', $originalOrder->shipping_address)
                            ->first();
                        $debugInfo['shipping_address'] = $shippingAddress ? (array) $shippingAddress : 'Not found';
                    }

                    // 4. Get billing address
                    if ($originalOrder->billing_address) {
                        $billingAddress = DB::connection($connectionName)->table('tl_com_customer_address')
                            ->where('id', $originalOrder->billing_address)
                            ->first();
                        $debugInfo['billing_address'] = $billingAddress ? (array) $billingAddress : 'Not found';
                    }

                    // 5. Get customer info (registered customer)
                    if ($originalOrder->customer_id) {
                        $customer = DB::connection($connectionName)->table('tl_com_customers')
                            ->where('id', $originalOrder->customer_id)
                            ->first();
                        $debugInfo['registered_customer'] = $customer ? (array) $customer : 'Not found';
                    }

                    // 6. Get guest customer info
                    $guestCustomer = DB::connection($connectionName)->table('tl_com_guest_customer')
                        ->where('order_id', $originalOrder->id)
                        ->first();
                    $debugInfo['guest_customer'] = $guestCustomer ? (array) $guestCustomer : 'Not found';

                    // 7. Get order products with details
                    $orderProducts = DB::connection($connectionName)->table('tl_com_ordered_products')
                        ->where('order_id', $originalOrder->id)
                        ->get();
                    $debugInfo['order_products'] = $orderProducts ? $orderProducts->toArray() : 'Not found';

                    // 8. Get all customer addresses for this customer (if registered)
                    if ($originalOrder->customer_id) {
                        $allAddresses = DB::connection($connectionName)->table('tl_com_customer_address')
                            ->where('customer_id', $originalOrder->customer_id)
                            ->get();
                        $debugInfo['all_customer_addresses'] = $allAddresses ? $allAddresses->toArray() : 'Not found';
                    }

                    // 9. Check if there are multiple orders with similar details
                    $similarOrders = DB::connection($connectionName)->table('tl_com_orders')
                        ->where('customer_email', $originalOrder->customer_email)
                        ->orWhere('customer_phone', $originalOrder->customer_phone)
                        ->limit(5)
                        ->get();
                    $debugInfo['similar_orders'] = $similarOrders ? $similarOrders->toArray() : 'Not found';

                    // 10. Calculate total order amount from products
                    if ($orderProducts) {
                        $calculatedTotal = 0;
                        foreach ($orderProducts as $product) {
                            $calculatedTotal += ($product->unit_price * $product->quantity);
                        }
                        $debugInfo['calculated_total_from_products'] = $calculatedTotal;
                        $debugInfo['order_total_vs_calculated'] = [
                            'order_total' => $originalOrder->total ?? 'N/A',
                            'calculated_total' => $calculatedTotal,
                            'difference' => ($originalOrder->total ?? 0) - $calculatedTotal
                        ];
                    }
                }
            }

            // 11. Check if there are other dropshipping orders for the same original order
            if ($dropshippingOrder && $dropshippingOrder->original_order_id) {
                $relatedDropshippingOrders = DB::connection($connectionName)->table('dropshipping_orders')
                    ->where('original_order_id', $dropshippingOrder->original_order_id)
                    ->get();
                $debugInfo['related_dropshipping_orders'] = $relatedDropshippingOrders ? $relatedDropshippingOrders->toArray() : 'Not found';
            }

            // Format as HTML for better readability
            $html = '<h2>Order Debug Information</h2>';
            $html .= '<p><strong>Database:</strong> ' . $database . '</p>';
            $html .= '<p><strong>Dropshipping Order ID:</strong> ' . $id . '</p>';

            foreach ($debugInfo as $section => $data) {
                $html .= '<h3>' . ucwords(str_replace('_', ' ', $section)) . '</h3>';
                if (is_array($data) && !empty($data)) {
                    $html .= '<pre>' . json_encode($data, JSON_PRETTY_PRINT) . '</pre>';
                } else {
                    $html .= '<p>' . (is_string($data) ? $data : 'Empty') . '</p>';
                }
                $html .= '<hr>';
            }

            return $html;
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    })->name('debug.order.details');

    // Route to list all orders with direct access links
    Route::get('/dropshipping/all-orders-list', function () {
        try {
            $tenants = DB::connection('mysql')->table('tenants')->get();
            $allOrdersWithLinks = [];

            foreach ($tenants as $tenant) {
                $tenantData = json_decode($tenant->data, true);
                $database = $tenantData['tenancy_db_name'] ?? null;

                if (!$database) continue;

                try {
                    $connectionName = 'tenant_' . $database;
                    $tenantConfig = config('database.connections.mysql');
                    $tenantConfig['database'] = $database;
                    config(["database.connections.$connectionName" => $tenantConfig]);

                    // Test connection
                    DB::connection($connectionName)->getPdo();

                    // Check if table exists
                    if (!DB::connection($connectionName)->getSchemaBuilder()->hasTable('dropshipping_orders')) {
                        continue;
                    }

                    $orders = DB::connection($connectionName)->table('dropshipping_orders')
                        ->select('id', 'order_number', 'tenant_id', 'product_name', 'customer_name', 'customer_email', 'status', 'created_at')
                        ->orderBy('id', 'desc')
                        ->get();

                    foreach ($orders as $order) {
                        $allOrdersWithLinks[] = [
                            'database' => $database,
                            'id' => $order->id,
                            'order_number' => $order->order_number,
                            'customer_name' => $order->customer_name,
                            'customer_email' => $order->customer_email,
                            'product_name' => $order->product_name,
                            'status' => $order->status,
                            'created_at' => $order->created_at,
                            'access_url' => url("/admin/dropshipping/orders/from-database/{$database}/{$order->id}"),
                            'enhanced_url' => url("/admin/dropshipping/orders/enhanced-simple-order/{$order->id}"),
                            'is_test_order' => strpos($order->order_number, 'TEST-') === 0
                        ];
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Sort by created_at descending
            usort($allOrdersWithLinks, function ($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            $html = '<h2>All Dropshipping Orders</h2>';
            $html .= '<table border="1" style="border-collapse: collapse; width: 100%;">';
            $html .= '<tr style="background-color: #f0f0f0;">
                <th>Database</th>
                <th>ID</th>
                <th>Order Number</th>
                <th>Customer</th>
                <th>Email</th>
                <th>Product</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>';

            foreach ($allOrdersWithLinks as $order) {
                $rowStyle = $order['is_test_order'] ? 'background-color: #ffe6e6;' : '';
                $testLabel = $order['is_test_order'] ? ' <span style="color: red;">(TEST)</span>' : '';

                $html .= "<tr style='{$rowStyle}'>
                    <td>{$order['database']}</td>
                    <td>{$order['id']}</td>
                    <td>{$order['order_number']}{$testLabel}</td>
                    <td>{$order['customer_name']}</td>
                    <td>{$order['customer_email']}</td>
                    <td>{$order['product_name']}</td>
                    <td>{$order['status']}</td>
                    <td>{$order['created_at']}</td>
                                         <td>
                         <a href='{$order['access_url']}' style='color: blue; margin-right: 10px;'>View Details</a>
                         <a href='{$order['enhanced_url']}' style='color: green; margin-right: 10px;'>Enhanced View</a>
                         <a href='" . url("/admin/dropshipping/debug/order-details/{$order['database']}/{$order['id']}") . "' style='color: red;'>Debug Data</a>
                     </td>
                </tr>";
            }

            $html .= '</table>';
            $html .= '<br><p><strong>Note:</strong> Test orders are highlighted in red. Click "View Details" to see the order with proper database context.</p>';

            return $html;
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    })->name('all.orders.list');

    // Route to access specific order from specific database
    Route::get('/dropshipping/from-database/{database}/{id}', function ($database, $id) {
        try {
            $connectionName = 'tenant_' . $database;
            $tenantConfig = config('database.connections.mysql');
            $tenantConfig['database'] = $database;
            config(["database.connections.$connectionName" => $tenantConfig]);

            // Test connection
            DB::connection($connectionName)->getPdo();

            // Check if table exists
            if (!DB::connection($connectionName)->getSchemaBuilder()->hasTable('dropshipping_orders')) {
                return redirect()->route('admin.dropshipping.orders.index')
                    ->with('error', "dropshipping_orders table does not exist in {$database}");
            }

            // Get the order
            $order = DB::connection($connectionName)->table('dropshipping_orders')
                ->where('id', $id)
                ->first();

            if (!$order) {
                return redirect()->route('admin.dropshipping.orders.index')
                    ->with('error', "Order {$id} not found in database {$database}");
            }

            // Convert to object with additional properties
            $orderObj = (object) $order;
            $orderObj->connection_name = $connectionName;
            $orderObj->tenant_database = $database;

            // Get additional order details if original_order_id exists
            if ($order->original_order_id) {
                // Get original order details
                $originalOrder = DB::connection($connectionName)->table('tl_com_orders')
                    ->where('id', $order->original_order_id)
                    ->first();

                if ($originalOrder) {
                    $orderObj->original_order = $originalOrder;

                    // Get shipping address
                    if ($originalOrder->shipping_address) {
                        $shippingInfo = DB::connection($connectionName)->table('tl_com_customer_address')
                            ->where('id', $originalOrder->shipping_address)
                            ->first();
                        if ($shippingInfo) {
                            $orderObj->shipping_info = $shippingInfo;
                        }
                    }

                    // Get billing address  
                    if ($originalOrder->billing_address) {
                        $billingInfo = DB::connection($connectionName)->table('tl_com_customer_address')
                            ->where('id', $originalOrder->billing_address)
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
                }
            }

            return view('plugin/dropshipping::admin.order-management.show', ['order' => $orderObj]);
        } catch (\Exception $e) {
            return redirect()->route('admin.dropshipping.orders.index')
                ->with('error', "Error loading order: " . $e->getMessage());
        }
    })->name('order.from.database');

    // DEBUG: Check orders in tenant databases (temporary)
    Route::get('/dropshipping/debug/check-orders', function () {
        try {
            $tenants = DB::connection('mysql')->table('tenants')->get();
            $allOrders = [];

            foreach ($tenants as $tenant) {
                $tenantData = json_decode($tenant->data, true);
                if (isset($tenantData['tenancy_db_name'])) {
                    $database = $tenantData['tenancy_db_name'];

                    try {
                        $connectionName = 'tenant_' . $database;
                        $tenantConfig = config('database.connections.mysql');
                        $tenantConfig['database'] = $database;
                        config(["database.connections.$connectionName" => $tenantConfig]);

                        // Test connection
                        DB::connection($connectionName)->getPdo();

                        // Check if table exists
                        $tableExists = DB::connection($connectionName)->getSchemaBuilder()->hasTable('dropshipping_orders');

                        if ($tableExists) {
                            $orders = DB::connection($connectionName)->table('dropshipping_orders')
                                ->select('id', 'order_number', 'tenant_id', 'status', 'created_at')
                                ->limit(10)
                                ->get();

                            $allOrders[$database] = [
                                'count' => DB::connection($connectionName)->table('dropshipping_orders')->count(),
                                'orders' => $orders
                            ];
                        } else {
                            $allOrders[$database] = ['error' => 'dropshipping_orders table does not exist'];
                        }
                    } catch (\Exception $e) {
                        $allOrders[$database] = ['error' => $e->getMessage()];
                    }
                }
            }

            return response()->json([
                'tenant_databases_found' => count($tenants),
                'orders_by_database' => $allOrders
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    })->name('debug.check.orders');

    // DEBUG: Simple order existence check
    Route::get('/dropshipping/debug/order-exists/{id}', function ($id) {
        try {
            $tenants = DB::connection('mysql')->table('tenants')->get();

            foreach ($tenants as $tenant) {
                $tenantData = json_decode($tenant->data, true);
                $database = $tenantData['tenancy_db_name'] ?? null;

                if (!$database) continue;

                try {
                    $connectionName = 'tenant_' . $database;
                    $tenantConfig = config('database.connections.mysql');
                    $tenantConfig['database'] = $database;
                    config(["database.connections.$connectionName" => $tenantConfig]);

                    DB::connection($connectionName)->getPdo();

                    if (!DB::connection($connectionName)->getSchemaBuilder()->hasTable('dropshipping_orders')) {
                        continue;
                    }

                    $orderExists = DB::connection($connectionName)->table('dropshipping_orders')
                        ->where('id', $id)
                        ->exists();

                    if ($orderExists) {
                        return response()->json([
                            'found' => true,
                            'database' => $database,
                            'order_id' => $id
                        ]);
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            return response()->json(['found' => false, 'order_id' => $id]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    })->name('debug.order.exists');

    // DEBUG: Test shipping information loading
    Route::get('/dropshipping/debug/test-order-shipping/{id}', function ($id) {
        try {
            $tenants = DB::connection('mysql')->table('tenants')->get();

            foreach ($tenants as $tenant) {
                $tenantData = json_decode($tenant->data, true);
                $database = $tenantData['tenancy_db_name'] ?? null;

                if (!$database) continue;

                try {
                    $connectionName = 'tenant_' . $database;
                    $tenantConfig = config('database.connections.mysql');
                    $tenantConfig['database'] = $database;
                    config(["database.connections.$connectionName" => $tenantConfig]);

                    // Test connection
                    DB::connection($connectionName)->getPdo();

                    // Check if table exists
                    if (!DB::connection($connectionName)->getSchemaBuilder()->hasTable('dropshipping_orders')) {
                        continue;
                    }

                    // Get the basic order
                    $order = DB::connection($connectionName)->table('dropshipping_orders')
                        ->where('id', $id)
                        ->first();

                    if ($order) {
                        $debug = [];
                        $debug['order'] = $order;
                        $debug['database'] = $database;
                        $debug['connection'] = $connectionName;

                        // Get original order details if available
                        if ($order->original_order_id) {
                            $originalOrder = DB::connection($connectionName)->table('tl_com_orders')
                                ->where('id', $order->original_order_id)
                                ->first();

                            $debug['original_order'] = $originalOrder;

                            if ($originalOrder && $originalOrder->shipping_address) {
                                // Get shipping address with related data
                                $shippingInfo = DB::connection($connectionName)->table('tl_com_customer_address')
                                    ->leftJoin('tl_com_countries', 'tl_com_customer_address.country_id', '=', 'tl_com_countries.id')
                                    ->leftJoin('tl_com_states', 'tl_com_customer_address.state_id', '=', 'tl_com_states.id')
                                    ->leftJoin('tl_com_cities', 'tl_com_customer_address.city_id', '=', 'tl_com_cities.id')
                                    ->where('tl_com_customer_address.id', $originalOrder->shipping_address)
                                    ->select(
                                        'tl_com_customer_address.*',
                                        'tl_com_countries.name as country',
                                        'tl_com_states.name as state',
                                        'tl_com_cities.name as city'
                                    )
                                    ->first();

                                $debug['shipping_info'] = $shippingInfo;
                            }

                            if ($originalOrder && $originalOrder->billing_address) {
                                // Get billing address with related data
                                $billingInfo = DB::connection($connectionName)->table('tl_com_customer_address')
                                    ->leftJoin('tl_com_countries', 'tl_com_customer_address.country_id', '=', 'tl_com_countries.id')
                                    ->leftJoin('tl_com_states', 'tl_com_customer_address.state_id', '=', 'tl_com_states.id')
                                    ->leftJoin('tl_com_cities', 'tl_com_customer_address.city_id', '=', 'tl_com_cities.id')
                                    ->where('tl_com_customer_address.id', $originalOrder->billing_address)
                                    ->select(
                                        'tl_com_customer_address.*',
                                        'tl_com_countries.name as country',
                                        'tl_com_states.name as state',
                                        'tl_com_cities.name as city'
                                    )
                                    ->first();

                                $debug['billing_info'] = $billingInfo;
                            }
                        }

                        return response()->json($debug, 200, [], JSON_PRETTY_PRINT);
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            return response()->json(['error' => "Order {$id} not found in any tenant database."]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    })->name('debug.test.shipping');

    // Plan Limits Management
    Route::get('/dropshipping/plan-limits', [\Plugin\Dropshipping\Http\Controllers\Admin\PlanLimitsController::class, 'index'])->name('plan-limits.index');
    Route::get('/dropshipping/plan-limits/create/{packageId}', [\Plugin\Dropshipping\Http\Controllers\Admin\PlanLimitsController::class, 'create'])->name('plan-limits.create');
    Route::post('/dropshipping/plan-limits', [\Plugin\Dropshipping\Http\Controllers\Admin\PlanLimitsController::class, 'store'])->name('plan-limits.store');
    Route::get('/dropshipping/plan-limits/{packageId}/edit', [\Plugin\Dropshipping\Http\Controllers\Admin\PlanLimitsController::class, 'edit'])->name('plan-limits.edit');
    Route::put('/dropshipping/plan-limits/{packageId}', [\Plugin\Dropshipping\Http\Controllers\Admin\PlanLimitsController::class, 'update'])->name('plan-limits.update');
    Route::delete('/dropshipping/plan-limits/{packageId}', [\Plugin\Dropshipping\Http\Controllers\Admin\PlanLimitsController::class, 'destroy'])->name('plan-limits.destroy');
    Route::get('/dropshipping/plan-limits/{packageId}/usage', [\Plugin\Dropshipping\Http\Controllers\Admin\PlanLimitsController::class, 'usage'])->name('plan-limits.usage');
    Route::post('/dropshipping/plan-limits/create-defaults', [\Plugin\Dropshipping\Http\Controllers\Admin\PlanLimitsController::class, 'createDefaults'])->name('plan-limits.create-defaults');

    // Reports
    Route::get('/dropshipping/reports/imports', [WooCommerceConfigController::class, 'importReports'])->name('reports.imports');
    Route::get('/dropshipping/reports/usage', [WooCommerceConfigController::class, 'usageReports'])->name('reports.usage');

    // Settings
    Route::get('/dropshipping/settings', [WooCommerceConfigController::class, 'settings'])->name('settings.index');
    Route::post('/dropshipping/settings/update', [WooCommerceConfigController::class, 'updateSettings'])->name('settings.update');
    Route::post('/dropshipping/settings/test-google-ai', [WooCommerceConfigController::class, 'testGoogleAIStudioApi'])->name('settings.test-google-ai');

    // AI Settings
    Route::get('/dropshipping/ai-settings', [\Plugin\Dropshipping\Http\Controllers\Admin\AISettingsController::class, 'index'])->name('ai.settings');
    Route::post('/dropshipping/ai-settings', [\Plugin\Dropshipping\Http\Controllers\Admin\AISettingsController::class, 'store'])->name('ai.settings.store');
    Route::post('/dropshipping/test-openai', [\Plugin\Dropshipping\Http\Controllers\Admin\AISettingsController::class, 'testOpenAI'])->name('ai.test.openai');
    Route::post('/dropshipping/test-google-ai', [\Plugin\Dropshipping\Http\Controllers\Admin\AISettingsController::class, 'testGoogleAI'])->name('ai.test.google');
    Route::post('/dropshipping/test-google-search', [\Plugin\Dropshipping\Http\Controllers\Admin\AISettingsController::class, 'testGoogleSearch'])->name('ai.test.google.search');

    // Search Cache Management Routes
    Route::prefix('dropshipping/search-cache')->as('search-cache.')->group(function () {
        Route::get('/', [\Plugin\Dropshipping\Http\Controllers\Admin\SearchCacheController::class, 'index'])->name('index');
        Route::get('/{id}', [\Plugin\Dropshipping\Http\Controllers\Admin\SearchCacheController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [\Plugin\Dropshipping\Http\Controllers\Admin\SearchCacheController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\Plugin\Dropshipping\Http\Controllers\Admin\SearchCacheController::class, 'update'])->name('update');
        Route::post('/{id}/toggle-status', [\Plugin\Dropshipping\Http\Controllers\Admin\SearchCacheController::class, 'toggleStatus'])->name('toggle.status');
        Route::delete('/{id}', [\Plugin\Dropshipping\Http\Controllers\Admin\SearchCacheController::class, 'destroy'])->name('destroy');
        Route::post('/clear-all', [\Plugin\Dropshipping\Http\Controllers\Admin\SearchCacheController::class, 'clearAll'])->name('clear.all');
        Route::get('/statistics/ajax', [\Plugin\Dropshipping\Http\Controllers\Admin\SearchCacheController::class, 'stats'])->name('stats');
    });

    // Order Management Routes
    Route::prefix('dropshipping/orders')->as('orders.')->group(function () {
        Route::get('/', [AdminOrderController::class, 'index'])->name('index');

        // Enhanced simple order lookup with all order details
        Route::get('/enhanced-simple-order/{id}', function ($id) {
            try {
                $tenants = DB::connection('mysql')->table('tenants')->get();

                foreach ($tenants as $tenant) {
                    $tenantData = json_decode($tenant->data, true);
                    $database = $tenantData['tenancy_db_name'] ?? null;

                    if (!$database) continue;

                    try {
                        $connectionName = 'tenant_' . $database;
                        $tenantConfig = config('database.connections.mysql');
                        $tenantConfig['database'] = $database;
                        config(["database.connections.$connectionName" => $tenantConfig]);

                        // Test connection
                        DB::connection($connectionName)->getPdo();

                        // Check if table exists
                        if (!DB::connection($connectionName)->getSchemaBuilder()->hasTable('dropshipping_orders')) {
                            continue;
                        }

                        // Get the basic order
                        $order = DB::connection($connectionName)->table('dropshipping_orders')
                            ->where('id', $id)
                            ->first();

                        if ($order) {
                            // Convert to object with additional properties
                            $orderObj = (object) $order;
                            $orderObj->connection_name = $connectionName;
                            $orderObj->tenant_database = $database;

                            // Get additional order details if original_order_id exists
                            if ($order->original_order_id) {
                                // Get original order details
                                $originalOrder = DB::connection($connectionName)->table('tl_com_orders')
                                    ->where('id', $order->original_order_id)
                                    ->first();

                                if ($originalOrder) {
                                    $orderObj->original_order = $originalOrder;

                                    // Get shipping address
                                    if ($originalOrder->shipping_address) {
                                        $shippingInfo = DB::connection($connectionName)->table('tl_com_customer_address')
                                            ->where('id', $originalOrder->shipping_address)
                                            ->first();
                                        if ($shippingInfo) {
                                            $orderObj->shipping_info = $shippingInfo;
                                        }
                                    }

                                    // Get billing address  
                                    if ($originalOrder->billing_address) {
                                        $billingInfo = DB::connection($connectionName)->table('tl_com_customer_address')
                                            ->where('id', $originalOrder->billing_address)
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
                                }
                            }

                            return view('plugin/dropshipping::admin.order-management.show', ['order' => $orderObj]);
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }

                return redirect()->route('admin.dropshipping.orders.index')
                    ->with('error', "Order {$id} not found in any tenant database.");
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()]);
            }
        })->name('enhanced.simple.order');

        // Simple direct order lookup (bypassing the complex enhanced method)
        Route::get('/simple-order/{id}', function ($id) {
            try {
                $tenants = DB::connection('mysql')->table('tenants')->get();

                foreach ($tenants as $tenant) {
                    $tenantData = json_decode($tenant->data, true);
                    $database = $tenantData['tenancy_db_name'] ?? null;

                    if (!$database) continue;

                    try {
                        $connectionName = 'tenant_' . $database;
                        $tenantConfig = config('database.connections.mysql');
                        $tenantConfig['database'] = $database;
                        config(["database.connections.$connectionName" => $tenantConfig]);

                        // Test connection
                        DB::connection($connectionName)->getPdo();

                        // Check if table exists
                        if (!DB::connection($connectionName)->getSchemaBuilder()->hasTable('dropshipping_orders')) {
                            continue;
                        }

                        // Simple direct query
                        $order = DB::connection($connectionName)->table('dropshipping_orders')
                            ->where('id', $id)
                            ->first();

                        if ($order) {
                            // Convert to object with additional properties
                            $orderObj = (object) $order;
                            $orderObj->connection_name = $connectionName;
                            $orderObj->tenant_database = $database;

                            return view('plugin/dropshipping::admin.order-management.show', ['order' => $orderObj]);
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
                }

                return redirect()->route('admin.dropshipping.orders.index')
                    ->with('error', "Order {$id} not found in any tenant database.");
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()]);
            }
        })->name('simple.order');

        // Test route to check if view loads with dummy data
        Route::get('/test-view', function () {
            try {
                // Create a dummy order object to test the view
                $order = (object) [
                    'id' => 999,
                    'order_number' => 'TEST-VIEW-123',
                    'customer_name' => 'Test Customer',
                    'status' => 'pending',
                    'product_name' => 'Test Product',
                    'tenant_id' => 'test-tenant',
                    'created_at' => now()
                ];

                return view('plugin/dropshipping::admin.order-management.show', compact('order'));
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'View failed to load',
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        })->name('test.view');

        // Debug route to test individual order lookup
        Route::get('/debug-find-order/{id}', function ($id) {
            try {
                $controller = new AdminOrderController();

                // Test the findOrderInTenantDatabasesEnhanced method directly
                $order = $controller->findOrderInTenantDatabasesEnhanced($id);

                if (!$order) {
                    return response()->json([
                        'success' => false,
                        'message' => "Order {$id} not found in any tenant database",
                        'searched_databases' => 'Check logs for details'
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => "Order {$id} found successfully",
                    'order' => [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'tenant_id' => $order->tenant_id,
                        'product_name' => $order->product_name,
                        'customer_name' => $order->customer_name,
                        'status' => $order->status,
                        'created_at' => $order->created_at,
                        'connection_name' => $order->connection_name ?? 'not set',
                        'tenant_database' => $order->tenant_database ?? 'not set'
                    ],
                    'has_shipping_info' => isset($order->shipping_info),
                    'has_billing_info' => isset($order->billing_info),
                    'has_payment_info' => isset($order->payment_info),
                    'original_order_id' => $order->original_order_id ?? 'not set'
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        })->name('debug.find.order');

        // Test route to create a sample order for testing
        Route::get('/create-test-order', function () {
            try {
                // Get the first tenant database
                $tenants = DB::connection('mysql')->table('tenants')->first();
                if (!$tenants) {
                    return response()->json(['error' => 'No tenants found']);
                }

                $tenantData = json_decode($tenants->data, true);
                $database = $tenantData['tenancy_db_name'] ?? null;

                if (!$database) {
                    return response()->json(['error' => 'No tenant database found']);
                }

                $connectionName = 'tenant_' . $database;
                $tenantConfig = config('database.connections.mysql');
                $tenantConfig['database'] = $database;
                config(["database.connections.$connectionName" => $tenantConfig]);

                // Check if dropshipping_orders table exists
                if (!DB::connection($connectionName)->getSchemaBuilder()->hasTable('dropshipping_orders')) {
                    return response()->json(['error' => 'dropshipping_orders table does not exist in ' . $database]);
                }

                // Create a test order
                $testOrder = [
                    'tenant_id' => $tenants->id,
                    'order_number' => 'TEST-' . time(),
                    'product_name' => 'Test Product',
                    'product_sku' => 'TEST-SKU-001',
                    'quantity' => 1,
                    'unit_price' => 25.00,
                    'total_amount' => 25.00,
                    'commission_rate' => 20.00,
                    'commission_amount' => 5.00,
                    'tenant_earning' => 20.00,
                    'customer_name' => 'Test Customer',
                    'customer_email' => 'test@example.com',
                    'customer_phone' => '1234567890',
                    'shipping_address' => '123 Test Street, Test City, Test State',
                    'fulfillment_note' => 'Test fulfillment note',
                    'status' => 'pending',
                    'submitted_by' => 1,
                    'submitted_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                DB::connection($connectionName)->table('dropshipping_orders')->insert($testOrder);

                return response()->json([
                    'success' => true,
                    'message' => 'Test order created successfully',
                    'order' => $testOrder
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()]);
            }
        })->name('create.test');

        // Test route that calls the actual controller method
        Route::get('/test-controller/{id}', function ($id) {
            try {
                $controller = new AdminOrderController();
                $result = $controller->show($id);

                if ($result instanceof \Illuminate\Http\JsonResponse) {
                    return $result;
                }

                if ($result instanceof \Illuminate\Http\RedirectResponse) {
                    return response()->json([
                        'type' => 'redirect',
                        'message' => 'Controller returned redirect',
                        'url' => $result->getTargetUrl()
                    ]);
                }

                return response()->json([
                    'type' => 'view',
                    'message' => 'Controller returned view',
                    'view_name' => 'Working'
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()]);
            }
        })->name('test.controller');

        // Very simple test route to check if routing works
        Route::get('/test-basic/{id}', function ($id) {
            return "Order ID: " . $id . " - Basic route is working!";
        })->name('test.basic');

        // Simple test route to debug view issues
        Route::get('/simple-view/{id}', function ($id) {
            try {
                $controller = new AdminOrderController();
                $order = $controller->findOrderInTenantDatabasesEnhanced($id);

                if (!$order) {
                    return response()->json(['error' => 'Order not found']);
                }

                return response()->json([
                    'success' => true,
                    'order' => $order,
                    'has_shipping_info' => isset($order->shipping_info),
                    'has_billing_info' => isset($order->billing_info),
                    'has_payment_info' => isset($order->payment_info)
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        })->name('simple.view');

        // Debug route to list all tables in tenant database
        Route::get('/debug-tables/{id}', function ($id) {
            try {
                $found = false;
                $database_info = [];

                $tenants = DB::connection('mysql')->table('tenants')->get();

                foreach ($tenants as $tenant) {
                    $tenantData = json_decode($tenant->data, true);
                    if (isset($tenantData['tenancy_db_name'])) {
                        $database = $tenantData['tenancy_db_name'];

                        try {
                            $connectionName = 'tenant_' . $database;
                            $tenantConfig = config('database.connections.mysql');
                            $tenantConfig['database'] = $database;
                            config(["database.connections.$connectionName" => $tenantConfig]);

                            DB::connection($connectionName)->getPdo();

                            if (DB::connection($connectionName)->getSchemaBuilder()->hasTable('dropshipping_orders')) {
                                $order = DB::connection($connectionName)->table('dropshipping_orders')
                                    ->where('id', $id)
                                    ->first();

                                if ($order) {
                                    $found = true;

                                    // Get all tables in this database
                                    $tables = DB::connection($connectionName)->select('SHOW TABLES');
                                    $tableNames = [];
                                    foreach ($tables as $table) {
                                        $tableName = array_values((array)$table)[0];
                                        $tableNames[] = $tableName;
                                    }

                                    $database_info = [
                                        'database_name' => $database,
                                        'all_tables' => $tableNames,
                                        'address_related_tables' => array_filter($tableNames, function ($table) {
                                            return strpos(strtolower($table), 'address') !== false ||
                                                strpos(strtolower($table), 'customer') !== false ||
                                                strpos(strtolower($table), 'payment') !== false ||
                                                strpos(strtolower($table), 'order') !== false;
                                        }),
                                        'original_order_data' => null
                                    ];

                                    // Get original order data to see what we're working with
                                    if ($order->original_order_id) {
                                        $originalOrder = DB::connection($connectionName)->table('tl_com_orders')
                                            ->where('id', $order->original_order_id)
                                            ->first();
                                        $database_info['original_order_data'] = $originalOrder;
                                    }

                                    break;
                                }
                            }
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                }

                return response()->json([
                    'order_id' => $id,
                    'found' => $found,
                    'database_info' => $database_info
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => $e->getMessage(),
                    'order_id' => $id
                ]);
            }
        })->name('debug.tables');

        // Enhanced debug route to test full order retrieval with shipping/billing
        Route::get('/test-enhanced/{id}', function ($id) {
            try {
                $found = false;
                $order_data = null;
                $enhanced_data = [];
                $errors = [];

                $tenants = DB::connection('mysql')->table('tenants')->get();

                foreach ($tenants as $tenant) {
                    $tenantData = json_decode($tenant->data, true);
                    if (isset($tenantData['tenancy_db_name'])) {
                        $database = $tenantData['tenancy_db_name'];

                        try {
                            $connectionName = 'tenant_' . $database;
                            $tenantConfig = config('database.connections.mysql');
                            $tenantConfig['database'] = $database;
                            config(["database.connections.$connectionName" => $tenantConfig]);

                            DB::connection($connectionName)->getPdo();

                            if (DB::connection($connectionName)->getSchemaBuilder()->hasTable('dropshipping_orders')) {
                                $order = DB::connection($connectionName)->table('dropshipping_orders')
                                    ->where('id', $id)
                                    ->first();

                                if ($order) {
                                    $found = true;
                                    $order_data = $order;

                                    // Test enhanced data retrieval
                                    if ($order->original_order_id) {
                                        $enhanced_data['original_order_search'] = $order->original_order_id;

                                        // Get original order
                                        $originalOrder = DB::connection($connectionName)->table('tl_com_orders')
                                            ->where('id', $order->original_order_id)
                                            ->first();

                                        if ($originalOrder) {
                                            $enhanced_data['original_order'] = $originalOrder;

                                            // Test shipping address lookup
                                            if ($originalOrder->shipping_address) {
                                                $enhanced_data['shipping_address_id'] = $originalOrder->shipping_address;

                                                $shippingInfo = DB::connection($connectionName)->table('tl_com_customer_address')
                                                    ->where('id', $originalOrder->shipping_address)
                                                    ->first();

                                                if ($shippingInfo) {
                                                    $enhanced_data['shipping_info'] = $shippingInfo;
                                                } else {
                                                    $errors[] = "Shipping address not found with ID: " . $originalOrder->shipping_address;
                                                }
                                            }

                                            // Test billing address lookup
                                            if ($originalOrder->billing_address) {
                                                $enhanced_data['billing_address_id'] = $originalOrder->billing_address;

                                                $billingInfo = DB::connection($connectionName)->table('tl_com_customer_address')
                                                    ->where('id', $originalOrder->billing_address)
                                                    ->first();

                                                if ($billingInfo) {
                                                    $enhanced_data['billing_info'] = $billingInfo;
                                                } else {
                                                    $errors[] = "Billing address not found with ID: " . $originalOrder->billing_address;
                                                }
                                            }

                                            // Test payment method lookup
                                            if ($originalOrder->payment_method) {
                                                $enhanced_data['payment_method_id'] = $originalOrder->payment_method;

                                                $paymentInfo = DB::connection($connectionName)->table('tl_com_payment_methods')
                                                    ->where('id', $originalOrder->payment_method)
                                                    ->first();

                                                if ($paymentInfo) {
                                                    $enhanced_data['payment_info'] = $paymentInfo;
                                                } else {
                                                    $errors[] = "Payment method not found with ID: " . $originalOrder->payment_method;
                                                }
                                            }

                                            // Test customer lookup
                                            if ($originalOrder->customer_id) {
                                                $enhanced_data['customer_id'] = $originalOrder->customer_id;

                                                $customerInfo = DB::connection($connectionName)->table('tl_com_customers')
                                                    ->where('id', $originalOrder->customer_id)
                                                    ->first();

                                                if ($customerInfo) {
                                                    $enhanced_data['customer_info'] = $customerInfo;
                                                } else {
                                                    $errors[] = "Customer not found with ID: " . $originalOrder->customer_id;
                                                }
                                            } else {
                                                // Check for guest customer
                                                $guestCustomer = DB::connection($connectionName)->table('tl_com_guest_customer')
                                                    ->where('order_id', $originalOrder->id)
                                                    ->first();

                                                if ($guestCustomer) {
                                                    $enhanced_data['guest_customer_info'] = $guestCustomer;
                                                } else {
                                                    $errors[] = "Guest customer not found for order ID: " . $originalOrder->id;
                                                }
                                            }

                                            // Get order products
                                            $orderProducts = DB::connection($connectionName)->table('tl_com_ordered_products')
                                                ->where('order_id', $originalOrder->id)
                                                ->get();

                                            $enhanced_data['order_products'] = $orderProducts;
                                        } else {
                                            $errors[] = "Original order not found with ID: " . $order->original_order_id;
                                        }
                                    }

                                    break;
                                }
                            }
                        } catch (\Exception $e) {
                            $errors[] = "Database error for {$database}: " . $e->getMessage();
                        }
                    }
                }

                return response()->json([
                    'order_id' => $id,
                    'found' => $found,
                    'order_data' => $order_data,
                    'enhanced_data' => $enhanced_data,
                    'errors' => $errors,
                    'total_tenants' => $tenants->count()
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => $e->getMessage(),
                    'order_id' => $id
                ]);
            }
        })->name('test.enhanced');

        // Simple debug route to test order viewing
        Route::get('/test-view/{id}', function ($id) {
            try {
                $found = false;
                $databases_checked = [];
                $order_data = null;

                $tenants = DB::connection('mysql')->table('tenants')->get();

                foreach ($tenants as $tenant) {
                    $tenantData = json_decode($tenant->data, true);
                    if (isset($tenantData['tenancy_db_name'])) {
                        $database = $tenantData['tenancy_db_name'];
                        $databases_checked[] = $database;

                        try {
                            $connectionName = 'tenant_' . $database;
                            $tenantConfig = config('database.connections.mysql');
                            $tenantConfig['database'] = $database;
                            config(["database.connections.$connectionName" => $tenantConfig]);

                            DB::connection($connectionName)->getPdo();

                            if (DB::connection($connectionName)->getSchemaBuilder()->hasTable('dropshipping_orders')) {
                                $order = DB::connection($connectionName)->table('dropshipping_orders')
                                    ->where('id', $id)
                                    ->first();

                                if ($order) {
                                    $found = true;
                                    $order_data = $order;
                                    break;
                                }
                            }
                        } catch (\Exception $e) {
                            // Continue checking other databases
                        }
                    }
                }

                return response()->json([
                    'order_id' => $id,
                    'found' => $found,
                    'databases_checked' => $databases_checked,
                    'order_data' => $order_data,
                    'total_tenants' => $tenants->count()
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => $e->getMessage(),
                    'order_id' => $id
                ]);
            }
        })->name('test.view');

        // DEBUG: Add debug route to test order retrieval
        Route::get('/debug/{id}', function ($id) {
            try {
                $controller = new AdminOrderController();
                $order = $controller->show($id);

                if ($order instanceof \Illuminate\Http\RedirectResponse) {
                    return response()->json([
                        'error' => 'Order not found',
                        'id' => $id,
                        'redirect' => true
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'order_found' => true,
                    'order_id' => $id,
                    'view_data' => $order->getData()
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'id' => $id
                ]);
            }
        })->name('debug');

        Route::get('/{id}', [AdminOrderController::class, 'show'])->name('show');
        Route::post('/{id}/approve', [AdminOrderController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [AdminOrderController::class, 'reject'])->name('reject');
        Route::post('/{id}/update-status', [AdminOrderController::class, 'updateStatus'])->name('update.status');
        Route::post('/bulk-action', [AdminOrderController::class, 'bulkAction'])->name('bulk.action');
        Route::get('/export/csv', [AdminOrderController::class, 'export'])->name('export');
        Route::get('/statistics/ajax', [AdminOrderController::class, 'getStatistics'])->name('statistics');
    });

    // Withdrawal Management Routes
    Route::prefix('dropshipping/withdrawals')->as('withdrawals.')->group(function () {
        Route::get('/', [AdminWithdrawalController::class, 'index'])->name('index');
        Route::get('/{id}', [AdminWithdrawalController::class, 'show'])->name('show');
        Route::post('/{id}/approve', [AdminWithdrawalController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [AdminWithdrawalController::class, 'reject'])->name('reject');
        Route::post('/{id}/process', [AdminWithdrawalController::class, 'markAsProcessed'])->name('process');
        Route::post('/bulk-action', [AdminWithdrawalController::class, 'bulkAction'])->name('bulk.action');
        Route::get('/export/csv', [AdminWithdrawalController::class, 'export'])->name('export');
        Route::get('/statistics/ajax', [AdminWithdrawalController::class, 'getStatistics'])->name('statistics');

        // Withdrawal Settings
        Route::get('/settings', [AdminWithdrawalController::class, 'settings'])->name('settings');
        Route::post('/settings/update', [AdminWithdrawalController::class, 'updateSettings'])->name('settings.update');
    });
});
