<?php

namespace App\Observers;

use App\Models\BasketballTeam;

class TeamObserver
{
    /**
     * Handle the Team "created" event.
     */
    public function created(BasketballTeam $team): void
    {
        // Log team creation
        activity()
            ->performedOn($team)
            ->causedBy(auth()->user())
            ->log('Team created');

        // Auto-generate team slug if not provided
        if (empty($team->slug)) {
            $team->update(['slug' => \Str::slug($team->name)]);
        }
    }

    /**
     * Handle the Team "updated" event.
     */
    public function updated(BasketballTeam $team): void
    {
        // Update player count when team changes
        if ($team->isDirty('max_players')) {
            $team->updatePlayerCount();
        }
    }

    /**
     * Handle the Team "deleted" event.
     */
    public function deleted(BasketballTeam $team): void
    {
        // Handle team deletion
        activity()
            ->performedOn($team)
            ->causedBy(auth()->user())
            ->log('Team deleted');
    }

    /**
     * Handle the Team "restored" event.
     */
    public function restored(BasketballTeam $team): void
    {
        activity()
            ->performedOn($team)
            ->causedBy(auth()->user())
            ->log('Team restored');
    }

    /**
     * Handle the Team "force deleted" event.
     */
    public function forceDeleted(BasketballTeam $team): void
    {
        // Handle permanent deletion
        activity()
            ->log('Team permanently deleted: ' . $team->name);
    }
}