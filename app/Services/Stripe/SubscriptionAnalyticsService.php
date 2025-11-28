<?php

namespace App\Services\Stripe;

use App\Models\Club;
use App\Models\Tenant;
use App\Services\Stripe\Analytics\SubscriptionAnalyticsService as NewSubscriptionAnalyticsService;
use Carbon\Carbon;

/**
 * @deprecated Use App\Services\Stripe\Analytics\SubscriptionAnalyticsService instead.
 *
 * This class is deprecated and will be removed in a future version.
 * It exists only for backward compatibility.
 *
 * Migration guide:
 * - Replace: use App\Services\Stripe\SubscriptionAnalyticsService;
 * - With:    use App\Services\Stripe\Analytics\SubscriptionAnalyticsService;
 *
 * Or use the specialized services directly:
 * - MRRCalculatorService for MRR calculations
 * - ChurnAnalyzerService for churn analysis
 * - LTVCalculatorService for LTV and cohort analysis
 * - SubscriptionHealthMetricsService for health metrics
 *
 * @see \App\Services\Stripe\Analytics\SubscriptionAnalyticsService
 * @see \App\Services\Stripe\Analytics\MRRCalculatorService
 * @see \App\Services\Stripe\Analytics\ChurnAnalyzerService
 * @see \App\Services\Stripe\Analytics\LTVCalculatorService
 * @see \App\Services\Stripe\Analytics\SubscriptionHealthMetricsService
 */
class SubscriptionAnalyticsService extends NewSubscriptionAnalyticsService
{
    /**
     * Constructor triggers deprecation notice.
     */
    public function __construct(
        Analytics\MRRCalculatorService $mrrCalculator,
        Analytics\ChurnAnalyzerService $churnAnalyzer,
        Analytics\LTVCalculatorService $ltvCalculator,
        Analytics\SubscriptionHealthMetricsService $healthMetrics,
        Analytics\AnalyticsCacheManager $cacheManager
    ) {
        @trigger_error(
            'App\Services\Stripe\SubscriptionAnalyticsService is deprecated. ' .
            'Use App\Services\Stripe\Analytics\SubscriptionAnalyticsService instead.',
            E_USER_DEPRECATED
        );

        parent::__construct($mrrCalculator, $churnAnalyzer, $ltvCalculator, $healthMetrics, $cacheManager);
    }
}
