<?php

namespace App\Observers;

use App\Models\Player;

class PlayerObserver
{
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

        // Update team player count
        if ($player->team) {
            $player->team->updatePlayerCount();
        }
    }

    /**
     * Handle the Player "updated" event.
     */
    public function updated(Player $player): void
    {
        // If team changed, update player counts for both teams
        if ($player->isDirty('team_id')) {
            // Update old team
            if ($player->getOriginal('team_id')) {
                $oldTeam = \App\Models\Team::find($player->getOriginal('team_id'));
                $oldTeam?->updatePlayerCount();
            }
            
            // Update new team
            if ($player->team) {
                $player->team->updatePlayerCount();
            }
        }

        // Auto-assign jersey number if not provided
        if (empty($player->jersey_number) && $player->team) {
            $player->update([
                'jersey_number' => $this->getNextAvailableJerseyNumber($player->team)
            ]);
        }
    }

    /**
     * Handle the Player "deleted" event.
     */
    public function deleted(Player $player): void
    {
        // Update team player count
        if ($player->team) {
            $player->team->updatePlayerCount();
        }

        activity()
            ->performedOn($player)
            ->causedBy(auth()->user())
            ->log('Player deleted');
    }

    /**
     * Handle the Player "restored" event.
     */
    public function restored(Player $player): void
    {
        // Update team player count
        if ($player->team) {
            $player->team->updatePlayerCount();
        }

        activity()
            ->performedOn($player)
            ->causedBy(auth()->user())
            ->log('Player restored');
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
     */
    private function getNextAvailableJerseyNumber(\App\Models\Team $team): int
    {
        $usedNumbers = $team->players()->pluck('jersey_number')->toArray();
        
        for ($i = 1; $i <= 99; $i++) {
            if (!in_array($i, $usedNumbers)) {
                return $i;
            }
        }
        
        return 0; // Fallback
    }
}