<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\PlayerAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlayerAvailabilityController extends Controller
{
    public function __construct(
        private PlayerAvailabilityService $availabilityService
    ) {}

    /**
     * Get upcoming events with availability for current player.
     * Used in Player Dashboard "Anstehende Termine" section.
     */
    public function myUpcomingEvents(Request $request): JsonResponse
    {
        $player = $request->user()->playerProfile;

        if (! $player) {
            return response()->json([
                'message' => 'Kein Spielerprofil gefunden.',
                'data' => [],
            ], 404);
        }

        $daysAhead = (int) $request->input('days_ahead', 14);
        $daysAhead = min(max($daysAhead, 7), 60); // Between 7 and 60 days

        $events = $this->availabilityService->getUpcomingEventsWithAvailability(
            $player,
            $daysAhead
        );

        return response()->json([
            'data' => $events,
            'meta' => [
                'days_ahead' => $daysAhead,
                'total_events' => count($events),
            ],
        ]);
    }

    /**
     * Respond to an event (Zusagen/Absagen).
     */
    public function respondToEvent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_type' => 'required|in:game,training',
            'event_id' => 'required|integer',
            'response' => 'required|in:available,unavailable,maybe',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        $player = $request->user()->playerProfile;

        if (! $player) {
            return response()->json([
                'message' => 'Kein Spielerprofil gefunden.',
            ], 404);
        }

        try {
            if ($validated['event_type'] === 'game') {
                $registration = $this->availabilityService->updateGameAvailability(
                    gameId: $validated['event_id'],
                    playerId: $player->id,
                    status: $validated['response'],
                    reason: $validated['reason'] ?? null,
                    notes: $validated['notes'] ?? null
                );

                return response()->json([
                    'message' => $this->getResponseMessage($validated['response']),
                    'data' => [
                        'registration_id' => $registration->id,
                        'availability_status' => $registration->availability_status,
                        'registration_status' => $registration->registration_status,
                    ],
                ]);
            } else {
                $registration = $this->availabilityService->updateTrainingAvailability(
                    trainingId: $validated['event_id'],
                    playerId: $player->id,
                    status: $validated['response'],
                    reason: $validated['reason'] ?? null,
                    notes: $validated['notes'] ?? null
                );

                return response()->json([
                    'message' => $this->getResponseMessage($validated['response']),
                    'data' => [
                        'registration_id' => $registration->id,
                        'status' => $registration->status,
                    ],
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get team availability overview for a specific event (trainer view).
     */
    public function eventAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_type' => 'required|in:game,training',
            'event_id' => 'required|integer',
        ]);

        $overview = $this->availabilityService->getEventAvailability(
            $validated['event_type'],
            $validated['event_id']
        );

        return response()->json([
            'data' => $overview,
        ]);
    }

    /**
     * Get team availability overview for a date range (trainer view).
     */
    public function teamAvailability(Request $request, Team $team): JsonResponse
    {
        // Check if user has access to this team
        $user = $request->user();

        if (! $user->hasRole('super_admin') && ! $user->hasRole('tenant_admin')) {
            $userTeamIds = $user->coachedTeams()->pluck('id')->toArray();
            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();

            if (! in_array($team->id, $userTeamIds) && ! in_array($team->club_id, $userClubIds)) {
                return response()->json([
                    'message' => 'Keine Berechtigung für dieses Team.',
                ], 403);
            }
        }

        $startDate = $request->has('start_date')
            ? Carbon::parse($request->input('start_date'))
            : now();

        $endDate = $request->has('end_date')
            ? Carbon::parse($request->input('end_date'))
            : now()->addDays(14);

        $overview = $this->availabilityService->getTeamAvailabilityOverview(
            $team->id,
            $startDate,
            $endDate
        );

        return response()->json([
            'data' => $overview,
        ]);
    }

    /**
     * Get players without response for an event.
     */
    public function pendingResponses(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_type' => 'required|in:game,training',
            'event_id' => 'required|integer',
        ]);

        $players = $this->availabilityService->getPlayersWithoutResponse(
            $validated['event_type'],
            $validated['event_id']
        );

        return response()->json([
            'data' => $players->map(fn ($player) => [
                'id' => $player->id,
                'name' => $player->full_name ?? $player->user?->name ?? 'Unbekannt',
                'email' => $player->user?->email,
            ]),
            'meta' => [
                'count' => $players->count(),
            ],
        ]);
    }

    /**
     * Get appropriate response message in German.
     */
    private function getResponseMessage(string $response): string
    {
        return match ($response) {
            'available' => 'Du hast zugesagt.',
            'unavailable' => 'Du hast abgesagt.',
            'maybe' => 'Du hast dich als unsicher eingetragen.',
            default => 'Antwort gespeichert.',
        };
    }
}
