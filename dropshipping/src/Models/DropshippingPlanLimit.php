<?php

namespace Plugin\Dropshipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DropshippingPlanLimit extends Model
{
    use HasFactory;

    protected $table = 'dropshipping_plan_limits';

    protected $fillable = [
        'package_id',
        'monthly_import_limit',
        'total_import_limit',
        'bulk_import_limit',
        'monthly_research_limit',
        'total_research_limit',
        'auto_sync_enabled',
        'pricing_markup_min',
        'pricing_markup_max',
        'allowed_categories',
        'restricted_categories',
        'settings'
    ];

    protected $casts = [
        'monthly_import_limit' => 'integer',
        'total_import_limit' => 'integer',
        'bulk_import_limit' => 'integer',
        'monthly_research_limit' => 'integer',
        'total_research_limit' => 'integer',
        'auto_sync_enabled' => 'boolean',
        'pricing_markup_min' => 'decimal:2',
        'pricing_markup_max' => 'decimal:2',
        'allowed_categories' => 'array',
        'restricted_categories' => 'array',
        'settings' => 'array'
    ];

    /**
     * Get the package/plan
     */
    public function package()
    {
        return $this->belongsTo(\Plugin\Saas\Models\Package::class, 'package_id');
    }

    /**
     * Get tenants using this package
     */
    public function tenants()
    {
        return $this->hasMany(\Plugin\Saas\Models\SaasAccount::class, 'package_id', 'package_id');
    }

    /**
     * Check if monthly import limit is reached for a tenant
     */
    public function isMonthlyImportLimitReached($tenantId)
    {
        if ($this->monthly_import_limit === -1) {
            return false; // Unlimited
        }

        $currentMonthImports = ProductImportHistory::forTenant($tenantId)
            ->successful()
            ->whereMonth('imported_at', now()->month)
            ->whereYear('imported_at', now()->year)
            ->count();

        return $currentMonthImports >= $this->monthly_import_limit;
    }

    /**
     * Check if total import limit is reached for a tenant
     */
    public function isTotalImportLimitReached($tenantId)
    {
        if ($this->total_import_limit === -1) {
            return false; // Unlimited
        }

        $totalImports = ProductImportHistory::forTenant($tenantId)
            ->successful()
            ->count();

        return $totalImports >= $this->total_import_limit;
    }

    /**
     * Check if monthly research limit is reached for a tenant
     */
    public function isMonthlyResearchLimitReached($tenantId)
    {
        if ($this->monthly_research_limit === -1) {
            return false; // Unlimited
        }

        $currentMonthResearch = DropshippingResearchUsage::getMonthlyUsage($tenantId);

        return $currentMonthResearch >= $this->monthly_research_limit;
    }

    /**
     * Check if total research limit is reached for a tenant
     */
    public function isTotalResearchLimitReached($tenantId)
    {
        if ($this->total_research_limit === -1) {
            return false; // Unlimited
        }

        $totalResearch = DropshippingResearchUsage::getTotalUsage($tenantId);

        return $totalResearch >= $this->total_research_limit;
    }

    /**
     * Get remaining monthly imports for a tenant
     */
    public function getRemainingMonthlyImports($tenantId)
    {
        if ($this->monthly_import_limit === -1) {
            return -1; // Unlimited
        }

        $currentMonthImports = ProductImportHistory::forTenant($tenantId)
            ->successful()
            ->whereMonth('imported_at', now()->month)
            ->whereYear('imported_at', now()->year)
            ->count();

        return max(0, $this->monthly_import_limit - $currentMonthImports);
    }

    /**
     * Get remaining total imports for a tenant
     */
    public function getRemainingTotalImports($tenantId)
    {
        if ($this->total_import_limit === -1) {
            return -1; // Unlimited
        }

        $totalImports = ProductImportHistory::forTenant($tenantId)
            ->successful()
            ->count();

        return max(0, $this->total_import_limit - $totalImports);
    }

    /**
     * Get remaining monthly research for a tenant
     */
    public function getRemainingMonthlyResearch($tenantId)
    {
        if ($this->monthly_research_limit === -1) {
            return -1; // Unlimited
        }

        $currentMonthResearch = DropshippingResearchUsage::getMonthlyUsage($tenantId);

        return max(0, $this->monthly_research_limit - $currentMonthResearch);
    }

    /**
     * Get remaining total research for a tenant
     */
    public function getRemainingTotalResearch($tenantId)
    {
        if ($this->total_research_limit === -1) {
            return -1; // Unlimited
        }

        $totalResearch = DropshippingResearchUsage::getTotalUsage($tenantId);

        return max(0, $this->total_research_limit - $totalResearch);
    }

