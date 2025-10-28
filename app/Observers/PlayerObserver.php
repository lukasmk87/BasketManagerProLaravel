<?php

namespace App\Observers;

use App\Models\Player;
use App\Services\ClubUsageTrackingService;

class PlayerObserver
{
    private ClubUsageTrackingService $usageTracker;

    public function __construct(ClubUsageTrackingService $usageTracker)
    {
        $this->usageTracker = $usageTracker;
    }

    /**
     * Handle the Player "created" event.
     */
    public function created(Player $player): void
    {
        // Log player creation
        activity()
            ->performedOn($player)
            ->causedBy(auth()->user())
            ->log('Player created');

        // Update team player counts for all active teams
        foreach ($player->activeTeams as $team) {
            $team->updatePlayerCount();
        }

        // Track usage for all affected clubs
        $this->trackPlayerUsageForClubs($player, 'track');
    }

    /**
     * Handle the Player "updated" event.
     */
    public function updated(Player $player): void
    {
        // Note: Team changes are now handled through the pivot table, 
        // not through direct player updates. Team player counts will be 
        // updated when teams are attached/detached from the player.
        
        // Auto-assign jersey numbers are now handled in the pivot table
        // and should be managed when attaching players to teams.
        
        // Log the update
        activity()
            ->performedOn($player)
            ->causedBy(auth()->user())
            ->log('Player updated');
    }

    /**
     * Handle the Player "deleted" event.
     */
    public function deleted(Player $player): void
    {
        // Update team player counts for all teams the player was on
        foreach ($player->teams as $team) {
            $team->updatePlayerCount();
        }

        activity()
            ->performedOn($player)
            ->causedBy(auth()->user())
            ->log('Player deleted');

        // Untrack usage for all affected clubs
        $this->trackPlayerUsageForClubs($player, 'untrack');
    }

    /**
     * Handle the Player "restored" event.
     */
    public function restored(Player $player): void
    {
        // Update team player counts for all active teams
        foreach ($player->activeTeams as $team) {
            $team->updatePlayerCount();
        }

        activity()
            ->performedOn($player)
            ->causedBy(auth()->user())
            ->log('Player restored');

        // Re-track usage for all affected clubs
        $this->trackPlayerUsageForClubs($player, 'track');
    }

    /**
     * Handle the Player "force deleted" event.
     */
    public function forceDeleted(Player $player): void
    {
        activity()
            ->log('Player permanently deleted: ' . $player->full_name);
    }

    /**
     * Get the next available jersey number for a team.
     * Note: Jersey numbers are now stored in the player_team pivot table.
     */
    private function getNextAvailableJerseyNumber(\App\Models\Team $team): int
    {
        // Get jersey numbers from the pivot table
        $usedNumbers = $team->players()
            ->wherePivot('is_active', true)
            ->pluck('player_team.jersey_number')
            ->filter()
            ->toArray();

        for ($i = 1; $i <= 99; $i++) {
            if (!in_array($i, $usedNumbers)) {
                return $i;
            }
        }

        return 0; // Fallback
    }

    /**
     * Track or untrack player usage for all affected clubs.
     *
     * Players can belong to multiple teams across different clubs.
     * We need to track usage for each unique club the player is associated with.
     *
     * @param Player $player
     * @param string $action 'track' or 'untrack'
     * @return void
     */
    private function trackPlayerUsageForClubs(Player $player, string $action): void
    {
        // Get all unique clubs from the player's teams
        $clubs = $player->teams()
            ->with('club')
            ->get()
            ->pluck('club')
            ->filter() // Remove nulls
            ->unique('id'); // Unique by club ID

        foreach ($clubs as $club) {
            if ($action === 'track') {
                $this->usageTracker->trackResource($club, 'max_players', 1);
            } else {
                $this->usageTracker->untrackResource($club, 'max_players', 1);
            }
        }
    }
}