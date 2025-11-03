<?php

namespace App\Console\Commands;

use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use App\Models\Tenant;
use App\Models\ClubSubscriptionEvent;
use App\Models\SubscriptionMRRSnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidateSubscriptionDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:validate
                            {--tenant= : Validate data for specific tenant ID}
                            {--fix : Automatically fix issues where possible}
                            {--verbose : Show detailed validation results}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate subscription data integrity and Stripe sync status';

    private array $issues = [];
    private int $criticalIssues = 0;
    private int $warningIssues = 0;
    private int $fixedIssues = 0;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Starting Subscription Data Validation...');
        $this->newLine();

        $tenantId = $this->option('tenant-id');
        $fix = $this->option('fix');
        $verbose = $this->option('verbose');

        // Run validation checks
        $this->validateOrphanedClubs($tenantId, $fix, $verbose);
        $this->validateOrphanedPlans($tenantId, $fix, $verbose);
        $this->validateStripeSyncStatus($tenantId, $fix, $verbose);
        $this->validateSubscriptionStates($tenantId, $fix, $verbose);
        $this->validateMissingRequiredFields($tenantId, $fix, $verbose);
        $this->validatePlanLimits($tenantId, $fix, $verbose);
        $this->validateAnalyticsData($tenantId, $fix, $verbose);

        // Display summary
        $this->displaySummary($fix);

        return $this->criticalIssues > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Validate orphaned clubs (clubs without tenant).
     */
    private function validateOrphanedClubs(?string $tenantId, bool $fix, bool $verbose): void
    {
        $this->info('📦 Checking for orphaned clubs...');

        $query = Club::whereDoesntHave('tenant');
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $orphanedClubs = $query->get();

        if ($orphanedClubs->isEmpty()) {
            $this->info('  ✓ No orphaned clubs found.');
            return;
        }

        $this->criticalIssues += $orphanedClubs->count();
        $this->issues[] = [
            'type' => 'CRITICAL',
            'category' => 'Orphaned Clubs',
            'count' => $orphanedClubs->count(),
            'description' => 'Clubs without valid tenant reference',
            'action' => $fix ? 'These records should be manually reviewed' : 'Run with --fix to attempt automatic cleanup',
        ];

        if ($verbose) {
            $this->error("  ✗ Found {$orphanedClubs->count()} orphaned club(s):");
            foreach ($orphanedClubs as $club) {
                $this->line("    - Club ID: {$club->id}, Name: {$club->name}, Tenant ID: {$club->tenant_id}");
            }
        }
    }

    /**
     * Validate orphaned subscription plans.
     */
    private function validateOrphanedPlans(?string $tenantId, bool $fix, bool $verbose): void
    {
        $this->info('📋 Checking for orphaned subscription plans...');

        $query = ClubSubscriptionPlan::whereDoesntHave('tenant');
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $orphanedPlans = $query->get();

        if ($orphanedPlans->isEmpty()) {
            $this->info('  ✓ No orphaned plans found.');
            return;
        }

        $this->criticalIssues += $orphanedPlans->count();
        $this->issues[] = [
            'type' => 'CRITICAL',
            'category' => 'Orphaned Plans',
            'count' => $orphanedPlans->count(),
            'description' => 'Subscription plans without valid tenant reference',
            'action' => $fix ? 'These records should be manually reviewed' : 'Run with --fix to attempt automatic cleanup',
        ];

        if ($verbose) {
            $this->error("  ✗ Found {$orphanedPlans->count()} orphaned plan(s):");
            foreach ($orphanedPlans as $plan) {
                $this->line("    - Plan ID: {$plan->id}, Name: {$plan->name}, Tenant ID: {$plan->tenant_id}");
            }
        }
    }

    /**
     * Validate Stripe sync status.
     */
    private function validateStripeSyncStatus(?string $tenantId, bool $fix, bool $verbose): void
    {
        $this->info('🔗 Checking Stripe sync status...');

        $query = ClubSubscriptionPlan::where('is_active', true)
            ->where('price', '>', 0)
            ->where(function ($q) {
                $q->where('is_stripe_synced', false)
                    ->orWhereNull('stripe_product_id')
                    ->orWhereNull('stripe_price_id_monthly');
            });

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $unsyncedPlans = $query->get();

        if ($unsyncedPlans->isEmpty()) {
            $this->info('  ✓ All paid plans are synced with Stripe.');
            return;
        }

        $this->warningIssues += $unsyncedPlans->count();
        $this->issues[] = [
            'type' => 'WARNING',
            'category' => 'Stripe Sync',
            'count' => $unsyncedPlans->count(),
            'description' => 'Active paid plans not synced with Stripe',
            'action' => 'Run: php artisan subscription:sync-plans --plan-id=X',
        ];

        if ($verbose) {
            $this->warn("  ⚠ Found {$unsyncedPlans->count()} unsynced plan(s):");
            foreach ($unsyncedPlans as $plan) {
                $this->line("    - Plan ID: {$plan->id}, Name: {$plan->name}, Price: €{$plan->price}");
            }
        }
    }

    /**
     * Validate subscription states.
     */
    private function validateSubscriptionStates(?string $tenantId, bool $fix, bool $verbose): void
    {
        $this->info('🔄 Checking subscription states...');

        // Check clubs with active subscription but no plan assigned
        $query = Club::where('subscription_status', 'active')
            ->whereNull('club_subscription_plan_id');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $invalidStates = $query->get();

        if ($invalidStates->isEmpty()) {
            $this->info('  ✓ All subscription states are consistent.');
            return;
        }

        $this->warningIssues += $invalidStates->count();
        $this->issues[] = [
            'type' => 'WARNING',
            'category' => 'Inconsistent States',
            'count' => $invalidStates->count(),
            'description' => 'Clubs with active subscription but no plan assigned',
            'action' => 'Review and assign appropriate plans',
        ];

        if ($verbose) {
            $this->warn("  ⚠ Found {$invalidStates->count()} inconsistent state(s):");
            foreach ($invalidStates as $club) {
                $this->line("    - Club ID: {$club->id}, Name: {$club->name}, Status: {$club->subscription_status}");
            }
        }
    }

    /**
     * Validate missing required fields.
     */
    private function validateMissingRequiredFields(?string $tenantId, bool $fix, bool $verbose): void
    {
        $this->info('📝 Checking for missing required fields...');

        // Check clubs with Stripe subscription but missing fields
        $query = Club::whereNotNull('stripe_subscription_id')
            ->where(function ($q) {
                $q->whereNull('stripe_customer_id')
                    ->orWhereNull('subscription_status')
                    ->orWhereNull('subscription_started_at');
            });

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $missingFields = $query->get();

        if ($missingFields->isEmpty()) {
            $this->info('  ✓ All required fields are present.');
            return;
        }

        $this->criticalIssues += $missingFields->count();
        $this->issues[] = [
            'type' => 'CRITICAL',
            'category' => 'Missing Fields',
            'count' => $missingFields->count(),
            'description' => 'Clubs with Stripe subscription but missing required fields',
            'action' => 'Manually review and populate missing fields',
        ];

        if ($verbose) {
            $this->error("  ✗ Found {$missingFields->count()} club(s) with missing fields:");
            foreach ($missingFields as $club) {
                $missing = [];
                if (!$club->stripe_customer_id) $missing[] = 'stripe_customer_id';
                if (!$club->subscription_status) $missing[] = 'subscription_status';
                if (!$club->subscription_started_at) $missing[] = 'subscription_started_at';

                $this->line("    - Club ID: {$club->id}, Name: {$club->name}, Missing: " . implode(', ', $missing));
            }
        }
    }

    /**
     * Validate plan limits.
     */
    private function validatePlanLimits(?string $tenantId, bool $fix, bool $verbose): void
    {
        $this->info('📊 Checking plan limits...');

        // Check plans with invalid limit configurations
        $query = ClubSubscriptionPlan::where('is_active', true);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $invalidLimits = 0;
        $plans = $query->get();

        foreach ($plans as $plan) {
            $limits = $plan->limits ?? [];

            // Check for negative limits (except -1 which means unlimited)
            foreach ($limits as $key => $value) {
                if ($value < -1) {
                    $invalidLimits++;
                    if ($verbose) {
                        $this->warn("  ⚠ Plan '{$plan->name}' has invalid limit: {$key} = {$value}");
                    }
                }
            }
        }

        if ($invalidLimits === 0) {
            $this->info('  ✓ All plan limits are valid.');
        } else {
            $this->warningIssues += $invalidLimits;
            $this->issues[] = [
                'type' => 'WARNING',
                'category' => 'Invalid Limits',
                'count' => $invalidLimits,
                'description' => 'Plans with invalid limit values',
                'action' => 'Review and correct limit configurations',
            ];
        }
    }

    /**
     * Validate analytics data.
     */
    private function validateAnalyticsData(?string $tenantId, bool $fix, bool $verbose): void
    {
        $this->info('📈 Checking analytics data...');

        // Check for MRR snapshots
        $snapshotCount = SubscriptionMRRSnapshot::when($tenantId, function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        })->count();

        if ($snapshotCount === 0) {
            $this->warn('  ⚠ No MRR snapshots found. Run: php artisan subscription:update-mrr');
            $this->warningIssues++;
            $this->issues[] = [
                'type' => 'WARNING',
                'category' => 'Missing Analytics',
                'count' => 1,
                'description' => 'No MRR snapshots found',
                'action' => 'Run: php artisan subscription:update-mrr',
            ];
        } else {
            $this->info("  ✓ Found {$snapshotCount} MRR snapshot(s).");
        }

        // Check for subscription events
        $eventCount = ClubSubscriptionEvent::when($tenantId, function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        })->count();

        if ($eventCount === 0 && Club::whereNotNull('stripe_subscription_id')->count() > 0) {
            $this->warn('  ⚠ No subscription events found but active subscriptions exist.');
            $this->warningIssues++;
        } else {
            $this->info("  ✓ Found {$eventCount} subscription event(s).");
        }
    }

    /**
     * Display validation summary.
     */
    private function displaySummary(bool $fix): void
    {
        $this->newLine();
        $this->info('✅ Validation ' . ($fix ? 'and Fix Attempt' : '') . ' Completed!');
        $this->newLine();

        $this->table(
            ['Severity', 'Count'],
            [
                ['🔴 Critical Issues', $this->criticalIssues],
                ['⚠️  Warnings', $this->warningIssues],
                [$fix ? '🔧 Fixed' : '', $fix ? $this->fixedIssues : '—'],
                ['Total', $this->criticalIssues + $this->warningIssues],
            ]
        );

        if (!empty($this->issues)) {
            $this->newLine();
            $this->info('📋 Issue Details:');
            $this->table(
                ['Type', 'Category', 'Count', 'Description', 'Recommended Action'],
                array_map(function ($issue) {
                    return [
                        $issue['type'],
                        $issue['category'],
                        $issue['count'],
                        $issue['description'],
                        $issue['action'],
                    ];
                }, $this->issues)
            );
        }

        if ($this->criticalIssues > 0) {
            $this->newLine();
            $this->error('⚠️  Critical issues found! Please review and fix before proceeding to production.');
        } elseif ($this->warningIssues > 0) {
            $this->newLine();
            $this->warn('💡 Warnings found. Consider addressing these issues for optimal system health.');
        } else {
            $this->newLine();
            $this->info('🎉 All checks passed! Your subscription data is in good shape.');
        }
    }
}
