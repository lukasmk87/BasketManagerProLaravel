<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\TournamentOfficial;
use App\Models\TournamentBracket;
use App\Models\User;
use App\Services\TournamentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TournamentOfficialController extends Controller
{
    protected TournamentService $tournamentService;

    public function __construct(TournamentService $tournamentService)
    {
        $this->tournamentService = $tournamentService;
    }

    /**
     * Get all officials for a tournament.
     */
    public function index(Tournament $tournament, Request $request): JsonResponse
    {
        $query = $tournament->officials()->with(['user']);

        // Filters
        if ($request->has('role')) {
            $query->byRole($request->role);
        }

        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('certification_level')) {
            $query->where('certification_level', $request->certification_level);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'asc');

        if (in_array($sortBy, ['created_at', 'confirmed_at', 'games_assigned', 'games_completed', 'performance_rating'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $officials = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $officials->items(),
            'meta' => [
                'current_page' => $officials->currentPage(),
                'last_page' => $officials->lastPage(),
                'per_page' => $officials->perPage(),
                'total' => $officials->total(),
            ],
        ]);
    }

    /**
     * Add official to tournament.
     */
    public function store(Tournament $tournament, Request $request): JsonResponse
    {
        try {
            $this->authorize('update', $tournament);

            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'role' => 'required|in:head_referee,assistant_referee,scorekeeper,timekeeper,statistician,announcer,media_coordinator,tournament_director',
                'certification_level' => 'nullable|string|max:100',
                'certifications' => 'nullable|array',
                'experience_years' => 'nullable|integer|min:0',
                'available_dates' => 'nullable|array',
                'available_times' => 'nullable|array',
                'max_games_per_day' => 'nullable|integer|min:1|max:10',
                'rate_per_game' => 'nullable|numeric|min:0',
                'travel_allowance' => 'nullable|numeric|min:0',
                'response_deadline' => 'nullable|date',
                'status' => 'nullable|in:invited,confirmed,declined,cancelled',
            ]);

            $official = $tournament->officials()->create(array_merge($validated, [
                'status' => $validated['status'] ?? 'invited',
            ]));

            return response()->json([
                'message' => 'Offizieller erfolgreich hinzugefügt',
                'data' => $official->load('user'),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Hinzufügen des Offiziellen',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get specific tournament official.
     */
    public function show(Tournament $tournament, TournamentOfficial $tournamentOfficial): JsonResponse
    {
        if ($tournamentOfficial->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Offizieller gehört nicht zu diesem Turnier'], 404);
        }

        return response()->json([
            'data' => $tournamentOfficial->load('user'),
        ]);
    }

    /**
     * Update tournament official.
     */
    public function update(Tournament $tournament, TournamentOfficial $tournamentOfficial, Request $request): JsonResponse
    {
        try {
            if ($tournamentOfficial->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Offizieller gehört nicht zu diesem Turnier'], 404);
            }

            $this->authorize('update', $tournament);

            $validated = $request->validate([
                'role' => 'sometimes|in:head_referee,assistant_referee,scorekeeper,timekeeper,statistician,announcer,media_coordinator,tournament_director',
                'certification_level' => 'nullable|string|max:100',
                'certifications' => 'nullable|array',
                'experience_years' => 'nullable|integer|min:0',
                'available_dates' => 'nullable|array',
                'available_times' => 'nullable|array',
                'max_games_per_day' => 'nullable|integer|min:1|max:10',
                'rate_per_game' => 'nullable|numeric|min:0',
                'travel_allowance' => 'nullable|numeric|min:0',
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_phone' => 'nullable|string|max:50',
                'dietary_restrictions' => 'nullable|string',
                'accommodation_needs' => 'nullable|string',
                'requires_transportation' => 'nullable|boolean',
                'equipment_notes' => 'nullable|string',
            ]);

            $tournamentOfficial->update($validated);

            return response()->json([
                'message' => 'Offizieller erfolgreich aktualisiert',
                'data' => $tournamentOfficial->load('user'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Aktualisieren des Offiziellen',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove official from tournament.
     */
    public function destroy(Tournament $tournament, TournamentOfficial $tournamentOfficial): JsonResponse
    {
        try {
            if ($tournamentOfficial->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Offizieller gehört nicht zu diesem Turnier'], 404);
            }

            $this->authorize('update', $tournament);

            $tournamentOfficial->delete();

            return response()->json([
                'message' => 'Offizieller erfolgreich entfernt',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Entfernen des Offiziellen',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Confirm official participation.
     */
    public function confirm(Tournament $tournament, TournamentOfficial $tournamentOfficial): JsonResponse
    {
        try {
            if ($tournamentOfficial->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Offizieller gehört nicht zu diesem Turnier'], 404);
            }

            // Allow official to confirm their own participation or tournament manager
            if (auth()->id() !== $tournamentOfficial->user_id) {
                $this->authorize('update', $tournament);
            }

            $tournamentOfficial->confirm();

            return response()->json([
                'message' => 'Teilnahme erfolgreich bestätigt',
                'data' => $tournamentOfficial->fresh('user'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Bestätigen der Teilnahme',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Decline official participation.
     */
    public function decline(Tournament $tournament, TournamentOfficial $tournamentOfficial, Request $request): JsonResponse
    {
        try {
            if ($tournamentOfficial->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Offizieller gehört nicht zu diesem Turnier'], 404);
            }

            // Allow official to decline their own participation
            if (auth()->id() !== $tournamentOfficial->user_id) {
                $this->authorize('update', $tournament);
            }

            $request->validate([
                'reason' => 'nullable|string|max:500',
            ]);

            $tournamentOfficial->decline($request->get('reason'));

            return response()->json([
                'message' => 'Teilnahme erfolgreich abgelehnt',
                'data' => $tournamentOfficial->fresh('user'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Ablehnen der Teilnahme',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel official participation.
     */
    public function cancel(Tournament $tournament, TournamentOfficial $tournamentOfficial, Request $request): JsonResponse
    {
        try {
            if ($tournamentOfficial->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Offizieller gehört nicht zu diesem Turnier'], 404);
            }

            $this->authorize('update', $tournament);

            $request->validate([
                'reason' => 'nullable|string|max:500',
            ]);

            $tournamentOfficial->cancel($request->get('reason'));

            return response()->json([
                'message' => 'Teilnahme erfolgreich abgesagt',
                'data' => $tournamentOfficial->fresh('user'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Absagen der Teilnahme',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Rate official performance.
     */
    public function rate(Tournament $tournament, TournamentOfficial $tournamentOfficial, Request $request): JsonResponse
    {
        try {
            if ($tournamentOfficial->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Offizieller gehört nicht zu diesem Turnier'], 404);
            }

            $this->authorize('update', $tournament);

            $validated = $request->validate([
                'performance_rating' => 'required|numeric|min:0|max:10',
                'punctuality_rating' => 'nullable|numeric|min:0|max:10',
                'communication_rating' => 'nullable|numeric|min:0|max:10',
                'professionalism_rating' => 'nullable|numeric|min:0|max:10',
                'notes' => 'nullable|string',
            ]);

            $tournamentOfficial->addPerformanceRating(
                $validated['performance_rating'],
                $validated['punctuality_rating'] ?? null,
                $validated['communication_rating'] ?? null,
                $validated['professionalism_rating'] ?? null,
                $validated['notes'] ?? null
            );

            return response()->json([
                'message' => 'Bewertung erfolgreich hinzugefügt',
                'data' => $tournamentOfficial->fresh('user'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Bewerten',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Add feedback for official.
     */
    public function addFeedback(Tournament $tournament, TournamentOfficial $tournamentOfficial, Request $request): JsonResponse
    {
        try {
            if ($tournamentOfficial->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Offizieller gehört nicht zu diesem Turnier'], 404);
            }

            $this->authorize('update', $tournament);

            $validated = $request->validate([
                'feedback' => 'required|string',
                'game_id' => 'nullable|exists:tournament_brackets,id',
            ]);

            $performanceNotes = $tournamentOfficial->performance_notes ?? [];
            $performanceNotes[] = [
                'feedback' => $validated['feedback'],
                'game_id' => $validated['game_id'] ?? null,
                'added_by' => auth()->id(),
                'date' => now()->toDateString(),
            ];

            $tournamentOfficial->update(['performance_notes' => $performanceNotes]);

            return response()->json([
                'message' => 'Feedback erfolgreich hinzugefügt',
                'data' => $tournamentOfficial->fresh('user'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Hinzufügen von Feedback',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get official performance metrics.
     */
    public function performance(Tournament $tournament, TournamentOfficial $tournamentOfficial): JsonResponse
    {
        if ($tournamentOfficial->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Offizieller gehört nicht zu diesem Turnier'], 404);
        }

        $performance = [
            'overview' => [
                'games_assigned' => $tournamentOfficial->games_assigned,
                'games_completed' => $tournamentOfficial->games_completed,
                'completion_rate' => $tournamentOfficial->completion_rate,
                'performance_rating' => $tournamentOfficial->performance_rating,
                'total_ratings' => $tournamentOfficial->total_ratings,
            ],
            'detailed_ratings' => [
                'performance' => $tournamentOfficial->performance_rating,
                'punctuality' => $tournamentOfficial->punctuality_rating,
                'communication' => $tournamentOfficial->communication_rating,
                'professionalism' => $tournamentOfficial->professionalism_rating,
                'average' => $tournamentOfficial->average_rating,
            ],
            'statistics' => [
                'technical_fouls_called' => $tournamentOfficial->technical_fouls_called,
                'ejections_made' => $tournamentOfficial->ejections_made,
            ],
            'financial' => [
                'rate_per_game' => $tournamentOfficial->rate_per_game,
                'travel_allowance' => $tournamentOfficial->travel_allowance,
                'total_earnings' => $tournamentOfficial->total_earnings,
                'payment_completed' => $tournamentOfficial->payment_completed,
            ],
            'feedback' => $tournamentOfficial->performance_notes ?? [],
        ];

        return response()->json([
            'data' => $performance,
        ]);
    }

    /**
     * Get official's game assignments.
     */
    public function assignments(Tournament $tournament, TournamentOfficial $tournamentOfficial): JsonResponse
    {
        if ($tournamentOfficial->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Offizieller gehört nicht zu diesem Turnier'], 404);
        }

        $assignedGameIds = $tournamentOfficial->assigned_games ?? [];

        $assignments = TournamentBracket::whereIn('id', $assignedGameIds)
            ->with(['team1.team', 'team2.team'])
            ->orderBy('scheduled_at')
            ->get();

        return response()->json([
            'data' => $assignments->map(function ($bracket) {
                return [
                    'game_id' => $bracket->id,
                    'round' => $bracket->round,
                    'round_name' => $bracket->round_name,
                    'team1' => $bracket->team1?->team->name ?? 'TBD',
                    'team2' => $bracket->team2?->team->name ?? 'TBD',
                    'scheduled_at' => $bracket->scheduled_at?->toISOString(),
                    'venue' => $bracket->venue,
                    'court' => $bracket->court,
                    'status' => $bracket->status,
                ];
            }),
        ]);
    }

    /**
     * Assign official to game.
     */
    public function assignToGame(Tournament $tournament, TournamentOfficial $tournamentOfficial, Request $request): JsonResponse
    {
        try {
            if ($tournamentOfficial->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Offizieller gehört nicht zu diesem Turnier'], 404);
            }

            $this->authorize('update', $tournament);

            $validated = $request->validate([
                'game_id' => 'required|exists:tournament_brackets,id',
            ]);

            $bracket = TournamentBracket::findOrFail($validated['game_id']);

            if ($bracket->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Spiel gehört nicht zu diesem Turnier'], 404);
            }

            $tournamentOfficial->assignGame($bracket->id);

            return response()->json([
                'message' => 'Offizieller erfolgreich zugewiesen',
                'data' => $tournamentOfficial->fresh('user'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Zuweisen zum Spiel',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove official from game.
     */
    public function unassignFromGame(Tournament $tournament, TournamentOfficial $tournamentOfficial, TournamentBracket $bracket): JsonResponse
    {
        try {
            if ($tournamentOfficial->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Offizieller gehört nicht zu diesem Turnier'], 404);
            }

            if ($bracket->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Spiel gehört nicht zu diesem Turnier'], 404);
            }

            $this->authorize('update', $tournament);

            $assignedGames = $tournamentOfficial->assigned_games ?? [];
            $assignedGames = array_values(array_diff($assignedGames, [$bracket->id]));

            $tournamentOfficial->update([
                'assigned_games' => $assignedGames,
                'games_assigned' => count($assignedGames),
            ]);

            return response()->json([
                'message' => 'Offizieller erfolgreich vom Spiel entfernt',
                'data' => $tournamentOfficial->fresh('user'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Entfernen vom Spiel',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Handle webhook response from official.
     */
    public function webhookResponse(string $token): JsonResponse
    {
        try {
            // TODO: Implement token-based webhook response handling
            // This allows officials to confirm/decline via email link without login

            return response()->json([
                'message' => 'Webhook verarbeitet (in Entwicklung)',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Verarbeiten des Webhooks',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
