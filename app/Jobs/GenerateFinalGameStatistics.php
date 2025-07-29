<?php

namespace App\Jobs;

use App\Models\Game;
use App\Services\StatisticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class GenerateFinalGameStatistics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Game $game
    ) {
        // Use default queue for final statistics generation
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(StatisticsService $statisticsService): void
    {
        Log::info('Generating final game statistics', [
            'game_id' => $this->game->id,
            'job_id' => $this->job?->getJobId(),
        ]);

        if ($this->game->status !== 'finished') {
            Log::warning('Attempted to generate final statistics for unfinished game', [
                'game_id' => $this->game->id,
                'status' => $this->game->status,
            ]);
            return;
        }

        try {
            DB::transaction(function () use ($statisticsService) {
                // Generate comprehensive final statistics
                $this->generateFinalTeamStatistics($statisticsService);
                $this->generateFinalPlayerStatistics($statisticsService);
                $this->updateGameSummary($statisticsService);
                
                // Mark statistics as verified
                $this->game->update([
                    'stats_verified' => true,
                    'stats_verified_at' => now(),
                    'stats_verified_by' => auth()->id() ?? 1, // System user
                ]);
            });

            Log::info('Final game statistics generated successfully', [
                'game_id' => $this->game->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate final game statistics', [
                'game_id' => $this->game->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate final team statistics.
     */
    private function generateFinalTeamStatistics(StatisticsService $statisticsService): void
    {
        $homeStats = $statisticsService->getTeamGameStats($this->game->homeTeam, $this->game);
        $awayStats = $statisticsService->getTeamGameStats($this->game->awayTeam, $this->game);
        
        $teamStats = [
            'home' => $homeStats,
            'away' => $awayStats,
            'game_totals' => [
                'total_points' => $homeStats['final_score'] + $awayStats['final_score'],
                'total_rebounds' => $homeStats['total_rebounds'] + $awayStats['total_rebounds'],
                'total_assists' => $homeStats['assists'] + $awayStats['assists'],
                'total_fouls' => $homeStats['personal_fouls'] + $awayStats['personal_fouls'],
                'total_turnovers' => $homeStats['turnovers'] + $awayStats['turnovers'],
            ],
            'generated_at' => now()->toISOString(),
        ];

        $this->game->update(['team_stats' => $teamStats]);
    }

    /**
     * Generate final player statistics.
     */
    private function generateFinalPlayerStatistics(StatisticsService $statisticsService): void
    {
        $playerStats = [];
        
        // Get all players who participated in the game
        $playerIds = $this->game->gameActions()
            ->distinct('player_id')
            ->pluck('player_id');

        foreach ($playerIds as $playerId) {
            $player = \App\Models\Player::find($playerId);
            if ($player) {
                $stats = $statisticsService->getPlayerGameStats($player, $this->game);
                
                $playerStats[$playerId] = array_merge($stats, [
                    'player_name' => $player->full_name,
                    'team_id' => $player->team_id,
                    'jersey_number' => $player->jersey_number,
                    'position' => $player->position,
                ]);
            }
        }

        // Sort by team and then by points
        uasort($playerStats, function ($a, $b) {
            if ($a['team_id'] === $b['team_id']) {
                return $b['total_points'] <=> $a['total_points'];
            }
            return $a['team_id'] <=> $b['team_id'];
        });

        $this->game->update(['player_stats' => $playerStats]);
    }

    /**
     * Update game summary with final details.
     */
    private function updateGameSummary(StatisticsService $statisticsService): void
    {
        // Calculate game highlights
        $highlights = $this->calculateGameHighlights();
        
        // Update game with final summary data
        $summary = array_merge($this->game->getSummary(), [
            'highlights' => $highlights,
            'final_statistics' => [
                'duration_minutes' => $this->game->duration,
                'total_actions' => $this->game->gameActions()->count(),
                'total_score' => $this->game->total_score,
                'lead_changes' => $this->calculateLeadChanges(),
                'largest_lead' => $this->calculateLargestLead(),
            ],
            'generated_at' => now()->toISOString(),
        ]);

        // Store summary for quick access
        $this->game->update(['game_summary' => $summary]);
    }

    /**
     * Calculate game highlights.
     */
    private function calculateGameHighlights(): array
    {
        $actions = $this->game->gameActions()
            ->with(['player'])
            ->get();

        $highlights = [
            'top_scorer' => null,
            'most_rebounds' => null,
            'most_assists' => null,
            'key_moments' => [],
        ];

        // Calculate top performers
        $playerStats = [];
        foreach ($actions as $action) {
            $playerId = $action->player_id;
            if (!isset($playerStats[$playerId])) {
                $playerStats[$playerId] = [
                    'player' => $action->player,
                    'points' => 0,
                    'rebounds' => 0,
                    'assists' => 0,
                ];
            }

            $playerStats[$playerId]['points'] += $action->points;
            
            if (in_array($action->action_type, ['rebound_offensive', 'rebound_defensive'])) {
                $playerStats[$playerId]['rebounds']++;
            }
            
            if ($action->action_type === 'assist') {
                $playerStats[$playerId]['assists']++;
            }
        }

        // Find top performers
        if (!empty($playerStats)) {
            $highlights['top_scorer'] = collect($playerStats)
                ->sortByDesc('points')
                ->first();
                
            $highlights['most_rebounds'] = collect($playerStats)
                ->sortByDesc('rebounds')
                ->first();
                
            $highlights['most_assists'] = collect($playerStats)
                ->sortByDesc('assists')
                ->first();
        }

        return $highlights;
    }

    /**
     * Calculate lead changes during the game.
     */
    private function calculateLeadChanges(): int
    {
        $homeScore = 0;
        $awayScore = 0;
        $leadChanges = 0;
        $lastLeader = null;

        $scoringActions = $this->game->gameActions()
            ->whereIn('action_type', ['field_goal_made', 'three_point_made', 'free_throw_made'])
            ->orderBy('created_at')
            ->get();

        foreach ($scoringActions as $action) {
            if ($action->team_id === $this->game->home_team_id) {
                $homeScore += $action->points;
            } else {
                $awayScore += $action->points;
            }

            $currentLeader = $homeScore > $awayScore ? 'home' : ($awayScore > $homeScore ? 'away' : 'tied');
            
            if ($lastLeader && $lastLeader !== $currentLeader && $currentLeader !== 'tied') {
                $leadChanges++;
            }
            
            $lastLeader = $currentLeader;
        }

        return $leadChanges;
    }

    /**
     * Calculate the largest lead in the game.
     */
    private function calculateLargestLead(): array
    {
        $homeScore = 0;
        $awayScore = 0;
        $largestLead = ['team' => null, 'points' => 0, 'when' => null];

        $scoringActions = $this->game->gameActions()
            ->whereIn('action_type', ['field_goal_made', 'three_point_made', 'free_throw_made'])
            ->orderBy('created_at')
            ->get();

        foreach ($scoringActions as $action) {
            if ($action->team_id === $this->game->home_team_id) {
                $homeScore += $action->points;
            } else {
                $awayScore += $action->points;
            }

            $currentLead = abs($homeScore - $awayScore);
            
            if ($currentLead > $largestLead['points']) {
                $largestLead = [
                    'team' => $homeScore > $awayScore ? 'home' : 'away',
                    'points' => $currentLead,
                    'when' => $action->display_time,
                ];
            }
        }

        return $largestLead;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateFinalGameStatistics job failed permanently', [
            'game_id' => $this->game->id,
            'error' => $exception->getMessage(),
        ]);
    }
}