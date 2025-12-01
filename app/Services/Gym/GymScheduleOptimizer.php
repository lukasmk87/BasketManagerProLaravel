<?php

namespace App\Services\Gym;

use App\Models\GymBooking;
use App\Models\GymHall;
use App\Models\GymTimeSlot;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service für Slot-Optimierung und Buchungs-Generierung.
 *
 * Verantwortlichkeiten:
 * - TimeSlot-Erstellung und Team-Zuweisung
 * - Verfügbare Slots finden
 * - Multi-Court Buchungen erstellen
 * - Optimale Court-Zuweisungen berechnen
 * - Automatische Buchungsgenerierung
 * - Segment-Management und Zeitraster-Generierung (extracted from GymTimeSlot)
 * - Booking-Erstellung für Zeitfenster (extracted from GymTimeSlot)
 */
class GymScheduleOptimizer
{
    public function __construct(
        private GymConflictDetector $conflictDetector
    ) {}

    /**
     * Create a new time slot for a gym hall.
     */
    public function createTimeSlot(GymHall $gymHall, array $data): GymTimeSlot
    {
        return DB::transaction(function () use ($gymHall, $data) {
            $timeSlot = $gymHall->timeSlots()->create(array_merge($data, [
                'gym_hall_id' => $gymHall->id,
            ]));

            // Generate bookings for the next period if it's recurring and has a team
            if ($timeSlot->is_recurring && $timeSlot->team_id) {
                $this->generateBookingsForTimeSlot($timeSlot, now(), now()->addMonths(3));
            }

            return $timeSlot;
        });
    }

    /**
     * Assign a time slot to a team.
     */
    public function assignTimeSlotToTeam(
        GymTimeSlot $timeSlot,
        Team $team,
        User $assignedBy,
        ?string $reason = null
    ): bool {
        return DB::transaction(function () use ($timeSlot, $team, $assignedBy, $reason) {
            $timeSlot->assignToTeam($team, $assignedBy, $reason);

            // Generate bookings for the next period if recurring
            if ($timeSlot->is_recurring) {
                $this->generateBookingsForTimeSlot($timeSlot, now(), now()->addMonths(3));
            }

            return true;
        });
    }

    /**
     * Generate bookings for a recurring time slot.
     */
    public function generateBookingsForTimeSlot(GymTimeSlot $timeSlot, Carbon $startDate, Carbon $endDate): int
    {
        if (! $timeSlot->is_recurring || ! $timeSlot->team_id) {
            return 0;
        }

        return $timeSlot->generateBookingsForPeriod($startDate, $endDate);
    }

    /**
     * Generate a time grid for a gym hall with 30-minute slots.
     *
     * @return array<int, array<string, mixed>>
     */
    public function generateDailyTimeGrid(GymHall $gymHall, Carbon $date, int $slotDuration = 30): array
    {
        return $gymHall->generateTimeGrid($date, $slotDuration);
    }

