<?php

namespace App\Services\Stripe\Analytics;

use App\Models\Club;
use App\Models\Tenant;
use App\Models\SubscriptionMRRSnapshot;
use App\Services\ClubUsageTrackingService;
use App\Services\Stripe\StripeClientManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * MRR Calculator Service
 *
 * Berechnet Monthly Recurring Revenue (MRR) für Clubs und Tenants.
 * Unterstützt historische MRR-Analysen und Plan-basierte Aufschlüsselungen.
 */
class MRRCalculatorService
{
    public function __construct(
        private StripeClientManager $clientManager,
        private ClubUsageTrackingService $usageService,
        private AnalyticsCacheManager $cacheManager
    ) {}

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

        try {
            // Try to get actual subscription from Stripe to determine interval
            if ($club->stripe_subscription_id) {
                $client = $this->clientManager->getCurrentTenantClient();
                $subscription = $client->subscriptions->retrieve($club->stripe_subscription_id);

                // Get the price object to determine interval
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
        $cacheKey = $this->cacheManager->getMRRCacheKey($tenant);

        return Cache::remember($cacheKey, AnalyticsCacheManager::CACHE_TTL_MRR, function () use ($tenant) {
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

    /**
     * Clear MRR cache for a tenant.
     */
    public function clearCache(Tenant $tenant): void
    {
        $this->cacheManager->clearMRRCache($tenant);
    }

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
}
