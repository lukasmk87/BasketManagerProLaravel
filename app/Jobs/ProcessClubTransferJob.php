<?php

namespace App\Jobs;

use App\Models\ClubTransfer;
use App\Services\ClubTransferService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessClubTransferJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ClubTransfer $transfer
    ) {
        // Use high priority queue for transfers
        $this->onQueue('high');
    }

    /**
     * Execute the job.
     */
    public function handle(ClubTransferService $transferService): void
    {
        Log::info('Processing club transfer', [
            'transfer_id' => $this->transfer->id,
            'club_id' => $this->transfer->club_id,
            'source_tenant_id' => $this->transfer->source_tenant_id,
            'target_tenant_id' => $this->transfer->target_tenant_id,
        ]);

        try {
            $transferService->processTransfer($this->transfer);

            Log::info('Club transfer completed successfully', [
                'transfer_id' => $this->transfer->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Club transfer job failed', [
                'transfer_id' => $this->transfer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger failed() method
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Club transfer job permanently failed after retries', [
            'transfer_id' => $this->transfer->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Transfer is already marked as failed in the service
        // Just log the permanent failure
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        // Exponential backoff: 30s, 60s, 120s
        return [30, 60, 120];
    }
}