    /**
     * Find available slots for multiple teams (parallel booking).
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAvailableSlots(
        GymHall $gymHall,
        Carbon $date,
        int $duration = 30,
        int $teamCount = 1
    ): array {
        if (! $gymHall->supports_parallel_bookings && $teamCount > 1) {
            return [];
        }

        $dayOfWeek = strtolower($date->format('l'));

        // Get operating hours for the day
        $operatingHours = $gymHall->operating_hours[$dayOfWeek] ?? null;

        if (! $operatingHours || ! $operatingHours['is_open']) {
            return [];
        }

        $startTime = Carbon::createFromTimeString($operatingHours['open_time']);
        $endTime = Carbon::createFromTimeString($operatingHours['close_time']);
        $increment = $gymHall->booking_increment ?: 30;

        $availableSlots = [];
        $current = $startTime->copy();

        while ($current->copy()->addMinutes($duration)->lte($endTime)) {
            $slotStart = $current->copy();
            $slotDateTime = $date->copy()->setTimeFrom($slotStart);

            // Check if we can accommodate the required number of teams
            if ($gymHall->canAccommodateTeams($teamCount, $slotDateTime, $duration)) {
                $availableCourts = $gymHall->getAvailableCourtsByTime($slotDateTime, $duration);

                $availableSlots[] = [
                    'start_time' => $slotStart->format('H:i'),
                    'end_time' => $slotStart->copy()->addMinutes($duration)->format('H:i'),
                    'duration_minutes' => $duration,
                    'datetime' => $slotDateTime,
                    'available_courts' => $availableCourts->count(),
                    'court_details' => $availableCourts->map(function ($court) {
                        return [
                            'id' => $court->id,
                            'identifier' => $court->court_identifier,
                            'name' => $court->court_name,
                            'color' => $court->color_code,
                        ];
                    })->toArray(),
                    'can_accommodate_teams' => min($teamCount, $availableCourts->count()),
                ];
            }

            $current->addMinutes($increment);
        }

        return $availableSlots;
    }

    /**
     * Create a multi-court booking with conflict checking.
     */
    public function createMultiCourtBooking(array $bookingData): GymBooking
    {
        return DB::transaction(function () use ($bookingData) {
            // Validate required data
            $requiredFields = ['gym_time_slot_id', 'team_id', 'booked_by_user_id', 'booking_date', 'start_time', 'duration_minutes'];
            foreach ($requiredFields as $field) {
                if (! isset($bookingData[$field])) {
                    throw new \InvalidArgumentException("Feld '{$field}' ist erforderlich.");
                }
            }

            $timeSlot = GymTimeSlot::findOrFail($bookingData['gym_time_slot_id']);
            $team = Team::findOrFail($bookingData['team_id']);
            if (! isset($bookingData['booked_by_user_id'])) {
                throw new \InvalidArgumentException('booked_by_user_id ist erforderlich.');
            }
            $bookedBy = User::findOrFail($bookingData['booked_by_user_id']);
            $date = Carbon::parse($bookingData['booking_date']);
            $startTime = $bookingData['start_time'];
            $duration = (int) $bookingData['duration_minutes'];
            $courtIds = $bookingData['court_ids'] ?? [];

            // Validate booking time using ConflictDetector
            $errors = $this->conflictDetector->validateFlexibleBookingTime($timeSlot, $date, $startTime, $duration);
            if (! empty($errors)) {
                throw new \InvalidArgumentException('Buchungsfehler: '.implode(', ', $errors));
            }

            // Validate courts if specified using ConflictDetector
            if (! empty($courtIds)) {
                $validationErrors = $this->conflictDetector->validateCourtSelection(
                    $timeSlot->gymHall,
                    $courtIds,
                    $date,
                    $startTime,
                    $duration
                );
                if (! empty($validationErrors)) {
                    throw new \InvalidArgumentException('Court-Fehler: '.implode(', ', $validationErrors));
                }
            }

            // Create the booking using the enhanced method
            $booking = $timeSlot->createFlexibleBookingForDate($date, $team, $bookedBy, $startTime, $duration, $courtIds);

            return $booking;
        });
    }

