<?php

namespace Plugin\Dropshipping\Services;

use Plugin\Dropshipping\Models\DropshippingPlanLimit;
use Plugin\Dropshipping\Models\DropshippingResearchUsage;
use Plugin\Dropshipping\Models\ProductImportHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LimitService
{
    /**
     * Get plan limits for a tenant
     */
    public static function getTenantLimits($tenantId)
    {
        $limits = DropshippingPlanLimit::getForTenant($tenantId);
        
        if (!$limits) {
            // Create default limits if none exist
            $limits = self::createDefaultLimits($tenantId);
        }
        
        return $limits;
    }

    /**
     * Check if tenant can import products
     */
    public static function canImport($tenantId, $quantity = 1)
    {
        $limits = self::getTenantLimits($tenantId);
        
        if (!$limits) {
            return [
                'allowed' => false,
                'reason' => 'no_limits_configured',
                'message' => 'No limits configured for your plan. Please contact administrator.'
            ];
        }
        
        return $limits->canImport($tenantId, $quantity);
    }

    /**
     * Check if tenant can perform product research
     */
    public static function canResearch($tenantId)
    {
        $limits = self::getTenantLimits($tenantId);
        
        if (!$limits) {
            return [
                'allowed' => false,
                'reason' => 'no_limits_configured',
                'message' => 'No limits configured for your plan. Please contact administrator.'
            ];
        }
        
        return $limits->canResearch($tenantId);
    }

    /**
     * Record product import usage
     */
    public static function recordImportUsage($tenantId, $productId, $success = true, $errorMessage = null)
    {
        try {
            // This would typically be handled by the existing ProductImportHistory model
            // We just need to ensure it's properly tracked
            Log::info("Import usage recorded for tenant {$tenantId}, product {$productId}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to record import usage: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Record product research usage
     */
    public static function recordResearchUsage($tenantId, $productId, $productName, $researchType = 'full_research', $apiCalls = 1, $success = true, $errorMessage = null, $researchData = null)
    {
        try {
            return DropshippingResearchUsage::recordUsage(
                $tenantId,
                $productId,
                $productName,
                $researchType,
                $apiCalls,
                $success,
                $errorMessage,
                $researchData
            );
        } catch (\Exception $e) {
            Log::error("Failed to record research usage: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get usage statistics for a tenant
     */
    public static function getUsageStats($tenantId)
    {
        $limits = self::getTenantLimits($tenantId);
        
        if (!$limits) {
            return [
                'error' => 'No limits configured for your plan'
            ];
        }
        
        return $limits->getUsageStats($tenantId);
    }

    /**
     * Get formatted usage display for tenant
     */
    public static function getUsageDisplay($tenantId)
    {
        $stats = self::getUsageStats($tenantId);
        
        if (isset($stats['error'])) {
            return $stats;
        }
        
        $display = [
            'imports' => [
                'monthly' => [
                    'used' => $stats['imports']['monthly_imports'],
                    'limit' => $stats['limits']['monthly_import_limit'],
                    'remaining' => $stats['remaining']['monthly_imports'],
                    'percentage' => self::calculatePercentage($stats['imports']['monthly_imports'], $stats['limits']['monthly_import_limit']),
                    'display' => self::formatLimitDisplay($stats['imports']['monthly_imports'], $stats['limits']['monthly_import_limit'])
                ],
                'total' => [
                    'used' => $stats['imports']['total_imports'],
                    'limit' => $stats['limits']['total_import_limit'],
                    'remaining' => $stats['remaining']['total_imports'],
                    'percentage' => self::calculatePercentage($stats['imports']['total_imports'], $stats['limits']['total_import_limit']),
                    'display' => self::formatLimitDisplay($stats['imports']['total_imports'], $stats['limits']['total_import_limit'])
                ]
            ],
            'research' => [
                'monthly' => [
                    'used' => $stats['research']['monthly_research_count'],
                    'limit' => $stats['limits']['monthly_research_limit'],
                    'remaining' => $stats['remaining']['monthly_research'],
                    'percentage' => self::calculatePercentage($stats['research']['monthly_research_count'], $stats['limits']['monthly_research_limit']),
                    'display' => self::formatLimitDisplay($stats['research']['monthly_research_count'], $stats['limits']['monthly_research_limit'])
                ],
                'total' => [
                    'used' => $stats['research']['total_research_count'],
                    'limit' => $stats['limits']['total_research_limit'],
                    'remaining' => $stats['remaining']['total_research'],
                    'percentage' => self::calculatePercentage($stats['research']['total_research_count'], $stats['limits']['total_research_limit']),
                    'display' => self::formatLimitDisplay($stats['research']['total_research_count'], $stats['limits']['total_research_limit'])
                ]
            ],
            'bulk_import_limit' => $stats['limits']['bulk_import_limit']
        ];
        
        return $display;
    }

    /**
     * Calculate percentage of limit used
     */
    private static function calculatePercentage($used, $limit)
    {
        if ($limit === -1) {
            return 0; // Unlimited
        }
        
        if ($limit === 0) {
            return 100;
        }
        
        return min(100, round(($used / $limit) * 100, 1));
    }

    /**
     * Format limit display string
     */
    private static function formatLimitDisplay($used, $limit)
    {
        if ($limit === -1) {
            return $used . ' / Unlimited';
        }
        
        return $used . ' / ' . $limit;
    }

    /**
     * Create default limits for a tenant
     */
    private static function createDefaultLimits($tenantId)
    {
        try {
            // Get tenant's package from main database
            $saasAccount = DB::connection('mysql')
                ->table('tl_saas_accounts')
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$saasAccount) {
                return null;
            }

            // Get package details
            $package = DB::connection('mysql')
                ->table('tl_saas_packages')
                ->where('id', $saasAccount->package_id)
                ->first();

            if (!$package) {
                return null;
            }

            // Create default limits based on package type
            $defaultLimits = [
                'package_id' => $saasAccount->package_id,
                'monthly_import_limit' => 10, // Basic default
                'total_import_limit' => -1,
                'bulk_import_limit' => 5,
                'monthly_research_limit' => 10, // Basic default
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

            // Create in main database
            $limitId = DB::connection('mysql')
                ->table('dropshipping_plan_limits')
                ->insertGetId($defaultLimits);

            return DropshippingPlanLimit::find($limitId);

        } catch (\Exception $e) {
            Log::error("Failed to create default limits for tenant {$tenantId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get upgrade plan message
     */
    public static function getUpgradeMessage($reason)
    {
        $messages = [
            'monthly_limit_reached' => 'You have reached your monthly limit. Upgrade your plan to get more imports and research capabilities.',
            'total_limit_reached' => 'You have reached your total limit. Upgrade your plan to continue importing and researching products.',
            'monthly_limit_would_exceed' => 'This action would exceed your monthly limit. Upgrade your plan or wait for next month.',
            'total_limit_would_exceed' => 'This action would exceed your total limit. Upgrade your plan to continue.',
            'no_limits_configured' => 'No limits configured for your plan. Please contact administrator or upgrade your plan.'
        ];
        
        return $messages[$reason] ?? 'Please upgrade your plan to continue using this feature.';
    }

    /**
     * Get tenant package information
     */
    public static function getTenantPackageInfo($tenantId)
    {
        try {
            $saasAccount = DB::connection('mysql')
                ->table('tl_saas_accounts')
                ->join('tl_saas_packages', 'tl_saas_packages.id', '=', 'tl_saas_accounts.package_id')
                ->leftJoin('tl_saas_package_plans', 'tl_saas_package_plans.id', '=', 'tl_saas_accounts.package_plan')
                ->where('tl_saas_accounts.tenant_id', $tenantId)
                ->select([
                    'tl_saas_accounts.package_id',
                    'tl_saas_accounts.package_plan',
                    'tl_saas_packages.name as package_name',
                    'tl_saas_packages.type as package_type',
                    'tl_saas_package_plans.name as plan_name'
                ])
                ->first();

            return $saasAccount;
        } catch (\Exception $e) {
            Log::error("Failed to get tenant package info: " . $e->getMessage());
            return null;
        }
    }
} 