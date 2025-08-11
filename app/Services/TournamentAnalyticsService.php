<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\TournamentTeam;
use App\Models\TournamentBracket;
use App\Models\User;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class TournamentAnalyticsService
{
    public function getComprehensiveTournamentReport(Tournament $tournament): array
    {
        return [
            'tournament_overview' => $this->getTournamentOverview($tournament),
            'participation_analysis' => $this->getParticipationAnalysis($tournament),
            'performance_analytics' => $this->getPerformanceAnalytics($tournament),
            'game_analytics' => $this->getGameAnalytics($tournament),
            'progression_analysis' => $this->getProgressionAnalysis($tournament),
            'competitive_balance' => $this->getCompetitiveBalanceAnalysis($tournament),
            'time_analysis' => $this->getTimeAnalysis($tournament),
            'venue_analytics' => $this->getVenueAnalytics($tournament),
            'official_performance' => $this->getOfficialPerformance($tournament),
        ];
    }

    public function getTournamentOverview(Tournament $tournament): array
    {
        $teams = $tournament->tournamentTeams()->where('status', 'approved')->get();
        $brackets = $tournament->brackets()->get();
        $completedBrackets = $brackets->where('status', 'completed');

        return [
            'basic_info' => [
                'name' => $tournament->name,
                'type' => $tournament->type,
                'category' => $tournament->category,
                'gender' => $tournament->gender,
                'status' => $tournament->status,
                'duration_days' => $tournament->duration,
            ],
            'participation' => [
                'registered_teams' => $tournament->registered_teams,
                'min_teams' => $tournament->min_teams,
                'max_teams' => $tournament->max_teams,
                'registration_rate' => ($tournament->registered_teams / $tournament->max_teams) * 100,
                'approved_teams' => $teams->count(),
            ],
            'progress' => [
                'total_games' => $brackets->count(),
                'completed_games' => $completedBrackets->count(),
                'completion_percentage' => $brackets->count() > 0 ? 
                    ($completedBrackets->count() / $brackets->count()) * 100 : 0,
                'games_remaining' => $brackets->where('status', 'pending')->count() + 
                                   $brackets->where('status', 'scheduled')->count(),
            ],
            'timeline' => [
                'registration_period' => $tournament->registration_start->diffInDays($tournament->registration_end),
                'tournament_period' => $tournament->start_date->diffInDays($tournament->end_date) + 1,
                'days_since_start' => $tournament->start_date->isPast() ? 
                    $tournament->start_date->diffInDays(now()) : null,
            ],
        ];
    }

    public function getParticipationAnalysis(Tournament $tournament): array
    {
        $teams = $tournament->tournamentTeams()->get();
        $approvedTeams = $teams->where('status', 'approved');

        return [
            'registration_timeline' => $this->getRegistrationTimeline($teams),
            'team_distribution' => [
                'by_status' => $teams->groupBy('status')->map->count(),
                'by_registration_date' => $this->groupByRegistrationDate($teams),
                'approval_rate' => $teams->count() > 0 ? 
                    ($approvedTeams->count() / $teams->count()) * 100 : 0,
            ],
            'geographic_distribution' => $this->getGeographicDistribution($approvedTeams),
            'seed_distribution' => $approvedTeams->whereNotNull('seed')
                                                ->groupBy('seed')
                                                ->map->count(),
            'withdrawal_analysis' => $this->getWithdrawalAnalysis($teams),
        ];
    }

    public function getPerformanceAnalytics(Tournament $tournament): array
    {
        $teams = $tournament->tournamentTeams()->where('status', 'approved')->get();

        return [
            'team_performance' => [
                'standings' => $this->getDetailedStandings($teams),
                'performance_metrics' => $this->getPerformanceMetrics($teams),
                'consistency_analysis' => $this->getConsistencyAnalysis($teams),
            ],
            'statistical_leaders' => [
                'highest_scoring' => $teams->sortByDesc('points_for')->take(5),
                'best_defense' => $teams->sortBy('points_against')->take(5),
                'best_differential' => $teams->sortByDesc('point_differential')->take(5),
                'most_wins' => $teams->sortByDesc('wins')->take(5),
            ],
            'performance_distribution' => [
                'win_percentage_brackets' => $this->getWinPercentageBrackets($teams),
                'scoring_distribution' => $this->getScoringDistribution($teams),
                'defensive_distribution' => $this->getDefensiveDistribution($teams),
            ],
        ];
    }

    public function getGameAnalytics(Tournament $tournament): array
    {
        $brackets = $tournament->brackets()->where('status', 'completed')->get();

        return [
            'scoring_analysis' => [
                'total_points' => $brackets->sum(fn($b) => ($b->team1_score ?? 0) + ($b->team2_score ?? 0)),
                'average_points_per_game' => $brackets->avg(fn($b) => ($b->team1_score ?? 0) + ($b->team2_score ?? 0)),
                'highest_scoring_game' => $brackets->max(fn($b) => ($b->team1_score ?? 0) + ($b->team2_score ?? 0)),
                'lowest_scoring_game' => $brackets->min(fn($b) => ($b->team1_score ?? 0) + ($b->team2_score ?? 0)),
                'scoring_trends_by_round' => $this->getScoringTrendsByRound($brackets),
            ],
            'game_competitiveness' => [
                'close_games' => $brackets->filter(fn($b) => abs(($b->team1_score ?? 0) - ($b->team2_score ?? 0)) <= 5)->count(),
                'blowouts' => $brackets->filter(fn($b) => abs(($b->team1_score ?? 0) - ($b->team2_score ?? 0)) >= 20)->count(),
                'overtime_games' => $brackets->where('overtime', true)->count(),
                'average_margin' => $brackets->avg(fn($b) => abs(($b->team1_score ?? 0) - ($b->team2_score ?? 0))),
                'margin_distribution' => $this->getMarginDistribution($brackets),
            ],
            'duration_analysis' => [
                'average_duration' => $brackets->avg('actual_duration'),
                'longest_game' => $brackets->max('actual_duration'),
                'shortest_game' => $brackets->min('actual_duration'),
                'duration_by_round' => $this->getDurationByRound($brackets),
                'overtime_impact' => $this->getOvertimeImpact($brackets),
            ],
        ];
    }

    public function getProgressionAnalysis(Tournament $tournament): array
    {
        $teams = $tournament->tournamentTeams()->where('status', 'approved')->get();
        
        return [
            'seeding_performance' => $this->getSeedingPerformance($teams),
            'upset_analysis' => $this->getUpsetAnalysis($tournament),
            'elimination_analysis' => [
                'by_round' => $teams->whereNotNull('elimination_round')
                                   ->groupBy('elimination_round')
                                   ->map->count(),
                'seed_survival' => $this->getSeedSurvivalRates($teams),
                'early_exits' => $this->getEarlyExits($teams),
            ],
            'advancement_patterns' => $this->getAdvancementPatterns($tournament),
        ];
    }

    public function getCompetitiveBalanceAnalysis(Tournament $tournament): array
    {
        $teams = $tournament->tournamentTeams()->where('status', 'approved')->get();
        $brackets = $tournament->brackets()->where('status', 'completed')->get();

        return [
            'parity_metrics' => [
                'win_percentage_std_dev' => $this->calculateWinPercentageStdDev($teams),
                'point_differential_range' => $teams->max('point_differential') - $teams->min('point_differential'),
                'competitive_balance_index' => $this->calculateCompetitiveBalanceIndex($teams),
            ],
            'predictability_analysis' => [
                'seed_correlation' => $this->calculateSeedCorrelation($teams),
                'upset_frequency' => $this->calculateUpsetFrequency($brackets),
                'favorite_success_rate' => $this->calculateFavoriteSuccessRate($brackets),
            ],
            'round_analysis' => [
                'competitiveness_by_round' => $this->getCompetitivenessByRound($brackets),
                'blowout_frequency_by_round' => $this->getBlowoutFrequencyByRound($brackets),
            ],
        ];
    }

    public function getTimeAnalysis(Tournament $tournament): array
    {
        $brackets = $tournament->brackets()->whereNotNull('scheduled_at')->get();
        
        return [
            'scheduling_analysis' => [
                'games_per_day' => $this->getGamesPerDay($brackets),
                'peak_game_times' => $this->getPeakGameTimes($brackets),
                'scheduling_efficiency' => $this->getSchedulingEfficiency($brackets),
            ],
            'duration_patterns' => [
                'by_time_of_day' => $this->getDurationByTimeOfDay($brackets),
                'by_day_of_tournament' => $this->getDurationByTournamentDay($brackets),
                'by_importance' => $this->getDurationByImportance($brackets),
            ],
            'delay_analysis' => $this->getDelayAnalysis($brackets),
        ];
    }

    public function getVenueAnalytics(Tournament $tournament): array
    {
        $brackets = $tournament->brackets()->whereNotNull('venue')->get();
        
        return [
            'venue_utilization' => $brackets->groupBy('venue')->map->count(),
            'court_utilization' => $brackets->whereNotNull('court')->groupBy('court')->map->count(),
            'venue_performance' => $this->getVenuePerformance($brackets),
            'capacity_analysis' => $this->getVenueCapacityAnalysis($tournament),
        ];
    }

    public function getOfficialPerformance(Tournament $tournament): array
    {
        $officials = $tournament->officials()->where('status', 'confirmed')->get();
        
        return [
            'assignment_distribution' => $officials->groupBy('role')->map->count(),
            'workload_analysis' => [
                'average_games_per_official' => $officials->avg('games_assigned'),
                'utilization_rate' => $officials->avg('completion_rate'),
                'performance_ratings' => $officials->whereNotNull('performance_rating')->avg('performance_rating'),
            ],
            'role_analysis' => $this->getRolePerformanceAnalysis($officials),
            'experience_correlation' => $this->getExperienceCorrelation($officials),
        ];
    }

    // Helper Methods for Complex Calculations
    protected function getRegistrationTimeline(Collection $teams): array
    {
        return $teams->groupBy(function ($team) {
            return $team->registered_at->toDateString();
        })->map->count()->toArray();
    }

    protected function groupByRegistrationDate(Collection $teams): array
    {
        return $teams->groupBy(function ($team) {
            return $team->registered_at->format('Y-m-d');
        })->map->count()->toArray();
    }

    protected function getGeographicDistribution(Collection $teams): array
    {
        // This would analyze team locations if available
        return $teams->groupBy(function ($team) {
            return $team->team->club->city ?? 'Unknown';
        })->map->count()->toArray();
    }

    protected function getWithdrawalAnalysis(Collection $teams): array
    {
        $withdrawn = $teams->where('status', 'withdrawn');
        
        return [
            'total_withdrawals' => $withdrawn->count(),
            'withdrawal_rate' => $teams->count() > 0 ? ($withdrawn->count() / $teams->count()) * 100 : 0,
            'withdrawal_reasons' => $withdrawn->whereNotNull('status_reason')
                                            ->groupBy('status_reason')
                                            ->map->count(),
            'withdrawal_timeline' => $withdrawn->groupBy(function ($team) {
                return $team->status_updated_at?->toDateString() ?? 'Unknown';
            })->map->count(),
        ];
    }

    protected function getDetailedStandings(Collection $teams): array
    {
        return $teams->sortByDesc([
            ['tournament_points', 'desc'],
            ['point_differential', 'desc'],
            ['points_for', 'desc'],
        ])->map(function ($team, $index) {
            return [
                'position' => $index + 1,
                'team_name' => $team->team->name,
                'games_played' => $team->games_played,
                'wins' => $team->wins,
                'losses' => $team->losses,
                'win_percentage' => $team->win_percentage,
                'points_for' => $team->points_for,
                'points_against' => $team->points_against,
                'point_differential' => $team->point_differential,
                'tournament_points' => $team->tournament_points,
            ];
        })->values()->toArray();
    }

    protected function getPerformanceMetrics(Collection $teams): array
    {
        return [
            'offensive_efficiency' => $teams->avg('average_points_for'),
            'defensive_efficiency' => $teams->avg('average_points_against'),
            'net_efficiency' => $teams->avg(fn($t) => $t->average_points_for - $t->average_points_against),
            'win_percentage_average' => $teams->avg('win_percentage'),
            'consistency_index' => $this->calculateConsistencyIndex($teams),
        ];
    }

    protected function getConsistencyAnalysis(Collection $teams): array
    {
        // This would analyze game-by-game performance variance
        return [
            'most_consistent_offense' => $teams->sortBy('offensive_variance')->first(),
            'most_consistent_defense' => $teams->sortBy('defensive_variance')->first(),
            'most_volatile' => $teams->sortByDesc('performance_variance')->first(),
        ];
    }

    protected function getWinPercentageBrackets(Collection $teams): array
    {
        return [
            '90-100%' => $teams->filter(fn($t) => $t->win_percentage >= 90)->count(),
            '75-89%' => $teams->filter(fn($t) => $t->win_percentage >= 75 && $t->win_percentage < 90)->count(),
            '50-74%' => $teams->filter(fn($t) => $t->win_percentage >= 50 && $t->win_percentage < 75)->count(),
            '25-49%' => $teams->filter(fn($t) => $t->win_percentage >= 25 && $t->win_percentage < 50)->count(),
            '0-24%' => $teams->filter(fn($t) => $t->win_percentage < 25)->count(),
        ];
    }

    protected function getScoringDistribution(Collection $teams): array
    {
        $avgScoring = $teams->avg('average_points_for');
        
        return [
            'high_scoring' => $teams->filter(fn($t) => $t->average_points_for > $avgScoring * 1.1)->count(),
            'average_scoring' => $teams->filter(fn($t) => 
                $t->average_points_for >= $avgScoring * 0.9 && 
                $t->average_points_for <= $avgScoring * 1.1
            )->count(),
            'low_scoring' => $teams->filter(fn($t) => $t->average_points_for < $avgScoring * 0.9)->count(),
        ];
    }

    protected function getDefensiveDistribution(Collection $teams): array
    {
        $avgDefense = $teams->avg('average_points_against');
        
        return [
            'elite_defense' => $teams->filter(fn($t) => $t->average_points_against < $avgDefense * 0.9)->count(),
            'average_defense' => $teams->filter(fn($t) => 
                $t->average_points_against >= $avgDefense * 0.9 && 
                $t->average_points_against <= $avgDefense * 1.1
            )->count(),
            'poor_defense' => $teams->filter(fn($t) => $t->average_points_against > $avgDefense * 1.1)->count(),
        ];
    }

    protected function getScoringTrendsByRound(Collection $brackets): array
    {
        return $brackets->groupBy('round')->map(function ($roundBrackets) {
            return $roundBrackets->avg(fn($b) => ($b->team1_score ?? 0) + ($b->team2_score ?? 0));
        })->toArray();
    }

    protected function getMarginDistribution(Collection $brackets): array
    {
        return [
            '1-5 points' => $brackets->filter(fn($b) => 
                abs(($b->team1_score ?? 0) - ($b->team2_score ?? 0)) <= 5 &&
                abs(($b->team1_score ?? 0) - ($b->team2_score ?? 0)) >= 1
            )->count(),
            '6-10 points' => $brackets->filter(fn($b) => 
                abs(($b->team1_score ?? 0) - ($b->team2_score ?? 0)) <= 10 &&
                abs(($b->team1_score ?? 0) - ($b->team2_score ?? 0)) >= 6
            )->count(),
            '11-20 points' => $brackets->filter(fn($b) => 
                abs(($b->team1_score ?? 0) - ($b->team2_score ?? 0)) <= 20 &&
                abs(($b->team1_score ?? 0) - ($b->team2_score ?? 0)) >= 11
            )->count(),
            '20+ points' => $brackets->filter(fn($b) => 
                abs(($b->team1_score ?? 0) - ($b->team2_score ?? 0)) > 20
            )->count(),
        ];
    }

    protected function getDurationByRound(Collection $brackets): array
    {
        return $brackets->groupBy('round')->map(function ($roundBrackets) {
            return $roundBrackets->avg('actual_duration');
        })->toArray();
    }

    protected function getOvertimeImpact(Collection $brackets): array
    {
        $overtimeGames = $brackets->where('overtime', true);
        $regularGames = $brackets->where('overtime', false);
        
        return [
            'overtime_games' => $overtimeGames->count(),
            'overtime_percentage' => $brackets->count() > 0 ? 
                ($overtimeGames->count() / $brackets->count()) * 100 : 0,
            'average_duration_regular' => $regularGames->avg('actual_duration'),
            'average_duration_overtime' => $overtimeGames->avg('actual_duration'),
            'duration_difference' => $overtimeGames->avg('actual_duration') - $regularGames->avg('actual_duration'),
        ];
    }

    // Additional helper methods would continue here...
    // For brevity, I'm showing the structure and key calculations

    protected function calculateCompetitiveBalanceIndex(Collection $teams): float
    {
        $winPercentages = $teams->pluck('win_percentage');
        $standardDeviation = $this->calculateStandardDeviation($winPercentages);
        $maxPossibleStdDev = 50; // Maximum possible std dev for win percentages
        
        return (1 - ($standardDeviation / $maxPossibleStdDev)) * 100;
    }

    protected function calculateStandardDeviation(Collection $values): float
    {
        $mean = $values->avg();
        $squaredDifferences = $values->map(fn($value) => pow($value - $mean, 2));
        $variance = $squaredDifferences->avg();
        
        return sqrt($variance);
    }

    protected function calculateWinPercentageStdDev(Collection $teams): float
    {
        return $this->calculateStandardDeviation($teams->pluck('win_percentage'));
    }

    // Placeholder methods for complex calculations
    protected function getSeedingPerformance(Collection $teams): array { return []; }
    protected function getUpsetAnalysis(Tournament $tournament): array { return []; }
    protected function getSeedSurvivalRates(Collection $teams): array { return []; }
    protected function getEarlyExits(Collection $teams): array { return []; }
    protected function getAdvancementPatterns(Tournament $tournament): array { return []; }
    protected function calculateSeedCorrelation(Collection $teams): float { return 0.0; }
    protected function calculateUpsetFrequency(Collection $brackets): float { return 0.0; }
    protected function calculateFavoriteSuccessRate(Collection $brackets): float { return 0.0; }
    protected function getCompetitivenessByRound(Collection $brackets): array { return []; }
    protected function getBlowoutFrequencyByRound(Collection $brackets): array { return []; }
    protected function getGamesPerDay(Collection $brackets): array { return []; }
    protected function getPeakGameTimes(Collection $brackets): array { return []; }
    protected function getSchedulingEfficiency(Collection $brackets): array { return []; }
    protected function getDurationByTimeOfDay(Collection $brackets): array { return []; }
    protected function getDurationByTournamentDay(Collection $brackets): array { return []; }
    protected function getDurationByImportance(Collection $brackets): array { return []; }
    protected function getDelayAnalysis(Collection $brackets): array { return []; }
    protected function getVenuePerformance(Collection $brackets): array { return []; }
    protected function getVenueCapacityAnalysis(Tournament $tournament): array { return []; }
    protected function getRolePerformanceAnalysis(Collection $officials): array { return []; }
    protected function getExperienceCorrelation(Collection $officials): array { return []; }
    protected function calculateConsistencyIndex(Collection $teams): float { return 0.0; }
}