    /**
     * Get comprehensive court schedule for a date range.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCourtSchedule(GymHall $gymHall, Carbon $startDate, Carbon $endDate): array
    {
        $schedule = [];
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $daySchedule = [
                'date' => $current->toDateString(),
                'day_name' => $current->format('l'),
                'is_open' => false,
                'time_grid' => [],
                'courts' => [],
            ];

            $dayOfWeek = strtolower($current->format('l'));
            $operatingHours = $gymHall->operating_hours[$dayOfWeek] ?? null;

            if ($operatingHours && $operatingHours['is_open']) {
                $daySchedule['is_open'] = true;
                $daySchedule['operating_hours'] = $operatingHours;
                $daySchedule['time_grid'] = $this->generateDailyTimeGrid($gymHall, $current);

                // Get court-specific bookings
                $courts = $gymHall->activeCourts()->orderBy('sort_order')->get();
                foreach ($courts as $court) {
                    $courtBookings = $court->getBookingsForDate($current);
                    $daySchedule['courts'][] = [
                        'id' => $court->id,
                        'identifier' => $court->court_identifier,
                        'name' => $court->court_name,
                        'color' => $court->color_code,
                        'bookings' => $courtBookings->map(function ($booking) {
                            return [
                                'id' => $booking->id,
                                'start_time' => $booking->start_time->format('H:i'),
                                'end_time' => $booking->end_time->format('H:i'),
                                'team_name' => $booking->team->name ?? 'Unbekannt',
                                'status' => $booking->status,
                                'booking_type' => $booking->booking_type,
                            ];
                        })->toArray(),
                    ];
                }
            }

            $schedule[] = $daySchedule;
            $current->addDay();
        }

        return $schedule;
    }

    /**
     * Get optimal court assignments for team preferences.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getOptimalCourtAssignments(GymHall $gymHall, Carbon $date, int $duration = 30): array
    {
        $assignments = [];

        // Get all time slots for this hall
        $dayOfWeek = strtolower($date->format('l'));
        $timeSlots = $gymHall->timeSlots()
            ->where('day_of_week', $dayOfWeek)
            ->where('status', 'active')
            ->where(function ($query) use ($date) {
                $query->where('valid_from', '<=', $date)
                    ->where(function ($q) use ($date) {
                        $q->whereNull('valid_until')
                            ->orWhere('valid_until', '>=', $date);
                    });
            })
            ->with('team')
            ->get();

        foreach ($timeSlots as $timeSlot) {
            if (! $timeSlot->team_id) {
                continue;
            }

            // Get available segments for this time slot
            $segments = $timeSlot->getAvailableSegments($date);

            foreach ($segments as $segment) {
                if (! $segment['is_available']) {
                    continue;
                }

                $segmentDateTime = $date->copy()->setTimeFromTimeString($segment['start_time']);
                $optimalCourts = $gymHall->findOptimalCourtsForTeams(1, $segmentDateTime, $duration);

                if ($optimalCourts->isNotEmpty()) {
                    $assignments[] = [
                        'time_slot_id' => $timeSlot->id,
                        'team_id' => $timeSlot->team_id,
                        'team_name' => $timeSlot->team->name ?? 'Unbekannt',
                        'segment' => $segment,
                        'recommended_courts' => $optimalCourts->map(function ($court) {
                            return [
                                'id' => $court->id,
                                'identifier' => $court->court_identifier,
                                'name' => $court->court_name,
                            ];
                        })->toArray(),
                    ];
                }
            }
        }

        return $assignments;
    }

    /**
     * Generate automatic bookings for time slots with 30-min flexibility.
     */
    public function generateFlexibleBookings(GymTimeSlot $timeSlot, Carbon $startDate, Carbon $endDate): int
    {
        if (! $timeSlot->is_recurring || ! $timeSlot->team_id || ! $timeSlot->supports_30_min_slots) {
            return 0;
        }

        $created = 0;
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            if (! $timeSlot->isAvailableForDate($current)) {
                $current->addDay();

                continue;
            }

            // Get available segments for this day
            $segments = $timeSlot->getAvailableSegments($current);

            // Find the best segment (prefer earlier times)
            $bestSegment = collect($segments)->first(function ($segment) {
                return $segment['is_available'];
            });

            if ($bestSegment && ! $timeSlot->hasBookingForDate($current)) {
                try {
                    $booking = $this->createMultiCourtBooking([
                        'gym_time_slot_id' => $timeSlot->id,
                        'team_id' => $timeSlot->team_id,
                        'booked_by_user_id' => $timeSlot->assigned_by ?: 1, // Fallback to system user
                        'booking_date' => $current->toDateString(),
                        'start_time' => $bestSegment['start_time'],
                        'duration_minutes' => $bestSegment['duration_minutes'],
                        'court_ids' => $timeSlot->hasPreferredCourts()
                            ? array_slice($timeSlot->getPreferredCourts(), 0, 1)
                            : [],
                    ]);

                    $created++;
                } catch (\Exception $e) {
                    // Log error but continue with next date
                    Log::warning("Failed to create flexible booking for time slot {$timeSlot->id} on {$current->toDateString()}: ".$e->getMessage());
                }
            }

            $current->addDay();
        }

