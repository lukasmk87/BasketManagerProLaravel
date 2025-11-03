<?php

namespace App\Console\Commands;

use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use App\Models\Tenant;
use App\Services\Stripe\ClubSubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateClubSubscriptionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:migrate-clubs
                            {--tenant= : Migrate clubs for specific tenant ID}
                            {--dry-run : Preview migration without making changes}
                            {--force : Force migration without confirmation}
                            {--plan= : Assign specific plan slug to all clubs}
                            {--skip-stripe : Skip Stripe subscription creation}
                            {--batch-size=50 : Number of clubs to process in each batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing clubs to new subscription system';

    private int $migratedCount = 0;
    private int $skippedCount = 0;
    private int $errorCount = 0;
    private array $errors = [];

    /**
     * Execute the console command.
     */
    public function handle(ClubSubscriptionService $subscriptionService): int
    {
        $this->info('🔄 Starting Club Subscription Migration...');

        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $tenantId = $this->option('tenant-id');
        $planSlug = $this->option('plan');
        $skipStripe = $this->option('skip-stripe');
        $batchSize = (int) $this->option('batch-size');

        // Display migration configuration
        $this->displayConfiguration($dryRun, $force, $tenantId, $planSlug, $skipStripe, $batchSize);

        // Get clubs to migrate
        $clubs = $this->getClubsToMigrate($tenantId);

        if ($clubs->isEmpty()) {
            $this->info('✓ No clubs found to migrate.');
            return Command::SUCCESS;
        }

        $this->info("📊 Found {$clubs->count()} club(s) to migrate.");

        // Confirm migration (unless force flag is set or dry-run)
        if (!$dryRun && !$force && !$this->confirm('Do you want to proceed with the migration?')) {
            $this->warn('Migration canceled.');
            return Command::FAILURE;
        }

        // Process clubs in batches
        $progressBar = $this->output->createProgressBar($clubs->count());
        $progressBar->start();

        $clubs->chunk($batchSize)->each(function ($clubBatch) use ($subscriptionService, $planSlug, $skipStripe, $dryRun, $progressBar) {
            foreach ($clubBatch as $club) {
                $this->migrateClub($club, $subscriptionService, $planSlug, $skipStripe, $dryRun);
                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        // Display migration summary
        $this->displaySummary($dryRun);

        return $this->errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Display migration configuration.
     */
    private function displayConfiguration(bool $dryRun, bool $force, ?string $tenantId, ?string $planSlug, bool $skipStripe, int $batchSize): void
    {
        $this->info('📋 Migration Configuration:');
        $this->table(
            ['Option', 'Value'],
            [
                ['Dry Run', $dryRun ? '✓ Yes (preview only)' : '✗ No'],
                ['Force', $force ? '✓ Yes' : '✗ No'],
                ['Tenant ID', $tenantId ?? 'All tenants'],
                ['Plan Slug', $planSlug ?? 'Auto-detect'],
                ['Skip Stripe', $skipStripe ? '✓ Yes' : '✗ No'],
                ['Batch Size', $batchSize],
            ]
        );
        $this->newLine();
    }

    /**
     * Get clubs that need migration.
     */
    private function getClubsToMigrate(?string $tenantId)
    {
        $query = Club::query()
            ->whereNull('club_subscription_plan_id') // Only clubs without a subscription plan
            ->orWhere(function ($q) {
                $q->whereNotNull('club_subscription_plan_id')
                    ->whereNull('stripe_subscription_id'); // Or clubs with plan but no Stripe subscription
            });

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->with(['tenant', 'subscriptionPlan'])->get();
    }

    /**
     * Migrate a single club.
     */
    private function migrateClub(Club $club, ClubSubscriptionService $subscriptionService, ?string $planSlug, bool $skipStripe, bool $dryRun): void
    {
        try {
            // Find appropriate plan
            $plan = $this->determinePlanForClub($club, $planSlug);

            if (!$plan) {
                $this->errors[] = "Club {$club->id} ({$club->name}): No suitable plan found";
                $this->skippedCount++;
                return;
            }

            if ($dryRun) {
                $this->info("\n[DRY RUN] Would migrate club '{$club->name}' to plan '{$plan->name}'");
                $this->migratedCount++;
                return;
            }

            // Start transaction
            DB::beginTransaction();

            try {
                // Assign plan to club
                $subscriptionService->assignPlanToClub($club, $plan);

                // Create Stripe subscription (unless skip-stripe flag is set or plan is free)
                if (!$skipStripe && $plan->price > 0) {
                    $this->info("\n  Creating Stripe subscription for '{$club->name}'...");
                    // Note: Actual Stripe subscription creation would happen through checkout flow
                    // For migration, we just set up the plan assignment
                    $this->warn("  ⚠ Stripe subscription creation requires checkout flow. Plan assigned, but Stripe subscription not created.");
                }

                // Update club status
                $club->update([
                    'subscription_status' => $plan->price > 0 ? 'incomplete' : 'active',
                    'subscription_started_at' => now(),
                ]);

                DB::commit();
                $this->migratedCount++;

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            $this->errors[] = "Club {$club->id} ({$club->name}): {$e->getMessage()}";
            $this->errorCount++;

            Log::error('Club subscription migration failed', [
                'club_id' => $club->id,
                'club_name' => $club->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Determine which plan to assign to a club.
     */
    private function determinePlanForClub(Club $club, ?string $planSlug): ?ClubSubscriptionPlan
    {
        // If specific plan slug is provided, use it
        if ($planSlug) {
            return ClubSubscriptionPlan::where('tenant_id', $club->tenant_id)
                ->where('slug', $planSlug)
                ->where('is_active', true)
                ->first();
        }

        // Auto-detect based on club characteristics
        $query = ClubSubscriptionPlan::where('tenant_id', $club->tenant_id)
            ->where('is_active', true)
            ->orderBy('price');

        // Get team count to determine appropriate plan
        $teamCount = $club->teams()->count();

        if ($teamCount === 0) {
            // Free plan for clubs with no teams
            return $query->where('price', 0)->first();
        } elseif ($teamCount <= 5) {
            // Standard plan for clubs with few teams
            return $query->where('price', '>', 0)->where('price', '<=', 50)->first();
        } elseif ($teamCount <= 20) {
            // Premium plan for medium-sized clubs
            return $query->where('price', '>', 50)->where('price', '<=', 150)->first();
        } else {
            // Enterprise plan for large clubs
            return $query->where('price', '>', 150)->first();
        }
    }

    /**
     * Display migration summary.
     */
    private function displaySummary(bool $dryRun): void
    {
        $this->info('✅ Migration ' . ($dryRun ? 'Preview' : 'Completed') . '!');
        $this->newLine();

        $this->table(
            ['Status', 'Count'],
            [
                ['✓ Migrated', $this->migratedCount],
                ['⊘ Skipped', $this->skippedCount],
                ['✗ Errors', $this->errorCount],
                ['Total', $this->migratedCount + $this->skippedCount + $this->errorCount],
            ]
        );

        if (!empty($this->errors)) {
            $this->newLine();
            $this->error('❌ Errors encountered:');
            foreach ($this->errors as $error) {
                $this->error("  - {$error}");
            }
        }

        if ($dryRun && $this->migratedCount > 0) {
            $this->newLine();
            $this->info('💡 To execute the migration, run the command without the --dry-run flag.');
        }
    }
}
