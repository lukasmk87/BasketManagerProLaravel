<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantUsage;
use App\Exceptions\FeatureNotAvailableException;
use App\Exceptions\UsageQuotaExceededException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class FeatureGateService
{
    private ?Tenant $tenant = null;
    private ?\App\Models\Club $club = null;

    public function __construct()
    {
        $this->tenant = app('tenant');
    }

    /**
     * Set the tenant for feature checking.
     */
    public function setTenant(?Tenant $tenant): self
    {
        $this->tenant = $tenant;
        return $this;
    }

    /**
     * Set the club for feature checking.
     */
    public function setClub(?\App\Models\Club $club): self
    {
        $this->club = $club;
        return $this;
    }

    /**
     * Check if a feature is available for the current tenant.
     */
    public function hasFeature(string $feature): bool
    {
        if (!$this->tenant) {
            return false;
        }

        $tierConfig = $this->getTierConfig($this->tenant->subscription_tier);
        
        if (!$tierConfig) {
            return false;
        }

        return in_array($feature, $tierConfig['features'] ?? []);
    }

    /**
     * Ensure the tenant has access to a feature, throw exception if not.
     */
    public function requireFeature(string $feature): void
    {
        if (!$this->hasFeature($feature)) {
            throw new FeatureNotAvailableException(
                "Feature '{$feature}' is not available in your current subscription tier: {$this->tenant->subscription_tier}"
            );
        }
    }

    /**
     * Check if tenant can perform an action based on usage limits.
     */
    public function canUse(string $metric, int $amount = 1): bool
    {
        if (!$this->tenant) {
            return false;
        }

        $tierConfig = $this->getTierConfig($this->tenant->subscription_tier);
        
        if (!$tierConfig || !isset($tierConfig['limits'][$metric])) {
            return true; // No limit defined
        }

        $limit = $tierConfig['limits'][$metric];
        
        // -1 means unlimited (for enterprise tier)
        if ($limit === -1) {
            return true;
        }

        $currentUsage = $this->getCurrentUsage($metric);
        
        return ($currentUsage + $amount) <= $limit;
    }

    /**
     * Ensure the tenant can use a resource, throw exception if quota exceeded.
     */
    public function requireUsage(string $metric, int $amount = 1): void
    {
        if (!$this->canUse($metric, $amount)) {
            $tierConfig = $this->getTierConfig($this->tenant->subscription_tier);
            $limit = $tierConfig['limits'][$metric] ?? 'unknown';
            
            throw new UsageQuotaExceededException(
                "Usage quota exceeded for '{$metric}'. Limit: {$limit}, attempted: {$amount}"
            );
        }
    }

    /**
     * Track usage for a metric.
     */
    public function trackUsage(string $metric, int $amount = 1): void
    {
        if (!$this->tenant) {
            return;
        }

        // Use Redis for real-time tracking
        $cacheKey = "tenant_usage:{$this->tenant->id}:{$metric}:" . now()->format('Y-m');
        $currentUsage = (int) Redis::get($cacheKey) ?: 0;
        
        Redis::setex($cacheKey, 86400 * 31, $currentUsage + $amount); // Expire after 31 days

        // Also update database for persistence
        $this->updateDatabaseUsage($metric, $amount);
    }

    /**
     * Get current usage for a metric.
     */
    public function getCurrentUsage(string $metric): int
    {
        if (!$this->tenant) {
            return 0;
        }

        // Try Redis first for real-time data
        $cacheKey = "tenant_usage:{$this->tenant->id}:{$metric}:" . now()->format('Y-m');
        $usage = Redis::get($cacheKey);
        
        if ($usage !== null) {
            return (int) $usage;
        }

        // Fallback to database
        $tenantUsage = TenantUsage::where('tenant_id', $this->tenant->id)
            ->where('metric', $metric)
            ->where('period_start', '>=', now()->startOfMonth())
            ->sum('usage_count');

        return $tenantUsage ?: 0;
    }

    /**
     * Get all current usage for the tenant.
     */
    public function getAllUsage(): array
    {
        if (!$this->tenant) {
            return [];
        }

        $tierConfig = $this->getTierConfig($this->tenant->subscription_tier);
        
        if (!$tierConfig || !isset($tierConfig['limits'])) {
            return [];
        }

        $usage = [];
        
        foreach ($tierConfig['limits'] as $metric => $limit) {
            $usage[$metric] = [
                'current' => $this->getCurrentUsage($metric),
                'limit' => $limit,
                'percentage' => $limit > 0 ? min(100, ($this->getCurrentUsage($metric) / $limit) * 100) : 0,
                'unlimited' => $limit === -1,
            ];
        }

        return $usage;
    }

    /**
     * Get the subscription tier configuration.
     */
    public function getTierConfig(?string $tier = null): ?array
    {
        $tier = $tier ?? $this->tenant?->subscription_tier ?? 'free';
        
        return config("tenants.tiers.{$tier}");
    }

    /**
     * Get all available tiers for comparison.
     */
    public function getAllTiers(): array
    {
        return config('tenants.tiers', []);
    }

    /**
     * Check if tenant can upgrade to a higher tier.
     */
    public function canUpgradeTo(string $tier): bool
    {
        if (!$this->tenant) {
            return false;
        }

        $tiers = ['free', 'basic', 'professional', 'enterprise'];
        $currentIndex = array_search($this->tenant->subscription_tier, $tiers);
        $targetIndex = array_search($tier, $tiers);

        return $targetIndex !== false && $targetIndex > $currentIndex;
    }

    /**
     * Get usage statistics for analytics.
     */
    public function getUsageStats(int $days = 30): array
    {
        if (!$this->tenant) {
            return [];
        }

        return TenantUsage::where('tenant_id', $this->tenant->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('metric, SUM(usage_count) as total_usage, COUNT(*) as total_requests')
            ->groupBy('metric')
            ->get()
            ->keyBy('metric')
            ->toArray();
    }

    /**
     * Reset usage for a specific metric (used for testing or manual resets).
     */
    public function resetUsage(string $metric): void
    {
        if (!$this->tenant) {
            return;
        }

        $cacheKey = "tenant_usage:{$this->tenant->id}:{$metric}:" . now()->format('Y-m');
        Redis::del($cacheKey);

        TenantUsage::where('tenant_id', $this->tenant->id)
            ->where('metric', $metric)
            ->where('period_start', '>=', now()->startOfMonth())
            ->delete();
    }

    /**
     * Check if tenant is in trial period.
     */
    public function isInTrial(): bool
    {
        if (!$this->tenant) {
            return false;
        }

        return $this->tenant->trial_ends_at && $this->tenant->trial_ends_at->isFuture();
    }

    /**
     * Get days remaining in trial.
     */
    public function trialDaysRemaining(): int
    {
        if (!$this->isInTrial()) {
            return 0;
        }

        return $this->tenant->trial_ends_at->diffInDays(now());
    }

    /**
     * Check if tenant has an active paid subscription.
     */
    public function hasActiveSubscription(): bool
    {
        if (!$this->tenant) {
            return false;
        }

        if ($this->tenant->subscription_tier === 'free') {
            return false;
        }

        // Check if tenant has active Stripe subscription
        return $this->tenant->subscribed('default') && !$this->tenant->subscription('default')->cancelled();
    }

    /**
     * Get recommended upgrade tier based on usage.
     */
    public function getRecommendedUpgrade(): ?string
    {
        if (!$this->tenant) {
            return null;
        }

        $usage = $this->getAllUsage();
        $tiers = $this->getAllTiers();
        
        // Check if any usage is over 80% of current limits
        foreach ($usage as $metric => $data) {
            if ($data['percentage'] > 80 && !$data['unlimited']) {
                // Find next tier that accommodates this usage
                foreach (['basic', 'professional', 'enterprise'] as $tier) {
                    if ($tier === $this->tenant->subscription_tier) {
                        continue;
                    }
                    
                    $tierConfig = $tiers[$tier] ?? null;
                    if (!$tierConfig) {
                        continue;
                    }
                    
                    $tierLimit = $tierConfig['limits'][$metric] ?? -1;
                    
                    if ($tierLimit === -1 || $data['current'] <= $tierLimit) {
                        return $tier;
                    }
                }
            }
        }

        return null;
    }

    // ============================
    // CLUB-LEVEL FEATURE METHODS
    // ============================

    /**
     * Check if club has a specific feature (considering tenant hierarchy).
     */
    public function hasClubFeature(string $feature): bool
    {
        if (!$this->club) {
            return false;
        }

        return $this->club->hasFeature($feature);
    }

    /**
     * Ensure club has access to a feature, throw exception if not.
     */
    public function requireClubFeature(string $feature): void
    {
        if (!$this->hasClubFeature($feature)) {
            $tier = $this->club->subscriptionPlan
                ? $this->club->subscriptionPlan->name
                : ($this->tenant ? $this->tenant->subscription_tier : 'unknown');

            throw new FeatureNotAvailableException(
                "Feature '{$feature}' is not available for club '{$this->club->name}' (Plan: {$tier})"
            );
        }
    }

    /**
     * Check if club can use a resource based on limits.
     */
    public function canClubUse(string $metric, int $amount = 1): bool
    {
        if (!$this->club) {
            return false;
        }

        return $this->club->canUse($metric, $amount);
    }

    /**
     * Ensure club can use a resource, throw exception if quota exceeded.
     */
    public function requireClubUsage(string $metric, int $amount = 1): void
    {
        if (!$this->canClubUse($metric, $amount)) {
            $limit = $this->club->getLimit($metric);

            throw new UsageQuotaExceededException(
                "Club '{$this->club->name}' usage quota exceeded for '{$metric}'. Limit: {$limit}"
            );
        }
    }

    /**
     * Get club subscription limits with current usage.
     */
    public function getClubLimits(): array
    {
        if (!$this->club) {
            return [];
        }

        return $this->club->getSubscriptionLimits();
    }

    /**
     * Update database usage (called from trackUsage).
     */
    private function updateDatabaseUsage(string $metric, int $amount): void
    {
        $periodStart = now()->startOfMonth();

        // ✅ SECURE: Use firstOrCreate then increment to prevent SQL injection
        $usage = TenantUsage::firstOrCreate(
            [
                'tenant_id' => $this->tenant->id,
                'metric' => $metric,
                'period_start' => $periodStart,
            ],
            [
                'usage_count' => 0,
                'last_tracked_at' => now(),
            ]
        );

        // Safely increment/decrement with type casting
        if ($amount != 0) {
            $usage->increment('usage_count', (int)$amount);
            $usage->update(['last_tracked_at' => now()]);
        }
    }
}