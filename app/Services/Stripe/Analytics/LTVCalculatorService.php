<?php

namespace App\Services\Stripe\Analytics;

use App\Models\Club;
use App\Models\ClubSubscriptionCohort;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * LTV Calculator Service
 *
 * Berechnet Customer Lifetime Value (LTV) und Cohort-Analysen.
 * Unterstützt LTV nach Plan, Cohort-Retention und Lifetime-Statistiken.
 */
class LTVCalculatorService
{
    public function __construct(
        private AnalyticsCacheManager $cacheManager,
        private MRRCalculatorService $mrrCalculator
    ) {}

    /**
     * Calculate average customer Lifetime Value.
     *
     * Formula: Average_MRR_per_club * Average_Subscription_Duration_months
     * Alternative: Average_Revenue_Per_Club / Monthly_Churn_Rate
     * Cached for 6 hours.
     *
     * @param Tenant $tenant
     * @return float Average LTV
     */
    public function calculateAverageLTV(Tenant $tenant): float
    {
        $cacheKey = $this->cacheManager->getLTVCacheKey($tenant);

        return Cache::remember($cacheKey, AnalyticsCacheManager::CACHE_TTL_LTV, function () use ($tenant) {
            // Get average MRR per club
            $avgMRR = Club::where('tenant_id', $tenant->id)
                ->whereIn('subscription_status', ['active', 'trialing'])
                ->whereNotNull('club_subscription_plan_id')
                ->with('clubSubscriptionPlan')
                ->get()
                ->avg(fn($club) => $this->mrrCalculator->calculateClubMRR($club));

            // Get average subscription duration in months
            $avgDuration = Club::where('tenant_id', $tenant->id)
                ->whereNotNull('subscription_started_at')
                ->selectRaw('AVG(DATEDIFF(COALESCE(subscription_ends_at, NOW()), subscription_started_at) / 30.0) as avg_months')
                ->value('avg_months');

            $avgDuration = $avgDuration ?? 12; // Default to 12 months if no data

            $ltv = $avgMRR * $avgDuration;

            return round($ltv, 2);
        });
    }

    /**
     * Get LTV segmented by subscription plan.
     *
     * Format: [plan_id => ['plan_name' => 'Pro', 'avg_ltv' => 1200.00, 'avg_duration_months' => 24]]
     *
     * @param Tenant $tenant
     * @return array LTV by plan
     */
    public function getLTVByPlan(Tenant $tenant): array
    {
        $clubs = Club::where('tenant_id', $tenant->id)
            ->whereNotNull('subscription_started_at')
            ->whereNotNull('club_subscription_plan_id')
            ->with('clubSubscriptionPlan')
            ->get()
            ->groupBy('club_subscription_plan_id');

        $breakdown = [];

        foreach ($clubs as $planId => $planClubs) {
            $plan = $planClubs->first()->clubSubscriptionPlan;

            // Calculate average MRR for this plan
            $avgMRR = $planClubs->avg(fn($club) => $this->mrrCalculator->calculateClubMRR($club));

            // Calculate average duration for this plan
            $avgDuration = $planClubs->avg(function ($club) {
                $start = $club->subscription_started_at;
                $end = $club->subscription_ends_at ?? now();
                return $start->diffInDays($end) / 30.0; // Convert to months
            });

            $avgLTV = $avgMRR * $avgDuration;

            $breakdown[$planId] = [
                'plan_name' => $plan->name,
                'avg_ltv' => round($avgLTV, 2),
                'avg_duration_months' => round($avgDuration, 1),
                'club_count' => $planClubs->count(),
            ];
        }

        // Sort by LTV descending
        uasort($breakdown, fn($a, $b) => $b['avg_ltv'] <=> $a['avg_ltv']);

        return $breakdown;
    }

    /**
     * Get cohort retention analysis for a specific cohort month.
     *
     * Analyzes retention for clubs that started in the same month.
     * cohortMonth format: 'YYYY-MM' (e.g., '2024-01')
     *
     * @param Tenant $tenant
     * @param string $cohortMonth Format: 'YYYY-MM'
     * @return array Cohort analysis
     */
    public function getCohortAnalysis(Tenant $tenant, string $cohortMonth): array
    {
        // Try to get pre-computed cohort data
        $cohort = ClubSubscriptionCohort::where('tenant_id', $tenant->id)
            ->where('cohort_month', $cohortMonth)
            ->first();

        if ($cohort) {
            // Determine retention trend
            $retention12 = $cohort->retention_month_12;
            $retentionTrend = match (true) {
                $retention12 >= 80 => 'excellent',
                $retention12 >= 60 => 'good',
                $retention12 >= 40 => 'moderate',
                default => 'poor',
            };

            return [
                'cohort' => $cohortMonth,
                'cohort_size' => $cohort->cohort_size,
                'retention_by_month' => [
                    1 => $cohort->retention_month_1,
                    2 => $cohort->retention_month_2,
                    3 => $cohort->retention_month_3,
                    6 => $cohort->retention_month_6,
                    12 => $cohort->retention_month_12,
                ],
                'cumulative_revenue' => round($cohort->cumulative_revenue, 2),
                'avg_ltv' => round($cohort->average_ltv, 2),
                'retention_trend' => $retentionTrend,
            ];
        }

        // Fallback: Calculate on-the-fly
        return $this->calculateCohortAnalysisOnTheFly($tenant, $cohortMonth);
    }

