<?php

namespace Plugin\Dropshipping\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Plugin\Dropshipping\Models\WooCommerceConfig;
use Plugin\Dropshipping\Services\WooCommerceApiService;

class DropshippingAdminController extends Controller
{
    /**
     * Show admin dashboard
     */
    public function dashboard()
    {
        $stats = [
            'total_configs' => WooCommerceConfig::count(),
            'active_configs' => WooCommerceConfig::where('is_active', 1)->count(),
            'total_products' => DB::table('dropshipping_products')->count(),
            'products_with_pricing' => DB::table('dropshipping_products')
                ->where(function ($query) {
                    $query->where('price', '>', 0)
                        ->orWhere('regular_price', '>', 0);
                })
                ->count(),
        ];

        return view('plugin/dropshipping::admin.dashboard', compact('stats'));
    }

    /**
     * Manually sync product prices to fix zero price issues
     */
    public function syncPrices(Request $request)
    {
        try {
            $configId = $request->get('config_id');
            $configs = collect();

            if ($configId) {
                $config = WooCommerceConfig::where('is_active', 1)->find($configId);
                if ($config) {
                    $configs->push($config);
                }
            } else {
                $configs = WooCommerceConfig::where('is_active', 1)->get();
            }

            if ($configs->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active WooCommerce configurations found.'
                ]);
            }

            $totalSynced = 0;
            $errors = [];

            foreach ($configs as $config) {
                try {
                    $apiService = new WooCommerceApiService(
                        $config->store_url,
                        $config->consumer_key,
                        $config->consumer_secret
                    );

                    $result = $apiService->syncProducts($config->id, 200);

                    if ($result['success']) {
                        $totalSynced += $result['synced_count'];
                        if (!empty($result['errors'])) {
                            $errors = array_merge($errors, $result['errors']);
                        }
                    } else {
                        $errors[] = "Store {$config->name}: " . $result['message'];
                    }
                } catch (\Exception $e) {
                    $errors[] = "Store {$config->name}: " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully synced {$totalSynced} products" .
                    (!empty($errors) ? " with some errors" : ""),
                'synced_count' => $totalSynced,
                'errors' => array_slice($errors, 0, 5) // Show only first 5 errors
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get price statistics
     */
    public function getPriceStats()
    {
        $total = DB::table('dropshipping_products')->count();
        $withPrice = DB::table('dropshipping_products')->whereNotNull('price')->where('price', '>', 0)->count();
        $withRegularPrice = DB::table('dropshipping_products')->whereNotNull('regular_price')->where('regular_price', '>', 0)->count();
        $noPricing = DB::table('dropshipping_products')
            ->where(function ($query) {
                $query->whereNull('price')->orWhere('price', '<=', 0);
            })
            ->where(function ($query) {
                $query->whereNull('regular_price')->orWhere('regular_price', '<=', 0);
            })
            ->count();

        return response()->json([
            'total' => $total,
            'with_price' => $withPrice,
            'with_regular_price' => $withRegularPrice,
            'no_pricing' => $noPricing,
            'pricing_percentage' => $total > 0 ? round((($withPrice + $withRegularPrice) / $total) * 100, 1) : 0
        ]);
    }
}
