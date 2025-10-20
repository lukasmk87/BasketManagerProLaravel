<?php

namespace App\Policies;

use App\Models\PlayerRegistrationInvitation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PlayerRegistrationInvitationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Check general permission (create OR manage)
        return $user->can('create player invitations') || $user->can('manage player invitations');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PlayerRegistrationInvitation $invitation): bool
    {
        // Check general permission
        if (!$user->can('create player invitations') && !$user->can('manage player invitations')) {
            return false;
        }

        // Super Admin and Admin can view all invitations
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Club admins can view invitations in their clubs
        if ($user->hasRole('club_admin')) {
            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
            return in_array($invitation->club_id, $userClubIds);
        }

        // Trainers can view invitations for teams they coach
        if ($user->hasRole('trainer')) {
            // Check if the invitation is for a team that the trainer coaches
            if ($invitation->target_team_id) {
                $coachTeamIds = $user->coachedTeams()->pluck('id')->toArray();
                return in_array($invitation->target_team_id, $coachTeamIds);
            }

            // If invitation has no target team, check if trainer belongs to the club
            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
            return in_array($invitation->club_id, $userClubIds);
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Check general permission
        return $user->can('create player invitations');
    }

    /**
     * Determine whether the user can create an invitation for a specific club.
     */
    public function createForClub(User $user, int $clubId): bool
    {
        // Check general permission
        if (!$user->can('create player invitations')) {
            return false;
        }

        // Super Admin and Admin can create for any club
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Club admins and trainers can only create for their clubs
        $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
        return in_array($clubId, $userClubIds);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PlayerRegistrationInvitation $invitation): bool
    {
        // Check general permission
        if (!$user->can('manage player invitations')) {
            return false;
        }

        // Super Admin and Admin can update all invitations
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Club admins can update invitations in their clubs
        if ($user->hasRole('club_admin')) {
            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
            return in_array($invitation->club_id, $userClubIds);
        }

        return false;
    }

    /**
     * Determine whether the user can delete (deactivate) the model.
     */
    public function delete(User $user, PlayerRegistrationInvitation $invitation): bool
    {
        // Check general permission
        if (!$user->can('manage player invitations') && !$user->can('create player invitations')) {
            return false;
        }

        // Super Admin and Admin can delete all invitations
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Club admins can delete invitations in their clubs
        if ($user->hasRole('club_admin')) {
            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
            return in_array($invitation->club_id, $userClubIds);
        }

        // Trainers can delete invitations they created
        if ($user->hasRole('trainer')) {
            // Check if trainer created this invitation
            if ($invitation->created_by_user_id === $user->id) {
                return true;
            }

            // Or if it's for a team they coach
            if ($invitation->target_team_id) {
                $coachTeamIds = $user->coachedTeams()->pluck('id')->toArray();
                return in_array($invitation->target_team_id, $coachTeamIds);
            }
        }

        return false;
    }

    /**
     * Determine whether the user can extend the invitation expiration.
     */
    public function extend(User $user, PlayerRegistrationInvitation $invitation): bool
    {
        // Same as update permission
        return $this->update($user, $invitation);
    }

    /**
     * Determine whether the user can download the QR code.
     */
    public function downloadQR(User $user, PlayerRegistrationInvitation $invitation): bool
    {
        // Anyone who can view the invitation can download the QR code
        return $this->view($user, $invitation);
    }

    /**
     * Determine whether the user can view statistics for the invitation.
     */
    public function viewStatistics(User $user, PlayerRegistrationInvitation $invitation): bool
    {
        // Anyone who can view the invitation can view its statistics
        return $this->view($user, $invitation);
    }

    /**
     * Determine whether the user can view registered players for this invitation.
     */
    public function viewRegisteredPlayers(User $user, PlayerRegistrationInvitation $invitation): bool
    {
        // Check general permission
        if (!$user->can('create player invitations') && !$user->can('manage player invitations')) {
            return false;
        }

        // Must be able to view the invitation
        return $this->view($user, $invitation);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PlayerRegistrationInvitation $invitation): bool
    {
        // Only users with manage permission can restore
        if (!$user->can('manage player invitations')) {
            return false;
        }

        // Super Admin and Admin can restore all
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Club admins can restore invitations in their clubs
        if ($user->hasRole('club_admin')) {
            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
            return in_array($invitation->club_id, $userClubIds);
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PlayerRegistrationInvitation $invitation): bool
    {
        // Only super admins can permanently delete
        return $user->hasRole('super_admin');
    }
}
