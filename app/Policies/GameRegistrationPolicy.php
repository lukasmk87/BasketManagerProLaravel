<?php

namespace App\Policies;

use App\Models\Game;
use App\Models\GameRegistration;
use App\Models\User;
use App\Policies\Concerns\AuthorizesUsers;

/**
 * GameRegistrationPolicy
 *
 * Handles authorization for game registration actions.
 * Players can manage their own registrations, trainers can manage their team's
 * registrations, and club admins can manage all registrations for their club.
 *
 * @see SEC-006 in SECURITY_AND_PERFORMANCE_FIXES.md
 */
class GameRegistrationPolicy
{
    use AuthorizesUsers;

    /**
     * Determine whether the user can view any game registrations.
     */
    public function viewAny(User $user): bool
    {
        // Check general permission
        if ($user->can('view game registrations')) {
            return true;
        }

        // Club admins, trainers, and players can view registrations
        return $user->hasAnyRole(['club_admin', 'trainer', 'player', 'tenant_admin']);
    }

    /**
     * Determine whether the user can view the game registration.
     */
    public function view(User $user, GameRegistration $registration): bool
    {
        // Check general permission
        if ($user->can('view game registrations')) {
            return true;
        }

        // Player can view their own registration
        if ($this->isOwnRegistration($user, $registration)) {
            return true;
        }

        // Trainer can view registrations for their team's games
        if ($this->isTrainerForGame($user, $registration->game)) {
            return true;
        }

        // Club admin can view registrations for their club's games
        if ($this->isClubAdminForGame($user, $registration->game)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create game registrations.
     */
    public function create(User $user): bool
    {
        // Check general permission
        if ($user->can('manage game registrations')) {
            return true;
        }

        // Trainers and club admins can create registrations
        if ($user->hasAnyRole(['club_admin', 'trainer', 'tenant_admin'])) {
            return true;
        }

        // Players can create their own registrations
        if ($user->hasRole('player') && $user->playerProfile) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the game registration.
     */
    public function update(User $user, GameRegistration $registration): bool
    {
        // Check general permission
        if ($user->can('manage game registrations')) {
            return true;
        }

        // Player can update their own registration (availability)
        if ($this->isOwnRegistration($user, $registration)) {
            return true;
        }

        // Trainer can update registrations for their team's games
        if ($this->isTrainerForGame($user, $registration->game)) {
            return true;
        }

        // Club admin can update registrations for their club's games
        if ($this->isClubAdminForGame($user, $registration->game)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the game registration.
     */
    public function delete(User $user, GameRegistration $registration): bool
    {
        // Check general permission
        if ($user->can('manage game registrations')) {
            return true;
        }

        // Trainer can delete registrations for their team's games
        if ($this->isTrainerForGame($user, $registration->game)) {
            return true;
        }

        // Club admin can delete registrations for their club's games
        if ($this->isClubAdminForGame($user, $registration->game)) {
            return true;
        }

        // Players cannot delete registrations (only cancel/update availability)
        return false;
    }

    /**
     * Determine whether the user can confirm the game registration.
     */
    public function confirm(User $user, GameRegistration $registration): bool
    {
        // Only trainers and above can confirm registrations
        if ($user->can('manage game registrations')) {
            return true;
        }

        // Trainer can confirm registrations for their team's games
        if ($this->isTrainerForGame($user, $registration->game)) {
            return true;
        }

        // Club admin can confirm registrations for their club's games
        if ($this->isClubAdminForGame($user, $registration->game)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can manage the roster (add/remove from roster).
     */
    public function manageRoster(User $user, GameRegistration $registration): bool
    {
        return $this->confirm($user, $registration);
    }

    /**
     * Determine whether the user can bulk register players.
     */
    public function bulkRegister(User $user, Game $game): bool
    {
        // Only trainers and above can bulk register
        if ($user->can('manage game registrations')) {
            return true;
        }

        // Trainer can bulk register for their team's games
        if ($this->isTrainerForGame($user, $game)) {
            return true;
        }

        // Club admin can bulk register for their club's games
        if ($this->isClubAdminForGame($user, $game)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the user is the player associated with this registration.
     */
    protected function isOwnRegistration(User $user, GameRegistration $registration): bool
    {
        $playerProfile = $user->playerProfile;
        if (!$playerProfile) {
            return false;
        }

        return $registration->player_id === $playerProfile->id;
    }

    /**
     * Check if the user is a trainer for the game's team.
     */
    protected function isTrainerForGame(User $user, ?Game $game): bool
    {
        if (!$game || !$user->hasRole('trainer')) {
            return false;
        }

        $coachTeamIds = $user->coachedTeams()->pluck('id')->toArray();
        return in_array($game->home_team_id, $coachTeamIds) ||
               in_array($game->away_team_id, $coachTeamIds);
    }

    /**
     * Check if the user is a club admin for the game's club.
     */
    protected function isClubAdminForGame(User $user, ?Game $game): bool
    {
        if (!$game || !$user->hasRole('club_admin')) {
            return false;
        }

        $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
        $gameClubIds = [];

        if ($game->homeTeam) {
            $gameClubIds[] = $game->homeTeam->club_id;
        }
        if ($game->awayTeam) {
            $gameClubIds[] = $game->awayTeam->club_id;
        }

        return !empty(array_intersect($userClubIds, $gameClubIds));
    }
}
