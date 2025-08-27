<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GymHall;
use App\Models\GymCourt;
use App\Models\Club;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class GymHallController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of gym halls for a specific club.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'club_id' => 'required|exists:clubs,id',
            'active_only' => 'boolean',
            'with_schedule' => 'boolean',
        ]);

        $clubId = $request->input('club_id');
        
        // Check if user has access to this club
        $this->authorize('viewAny', [GymHall::class, $clubId]);

        $query = GymHall::where('club_id', $clubId)
            ->with(['club']);

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        if ($request->boolean('with_schedule')) {
            $query->with(['timeSlots' => function ($q) {
                $q->active()->with('team:id,name,short_name');
            }]);
        }

        $gymHalls = $query->orderBy('name')->get();

        // Add weekly schedule if requested
        if ($request->boolean('with_schedule')) {
            $gymHalls->each(function ($hall) {
                $hall->weekly_schedule = $hall->getWeeklySchedule();
            });
        }

        return response()->json([
            'success' => true,
            'data' => $gymHalls,
            'meta' => [
                'total' => $gymHalls->count(),
                'active_count' => $gymHalls->where('is_active', true)->count(),
            ]
        ]);
    }

    /**
     * Store a newly created gym hall.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'club_id' => 'required|exists:clubs,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'hall_type' => 'nullable|in:single,double,triple,multi',
            'court_count' => 'nullable|integer|min:1|max:10',
            'supports_parallel_bookings' => 'boolean',
            'min_booking_duration_minutes' => 'nullable|integer|min:15|max:480',
            'booking_increment_minutes' => 'nullable|integer|in:15,30,60',
            'address_street' => 'nullable|string|max:255',
            'address_city' => 'nullable|string|max:255',
            'address_zip' => 'nullable|string|max:20',
            'address_country' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'capacity' => 'nullable|integer|min:1',
            'facilities' => 'nullable|array',
            'equipment' => 'nullable|array',
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i|after:opening_time',
            'operating_hours' => 'nullable|array',
            'hourly_rate' => 'nullable|numeric|min:0',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:255',
            'requires_key' => 'boolean',
            'access_instructions' => 'nullable|string',
            'special_rules' => 'nullable|string',
        ]);

        $this->authorize('create', [GymHall::class, $request->input('club_id')]);

        // Prepare data for creation, filtering out null/empty values and ensuring proper types
        $data = collect($request->only([
            'club_id',
            'name', 
            'description',
            'hall_type',
            'court_count',
            'supports_parallel_bookings',
            'min_booking_duration_minutes',
            'booking_increment_minutes',
            'address_street',
            'address_city', 
            'address_zip',
            'address_country',
            'latitude',
            'longitude',
            'capacity',
            'facilities',
            'equipment',
            'opening_time',
            'closing_time',
            'operating_hours',
            'hourly_rate',
            'contact_name',
            'contact_phone',
            'contact_email',
            'is_active',
            'requires_key',
            'access_instructions',
            'special_rules'
        ]))->filter(function ($value, $key) {
            // Keep boolean values even if false, but filter out null/empty strings
            if ($key === 'supports_parallel_bookings' || $key === 'requires_key' || $key === 'is_active') {
                return !is_null($value);
            }
            // Keep arrays even if empty
            if (in_array($key, ['facilities', 'equipment', 'operating_hours'])) {
                return !is_null($value);
            }
            // Filter out null or empty string values for other fields
            return !is_null($value) && $value !== '';
        })->toArray();

        // Set defaults if not provided
        $data['hall_type'] = $data['hall_type'] ?? 'single';
        $data['court_count'] = $data['court_count'] ?? 1;
        $data['supports_parallel_bookings'] = $data['supports_parallel_bookings'] ?? false;
        $data['min_booking_duration_minutes'] = $data['min_booking_duration_minutes'] ?? 30;
        $data['booking_increment_minutes'] = $data['booking_increment_minutes'] ?? 30;
        $data['is_active'] = $data['is_active'] ?? true;
        $data['requires_key'] = $data['requires_key'] ?? false;

        $gymHall = GymHall::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Sporthalle erfolgreich erstellt.',
            'data' => $gymHall->load('club')
        ], 201);
    }

    /**
     * Display the specified gym hall.
     */
    public function show(GymHall $gymHall): JsonResponse
    {
        try {
            // Verify gym hall exists and is accessible
            if (!$gymHall->exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sporthalle nicht gefunden.'
                ], 404);
            }

            $this->authorize('view', $gymHall);

            $gymHall->load([
                'club',
                'timeSlots' => function ($query) {
                    $query->active()->with('team:id,name,short_name');
                }
            ]);

            // Add additional computed data with null safety
            $startOfWeek = now()->startOfWeek();
            $endOfWeek = now()->endOfWeek();

            $additionalData = [];
            
            try {
                $additionalData['weekly_schedule'] = $gymHall->getWeeklySchedule($startOfWeek) ?? [];
            } catch (\Exception $e) {
                \Log::warning('Failed to get weekly schedule for gym hall', [
                    'gym_hall_id' => $gymHall->id,
                    'error' => $e->getMessage()
                ]);
                $additionalData['weekly_schedule'] = [];
            }

            try {
                $additionalData['utilization_rate'] = $gymHall->getUtilizationRate($startOfWeek, $endOfWeek) ?? 0.0;
            } catch (\Exception $e) {
                \Log::warning('Failed to get utilization rate for gym hall', [
                    'gym_hall_id' => $gymHall->id,
                    'error' => $e->getMessage()
                ]);
                $additionalData['utilization_rate'] = 0.0;
            }

            try {
                $additionalData['is_open_now'] = $gymHall->is_open ?? false;
            } catch (\Exception $e) {
                \Log::warning('Failed to get is_open status for gym hall', [
                    'gym_hall_id' => $gymHall->id,
                    'error' => $e->getMessage()
                ]);
                $additionalData['is_open_now'] = false;
            }

            try {
                $additionalData['todays_schedule'] = $gymHall->getTodaysSchedule() ?? [];
            } catch (\Exception $e) {
                \Log::warning('Failed to get today\'s schedule for gym hall', [
                    'gym_hall_id' => $gymHall->id,
                    'error' => $e->getMessage()
                ]);
                $additionalData['todays_schedule'] = [];
            }

            return response()->json([
                'success' => true,
                'data' => array_merge($gymHall->toArray(), $additionalData)
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            \Log::info('Authorization failed for gym hall view', [
                'gym_hall_id' => $gymHall->id ?? 'unknown',
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Sie haben keine Berechtigung, diese Sporthalle zu betrachten.'
            ], 403);
        } catch (\Exception $e) {
            \Log::error('Error showing gym hall: ' . $e->getMessage(), [
                'gym_hall_id' => $gymHall->id ?? 'unknown',
                'user_id' => auth()->id(),
                'request_url' => request()->fullUrl(),
                'stack_trace' => $e->getTraceAsString(),
                'exception_class' => get_class($e),
                'exception' => $e
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Laden der Hallendaten.'
            ], 500);
        }
    }

    /**
     * Update the specified gym hall.
     */
    public function update(Request $request, GymHall $gymHall): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'hall_type' => 'sometimes|in:single,double,triple,multi',
            'court_count' => 'sometimes|integer|min:1|max:10',
            'supports_parallel_bookings' => 'boolean',
            'min_booking_duration_minutes' => 'sometimes|integer|min:15|max:480',
            'booking_increment_minutes' => 'sometimes|integer|in:15,30,60',
            'address_street' => 'nullable|string|max:255',
            'address_city' => 'nullable|string|max:255',
            'address_zip' => 'nullable|string|max:20',
            'address_country' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'capacity' => 'nullable|integer|min:1',
            'facilities' => 'nullable|array',
            'equipment' => 'nullable|array',
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i|after:opening_time',
            'operating_hours' => 'nullable|array',
            'hourly_rate' => 'nullable|numeric|min:0',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
            'requires_key' => 'boolean',
            'access_instructions' => 'nullable|string',
            'special_rules' => 'nullable|string',
        ]);

        $this->authorize('update', $gymHall);

        $gymHall->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Sporthalle erfolgreich aktualisiert.',
            'data' => $gymHall->fresh(['club'])
        ]);
    }

    /**
     * Remove the specified gym hall from storage.
     */
    public function destroy(GymHall $gymHall): JsonResponse
    {
        $this->authorize('delete', $gymHall);

        // Check if hall has active time slots
        if ($gymHall->activeTimeSlots()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Sporthalle kann nicht gelöscht werden, da sie aktive Zeitfenster hat.'
            ], 422);
        }

        $gymHall->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sporthalle erfolgreich gelöscht.'
        ]);
    }

    /**
     * Get availability for a specific date range.
     */
    public function availability(Request $request, GymHall $gymHall): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $this->authorize('view', $gymHall);

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));

        $availability = [];
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $availability[] = [
                'date' => $current->toDateString(),
                'day_name' => $current->format('l'),
                'available_slots' => $gymHall->getAvailableTimeSlots($current),
                'is_available' => $gymHall->isAvailableAt($current),
            ];

            $current->addDay();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'gym_hall' => $gymHall->only(['id', 'name', 'capacity']),
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ],
                'availability' => $availability
            ]
        ]);
    }

    /**
     * Get weekly schedule for a gym hall.
     */
    public function schedule(Request $request, GymHall $gymHall): JsonResponse
    {
        $request->validate([
            'week_start' => 'nullable|date',
        ]);

        $this->authorize('view', $gymHall);

        $weekStart = $request->input('week_start') 
            ? Carbon::parse($request->input('week_start'))->startOfWeek()
            : now()->startOfWeek();

        $schedule = $gymHall->getWeeklySchedule($weekStart);

        // Add booking information for each slot
        foreach ($schedule as &$day) {
            foreach ($day['slots'] as &$slot) {
                $slot['bookings'] = $gymHall->bookings()
                    ->whereDate('booking_date', $day['date'])
                    ->whereIn('status', ['reserved', 'confirmed', 'released'])
                    ->with(['team:id,name,short_name', 'bookedByUser:id,name'])
                    ->get();
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'gym_hall' => $gymHall->only(['id', 'name', 'address_street', 'address_city']),
                'week_start' => $weekStart->toDateString(),
                'schedule' => $schedule,
                'utilization_rate' => $gymHall->getUtilizationRate(
                    $weekStart, 
                    $weekStart->copy()->endOfWeek()
                )
            ]
        ]);
    }

    /**
     * Get statistics for a gym hall.
     */
    public function statistics(Request $request, GymHall $gymHall): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|in:week,month,quarter,year',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $this->authorize('view', $gymHall);

        $period = $request->input('period', 'month');
        
        // Set date range based on period
        if ($request->has(['start_date', 'end_date'])) {
            $startDate = Carbon::parse($request->input('start_date'));
            $endDate = Carbon::parse($request->input('end_date'));
        } else {
            switch ($period) {
                case 'week':
                    $startDate = now()->startOfWeek();
                    $endDate = now()->endOfWeek();
                    break;
                case 'quarter':
                    $startDate = now()->startOfQuarter();
                    $endDate = now()->endOfQuarter();
                    break;
                case 'year':
                    $startDate = now()->startOfYear();
                    $endDate = now()->endOfYear();
                    break;
                default: // month
                    $startDate = now()->startOfMonth();
                    $endDate = now()->endOfMonth();
                    break;
            }
        }

        $bookings = $gymHall->bookings()
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->with('team:id,name');

        $statistics = [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
                'type' => $period
            ],
            'total_bookings' => $bookings->count(),
            'bookings_by_status' => $bookings->get()->groupBy('status')->map->count(),
            'utilization_rate' => $gymHall->getUtilizationRate($startDate, $endDate),
            'most_active_teams' => $bookings->get()
                ->groupBy('team.name')
                ->map->count()
                ->sortDesc()
                ->take(5),
            'bookings_by_day' => $bookings->get()
                ->groupBy(fn($booking) => $booking->booking_date->format('l'))
                ->map->count(),
            'average_bookings_per_day' => round($bookings->count() / $startDate->diffInDays($endDate), 1),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'gym_hall' => $gymHall->only(['id', 'name']),
                'statistics' => $statistics
            ]
        ]);
    }

    /**
     * Get gym management statistics for dashboard.
     */
    public function getStats(Request $request): JsonResponse
    {
        $user = Auth::user();
        $clubId = $user->currentTeam?->club_id ?? $user->clubs()->first()?->id;

        // If no club association, return empty stats instead of error
        if (!$clubId) {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_halls' => 0,
                    'active_bookings' => 0,
                    'pending_requests' => 0,
                    'utilization_rate' => 0,
                ],
                'message' => 'No club associated with user'
            ]);
        }

        $totalHalls = GymHall::where('club_id', $clubId)->count();
        
        $activeBookings = \App\Models\GymBooking::whereHas('gymTimeSlot', function ($query) use ($clubId) {
                $query->whereHas('gymHall', function ($q) use ($clubId) {
                    $q->where('club_id', $clubId);
                });
            })
            ->where('booking_date', '>=', now()->toDateString())
            ->where('status', 'confirmed')
            ->count();
        
        $pendingRequests = \App\Models\GymBookingRequest::whereHas('gymBooking', function ($query) use ($clubId) {
                $query->whereHas('gymTimeSlot', function ($q) use ($clubId) {
                    $q->whereHas('gymHall', function ($hall) use ($clubId) {
                        $hall->where('club_id', $clubId);
                    });
                });
            })
            ->where('status', 'pending')
            ->count();

        // Calculate utilization rate
        $utilizationRate = 0;
        if ($totalHalls > 0) {
            $totalPossibleSlots = $totalHalls * 7 * 12; // 7 days, 12 possible time slots per day
            $bookedSlots = \App\Models\GymBooking::whereHas('gymTimeSlot', function ($query) use ($clubId) {
                    $query->whereHas('gymHall', function ($q) use ($clubId) {
                        $q->where('club_id', $clubId);
                    });
                })
                ->where('booking_date', '>=', now()->startOfWeek())
                ->where('booking_date', '<=', now()->endOfWeek())
                ->where('status', 'confirmed')
                ->count();
            
            $utilizationRate = $totalPossibleSlots > 0 ? round(($bookedSlots / $totalPossibleSlots) * 100, 1) : 0;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_halls' => $totalHalls,
                'active_bookings' => $activeBookings,
                'pending_requests' => $pendingRequests,
                'utilization_rate' => $utilizationRate,
            ]
        ]);
    }

    /**
     * Get weekly bookings for calendar display.
     */
    public function getWeeklyBookings(Request $request): JsonResponse
    {
        $request->validate([
            'week_start' => 'required|date',
        ]);

        $user = Auth::user();
        $clubId = $user->currentTeam?->club_id ?? $user->clubs()->first()?->id;

        // If no club association, return empty bookings instead of error
        if (!$clubId) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No club associated with user'
            ]);
        }

        $weekStart = Carbon::parse($request->input('week_start'));
        $weekEnd = $weekStart->copy()->endOfWeek();

        $bookings = \App\Models\GymBooking::whereHas('gymTimeSlot', function ($query) use ($clubId) {
                $query->whereHas('gymHall', function ($q) use ($clubId) {
                    $q->where('club_id', $clubId);
                });
            })
            ->whereBetween('booking_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->with(['gymTimeSlot.gymHall:id,name', 'team:id,name'])
            ->get()
            ->groupBy('booking_date');

        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }

    /**
     * Get recent activities for dashboard.
     */
    public function getRecentActivities(Request $request): JsonResponse
    {
        $user = Auth::user();
        $clubId = $user->currentTeam?->club_id ?? $user->clubs()->first()?->id;

        // If no club association, return empty activities instead of error
        if (!$clubId) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No club associated with user'
            ]);
        }

        // Mock recent activities - in a real app this would come from an activity log
        $activities = collect([
            [
                'id' => 1,
                'type' => 'booking_created',
                'message' => 'Neue Buchung für Halle A erstellt',
                'created_at' => now()->subMinutes(15),
            ],
            [
                'id' => 2,
                'type' => 'request_approved',
                'message' => 'Buchungsanfrage für Team Warriors genehmigt',
                'created_at' => now()->subHours(2),
            ],
            [
                'id' => 3,
                'type' => 'booking_confirmed',
                'message' => 'Buchung für Halle B bestätigt',
                'created_at' => now()->subHours(5),
            ],
        ]);

        return response()->json([
            'success' => true,
            'data' => $activities
        ]);
    }

    /**
     * Get pending booking requests for dashboard.
     */
    public function getPendingRequests(Request $request): JsonResponse
    {
        $user = Auth::user();
        $clubId = $user->currentTeam?->club_id ?? $user->clubs()->first()?->id;

        // If no club association, return empty requests instead of error
        if (!$clubId) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No club associated with user'
            ]);
        }

        $requests = \App\Models\GymBookingRequest::whereHas('gymBooking', function ($query) use ($clubId) {
                $query->whereHas('gymTimeSlot', function ($q) use ($clubId) {
                    $q->whereHas('gymHall', function ($hall) use ($clubId) {
                        $hall->where('club_id', $clubId);
                    });
                });
            })
            ->where('status', 'pending')
            ->with(['requestingTeam:id,name', 'gymBooking.gymTimeSlot.gymHall:id,name', 'requestedByUser:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    /**
     * Initialize default courts for a gym hall based on its type.
     */
    public function initializeCourts(GymHall $gymHall): JsonResponse
    {
        $this->authorize('update', $gymHall);

        $gymHall->initializeDefaultCourts();

        $courts = $gymHall->courts()->orderBy('sort_order')->get();

        return response()->json([
            'success' => true,
            'message' => 'Default courts erfolgreich initialisiert.',
            'data' => [
                'gym_hall' => $gymHall->only(['id', 'name', 'hall_type', 'court_count']),
                'courts' => $courts->map(function ($court) {
                    return [
                        'id' => $court->id,
                        'name' => $court->name,
                        'court_number' => $court->court_number,
                        'court_identifier' => $court->court_identifier,
                        'color_code' => $court->color_code,
                        'court_type' => $court->court_type,
                        'max_capacity' => $court->max_capacity,
                        'is_active' => $court->is_active,
                        'sort_order' => $court->sort_order
                    ];
                })
            ]
        ]);
    }

    /**
     * Get enhanced availability with court-specific information.
     */
    public function availabilityWithCourts(Request $request, GymHall $gymHall): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'include_courts' => 'boolean'
        ]);

        $this->authorize('view', $gymHall);

        $startDate = Carbon::parse($request->input('start_date'));
        $endDate = Carbon::parse($request->input('end_date'));
        $includeCourts = $request->boolean('include_courts', true);

        if ($startDate->diffInDays($endDate) > 30) {
            return response()->json([
                'success' => false,
                'message' => 'Datumsbereich darf nicht größer als 30 Tage sein.'
            ], 400);
        }

        $gymScheduleService = app(\App\Services\GymScheduleService::class);
        $schedule = $gymScheduleService->getCourtSchedule($gymHall, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => [
                'gym_hall' => [
                    'id' => $gymHall->id,
                    'name' => $gymHall->name,
                    'hall_type' => $gymHall->hall_type,
                    'court_count' => $gymHall->court_count,
                    'supports_parallel_bookings' => $gymHall->supports_parallel_bookings
                ],
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ],
                'schedule' => $schedule,
                'summary' => [
                    'total_days' => count($schedule),
                    'total_courts' => $gymHall->courts()->active()->count(),
                    'supports_multi_booking' => $gymHall->supports_parallel_bookings
                ]
            ]
        ]);
    }

    /**
     * Update gym hall court settings.
     */
    public function updateCourtSettings(Request $request, GymHall $gymHall): JsonResponse
    {
        $this->authorize('update', $gymHall);

        $validated = $request->validate([
            'hall_type' => ['sometimes', Rule::in(['single', 'double', 'triple', 'multi'])],
            'court_count' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'supports_parallel_bookings' => ['sometimes', 'boolean'],
            'min_booking_duration_minutes' => ['sometimes', 'integer', 'min:15', 'max:480'],
            'booking_increment_minutes' => ['sometimes', 'integer', 'in:15,30,60']
        ]);

        $gymHall->update($validated);

        // If hall type changed and no courts exist, initialize default courts
        if (isset($validated['hall_type']) && $gymHall->courts()->count() === 0) {
            $gymHall->initializeDefaultCourts();
        }

        return response()->json([
            'success' => true,
            'message' => 'Court-Einstellungen erfolgreich aktualisiert.',
            'data' => [
                'id' => $gymHall->id,
                'hall_type' => $gymHall->hall_type,
                'court_count' => $gymHall->court_count,
                'supports_parallel_bookings' => $gymHall->supports_parallel_bookings,
                'min_booking_duration_minutes' => $gymHall->min_booking_duration_minutes,
                'booking_increment_minutes' => $gymHall->booking_increment_minutes
            ]
        ]);
    }

    /**
     * Get all courts for a gym hall.
     */
    public function getCourts(GymHall $gymHall): JsonResponse
    {
        try {
            $this->authorize('view', $gymHall);

            $courts = $gymHall->courts()
                ->active()
                ->orderedByNumber()
                ->get()
                ->map(function ($court) {
                    return [
                        'id' => $court->id,
                        'uuid' => $court->uuid,
                        'name' => $court->full_name,
                        'court_number' => $court->court_number,
                        'is_active' => $court->is_active,
                        'hourly_rate' => $court->effective_hourly_rate,
                        'notes' => $court->notes,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'courts' => $courts,
                    'total' => $courts->count()
                ]
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Keine Berechtigung für diese Halle.'
            ], 403);
        } catch (\Exception $e) {
            \Log::error('Error getting courts: ' . $e->getMessage(), [
                'gym_hall_id' => $gymHall->id ?? 'unknown',
                'user_id' => auth()->id(),
                'exception' => $e
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Laden der Plätze.'
            ], 500);
        }
    }

    /**
     * Get time grid for a gym hall.
     */
    public function getTimeGrid(Request $request, GymHall $gymHall): JsonResponse
    {
        try {
            $this->authorize('view', $gymHall);

            $request->validate([
                'date' => 'required|date',
                'slot_duration' => 'sometimes|integer|min:15|max:120',
            ]);

            $date = Carbon::parse($request->date);
            $slotDuration = $request->get('slot_duration', 30);

            // Use the comprehensive time grid generation from the model
            $timeGrid = $gymHall->generateTimeGrid($date, $slotDuration);
            
            if (empty($timeGrid)) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'date' => $date->format('Y-m-d'),
                        'day_of_week' => strtolower($date->format('l')),
                        'time_slots' => [],
                        'message' => 'Keine Öffnungszeiten für diesen Tag konfiguriert.'
                    ]
                ]);
            }

            // Add is_past flag to each slot
            $timeGrid = array_map(function ($slot) use ($date) {
                $slotDateTime = Carbon::createFromFormat('Y-m-d H:i', $date->format('Y-m-d') . ' ' . $slot['start_time']);
                $slot['is_past'] = $slotDateTime->lt(now());
                return $slot;
            }, $timeGrid);

            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $date->format('Y-m-d'),
                    'day_of_week' => strtolower($date->format('l')),
                    'slot_duration' => $slotDuration,
                    'time_slots' => $timeGrid,
                    'hall' => [
                        'id' => $gymHall->id,
                        'name' => $gymHall->name,
                        'court_count' => $gymHall->court_count,
                        'supports_parallel_bookings' => $gymHall->supports_parallel_bookings,
                    ]
                ]
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Keine Berechtigung für diese Halle.'
            ], 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ungültige Eingabedaten.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error getting time grid: ' . $e->getMessage(), [
                'gym_hall_id' => $gymHall->id ?? 'unknown',
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
                'exception' => $e
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Laden des Zeitrasters.'
            ], 500);
        }
    }
}