<?php

namespace App\Services\Stripe;

use App\Models\Club;
use App\Models\Tenant;
use App\Models\SubscriptionMRRSnapshot;
use App\Models\ClubSubscriptionEvent;
use App\Models\ClubSubscriptionCohort;
use App\Services\ClubUsageTrackingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Subscription Analytics Service
 *
 * Provides comprehensive subscription analytics for multi-tenant SaaS operations:
 * - MRR (Monthly Recurring Revenue) tracking and analysis
 * - Churn rate calculation and breakdown
 * - LTV (Lifetime Value) metrics and cohort analysis
 * - Subscription health metrics
 *
 * All metrics are tenant-isolated and cached for performance.
 */
class SubscriptionAnalyticsService
{
    /**
     * Cache TTL constants (in seconds)
     */
    private const CACHE_TTL_MRR = 3600;      // 1 hour
    private const CACHE_TTL_CHURN = 86400;   // 24 hours
    private const CACHE_TTL_LTV = 21600;     // 6 hours

    public function __construct(
        private StripeClientManager $clientManager,
        private ClubUsageTrackingService $usageService
    ) {}

    // ========================================================================
    // MRR (Monthly Recurring Revenue) Methods
    // ========================================================================

    /**
     * Calculate Monthly Recurring Revenue for a single club.
     *
     * Normalizes yearly subscriptions to monthly (price / 12).
     * Returns 0 if no active subscription or plan.
     *
     * @param Club $club
     * @return float MRR in club's currency
     */
    public function calculateClubMRR(Club $club): float
    {
        // Check if club has active subscription
        if (!in_array($club->subscription_status, ['active', 'trialing'])) {
            return 0.0;
        }

        // Get subscription plan
        $plan = $club->clubSubscriptionPlan;
        if (!$plan) {
            return 0.0;
        }

        // Calculate MRR based on billing interval
        // Assumption: We need to determine billing interval from Stripe subscription
        // For now, we'll use the plan price as monthly and calculate from there

        try {
            // Try to get actual subscription from Stripe to determine interval
            if ($club->stripe_subscription_id) {
                $client = $this->clientManager->getCurrentTenantClient();
                $subscription = $client->subscriptions->retrieve($club->stripe_subscription_id);

                // Get the price object to determine interval
                $priceId = $subscription->items->data[0]->price->id;
                $interval = $subscription->items->data[0]->price->recurring->interval;

                $amount = $subscription->items->data[0]->price->unit_amount / 100; // Convert from cents

                // Normalize to monthly
                if ($interval === 'year') {
                    return round($amount / 12, 2);
                } elseif ($interval === 'month') {
                    return round($amount, 2);
                }
            }

            // Fallback: Use plan price as monthly
            return round($plan->price, 2);
        } catch (\Exception $e) {
            Log::warning('Failed to calculate club MRR from Stripe', [
                'club_id' => $club->id,
                'error' => $e->getMessage(),
            ]);

            // Fallback to plan price
            return round($plan->price, 2);
        }
    }

