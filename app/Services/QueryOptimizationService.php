<?php

namespace App\Services;

use App\Models\Game;
use App\Models\GameAction;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Query Optimization Service für Basketball Analytics
 * 
 * Bereitstellung optimierter, gecachter Queries für häufige Basketball-Datenabfragen
 */
class QueryOptimizationService
{
    private const CACHE_TTL = 3600; // 1 hour default cache
    private const STATS_CACHE_TTL = 1800; // 30 minutes for stats
    private const LIVE_CACHE_TTL = 30; // 30 seconds for live data

    /**
     * Optimierte Player Statistics Abfrage
     *
     * @param int $playerId
     * @param string|null $season
     * @param bool $useCache
     * @return array
     */
    public function getPlayerSeasonStatistics(int $playerId, ?string $season = null, bool $useCache = true): array
    {
        $cacheKey = "player_stats_{$playerId}_{$season}";

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Optimierte Query mit View
        $stats = DB::select("CALL GetPlayerSeasonStats(?, ?)", [$playerId, $season]);
        
        if (empty($stats)) {
            // Fallback to direct query if stored procedure fails
            $stats = $this->getPlayerSeasonStatsDirect($playerId, $season);
        }

        $result = !empty($stats) ? (array) $stats[0] : [];
        
        if ($useCache) {
            Cache::put($cacheKey, $result, self::STATS_CACHE_TTL);
        }

        return $result;
    }

    /**
     * Optimierte Team Head-to-Head Statistics
     *
     * @param int $team1Id
     * @param int $team2Id
     * @param bool $useCache
     * @return array
     */
    public function getTeamHeadToHeadStats(int $team1Id, int $team2Id, bool $useCache = true): array
    {
        $cacheKey = "h2h_stats_{$team1Id}_{$team2Id}";

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $stats = DB::select("CALL GetTeamHeadToHead(?, ?)", [$team1Id, $team2Id]);
        $result = !empty($stats) ? (array) $stats[0] : [];

        if ($useCache) {
            Cache::put($cacheKey, $result, self::CACHE_TTL);
        }

        return $result;
    }

    /**
     * Optimierte Shot Chart Data Abfrage
     *
     * @param int $playerId
     * @param int|null $teamId
     * @param int|null $gameId
     * @param bool $useCache
     * @return Collection
     */
    public function getShotChartData(int $playerId, ?int $teamId = null, ?int $gameId = null, bool $useCache = true): Collection
    {
        $cacheKey = "shot_chart_{$playerId}_{$teamId}_{$gameId}";

        if ($useCache && Cache::has($cacheKey)) {
            return collect(Cache::get($cacheKey));
        }

        // Optimierte Query mit Indexes
        $query = DB::table('game_actions')
            ->select([
                'shot_x', 'shot_y', 'shot_distance', 'shot_zone',
                'is_successful', 'action_type', 'period', 'game_id',
                'recorded_at'
            ])
            ->where('player_id', $playerId)
            ->whereIn('action_type', ['field_goal_made', 'field_goal_missed', 'three_point_made', 'three_point_missed'])
            ->whereNotNull('shot_x')
            ->whereNotNull('shot_y')
            ->orderBy('recorded_at', 'desc');

        if ($teamId) {
            $query->where('team_id', $teamId);
        }

        if ($gameId) {
            $query->where('game_id', $gameId);
        } else {
            // Limit to recent data if no specific game
            $query->limit(1000);
        }

        $result = $query->get();

        if ($useCache) {
            Cache::put($cacheKey, $result->toArray(), self::STATS_CACHE_TTL);
        }

        return $result;
    }

