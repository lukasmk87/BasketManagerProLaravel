<?php

namespace App\Services\Statistics;

use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Models\GameAction;

/**
 * AdvancedMetricsService
 *
 * Verantwortung: Berechnung komplexer Basketball-Metriken wie:
 * - True Shooting Percentage (TS%)
 * - Player Efficiency Rating (PER)
 * - Offensive/Defensive Rating
 * - Usage Rate
 * - Four Factors of Basketball Success
 * - Game Score (Hollinger)
 */
class AdvancedMetricsService
{
    /**
     * Calculate True Shooting Percentage.
     * TS% = PTS / (2 × TSA), where TSA = FGA + (0.44 × FTA)
     */
    public function calculateTrueShootingPercentage(int $points, int $fga, int $fta): float
    {
        $tsa = $fga + (0.44 * $fta); // True Shot Attempts

        return $tsa > 0 ? round(($points / (2 * $tsa)) * 100, 1) : 0;
    }

    /**
     * Calculate Player Efficiency Rating (simplified).
     * Simplified PER = PTS + REB + AST + STL + BLK - TO - PF
     */
    public function calculatePlayerEfficiencyRating(array $stats): float
    {
        $per = ($stats['total_points'] + $stats['total_rebounds'] + $stats['assists']
                + $stats['steals'] + $stats['blocks'] - $stats['turnovers']
                - $stats['personal_fouls']);

        return round($per, 1);
    }

    /**
     * Calculate Offensive Rating.
     * ORtg = (Points / Possessions) × 100
     */
    public function calculateOffensiveRating(array $stats): float
    {
        $possessions = $this->estimatePossessions($stats);

        return $possessions > 0 ? round(($stats['points_for'] / $possessions) * 100, 1) : 0;
    }

    /**
     * Calculate Defensive Rating.
     * DRtg = (Points Allowed / Possessions) × 100
     */
    public function calculateDefensiveRating(array $stats): float
    {
        $possessions = $this->estimatePossessions($stats);

        return $possessions > 0 ? round(($stats['points_against'] / $possessions) * 100, 1) : 0;
    }

    /**
     * Calculate Usage Rate - percentage of team plays used by player.
     */
    public function calculateUsageRate(Player $player, Game $game): float
    {
        $playerActions = GameAction::where('game_id', $game->id)
            ->where('player_id', $player->id)
            ->whereIn('action_type', [
                'field_goal_made', 'field_goal_missed',
                'three_point_made', 'three_point_missed',
                'free_throw_made', 'free_throw_missed',
                'turnover'
            ])
            ->count();

        $teamActions = GameAction::where('game_id', $game->id)
            ->where('team_id', $player->team_id)
            ->whereIn('action_type', [
                'field_goal_made', 'field_goal_missed',
                'three_point_made', 'three_point_missed',
                'free_throw_made', 'free_throw_missed',
                'turnover'
            ])
            ->count();

        return $teamActions > 0 ? round(($playerActions / $teamActions) * 100, 1) : 0;
    }

    /**
     * Calculate Plus/Minus for a player.
     * Simplified - real plus/minus requires tracking when player is on court.
     */
    public function calculatePlusMinus(Player $player, Game $game): int
    {
        $isHome = $player->team_id === $game->home_team_id;
        $teamScore = $isHome ? $game->home_team_score : $game->away_team_score;
        $opponentScore = $isHome ? $game->away_team_score : $game->home_team_score;

        return $teamScore - $opponentScore;
    }

    /**
     * Calculate Effective Field Goal Percentage.
     * eFG% = (FGM + 0.5 × 3PM) / FGA
     */
    public function calculateEffectiveFGPercentage(array $stats): float
    {
        $fga = $stats['field_goals_attempted'] + $stats['three_points_attempted'];
        if ($fga === 0) {
            return 0;
        }

        $fgm = $stats['field_goals_made'] + $stats['three_points_made'];
        $threeMade = $stats['three_points_made'];

        return round((($fgm + (0.5 * $threeMade)) / $fga) * 100, 1);
    }

    /**
     * Calculate Assist to Turnover Ratio.
     */
    public function calculateAssistToTurnoverRatio(array $stats): float
    {
        return $stats['turnovers'] > 0 ?
            round($stats['assists'] / $stats['turnovers'], 1) :
            $stats['assists'];
    }

    /**
     * Calculate Player Impact Estimate (simplified).
     * PIE = Positive contributions - Negative contributions
     */
    public function calculatePlayerImpact(array $stats): float
    {
        $positive = $stats['total_points'] + $stats['total_rebounds'] + $stats['assists']
                   + $stats['steals'] + $stats['blocks'];
        $negative = $stats['turnovers'] + $stats['personal_fouls'];

        return round($positive - $negative, 1);
    }

