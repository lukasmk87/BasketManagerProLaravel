<?php

namespace App\Services\Statistics;

use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Models\GameAction;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * StatisticsCacheManager
 *
 * Verantwortung: Cache-Strategie, Key-Building und selektive Invalidierung.
 * Unterstützt dynamische TTLs basierend auf Game-Status und ist kompatibel
 * mit dem Database Cache Driver.
 */
class StatisticsCacheManager
{
    private string $cachePrefix = 'basketball:stats:';
    private int $defaultCacheTtl = 3600; // 1 hour (fallback)

    // PERF-007: Dynamic TTLs based on data type
    private int $liveCacheTtl = 300;       // 5 min for live games
    private int $finishedCacheTtl = 3600;  // 1 hour for finished games
    private int $seasonCacheTtl = 86400;   // 24 hours for season stats

    // Cache key patterns for explicit invalidation (Database cache driver compatible)
    private array $cacheKeyPatterns = [
        'player_game' => 'basketball:stats:player:{player_id}:game:{game_id}',
        'player_season' => 'basketball:stats:player:{player_id}:season:{season}',
        'team_game' => 'basketball:stats:team:{team_id}:game:{game_id}',
        'team_season' => 'basketball:stats:team:{team_id}:season:{season}',
        'shot_chart' => 'basketball:stats:shotchart:player:{player_id}:game:{game_id}',
    ];

    /**
     * Get dynamic cache TTL based on game status.
     * Live games use shorter TTL, finished games use longer TTL.
     */
    public function getCacheTtlForGame(Game $game): int
    {
        return match ($game->status) {
            'live', 'in_progress', 'active' => $this->liveCacheTtl,
            'finished', 'completed', 'final' => $this->finishedCacheTtl,
            default => $this->defaultCacheTtl,
        };
    }

    /**
     * Get cache TTL for season stats.
     */
    public function getSeasonCacheTtl(): int
    {
        return $this->seasonCacheTtl;
    }

    /**
     * Build cache key from pattern and parameters.
     * Uses explicit key building for Database cache driver compatibility.
     */
    public function buildCacheKey(string $patternName, array $params): string
    {
        $pattern = $this->cacheKeyPatterns[$patternName] ?? $patternName;

        foreach ($params as $key => $value) {
            $pattern = str_replace("{{$key}}", (string) $value, $pattern);
        }

        return $pattern;
    }

    /**
     * Get the current season (helper for cache invalidation).
     */
    public function getCurrentSeason(): string
    {
        $now = Carbon::now();
        $year = $now->month >= 8 ? $now->year : $now->year - 1;

        return $year . '-' . substr((string) ($year + 1), -2);
    }

    /**
     * Invalidate player statistics cache for a specific game.
     */
    public function clearPlayerCache(Player $player, ?Game $game = null): void
    {
        // Clear player-game stats if game is provided
        if ($game) {
            Cache::forget($this->buildCacheKey('player_game', [
                'player_id' => $player->id,
                'game_id' => $game->id,
            ]));

            Cache::forget($this->buildCacheKey('shot_chart', [
                'player_id' => $player->id,
                'game_id' => $game->id,
            ]));
        }

        // Clear player season stats for current season
        $currentSeason = $this->getCurrentSeason();
        Cache::forget($this->buildCacheKey('player_season', [
            'player_id' => $player->id,
            'season' => $currentSeason,
        ]));
    }

    /**
     * Invalidate team statistics cache for a specific game.
     */
    public function clearTeamCache(Team $team, ?Game $game = null): void
    {
        // Clear team-game stats if game is provided
        if ($game) {
            Cache::forget($this->buildCacheKey('team_game', [
                'team_id' => $team->id,
                'game_id' => $game->id,
            ]));
        }

        // Clear team season stats for current season
        $currentSeason = $this->getCurrentSeason();
        Cache::forget($this->buildCacheKey('team_season', [
            'team_id' => $team->id,
            'season' => $currentSeason,
        ]));
    }

    /**
     * Invalidate all statistics cache for a specific game.
     * Clears player stats, team stats, and shot charts for all participants.
     */
    public function clearGameCache(Game $game): void
    {
        // Clear team caches
        if ($game->homeTeam) {
            $this->clearTeamCache($game->homeTeam, $game);
        }
        if ($game->awayTeam) {
            $this->clearTeamCache($game->awayTeam, $game);
        }

        // Clear player caches for all players with game actions
        $playerIds = GameAction::where('game_id', $game->id)
            ->distinct()
            ->pluck('player_id');

        foreach ($playerIds as $playerId) {
            $player = Player::find($playerId);
            if ($player) {
                $this->clearPlayerCache($player, $game);
            }
        }
    }
}
