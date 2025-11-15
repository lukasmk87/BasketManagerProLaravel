<?php

namespace App\Listeners;

use App\Models\ClubTransferRollbackData;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class CleanupExpiredRollbackData implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * This listener is designed to be triggered by any transfer event
     * and will clean up expired rollback data globally.
     */
    public function handle($event): void
    {
        try {
            // Find all transfers with expired rollback data
            $expiredTransfers = \App\Models\ClubTransfer::query()
                ->where('rollback_expires_at', '<', now())
                ->where('can_rollback', true)
                ->get();

            foreach ($expiredTransfers as $transfer) {
                // Delete rollback data
                $deletedCount = ClubTransferRollbackData::where('club_transfer_id', $transfer->id)
                    ->delete();

                // Mark transfer as non-rollbackable
                $transfer->update(['can_rollback' => false]);

                Log::info('Cleaned up expired rollback data', [
                    'transfer_id' => $transfer->id,
                    'records_deleted' => $deletedCount,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to cleanup expired rollback data', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine whether the listener should be queued.
     */
    public function shouldQueue(): bool
    {
        return true;
    }
}
