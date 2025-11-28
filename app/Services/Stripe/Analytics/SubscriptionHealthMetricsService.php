<?php

namespace App\Services\Stripe\Analytics;

use App\Models\Club;
use App\Models\ClubSubscriptionEvent;
use App\Models\ClubSubscriptionPlan;
use App\Models\Tenant;

/**
 * Subscription Health Metrics Service
 *
 * Überwacht die Gesundheit des Subscription-Systems.
 * Berechnet Metriken wie aktive Subscriptions, Trial Conversion, Upgrade/Downgrade-Raten.
 */
class SubscriptionHealthMetricsService
{
    public function __construct(
        private AnalyticsCacheManager $cacheManager
    ) {}

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
            $oldPlan = ClubSubscriptionPlan::find($event->old_plan_id);
            $newPlan = ClubSubscriptionPlan::find($event->new_plan_id);

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

    /**
     * Get subscription status distribution.
     *
     * @param Tenant $tenant
     * @return array Status distribution
     */
    public function getStatusDistribution(Tenant $tenant): array
    {
        $distribution = Club::where('tenant_id', $tenant->id)
            ->whereNotNull('subscription_status')
            ->selectRaw('subscription_status, COUNT(*) as count')
            ->groupBy('subscription_status')
            ->pluck('count', 'subscription_status')
            ->toArray();

        $total = array_sum($distribution);

        $result = [];
        foreach ($distribution as $status => $count) {
            $result[$status] = [
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 2) : 0,
            ];
        }

        return $result;
    }

    /**
     * Get subscription health score.
     *
     * Calculates an overall health score (0-100) based on various metrics.
     *
     * @param Tenant $tenant
     * @return array Health score and breakdown
     */
    public function getHealthScore(Tenant $tenant): array
    {
        $activeCount = $this->getActiveSubscriptionsCount($tenant);
        $avgDuration = $this->getAverageSubscriptionDuration($tenant);
        $rates = $this->getUpgradeDowngradeRates($tenant, 3);

        // Health factors (each contributes to final score)
        $factors = [
            'active_subscriptions' => min($activeCount / 10, 1) * 25, // Max 25 points
            'duration' => min($avgDuration / 365, 1) * 25, // Max 25 points for 1+ year avg
            'net_expansion' => ($rates['net_change'] >= 0 ? 25 : max(0, 25 + $rates['net_change'] * 2.5)), // Max 25 points
            'upgrade_activity' => min($rates['upgrade_rate'] / 10, 1) * 25, // Max 25 points for 10%+ upgrade rate
        ];

        $totalScore = array_sum($factors);

        return [
            'score' => round($totalScore, 0),
            'grade' => $this->scoreToGrade($totalScore),
            'factors' => $factors,
            'active_subscriptions' => $activeCount,
            'avg_duration_days' => $avgDuration,
            'net_expansion' => $rates['net_change'],
        ];
    }

    /**
     * Convert numeric score to letter grade.
     */
    private function scoreToGrade(float $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'F',
        };
    }
}