    /**
     * Check if bulk import is allowed
     */
    public function canBulkImport($quantity)
    {
        if ($this->bulk_import_limit === -1) {
            return true; // Unlimited
        }

        return $quantity <= $this->bulk_import_limit;
    }

    /**
     * Check if a tenant can import products
     */
    public function canImport($tenantId, $quantity = 1)
    {
        // Check monthly limit
        if ($this->isMonthlyImportLimitReached($tenantId)) {
            return [
                'allowed' => false,
                'reason' => 'monthly_limit_reached',
                'message' => 'Monthly import limit of ' . $this->monthly_import_limit . ' products reached. Please upgrade your plan or wait for next month.'
            ];
        }

        // Check total limit
        if ($this->isTotalImportLimitReached($tenantId)) {
            return [
                'allowed' => false,
                'reason' => 'total_limit_reached',
                'message' => 'Total import limit of ' . $this->total_import_limit . ' products reached. Please upgrade your plan.'
            ];
        }

        // Check if this import would exceed monthly limit
        $remainingMonthly = $this->getRemainingMonthlyImports($tenantId);
        if ($remainingMonthly !== -1 && $quantity > $remainingMonthly) {
            return [
                'allowed' => false,
                'reason' => 'monthly_limit_would_exceed',
                'message' => 'This import would exceed your monthly limit. You can import ' . $remainingMonthly . ' more products this month.'
            ];
        }

        // Check if this import would exceed total limit
        $remainingTotal = $this->getRemainingTotalImports($tenantId);
        if ($remainingTotal !== -1 && $quantity > $remainingTotal) {
            return [
                'allowed' => false,
                'reason' => 'total_limit_would_exceed',
                'message' => 'This import would exceed your total limit. You can import ' . $remainingTotal . ' more products.'
            ];
        }

        return [
            'allowed' => true,
            'remaining_monthly' => $remainingMonthly,
            'remaining_total' => $remainingTotal
        ];
    }

    /**
     * Check if a tenant can perform product research
     */
    public function canResearch($tenantId)
    {
        // Check monthly limit
        if ($this->isMonthlyResearchLimitReached($tenantId)) {
            return [
                'allowed' => false,
                'reason' => 'monthly_limit_reached',
                'message' => 'Monthly research limit of ' . $this->monthly_research_limit . ' researches reached. Please upgrade your plan or wait for next month.'
            ];
        }

        // Check total limit
        if ($this->isTotalResearchLimitReached($tenantId)) {
            return [
                'allowed' => false,
                'reason' => 'total_limit_reached',
                'message' => 'Total research limit of ' . $this->total_research_limit . ' researches reached. Please upgrade your plan.'
            ];
        }

        return [
            'allowed' => true,
            'remaining_monthly' => $this->getRemainingMonthlyResearch($tenantId),
            'remaining_total' => $this->getRemainingTotalResearch($tenantId)
        ];
    }

    /**
     * Validate pricing markup
     */
    public function isValidMarkup($markup)
    {
        if ($this->pricing_markup_min !== null && $markup < $this->pricing_markup_min) {
            return false;
        }

        if ($this->pricing_markup_max !== null && $markup > $this->pricing_markup_max) {
            return false;
        }

        return true;
    }

    /**
     * Get usage statistics for a tenant
     */
    public function getUsageStats($tenantId)
    {
        $importStats = [
            'monthly_imports' => ProductImportHistory::forTenant($tenantId)
                ->successful()
                ->whereMonth('imported_at', now()->month)
                ->whereYear('imported_at', now()->year)
                ->count(),
            'total_imports' => ProductImportHistory::forTenant($tenantId)
                ->successful()
                ->count()
        ];

        $researchStats = DropshippingResearchUsage::getUsageStats($tenantId);

        return [
            'imports' => $importStats,
            'research' => $researchStats,
            'limits' => [
                'monthly_import_limit' => $this->monthly_import_limit,
                'total_import_limit' => $this->total_import_limit,
                'bulk_import_limit' => $this->bulk_import_limit,
                'monthly_research_limit' => $this->monthly_research_limit,
                'total_research_limit' => $this->total_research_limit
            ],
            'remaining' => [
                'monthly_imports' => $this->getRemainingMonthlyImports($tenantId),
                'total_imports' => $this->getRemainingTotalImports($tenantId),
                'monthly_research' => $this->getRemainingMonthlyResearch($tenantId),
                'total_research' => $this->getRemainingTotalResearch($tenantId)
            ]
        ];
    }

    /**
     * Get plan limits for a tenant by package ID
     */
    public static function getForTenant($tenantId)
    {
        // Get tenant's package from main database
        $saasAccount = \Illuminate\Support\Facades\DB::connection('mysql')
            ->table('tl_saas_accounts')
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$saasAccount) {
            return null;
        }

        return self::where('package_id', $saasAccount->package_id)->first();
    }
}
