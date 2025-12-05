<?php

namespace App\Http\Controllers\Gym;

use App\Http\Controllers\Controller;
use App\Models\GymHall;
use App\Models\GymTimeSlot;
use App\Models\GymTimeSlotTeamAssignment;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Carbon\Carbon;

class GymTimeSlotController extends Controller
{
    /**
     * Get time slots for a specific gym hall.
     */
    public function getHallTimeSlots(Request $request, $hallId): JsonResponse
    {
        try {
            $user = Auth::user();
            $userClub = $user->currentTeam?->club ?? $user->clubs()->first();

            if (!$userClub) {
                return response()->json([
                    'success' => false,
                    'message' => 'Keine Vereinszuordnung gefunden. Bitte wenden Sie sich an den Administrator.'
                ], 422);
            }

            $hall = GymHall::where('id', $hallId)
                ->where('club_id', $userClub->id)
                ->first();

            if (!$hall) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sporthalle nicht gefunden oder keine Berechtigung.'
                ], 404);
            }

            $timeSlots = GymTimeSlot::where('gym_hall_id', $hall->id)
                ->with(['team'])
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get();

            // Get operating hours with parallel bookings settings
            $operatingHours = $hall->operating_hours ?? [];

            return response()->json([
                'success' => true,
                'data' => $timeSlots->map(function ($slot) {
                    return [
                        'id' => $slot->id,
                        'uuid' => $slot->uuid,
                        'title' => $slot->title,
                        'description' => $slot->description,
                        'day_of_week' => $slot->day_of_week,
                        'uses_custom_times' => $slot->uses_custom_times,
                        'custom_times' => $slot->custom_times,
                        'start_time' => $slot->start_time?->format('H:i'),
                        'end_time' => $slot->end_time?->format('H:i'),
                        'duration_minutes' => $slot->duration_minutes,
                        'team' => $slot->team ? [
                            'id' => $slot->team->id,
                            'name' => $slot->team->name,
                        ] : null,
                        'status' => $slot->status,
                        'slot_type' => $slot->slot_type,
                        'is_recurring' => $slot->is_recurring,
                        'allows_substitution' => $slot->allows_substitution,
                        'valid_from' => $slot->valid_from?->format('Y-m-d'),
                        'valid_until' => $slot->valid_until?->format('Y-m-d'),
                        'all_day_times' => $slot->getAllDayTimes(),
                    ];
                }),
                'operating_hours' => $operatingHours,
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting hall time slots: ' . $e->getMessage(), [
                'hall_id' => $hallId,
                'user_id' => auth()->id(),
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Laden der Zeitslots.'
            ], 500);
        }
    }

    /**
     * Create or update time slots for a gym hall.
     */
    public function updateHallTimeSlots(Request $request, $hallId): JsonResponse
    {
        Log::info('UpdateHallTimeSlots called', [
            'hall_id' => $hallId,
            'user_id' => auth()->id(),
            'payload' => $request->all()
        ]);

        $user = Auth::user();
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();

        if (!$user->hasAnyRole(['tenant_admin', 'super_admin', 'club_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Keine Berechtigung zum Bearbeiten von Zeitslots.'
            ], 403);
        }

        if (!$userClub) {
            return response()->json([
                'success' => false,
                'message' => 'Keine Vereinszuordnung gefunden. Bitte wenden Sie sich an den Administrator.'
            ], 422);
        }

        $hall = GymHall::where('id', $hallId)
            ->where('club_id', $userClub->id)
            ->first();

        if (!$hall) {
            return response()->json([
                'success' => false,
                'message' => 'Sporthalle nicht gefunden oder keine Berechtigung.'
            ], 404);
        }

        try {
            $request->validate([
                'time_slots' => 'required|array',
                'time_slots.*.title' => 'required|string|max:255',
                'time_slots.*.description' => 'nullable|string',
                'time_slots.*.day_of_week' => 'nullable|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                'time_slots.*.uses_custom_times' => 'boolean',
                'time_slots.*.custom_times' => 'nullable|array',
                'time_slots.*.start_time' => 'nullable|string',
                'time_slots.*.end_time' => 'nullable|string',
                'time_slots.*.slot_type' => 'required|in:training,game,event,maintenance',
                'time_slots.*.valid_from' => 'required|date',
                'time_slots.*.valid_until' => 'nullable|date|after:valid_from',
                'time_slots.*.supports_parallel_bookings' => 'boolean',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler bei den Zeitslot-Daten.',
                'errors' => $e->errors()
            ], 422);
        }

        // Additional validation: either custom_times OR day_of_week + start_time + end_time
        foreach ($request->time_slots as $index => $slotData) {
            $usesCustomTimes = $slotData['uses_custom_times'] ?? false;

            if ($usesCustomTimes) {
                if (!empty($slotData['custom_times'])) {
                    // Old format with custom_times object - valid
                } else if (!empty($slotData['day_of_week']) && !empty($slotData['start_time']) && !empty($slotData['end_time'])) {
                    // New format with individual slots - valid
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => "Zeitslot {$index}: Bei individuellen Zeiten sind entweder custom_times oder day_of_week + start_time + end_time erforderlich.",
                        'errors' => ["time_slots.{$index}" => ['Vollständige Zeitangaben sind erforderlich.']]
                    ], 422);
                }
            } else {
                if (empty($slotData['day_of_week']) || empty($slotData['start_time']) || empty($slotData['end_time'])) {
                    return response()->json([
                        'success' => false,
                        'message' => "Zeitslot {$index}: Bei Standard-Zeiten sind day_of_week, start_time und end_time erforderlich.",
                        'errors' => ["time_slots.{$index}" => ['Wochentag, Start- und Endzeit sind erforderlich.']]
                    ], 422);
                }
            }
        }

        // Get existing time slots for this hall
        $existingSlots = GymTimeSlot::where('gym_hall_id', $hall->id)->get();

        // Collect IDs of slots being updated
        $updatingSlotIds = [];
        foreach ($request->time_slots as $slotData) {
            if (isset($slotData['id']) && !empty($slotData['id'])) {
                $updatingSlotIds[] = $slotData['id'];
            }
        }

        if (empty($updatingSlotIds) && $existingSlots->count() > 0) {
            $updatingSlotIds = $existingSlots->pluck('id')->toArray();
        }

        // Additional validation for custom times
        foreach ($request->time_slots as $index => $slotData) {
            if (!empty($slotData['uses_custom_times']) && !empty($slotData['custom_times'])) {
                $validationErrors = GymTimeSlot::validateCustomTimes($slotData['custom_times']);

                if (!empty($validationErrors)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validierungsfehler bei den Zeitangaben',
                        'errors' => $validationErrors
                    ], 422);
                }

                // Check for overlaps with existing slots
                $overlapConflicts = GymTimeSlot::hasOverlappingSlots(
                    $hall->id,
                    $slotData['custom_times'],
                    $updatingSlotIds
                );

                if (!empty($overlapConflicts)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Zeitüberschneidungen gefunden',
                        'conflicts' => $overlapConflicts
                    ], 422);
                }
            }
        }

        $processedSlotIds = [];
        $createdSlots = [];

        foreach ($request->time_slots as $slotData) {
            if (isset($slotData['id']) && !empty($slotData['id'])) {
                // Update existing slot
                $timeSlot = $existingSlots->where('id', $slotData['id'])->first();
                if ($timeSlot) {
                    $timeSlot->update([
                        'title' => $slotData['title'],
                        'description' => $slotData['description'] ?? null,
                        'day_of_week' => $slotData['day_of_week'] ?? null,
                        'uses_custom_times' => $slotData['uses_custom_times'] ?? false,
                        'custom_times' => $slotData['custom_times'] ?? null,
                        'start_time' => $slotData['start_time'] ?? null,
                        'end_time' => $slotData['end_time'] ?? null,
                        'duration_minutes' => ($slotData['uses_custom_times'] ?? false)
                            ? null
                            : $this->calculateDuration($slotData['start_time'] ?? null, $slotData['end_time'] ?? null),
                        'slot_type' => $slotData['slot_type'],
                        'valid_from' => $slotData['valid_from'],
                        'valid_until' => $slotData['valid_until'] ?? null,
                        'supports_parallel_bookings' => $slotData['supports_parallel_bookings'] ?? true,
                        'status' => 'active',
                    ]);
                    $processedSlotIds[] = $timeSlot->id;
                    $createdSlots[] = $timeSlot;
                }
            } else {
                // Create new slot
                $timeSlot = GymTimeSlot::create([
                    'uuid' => Str::uuid(),
                    'gym_hall_id' => $hall->id,
                    'title' => $slotData['title'],
                    'description' => $slotData['description'] ?? null,
                    'day_of_week' => $slotData['day_of_week'] ?? null,
                    'uses_custom_times' => $slotData['uses_custom_times'] ?? false,
                    'custom_times' => $slotData['custom_times'] ?? null,
                    'start_time' => $slotData['start_time'] ?? null,
                    'end_time' => $slotData['end_time'] ?? null,
                    'duration_minutes' => ($slotData['uses_custom_times'] ?? false)
                        ? null
                        : $this->calculateDuration($slotData['start_time'] ?? null, $slotData['end_time'] ?? null),
                    'slot_type' => $slotData['slot_type'],
                    'valid_from' => $slotData['valid_from'],
                    'valid_until' => $slotData['valid_until'] ?? null,
                    'supports_parallel_bookings' => $slotData['supports_parallel_bookings'] ?? true,
                    'status' => 'active',
                    'is_recurring' => true,
                    'allows_substitution' => true,
                ]);
                $processedSlotIds[] = $timeSlot->id;
                $createdSlots[] = $timeSlot;
            }
        }

        // Delete slots that were not included in the update
        if (!empty($request->time_slots)) {
            GymTimeSlot::where('gym_hall_id', $hall->id)
                ->whereNotIn('id', $processedSlotIds)
                ->delete();
        }

        // Update hall's operating hours from the time slots
        $operatingHours = [];
        foreach ($request->time_slots as $timeSlotData) {
            if (isset($timeSlotData['day_of_week']) &&
                isset($timeSlotData['start_time']) &&
                isset($timeSlotData['end_time'])) {

                $dayKey = $timeSlotData['day_of_week'];
                $operatingHours[$dayKey] = [
                    'is_open' => true,
                    'open_time' => $timeSlotData['start_time'],
                    'close_time' => $timeSlotData['end_time'],
                    'supports_parallel_bookings' => $timeSlotData['supports_parallel_bookings'] ?? true
                ];
            }
        }

        if (!empty($operatingHours)) {
            $hall->update(['operating_hours' => $operatingHours]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Zeitslots und Öffnungszeiten erfolgreich aktualisiert.',
            'data' => collect($createdSlots)->map(function ($slot) {
                return [
                    'id' => $slot->id,
                    'uuid' => $slot->uuid,
                    'title' => $slot->title,
                    'description' => $slot->description,
                    'day_of_week' => $slot->day_of_week,
                    'uses_custom_times' => $slot->uses_custom_times,
                    'custom_times' => $slot->custom_times,
                    'start_time' => $slot->start_time?->format('H:i'),
                    'end_time' => $slot->end_time?->format('H:i'),
                    'slot_type' => $slot->slot_type,
                    'all_day_times' => $slot->getAllDayTimes(),
                ];
            }),
        ]);
    }

    /**
     * Update custom times for a specific time slot.
     */
    public function updateTimeSlotCustomTimes(Request $request, $slotId): JsonResponse
    {
        $user = Auth::user();
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();

        if (!$user->hasAnyRole(['tenant_admin', 'super_admin', 'club_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Keine Berechtigung zum Bearbeiten von Zeitslots.'
            ], 403);
        }

        $timeSlot = GymTimeSlot::whereHas('gymHall', function ($query) use ($userClub) {
                $query->where('club_id', $userClub->id);
            })
            ->where('id', $slotId)
            ->firstOrFail();

        $request->validate([
            'custom_times' => 'required|array',
            'custom_times.*.start_time' => 'required|string',
            'custom_times.*.end_time' => 'required|string|after:custom_times.*.start_time',
        ]);

        // Validate custom times structure
        $validationErrors = GymTimeSlot::validateCustomTimes($request->custom_times);

        if (!empty($validationErrors)) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler bei den Zeitangaben',
                'errors' => $validationErrors
            ], 422);
        }

        // Check for overlaps with other slots
        $overlapConflicts = GymTimeSlot::hasOverlappingSlots(
            $timeSlot->gym_hall_id,
            $request->custom_times,
            $timeSlot->id
        );

        if (!empty($overlapConflicts)) {
            return response()->json([
                'success' => false,
                'message' => 'Zeitüberschneidungen mit anderen Slots gefunden',
                'conflicts' => $overlapConflicts
            ], 422);
        }

        // Check for conflicts with existing bookings
        $bookingConflicts = $timeSlot->getConflictingBookings($request->custom_times);

        if (!empty($bookingConflicts)) {
            return response()->json([
                'success' => false,
                'message' => 'Änderungen würden bestehende Buchungen betreffen',
                'booking_conflicts' => $bookingConflicts,
                'warning' => true
            ], 422);
        }

        $timeSlot->setCustomTimes($request->custom_times);

        return response()->json([
            'success' => true,
            'message' => 'Individuelle Zeiten erfolgreich gespeichert.',
            'data' => [
                'id' => $timeSlot->id,
                'uses_custom_times' => $timeSlot->uses_custom_times,
                'custom_times' => $timeSlot->custom_times,
                'all_day_times' => $timeSlot->getAllDayTimes(),
            ],
        ]);
    }

    /**
     * Get available teams for time slot assignment.
     */
    public function getAvailableTeams(Request $request): JsonResponse
    {
        $user = Auth::user();
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();

        if (!$userClub) {
            return response()->json([
                'success' => false,
                'message' => 'Kein Verein gefunden.'
            ], 404);
        }

        $teams = Team::where('club_id', $userClub->id)
            ->where('personal_team', false)
            ->orderBy('name')
            ->get(['id', 'name', 'short_name', 'age_group', 'gender']);

        return response()->json([
            'success' => true,
            'data' => $teams
        ]);
    }

    /**
     * Assign team to time slot.
     */
    public function assignTeamToTimeSlot(Request $request, $timeSlotId): JsonResponse
    {
        $user = Auth::user();
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();

        if (!$user->hasAnyRole(['tenant_admin', 'super_admin', 'club_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Keine Berechtigung zum Zuordnen von Teams.'
            ], 403);
        }

        $request->validate([
            'team_id' => 'required|exists:teams,id'
        ]);

        $timeSlot = GymTimeSlot::whereHas('gymHall', function ($query) use ($userClub) {
                $query->where('club_id', $userClub->id);
            })
            ->where('id', $timeSlotId)
            ->firstOrFail();

        $team = Team::where('id', $request->team_id)
            ->where('club_id', $userClub->id)
            ->firstOrFail();

        // Check for conflicts
        $conflicts = $this->checkTeamTimeSlotConflicts($team->id, $timeSlot);

        if (!empty($conflicts)) {
            return response()->json([
                'success' => false,
                'message' => 'Zeitkonflikte gefunden',
                'conflicts' => $conflicts
            ], 422);
        }

        $timeSlot->update([
            'team_id' => $team->id,
            'assigned_by' => $user->id,
            'assigned_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Team erfolgreich zugeordnet.',
            'data' => [
                'time_slot' => [
                    'id' => $timeSlot->id,
                    'team' => [
                        'id' => $team->id,
                        'name' => $team->name,
                    ]
                ]
            ]
        ]);
    }

    /**
     * Remove team assignment from time slot.
     */
    public function removeTeamFromTimeSlot(Request $request, $timeSlotId): JsonResponse
    {
        $user = Auth::user();
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();

        if (!$user->hasAnyRole(['tenant_admin', 'super_admin', 'club_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Keine Berechtigung zum Entfernen von Team-Zuordnungen.'
            ], 403);
        }

        $timeSlot = GymTimeSlot::whereHas('gymHall', function ($query) use ($userClub) {
                $query->where('club_id', $userClub->id);
            })
            ->where('id', $timeSlotId)
            ->firstOrFail();

        $timeSlot->update([
            'team_id' => null,
            'assigned_by' => null,
            'assigned_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Team-Zuordnung erfolgreich entfernt.',
        ]);
    }

    /**
     * Get team's assigned time slots.
     */
    public function getTeamTimeSlots(Request $request, $teamId): JsonResponse
    {
        $user = Auth::user();
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();

        $team = Team::where('id', $teamId)
            ->where('club_id', $userClub->id)
            ->firstOrFail();

        $timeSlots = GymTimeSlot::where('team_id', $team->id)
            ->with(['gymHall'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $timeSlots->map(function ($slot) {
                return [
                    'id' => $slot->id,
                    'title' => $slot->title,
                    'day_of_week' => $slot->day_of_week,
                    'start_time' => $slot->start_time?->format('H:i'),
                    'end_time' => $slot->end_time?->format('H:i'),
                    'slot_type' => $slot->slot_type,
                    'gym_hall' => [
                        'id' => $slot->gymHall->id,
                        'name' => $slot->gymHall->name,
                        'address' => $slot->gymHall->address,
                    ],
                ];
            })
        ]);
    }

    /**
     * Get available time segments for a time slot and day.
     */
    public function getTimeSlotSegments(Request $request, $timeSlotId): JsonResponse
    {
        try {
            $user = Auth::user();
            $userClub = $user->currentTeam?->club ?? $user->clubs()->first();

            if (!$userClub) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kein Verein gefunden.'
                ], 404);
            }

            $timeSlot = GymTimeSlot::whereHas('gymHall', function ($query) use ($userClub) {
                    $query->where('club_id', $userClub->id);
                })
                ->where('id', $timeSlotId)
                ->first();

            if (!$timeSlot) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zeitslot nicht gefunden.'
                ], 404);
            }

            $dayOfWeek = $request->get('day_of_week', 'monday');
            $incrementMinutes = $request->get('increment_minutes', 30);

            $segments = $timeSlot->getAvailableSegmentsForDay($dayOfWeek, $incrementMinutes);

            return response()->json([
                'success' => true,
                'data' => $segments,
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting time slot segments: ' . $e->getMessage(), [
                'time_slot_id' => $timeSlotId,
                'user_id' => auth()->id(),
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Laden der Zeitfenster.'
            ], 500);
        }
    }

    /**
     * Assign team to a specific time segment.
     */
    public function assignTeamToSegment(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $userClub = $user->currentTeam?->club ?? $user->clubs()->first();

            if (!$user->hasAnyRole(['tenant_admin', 'super_admin', 'club_admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Keine Berechtigung zum Zuordnen von Teams.'
                ], 403);
            }

            if (!$userClub) {
                return response()->json([
                    'success' => false,
                    'message' => 'Keine Vereinszuordnung gefunden. Bitte wenden Sie sich an den Administrator.'
                ], 422);
            }

            $request->validate([
                'gym_time_slot_id' => 'required|exists:gym_time_slots,id',
                'team_id' => 'required|exists:teams,id',
                'gym_court_id' => 'nullable|exists:gym_courts,id',
                'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
                'start_time' => 'required|string',
                'end_time' => 'required|string|after:start_time',
                'notes' => 'nullable|string|max:500',
            ]);

            $timeSlot = GymTimeSlot::with(['gymHall', 'gymHall.courts'])
                ->whereHas('gymHall', function ($query) use ($userClub) {
                    $query->where('club_id', $userClub->id);
                })
                ->where('id', $request->gym_time_slot_id)
                ->first();

            if (!$timeSlot) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zeitslot nicht gefunden oder keine Berechtigung.'
                ], 404);
            }

            $team = Team::where('id', $request->team_id)
                ->where('club_id', $userClub->id)
                ->first();

            if (!$team) {
                return response()->json([
                    'success' => false,
                    'message' => 'Team nicht gefunden oder keine Berechtigung.'
                ], 404);
            }

            // Validate court belongs to the same hall if specified
            $gymCourt = null;
            if ($request->gym_court_id) {
                $gymCourt = \App\Models\GymCourt::where('id', $request->gym_court_id)
                    ->where('gym_hall_id', $timeSlot->gym_hall_id)
                    ->where('is_active', true)
                    ->first();

                if (!$gymCourt) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Das ausgewählte Feld gehört nicht zu dieser Halle oder ist nicht aktiv.',
                        'errors' => ['gym_court_id' => ['Ungültiges Feld ausgewählt.']]
                    ], 422);
                }
            }

            // Additional validation for time slot
            if (!$timeSlot->gymHall) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zeitslot hat keine zugehörige Sporthalle.'
                ], 422);
            }

            $validationErrors = $timeSlot->canAssignTeamToSegment(
                $team->id,
                $request->day_of_week,
                $request->start_time,
                $request->end_time,
                $gymCourt?->id
            );

            if (!empty($validationErrors)) {
                Log::info('Team assignment validation failed', [
                    'user_id' => auth()->id(),
                    'time_slot_id' => $request->gym_time_slot_id,
                    'team_id' => $request->team_id,
                    'day_of_week' => $request->day_of_week,
                    'time_range' => $request->start_time . ' - ' . $request->end_time,
                    'gym_hall_id' => $timeSlot->gym_hall_id,
                    'global_parallel_bookings' => $timeSlot->gymHall->supports_parallel_bookings,
                    'day_specific_parallel_bookings' => $timeSlot->gymHall->supportsParallelBookingsForDay($request->day_of_week),
                    'validation_errors' => $validationErrors
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Zuordnung nicht möglich: ' . implode(' ', $validationErrors),
                    'errors' => $validationErrors,
                    'debug_info' => [
                        'global_parallel_bookings_enabled' => $timeSlot->gymHall->supports_parallel_bookings,
                        'day_specific_parallel_bookings_enabled' => $timeSlot->gymHall->supportsParallelBookingsForDay($request->day_of_week),
                        'gym_hall_name' => $timeSlot->gymHall->name
                    ]
                ], 422);
            }

            // Use database transaction to ensure data integrity
            DB::beginTransaction();
            try {
                $assignment = $timeSlot->assignTeamToSegment(
                    $team,
                    $request->day_of_week,
                    $request->start_time,
                    $request->end_time,
                    $user,
                    $request->notes,
                    $gymCourt
                );

                if (!$assignment) {
                    DB::rollback();
                    Log::error('Failed to create team assignment - assignTeamToSegment returned null', [
                        'user_id' => auth()->id(),
                        'time_slot_id' => $request->gym_time_slot_id,
                        'team_id' => $request->team_id,
                        'request_data' => $request->all()
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Fehler beim Erstellen der Team-Zuordnung.'
                    ], 500);
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

            return response()->json([
                'success' => true,
                'message' => 'Team erfolgreich dem Zeitfenster zugeordnet.',
                'data' => [
                    'id' => $assignment->id,
                    'team_name' => $team->name,
                    'time_range' => $assignment->time_range ?? 'N/A',
                    'day_name' => $assignment->day_name ?? 'N/A',
                    'court_name' => $gymCourt?->name,
                    'court_id' => $gymCourt?->id,
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::info('Validation failed for team assignment', [
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
                'validation_errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ungültige Eingabedaten.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('Model not found during team assignment', [
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
                'model' => $e->getModel(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ein benötigter Datensatz wurde nicht gefunden.'
            ], 404);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error during team assignment', [
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
                'sql_error' => $e->getMessage(),
                'error_code' => $e->getCode()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Datenbankfehler bei der Zuordnung. Möglicherweise existiert bereits eine Zuordnung für diesen Zeitraum.'
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error assigning team to segment: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
                'request_url' => request()->fullUrl(),
                'exception_class' => get_class($e),
                'stack_trace' => $e->getTraceAsString(),
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Zuordnen des Teams.'
            ], 500);
        }
    }

    /**
     * Remove team assignment from time segment.
     */
    public function removeTeamSegmentAssignment(Request $request, $assignmentId): JsonResponse
    {
        $user = Auth::user();
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();

        if (!$user->hasAnyRole(['tenant_admin', 'super_admin', 'club_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Keine Berechtigung zum Entfernen von Team-Zuordnungen.'
            ], 403);
        }

        $assignment = GymTimeSlotTeamAssignment::whereHas('gymTimeSlot.gymHall', function ($query) use ($userClub) {
                $query->where('club_id', $userClub->id);
            })
            ->where('id', $assignmentId)
            ->firstOrFail();

        $assignment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Team-Zuordnung erfolgreich entfernt.',
        ]);
    }

    /**
     * Get team assignments for a specific time slot.
     */
    public function getTimeSlotTeamAssignments(Request $request, $timeSlotId): JsonResponse
    {
        try {
            $user = Auth::user();
            $userClub = $user->currentTeam?->club ?? $user->clubs()->first();

            if (!$userClub) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kein Verein gefunden.'
                ], 404);
            }

            $timeSlot = GymTimeSlot::whereHas('gymHall', function ($query) use ($userClub) {
                    $query->where('club_id', $userClub->id);
                })
                ->where('id', $timeSlotId)
                ->first();

            if (!$timeSlot) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zeitslot nicht gefunden.'
                ], 404);
            }

            $assignments = $timeSlot->activeTeamAssignments()
                ->with(['team', 'gymCourt'])
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get()
                ->groupBy('day_of_week')
                ->map(function ($dayAssignments) {
                    return $dayAssignments->map(function ($assignment) {
                        return [
                            'id' => $assignment->id,
                            'team_id' => $assignment->team_id,
                            'team_name' => $assignment->team?->name ?? 'Unbekanntes Team',
                            'gym_court_id' => $assignment->gym_court_id,
                            'court_name' => $assignment->gymCourt?->name,
                            'start_time' => $assignment->start_time ? $assignment->start_time->format('H:i') : null,
                            'end_time' => $assignment->end_time ? $assignment->end_time->format('H:i') : null,
                            'duration_minutes' => $assignment->duration_minutes,
                            'notes' => $assignment->notes,
                            'assigned_at' => $assignment->assigned_at ? $assignment->assigned_at->format('Y-m-d H:i') : null,
                        ];
                    })->toArray();
                });

            return response()->json([
                'success' => true,
                'data' => $assignments,
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting time slot team assignments: ' . $e->getMessage(), [
                'time_slot_id' => $timeSlotId,
                'user_id' => auth()->id(),
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Laden der Team-Zuordnungen.'
            ], 500);
        }
    }

    /**
     * Check for team time slot conflicts.
     */
    private function checkTeamTimeSlotConflicts(int $teamId, GymTimeSlot $timeSlot): array
    {
        $conflicts = [];

        // Check for existing time slot assignments
        $existingSlots = GymTimeSlot::where('team_id', $teamId)
            ->where('id', '!=', $timeSlot->id)
            ->where('status', 'active')
            ->get();

        foreach ($existingSlots as $existingSlot) {
            // Check day overlap
            if ($timeSlot->day_of_week === $existingSlot->day_of_week) {
                // Check time overlap
                if ($this->timePeriodsOverlap(
                    $timeSlot->start_time,
                    $timeSlot->end_time,
                    $existingSlot->start_time,
                    $existingSlot->end_time
                )) {
                    $conflicts[] = [
                        'type' => 'time_slot_conflict',
                        'message' => 'Team hat bereits einen Zeitslot zu dieser Zeit',
                        'conflicting_slot' => [
                            'id' => $existingSlot->id,
                            'title' => $existingSlot->title,
                            'day_of_week' => $existingSlot->day_of_week,
                            'start_time' => $existingSlot->start_time?->format('H:i'),
                            'end_time' => $existingSlot->end_time?->format('H:i'),
                            'gym_hall' => $existingSlot->gymHall->name,
                        ]
                    ];
                }
            }
        }

        return $conflicts;
    }

    /**
     * Check if two time periods overlap.
     */
    private function timePeriodsOverlap($start1, $end1, $start2, $end2): bool
    {
        if (!$start1 || !$end1 || !$start2 || !$end2) {
            return false;
        }

        $start1 = Carbon::createFromTimeString($start1);
        $end1 = Carbon::createFromTimeString($end1);
        $start2 = Carbon::createFromTimeString($start2);
        $end2 = Carbon::createFromTimeString($end2);

        return $start1->lt($end2) && $start2->lt($end1);
    }

    /**
     * Calculate duration in minutes between two time strings.
     */
    private function calculateDuration(?string $startTime, ?string $endTime): ?int
    {
        if (!$startTime || !$endTime) {
            return null;
        }

        $start = Carbon::createFromTimeString($startTime);
        $end = Carbon::createFromTimeString($endTime);

        return $end->diffInMinutes($start);
    }
}
