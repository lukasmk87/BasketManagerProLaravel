<?php

namespace App\Policies;

use App\Models\ClubInvitation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ClubInvitationPolicy
{
    /**
     * Determine whether the user can view any models.
     * Club admins can view invitations for their clubs.
     */
    public function viewAny(User $user): bool
    {
        // Super Admin, Admin, and Club Admin can view invitations
        return $user->hasAnyRole(['super_admin', 'admin', 'club_admin']);
    }

    /**
     * Determine whether the user can view the model.
     * User must be admin of the invitation's club.
     */
    public function view(User $user, ClubInvitation $clubInvitation): bool
    {
        // Super Admin and Admin can view all
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Club Admin can view if they administer the invitation's club
        $adminClubIds = $user->getAdministeredClubIds();
        return in_array($clubInvitation->club_id, $adminClubIds);
    }

    /**
     * Determine whether the user can create models.
     * Club admins can create invitations for their clubs.
     */
    public function create(User $user): bool
    {
        // Super Admin, Admin, and Club Admin can create invitations
        return $user->hasAnyRole(['super_admin', 'admin', 'club_admin']);
    }

    /**
     * Determine whether the user can update the model.
     * Currently not allowed - invitations should be deactivated and new ones created.
     */
    public function update(User $user, ClubInvitation $clubInvitation): bool
    {
        // Invitations are immutable - deactivate and create new instead
        return false;
    }

    /**
     * Determine whether the user can delete (deactivate) the model.
     * User must be admin of the invitation's club.
     */
    public function delete(User $user, ClubInvitation $clubInvitation): bool
    {
        // Super Admin and Admin can delete all
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Club Admin can delete if they administer the invitation's club
        $adminClubIds = $user->getAdministeredClubIds();
        return in_array($clubInvitation->club_id, $adminClubIds);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ClubInvitation $clubInvitation): bool
    {
        // Same as delete - can restore if can delete
        return $this->delete($user, $clubInvitation);
    }

    /**
     * Determine whether the user can permanently delete the model.
     * Only Super Admin can force delete.
     */
    public function forceDelete(User $user, ClubInvitation $clubInvitation): bool
    {
        // Only Super Admin can permanently delete
        return $user->hasRole('super_admin');
    }
}
