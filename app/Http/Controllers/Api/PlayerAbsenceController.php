<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\PlayerAbsence;
use App\Services\PlayerAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlayerAbsenceController extends Controller
{
    public function __construct(
        private PlayerAvailabilityService $availabilityService
    ) {}

    /**
     * Get absences for a player or the current user.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PlayerAbsence::class);

        $playerId = $request->input('player_id');

        // If no player_id specified, use current user's player profile
        if (! $playerId && $request->user()->playerProfile) {
            $playerId = $request->user()->playerProfile->id;
        }

        if (! $playerId) {
            return response()->json([
                'message' => 'Kein Spieler angegeben.',
                'data' => [],
            ]);
        }

        $query = PlayerAbsence::where('player_id', $playerId)
            ->orderBy('start_date', 'desc');

        // Filter by status (current, upcoming, past)
        if ($request->has('status')) {
            switch ($request->input('status')) {
                case 'current':
                    $query->current();
                    break;
                case 'upcoming':
                    $query->upcoming();
                    break;
                case 'past':
                    $query->where('end_date', '<', now()->toDateString());
                    break;
            }
        }

        // Filter by type
        if ($request->has('type')) {
            $query->byType($request->input('type'));
        }

        $absences = $query->get()->map(fn ($absence) => $absence->getSummary());

        return response()->json([
            'data' => $absences,
        ]);
    }

    /**
     * Store a new absence.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'player_id' => 'nullable|exists:players,id',
            'type' => 'required|in:vacation,illness,injury,personal,other',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Use current user's player profile if no player_id specified
        $playerId = $validated['player_id'] ?? $request->user()->playerProfile?->id;

        if (! $playerId) {
            return response()->json([
                'message' => 'Kein Spielerprofil gefunden.',
            ], 404);
        }

        $player = Player::findOrFail($playerId);

        // Check authorization
        $this->authorize('createFor', [PlayerAbsence::class, $player]);

        // Check for overlapping absences
        $overlapping = PlayerAbsence::where('player_id', $playerId)
            ->overlapping($validated['start_date'], $validated['end_date'])
            ->exists();

        if ($overlapping) {
            return response()->json([
                'message' => 'Es existiert bereits eine Abwesenheit in diesem Zeitraum.',
            ], 422);
        }

        $validated['player_id'] = $playerId;
        $absence = $this->availabilityService->createAbsence($validated);

        return response()->json([
            'message' => 'Abwesenheit erfolgreich eingetragen.',
            'data' => $absence->getSummary(),
        ], 201);
    }

    /**
     * Show a specific absence.
     */
    public function show(PlayerAbsence $absence): JsonResponse
    {
        $this->authorize('view', $absence);

        return response()->json([
            'data' => $absence->getSummary(),
        ]);
    }

    /**
     * Update an absence.
     */
    public function update(Request $request, PlayerAbsence $absence): JsonResponse
    {
        $this->authorize('update', $absence);

        $validated = $request->validate([
            'type' => 'sometimes|in:vacation,illness,injury,personal,other',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        // If dates are being changed, check for overlaps (excluding current absence)
        if (isset($validated['start_date']) || isset($validated['end_date'])) {
            $startDate = $validated['start_date'] ?? $absence->start_date;
            $endDate = $validated['end_date'] ?? $absence->end_date;

            $overlapping = PlayerAbsence::where('player_id', $absence->player_id)
                ->where('id', '!=', $absence->id)
                ->overlapping($startDate, $endDate)
                ->exists();

            if ($overlapping) {
                return response()->json([
                    'message' => 'Es existiert bereits eine andere Abwesenheit in diesem Zeitraum.',
                ], 422);
            }
        }

        $absence->update($validated);

        return response()->json([
            'message' => 'Abwesenheit erfolgreich aktualisiert.',
            'data' => $absence->fresh()->getSummary(),
        ]);
    }

    /**
     * Delete an absence.
     */
    public function destroy(PlayerAbsence $absence): JsonResponse
    {
        $this->authorize('delete', $absence);

        $absence->delete();

        return response()->json([
            'message' => 'Abwesenheit erfolgreich gelöscht.',
        ]);
    }

    /**
     * Get my absences (shortcut for current player).
     */
    public function myAbsences(Request $request): JsonResponse
    {
        $player = $request->user()->playerProfile;

        if (! $player) {
            return response()->json([
                'message' => 'Kein Spielerprofil gefunden.',
            ], 404);
        }

        $absences = PlayerAbsence::where('player_id', $player->id)
            ->where('end_date', '>=', now()->subDays(30)->toDateString())
            ->orderBy('start_date', 'desc')
            ->get()
            ->map(fn ($absence) => $absence->getSummary());

        return response()->json([
            'data' => $absences,
        ]);
    }

    /**
     * Get absences for a team (trainer view).
     */
    public function teamAbsences(Request $request, int $teamId): JsonResponse
    {
        // Get all players in team
        $playerIds = Player::whereHas('teams', function ($query) use ($teamId) {
            $query->where('teams.id', $teamId)
                ->where('player_team.is_active', true);
        })->pluck('id');

        $startDate = $request->input('start_date', now()->toDateString());
        $endDate = $request->input('end_date', now()->addMonths(1)->toDateString());

        $absences = PlayerAbsence::whereIn('player_id', $playerIds)
            ->overlapping($startDate, $endDate)
            ->with('player.user')
            ->orderBy('start_date')
            ->get()
            ->map(fn ($absence) => $absence->getSummary());

        return response()->json([
            'data' => $absences,
        ]);
    }
}
