<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\Team;
use App\Services\PlayerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    public function __construct(
        private PlayerService $playerService
    ) {}

    /**
     * Display a listing of players.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Get players based on user permissions
        $players = Player::query()
            ->with(['teams.club', 'user'])
            ->join('users', 'players.user_id', '=', 'users.id')
            ->when($user->hasRole('admin') || $user->hasRole('super_admin'), function ($query) {
                // Admin users see all players
                return $query;
            }, function ($query) use ($user) {
                // Other users see players from their teams/clubs
                return $query->whereHas('teams', function ($q) use ($user) {
                    $q->where('head_coach_id', $user->id)
                      ->orWhereJsonContains('assistant_coaches', $user->id)
                      ->orWhereHas('club.users', function ($subQ) use ($user) {
                          $subQ->where('user_id', $user->id);
                      });
                });
            })
            ->leftJoin('player_team', 'players.id', '=', 'player_team.player_id')
            ->where('player_team.is_active', true)
            ->orderBy('player_team.jersey_number')
            ->orderBy('users.name')
            ->select('players.*')
            ->distinct()
            ->paginate(20);

        return Inertia::render('Players/Index', [
            'players' => $players,
            'can' => [
                'create' => $user->can('create', Player::class),
            ],
        ]);
    }

    /**
     * Show the form for creating a new player.
     */
    public function create(): Response
    {
        $this->authorize('create', Player::class);

        $user = auth()->user();
        
        $teams = Team::query()
            ->with('club')
            ->select(['id', 'name', 'club_id'])
            ->where('is_active', true)
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
            ->get();

        return Inertia::render('Players/Create', [
            'teams' => $teams,
        ]);
    }

    /**
     * Store a newly created player in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Player::class);

        $validated = $request->validate([
            // User Information (these will be handled separately for User model)
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            
            // Player Basic Info
            'team_id' => 'required|exists:teams,id',
            'jersey_number' => 'required|integer|min:0|max:99',
            'primary_position' => 'required|in:PG,SG,SF,PF,C',
            'secondary_positions' => 'nullable|array',
            'secondary_positions.*' => 'in:PG,SG,SF,PF,C',
            
            // Physical Information
            'height_cm' => 'nullable|integer|min:100|max:250',
            'weight_kg' => 'nullable|numeric|min:30|max:200',
            'dominant_hand' => 'nullable|in:left,right,ambidextrous',
            'shoe_size' => 'nullable|string|max:10',
            
            // Basketball Experience
            'started_playing' => 'nullable|date|before_or_equal:today',
            'years_experience' => 'nullable|integer|min:0|max:50',
            'previous_teams' => 'nullable|array',
            'achievements' => 'nullable|array',
            
            // Player Status
            'status' => 'required|in:active,inactive,injured,suspended',
            'is_starter' => 'boolean',
            'is_captain' => 'boolean',
            'is_rookie' => 'boolean',
            
            // Contract Information
            'contract_start' => 'nullable|date',
            'contract_end' => 'nullable|date|after:contract_start',
            'registration_number' => 'nullable|string|max:50|unique:players,registration_number',
            
            // Medical Information
            'medical_conditions' => 'nullable|array',
            'allergies' => 'nullable|array',
            'medications' => 'nullable|array',
            'blood_type' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'medical_clearance' => 'boolean',
            'medical_clearance_expires' => 'nullable|date|after:today',
            'preferred_hospital' => 'nullable|string|max:255',
            'medical_notes' => 'nullable|string|max:2000',
            
            // Insurance Information
            'insurance_provider' => 'nullable|string|max:255',
            'insurance_policy_number' => 'nullable|string|max:100',
            'insurance_expires' => 'nullable|date|after:today',
            
            // Emergency Contacts
            'emergency_medical_contact' => 'nullable|string|max:255',
            'emergency_medical_phone' => 'nullable|string|max:20',
            'parent_user_id' => 'nullable|exists:users,id',
            'guardian_contacts' => 'nullable|array',
            
            // Development & Training
            'training_focus_areas' => 'nullable|array',
            'development_goals' => 'nullable|array',
            'coach_notes' => 'nullable|string|max:2000',
            
            // Academic Information (for minors)
            'school_name' => 'nullable|string|max:255',
            'grade_level' => 'nullable|string|max:20',
            'gpa' => 'nullable|numeric|min:1.0|max:4.0',
            'academic_eligibility' => 'boolean',
            
            // Preferences
            'preferences' => 'nullable|array',
            'dietary_restrictions' => 'nullable|array',
            'social_media' => 'nullable|array',
            'allow_photos' => 'boolean',
            'allow_media_interviews' => 'boolean',
        ]);

        // Check jersey number uniqueness within team
        $existingPlayer = Player::query()
            ->join('player_team', 'players.id', '=', 'player_team.player_id')
            ->where('player_team.team_id', $validated['team_id'])
            ->where('player_team.jersey_number', $validated['jersey_number'])
            ->where('player_team.is_active', true)
            ->first();

        if ($existingPlayer) {
            return back()->withErrors([
                'jersey_number' => 'Diese Rückennummer ist bereits im Team vergeben.'
            ]);
        }

        $player = $this->playerService->createPlayer($validated);

        return redirect()->route('players.show', $player)
            ->with('success', 'Spieler wurde erfolgreich erstellt.');
    }

    /**
     * Display the specified player.
     */
    public function show(Player $player): Response
    {
        $this->authorize('view', $player);

        // Load all relevant relationships
        $player->load([
            'teams.club',
            'user',
            'parent',
            'gameActions.game'
        ]);

        // Add computed attributes to the player
        $player->append([
            'full_name',
            'display_name',
            'height_feet',
            'field_goal_percentage',
            'three_point_percentage',
            'free_throw_percentage',
            'points_per_game',
            'rebounds_per_game',
            'assists_per_game',
            'age',
            'all_positions',
            'medical_clearance_expired',
            'insurance_expired'
        ]);

        $playerStats = $this->playerService->getPlayerStatistics($player, $player->primaryTeam()?->season);

        return Inertia::render('Players/Show', [
            'player' => $player,
            'statistics' => $playerStats,
            'can' => [
                'update' => auth()->user()->can('update', $player),
                'delete' => auth()->user()->can('delete', $player),
                'view_medical' => auth()->user()->can('view', $player) && 
                    (auth()->user()->hasRole(['admin', 'club_admin', 'trainer']) || auth()->id() === $player->user_id),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified player.
     */
    public function edit(Player $player): Response
    {
        $this->authorize('update', $player);

        $user = auth()->user();
        
        // Load player with proper relationships and pivot data
        $player->load([
            'teams.club',
            'user',
            'parent'
        ]);
        
        // Get the primary team for form population
        $primaryTeam = $player->primaryTeam();
        
        // Structure player data with team-specific fields from pivot
        $playerData = $player->toArray();
        
        if ($primaryTeam) {
            $playerData['team_id'] = $primaryTeam->id;
            $playerData['jersey_number'] = $primaryTeam->pivot->jersey_number;
            $playerData['primary_position'] = $primaryTeam->pivot->primary_position;
            $playerData['secondary_positions'] = $primaryTeam->pivot->secondary_positions ?? [];
            $playerData['status'] = $primaryTeam->pivot->status ?? 'active';
            $playerData['is_captain'] = $primaryTeam->pivot->is_captain ?? false;
            $playerData['is_starter'] = $primaryTeam->pivot->is_starter ?? false;
            $playerData['contract_start'] = $primaryTeam->pivot->contract_start;
            $playerData['contract_end'] = $primaryTeam->pivot->contract_end;
            $playerData['registration_number'] = $primaryTeam->pivot->registration_number;
        }
        
        // Add user data if available
        if ($player->user) {
            $playerData['first_name'] = $player->user->first_name ?? '';
            $playerData['last_name'] = $player->user->last_name ?? '';
            $playerData['email'] = $player->user->email ?? '';
            $playerData['phone'] = $player->user->phone ?? '';
            $playerData['birth_date'] = $player->user->birth_date ?? '';
            $playerData['gender'] = $player->user->gender ?? '';
        }
        
        $teams = Team::query()
            ->with('club')
            ->select(['id', 'name', 'club_id'])
            ->where('is_active', true)
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
            ->get();

        return Inertia::render('Players/Edit', [
            'player' => $playerData,
            'teams' => $teams,
        ]);
    }

    /**
     * Update the specified player in storage.
     */
    public function update(Request $request, Player $player)
    {
        $this->authorize('update', $player);

        $validated = $request->validate([
            // User Information (these will be handled separately for User model)
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $player->user_id,
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            
            // Player Basic Info
            'team_id' => 'required|exists:teams,id',
            'jersey_number' => 'required|integer|min:0|max:99',
            'primary_position' => 'required|in:PG,SG,SF,PF,C',
            'secondary_positions' => 'nullable|array',
            'secondary_positions.*' => 'in:PG,SG,SF,PF,C',
            
            // Physical Information
            'height_cm' => 'nullable|integer|min:100|max:250',
            'weight_kg' => 'nullable|numeric|min:30|max:200',
            'dominant_hand' => 'nullable|in:left,right,ambidextrous',
            'shoe_size' => 'nullable|string|max:10',
            
            // Basketball Experience
            'started_playing' => 'nullable|date|before_or_equal:today',
            'years_experience' => 'nullable|integer|min:0|max:50',
            'previous_teams' => 'nullable|array',
            'achievements' => 'nullable|array',
            
            // Player Status
            'status' => 'required|in:active,inactive,injured,suspended',
            'is_starter' => 'boolean',
            'is_captain' => 'boolean',
            'is_rookie' => 'boolean',
            
            // Contract Information
            'contract_start' => 'nullable|date',
            'contract_end' => 'nullable|date|after:contract_start',
            'registration_number' => 'nullable|string|max:50|unique:players,registration_number,' . $player->id,
            
            // Medical Information
            'medical_conditions' => 'nullable|array',
            'allergies' => 'nullable|array',
            'medications' => 'nullable|array',
            'blood_type' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'medical_clearance' => 'boolean',
            'medical_clearance_expires' => 'nullable|date|after:today',
            'preferred_hospital' => 'nullable|string|max:255',
            'medical_notes' => 'nullable|string|max:2000',
            
            // Insurance Information
            'insurance_provider' => 'nullable|string|max:255',
            'insurance_policy_number' => 'nullable|string|max:100',
            'insurance_expires' => 'nullable|date|after:today',
            
            // Emergency Contacts
            'emergency_medical_contact' => 'nullable|string|max:255',
            'emergency_medical_phone' => 'nullable|string|max:20',
            'parent_user_id' => 'nullable|exists:users,id',
            'guardian_contacts' => 'nullable|array',
            
            // Development & Training
            'training_focus_areas' => 'nullable|array',
            'development_goals' => 'nullable|array',
            'coach_notes' => 'nullable|string|max:2000',
            
            // Academic Information (for minors)
            'school_name' => 'nullable|string|max:255',
            'grade_level' => 'nullable|string|max:20',
            'gpa' => 'nullable|numeric|min:1.0|max:4.0',
            'academic_eligibility' => 'boolean',
            
            // Preferences
            'preferences' => 'nullable|array',
            'dietary_restrictions' => 'nullable|array',
            'social_media' => 'nullable|array',
            'allow_photos' => 'boolean',
            'allow_media_interviews' => 'boolean',
        ]);

        // Check jersey number uniqueness within team (excluding current player)
        $existingPlayer = Player::query()
            ->join('player_team', 'players.id', '=', 'player_team.player_id')
            ->where('player_team.team_id', $validated['team_id'])
            ->where('player_team.jersey_number', $validated['jersey_number'])
            ->where('player_team.is_active', true)
            ->where('players.id', '!=', $player->id)
            ->first();

        if ($existingPlayer) {
            return back()->withErrors([
                'jersey_number' => 'Diese Rückennummer ist bereits im Team vergeben.'
            ]);
        }

        $this->playerService->updatePlayer($player, $validated);

        return redirect()->route('players.show', $player)
            ->with('success', 'Spieler wurde erfolgreich aktualisiert.');
    }

    /**
     * Remove the specified player from storage.
     */
    public function destroy(Player $player)
    {
        $this->authorize('delete', $player);

        $this->playerService->deletePlayer($player);

        return redirect()->route('players.index')
            ->with('success', 'Spieler wurde erfolgreich gelöscht.');
    }

    /**
     * Update medical information for a player.
     */
    public function updateMedical(Request $request, Player $player)
    {
        $this->authorize('update', $player);

        $validated = $request->validate([
            'medical_conditions' => 'nullable|array',
            'allergies' => 'nullable|array',
            'medications' => 'nullable|array',
            'blood_type' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'medical_clearance' => 'boolean',
            'medical_clearance_expires' => 'nullable|date|after:today',
            'last_medical_check' => 'nullable|date|before_or_equal:today',
            'preferred_hospital' => 'nullable|string|max:255',
            'medical_notes' => 'nullable|string|max:2000',
            'insurance_provider' => 'nullable|string|max:255',
            'insurance_policy_number' => 'nullable|string|max:100',
            'insurance_expires' => 'nullable|date|after:today',
            'emergency_medical_contact' => 'nullable|string|max:255',
            'emergency_medical_phone' => 'nullable|string|max:20',
            'guardian_contacts' => 'nullable|array',
        ]);

        $player->update($validated);

        return back()->with('success', 'Medizinische Informationen wurden erfolgreich aktualisiert.');
    }

    /**
     * Update development information for a player.
     */
    public function updateDevelopment(Request $request, Player $player)
    {
        $this->authorize('update', $player);

        $validated = $request->validate([
            'training_focus_areas' => 'nullable|array',
            'development_goals' => 'nullable|array',
            'coach_notes' => 'nullable|string|max:2000',
            'shooting_rating' => 'nullable|numeric|min:1|max:10',
            'defense_rating' => 'nullable|numeric|min:1|max:10',
            'passing_rating' => 'nullable|numeric|min:1|max:10',
            'rebounding_rating' => 'nullable|numeric|min:1|max:10',
            'speed_rating' => 'nullable|numeric|min:1|max:10',
            'overall_rating' => 'nullable|numeric|min:1|max:10',
        ]);

        $player->update($validated);

        return back()->with('success', 'Entwicklungsinformationen wurden erfolgreich aktualisiert.');
    }

    /**
     * Update player preferences.
     */
    public function updatePreferences(Request $request, Player $player)
    {
        $this->authorize('update', $player);

        $validated = $request->validate([
            'preferences' => 'nullable|array',
            'dietary_restrictions' => 'nullable|array',
            'social_media' => 'nullable|array',
            'allow_photos' => 'boolean',
            'allow_media_interviews' => 'boolean',
        ]);

        $player->update($validated);

        return back()->with('success', 'Präferenzen wurden erfolgreich aktualisiert.');
    }

    /**
     * Bulk update player status.
     */
    public function bulkUpdateStatus(Request $request)
    {
        $this->authorize('create', Player::class);

        $validated = $request->validate([
            'player_ids' => 'required|array',
            'player_ids.*' => 'exists:players,id',
            'status' => 'required|in:active,inactive,injured,suspended',
        ]);

        Player::whereIn('id', $validated['player_ids'])
            ->update(['status' => $validated['status']]);

        $count = count($validated['player_ids']);
        return back()->with('success', "{$count} Spieler wurden erfolgreich aktualisiert.");
    }

    /**
     * Export player statistics.
     */
    public function exportStats(Player $player)
    {
        $this->authorize('view', $player);

        return $this->playerService->exportPlayerStatistics($player);
    }

    /**
     * Get emergency contacts for quick access.
     */
    public function emergencyContacts(Player $player)
    {
        $this->authorize('view', $player);

        $contacts = $player->getEmergencyContacts();

        return response()->json([
            'player' => [
                'id' => $player->id,
                'name' => $player->full_name,
                'jersey_number' => $player->primaryTeam()?->pivot->jersey_number,
                'team' => $player->primaryTeam()?->name,
            ],
            'contacts' => $contacts,
            'medical_info' => [
                'medical_conditions' => $player->medical_conditions,
                'allergies' => $player->allergies,
                'medications' => $player->medications,
                'blood_type' => $player->blood_type,
                'preferred_hospital' => $player->preferred_hospital,
            ]
        ]);
    }

    /**
     * Search for players (API endpoint for team management)
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $excludeTeam = $request->get('exclude_team');
        
        if (strlen($query) < 2) {
            return response()->json(['players' => []]);
        }

        $playersQuery = Player::query()
            ->with('user')
            ->join('users', 'players.user_id', '=', 'users.id')
            ->where(function($q) use ($query) {
                $q->where('users.name', 'LIKE', "%{$query}%")
                  ->orWhere('users.email', 'LIKE', "%{$query}%");
            });

        // Exclude players already in a specific team
        if ($excludeTeam) {
            $playersQuery->whereNotExists(function($q) use ($excludeTeam) {
                $q->select('*')
                  ->from('player_team')
                  ->whereColumn('player_team.player_id', 'players.id')
                  ->where('player_team.team_id', $excludeTeam)
                  ->where('player_team.is_active', true);
            });
        }

        $players = $playersQuery
            ->select('players.*')
            ->distinct()
            ->limit(20)
            ->get();

        return response()->json([
            'players' => $players->map(function ($player) {
                return [
                    'id' => $player->id,
                    'user' => $player->user ? [
                        'id' => $player->user->id,
                        'name' => $player->user->name,
                        'email' => $player->user->email,
                    ] : null
                ];
            })
        ]);
    }
}