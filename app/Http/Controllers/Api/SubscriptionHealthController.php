<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\SubscriptionHealthMonitorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Health Check API Controller für Subscription System.
 *
 * Endpoints für Monitoring-Dashboards und externe Tools.
 */
class SubscriptionHealthController extends Controller
{
    public function __construct(
        private SubscriptionHealthMonitorService $healthMonitor
    ) {}

    /**
     * Get overall subscription system health.
     *
     * GET /api/health/subscriptions
     *
     * Query Parameters:
     * - period: 24h, 7d, 30d (default: 24h)
     * - refresh: true to bypass cache
     *
     * @response 200 {
     *   "status": "success",
     *   "data": {
     *     "health_score": 87.5,
     *     "status": "good",
     *     "metrics": {...},
     *     "alerts": [],
     *     "checked_at": "2025-11-03T10:30:00Z",
     *     "period": "24h"
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $period = $request->query('period', '24h');
        $refresh = $request->query('refresh', false);

        // Validate period
        if (!in_array($period, ['24h', '7d', '30d', '90d'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid period. Allowed values: 24h, 7d, 30d, 90d',
            ], 400);
        }

        // Clear cache if refresh requested
        if ($refresh) {
            $this->healthMonitor->clearCache($period);
        }

        try {
            $health = $this->healthMonitor->calculateOverallHealth($period);

            return response()->json([
                'status' => 'success',
                'data' => $health,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to calculate health metrics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get detailed subscription metrics.
     *
     * GET /api/health/subscriptions/metrics
     *
     * Query Parameters:
     * - period: 24h, 7d, 30d (default: 24h)
     *
     * @response 200 {
     *   "status": "success",
     *   "data": {
     *     "payment_success_rate": {...},
     *     "churn_rate": {...},
     *     "webhook_health": {...},
     *     "queue_health": {...},
     *     "stripe_api_health": {...},
     *     "mrr_growth": {...}
     *   }
     * }
     */
    public function metrics(Request $request): JsonResponse
    {
        $period = $request->query('period', '24h');

        if (!in_array($period, ['24h', '7d', '30d', '90d'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid period. Allowed values: 24h, 7d, 30d, 90d',
            ], 400);
        }

        try {
            $health = $this->healthMonitor->calculateOverallHealth($period);

            return response()->json([
                'status' => 'success',
                'data' => $health['metrics'] ?? [],
                'period' => $period,
                'checked_at' => $health['checked_at'] ?? now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve metrics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get payment success rate metrics.
     *
     * GET /api/health/subscriptions/payments
     *
     * @response 200 {
     *   "status": "success",
     *   "data": {
     *     "success_rate": 97.5,
     *     "failure_rate": 2.5,
     *     "total_payments": 1000,
     *     "succeeded": 975,
     *     "failed": 25,
     *     "is_healthy": true,
     *     "threshold": 5.0
     *   }
     * }
     */
    public function payments(Request $request): JsonResponse
    {
        $period = $request->query('period', '24h');

        try {
            $paymentMetrics = $this->healthMonitor->calculatePaymentSuccessRate($period);

            return response()->json([
                'status' => 'success',
                'data' => $paymentMetrics,
                'period' => $period,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to calculate payment metrics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get churn rate metrics.
     *
     * GET /api/health/subscriptions/churn
     *
     * @response 200 {
     *   "status": "success",
     *   "data": {
     *     "churn_rate": 5.2,
     *     "cancelled_subscriptions": 13,
     *     "active_at_start": 250,
     *     "is_healthy": true,
     *     "threshold": 10.0,
     *     "period": "30d"
     *   }
     * }
     */
    public function churn(Request $request): JsonResponse
    {
        $period = $request->query('period', '30d');

        try {
            $churnMetrics = $this->healthMonitor->calculateChurnRate($period);

            return response()->json([
                'status' => 'success',
                'data' => $churnMetrics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to calculate churn metrics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get Stripe API health status.
     *
     * GET /api/health/stripe
     *
     * @response 200 {
     *   "status": "success",
     *   "data": {
     *     "is_healthy": true,
     *     "api_accessible": true,
     *     "response_time_ms": 125.5,
     *     "error_rate": 0.5,
     *     "error_events": 5,
     *     "total_events": 1000,
     *     "threshold": 2.0,
     *     "error_message": null
     *   }
     * }
     */
    public function stripe(Request $request): JsonResponse
    {
        $period = $request->query('period', '24h');

        try {
            $stripeHealth = $this->healthMonitor->calculateStripeApiHealth($period);

            return response()->json([
                'status' => 'success',
                'data' => $stripeHealth,
                'period' => $period,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to check Stripe API health',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get webhook processing health.
     *
     * GET /api/health/webhooks
     *
     * @response 200 {
     *   "status": "success",
     *   "data": {
     *     "avg_processing_time": 1.5,
     *     "max_processing_time": 45.2,
     *     "total_webhooks": 500,
     *     "is_healthy": true,
     *     "threshold": 300,
     *     "status": "healthy"
     *   }
     * }
     */
    public function webhooks(Request $request): JsonResponse
    {
        $period = $request->query('period', '24h');

        try {
            $webhookHealth = $this->healthMonitor->calculateWebhookHealth($period);

            return response()->json([
                'status' => 'success',
                'data' => $webhookHealth,
                'period' => $period,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to check webhook health',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get queue health status.
     *
     * GET /api/health/queue
     *
     * @response 200 {
     *   "status": "success",
     *   "data": {
     *     "failure_rate": 2.5,
     *     "failed_jobs": 25,
     *     "estimated_total_jobs": 1000,
     *     "is_healthy": true,
     *     "threshold": 5.0
     *   }
     * }
     */
    public function queue(Request $request): JsonResponse
    {
        $period = $request->query('period', '24h');

        try {
            $queueHealth = $this->healthMonitor->calculateQueueHealth($period);

            return response()->json([
                'status' => 'success',
                'data' => $queueHealth,
                'period' => $period,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to check queue health',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get MRR growth metrics.
     *
     * GET /api/health/mrr
     *
     * @response 200 {
     *   "status": "success",
     *   "data": {
     *     "growth_rate": 15.5,
     *     "growth_absolute": 1500.00,
     *     "mrr_start": 10000.00,
     *     "mrr_end": 11500.00,
     *     "is_healthy": true,
     *     "threshold": -10.0,
     *     "trend": "increasing"
     *   }
     * }
     */
    public function mrr(Request $request): JsonResponse
    {
        $period = $request->query('period', '30d');

        try {
            $mrrGrowth = $this->healthMonitor->calculateMRRGrowth($period);

            return response()->json([
                'status' => 'success',
                'data' => $mrrGrowth,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to calculate MRR growth',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get health status for specific tenant.
     *
     * GET /api/health/tenant/{tenant}
     *
     * @response 200 {
     *   "status": "success",
     *   "data": {
     *     "tenant_id": 1,
     *     "tenant_name": "Test Tenant",
     *     "subscriptions": {
     *       "active": 10,
     *       "cancelled": 2,
     *       "trialing": 1,
     *       "past_due": 0,
     *       "total": 13
     *     },
     *     "mrr": 1500.00,
     *     "health_status": "healthy",
     *     "checked_at": "2025-11-03T10:30:00Z"
     *   }
     * }
     */
    public function tenant(Request $request, int $tenantId): JsonResponse
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);
            $period = $request->query('period', '30d');

            $tenantHealth = $this->healthMonitor->calculateTenantHealth($tenant, $period);

            return response()->json([
                'status' => 'success',
                'data' => $tenantHealth,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tenant not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to calculate tenant health',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get system status (simple uptime check).
     *
     * GET /api/health/status
     *
     * @response 200 {
     *   "status": "ok",
     *   "timestamp": "2025-11-03T10:30:00Z",
     *   "version": "1.0.0"
     * }
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
        ]);
    }

    /**
     * Clear health check cache.
     *
     * DELETE /api/health/cache
     *
     * @response 200 {
     *   "status": "success",
     *   "message": "Health cache cleared successfully"
     * }
     */
    public function clearCache(Request $request): JsonResponse
    {
        $period = $request->query('period');

        try {
            $this->healthMonitor->clearCache($period);

            return response()->json([
                'status' => 'success',
                'message' => $period
                    ? "Health cache cleared for period: {$period}"
                    : 'All health caches cleared successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to clear health cache',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
