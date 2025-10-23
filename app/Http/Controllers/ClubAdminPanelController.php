<?php

namespace App\Http\Controllers;

use App\Models\Club;
use App\Models\BasketballTeam;
use App\Models\Player;
use App\Models\Game;
use App\Models\User;
use App\Services\ClubService;
use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class ClubAdminPanelController extends Controller
{
    public function __construct(
        private ClubService $clubService,
        private StatisticsService $statisticsService
    ) {
        // Ensure only club admins can access
        $this->middleware(['auth', 'verified', 'role:club_admin|admin|super_admin']);
    }

    /**
     * Display the club admin dashboard.
     */
    public function dashboard(): Response
    {
        $user = Auth::user();

        // Get clubs where user is club admin
        $adminClubs = $user->clubs()
            ->wherePivot('role', 'club_admin')
            ->with(['teams', 'users'])
            ->get();

        if ($adminClubs->isEmpty()) {
            return Inertia::render('ClubAdmin/Dashboard', [
                'error' => 'Sie sind kein Administrator eines Clubs.',
                'clubs' => [],
            ]);
        }

        $primaryClub = $adminClubs->first();

        try {
            // Get club statistics
            $clubStats = $this->clubService->getClubStatistics($primaryClub);

            // Get teams overview
            $teams = $primaryClub->teams()
                ->with(['headCoach:id,name', 'players'])
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

            // Get upcoming games
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

            // Get recent activities
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

            // Get pending players count
            $pendingPlayersCount = Player::where('pending_team_assignment', true)
                ->whereHas('registeredViaInvitation', function ($q) use ($primaryClub) {
                    $q->where('club_id', $primaryClub->id);
                })
                ->count();

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
                    'total_members' => $primaryClub->users()->count(),
                    'upcoming_games' => $upcomingGames->count(),
                    'pending_players' => $pendingPlayersCount,
                ],
                'teams' => $teams,
                'upcoming_games' => $upcomingGames,
                'recent_members' => $recentMembers,
                'all_clubs' => $adminClubs->map(fn($club) => [
                    'id' => $club->id,
                    'name' => $club->name,
                    'logo_url' => $club->logo_url,
                ]),
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
     * Show club settings page.
     */
    public function settings(): Response
    {
        $user = Auth::user();
        $adminClubs = $user->clubs()->wherePivot('role', 'club_admin')->get();

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        return Inertia::render('ClubAdmin/Settings', [
            'club' => [
                'id' => $primaryClub->id,
                'name' => $primaryClub->name,
                'short_name' => $primaryClub->short_name,
                'logo_url' => $primaryClub->logo_url,
                'description' => $primaryClub->description,
                'website' => $primaryClub->website,
                'email' => $primaryClub->email,
                'phone' => $primaryClub->phone,
                'address' => $primaryClub->address,
                'city' => $primaryClub->city,
                'postal_code' => $primaryClub->postal_code,
                'country' => $primaryClub->country,
                'facebook_url' => $primaryClub->facebook_url,
                'twitter_url' => $primaryClub->twitter_url,
                'instagram_url' => $primaryClub->instagram_url,
                'is_active' => $primaryClub->is_active,
                'is_verified' => $primaryClub->is_verified,
            ],
        ]);
    }

    /**
     * Update club settings.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $adminClubs = $user->clubs()->wherePivot('role', 'club_admin')->get();

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'website' => 'nullable|url|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'facebook_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
        ]);

        try {
            $primaryClub->update($validated);

            return redirect()->route('club-admin.settings')
                ->with('success', 'Club-Einstellungen erfolgreich aktualisiert.');
        } catch (\Exception $e) {
            Log::error('Failed to update club settings', [
                'user_id' => $user->id,
                'club_id' => $primaryClub->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Fehler beim Aktualisieren der Einstellungen.')
                ->withInput();
        }
    }

    /**
     * Show club members management page.
     */
    public function members(): Response
    {
        $user = Auth::user();
        $adminClubs = $user->clubs()->wherePivot('role', 'club_admin')->get();

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        $members = $primaryClub->users()
            ->withPivot('role', 'joined_at', 'status')
            ->with('roles')
            ->orderBy('pivot_joined_at', 'desc')
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'club_role' => $member->pivot->role,
                    'status' => $member->pivot->status,
                    'joined_at' => $member->pivot->joined_at,
                    'roles' => $member->roles->pluck('name')->toArray(),
                    'is_active' => $member->is_active,
                ];
            });

        return Inertia::render('ClubAdmin/Members/Index', [
            'club' => [
                'id' => $primaryClub->id,
                'name' => $primaryClub->name,
            ],
            'members' => $members,
            'available_roles' => ['member', 'player', 'trainer', 'team_manager', 'scorer'],
        ]);
    }

    /**
     * Show club teams management page.
     */
    public function teams(): Response
    {
        $user = Auth::user();
        $adminClubs = $user->clubs()->wherePivot('role', 'club_admin')->get();

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        $teams = BasketballTeam::where('club_id', $primaryClub->id)
            ->with(['headCoach:id,name', 'players'])
            ->withCount(['players', 'homeGames', 'awayGames'])
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get()
            ->map(function ($team) {
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'season' => $team->season,
                    'league' => $team->league,
                    'age_group' => $team->age_group,
                    'gender' => $team->gender,
                    'head_coach' => [
                        'id' => $team->headCoach?->id,
                        'name' => $team->headCoach?->name,
                    ],
                    'player_count' => $team->players_count,
                    'games_count' => ($team->home_games_count ?? 0) + ($team->away_games_count ?? 0),
                    'is_active' => $team->is_active,
                    'created_at' => $team->created_at,
                ];
            });

        return Inertia::render('ClubAdmin/Teams/Index', [
            'club' => [
                'id' => $primaryClub->id,
                'name' => $primaryClub->name,
            ],
            'teams' => $teams,
        ]);
    }

    /**
     * Show club players management page.
     */
    public function players(): Response
    {
        $user = Auth::user();
        $adminClubs = $user->clubs()->wherePivot('role', 'club_admin')->get();

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        // Get all players from club teams
        $players = Player::whereHas('teams', function ($query) use ($primaryClub) {
            $query->where('club_id', $primaryClub->id);
        })
        ->with(['user:id,name,email', 'teams' => function ($query) use ($primaryClub) {
            $query->where('club_id', $primaryClub->id);
        }])
        ->get()
        ->map(function ($player) {
            return [
                'id' => $player->id,
                'name' => $player->user?->name ?? $player->full_name,
                'email' => $player->user?->email,
                'status' => $player->status,
                'birth_date' => $player->user?->birth_date,
                'teams' => $player->teams->map(fn($team) => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'jersey_number' => $team->pivot->jersey_number,
                    'position' => $team->pivot->primary_position,
                ]),
                'created_at' => $player->created_at,
            ];
        });

        // Get pending players count
        $pendingPlayersCount = Player::where('pending_team_assignment', true)
            ->whereHas('registeredViaInvitation', function ($q) use ($primaryClub) {
                $q->where('club_id', $primaryClub->id);
            })
            ->count();

        return Inertia::render('ClubAdmin/Players/Index', [
            'club' => [
                'id' => $primaryClub->id,
                'name' => $primaryClub->name,
            ],
            'players' => $players,
            'pending_players_count' => $pendingPlayersCount,
        ]);
    }

    /**
     * Show financial management page.
     */
    public function financial(): Response
    {
        $user = Auth::user();
        $adminClubs = $user->clubs()->wherePivot('role', 'club_admin')->get();

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        // TODO: Implement financial tracking
        // For now, return placeholder data
        return Inertia::render('ClubAdmin/Financial/Index', [
            'club' => [
                'id' => $primaryClub->id,
                'name' => $primaryClub->name,
            ],
            'financial_data' => [
                'total_budget' => 0,
                'total_expenses' => 0,
                'total_income' => 0,
                'balance' => 0,
            ],
            'message' => 'Finanzverwaltung wird in einer zukünftigen Version verfügbar sein.',
        ]);
    }

    /**
     * Show reports and statistics page.
     */
    public function reports(): Response
    {
        $user = Auth::user();
        $adminClubs = $user->clubs()->wherePivot('role', 'club_admin')->get();

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        try {
            $clubStats = $this->clubService->getClubStatistics($primaryClub);

            return Inertia::render('ClubAdmin/Reports/Index', [
                'club' => [
                    'id' => $primaryClub->id,
                    'name' => $primaryClub->name,
                ],
                'statistics' => $clubStats,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load club reports', [
                'user_id' => $user->id,
                'club_id' => $primaryClub->id,
                'error' => $e->getMessage(),
            ]);

            return Inertia::render('ClubAdmin/Reports/Index', [
                'club' => [
                    'id' => $primaryClub->id,
                    'name' => $primaryClub->name,
                ],
                'error' => 'Statistiken konnten nicht geladen werden.',
            ]);
        }
    }

    /**
     * Show subscriptions management page.
     */
    public function subscriptions(): Response
    {
        $user = Auth::user();
        $adminClubs = $user->clubs()->wherePivot('role', 'club_admin')->get();

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        // TODO: Implement subscription management with Stripe integration
        return Inertia::render('ClubAdmin/Subscriptions/Index', [
            'club' => [
                'id' => $primaryClub->id,
                'name' => $primaryClub->name,
            ],
            'subscription' => [
                'plan' => 'Free',
                'status' => 'active',
                'features' => [
                    'Max Teams' => 2,
                    'Max Players' => 50,
                    'Live Scoring' => false,
                    'Statistics' => true,
                ],
            ],
            'message' => 'Abo-Verwaltung wird in einer zukünftigen Version verfügbar sein.',
        ]);
    }
}
