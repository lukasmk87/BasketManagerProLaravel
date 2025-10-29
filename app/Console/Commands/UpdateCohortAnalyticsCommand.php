<?php

namespace App\Console\Commands;

use App\Models\Club;
use App\Models\ClubSubscriptionCohort;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateCohortAnalyticsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:update-cohorts
                            {--tenant= : Specific tenant ID to calculate cohorts for}
                            {--cohort= : Specific cohort month to calculate (format: YYYY-MM)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate cohort retention and LTV for subscription analytics';

    /**
     * Tracked retention periods in months.
     */
    private const RETENTION_PERIODS = [1, 2, 3, 6, 12];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🏀 Starting cohort analytics calculation...');

        $tenantId = $this->option('tenant');
        $cohortOption = $this->option('cohort');

        // Get tenants to process
        $tenants = $this->getTenants($tenantId);

        if ($tenants->isEmpty()) {
            $this->error('No tenants found to process.');
            return self::FAILURE;
        }

        $this->info("Processing cohorts for {$tenants->count()} tenant(s)...");
        $this->newLine();

        $totalCohortsProcessed = 0;
        $errorCount = 0;

        foreach ($tenants as $tenant) {
            try {
                $this->line("<fg=cyan>Tenant:</> <fg=white;options=bold>{$tenant->name}</> (ID: {$tenant->id})");

                // Get cohort months to process
                $cohortMonths = $cohortOption
                    ? [Carbon::parse($cohortOption)->startOfMonth()]
                    : $this->getCohortMonths($tenant);

                if (empty($cohortMonths)) {
                    $this->warn("  No cohorts found for this tenant.");
                    continue;
                }

                $this->info("  Found " . count($cohortMonths) . " cohort(s) to process.");

                foreach ($cohortMonths as $cohortMonth) {
                    $this->processCohort($tenant, $cohortMonth);
                    $totalCohortsProcessed++;
                }

                $this->newLine();
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("Error processing tenant {$tenant->name}: {$e->getMessage()}");
                Log::error('Cohort calculation failed', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // Summary
        $this->info('✅ Cohort analytics calculation completed!');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Tenants Processed', $tenants->count()],
                ['Total Cohorts Processed', $totalCohortsProcessed],
                ['Errors', $errorCount],
            ]
        );

        return $errorCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Process a single cohort.
     */
    private function processCohort(Tenant $tenant, Carbon $cohortMonth): void
    {
        $this->line("    Processing cohort: <fg=yellow>{$cohortMonth->format('Y-m')}</>");

        // Find clubs that started in this cohort month
        $cohortClubs = Club::where('tenant_id', $tenant->id)
            ->whereYear('subscription_started_at', $cohortMonth->year)
            ->whereMonth('subscription_started_at', $cohortMonth->month)
            ->whereNotNull('subscription_started_at')
            ->get();

        $cohortSize = $cohortClubs->count();

        if ($cohortSize === 0) {
            $this->line("      <fg=gray>No clubs in this cohort. Skipping.</>");
            return;
        }

        $this->line("      Cohort Size: <fg=white;options=bold>{$cohortSize}</> clubs");

        // Calculate retention for each tracked period
        $retentionRates = $this->calculateRetentionRates($cohortClubs, $cohortMonth, $cohortSize);

        // Calculate cumulative revenue from this cohort
        $cumulativeRevenue = $cohortClubs->sum('lifetime_revenue');

        // Calculate average LTV
        $avgLTV = $cohortSize > 0 ? $cumulativeRevenue / $cohortSize : 0;

        // Display retention table
        $retentionTable = [];
        foreach (self::RETENTION_PERIODS as $monthsAfter) {
            $rate = $retentionRates[$monthsAfter] ?? 0;
            $retentionTable[] = [
                "Month {$monthsAfter}",
                number_format($rate, 2) . '%',
            ];
        }

        $this->table(
            ['Period', 'Retention Rate'],
            $retentionTable
        );

        $this->line("      Cumulative Revenue: <fg=green>€" . number_format($cumulativeRevenue, 2) . "</>");
        $this->line("      Average LTV: <fg=green>€" . number_format($avgLTV, 2) . "</>");

        // Create or update cohort record
        ClubSubscriptionCohort::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'cohort_month' => $cohortMonth,
            ],
            [
                'cohort_size' => $cohortSize,
                'retention_month_1' => $retentionRates[1] ?? 100,
                'retention_month_2' => $retentionRates[2] ?? 0,
                'retention_month_3' => $retentionRates[3] ?? 0,
                'retention_month_6' => $retentionRates[6] ?? 0,
                'retention_month_12' => $retentionRates[12] ?? 0,
                'cumulative_revenue' => $cumulativeRevenue,
                'avg_ltv' => $avgLTV,
                'last_calculated_at' => now(),
            ]
        );

        $this->line("      ✅ Cohort data saved.");
    }

    /**
     * Calculate retention rates for a cohort.
     *
     * @return array<int, float> Retention rates keyed by months after cohort start
     */
    private function calculateRetentionRates($cohortClubs, Carbon $cohortMonth, int $cohortSize): array
    {
        $retentionRates = [];

        foreach (self::RETENTION_PERIODS as $monthsAfter) {
            $targetDate = $cohortMonth->copy()->addMonths($monthsAfter);

            // Only calculate if target date is in the past
            if ($targetDate->isFuture()) {
                $retentionRates[$monthsAfter] = 0;
                continue;
            }

            // Count how many clubs are still active at target date
            $stillActive = $cohortClubs->filter(function ($club) use ($targetDate) {
                // Active if:
                // 1. subscription_started_at <= targetDate
                // 2. AND (subscription_ends_at is null OR subscription_ends_at > targetDate)
                $startedBeforeTarget = $club->subscription_started_at <= $targetDate;
                $stillActiveAtTarget = $club->subscription_ends_at === null
                    || $club->subscription_ends_at > $targetDate;

                return $startedBeforeTarget && $stillActiveAtTarget;
            })->count();

            $retentionRate = ($stillActive / $cohortSize) * 100;
            $retentionRates[$monthsAfter] = round($retentionRate, 2);
        }

        return $retentionRates;
    }

    /**
     * Get all cohort months for a tenant.
     *
     * @return array<Carbon>
     */
    private function getCohortMonths(Tenant $tenant): array
    {
        // Get all unique cohort months from club subscription_started_at
        $cohortMonths = Club::where('tenant_id', $tenant->id)
            ->whereNotNull('subscription_started_at')
            ->select(DB::raw('DATE_FORMAT(subscription_started_at, "%Y-%m-01") as cohort_month'))
            ->distinct()
            ->orderBy('cohort_month', 'desc')
            ->pluck('cohort_month')
            ->map(fn ($month) => Carbon::parse($month))
            ->toArray();

        return $cohortMonths;
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
