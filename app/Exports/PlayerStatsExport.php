<?php

namespace App\Exports;

use App\Models\Player;
use App\Services\StatisticsService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Illuminate\Support\Collection;

class PlayerStatsExport implements WithMultipleSheets
{
    public function __construct(
        private Player $player,
        private string $season,
        private StatisticsService $statisticsService
    ) {}

    public function sheets(): array
    {
        return [
            'Season Stats' => new PlayerSeasonStatsSheet($this->player, $this->season, $this->statisticsService),
            'Game Log' => new PlayerGameLogSheet($this->player, $this->season, $this->statisticsService),
        ];
    }
}

class PlayerSeasonStatsSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private Player $player,
        private string $season,
        private StatisticsService $statisticsService
    ) {}

    public function collection()
    {
        $stats = $this->statisticsService->getPlayerSeasonStats($this->player, $this->season);

        return collect([
            [
                'Player' => $this->player->name,
                'Team' => $this->player->team->name ?? 'No Team',
                'Jersey' => $this->player->jersey_number ?? '',
                'Season' => $this->season,
                'Games Played' => $stats['games_played'] ?? 0,
                'Points' => $stats['total_points'] ?? 0,
                'PPG' => $stats['avg_points'] ?? 0,
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
                'OReb' => $stats['rebounds_offensive'] ?? 0,
                'DReb' => $stats['rebounds_defensive'] ?? 0,
                'Assists' => $stats['assists'] ?? 0,
                'APG' => $stats['avg_assists'] ?? 0,
                'Steals' => $stats['steals'] ?? 0,
                'SPG' => $stats['avg_steals'] ?? 0,
                'Blocks' => $stats['blocks'] ?? 0,
                'BPG' => $stats['avg_blocks'] ?? 0,
                'Turnovers' => $stats['turnovers'] ?? 0,
                'TOPG' => $stats['avg_turnovers'] ?? 0,
                'Fouls' => $stats['personal_fouls'] ?? 0,
                'FPG' => $stats['avg_fouls'] ?? 0,
                'TS%' => ($stats['true_shooting_percentage'] ?? 0) . '%',
                'PER' => $stats['player_efficiency_rating'] ?? 0,
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'Player', 'Team', 'Jersey', 'Season', 'GP', 'Points', 'PPG', 'FGM', 'FGA', 'FG%',
            '3PM', '3PA', '3P%', 'FTM', 'FTA', 'FT%', 'Reb', 'RPG', 'OReb', 'DReb',
            'Ast', 'APG', 'Stl', 'SPG', 'Blk', 'BPG', 'TO', 'TOPG', 'Fouls', 'FPG', 'TS%', 'PER'
        ];
    }

    public function title(): string
    {
        return 'Season Stats';
    }
}

class PlayerGameLogSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private Player $player,
        private string $season,
        private StatisticsService $statisticsService
    ) {}

    public function collection()
    {
        $games = \App\Models\Game::whereHas('gameActions', function($query) {
                $query->where('player_id', $this->player->id);
            })
            ->where('season', $this->season)
            ->where('status', 'finished')
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('scheduled_at', 'desc')
            ->get();

        return $games->map(function($game) {
            $stats = $this->statisticsService->getPlayerGameStats($this->player, $game);
            $isHome = $game->home_team_id === $this->player->team_id;
            $opponent = $isHome ? $game->awayTeam : $game->homeTeam;
            $teamScore = $isHome ? $game->home_team_score : $game->away_team_score;
            $opponentScore = $isHome ? $game->away_team_score : $game->home_team_score;
            $result = $teamScore > $opponentScore ? 'W' : 'L';

            return [
                'Date' => $game->scheduled_at->format('Y-m-d'),
                'Opponent' => $opponent->name,
                'Home/Away' => $isHome ? 'vs' : '@',
                'Result' => $result,
                'Score' => $teamScore . '-' . $opponentScore,
                'Points' => $stats['total_points'],
                'FGM-A' => $stats['field_goals_made'] . '-' . $stats['field_goals_attempted'],
                'FG%' => $stats['field_goal_percentage'] . '%',
                '3PM-A' => $stats['three_points_made'] . '-' . $stats['three_points_attempted'],
                '3P%' => $stats['three_point_percentage'] . '%',
                'FTM-A' => $stats['free_throws_made'] . '-' . $stats['free_throws_attempted'],
                'FT%' => $stats['free_throw_percentage'] . '%',
                'Reb' => $stats['total_rebounds'],
                'Ast' => $stats['assists'],
                'Stl' => $stats['steals'],
                'Blk' => $stats['blocks'],
                'TO' => $stats['turnovers'],
                'Fouls' => $stats['personal_fouls'],
                'TS%' => $stats['true_shooting_percentage'] . '%',
                'PER' => $stats['player_efficiency_rating'],
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Date', 'Opponent', 'H/A', 'Result', 'Score', 'Points', 'FGM-A', 'FG%',
            '3PM-A', '3P%', 'FTM-A', 'FT%', 'Reb', 'Ast', 'Stl', 'Blk', 'TO', 'Fouls', 'TS%', 'PER'
        ];
    }

    public function title(): string
    {
        return 'Game Log';
    }
}