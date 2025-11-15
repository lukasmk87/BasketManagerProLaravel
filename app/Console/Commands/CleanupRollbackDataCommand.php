<?php

namespace App\Console\Commands;

use App\Models\ClubTransfer;
use App\Models\ClubTransferRollbackData;
use Illuminate\Console\Command;

class CleanupRollbackDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'club-transfer:cleanup-rollback-data
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup expired rollback data for club transfers (older than 24 hours)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Cleaning up expired club transfer rollback data...');

        // Find all transfers with expired rollback data
        $expiredTransfers = ClubTransfer::query()
            ->where('rollback_expires_at', '<', now())
            ->where('can_rollback', true)
            ->get();

        if ($expiredTransfers->isEmpty()) {
            $this->info('No expired rollback data found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$expiredTransfers->count()} transfers with expired rollback data.");

        $totalDeleted = 0;

        foreach ($expiredTransfers as $transfer) {
            $rollbackDataCount = ClubTransferRollbackData::where('club_transfer_id', $transfer->id)->count();

            if ($this->option('dry-run')) {
                $this->line("Would delete {$rollbackDataCount} rollback records for transfer {$transfer->id}");
                continue;
            }

            // Delete rollback data
            $deleted = ClubTransferRollbackData::where('club_transfer_id', $transfer->id)->delete();

            // Mark transfer as non-rollbackable
            $transfer->update(['can_rollback' => false]);

            $totalDeleted += $deleted;

            $this->line("Deleted {$deleted} rollback records for transfer {$transfer->id} (Club: {$transfer->club->name})");
        }

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN - No data was actually deleted');
        } else {
            $this->info("Successfully deleted {$totalDeleted} rollback records.");
        }

        return Command::SUCCESS;
    }
}
