<?php

namespace App\Observers;

use App\Models\Game;

class GameObserver
{
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
}