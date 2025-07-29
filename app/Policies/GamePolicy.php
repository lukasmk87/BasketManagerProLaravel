<?php

namespace App\Policies;

use App\Models\Game;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class GamePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view games');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Game $game): bool
    {
        // Check general permission
        if ($user->can('view games')) {
            return true;
        }

        // Players can view games their team participates in
        if ($user->isPlayer()) {
            $playerTeamId = $user->playerProfile?->team_id;
            return $playerTeamId && (
                $game->home_team_id === $playerTeamId || 
                $game->away_team_id === $playerTeamId
            );
        }

        // Coaches can view games of teams they coach
        if ($user->isCoach()) {
            $coachTeamIds = $user->coachedTeams()->pluck('id')->toArray();
            return !empty(array_intersect($coachTeamIds, [$game->home_team_id, $game->away_team_id]));
        }

        // Parents can view games of their children's teams
        if ($user->isParent()) {
            $childTeamIds = $user->children()
                ->with('playerProfile')
                ->get()
                ->pluck('playerProfile.team_id')
                ->filter()
                ->toArray();
            return !empty(array_intersect($childTeamIds, [$game->home_team_id, $game->away_team_id]));
        }

        // Club members can view games involving their club's teams
        $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
        if (!empty($userClubIds)) {
            $gameClubIds = [];
            if ($game->homeTeam) {
                $gameClubIds[] = $game->homeTeam->club_id;
            }
            if ($game->awayTeam) {
                $gameClubIds[] = $game->awayTeam->club_id;
            }
            return !empty(array_intersect($userClubIds, $gameClubIds));
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create games');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Game $game): bool
    {
        // Check general permission
        if ($user->can('edit games')) {
            return true;
        }

        // Club admins can edit games involving their club's teams
        if ($user->hasRole('club_admin')) {
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

        // Coaches can edit games of teams they coach
        if ($user->hasRole('trainer')) {
            $coachTeamIds = $user->coachedTeams()->pluck('id')->toArray();
            return !empty(array_intersect($coachTeamIds, [$game->home_team_id, $game->away_team_id]));
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Game $game): bool
    {
        // Cannot delete games that have already started or finished
        if ($game->status !== 'scheduled') {
            return false;
        }

        // Only users with delete permission can delete games
        if (!$user->can('delete games')) {
            return false;
        }

        // Club admins can delete games involving their club's teams
        if ($user->hasRole('club_admin')) {
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

        return true; // For admins and super_admins
    }

    /**
     * Determine whether the user can score/manage live scoring for the game.
     */
    public function score(User $user, Game $game): bool
    {
        // Must have scoring permission
        if (!$user->can('score games')) {
            return false;
        }

        // Game must be live or ready to start
        if (!in_array($game->status, ['scheduled', 'live', 'halftime'])) {
            return false;
        }

        // Coaches can score games of teams they coach
        if ($user->isCoach()) {
            $coachTeamIds = $user->coachedTeams()->pluck('id')->toArray();
            return !empty(array_intersect($coachTeamIds, [$game->home_team_id, $game->away_team_id]));
        }

        // Scorers can score any game
        if ($user->hasRole('scorer')) {
            return true;
        }

        // Referees can score games they officiate
        if ($user->hasRole('referee')) {
            return $game->referees()->where('user_id', $user->id)->exists();
        }

        return true; // For admins and super_admins
    }

    /**
     * Determine whether the user can view live games.
     */
    public function viewLive(User $user, Game $game): bool
    {
        // Check general permission
        if ($user->can('view live games')) {
            return true;
        }

        // Anyone who can view the game can view it live
        return $this->view($user, $game);
    }

    /**
     * Determine whether the user can manage game officials (referees).
     */
    public function manageOfficials(User $user, Game $game): bool
    {
        // Must have official management permission
        if (!$user->can('manage game officials')) {
            return false;
        }

        // Club admins can manage officials for games involving their club's teams
        if ($user->hasRole('club_admin')) {
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

        return true; // For admins and super_admins
    }

    /**
     * Determine whether the user can publish game results.
     */
    public function publishResults(User $user, Game $game): bool
    {
        // Game must be finished
        if ($game->status !== 'finished') {
            return false;
        }

        // Must have publish permission
        if (!$user->can('publish game results')) {
            return false;
        }

        // Coaches can publish results for games they coached
        if ($user->isCoach()) {
            $coachTeamIds = $user->coachedTeams()->pluck('id')->toArray();
            return !empty(array_intersect($coachTeamIds, [$game->home_team_id, $game->away_team_id]));
        }

        // Club admins can publish results for their club's games
        if ($user->hasRole('club_admin')) {
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

        return true; // For admins and super_admins
    }

    /**
     * Determine whether the user can start/stop the game.
     */
    public function controlGame(User $user, Game $game): bool
    {
        // Must have scoring permission to control game flow
        if (!$this->score($user, $game)) {
            return false;
        }

        // Referees have primary control
        if ($user->hasRole('referee') && $game->referees()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Coaches can control in absence of referees
        if ($user->isCoach()) {
            $coachTeamIds = $user->coachedTeams()->pluck('id')->toArray();
            return !empty(array_intersect($coachTeamIds, [$game->home_team_id, $game->away_team_id]));
        }

        return true; // For admins, super_admins, and scorers
    }

    /**
     * Determine whether the user can export game data.
     */
    public function exportData(User $user, Game $game): bool
    {
        // Must have export permission
        if (!$user->can('export statistics')) {
            return false;
        }

        // Must be able to view the game
        return $this->view($user, $game);
    }

    /**
     * Determine whether the user can view game statistics.
     */
    public function viewStatistics(User $user, Game $game): bool
    {
        // Anyone who can view the game can view its statistics
        return $this->view($user, $game);
    }

    /**
     * Determine whether the user can edit game statistics.
     */
    public function editStatistics(User $user, Game $game): bool
    {
        // Must have statistics editing permission
        if (!$user->can('edit player statistics')) {
            return false;
        }

        // Only for finished games or by scorers during live games
        if ($game->status === 'live' && !$this->score($user, $game)) {
            return false;
        }

        // Coaches can edit statistics for their team's games
        if ($user->isCoach()) {
            $coachTeamIds = $user->coachedTeams()->pluck('id')->toArray();
            return !empty(array_intersect($coachTeamIds, [$game->home_team_id, $game->away_team_id]));
        }

        return true; // For admins, super_admins, and scorers
    }

    /**
     * Determine whether the user can reschedule the game.
     */
    public function reschedule(User $user, Game $game): bool
    {
        // Cannot reschedule games that have started
        if (in_array($game->status, ['live', 'halftime', 'finished', 'cancelled'])) {
            return false;
        }

        // Must have edit permission
        return $this->update($user, $game);
    }

    /**
     * Determine whether the user can cancel the game.
     */
    public function cancel(User $user, Game $game): bool
    {
        // Cannot cancel finished games
        if ($game->status === 'finished') {
            return false;
        }

        // Must have edit permission
        if (!$this->update($user, $game)) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can manage game media (photos, videos).
     */
    public function manageMedia(User $user, Game $game): bool
    {
        // Must have media management permission
        if (!$user->can('manage media library')) {
            return false;
        }

        // Must be able to view the game
        return $this->view($user, $game);
    }

    /**
     * Determine whether the user can view the game's activity log.
     */
    public function viewActivityLog(User $user, Game $game): bool
    {
        // Must have activity log permission
        if (!$user->can('view activity logs')) {
            return false;
        }

        // Must be able to view the game
        return $this->view($user, $game);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Game $game): bool
    {
        return $user->can('delete games');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Game $game): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can access game emergency procedures.
     */
    public function accessEmergencyProcedures(User $user, Game $game): bool
    {
        // Must have emergency access permission
        if (!$user->can('access emergency information')) {
            return false;
        }

        // Anyone involved in the game can access emergency procedures
        return $this->view($user, $game);
    }

    /**
     * Determine whether the user can generate QR codes for game access.
     */
    public function generateQRCodes(User $user, Game $game): bool
    {
        // Must have emergency QR code permission
        if (!$user->can('generate emergency qr codes')) {
            return false;
        }

        // Must be able to update the game
        return $this->update($user, $game);
    }
}