        return $created;
    }

    // ============================
    // SEGMENT MANAGEMENT METHODS (extracted from GymTimeSlot model)
    // ============================

    /**
     * Get available time segments for a specific date.
     *
     * @return array<int, array{start_time: string, end_time: string, duration_minutes: int, segment_id: string, is_available: bool}>
     */
    public function getAvailableSegmentsForTimeSlot(GymTimeSlot $timeSlot, Carbon $date): array
    {
        $dayOfWeek = strtolower($date->format('l'));

        // Get times for this specific day (either custom or default)
        $times = $timeSlot->getTimesForDay($dayOfWeek);

        if (! $times || ! $times['start_time'] || ! $times['end_time']) {
            return [];
        }

        $startTime = Carbon::createFromTimeString($times['start_time']);
        $endTime = Carbon::createFromTimeString($times['end_time']);
        $increment = $timeSlot->booking_increment_minutes ?: 30;

        $segments = [];
        $current = $startTime->copy();

        while ($current->copy()->addMinutes($increment)->lte($endTime)) {
            $segmentStart = $current->copy();
            $segmentEnd = $current->copy()->addMinutes($increment);

            $segments[] = [
                'start_time' => $segmentStart->format('H:i'),
                'end_time' => $segmentEnd->format('H:i'),
                'duration_minutes' => $increment,
                'segment_id' => $segmentStart->format('Hi').'-'.$segmentEnd->format('Hi'),
                'is_available' => $this->isSegmentAvailable($timeSlot, $date, $segmentStart->format('H:i'), $increment),
            ];

            $current->addMinutes($increment);
        }

        return $segments;
    }

    /**
     * Get available segments for a specific day with team assignments.
     *
     * @return array<int, array{start_time: string, end_time: string, duration_minutes: int, segment_id: string, is_available: bool, assigned_teams: array}>
     */
    public function getAvailableSegmentsForDay(GymTimeSlot $timeSlot, string $dayOfWeek, int $incrementMinutes = 30): array
    {
        $times = $timeSlot->getTimesForDay($dayOfWeek);

        if (! $times || ! $times['start_time'] || ! $times['end_time']) {
            return [];
        }

        $startTime = Carbon::createFromTimeString($times['start_time']);
        $endTime = Carbon::createFromTimeString($times['end_time']);

        $segments = [];
        $current = $startTime->copy();

        while ($current->copy()->addMinutes($incrementMinutes)->lte($endTime)) {
            $segmentStart = $current->copy();
            $segmentEnd = $current->copy()->addMinutes($incrementMinutes);

            $assignedTeams = $this->getTeamsAssignedToSegment(
                $timeSlot,
                $dayOfWeek,
                $segmentStart->format('H:i'),
                $segmentEnd->format('H:i')
            );

            $segments[] = [
                'start_time' => $segmentStart->format('H:i'),
                'end_time' => $segmentEnd->format('H:i'),
                'duration_minutes' => $incrementMinutes,
                'segment_id' => $segmentStart->format('Hi').'-'.$segmentEnd->format('Hi'),
                'is_available' => empty($assignedTeams),
                'assigned_teams' => $assignedTeams,
            ];

            $current->addMinutes($incrementMinutes);
        }

        return $segments;
    }

    /**
     * Get time grid for a day from time slot.
     *
     * @return array<int, array{start_time: string, end_time: string, duration_minutes: int, time_key: string}>
     */
    public function getTimeGridForTimeSlot(GymTimeSlot $timeSlot, string $dayOfWeek): array
    {
        $times = $timeSlot->getTimesForDay($dayOfWeek);

        if (! $times || ! $times['start_time'] || ! $times['end_time']) {
            return [];
        }

        $startTime = Carbon::createFromTimeString($times['start_time']);
        $endTime = Carbon::createFromTimeString($times['end_time']);
        $increment = $timeSlot->booking_increment_minutes ?: 30;

        $grid = [];
        $current = $startTime->copy();

        while ($current->copy()->addMinutes($increment)->lte($endTime)) {
            $grid[] = [
                'start_time' => $current->format('H:i'),
                'end_time' => $current->copy()->addMinutes($increment)->format('H:i'),
                'duration_minutes' => $increment,
                'time_key' => $current->format('Hi'),
            ];

            $current->addMinutes($increment);
        }

        return $grid;
    }

    /**
     * Get teams assigned to a specific segment.
     *
     * @return array<int, array{id: int, team_id: int, team_name: string, start_time: string, end_time: string}>
     */
    public function getTeamsAssignedToSegment(
        GymTimeSlot $timeSlot,
        string $dayOfWeek,
        string $startTime,
        string $endTime
    ): array {
        return $timeSlot->activeTeamAssignments()
            ->where('day_of_week', $dayOfWeek)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->with(['team'])
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'team_id' => $assignment->team_id,
                    'team_name' => $assignment->team->name,
                    'start_time' => $assignment->start_time->format('H:i'),
                    'end_time' => $assignment->end_time->format('H:i'),
                ];
            })
            ->toArray();
    }

    /**
     * Check if a segment is available (no conflicting bookings).
     */
    private function isSegmentAvailable(GymTimeSlot $timeSlot, Carbon $date, string $startTime, int $duration): bool
    {
        $dateTimeString = $date->toDateString().' '.$startTime;
        $segmentDateTime = Carbon::createFromFormat('Y-m-d H:i', $dateTimeString);

        // Check if segment conflicts with existing bookings
        $conflictingBookings = $timeSlot->bookings()
            ->whereDate('booking_date', $date)
            ->where(function ($query) use ($segmentDateTime, $duration) {
                $segmentEnd = $segmentDateTime->copy()->addMinutes($duration);
                $query->where(function ($q) use ($segmentDateTime, $segmentEnd) {
                    $q->where('start_time', '<', $segmentEnd->format('H:i:s'))
                        ->where('end_time', '>', $segmentDateTime->format('H:i:s'));
                });
            })
            ->whereIn('status', ['reserved', 'confirmed'])
            ->exists();

        return ! $conflictingBookings;
    }

    // ============================
    // BOOKING CREATION METHODS (extracted from GymTimeSlot model)
    // ============================

    /**
     * Create a booking for a specific date.
     */
    public function createBookingForTimeSlot(GymTimeSlot $timeSlot, Carbon $date, Team $team, User $bookedBy): GymBooking
    {
        return $timeSlot->bookings()->create([
            'uuid' => Str::uuid(),
            'team_id' => $team->id,
            'booked_by_user_id' => $bookedBy->id,
            'booking_date' => $date,
            'start_time' => $timeSlot->start_time,
            'end_time' => $timeSlot->end_time,
            'duration_minutes' => $timeSlot->duration_minutes,
            'status' => 'reserved',
            'booking_type' => 'regular',
        ]);
    }

    /**
     * Create a flexible booking with custom times and optional court selection.
     *
     * @param  array<int>  $courtIds
     */
    public function createFlexibleBooking(
        GymTimeSlot $timeSlot,
        Carbon $date,
        Team $team,
        User $bookedBy,
        string $startTime,
        int $durationMinutes,
        array $courtIds = []
    ): GymBooking {
        $endTime = Carbon::createFromTimeString($startTime)->addMinutes($durationMinutes);

        $bookingData = [
            'uuid' => Str::uuid(),
            'team_id' => $team->id,
            'booked_by_user_id' => $bookedBy->id,
            'booking_date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime->format('H:i'),
            'duration_minutes' => $durationMinutes,
            'status' => 'reserved',
            'booking_type' => 'regular',
            'court_ids' => $courtIds,
            'is_partial_court' => count($courtIds) < $timeSlot->gymHall->court_count,
        ];

        $booking = $timeSlot->bookings()->create($bookingData);

        // Attach courts if specified
        if (! empty($courtIds)) {
            $booking->courts()->attach($courtIds);
        }

        return $booking;
    }

    /**
     * Generate recurring bookings for a time slot.
     */
    public function generateRecurringBookingsForPeriod(
        GymTimeSlot $timeSlot,
        Carbon $startDate,
        Carbon $endDate
    ): int {
        if (! $timeSlot->is_recurring || ! $timeSlot->team_id) {
            return 0;
        }

        $created = 0;
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            if ($timeSlot->isAvailableForDate($current) && ! $timeSlot->hasBookingForDate($current)) {
                $this->createBookingForTimeSlot($current, $timeSlot->team, $timeSlot->assignedByUser);
                $created++;
            }

            switch ($timeSlot->recurrence_type) {
                case 'weekly':
                    $current->addWeek();
                    break;
                case 'biweekly':
                    $current->addWeeks(2);
                    break;
                case 'monthly':
                    $current->addMonth();
                    break;
                default:
                    break 2; // Exit the while loop
            }
        }

        return $created;
    }
}
