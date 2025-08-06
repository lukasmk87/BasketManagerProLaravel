<?php

namespace App\Exports;

use App\Models\Game;
use App\Services\StatisticsService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Support\Collection;

class GameStatsExport implements WithMultipleSheets
{
    public function __construct(
        private Game $game,
        private StatisticsService $statisticsService
    ) {}

    public function sheets(): array
    {
        return [
            'Game Summary' => new GameSummarySheet($this->game, $this->statisticsService),
            'Home Team Players' => new PlayerStatsSheet($this->game, $this->game->homeTeam, $this->statisticsService),
            'Away Team Players' => new PlayerStatsSheet($this->game, $this->game->awayTeam, $this->statisticsService),
            'Play by Play' => new PlayByPlaySheet($this->game),
        ];
    }
}

class GameSummarySheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private Game $game,
        private StatisticsService $statisticsService
    ) {}

    public function collection()
    {
        $homeStats = $this->statisticsService->getTeamGameStats($this->game->homeTeam, $this->game);
        $awayStats = $this->statisticsService->getTeamGameStats($this->game->awayTeam, $this->game);

        return collect([
            [
                'Team' => $this->game->homeTeam->name . ' (Home)',
                'Final Score' => $homeStats['final_score'] ?? $this->game->home_team_score,
                'Field Goals' => $homeStats['field_goals_made'] . '/' . $homeStats['field_goals_attempted'],
                'FG%' => $homeStats['field_goals_attempted'] > 0 ? 
                    round(($homeStats['field_goals_made'] / $homeStats['field_goals_attempted']) * 100, 1) . '%' : '0%',
                '3-Pointers' => $homeStats['three_points_made'] . '/' . $homeStats['three_points_attempted'],
                '3P%' => $homeStats['three_points_attempted'] > 0 ? 
                    round(($homeStats['three_points_made'] / $homeStats['three_points_attempted']) * 100, 1) . '%' : '0%',
                'Free Throws' => $homeStats['free_throws_made'] . '/' . $homeStats['free_throws_attempted'],
                'FT%' => $homeStats['free_throws_attempted'] > 0 ? 
                    round(($homeStats['free_throws_made'] / $homeStats['free_throws_attempted']) * 100, 1) . '%' : '0%',
                'Rebounds' => $homeStats['total_rebounds'],
                'Assists' => $homeStats['assists'],
                'Steals' => $homeStats['steals'],
                'Blocks' => $homeStats['blocks'],
                'Turnovers' => $homeStats['turnovers'],
                'Fouls' => $homeStats['personal_fouls'],
            ],
            [
                'Team' => $this->game->awayTeam->name . ' (Away)',
                'Final Score' => $awayStats['final_score'] ?? $this->game->away_team_score,
                'Field Goals' => $awayStats['field_goals_made'] . '/' . $awayStats['field_goals_attempted'],
                'FG%' => $awayStats['field_goals_attempted'] > 0 ? 
                    round(($awayStats['field_goals_made'] / $awayStats['field_goals_attempted']) * 100, 1) . '%' : '0%',
                '3-Pointers' => $awayStats['three_points_made'] . '/' . $awayStats['three_points_attempted'],
                '3P%' => $awayStats['three_points_attempted'] > 0 ? 
                    round(($awayStats['three_points_made'] / $awayStats['three_points_attempted']) * 100, 1) . '%' : '0%',
                'Free Throws' => $awayStats['free_throws_made'] . '/' . $awayStats['free_throws_attempted'],
                'FT%' => $awayStats['free_throws_attempted'] > 0 ? 
                    round(($awayStats['free_throws_made'] / $awayStats['free_throws_attempted']) * 100, 1) . '%' : '0%',
                'Rebounds' => $awayStats['total_rebounds'],
                'Assists' => $awayStats['assists'],
                'Steals' => $awayStats['steals'],
                'Blocks' => $awayStats['blocks'],
                'Turnovers' => $awayStats['turnovers'],
                'Fouls' => $awayStats['personal_fouls'],
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'Team',
            'Final Score',
            'Field Goals',
            'FG%',
            '3-Pointers',
            '3P%',
            'Free Throws',
            'FT%',
            'Rebounds',
            'Assists',
            'Steals',
            'Blocks',
            'Turnovers',
            'Fouls'
        ];
    }

    public function title(): string
    {
        return 'Game Summary';
    }
}

class PlayerStatsSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private Game $game,
        private $team,
        private StatisticsService $statisticsService
    ) {}

    public function collection()
    {
        $playerStats = collect();

        foreach ($this->team->players as $player) {
            $stats = $this->statisticsService->getPlayerGameStats($player, $this->game);
            
            // Only include players who played
            if ($stats['total_points'] > 0 || $stats['total_rebounds'] > 0 || $stats['assists'] > 0) {
                $playerStats->push([
                    'Player' => $player->name,
                    'Jersey' => $player->jersey_number ?? '',
                    'Points' => $stats['total_points'],
                    'FGM' => $stats['field_goals_made'],
                    'FGA' => $stats['field_goals_attempted'],
                    'FG%' => $stats['field_goal_percentage'] . '%',
                    '3PM' => $stats['three_points_made'],
                    '3PA' => $stats['three_points_attempted'],
                    '3P%' => $stats['three_point_percentage'] . '%',
                    'FTM' => $stats['free_throws_made'],
                    'FTA' => $stats['free_throws_attempted'],
                    'FT%' => $stats['free_throw_percentage'] . '%',
                    'OReb' => $stats['rebounds_offensive'],
                    'DReb' => $stats['rebounds_defensive'],
                    'Reb' => $stats['total_rebounds'],
                    'Ast' => $stats['assists'],
                    'Stl' => $stats['steals'],
                    'Blk' => $stats['blocks'],
                    'TO' => $stats['turnovers'],
                    'Fouls' => $stats['personal_fouls'],
                    'TS%' => $stats['true_shooting_percentage'] . '%',
                    'PER' => $stats['player_efficiency_rating'],
                ]);
            }
        }

        return $playerStats;
    }

    public function headings(): array
    {
        return [
            'Player', 'Jersey', 'Points', 'FGM', 'FGA', 'FG%', '3PM', '3PA', '3P%',
            'FTM', 'FTA', 'FT%', 'OReb', 'DReb', 'Reb', 'Ast', 'Stl', 'Blk', 'TO', 'Fouls', 'TS%', 'PER'
        ];
    }

    public function title(): string
    {
        return $this->team->name . ' Players';
    }
}

class PlayByPlaySheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private Game $game
    ) {}

    public function collection()
    {
        return $this->game->gameActions()
            ->with(['player', 'assistedByPlayer'])
            ->orderBy('recorded_at')
            ->get()
            ->map(function ($action) {
                return [
                    'Time' => "Q{$action->period} {$this->formatTime($action->time_remaining)}",
                    'Team' => $action->player->team->name ?? 'Unknown',
                    'Player' => $action->player->name ?? 'Unknown',
                    'Action' => $this->formatActionType($action->action_type),
                    'Points' => $action->points > 0 ? $action->points : '',
                    'Assist' => $action->assistedByPlayer?->name ?? '',
                    'Description' => $action->description ?? '',
                ];
            });
    }

    public function headings(): array
    {
        return ['Time', 'Team', 'Player', 'Action', 'Points', 'Assist', 'Description'];
    }

    public function title(): string
    {
        return 'Play by Play';
    }

    private function formatTime($timeString): string
    {
        if (!$timeString) return '00:00';
        $parts = explode(':', $timeString);
        return isset($parts[1], $parts[2]) ? $parts[1] . ':' . $parts[2] : $timeString;
    }

    private function formatActionType(string $actionType): string
    {
        $translations = [
            'field_goal_made' => '2P Made',
            'field_goal_missed' => '2P Missed',
            'three_point_made' => '3P Made',
            'three_point_missed' => '3P Missed',
            'free_throw_made' => 'FT Made',
            'free_throw_missed' => 'FT Missed',
            'rebound_offensive' => 'Off. Rebound',
            'rebound_defensive' => 'Def. Rebound',
            'assist' => 'Assist',
            'steal' => 'Steal',
            'block' => 'Block',
            'turnover' => 'Turnover',
            'foul_personal' => 'Personal Foul',
            'foul_technical' => 'Technical Foul',
            'substitution_in' => 'Sub In',
            'substitution_out' => 'Sub Out',
        ];

        return $translations[$actionType] ?? $actionType;
    }
}