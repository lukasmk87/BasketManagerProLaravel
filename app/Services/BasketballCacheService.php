<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Models\GameAction;

/**
 * Basketball-spezifischer Cache Service
 * 
 * Optimiertes Caching für Basketball-Daten mit intelligenten Invalidation-Strategien
 */
class BasketballCacheService
{
    // Cache TTL Konstanten (in Sekunden)
    const LIVE_GAME_TTL = 10;          // 10 Sekunden für Live-Daten
    const PLAYER_STATS_TTL = 1800;     // 30 Minuten für Spielerstatistiken
    const TEAM_STATS_TTL = 1800;       // 30 Minuten für Teamstatistiken
    const HISTORICAL_TTL = 7200;       // 2 Stunden für historische Daten
    const SHOT_CHART_TTL = 3600;       // 1 Stunde für Shot Charts
    const LEADERBOARD_TTL = 900;       // 15 Minuten für Leaderboards
    const SEARCH_TTL = 600;            // 10 Minuten für Suchergebnisse

    // Cache Keys Prefixes
    const LIVE_GAME_PREFIX = 'live_game:';
    const PLAYER_STATS_PREFIX = 'player_stats:';
    const TEAM_STATS_PREFIX = 'team_stats:';
    const SHOT_CHART_PREFIX = 'shot_chart:';
    const LEADERBOARD_PREFIX = 'leaderboard:';
    const SEARCH_PREFIX = 'search:';
    const GAME_SUMMARY_PREFIX = 'game_summary:';

    private $redis;

    public function __construct()
    {
        $this->redis = Redis::connection('cache');
    }

    /**
     * Live Game Caching
     */
    public function cacheLiveGameData(int $gameId, array $data): void
    {
        $key = self::LIVE_GAME_PREFIX . $gameId;
        
        // Verwende Redis Pipeline für atomische Updates
        $this->redis->pipeline(function ($pipe) use ($key, $data) {
            $pipe->hmset($key, [
                'game_id' => $data['game_id'],
                'home_score' => $data['home_score'],
                'away_score' => $data['away_score'],
                'period' => $data['period'],
                'time_remaining' => $data['time_remaining'],
                'status' => $data['status'],
                'last_action' => json_encode($data['last_action'] ?? []),
                'home_stats' => json_encode($data['home_stats'] ?? []),
                'away_stats' => json_encode($data['away_stats'] ?? []),
                'updated_at' => now()->toISOString()
            ]);
            $pipe->expire($key, self::LIVE_GAME_TTL);
        });

        // Broadcast-Event für Real-time Updates
        $this->broadcastLiveUpdate($gameId, $data);
    }

    public function getLiveGameData(int $gameId): ?array
    {
        $key = self::LIVE_GAME_PREFIX . $gameId;
        $data = $this->redis->hgetall($key);
        
        if (empty($data)) {
            return null;
        }

        // JSON-Felder decodieren
        $data['last_action'] = json_decode($data['last_action'] ?? '[]', true);
        $data['home_stats'] = json_decode($data['home_stats'] ?? '[]', true);
        $data['away_stats'] = json_decode($data['away_stats'] ?? '[]', true);

        return $data;
    }

    /**
     * Player Statistics Caching mit Layered Cache
     */
    public function cachePlayerStats(int $playerId, string $season, array $stats, string $type = 'season'): void
    {
        $key = self::PLAYER_STATS_PREFIX . "{$playerId}:{$season}:{$type}";
        
        // L1 Cache: Redis Hash für schnelle Zugriffe
        $this->redis->hmset($key, array_merge($stats, [
            'cached_at' => now()->toISOString(),
            'cache_version' => 'v1'
        ]));
        $this->redis->expire($key, self::PLAYER_STATS_TTL);

        // L2 Cache: Laravel Cache für Backup
        Cache::put($key, $stats, self::PLAYER_STATS_TTL);

        // Zusätzliche Indizierung für schnelle Suche
        $this->indexPlayerForSearch($playerId, $stats);
    }

    public function getPlayerStats(int $playerId, string $season, string $type = 'season'): ?array
    {
        $key = self::PLAYER_STATS_PREFIX . "{$playerId}:{$season}:{$type}";
        
        // L1 Cache Check
        $stats = $this->redis->hgetall($key);
        if (!empty($stats)) {
            unset($stats['cached_at'], $stats['cache_version']);
            return $stats;
        }

        // L2 Cache Fallback
        return Cache::get($key);
    }

