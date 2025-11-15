<?php

namespace App\Console\Commands;

use App\Jobs\ProcessClubTransferJob;
use App\Models\ClubTransfer;
use Illuminate\Console\Command;

class RetryFailedTransfersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'club-transfer:retry-failed
                            {--id= : Specific transfer ID to retry}
                            {--all : Retry all failed transfers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry failed club transfers';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($transferId = $this->option('id')) {
            return $this->retrySingleTransfer($transferId);
        }

        if ($this->option('all')) {
            return $this->retryAllFailedTransfers();
        }

        $this->error('Please specify either --id=<transfer-id> or --all');
        return Command::FAILURE;
    }

    /**
     * Retry a single transfer.
     */
    protected function retrySingleTransfer(string $transferId): int
    {
        $transfer = ClubTransfer::find($transferId);

        if (!$transfer) {
            $this->error("Transfer with ID {$transferId} not found.");
            return Command::FAILURE;
        }

        if (!$transfer->isFailed()) {
            $this->error("Transfer {$transferId} is not in failed state (current status: {$transfer->status}).");
            return Command::FAILURE;
        }

        if (!$this->confirm("Retry transfer for club '{$transfer->club->name}'?")) {
            $this->info('Cancelled.');
            return Command::SUCCESS;
        }

        $this->info("Retrying transfer {$transferId}...");

        // Reset transfer status
        $transfer->markAsPending();

        // Dispatch job
        ProcessClubTransferJob::dispatch($transfer);

        $this->info("Transfer {$transferId} has been queued for retry.");

        return Command::SUCCESS;
    }

    /**
     * Retry all failed transfers.
     */
    protected function retryAllFailedTransfers(): int
    {
        $failedTransfers = ClubTransfer::failed()->get();

        if ($failedTransfers->isEmpty()) {
            $this->info('No failed transfers found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$failedTransfers->count()} failed transfers.");

        if (!$this->confirm('Retry all failed transfers?')) {
            $this->info('Cancelled.');
            return Command::SUCCESS;
        }

        $retriedCount = 0;

        foreach ($failedTransfers as $transfer) {
            $this->line("Retrying transfer {$transfer->id} (Club: {$transfer->club->name})...");

            // Reset transfer status
            $transfer->markAsPending();

            // Dispatch job
            ProcessClubTransferJob::dispatch($transfer);

            $retriedCount++;
        }

        $this->info("Successfully queued {$retriedCount} transfers for retry.");

        return Command::SUCCESS;
    }
}
