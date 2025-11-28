<?php

namespace App\Jobs;

use App\Models\Game;
use App\Services\Statistics\StatisticsService;
use App\Events\StatisticsUpdated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateGameStatistics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Game $game
    ) {
        // Set queue priority for real-time updates
        $this->onQueue('high');
    }

    /**
     * Execute the job.
     */
    public function handle(StatisticsService $statisticsService): void
    {
        Log::info('Updating game statistics', [
            'game_id' => $this->game->id,
            'job_id' => $this->job?->getJobId(),
        ]);

        try {
            // Recalculate all player statistics for this game
            $this->updatePlayerStatistics($statisticsService);
            
            // Update team statistics
            $this->updateTeamStatistics($statisticsService);
            
            // Invalidate relevant caches
            $this->invalidateCaches($statisticsService);
            
            // Broadcast statistics update if game is live
            if ($this->game->is_live) {
                broadcast(new StatisticsUpdated($this->game));
            }

            Log::info('Game statistics updated successfully', [
                'game_id' => $this->game->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update game statistics', [
                'game_id' => $this->game->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Update player statistics for all players in the game.
     */
    private function updatePlayerStatistics(StatisticsService $statisticsService): void
    {
        // Get all players who have actions in this game
        $playerIds = $this->game->gameActions()
            ->distinct('player_id')
            ->pluck('player_id');

        foreach ($playerIds as $playerId) {
            $player = \App\Models\Player::find($playerId);
            if ($player) {
                // Invalidate player's game and season statistics
                $statisticsService->invalidatePlayerStats($player);
                
                // Optionally pre-calculate current game stats for caching
                $statisticsService->getPlayerGameStats($player, $this->game);
            }
        }
    }

    /**
     * Update team statistics.
     */
    private function updateTeamStatistics(StatisticsService $statisticsService): void
    {
        // Update home team statistics
        $statisticsService->invalidateTeamStats($this->game->homeTeam);
        $statisticsService->getTeamGameStats($this->game->homeTeam, $this->game);
        
        // Update away team statistics  
        $statisticsService->invalidateTeamStats($this->game->awayTeam);
        $statisticsService->getTeamGameStats($this->game->awayTeam, $this->game);
    }

    /**
     * Invalidate relevant caches.
     */
    private function invalidateCaches(StatisticsService $statisticsService): void
    {
        // Invalidate game-specific statistics cache
        $statisticsService->invalidateGameStats($this->game);
        
        // Invalidate season statistics if game is finished
        if ($this->game->status === 'finished') {
            $season = $this->game->season;
            
            // This would trigger recalculation of season statistics
            // for both teams when next accessed
            $statisticsService->invalidateTeamStats($this->game->homeTeam);
            $statisticsService->invalidateTeamStats($this->game->awayTeam);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('UpdateGameStatistics job failed permanently', [
            'game_id' => $this->game->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Optionally notify administrators about the failure
        // \App\Notifications\JobFailedNotification::dispatch('UpdateGameStatistics', $this->game->id, $exception);
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [30, 60, 120]; // Wait 30s, 1min, 2min between retries
    }

    /**
     * Determine if the job should be retried based on the exception.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(10); // Stop retrying after 10 minutes
    }
}