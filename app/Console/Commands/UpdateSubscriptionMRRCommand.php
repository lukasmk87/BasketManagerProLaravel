<?php

namespace App\Console\Commands;

use App\Models\Club;
use App\Models\ClubSubscriptionEvent;
use App\Models\SubscriptionMRRSnapshot;
use App\Models\Tenant;
use App\Services\Stripe\SubscriptionAnalyticsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateSubscriptionMRRCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:update-mrr
                            {--tenant= : Specific tenant ID to calculate MRR for}
                            {--type=daily : Snapshot type (daily or monthly)}
                            {--force : Force recalculation even if snapshot exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and store MRR snapshots for subscription analytics';

    /**
     * Execute the console command.
     */
    public function handle(SubscriptionAnalyticsService $analytics): int
    {
        $this->info('🏀 Starting MRR snapshot calculation...');

        $tenantId = $this->option('tenant');
        $snapshotType = $this->option('type');
        $force = $this->option('force');

        // Validate snapshot type
        if (!in_array($snapshotType, ['daily', 'monthly'])) {
            $this->error("Invalid snapshot type: {$snapshotType}. Must be 'daily' or 'monthly'.");
            return self::FAILURE;
        }

        // Get tenants to process
        $tenants = $this->getTenants($tenantId);

        if ($tenants->isEmpty()) {
            $this->error('No tenants found to process.');
            return self::FAILURE;
        }

        $this->info("Processing {$tenants->count()} tenant(s) for {$snapshotType} MRR snapshot...");

        $successCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($tenants as $tenant) {
            try {
                $result = $this->processTenant($tenant, $analytics, $snapshotType, $force);

                if ($result === 'success') {
                    $successCount++;
                } elseif ($result === 'skipped') {
                    $skippedCount++;
                }
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("Error processing tenant {$tenant->name}: {$e->getMessage()}");
                Log::error('MRR Snapshot calculation failed', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->newLine();
        $this->info('✅ MRR snapshot calculation completed!');
        $this->table(
            ['Status', 'Count'],
            [
                ['Success', $successCount],
                ['Skipped', $skippedCount],
                ['Errors', $errorCount],
            ]
        );

        return $errorCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Process a single tenant's MRR snapshot.
     */
    private function processTenant(
        Tenant $tenant,
        SubscriptionAnalyticsService $analytics,
        string $snapshotType,
        bool $force
    ): string {
        $this->info("Processing tenant: {$tenant->name} (ID: {$tenant->id})");

        $snapshotDate = today();

        // Check if snapshot already exists
        $existingSnapshot = SubscriptionMRRSnapshot::where('tenant_id', $tenant->id)
            ->where('snapshot_date', $snapshotDate)
            ->where('snapshot_type', $snapshotType)
            ->first();

        if ($existingSnapshot && !$force) {
            $this->warn("  ⏭️  Snapshot already exists for {$snapshotDate->format('Y-m-d')}. Use --force to recalculate.");
            return 'skipped';
        }

        // Calculate Club MRR (via SubscriptionAnalyticsService)
        $clubMRR = $analytics->calculateTenantMRR($tenant);

        // Calculate Tenant's own MRR (if using Cashier)
        $tenantMRR = $this->calculateTenantOwnMRR($tenant);

        // Total MRR
        $totalMRR = $clubMRR + $tenantMRR;

        // Get active club count
        $clubCount = Club::where('tenant_id', $tenant->id)
            ->whereIn('subscription_status', ['active', 'trialing'])
            ->count();

        // Calculate growth compared to previous snapshot
        $previousSnapshot = SubscriptionMRRSnapshot::where('tenant_id', $tenant->id)
            ->where('snapshot_type', $snapshotType)
            ->where('snapshot_date', '<', $snapshotDate)
            ->latest('snapshot_date')
            ->first();

        $mrrGrowth = $previousSnapshot ? ($totalMRR - $previousSnapshot->total_mrr) : 0;
        $mrrGrowthRate = $previousSnapshot && $previousSnapshot->total_mrr > 0
            ? (($totalMRR - $previousSnapshot->total_mrr) / $previousSnapshot->total_mrr) * 100
            : 0;

        // Calculate MRR breakdown from events (last period)
        $periodStart = $snapshotType === 'daily'
            ? today()->subDay()
            : today()->subMonth()->startOfMonth();
        $periodEnd = today();

        $mrrBreakdown = $this->calculateMRRBreakdown($tenant, $periodStart, $periodEnd);

        // Create or update snapshot
        $snapshot = $existingSnapshot ?: new SubscriptionMRRSnapshot();
        $snapshot->fill([
            'tenant_id' => $tenant->id,
            'snapshot_date' => $snapshotDate,
            'snapshot_type' => $snapshotType,
            'club_mrr' => $clubMRR,
            'club_count' => $clubCount,
            'tenant_mrr' => $tenantMRR,
            'total_mrr' => $totalMRR,
            'mrr_growth' => $mrrGrowth,
            'mrr_growth_rate' => round($mrrGrowthRate, 2),
            'new_business_mrr' => $mrrBreakdown['new_business'],
            'expansion_mrr' => $mrrBreakdown['expansion'],
            'contraction_mrr' => $mrrBreakdown['contraction'],
            'churned_mrr' => $mrrBreakdown['churned'],
        ]);
        $snapshot->save();

        // Update tenant's monthly_recurring_revenue field
        $tenant->update(['monthly_recurring_revenue' => $totalMRR]);

        $this->info("  ✅ MRR Snapshot created:");
        $this->line("     Club MRR: €" . number_format($clubMRR, 2));
        $this->line("     Tenant MRR: €" . number_format($tenantMRR, 2));
        $this->line("     Total MRR: €" . number_format($totalMRR, 2));
        $this->line("     Growth: €" . number_format($mrrGrowth, 2) . " (" . number_format($mrrGrowthRate, 2) . "%)");
        $this->line("     Active Clubs: {$clubCount}");

        return 'success';
    }

    /**
     * Calculate tenant's own MRR from Cashier subscription.
     */
    private function calculateTenantOwnMRR(Tenant $tenant): float
    {
        // If tenant uses Laravel Cashier for their own subscription
        if (method_exists($tenant, 'subscribed') && $tenant->subscribed('default')) {
            $subscription = $tenant->subscription('default');

            if ($subscription && $subscription->active()) {
                // Get subscription items and calculate MRR
                $items = $subscription->items;
                $totalAmount = 0;

                foreach ($items as $item) {
                    // Get price amount in cents
                    $amount = $item->quantity * $item->stripe_price;

                    // Normalize to monthly (if yearly, divide by 12)
                    // Note: This is a simplified calculation
                    // In reality, you'd need to check the price's recurring interval
                    $totalAmount += $amount;
                }

                // Convert cents to euros/dollars
                return $totalAmount / 100;
            }
        }

        return 0.0;
    }

    /**
     * Calculate MRR breakdown from subscription events.
     */
    private function calculateMRRBreakdown(Tenant $tenant, Carbon $periodStart, Carbon $periodEnd): array
    {
        $events = ClubSubscriptionEvent::where('tenant_id', $tenant->id)
            ->whereBetween('event_date', [$periodStart, $periodEnd])
            ->get();

        return [
            'new_business' => $events->where('event_type', 'subscription_created')
                ->sum('mrr_change'),
            'expansion' => $events->where('event_type', 'plan_upgraded')
                ->sum('mrr_change'),
            'contraction' => abs($events->where('event_type', 'plan_downgraded')
                ->sum('mrr_change')),
            'churned' => abs($events->whereIn('event_type', ['subscription_canceled', 'trial_expired'])
                ->sum('mrr_change')),
        ];
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