    /**
     * Optimierte Live Game Statistics
     *
     * @param int $gameId
     * @param bool $useCache
     * @return array
     */
    public function getLiveGameStatistics(int $gameId, bool $useCache = true): array
    {
        $cacheKey = "live_game_stats_{$gameId}";

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Optimierte Query für Live Statistics
        $homeStats = $this->getTeamGameStatistics($gameId, 'home');
        $awayStats = $this->getTeamGameStatistics($gameId, 'away');

        // Recent actions für Live Updates
        $recentActions = DB::table('game_actions')
            ->select(['action_type', 'player_id', 'team_id', 'points', 'period', 'recorded_at'])
            ->where('game_id', $gameId)
            ->orderBy('recorded_at', 'desc')
            ->limit(10)
            ->get();

        $result = [
            'game_id' => $gameId,
            'home_stats' => $homeStats,
            'away_stats' => $awayStats,
            'recent_actions' => $recentActions->toArray(),
            'last_updated' => now()
        ];

        if ($useCache) {
            Cache::put($cacheKey, $result, self::LIVE_CACHE_TTL);
        }

        return $result;
    }

    /**
     * Optimierte Team Performance Trends
     *
     * @param int $teamId
     * @param int $lastNGames
     * @param bool $useCache
     * @return array
     */
    public function getTeamPerformanceTrends(int $teamId, int $lastNGames = 10, bool $useCache = true): array
    {
        $cacheKey = "team_trends_{$teamId}_{$lastNGames}";

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Use materialized view für bessere Performance
        $trends = DB::table('team_game_statistics as tgs')
            ->join('games as g', 'tgs.game_id', '=', 'g.id')
            ->select([
                'g.played_at',
                'tgs.points_scored',
                'tgs.field_goal_percentage',
                'tgs.total_rebounds',
                'tgs.assists',
                'tgs.turnovers',
                'g.home_team_score',
                'g.away_team_score',
                DB::raw('CASE 
                    WHEN (g.home_team_id = ? AND g.home_team_score > g.away_team_score) OR
                         (g.away_team_id = ? AND g.away_team_score > g.home_team_score)
                    THEN 1 ELSE 0 END as won')
            ])
            ->where('tgs.team_id', $teamId)
            ->whereRaw('(g.home_team_id = ? OR g.away_team_id = ?)', [$teamId, $teamId])
            ->where('g.status', 'finished')
            ->orderBy('g.played_at', 'desc')
            ->limit($lastNGames)
            ->get();

        // Calculate trends
        $result = [
            'games' => $trends->toArray(),
            'averages' => [
                'points' => $trends->avg('points_scored'),
                'fg_percentage' => $trends->avg('field_goal_percentage'),
                'rebounds' => $trends->avg('total_rebounds'),
                'assists' => $trends->avg('assists'),
                'turnovers' => $trends->avg('turnovers')
            ],
            'record' => [
                'wins' => $trends->sum('won'),
                'losses' => $trends->count() - $trends->sum('won')
            ],
            'form' => $this->calculateForm($trends->pluck('won')->toArray())
        ];

        if ($useCache) {
            Cache::put($cacheKey, $result, self::STATS_CACHE_TTL);
        }

        return $result;
    }

    /**
     * Optimierte Player Performance Comparison
     *
     * @param array $playerIds
     * @param string|null $season
     * @param bool $useCache
     * @return array
     */
    public function comparePlayersPerformance(array $playerIds, ?string $season = null, bool $useCache = true): array
    {
        $cacheKey = "player_comparison_" . implode('_', $playerIds) . "_{$season}";

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Batch query für multiple players
        $comparisons = DB::table('player_game_statistics as pgs')
            ->join('players as p', 'pgs.player_id', '=', 'p.id')
            ->select([
                'p.id',
                'p.first_name',
                'p.last_name',
                'p.position',
                DB::raw('COUNT(pgs.game_id) as games_played'),
                DB::raw('ROUND(AVG(pgs.points), 2) as avg_points'),
                DB::raw('ROUND(AVG(pgs.rebounds), 2) as avg_rebounds'),
                DB::raw('ROUND(AVG(pgs.assists), 2) as avg_assists'),
                DB::raw('ROUND(AVG(pgs.steals), 2) as avg_steals'),
                DB::raw('ROUND(AVG(pgs.blocks), 2) as avg_blocks'),
                DB::raw('CASE 
                    WHEN SUM(pgs.field_goal_attempts) > 0 
                    THEN ROUND(SUM(pgs.field_goal_made) * 100.0 / SUM(pgs.field_goal_attempts), 2)
                    ELSE 0 
                END as fg_percentage'),
                DB::raw('CASE 
                    WHEN SUM(pgs.three_point_attempts) > 0 
                    THEN ROUND(SUM(pgs.three_point_made) * 100.0 / SUM(pgs.three_point_attempts), 2)
                    ELSE 0 
                END as three_point_percentage')
            ])
            ->whereIn('pgs.player_id', $playerIds)
            ->when($season, function($query, $season) {
                return $query->where('pgs.season', $season);
            })
            ->groupBy(['p.id', 'p.first_name', 'p.last_name', 'p.position'])
            ->get();

        $result = $comparisons->toArray();

        if ($useCache) {
            Cache::put($cacheKey, $result, self::STATS_CACHE_TTL);
        }

        return $result;
    }

    /**
     * Optimierte Top Performers Query
     *
     * @param string $metric
     * @param int $limit
     * @param string|null $season
     * @param bool $useCache
     * @return array
     */
    public function getTopPerformers(string $metric = 'points', int $limit = 10, ?string $season = null, bool $useCache = true): array
    {
        $cacheKey = "top_performers_{$metric}_{$limit}_{$season}";

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // SEC-007: Whitelist of valid metrics with their SQL expressions
        // Only keys from this array can be used as column aliases
        $validMetrics = [
            'points' => 'AVG(pgs.points)',
            'rebounds' => 'AVG(pgs.rebounds)',
            'assists' => 'AVG(pgs.assists)',
            'steals' => 'AVG(pgs.steals)',
            'blocks' => 'AVG(pgs.blocks)',
            'field_goal_percentage' => 'CASE WHEN SUM(pgs.field_goal_attempts) > 0 THEN SUM(pgs.field_goal_made) * 100.0 / SUM(pgs.field_goal_attempts) ELSE 0 END'
        ];

        // SEC-007: Strict validation - metric must be in whitelist
        if (!isset($validMetrics[$metric])) {
            $metric = 'points';
        }

        // SEC-007: Additional sanitization of alias name (defense in depth)
        // Even though metric is whitelisted, we ensure the alias is safe
        $safeAlias = preg_replace('/[^a-z_]/', '', $metric);

        $topPerformers = DB::table('player_game_statistics as pgs')
            ->join('players as p', 'pgs.player_id', '=', 'p.id')
            ->join('teams as t', 'p.team_id', '=', 't.id')
            ->select([
                'p.id',
                'p.first_name',
                'p.last_name',
                'p.position',
                't.name as team_name',
                DB::raw('COUNT(pgs.game_id) as games_played'),
                // SEC-007: Use sanitized alias name in SQL
                DB::raw("ROUND({$validMetrics[$metric]}, 2) as " . $safeAlias)
            ])
            ->when($season, function($query, $season) {
                return $query->where('pgs.season', $season);
            })
            ->groupBy(['p.id', 'p.first_name', 'p.last_name', 'p.position', 't.name'])
            ->having('games_played', '>=', 5) // Minimum games threshold
            ->orderBy($safeAlias, 'desc') // SEC-007: Use sanitized alias
            ->limit($limit)
            ->get();

        $result = $topPerformers->toArray();

        if ($useCache) {
            Cache::put($cacheKey, $result, self::STATS_CACHE_TTL);
        }

        return $result;
    }

    /**
     * Database Performance Monitoring
     *
     * @return array
     */
    public function getDatabasePerformanceMetrics(): array
    {
        // Query Performance Stats
        $slowQueries = DB::select("
            SELECT query_time, lock_time, rows_sent, rows_examined, 
                   LEFT(sql_text, 100) as query_preview
            FROM mysql.slow_log 
            WHERE start_time >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ORDER BY query_time DESC 
            LIMIT 10
        ");

        // Table sizes
        $tableSizes = DB::select("
            SELECT 
                table_name,
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                table_rows
            FROM information_schema.TABLES 
            WHERE table_schema = DATABASE()
            ORDER BY (data_length + index_length) DESC
            LIMIT 10
        ");

        // Index usage
        $indexUsage = DB::select("
            SELECT 
                OBJECT_SCHEMA,
                OBJECT_NAME,
                INDEX_NAME,
                COUNT_FETCH,
                COUNT_INSERT,
                COUNT_UPDATE,
                COUNT_DELETE
            FROM performance_schema.table_io_waits_summary_by_index_usage
            WHERE OBJECT_SCHEMA = DATABASE()
            ORDER BY COUNT_FETCH DESC
            LIMIT 10
        ");

        return [
            'slow_queries' => $slowQueries,
            'table_sizes' => $tableSizes,
            'index_usage' => $indexUsage,
            'cache_hit_rate' => $this->getCacheHitRate(),
            'generated_at' => now()
        ];
    }

    /**
     * Cache Management
     *
     * @param string $pattern
     * @return int
     */
    public function clearCachePattern(string $pattern): int
    {
        $keys = Cache::getRedis()->keys($pattern);
        $deleted = 0;

        foreach ($keys as $key) {
            if (Cache::forget($key)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Warm up cache mit häufigen Queries
     *
     * @return array
     */
    public function warmUpCache(): array
    {
        $warmedUp = [];

        // Top players cache
        $topScorers = $this->getTopPerformers('points', 20, null, false);
        $warmedUp['top_scorers'] = count($topScorers);

        // Recent games
        $recentGames = Game::with(['homeTeam', 'awayTeam'])
            ->where('played_at', '>=', now()->subDays(7))
            ->get();
        
        foreach ($recentGames as $game) {
            $this->getLiveGameStatistics($game->id, false);
        }
        $warmedUp['recent_games'] = $recentGames->count();

        // Active players stats
        $activePlayers = Player::where('status', 'active')->limit(50)->get();
        foreach ($activePlayers as $player) {
            $this->getPlayerSeasonStatistics($player->id, null, false);
        }
        $warmedUp['player_stats'] = $activePlayers->count();

        return $warmedUp;
    }

    // Private Helper Methods

    /**
     * Direkte Player Season Stats Abfrage (Fallback)
     */
    private function getPlayerSeasonStatsDirect(int $playerId, ?string $season): array
    {
        return DB::table('player_game_statistics as pgs')
            ->join('players as p', 'pgs.player_id', '=', 'p.id')
            ->select([
                'p.id',
                'p.first_name',
                'p.last_name',
                DB::raw('COUNT(pgs.game_id) as games_played'),
                DB::raw('ROUND(AVG(pgs.points), 2) as avg_points'),
                DB::raw('ROUND(AVG(pgs.rebounds), 2) as avg_rebounds'),
                DB::raw('ROUND(AVG(pgs.assists), 2) as avg_assists'),
                DB::raw('CASE 
                    WHEN SUM(pgs.field_goal_attempts) > 0 
                    THEN ROUND(SUM(pgs.field_goal_made) * 100.0 / SUM(pgs.field_goal_attempts), 2)
                    ELSE 0 
                END as field_goal_percentage')
            ])
            ->where('pgs.player_id', $playerId)
            ->when($season, function($query, $season) {
                return $query->where('pgs.season', $season);
            })
            ->groupBy(['p.id', 'p.first_name', 'p.last_name'])
            ->first();
    }

    /**
     * Team Game Statistics Helper
     */
    private function getTeamGameStatistics(int $gameId, string $side): array
    {
        $game = Game::find($gameId);
        $teamId = ($side === 'home') ? $game->home_team_id : $game->away_team_id;

        return DB::table('team_game_statistics')
            ->where('game_id', $gameId)
            ->where('team_id', $teamId)
            ->first() ?: [];
    }

    /**
     * Calculate team form (W/L pattern)
     */
    private function calculateForm(array $results): string
    {
        return implode('', array_map(fn($result) => $result ? 'W' : 'L', $results));
    }

    /**
     * Get Cache Hit Rate
     */
    private function getCacheHitRate(): float
    {
        try {
            $info = Cache::getRedis()->info('stats');
            $hits = $info['keyspace_hits'] ?? 0;
            $misses = $info['keyspace_misses'] ?? 0;
            $total = $hits + $misses;
            
            return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
}