<?php

namespace App\Console\Commands;

use App\Models\Club;
use App\Services\ClubUsageTrackingService;
use Illuminate\Console\Command;

/**
 * SEC-008: Artisan command to sync storage usage for all clubs.
 *
 * Usage:
 *   php artisan club:sync-storage           # Sync all clubs
 *   php artisan club:sync-storage --club=5  # Sync specific club
 *   php artisan club:sync-storage -v        # Verbose output
 */
class SyncClubStorageCommand extends Command
{
    protected $signature = 'club:sync-storage
                            {--club= : Specific club ID to sync}
                            {--dry-run : Show what would be synced without making changes}';

    protected $description = 'Sync storage usage calculations for clubs';

    public function handle(ClubUsageTrackingService $usageService): int
    {
        $query = Club::query();

        if ($clubId = $this->option('club')) {
            $query->where('id', $clubId);
        }

        $clubs = $query->get();

        if ($clubs->isEmpty()) {
            $this->warn('No clubs found to sync.');
            return Command::SUCCESS;
        }

        $this->info("Syncing storage for {$clubs->count()} club(s)...");
        $this->newLine();

        $bar = $this->output->createProgressBar($clubs->count());
        $bar->start();

        $totalStorage = 0;

        foreach ($clubs as $club) {
            $calculatedGB = $club->calculateStorageUsage();
            $currentTracked = $usageService->getCurrentUsage($club, 'max_storage_gb');

            if ($this->option('dry-run')) {
                $this->newLine();
                $this->line("  [{$club->id}] {$club->name}:");
                $this->line("      Calculated: {$calculatedGB} GB");
                $this->line("      Currently tracked: {$currentTracked} GB");
                $this->line("      Difference: " . round($calculatedGB - $currentTracked, 3) . " GB");
            } else {
                // Sync the calculated usage
                $usageService->syncClubUsage($club, 'max_storage_gb', $calculatedGB);
            }

            $totalStorage += $calculatedGB;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($this->option('dry-run')) {
            $this->info('[DRY RUN] No changes were made.');
        } else {
            $this->info('Storage sync completed successfully!');
        }

        $this->newLine();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Clubs processed', $clubs->count()],
                ['Total storage', round($totalStorage, 3) . ' GB'],
                ['Average per club', round($totalStorage / $clubs->count(), 3) . ' GB'],
            ]
        );

        return Command::SUCCESS;
    }
}
