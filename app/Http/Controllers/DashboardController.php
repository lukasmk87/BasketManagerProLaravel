<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use App\Services\TeamService;
use App\Services\PlayerService;
use App\Services\ClubService;
use App\Services\StatisticsService;
use App\Models\User;
use App\Models\Club;
use App\Models\Team;
use App\Models\Player;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private UserService $userService,
        private TeamService $teamService,
        private PlayerService $playerService,
        private ClubService $clubService,
        private StatisticsService $statisticsService
    ) {}

    /**
     * Main dashboard entry point with role-based routing.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Determine primary role for dashboard routing
        $primaryRole = $this->getPrimaryRole($user);

        // Get role-specific dashboard data
        $dashboardData = match ($primaryRole) {
            'admin', 'super_admin' => $this->getAdminDashboard($user),
            'club-admin' => $this->getClubAdminDashboard($user),
            'trainer', 'head-coach', 'assistant-coach' => $this->getTrainerDashboard($user),
            'player' => $this->getPlayerDashboard($user),
            default => $this->getBasicDashboard($user),
        };

        return Inertia::render('Dashboard', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'primary_role' => $primaryRole,
                'roles' => $user->roles->pluck('name')->toArray(),
                'avatar_url' => $user->avatar_url,
            ],
            'dashboard_type' => $primaryRole,
            'dashboard_data' => $dashboardData,
            'quick_actions' => $this->getQuickActions($user, $primaryRole),
            'recent_activities' => $this->getRecentActivities($user, 10),
        ]);
    }

    /**
     * Get admin-specific dashboard data.
     */
    public function getAdminDashboard(User $user): array
    {
        try {
            $systemStats = $this->userService->getUserStatistics();
            
            $dashboardStats = [
                'system_overview' => [
                    'total_users' => $systemStats['total_users'],
                    'active_users' => $systemStats['active_users'],
                    'new_users_this_month' => $systemStats['new_users_this_month'],
                    'total_clubs' => Club::count(),
                    'active_clubs' => Club::where('is_active', true)->count(),
                    'verified_clubs' => Club::where('is_verified', true)->count(),
                    'total_teams' => Team::count(),
                    'active_teams' => Team::where('is_active', true)->count(),
                    'total_players' => Player::count(),
                    'active_players' => Player::where('status', 'active')->count(),
                    'total_games_today' => Game::whereDate('scheduled_at', today())
                        ->whereIn('status', ['scheduled', 'in_progress'])
                        ->count(),
                ],
                'role_distribution' => $systemStats['role_distribution'],
                'country_distribution' => $systemStats['country_distribution'],
                'age_distribution' => $systemStats['age_distribution'],
                'recent_registrations' => User::with('roles')
                    ->latest()
                    ->limit(10)
                    ->get()
                    ->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'roles' => $user->roles->pluck('name')->toArray(),
                            'created_at' => $user->created_at,
                            'is_active' => $user->is_active,
                        ];
                    }),
                'system_health' => [
                    'storage_usage' => $this->getStorageUsage(),
                    'cache_status' => $this->getCacheStatus(),
                    'queue_status' => $this->getQueueStatus(),
                ],
                'upcoming_games_today' => Game::with(['homeTeam:id,name', 'awayTeam:id,name'])
                    ->whereDate('scheduled_at', today())
                    ->where('status', 'scheduled')
                    ->orderBy('scheduled_at')
                    ->limit(10)
                    ->get(),
                'active_live_games' => Game::with(['homeTeam:id,name', 'awayTeam:id,name'])
                    ->where('status', 'in_progress')
                    ->limit(5)
                    ->get(),
            ];

            return $dashboardStats;

        } catch (\Exception $e) {
            Log::error('Failed to load admin dashboard', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return ['error' => 'Dashboard-Daten konnten nicht geladen werden.'];
        }
    }

    /**
     * Get club admin-specific dashboard data.
     */
    public function getClubAdminDashboard(User $user): array
    {
        try {
            // Get clubs where user is admin
            $adminClubs = $user->clubs()
                ->wherePivotIn('role', ['admin', 'manager'])
                ->with(['teams.players', 'users'])
                ->get();

            if ($adminClubs->isEmpty()) {
                return ['message' => 'Sie sind aktuell kein Administrator eines Clubs.'];
            }

            $primaryClub = $adminClubs->first();
            $clubStats = $this->clubService->getClubStatistics($primaryClub);

            return [
                'primary_club' => [
                    'id' => $primaryClub->id,
                    'name' => $primaryClub->name,
                    'logo_url' => $primaryClub->logo_url,
                    'is_verified' => $primaryClub->is_verified,
                ],
                'club_statistics' => $clubStats,
                'teams_overview' => $primaryClub->teams()
                    ->with(['headCoach:id,name', 'players'])
                    ->withCount(['players', 'games'])
                    ->get()
                    ->map(function ($team) {
                        return [
                            'id' => $team->id,
                            'name' => $team->name,
                            'season' => $team->season,
                            'league' => $team->league,
                            'head_coach' => $team->headCoach?->name,
                            'player_count' => $team->players_count,
                            'games_count' => $team->games_count,
                            'is_active' => $team->is_active,
                            'win_percentage' => $team->win_percentage,
                        ];
                    }),
                'recent_member_activity' => $primaryClub->users()
                    ->withPivot('joined_at', 'role')
                    ->orderBy('pivot_joined_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'role' => $user->pivot->role,
                            'joined_at' => $user->pivot->joined_at,
                        ];
                    }),
                'upcoming_games' => Game::whereHas('homeTeam', function ($query) use ($primaryClub) {
                        $query->where('club_id', $primaryClub->id);
                    })
                    ->orWhereHas('awayTeam', function ($query) use ($primaryClub) {
                        $query->where('club_id', $primaryClub->id);
                    })
                    ->with(['homeTeam:id,name', 'awayTeam:id,name'])
                    ->where('scheduled_at', '>', now())
                    ->where('status', 'scheduled')
                    ->orderBy('scheduled_at')
                    ->limit(10)
                    ->get(),
                'all_clubs' => $adminClubs->map(function ($club) {
                    return [
                        'id' => $club->id,
                        'name' => $club->name,
                        'role' => $club->pivot->role,
                        'team_count' => $club->teams_count,
                        'player_count' => $club->players_count,
                    ];
                }),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to load club admin dashboard', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return ['error' => 'Dashboard-Daten konnten nicht geladen werden.'];
        }
    }

    /**
     * Get trainer-specific dashboard data.
     */
    public function getTrainerDashboard(User $user): array
    {
        try {
            // Get teams where user is coach
            $coachedTeams = $user->coachedTeams()
                ->with(['club:id,name', 'players.user:id,name'])
                ->withCount(['players', 'games'])
                ->get();

            $assistantCoachedTeams = $user->assistantCoachedTeams()
                ->with(['club:id,name', 'players.user:id,name'])
                ->withCount(['players', 'games'])
                ->get();

            $allTeams = $coachedTeams->merge($assistantCoachedTeams);

            if ($allTeams->isEmpty()) {
                return ['message' => 'Sie sind aktuell kein Trainer eines Teams.'];
            }

            $primaryTeam = $allTeams->first();
            $teamStats = $this->teamService->getTeamStatistics($primaryTeam);

            return [
                'primary_team' => [
                    'id' => $primaryTeam->id,
                    'name' => $primaryTeam->name,
                    'club_name' => $primaryTeam->club->name,
                    'season' => $primaryTeam->season,
                    'league' => $primaryTeam->league,
                    'role' => $coachedTeams->contains($primaryTeam) ? 'head_coach' : 'assistant_coach',
                ],
                'team_statistics' => $teamStats,
                'roster_overview' => $primaryTeam->players()
                    ->with('user:id,name,birth_date')
                    ->wherePivot('status', 'active')
                    ->wherePivot('is_active', true)
                    ->orderBy('player_team.jersey_number')
                    ->get()
                    ->map(function ($player) {
                        return [
                            'id' => $player->id,
                            'name' => $player->user?->name ?? $player->full_name,
                            'jersey_number' => $player->pivot->jersey_number,
                            'position' => $player->pivot->primary_position,
                            'age' => $player->user?->birth_date?->age,
                            'is_captain' => $player->pivot->is_captain,
                            'is_starter' => $player->pivot->is_starter,
                            'games_played' => $player->pivot->games_played,
                            'points_per_game' => $player->points_per_game,
                        ];
                    }),
                'upcoming_games' => $primaryTeam->allGames()
                    ->with(['homeTeam:id,name', 'awayTeam:id,name'])
                    ->where('scheduled_at', '>', now())
                    ->where('status', 'scheduled')
                    ->orderBy('scheduled_at')
                    ->limit(5)
                    ->get(),
                'recent_games' => $primaryTeam->allGames()
                    ->with(['homeTeam:id,name', 'awayTeam:id,name'])
                    ->where('status', 'finished')
                    ->orderBy('scheduled_at', 'desc')
                    ->limit(5)
                    ->get(),
                'training_schedule' => $primaryTeam->training_schedule,
                'all_teams' => $allTeams->map(function ($team) use ($coachedTeams) {
                    return [
                        'id' => $team->id,
                        'name' => $team->name,
                        'club_name' => $team->club->name,
                        'role' => $coachedTeams->contains($team) ? 'head_coach' : 'assistant_coach',
                        'player_count' => $team->players_count,
                        'season' => $team->season,
                    ];
                }),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to load trainer dashboard', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return ['error' => 'Dashboard-Daten konnten nicht geladen werden.'];
        }
    }

    /**
     * Get player-specific dashboard data.
     */
    public function getPlayerDashboard(User $user): array
    {
        try {
            $player = $user->playerProfile;
            
            if (!$player || !$player->team) {
                return ['message' => 'Sie sind aktuell keinem Team zugeordnet.'];
            }

            $team = $player->team;
            $currentSeason = $team->season;
            $playerStats = $this->playerService->getPlayerStatistics($player, $currentSeason);

            return [
                'player_info' => [
                    'id' => $player->id,
                    'name' => $user->name,
                    'jersey_number' => $player->jersey_number,
                    'position' => $player->primary_position,
                    'is_captain' => $player->is_captain,
                    'is_starter' => $player->is_starter,
                    'status' => $player->status,
                ],
                'team_info' => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'club_name' => $team->club->name,
                    'season' => $team->season,
                    'league' => $team->league,
                    'head_coach' => $team->headCoach?->name,
                ],
                'personal_statistics' => $playerStats,
                'team_roster' => $team->players()
                    ->with('user:id,name')
                    ->wherePivot('status', 'active')
                    ->wherePivot('is_active', true)
                    ->orderBy('player_team.jersey_number')
                    ->get()
                    ->map(function ($teammate) {
                        return [
                            'id' => $teammate->id,
                            'name' => $teammate->user?->name ?? $teammate->full_name,
                            'jersey_number' => $teammate->pivot->jersey_number,
                            'position' => $teammate->pivot->primary_position,
                            'is_captain' => $teammate->pivot->is_captain,
                            'is_starter' => $teammate->pivot->is_starter,
                        ];
                    }),
                'upcoming_games' => $team->allGames()
                    ->with(['homeTeam:id,name', 'awayTeam:id,name'])
                    ->where('scheduled_at', '>', now())
                    ->where('status', 'scheduled')
                    ->orderBy('scheduled_at')
                    ->limit(5)
                    ->get(),
                'recent_games' => $team->allGames()
                    ->with(['homeTeam:id,name', 'awayTeam:id,name'])
                    ->where('status', 'finished')
                    ->orderBy('scheduled_at', 'desc')
                    ->limit(5)
                    ->get(),
                'training_schedule' => $team->training_schedule,
                'development_goals' => $player->development_goals,
                'training_focus_areas' => $player->training_focus_areas,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to load player dashboard', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return ['error' => 'Dashboard-Daten konnten nicht geladen werden.'];
        }
    }

    /**
     * Get basic dashboard for users without specific roles.
     */
    private function getBasicDashboard(User $user): array
    {
        return [
            'message' => 'Willkommen bei BasketManager Pro! Kontaktieren Sie Ihren Administrator, um Rollen und Berechtigungen zu erhalten.',
            'available_actions' => [
                'profile' => 'Profil bearbeiten',
                'settings' => 'Einstellungen anpassen',
                'support' => 'Support kontaktieren',
            ],
        ];
    }

    /**
     * Get quick actions based on user role.
     */
    private function getQuickActions(User $user, string $primaryRole): array
    {
        return match ($primaryRole) {
            'admin', 'super_admin' => [
                ['label' => 'Neuer Club', 'route' => 'clubs.create', 'icon' => 'building'],
                ['label' => 'Neues Team', 'route' => 'teams.create', 'icon' => 'user-group'],
                ['label' => 'Neuer Spieler', 'route' => 'players.create', 'icon' => 'user'],
                ['label' => 'Admin Panel', 'route' => 'admin.settings', 'icon' => 'cog'],
                ['label' => 'Profil bearbeiten', 'route' => 'profile.show', 'icon' => 'user-circle'],
                ['label' => 'Dashboard aktualisieren', 'route' => 'dashboard', 'icon' => 'refresh'],
            ],
            'club-admin' => [
                ['label' => 'Profil bearbeiten', 'route' => 'profile.show', 'icon' => 'user'],
                ['label' => 'Dashboard aktualisieren', 'route' => 'dashboard', 'icon' => 'refresh'],
            ],
            'trainer', 'head-coach', 'assistant-coach' => [
                ['label' => 'Profil bearbeiten', 'route' => 'profile.show', 'icon' => 'user'],
                ['label' => 'Dashboard aktualisieren', 'route' => 'dashboard', 'icon' => 'refresh'],
            ],
            'player' => [
                ['label' => 'Profil bearbeiten', 'route' => 'profile.show', 'icon' => 'user'],
                ['label' => 'Dashboard aktualisieren', 'route' => 'dashboard', 'icon' => 'refresh'],
            ],
            default => [
                ['label' => 'Profil bearbeiten', 'route' => 'profile.show', 'icon' => 'user'],
                ['label' => 'Dashboard aktualisieren', 'route' => 'dashboard', 'icon' => 'refresh'],
            ],
        };
    }

    /**
     * Get recent activities for user.
     */
    private function getRecentActivities(User $user, int $limit): array
    {
        return $this->userService->getUserActivities($user, $limit);
    }

    /**
     * Determine the primary role for dashboard routing.
     */
    private function getPrimaryRole(User $user): string
    {
        $roles = $user->roles->pluck('name')->toArray();

        // Priority order for role determination
        $rolePriority = [
            'super_admin', 'admin', 'club-admin', 'trainer', 
            'head-coach', 'assistant-coach', 'player', 'member'
        ];

        foreach ($rolePriority as $role) {
            if (in_array($role, $roles)) {
                return $role;
            }
        }

        return 'member';
    }

    /**
     * Get storage usage information.
     */
    private function getStorageUsage(): array
    {
        try {
            $storagePath = storage_path();
            $freeSpace = disk_free_space($storagePath);
            $totalSpace = disk_total_space($storagePath);
            $usedSpace = $totalSpace - $freeSpace;

            return [
                'total' => $this->formatBytes($totalSpace),
                'used' => $this->formatBytes($usedSpace),
                'free' => $this->formatBytes($freeSpace),
                'percentage' => round(($usedSpace / $totalSpace) * 100, 1),
            ];
        } catch (\Exception $e) {
            return ['error' => 'Storage information unavailable'];
        }
    }

    /**
     * Get cache status.
     */
    private function getCacheStatus(): array
    {
        try {
            return [
                'driver' => config('cache.default'),
                'status' => 'operational', // Could be enhanced with actual health checks
            ];
        } catch (\Exception $e) {
            return ['error' => 'Cache status unavailable'];
        }
    }

    /**
     * Get queue status.
     */
    private function getQueueStatus(): array
    {
        try {
            return [
                'driver' => config('queue.default'),
                'status' => 'operational', // Could be enhanced with actual queue monitoring
            ];
        } catch (\Exception $e) {
            return ['error' => 'Queue status unavailable'];
        }
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}