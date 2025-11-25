<?php

namespace App\Services;

use App\Models\Club;
use App\Models\ClubUsage;
use App\Exceptions\UsageQuotaExceededException;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClubUsageTrackingService
{
    /**
     * Track resource usage for a club.
     *
     * @param Club $club The club to track usage for
     * @param string $metric The metric to track (e.g., 'max_teams', 'max_players')
     * @param int $amount The amount to add (default: 1)
     * @param array $metadata Optional metadata for context
     * @return void
     */
    public function trackResource(Club $club, string $metric, int $amount = 1, array $metadata = []): void
    {
        // Update Redis cache for real-time tracking
        $cacheKey = $this->getCacheKey($club->id, $metric);
        $currentUsage = (int) Redis::get($cacheKey) ?: 0;

        // Set expiry to 31 days (covers full month + buffer)
        Redis::setex($cacheKey, 86400 * 31, $currentUsage + $amount);

        // Update database for persistence
        $this->updateDatabaseUsage($club, $metric, $amount, $metadata);

        Log::info('Club resource tracked', [
            'club_id' => $club->id,
            'club_name' => $club->name,
            'metric' => $metric,
            'amount' => $amount,
            'new_usage' => $currentUsage + $amount,
        ]);
    }

    /**
     * Remove tracked resource usage (for deletions).
     *
     * @param Club $club The club to untrack usage for
     * @param string $metric The metric to untrack
     * @param int $amount The amount to subtract (default: 1)
     * @return void
     */
    public function untrackResource(Club $club, string $metric, int $amount = 1): void
    {
        // Update Redis cache
        $cacheKey = $this->getCacheKey($club->id, $metric);
        $currentUsage = (int) Redis::get($cacheKey) ?: 0;
        $newUsage = max(0, $currentUsage - $amount); // Prevent negative usage

        Redis::setex($cacheKey, 86400 * 31, $newUsage);

        // Update database
        $this->updateDatabaseUsage($club, $metric, -$amount);

        Log::info('Club resource untracked', [
            'club_id' => $club->id,
            'club_name' => $club->name,
            'metric' => $metric,
            'amount' => $amount,
            'new_usage' => $newUsage,
        ]);
    }

    /**
     * Synchronize usage by recalculating from actual database counts.
     *
     * Useful for:
     * - Initial sync when enabling usage tracking
     * - Fixing discrepancies between cached and actual usage
     * - Periodic reconciliation
     *
     * @param Club $club The club to sync
     * @param array|null $metrics Specific metrics to sync (null = all metrics)
     * @return array Synced usage data
     */
    public function syncClubUsage(Club $club, ?array $metrics = null): array
    {
        $metricsToSync = $metrics ?? ['max_teams', 'max_players', 'max_games_per_month', 'max_training_sessions_per_month'];
        $syncedData = [];

        foreach ($metricsToSync as $metric) {
            $actualUsage = $this->calculateActualUsage($club, $metric);

            // Update both Redis and database
            $cacheKey = $this->getCacheKey($club->id, $metric);
            Redis::setex($cacheKey, 86400 * 31, $actualUsage);

            $this->setDatabaseUsage($club, $metric, $actualUsage);

            $syncedData[$metric] = $actualUsage;
        }

        Log::info('Club usage synced', [
            'club_id' => $club->id,
            'club_name' => $club->name,
            'synced_metrics' => $syncedData,
        ]);

        return $syncedData;
    }

    /**
     * SEC-008: Set storage usage directly with a specific value.
     *
     * This method is used by the storage sync command to set the calculated
     * storage usage value directly, bypassing the automatic calculation.
     *
     * @param Club $club The club to update
     * @param string $metric The storage metric (e.g., 'max_storage_gb')
     * @param float|int $value The storage value (in GB for storage metrics)
     * @return void
     */
    public function setStorageUsage(Club $club, string $metric, float|int $value): void
    {
        // Convert to integer (usage_count is stored as int, GB with 3 decimal precision)
        // We store as millibytes (value * 1000) to preserve precision
        $storageValue = (int) round($value * 1000);

        // Update Redis cache
        $cacheKey = $this->getCacheKey($club->id, $metric);
        Redis::setex($cacheKey, 86400 * 31, $storageValue);

        // Update database
        $this->setDatabaseUsage($club, $metric, $storageValue);

        Log::debug('Storage usage set directly', [
            'club_id' => $club->id,
            'metric' => $metric,
            'value_gb' => $value,
            'stored_value' => $storageValue,
        ]);
    }

    /**
     * SEC-008: Get storage usage in GB (converts from internal representation).
     *
     * @param Club $club The club
     * @param string $metric The storage metric
     * @return float Storage usage in GB
     */
    public function getStorageUsageGB(Club $club, string $metric = 'max_storage_gb'): float
    {
        $rawValue = $this->getCurrentUsage($club, $metric);
        // Convert from millibytes back to GB
        return $rawValue / 1000;
    }

    /**
     * Check if club can use a resource (respects limits).
     *
     * @param Club $club The club to check
     * @param string $metric The metric to check
     * @param int $amount The amount to check (default: 1)
     * @return bool True if usage is within limits
     */
    public function checkLimit(Club $club, string $metric, int $amount = 1): bool
    {
        $limit = $club->getLimit($metric);

        // -1 means unlimited
        if ($limit === -1) {
            return true;
        }

        $currentUsage = $this->getCurrentUsage($club, $metric);

        return ($currentUsage + $amount) <= $limit;
    }

    /**
     * Require that club can use a resource, throw exception if over limit.
     *
     * @param Club $club The club to check
     * @param string $metric The metric to check
     * @param int $amount The amount to check (default: 1)
     * @throws UsageQuotaExceededException If quota is exceeded
     * @return void
     */
    public function requireLimit(Club $club, string $metric, int $amount = 1): void
    {
        if (!$this->checkLimit($club, $metric, $amount)) {
            $limit = $club->getLimit($metric);
            $currentUsage = $this->getCurrentUsage($club, $metric);

            throw new UsageQuotaExceededException(
                "Club '{$club->name}' has exceeded the usage quota for '{$metric}'. " .
                "Current: {$currentUsage}, Limit: {$limit}, Attempted: +{$amount}. " .
                "Please upgrade your subscription plan to continue."
            );
        }
    }

    /**
     * Get current usage for a specific metric.
     *
     * @param Club $club The club to get usage for
     * @param string $metric The metric to get usage for
     * @return int Current usage count
     */
    public function getCurrentUsage(Club $club, string $metric): int
    {
        // Try Redis first for real-time data
        $cacheKey = $this->getCacheKey($club->id, $metric);
        $usage = Redis::get($cacheKey);

        if ($usage !== null) {
            return (int) $usage;
        }

        // Fallback to database
        $clubUsage = ClubUsage::where('club_id', $club->id)
            ->where('metric', $metric)
            ->currentPeriod()
            ->sum('usage_count');

        // If no database record, calculate from actual data
        if ($clubUsage === 0) {
            $clubUsage = $this->calculateActualUsage($club, $metric);

            // Cache the calculated value
            Redis::setex($cacheKey, 86400 * 31, $clubUsage);
        }

        return $clubUsage;
    }

    /**
     * Get usage percentage for a metric.
     *
     * @param Club $club The club to get percentage for
     * @param string $metric The metric to calculate percentage for
     * @return float Percentage of limit used (0-100, or 0 for unlimited)
     */
    public function getUsagePercentage(Club $club, string $metric): float
    {
        $limit = $club->getLimit($metric);

        if ($limit === -1 || $limit === 0) {
            return 0; // Unlimited or no limit
        }

        $currentUsage = $this->getCurrentUsage($club, $metric);

        return min(100, ($currentUsage / $limit) * 100);
    }

    /**
     * Get all usage metrics for a club with percentages and limits.
     *
     * @param Club $club The club to get all usage for
     * @return array Usage data keyed by metric
     */
    public function getAllUsage(Club $club): array
    {
        $metrics = ['max_teams', 'max_players', 'max_games_per_month', 'max_training_sessions_per_month', 'max_storage_gb'];
        $usageData = [];

        foreach ($metrics as $metric) {
            $limit = $club->getLimit($metric);
            $currentUsage = $this->getCurrentUsage($club, $metric);
            $percentage = $this->getUsagePercentage($club, $metric);

            $usageData[$metric] = [
                'current' => $currentUsage,
                'limit' => $limit,
                'remaining' => $limit === -1 ? -1 : max(0, $limit - $currentUsage),
                'percentage' => $percentage,
                'unlimited' => $limit === -1,
                'near_limit' => $percentage > 80 && $percentage <= 100,
                'over_limit' => $percentage > 100,
            ];
        }

        return $usageData;
    }

    /**
     * Get usage statistics for analytics (historical data).
     *
     * @param Club $club The club to get stats for
     * @param int $days Number of days to look back (default: 30)
     * @return array Usage statistics grouped by metric
     */
    public function getUsageStats(Club $club, int $days = 30): array
    {
        return ClubUsage::where('club_id', $club->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('metric, SUM(usage_count) as total_usage, AVG(usage_count) as avg_usage, MAX(usage_count) as peak_usage')
            ->groupBy('metric')
            ->get()
            ->keyBy('metric')
            ->toArray();
    }

    /**
     * Reset usage for a specific metric (manual reset or testing).
     *
     * @param Club $club The club to reset usage for
     * @param string $metric The metric to reset
     * @return void
     */
    public function resetUsage(Club $club, string $metric): void
    {
        // Clear Redis cache
        $cacheKey = $this->getCacheKey($club->id, $metric);
        Redis::del($cacheKey);

        // Delete current period database records
        ClubUsage::where('club_id', $club->id)
            ->where('metric', $metric)
            ->currentPeriod()
            ->delete();

        Log::info('Club usage reset', [
            'club_id' => $club->id,
            'metric' => $metric,
        ]);
    }

    /**
     * Check if club is approaching any limits (>80% usage).
     *
     * @param Club $club The club to check
     * @return array Metrics that are approaching limits
     */
    public function getApproachingLimits(Club $club): array
    {
        $allUsage = $this->getAllUsage($club);

        return array_filter($allUsage, function ($data) {
            return $data['near_limit'] || $data['over_limit'];
        });
    }

    /**
     * Get recommended upgrade based on usage.
     *
     * @param Club $club The club to get recommendation for
     * @return array|null Upgrade recommendation or null
     */
    public function getUpgradeRecommendation(Club $club): ?array
    {
        $approachingLimits = $this->getApproachingLimits($club);

        if (empty($approachingLimits)) {
            return null;
        }

        $limitMetrics = array_keys($approachingLimits);
        $currentPlan = $club->subscriptionPlan;

        return [
            'current_plan' => $currentPlan ? $currentPlan->name : 'None',
            'reason' => 'You are approaching or exceeding limits',
            'affected_metrics' => $limitMetrics,
            'recommendation' => 'Consider upgrading to a higher plan to accommodate your growth',
            'metrics_detail' => $approachingLimits,
        ];
    }

    // ============================
    // PRIVATE HELPER METHODS
    // ============================

    /**
     * Get Redis cache key for club usage.
     *
     * @param int $clubId The club ID
     * @param string $metric The metric name
     * @return string Redis cache key
     */
    private function getCacheKey(int $clubId, string $metric): string
    {
        return "club_usage:{$clubId}:{$metric}:" . now()->format('Y-m');
    }

    /**
     * Update database usage (increment/decrement).
     *
     * @param Club $club The club
     * @param string $metric The metric
     * @param int $amount The amount to add/subtract
     * @param array $metadata Optional metadata
     * @return void
     */
    private function updateDatabaseUsage(Club $club, string $metric, int $amount, array $metadata = []): void
    {
        $periodStart = now()->startOfMonth();

        // ✅ SECURE: Use firstOrCreate then increment to prevent SQL injection
        $clubUsage = ClubUsage::firstOrCreate(
            [
                'club_id' => $club->id,
                'tenant_id' => $club->tenant_id,
                'metric' => $metric,
                'period_start' => $periodStart,
            ],
            [
                'usage_count' => 0,
                'last_tracked_at' => now(),
                'metadata' => $metadata ?: null,
            ]
        );

        // Increment usage_count safely (prevents negative with max())
        if ($amount > 0) {
            $clubUsage->increment('usage_count', (int)$amount);
        } elseif ($amount < 0) {
            // Prevent negative values
            $clubUsage->decrement('usage_count', min(abs((int)$amount), $clubUsage->usage_count));
        }

        // Update metadata and timestamp
        $clubUsage->update([
            'last_tracked_at' => now(),
            'metadata' => $metadata ?: null,
        ]);
    }

    /**
     * Set database usage to exact value (used in sync).
     *
     * @param Club $club The club
     * @param string $metric The metric
     * @param int $value The exact value to set
     * @return void
     */
    private function setDatabaseUsage(Club $club, string $metric, int $value): void
    {
        $periodStart = now()->startOfMonth();

        ClubUsage::updateOrCreate(
            [
                'club_id' => $club->id,
                'tenant_id' => $club->tenant_id,
                'metric' => $metric,
                'period_start' => $periodStart,
            ],
            [
                'usage_count' => $value,
                'last_tracked_at' => now(),
            ]
        );
    }

    /**
     * Calculate actual usage from database counts (expensive operation).
     *
     * @param Club $club The club
     * @param string $metric The metric to calculate
     * @return int Actual usage count
     */
    private function calculateActualUsage(Club $club, string $metric): int
    {
        return match($metric) {
            'max_teams' => $club->teams()->count(),
            'max_players' => $club->players()->count(),
            'max_games_per_month' => $club->getGames()
                ->where('game_date', '>=', now()->startOfMonth())
                ->where('game_date', '<=', now()->endOfMonth())
                ->count(),
            'max_training_sessions_per_month' => DB::table('training_sessions')
                ->join('teams', 'training_sessions.team_id', '=', 'teams.id')
                ->where('teams.club_id', $club->id)
                ->where('training_sessions.scheduled_at', '>=', now()->startOfMonth())
                ->where('training_sessions.scheduled_at', '<=', now()->endOfMonth())
                ->count(),
            'max_storage_gb' => $club->calculateStorageUsage(),
            default => 0,
        };
    }
}
