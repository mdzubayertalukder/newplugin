<?php

namespace Plugin\Dropshipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DropshippingResearchUsage extends Model
{
    use HasFactory;

    protected $table = 'dropshipping_research_usage';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'product_name',
        'research_type',
        'api_calls_used',
        'success',
        'error_message',
        'research_data',
        'researched_at'
    ];

    protected $casts = [
        'success' => 'boolean',
        'api_calls_used' => 'integer',
        'research_data' => 'array',
        'researched_at' => 'datetime'
    ];

    /**
     * Scope to filter by tenant
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter by current month
     */
    public function scopeCurrentMonth($query)
    {
        return $query->whereMonth('researched_at', now()->month)
                    ->whereYear('researched_at', now()->year);
    }

    /**
     * Scope to filter by successful research
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Scope to filter by research type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('research_type', $type);
    }

    /**
     * Get monthly research count for a tenant
     */
    public static function getMonthlyUsage($tenantId)
    {
        return self::forTenant($tenantId)
            ->currentMonth()
            ->successful()
            ->count();
    }

    /**
     * Get total research count for a tenant
     */
    public static function getTotalUsage($tenantId)
    {
        return self::forTenant($tenantId)
            ->successful()
            ->count();
    }

    /**
     * Get monthly API calls used for a tenant
     */
    public static function getMonthlyApiCalls($tenantId)
    {
        return self::forTenant($tenantId)
            ->currentMonth()
            ->successful()
            ->sum('api_calls_used');
    }

    /**
     * Get total API calls used for a tenant
     */
    public static function getTotalApiCalls($tenantId)
    {
        return self::forTenant($tenantId)
            ->successful()
            ->sum('api_calls_used');
    }

    /**
     * Record a research usage
     */
    public static function recordUsage($tenantId, $productId, $productName, $researchType = 'full_research', $apiCalls = 1, $success = true, $errorMessage = null, $researchData = null)
    {
        return self::create([
            'tenant_id' => $tenantId,
            'product_id' => $productId,
            'product_name' => $productName,
            'research_type' => $researchType,
            'api_calls_used' => $apiCalls,
            'success' => $success,
            'error_message' => $errorMessage,
            'research_data' => $researchData,
            'researched_at' => now()
        ]);
    }

    /**
     * Get research usage statistics for a tenant
     */
    public static function getUsageStats($tenantId)
    {
        $stats = [
            'monthly_research_count' => self::getMonthlyUsage($tenantId),
            'total_research_count' => self::getTotalUsage($tenantId),
            'monthly_api_calls' => self::getMonthlyApiCalls($tenantId),
            'total_api_calls' => self::getTotalApiCalls($tenantId),
            'by_type' => []
        ];

        // Get usage by research type
        $typeStats = self::forTenant($tenantId)
            ->currentMonth()
            ->successful()
            ->selectRaw('research_type, COUNT(*) as count, SUM(api_calls_used) as api_calls')
            ->groupBy('research_type')
            ->get()
            ->keyBy('research_type');

        foreach (['full_research', 'price_comparison', 'seo_analysis', 'competitor_analysis'] as $type) {
            $stats['by_type'][$type] = [
                'count' => $typeStats[$type]->count ?? 0,
                'api_calls' => $typeStats[$type]->api_calls ?? 0
            ];
        }

        return $stats;
    }
} 