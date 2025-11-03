<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\SubscriptionHealthMonitorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

/**
 * Command für automatische Subscription Health Checks.
 *
 * Kann manuell oder via Scheduler ausgeführt werden:
 * - php artisan subscriptions:health-check
 * - php artisan subscriptions:health-check --period=7d --alert
 *
 * In routes/console.php schedulen:
 * - $schedule->command('subscriptions:health-check --alert')->hourly();
 */
class SubscriptionHealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:health-check
                          {--period=24h : Time period for health check (24h, 7d, 30d)}
                          {--tenant= : Check specific tenant ID only}
                          {--alert : Send alerts for critical issues}
                          {--clear-cache : Clear health cache before check}
                          {--json : Output results as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform subscription system health check and alert on critical issues';

    /**
     * Execute the console command.
     */
    public function handle(SubscriptionHealthMonitorService $healthMonitor): int
    {
        $period = $this->option('period');
        $tenantId = $this->option('tenant');
        $shouldAlert = $this->option('alert');
        $clearCache = $this->option('clear-cache');
        $jsonOutput = $this->option('json');

        $this->info("🔍 Running Subscription Health Check (period: {$period})...");

        // Clear cache if requested
        if ($clearCache) {
            $healthMonitor->clearCache($period);
            $this->info('✓ Health cache cleared');
        }

        try {
            if ($tenantId) {
                // Check specific tenant
                $tenant = Tenant::findOrFail($tenantId);
                $result = $this->checkTenantHealth($healthMonitor, $tenant, $period);
            } else {
                // Check overall health
                $result = $this->checkOverallHealth($healthMonitor, $period);
            }

            // Output results
            if ($jsonOutput) {
                $this->line(json_encode($result, JSON_PRETTY_PRINT));
            } else {
                $this->displayHealthReport($result);
            }

            // Send alerts if requested and issues found
            if ($shouldAlert && !empty($result['alerts'])) {
                $this->sendAlerts($result);
            }

            // Log results
            $this->logHealthCheck($result);

            // Determine exit code
            $exitCode = $this->determineExitCode($result);

            if ($exitCode === 0) {
                $this->info('✅ Health check completed successfully');
            } else {
                $this->error('⚠️  Health check found issues');
            }

            return $exitCode;
        } catch (\Exception $e) {
            $this->error("❌ Health check failed: {$e->getMessage()}");
            Log::error('Subscription health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Check overall system health.
     */
    private function checkOverallHealth(SubscriptionHealthMonitorService $healthMonitor, string $period): array
    {
        $this->line('Checking overall subscription system health...');

        $startTime = microtime(true);
        $health = $healthMonitor->calculateOverallHealth($period);
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $health['check_duration_ms'] = $duration;

        return $health;
    }

    /**
     * Check specific tenant health.
     */
    private function checkTenantHealth(SubscriptionHealthMonitorService $healthMonitor, Tenant $tenant, string $period): array
    {
        $this->line("Checking health for tenant: {$tenant->name} (ID: {$tenant->id})");

        $startTime = microtime(true);
        $health = $healthMonitor->calculateTenantHealth($tenant, $period);
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $health['check_duration_ms'] = $duration;

        return $health;
    }

    /**
     * Display health report in console.
     */
    private function displayHealthReport(array $result): void
    {
        $this->newLine();
        $this->line('═══════════════════════════════════════════════════════════════');
        $this->line('                    SUBSCRIPTION HEALTH REPORT                  ');
        $this->line('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        // Overall Status
        $statusColor = $this->getStatusColor($result['status'] ?? 'unknown');
        $score = $result['health_score'] ?? 0;

        $this->line("Health Score: <{$statusColor}>{$score}/100</{$statusColor}>");
        $this->line("Status: <{$statusColor}>" . strtoupper($result['status'] ?? 'unknown') . "</{$statusColor}>");
        $this->line("Period: " . ($result['period'] ?? 'N/A'));
        $this->line("Checked At: " . ($result['checked_at'] ?? now()->toIso8601String()));
        $this->newLine();

        // Metrics
        if (isset($result['metrics'])) {
            $this->displayMetrics($result['metrics']);
        }

        // Alerts
        if (isset($result['alerts']) && !empty($result['alerts'])) {
            $this->displayAlerts($result['alerts']);
        } else {
            $this->info('✓ No alerts detected');
        }

        // Tenant-specific info
        if (isset($result['tenant_id'])) {
            $this->newLine();
            $this->line('─────────────────────────────────────────────────────────────');
            $this->line("Tenant: {$result['tenant_name']} (ID: {$result['tenant_id']})");

            if (isset($result['subscriptions'])) {
                $subs = $result['subscriptions'];
                $this->line("Active Subscriptions: {$subs['active']}");
                $this->line("Trialing: {$subs['trialing']}");
                $this->line("Past Due: <fg=red>{$subs['past_due']}</>");
                $this->line("Cancelled: {$subs['cancelled']}");
                $this->line("Total MRR: €{$result['mrr']}");
            }
        }

        $this->newLine();
        $this->line('═══════════════════════════════════════════════════════════════');
    }

    /**
     * Display metrics section.
     */
    private function displayMetrics(array $metrics): void
    {
        $this->line('─────────────────────────────────────────────────────────────');
        $this->line('METRICS');
        $this->line('─────────────────────────────────────────────────────────────');

        // Payment Success Rate
        if (isset($metrics['payment_success_rate'])) {
            $psr = $metrics['payment_success_rate'];
            $color = $psr['is_healthy'] ? 'green' : 'red';
            $this->line(sprintf(
                'Payment Success Rate: <fg=%s>%.2f%%</> (Failed: %d/%d)',
                $color,
                $psr['success_rate'],
                $psr['failed'],
                $psr['total_payments']
            ));
        }

        // Churn Rate
        if (isset($metrics['churn_rate'])) {
            $churn = $metrics['churn_rate'];
            $color = $churn['is_healthy'] ? 'green' : 'red';
            $this->line(sprintf(
                'Churn Rate: <fg=%s>%.2f%%</> (Cancelled: %d)',
                $color,
                $churn['churn_rate'],
                $churn['cancelled_subscriptions']
            ));
        }

        // Webhook Health
        if (isset($metrics['webhook_health'])) {
            $webhook = $metrics['webhook_health'];
            $color = $webhook['is_healthy'] ? 'green' : 'yellow';
            $this->line(sprintf(
                'Webhook Processing: <fg=%s>%s</> (Avg: %.2fs, Max: %.2fs)',
                $color,
                $webhook['status'],
                $webhook['avg_processing_time'],
                $webhook['max_processing_time']
            ));
        }

        // Queue Health
        if (isset($metrics['queue_health'])) {
            $queue = $metrics['queue_health'];
            $color = $queue['is_healthy'] ? 'green' : 'red';
            $this->line(sprintf(
                'Queue Health: <fg=%s>%.2f%% failure rate</> (Failed: %d jobs)',
                $color,
                $queue['failure_rate'],
                $queue['failed_jobs']
            ));
        }

        // Stripe API Health
        if (isset($metrics['stripe_api_health'])) {
            $stripe = $metrics['stripe_api_health'];
            $color = $stripe['is_healthy'] ? 'green' : 'red';
            $status = $stripe['api_accessible'] ? 'Accessible' : 'Unavailable';
            $this->line(sprintf(
                'Stripe API: <fg=%s>%s</> (Response: %.2fms, Error Rate: %.2f%%)',
                $color,
                $status,
                $stripe['response_time_ms'],
                $stripe['error_rate']
            ));
        }

        // MRR Growth
        if (isset($metrics['mrr_growth'])) {
            $mrr = $metrics['mrr_growth'];
            $color = $mrr['is_healthy'] ? 'green' : 'red';
            $this->line(sprintf(
                'MRR Growth: <fg=%s>%.2f%%</> (€%.2f → €%.2f, %s)',
                $color,
                $mrr['growth_rate'],
                $mrr['mrr_start'],
                $mrr['mrr_end'],
                $mrr['trend']
            ));
        }

        $this->newLine();
    }

    /**
     * Display alerts section.
     */
    private function displayAlerts(array $alerts): void
    {
        $this->line('─────────────────────────────────────────────────────────────');
        $this->line('⚠️  ALERTS');
        $this->line('─────────────────────────────────────────────────────────────');

        foreach ($alerts as $alert) {
            $severityColor = match ($alert['severity']) {
                'critical' => 'red',
                'high' => 'red',
                'medium' => 'yellow',
                'low' => 'blue',
                default => 'white',
            };

            $icon = match ($alert['severity']) {
                'critical' => '🔴',
                'high' => '🔴',
                'medium' => '🟡',
                'low' => '🔵',
                default => '⚪',
            };

            $this->line(sprintf(
                '%s <fg=%s>[%s]</> %s',
                $icon,
                $severityColor,
                strtoupper($alert['severity']),
                $alert['message']
            ));
        }

        $this->newLine();
    }

    /**
     * Send alerts via configured channels.
     */
    private function sendAlerts(array $result): void
    {
        $criticalAlerts = array_filter($result['alerts'], fn ($alert) => in_array($alert['severity'], ['critical', 'high']));

        if (empty($criticalAlerts)) {
            return;
        }

        $this->warn('📧 Sending alerts for critical issues...');

        // Log alerts
        Log::warning('Subscription health check detected critical issues', [
            'health_score' => $result['health_score'],
            'status' => $result['status'],
            'alerts' => $criticalAlerts,
            'period' => $result['period'],
        ]);

        // Send email notification (if configured)
        $adminEmail = config('mail.admin_email');
        if ($adminEmail) {
            try {
                Mail::raw(
                    $this->formatAlertsEmail($result, $criticalAlerts),
                    function ($message) use ($adminEmail) {
                        $message->to($adminEmail)
                            ->subject('🚨 Subscription System Health Alert');
                    }
                );

                $this->info("✓ Alert email sent to {$adminEmail}");
            } catch (\Exception $e) {
                $this->error("Failed to send alert email: {$e->getMessage()}");
            }
        }

        // Additional alert channels (Slack, Sentry, etc.) could be added here
    }

    /**
     * Format alerts for email.
     */
    private function formatAlertsEmail(array $result, array $alerts): string
    {
        $content = "Subscription System Health Alert\n";
        $content .= "==================================\n\n";
        $content .= "Health Score: {$result['health_score']}/100\n";
        $content .= "Status: " . strtoupper($result['status']) . "\n";
        $content .= "Period: {$result['period']}\n";
        $content .= "Checked At: {$result['checked_at']}\n\n";
        $content .= "Critical Issues Detected:\n";
        $content .= "-------------------------\n\n";

        foreach ($alerts as $alert) {
            $content .= sprintf(
                "[%s] %s\n  Metric: %s\n  Value: %.2f (Threshold: %.2f)\n\n",
                strtoupper($alert['severity']),
                $alert['message'],
                $alert['metric'],
                $alert['value'],
                $alert['threshold']
            );
        }

        $content .= "\nPlease investigate these issues immediately.\n";
        $content .= "Run 'php artisan subscriptions:health-check --verbose' for detailed information.\n";

        return $content;
    }

    /**
     * Log health check results.
     */
    private function logHealthCheck(array $result): void
    {
        $logLevel = empty($result['alerts']) ? 'info' : 'warning';

        Log::log($logLevel, 'Subscription health check completed', [
            'health_score' => $result['health_score'] ?? null,
            'status' => $result['status'] ?? null,
            'alerts_count' => count($result['alerts'] ?? []),
            'period' => $result['period'] ?? null,
            'duration_ms' => $result['check_duration_ms'] ?? null,
        ]);
    }

    /**
     * Determine exit code based on health status.
     */
    private function determineExitCode(array $result): int
    {
        // Check for critical alerts
        $criticalAlerts = array_filter(
            $result['alerts'] ?? [],
            fn ($alert) => $alert['severity'] === 'critical'
        );

        if (!empty($criticalAlerts)) {
            return Command::FAILURE;
        }

        // Check health score
        $score = $result['health_score'] ?? 100;
        if ($score < 40) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Get color for status display.
     */
    private function getStatusColor(string $status): string
    {
        return match ($status) {
            'excellent' => 'green',
            'good' => 'green',
            'fair' => 'yellow',
            'poor' => 'red',
            'critical' => 'red',
            default => 'white',
        };
    }
}
