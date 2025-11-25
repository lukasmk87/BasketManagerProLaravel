<?php

namespace App\Exports;

use App\Models\Player;
use App\Models\Game;
use App\Services\StatisticsService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

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

/**
 * PERF-008: PlayerGameLogSheet with chunking for memory optimization.
 *
 * Uses FromQuery + WithMapping + WithCustomChunkSize to process games in batches
 * instead of loading all games into memory at once.
 */
class PlayerGameLogSheet implements FromQuery, WithHeadings, WithTitle, WithMapping, WithCustomChunkSize
{
    public function __construct(
        private Player $player,
        private string $season,
        private StatisticsService $statisticsService
    ) {}

    /**
     * PERF-008: Return query builder instead of collection.
     * This allows Maatwebsite Excel to chunk the results automatically.
     */
    public function query(): Builder
    {
        return Game::whereHas('gameActions', function ($query) {
                $query->where('player_id', $this->player->id);
            })
            ->where('season', $this->season)
            ->where('status', 'finished')
            ->with(['homeTeam:id,name', 'awayTeam:id,name']) // Select only needed fields
            ->orderBy('scheduled_at', 'desc');
    }

    /**
     * PERF-008: Map each game row to export format.
     * Called automatically by Maatwebsite Excel for each row.
     */
    public function map($game): array
    {
        $stats = $this->statisticsService->getPlayerGameStats($this->player, $game);
        $isHome = $game->home_team_id === $this->player->team_id;
        $opponent = $isHome ? $game->awayTeam : $game->homeTeam;
        $teamScore = $isHome ? $game->home_team_score : $game->away_team_score;
        $opponentScore = $isHome ? $game->away_team_score : $game->home_team_score;
        $result = $teamScore > $opponentScore ? 'W' : 'L';

        return [
            $game->scheduled_at?->format('Y-m-d') ?? '',
            $opponent->name ?? 'Unknown',
            $isHome ? 'vs' : '@',
            $result,
            $teamScore . '-' . $opponentScore,
            $stats['total_points'] ?? 0,
            ($stats['field_goals_made'] ?? 0) . '-' . ($stats['field_goals_attempted'] ?? 0),
            ($stats['field_goal_percentage'] ?? 0) . '%',
            ($stats['three_points_made'] ?? 0) . '-' . ($stats['three_points_attempted'] ?? 0),
            ($stats['three_point_percentage'] ?? 0) . '%',
            ($stats['free_throws_made'] ?? 0) . '-' . ($stats['free_throws_attempted'] ?? 0),
            ($stats['free_throw_percentage'] ?? 0) . '%',
            $stats['total_rebounds'] ?? 0,
            $stats['assists'] ?? 0,
            $stats['steals'] ?? 0,
            $stats['blocks'] ?? 0,
            $stats['turnovers'] ?? 0,
            $stats['personal_fouls'] ?? 0,
            ($stats['true_shooting_percentage'] ?? 0) . '%',
            $stats['player_efficiency_rating'] ?? 0,
        ];
    }

    /**
     * PERF-008: Define chunk size for memory optimization.
     * Process 100 games at a time instead of all at once.
     */
    public function chunkSize(): int
    {
        return 100;
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