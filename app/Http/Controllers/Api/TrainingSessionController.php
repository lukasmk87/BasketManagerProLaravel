<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrainingRegistration;
use App\Models\TrainingSession;
use App\Services\TrainingService;
use App\Services\BookingService;
use App\Http\Requests\TrainingSession\CreateTrainingSessionRequest;
use App\Http\Requests\TrainingSession\UpdateTrainingSessionRequest;
use App\Http\Resources\TrainingSessionResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class TrainingSessionController extends Controller
{
    public function __construct(
        private TrainingService $trainingService,
        private BookingService $bookingService
    ) {}

    /**
     * Display a listing of training sessions.
     */
    public function index(Request $request): JsonResponse
    {
        $query = TrainingSession::with(['team', 'trainer', 'assistantTrainer', 'drills']);

        // Filter by team
        if ($request->has('team_id')) {
            $query->where('team_id', $request->team_id);
        }

        // Filter by trainer
        if ($request->has('trainer_id')) {
            $query->byTrainer($request->trainer_id);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('scheduled_at', [
                Carbon::parse($request->start_date),
                Carbon::parse($request->end_date)
            ]);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by session type
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        // Time-based filters
        if ($request->has('filter')) {
            match ($request->filter) {
                'upcoming' => $query->upcoming(),
                'today' => $query->today(),
                'this_week' => $query->thisWeek(),
                'completed' => $query->completed(),
                default => null,
            };
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'scheduled_at');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $sessions = $query->paginate($perPage);

        return response()->json([
            'data' => TrainingSessionResource::collection($sessions->items()),
            'meta' => [
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage(),
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total(),
            ]
        ]);
    }

    /**
     * Store a newly created training session.
     */
    public function store(CreateTrainingSessionRequest $request): JsonResponse
    {
        try {
            $session = $this->trainingService->createTrainingSession($request->validated());

            return response()->json([
                'message' => 'Trainingseinheit erfolgreich erstellt',
                'data' => new TrainingSessionResource($session->load(['team', 'trainer', 'drills']))
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Erstellen der Trainingseinheit',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified training session.
     */
    public function show(TrainingSession $trainingSession): JsonResponse
    {
        $trainingSession->load([
            'team', 
            'trainer', 
            'assistantTrainer',
            'drills' => function ($query) {
                $query->orderBy('training_drills.order_in_session');
            },
            'attendance.player',
            'playerPerformances.player'
        ]);

        return response()->json([
            'data' => new TrainingSessionResource($trainingSession)
        ]);
    }

    /**
     * Update the specified training session.
     */
    public function update(UpdateTrainingSessionRequest $request, TrainingSession $trainingSession): JsonResponse
    {
        try {
            $session = $this->trainingService->updateTrainingSession($trainingSession, $request->validated());

            return response()->json([
                'message' => 'Trainingseinheit erfolgreich aktualisiert',
                'data' => new TrainingSessionResource($session->load(['team', 'trainer', 'drills']))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Aktualisieren der Trainingseinheit',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove the specified training session.
     */
    public function destroy(TrainingSession $trainingSession): JsonResponse
    {
        try {
            $trainingSession->delete();

            return response()->json([
                'message' => 'Trainingseinheit erfolgreich gelöscht'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Löschen der Trainingseinheit',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Start a training session.
     */
    public function start(TrainingSession $trainingSession): JsonResponse
    {
        try {
            $session = $this->trainingService->startTrainingSession($trainingSession);

            return response()->json([
                'message' => 'Training erfolgreich gestartet',
                'data' => new TrainingSessionResource($session->load(['team', 'trainer', 'drills']))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Training kann nicht gestartet werden',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Complete a training session.
     */
    public function complete(Request $request, TrainingSession $trainingSession): JsonResponse
    {
        try {
            $completionData = $request->validate([
                'overall_rating' => 'nullable|integer|min:1|max:10',
                'trainer_notes' => 'nullable|string',
                'session_feedback' => 'nullable|string',
                'goals_achieved' => 'nullable|array',
            ]);

            $session = $this->trainingService->completeTrainingSession($trainingSession, $completionData);

            return response()->json([
                'message' => 'Training erfolgreich abgeschlossen',
                'data' => new TrainingSessionResource($session->load(['team', 'trainer', 'drills']))
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Training kann nicht abgeschlossen werden',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get session drills.
     */
    public function drills(TrainingSession $trainingSession): JsonResponse
    {
        $drills = $trainingSession->drills()
            ->orderBy('training_drills.order_in_session')
            ->get();

        return response()->json([
            'data' => $drills->map(function ($drill) {
                return [
                    'drill' => $drill,
                    'session_config' => [
                        'order_in_session' => $drill->pivot->order_in_session,
                        'planned_duration' => $drill->pivot->planned_duration,
                        'actual_duration' => $drill->pivot->actual_duration,
                        'status' => $drill->pivot->status,
                        'drill_rating' => $drill->pivot->drill_rating,
                        'participants_count' => $drill->pivot->participants_count,
                        'specific_instructions' => $drill->pivot->specific_instructions,
                        'performance_notes' => $drill->pivot->performance_notes,
                    ]
                ];
            })
        ]);
    }

    /**
     * Add drill to session.
     */
    public function addDrill(Request $request, TrainingSession $trainingSession): JsonResponse
    {
        try {
            $validated = $request->validate([
                'drill_id' => 'required|exists:drills,id',
                'order_in_session' => 'nullable|integer',
                'planned_duration' => 'nullable|integer',
                'specific_instructions' => 'nullable|string',
                'participants_count' => 'nullable|integer',
            ]);

            $this->trainingService->addDrillToSession(
                $trainingSession,
                $validated['drill_id'],
                collect($validated)->except('drill_id')->toArray()
            );

            return response()->json([
                'message' => 'Drill erfolgreich hinzugefügt'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Hinzufügen des Drills',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove drill from session.
     */
    public function removeDrill(TrainingSession $trainingSession, int $drillId): JsonResponse
    {
        try {
            $this->trainingService->removeDrillFromSession($trainingSession, $drillId);

            return response()->json([
                'message' => 'Drill erfolgreich entfernt'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Entfernen des Drills',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Reorder session drills.
     */
    public function reorderDrills(Request $request, TrainingSession $trainingSession): JsonResponse
    {
        try {
            $validated = $request->validate([
                'drill_order' => 'required|array',
                'drill_order.*' => 'integer|exists:drills,id',
            ]);

            $this->trainingService->reorderSessionDrills($trainingSession, $validated['drill_order']);

            return response()->json([
                'message' => 'Drill-Reihenfolge erfolgreich aktualisiert'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Aktualisieren der Drill-Reihenfolge',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Record drill performance.
     */
    public function recordDrillPerformance(Request $request, TrainingSession $trainingSession, int $drillId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'actual_duration' => 'nullable|integer',
                'drill_rating' => 'nullable|integer|min:1|max:10',
                'performance_notes' => 'nullable|string',
                'trainer_observations' => 'nullable|string',
                'success_metrics' => 'nullable|array',
                'goals_achieved' => 'nullable|boolean',
                'player_difficulty_rating' => 'nullable|numeric|min:1|max:10',
                'player_enjoyment_rating' => 'nullable|numeric|min:1|max:10',
            ]);

            $this->trainingService->recordDrillPerformance($trainingSession, $drillId, $validated);

            return response()->json([
                'message' => 'Drill-Leistung erfolgreich aufgezeichnet'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Aufzeichnen der Drill-Leistung',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get session attendance.
     */
    public function attendance(TrainingSession $trainingSession): JsonResponse
    {
        $attendance = $trainingSession->attendance()
            ->with('player')
            ->get()
            ->map(function ($attendance) {
                return [
                    'id' => $attendance->id,
                    'player' => $attendance->player,
                    'status' => $attendance->status,
                    'status_display' => $attendance->status_display,
                    'arrival_time' => $attendance->arrival_time,
                    'minutes_late' => $attendance->minutes_late,
                    'participation_level' => $attendance->participation_level,
                    'effort_rating' => $attendance->effort_rating,
                    'attitude_rating' => $attendance->attitude_rating,
                    'notes' => $attendance->notes,
                ];
            });

        return response()->json([
            'data' => $attendance,
            'summary' => $trainingSession->getParticipationStats(),
        ]);
    }

    /**
     * Mark attendance.
     */
    public function markAttendance(Request $request, TrainingSession $trainingSession): JsonResponse
    {
        try {
            $validated = $request->validate([
                'attendance' => 'required|array',
                'attendance.*.player_id' => 'required|exists:players,id',
                'attendance.*.status' => 'required|in:present,absent,late,excused,injured',
                'attendance.*.notes' => 'nullable|string',
            ]);

            $attendanceData = collect($validated['attendance'])
                ->keyBy('player_id')
                ->toArray();

            $this->trainingService->bulkMarkAttendance($trainingSession, $attendanceData);

            return response()->json([
                'message' => 'Anwesenheit erfolgreich aktualisiert'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Aktualisieren der Anwesenheit',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get session statistics.
     */
    public function statistics(TrainingSession $trainingSession): JsonResponse
    {
        $stats = [
            'session_info' => [
                'duration' => $trainingSession->duration,
                'planned_duration' => $trainingSession->planned_duration,
                'actual_duration' => $trainingSession->actual_duration,
                'status' => $trainingSession->status,
                'overall_rating' => $trainingSession->overall_rating,
            ],
            'drills' => [
                'total_drills' => $trainingSession->drills()->count(),
                'completed_drills' => $trainingSession->drills()->wherePivot('status', 'completed')->count(),
                'average_rating' => $trainingSession->average_drill_rating,
                'total_planned_duration' => $trainingSession->calculateTotalPlannedDuration(),
                'total_actual_duration' => $trainingSession->calculateTotalActualDuration(),
            ],
            'attendance' => $trainingSession->getParticipationStats(),
        ];

        return response()->json([
            'data' => $stats
        ]);
    }

    // ============================
    // REGISTRATION ENDPOINTS
    // ============================

    /**
     * Get registration information for a training session.
     */
    public function registrations(TrainingSession $trainingSession): JsonResponse
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
    public function register(Request $request, TrainingSession $trainingSession): JsonResponse
    {
        try {
            $validated = $request->validate([
                'player_id' => 'required|exists:players,id',
                'notes' => 'nullable|string|max:500',
            ]);

            // SEC-006: Authorization check
            $this->authorize('create', [TrainingRegistration::class, $trainingSession]);

            $registration = $this->bookingService->registerForTraining(
                $trainingSession->id,
                $validated['player_id'],
                $validated['notes'] ?? null
            );

            return response()->json([
                'message' => 'Erfolgreich für Training angemeldet',
                'data' => $registration->getRegistrationSummary(),
                'session_summary' => $trainingSession->getRegistrationSummary(),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler bei der Anmeldung',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Cancel registration for a training session.
     */
    public function cancelRegistration(Request $request, TrainingSession $trainingSession): JsonResponse
    {
        try {
            $validated = $request->validate([
                'player_id' => 'required|exists:players,id',
                'reason' => 'nullable|string|max:500',
            ]);

            // SEC-006: Authorization check - find registration and authorize cancel
            $registration = TrainingRegistration::where('training_session_id', $trainingSession->id)
                ->where('player_id', $validated['player_id'])
                ->firstOrFail();
            $this->authorize('delete', $registration);

            $success = $this->bookingService->cancelTrainingRegistration(
                $trainingSession->id,
                $validated['player_id'],
                $validated['reason'] ?? null
            );

            if ($success) {
                return response()->json([
                    'message' => 'Anmeldung erfolgreich storniert',
                    'session_summary' => $trainingSession->getRegistrationSummary(),
                ]);
            }

            return response()->json([
                'message' => 'Anmeldung konnte nicht storniert werden'
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler bei der Stornierung',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Bulk register players for a training session (trainer/admin only).
     */
    public function bulkRegister(Request $request, TrainingSession $trainingSession): JsonResponse
    {
        try {
            $validated = $request->validate([
                'player_ids' => 'required|array|min:1',
                'player_ids.*' => 'exists:players,id',
                'notes' => 'nullable|string|max:500',
            ]);

            // SEC-006: Authorization check - only trainers/admins can bulk register
            $this->authorize('bulkRegister', [TrainingRegistration::class, $trainingSession]);

            $results = $this->bookingService->bulkRegisterForTraining(
                $trainingSession->id,
                $validated['player_ids'],
                $validated['notes'] ?? null
            );

            return response()->json([
                'message' => 'Massenanmeldung abgeschlossen',
                'results' => $results,
                'session_summary' => $trainingSession->getRegistrationSummary(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler bei der Massenanmeldung',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Confirm a registration (trainer only).
     */
    public function confirmRegistration(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'registration_id' => 'required|exists:training_registrations,id',
                'notes' => 'nullable|string|max:500',
            ]);

            // SEC-006: Authorization check - only trainers can confirm
            $registration = TrainingRegistration::findOrFail($validated['registration_id']);
            $this->authorize('confirm', $registration);

            $success = $this->bookingService->confirmTrainingRegistration(
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
     * Decline a registration (trainer only).
     */
    public function declineRegistration(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'registration_id' => 'required|exists:training_registrations,id',
                'reason' => 'nullable|string|max:500',
            ]);

            // SEC-006: Authorization check - only trainers can decline
            $registration = TrainingRegistration::findOrFail($validated['registration_id']);
            $this->authorize('decline', $registration);

            $success = $this->bookingService->declineTrainingRegistration(
                $validated['registration_id'],
                $request->user()->id,
                $validated['reason'] ?? null
            );

            if ($success) {
                return response()->json([
                    'message' => 'Anmeldung erfolgreich abgelehnt'
                ]);
            }

            return response()->json([
                'message' => 'Anmeldung konnte nicht abgelehnt werden'
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler bei der Ablehnung',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get player's upcoming training registrations with deadlines.
     */
    public function getPlayerRegistrations(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'player_id' => 'required|exists:players,id',
                'days_ahead' => 'nullable|integer|min:1|max:30',
            ]);

            // SEC-006: Authorization check - user can only view their own or team's registrations
            $this->authorize('viewAny', TrainingRegistration::class);

            $daysAhead = $validated['days_ahead'] ?? 14;
            $endDate = now()->addDays($daysAhead);

            $registrations = $this->bookingService->getTrainingRegistrationsWithDeadlines(
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
}