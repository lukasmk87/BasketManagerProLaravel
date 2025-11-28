<?php

namespace App\Services\Stripe\Analytics;

use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Analytics Cache Manager
 *
 * Zentralisierte Cache-Strategie für alle Subscription Analytics.
 * Verwaltet Cache-Keys, TTLs und Invalidierung.
 */
class AnalyticsCacheManager
{
    /**
     * Cache TTL constants (in seconds)
     */
    public const CACHE_TTL_MRR = 3600;      // 1 hour
    public const CACHE_TTL_CHURN = 86400;   // 24 hours
    public const CACHE_TTL_LTV = 21600;     // 6 hours
    public const CACHE_TTL_HEALTH = 1800;   // 30 minutes

    /**
     * Cache key prefix for analytics
     */
    private const CACHE_PREFIX = 'subscription:';

    /**
     * Get MRR cache key for tenant.
     */
    public function getMRRCacheKey(Tenant $tenant): string
    {
        return self::CACHE_PREFIX . "mrr:{$tenant->id}";
    }

    /**
     * Get historical MRR cache key for tenant.
     */
    public function getHistoricalMRRCacheKey(Tenant $tenant, int $months): string
    {
        return self::CACHE_PREFIX . "mrr:historical:{$tenant->id}:{$months}";
    }

    /**
     * Get MRR by plan cache key for tenant.
     */
    public function getMRRByPlanCacheKey(Tenant $tenant): string
    {
        return self::CACHE_PREFIX . "mrr:byplan:{$tenant->id}";
    }

    /**
     * Get churn cache key for tenant and month.
     */
    public function getChurnCacheKey(Tenant $tenant, Carbon $month): string
    {
        return self::CACHE_PREFIX . "churn:{$tenant->id}:" . $month->format('Y-m');
    }

    /**
     * Get churn by plan cache key for tenant.
     */
    public function getChurnByPlanCacheKey(Tenant $tenant, int $months): string
    {
        return self::CACHE_PREFIX . "churn:byplan:{$tenant->id}:{$months}";
    }

    /**
     * Get LTV cache key for tenant.
     */
    public function getLTVCacheKey(Tenant $tenant): string
    {
        return self::CACHE_PREFIX . "ltv:{$tenant->id}";
    }

    /**
     * Get LTV by plan cache key for tenant.
     */
    public function getLTVByPlanCacheKey(Tenant $tenant): string
    {
        return self::CACHE_PREFIX . "ltv:byplan:{$tenant->id}";
    }

    /**
     * Get cohort analysis cache key.
     */
    public function getCohortCacheKey(Tenant $tenant, string $cohortMonth): string
    {
        return self::CACHE_PREFIX . "cohort:{$tenant->id}:{$cohortMonth}";
    }

    /**
     * Get customer lifetime stats cache key.
     */
    public function getLifetimeStatsCacheKey(Tenant $tenant): string
    {
        return self::CACHE_PREFIX . "lifetime:{$tenant->id}";
    }

    /**
     * Get health metrics cache key for tenant.
     */
    public function getHealthCacheKey(Tenant $tenant, string $metric): string
    {
        return self::CACHE_PREFIX . "health:{$tenant->id}:{$metric}";
    }

    /**
     * Clear all analytics cache for a tenant.
     */
    public function clearTenantCache(Tenant $tenant): void
    {
        $patterns = [
            $this->getMRRCacheKey($tenant),
            $this->getLTVCacheKey($tenant),
            self::CACHE_PREFIX . "mrr:*:{$tenant->id}*",
            self::CACHE_PREFIX . "churn:*:{$tenant->id}*",
            self::CACHE_PREFIX . "ltv:*:{$tenant->id}*",
            self::CACHE_PREFIX . "cohort:{$tenant->id}:*",
            self::CACHE_PREFIX . "lifetime:{$tenant->id}",
            self::CACHE_PREFIX . "health:{$tenant->id}:*",
        ];

        foreach ($patterns as $pattern) {
            // For simple keys, use forget
            if (!str_contains($pattern, '*')) {
                Cache::forget($pattern);
            }
        }

        // Clear with tags if available
        if (method_exists(Cache::getStore(), 'tags')) {
            Cache::tags(['analytics', "tenant:{$tenant->id}"])->flush();
        }
    }

    /**
     * Clear all analytics cache across all tenants.
     */
    public function clearAllAnalyticsCache(): void
    {
        if (method_exists(Cache::getStore(), 'tags')) {
            Cache::tags(['analytics'])->flush();
        }
    }

    /**
     * Clear MRR-specific cache for a tenant.
     */
    public function clearMRRCache(Tenant $tenant): void
    {
        Cache::forget($this->getMRRCacheKey($tenant));
        Cache::forget($this->getMRRByPlanCacheKey($tenant));
    }

    /**
     * Clear churn-specific cache for a tenant.
     */
    public function clearChurnCache(Tenant $tenant): void
    {
        // Clear current month's churn cache
        Cache::forget($this->getChurnCacheKey($tenant, now()));
        Cache::forget($this->getChurnCacheKey($tenant, now()->subMonth()));
    }

    /**
     * Clear LTV-specific cache for a tenant.
     */
    public function clearLTVCache(Tenant $tenant): void
    {
        Cache::forget($this->getLTVCacheKey($tenant));
        Cache::forget($this->getLTVByPlanCacheKey($tenant));
        Cache::forget($this->getLifetimeStatsCacheKey($tenant));
    }
}