    /**
     * Shot Chart Caching mit Spatial Indexing
     */
    public function cacheShotChartData(int $playerId, array $shots, array $filters = []): void
    {
        $filterKey = md5(json_encode($filters));
        $key = self::SHOT_CHART_PREFIX . "{$playerId}:{$filterKey}";
        
        // Komprimierte Shot-Daten
        $compressedData = $this->compressShotData($shots);
        
        $this->redis->setex($key, self::SHOT_CHART_TTL, json_encode([
            'player_id' => $playerId,
            'filters' => $filters,
            'shots' => $compressedData,
            'total_shots' => count($shots),
            'made_shots' => count(array_filter($shots, fn($s) => $s['is_successful'])),
            'cached_at' => now()->toISOString()
        ]));

        // Räumlicher Index für Hot Zones
        $this->indexShotZones($playerId, $shots);
    }

    public function getShotChartData(int $playerId, array $filters = []): ?array
    {
        $filterKey = md5(json_encode($filters));
        $key = self::SHOT_CHART_PREFIX . "{$playerId}:{$filterKey}";
        
        $data = $this->redis->get($key);
        
        if ($data) {
            $decoded = json_decode($data, true);
            $decoded['shots'] = $this->decompressShotData($decoded['shots']);
            return $decoded;
        }

        return null;
    }

    /**
     * Team Performance Caching mit Trending
     */
    public function cacheTeamPerformance(int $teamId, array $performance, string $period = 'season'): void
    {
        $key = self::TEAM_STATS_PREFIX . "{$teamId}:{$period}";
        
        // Current Performance
        $this->redis->hmset($key, array_merge($performance, [
            'team_id' => $teamId,
            'period' => $period,
            'updated_at' => now()->toISOString()
        ]));
        $this->redis->expire($key, self::TEAM_STATS_TTL);

        // Performance Trending (letzte 10 Datenpunkte)
        $trendKey = self::TEAM_STATS_PREFIX . "trend:{$teamId}";
        $this->redis->lpush($trendKey, json_encode([
            'timestamp' => now()->toISOString(),
            'wins' => $performance['wins'] ?? 0,
            'losses' => $performance['losses'] ?? 0,
            'points_avg' => $performance['points_avg'] ?? 0,
            'opponent_points_avg' => $performance['opponent_points_avg'] ?? 0
        ]));
        $this->redis->ltrim($trendKey, 0, 9); // Keep only last 10
        $this->redis->expire($trendKey, self::TEAM_STATS_TTL);
    }

    public function getTeamPerformanceTrend(int $teamId): array
    {
        $trendKey = self::TEAM_STATS_PREFIX . "trend:{$teamId}";
        $trendData = $this->redis->lrange($trendKey, 0, -1);
        
        return array_map(fn($data) => json_decode($data, true), $trendData);
    }

    /**
     * Dynamic Leaderboards mit Sorted Sets
     */
    public function updateLeaderboard(string $category, int $playerId, float $score, array $metadata = []): void
    {
        $key = self::LEADERBOARD_PREFIX . $category;
        
        // Sorted Set für Rankings
        $this->redis->zadd($key, $score, $playerId);
        $this->redis->expire($key, self::LEADERBOARD_TTL);

        // Metadata für Leaderboard-Einträge
        $metaKey = $key . ':meta:' . $playerId;
        $this->redis->hmset($metaKey, array_merge($metadata, [
            'score' => $score,
            'updated_at' => now()->toISOString()
        ]));
        $this->redis->expire($metaKey, self::LEADERBOARD_TTL);
    }

    public function getLeaderboard(string $category, int $limit = 10, int $offset = 0): array
    {
        $key = self::LEADERBOARD_PREFIX . $category;
        
        // Top-Performers mit Scores
        $rankings = $this->redis->zrevrange($key, $offset, $offset + $limit - 1, 'WITHSCORES');
        
        $leaderboard = [];
        for ($i = 0; $i < count($rankings); $i += 2) {
            $playerId = $rankings[$i];
            $score = $rankings[$i + 1];
            
            // Metadata abrufen
            $metaKey = $key . ':meta:' . $playerId;
            $metadata = $this->redis->hgetall($metaKey);
            
            $leaderboard[] = array_merge($metadata, [
                'player_id' => $playerId,
                'score' => $score,
                'rank' => $offset + ($i / 2) + 1
            ]);
        }
        
        return $leaderboard;
    }

