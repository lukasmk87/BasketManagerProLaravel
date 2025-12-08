<?php

namespace App\Policies;

use App\Models\Player;
use App\Models\User;
use App\Policies\Concerns\AuthorizesUsers;

class PlayerPolicy
{
    use AuthorizesUsers;

    /**
     * Check if any of the player's teams are in the given team IDs.
     */
    private function playerBelongsToTeams(Player $player, array $teamIds): bool
    {
        if (empty($teamIds)) {
            return false;
        }

        return $player->teams()->whereIn('basketball_teams.id', $teamIds)->exists();
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view players');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Player $player): bool
    {
        // Check general permission
        if ($user->can('view players')) {
            return true;
        }

        // Players can view their own profile
        if ($user->isPlayer() && $user->playerProfile?->id === $player->id) {
            return true;
        }

        // Players can view teammates
        if ($user->isPlayer() && $user->playerProfile) {
            $userTeamIds = $user->playerProfile->teams()->pluck('basketball_teams.id')->toArray();
            if ($this->playerBelongsToTeams($player, $userTeamIds)) {
                return true;
            }
        }

        // Coaches can view players in their teams
        if ($user->isCoach()) {
            $coachTeamIds = $user->allCoachedTeams()->pluck('id')->toArray();

            return $this->playerBelongsToTeams($player, $coachTeamIds);
        }

        // Parents can view their children
        if ($user->isParent()) {
            $childPlayerIds = $user->children()
                ->with('playerProfile')
                ->get()
                ->pluck('playerProfile.id')
                ->filter()
                ->toArray();

            return in_array($player->id, $childPlayerIds);
        }

        // Club admins can view all players in their clubs
        if ($user->hasRole('club_admin')) {
            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
            if ($player->team && in_array($player->team->club_id, $userClubIds)) {
                return true;
            }
        }

        // Club members can view players in their clubs
        if ($player->team && $user->clubs()->where('club_id', $player->team->club_id)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create players');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Player $player): bool
    {
        // Players can update their own basic profile
        if ($user->isPlayer() && $user->playerProfile?->id === $player->id) {
            return true;
        }

        // Check general permission
        if ($user->can('edit players')) {
            return true;
        }

        // Club admins can edit players in their clubs
        if ($user->hasRole('club_admin') && $player->team) {
            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();

            return in_array($player->team->club_id, $userClubIds);
        }

        // Coaches can edit players in their teams
        if ($user->hasRole('trainer')) {
            $coachTeamIds = $user->allCoachedTeams()->pluck('id')->toArray();

            return $this->playerBelongsToTeams($player, $coachTeamIds);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Player $player): bool
    {
        // Players cannot delete themselves
        if ($user->isPlayer() && $user->playerProfile?->id === $player->id) {
            return false;
        }

        // Only users with delete permission can delete players
        if (! $user->can('delete players')) {
            return false;
        }

        // Club admins can delete players in their clubs
        if ($user->hasRole('club_admin') && $player->team) {
            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();

            return in_array($player->team->club_id, $userClubIds);
        }

        return true;
    }

    /**
     * Determine whether the user can view player statistics.
     */
    public function viewStatistics(User $user, Player $player): bool
    {
        // Check general permission
        if ($user->can('view player statistics')) {
            return true;
        }

        // Anyone who can view the player can view basic statistics
        return $this->view($user, $player);
    }

    /**
     * Determine whether the user can edit player statistics.
     */
    public function editStatistics(User $user, Player $player): bool
    {
        // Check general permission
        if (! $user->can('edit player statistics')) {
            return false;
        }

        // Coaches can edit statistics for their players
        if ($user->isCoach()) {
            $coachTeamIds = $user->allCoachedTeams()->pluck('id')->toArray();

            return $this->playerBelongsToTeams($player, $coachTeamIds);
        }

        // Scorers can edit statistics during games
        if ($user->hasRole('scorer')) {
            return true;
        }

        return true; // For admins and club_admins
    }

    /**
     * Determine whether the user can manage player contracts.
     */
    public function manageContracts(User $user, Player $player): bool
    {
        // Check general permission
        if (! $user->can('manage player contracts')) {
            return false;
        }

        // Club admins can manage contracts for players in their clubs
        if ($user->hasRole('club_admin') && $player->team) {
            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();

            return in_array($player->team->club_id, $userClubIds);
        }

        return true; // For admins and super_admins
    }

    /**
     * Determine whether the user can view player medical information.
     */
    public function viewMedicalInfo(User $user, Player $player): bool
    {
        // Players can view their own medical info
        if ($user->isPlayer() && $user->playerProfile?->id === $player->id) {
            return true;
        }

        // Must have permission to view player medical info
        if (! $user->can('view player medical info')) {
            return false;
        }

        // Coaches can view medical info for their players
        if ($user->isCoach()) {
            $coachTeamIds = $user->allCoachedTeams()->pluck('id')->toArray();

            return $this->playerBelongsToTeams($player, $coachTeamIds);
        }

        // Parents can view their child's medical info
        if ($user->isParent()) {
            $childPlayerIds = $user->children()
                ->with('playerProfile')
                ->get()
                ->pluck('playerProfile.id')
                ->filter()
                ->toArray();

            return in_array($player->id, $childPlayerIds);
        }

        return true; // For admins and club_admins
    }

    /**
     * Determine whether the user can edit player medical information.
     */
    public function editMedicalInfo(User $user, Player $player): bool
    {
        // Players can edit their own medical info
        if ($user->isPlayer() && $user->playerProfile?->id === $player->id) {
            return true;
        }

        // Must have permission to edit player medical info
        if (! $user->can('edit player medical info')) {
            return false;
        }

        // Coaches can edit medical info for their players
        if ($user->isCoach()) {
            $coachTeamIds = $user->allCoachedTeams()->pluck('id')->toArray();

            return $this->playerBelongsToTeams($player, $coachTeamIds);
        }

        // Parents can edit their child's medical info
        if ($user->isParent()) {
            $childPlayerIds = $user->children()
                ->with('playerProfile')
                ->get()
                ->pluck('playerProfile.id')
                ->filter()
                ->toArray();

            return in_array($player->id, $childPlayerIds);
        }

        return true; // For admins and club_admins
    }

    /**
     * Determine whether the user can transfer the player to another team.
     */
    public function transfer(User $user, Player $player): bool
    {
        // Players cannot transfer themselves
        if ($user->isPlayer() && $user->playerProfile?->id === $player->id) {
            return false;
        }

        // Must have edit permission
        if (! $user->can('edit players')) {
            return false;
        }

        // Club admins can transfer players within or out of their clubs
        if ($user->hasRole('club_admin') && $player->team) {
            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();

            return in_array($player->team->club_id, $userClubIds);
        }

        return true; // For admins and super_admins
    }

    /**
     * Determine whether the user can assign jersey numbers.
     */
    public function assignJerseyNumber(User $user, Player $player): bool
    {
        // Must be able to edit the player
        if (! $this->update($user, $player)) {
            return false;
        }

        // Team managers can assign jersey numbers
        if ($user->hasRole('team_manager')) {
            $managedTeamIds = $user->managedTeams()->pluck('id')->toArray();

            return $this->playerBelongsToTeams($player, $managedTeamIds);
        }

        return true;
    }

    /**
     * Determine whether the user can activate/deactivate the player.
     */
    public function changeStatus(User $user, Player $player): bool
    {
        // Players cannot change their own status
        if ($user->isPlayer() && $user->playerProfile?->id === $player->id) {
            return false;
        }

        // Must have edit permission
        if (! $user->can('edit players')) {
            return false;
        }

        // Club admins can change status for players in their clubs
        if ($user->hasRole('club_admin') && $player->team) {
            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();

            return in_array($player->team->club_id, $userClubIds);
        }

        return true; // For admins and super_admins
    }

    /**
     * Determine whether the user can export player data.
     */
    public function exportData(User $user, Player $player): bool
    {
        // Players can export their own data (GDPR)
        if ($user->isPlayer() && $user->playerProfile?->id === $player->id) {
            return true;
        }

        // Must have export permission
        if (! $user->can('export statistics')) {
            return false;
        }

        // Must be able to view the player
        return $this->view($user, $player);
    }

    /**
     * Determine whether the user can view player's activity log.
     */
    public function viewActivityLog(User $user, Player $player): bool
    {
        // Players can view their own activity log
        if ($user->isPlayer() && $user->playerProfile?->id === $player->id) {
            return true;
        }

        // Must have activity log permission
        if (! $user->can('view activity logs')) {
            return false;
        }

        // Must be able to view the player
        return $this->view($user, $player);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Player $player): bool
    {
        return $user->can('delete players');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Player $player): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can manage player media (photos, videos).
     */
    public function manageMedia(User $user, Player $player): bool
    {
        // Players can manage their own media
        if ($user->isPlayer() && $user->playerProfile?->id === $player->id) {
            return true;
        }

        // Must have media management permission
        if (! $user->can('manage media library')) {
            return false;
        }

        // Must be able to update the player
        return $this->update($user, $player);
    }

    /**
     * Determine whether the user can view player's emergency contacts.
     */
    public function viewEmergencyContacts(User $user, Player $player): bool
    {
        // Players can view their own emergency contacts
        if ($user->isPlayer() && $user->playerProfile?->id === $player->id) {
            return true;
        }

        // Must have emergency contact permission
        if (! $user->can('view emergency contacts')) {
            return false;
        }

        // Coaches can view emergency contacts for their players
        if ($user->isCoach()) {
            $coachTeamIds = $user->allCoachedTeams()->pluck('id')->toArray();

            return $this->playerBelongsToTeams($player, $coachTeamIds);
        }

        // Parents can view their child's emergency contacts
        if ($user->isParent()) {
            $childPlayerIds = $user->children()
                ->with('playerProfile')
                ->get()
                ->pluck('playerProfile.id')
                ->filter()
                ->toArray();

            return in_array($player->id, $childPlayerIds);
        }

        return true; // For admins and club_admins
    }

    /**
     * Determine whether the user can edit player's emergency contacts.
     */
    public function editEmergencyContacts(User $user, Player $player): bool
    {
        // Players can edit their own emergency contacts
        if ($user->isPlayer() && $user->playerProfile?->id === $player->id) {
            return true;
        }

        // Must have emergency contact edit permission
        if (! $user->can('edit emergency contacts')) {
            return false;
        }

        // Parents can edit their child's emergency contacts
        if ($user->isParent()) {
            $childPlayerIds = $user->children()
                ->with('playerProfile')
                ->get()
                ->pluck('playerProfile.id')
                ->filter()
                ->toArray();

            return in_array($player->id, $childPlayerIds);
        }

        return true; // For admins, club_admins, and coaches
    }

    /**
     * Determine whether the user can view pending players.
     */
    public function viewPending(User $user): bool
    {
        // Check permission
        if (! $user->can('assign pending players')) {
            return false;
        }

        // Only club admins, admins, and super admins can view pending players
        return $user->hasAnyRole(['super_admin', 'tenant_admin', 'club_admin']);
    }

    /**
     * Determine whether the user can assign a pending player to a team.
     */
    public function assignToTeam(User $user, Player $player): bool
    {
        // Check permission
        if (! $user->can('assign pending players')) {
            return false;
        }

        // Player must be pending assignment
        if (! $player->pending_team_assignment) {
            return false;
        }

        // Super Admin and Admin can assign any player
        if ($user->hasAnyRole(['super_admin', 'tenant_admin'])) {
            return true;
        }

        // Club admins can assign players to teams in their clubs
        if ($user->hasRole('club_admin')) {
            // Get the club ID from the player's registration invitation
            $playerClubId = $player->registeredViaInvitation?->club_id;

            if (! $playerClubId) {
                return false;
            }

            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();

            return in_array($playerClubId, $userClubIds);
        }

        return false;
    }
}
