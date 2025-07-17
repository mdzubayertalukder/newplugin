<?php

namespace Plugin\Dropshipping\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Plugin\Dropshipping\Models\DropshippingPlanLimit;
use Plugin\Saas\Models\Package;

class PlanLimitsController extends Controller
{
    /**
     * Display plan limits management page
     */
    public function index()
    {
        // Get all packages with their limits
        $packages = Package::with(['dropshippingLimits'])->get();
        
        // Get packages without limits
        $packagesWithoutLimits = Package::whereDoesntHave('dropshippingLimits')->get();
        
        return view('plugin/dropshipping::admin.plan-limits.index', compact('packages', 'packagesWithoutLimits'));
    }

    /**
     * Show form to create limits for a package
     */
    public function create($packageId)
    {
        $package = Package::findOrFail($packageId);
        
        // Check if limits already exist
        $existingLimits = DropshippingPlanLimit::where('package_id', $packageId)->first();
        
        if ($existingLimits) {
            return redirect()->route('admin.dropshipping.plan-limits.edit', $packageId)
                ->with('info', 'Limits already exist for this package. Redirected to edit page.');
        }
        
        return view('plugin/dropshipping::admin.plan-limits.create', compact('package'));
    }

    /**
     * Store new plan limits
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'package_id' => 'required|exists:tl_saas_packages,id',
            'monthly_import_limit' => 'required|integer|min:-1',
            'total_import_limit' => 'required|integer|min:-1',
            'bulk_import_limit' => 'required|integer|min:-1',
            'monthly_research_limit' => 'required|integer|min:-1',
            'total_research_limit' => 'required|integer|min:-1',
            'auto_sync_enabled' => 'boolean',
            'pricing_markup_min' => 'nullable|numeric|min:0',
            'pricing_markup_max' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Check if limits already exist
        $existingLimits = DropshippingPlanLimit::where('package_id', $request->package_id)->first();
        
        if ($existingLimits) {
            return redirect()->back()
                ->with('error', 'Limits already exist for this package.')
                ->withInput();
        }

        DropshippingPlanLimit::create([
            'package_id' => $request->package_id,
            'monthly_import_limit' => $request->monthly_import_limit,
            'total_import_limit' => $request->total_import_limit,
            'bulk_import_limit' => $request->bulk_import_limit,
            'monthly_research_limit' => $request->monthly_research_limit,
            'total_research_limit' => $request->total_research_limit,
            'auto_sync_enabled' => $request->boolean('auto_sync_enabled'),
            'pricing_markup_min' => $request->pricing_markup_min,
            'pricing_markup_max' => $request->pricing_markup_max,
            'settings' => json_encode([
                'auto_update_prices' => $request->boolean('auto_update_prices'),
                'auto_update_stock' => $request->boolean('auto_update_stock'),
                'import_reviews' => $request->boolean('import_reviews')
            ])
        ]);

        return redirect()->route('admin.dropshipping.plan-limits.index')
            ->with('success', 'Plan limits created successfully.');
    }

    /**
     * Show form to edit plan limits
     */
    public function edit($packageId)
    {
        $package = Package::findOrFail($packageId);
        $limits = DropshippingPlanLimit::where('package_id', $packageId)->firstOrFail();
        
        return view('plugin/dropshipping::admin.plan-limits.edit', compact('package', 'limits'));
    }

