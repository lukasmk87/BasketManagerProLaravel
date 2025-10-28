<?php

namespace App\Observers;

use App\Models\Game;
use App\Services\ClubUsageTrackingService;

class GameObserver
{
    private ClubUsageTrackingService $usageTracker;

    public function __construct(ClubUsageTrackingService $usageTracker)
    {
        $this->usageTracker = $usageTracker;
    }

    /**
     * Handle the Game "created" event.
     */
    public function created(Game $game): void
    {
        // Log game creation
        activity()
            ->performedOn($game)
            ->causedBy(auth()->user())
            ->log('Game created');

        // Initialize live game record if this is a scheduled game
        if ($game->status === 'scheduled') {
            \App\Models\LiveGame::create([
                'game_id' => $game->id,
                'current_quarter' => 1,
                'time_remaining' => '12:00',
                'home_score' => 0,
                'away_score' => 0,
                'is_active' => false,
            ]);
        }

        // Track usage for both clubs (only current month games count toward limit)
        if ($game->game_date && $game->game_date->isSameMonth(now())) {
            $this->trackGameUsageForClubs($game, 'track');
        }
    }

    /**
     * Handle the Game "updated" event.
     */
    public function updated(Game $game): void
    {
        // If game status changed to finished, update team statistics
        if ($game->isDirty('status') && $game->status === 'finished') {
            $this->updateTeamStatistics($game);
        }

        // If score changed, broadcast update
        if ($game->isDirty(['final_score_home', 'final_score_away'])) {
            // This would be handled by events/broadcasting
            broadcast(new \App\Events\GameScoreUpdated($game));
        }
    }

    /**
     * Handle the Game "deleted" event.
     */
    public function deleted(Game $game): void
    {
        // Clean up related records
        $game->gameActions()->delete();
        $game->liveGame()?->delete();

        activity()
            ->performedOn($game)
            ->causedBy(auth()->user())
            ->log('Game deleted');

        // Untrack usage if game was in current month
        if ($game->game_date && $game->game_date->isSameMonth(now())) {
            $this->trackGameUsageForClubs($game, 'untrack');
        }
    }

    /**
     * Handle the Game "restored" event.
     */
    public function restored(Game $game): void
    {
        activity()
            ->performedOn($game)
            ->causedBy(auth()->user())
            ->log('Game restored');

        // Re-track usage if game is in current month
        if ($game->game_date && $game->game_date->isSameMonth(now())) {
            $this->trackGameUsageForClubs($game, 'track');
        }
    }

    /**
     * Handle the Game "force deleted" event.
     */
    public function forceDeleted(Game $game): void
    {
        activity()
            ->log('Game permanently deleted: ' . $game->homeTeam?->name . ' vs ' . $game->awayTeam?->name);
    }

    /**
     * Update team statistics after game completion.
     */
    private function updateTeamStatistics(Game $game): void
    {
        if (!$game->homeTeam || !$game->awayTeam) {
            return;
        }

        // Update home team
        $homeTeam = $game->homeTeam;
        $homeTeam->increment('games_played');
        $homeTeam->increment('points_scored', $game->final_score_home ?? 0);
        $homeTeam->increment('points_allowed', $game->final_score_away ?? 0);

        // Update away team
        $awayTeam = $game->awayTeam;
        $awayTeam->increment('games_played');
        $awayTeam->increment('points_scored', $game->final_score_away ?? 0);
        $awayTeam->increment('points_allowed', $game->final_score_home ?? 0);

        // Update wins/losses
        if ($game->final_score_home > $game->final_score_away) {
            $homeTeam->increment('games_won');
            $awayTeam->increment('games_lost');
        } elseif ($game->final_score_away > $game->final_score_home) {
            $awayTeam->increment('games_won');
            $homeTeam->increment('games_lost');
        } else {
            $homeTeam->increment('games_tied');
            $awayTeam->increment('games_tied');
        }
    }

    /**
     * Track or untrack game usage for all affected clubs.
     *
     * Games involve two teams which may belong to different clubs.
     * We track usage for both clubs (if different).
     *
     * @param Game $game
     * @param string $action 'track' or 'untrack'
     * @return void
     */
    private function trackGameUsageForClubs(Game $game, string $action): void
    {
        $clubs = collect();

        // Add home team's club
        if ($game->homeTeam && $game->homeTeam->club) {
            $clubs->push($game->homeTeam->club);
        }

        // Add away team's club (if different)
        if ($game->awayTeam && $game->awayTeam->club) {
            $clubs->push($game->awayTeam->club);
        }

        // Remove duplicates (same club hosting both teams)
        $clubs = $clubs->unique('id');

        foreach ($clubs as $club) {
            if ($action === 'track') {
                $this->usageTracker->trackResource($club, 'max_games_per_month', 1);
            } else {
                $this->usageTracker->untrackResource($club, 'max_games_per_month', 1);
            }
        }
    }
}