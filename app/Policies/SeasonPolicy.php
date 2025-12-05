<?php

namespace App\Policies;

use App\Models\Season;
use App\Models\User;
use App\Policies\Concerns\AuthorizesUsers;

class SeasonPolicy
{
    use AuthorizesUsers;

    /**
     * Determine whether the user can view any seasons.
     */
    public function viewAny(User $user): bool
    {
        // Super admins and admins can view all seasons
        if ($user->hasAnyRole(['super_admin', 'tenant_admin'])) {
            return true;
        }

        // Club admins, trainers, and assistant coaches can view seasons
        if ($user->hasAnyRole(['club_admin', 'trainer', 'assistant_coach'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the season.
     */
    public function view(User $user, Season $season): bool
    {
        // Super admins and admins can view all seasons
        if ($user->hasAnyRole(['super_admin', 'tenant_admin'])) {
            return true;
        }

        // Club admins can view seasons of clubs they administer
        if ($user->hasRole('club_admin')) {
            $administeredClubIds = $user->getAdministeredClubIds();
            return in_array($season->club_id, $administeredClubIds);
        }

        // Trainers and assistant coaches can view seasons of their club
        if ($user->hasAnyRole(['trainer', 'assistant_coach'])) {
            // Get clubs through team coaching relationships
            $coachClubIds = $user->coachedTeams()
                ->with('club')
                ->get()
                ->pluck('club.id')
                ->filter()
                ->unique()
                ->toArray();
            return in_array($season->club_id, $coachClubIds);
        }

        return false;
    }

    /**
     * Determine whether the user can create seasons.
     */
    public function create(User $user): bool
    {
        // Super admins and admins can create seasons
        if ($user->hasAnyRole(['super_admin', 'tenant_admin'])) {
            return true;
        }

        // Club admins can create seasons for their clubs
        if ($user->hasRole('club_admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the season.
     */
    public function update(User $user, Season $season): bool
    {
        // Super admins and admins can update any season
        if ($user->hasAnyRole(['super_admin', 'tenant_admin'])) {
            return true;
        }

        // Club admins can only update seasons of clubs they administer
        if ($user->hasRole('club_admin')) {
            $administeredClubIds = $user->getAdministeredClubIds();
            return in_array($season->club_id, $administeredClubIds);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the season.
     */
    public function delete(User $user, Season $season): bool
    {
        // Cannot delete active seasons
        if ($season->isActive()) {
            return false;
        }

        // Super admins and admins can delete seasons
        if ($user->hasAnyRole(['super_admin', 'tenant_admin'])) {
            return true;
        }

        // Club admins can delete seasons of clubs they administer (if not active)
        if ($user->hasRole('club_admin')) {
            $administeredClubIds = $user->getAdministeredClubIds();
            return in_array($season->club_id, $administeredClubIds);
        }

        return false;
    }

    /**
     * Determine whether the user can complete the season.
     */
    public function complete(User $user, Season $season): bool
    {
        // Only active seasons can be completed
        if (!$season->isActive()) {
            return false;
        }

        // Super admins and admins can complete any season
        if ($user->hasAnyRole(['super_admin', 'tenant_admin'])) {
            return true;
        }

        // Club admins can complete seasons of clubs they administer
        if ($user->hasRole('club_admin')) {
            $administeredClubIds = $user->getAdministeredClubIds();
            return in_array($season->club_id, $administeredClubIds);
        }

        return false;
    }

    /**
     * Determine whether the user can activate the season.
     */
    public function activate(User $user, Season $season): bool
    {
        // Only draft seasons can be activated
        if (!$season->isDraft()) {
            return false;
        }

        // Super admins and admins can activate any season
        if ($user->hasAnyRole(['super_admin', 'tenant_admin'])) {
            return true;
        }

        // Club admins can activate seasons of clubs they administer
        if ($user->hasRole('club_admin')) {
            $administeredClubIds = $user->getAdministeredClubIds();
            return in_array($season->club_id, $administeredClubIds);
        }

        return false;
    }

    /**
     * Determine whether the user can start a new season (wizard).
     */
    public function startNew(User $user): bool
    {
        // Super admins and admins can start new seasons
        if ($user->hasAnyRole(['super_admin', 'tenant_admin'])) {
            return true;
        }

        // Club admins can start new seasons for their clubs
        if ($user->hasRole('club_admin')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can export season statistics.
     */
    public function exportStatistics(User $user, Season $season): bool
    {
        // Super admins and admins can export any season's statistics
        if ($user->hasAnyRole(['super_admin', 'tenant_admin'])) {
            return true;
        }

        // Club admins can export statistics of clubs they administer
        if ($user->hasRole('club_admin')) {
            $administeredClubIds = $user->getAdministeredClubIds();
            return in_array($season->club_id, $administeredClubIds);
        }

        // Trainers and assistant coaches can export statistics of their club
        if ($user->hasAnyRole(['trainer', 'assistant_coach'])) {
            $coachClubIds = $user->coachedTeams()
                ->with('club')
                ->get()
                ->pluck('club.id')
                ->filter()
                ->unique()
                ->toArray();
            return in_array($season->club_id, $coachClubIds);
        }

        return false;
    }

    /**
     * Determine whether the user can compare seasons.
     */
    public function compareSeasons(User $user): bool
    {
        // Super admins and admins can compare any seasons
        if ($user->hasAnyRole(['super_admin', 'tenant_admin'])) {
            return true;
        }

        // Club admins, trainers, and assistant coaches can compare seasons
        if ($user->hasAnyRole(['club_admin', 'trainer', 'assistant_coach'])) {
            return true;
        }

        return false;
    }
}
