<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Stripe\SubscriptionAnalyticsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SubscriptionAnalyticsReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:report
                            {--tenant= : Specific tenant ID to generate report for}
                            {--format=table : Output format (table, json, csv)}
                            {--email : Send report via email (not implemented)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate comprehensive subscription analytics report';

    /**
     * Execute the console command.
     */
    public function handle(SubscriptionAnalyticsService $analytics): int
    {
        $this->info('🏀 Generating Subscription Analytics Report...');
        $this->newLine();

        $tenantId = $this->option('tenant');
        $format = $this->option('format');
        $sendEmail = $this->option('email');

        // Validate format
        if (!in_array($format, ['table', 'json', 'csv'])) {
            $this->error("Invalid format: {$format}. Must be 'table', 'json', or 'csv'.");
            return self::FAILURE;
        }

        // Get tenants to process
        $tenants = $this->getTenants($tenantId);

        if ($tenants->isEmpty()) {
            $this->error('No tenants found to process.');
            return self::FAILURE;
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($tenants as $tenant) {
            try {
                $this->generateReport($tenant, $analytics, $format, $sendEmail);
                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("Error generating report for tenant {$tenant->name}: {$e->getMessage()}");
                Log::error('Analytics report generation failed', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->newLine();
        $this->info('✅ Report generation completed!');

        return $errorCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Generate report for a single tenant.
     */
    private function generateReport(
        Tenant $tenant,
        SubscriptionAnalyticsService $analytics,
        string $format,
        bool $sendEmail
    ): void {
        $this->line("<fg=cyan>==============================================</>");
        $this->line("<fg=cyan>Subscription Analytics Report</>");
        $this->line("<fg=cyan>==============================================</>");
        $this->line("Tenant: <fg=white;options=bold>{$tenant->name}</> (ID: {$tenant->id})");
        $this->line("Date: " . now()->format('Y-m-d H:i:s'));
        $this->line("<fg=cyan>==============================================</>");
        $this->newLine();

        // Collect all metrics
        $report = [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
            ],
            'date' => now()->format('Y-m-d H:i:s'),
            'mrr' => $this->collectMRRMetrics($tenant, $analytics),
            'churn' => $this->collectChurnMetrics($tenant, $analytics),
            'ltv' => $this->collectLTVMetrics($tenant, $analytics),
            'health' => $this->collectHealthMetrics($tenant, $analytics),
        ];

        // Output based on format
        if ($format === 'json') {
            $this->outputJSON($report);
        } elseif ($format === 'csv') {
            $this->outputCSV($report);
        } else {
            $this->outputTable($report);
        }

        // Send email if requested
        if ($sendEmail) {
            $this->warn('📧 Email sending is not yet implemented. Report available in output only.');
            // TODO: Implement email sending
            // Mail::to($tenant->admin_email)->send(new SubscriptionAnalyticsReport($report));
        }
    }

    /**
     * Collect MRR metrics.
     */
    private function collectMRRMetrics(Tenant $tenant, SubscriptionAnalyticsService $analytics): array
    {
        return [
            'total' => $analytics->calculateTenantMRR($tenant),
            'growth_rate_3m' => $analytics->getMRRGrowthRate($tenant, 3),
            'by_plan' => $analytics->getMRRByPlan($tenant),
        ];
    }

    /**
     * Collect Churn metrics.
     */
    private function collectChurnMetrics(Tenant $tenant, SubscriptionAnalyticsService $analytics): array
    {
        $monthlyChurn = $analytics->calculateMonthlyChurnRate($tenant);

        return [
            'monthly_rate' => $monthlyChurn['churn_rate'],
            'voluntary_churn' => $monthlyChurn['voluntary_churn'],
            'involuntary_churn' => $monthlyChurn['involuntary_churn'],
            'revenue_churn' => $analytics->calculateRevenueChurn($tenant),
            'reasons' => $analytics->getChurnReasons($tenant),
        ];
    }

    /**
     * Collect LTV metrics.
     */
    private function collectLTVMetrics(Tenant $tenant, SubscriptionAnalyticsService $analytics): array
    {
        return [
            'average' => $analytics->calculateAverageLTV($tenant),
            'by_plan' => $analytics->getLTVByPlan($tenant),
            'lifetime_stats' => $analytics->getCustomerLifetimeStats($tenant),
        ];
    }

    /**
     * Collect Health metrics.
     */
    private function collectHealthMetrics(Tenant $tenant, SubscriptionAnalyticsService $analytics): array
    {
        return [
            'active_subscriptions' => $analytics->getActiveSubscriptionsCount($tenant),
            'trial_conversion' => $analytics->getTrialConversionRate($tenant),
            'avg_duration_days' => $analytics->getAverageSubscriptionDuration($tenant),
            'upgrade_downgrade' => $analytics->getUpgradeDowngradeRates($tenant),
        ];
    }

    /**
     * Output report as formatted tables.
     */
    private function outputTable(array $report): void
    {
        // MRR Section
        $this->line("<fg=green;options=bold>📊 MRR (Monthly Recurring Revenue)</>");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total MRR', '€' . number_format($report['mrr']['total'], 2)],
                ['Growth Rate (3 months)', number_format($report['mrr']['growth_rate_3m'], 2) . '%'],
            ]
        );

        if (!empty($report['mrr']['by_plan'])) {
            $this->line('MRR by Plan:');
            $planTable = [];
            foreach ($report['mrr']['by_plan'] as $planData) {
                $planTable[] = [
                    $planData['plan_name'],
                    '€' . number_format($planData['mrr'], 2),
                    $planData['club_count'],
                    number_format($planData['percentage'], 1) . '%',
                ];
            }
            $this->table(['Plan', 'MRR', 'Clubs', 'Share'], $planTable);
        }

        $this->newLine();

        // Churn Section
        $this->line("<fg=yellow;options=bold>📉 Churn Metrics</>");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Monthly Churn Rate', number_format($report['churn']['monthly_rate'], 2) . '%'],
                ['Voluntary Churn', $report['churn']['voluntary_churn']],
                ['Involuntary Churn', $report['churn']['involuntary_churn']],
                ['Revenue Churn', number_format($report['churn']['revenue_churn'], 2) . '%'],
            ]
        );

        $this->newLine();

        // LTV Section
        $this->line("<fg=blue;options=bold>💰 LTV (Lifetime Value)</>");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Average LTV', '€' . number_format($report['ltv']['average'], 2)],
            ]
        );

        $this->newLine();

        // Health Section
        $this->line("<fg=cyan;options=bold>💚 Health Metrics</>");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Active Subscriptions', $report['health']['active_subscriptions']],
                ['Trial Conversion Rate', number_format($report['health']['trial_conversion'], 2) . '%'],
                ['Avg Subscription Duration', number_format($report['health']['avg_duration_days'], 0) . ' days'],
            ]
        );
    }

    /**
     * Output report as JSON.
     */
    private function outputJSON(array $report): void
    {
        $this->line(json_encode($report, JSON_PRETTY_PRINT));
    }

    /**
     * Output report as CSV.
     */
    private function outputCSV(array $report): void
    {
        $this->line('Metric,Value');
        $this->line("Tenant,{$report['tenant']['name']}");
        $this->line("Date,{$report['date']}");
        $this->line('');
        $this->line('MRR Metrics');
        $this->line("Total MRR,{$report['mrr']['total']}");
        $this->line("MRR Growth Rate (3m),{$report['mrr']['growth_rate_3m']}");
        $this->line('');
        $this->line('Churn Metrics');
        $this->line("Monthly Churn Rate,{$report['churn']['monthly_rate']}");
        $this->line("Revenue Churn,{$report['churn']['revenue_churn']}");
        $this->line('');
        $this->line('LTV Metrics');
        $this->line("Average LTV,{$report['ltv']['average']}");
        $this->line('');
        $this->line('Health Metrics');
        $this->line("Active Subscriptions,{$report['health']['active_subscriptions']}");
        $this->line("Trial Conversion Rate,{$report['health']['trial_conversion']}");
        $this->line("Avg Duration (days),{$report['health']['avg_duration_days']}");
    }

    /**
     * Get tenants to process based on options.
     */
    private function getTenants(?string $tenantId)
    {
        if ($tenantId) {
            return Tenant::where('id', $tenantId)->get();
        }

        return Tenant::where('is_active', true)->get();
    }
}
