<?php

namespace App\Services;

use App\Models\Club;
use App\Models\ClubSubscription;
use App\Models\ClubSubscriptionEvent;
use App\Models\SubscriptionMRRSnapshot;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service für Monitoring und Health Checks des Subscription-Systems.
 *
 * Überwacht:
 * - Payment Success/Failure Rates
 * - Churn Rates
 * - MRR Growth
 * - Webhook Processing Times
 * - Failed Queue Jobs
 * - Stripe API Response Times
 * - System Health Score
 */
class SubscriptionHealthMonitorService
{
    /**
     * Alert Thresholds
     */
    private const THRESHOLD_PAYMENT_FAILURE_RATE = 5.0; // 5%
    private const THRESHOLD_CHURN_RATE = 10.0; // 10%
    private const THRESHOLD_WEBHOOK_DELAY = 300; // 5 minutes in seconds
    private const THRESHOLD_STRIPE_ERROR_RATE = 2.0; // 2%
    private const THRESHOLD_QUEUE_FAILURE_RATE = 5.0; // 5%
    private const THRESHOLD_MRR_DECLINE = -10.0; // -10%

    /**
     * Health score weights
     */
    private const WEIGHT_PAYMENT_SUCCESS = 0.25;
    private const WEIGHT_CHURN = 0.20;
    private const WEIGHT_WEBHOOKS = 0.15;
    private const WEIGHT_QUEUE = 0.15;
    private const WEIGHT_STRIPE_API = 0.15;
    private const WEIGHT_MRR_GROWTH = 0.10;

