<?php

namespace App\Policies;

use App\Models\TrainingRegistration;
use App\Models\TrainingSession;
use App\Models\User;
use App\Policies\Concerns\AuthorizesUsers;

/**
 * TrainingRegistrationPolicy
 *
 * Handles authorization for training registration actions.
 * Players can manage their own registrations, trainers can manage their team's
 * registrations, and club admins can manage all registrations for their club.
 *
 * @see SEC-006 in SECURITY_AND_PERFORMANCE_FIXES.md
 */
class TrainingRegistrationPolicy
{
    use AuthorizesUsers;

    /**
     * Determine whether the user can view any training registrations.
     */
    public function viewAny(User $user): bool
    {
        // Check general permission
        if ($user->can('view training registrations')) {
            return true;
        }

        // Club admins, trainers, and players can view registrations
        return $user->hasAnyRole(['club_admin', 'trainer', 'player', 'admin']);
    }

    /**
     * Determine whether the user can view the training registration.
     */
    public function view(User $user, TrainingRegistration $registration): bool
    {
        // Check general permission
        if ($user->can('view training registrations')) {
            return true;
        }

        // Player can view their own registration
        if ($this->isOwnRegistration($user, $registration)) {
            return true;
        }

        // Trainer can view registrations for their team's sessions
        if ($this->isTrainerForSession($user, $registration->trainingSession)) {
            return true;
        }

        // Club admin can view registrations for their club's sessions
        if ($this->isClubAdminForSession($user, $registration->trainingSession)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create training registrations.
     */
    public function create(User $user): bool
    {
        // Check general permission
        if ($user->can('manage training registrations')) {
            return true;
        }

        // Trainers and club admins can create registrations
        if ($user->hasAnyRole(['club_admin', 'trainer', 'admin'])) {
            return true;
        }

        // Players can create their own registrations
        if ($user->hasRole('player') && $user->playerProfile) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the training registration.
     */
    public function update(User $user, TrainingRegistration $registration): bool
    {
        // Check general permission
        if ($user->can('manage training registrations')) {
            return true;
        }

        // Player can update their own registration (status, notes)
        if ($this->isOwnRegistration($user, $registration)) {
            return true;
        }

        // Trainer can update registrations for their team's sessions
        if ($this->isTrainerForSession($user, $registration->trainingSession)) {
            return true;
        }

        // Club admin can update registrations for their club's sessions
        if ($this->isClubAdminForSession($user, $registration->trainingSession)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the training registration.
     */
    public function delete(User $user, TrainingRegistration $registration): bool
    {
        // Check general permission
        if ($user->can('manage training registrations')) {
            return true;
        }

        // Trainer can delete registrations for their team's sessions
        if ($this->isTrainerForSession($user, $registration->trainingSession)) {
            return true;
        }

        // Club admin can delete registrations for their club's sessions
        if ($this->isClubAdminForSession($user, $registration->trainingSession)) {
            return true;
        }

        // Players cannot delete registrations (only cancel)
        return false;
    }

    /**
     * Determine whether the user can confirm the training registration.
     */
    public function confirm(User $user, TrainingRegistration $registration): bool
    {
        // Only trainers and above can confirm registrations
        if ($user->can('manage training registrations')) {
            return true;
        }

        // Trainer can confirm registrations for their team's sessions
        if ($this->isTrainerForSession($user, $registration->trainingSession)) {
            return true;
        }

        // Club admin can confirm registrations for their club's sessions
        if ($this->isClubAdminForSession($user, $registration->trainingSession)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can bulk register players.
     */
    public function bulkRegister(User $user, TrainingSession $session): bool
    {
        // Only trainers and above can bulk register
        if ($user->can('manage training registrations')) {
            return true;
        }

        // Trainer can bulk register for their team's sessions
        if ($this->isTrainerForSession($user, $session)) {
            return true;
        }

        // Club admin can bulk register for their club's sessions
        if ($this->isClubAdminForSession($user, $session)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can cancel the training registration.
     */
    public function cancel(User $user, TrainingRegistration $registration): bool
    {
        // Players can cancel their own registrations
        if ($this->isOwnRegistration($user, $registration)) {
            return true;
        }

        // Trainers and above can cancel any registration they can update
        return $this->update($user, $registration);
    }

    /**
     * Check if the user is the player associated with this registration.
     */
    protected function isOwnRegistration(User $user, TrainingRegistration $registration): bool
    {
        $playerProfile = $user->playerProfile;
        if (!$playerProfile) {
            return false;
        }

        return $registration->player_id === $playerProfile->id;
    }

    /**
     * Check if the user is a trainer for the session's team.
     */
    protected function isTrainerForSession(User $user, ?TrainingSession $session): bool
    {
        if (!$session || !$user->hasRole('trainer')) {
            return false;
        }

        $coachTeamIds = $user->coachedTeams()->pluck('id')->toArray();
        return in_array($session->team_id, $coachTeamIds);
    }

    /**
     * Check if the user is a club admin for the session's club.
     */
    protected function isClubAdminForSession(User $user, ?TrainingSession $session): bool
    {
        if (!$session || !$user->hasRole('club_admin')) {
            return false;
        }

        $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();

        // Get the team's club_id
        $team = $session->team;
        if (!$team) {
            return false;
        }

        return in_array($team->club_id, $userClubIds);
    }
}
