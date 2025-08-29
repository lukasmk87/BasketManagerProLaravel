<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrainingSession;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainingRegistrationController extends Controller
{
    public function __construct(
        private BookingService $bookingService
    ) {}

    /**
     * Get registration information for a training session.
     */
    public function index(TrainingSession $trainingSession): JsonResponse
    {
        $registrations = $trainingSession->registrations()
            ->with('player')
            ->orderBy('registered_at')
            ->get()
            ->map(function ($registration) {
                return $registration->getRegistrationSummary();
            });

        return response()->json([
            'data' => $registrations,
            'summary' => $trainingSession->getRegistrationSummary(),
        ]);
    }

    /**
     * Register current user's player for a training session.
     */
    public function store(Request $request, TrainingSession $trainingSession): JsonResponse
    {
        try {
            $validated = $request->validate([
                'player_id' => 'required|exists:players,id',
                'status' => 'required|in:registered,cancelled',
                'registration_notes' => 'nullable|string|max:500',
            ]);

            // TODO: Add authorization check - user can only register their own players

            $registration = $this->bookingService->registerForTraining(
                $trainingSession->id,
                $validated['player_id'],
                $validated['status'],
                $validated['registration_notes'] ?? null
            );

            return response()->json([
                'message' => 'Erfolgreich für Training angemeldet',
                'data' => $registration->getRegistrationSummary(),
                'training_summary' => $trainingSession->getRegistrationSummary(),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler bei der Anmeldung',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update player registration status for a training session.
     */
    public function updateStatus(Request $request, TrainingSession $trainingSession): JsonResponse
    {
        try {
            $validated = $request->validate([
                'player_id' => 'required|exists:players,id',
                'status' => 'required|in:registered,cancelled',
                'registration_notes' => 'nullable|string|max:500',
                'cancellation_reason' => 'nullable|string|max:500',
            ]);

            // TODO: Add authorization check - user can only update their own players

            $success = $this->bookingService->updateTrainingRegistration(
                $trainingSession->id,
                $validated['player_id'],
                $validated['status'],
                $validated['registration_notes'] ?? null,
                $validated['cancellation_reason'] ?? null
            );

            if ($success) {
                return response()->json([
                    'message' => 'Anmeldestatus erfolgreich aktualisiert',
                    'training_summary' => $trainingSession->getRegistrationSummary(),
                ]);
            }

            return response()->json([
                'message' => 'Anmeldestatus konnte nicht aktualisiert werden',
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler bei der Aktualisierung',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Bulk register players for a training session (trainer only).
     */
    public function bulkRegister(Request $request, TrainingSession $trainingSession): JsonResponse
    {
        try {
            $validated = $request->validate([
                'players' => 'required|array|min:1',
                'players.*.player_id' => 'required|exists:players,id',
                'players.*.status' => 'required|in:registered,cancelled',
                'players.*.registration_notes' => 'nullable|string|max:500',
            ]);

            // TODO: Add authorization check - only trainers/admins

            $results = $this->bookingService->bulkRegisterForTraining(
                $trainingSession->id,
                $validated['players']
            );

            return response()->json([
                'message' => 'Massenanmeldung abgeschlossen',
                'results' => $results,
                'training_summary' => $trainingSession->getRegistrationSummary(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler bei der Massenanmeldung',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Confirm a training registration (coach only).
     */
    public function confirm(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'registration_id' => 'required|exists:training_registrations,id',
                'trainer_notes' => 'nullable|string|max:500',
            ]);

            // TODO: Add authorization check - only coaches

            $success = $this->bookingService->confirmTrainingRegistration(
                $validated['registration_id'],
                $request->user()->id,
                $validated['trainer_notes'] ?? null
            );

            if ($success) {
                return response()->json([
                    'message' => 'Anmeldung erfolgreich bestätigt',
                ]);
            }

            return response()->json([
                'message' => 'Anmeldung konnte nicht bestätigt werden',
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler bei der Bestätigung',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get player's upcoming training registrations.
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

            $registrations = $this->bookingService->getTrainingRegistrations(
                $validated['player_id'],
                now(),
                $endDate
            );

            return response()->json([
                'data' => $registrations,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Laden der Anmeldungen',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