    /**
     * Game Summary Caching
     */
    public function cacheGameSummary(int $gameId, array $summary): void
    {
        $key = self::GAME_SUMMARY_PREFIX . $gameId;
        
        // JSON mit Kompression
        $compressedSummary = gzcompress(json_encode($summary), 6);
        
        $this->redis->setex($key, self::HISTORICAL_TTL, base64_encode($compressedSummary));
    }

    public function getGameSummary(int $gameId): ?array
    {
        $key = self::GAME_SUMMARY_PREFIX . $gameId;
        $compressed = $this->redis->get($key);
        
        if ($compressed) {
            $decompressed = gzuncompress(base64_decode($compressed));
            return json_decode($decompressed, true);
        }
        
        return null;
    }

    /**
     * Search Results Caching
     */
    public function cacheSearchResults(string $query, string $type, array $results): void
    {
        $key = self::SEARCH_PREFIX . $type . ':' . md5(strtolower($query));
        
        $this->redis->setex($key, self::SEARCH_TTL, json_encode([
            'query' => $query,
            'type' => $type,
            'results' => $results,
            'count' => count($results),
            'cached_at' => now()->toISOString()
        ]));
    }

    public function getSearchResults(string $query, string $type): ?array
    {
        $key = self::SEARCH_PREFIX . $type . ':' . md5(strtolower($query));
        $data = $this->redis->get($key);
        
        return $data ? json_decode($data, true) : null;
    }

    /**
     * Cache Invalidation Strategien
     */
    public function invalidatePlayerCache(int $playerId): void
    {
        $pattern = self::PLAYER_STATS_PREFIX . $playerId . ':*';
        $this->deleteByPattern($pattern);
        
        // Auch Shot Chart Cache invalidieren
        $shotPattern = self::SHOT_CHART_PREFIX . $playerId . ':*';
        $this->deleteByPattern($shotPattern);

        // Leaderboards aktualisieren
        $this->invalidateLeaderboards();
    }

    public function invalidateTeamCache(int $teamId): void
    {
        $pattern = self::TEAM_STATS_PREFIX . $teamId . ':*';
        $this->deleteByPattern($pattern);
    }

    public function invalidateGameCache(int $gameId): void
    {
        $this->redis->del(self::LIVE_GAME_PREFIX . $gameId);
        $this->redis->del(self::GAME_SUMMARY_PREFIX . $gameId);
    }

    public function invalidateLeaderboards(): void
    {
        $pattern = self::LEADERBOARD_PREFIX . '*';
        $this->deleteByPattern($pattern);
    }

    /**
     * Cache Analytics
     */
    public function getCacheAnalytics(): array
    {
        $info = $this->redis->info('memory');
        $keyspace = $this->redis->info('keyspace');
        
        $analytics = [
            'memory_usage' => [
                'used' => $info['used_memory_human'] ?? 'N/A',
                'peak' => $info['used_memory_peak_human'] ?? 'N/A',
                'fragmentation_ratio' => $info['mem_fragmentation_ratio'] ?? 0
            ],
            'keyspace' => $keyspace,
            'hit_ratio' => $this->calculateHitRatio(),
            'cache_distribution' => $this->getCacheDistribution()
        ];

        return $analytics;
    }

    /**
     * Bulk Cache Operations
     */
    public function bulkCachePlayerStats(array $playersData): void
    {
        $this->redis->pipeline(function ($pipe) use ($playersData) {
            foreach ($playersData as $playerData) {
                $key = self::PLAYER_STATS_PREFIX . $playerData['player_id'] . ':' . $playerData['season'] . ':bulk';
                $pipe->hmset($key, $playerData['stats']);
                $pipe->expire($key, self::PLAYER_STATS_TTL);
            }
        });
    }

    public function bulkInvalidate(array $patterns): int
    {
        $deleted = 0;
        foreach ($patterns as $pattern) {
            $deleted += $this->deleteByPattern($pattern);
        }
        return $deleted;
    }

