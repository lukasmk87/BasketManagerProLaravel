<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Models\GameAction;
use Illuminate\Support\Facades\Log;

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

    public function __construct()
    {
        // Constructor vereinfacht - nutzt Laravel Cache-Facade statt direkter Redis-Verbindung
    }

    /**
     * Live Game Caching
     */
    public function cacheLiveGameData(int $gameId, array $data): void
    {
        $key = self::LIVE_GAME_PREFIX . $gameId;
        
        $cacheData = [
            'game_id' => $data['game_id'],
            'home_score' => $data['home_score'],
            'away_score' => $data['away_score'],
            'period' => $data['period'],
            'time_remaining' => $data['time_remaining'],
            'status' => $data['status'],
            'last_action' => $data['last_action'] ?? [],
            'home_stats' => $data['home_stats'] ?? [],
            'away_stats' => $data['away_stats'] ?? [],
            'updated_at' => now()->toISOString()
        ];

        Cache::put($key, $cacheData, self::LIVE_GAME_TTL);

        // Broadcast-Event für Real-time Updates
        $this->broadcastLiveUpdate($gameId, $data);
    }

    public function getLiveGameData(int $gameId): ?array
    {
        $key = self::LIVE_GAME_PREFIX . $gameId;
        return Cache::get($key);
    }

    /**
     * Player Statistics Caching mit Layered Cache
     */
    public function cachePlayerStats(int $playerId, string $season, array $stats, string $type = 'season'): void
    {
        $key = self::PLAYER_STATS_PREFIX . "{$playerId}:{$season}:{$type}";
        
        $cacheData = array_merge($stats, [
            'cached_at' => now()->toISOString(),
            'cache_version' => 'v1'
        ]);

        Cache::put($key, $cacheData, self::PLAYER_STATS_TTL);

        // Zusätzliche Indizierung für schnelle Suche
        $this->indexPlayerForSearch($playerId, $stats);
    }

    public function getPlayerStats(int $playerId, string $season, string $type = 'season'): ?array
    {
        $key = self::PLAYER_STATS_PREFIX . "{$playerId}:{$season}:{$type}";
        
        $stats = Cache::get($key);
        if ($stats) {
            // Bereinige Cache-Metadaten für Rückgabe
            unset($stats['cached_at'], $stats['cache_version']);
        }
        
        return $stats;
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
        
        $cacheData = [
            'player_id' => $playerId,
            'filters' => $filters,
            'shots' => $compressedData,
            'total_shots' => count($shots),
            'made_shots' => count(array_filter($shots, fn($s) => $s['is_successful'])),
            'cached_at' => now()->toISOString()
        ];
        
        Cache::put($key, $cacheData, self::SHOT_CHART_TTL);

        // Räumlicher Index für Hot Zones
        $this->indexShotZones($playerId, $shots);
    }

    public function getShotChartData(int $playerId, array $filters = []): ?array
    {
        $filterKey = md5(json_encode($filters));
        $key = self::SHOT_CHART_PREFIX . "{$playerId}:{$filterKey}";
        
        $data = Cache::get($key);
        
        if ($data) {
            $data['shots'] = $this->decompressShotData($data['shots']);
            return $data;
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
        $cacheData = array_merge($performance, [
            'team_id' => $teamId,
            'period' => $period,
            'updated_at' => now()->toISOString()
        ]);
        
        Cache::put($key, $cacheData, self::TEAM_STATS_TTL);

        // Performance Trending (letzte 10 Datenpunkte als Array)
        $trendKey = self::TEAM_STATS_PREFIX . "trend:{$teamId}";
        $currentTrend = Cache::get($trendKey, []);
        
        // Neuen Datenpunkt hinzufügen
        $newDataPoint = [
            'timestamp' => now()->toISOString(),
            'wins' => $performance['wins'] ?? 0,
            'losses' => $performance['losses'] ?? 0,
            'points_avg' => $performance['points_avg'] ?? 0,
            'opponent_points_avg' => $performance['opponent_points_avg'] ?? 0
        ];
        
        // Am Anfang des Arrays hinzufügen und auf 10 Elemente begrenzen
        array_unshift($currentTrend, $newDataPoint);
        $currentTrend = array_slice($currentTrend, 0, 10);
        
        Cache::put($trendKey, $currentTrend, self::TEAM_STATS_TTL);
    }

    public function getTeamPerformanceTrend(int $teamId): array
    {
        $trendKey = self::TEAM_STATS_PREFIX . "trend:{$teamId}";
        return Cache::get($trendKey, []);
    }

    /**
     * Dynamic Leaderboards mit Array-basierter Sortierung
     */
    public function updateLeaderboard(string $category, int $playerId, float $score, array $metadata = []): void
    {
        $key = self::LEADERBOARD_PREFIX . $category;
        
        // Aktuelles Leaderboard laden
        $leaderboard = Cache::get($key, []);
        
        // Player-Eintrag aktualisieren oder hinzufügen
        $leaderboard[$playerId] = array_merge($metadata, [
            'player_id' => $playerId,
            'score' => $score,
            'updated_at' => now()->toISOString()
        ]);
        
        // Nach Score sortieren (absteigend)
        uasort($leaderboard, fn($a, $b) => $b['score'] <=> $a['score']);
        
        Cache::put($key, $leaderboard, self::LEADERBOARD_TTL);
    }

    public function getLeaderboard(string $category, int $limit = 10, int $offset = 0): array
    {
        $key = self::LEADERBOARD_PREFIX . $category;
        
        $leaderboard = Cache::get($key, []);
        
        // Pagination anwenden und Rank hinzufügen
        $sliced = array_slice($leaderboard, $offset, $limit, true);
        
        $result = [];
        $rank = $offset + 1;
        foreach ($sliced as $entry) {
            $result[] = array_merge($entry, ['rank' => $rank++]);
        }
        
        return $result;
    }

    /**
     * Game Summary Caching
     */
    public function cacheGameSummary(int $gameId, array $summary): void
    {
        $key = self::GAME_SUMMARY_PREFIX . $gameId;
        
        // JSON mit Kompression für große Datenmengen
        $compressedSummary = base64_encode(gzcompress(json_encode($summary), 6));
        
        Cache::put($key, $compressedSummary, self::HISTORICAL_TTL);
    }

    public function getGameSummary(int $gameId): ?array
    {
        $key = self::GAME_SUMMARY_PREFIX . $gameId;
        $compressed = Cache::get($key);
        
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
        
        $cacheData = [
            'query' => $query,
            'type' => $type,
            'results' => $results,
            'count' => count($results),
            'cached_at' => now()->toISOString()
        ];
        
        Cache::put($key, $cacheData, self::SEARCH_TTL);
    }

    public function getSearchResults(string $query, string $type): ?array
    {
        $key = self::SEARCH_PREFIX . $type . ':' . md5(strtolower($query));
        return Cache::get($key);
    }

    /**
     * Cache Invalidation Strategien
     */
    public function invalidatePlayerCache(int $playerId): void
    {
        // Player-spezifische Cache-Keys löschen
        $this->forgetCacheByPrefix(self::PLAYER_STATS_PREFIX . $playerId);
        
        // Auch Shot Chart Cache invalidieren
        $this->forgetCacheByPrefix(self::SHOT_CHART_PREFIX . $playerId);

        // Leaderboards aktualisieren
        $this->invalidateLeaderboards();
    }

    public function invalidateTeamCache(int $teamId): void
    {
        $this->forgetCacheByPrefix(self::TEAM_STATS_PREFIX . $teamId);
    }

    public function invalidateGameCache(int $gameId): void
    {
        Cache::forget(self::LIVE_GAME_PREFIX . $gameId);
        Cache::forget(self::GAME_SUMMARY_PREFIX . $gameId);
    }

    public function invalidateLeaderboards(): void
    {
        $this->forgetCacheByPrefix(self::LEADERBOARD_PREFIX);
    }

    /**
     * Cache Analytics (vereinfacht für Laravel Cache)
     */
    public function getCacheAnalytics(): array
    {
        $analytics = [
            'cache_driver' => config('cache.default'),
            'cache_store' => Cache::getStore(),
            'status' => 'active',
            'cache_distribution' => $this->getCacheDistribution(),
            'note' => 'Detailed analytics available only with Redis driver'
        ];

        return $analytics;
    }

    /**
     * Bulk Cache Operations
     */
    public function bulkCachePlayerStats(array $playersData): void
    {
        foreach ($playersData as $playerData) {
            $key = self::PLAYER_STATS_PREFIX . $playerData['player_id'] . ':' . $playerData['season'] . ':bulk';
            Cache::put($key, $playerData['stats'], self::PLAYER_STATS_TTL);
        }
    }

    public function bulkInvalidate(array $patterns): int
    {
        $deleted = 0;
        foreach ($patterns as $pattern) {
            $deleted += $this->forgetCacheByPrefix($pattern);
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
        
        // Aktuellen Search Index laden
        $currentIndex = Cache::get($searchKey, []);
        $currentIndex[$playerId] = $playerData;
        
        Cache::put($searchKey, $currentIndex, self::SEARCH_TTL);
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
        $zoneData = [];
        
        foreach ($zones as $zone => $stats) {
            $percentage = $stats['attempted'] > 0 ? ($stats['made'] / $stats['attempted']) * 100 : 0;
            $zoneData[$zone] = array_merge($stats, ['percentage' => $percentage]);
        }
        
        Cache::put($zoneKey, $zoneData, self::SHOT_CHART_TTL);
    }

    private function broadcastLiveUpdate(int $gameId, array $data): void
    {
        // Log real-time update (Laravel Broadcasting kann später hinzugefügt werden)
        Log::info('Live game update', [
            'type' => 'score_update',
            'game_id' => $gameId,
            'data' => $data,
            'timestamp' => now()->toISOString()
        ]);
        
        // TODO: Implement Laravel Broadcasting when needed
        // broadcast(new GameScoreUpdated($gameId, $data));
    }

    private function forgetCacheByPrefix(string $prefix): int
    {
        // Da Laravel Cache keine Pattern-basierten Löschungen unterstützt,
        // verwenden wir eine einfache Implementierung mit bekannten Suffixen
        $deleted = 0;
        $suffixes = ['season', 'career', 'trend', 'bulk', 'meta'];
        
        // Versuche verschiedene bekannte Kombinationen zu löschen
        foreach ($suffixes as $suffix) {
            $key = $prefix . ':' . $suffix;
            if (Cache::forget($key)) {
                $deleted++;
            }
        }
        
        // Auch den Prefix allein versuchen
        if (Cache::forget($prefix)) {
            $deleted++;
        }
        
        return $deleted;
    }

    private function calculateHitRatio(): float
    {
        // Hit-Ratio nur für Redis verfügbar
        return 0.0;
    }

    private function getCacheDistribution(): array
    {
        // Cache-Distribution vereinfacht für Laravel Cache
        return [
            'live_game' => 'N/A',
            'player_stats' => 'N/A', 
            'team_stats' => 'N/A',
            'shot_chart' => 'N/A',
            'leaderboard' => 'N/A',
            'search' => 'N/A',
            'note' => 'Key counting not available with current cache driver'
        ];
    }
}