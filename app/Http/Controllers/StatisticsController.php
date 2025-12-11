<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Services\Statistics\StatisticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StatisticsController extends Controller
{
    public function __construct(
        private StatisticsService $statisticsService
    ) {}

    /**
     * Display statistics overview.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get overall statistics based on user permissions
        $overallStats = $this->getOverallStatistics($user);

        return Inertia::render('Statistics/Index', [
            'overallStats' => $overallStats,
        ]);
    }

    /**
     * Display team statistics.
     */
    public function teams(Request $request): Response
    {
        $user = $request->user();

        $teams = Team::query()
            ->with(['club', 'players'])
            ->withCount(['players', 'homeGames', 'awayGames'])
            ->when(! $user->hasAnyRole(['tenant_admin', 'super_admin']), function ($query) use ($user) {
                return $query->where(function ($q) use ($user) {
                    $q->whereHas('club.users', function ($subQ) use ($user) {
                        $subQ->where('user_id', $user->id);
                    })->orWhere('head_coach_id', $user->id)
                        ->orWhereJsonContains('assistant_coaches', $user->id);
                });
            })
            ->get()
            ->map(function ($team) {
                try {
                    $stats = $this->statisticsService->getTeamStatistics($team);
                } catch (\Exception $e) {
                    $stats = [];
                }

                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'club_name' => $team->club?->name ?? 'No Club',
                    'season' => $team->season,
                    'players_count' => $team->players_count,
                    'games_count' => $team->home_games_count + $team->away_games_count,
                    'statistics' => $stats,
                ];
            });

        return Inertia::render('Statistics/Teams', [
            'teams' => $teams,
        ]);
    }

    /**
     * Display player statistics.
     */
    public function players(Request $request): Response
    {
        $user = $request->user();

        $players = Player::query()
            ->with(['team.club', 'user'])
            ->when(! $user->hasAnyRole(['tenant_admin', 'super_admin']), function ($query) use ($user) {
                return $query->whereHas('team', function ($q) use ($user) {
                    $q->where('head_coach_id', $user->id)
                        ->orWhereJsonContains('assistant_coaches', $user->id)
                        ->orWhereHas('club.users', function ($subQ) use ($user) {
                            $subQ->where('user_id', $user->id);
                        });
                });
            })
            ->get()
            ->filter(function ($player) {
                return $player->primaryTeam() !== null; // Filter out players without teams
            })
            ->map(function ($player) {
                try {
                    $stats = $this->statisticsService->getPlayerStatistics($player, $player->primaryTeam()?->season);
                } catch (\Exception $e) {
                    $stats = [];
                }

                return [
                    'id' => $player->id,
                    'name' => $player->user?->name ?? $player->full_name,
                    'jersey_number' => $player->primaryTeam()?->pivot->jersey_number ?? null,
                    'position' => $player->primaryTeam()?->pivot->primary_position ?? null,
                    'team_name' => $player->primaryTeam()?->name ?? 'No Team',
                    'club_name' => $player->primaryTeam()?->club?->name ?? 'No Club',
                    'statistics' => $stats,
                ];
            });

        return Inertia::render('Statistics/Players', [
            'players' => $players,
        ]);
    }

    /**
     * Display game statistics.
     */
    public function games(Request $request): Response
    {
        $user = $request->user();

        $games = Game::query()
            ->with(['homeTeam.club', 'awayTeam.club'])
            ->where('status', 'finished')
            ->when(! $user->hasAnyRole(['tenant_admin', 'super_admin']), function ($query) use ($user) {
                return $query->where(function ($q) use ($user) {
                    $q->whereHas('homeTeam', function ($subQ) use ($user) {
                        $subQ->where('head_coach_id', $user->id)
                            ->orWhereJsonContains('assistant_coaches', $user->id)
                            ->orWhereHas('club.users', function ($clubQ) use ($user) {
                                $clubQ->where('user_id', $user->id);
                            });
                    })->orWhereHas('awayTeam', function ($subQ) use ($user) {
                        $subQ->where('head_coach_id', $user->id)
                            ->orWhereJsonContains('assistant_coaches', $user->id)
                            ->orWhereHas('club.users', function ($clubQ) use ($user) {
                                $clubQ->where('user_id', $user->id);
                            });
                    });
                });
            })
            ->orderBy('scheduled_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($game) {
                try {
                    $stats = $this->statisticsService->getGameStatistics($game);
                } catch (\Exception $e) {
                    $stats = [];
                }

                return [
                    'id' => $game->id,
                    'home_team' => $game->homeTeam?->name ?? 'Unknown Team',
                    'away_team' => $game->awayTeam?->name ?? 'Unknown Team',
                    'scheduled_at' => $game->scheduled_at,
                    'home_score' => $game->home_score,
                    'away_score' => $game->away_score,
                    'location' => $game->location,
                    'statistics' => $stats,
                ];
            });

        return Inertia::render('Statistics/Games', [
            'games' => $games,
        ]);
    }

    /**
     * Get overall statistics based on user permissions.
     */
    private function getOverallStatistics($user): array
    {
        if ($user->hasAnyRole(['tenant_admin', 'super_admin'])) {
            // System-wide statistics for admin users
            return [
                'total_teams' => Team::count(),
                'active_teams' => Team::where('is_active', true)->count(),
                'total_players' => Player::count(),
                'active_players' => Player::where('status', 'active')->count(),
                'total_games' => Game::count(),
                'finished_games' => Game::where('status', 'finished')->count(),
                'scheduled_games' => Game::where('status', 'scheduled')->count(),
                'live_games' => Game::where('status', 'in_progress')->count(),
            ];
        }

        // User-specific statistics
        $userTeams = collect();

        if ($user->hasRole('club_admin')) {
            $userTeams = Team::whereHas('club.users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->get();
        } elseif ($user->hasAnyRole(['trainer', 'assistant_coach'])) {
            $userTeams = Team::where(function ($query) use ($user) {
                $query->where('head_coach_id', $user->id)
                    ->orWhereJsonContains('assistant_coaches', $user->id);
            })->get();
        }

        $teamIds = $userTeams->pluck('id');

        return [
            'my_teams' => $userTeams->count(),
            'my_players' => Player::whereHas('teams', function ($q) use ($teamIds) {
                $q->whereIn('teams.id', $teamIds)->where('player_team.is_active', true);
            })->where('status', 'active')->count(),
            'my_games_total' => Game::where(function ($query) use ($teamIds) {
                $query->whereIn('home_team_id', $teamIds)->orWhereIn('away_team_id', $teamIds);
            })->count(),
            'my_scheduled_games' => Game::where(function ($query) use ($teamIds) {
                $query->whereIn('home_team_id', $teamIds)->orWhereIn('away_team_id', $teamIds);
            })->where('status', 'scheduled')->count(),
        ];
    }
}