    /**
     * Cache Warming Strategien
     */
    public function warmPopularData(): array
    {
        $warmed = [
            'leaderboards' => 0,
            'team_stats' => 0,
            'recent_games' => 0
        ];

        // Warm Leaderboards
        $categories = ['points', 'rebounds', 'assists', 'steals', 'blocks'];
        foreach ($categories as $category) {
            // Logic würde hier die Top-Performer laden und cachen
            $warmed['leaderboards']++;
        }

        // Warm Team Stats für aktive Teams
        $activeTeams = Team::where('status', 'active')->limit(20)->pluck('id');
        foreach ($activeTeams as $teamId) {
            // Logic würde hier Team-Stats laden und cachen
            $warmed['team_stats']++;
        }

        // Warm Recent Game Data
        $recentGames = Game::where('played_at', '>=', now()->subDays(7))->limit(10)->pluck('id');
        foreach ($recentGames as $gameId) {
            // Logic würde hier Game-Summaries laden und cachen
            $warmed['recent_games']++;
        }

        return $warmed;
    }

    /**
     * Private Helper Methods
     */
    private function compressShotData(array $shots): array
    {
        // Komprimiere Shot-Daten durch Entfernung redundanter Felder
        return array_map(function ($shot) {
            return [
                'x' => $shot['shot_x'],
                'y' => $shot['shot_y'],
                's' => $shot['is_successful'] ? 1 : 0,
                'd' => $shot['shot_distance'] ?? 0,
                'z' => $shot['shot_zone'] ?? '',
                't' => $shot['action_type']
            ];
        }, $shots);
    }

    private function decompressShotData(array $compressed): array
    {
        return array_map(function ($shot) {
            return [
                'shot_x' => $shot['x'],
                'shot_y' => $shot['y'],
                'is_successful' => $shot['s'] === 1,
                'shot_distance' => $shot['d'],
                'shot_zone' => $shot['z'],
                'action_type' => $shot['t']
            ];
        }, $compressed);
    }

    private function indexPlayerForSearch(int $playerId, array $stats): void
    {
        $searchKey = 'search_index:players';
        $playerData = [
            'id' => $playerId,
            'avg_points' => $stats['avg_points'] ?? 0,
            'avg_rebounds' => $stats['avg_rebounds'] ?? 0,
            'avg_assists' => $stats['avg_assists'] ?? 0
        ];
        
        $this->redis->hset($searchKey, $playerId, json_encode($playerData));
        $this->redis->expire($searchKey, self::SEARCH_TTL);
    }

    private function indexShotZones(int $playerId, array $shots): void
    {
        $zones = [];
        foreach ($shots as $shot) {
            $zone = $shot['shot_zone'] ?? 'unknown';
            if (!isset($zones[$zone])) {
                $zones[$zone] = ['made' => 0, 'attempted' => 0];
            }
            $zones[$zone]['attempted']++;
            if ($shot['is_successful']) {
                $zones[$zone]['made']++;
            }
        }

        $zoneKey = 'shot_zones:' . $playerId;
        foreach ($zones as $zone => $stats) {
            $percentage = $stats['attempted'] > 0 ? ($stats['made'] / $stats['attempted']) * 100 : 0;
            $this->redis->hset($zoneKey, $zone, json_encode(array_merge($stats, ['percentage' => $percentage])));
        }
        $this->redis->expire($zoneKey, self::SHOT_CHART_TTL);
    }

    private function broadcastLiveUpdate(int $gameId, array $data): void
    {
        // Publish to Redis Channel for real-time updates
        $this->redis->publish('live_games', json_encode([
            'type' => 'score_update',
            'game_id' => $gameId,
            'data' => $data,
            'timestamp' => now()->toISOString()
        ]));
    }

    private function deleteByPattern(string $pattern): int
    {
        $keys = $this->redis->keys($pattern);
        
        if (empty($keys)) {
            return 0;
        }

        return $this->redis->del($keys);
    }

    private function calculateHitRatio(): float
    {
        $stats = $this->redis->info('stats');
        $hits = $stats['keyspace_hits'] ?? 0;
        $misses = $stats['keyspace_misses'] ?? 0;
        $total = $hits + $misses;
        
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }

    private function getCacheDistribution(): array
    {
        $prefixes = [
            'live_game' => self::LIVE_GAME_PREFIX,
            'player_stats' => self::PLAYER_STATS_PREFIX,
            'team_stats' => self::TEAM_STATS_PREFIX,
            'shot_chart' => self::SHOT_CHART_PREFIX,
            'leaderboard' => self::LEADERBOARD_PREFIX,
            'search' => self::SEARCH_PREFIX
        ];

        $distribution = [];
        foreach ($prefixes as $name => $prefix) {
            $keys = $this->redis->keys($prefix . '*');
            $distribution[$name] = count($keys);
        }

        return $distribution;
    }
}