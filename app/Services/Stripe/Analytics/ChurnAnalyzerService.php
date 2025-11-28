<?php

namespace App\Services\Stripe\Analytics;

use App\Models\Club;
use App\Models\ClubSubscriptionEvent;
use App\Models\ClubSubscriptionPlan;
use App\Models\SubscriptionMRRSnapshot;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Churn Analyzer Service
 *
 * Analysiert Kundenabwanderung (Churn) für Subscription Analytics.
 * Unterstützt monatliche Churn-Raten, Plan-basierte Analysen und Revenue Churn.
 */
class ChurnAnalyzerService
{
    public function __construct(
        private AnalyticsCacheManager $cacheManager,
        private MRRCalculatorService $mrrCalculator
    ) {}

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
        $cacheKey = $this->cacheManager->getChurnCacheKey($tenant, $month);

        return Cache::remember($cacheKey, AnalyticsCacheManager::CACHE_TTL_CHURN, function () use ($tenant, $month) {
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
            $plan = ClubSubscriptionPlan::find($planId);

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

        $mrrStart = $startSnapshot?->mrr ?? $this->mrrCalculator->calculateTenantMRR($tenant);

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

    /**
     * Clear churn cache for a tenant.
     */
    public function clearCache(Tenant $tenant): void
    {
        $this->cacheManager->clearChurnCache($tenant);
    }
}
