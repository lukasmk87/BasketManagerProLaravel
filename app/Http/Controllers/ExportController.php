<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Services\StatisticsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GameStatsExport;
use App\Exports\PlayerStatsExport;
use App\Exports\TeamStatsExport;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ExportController extends Controller
{
    public function __construct(
        private StatisticsService $statisticsService
    ) {
        $this->middleware('auth');
    }

    /**
     * Export game statistics as PDF.
     */
    public function gameStatsPdf(Game $game)
    {
        $this->authorize('view', $game);

        $homeStats = $this->statisticsService->getTeamGameStats($game->homeTeam, $game);
        $awayStats = $this->statisticsService->getTeamGameStats($game->awayTeam, $game);
        
        // Get player stats for both teams
        $homePlayerStats = [];
        $awayPlayerStats = [];
        
        foreach ($game->homeTeam->players as $player) {
            $stats = $this->statisticsService->getPlayerGameStats($player, $game);
            if ($stats['total_points'] > 0 || $stats['total_rebounds'] > 0 || $stats['assists'] > 0) {
                $homePlayerStats[] = array_merge(['player' => $player], $stats);
            }
        }
        
        foreach ($game->awayTeam->players as $player) {
            $stats = $this->statisticsService->getPlayerGameStats($player, $game);
            if ($stats['total_points'] > 0 || $stats['total_rebounds'] > 0 || $stats['assists'] > 0) {
                $awayPlayerStats[] = array_merge(['player' => $player], $stats);
            }
        }

        $pdf = PDF::loadView('exports.game-stats', [
            'game' => $game,
            'homeStats' => $homeStats,
            'awayStats' => $awayStats,
            'homePlayerStats' => $homePlayerStats,
            'awayPlayerStats' => $awayPlayerStats,
        ]);

        $filename = sprintf(
            'game_stats_%s_vs_%s_%s.pdf',
            str_replace(' ', '_', $game->homeTeam->name),
            str_replace(' ', '_', $game->awayTeam->name),
            $game->scheduled_at->format('Y-m-d')
        );

        return $pdf->download($filename);
    }

    /**
     * Export game statistics as Excel.
     */
    public function gameStatsExcel(Game $game)
    {
        $this->authorize('view', $game);

        $filename = sprintf(
            'game_stats_%s_vs_%s_%s.xlsx',
            str_replace(' ', '_', $game->homeTeam->name),
            str_replace(' ', '_', $game->awayTeam->name),
            $game->scheduled_at->format('Y-m-d')
        );

        return Excel::download(new GameStatsExport($game, $this->statisticsService), $filename);
    }

    /**
     * Export player season statistics as PDF.
     */
    public function playerStatsPdf(Player $player, Request $request)
    {
        $this->authorize('view', $player);

        $season = $request->input('season', config('basketball.default_season'));
        $stats = $this->statisticsService->getPlayerSeasonStats($player, $season);
        
        // Get recent games
        $recentGames = Game::whereHas('gameActions', function($query) use ($player) {
                $query->where('player_id', $player->id);
            })
            ->where('season', $season)
            ->where('status', 'finished')
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('scheduled_at', 'desc')
            ->limit(10)
            ->get();

        $pdf = PDF::loadView('exports.player-stats', [
            'player' => $player,
            'season' => $season,
            'stats' => $stats,
            'recentGames' => $recentGames,
        ]);

        $filename = sprintf(
            'player_stats_%s_%s.pdf',
            str_replace(' ', '_', $player->name),
            $season
        );

        return $pdf->download($filename);
    }

    /**
     * Export player season statistics as Excel.
     */
    public function playerStatsExcel(Player $player, Request $request)
    {
        $this->authorize('view', $player);

        $season = $request->input('season', config('basketball.default_season'));

        $filename = sprintf(
            'player_stats_%s_%s.xlsx',
            str_replace(' ', '_', $player->name),
            $season
        );

        return Excel::download(new PlayerStatsExport($player, $season, $this->statisticsService), $filename);
    }

    /**
     * Export team season statistics as PDF.
     */
    public function teamStatsPdf(Team $team, Request $request)
    {
        $this->authorize('view', $team);

        $season = $request->input('season', config('basketball.default_season'));
        $stats = $this->statisticsService->getTeamSeasonStats($team, $season);
        $analytics = $this->statisticsService->getTeamAnalytics($team, $season);

        // Get recent games
        $recentGames = Game::where('season', $season)
            ->where('status', 'finished')
            ->where(function($query) use ($team) {
                $query->where('home_team_id', $team->id)
                      ->orWhere('away_team_id', $team->id);
            })
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('scheduled_at', 'desc')
            ->limit(15)
            ->get();

        // Get top players
        $topPlayers = $team->players()
            ->whereHas('gameActions', function($query) use ($season) {
                $query->whereHas('game', function($q) use ($season) {
                    $q->where('season', $season)->where('status', 'finished');
                });
            })
            ->get()
            ->map(function($player) use ($season) {
                return [
                    'player' => $player,
                    'stats' => $this->statisticsService->getPlayerSeasonStats($player, $season)
                ];
            })
            ->sortByDesc('stats.avg_points')
            ->take(10);

        $pdf = PDF::loadView('exports.team-stats', [
            'team' => $team,
            'season' => $season,
            'stats' => $stats,
            'analytics' => $analytics,
            'recentGames' => $recentGames,
            'topPlayers' => $topPlayers,
        ]);

        $filename = sprintf(
            'team_stats_%s_%s.pdf',
            str_replace(' ', '_', $team->name),
            $season
        );

        return $pdf->download($filename);
    }

    /**
     * Export team season statistics as Excel.
     */
    public function teamStatsExcel(Team $team, Request $request)
    {
        $this->authorize('view', $team);

        $season = $request->input('season', config('basketball.default_season'));

        $filename = sprintf(
            'team_stats_%s_%s.xlsx',
            str_replace(' ', '_', $team->name),
            $season
        );

        return Excel::download(new TeamStatsExport($team, $season, $this->statisticsService), $filename);
    }

    /**
     * Export game scoresheet as PDF.
     */
    public function gameScoresheet(Game $game)
    {
        $this->authorize('view', $game);

        $gameActions = $game->gameActions()
            ->with(['player', 'assistedByPlayer'])
            ->orderBy('recorded_at')
            ->get();

        $pdf = PDF::loadView('exports.game-scoresheet', [
            'game' => $game,
            'gameActions' => $gameActions,
            'liveGame' => $game->liveGame,
        ]);

        $filename = sprintf(
            'scoresheet_%s_vs_%s_%s.pdf',
            str_replace(' ', '_', $game->homeTeam->name),
            str_replace(' ', '_', $game->awayTeam->name),
            $game->scheduled_at->format('Y-m-d')
        );

        return $pdf->download($filename);
    }

    /**
     * Export shot chart data as CSV.
     */
    public function shotChartCsv(Player $player, Game $game)
    {
        $this->authorize('view', $game);
        $this->authorize('view', $player);

        $shotChart = $this->statisticsService->getPlayerShotChart($player, $game);
        
        $filename = sprintf(
            'shot_chart_%s_%s_vs_%s.csv',
            str_replace(' ', '_', $player->name),
            str_replace(' ', '_', $game->homeTeam->name),
            str_replace(' ', '_', $game->awayTeam->name)
        );

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($shotChart) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, ['X', 'Y', 'Made', 'Distance', 'Zone', 'Period', 'Time', 'Points']);
            
            // CSV data
            foreach ($shotChart['shots'] as $shot) {
                fputcsv($file, [
                    $shot['x'],
                    $shot['y'],
                    $shot['made'] ? 'Yes' : 'No',
                    $shot['distance'],
                    $shot['zone'],
                    $shot['period'],
                    $shot['time'],
                    $shot['points'],
                ]);
            }
            
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export league standings as PDF.
     */
    public function leagueStandingsPdf(Request $request)
    {
        $league = $request->input('league');
        $season = $request->input('season', config('basketball.default_season'));

        $teams = Team::where('league', $league)
            ->where('season', $season)
            ->get()
            ->map(function($team) use ($season) {
                return [
                    'team' => $team,
                    'stats' => $this->statisticsService->getTeamSeasonStats($team, $season)
                ];
            })
            ->sortByDesc('stats.win_percentage');

        $pdf = PDF::loadView('exports.league-standings', [
            'league' => $league,
            'season' => $season,
            'teams' => $teams,
        ]);

        $filename = sprintf(
            'league_standings_%s_%s.pdf',
            str_replace(' ', '_', $league),
            $season
        );

        return $pdf->download($filename);
    }
}