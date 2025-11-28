<?php

namespace App\Services\Stripe\Analytics;

use App\Models\Club;
use App\Models\Tenant;
use Carbon\Carbon;

/**
 * Subscription Analytics Service (Facade/Orchestrator)
 *
 * Zentralisierte API für alle Subscription Analytics.
 * Delegiert an spezialisierte Services und bietet eine rückwärtskompatible
 * öffentliche API für alle Consumer (Commands, Controller, Jobs).
 *
 * @see MRRCalculatorService für MRR-Berechnungen
 * @see ChurnAnalyzerService für Churn-Analysen
 * @see LTVCalculatorService für LTV und Cohort-Analysen
 * @see SubscriptionHealthMetricsService für Health-Metriken
 * @see AnalyticsCacheManager für Cache-Strategie
 */
class SubscriptionAnalyticsService
{
    public function __construct(
        private MRRCalculatorService $mrrCalculator,
        private ChurnAnalyzerService $churnAnalyzer,
        private LTVCalculatorService $ltvCalculator,
        private SubscriptionHealthMetricsService $healthMetrics,
        private AnalyticsCacheManager $cacheManager
    ) {}

    // ========================================================================
    // MRR (Monthly Recurring Revenue) Methods - Delegiert an MRRCalculatorService
    // ========================================================================

    /**
     * Calculate Monthly Recurring Revenue for a single club.
     *
     * @param Club $club
     * @return float MRR in club's currency
     */
    public function calculateClubMRR(Club $club): float
    {
        return $this->mrrCalculator->calculateClubMRR($club);
    }

    /**
     * Calculate total MRR for a tenant across all active club subscriptions.
     *
     * @param Tenant $tenant
     * @return float Total MRR
     */
    public function calculateTenantMRR(Tenant $tenant): float
    {
        return $this->mrrCalculator->calculateTenantMRR($tenant);
    }

    /**
     * Get historical MRR data with growth rates.
     *
     * @param Tenant $tenant
     * @param int $months Number of months to retrieve
     * @return array Historical MRR data
     */
    public function getHistoricalMRR(Tenant $tenant, int $months = 12): array
    {
        return $this->mrrCalculator->getHistoricalMRR($tenant, $months);
    }

    /**
     * Calculate MRR growth rate over N months.
     *
     * @param Tenant $tenant
     * @param int $months Lookback period
     * @return float Growth rate percentage
     */
    public function getMRRGrowthRate(Tenant $tenant, int $months = 3): float
    {
        return $this->mrrCalculator->getMRRGrowthRate($tenant, $months);
    }

    /**
     * Get MRR breakdown by subscription plan.
     *
     * @param Tenant $tenant
     * @return array MRR by plan
     */
    public function getMRRByPlan(Tenant $tenant): array
    {
        return $this->mrrCalculator->getMRRByPlan($tenant);
    }

    // ========================================================================
    // Churn Methods - Delegiert an ChurnAnalyzerService
    // ========================================================================

    /**
     * Calculate monthly churn rate for a specific month.
     *
     * @param Tenant $tenant
     * @param Carbon|null $month Target month
     * @return array Churn metrics
     */
    public function calculateMonthlyChurnRate(Tenant $tenant, ?Carbon $month = null): array
    {
        return $this->churnAnalyzer->calculateMonthlyChurnRate($tenant, $month);
    }

    /**
     * Get churn breakdown by subscription plan.
     *
     * @param Tenant $tenant
     * @param int $months Lookback period
     * @return array Churn by plan
     */
    public function getChurnByPlan(Tenant $tenant, int $months = 12): array
    {
        return $this->churnAnalyzer->getChurnByPlan($tenant, $months);
    }

    /**
     * Get breakdown of churn by reason.
     *
     * @param Tenant $tenant
     * @param int $months Lookback period
     * @return array Churn reasons
     */
    public function getChurnReasons(Tenant $tenant, int $months = 6): array
    {
        return $this->churnAnalyzer->getChurnReasons($tenant, $months);
    }

    /**
     * Calculate revenue churn (MRR lost from cancellations).
     *
     * @param Tenant $tenant
     * @param Carbon|null $month Target month
     * @return float Revenue churn percentage
     */
    public function calculateRevenueChurn(Tenant $tenant, ?Carbon $month = null): float
    {
        return $this->churnAnalyzer->calculateRevenueChurn($tenant, $month);
    }

    // ========================================================================
    // LTV (Lifetime Value) Methods - Delegiert an LTVCalculatorService
    // ========================================================================

    /**
     * Calculate average customer Lifetime Value.
     *
     * @param Tenant $tenant
     * @return float Average LTV
     */
    public function calculateAverageLTV(Tenant $tenant): float
    {
        return $this->ltvCalculator->calculateAverageLTV($tenant);
    }