    /**
     * Calculate Steal Percentage.
     * STL% = (STL × Team Minutes) / (Player Minutes × Opponent Possessions)
     * Simplified - would need opponent possessions data.
     */
    public function calculateStealPercentage(Player $player, Game $game): float
    {
        return 0; // Would need opponent possessions data
    }

    /**
     * Calculate Block Percentage.
     * BLK% = (BLK × Team Minutes) / (Player Minutes × Opponent 2PA)
     * Simplified - would need opponent shot attempts data.
     */
    public function calculateBlockPercentage(Player $player, Game $game): float
    {
        return 0; // Would need opponent shot attempts data
    }

    /**
     * Calculate Rebound Percentage.
     * REB% = Player Rebounds / Total Available Rebounds
     */
    public function calculateReboundPercentage(Player $player, Game $game): float
    {
        $playerRebs = GameAction::where('game_id', $game->id)
            ->where('player_id', $player->id)
            ->whereIn('action_type', ['rebound_offensive', 'rebound_defensive'])
            ->count();

        $totalRebs = GameAction::where('game_id', $game->id)
            ->whereIn('action_type', ['rebound_offensive', 'rebound_defensive'])
            ->count();

        return $totalRebs > 0 ? round(($playerRebs / $totalRebs) * 100, 1) : 0;
    }

    /**
     * Calculate Points Per Possession.
     * PPP = Points / Possessions
     */
    public function calculatePointsPerPossession(array $stats): float
    {
        $possessions = $stats['field_goals_attempted'] + $stats['three_points_attempted']
                      + ($stats['free_throws_attempted'] * 0.44) + $stats['turnovers'];

        return $possessions > 0 ? round($stats['total_points'] / $possessions, 2) : 0;
    }

    /**
     * Calculate Game Score (John Hollinger's metric).
     * GmSc = PTS + 0.4×FGM - 0.7×FGA - 0.4×(FTA-FTM) + 0.7×ORB + 0.3×DRB + STL + 0.7×AST + 0.7×BLK - 0.4×PF - TO
     */
    public function calculateGameScore(array $stats): float
    {
        return round(
            $stats['total_points'] +
            (0.4 * ($stats['field_goals_made'] + $stats['three_points_made'])) -
            (0.7 * ($stats['field_goals_attempted'] + $stats['three_points_attempted'] - $stats['field_goals_made'] - $stats['three_points_made'])) -
            (0.4 * ($stats['free_throws_attempted'] - $stats['free_throws_made'])) +
            (0.7 * $stats['rebounds_offensive']) +
            (0.3 * $stats['rebounds_defensive']) +
            $stats['steals'] +
            (0.7 * $stats['assists']) +
            (0.7 * $stats['blocks']) -
            (0.4 * $stats['personal_fouls']) -
            $stats['turnovers'], 1
        );
    }

    /**
     * Estimate possessions (simplified).
     * Possessions ≈ FGA + 0.44×FTA + TO
     */
    public function estimatePossessions(array $stats): float
    {
        return $stats['field_goals_attempted'] + ($stats['turnovers'] * 0.8) + ($stats['free_throws_attempted'] * 0.44);
    }

    /**
     * Calculate the Four Factors of Basketball Success.
     */
    public function calculateFourFactors(array $stats): array
    {
        return [
            'effective_fg_percentage' => $this->calculateTeamEffectiveFG($stats),
            'turnover_rate' => $this->calculateTurnoverRate($stats),
            'offensive_rebounding_percentage' => 0, // Would need opponent data
            'free_throw_rate' => $this->calculateFreeThrowRate($stats),
        ];
    }

    /**
     * Calculate Team Effective FG%.
     */
    public function calculateTeamEffectiveFG(array $stats): float
    {
        $fga = $stats['field_goals_attempted'];
        if ($fga === 0) {
            return 0;
        }

        $fgm = $stats['field_goals_made'];
        $threeMade = $stats['three_points_made'];

        return round((($fgm + (0.5 * $threeMade)) / $fga) * 100, 1);
    }

    /**
     * Calculate Turnover Rate.
     * TOV% = TOV / Possessions
     */
    public function calculateTurnoverRate(array $stats): float
    {
        $possessions = $this->estimatePossessions($stats);
        return $possessions > 0 ?
            round(($stats['turnovers'] / $possessions) * 100, 1) : 0;
    }

    /**
     * Calculate Free Throw Rate.
     * FTr = FTA / FGA
     */
    public function calculateFreeThrowRate(array $stats): float
    {
        $fga = $stats['field_goals_attempted'];
        return $fga > 0 ?
            round($stats['free_throws_attempted'] / $fga, 2) : 0;
    }

    /**
     * Calculate Offensive Rebounding Rate.
     * ORB% = ORB / (ORB + Opponent DRB)
     * Simplified - would need opponent data.
     */
    public function calculateOffensiveReboundingRate(Team $team, string $season): float
    {
        return 0; // Would need opponent data
    }
}
