<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V2\Players\StorePlayerRequest;
use App\Http\Requests\Api\V2\Players\UpdatePlayerRequest;
use App\Http\Requests\Api\V2\Players\IndexPlayersRequest;
use App\Http\Resources\PlayerResource;
use App\Models\Player;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PlayerController extends Controller
{
    /**
     * Display a listing of players.
     */
    public function index(IndexPlayersRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Player::class);

        $players = Player::query()
            ->with(['user:id,name,birth_date', 'team.club:id,name'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                      ->orWhere('jersey_number', $search)
                      ->orWhereHas('user', function ($userQuery) use ($search) {
                          $userQuery->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->when($request->filled('team_id'), function ($query) use ($request) {
                $query->where('team_id', $request->team_id);
            })
            ->when($request->filled('club_id'), function ($query) use ($request) {
                $query->whereHas('team', function ($q) use ($request) {
                    $q->where('club_id', $request->club_id);
                });
            })
            ->when($request->filled('position'), function ($query) use ($request) {
                $query->where('primary_position', $request->position);
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('is_captain'), function ($query) use ($request) {
                $captain = $request->is_captain === 'true';
                $query->where('is_captain', $captain);
            })
            ->when($request->filled('is_starter'), function ($query) use ($request) {
                $starter = $request->is_starter === 'true';
                $query->where('is_starter', $starter);
            })
            ->when($request->filled('min_age'), function ($query) use ($request) {
                $maxBirthDate = now()->subYears($request->min_age);
                $query->whereHas('user', function ($q) use ($maxBirthDate) {
                    $q->where('birth_date', '<=', $maxBirthDate);
                });
            })
            ->when($request->filled('max_age'), function ($query) use ($request) {
                $minBirthDate = now()->subYears($request->max_age + 1);
                $query->whereHas('user', function ($q) use ($minBirthDate) {
                    $q->where('birth_date', '>', $minBirthDate);
                });
            })
            ->when($request->filled('season'), function ($query) use ($request) {
                $query->whereHas('team', function ($q) use ($request) {
                    $q->where('season', $request->season);
                });
            })
            ->when($request->filled('sort'), function ($query) use ($request) {
                $sortField = $request->sort;
                $sortDirection = $request->filled('direction') && $request->direction === 'desc' ? 'desc' : 'asc';
                
                $allowedSortFields = ['jersey_number', 'created_at', 'points_scored', 'games_played'];
                if (in_array($sortField, $allowedSortFields)) {
                    $query->orderBy($sortField, $sortDirection);
                } elseif ($sortField === 'name') {
                    $query->join('users', 'players.user_id', '=', 'users.id')
                          ->orderBy('users.name', $sortDirection)
                          ->select('players.*');
                }
            })
            ->latest()
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        return PlayerResource::collection($players);
    }

    /**
     * Store a newly created player.
     */
    public function store(StorePlayerRequest $request): PlayerResource
    {
        $this->authorize('create', Player::class);

        $playerData = $request->validated();
        $player = Player::create($playerData);

        // Create emergency contacts if provided
        if (isset($playerData['emergency_contacts'])) {
            foreach ($playerData['emergency_contacts'] as $contactData) {
                $player->emergencyContacts()->create($contactData);
            }
        }

        return new PlayerResource($player->load(['user', 'team.club', 'emergencyContacts']));
    }

    /**
     * Display the specified player.
     */
    public function show(Player $player): PlayerResource
    {
        $this->authorize('view', $player);

        $player->load([
            'user:id,name,email,birth_date,phone',
            'team.club:id,name',
            'team.headCoach:id,name',
            'emergencyContacts' => function ($query) {
                $query->orderBy('is_primary', 'desc');
            },
        ]);

        return new PlayerResource($player);
    }

    /**
     * Update the specified player.
     */
    public function update(UpdatePlayerRequest $request, Player $player): PlayerResource
    {
        $this->authorize('update', $player);

        $playerData = $request->validated();
        $player->update($playerData);

        // Update emergency contacts if provided
        if (isset($playerData['emergency_contacts'])) {
            $player->emergencyContacts()->delete();
            foreach ($playerData['emergency_contacts'] as $contactData) {
                $player->emergencyContacts()->create($contactData);
            }
        }

        return new PlayerResource($player->load(['user', 'team.club', 'emergencyContacts']));
    }

    /**
     * Remove the specified player.
     */
    public function destroy(Player $player): JsonResponse
    {
        $this->authorize('delete', $player);

        $player->delete();

        return response()->json([
            'message' => 'Spieler erfolgreich gelöscht.',
        ]);
    }

    /**
     * Get player statistics.
     */
    public function statistics(Player $player, IndexPlayersRequest $request): JsonResponse
    {
        $this->authorize('view', $player);

        $season = $request->get('season', $player->team?->season);
        $statistics = $player->getStatistics();

        // Add per-game averages
        if ($player->games_played > 0) {
            $statistics['per_game'] = [
                'points' => $player->points_per_game,
                'rebounds' => $player->rebounds_per_game,
                'assists' => $player->assists_per_game,
                'minutes' => round($player->minutes_played / $player->games_played, 1),
            ];
        }

        // Add shooting percentages
        $statistics['shooting_percentages'] = [
            'field_goal' => $player->field_goal_percentage,
            'three_point' => $player->three_point_percentage,
            'free_throw' => $player->free_throw_percentage,
        ];

        return response()->json($statistics);
    }

    /**
     * Update player statistics.
     */
    public function updateStatistics(Player $player, UpdatePlayerRequest $request): PlayerResource
    {
        $this->authorize('update', $player);

        $validated = $request->validate([
            'games_played' => 'integer|min:0',
            'games_started' => 'integer|min:0',
            'minutes_played' => 'integer|min:0',
            'points_scored' => 'integer|min:0',
            'field_goals_made' => 'integer|min:0',
            'field_goals_attempted' => 'integer|min:0',
            'three_pointers_made' => 'integer|min:0',
            'three_pointers_attempted' => 'integer|min:0',
            'free_throws_made' => 'integer|min:0',
            'free_throws_attempted' => 'integer|min:0',
            'rebounds_offensive' => 'integer|min:0',
            'rebounds_defensive' => 'integer|min:0',
            'assists' => 'integer|min:0',
            'steals' => 'integer|min:0',
            'blocks' => 'integer|min:0',
            'turnovers' => 'integer|min:0',
            'fouls_personal' => 'integer|min:0',
            'fouls_technical' => 'integer|min:0',
        ]);

        // Calculate total rebounds
        if (isset($validated['rebounds_offensive']) || isset($validated['rebounds_defensive'])) {
            $validated['rebounds_total'] = 
                ($validated['rebounds_offensive'] ?? $player->rebounds_offensive) + 
                ($validated['rebounds_defensive'] ?? $player->rebounds_defensive);
        }

        $player->update($validated);

        return new PlayerResource($player->load(['user', 'team.club']));
    }

    /**
     * Get player's game history.
     */
    public function gameHistory(Player $player, IndexPlayersRequest $request): JsonResponse
    {
        $this->authorize('view', $player);

        // This would need a proper game_player_stats table in a real implementation
        // For now, return mock data structure
        $games = collect([
            // Mock game data - in real implementation, this would come from game_player_stats table
        ]);

        return response()->json([
            'games' => $games,
            'total_games' => $games->count(),
        ]);
    }

    /**
     * Toggle player captain status.
     */
    public function toggleCaptain(Player $player): PlayerResource
    {
        $this->authorize('update', $player);

        $player->update(['is_captain' => !$player->is_captain]);

        return new PlayerResource($player->load(['user', 'team']));
    }

    /**
     * Toggle player starter status.
     */
    public function toggleStarter(Player $player): PlayerResource
    {
        $this->authorize('update', $player);

        $player->update(['is_starter' => !$player->is_starter]);

        return new PlayerResource($player->load(['user', 'team']));
    }

    /**
     * Update player status.
     */
    public function updateStatus(Player $player, UpdatePlayerRequest $request): PlayerResource
    {
        $this->authorize('update', $player);

        $validated = $request->validate([
            'status' => 'required|string|in:active,injured,suspended,inactive,transferred',
        ]);

        $player->update($validated);

        return new PlayerResource($player->load(['user', 'team']));
    }

    /**
     * Get player emergency contacts.
     */
    public function emergencyContacts(Player $player): JsonResponse
    {
        $this->authorize('viewEmergencyContacts', $player);

        $contacts = $player->getEmergencyContacts();

        return response()->json([
            'emergency_contacts' => $contacts,
        ]);
    }

    /**
     * Check if player can play (eligibility check).
     */
    public function eligibility(Player $player): JsonResponse
    {
        $this->authorize('view', $player);

        $eligibility = [
            'can_play' => $player->canPlay(),
            'is_minor' => $player->isMinor(),
            'medical_clearance' => $player->medical_clearance,
            'medical_clearance_expired' => $player->medical_clearance_expired,
            'insurance_expired' => $player->insurance_expired,
            'academic_eligibility' => $player->academic_eligibility,
            'status' => $player->status,
        ];

        return response()->json($eligibility);
    }

    /**
     * Get player development data.
     */
    public function development(Player $player): JsonResponse
    {
        $this->authorize('view', $player);

        $development = [
            'training_focus_areas' => $player->training_focus_areas,
            'development_goals' => $player->development_goals,
            'coach_notes' => $player->coach_notes,
            'ratings' => [
                'shooting' => $player->shooting_rating,
                'defense' => $player->defense_rating,
                'passing' => $player->passing_rating,
                'rebounding' => $player->rebounding_rating,
                'speed' => $player->speed_rating,
                'overall' => $player->overall_rating,
            ],
            'years_experience' => $player->years_experience,
            'previous_teams' => $player->previous_teams,
            'achievements' => $player->achievements,
        ];

        return response()->json($development);
    }
}