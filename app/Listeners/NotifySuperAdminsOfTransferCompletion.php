<?php

namespace App\Listeners;

use App\Events\ClubTransferCompleted;
use App\Mail\ClubTransferCompletedMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class NotifySuperAdminsOfTransferCompletion implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(ClubTransferCompleted $event): void
    {
        $transfer = $event->transfer;

        // Get all super admins
        $superAdmins = User::role('super_admin')->get();

        foreach ($superAdmins as $admin) {
            Mail::to($admin->email)->queue(
                new ClubTransferCompletedMail($transfer, $admin)
            );
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
