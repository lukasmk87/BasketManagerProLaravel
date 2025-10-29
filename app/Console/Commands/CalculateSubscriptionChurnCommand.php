<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Stripe\SubscriptionAnalyticsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CalculateSubscriptionChurnCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:calculate-churn
                            {--tenant= : Specific tenant ID to calculate churn for}
                            {--month= : Specific month to calculate (format: YYYY-MM)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate monthly churn rates and identify churned customers';

    /**
     * Execute the console command.
     */
    public function handle(SubscriptionAnalyticsService $analytics): int
    {
        $this->info('🏀 Starting churn rate calculation...');

        $tenantId = $this->option('tenant');
        $monthOption = $this->option('month');

        // Parse month
        try {
            $month = $monthOption
                ? Carbon::parse($monthOption)->startOfMonth()
                : now()->subMonth()->startOfMonth();
        } catch (\Exception $e) {
            $this->error("Invalid month format: {$monthOption}. Use YYYY-MM format.");
            return self::FAILURE;
        }

        // Get tenants to process
        $tenants = $this->getTenants($tenantId);

        if ($tenants->isEmpty()) {
            $this->error('No tenants found to process.');
            return self::FAILURE;
        }

        $this->info("Calculating churn for {$tenants->count()} tenant(s) - {$month->format('F Y')}");
        $this->newLine();

        $successCount = 0;
        $errorCount = 0;
        $highChurnAlerts = 0;

        foreach ($tenants as $tenant) {
            try {
                $hasHighChurn = $this->processTenant($tenant, $analytics, $month);

                if ($hasHighChurn) {
                    $highChurnAlerts++;
                }

                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("Error processing tenant {$tenant->name}: {$e->getMessage()}");
                Log::error('Churn calculation failed', [
                    'tenant_id' => $tenant->id,
                    'month' => $month->format('Y-m'),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            $this->newLine();
        }

        // Summary
        $this->info('✅ Churn calculation completed!');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Tenants Processed', $successCount],
                ['Errors', $errorCount],
                ['High Churn Alerts (>5%)', $highChurnAlerts],
            ]
        );

        return $errorCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Process a single tenant's churn calculation.
     *
     * @return bool Returns true if churn rate is high (>5%)
     */
    private function processTenant(
        Tenant $tenant,
        SubscriptionAnalyticsService $analytics,
        Carbon $month
    ): bool {
        $this->line("<fg=cyan>Tenant:</> <fg=white;options=bold>{$tenant->name}</> (ID: {$tenant->id})");

        // Calculate churn data
        $churnData = $analytics->calculateMonthlyChurnRate($tenant, $month);

        // Display churn metrics table
        $this->table(
            ['Metric', 'Value'],
            [
                ['Period', $churnData['period']],
                ['Customers at Start', $churnData['customers_start']],
                ['Customers at End', $churnData['customers_end']],
                ['Churned Customers', $churnData['churned_customers']],
                ['Churn Rate', number_format($churnData['churn_rate'], 2) . '%'],
                ['Voluntary Churn', $churnData['voluntary_churn']],
                ['Involuntary Churn', $churnData['involuntary_churn']],
            ]
        );

        // Calculate revenue churn
        $revenueChurn = $analytics->calculateRevenueChurn($tenant, $month);
        $this->line("  <fg=yellow>Revenue Churn:</> " . number_format($revenueChurn, 2) . '%');

        // Get churn reasons breakdown
        $churnReasons = $analytics->getChurnReasons($tenant, 6);

        if (!empty($churnReasons)) {
            $this->line('  <fg=yellow>Churn Reasons (Last 6 Months):</>');

            $reasonsTable = [];
            foreach ($churnReasons as $reason => $data) {
                $reasonsTable[] = [
                    ucfirst(str_replace('_', ' ', $reason)),
                    $data['count'],
                    number_format($data['percentage'], 1) . '%',
                ];
            }

            $this->table(
                ['Reason', 'Count', 'Percentage'],
                $reasonsTable
            );
        }

        // Alert if churn rate is high
        $isHighChurn = $churnData['churn_rate'] > 5.0;

        if ($isHighChurn) {
            $this->warn("  ⚠️  HIGH CHURN ALERT: Churn rate is {$churnData['churn_rate']}% (threshold: 5%)");
            $this->line("  📧 Consider investigating and sending retention campaigns.");

            // Log high churn for monitoring
            Log::warning('High churn rate detected', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'period' => $churnData['period'],
                'churn_rate' => $churnData['churn_rate'],
                'churned_customers' => $churnData['churned_customers'],
                'voluntary_churn' => $churnData['voluntary_churn'],
                'involuntary_churn' => $churnData['involuntary_churn'],
            ]);

            // TODO: Send alert email to tenant admin
        } else {
            $this->info("  ✅ Churn rate is within acceptable limits.");
        }

        return $isHighChurn;
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