    /**
     * Get LTV segmented by subscription plan.
     *
     * @param Tenant $tenant
     * @return array LTV by plan
     */
    public function getLTVByPlan(Tenant $tenant): array
    {
        return $this->ltvCalculator->getLTVByPlan($tenant);
    }

    /**
     * Get cohort retention analysis for a specific cohort month.
     *
     * @param Tenant $tenant
     * @param string $cohortMonth Format: 'YYYY-MM'
     * @return array Cohort analysis
     */
    public function getCohortAnalysis(Tenant $tenant, string $cohortMonth): array
    {
        return $this->ltvCalculator->getCohortAnalysis($tenant, $cohortMonth);
    }

    /**
     * Get aggregate customer lifetime statistics.
     *
     * @param Tenant $tenant
     * @return array Lifetime statistics
     */
    public function getCustomerLifetimeStats(Tenant $tenant): array
    {
        return $this->ltvCalculator->getCustomerLifetimeStats($tenant);
    }

    // ========================================================================
    // Health Metrics Methods - Delegiert an SubscriptionHealthMetricsService
    // ========================================================================

    /**
     * Get count of active subscriptions.
     *
     * @param Tenant $tenant
     * @return int Active subscription count
     */
    public function getActiveSubscriptionsCount(Tenant $tenant): int
    {
        return $this->healthMetrics->getActiveSubscriptionsCount($tenant);
    }

    /**
     * Get trial to paid conversion rate.
     *
     * @param Tenant $tenant
     * @param int $days Lookback period
     * @return float Conversion rate percentage
     */
    public function getTrialConversionRate(Tenant $tenant, int $days = 30): float
    {
        return $this->healthMetrics->getTrialConversionRate($tenant, $days);
    }

    /**
     * Get average subscription duration in days.
     *
     * @param Tenant $tenant
     * @return float Average duration in days
     */
    public function getAverageSubscriptionDuration(Tenant $tenant): float
    {
        return $this->healthMetrics->getAverageSubscriptionDuration($tenant);
    }

    /**
     * Get upgrade and downgrade rates.
     *
     * @param Tenant $tenant
     * @param int $months Lookback period
     * @return array Upgrade/downgrade metrics
     */
    public function getUpgradeDowngradeRates(Tenant $tenant, int $months = 3): array
    {
        return $this->healthMetrics->getUpgradeDowngradeRates($tenant, $months);
    }

    /**
     * Get subscription health score.
     *
     * @param Tenant $tenant
     * @return array Health score and breakdown
     */
    public function getHealthScore(Tenant $tenant): array
    {
        return $this->healthMetrics->getHealthScore($tenant);
    }

    /**
     * Get subscription status distribution.
     *
     * @param Tenant $tenant
     * @return array Status distribution
     */
    public function getStatusDistribution(Tenant $tenant): array
    {
        return $this->healthMetrics->getStatusDistribution($tenant);
    }

    // ========================================================================
    // Cache Management Methods
    // ========================================================================

    /**
     * Clear all analytics cache for a tenant.
     *
     * @param Tenant $tenant
     */
    public function clearTenantCache(Tenant $tenant): void
    {
        $this->cacheManager->clearTenantCache($tenant);
    }

    /**
     * Clear all analytics cache across all tenants.
     */
    public function clearAllCache(): void
    {
        $this->cacheManager->clearAllAnalyticsCache();
    }

    /**
     * Clear MRR-specific cache for a tenant.
     *
     * @param Tenant $tenant
     */
    public function clearMRRCache(Tenant $tenant): void
    {
        $this->mrrCalculator->clearCache($tenant);
    }

    /**
     * Clear churn-specific cache for a tenant.
     *
     * @param Tenant $tenant
     */
    public function clearChurnCache(Tenant $tenant): void
    {
        $this->churnAnalyzer->clearCache($tenant);
    }

    /**
     * Clear LTV-specific cache for a tenant.
     *
     * @param Tenant $tenant
     */
    public function clearLTVCache(Tenant $tenant): void
    {
        $this->ltvCalculator->clearCache($tenant);
    }

    // ========================================================================
    // Service Access (für direkten Zugriff auf spezialisierte Services)
    // ========================================================================

    /**
     * Get MRR Calculator Service.
     */
    public function getMRRCalculator(): MRRCalculatorService
    {
        return $this->mrrCalculator;
    }

    /**
     * Get Churn Analyzer Service.
     */
    public function getChurnAnalyzer(): ChurnAnalyzerService
    {
        return $this->churnAnalyzer;
    }

    /**
     * Get LTV Calculator Service.
     */
    public function getLTVCalculator(): LTVCalculatorService
    {
        return $this->ltvCalculator;
    }

    /**
     * Get Health Metrics Service.
     */
    public function getHealthMetrics(): SubscriptionHealthMetricsService
    {
        return $this->healthMetrics;
    }

    /**
     * Get Cache Manager.
     */
    public function getCacheManager(): AnalyticsCacheManager
    {
        return $this->cacheManager;
    }
}
