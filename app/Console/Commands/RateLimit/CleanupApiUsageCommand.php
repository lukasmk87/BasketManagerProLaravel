<?php

namespace App\Console\Commands\RateLimit;

use App\Models\ApiUsageTracking;
use Illuminate\Console\Command;

class CleanupApiUsageCommand extends Command
{
    protected $signature = 'rate-limit:cleanup
                          {--days=30 : Number of days to keep records}
                          {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Clean up old API usage tracking records to maintain database performance';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info("Cleaning up API usage records older than {$days} days...");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No records will be deleted');
        }

        try {
            if ($dryRun) {
                // Count records that would be deleted
                $count = ApiUsageTracking::where('created_at', '<', now()->subDays($days))->count();
                $this->info("Would delete {$count} records");
            } else {
                // Actually delete the records
                $count = ApiUsageTracking::cleanupOldRecords($days);
                $this->info("Deleted {$count} old API usage records");
            }

            // Show current database statistics
            $this->showDatabaseStats();

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error cleaning up records: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    protected function showDatabaseStats(): void
    {
        $totalRecords = ApiUsageTracking::count();
        $todayRecords = ApiUsageTracking::whereDate('created_at', today())->count();
        $thisWeekRecords = ApiUsageTracking::where('created_at', '>=', now()->subWeek())->count();
        $thisMonthRecords = ApiUsageTracking::where('created_at', '>=', now()->subMonth())->count();

        $this->newLine();
        $this->info('Current API Usage Statistics:');
        $this->table(
            ['Period', 'Records'],
            [
                ['Total', number_format($totalRecords)],
                ['Today', number_format($todayRecords)],
                ['This Week', number_format($thisWeekRecords)],
                ['This Month', number_format($thisMonthRecords)],
            ]
        );
    }
}