    /**
     * Calculate total MRR for a tenant across all active club subscriptions.
     *
     * Aggregates MRR from all active/trialing clubs.
     * Updates tenant's monthly_recurring_revenue field.
     * Cached for 1 hour.
     *
     * @param Tenant $tenant
     * @return float Total MRR
     */
    public function calculateTenantMRR(Tenant $tenant): float
    {
        $cacheKey = "subscription:mrr:{$tenant->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL_MRR, function () use ($tenant) {
            $totalMRR = 0.0;

            // Get all clubs with active/trialing subscriptions
            $activeClubs = Club::where('tenant_id', $tenant->id)
                ->whereIn('subscription_status', ['active', 'trialing'])
                ->whereNotNull('club_subscription_plan_id')
                ->with('clubSubscriptionPlan')
                ->get();

            foreach ($activeClubs as $club) {
                $totalMRR += $this->calculateClubMRR($club);
            }

            // Update tenant's MRR field
            $tenant->update(['monthly_recurring_revenue' => $totalMRR]);

            Log::info('Tenant MRR calculated', [
                'tenant_id' => $tenant->id,
                'mrr' => $totalMRR,
                'club_count' => $activeClubs->count(),
            ]);

            return round($totalMRR, 2);
        });
    }

    /**
     * Get historical MRR data with growth rates.
     *
     * Returns array of MRR snapshots ordered by date (newest first).
     * Format: [['month' => '2025-01', 'mrr' => 1234.56, 'growth_rate' => 5.2], ...]
     *
     * @param Tenant $tenant
     * @param int $months Number of months to retrieve
     * @return array Historical MRR data
     */
    public function getHistoricalMRR(Tenant $tenant, int $months = 12): array
    {
        // Query monthly snapshots
        $snapshots = SubscriptionMRRSnapshot::where('tenant_id', $tenant->id)
            ->monthly()
            ->latest('snapshot_date')
            ->limit($months)
            ->get();

        // If we have snapshots, format and return
        if ($snapshots->isNotEmpty()) {
            return $snapshots->map(function ($snapshot) {
                return [
                    'month' => $snapshot->snapshot_date->format('Y-m'),
                    'mrr' => round($snapshot->mrr, 2),
                    'growth_rate' => round($snapshot->mrr_growth_rate ?? 0, 2),
                    'active_subscriptions' => $snapshot->active_subscriptions,
                    'new_subscriptions' => $snapshot->new_subscriptions,
                    'churned_subscriptions' => $snapshot->churned_subscriptions,
                ];
            })->toArray();
        }

        // Fallback: Calculate from club_subscription_events
        return $this->calculateHistoricalMRRFromEvents($tenant, $months);
    }

    /**
     * Calculate MRR growth rate over N months.
     *
     * Formula: ((current_mrr - mrr_N_months_ago) / mrr_N_months_ago) * 100
     * Returns percentage (e.g., 15.5 = 15.5% growth)
     *
     * @param Tenant $tenant
     * @param int $months Lookback period
     * @return float Growth rate percentage
     */
    public function getMRRGrowthRate(Tenant $tenant, int $months = 3): float
    {
        $historical = $this->getHistoricalMRR($tenant, $months + 1);

        if (count($historical) < 2) {
            return 0.0;
        }

        $currentMRR = $historical[0]['mrr'] ?? 0;
        $pastMRR = $historical[$months]['mrr'] ?? 0;

        if ($pastMRR == 0) {
            return 0.0;
        }

        $growthRate = (($currentMRR - $pastMRR) / $pastMRR) * 100;

        return round($growthRate, 2);
    }

    /**
     * Get MRR breakdown by subscription plan.
     *
     * Format: [plan_id => ['plan_name' => 'Pro', 'mrr' => 500.00, 'club_count' => 5, 'percentage' => 25.0]]
     * Sorted by MRR descending.
     *
     * @param Tenant $tenant
     * @return array MRR by plan
     */
    public function getMRRByPlan(Tenant $tenant): array
    {
        $totalMRR = $this->calculateTenantMRR($tenant);

        // Get all active clubs grouped by plan
        $clubs = Club::where('tenant_id', $tenant->id)
            ->whereIn('subscription_status', ['active', 'trialing'])
            ->whereNotNull('club_subscription_plan_id')
            ->with('clubSubscriptionPlan')
            ->get()
            ->groupBy('club_subscription_plan_id');

        $breakdown = [];

        foreach ($clubs as $planId => $planClubs) {
            $plan = $planClubs->first()->clubSubscriptionPlan;
            $planMRR = 0.0;

            foreach ($planClubs as $club) {
                $planMRR += $this->calculateClubMRR($club);
            }

            $breakdown[$planId] = [
                'plan_name' => $plan->name,
                'mrr' => round($planMRR, 2),
                'club_count' => $planClubs->count(),
                'percentage' => $totalMRR > 0 ? round(($planMRR / $totalMRR) * 100, 2) : 0,
            ];
        }

        // Sort by MRR descending
        uasort($breakdown, fn($a, $b) => $b['mrr'] <=> $a['mrr']);

        return $breakdown;
    }

    // ========================================================================
    // Churn Methods
    // ========================================================================

    /**
     * Calculate monthly churn rate for a specific month.
     *
     * Returns customer churn rate with voluntary vs involuntary breakdown.
     * Cached for 24 hours.
     *
     * @param Tenant $tenant
     * @param Carbon|null $month Target month (defaults to current month)
     * @return array Churn metrics
     */
    public function calculateMonthlyChurnRate(Tenant $tenant, ?Carbon $month = null): array
    {
        $month = $month ?? now();
        $cacheKey = "subscription:churn:{$tenant->id}:" . $month->format('Y-m');

        return Cache::remember($cacheKey, self::CACHE_TTL_CHURN, function () use ($tenant, $month) {
            $year = $month->year;
            $monthNum = $month->month;

            // Get customers at start of month
            $customersStart = Club::where('tenant_id', $tenant->id)
                ->whereIn('subscription_status', ['active', 'trialing'])
                ->where('subscription_started_at', '<', $month->startOfMonth())
                ->count();

            // Get churn events in this month
            $churnEvents = ClubSubscriptionEvent::where('tenant_id', $tenant->id)
                ->churnEvents()
                ->inMonth($year, $monthNum)
                ->get();

            $churnedCustomers = $churnEvents->count();
            $voluntaryChurn = $churnEvents->where('event_type', 'subscription_canceled')->count();
            $involuntaryChurn = $churnEvents->where('event_type', 'subscription_payment_failed')->count();

            // Get customers at end of month
            $customersEnd = Club::where('tenant_id', $tenant->id)
                ->whereIn('subscription_status', ['active', 'trialing'])
                ->where('subscription_started_at', '<', $month->copy()->endOfMonth())
                ->count();

            $churnRate = $customersStart > 0 ? ($churnedCustomers / $customersStart) * 100 : 0;

            return [
                'period' => $month->format('Y-m'),
                'customers_start' => $customersStart,
                'customers_end' => $customersEnd,
                'churned_customers' => $churnedCustomers,
                'churn_rate' => round($churnRate, 2),
                'voluntary_churn' => $voluntaryChurn,
                'involuntary_churn' => $involuntaryChurn,
            ];
        });
    }

    /**
     * Get churn breakdown by subscription plan.
     *
     * Identifies which plans have highest churn rates.
     * Format: [plan_id => ['plan_name' => 'Basic', 'churned_count' => 10, 'churn_rate' => 15.5]]
     *
     * @param Tenant $tenant
     * @param int $months Lookback period
     * @return array Churn by plan
     */
    public function getChurnByPlan(Tenant $tenant, int $months = 12): array
    {
        $startDate = now()->subMonths($months);

        // Get churn events grouped by old plan
        $churnEvents = ClubSubscriptionEvent::where('tenant_id', $tenant->id)
            ->churnEvents()
            ->where('event_date', '>=', $startDate)
            ->whereNotNull('old_plan_id')
            ->get()
            ->groupBy('old_plan_id');

        $breakdown = [];

        foreach ($churnEvents as $planId => $events) {
            $plan = \App\Models\ClubSubscriptionPlan::find($planId);

            // Get total clubs that were on this plan during the period
            $totalOnPlan = Club::where('tenant_id', $tenant->id)
                ->where('club_subscription_plan_id', $planId)
                ->orWhere(function ($query) use ($planId, $startDate) {
                    $query->whereHas('clubSubscriptionEvents', function ($q) use ($planId, $startDate) {
                        $q->where('old_plan_id', $planId)
                            ->where('event_date', '>=', $startDate);
                    });
                })
                ->count();

            $churnedCount = $events->count();
            $churnRate = $totalOnPlan > 0 ? ($churnedCount / $totalOnPlan) * 100 : 0;

            $breakdown[$planId] = [
                'plan_name' => $plan?->name ?? 'Unknown Plan',
                'churned_count' => $churnedCount,
                'churn_rate' => round($churnRate, 2),
                'total_on_plan' => $totalOnPlan,
            ];
        }

        // Sort by churn rate descending
        uasort($breakdown, fn($a, $b) => $b['churn_rate'] <=> $a['churn_rate']);

        return $breakdown;
    }

    /**
     * Get breakdown of churn by reason.
     *
     * Breaks down churn by reason: voluntary, payment_failed, trial_expired
     * Format: ['voluntary' => 25, 'payment_failed' => 10, 'trial_expired' => 5]
     *
     * @param Tenant $tenant
     * @param int $months Lookback period
     * @return array Churn reasons
     */
    public function getChurnReasons(Tenant $tenant, int $months = 6): array
    {
        $startDate = now()->subMonths($months);

        $events = ClubSubscriptionEvent::where('tenant_id', $tenant->id)
            ->churnEvents()
            ->where('event_date', '>=', $startDate)
            ->get();

        $totalChurned = $events->count();

        $reasons = [
            'voluntary' => $events->where('event_type', 'subscription_canceled')->count(),
            'payment_failed' => $events->where('event_type', 'subscription_payment_failed')->count(),
            'trial_expired' => $events->where('event_type', 'trial_ended_without_payment')->count(),
        ];

        // Add percentages
        foreach ($reasons as $key => $count) {
            $reasons[$key] = [
                'count' => $count,
                'percentage' => $totalChurned > 0 ? round(($count / $totalChurned) * 100, 2) : 0,
            ];
        }

        return $reasons;
    }

    /**
     * Calculate revenue churn (MRR lost from cancellations).
     *
     * Formula: (MRR_lost_from_cancellations / MRR_at_period_start) * 100
     * More important than customer churn for SaaS businesses.
     *
     * @param Tenant $tenant
     * @param Carbon|null $month Target month
     * @return float Revenue churn percentage
     */
    public function calculateRevenueChurn(Tenant $tenant, ?Carbon $month = null): float
    {
        $month = $month ?? now();
        $year = $month->year;
        $monthNum = $month->month;

        // Get MRR at start of month
        $startSnapshot = SubscriptionMRRSnapshot::where('tenant_id', $tenant->id)
            ->monthly()
            ->whereYear('snapshot_date', $year)
            ->whereMonth('snapshot_date', $monthNum)
            ->first();

        $mrrStart = $startSnapshot?->mrr ?? $this->calculateTenantMRR($tenant);

        if ($mrrStart == 0) {
            return 0.0;
        }

        // Get sum of negative MRR changes from cancellations
        $mrrLost = ClubSubscriptionEvent::where('tenant_id', $tenant->id)
            ->churnEvents()
            ->inMonth($year, $monthNum)
            ->sum('mrr_change'); // This will be negative

        $mrrLost = abs($mrrLost);

        $revenueChurn = ($mrrLost / $mrrStart) * 100;

        return round($revenueChurn, 2);
    }

    // ========================================================================
    // LTV (Lifetime Value) Methods
    // ========================================================================

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
        $cacheKey = "subscription:ltv:{$tenant->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL_LTV, function () use ($tenant) {
            // Get average MRR per club
            $avgMRR = Club::where('tenant_id', $tenant->id)
                ->whereIn('subscription_status', ['active', 'trialing'])
                ->whereNotNull('club_subscription_plan_id')
                ->with('clubSubscriptionPlan')
                ->get()
                ->avg(fn($club) => $this->calculateClubMRR($club));

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
            $avgMRR = $planClubs->avg(fn($club) => $this->calculateClubMRR($club));

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
            $mrr = $this->calculateClubMRR($club);
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

    // ========================================================================
    // Health Metrics Methods
    // ========================================================================

    /**
     * Get count of active subscriptions.
     *
     * @param Tenant $tenant
     * @return int Active subscription count
     */
    public function getActiveSubscriptionsCount(Tenant $tenant): int
    {
        return Club::where('tenant_id', $tenant->id)
            ->whereIn('subscription_status', ['active', 'trialing'])
            ->count();
    }

    /**
     * Get trial to paid conversion rate.
     *
     * Formula: (clubs_converted_from_trial / clubs_started_trial) * 100
     *
     * @param Tenant $tenant
     * @param int $days Lookback period
     * @return float Conversion rate percentage
     */
    public function getTrialConversionRate(Tenant $tenant, int $days = 30): float
    {
        $startDate = now()->subDays($days);

        $trialsStarted = ClubSubscriptionEvent::where('tenant_id', $tenant->id)
            ->where('event_type', 'trial_started')
            ->where('event_date', '>=', $startDate)
            ->count();

        if ($trialsStarted == 0) {
            return 0.0;
        }

        $trialsConverted = ClubSubscriptionEvent::where('tenant_id', $tenant->id)
            ->where('event_type', 'subscription_renewed') // Trial converted to paid
            ->where('event_date', '>=', $startDate)
            ->whereHas('club', function ($query) {
                $query->whereNotNull('subscription_trial_ends_at');
            })
            ->count();

        $conversionRate = ($trialsConverted / $trialsStarted) * 100;

        return round($conversionRate, 2);
    }

    /**
     * Get average subscription duration in days.
     *
     * @param Tenant $tenant
     * @return float Average duration in days
     */
    public function getAverageSubscriptionDuration(Tenant $tenant): float
    {
        $avgDays = Club::where('tenant_id', $tenant->id)
            ->whereNotNull('subscription_started_at')
            ->selectRaw('AVG(DATEDIFF(COALESCE(subscription_ends_at, NOW()), subscription_started_at)) as avg_days')
            ->value('avg_days');

        return round($avgDays ?? 0, 1);
    }

    /**
     * Get upgrade and downgrade rates.
     *
     * Tracks plan change activity over N months.
     *
     * @param Tenant $tenant
     * @param int $months Lookback period
     * @return array Upgrade/downgrade metrics
     */
    public function getUpgradeDowngradeRates(Tenant $tenant, int $months = 3): array
    {
        $startDate = now()->subMonths($months);
        $endDate = now();

        // Get plan change events
        $planChanges = ClubSubscriptionEvent::where('tenant_id', $tenant->id)
            ->where('event_type', 'plan_changed')
            ->whereBetween('event_date', [$startDate, $endDate])
            ->whereNotNull('old_plan_id')
            ->whereNotNull('new_plan_id')
            ->get();

        $upgrades = 0;
        $downgrades = 0;

        foreach ($planChanges as $event) {
            $oldPlan = \App\Models\ClubSubscriptionPlan::find($event->old_plan_id);
            $newPlan = \App\Models\ClubSubscriptionPlan::find($event->new_plan_id);

            if ($oldPlan && $newPlan) {
                if ($newPlan->price > $oldPlan->price) {
                    $upgrades++;
                } elseif ($newPlan->price < $oldPlan->price) {
                    $downgrades++;
                }
            }
        }

        $totalActive = $this->getActiveSubscriptionsCount($tenant);

        return [
            'upgrades' => $upgrades,
            'downgrades' => $downgrades,
            'upgrade_rate' => $totalActive > 0 ? round(($upgrades / $totalActive) * 100, 2) : 0,
            'downgrade_rate' => $totalActive > 0 ? round(($downgrades / $totalActive) * 100, 2) : 0,
            'net_change' => $upgrades - $downgrades,
        ];
    }

    // ========================================================================
    // Private Helper Methods
    // ========================================================================

    /**
     * Calculate historical MRR from club subscription events (fallback method).
     */
    private function calculateHistoricalMRRFromEvents(Tenant $tenant, int $months): array
    {
        $result = [];

        for ($i = 0; $i < $months; $i++) {
            $targetMonth = now()->subMonths($i);

            $mrr = Club::where('tenant_id', $tenant->id)
                ->whereIn('subscription_status', ['active', 'trialing'])
                ->where('subscription_started_at', '<=', $targetMonth->endOfMonth())
                ->where(function ($query) use ($targetMonth) {
                    $query->whereNull('subscription_ends_at')
                        ->orWhere('subscription_ends_at', '>', $targetMonth->startOfMonth());
                })
                ->with('clubSubscriptionPlan')
                ->get()
                ->sum(fn($club) => $this->calculateClubMRR($club));

            $result[] = [
                'month' => $targetMonth->format('Y-m'),
                'mrr' => round($mrr, 2),
                'growth_rate' => 0, // Cannot calculate without previous data
                'active_subscriptions' => 0,
                'new_subscriptions' => 0,
                'churned_subscriptions' => 0,
            ];
        }

        return $result;
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
            $mrr = $this->calculateClubMRR($club);
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
