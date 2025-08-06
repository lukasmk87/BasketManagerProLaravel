<?php

namespace App\Exports;

use App\Models\Team;
use App\Services\StatisticsService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Support\Collection;

class TeamStatsExport implements WithMultipleSheets
{
    public function __construct(
        private Team $team,
        private string $season,
        private StatisticsService $statisticsService
    ) {}

    public function sheets(): array
    {
        return [
            'Team Stats' => new TeamSeasonStatsSheet($this->team, $this->season, $this->statisticsService),
            'Player Stats' => new TeamPlayerStatsSheet($this->team, $this->season, $this->statisticsService),
            'Game Log' => new TeamGameLogSheet($this->team, $this->season, $this->statisticsService),
            'Four Factors' => new TeamFourFactorsSheet($this->team, $this->season, $this->statisticsService),
        ];
    }
}

class TeamSeasonStatsSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private Team $team,
        private string $season,
        private StatisticsService $statisticsService
    ) {}

    public function collection()
    {
        $stats = $this->statisticsService->getTeamSeasonStats($this->team, $this->season);
        $analytics = $this->statisticsService->getTeamAnalytics($this->team, $this->season);

        return collect([
            [
                'Team' => $this->team->name,
                'Season' => $this->season,
                'League' => $this->team->league ?? '',
                'Games' => $stats['games_played'] ?? 0,
                'Wins' => $stats['wins'] ?? 0,
                'Losses' => $stats['losses'] ?? 0,
                'Win%' => ($stats['win_percentage'] ?? 0) . '%',
                'Points For' => $stats['points_for'] ?? 0,
                'Points Against' => $stats['points_against'] ?? 0,
                'PPG' => $stats['avg_points_for'] ?? 0,
                'Opp PPG' => $stats['avg_points_against'] ?? 0,
                'Diff' => ($stats['avg_points_for'] ?? 0) - ($stats['avg_points_against'] ?? 0),
                'FGM' => $stats['field_goals_made'] ?? 0,
                'FGA' => $stats['field_goals_attempted'] ?? 0,
                'FG%' => ($stats['field_goal_percentage'] ?? 0) . '%',
                '3PM' => $stats['three_points_made'] ?? 0,
                '3PA' => $stats['three_points_attempted'] ?? 0,
                '3P%' => ($stats['three_point_percentage'] ?? 0) . '%',
                'FTM' => $stats['free_throws_made'] ?? 0,
                'FTA' => $stats['free_throws_attempted'] ?? 0,
                'FT%' => ($stats['free_throw_percentage'] ?? 0) . '%',
                'Rebounds' => $stats['total_rebounds'] ?? 0,
                'RPG' => $stats['avg_rebounds'] ?? 0,
                'Assists' => $stats['assists'] ?? 0,
                'APG' => $stats['avg_assists'] ?? 0,
                'Steals' => $stats['steals'] ?? 0,
                'Blocks' => $stats['blocks'] ?? 0,
                'Turnovers' => $stats['turnovers'] ?? 0,
                'Fouls' => $stats['personal_fouls'] ?? 0,
                'Off Rating' => $stats['offensive_rating'] ?? 0,
                'Def Rating' => $stats['defensive_rating'] ?? 0,
                'Net Rating' => $stats['net_rating'] ?? 0,
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'Team', 'Season', 'League', 'GP', 'W', 'L', 'Win%', 'PF', 'PA', 'PPG', 'Opp PPG', 'Diff',
            'FGM', 'FGA', 'FG%', '3PM', '3PA', '3P%', 'FTM', 'FTA', 'FT%', 'Reb', 'RPG',
            'Ast', 'APG', 'Stl', 'Blk', 'TO', 'Fouls', 'Off Rtg', 'Def Rtg', 'Net Rtg'
        ];
    }

    public function title(): string
    {
        return 'Team Stats';
    }
}

class TeamPlayerStatsSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private Team $team,
        private string $season,
        private StatisticsService $statisticsService
    ) {}

    public function collection()
    {
        return $this->team->players()
            ->whereHas('gameActions', function($query) {
                $query->whereHas('game', function($q) {
                    $q->where('season', $this->season)->where('status', 'finished');
                });
            })
            ->get()
            ->map(function($player) {
                $stats = $this->statisticsService->getPlayerSeasonStats($player, $this->season);
                
                return [
                    'Player' => $player->name,
                    'Jersey' => $player->jersey_number ?? '',
                    'Position' => $player->position ?? '',
                    'GP' => $stats['games_played'] ?? 0,
                    'Points' => $stats['total_points'] ?? 0,
                    'PPG' => $stats['avg_points'] ?? 0,
                    'FG%' => ($stats['field_goal_percentage'] ?? 0) . '%',
                    '3P%' => ($stats['three_point_percentage'] ?? 0) . '%',
                    'FT%' => ($stats['free_throw_percentage'] ?? 0) . '%',
                    'Reb' => $stats['total_rebounds'] ?? 0,
                    'RPG' => $stats['avg_rebounds'] ?? 0,
                    'Ast' => $stats['assists'] ?? 0,
                    'APG' => $stats['avg_assists'] ?? 0,
                    'Stl' => $stats['steals'] ?? 0,
                    'Blk' => $stats['blocks'] ?? 0,
                    'TO' => $stats['turnovers'] ?? 0,
                    'Fouls' => $stats['personal_fouls'] ?? 0,
                    'TS%' => ($stats['true_shooting_percentage'] ?? 0) . '%',
                    'PER' => $stats['player_efficiency_rating'] ?? 0,
                ];
            })
            ->sortByDesc('avg_points');
    }

    public function headings(): array
    {
        return [
            'Player', 'Jersey', 'Position', 'GP', 'Points', 'PPG', 'FG%', '3P%', 'FT%',
            'Reb', 'RPG', 'Ast', 'APG', 'Stl', 'Blk', 'TO', 'Fouls', 'TS%', 'PER'
        ];
    }

    public function title(): string
    {
        return 'Player Stats';
    }
}

class TeamGameLogSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private Team $team,
        private string $season,
        private StatisticsService $statisticsService
    ) {}

    public function collection()
    {
        $games = \App\Models\Game::where('season', $this->season)
            ->where('status', 'finished')
            ->where(function($query) {
                $query->where('home_team_id', $this->team->id)
                      ->orWhere('away_team_id', $this->team->id);
            })
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('scheduled_at', 'desc')
            ->get();

        return $games->map(function($game) {
            $isHome = $game->home_team_id === $this->team->id;
            $opponent = $isHome ? $game->awayTeam : $game->homeTeam;
            $teamScore = $isHome ? $game->home_team_score : $game->away_team_score;
            $opponentScore = $isHome ? $game->away_team_score : $game->home_team_score;
            $result = $teamScore > $opponentScore ? 'W' : 'L';
            $margin = $teamScore - $opponentScore;

            $stats = $this->statisticsService->getTeamGameStats($this->team, $game);

            return [
                'Date' => $game->scheduled_at->format('Y-m-d'),
                'Opponent' => $opponent->name,
                'H/A' => $isHome ? 'H' : 'A',
                'Result' => $result,
                'Score' => $teamScore . '-' . $opponentScore,
                'Margin' => $margin > 0 ? '+' . $margin : $margin,
                'FGM-A' => ($stats['field_goals_made'] ?? 0) . '-' . ($stats['field_goals_attempted'] ?? 0),
                'FG%' => ($stats['field_goal_percentage'] ?? 0) . '%',
                '3PM-A' => ($stats['three_points_made'] ?? 0) . '-' . ($stats['three_points_attempted'] ?? 0),
                '3P%' => ($stats['three_point_percentage'] ?? 0) . '%',
                'FTM-A' => ($stats['free_throws_made'] ?? 0) . '-' . ($stats['free_throws_attempted'] ?? 0),
                'FT%' => ($stats['free_throw_percentage'] ?? 0) . '%',
                'Reb' => $stats['total_rebounds'] ?? 0,
                'Ast' => $stats['assists'] ?? 0,
                'Stl' => $stats['steals'] ?? 0,
                'Blk' => $stats['blocks'] ?? 0,
                'TO' => $stats['turnovers'] ?? 0,
                'Fouls' => $stats['personal_fouls'] ?? 0,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Date', 'Opponent', 'H/A', 'Result', 'Score', 'Margin', 'FGM-A', 'FG%',
            '3PM-A', '3P%', 'FTM-A', 'FT%', 'Reb', 'Ast', 'Stl', 'Blk', 'TO', 'Fouls'
        ];
    }

    public function title(): string
    {
        return 'Game Log';
    }
}

class TeamFourFactorsSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private Team $team,
        private string $season,
        private StatisticsService $statisticsService
    ) {}

    public function collection()
    {
        $analytics = $this->statisticsService->getTeamAnalytics($this->team, $this->season);
        $fourFactors = $analytics['four_factors'] ?? [];

        return collect([
            [
                'Team' => $this->team->name,
                'Season' => $this->season,
                'Effective FG%' => ($fourFactors['effective_fg_percentage'] ?? 0) . '%',
                'Turnover Rate' => ($fourFactors['turnover_rate'] ?? 0) . '%',
                'Off Rebounding%' => ($fourFactors['offensive_rebounding_percentage'] ?? 0) . '%',
                'Free Throw Rate' => $fourFactors['free_throw_rate'] ?? 0,
            ]
        ]);
    }

    public function headings(): array
    {
        return ['Team', 'Season', 'Effective FG%', 'Turnover Rate', 'Off Rebounding%', 'Free Throw Rate'];
    }

    public function title(): string
    {
        return 'Four Factors';
    }
}