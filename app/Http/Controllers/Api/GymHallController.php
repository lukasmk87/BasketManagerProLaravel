<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GymHall;
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

        $gymHall = GymHall::create($request->all());

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
        $this->authorize('view', $gymHall);

        $gymHall->load([
            'club',
            'timeSlots' => function ($query) {
                $query->active()->with('team:id,name,short_name');
            }
        ]);

        // Add additional computed data
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        $additionalData = [
            'weekly_schedule' => $gymHall->getWeeklySchedule($startOfWeek),
            'utilization_rate' => $gymHall->getUtilizationRate($startOfWeek, $endOfWeek),
            'is_open_now' => $gymHall->is_open,
            'todays_schedule' => $gymHall->getTodaysSchedule(),
        ];

        return response()->json([
            'success' => true,
            'data' => array_merge($gymHall->toArray(), $additionalData)
        ]);
    }

    /**
     * Update the specified gym hall.
     */
    public function update(Request $request, GymHall $gymHall): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
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
        
        $activeBookings = \App\Models\GymBooking::whereHas('gymHall', function ($query) use ($clubId) {
                $query->where('club_id', $clubId);
            })
            ->where('booking_date', '>=', now()->toDateString())
            ->where('status', 'confirmed')
            ->count();
        
        $pendingRequests = \App\Models\GymBookingRequest::whereHas('gymHall', function ($query) use ($clubId) {
                $query->where('club_id', $clubId);
            })
            ->where('status', 'pending')
            ->count();

        // Calculate utilization rate
        $utilizationRate = 0;
        if ($totalHalls > 0) {
            $totalPossibleSlots = $totalHalls * 7 * 12; // 7 days, 12 possible time slots per day
            $bookedSlots = \App\Models\GymBooking::whereHas('gymHall', function ($query) use ($clubId) {
                    $query->where('club_id', $clubId);
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

        $bookings = \App\Models\GymBooking::whereHas('gymHall', function ($query) use ($clubId) {
                $query->where('club_id', $clubId);
            })
            ->whereBetween('booking_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->with(['gymHall:id,name', 'team:id,name'])
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

        $requests = \App\Models\GymBookingRequest::whereHas('gymHall', function ($query) use ($clubId) {
                $query->where('club_id', $clubId);
            })
            ->where('status', 'pending')
            ->with(['team:id,name', 'gymHall:id,name', 'requestedBy:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }
}