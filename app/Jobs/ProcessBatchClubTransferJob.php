<?php

namespace App\Jobs;

use App\Models\ClubTransfer;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class ProcessBatchClubTransferJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 1800; // 30 minutes for batch

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $transferIds
    ) {
        $this->onQueue('high');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        Log::info('Processing batch club transfer', [
            'transfer_count' => count($this->transferIds),
            'transfer_ids' => $this->transferIds,
        ]);

        // Create individual transfer jobs
        $jobs = [];
        foreach ($this->transferIds as $transferId) {
            $transfer = ClubTransfer::find($transferId);

            if ($transfer) {
                $jobs[] = new ProcessClubTransferJob($transfer);
            }
        }

        // Dispatch as a batch
        if (!empty($jobs)) {
            Bus::batch($jobs)
                ->name('Club Batch Transfer - ' . now()->toDateTimeString())
                ->allowFailures()
                ->onQueue('high')
                ->then(function () {
                    Log::info('Batch club transfer completed', [
                        'transfer_ids' => $this->transferIds,
                    ]);
                })
                ->catch(function (\Throwable $e) {
                    Log::error('Batch club transfer failed', [
                        'transfer_ids' => $this->transferIds,
                        'error' => $e->getMessage(),
                    ]);
                })
                ->dispatch();
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Batch club transfer job failed', [
            'transfer_ids' => $this->transferIds,
            'error' => $exception->getMessage(),
        ]);
    }
}
