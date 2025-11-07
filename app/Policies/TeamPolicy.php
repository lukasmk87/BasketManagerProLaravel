<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use App\Policies\Concerns\AuthorizesUsers;
use Illuminate\Auth\Access\Response;

class TeamPolicy
{
    use AuthorizesUsers;
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view teams');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Team $team): bool
    {
        // Check general permission
        if ($user->can('view teams')) {
            return true;
        }

        // Players can view their own team
        if ($user->isPlayer() && $user->playerProfile?->team_id === $team->id) {
            return true;
        }

        // Coaches can view teams they coach
        if ($user->isCoach()) {
            $coachTeamIds = $user->coachedTeams()->pluck('id')->toArray();
            return in_array($team->id, $coachTeamIds);
        }

        // Parents can view their child's team
        if ($user->isParent()) {
            $childTeamIds = $user->children()
                ->with('playerProfile')
                ->get()
                ->pluck('playerProfile.team_id')
                ->filter()
                ->toArray();
            return in_array($team->id, $childTeamIds);
        }

        // Club members can view teams in their clubs
        if ($user->clubs()->where('club_id', $team->club_id)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create teams');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Team $team): bool
    {
        // Check general permission
        if ($user->can('edit teams')) {
            return true;
        }

        // Club admins can edit teams in their clubs
        if ($user->hasRole('club_admin')) {
            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
            return in_array($team->club_id, $userClubIds);
        }

        // Head coaches can edit their teams
        if ($user->hasRole('trainer')) {
            $coachTeamIds = $user->coachedTeams()->pluck('id')->toArray();
            return in_array($team->id, $coachTeamIds);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Team $team): bool
    {
        // Only users with delete permission can delete teams
        if (!$user->can('delete teams')) {
            return false;
        }

        // Club admins can delete teams in their clubs
        if ($user->hasRole('club_admin')) {
            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
            return in_array($team->club_id, $userClubIds);
        }

        return true;
    }

    /**
     * Determine whether the user can manage team rosters.
     */
    public function manageRoster(User $user, Team $team): bool
    {
        // Check general permission
        if (!$user->can('manage team rosters')) {
            return false;
        }

        // Club admins can manage rosters in their clubs
        if ($user->hasRole('club_admin')) {
            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
            return in_array($team->club_id, $userClubIds);
        }

        // Head coaches can manage their team rosters
        if ($user->hasRole('trainer')) {
            $coachTeamIds = $user->coachedTeams()->pluck('id')->toArray();
            return in_array($team->id, $coachTeamIds);
        }

        // Team managers can manage rosters
        if ($user->hasRole('team_manager')) {
            return $user->managedTeams()->where('id', $team->id)->exists();
        }

        return true; // For admins and super_admins
    }

    /**
     * Determine whether the user can assign coaches to the team.
     */
    public function assignCoaches(User $user, Team $team): bool
    {
        // Check general permission
        if (!$user->can('assign team coaches')) {
            return false;
        }

        // Club admins can assign coaches in their clubs
        if ($user->hasRole('club_admin')) {
            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
            return in_array($team->club_id, $userClubIds);
        }

        return true; // For admins and super_admins
    }

    /**
     * Determine whether the user can view team statistics.
     */
    public function viewStatistics(User $user, Team $team): bool
    {
        // Check general permission
        if ($user->can('view team statistics')) {
            return true;
        }

        // Anyone who can view the team can view basic statistics
        return $this->view($user, $team);
    }

    /**
     * Determine whether the user can manage team settings.
     */
    public function manageSettings(User $user, Team $team): bool
    {
        // Check general permission
        if (!$user->can('manage team settings')) {
            return false;
        }

        // Club admins can manage settings for teams in their clubs
        if ($user->hasRole('club_admin')) {
            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
            return in_array($team->club_id, $userClubIds);
        }

        // Head coaches can manage their team settings
        if ($user->hasRole('trainer')) {
            $coachTeamIds = $user->coachedTeams()->pluck('id')->toArray();
            return in_array($team->id, $coachTeamIds);
        }

        return true; // For admins and super_admins
    }

    /**
     * Determine whether the user can export team data.
     */
    public function exportData(User $user, Team $team): bool
    {
        // Must have export permission
        if (!$user->can('export statistics')) {
            return false;
        }

        // Must be able to view the team
        return $this->view($user, $team);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Team $team): bool
    {
        return $user->can('delete teams');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Team $team): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can view team's activity log.
     */
    public function viewActivityLog(User $user, Team $team): bool
    {
        // Must have activity log permission
        if (!$user->can('view activity logs')) {
            return false;
        }

        // Must be able to view the team
        return $this->view($user, $team);
    }

    /**
     * Determine whether the user can manage team media.
     */
    public function manageMedia(User $user, Team $team): bool
    {
        // Must have media management permission
        if (!$user->can('manage media library')) {
            return false;
        }

        // Must be able to update the team
        return $this->update($user, $team);
    }
}