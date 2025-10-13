<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantPlanCustomization;
use App\Exceptions\UsageQuotaExceededException;
use Illuminate\Support\Facades\Log;

class LimitEnforcementService
{
    private ?Tenant $tenant = null;
    private ?TenantPlanCustomization $customization = null;

    public function __construct()
    {
        $this->tenant = app('tenant');

        if ($this->tenant) {
            $this->customization = $this->tenant->activeCustomization;
        }
    }

    /**
     * Set tenant for limit checking.
     */
    public function setTenant(?Tenant $tenant): self
    {
        $this->tenant = $tenant;

        if ($this->tenant) {
            $this->customization = $this->tenant->activeCustomization;
        }

        return $this;
    }

    /**
     * Check if tenant can create a new team.
     */
    public function canCreateTeam(): bool
    {
        if (!$this->tenant) {
            return false;
        }

        $limit = $this->getLimit('teams');

        // -1 means unlimited
        if ($limit === -1) {
            return true;
        }

        $currentCount = $this->tenant->current_teams_count ?? $this->tenant->teams()->count();

        return $currentCount < $limit;
    }

    /**
     * Enforce team creation limit.
     */
    public function enforceTeamLimit(): void
    {
        if (!$this->canCreateTeam()) {
            $limit = $this->getLimit('teams');

            throw new UsageQuotaExceededException(
                "Team-Limit erreicht. Ihr aktueller Plan erlaubt maximal {$limit} Teams. Bitte upgraden Sie Ihr Abo, um weitere Teams zu erstellen."
            );
        }
    }

    /**
     * Check if tenant can create a new player.
     */
    public function canCreatePlayer(): bool
    {
        if (!$this->tenant) {
            return false;
        }

        $limit = $this->getLimit('players');

        // -1 means unlimited
        if ($limit === -1) {
            return true;
        }

        $currentCount = $this->tenant->players()->count();

        return $currentCount < $limit;
    }

    /**
     * Enforce player creation limit.
     */
    public function enforcePlayerLimit(): void
    {
        if (!$this->canCreatePlayer()) {
            $limit = $this->getLimit('players');

            throw new UsageQuotaExceededException(
                "Spieler-Limit erreicht. Ihr aktueller Plan erlaubt maximal {$limit} Spieler. Bitte upgraden Sie Ihr Abo, um weitere Spieler hinzuzufügen."
            );
        }
    }

    /**
     * Check if tenant can create a new user.
     */
    public function canCreateUser(): bool
    {
        if (!$this->tenant) {
            return false;
        }

        $limit = $this->getLimit('users');

        // -1 means unlimited
        if ($limit === -1) {
            return true;
        }

        $currentCount = $this->tenant->current_users_count ?? $this->tenant->users()->count();

        return $currentCount < $limit;
    }

    /**
     * Enforce user creation limit.
     */
    public function enforceUserLimit(): void
    {
        if (!$this->canCreateUser()) {
            $limit = $this->getLimit('users');

            throw new UsageQuotaExceededException(
                "Benutzer-Limit erreicht. Ihr aktueller Plan erlaubt maximal {$limit} Benutzer. Bitte upgraden Sie Ihr Abo, um weitere Benutzer hinzuzufügen."
            );
        }
    }

    /**
     * Check if tenant can store more data.
     */
    public function canStoreData(float $additionalGb): bool
    {
        if (!$this->tenant) {
            return false;
        }

        $limit = $this->getLimit('storage_gb');

        // -1 means unlimited
        if ($limit === -1) {
            return true;
        }

        $currentUsage = $this->tenant->current_storage_gb ?? 0;

        return ($currentUsage + $additionalGb) <= $limit;
    }

    /**
     * Enforce storage limit.
     */
    public function enforceStorageLimit(float $additionalGb): void
    {
        if (!$this->canStoreData($additionalGb)) {
            $limit = $this->getLimit('storage_gb');
            $current = $this->tenant->current_storage_gb ?? 0;

            throw new UsageQuotaExceededException(
                "Speicher-Limit erreicht. Ihr aktueller Plan erlaubt maximal {$limit} GB. Aktuell verwendet: {$current} GB. Bitte upgraden Sie Ihr Abo."
            );
        }
    }

    /**
     * Check if tenant can create a game.
     */
    public function canCreateGame(): bool
    {
        if (!$this->tenant) {
            return false;
        }

        $limit = $this->getLimit('games_per_month');

        // -1 means unlimited
        if ($limit === -1) {
            return true;
        }

        $currentCount = $this->tenant->games()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        return $currentCount < $limit;
    }

    /**
     * Enforce game creation limit.
     */
    public function enforceGameLimit(): void
    {
        if (!$this->canCreateGame()) {
            $limit = $this->getLimit('games_per_month');

            throw new UsageQuotaExceededException(
                "Spiele-Limit für diesen Monat erreicht. Ihr aktueller Plan erlaubt maximal {$limit} Spiele pro Monat. Bitte upgraden Sie Ihr Abo."
            );
        }
    }

    /**
     * Check if tenant can create a training session.
     */
    public function canCreateTrainingSession(): bool
    {
        if (!$this->tenant) {
            return false;
        }

        $limit = $this->getLimit('training_sessions_per_month');

        // -1 means unlimited
        if ($limit === -1) {
            return true;
        }

        $currentCount = $this->tenant->trainingSessions()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        return $currentCount < $limit;
    }

