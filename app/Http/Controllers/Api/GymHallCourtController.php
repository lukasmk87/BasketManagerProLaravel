<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GymHall;
use App\Models\GymHallCourt;
use App\Services\GymScheduleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class GymHallCourtController extends Controller
{
    protected GymScheduleService $gymScheduleService;

    public function __construct(GymScheduleService $gymScheduleService)
    {
        $this->gymScheduleService = $gymScheduleService;
    }

    /**
     * Display a listing of courts for a gym hall.
     */
    public function index(GymHall $gymHall): JsonResponse
    {
        $courts = $gymHall->courts()
            ->orderBy('sort_order')
            ->orderBy('court_identifier')
            ->get()
            ->map(function ($court) {
                return [
                    'id' => $court->id,
                    'uuid' => $court->uuid,
                    'court_identifier' => $court->court_identifier,
                    'court_name' => $court->court_name,
                    'court_type' => $court->court_type,
                    'max_capacity' => $court->max_capacity,
                    'equipment' => $court->equipment,
                    'color_code' => $court->color_code,
                    'dimensions' => $court->dimensions,
                    'area' => $court->area,
                    'is_active' => $court->is_active,
                    'description' => $court->description,
                    'sort_order' => $court->sort_order
                ];
            });

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
                'courts' => $courts
            ]
        ]);
    }

    /**
     * Store a newly created court for a gym hall.
     */
    public function store(Request $request, GymHall $gymHall): JsonResponse
    {
        $validated = $request->validate([
            'court_identifier' => ['required', 'string', 'max:10', Rule::unique('gym_hall_courts')->where('gym_hall_id', $gymHall->id)],
            'court_name' => ['required', 'string', 'max:255'],
            'court_type' => ['required', Rule::in(['full', 'half', 'third'])],
            'max_capacity' => ['nullable', 'integer', 'min:1'],
            'equipment' => ['nullable', 'array'],
            'equipment.*' => ['string', 'max:255'],
            'color_code' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'width_meters' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'length_meters' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0']
        ]);

        // Set default sort order if not provided
        if (!isset($validated['sort_order'])) {
            $validated['sort_order'] = $gymHall->courts()->max('sort_order') + 1;
        }

        // Set default color if not provided
        if (!isset($validated['color_code'])) {
            $colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#F97316'];
            $validated['color_code'] = $colors[$validated['sort_order'] % count($colors)];
        }

        $court = $gymHall->courts()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Court erfolgreich erstellt.',
            'data' => [
                'id' => $court->id,
                'uuid' => $court->uuid,
                'court_identifier' => $court->court_identifier,
                'court_name' => $court->court_name,
                'court_type' => $court->court_type,
                'max_capacity' => $court->max_capacity,
                'equipment' => $court->equipment,
                'color_code' => $court->color_code,
                'dimensions' => $court->dimensions,
                'is_active' => $court->is_active,
                'sort_order' => $court->sort_order
            ]
        ], 201);
    }

    /**
     * Display the specified court.
     */
    public function show(GymHall $gymHall, GymHallCourt $court): JsonResponse
    {
        // Ensure court belongs to the gym hall
        if ($court->gym_hall_id !== $gymHall->id) {
            return response()->json([
                'success' => false,
                'message' => 'Court gehört nicht zu dieser Halle.'
            ], 404);
        }

        $courtData = [
            'id' => $court->id,
            'uuid' => $court->uuid,
            'court_identifier' => $court->court_identifier,
            'court_name' => $court->court_name,
            'court_type' => $court->court_type,
            'max_capacity' => $court->max_capacity,
            'equipment' => $court->equipment,
            'color_code' => $court->color_code,
            'width_meters' => $court->width_meters,
            'length_meters' => $court->length_meters,
            'dimensions' => $court->dimensions,
            'area' => $court->area,
            'is_active' => $court->is_active,
            'description' => $court->description,
            'sort_order' => $court->sort_order,
            'created_at' => $court->created_at,
            'updated_at' => $court->updated_at
        ];

        return response()->json([
            'success' => true,
            'data' => $courtData
        ]);
    }

    /**
     * Update the specified court.
     */
    public function update(Request $request, GymHall $gymHall, GymHallCourt $court): JsonResponse
    {
        // Ensure court belongs to the gym hall
        if ($court->gym_hall_id !== $gymHall->id) {
            return response()->json([
                'success' => false,
                'message' => 'Court gehört nicht zu dieser Halle.'
            ], 404);
        }

        $validated = $request->validate([
            'court_identifier' => [
                'sometimes',
                'required', 
                'string', 
                'max:10', 
                Rule::unique('gym_hall_courts')->where('gym_hall_id', $gymHall->id)->ignore($court->id)
            ],
            'court_name' => ['sometimes', 'required', 'string', 'max:255'],
            'court_type' => ['sometimes', 'required', Rule::in(['full', 'half', 'third'])],
            'max_capacity' => ['nullable', 'integer', 'min:1'],
            'equipment' => ['nullable', 'array'],
            'equipment.*' => ['string', 'max:255'],
            'color_code' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'width_meters' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'length_meters' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0']
        ]);

        $court->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Court erfolgreich aktualisiert.',
            'data' => [
                'id' => $court->id,
                'court_identifier' => $court->court_identifier,
                'court_name' => $court->court_name,
                'court_type' => $court->court_type,
                'max_capacity' => $court->max_capacity,
                'equipment' => $court->equipment,
                'color_code' => $court->color_code,
                'dimensions' => $court->dimensions,
                'is_active' => $court->is_active,
                'sort_order' => $court->sort_order
            ]
        ]);
    }

    /**
     * Remove the specified court.
     */
    public function destroy(GymHall $gymHall, GymHallCourt $court): JsonResponse
    {
        // Ensure court belongs to the gym hall
        if ($court->gym_hall_id !== $gymHall->id) {
            return response()->json([
                'success' => false,
                'message' => 'Court gehört nicht zu dieser Halle.'
            ], 404);
        }

        // Check if court has active bookings
        $hasActiveBookings = $court->bookings()
            ->whereIn('status', ['reserved', 'confirmed'])
            ->where('booking_date', '>=', now()->toDateString())
            ->exists();

        if ($hasActiveBookings) {
            return response()->json([
                'success' => false,
                'message' => 'Court kann nicht gelöscht werden - es existieren noch aktive Buchungen.'
            ], 400);
        }

        $court->delete();

        return response()->json([
            'success' => true,
            'message' => 'Court erfolgreich gelöscht.'
        ]);
    }

    /**
     * Get court availability for a specific date and time range.
     */
    public function availability(Request $request, GymHall $gymHall, GymHallCourt $court): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'duration' => ['nullable', 'integer', 'min:30', 'max:480'], // Max 8 hours
        ]);

        $date = Carbon::parse($validated['date']);
        $duration = $validated['duration'] ?? 30;

        $availability = $court->getAvailableTimeSlots($date, $duration);

        return response()->json([
            'success' => true,
            'data' => [
                'court' => [
                    'id' => $court->id,
                    'identifier' => $court->court_identifier,
                    'name' => $court->court_name
                ],
                'date' => $date->toDateString(),
                'duration_minutes' => $duration,
                'available_slots' => $availability,
                'total_slots' => count($availability)
            ]
        ]);
    }

    /**
     * Get court schedule for a date range.
     */
    public function schedule(Request $request, GymHall $gymHall): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'include_courts' => ['boolean']
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $includeCourts = $validated['include_courts'] ?? true;

        if ($startDate->diffInDays($endDate) > 30) {
            return response()->json([
                'success' => false,
                'message' => 'Datumsbereich darf nicht größer als 30 Tage sein.'
            ], 400);
        }

        $schedule = $this->gymScheduleService->getCourtSchedule($gymHall, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => [
                'gym_hall' => [
                    'id' => $gymHall->id,
                    'name' => $gymHall->name,
                    'supports_parallel_bookings' => $gymHall->supports_parallel_bookings
                ],
                'date_range' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'days' => $startDate->diffInDays($endDate) + 1
                ],
                'schedule' => $schedule
            ]
        ]);
    }

    /**
     * Get time grid for a specific date.
     */
    public function timeGrid(Request $request, GymHall $gymHall): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'slot_duration' => ['nullable', 'integer', 'in:15,30,60'] // Allow 15, 30, or 60 minute slots
        ]);

        $date = Carbon::parse($validated['date']);
        $slotDuration = $validated['slot_duration'] ?? 30;

        $timeGrid = $this->gymScheduleService->generateDailyTimeGrid($gymHall, $date, $slotDuration);

        return response()->json([
            'success' => true,
            'data' => [
                'gym_hall' => [
                    'id' => $gymHall->id,
                    'name' => $gymHall->name,
                    'court_count' => $gymHall->court_count,
                    'supports_parallel_bookings' => $gymHall->supports_parallel_bookings
                ],
                'date' => $date->toDateString(),
                'day_name' => $date->format('l'),
                'slot_duration_minutes' => $slotDuration,
                'time_grid' => $timeGrid,
                'total_slots' => count($timeGrid)
            ]
        ]);
    }

    /**
     * Find available slots for multiple teams.
     */
    public function findAvailableSlots(Request $request, GymHall $gymHall): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'duration' => ['nullable', 'integer', 'min:30', 'max:480'], // Max 8 hours
            'team_count' => ['nullable', 'integer', 'min:1', 'max:10'] // Max 10 teams
        ]);

        $date = Carbon::parse($validated['date']);
        $duration = $validated['duration'] ?? 30;
        $teamCount = $validated['team_count'] ?? 1;

        $availableSlots = $this->gymScheduleService->findAvailableSlots($gymHall, $date, $duration, $teamCount);

        return response()->json([
            'success' => true,
            'data' => [
                'gym_hall' => [
                    'id' => $gymHall->id,
                    'name' => $gymHall->name,
                    'supports_parallel_bookings' => $gymHall->supports_parallel_bookings
                ],
                'search_criteria' => [
                    'date' => $date->toDateString(),
                    'duration_minutes' => $duration,
                    'team_count' => $teamCount
                ],
                'available_slots' => $availableSlots,
                'total_available' => count($availableSlots)
            ]
        ]);
    }
}
