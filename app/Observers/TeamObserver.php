<?php

namespace App\Observers;

use App\Models\Team;
use App\Services\ClubUsageTrackingService;

class TeamObserver
{
    private ClubUsageTrackingService $usageTracker;

    public function __construct(ClubUsageTrackingService $usageTracker)
    {
        $this->usageTracker = $usageTracker;
    }

    /**
     * Handle the Team "created" event.
     */
    public function created(Team $team): void
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

        // Track usage for club
        if ($team->club) {
            $this->usageTracker->trackResource($team->club, 'max_teams', 1);
        }
    }

    /**
     * Handle the Team "updated" event.
     */
    public function updated(Team $team): void
    {
        // Update player count when team changes
        if ($team->isDirty('max_players')) {
            $team->updatePlayerCount();
        }
    }

    /**
     * Handle the Team "deleted" event.
     */
    public function deleted(Team $team): void
    {
        // Handle team deletion
        activity()
            ->performedOn($team)
            ->causedBy(auth()->user())
            ->log('Team deleted');

        // Untrack usage for club
        if ($team->club) {
            $this->usageTracker->untrackResource($team->club, 'max_teams', 1);
        }
    }

    /**
     * Handle the Team "restored" event.
     */
    public function restored(Team $team): void
    {
        activity()
            ->performedOn($team)
            ->causedBy(auth()->user())
            ->log('Team restored');

        // Re-track usage for club (restore = undelete)
        if ($team->club) {
            $this->usageTracker->trackResource($team->club, 'max_teams', 1);
        }
    }

    /**
     * Handle the Team "force deleted" event.
     */
    public function forceDeleted(Team $team): void
    {
        // Handle permanent deletion
        activity()
            ->log('Team permanently deleted: ' . $team->name);
    }
}