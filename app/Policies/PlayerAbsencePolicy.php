<?php

namespace App\Policies;

use App\Models\Player;
use App\Models\PlayerAbsence;
use App\Models\User;
use App\Policies\Concerns\AuthorizesUsers;

/**
 * PlayerAbsencePolicy
 *
 * Handles authorization for player absence actions.
 * Players can manage their own absences, trainers can view their team's
 * player absences, and club admins can view all absences for their club.
 */
class PlayerAbsencePolicy
{
    use AuthorizesUsers;

    /**
     * Determine whether the user can view any player absences.
     */
    public function viewAny(User $user): bool
    {
        // Check general permission
        if ($user->can('view player absences')) {
            return true;
        }

        // Club admins, trainers, and players can view absences
        return $user->hasAnyRole(['club_admin', 'trainer', 'assistant_coach', 'player', 'tenant_admin']);
    }

    /**
     * Determine whether the user can view the player absence.
     */
    public function view(User $user, PlayerAbsence $absence): bool
    {
        // Check general permission
        if ($user->can('view player absences')) {
            return true;
        }

        // Player can view their own absence
        if ($this->isOwnAbsence($user, $absence)) {
            return true;
        }

        // Trainer can view absences for their team's players
        if ($this->isTrainerForPlayer($user, $absence->player)) {
            return true;
        }

        // Club admin can view absences for their club's players
        if ($this->isClubAdminForPlayer($user, $absence->player)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create player absences.
     */
    public function create(User $user): bool
    {
        // Check general permission
        if ($user->can('manage player absences')) {
            return true;
        }

        // Trainers and club admins can create absences for their players
        if ($user->hasAnyRole(['club_admin', 'trainer', 'tenant_admin'])) {
            return true;
        }

        // Players can create their own absences
        if ($user->hasRole('player') && $user->playerProfile) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create an absence for a specific player.
     */
    public function createFor(User $user, Player $player): bool
    {
        // Check general permission
        if ($user->can('manage player absences')) {
            return true;
        }

        // Player can create absence for themselves
        if ($user->playerProfile && $user->playerProfile->id === $player->id) {
            return true;
        }

        // Trainer can create absences for their team's players
        if ($this->isTrainerForPlayer($user, $player)) {
            return true;
        }

        // Club admin can create absences for their club's players
        if ($this->isClubAdminForPlayer($user, $player)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the player absence.
     */
    public function update(User $user, PlayerAbsence $absence): bool
    {
        // Check general permission
        if ($user->can('manage player absences')) {
            return true;
        }

        // Player can update their own absence
        if ($this->isOwnAbsence($user, $absence)) {
            return true;
        }

        // Trainer can update absences for their team's players
        if ($this->isTrainerForPlayer($user, $absence->player)) {
            return true;
        }

        // Club admin can update absences for their club's players
        if ($this->isClubAdminForPlayer($user, $absence->player)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the player absence.
     */
    public function delete(User $user, PlayerAbsence $absence): bool
    {
        // Check general permission
        if ($user->can('manage player absences')) {
            return true;
        }

        // Player can delete their own absence
        if ($this->isOwnAbsence($user, $absence)) {
            return true;
        }

        // Trainer can delete absences for their team's players
        if ($this->isTrainerForPlayer($user, $absence->player)) {
            return true;
        }

        // Club admin can delete absences for their club's players
        if ($this->isClubAdminForPlayer($user, $absence->player)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the user is the player associated with this absence.
     */
    protected function isOwnAbsence(User $user, PlayerAbsence $absence): bool
    {
        $playerProfile = $user->playerProfile;
        if (! $playerProfile) {
            return false;
        }

        return $absence->player_id === $playerProfile->id;
    }

    /**
     * Check if the user is a trainer/coach for any of the player's teams.
     */
    protected function isTrainerForPlayer(User $user, ?Player $player): bool
    {
        if (! $player || ! $user->hasAnyRole(['trainer', 'assistant_coach'])) {
            return false;
        }

        // Get all team IDs where user is a coach
        $coachTeamIds = $user->coachedTeams()->pluck('id')->toArray();

        if (empty($coachTeamIds)) {
            return false;
        }

        // Check if player belongs to any of those teams
        $playerTeamIds = $player->teams()->pluck('teams.id')->toArray();

        return ! empty(array_intersect($coachTeamIds, $playerTeamIds));
    }

    /**
     * Check if the user is a club admin for the player's club.
     */
    protected function isClubAdminForPlayer(User $user, ?Player $player): bool
    {
        if (! $player || ! $user->hasRole('club_admin')) {
            return false;
        }

        $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();

        if (empty($userClubIds)) {
            return false;
        }

        // Get player's team club IDs
        $playerClubIds = $player->teams()
            ->whereNotNull('club_id')
            ->pluck('club_id')
            ->unique()
            ->toArray();

        return ! empty(array_intersect($userClubIds, $playerClubIds));
    }
}
