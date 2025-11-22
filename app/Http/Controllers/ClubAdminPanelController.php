<?php

namespace App\Http\Controllers;

use App\Models\BasketballTeam;
use App\Models\Club;
use App\Models\Game;
use App\Models\Player;
use App\Models\User;
use App\Services\ClubService;
use App\Services\PlayerService;
use App\Services\StatisticsService;
use App\Services\TeamService;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class ClubAdminPanelController extends Controller
{
    public function __construct(
        private ClubService $clubService,
        private StatisticsService $statisticsService,
        private UserService $userService,
        private TeamService $teamService,
        private PlayerService $playerService
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

        // Get clubs where user is admin (respects role hierarchy)
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

        // Load relationships for display
        $adminClubs->load(['teams', 'users']);

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
                'all_clubs' => $adminClubs->map(fn ($club) => [
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
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
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
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
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
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        $members = $primaryClub->users()
            ->withPivot('role', 'joined_at', 'is_active')
            ->with('roles')
            ->orderBy('pivot_joined_at', 'desc')
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'club_role' => $member->pivot->role,
                    'membership_is_active' => $member->pivot->is_active,
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
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
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
                    'slug' => $team->slug,
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
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
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
                    'teams' => $player->teams->map(fn ($team) => [
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
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
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
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
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
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();
        $primaryClub->load('subscriptionPlan');

        // Get available subscription plans for this tenant
        $availablePlans = \App\Models\ClubSubscriptionPlan::query()
            ->where('tenant_id', $primaryClub->tenant_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description', 'price', 'currency', 'billing_interval', 'features', 'limits', 'color', 'icon']);

        // Get current subscription plan details
        $currentPlan = $primaryClub->subscriptionPlan;

        // Get subscription limits and usage
        $subscriptionLimits = $primaryClub->getSubscriptionLimits();

        return Inertia::render('ClubAdmin/Subscriptions/Index', [
            'club' => [
                'id' => $primaryClub->id,
                'name' => $primaryClub->name,
                'club_subscription_plan_id' => $primaryClub->club_subscription_plan_id,
            ],
            'current_plan' => $currentPlan ? [
                'id' => $currentPlan->id,
                'name' => $currentPlan->name,
                'slug' => $currentPlan->slug,
                'description' => $currentPlan->description,
                'price' => $currentPlan->price,
                'currency' => $currentPlan->currency,
                'billing_interval' => $currentPlan->billing_interval,
                'features' => $currentPlan->features,
                'limits' => $currentPlan->limits,
                'color' => $currentPlan->color,
                'icon' => $currentPlan->icon,
            ] : null,
            'available_plans' => $availablePlans,
            'subscription_limits' => $subscriptionLimits,
            'can_change_plan' => $user->hasAnyRole(['super_admin', 'admin']),
        ]);
    }

    /**
     * Update club subscription plan.
     */
    public function updateSubscription(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Only super_admin and admin can change subscription plans
        if (! $user->hasAnyRole(['super_admin', 'admin'])) {
            abort(403, 'Sie haben keine Berechtigung, den Subscription Plan zu ändern.');
        }

        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        $validated = $request->validate([
            'club_subscription_plan_id' => [
                'nullable',
                'exists:club_subscription_plans,id',
                function ($attribute, $value, $fail) use ($primaryClub) {
                    // Validate that plan belongs to same tenant as club
                    if ($value) {
                        $plan = \App\Models\ClubSubscriptionPlan::find($value);
                        if ($plan && $plan->tenant_id !== $primaryClub->tenant_id) {
                            $fail('Der ausgewählte Plan gehört nicht zum selben Tenant wie der Club.');
                        }
                    }
                },
            ],
        ]);

        try {
            $primaryClub->update([
                'club_subscription_plan_id' => $validated['club_subscription_plan_id'],
            ]);

            Log::info('Club subscription plan updated', [
                'user_id' => $user->id,
                'club_id' => $primaryClub->id,
                'old_plan_id' => $primaryClub->getOriginal('club_subscription_plan_id'),
                'new_plan_id' => $validated['club_subscription_plan_id'],
            ]);

            return redirect()->route('club-admin.subscriptions')
                ->with('success', 'Subscription Plan wurde erfolgreich aktualisiert.');
        } catch (\Exception $e) {
            Log::error('Failed to update club subscription plan', [
                'user_id' => $user->id,
                'club_id' => $primaryClub->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Fehler beim Aktualisieren des Subscription Plans.')
                ->withInput();
        }
    }

    /**
     * Show the create member form.
     */
    public function createMember(): Response
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        // Available club roles (not system roles)
        $availableRoles = [
            ['value' => 'member', 'label' => 'Mitglied'],
            ['value' => 'player', 'label' => 'Spieler'],
            ['value' => 'trainer', 'label' => 'Trainer'],
            ['value' => 'assistant_coach', 'label' => 'Co-Trainer'],
            ['value' => 'team_manager', 'label' => 'Team Manager'],
            ['value' => 'scorer', 'label' => 'Anschreiber'],
            ['value' => 'volunteer', 'label' => 'Freiwilliger'],
        ];

        return Inertia::render('ClubAdmin/Members/Create', [
            'club' => [
                'id' => $primaryClub->id,
                'name' => $primaryClub->name,
            ],
            'available_roles' => $availableRoles,
        ]);
    }

    /**
     * Store a newly created member.
     */
    public function storeMember(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'club_role' => 'required|in:member,player,trainer,assistant_coach,team_manager,scorer,volunteer',
            'is_active' => 'boolean',
            'send_credentials_email' => 'boolean',
        ]);

        try {
            // Create the user
            $newUser = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Attach user to club with specified role
            $primaryClub->users()->attach($newUser->id, [
                'role' => $validated['club_role'],
                'joined_at' => now(),
                'is_active' => true,
            ]);

            // Assign appropriate Spatie role based on club role
            $spatieRole = match ($validated['club_role']) {
                'trainer', 'assistant_coach' => 'trainer',
                'player' => 'player',
                default => 'guest', // member, team_manager, scorer, volunteer
            };
            $newUser->assignRole($spatieRole);

            Log::info('Club admin created new member', [
                'club_admin_id' => $user->id,
                'club_id' => $primaryClub->id,
                'new_user_id' => $newUser->id,
                'club_role' => $validated['club_role'],
            ]);

            // Send credentials email if requested
            if ($request->boolean('send_credentials_email')) {
                // TODO: Send email notification
                // $newUser->notify(new NewMemberCreatedNotification($validated['password'], $primaryClub->name));
            }

            return redirect()->route('club-admin.members')
                ->with('success', 'Mitglied wurde erfolgreich hinzugefügt.');
        } catch (\Exception $e) {
            Log::error('Failed to create club member', [
                'club_admin_id' => $user->id,
                'club_id' => $primaryClub->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Fehler beim Hinzufügen des Mitglieds: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the edit member form.
     */
    public function editMember(User $user): Response
    {
        $authUser = Auth::user();
        $adminClubs = $authUser->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        // Authorization: Check if user can edit this member
        $this->authorize('update', $user);

        // Verify the user is a member of this club
        if (! $user->clubs()->where('clubs.id', $primaryClub->id)->exists()) {
            abort(404, 'Dieser Benutzer gehört nicht zu Ihrem Club.');
        }

        // Get user's club role
        $clubMembership = $user->clubs()->where('clubs.id', $primaryClub->id)->first();

        // Available club roles (not system roles)
        $availableRoles = [
            ['value' => 'member', 'label' => 'Mitglied'],
            ['value' => 'player', 'label' => 'Spieler'],
            ['value' => 'trainer', 'label' => 'Trainer'],
            ['value' => 'assistant_coach', 'label' => 'Co-Trainer'],
            ['value' => 'team_manager', 'label' => 'Team Manager'],
            ['value' => 'scorer', 'label' => 'Anschreiber'],
            ['value' => 'volunteer', 'label' => 'Freiwilliger'],
        ];

        return Inertia::render('ClubAdmin/Members/Edit', [
            'club' => [
                'id' => $primaryClub->id,
                'name' => $primaryClub->name,
            ],
            'member' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'club_role' => $clubMembership->pivot->role,
                'is_active' => $user->is_active,
                'membership_is_active' => $clubMembership->pivot->is_active,
                'joined_at' => $clubMembership->pivot->joined_at,
                'roles' => $user->roles->pluck('name')->toArray(),
            ],
            'available_roles' => $availableRoles,
        ]);
    }

    /**
     * Update an existing member.
     */
    public function updateMember(Request $request, User $user): RedirectResponse
    {
        $authUser = Auth::user();
        $adminClubs = $authUser->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        // Authorization: Check if user can edit this member
        $this->authorize('update', $user);

        // Verify the user is a member of this club
        if (! $user->clubs()->where('clubs.id', $primaryClub->id)->exists()) {
            abort(404, 'Dieser Benutzer gehört nicht zu Ihrem Club.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:20',
            'club_role' => 'required|in:member,player,trainer,assistant_coach,team_manager,scorer,volunteer',
            'is_active' => 'boolean',
            'membership_is_active' => 'boolean',
        ]);

        try {
            // Update user details
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Update club membership
            $user->clubs()->updateExistingPivot($primaryClub->id, [
                'role' => $validated['club_role'],
                'is_active' => $validated['membership_is_active'] ?? true,
            ]);

            // Update Spatie role if club role changed
            $newSpatieRole = match ($validated['club_role']) {
                'trainer', 'assistant_coach' => 'trainer',
                'player' => 'player',
                default => 'guest',
            };

            // Remove old roles and assign new one
            $user->syncRoles([$newSpatieRole]);

            Log::info('Club admin updated member', [
                'club_admin_id' => $authUser->id,
                'club_id' => $primaryClub->id,
                'user_id' => $user->id,
                'club_role' => $validated['club_role'],
            ]);

            return redirect()->route('club-admin.members')
                ->with('success', 'Mitglied wurde erfolgreich aktualisiert.');
        } catch (\Exception $e) {
            Log::error('Failed to update club member', [
                'club_admin_id' => $authUser->id,
                'club_id' => $primaryClub->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Fehler beim Aktualisieren des Mitglieds: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Send password reset link to a member.
     */
    public function sendPasswordReset(User $user): RedirectResponse
    {
        $authUser = Auth::user();
        $adminClubs = $authUser->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        // Authorization: Check if user can send password reset
        $this->authorize('sendPasswordReset', $user);

        // Verify the user is a member of this club
        if (! $user->clubs()->where('clubs.id', $primaryClub->id)->exists()) {
            abort(404, 'Dieser Benutzer gehört nicht zu Ihrem Club.');
        }

        try {
            // Send password reset notification
            $status = Password::sendResetLink(
                ['email' => $user->email]
            );

            if ($status === Password::RESET_LINK_SENT) {
                Log::info('Club admin sent password reset', [
                    'club_admin_id' => $authUser->id,
                    'club_id' => $primaryClub->id,
                    'user_id' => $user->id,
                ]);

                return back()->with('success', 'Passwort-Reset-Link wurde erfolgreich gesendet.');
            }

            return back()->with('error', 'Fehler beim Senden des Passwort-Reset-Links.');
        } catch (\Exception $e) {
            Log::error('Failed to send password reset', [
                'club_admin_id' => $authUser->id,
                'club_id' => $primaryClub->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Fehler beim Senden des Passwort-Reset-Links: '.$e->getMessage());
        }
    }

    /**
     * Show the create team form.
     */
    public function createTeam(): Response
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        // Get potential coaches (users with trainer role in the club)
        $coaches = $primaryClub->users()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'trainer');
            })
            ->get(['id', 'name']);

        return Inertia::render('ClubAdmin/Teams/Create', [
            'club' => [
                'id' => $primaryClub->id,
                'name' => $primaryClub->name,
            ],
            'coaches' => $coaches,
            'age_groups' => ['U8', 'U10', 'U12', 'U14', 'U16', 'U18', 'U20', 'Senior', 'Sonstige'],
            'genders' => [
                ['value' => 'male', 'label' => 'Männlich'],
                ['value' => 'female', 'label' => 'Weiblich'],
                ['value' => 'mixed', 'label' => 'Gemischt'],
            ],
        ]);
    }

    /**
     * Store a newly created team.
     */
    public function storeTeam(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'season' => 'required|string|max:20',
            'league' => 'nullable|string|max:255',
            'age_group' => 'nullable|string|max:50',
            'gender' => 'required|in:male,female,mixed',
            'head_coach_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
        ]);

        try {
            $team = $this->teamService->createTeam(
                club: $primaryClub,
                name: $validated['name'],
                season: $validated['season'],
                league: $validated['league'] ?? null,
                ageGroup: $validated['age_group'] ?? null,
                gender: $validated['gender'],
                headCoachId: $validated['head_coach_id'] ?? null,
                isActive: $validated['is_active'] ?? true
            );

            Log::info('Club admin created team', [
                'club_admin_id' => $user->id,
                'club_id' => $primaryClub->id,
                'team_id' => $team->id,
            ]);

            return redirect()->route('club-admin.teams')
                ->with('success', 'Team wurde erfolgreich erstellt.');
        } catch (\Exception $e) {
            Log::error('Failed to create team', [
                'club_admin_id' => $user->id,
                'club_id' => $primaryClub->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Fehler beim Erstellen des Teams: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the edit team form.
     */
    public function editTeam(BasketballTeam $team): Response
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        // Authorization: Check if team belongs to one of the admin's clubs
        $adminClubIds = $adminClubs->pluck('id')->toArray();

        if (!in_array($team->club_id, $adminClubIds)) {
            abort(403, 'Dieses Team gehört nicht zu einem Ihrer Clubs.');
        }

        $this->authorize('update', $team);

        // Get the team's club for coaches
        $teamClub = $team->club;

        // Get potential coaches (users with trainer role in the club)
        $coaches = $teamClub->users()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'trainer');
            })
            ->get(['id', 'name']);

        return Inertia::render('ClubAdmin/Teams/Edit', [
            'club' => [
                'id' => $teamClub->id,
                'name' => $teamClub->name,
            ],
            'team' => [
                'id' => $team->id,
                'name' => $team->name,
                'season' => $team->season,
                'league' => $team->league,
                'age_group' => $team->age_group,
                'gender' => $team->gender,
                'head_coach_id' => $team->head_coach_id,
                'is_active' => $team->is_active,
            ],
            'coaches' => $coaches,
            'age_groups' => ['U8', 'U10', 'U12', 'U14', 'U16', 'U18', 'U20', 'Senior', 'Sonstige'],
            'genders' => [
                ['value' => 'male', 'label' => 'Männlich'],
                ['value' => 'female', 'label' => 'Weiblich'],
                ['value' => 'mixed', 'label' => 'Gemischt'],
            ],
        ]);
    }

    /**
     * Update an existing team.
     */
    public function updateTeam(Request $request, BasketballTeam $team): RedirectResponse
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        // Authorization: Check if team belongs to one of the admin's clubs
        $adminClubIds = $adminClubs->pluck('id')->toArray();

        if (!in_array($team->club_id, $adminClubIds)) {
            abort(403, 'Dieses Team gehört nicht zu einem Ihrer Clubs.');
        }

        $this->authorize('update', $team);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'season' => 'required|string|max:20',
            'league' => 'nullable|string|max:255',
            'age_group' => 'nullable|string|max:50',
            'gender' => 'required|in:male,female,mixed',
            'head_coach_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
        ]);

        try {
            $team->update([
                'name' => $validated['name'],
                'season' => $validated['season'],
                'league' => $validated['league'] ?? null,
                'age_group' => $validated['age_group'] ?? null,
                'gender' => $validated['gender'],
                'head_coach_id' => $validated['head_coach_id'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            Log::info('Club admin updated team', [
                'club_admin_id' => $user->id,
                'club_id' => $primaryClub->id,
                'team_id' => $team->id,
            ]);

            return redirect()->route('club-admin.teams')
                ->with('success', 'Team wurde erfolgreich aktualisiert.');
        } catch (\Exception $e) {
            Log::error('Failed to update team', [
                'club_admin_id' => $user->id,
                'club_id' => $primaryClub->id,
                'team_id' => $team->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Fehler beim Aktualisieren des Teams: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the create player form.
     */
    public function createPlayer(): Response
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        // Get club teams
        $teams = $primaryClub->teams()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'season', 'age_group']);

        return Inertia::render('ClubAdmin/Players/Create', [
            'club' => [
                'id' => $primaryClub->id,
                'name' => $primaryClub->name,
            ],
            'teams' => $teams,
            'positions' => ['PG', 'SG', 'SF', 'PF', 'C'],
        ]);
    }

    /**
     * Store a newly created player.
     */
    public function storePlayer(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'birth_date' => 'nullable|date',
            'phone' => 'nullable|string|max:20',
            'team_id' => 'nullable|exists:basketball_teams,id',
            'jersey_number' => 'nullable|integer|min:0|max:99',
            'primary_position' => 'nullable|in:PG,SG,SF,PF,C',
        ]);

        try {
            // Create user for player
            $playerUser = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
                'is_active' => true,
            ]);

            // Assign player role
            $playerUser->assignRole('player');

            // Attach to club
            $primaryClub->users()->attach($playerUser->id, [
                'role' => 'player',
                'joined_at' => now(),
                'is_active' => true,
            ]);

            // Create player profile
            $player = $this->playerService->createPlayer(
                user: $playerUser,
                status: 'active'
            );

            // Assign to team if provided
            if (! empty($validated['team_id'])) {
                $team = BasketballTeam::find($validated['team_id']);
                if ($team && $team->club_id === $primaryClub->id) {
                    $player->teams()->attach($team->id, [
                        'jersey_number' => $validated['jersey_number'] ?? null,
                        'primary_position' => $validated['primary_position'] ?? null,
                        'joined_at' => now(),
                        'is_active' => true,
                    ]);
                }
            }

            Log::info('Club admin created player', [
                'club_admin_id' => $user->id,
                'club_id' => $primaryClub->id,
                'player_id' => $player->id,
                'user_id' => $playerUser->id,
            ]);

            return redirect()->route('club-admin.players')
                ->with('success', 'Spieler wurde erfolgreich erstellt.');
        } catch (\Exception $e) {
            Log::error('Failed to create player', [
                'club_admin_id' => $user->id,
                'club_id' => $primaryClub->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Fehler beim Erstellen des Spielers: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the edit player form.
     */
    public function editPlayer(Player $player): Response
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        // Authorization: Check if player belongs to club
        $playerTeams = $player->teams()->where('club_id', $primaryClub->id)->exists();
        if (! $playerTeams) {
            abort(403, 'Dieser Spieler gehört nicht zu Ihrem Club.');
        }

        $this->authorize('update', $player);

        // Get club teams
        $teams = $primaryClub->teams()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'season', 'age_group']);

        // Get player's current team in this club
        $playerTeam = $player->teams()
            ->where('club_id', $primaryClub->id)
            ->first();

        return Inertia::render('ClubAdmin/Players/Edit', [
            'club' => [
                'id' => $primaryClub->id,
                'name' => $primaryClub->name,
            ],
            'player' => [
                'id' => $player->id,
                'name' => $player->user?->name ?? $player->full_name,
                'email' => $player->user?->email,
                'phone' => $player->user?->phone,
                'birth_date' => $player->user?->birth_date,
                'status' => $player->status,
                'team_id' => $playerTeam?->id,
                'jersey_number' => $playerTeam?->pivot->jersey_number,
                'primary_position' => $playerTeam?->pivot->primary_position,
            ],
            'teams' => $teams,
            'positions' => ['PG', 'SG', 'SF', 'PF', 'C'],
        ]);
    }

    /**
     * Update an existing player.
     */
    public function updatePlayer(Request $request, Player $player): RedirectResponse
    {
        $user = Auth::user();
        $adminClubs = $user->getAdministeredClubs(false);

        if ($adminClubs->isEmpty()) {
            abort(403, 'Sie sind aktuell kein Administrator eines Clubs.');
        }

        $primaryClub = $adminClubs->first();

        // Authorization: Check if player belongs to club
        $playerTeams = $player->teams()->where('club_id', $primaryClub->id)->exists();
        if (! $playerTeams) {
            abort(403, 'Dieser Spieler gehört nicht zu Ihrem Club.');
        }

        $this->authorize('update', $player);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$player->user_id,
            'birth_date' => 'nullable|date',
            'phone' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive,injured,suspended',
            'team_id' => 'nullable|exists:basketball_teams,id',
            'jersey_number' => 'nullable|integer|min:0|max:99',
            'primary_position' => 'nullable|in:PG,SG,SF,PF,C',
        ]);

        try {
            // Update user details if player has a user account
            if ($player->user) {
                $player->user->update([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                    'birth_date' => $validated['birth_date'] ?? null,
                ]);
            }

            // Update player status
            $player->update([
                'status' => $validated['status'],
            ]);

            // Update team assignment if changed
            if (! empty($validated['team_id'])) {
                $team = BasketballTeam::find($validated['team_id']);
                if ($team && $team->club_id === $primaryClub->id) {
                    // Sync to new team (removes old team assignments in this club)
                    $player->teams()->wherePivot('club_id', $primaryClub->id)->detach();
                    $player->teams()->attach($team->id, [
                        'jersey_number' => $validated['jersey_number'] ?? null,
                        'primary_position' => $validated['primary_position'] ?? null,
                        'joined_at' => now(),
                        'is_active' => true,
                    ]);
                }
            }

            Log::info('Club admin updated player', [
                'club_admin_id' => $user->id,
                'club_id' => $primaryClub->id,
                'player_id' => $player->id,
            ]);

            return redirect()->route('club-admin.players')
                ->with('success', 'Spieler wurde erfolgreich aktualisiert.');
        } catch (\Exception $e) {
            Log::error('Failed to update player', [
                'club_admin_id' => $user->id,
                'club_id' => $primaryClub->id,
                'player_id' => $player->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->with('error', 'Fehler beim Aktualisieren des Spielers: '.$e->getMessage())
                ->withInput();
        }
    }
}
