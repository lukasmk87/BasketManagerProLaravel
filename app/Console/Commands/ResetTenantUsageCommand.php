<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantUsage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class ResetTenantUsageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:reset-usage 
                            {--tenant= : Specific tenant ID to reset} 
                            {--metric= : Specific metric to reset} 
                            {--dry-run : Show what would be reset without actually doing it}
                            {--monthly : Reset monthly usage counters}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset tenant usage counters (typically run monthly)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting tenant usage reset...');

        $tenantId = $this->option('tenant');
        $metric = $this->option('metric');
        $dryRun = $this->option('dry-run');
        $monthly = $this->option('monthly');

        if ($tenantId) {
            $tenants = Tenant::where('id', $tenantId)->get();
            if ($tenants->isEmpty()) {
                $this->error("Tenant with ID {$tenantId} not found.");
                return self::FAILURE;
            }
        } else {
            $tenants = Tenant::where('is_active', true)->get();
        }

        $resetCount = 0;
        $cacheResetCount = 0;

        foreach ($tenants as $tenant) {
            $this->info("Processing tenant: {$tenant->name} ({$tenant->id})");

            if ($monthly) {
                $resetCount += $this->resetMonthlyUsage($tenant, $metric, $dryRun);
            } else {
                $resetCount += $this->resetCurrentUsage($tenant, $metric, $dryRun);
            }

            $cacheResetCount += $this->resetCachedUsage($tenant, $metric, $dryRun);
        }

        if ($dryRun) {
            $this->warn("DRY RUN: Would have reset {$resetCount} usage records and {$cacheResetCount} cache entries.");
        } else {
            $this->info("Successfully reset {$resetCount} usage records and {$cacheResetCount} cache entries.");
        }

        return self::SUCCESS;
    }

    /**
     * Reset monthly usage (archives old data and creates new period).
     */
    private function resetMonthlyUsage(Tenant $tenant, ?string $metric, bool $dryRun): int
    {
        $query = TenantUsage::where('tenant_id', $tenant->id)
            ->where('period_start', '>=', now()->startOfMonth())
            ->whereNull('period_end');

        if ($metric) {
            $query->where('metric', $metric);
        }

        $usageRecords = $query->get();

        if ($dryRun) {
            $this->line("  Would archive {$usageRecords->count()} current period records");
            return $usageRecords->count();
        }

        // Archive current period by setting end date
        foreach ($usageRecords as $record) {
            $record->update([
                'period_end' => now()->endOfMonth(),
            ]);
        }

        $this->line("  Archived {$usageRecords->count()} usage records for {$tenant->name}");
        return $usageRecords->count();
    }

    /**
     * Reset current usage (deletes current period data).
     */
    private function resetCurrentUsage(Tenant $tenant, ?string $metric, bool $dryRun): int
    {
        $query = TenantUsage::where('tenant_id', $tenant->id)
            ->where('period_start', '>=', now()->startOfMonth());

        if ($metric) {
            $query->where('metric', $metric);
        }

        if ($dryRun) {
            $count = $query->count();
            $this->line("  Would delete {$count} current period records");
            return $count;
        }

        $deleted = $query->delete();
        $this->line("  Deleted {$deleted} usage records for {$tenant->name}");
        return $deleted;
    }

    /**
     * Reset cached usage data in Redis.
     */
    private function resetCachedUsage(Tenant $tenant, ?string $metric, bool $dryRun): int
    {
        $pattern = "tenant_usage:{$tenant->id}:*:" . now()->format('Y-m');
        
        if ($metric) {
            $pattern = "tenant_usage:{$tenant->id}:{$metric}:" . now()->format('Y-m');
        }

        try {
            // Get all matching keys
            $keys = Redis::keys($pattern);
            
            if (empty($keys)) {
                return 0;
            }

            if ($dryRun) {
                $this->line("  Would delete " . count($keys) . " cache entries");
                return count($keys);
            }

            // Delete the keys
            Redis::del($keys);
            $this->line("  Deleted " . count($keys) . " cache entries for {$tenant->name}");
            return count($keys);

        } catch (\Exception $e) {
            $this->warn("  Could not reset cache for {$tenant->name}: " . $e->getMessage());
            return 0;
        }
    }
}