    /**
     * Cache TTL in seconds
     */
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Berechne den Gesundheitsstatus für alle Subscriptions.
     */
    public function calculateOverallHealth(string $period = '24h'): array
    {
        $cacheKey = "subscription_health_overall_{$period}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($period) {
            $metrics = [
                'payment_success_rate' => $this->calculatePaymentSuccessRate($period),
                'churn_rate' => $this->calculateChurnRate($period),
                'webhook_health' => $this->calculateWebhookHealth($period),
                'queue_health' => $this->calculateQueueHealth($period),
                'stripe_api_health' => $this->calculateStripeApiHealth($period),
                'mrr_growth' => $this->calculateMRRGrowth($period),
            ];

            $healthScore = $this->calculateHealthScore($metrics);
            $alerts = $this->generateAlerts($metrics);

            return [
                'health_score' => $healthScore,
                'status' => $this->determineHealthStatus($healthScore),
                'metrics' => $metrics,
                'alerts' => $alerts,
                'checked_at' => now()->toIso8601String(),
                'period' => $period,
            ];
        });
    }

    /**
     * Berechne Payment Success Rate.
     */
    public function calculatePaymentSuccessRate(string $period = '24h'): array
    {
        $startDate = $this->parsePeriod($period);

        $payments = ClubSubscriptionEvent::whereBetween('created_at', [$startDate, now()])
            ->whereIn('event_type', ['payment_succeeded', 'payment_failed'])
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->pluck('count', 'event_type');

        $succeeded = $payments['payment_succeeded'] ?? 0;
        $failed = $payments['payment_failed'] ?? 0;
        $total = $succeeded + $failed;

        $successRate = $total > 0 ? ($succeeded / $total) * 100 : 100.0;
        $failureRate = $total > 0 ? ($failed / $total) * 100 : 0.0;

        return [
            'success_rate' => round($successRate, 2),
            'failure_rate' => round($failureRate, 2),
            'total_payments' => $total,
            'succeeded' => $succeeded,
            'failed' => $failed,
            'is_healthy' => $failureRate < self::THRESHOLD_PAYMENT_FAILURE_RATE,
            'threshold' => self::THRESHOLD_PAYMENT_FAILURE_RATE,
        ];
    }

    /**
     * Berechne Churn Rate.
     */
    public function calculateChurnRate(string $period = '30d'): array
    {
        $startDate = $this->parsePeriod($period);

        // Aktive Subscriptions am Anfang des Zeitraums
        $activeAtStart = ClubSubscription::where('status', 'active')
            ->where('created_at', '<=', $startDate)
            ->count();

        // Gekündigte Subscriptions im Zeitraum
        $cancelled = ClubSubscriptionEvent::whereBetween('created_at', [$startDate, now()])
            ->where('event_type', 'subscription_cancelled')
            ->count();

        $churnRate = $activeAtStart > 0 ? ($cancelled / $activeAtStart) * 100 : 0.0;

        return [
            'churn_rate' => round($churnRate, 2),
            'cancelled_subscriptions' => $cancelled,
            'active_at_start' => $activeAtStart,
            'is_healthy' => $churnRate < self::THRESHOLD_CHURN_RATE,
            'threshold' => self::THRESHOLD_CHURN_RATE,
            'period' => $period,
        ];
    }

    /**
     * Berechne Webhook Health (Processing Time und Success Rate).
     */
    public function calculateWebhookHealth(string $period = '24h'): array
    {
        $startDate = $this->parsePeriod($period);

        // Webhook Events mit Processing Time
        $webhookEvents = ClubSubscriptionEvent::whereBetween('created_at', [$startDate, now()])
            ->whereNotNull('stripe_event_id')
            ->select([
                DB::raw('COUNT(*) as total'),
                DB::raw('AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_processing_time'),
                DB::raw('MAX(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as max_processing_time'),
            ])
            ->first();

        $avgProcessingTime = $webhookEvents->avg_processing_time ?? 0;
        $maxProcessingTime = $webhookEvents->max_processing_time ?? 0;
        $totalWebhooks = $webhookEvents->total ?? 0;

        return [
            'avg_processing_time' => round($avgProcessingTime, 2),
            'max_processing_time' => round($maxProcessingTime, 2),
            'total_webhooks' => $totalWebhooks,
            'is_healthy' => $maxProcessingTime < self::THRESHOLD_WEBHOOK_DELAY,
            'threshold' => self::THRESHOLD_WEBHOOK_DELAY,
            'status' => $maxProcessingTime < self::THRESHOLD_WEBHOOK_DELAY ? 'healthy' : 'delayed',
        ];
    }

    /**
     * Berechne Queue Health (Failed Jobs Rate).
     */
    public function calculateQueueHealth(string $period = '24h'): array
    {
        $startDate = $this->parsePeriod($period);

        // Failed Jobs aus der failed_jobs Tabelle
        $failedJobs = DB::table('failed_jobs')
            ->whereBetween('failed_at', [$startDate, now()])
            ->where('payload', 'like', '%Subscription%')
            ->count();

        // Geschätzte Gesamtanzahl der Jobs (aus ClubSubscriptionEvent)
        $totalJobs = ClubSubscriptionEvent::whereBetween('created_at', [$startDate, now()])
            ->count();

        $failureRate = $totalJobs > 0 ? ($failedJobs / $totalJobs) * 100 : 0.0;

        return [
            'failure_rate' => round($failureRate, 2),
            'failed_jobs' => $failedJobs,
            'estimated_total_jobs' => $totalJobs,
            'is_healthy' => $failureRate < self::THRESHOLD_QUEUE_FAILURE_RATE,
            'threshold' => self::THRESHOLD_QUEUE_FAILURE_RATE,
        ];
    }

    /**
     * Berechne Stripe API Health.
     */
    public function calculateStripeApiHealth(string $period = '24h'): array
    {
        $cacheKey = "stripe_api_health_{$period}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($period) {
            // Test Stripe API Connection
            $startTime = microtime(true);
            $apiHealthy = true;
            $errorMessage = null;

            try {
                $stripe = new \Stripe\StripeClient(config('cashier.secret'));
                $stripe->accounts->retrieve();
                $responseTime = (microtime(true) - $startTime) * 1000; // in ms
            } catch (\Exception $e) {
                $apiHealthy = false;
                $errorMessage = $e->getMessage();
                $responseTime = 0;
                Log::error('Stripe API Health Check failed', [
                    'error' => $e->getMessage(),
                    'period' => $period,
                ]);
            }

            // Stripe Error Rate aus Events
            $startDate = $this->parsePeriod($period);
            $errorEvents = ClubSubscriptionEvent::whereBetween('created_at', [$startDate, now()])
                ->whereIn('event_type', ['payment_failed', 'invoice_payment_failed'])
                ->where('metadata->error', '!=', null)
                ->count();

            $totalEvents = ClubSubscriptionEvent::whereBetween('created_at', [$startDate, now()])
                ->count();

            $errorRate = $totalEvents > 0 ? ($errorEvents / $totalEvents) * 100 : 0.0;

            return [
                'is_healthy' => $apiHealthy && $errorRate < self::THRESHOLD_STRIPE_ERROR_RATE,
                'api_accessible' => $apiHealthy,
                'response_time_ms' => round($responseTime, 2),
                'error_rate' => round($errorRate, 2),
                'error_events' => $errorEvents,
                'total_events' => $totalEvents,
                'threshold' => self::THRESHOLD_STRIPE_ERROR_RATE,
                'error_message' => $errorMessage,
            ];
        });
    }

    /**
     * Berechne MRR Growth.
     */
    public function calculateMRRGrowth(string $period = '30d'): array
    {
        $endDate = now();
        $startDate = $this->parsePeriod($period);

        // MRR am Anfang und Ende des Zeitraums
        $mrrStart = SubscriptionMRRSnapshot::whereDate('snapshot_date', '<=', $startDate)
            ->orderBy('snapshot_date', 'desc')
            ->first()?->total_mrr ?? 0;

        $mrrEnd = SubscriptionMRRSnapshot::whereDate('snapshot_date', '<=', $endDate)
            ->orderBy('snapshot_date', 'desc')
            ->first()?->total_mrr ?? 0;

        $mrrGrowth = $mrrStart > 0 ? (($mrrEnd - $mrrStart) / $mrrStart) * 100 : 0.0;
        $mrrGrowthAbsolute = $mrrEnd - $mrrStart;

        return [
            'growth_rate' => round($mrrGrowth, 2),
            'growth_absolute' => round($mrrGrowthAbsolute, 2),
            'mrr_start' => round($mrrStart, 2),
            'mrr_end' => round($mrrEnd, 2),
            'is_healthy' => $mrrGrowth > self::THRESHOLD_MRR_DECLINE,
            'threshold' => self::THRESHOLD_MRR_DECLINE,
            'trend' => $mrrGrowth > 0 ? 'increasing' : ($mrrGrowth < 0 ? 'decreasing' : 'stable'),
        ];
    }

    /**
     * Berechne Health Score (0-100).
     */
    private function calculateHealthScore(array $metrics): float
    {
        $score = 0;

        // Payment Success Rate
        $score += ($metrics['payment_success_rate']['success_rate'] / 100) * self::WEIGHT_PAYMENT_SUCCESS * 100;

        // Churn Rate (inverted - lower is better)
        $churnScore = max(0, 100 - $metrics['churn_rate']['churn_rate']);
        $score += ($churnScore / 100) * self::WEIGHT_CHURN * 100;

        // Webhook Health
        $webhookScore = $metrics['webhook_health']['is_healthy'] ? 100 : 50;
        $score += ($webhookScore / 100) * self::WEIGHT_WEBHOOKS * 100;

        // Queue Health
        $queueScore = max(0, 100 - $metrics['queue_health']['failure_rate']);
        $score += ($queueScore / 100) * self::WEIGHT_QUEUE * 100;

        // Stripe API Health
        $stripeScore = $metrics['stripe_api_health']['is_healthy'] ? 100 : 0;
        $score += ($stripeScore / 100) * self::WEIGHT_STRIPE_API * 100;

        // MRR Growth
        $mrrScore = min(100, max(0, 50 + $metrics['mrr_growth']['growth_rate']));
        $score += ($mrrScore / 100) * self::WEIGHT_MRR_GROWTH * 100;

        return round($score, 2);
    }

    /**
     * Bestimme Health Status basierend auf Score.
     */
    private function determineHealthStatus(float $score): string
    {
        if ($score >= 90) {
            return 'excellent';
        } elseif ($score >= 75) {
            return 'good';
        } elseif ($score >= 60) {
            return 'fair';
        } elseif ($score >= 40) {
            return 'poor';
        } else {
            return 'critical';
        }
    }

    /**
     * Generiere Alerts basierend auf Metriken.
     */
    private function generateAlerts(array $metrics): array
    {
        $alerts = [];

        // Payment Failure Rate Alert
        if (!$metrics['payment_success_rate']['is_healthy']) {
            $alerts[] = [
                'severity' => 'high',
                'type' => 'payment_failure',
                'message' => sprintf(
                    'Payment failure rate is %.2f%% (threshold: %.2f%%)',
                    $metrics['payment_success_rate']['failure_rate'],
                    self::THRESHOLD_PAYMENT_FAILURE_RATE
                ),
                'metric' => 'payment_success_rate',
                'value' => $metrics['payment_success_rate']['failure_rate'],
                'threshold' => self::THRESHOLD_PAYMENT_FAILURE_RATE,
            ];
        }

        // Churn Rate Alert
        if (!$metrics['churn_rate']['is_healthy']) {
            $alerts[] = [
                'severity' => 'high',
                'type' => 'high_churn',
                'message' => sprintf(
                    'Churn rate is %.2f%% (threshold: %.2f%%)',
                    $metrics['churn_rate']['churn_rate'],
                    self::THRESHOLD_CHURN_RATE
                ),
                'metric' => 'churn_rate',
                'value' => $metrics['churn_rate']['churn_rate'],
                'threshold' => self::THRESHOLD_CHURN_RATE,
            ];
        }

        // Webhook Delay Alert
        if (!$metrics['webhook_health']['is_healthy']) {
            $alerts[] = [
                'severity' => 'medium',
                'type' => 'webhook_delay',
                'message' => sprintf(
                    'Webhook processing delayed: max %.2fs (threshold: %ds)',
                    $metrics['webhook_health']['max_processing_time'],
                    self::THRESHOLD_WEBHOOK_DELAY
                ),
                'metric' => 'webhook_health',
                'value' => $metrics['webhook_health']['max_processing_time'],
                'threshold' => self::THRESHOLD_WEBHOOK_DELAY,
            ];
        }

        // Queue Health Alert
        if (!$metrics['queue_health']['is_healthy']) {
            $alerts[] = [
                'severity' => 'medium',
                'type' => 'queue_failures',
                'message' => sprintf(
                    'Queue failure rate is %.2f%% (threshold: %.2f%%)',
                    $metrics['queue_health']['failure_rate'],
                    self::THRESHOLD_QUEUE_FAILURE_RATE
                ),
                'metric' => 'queue_health',
                'value' => $metrics['queue_health']['failure_rate'],
                'threshold' => self::THRESHOLD_QUEUE_FAILURE_RATE,
            ];
        }

        // Stripe API Alert
        if (!$metrics['stripe_api_health']['is_healthy']) {
            $alerts[] = [
                'severity' => 'critical',
                'type' => 'stripe_api_error',
                'message' => sprintf(
                    'Stripe API issues detected: %s',
                    $metrics['stripe_api_health']['error_message'] ?? 'High error rate'
                ),
                'metric' => 'stripe_api_health',
                'value' => $metrics['stripe_api_health']['error_rate'],
                'threshold' => self::THRESHOLD_STRIPE_ERROR_RATE,
            ];
        }

        // MRR Decline Alert
        if (!$metrics['mrr_growth']['is_healthy']) {
            $alerts[] = [
                'severity' => 'high',
                'type' => 'mrr_decline',
                'message' => sprintf(
                    'MRR declining: %.2f%% (threshold: %.2f%%)',
                    $metrics['mrr_growth']['growth_rate'],
                    self::THRESHOLD_MRR_DECLINE
                ),
                'metric' => 'mrr_growth',
                'value' => $metrics['mrr_growth']['growth_rate'],
                'threshold' => self::THRESHOLD_MRR_DECLINE,
            ];
        }

        return $alerts;
    }

    /**
     * Parse Period String zu Carbon Datum.
     */
    private function parsePeriod(string $period): Carbon
    {
        $match = preg_match('/^(\d+)([hdwmy])$/', $period, $matches);

        if (!$match) {
            throw new \InvalidArgumentException("Invalid period format: {$period}");
        }

        $value = (int) $matches[1];
        $unit = $matches[2];

        return match ($unit) {
            'h' => now()->subHours($value),
            'd' => now()->subDays($value),
            'w' => now()->subWeeks($value),
            'm' => now()->subMonths($value),
            'y' => now()->subYears($value),
            default => now()->subDays(1),
        };
    }

    /**
     * Berechne Health für spezifischen Tenant.
     */
    public function calculateTenantHealth(Tenant $tenant, string $period = '30d'): array
    {
        $cacheKey = "subscription_health_tenant_{$tenant->id}_{$period}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenant, $period) {
            $startDate = $this->parsePeriod($period);

            $subscriptions = ClubSubscription::whereHas('club', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id);
            })->get();

            $activeCount = $subscriptions->where('status', 'active')->count();
            $cancelledCount = $subscriptions->where('status', 'cancelled')->count();
            $trialingCount = $subscriptions->where('status', 'trialing')->count();
            $pastDueCount = $subscriptions->where('status', 'past_due')->count();

            $totalMRR = $subscriptions->where('status', 'active')->sum(function ($sub) {
                return $sub->plan ? $sub->plan->price_monthly : 0;
            });

            return [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'subscriptions' => [
                    'active' => $activeCount,
                    'cancelled' => $cancelledCount,
                    'trialing' => $trialingCount,
                    'past_due' => $pastDueCount,
                    'total' => $subscriptions->count(),
                ],
                'mrr' => round($totalMRR, 2),
                'health_status' => $pastDueCount > 0 ? 'at_risk' : 'healthy',
                'checked_at' => now()->toIso8601String(),
            ];
        });
    }

    /**
     * Clear Health Cache.
     */
    public function clearCache(?string $period = null): void
    {
        if ($period) {
            Cache::forget("subscription_health_overall_{$period}");
            Cache::forget("stripe_api_health_{$period}");
        } else {
            // Clear all health caches
            foreach (['24h', '7d', '30d'] as $p) {
                Cache::forget("subscription_health_overall_{$p}");
                Cache::forget("stripe_api_health_{$p}");
            }
        }
    }
}
