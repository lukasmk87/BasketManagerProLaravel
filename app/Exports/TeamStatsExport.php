<?php

namespace App\Exports;

use App\Models\Team;
use App\Models\Game;
use App\Models\Player;
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

/**
 * PERF-008: TeamPlayerStatsSheet with chunking for memory optimization.
 *
 * Uses FromQuery + WithMapping + WithCustomChunkSize to process players in batches.
 */
class TeamPlayerStatsSheet implements FromQuery, WithHeadings, WithTitle, WithMapping, WithCustomChunkSize
{
    public function __construct(
        private Team $team,
        private string $season,
        private StatisticsService $statisticsService
    ) {}

    /**
     * PERF-008: Return query builder instead of collection.
     */
    public function query(): Builder
    {
        return Player::where('team_id', $this->team->id)
            ->whereHas('gameActions', function ($query) {
                $query->whereHas('game', function ($q) {
                    $q->where('season', $this->season)->where('status', 'finished');
                });
            })
            ->select(['id', 'name', 'jersey_number', 'position', 'team_id', 'user_id'])
            ->orderBy('name');
    }

    /**
     * PERF-008: Map each player to export format.
     */
    public function map($player): array
    {
        $stats = $this->statisticsService->getPlayerSeasonStats($player, $this->season);

        return [
            $player->name ?? '',
            $player->jersey_number ?? '',
            $player->position ?? '',
            $stats['games_played'] ?? 0,
            $stats['total_points'] ?? 0,
            $stats['avg_points'] ?? 0,
            ($stats['field_goal_percentage'] ?? 0) . '%',
            ($stats['three_point_percentage'] ?? 0) . '%',
            ($stats['free_throw_percentage'] ?? 0) . '%',
            $stats['total_rebounds'] ?? 0,
            $stats['avg_rebounds'] ?? 0,
            $stats['assists'] ?? 0,
            $stats['avg_assists'] ?? 0,
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
     */
    public function chunkSize(): int
    {
        return 50; // Typically teams have 10-20 players, but chunking for safety
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

/**
 * PERF-008: TeamGameLogSheet with chunking for memory optimization.
 *
 * Uses FromQuery + WithMapping + WithCustomChunkSize to process games in batches.
 */
class TeamGameLogSheet implements FromQuery, WithHeadings, WithTitle, WithMapping, WithCustomChunkSize
{
    public function __construct(
        private Team $team,
        private string $season,
        private StatisticsService $statisticsService
    ) {}

    /**
     * PERF-008: Return query builder instead of collection.
     */
    public function query(): Builder
    {
        return Game::where('season', $this->season)
            ->where('status', 'finished')
            ->where(function ($query) {
                $query->where('home_team_id', $this->team->id)
                      ->orWhere('away_team_id', $this->team->id);
            })
            ->with(['homeTeam:id,name', 'awayTeam:id,name']) // Select only needed fields
            ->orderBy('scheduled_at', 'desc');
    }

    /**
     * PERF-008: Map each game to export format.
     */
    public function map($game): array
    {
        $isHome = $game->home_team_id === $this->team->id;
        $opponent = $isHome ? $game->awayTeam : $game->homeTeam;
        $teamScore = $isHome ? $game->home_team_score : $game->away_team_score;
        $opponentScore = $isHome ? $game->away_team_score : $game->home_team_score;
        $result = $teamScore > $opponentScore ? 'W' : 'L';
        $margin = $teamScore - $opponentScore;

        $stats = $this->statisticsService->getTeamGameStats($this->team, $game);

        return [
            $game->scheduled_at?->format('Y-m-d') ?? '',
            $opponent->name ?? 'Unknown',
            $isHome ? 'H' : 'A',
            $result,
            $teamScore . '-' . $opponentScore,
            $margin > 0 ? '+' . $margin : $margin,
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
        ];
    }

    /**
     * PERF-008: Define chunk size for memory optimization.
     */
    public function chunkSize(): int
    {
        return 100; // Process 100 games at a time
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