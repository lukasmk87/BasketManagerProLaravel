<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameRegistration;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GameRegistrationController extends Controller
{
    public function __construct(
        private BookingService $bookingService
    ) {}

    /**
     * Get registration information for a game.
     */
    public function index(Game $game): JsonResponse
    {
        $registrations = $game->registrations()
            ->with('player')
            ->orderBy('registered_at')
            ->get()
            ->map(function ($registration) {
                return $registration->getRegistrationSummary();
            });

        return response()->json([
            'data' => $registrations,
            'summary' => $game->getRegistrationSummary(),
        ]);
    }

    /**
     * Register current user's player for a game.
     */
    public function store(Request $request, Game $game): JsonResponse
    {
        try {
            $validated = $request->validate([
                'player_id' => 'required|exists:players,id',
                'availability_status' => 'required|in:available,unavailable,maybe,injured,suspended',
                'notes' => 'nullable|string|max:500',
            ]);

            // TODO: Add authorization check - user can only register their own players
            
            $registration = $this->bookingService->registerForGame(
                $game->id,
                $validated['player_id'],
                $validated['availability_status'],
                $validated['notes'] ?? null
            );

            return response()->json([
                'message' => 'Erfolgreich für Spiel angemeldet',
                'data' => $registration->getRegistrationSummary(),
                'game_summary' => $game->getRegistrationSummary(),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler bei der Anmeldung',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Update player availability for a game.
     */
    public function updateAvailability(Request $request, Game $game): JsonResponse
    {
        try {
            $validated = $request->validate([
                'player_id' => 'required|exists:players,id',
                'availability_status' => 'required|in:available,unavailable,maybe,injured,suspended',
                'reason' => 'nullable|string|max:500',
            ]);

            // TODO: Add authorization check - user can only update their own players

            $success = $this->bookingService->updateGameAvailability(
                $game->id,
                $validated['player_id'],
                $validated['availability_status'],
                $validated['reason'] ?? null
            );

            if ($success) {
                return response()->json([
                    'message' => 'Verfügbarkeit erfolgreich aktualisiert',
                    'game_summary' => $game->getRegistrationSummary(),
                ]);
            }

            return response()->json([
                'message' => 'Verfügbarkeit konnte nicht aktualisiert werden'
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler bei der Aktualisierung',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Bulk register players for a game (trainer/admin only).
     */
    public function bulkRegister(Request $request, Game $game): JsonResponse
    {
        try {
            $validated = $request->validate([
                'players' => 'required|array|min:1',
                'players.*.player_id' => 'required|exists:players,id',
                'players.*.availability_status' => 'required|in:available,unavailable,maybe,injured,suspended',
                'players.*.notes' => 'nullable|string|max:500',
            ]);

            // TODO: Add authorization check - only trainers/admins

            $results = $this->bookingService->bulkRegisterForGame(
                $game->id,
                $validated['players']
            );

            return response()->json([
                'message' => 'Massenanmeldung abgeschlossen',
                'results' => $results,
                'game_summary' => $game->getRegistrationSummary(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler bei der Massenanmeldung',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Confirm a game registration (coach only).
     */
    public function confirm(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'registration_id' => 'required|exists:game_registrations,id',
                'notes' => 'nullable|string|max:500',
            ]);

            // TODO: Add authorization check - only coaches

            $success = $this->bookingService->confirmGameRegistration(
                $validated['registration_id'],
                $request->user()->id,
                $validated['notes'] ?? null
            );

            if ($success) {
                return response()->json([
                    'message' => 'Anmeldung erfolgreich bestätigt'
                ]);
            }

            return response()->json([
                'message' => 'Anmeldung konnte nicht bestätigt werden'
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler bei der Bestätigung',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get roster information for a game.
     */
    public function roster(Game $game): JsonResponse
    {
        $participations = $game->participations()
            ->with('player')
            ->orderByRaw("FIELD(role, 'captain', 'vice_captain', 'starter', 'substitute', 'reserve')")
            ->get()
            ->map(function ($participation) {
                return $participation->getParticipationSummary();
            });

        return response()->json([
            'data' => $participations,
            'lineup' => $game->getRosterLineup(),
            'summary' => $game->getRegistrationSummary(),
        ]);
    }

    /**
     * Add player to game roster (coach only).
     */
    public function addToRoster(Request $request, Game $game): JsonResponse
    {
        try {
            $validated = $request->validate([
                'player_id' => 'required|exists:players,id',
                'role' => 'required|in:starter,substitute,reserve,captain,vice_captain',
                'jersey_number' => 'nullable|integer|min:0|max:99',
                'playing_position' => 'nullable|in:PG,SG,SF,PF,C,G,F,UTIL',
            ]);

            // TODO: Add authorization check - only coaches

            $participation = $this->bookingService->addPlayerToGameRoster(
                $game->id,
                $validated['player_id'],
                $validated['role'],
                $validated['jersey_number'] ?? null,
                $validated['playing_position'] ?? null
            );

            return response()->json([
                'message' => 'Spieler erfolgreich zum Kader hinzugefügt',
                'data' => $participation->getParticipationSummary(),
                'game_summary' => $game->getRegistrationSummary(),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Hinzufügen zum Kader',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove player from game roster (coach only).
     */
    public function removeFromRoster(Request $request, Game $game): JsonResponse
    {
        try {
            $validated = $request->validate([
                'player_id' => 'required|exists:players,id',
            ]);

            // TODO: Add authorization check - only coaches

            $success = $this->bookingService->removePlayerFromGameRoster(
                $game->id,
                $validated['player_id']
            );

            if ($success) {
                return response()->json([
                    'message' => 'Spieler erfolgreich aus Kader entfernt',
                    'game_summary' => $game->getRegistrationSummary(),
                ]);
            }

            return response()->json([
                'message' => 'Spieler konnte nicht aus Kader entfernt werden'
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Entfernen aus Kader',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Update player role in roster (coach only).
     */
    public function updateRole(Request $request, Game $game): JsonResponse
    {
        try {
            $validated = $request->validate([
                'player_id' => 'required|exists:players,id',
                'role' => 'required|in:starter,substitute,reserve,captain,vice_captain',
                'jersey_number' => 'nullable|integer|min:0|max:99',
                'playing_position' => 'nullable|in:PG,SG,SF,PF,C,G,F,UTIL',
            ]);

            // TODO: Add authorization check - only coaches

            $participation = $game->getPlayerParticipation($validated['player_id']);
            
            if (!$participation) {
                return response()->json([
                    'message' => 'Spieler ist nicht im Kader für dieses Spiel'
                ], 404);
            }

            $participation->changeRole($validated['role']);

            if (isset($validated['jersey_number'])) {
                $participation->assignJersey($validated['jersey_number']);
            }

            if (isset($validated['playing_position'])) {
                $participation->update(['playing_position' => $validated['playing_position']]);
            }

            return response()->json([
                'message' => 'Spielerrolle erfolgreich aktualisiert',
                'data' => $participation->getParticipationSummary(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler bei der Aktualisierung der Rolle',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get player's upcoming game registrations with deadlines.
     */
    public function getPlayerRegistrations(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'player_id' => 'required|exists:players,id',
                'days_ahead' => 'nullable|integer|min:1|max:30',
            ]);

            // TODO: Add authorization check - user can only view their own players

            $daysAhead = $validated['days_ahead'] ?? 14;
            $endDate = now()->addDays($daysAhead);

            $registrations = $this->bookingService->getGameRegistrationsWithDeadlines(
                $validated['player_id'],
                now(),
                $endDate
            );

            return response()->json([
                'data' => $registrations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Laden der Anmeldungen',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get upcoming deadlines for all registrations.
     */
    public function getUpcomingDeadlines(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'player_id' => 'nullable|exists:players,id',
                'days_ahead' => 'nullable|integer|min:1|max:30',
            ]);

            $daysAhead = $validated['days_ahead'] ?? 7;
            $playerId = $validated['player_id'] ?? null;

            $deadlines = $this->bookingService->getUpcomingDeadlines($playerId, $daysAhead);

            return response()->json([
                'data' => $deadlines,
                'meta' => [
                    'days_ahead' => $daysAhead,
                    'player_id' => $playerId,
                    'total_deadlines' => count($deadlines),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Laden der Fristen',
                'error' => $e->getMessage()
            ], 422);
        }
    }
}