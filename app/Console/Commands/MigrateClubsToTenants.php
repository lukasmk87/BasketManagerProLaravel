<?php

namespace App\Console\Commands;

use App\Models\Club;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateClubsToTenants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clubs:migrate-to-tenants
                            {--tenant= : Specific tenant ID to assign all clubs to}
                            {--create-tenant : Create a new default tenant if none exists}
                            {--dry-run : Run without making any changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing clubs to be associated with tenants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $tenantId = $this->option('tenant');
        $createTenant = $this->option('create-tenant');

        $this->info('🔍 Checking clubs without tenant assignment...');

        // Get clubs without tenant_id
        $clubsWithoutTenant = Club::whereNull('tenant_id')->count();

        if ($clubsWithoutTenant === 0) {
            $this->info('✅ All clubs are already assigned to tenants!');
            return Command::SUCCESS;
        }

        $this->warn("Found {$clubsWithoutTenant} clubs without tenant assignment.");

        // Determine which tenant to use
        $tenant = null;

        if ($tenantId) {
            // Use specific tenant ID
            $tenant = Tenant::find($tenantId);

            if (!$tenant) {
                $this->error("❌ Tenant with ID {$tenantId} not found!");
                return Command::FAILURE;
            }

            $this->info("Using tenant: {$tenant->name} (ID: {$tenant->id})");
        } elseif ($createTenant) {
            // Create a new default tenant
            if ($isDryRun) {
                $this->info('[DRY RUN] Would create a new default tenant');
                $tenant = new Tenant(['id' => 'dry-run-tenant', 'name' => 'Default Tenant']);
            } else {
                $tenant = Tenant::create([
                    'name' => 'Default Organization',
                    'slug' => 'default-organization',
                    'subdomain' => 'default',
                    'subscription_tier' => 'professional',
                    'is_active' => true,
                    'max_users' => 1000,
                    'max_teams' => 100,
                    'max_storage_gb' => 100,
                    'max_api_calls_per_hour' => 5000,
                    'trial_ends_at' => now()->addDays(30),
                ]);

                $this->info("✅ Created new tenant: {$tenant->name} (ID: {$tenant->id})");
            }
        } else {
            // Try to use first available tenant
            $tenant = Tenant::where('is_active', true)->first();

            if (!$tenant) {
                $this->error('❌ No active tenants found!');
                $this->info('💡 Use --create-tenant flag to create a new tenant, or --tenant=<id> to specify one.');
                return Command::FAILURE;
            }

            $this->info("Using first active tenant: {$tenant->name} (ID: {$tenant->id})");
        }

        // Confirm action
        if (!$isDryRun) {
            $confirmed = $this->confirm(
                "Are you sure you want to assign {$clubsWithoutTenant} clubs to tenant '{$tenant->name}'?",
                true
            );

            if (!$confirmed) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        // Perform migration
        DB::beginTransaction();

        try {
            $clubs = Club::whereNull('tenant_id')->get();
            $migrated = 0;

            $progressBar = $this->output->createProgressBar($clubs->count());
            $progressBar->start();

            foreach ($clubs as $club) {
                if ($isDryRun) {
                    $this->newLine();
                    $this->line("[DRY RUN] Would assign club '{$club->name}' (ID: {$club->id}) to tenant '{$tenant->name}'");
                } else {
                    $club->update(['tenant_id' => $tenant->id]);
                    $migrated++;
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            if (!$isDryRun) {
                DB::commit();
                $this->info("✅ Successfully migrated {$migrated} clubs to tenant '{$tenant->name}'");
            } else {
                DB::rollBack();
                $this->info("[DRY RUN] Would have migrated {$clubs->count()} clubs");
            }

            // Summary
            $this->newLine();
            $this->info('📊 Migration Summary:');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Clubs Migrated', $isDryRun ? $clubs->count() . ' (dry run)' : $migrated],
                    ['Target Tenant', $tenant->name],
                    ['Tenant ID', $tenant->id],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Migration failed: {$e->getMessage()}");
            $this->error("Stack trace: {$e->getTraceAsString()}");
            return Command::FAILURE;
        }
    }
}
