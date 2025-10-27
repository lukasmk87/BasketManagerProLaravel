<?php

namespace App\Policies;

use App\Models\Club;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ClubPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view clubs');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Club $club): bool
    {
        // Check general permission
        if ($user->can('view clubs')) {
            return true;
        }

        // Club members can view their own club
        if ($user->clubs()->where('club_id', $club->id)->exists()) {
            return true;
        }

        // Players can view their club through team membership
        if ($user->isPlayer() && $user->playerProfile?->team?->club_id === $club->id) {
            return true;
        }

        // Coaches can view clubs of teams they coach
        if ($user->isCoach()) {
            $coachClubIds = $user->coachedTeams()
                ->with('club')
                ->get()
                ->pluck('club.id')
                ->filter()
                ->unique()
                ->toArray();
            return in_array($club->id, $coachClubIds);
        }

        // Parents can view clubs of their children's teams
        if ($user->isParent()) {
            $childClubIds = $user->children()
                ->with('playerProfile.team.club')
                ->get()
                ->pluck('playerProfile.team.club.id')
                ->filter()
                ->unique()
                ->toArray();
            return in_array($club->id, $childClubIds);
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create clubs');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Club $club): bool
    {
        // Super admins and admins can edit any club
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Club admins can only edit clubs they administer
        if ($user->hasRole('club_admin')) {
            $administeredClubIds = $user->getAdministeredClubIds();
            return in_array($club->id, $administeredClubIds);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Club $club): bool
    {
        // Only users with delete permission can delete clubs
        if (!$user->can('delete clubs')) {
            return false;
        }

        // Club admins cannot delete their own clubs
        if ($user->hasRole('club_admin')) {
            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
            if (in_array($club->id, $userClubIds)) {
                return false;
            }
        }

        return true; // For admins and super_admins
    }

    /**
     * Determine whether the user can manage club settings.
     */
    public function manageSettings(User $user, Club $club): bool
    {
        // Super admins and admins can manage any club settings
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Club admins can only manage settings for clubs they administer
        if ($user->hasRole('club_admin')) {
            $administeredClubIds = $user->getAdministeredClubIds();
            return in_array($club->id, $administeredClubIds);
        }

        return false;
    }

    /**
     * Determine whether the user can manage club members.
     */
    public function manageMembers(User $user, Club $club): bool
    {
        // Super admins and admins can manage any club members
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Club admins can only manage members in clubs they administer
        if ($user->hasRole('club_admin')) {
            $administeredClubIds = $user->getAdministeredClubIds();
            return in_array($club->id, $administeredClubIds);
        }

        return false;
    }

    /**
     * Determine whether the user can view club statistics.
     */
    public function viewStatistics(User $user, Club $club): bool
    {
        // Check general permission
        if ($user->can('view club statistics')) {
            return true;
        }

        // Anyone who can view the club can view basic statistics
        return $this->view($user, $club);
    }

    /**
     * Determine whether the user can invite members to the club.
     */
    public function inviteMembers(User $user, Club $club): bool
    {
        // Must be able to manage members
        return $this->manageMembers($user, $club);
    }

    /**
     * Determine whether the user can remove members from the club.
     */
    public function removeMembers(User $user, Club $club): bool
    {
        // Must be able to manage members
        return $this->manageMembers($user, $club);
    }

    /**
     * Determine whether the user can assign roles within the club.
     */
    public function assignRoles(User $user, Club $club): bool
    {
        // Super admins and admins can assign roles in any club
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Club admins can only assign roles in clubs they administer
        if ($user->hasRole('club_admin') && $user->can('manage user roles')) {
            $administeredClubIds = $user->getAdministeredClubIds();
            return in_array($club->id, $administeredClubIds);
        }

        return false;
    }

    /**
     * Determine whether the user can create teams for the club.
     */
    public function createTeams(User $user, Club $club): bool
    {
        // Super admins and admins can create teams in any club
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Club admins can only create teams in clubs they administer
        if ($user->hasRole('club_admin') && $user->can('create teams')) {
            $administeredClubIds = $user->getAdministeredClubIds();
            return in_array($club->id, $administeredClubIds);
        }

        return false;
    }

    /**
     * Determine whether the user can manage club finances.
     */
    public function manageFinances(User $user, Club $club): bool
    {
        // Super admins and admins can manage any club finances
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Club admins can only manage finances for clubs they administer
        if ($user->hasRole('club_admin') && $user->can('view financial data')) {
            $administeredClubIds = $user->getAdministeredClubIds();
            return in_array($club->id, $administeredClubIds);
        }

        return false;
    }

    /**
     * Determine whether the user can manage club billing (Stripe subscriptions).
     */
    public function manageBilling(User $user, Club $club): bool
    {
        // Super admins and admins can manage billing for any club
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Club admins can only manage billing for clubs they administer
        if ($user->hasRole('club_admin') && $user->can('view financial data')) {
            $administeredClubIds = $user->getAdministeredClubIds();
            return in_array($club->id, $administeredClubIds);
        }

        return false;
    }

    /**
     * Determine whether the user can manage club media library.
     */
    public function manageMedia(User $user, Club $club): bool
    {
        // Super admins and admins can manage any club media
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Club admins can only manage media for clubs they administer
        if ($user->hasRole('club_admin') && $user->can('manage media library')) {
            $administeredClubIds = $user->getAdministeredClubIds();
            return in_array($club->id, $administeredClubIds);
        }

        return false;
    }

    /**
     * Determine whether the user can export club data.
     */
    public function exportData(User $user, Club $club): bool
    {
        // Must have export permission
        if (!$user->can('export statistics')) {
            return false;
        }

        // Must be able to view the club
        return $this->view($user, $club);
    }

    /**
     * Determine whether the user can send announcements for the club.
     */
    public function sendAnnouncements(User $user, Club $club): bool
    {
        // Super admins and admins can send announcements for any club
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Club admins can only send announcements for clubs they administer
        if ($user->hasRole('club_admin') && $user->can('manage announcements')) {
            $administeredClubIds = $user->getAdministeredClubIds();
            return in_array($club->id, $administeredClubIds);
        }

        return false;
    }

    /**
     * Determine whether the user can manage club tournaments.
     */
    public function manageTournaments(User $user, Club $club): bool
    {
        // Super admins and admins can manage tournaments for any club
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Club admins can only manage tournaments for clubs they administer
        if ($user->hasRole('club_admin') && $user->can('create tournaments')) {
            $administeredClubIds = $user->getAdministeredClubIds();
            return in_array($club->id, $administeredClubIds);
        }

        return false;
    }

    /**
     * Determine whether the user can access emergency information for the club.
     */
    public function accessEmergencyInfo(User $user, Club $club): bool
    {
        // Must have emergency access permission
        if (!$user->can('access emergency information')) {
            return false;
        }

        // Club members with emergency roles can access
        if ($user->clubs()->where('club_id', $club->id)->exists()) {
            return true;
        }

        return true; // For admins and super_admins
    }

    /**
     * Determine whether the user can change club ownership.
     */
    public function changeOwnership(User $user, Club $club): bool
    {
        // Only super admins can change club ownership
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can activate/deactivate the club.
     */
    public function changeStatus(User $user, Club $club): bool
    {
        // Only admins and super_admins can change club status
        if (!$user->hasAnyRole(['admin', 'super_admin'])) {
            return false;
        }

        // Must have edit permission
        return $user->can('edit clubs');
    }

    /**
     * Determine whether the user can view club's activity log.
     */
    public function viewActivityLog(User $user, Club $club): bool
    {
        // Must have activity log permission
        if (!$user->can('view activity logs')) {
            return false;
        }

        // Must be able to view the club
        return $this->view($user, $club);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Club $club): bool
    {
        return $user->can('delete clubs');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Club $club): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can manage integrations for the club.
     */
    public function manageIntegrations(User $user, Club $club): bool
    {
        // Super admins and admins can manage integrations for any club
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Club admins can only manage integrations for clubs they administer
        if ($user->hasRole('club_admin') && $user->can('manage integrations')) {
            $administeredClubIds = $user->getAdministeredClubIds();
            return in_array($club->id, $administeredClubIds);
        }

        return false;
    }

    /**
     * Determine whether the user can view club compliance data.
     */
    public function viewComplianceData(User $user, Club $club): bool
    {
        // Super admins and admins can view compliance data for any club
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Club admins can only view compliance data for clubs they administer
        if ($user->hasRole('club_admin') && $user->can('manage consent records')) {
            $administeredClubIds = $user->getAdministeredClubIds();
            return in_array($club->id, $administeredClubIds);
        }

        return false;
    }
}