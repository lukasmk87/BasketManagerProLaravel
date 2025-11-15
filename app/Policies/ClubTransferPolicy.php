<?php

namespace App\Policies;

use App\Models\ClubTransfer;
use App\Models\User;

class ClubTransferPolicy
{
    /**
     * Determine if the user can view any transfers.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine if the user can view the transfer.
     */
    public function view(User $user, ClubTransfer $transfer): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine if the user can create transfers.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine if the user can rollback the transfer.
     */
    public function rollback(User $user, ClubTransfer $transfer): bool
    {
        return $user->hasRole('super_admin') && $transfer->canBeRolledBack();
    }

    /**
     * Determine if the user can delete the transfer.
     */
    public function delete(User $user, ClubTransfer $transfer): bool
    {
        // Only allow deletion of old transfers (> 30 days) by super admins
        return $user->hasRole('super_admin')
            && $transfer->created_at->lessThan(now()->subDays(30));
    }
}
