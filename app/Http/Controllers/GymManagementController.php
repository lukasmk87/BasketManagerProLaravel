<?php

namespace App\Http\Controllers;

use App\Models\GymHall;
use App\Models\GymBooking;
use App\Models\GymBookingRequest;
use App\Models\GymTimeSlot;
use App\Models\GymTimeSlotTeamAssignment;
use App\Models\Team;
use App\Services\GymScheduleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class GymManagementController extends Controller
{
    public function __construct(
        private GymScheduleService $gymScheduleService
    ) {}

    /**
     * Display the gym management dashboard.
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();
        
        // Get gym halls for the user's club
        $gymHalls = collect();
        if ($userClub) {
            $gymHalls = GymHall::where('club_id', $userClub->id)
                ->with(['timeSlots', 'bookings.team'])
                ->orderBy('name')
                ->get();
        }

        // Get statistics
        $stats = $this->getGymStatistics($userClub);

        // Get pending booking requests for club admins
        $pendingRequests = collect();
        if ($userClub && $user->hasAnyRole(['admin', 'super_admin', 'club_admin'])) {
            $pendingRequests = GymBookingRequest::whereHas('gymBooking.gymTimeSlot.gymHall', function ($query) use ($userClub) {
                    $query->where('club_id', $userClub->id);
                })
                ->where('status', 'pending')
                ->with(['team', 'timeSlot'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Get user permissions
        $userPermissions = [
            'canManageHalls' => $user->hasAnyRole(['admin', 'super_admin', 'club_admin']),
            'canBookHalls' => $user->hasAnyRole(['admin', 'super_admin', 'club_admin', 'trainer', 'team_manager']),
            'canApproveRequests' => $user->hasAnyRole(['admin', 'super_admin', 'club_admin']),
        ];

        return Inertia::render('Gym/Dashboard', [
            'gymHalls' => $gymHalls,
            'initialStats' => $stats,
            'pendingRequests' => $pendingRequests,
            'userPermissions' => $userPermissions,
            'currentClub' => $userClub ? [
                'id' => $userClub->id,
                'name' => $userClub->name,
            ] : null,
        ]);
    }

    /**
     * Show the form for creating a new gym hall.
     */
    public function create(): Response
    {
        $this->authorize('create', GymHall::class);
        
        $user = Auth::user();
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();
        
        return Inertia::render('Gym/CreateHall', [
            'currentClub' => $userClub ? [
                'id' => $userClub->id,
                'name' => $userClub->name,
            ] : null,
        ]);
    }

    /**
     * Show halls management page.
     */
    public function halls(): Response
    {
        $this->authorize('viewAny', GymHall::class);
        
        $user = Auth::user();
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();
        
        $gymHalls = collect();
        if ($userClub) {
            $gymHalls = GymHall::where('club_id', $userClub->id)
                ->withCount(['timeSlots', 'bookings'])
                ->with([
                    'timeSlots' => function ($query) {
                        $query->orderBy('day_of_week')->orderBy('start_time');
                    },
                    'courts' => function ($query) {
                        $query->active()->orderBy('sort_order');
                    }
                ])
                ->orderBy('name')
                ->get();
        }

        return Inertia::render('Gym/Halls', [
            'gymHalls' => $gymHalls,
            'currentClub' => $userClub ? [
                'id' => $userClub->id,
                'name' => $userClub->name,
            ] : null,
        ]);
    }

    /**
     * Show bookings management page.
     */
    public function bookings(): Response
    {
        $user = Auth::user();
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();
        
        $bookings = collect();
        if ($userClub) {
            $query = GymBooking::whereHas('gymTimeSlot.gymHall', function ($query) use ($userClub) {
                $query->where('club_id', $userClub->id);
            });

            // Filter by user's team if not admin
            if (!$user->hasAnyRole(['admin', 'super_admin', 'club_admin'])) {
                $userTeam = $user->currentTeam;
                if ($userTeam) {
                    $query->where('team_id', $userTeam->id);
                }
            }

            $bookings = $query->with(['gymTimeSlot.gymHall', 'team'])
                ->orderBy('booking_date', 'desc')
                ->orderBy('start_time')
                ->paginate(20);
        }

        return Inertia::render('Gym/Bookings', [
            'bookings' => $bookings,
        ]);
    }

    /**
     * Show booking requests management page.
     */
    public function requests(): Response
    {
        $this->authorize('viewAny', GymBookingRequest::class);
        
        $user = Auth::user();
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();
        
        $requests = collect();
        if ($userClub) {
            $requests = GymBookingRequest::whereHas('gymBooking.gymTimeSlot.gymHall', function ($query) use ($userClub) {
                    $query->where('club_id', $userClub->id);
                })
                ->with(['team', 'timeSlot', 'requestedBy'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);
        }

        return Inertia::render('Gym/Requests', [
            'requests' => $requests,
        ]);
    }

    /**
     * Get gym statistics for the dashboard.
     */
    private function getGymStatistics($club): array
    {
        if (!$club) {
            return [
                'total_halls' => 0,
                'active_bookings' => 0,
                'pending_requests' => 0,
                'utilization_rate' => 0,
            ];
        }

        $totalHalls = GymHall::where('club_id', $club->id)->count();
        
        $activeBookings = GymBooking::whereHas('gymTimeSlot.gymHall', function ($query) use ($club) {
                $query->where('club_id', $club->id);
            })
            ->where('booking_date', '>=', now()->toDateString())
            ->where('status', 'confirmed')
            ->count();
        
        $pendingRequests = GymBookingRequest::whereHas('gymBooking.gymTimeSlot.gymHall', function ($query) use ($club) {
                $query->where('club_id', $club->id);
            })
            ->where('status', 'pending')
            ->count();

        // Calculate utilization rate (simplified)
        $utilizationRate = 0;
        if ($totalHalls > 0) {
            $totalPossibleSlots = $totalHalls * 7 * 12; // 7 days, 12 possible time slots per day
            $bookedSlots = GymBooking::whereHas('gymTimeSlot.gymHall', function ($query) use ($club) {
                    $query->where('club_id', $club->id);
                })
                ->where('booking_date', '>=', now()->startOfWeek())
                ->where('booking_date', '<=', now()->endOfWeek())
                ->where('status', 'confirmed')
                ->count();
            
            $utilizationRate = $totalPossibleSlots > 0 ? round(($bookedSlots / $totalPossibleSlots) * 100, 1) : 0;
        }

        return [
            'total_halls' => $totalHalls,
            'active_bookings' => $activeBookings,
            'pending_requests' => $pendingRequests,
            'utilization_rate' => $utilizationRate,
        ];
    }

    /**
     * Get time slots for a specific gym hall.
     */
    public function getHallTimeSlots(Request $request, $hallId): JsonResponse
    {
        $user = Auth::user();
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();
        
        $hall = GymHall::where('id', $hallId)
            ->where('club_id', $userClub->id)
            ->firstOrFail();

        $timeSlots = GymTimeSlot::where('gym_hall_id', $hall->id)
            ->with(['team'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

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
        ]);
    }

    /**
     * Create or update time slots for a gym hall.
     */
    public function updateHallTimeSlots(Request $request, $hallId): JsonResponse
    {
        \Log::info('UpdateHallTimeSlots called', [
            'hall_id' => $hallId,
            'user_id' => auth()->id(),
            'payload' => $request->all()
        ]);
        
        $user = Auth::user();
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();
        
        if (!$user->hasAnyRole(['admin', 'super_admin', 'club_admin'])) {
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
                // For custom times, we support two formats:
                // 1. Old format: custom_times object with days as keys
                // 2. New format: separate slots with day_of_week + start_time + end_time
                
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
        
        // Collect IDs of slots being updated (to exclude them from overlap checking)
        $updatingSlotIds = [];
        foreach ($request->time_slots as $slotData) {
            if (isset($slotData['id']) && !empty($slotData['id'])) {
                $updatingSlotIds[] = $slotData['id'];
            }
        }
        
        // If no existing slots are being updated, we're replacing all slots
        // In this case, exclude all existing slots from overlap checking
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

                // Check for overlaps with existing slots (excluding slots being updated)
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
            // Check if this is an update (has id) or create new
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
                    'status' => 'active',
                    'is_recurring' => true,
                    'allows_substitution' => true,
                ]);
                $processedSlotIds[] = $timeSlot->id;
                $createdSlots[] = $timeSlot;
            }
        }

        // Delete slots that were not included in the update (only if we got specific slots to replace)
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
                    'supports_parallel_bookings' => $timeSlotData['supports_parallel_bookings'] ?? false
                ];
            }
        }
        
        // Update hall's operating hours if we have data
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
        
        if (!$user->hasAnyRole(['admin', 'super_admin', 'club_admin'])) {
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

        // Check for overlaps with other slots (excluding current slot)
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

        $teams = \App\Models\Team::where('club_id', $userClub->id)
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
        
        if (!$user->hasAnyRole(['admin', 'super_admin', 'club_admin'])) {
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

        $team = \App\Models\Team::where('id', $request->team_id)
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
        
        if (!$user->hasAnyRole(['admin', 'super_admin', 'club_admin'])) {
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

        $team = \App\Models\Team::where('id', $teamId)
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

        $start1 = \Carbon\Carbon::createFromTimeString($start1);
        $end1 = \Carbon\Carbon::createFromTimeString($end1);
        $start2 = \Carbon\Carbon::createFromTimeString($start2);
        $end2 = \Carbon\Carbon::createFromTimeString($end2);

        return $start1->lt($end2) && $start2->lt($end1);
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
            \Log::error('Error getting time slot segments: ' . $e->getMessage(), [
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
        $user = Auth::user();
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();
        
        if (!$user->hasAnyRole(['admin', 'super_admin', 'club_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Keine Berechtigung zum Zuordnen von Teams.'
            ], 403);
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

        $timeSlot = GymTimeSlot::whereHas('gymHall', function ($query) use ($userClub) {
                $query->where('club_id', $userClub->id);
            })
            ->where('id', $request->gym_time_slot_id)
            ->firstOrFail();

        $team = \App\Models\Team::where('id', $request->team_id)
            ->where('club_id', $userClub->id)
            ->firstOrFail();

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

        $validationErrors = $timeSlot->canAssignTeamToSegment(
            $team->id,
            $request->day_of_week,
            $request->start_time,
            $request->end_time,
            $gymCourt?->id
        );

        if (!empty($validationErrors)) {
            return response()->json([
                'success' => false,
                'message' => 'Zuordnung nicht möglich',
                'errors' => $validationErrors
            ], 422);
        }

        $assignment = $timeSlot->assignTeamToSegment(
            $team,
            $request->day_of_week,
            $request->start_time,
            $request->end_time,
            $user,
            $request->notes,
            $gymCourt
        );

        return response()->json([
            'success' => true,
            'message' => 'Team erfolgreich dem Zeitfenster zugeordnet.',
            'data' => [
                'id' => $assignment->id,
                'team_name' => $team->name,
                'time_range' => $assignment->time_range,
                'day_name' => $assignment->day_name,
                'court_name' => $gymCourt?->name,
                'court_id' => $gymCourt?->id,
            ]
        ]);
    }

    /**
     * Remove team assignment from time segment.
     */
    public function removeTeamSegmentAssignment(Request $request, $assignmentId): JsonResponse
    {
        $user = Auth::user();
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();
        
        if (!$user->hasAnyRole(['admin', 'super_admin', 'club_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Keine Berechtigung zum Entfernen von Team-Zuordnungen.'
            ], 403);
        }

        $assignment = \App\Models\GymTimeSlotTeamAssignment::whereHas('gymTimeSlot.gymHall', function ($query) use ($userClub) {
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
            \Log::error('Error getting time slot team assignments: ' . $e->getMessage(), [
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
     * Get teams for team selection in gym management.
     */
    public function getTeams(Request $request): JsonResponse
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
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $teams
        ]);
    }

    /**
     * Get courts for a specific gym hall.
     */
    public function getHallCourts(Request $request, $hallId): JsonResponse
    {
        $user = Auth::user();
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();
        
        $hall = GymHall::where('id', $hallId)
            ->where('club_id', $userClub->id)
            ->firstOrFail();

        $courts = $hall->courts()
            ->orderBy('sort_order')
            ->orderBy('court_number')
            ->get(['id', 'name', 'court_number', 'is_active', 'is_main_court', 'metadata']);

        return response()->json([
            'success' => true,
            'data' => $courts->map(function ($court) {
                return [
                    'id' => $court->id,
                    'name' => $court->name,
                    'court_number' => $court->court_number,
                    'is_active' => $court->is_active,
                    'is_main_court' => $court->is_main_court,
                    'court_identifier' => $court->court_identifier,
                    'color_code' => $court->color_code,
                ];
            }),
        ]);
    }

    /**
     * Update a gym court.
     */
    public function updateCourt(Request $request, $courtId): JsonResponse
    {
        $user = Auth::user();
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();
        
        if (!$user->hasAnyRole(['admin', 'super_admin', 'club_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Keine Berechtigung zum Bearbeiten von Feldern.'
            ], 403);
        }

        $court = \App\Models\GymCourt::whereHas('gymHall', function ($query) use ($userClub) {
                $query->where('club_id', $userClub->id);
            })
            ->where('id', $courtId)
            ->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'is_main_court' => 'boolean',
        ]);

        // Handle main court setting
        if ($request->has('is_main_court') && $request->is_main_court) {
            // Set as main court (automatically unsets other main courts)
            $court->setAsMainCourt();
        } elseif ($request->has('is_main_court') && !$request->is_main_court && $court->is_main_court) {
            // Unset as main court
            $court->unsetAsMainCourt();
        }
        
        $court->update([
            'name' => $request->name,
            'is_active' => $request->is_active ?? $court->is_active,
        ]);
        
        // Refresh to get updated main court status
        $court->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Feld erfolgreich aktualisiert.',
            'data' => [
                'id' => $court->id,
                'name' => $court->name,
                'court_number' => $court->court_number,
                'is_active' => $court->is_active,
                'is_main_court' => $court->is_main_court,
                'court_identifier' => $court->court_identifier,
                'color_code' => $court->color_code,
            ]
        ]);
    }

    /**
     * Create a new court for a gym hall.
     */
    public function createCourt(Request $request, $hallId): JsonResponse
    {
        $user = Auth::user();
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();
        
        if (!$user->hasAnyRole(['admin', 'super_admin', 'club_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Keine Berechtigung zum Erstellen von Feldern.'
            ], 403);
        }

        $hall = GymHall::where('id', $hallId)
            ->where('club_id', $userClub->id)
            ->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'court_number' => 'required|integer|min:1|unique:gym_courts,court_number,NULL,id,gym_hall_id,' . $hall->id,
        ]);

        $court = $hall->courts()->create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'name' => $request->name,
            'court_number' => $request->court_number,
            'is_active' => true,
            'sort_order' => $request->court_number,
            'metadata' => [
                'identifier' => (string) $request->court_number,
                'color_code' => '#3B82F6',
                'court_type' => 'full',
            ]
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Feld erfolgreich erstellt.',
            'data' => [
                'id' => $court->id,
                'name' => $court->name,
                'court_number' => $court->court_number,
                'is_active' => $court->is_active,
                'is_main_court' => $court->is_main_court,
                'court_identifier' => $court->court_identifier,
                'color_code' => $court->color_code,
            ]
        ]);
    }

    /**
     * Calculate duration in minutes between two time strings.
     */
    private function calculateDuration(?string $startTime, ?string $endTime): ?int
    {
        if (!$startTime || !$endTime) {
            return null;
        }

        $start = \Carbon\Carbon::createFromTimeString($startTime);
        $end = \Carbon\Carbon::createFromTimeString($endTime);
        
        return $end->diffInMinutes($start);
    }
}