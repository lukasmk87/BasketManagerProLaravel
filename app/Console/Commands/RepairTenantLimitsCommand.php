<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantLimitsService;
use Illuminate\Console\Command;

/**
 * Repair Tenant Limits Command
 *
 * Updates existing tenants with proper subscription limits and features.
 * Useful for fixing tenants created via the Installation Wizard before the fix.
 *
 * Usage:
 *   php artisan tenant:repair-limits
 *   php artisan tenant:repair-limits --dry-run (preview changes)
 *   php artisan tenant:repair-limits --tenant=1 (specific tenant)
 */
class RepairTenantLimitsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:repair-limits
                            {--dry-run : Preview changes without applying them}
                            {--tenant= : Repair specific tenant by ID}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Repair tenant subscription limits and features based on their tier';

    /**
     * Statistics for the repair operation
     */
    protected int $tenantsChecked = 0;
    protected int $tenantsRepaired = 0;
    protected int $tenantsSkipped = 0;
    protected array $changes = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔧 BasketManager Pro - Tenant Limits Repair Tool');
        $this->newLine();

        // Get tenants to process
        $tenants = $this->getTenants();

        if ($tenants->isEmpty()) {
            $this->warn('⚠️  No tenants found to repair.');
            return self::SUCCESS;
        }

        $this->info('Found ' . $tenants->count() . ' tenant(s) to check.');
        $this->newLine();

        // Show dry-run notice
        if ($this->option('dry-run')) {
            $this->warn('🔍 DRY-RUN MODE: No changes will be applied.');
            $this->newLine();
        } elseif (!$this->option('force')) {
            if (!$this->confirm('Do you want to proceed with repairing tenant limits?', true)) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }
            $this->newLine();
        }

        // Process each tenant
        foreach ($tenants as $tenant) {
            $this->processTenant($tenant);
        }

        // Show summary
        $this->newLine();
        $this->showSummary();

        return self::SUCCESS;
    }

    /**
     * Get tenants to process
     */
    protected function getTenants()
    {
        if ($tenantId = $this->option('tenant')) {
            $tenant = Tenant::find($tenantId);
            if (!$tenant) {
                $this->error('❌ Tenant not found: ' . $tenantId);
                return collect();
            }
            return collect([$tenant]);
        }

        return Tenant::all();
    }

    /**
     * Process a single tenant
     */
    protected function processTenant(Tenant $tenant): void
    {
        $this->tenantsChecked++;

        $this->line('Checking Tenant: ' . $tenant->name . ' (ID: ' . $tenant->id . ')');
        $this->line('  Current Tier: ' . $tenant->subscription_tier);

        // Get expected limits
        $expectedLimits = TenantLimitsService::getLimits($tenant->subscription_tier);

        // Check what needs to be updated
        $needsUpdate = false;
        $updates = [];
        $settingsUpdates = [];

        // Check numeric limits
        foreach (['max_users', 'max_teams', 'max_storage_gb', 'max_api_calls_per_hour'] as $field) {
            $currentValue = $tenant->$field;
            $expectedValue = $expectedLimits[$field];

            if ($currentValue !== $expectedValue) {
                $needsUpdate = true;
                $updates[$field] = $expectedValue;
                $this->line('  ⚠️  ' . $field . ': ' . ($currentValue ?? 'null') . ' → ' . $expectedValue);
            }
        }

        // Check features in settings
        $currentSettings = $tenant->settings ?? [];
        $currentFeatures = $currentSettings['features'] ?? [];
        $expectedFeatures = $expectedLimits['features'];

        if ($currentFeatures !== $expectedFeatures) {
            $needsUpdate = true;
            $settingsUpdates['features'] = $expectedFeatures;
            $addedFeatures = array_diff_key($expectedFeatures, $currentFeatures);
            $removedFeatures = array_diff_key($currentFeatures, $expectedFeatures);

            if (!empty($addedFeatures)) {
                $this->line('  ✅ Adding features: ' . implode(', ', array_keys($addedFeatures)));
            }
            if (!empty($removedFeatures)) {
                $this->line('  ❌ Removing features: ' . implode(', ', array_keys($removedFeatures)));
            }
        }

        // Check missing settings structure
        if (!isset($currentSettings['branding'])) {
            $needsUpdate = true;
            $settingsUpdates['branding'] = [
                'primary_color' => env('TENANT_PRIMARY_COLOR', '#4F46E5'),
                'logo_url' => env('TENANT_LOGO_URL'),
            ];
            $this->line('  ➕ Adding branding settings');
        }

        if (!isset($currentSettings['contact'])) {
            $needsUpdate = true;
            $settingsUpdates['contact'] = [
                'support_email' => env('TENANT_SUPPORT_EMAIL', $tenant->billing_email),
                'phone' => env('TENANT_PHONE'),
            ];
            $this->line('  ➕ Adding contact settings');
        }

        // Check missing critical fields
        if (empty($tenant->billing_email)) {
            $needsUpdate = true;
            $updates['billing_email'] = env('TENANT_SUPPORT_EMAIL', 'admin@' . $tenant->domain);
            $this->line('  ➕ Adding billing_email');
        }

        if (empty($tenant->country_code)) {
            $needsUpdate = true;
            $updates['country_code'] = env('TENANT_COUNTRY', 'DE');
            $this->line('  ➕ Adding country_code');
        }

        if (empty($tenant->locale)) {
            $needsUpdate = true;
            $updates['locale'] = $currentSettings['language'] ?? config('app.locale', 'de');
            $this->line('  ➕ Adding locale');
        }

        if (empty($tenant->currency)) {
            $needsUpdate = true;
            $updates['currency'] = env('TENANT_CURRENCY', 'EUR');
            $this->line('  ➕ Adding currency');
        }

        if (!isset($tenant->is_active)) {
            $needsUpdate = true;
            $updates['is_active'] = true;
            $this->line('  ➕ Setting is_active');
        }

        // Apply updates or skip
        if (!$needsUpdate) {
            $this->line('  ✅ Already correct, skipping');
            $this->tenantsSkipped++;
            $this->newLine();
            return;
        }

        // Apply changes if not dry-run
        if (!$this->option('dry-run')) {
            try {
                // Update numeric fields
                if (!empty($updates)) {
                    $tenant->update($updates);
                }

                // Update settings (merge with existing)
                if (!empty($settingsUpdates)) {
                    $newSettings = array_merge($currentSettings, $settingsUpdates);
                    $tenant->settings = $newSettings;
                    $tenant->save();
                }

                $this->line('  ✅ Repaired successfully');
                $this->tenantsRepaired++;

                // Track changes
                $this->changes[] = [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'updates' => array_merge($updates, ['settings' => $settingsUpdates]),
                ];
            } catch (\Exception $e) {
                $this->error('  ❌ Failed to repair: ' . $e->getMessage());
            }
        } else {
            $this->line('  🔍 Would repair (dry-run)');
            $this->tenantsRepaired++;
        }

        $this->newLine();
    }

    /**
     * Show summary of the operation
     */
    protected function showSummary(): void
    {
        $this->info('📊 Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Tenants Checked', $this->tenantsChecked],
                ['Tenants Repaired', $this->tenantsRepaired],
                ['Tenants Skipped', $this->tenantsSkipped],
            ]
        );

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->warn('🔍 This was a dry-run. No changes were applied.');
            $this->info('Run without --dry-run to apply changes.');
        } elseif ($this->tenantsRepaired > 0) {
            $this->newLine();
            $this->info('✅ Repair completed successfully!');
            $this->info('Updated ' . $this->tenantsRepaired . ' tenant(s).');
        }
    }
}
