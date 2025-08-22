<?php

namespace App\Http\Controllers;

use App\Models\GymHall;
use App\Models\GymBooking;
use App\Models\GymBookingRequest;
use App\Models\GymTimeSlot;
use App\Services\GymScheduleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
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
        $user = Auth::user();
        $userClub = $user->currentTeam?->club ?? $user->clubs()->first();
        
        if (!$user->hasAnyRole(['admin', 'super_admin', 'club_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Keine Berechtigung zum Bearbeiten von Zeitslots.'
            ], 403);
        }
        
        $hall = GymHall::where('id', $hallId)
            ->where('club_id', $userClub->id)
            ->firstOrFail();

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

        // Additional validation: either custom_times OR day_of_week + start_time + end_time
        foreach ($request->time_slots as $index => $slotData) {
            $usesCustomTimes = $slotData['uses_custom_times'] ?? false;
            
            if ($usesCustomTimes) {
                if (empty($slotData['custom_times'])) {
                    return response()->json([
                        'success' => false,
                        'message' => "Zeitslot {$index}: Bei individuellen Zeiten müssen custom_times definiert sein.",
                        'errors' => ["time_slots.{$index}.custom_times" => ['Custom times sind erforderlich.']]
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

        return response()->json([
            'success' => true,
            'message' => 'Zeitslots erfolgreich aktualisiert.',
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