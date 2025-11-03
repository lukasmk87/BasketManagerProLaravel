<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Club;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FeatureFlagService
{
    /**
     * Check if a feature is enabled.
     */
    public function isEnabled(string $featureKey, ?Tenant $tenant = null, ?Club $club = null): bool
    {
        // Development mode: all features enabled
        if (config('features.development_mode')) {
            return true;
        }

        // Get feature configuration
        $feature = config("features.flags.{$featureKey}");

        if (!$feature) {
            Log::warning("Feature flag not found: {$featureKey}");
            return false;
        }

        // Check if feature is globally disabled
        if (!$feature['enabled']) {
            return false;
        }

        // Check dependencies
        if (!$this->checkDependencies($feature)) {
            return false;
        }

        // Check beta opt-in if required
        if ($feature['beta'] && config('features.rollout.beta_opt_in_required')) {
            if (!$this->hasBetaOptIn($tenant, $club, $featureKey)) {
                return false;
            }
        }

        // Check club-level override (most specific)
        if ($club) {
            $clubOverride = $this->getClubOverride($club, $featureKey);
            if ($clubOverride !== null) {
                return $clubOverride;
            }
        }

        // Check tenant-level override
        if ($tenant) {
            $tenantOverride = $this->getTenantOverride($tenant, $featureKey);
            if ($tenantOverride !== null) {
                return $tenantOverride;
            }
        }

        // Check rollout percentage
        return $this->checkRolloutPercentage($feature, $tenant);
    }

    /**
     * Check if feature dependencies are met.
     */
    private function checkDependencies(array $feature): bool
    {
        if (empty($feature['requires'])) {
            return true;
        }

        foreach ($feature['requires'] as $requiredFeature) {
            if (!$this->isEnabled($requiredFeature)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if tenant/club has opted into beta features.
     */
    private function hasBetaOptIn(?Tenant $tenant, ?Club $club, string $featureKey): bool
    {
        if (!config('features.persistence.enabled')) {
            return false;
        }

        $cacheKey = $club
            ? "feature_beta_opt_in:club:{$club->id}:{$featureKey}"
            : "feature_beta_opt_in:tenant:{$tenant?->id}:{$featureKey}";

        return Cache::remember($cacheKey, config('features.persistence.cache_ttl'), function () use ($club, $tenant, $featureKey) {
            $query = DB::table('feature_flags')
                ->where('feature_key', $featureKey)
                ->where('is_beta_opt_in', true);

            if ($club) {
                $query->where('club_id', $club->id);
            } elseif ($tenant) {
                $query->where('tenant_id', $tenant->id)
                    ->whereNull('club_id');
            } else {
                return false;
            }

            return $query->exists();
        });
    }

    /**
     * Get club-level feature flag override.
     */
    private function getClubOverride(Club $club, string $featureKey): ?bool
    {
        if (!config('features.persistence.enabled')) {
            return null;
        }

        $cacheKey = "feature_flag:club:{$club->id}:{$featureKey}";

        return Cache::remember($cacheKey, config('features.persistence.cache_ttl'), function () use ($club, $featureKey) {
            $flag = DB::table('feature_flags')
                ->where('club_id', $club->id)
                ->where('feature_key', $featureKey)
                ->first();

            return $flag ? (bool) $flag->is_enabled : null;
        });
    }

    /**
     * Get tenant-level feature flag override.
     */
    private function getTenantOverride(Tenant $tenant, string $featureKey): ?bool
    {
        if (!config('features.persistence.enabled')) {
            return null;
        }

        $cacheKey = "feature_flag:tenant:{$tenant->id}:{$featureKey}";

        return Cache::remember($cacheKey, config('features.persistence.cache_ttl'), function () use ($tenant, $featureKey) {
            $flag = DB::table('feature_flags')
                ->where('tenant_id', $tenant->id)
                ->whereNull('club_id')
                ->where('feature_key', $featureKey)
                ->first();

            return $flag ? (bool) $flag->is_enabled : null;
        });
    }

    /**
     * Check if tenant is within rollout percentage.
     */
    private function checkRolloutPercentage(array $feature, ?Tenant $tenant): bool
    {
        $percentage = $feature['rollout_percentage'] ?? 100;

        // 100% = feature available to all
        if ($percentage >= 100) {
            return true;
        }

        // 0% = feature not available to anyone
        if ($percentage <= 0) {
            return false;
        }

        // Check whitelist method
        if (config('features.rollout.method') === 'whitelist') {
            if (!$tenant) {
                return false;
            }

            return in_array($tenant->id, config('features.rollout.whitelist_tenants', []));
        }

        // Percentage-based rollout (deterministic based on tenant ID)
        if ($tenant) {
            // Use tenant ID to determine if they're in the rollout percentage
            // This ensures consistent behavior for the same tenant
            $hash = crc32($tenant->id . $feature['name']);
            $tenantPercentage = ($hash % 100) + 1; // 1-100

            return $tenantPercentage <= $percentage;
        }

        // For non-tenant-specific checks, use random rollout
        return mt_rand(1, 100) <= $percentage;
    }

    /**
     * Enable a feature for a tenant.
     */
    public function enableForTenant(string $featureKey, Tenant $tenant): void
    {
        $this->setTenantFlag($tenant, $featureKey, true);
    }

    /**
     * Disable a feature for a tenant.
     */
    public function disableForTenant(string $featureKey, Tenant $tenant): void
    {
        $this->setTenantFlag($tenant, $featureKey, false);
    }

    /**
     * Enable a feature for a club.
     */
    public function enableForClub(string $featureKey, Club $club): void
    {
        $this->setClubFlag($club, $featureKey, true);
    }

    /**
     * Disable a feature for a club.
     */
    public function disableForClub(string $featureKey, Club $club): void
    {
        $this->setClubFlag($club, $featureKey, false);
    }

    /**
     * Opt a tenant into beta features.
     */
    public function optInToBeta(string $featureKey, Tenant $tenant): void
    {
        $this->setBetaOptIn($tenant, null, $featureKey, true);
    }

    /**
     * Opt a club into beta features.
     */
    public function optClubInToBeta(string $featureKey, Club $club): void
    {
        $this->setBetaOptIn($club->tenant, $club, $featureKey, true);
    }

    /**
     * Set tenant feature flag.
     */
    private function setTenantFlag(Tenant $tenant, string $featureKey, bool $enabled): void
    {
        if (!config('features.persistence.enabled')) {
            return;
        }

        DB::table('feature_flags')->updateOrInsert(
            [
                'tenant_id' => $tenant->id,
                'club_id' => null,
                'feature_key' => $featureKey,
            ],
            [
                'is_enabled' => $enabled,
                'updated_at' => now(),
            ]
        );

        // Clear cache
        Cache::forget("feature_flag:tenant:{$tenant->id}:{$featureKey}");

        // Log change
        if (config('features.persistence.log_changes')) {
            Log::info("Feature flag changed for tenant", [
                'tenant_id' => $tenant->id,
                'feature_key' => $featureKey,
                'enabled' => $enabled,
            ]);
        }
    }

    /**
     * Set club feature flag.
     */
    private function setClubFlag(Club $club, string $featureKey, bool $enabled): void
    {
        if (!config('features.persistence.enabled')) {
            return;
        }

        DB::table('feature_flags')->updateOrInsert(
            [
                'tenant_id' => $club->tenant_id,
                'club_id' => $club->id,
                'feature_key' => $featureKey,
            ],
            [
                'is_enabled' => $enabled,
                'updated_at' => now(),
            ]
        );

        // Clear cache
        Cache::forget("feature_flag:club:{$club->id}:{$featureKey}");

        // Log change
        if (config('features.persistence.log_changes')) {
            Log::info("Feature flag changed for club", [
                'club_id' => $club->id,
                'feature_key' => $featureKey,
                'enabled' => $enabled,
            ]);
        }
    }

    /**
     * Set beta opt-in flag.
     */
    private function setBetaOptIn(Tenant $tenant, ?Club $club, string $featureKey, bool $optIn): void
    {
        if (!config('features.persistence.enabled')) {
            return;
        }

        DB::table('feature_flags')->updateOrInsert(
            [
                'tenant_id' => $tenant->id,
                'club_id' => $club?->id,
                'feature_key' => $featureKey,
            ],
            [
                'is_beta_opt_in' => $optIn,
                'updated_at' => now(),
            ]
        );

        // Clear cache
        $cacheKey = $club
            ? "feature_beta_opt_in:club:{$club->id}:{$featureKey}"
            : "feature_beta_opt_in:tenant:{$tenant->id}:{$featureKey}";
        Cache::forget($cacheKey);
    }

    /**
     * Get all features with their status for a tenant/club.
     */
    public function getAllFeatures(?Tenant $tenant = null, ?Club $club = null): array
    {
        $features = config('features.flags', []);
        $result = [];

        foreach ($features as $key => $feature) {
            $result[$key] = [
                'name' => $feature['name'],
                'description' => $feature['description'],
                'category' => $feature['category'],
                'enabled' => $this->isEnabled($key, $tenant, $club),
                'beta' => $feature['beta'] ?? false,
                'rollout_percentage' => $feature['rollout_percentage'] ?? 100,
            ];
        }

        return $result;
    }

    /**
     * Clear all feature flag caches.
     */
    public function clearCache(): void
    {
        Cache::flush();
        Log::info('Feature flag cache cleared');
    }
}