    /**
     * Enforce training session creation limit.
     */
    public function enforceTrainingSessionLimit(): void
    {
        if (!$this->canCreateTrainingSession()) {
            $limit = $this->getLimit('training_sessions_per_month');

            throw new UsageQuotaExceededException(
                "Trainingseinheiten-Limit für diesen Monat erreicht. Ihr aktueller Plan erlaubt maximal {$limit} Trainingseinheiten pro Monat. Bitte upgraden Sie Ihr Abo."
            );
        }
    }

    /**
     * Get limit for a specific metric.
     */
    public function getLimit(string $metric): int|float
    {
        // First check if there's an active customization
        if ($this->customization && $this->customization->isActive()) {
            $limits = $this->customization->getEffectiveLimits();

            if (isset($limits[$metric])) {
                return $limits[$metric];
            }
        }

        // Then check if tenant has a subscription plan
        if ($this->tenant && $this->tenant->subscriptionPlan) {
            return $this->tenant->subscriptionPlan->getLimit($metric);
        }

        // Fall back to config-based limits
        if ($this->tenant) {
            $tierLimits = config("tenants.tiers.{$this->tenant->subscription_tier}.limits", []);

            return $tierLimits[$metric] ?? 0;
        }

        return 0;
    }

    /**
     * Get all limits for current tenant.
     */
    public function getAllLimits(): array
    {
        if (!$this->tenant) {
            return [];
        }

        $metrics = ['users', 'teams', 'players', 'storage_gb', 'api_calls_per_hour', 'games_per_month', 'training_sessions_per_month'];
        $limits = [];

        foreach ($metrics as $metric) {
            $limits[$metric] = [
                'limit' => $this->getLimit($metric),
                'current' => $this->getCurrentUsage($metric),
                'percentage' => $this->getUsagePercentage($metric),
                'unlimited' => $this->getLimit($metric) === -1,
            ];
        }

        return $limits;
    }

    /**
     * Get current usage for a metric.
     */
    public function getCurrentUsage(string $metric): int|float
    {
        if (!$this->tenant) {
            return 0;
        }

        return match($metric) {
            'users' => $this->tenant->current_users_count ?? $this->tenant->users()->count(),
            'teams' => $this->tenant->current_teams_count ?? $this->tenant->teams()->count(),
            'players' => $this->tenant->players()->count(),
            'storage_gb' => $this->tenant->current_storage_gb ?? 0,
            'api_calls_per_hour' => $this->getApiCallsLastHour(),
            'games_per_month' => $this->getGamesThisMonth(),
            'training_sessions_per_month' => $this->getTrainingSessionsThisMonth(),
            default => 0,
        };
    }

    /**
     * Get usage percentage for a metric.
     */
    public function getUsagePercentage(string $metric): float
    {
        $limit = $this->getLimit($metric);

        if ($limit === -1 || $limit == 0) {
            return 0;
        }

        $current = $this->getCurrentUsage($metric);

        return min(100, round(($current / $limit) * 100, 1));
    }

    /**
     * Check if tenant is approaching limit (>80%).
     */
    public function isApproachingLimit(string $metric): bool
    {
        return $this->getUsagePercentage($metric) > 80;
    }

    /**
     * Get API calls in the last hour.
     */
    private function getApiCallsLastHour(): int
    {
        if (!$this->tenant) {
            return 0;
        }

        return $this->tenant->apiUsage()
            ->where('created_at', '>=', now()->subHour())
            ->sum('request_count');
    }

    /**
     * Get games created this month.
     */
    private function getGamesThisMonth(): int
    {
        if (!$this->tenant) {
            return 0;
        }

        return $this->tenant->games()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();
    }

    /**
     * Get training sessions created this month.
     */
    private function getTrainingSessionsThisMonth(): int
    {
        if (!$this->tenant) {
            return 0;
        }

        return $this->tenant->trainingSessions()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();
    }

    /**
     * Track resource creation (to update counters).
     */
    public function trackResourceCreation(string $resourceType): void
    {
        if (!$this->tenant) {
            return;
        }

        try {
            match($resourceType) {
                'team' => $this->tenant->increment('current_teams_count'),
                'user' => $this->tenant->increment('current_users_count'),
                default => null,
            };

            Log::info("Resource created", [
                'tenant_id' => $this->tenant->id,
                'resource_type' => $resourceType,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to track resource creation", [
                'tenant_id' => $this->tenant->id,
                'resource_type' => $resourceType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Track resource deletion (to update counters).
     */
    public function trackResourceDeletion(string $resourceType): void
    {
        if (!$this->tenant) {
            return;
        }

        try {
            match($resourceType) {
                'team' => $this->tenant->decrement('current_teams_count'),
                'user' => $this->tenant->decrement('current_users_count'),
                default => null,
            };

            Log::info("Resource deleted", [
                'tenant_id' => $this->tenant->id,
                'resource_type' => $resourceType,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to track resource deletion", [
                'tenant_id' => $this->tenant->id,
                'resource_type' => $resourceType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
