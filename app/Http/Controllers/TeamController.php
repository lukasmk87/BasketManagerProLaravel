<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Club;
use App\Models\Player;
use App\Models\User;
use App\Models\TeamCoach;
use App\Services\TeamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function __construct(
        private TeamService $teamService
    ) {}

    /**
     * Display a listing of teams.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Get teams based on user permissions
        $teams = Team::query()
            ->with(['club', 'headCoach'])
            ->withCount(['players', 'homeGames', 'awayGames'])
            ->when($user->hasRole('admin') || $user->hasRole('super_admin'), function ($query) {
                // Admin users see all teams
                return $query;
            }, function ($query) use ($user) {
                // Other users see teams from their clubs or teams they coach
                return $query->where(function ($q) use ($user) {
                    $q->whereHas('club.users', function ($subQ) use ($user) {
                        $subQ->where('user_id', $user->id);
                    })->orWhere('head_coach_id', $user->id)
                    ->orWhereJsonContains('assistant_coaches', $user->id);
                });
            })
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('Teams/Index', [
            'teams' => $teams,
            'can' => [
                'create' => $user->can('create', Team::class),
            ],
        ]);
    }

    /**
     * Show the form for creating a new team.
     */
    public function create(): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        try {
            $this->authorize('create', Team::class);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            // Log the authorization failure with detailed info
            \Log::warning('Teams Create - Authorization failed', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'user_roles' => auth()->user()?->getRoleNames()->toArray() ?? [],
                'user_permissions' => auth()->user()?->getAllPermissions()->pluck('name')->toArray() ?? [],
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('dashboard')->withErrors([
                'authorization' => 'Sie haben keine Berechtigung, Teams zu erstellen. Bitte wenden Sie sich an den Administrator.'
            ]);
        }

        $user = auth()->user();
        
        $clubs = Club::query()
            ->select(['id', 'name'])
            ->where('is_active', true)
            ->when($user->hasRole('admin') || $user->hasRole('super_admin'), function ($query) {
                // Admin users see all clubs
                return $query;
            }, function ($query) use ($user) {
                // Other users see only their clubs
                return $query->whereHas('users', function ($subQ) use ($user) {
                    $subQ->where('user_id', $user->id);
                });
            })
            ->orderBy('name')
            ->get();

        // Convert to simple array
        $clubsArray = $clubs->map(function ($club) {
            return [
                'id' => $club->id,
                'name' => $club->name,
            ];
        })->toArray();

        // Debug logging - Before returning response
        \Log::info('Teams Create - Clubs loaded', [
            'clubs_count' => count($clubsArray),
            'clubs' => $clubsArray,
            'user_id' => auth()->id(),
            'user_roles' => auth()->user()->getRoleNames()->toArray(),
            'user_permissions' => auth()->user()->getAllPermissions()->pluck('name')->toArray()
        ]);

        $response_data = [
            'clubs' => $clubsArray,
        ];

        // Debug logging - Response data that will be sent
        \Log::info('Teams Create - Response data', [
            'response_data' => $response_data,
            'clubs_in_response' => $response_data['clubs']
        ]);

        return Inertia::render('Teams/Create', $response_data);
    }

    /**
     * Store a newly created team in storage.
     */
    public function store(Request $request)
    {
        // Debug logging - Request start
        \Log::info('Teams Store - Request received', [
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
            'request_data' => $request->all(),
            'session_id' => $request->session()->getId(),
            'is_authenticated' => auth()->check(),
            'auth_guard' => config('auth.defaults.guard'),
        ]);

        try {
            $this->authorize('create', Team::class);
            
            \Log::info('Teams Store - Authorization passed', [
                'user_id' => auth()->id(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Teams Store - Authorization failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'club_id' => 'required|exists:clubs,id',
                'season' => 'required|string|max:9',
                'league' => 'nullable|string|max:255',
                'division' => 'nullable|string|max:255',
                'age_group' => 'nullable|in:u8,u10,u12,u14,u16,u18,u20,senior,masters,veterans',
                'gender' => 'required|in:male,female,mixed',
                'is_active' => 'boolean',
                'description' => 'nullable|string|max:1000',
            ]);
            
            \Log::info('Teams Store - Validation passed', [
                'validated_data' => $validated,
            ]);
        } catch (\Exception $e) {
            \Log::error('Teams Store - Validation failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);
            throw $e;
        }

        try {
            $team = $this->teamService->createTeam($validated);
            
            \Log::info('Teams Store - Team created successfully', [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'club_id' => $team->club_id,
            ]);

            return redirect()->route('web.teams.show', $team)
                ->with('success', 'Team wurde erfolgreich erstellt.');
                
        } catch (\Exception $e) {
            \Log::error('Teams Store - Team creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'validated_data' => $validated,
            ]);
            
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Team konnte nicht erstellt werden: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified team.
     */
    public function show(Team $team): Response
    {
        // Debug logging to identify the issue
        \Log::info('Web TeamController show - Request received', [
            'team_id' => $team->id,
            'request_url' => request()->url(),
            'request_path' => request()->path(),
            'is_inertia' => request()->header('X-Inertia') ? true : false,
            'accepts_json' => request()->wantsJson(),
            'user_agent' => request()->userAgent(),
            'method' => request()->method(),
        ]);
        
        $this->authorize('view', $team);

        $team->load([
            'club',
            'headCoach',
            'players.user',
            'homeGames.awayTeam',
            'awayGames.homeTeam'
        ]);

        $teamStats = $this->teamService->getTeamStatistics($team);

        // Convert team to simple array for Inertia (avoid Resource serialization)
        $teamData = $team->toArray();
        $teamData['club'] = $team->club?->toArray();
        $teamData['head_coach'] = $team->headCoach?->toArray();
        $teamData['players'] = $team->players->map(function($player) {
            $playerData = $player->toArray();
            $playerData['user'] = $player->user?->toArray();
            return $playerData;
        })->toArray();

        \Log::info('Web TeamController show - Returning Inertia response', [
            'team_id' => $team->id,
            'inertia_page' => 'Teams/Show',
        ]);
        
        return Inertia::render('Teams/Show', [
            'team' => $teamData,
            'statistics' => $teamStats,
            'can' => [
                'update' => auth()->user()->can('update', $team),
                'delete' => auth()->user()->can('delete', $team),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified team.
     */
    public function edit(Team $team): Response
    {
        $this->authorize('update', $team);

        // Load players and NEW coaches relationships from team_coaches table
        $team->load([
            'players.user',
            'teamCoaches.user.roles' // Load coaches with their system roles
        ]);

        $user = auth()->user();
        
        $clubs = Club::query()
            ->select(['id', 'name'])
            ->where('is_active', true)
            ->when($user->hasRole('admin') || $user->hasRole('super_admin'), function ($query) {
                // Admin users see all clubs
                return $query;
            }, function ($query) use ($user) {
                // Other users see only their clubs
                return $query->whereHas('users', function ($subQ) use ($user) {
                    $subQ->where('user_id', $user->id);
                });
            })
            ->orderBy('name')
            ->get();

        // Format team data to match the show() method structure
        $teamData = $team->toArray();
        $teamData['players'] = $team->players->map(function($player) {
            $playerData = $player->toArray();
            $playerData['user'] = $player->user?->toArray();
            return $playerData;
        })->toArray();

        // Add coaches data from NEW team_coaches table with system roles
        // Filter out coaches with deleted users to prevent null pointer errors
        $teamData['coaches'] = $team->teamCoaches
            ->filter(fn($teamCoach) => $teamCoach->user !== null)
            ->map(function($teamCoach) {
                return [
                    'id' => $teamCoach->user->id,
                    'name' => $teamCoach->user->name,
                    'email' => $teamCoach->user->email,
                    'role' => $teamCoach->role, // head_coach or assistant_coach
                    'coaching_license' => $teamCoach->coaching_license,
                    'coaching_certifications' => $teamCoach->coaching_certifications ?? [],
                    'coaching_specialties' => $teamCoach->coaching_specialties,
                    'joined_at' => $teamCoach->joined_at,
                    'is_active' => $teamCoach->is_active,
                    // NEW: System roles for multi-role support
                    'system_roles' => $teamCoach->user->roles->pluck('name')->toArray(),
                    'system_role_labels' => $this->getRoleLabels($teamCoach->user->roles),
                ];
            })->values()->toArray();

        return Inertia::render('Teams/Edit', [
            'team' => $teamData,
            'clubs' => $clubs->toArray(),
        ]);
    }

    /**
     * Update the specified team in storage.
     */
    public function update(Request $request, Team $team)
    {
        $this->authorize('update', $team);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'club_id' => 'required|exists:clubs,id',
            'season' => 'required|string|max:9',
            'league' => 'nullable|string|max:255',
            'division' => 'nullable|string|max:255',
            'age_group' => 'nullable|in:u8,u10,u12,u14,u16,u18,u20,senior,masters,veterans',
            'gender' => 'required|in:male,female,mixed',
            'is_active' => 'boolean',
            'is_recruiting' => 'boolean',
            'max_players' => 'required|integer|min:5|max:20',
            'min_players' => 'required|integer|min:3|max:15',
            'description' => 'nullable|string|max:1000',
        ]);

        $this->teamService->updateTeam($team, $validated);

        return redirect()->route('web.teams.show', $team)
            ->with('success', 'Team wurde erfolgreich aktualisiert.');
    }

    /**
     * Remove the specified team from storage.
     */
    public function destroy(Team $team)
    {
        $this->authorize('delete', $team);

        $this->teamService->deleteTeam($team);

        return redirect()->route('web.teams.index')
            ->with('success', 'Team wurde erfolgreich gelöscht.');
    }

    /**
     * Get players for a specific team.
     */
    public function players(Team $team)
    {
        $this->authorize('view', $team);

        $players = $team->players()
            ->with('user')
            ->orderBy('jersey_number')
            ->orderBy('is_starter', 'desc')
            ->get();

        return response()->json([
            'players' => $players->map(function ($player) {
                return [
                    'id' => $player->id,
                    'user' => $player->user,
                    'pivot' => [
                        'jersey_number' => $player->pivot->jersey_number,
                        'primary_position' => $player->pivot->primary_position,
                        'secondary_positions' => $player->pivot->secondary_positions,
                        'is_active' => $player->pivot->is_active,
                        'is_starter' => $player->pivot->is_starter,
                        'is_captain' => $player->pivot->is_captain,
                        'status' => $player->pivot->status,
                        'joined_at' => $player->pivot->joined_at,
                        'notes' => $player->pivot->notes,
                    ]
                ];
            })
        ]);
    }

    /**
     * Attach a player to a team with pivot data.
     */
    public function attachPlayer(Request $request, Team $team)
    {
        $this->authorize('update', $team);

        $validated = $request->validate([
            'player_ids' => 'required|array',
            'player_ids.*' => 'exists:players,id',
            'jersey_number' => 'nullable|integer|between:0,99',
            'primary_position' => 'nullable|in:PG,SG,SF,PF,C',
            'secondary_positions' => 'nullable|array',
            'secondary_positions.*' => 'in:PG,SG,SF,PF,C',
            'is_starter' => 'boolean',
            'is_captain' => 'boolean',
            'status' => 'in:active,inactive,injured,suspended,on_loan',
            'notes' => 'nullable|string|max:1000',
        ]);

        $playerIds = $validated['player_ids'];
        unset($validated['player_ids']);

        $pivotData = array_merge($validated, [
            'joined_at' => now(),
            'is_active' => true,
        ]);

        foreach ($playerIds as $playerId) {
            // Check if player is already in the team
            if ($team->players()->where('player_id', $playerId)->exists()) {
                continue;
            }

            // Check jersey number uniqueness within team
            if (isset($validated['jersey_number'])) {
                $existingJersey = $team->players()
                    ->wherePivot('jersey_number', $validated['jersey_number'])
                    ->wherePivot('is_active', true)
                    ->exists();

                if ($existingJersey) {
                    return redirect()->back()->withErrors([
                        'jersey_number' => "Trikotnummer {$validated['jersey_number']} ist bereits vergeben."
                    ]);
                }
            }

            $team->players()->attach($playerId, $pivotData);
        }

        return redirect()->back()->with('success', 'Spieler wurde(n) erfolgreich zum Team hinzugefügt.');
    }

    /**
     * Update player's pivot data in the team.
     */
    public function updatePlayer(Request $request, Team $team, Player $player)
    {
        $this->authorize('update', $team);

        // Check if player is actually in this team
        if (!$team->players()->where('player_id', $player->id)->exists()) {
            return response()->json(['error' => 'Spieler ist nicht in diesem Team.'], 404);
        }

        $validated = $request->validate([
            'jersey_number' => 'nullable|integer|between:0,99',
            'primary_position' => 'nullable|in:PG,SG,SF,PF,C',
            'secondary_positions' => 'nullable|array',
            'secondary_positions.*' => 'in:PG,SG,SF,PF,C',
            'is_active' => 'boolean',
            'is_starter' => 'boolean',
            'is_captain' => 'boolean',
            'status' => 'in:active,inactive,injured,suspended,on_loan',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Check jersey number uniqueness within team (exclude current player)
        if (isset($validated['jersey_number'])) {
            $existingJersey = $team->players()
                ->wherePivot('jersey_number', $validated['jersey_number'])
                ->wherePivot('is_active', true)
                ->where('player_id', '!=', $player->id)
                ->exists();

            if ($existingJersey) {
                return response()->json([
                    'error' => "Trikotnummer {$validated['jersey_number']} ist bereits vergeben."
                ], 422);
            }
        }

        $team->players()->updateExistingPivot($player->id, $validated);

        return response()->json([
            'message' => 'Spielerdaten wurden erfolgreich aktualisiert.',
        ]);
    }

    /**
     * Detach a player from a team.
     */
    public function detachPlayer(Team $team, Player $player)
    {
        $this->authorize('update', $team);

        if (!$team->players()->where('player_id', $player->id)->exists()) {
            return response()->json(['error' => 'Spieler ist nicht in diesem Team.'], 404);
        }

        $team->players()->detach($player->id);

        return response()->json([
            'message' => 'Spieler wurde erfolgreich vom Team entfernt.',
        ]);
    }

    /**
     * Get available coaches for a club.
     *
     * Returns all users with 'trainer' role in the specified club,
     * including ALL their system roles for display in the UI.
     */
    public function getAvailableCoaches(Request $request, Club $club)
    {
        // Authorization: User must be able to assign coaches
        $this->authorize('assignCoaches', Team::class);

        // Get users with 'trainer' role in this club WITH all their roles
        $coaches = $club->users()
            ->whereHas('roles', function($q) {
                $q->where('name', 'trainer');
            })
            ->where('is_active', true)
            ->with('roles:id,name')  // Load all system roles
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name'),  // All role names
                    'role_labels' => $this->getRoleLabels($user->roles),  // German labels
                ];
            });

        return response()->json([
            'coaches' => $coaches,
        ]);
    }

    /**
     * Get German labels for system roles.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $roles
     * @return array
     */
    private function getRoleLabels($roles)
    {
        $labels = [
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'club_admin' => 'Club Admin',
            'trainer' => 'Trainer',
            'assistant_coach' => 'Co-Trainer',
            'player' => 'Spieler',
            'parent' => 'Elternteil',
            'scorer' => 'Anschreiber',
            'referee' => 'Schiedsrichter',
            'team_manager' => 'Team Manager',
            'guest' => 'Gast',
        ];

        return $roles->map(fn($role) => $labels[$role->name] ?? $role->name)->toArray();
    }

    /**
     * Assign a head coach to a team.
     *
     * Uses the NEW team_coaches table, allowing multi-role users (e.g., player-coach).
     * Validates that the user has trainer role and belongs to the same club.
     */
    public function assignHeadCoach(Request $request, Team $team)
    {
        $this->authorize('assignCoaches', $team);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'coaching_license' => 'nullable|string|max:255',
            'coaching_certifications' => 'nullable|array',
            'coaching_specialties' => 'nullable|string|max:1000',
        ]);

        $user = User::findOrFail($validated['user_id']);

        // Validate user has trainer role
        if (!$user->hasRole('trainer')) {
            return back()->withErrors([
                'user_id' => 'Der ausgewählte Benutzer hat keine Trainer-Rolle.'
            ]);
        }

        // Validate user belongs to same club
        if (!$user->clubs()->where('club_id', $team->club_id)->exists()) {
            return back()->withErrors([
                'user_id' => 'Der Trainer gehört nicht zum gleichen Club.'
            ]);
        }

        DB::transaction(function() use ($team, $user, $validated) {
            // Update legacy field for backward compatibility
            $team->head_coach_id = $user->id;
            $team->save();

            // Deactivate any existing head coach in team_coaches table
            TeamCoach::where('team_id', $team->id)
                ->where('role', 'head_coach')
                ->where('user_id', '!=', $user->id)
                ->update(['is_active' => false]);

            // Create or update head coach in team_coaches table
            TeamCoach::updateOrCreate(
                [
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                    'role' => 'head_coach',
                ],
                [
                    'coaching_license' => $validated['coaching_license'] ?? null,
                    'coaching_certifications' => $validated['coaching_certifications'] ?? null,
                    'coaching_specialties' => $validated['coaching_specialties'] ?? null,
                    'joined_at' => now(),
                    'is_active' => true,
                ]
            );
        });

        return redirect()->back()->with('success', 'Haupttrainer erfolgreich zugewiesen.');
    }

    /**
     * Manage assistant coaches for a team.
     *
     * Uses the NEW team_coaches table. Adds or removes assistant coaches.
     * Supports unlimited assistant coaches without conflicts with player role.
     */
    public function manageAssistantCoaches(Request $request, Team $team)
    {
        $this->authorize('assignCoaches', $team);

        $validated = $request->validate([
            'action' => 'required|in:add,remove',
            'user_id' => 'required|exists:users,id',
            'coaching_license' => 'nullable|string|max:255',
            'coaching_certifications' => 'nullable|array',
            'coaching_specialties' => 'nullable|string|max:1000',
        ]);

        $user = User::findOrFail($validated['user_id']);

        if ($validated['action'] === 'add') {
            // Validate user has trainer role
            if (!$user->hasRole('trainer')) {
                return back()->withErrors([
                    'user_id' => 'Der ausgewählte Benutzer hat keine Trainer-Rolle.'
                ]);
            }

            // Validate user belongs to same club
            if (!$user->clubs()->where('club_id', $team->club_id)->exists()) {
                return back()->withErrors([
                    'user_id' => 'Der Trainer gehört nicht zum gleichen Club.'
                ]);
            }

            // Check if user is already an assistant coach on this team
            $existingCoach = TeamCoach::where('team_id', $team->id)
                ->where('user_id', $user->id)
                ->where('role', 'assistant_coach')
                ->where('is_active', true)
                ->first();

            if ($existingCoach) {
                return back()->withErrors([
                    'user_id' => 'Dieser Trainer ist bereits als Co-Trainer diesem Team zugeordnet.'
                ]);
            }

            DB::transaction(function() use ($team, $user, $validated) {
                // Update legacy field (assistant_coaches array)
                $assistants = $team->assistant_coaches ?? [];
                if (!in_array($user->id, $assistants)) {
                    $assistants[] = $user->id;
                    $team->assistant_coaches = $assistants;
                    $team->save();
                }

                // Create in team_coaches table
                TeamCoach::create([
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                    'role' => 'assistant_coach',
                    'coaching_license' => $validated['coaching_license'] ?? null,
                    'coaching_certifications' => $validated['coaching_certifications'] ?? null,
                    'coaching_specialties' => $validated['coaching_specialties'] ?? null,
                    'joined_at' => now(),
                    'is_active' => true,
                ]);
            });

            return redirect()->back()->with('success', 'Co-Trainer erfolgreich hinzugefügt.');

        } else {
            // Remove assistant coach
            DB::transaction(function() use ($team, $user) {
                // Update legacy field
                $assistants = $team->assistant_coaches ?? [];
                if (($key = array_search($user->id, $assistants)) !== false) {
                    unset($assistants[$key]);
                    $team->assistant_coaches = array_values($assistants);
                    $team->save();
                }

                // Delete from team_coaches table
                TeamCoach::where('team_id', $team->id)
                    ->where('user_id', $user->id)
                    ->where('role', 'assistant_coach')
                    ->delete();
            });

            return redirect()->back()->with('success', 'Co-Trainer erfolgreich entfernt.');
        }
    }

    /**
     * Update coach details (license, certifications, specialties).
     *
     * Updates the team_coaches table information for a coach.
     */
    public function updateCoachDetails(Request $request, Team $team, User $user)
    {
        $this->authorize('assignCoaches', $team);

        // Verify user is a coach on this team in team_coaches table
        $teamCoach = TeamCoach::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$teamCoach) {
            return back()->withErrors([
                'error' => 'Dieser Benutzer ist kein aktiver Trainer dieses Teams.'
            ]);
        }

        $validated = $request->validate([
            'coaching_license' => 'nullable|string|max:255',
            'coaching_certifications' => 'nullable|array',
            'coaching_specialties' => 'nullable|string|max:1000',
        ]);

        // Update team_coaches record
        $teamCoach->update([
            'coaching_license' => $validated['coaching_license'] ?? null,
            'coaching_certifications' => $validated['coaching_certifications'] ?? null,
            'coaching_specialties' => $validated['coaching_specialties'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Trainer-Details erfolgreich aktualisiert.');
    }
}