<?php

namespace App\Services\Statistics;

use App\Models\Game;
use App\Models\Player;
use App\Models\Team;

/**
 * StatisticsService (Facade/Orchestrator)
 *
 * Verantwortung: Zentrale API für alle Statistik-Operationen.
 * Delegiert an spezialisierte Services und bietet eine rückwärtskompatible
 * öffentliche API für alle Consumer (Controller, Jobs, Exports).
 *
 * @see PlayerStatisticsService für Spieler-Stats
 * @see TeamStatisticsService für Team-Stats
 * @see GameStatisticsService für Game-Level Stats
 * @see ShotChartService für Shot Chart & Zonen
 * @see AdvancedMetricsService für erweiterte Berechnungen
 * @see StatisticsCacheManager für Cache-Strategie
 */
class StatisticsService
{
    public function __construct(
        private PlayerStatisticsService $playerStats,
        private TeamStatisticsService $teamStats,
        private GameStatisticsService $gameStats,
        private ShotChartService $shotChart,
        private AdvancedMetricsService $metrics,
        private StatisticsCacheManager $cache
    ) {}

    // ========================================================================
    // Player Statistics (delegiert an PlayerStatisticsService)
    // ========================================================================

    /**
     * Get player statistics wrapper method (used by StatisticsController).
     */
    public function getPlayerStatistics(Player $player, ?string $season = null): array
    {
        return $this->playerStats->getPlayerStatistics($player, $season);
    }

    /**
     * Get player statistics for a specific game.
     */
    public function getPlayerGameStats(Player $player, Game $game): array
    {
        return $this->playerStats->getPlayerGameStats($player, $game);
    }

    /**
     * Get player statistics for a season.
     */
    public function getPlayerSeasonStats(Player $player, string $season): array
    {
        return $this->playerStats->getPlayerSeasonStats($player, $season);
    }

    /**
     * Get advanced player metrics.
     */
    public function getAdvancedPlayerStats(Player $player, Game $game): array
    {
        return $this->playerStats->getAdvancedPlayerStats($player, $game);
    }

    // ========================================================================
    // Team Statistics (delegiert an TeamStatisticsService)
    // ========================================================================

    /**
     * Get team statistics wrapper method (used by StatisticsController).
     */
    public function getTeamStatistics(Team $team): array
    {
        return $this->teamStats->getTeamStatistics($team);
    }

    /**
     * Get team statistics for a specific game.
     */
    public function getTeamGameStats(Team $team, Game $game): array
    {
        return $this->teamStats->getTeamGameStats($team, $game);
    }

    /**
     * Get team statistics for a season.
     */
    public function getTeamSeasonStats(Team $team, string $season): array
    {
        return $this->teamStats->getTeamSeasonStats($team, $season);
    }

    /**
     * Get team's pace (possessions per 48 minutes).
     */
    public function getTeamPace(Team $team, Game $game): float
    {
        return $this->teamStats->getTeamPace($team, $game);
    }

    /**
     * Get comprehensive team analytics.
     */
    public function getTeamAnalytics(Team $team, string $season): array
    {
        return $this->teamStats->getTeamAnalytics($team, $season);
    }

    // ========================================================================
    // Game Statistics (delegiert an GameStatisticsService)
    // ========================================================================

    /**
     * Get game statistics wrapper method (used by StatisticsController).
     */
    public function getGameStatistics(Game $game): array
    {
        return $this->gameStats->getGameStatistics($game);
    }

    /**
     * Get current game statistics (for live games).
     */
    public function getCurrentGameStats(Game $game): array
    {
        return $this->gameStats->getCurrentGameStats($game);
    }

    // ========================================================================
    // Shot Chart (delegiert an ShotChartService)
    // ========================================================================

    /**
     * Get shot chart data for a player in a game.
     */
    public function getPlayerShotChart(Player $player, Game $game): array
    {
        return $this->shotChart->getPlayerShotChart($player, $game);
    }

    // ========================================================================
    // Cache Invalidation (delegiert an StatisticsCacheManager)
    // ========================================================================

    /**
     * Invalidate player statistics cache for a specific game.
     */
    public function clearPlayerCache(Player $player, ?Game $game = null): void
    {
        $this->cache->clearPlayerCache($player, $game);
    }

    /**
     * Invalidate team statistics cache for a specific game.
     */
    public function clearTeamCache(Team $team, ?Game $game = null): void
    {
        $this->cache->clearTeamCache($team, $game);
    }

    /**
     * Invalidate all statistics cache for a specific game.
     */
    public function clearGameCache(Game $game): void
    {
        $this->cache->clearGameCache($game);
    }

    // ========================================================================
    // Legacy Methods (für Rückwärtskompatibilität)
    // ========================================================================

    /**
     * Legacy method - redirects to new clearPlayerCache for backwards compatibility.
     * @deprecated Use clearPlayerCache() instead
     */
    public function invalidatePlayerStats(Player $player): void
    {
        $this->cache->clearPlayerCache($player);

        // Also invalidate team stats (legacy behavior)
        if ($player->team) {
            $this->cache->clearTeamCache($player->team);
        }
    }

    /**
     * Legacy method - redirects to new clearTeamCache for backwards compatibility.
     * @deprecated Use clearTeamCache() instead
     */
    public function invalidateTeamStats(Team $team): void
    {
        $this->cache->clearTeamCache($team);
    }

    /**
     * Legacy method - redirects to new clearGameCache for backwards compatibility.
     * @deprecated Use clearGameCache() instead
     */
    public function invalidateGameStats(Game $game): void
    {
        $this->cache->clearGameCache($game);
    }

    // ========================================================================
    // Direct Service Access (für fortgeschrittene Nutzung)
    // ========================================================================

    /**
     * Get the PlayerStatisticsService instance.
     */
    public function playerStatistics(): PlayerStatisticsService
    {
        return $this->playerStats;
    }

    /**
     * Get the TeamStatisticsService instance.
     */
    public function teamStatistics(): TeamStatisticsService
    {
        return $this->teamStats;
    }

    /**
     * Get the GameStatisticsService instance.
     */
    public function gameStatistics(): GameStatisticsService
    {
        return $this->gameStats;
    }

    /**
     * Get the ShotChartService instance.
     */
    public function shotChartService(): ShotChartService
    {
        return $this->shotChart;
    }

    /**
     * Get the AdvancedMetricsService instance.
     */
    public function advancedMetrics(): AdvancedMetricsService
    {
        return $this->metrics;
    }

    /**
     * Get the StatisticsCacheManager instance.
     */
    public function cacheManager(): StatisticsCacheManager
    {
        return $this->cache;
    }
}