    /**
     * Update plan limits
     */
    public function update(Request $request, $packageId)
    {
        $validator = Validator::make($request->all(), [
            'monthly_import_limit' => 'required|integer|min:-1',
            'total_import_limit' => 'required|integer|min:-1',
            'bulk_import_limit' => 'required|integer|min:-1',
            'monthly_research_limit' => 'required|integer|min:-1',
            'total_research_limit' => 'required|integer|min:-1',
            'auto_sync_enabled' => 'boolean',
            'pricing_markup_min' => 'nullable|numeric|min:0',
            'pricing_markup_max' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $limits = DropshippingPlanLimit::where('package_id', $packageId)->firstOrFail();
        
        $limits->update([
            'monthly_import_limit' => $request->monthly_import_limit,
            'total_import_limit' => $request->total_import_limit,
            'bulk_import_limit' => $request->bulk_import_limit,
            'monthly_research_limit' => $request->monthly_research_limit,
            'total_research_limit' => $request->total_research_limit,
            'auto_sync_enabled' => $request->boolean('auto_sync_enabled'),
            'pricing_markup_min' => $request->pricing_markup_min,
            'pricing_markup_max' => $request->pricing_markup_max,
            'settings' => json_encode([
                'auto_update_prices' => $request->boolean('auto_update_prices'),
                'auto_update_stock' => $request->boolean('auto_update_stock'),
                'import_reviews' => $request->boolean('import_reviews')
            ])
        ]);

        return redirect()->route('admin.dropshipping.plan-limits.index')
            ->with('success', 'Plan limits updated successfully.');
    }

    /**
     * Delete plan limits
     */
    public function destroy($packageId)
    {
        $limits = DropshippingPlanLimit::where('package_id', $packageId)->firstOrFail();
        $limits->delete();

        return redirect()->route('admin.dropshipping.plan-limits.index')
            ->with('success', 'Plan limits deleted successfully.');
    }

    /**
     * Create default limits for all packages
     */
    public function createDefaults()
    {
        $packages = Package::whereDoesntHave('dropshippingLimits')->get();
        $created = 0;

        foreach ($packages as $package) {
            $defaultLimits = [
                'package_id' => $package->id,
                'monthly_import_limit' => 10,
                'total_import_limit' => -1,
                'bulk_import_limit' => 5,
                'monthly_research_limit' => 10,
                'total_research_limit' => -1,
                'auto_sync_enabled' => false,
                'settings' => json_encode([
                    'auto_update_prices' => false,
                    'auto_update_stock' => false,
                    'import_reviews' => false
                ])
            ];

            // Adjust limits based on package type
            if ($package->type === 'free') {
                $defaultLimits['monthly_import_limit'] = 5;
                $defaultLimits['monthly_research_limit'] = 5;
                $defaultLimits['bulk_import_limit'] = 2;
            } elseif ($package->type === 'paid') {
                $defaultLimits['monthly_import_limit'] = 100;
                $defaultLimits['monthly_research_limit'] = 100;
                $defaultLimits['bulk_import_limit'] = 20;
                $defaultLimits['auto_sync_enabled'] = true;
            }

            DropshippingPlanLimit::create($defaultLimits);
            $created++;
        }

        return redirect()->route('admin.dropshipping.plan-limits.index')
            ->with('success', "Default limits created for {$created} packages.");
    }

    /**
     * Get usage statistics for a package
     */
    public function usage($packageId)
    {
        $package = Package::findOrFail($packageId);
        $limits = DropshippingPlanLimit::where('package_id', $packageId)->first();
        
        if (!$limits) {
            return redirect()->route('admin.dropshipping.plan-limits.index')
                ->with('error', 'No limits found for this package.');
        }

        // Get tenants using this package (avoid collation issue by doing separate queries)
        $tenants = [];
        try {
            $saasAccounts = DB::table('tl_saas_accounts')
                ->where('package_id', $packageId)
                ->select('tenant_id', 'store_name')
                ->get();
            
            foreach ($saasAccounts as $account) {
                try {
                    $tenantData = DB::table('tenants')
                        ->where('id', $account->tenant_id)
                        ->select('data')
                        ->first();
                    
                    if ($tenantData) {
                        $tenants[] = (object) [
                            'tenant_id' => $account->tenant_id,
                            'store_name' => $account->store_name,
                            'data' => $tenantData->data
                        ];
                    }
                } catch (\Exception $e) {
                    // Skip this tenant if there's an issue
                    \Log::warning("Failed to get tenant data for tenant {$account->tenant_id}: " . $e->getMessage());
                    continue;
                }
            }
        } catch (\Exception $e) {
            // If we can't get saas accounts, log the error and continue with empty array
            \Log::error("Failed to get saas accounts for package {$packageId}: " . $e->getMessage());
            $tenants = [];
        }

        $usageStats = [];
        
        foreach ($tenants as $tenant) {
            try {
                $stats = $limits->getUsageStats($tenant->tenant_id);
                $usageStats[] = [
                    'tenant_id' => $tenant->tenant_id,
                    'store_name' => $tenant->store_name ?? 'Unknown Store',
                    'stats' => $stats
                ];
            } catch (\Exception $e) {
                // Log error but continue processing other tenants
                \Log::warning("Failed to get usage stats for tenant {$tenant->tenant_id}: " . $e->getMessage());
                continue;
            }
        }

        return view('plugin/dropshipping::admin.plan-limits.usage', compact('package', 'limits', 'usageStats'));
    }
} 