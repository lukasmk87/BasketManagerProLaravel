<?php

namespace App\Policies;

use App\Models\Tournament;
use App\Models\TournamentAward;
use App\Models\User;
use App\Policies\Concerns\AuthorizesUsers;

/**
 * TournamentAwardPolicy
 *
 * Handles authorization for tournament award actions.
 * Club admins can manage awards for their tournaments, trainers can view
 * awards for tournaments their team participates in.
 *
 * @see SEC-006 in SECURITY_AND_PERFORMANCE_FIXES.md
 */
class TournamentAwardPolicy
{
    use AuthorizesUsers;

    /**
     * Determine whether the user can view any tournament awards.
     */
    public function viewAny(User $user): bool
    {
        // Check general permission
        if ($user->can('view tournament awards')) {
            return true;
        }

        // Club admins, trainers, and players can view awards
        return $user->hasAnyRole(['club_admin', 'trainer', 'player', 'admin']);
    }

    /**
     * Determine whether the user can view the tournament award.
     */
    public function view(User $user, TournamentAward $award): bool
    {
        // Check general permission
        if ($user->can('view tournament awards')) {
            return true;
        }

        $tournament = $award->tournament;
        if (!$tournament) {
            return false;
        }

        // Club admin can view awards for their club's tournaments
        if ($this->isClubAdminForTournament($user, $tournament)) {
            return true;
        }

        // Trainer can view awards for tournaments their team participates in
        if ($this->isTrainerInTournament($user, $tournament)) {
            return true;
        }

        // Members of the organizing club can view
        if ($this->isClubMemberForTournament($user, $tournament)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create tournament awards.
     */
    public function create(User $user): bool
    {
        // Check general permission
        if ($user->can('manage tournament awards')) {
            return true;
        }

        // Club admins can create awards
        return $user->hasAnyRole(['club_admin', 'admin']);
    }

    /**
     * Determine whether the user can update the tournament award.
     */
    public function update(User $user, TournamentAward $award): bool
    {
        // Check general permission
        if ($user->can('manage tournament awards')) {
            return true;
        }

        $tournament = $award->tournament;
        if (!$tournament) {
            return false;
        }

        // Club admin can update awards for their club's tournaments
        if ($this->isClubAdminForTournament($user, $tournament)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the tournament award.
     */
    public function delete(User $user, TournamentAward $award): bool
    {
        // Check general permission
        if ($user->can('manage tournament awards')) {
            return true;
        }

        $tournament = $award->tournament;
        if (!$tournament) {
            return false;
        }

        // Club admin can delete awards for their club's tournaments
        if ($this->isClubAdminForTournament($user, $tournament)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can present the tournament award.
     */
    public function present(User $user, TournamentAward $award): bool
    {
        // Check general permission
        if ($user->can('manage tournament awards')) {
            return true;
        }

        $tournament = $award->tournament;
        if (!$tournament) {
            return false;
        }

        // Club admin can present awards for their club's tournaments
        if ($this->isClubAdminForTournament($user, $tournament)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can assign the award to a recipient.
     */
    public function assign(User $user, TournamentAward $award): bool
    {
        return $this->update($user, $award);
    }

    /**
     * Determine whether the user can feature the award on the website.
     */
    public function feature(User $user, TournamentAward $award): bool
    {
        return $this->update($user, $award);
    }

    /**
     * Determine whether the user can unfeature the award from the website.
     */
    public function unfeature(User $user, TournamentAward $award): bool
    {
        return $this->update($user, $award);
    }

    /**
     * Determine whether the user can generate automatic awards.
     */
    public function generateAutomatic(User $user, Tournament $tournament): bool
    {
        // Check general permission
        if ($user->can('manage tournament awards')) {
            return true;
        }

        // Club admin can generate awards for their club's tournaments
        if ($this->isClubAdminForTournament($user, $tournament)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the user is a club admin for the tournament's organizing club.
     */
    protected function isClubAdminForTournament(User $user, Tournament $tournament): bool
    {
        if (!$user->hasRole('club_admin')) {
            return false;
        }

        $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
        return in_array($tournament->club_id, $userClubIds);
    }

    /**
     * Check if the user is a trainer for a team in the tournament.
     */
    protected function isTrainerInTournament(User $user, Tournament $tournament): bool
    {
        if (!$user->hasRole('trainer')) {
            return false;
        }

        $coachTeamIds = $user->coachedTeams()->pluck('id')->toArray();
        if (empty($coachTeamIds)) {
            return false;
        }

        // Check if any coached team is registered in the tournament
        return $tournament->teams()
            ->whereIn('basketball_team_id', $coachTeamIds)
            ->exists();
    }

    /**
     * Check if the user is a member of the tournament's organizing club.
     */
    protected function isClubMemberForTournament(User $user, Tournament $tournament): bool
    {
        $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
        return in_array($tournament->club_id, $userClubIds);
    }
}
