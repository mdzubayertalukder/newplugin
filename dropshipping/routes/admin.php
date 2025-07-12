<?php

use Illuminate\Support\Facades\Route;
use Plugin\Dropshipping\Http\Controllers\Admin\WooCommerceConfigController;
use Plugin\Dropshipping\Http\Controllers\Admin\OrderManagementController as AdminOrderController;
use Plugin\Dropshipping\Http\Controllers\Admin\WithdrawalController as AdminWithdrawalController;
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

    // Plan Limits Management
    Route::get('/dropshipping/plan-limits', [WooCommerceConfigController::class, 'planLimits'])->name('plan-limits.index');
    Route::post('/dropshipping/plan-limits', [WooCommerceConfigController::class, 'storePlanLimits'])->name('plan-limits.store');
    Route::put('/dropshipping/plan-limits/{id}', [WooCommerceConfigController::class, 'updatePlanLimits'])->name('plan-limits.update');

    // Reports
    Route::get('/dropshipping/reports/imports', [WooCommerceConfigController::class, 'importReports'])->name('reports.imports');
    Route::get('/dropshipping/reports/usage', [WooCommerceConfigController::class, 'usageReports'])->name('reports.usage');

    // Settings
    Route::get('/dropshipping/settings', [WooCommerceConfigController::class, 'settings'])->name('settings.index');
    Route::post('/dropshipping/settings/update', [WooCommerceConfigController::class, 'updateSettings'])->name('settings.update');

    // Order Management Routes
    Route::prefix('dropshipping/orders')->as('orders.')->group(function () {
        Route::get('/', [AdminOrderController::class, 'index'])->name('index');

        // Debug route to check what orders exist in databases
        Route::get('/debug-orders', function () {
            try {
                $tenants = DB::connection('mysql')->table('tenants')->get();
                $allOrders = [];

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
                            $allOrders[$database] = ['error' => 'dropshipping_orders table does not exist'];
                            continue;
                        }

                        $orders = DB::connection($connectionName)->table('dropshipping_orders')
                            ->select('id', 'order_number', 'tenant_id', 'product_name', 'customer_name', 'status', 'created_at')
                            ->orderBy('id', 'desc')
                            ->get();

                        $allOrders[$database] = [
                            'count' => $orders->count(),
                            'orders' => $orders->toArray()
                        ];
                    } catch (\Exception $e) {
                        $allOrders[$database] = ['error' => $e->getMessage()];
                    }
                }

                return response()->json([
                    'tenant_databases_found' => count($tenants),
                    'orders_by_database' => $allOrders
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()]);
            }
        })->name('debug.orders');

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