    /**
     * Get aggregate customer lifetime statistics.
     *
     * Returns comprehensive statistics across all clubs.
     *
     * @param Tenant $tenant
     * @return array Lifetime statistics
     */
    public function getCustomerLifetimeStats(Tenant $tenant): array
    {
        $clubs = Club::where('tenant_id', $tenant->id)
            ->whereNotNull('subscription_started_at')
            ->get();

        $durations = $clubs->map(function ($club) {
            $start = $club->subscription_started_at;
            $end = $club->subscription_ends_at ?? now();
            return $start->diffInDays($end);
        })->filter()->values();

        $ltvs = $clubs->map(function ($club) {
            $mrr = $this->mrrCalculator->calculateClubMRR($club);
            $start = $club->subscription_started_at;
            $end = $club->subscription_ends_at ?? now();
            $months = $start->diffInDays($end) / 30.0;
            return $mrr * $months;
        })->filter()->values();

        $activeClubs = Club::where('tenant_id', $tenant->id)
            ->whereIn('subscription_status', ['active', 'trialing'])
            ->count();

        return [
            'avg_subscription_duration_days' => $durations->isNotEmpty() ? round($durations->avg(), 0) : 0,
            'median_subscription_duration_days' => $durations->isNotEmpty() ? $durations->median() : 0,
            'avg_ltv' => $ltvs->isNotEmpty() ? round($ltvs->avg(), 2) : 0,
            'median_ltv' => $ltvs->isNotEmpty() ? round($ltvs->median(), 2) : 0,
            'total_lifetime_revenue' => round($ltvs->sum(), 2),
            'total_active_clubs' => $activeClubs,
        ];
    }

    /**
     * Clear LTV cache for a tenant.
     */
    public function clearCache(Tenant $tenant): void
    {
        $this->cacheManager->clearLTVCache($tenant);
    }

    /**
     * Calculate cohort analysis on-the-fly (fallback method).
     */
    private function calculateCohortAnalysisOnTheFly(Tenant $tenant, string $cohortMonth): array
    {
        [$year, $month] = explode('-', $cohortMonth);

        $cohortStart = Carbon::create($year, $month, 1)->startOfMonth();
        $cohortEnd = $cohortStart->copy()->endOfMonth();

        // Get all clubs that started in this cohort month
        $cohortClubs = Club::where('tenant_id', $tenant->id)
            ->whereBetween('subscription_started_at', [$cohortStart, $cohortEnd])
            ->get();

        $cohortSize = $cohortClubs->count();

        if ($cohortSize == 0) {
            return [
                'cohort' => $cohortMonth,
                'cohort_size' => 0,
                'retention_by_month' => [],
                'cumulative_revenue' => 0,
                'avg_ltv' => 0,
                'retention_trend' => 'no_data',
            ];
        }

        // Calculate retention for different time periods
        $retentionByMonth = [];
        foreach ([1, 2, 3, 6, 12] as $monthNum) {
            $checkDate = $cohortStart->copy()->addMonths($monthNum);
            $retained = $cohortClubs->filter(function ($club) use ($checkDate) {
                $stillActive = $club->subscription_status === 'active' || $club->subscription_status === 'trialing';
                $notEndedYet = is_null($club->subscription_ends_at) || $club->subscription_ends_at->isAfter($checkDate);
                return $stillActive && $notEndedYet;
            })->count();

            $retentionByMonth[$monthNum] = round(($retained / $cohortSize) * 100, 2);
        }

        // Calculate cumulative revenue
        $cumulativeRevenue = $cohortClubs->sum(function ($club) {
            $mrr = $this->mrrCalculator->calculateClubMRR($club);
            $months = $club->subscription_started_at->diffInMonths($club->subscription_ends_at ?? now());
            return $mrr * $months;
        });

        $avgLTV = $cohortSize > 0 ? $cumulativeRevenue / $cohortSize : 0;

        $retention12 = $retentionByMonth[12] ?? 0;
        $retentionTrend = match (true) {
            $retention12 >= 80 => 'excellent',
            $retention12 >= 60 => 'good',
            $retention12 >= 40 => 'moderate',
            default => 'poor',
        };

        return [
            'cohort' => $cohortMonth,
            'cohort_size' => $cohortSize,
            'retention_by_month' => $retentionByMonth,
            'cumulative_revenue' => round($cumulativeRevenue, 2),
            'avg_ltv' => round($avgLTV, 2),
            'retention_trend' => $retentionTrend,
        ];
    }
}
