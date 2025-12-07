<?php

namespace App\Http\Controllers\ClubAdmin;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\Game;
use App\Models\Player;
use App\Models\Season;
use App\Services\Club\ClubStatisticsService;
use App\Services\ClubUsageTrackingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ClubAdminDashboardController extends Controller
{
    public function __construct(
        private ClubStatisticsService $clubStatisticsService,
        private ClubUsageTrackingService $usageTrackingService
    ) {
        $this->middleware(['auth', 'verified', 'role:club_admin|admin|super_admin']);
    }

    /**
     * Display the club admin dashboard.
     */
    public function __invoke(): Response
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            return Inertia::render('ClubAdmin/Dashboard', [
                'club' => null,
                'error' => 'Sie sind aktuell kein Administrator eines Clubs. Bitte kontaktieren Sie Ihren Administrator, um einem Club zugewiesen zu werden.',
                'clubs' => [],
                'statistics' => [],
                'teams' => [],
                'upcoming_games' => [],
                'recent_members' => [],
                'all_clubs' => [],
            ]);
        }

        $adminClubs->load(['teams', 'users']);
        $primaryClub = $adminClubs->first();

        try {
            $clubStats = $this->clubStatisticsService->getClubStatistics($primaryClub);

            $teams = $primaryClub->teams()
                ->select(['id', 'name', 'season', 'league', 'age_group', 'gender', 'head_coach_id', 'is_active', 'win_percentage', 'club_id'])
                ->with(['headCoach:id,name'])
                ->withCount(['players', 'homeGames', 'awayGames'])
                ->get()
                ->map(function ($team) {
                    $totalGames = ($team->home_games_count ?? 0) + ($team->away_games_count ?? 0);

                    return [
                        'id' => $team->id,
                        'name' => $team->name,
                        'season' => $team->season,
                        'league' => $team->league,
                        'age_group' => $team->age_group,
                        'gender' => $team->gender,
                        'head_coach' => $team->headCoach?->name,
                        'player_count' => $team->players_count,
                        'games_count' => $totalGames,
                        'is_active' => $team->is_active,
                        'win_percentage' => $team->win_percentage ?? 0,
                    ];
                });

            $upcomingGames = Game::where(function ($query) use ($primaryClub) {
                $query->whereHas('homeTeam', function ($q) use ($primaryClub) {
                    $q->where('club_id', $primaryClub->id);
                })
                    ->orWhereHas('awayTeam', function ($q) use ($primaryClub) {
                        $q->where('club_id', $primaryClub->id);
                    });
            })
                ->with(['homeTeam:id,name', 'awayTeam:id,name'])
                ->where('scheduled_at', '>', now())
                ->where('status', 'scheduled')
                ->orderBy('scheduled_at')
                ->limit(10)
                ->get();

            $recentMembers = $primaryClub->users()
                ->withPivot('joined_at', 'role')
                ->orderBy('pivot_joined_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->pivot->role,
                        'joined_at' => $user->pivot->joined_at,
                    ];
                });

            $pendingPlayersCount = Player::where('pending_team_assignment', true)
                ->whereHas('registeredViaInvitation', function ($q) use ($primaryClub) {
                    $q->where('club_id', $primaryClub->id);
                })
                ->count();

            $storageUsage = $this->getClubStorageUsage($primaryClub);

            // Load current season
            $currentSeason = Season::where('club_id', $primaryClub->id)
                ->where('is_current', true)
                ->first(['id', 'name', 'status', 'start_date', 'end_date']);

            return Inertia::render('ClubAdmin/Dashboard', [
                'club' => [
                    'id' => $primaryClub->id,
                    'name' => $primaryClub->name,
                    'logo_url' => $primaryClub->logo_url,
                    'is_verified' => $primaryClub->is_verified,
                    'is_active' => $primaryClub->is_active,
                    'description' => $primaryClub->description,
                ],
                'statistics' => [
                    'total_teams' => $clubStats['total_teams'] ?? 0,
                    'active_teams' => $clubStats['active_teams'] ?? 0,
                    'total_players' => $clubStats['total_players'] ?? 0,
                    'active_players' => $clubStats['active_players'] ?? 0,
                    'total_members' => $primaryClub->users->count(),
                    'upcoming_games' => $upcomingGames->count(),
                    'pending_players' => $pendingPlayersCount,
                ],
                'teams' => $teams,
                'upcoming_games' => $upcomingGames,
                'recent_members' => $recentMembers,
                'all_clubs' => $adminClubs->map(fn ($club) => [
                    'id' => $club->id,
                    'name' => $club->name,
                    'logo_url' => $club->logo_url,
                ]),
                'storage_usage' => $storageUsage,
                'current_season' => $currentSeason,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load club admin dashboard', [
                'user_id' => $user->id,
                'club_id' => $primaryClub->id,
                'error' => $e->getMessage(),
            ]);

            return Inertia::render('ClubAdmin/Dashboard', [
                'error' => 'Dashboard-Daten konnten nicht geladen werden.',
                'club' => [
                    'id' => $primaryClub->id,
                    'name' => $primaryClub->name,
                    'logo_url' => $primaryClub->logo_url,
                ],
            ]);
        }
    }

    /**
     * Get storage usage data for a club.
     */
    private function getClubStorageUsage(Club $club): array
    {
        try {
            $usedGB = $club->calculateStorageUsage();
            $limitGB = $club->getLimit('max_storage_gb') ?? 5;
            $percentage = $limitGB > 0 ? min(100, round(($usedGB / $limitGB) * 100, 1)) : 0;

            return [
                'used' => round($usedGB, 2),
                'limit' => $limitGB,
                'percentage' => $percentage,
                'formatted_used' => $this->formatStorageSize($usedGB),
                'formatted_limit' => $this->formatStorageSize($limitGB),
                'is_near_limit' => $percentage >= 80,
                'is_over_limit' => $percentage >= 100,
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to calculate storage usage', [
                'club_id' => $club->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'used' => 0,
                'limit' => 5,
                'percentage' => 0,
                'formatted_used' => '0 GB',
                'formatted_limit' => '5 GB',
                'is_near_limit' => false,
                'is_over_limit' => false,
            ];
        }
    }

    /**
     * Format storage size for display.
     */
    private function formatStorageSize(float $sizeGB): string
    {
        if ($sizeGB < 0.001) {
            return '0 MB';
        }

        if ($sizeGB < 1) {
            $sizeMB = $sizeGB * 1024;
            return round($sizeMB, 1) . ' MB';
        }

        return round($sizeGB, 2) . ' GB';
    }
}
