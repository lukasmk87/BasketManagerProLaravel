<?php

namespace App\Services;

use App\Models\GymHall;
use App\Models\GymHallCourt;
use App\Models\GymTimeSlot;
use App\Models\GymBooking;
use App\Models\GymBookingRequest;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class GymScheduleService
{
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
    public function assignTimeSlotToTeam(GymTimeSlot $timeSlot, Team $team, User $assignedBy, string $reason = null): bool
    {
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
        if (!$timeSlot->is_recurring || !$timeSlot->team_id) {
            return 0;
        }

        return $timeSlot->generateBookingsForPeriod($startDate, $endDate);
    }

    /**
     * Release a booking by a trainer or assistant trainer.
     */
    public function releaseBooking(GymBooking $booking, User $releasedBy, string $reason = null): bool
    {
        if (!$this->canUserReleaseBooking($booking, $releasedBy)) {
            throw new \Exception('Sie haben keine Berechtigung, diese Buchung freizugeben.');
        }

        return DB::transaction(function () use ($booking, $releasedBy, $reason) {
            $success = $booking->releaseTime($releasedBy, $reason);
            
            if ($success) {
                // Send notifications to other teams
                $this->notifyTeamsAboutAvailableTime($booking);
            }

            return $success;
        });
    }

    /**
     * Request a released booking for a team.
     */
    public function requestBooking(GymBooking $booking, Team $requestingTeam, User $requestedBy, array $details = []): GymBookingRequest
    {
        if ($booking->status !== 'released') {
            throw new \Exception('Diese Buchung ist nicht verfügbar für Anfragen.');
        }

        if ($booking->original_team_id === $requestingTeam->id) {
            throw new \Exception('Sie können Ihre eigene freigegebene Zeit nicht anfragen.');
        }

        return DB::transaction(function () use ($booking, $requestingTeam, $requestedBy, $details) {
            $request = $booking->requestBooking(
                $requestingTeam, 
                $requestedBy, 
                $details['message'] ?? null, 
                $details
            );

            // Notify original team about the request
            $request->notifyOriginalTeam();

            return $request;
        });
    }

    /**
     * Approve a booking request.
     */
    public function approveBookingRequest(GymBookingRequest $request, User $reviewedBy, string $reviewNotes = null, array $conditions = []): bool
    {
        if (!$request->canBeApprovedBy($reviewedBy)) {
            throw new \Exception('Sie haben keine Berechtigung, diese Anfrage zu genehmigen.');
        }

        return DB::transaction(function () use ($request, $reviewedBy, $reviewNotes, $conditions) {
            return $request->approve($reviewedBy, $reviewNotes, $conditions);
        });
    }

    /**
     * Reject a booking request.
     */
    public function rejectBookingRequest(GymBookingRequest $request, User $reviewedBy, string $rejectionReason, string $reviewNotes = null): bool
    {
        if (!$request->canBeApprovedBy($reviewedBy)) {
            throw new \Exception('Sie haben keine Berechtigung, diese Anfrage abzulehnen.');
        }

        return DB::transaction(function () use ($request, $reviewedBy, $rejectionReason, $reviewNotes) {
            return $request->reject($reviewedBy, $rejectionReason, $reviewNotes);
        });
    }

    /**
     * Cancel a booking.
     */
    public function cancelBooking(GymBooking $booking, User $cancelledBy, string $reason = null): bool
    {
        if (!$this->canUserCancelBooking($booking, $cancelledBy)) {
            throw new \Exception('Sie haben keine Berechtigung, diese Buchung zu stornieren.');
        }

        return $booking->cancelBooking($cancelledBy, $reason);
    }

    /**
     * Get available time slots for a team to book.
     */
    public function getAvailableTimeSlotsForTeam(Team $team, Carbon $startDate, Carbon $endDate): Collection
    {
        $club = $team->club;
        
        return GymBooking::whereHas('gymTimeSlot.gymHall', function ($query) use ($club) {
                $query->where('club_id', $club->id);
            })
            ->where('status', 'released')
            ->where('original_team_id', '!=', $team->id) // Exclude own releases
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->with([
                'gymTimeSlot.gymHall:id,name,address_street,address_city',
                'originalTeam:id,name,short_name'
            ])
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Get pending booking requests for a team to review.
     */
    public function getPendingRequestsForTeam(Team $team): Collection
    {
        return GymBookingRequest::getPendingRequestsForReview($team);
    }

    /**
     * Get booking requests made by a team.
     */
    public function getRequestsByTeam(Team $team, string $status = null): Collection
    {
        return GymBookingRequest::getRequestsForTeam($team, $status);
    }

    /**
     * Get weekly schedule for multiple gym halls.
     */
    public function getClubWeeklySchedule(Club $club, Carbon $weekStart = null): array
    {
        if (!$weekStart) {
            $weekStart = now()->startOfWeek();
        }

        $gymHalls = $club->gymHalls()->active()->with(['timeSlots' => function ($query) {
            $query->active()->with('team:id,name,short_name');
        }])->get();

        $schedule = [];
        foreach ($gymHalls as $hall) {
            $schedule[$hall->id] = [
                'gym_hall' => $hall->only(['id', 'name', 'capacity']),
                'weekly_schedule' => $hall->getWeeklySchedule($weekStart)
            ];
        }

        return $schedule;
    }

    /**
     * Get utilization statistics for a club.
     */
    public function getClubUtilizationStats(Club $club, Carbon $startDate, Carbon $endDate): array
    {
        $gymHalls = $club->gymHalls()->active()->get();
        
        $stats = [];
        $totalUtilization = 0;
        $totalBookings = 0;

        foreach ($gymHalls as $hall) {
            $utilization = $hall->getUtilizationRate($startDate, $endDate);
            $bookings = $hall->bookings()
                ->whereBetween('booking_date', [$startDate, $endDate])
                ->whereIn('status', ['reserved', 'confirmed', 'completed'])
                ->count();

            $stats['halls'][$hall->id] = [
                'name' => $hall->name,
                'utilization_rate' => $utilization,
                'total_bookings' => $bookings,
            ];

            $totalUtilization += $utilization;
            $totalBookings += $bookings;
        }

        $stats['overview'] = [
            'total_halls' => $gymHalls->count(),
            'average_utilization' => $gymHalls->count() > 0 ? round($totalUtilization / $gymHalls->count(), 1) : 0,
            'total_bookings' => $totalBookings,
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ]
        ];

        return $stats;
    }

    /**
     * Process expired booking requests.
     */
    public function processExpiredRequests(): int
    {
        return GymBookingRequest::expireOldRequests();
    }

    /**
     * Mark past bookings as completed or no-show.
     */
    public function processPastBookings(): array
    {
        $results = ['completed' => 0, 'no_show' => 0];

        $pastBookings = GymBooking::past()
            ->whereIn('status', ['reserved', 'confirmed'])
            ->get();

        foreach ($pastBookings as $booking) {
            // For now, automatically mark as completed
            // In a real implementation, you might check attendance or have manual input
            $booking->markAsCompleted();
            $results['completed']++;
        }

        return $results;
    }

    /**
     * Check if user can release a booking.
     */
    public function canUserReleaseBooking(GymBooking $booking, User $user): bool
    {
        if (!$booking->can_be_released) {
            return false;
        }

        // Check if user is trainer or assistant trainer of the team
        return $booking->team->users()
            ->wherePivotIn('role', ['trainer', 'assistant_trainer'])
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Check if user can cancel a booking.
     */
    public function canUserCancelBooking(GymBooking $booking, User $user): bool
    {
        if (!$booking->can_be_cancelled) {
            return false;
        }

        // User can cancel if they are trainer/assistant of the team or if they booked it
        $isTeamTrainer = $booking->team->users()
            ->wherePivotIn('role', ['trainer', 'assistant_trainer'])
            ->where('user_id', $user->id)
            ->exists();

        return $isTeamTrainer || $booking->booked_by_user_id === $user->id;
    }

    /**
     * Get conflicts for a time slot.
     */
    public function getTimeSlotConflicts(GymTimeSlot $timeSlot): array
    {
        $conflicts = [];

        // Check for overlapping time slots in the same hall
        $overlappingSlots = GymTimeSlot::where('gym_hall_id', $timeSlot->gym_hall_id)
            ->where('id', '!=', $timeSlot->id)
            ->where('day_of_week', $timeSlot->day_of_week)
            ->where('status', 'active')
            ->where(function ($query) use ($timeSlot) {
                $query->where(function ($q) use ($timeSlot) {
                    $q->where('start_time', '<', $timeSlot->end_time)
                      ->where('end_time', '>', $timeSlot->start_time);
                });
            })
            ->with('team:id,name')
            ->get();

        foreach ($overlappingSlots as $slot) {
            $conflicts[] = [
                'type' => 'time_overlap',
                'message' => "Überschneidung mit {$slot->title} ({$slot->time_range})",
                'conflicting_slot' => $slot,
            ];
        }

        return $conflicts;
    }

    /**
     * Validate time slot data.
     */
    public function validateTimeSlot(array $data, GymHall $gymHall): array
    {
        $errors = [];

        // Check if hall is open during the requested time
        if (isset($data['start_time'], $data['end_time'])) {
            $startTime = Carbon::createFromTimeString($data['start_time']);
            $endTime = Carbon::createFromTimeString($data['end_time']);

            if ($gymHall->opening_time && $startTime->lt($gymHall->opening_time)) {
                $errors[] = "Die Startzeit liegt vor der Öffnungszeit der Halle.";
            }

            if ($gymHall->closing_time && $endTime->gt($gymHall->closing_time)) {
                $errors[] = "Die Endzeit liegt nach der Schließzeit der Halle.";
            }
        }

        return $errors;
    }

    /**
     * Send notifications to teams about available time.
     */
    protected function notifyTeamsAboutAvailableTime(GymBooking $booking): void
    {
        // This method would implement the actual notification logic
        // For now, it's handled by the GymBooking model's notifyAvailableTeams method
        // but could be expanded here with more complex notification rules
    }

    /**
     * Get booking statistics for a team.
     */
    public function getTeamBookingStats(Team $team, Carbon $startDate, Carbon $endDate): array
    {
        $bookings = GymBooking::forTeam($team->id)
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->get();

        return [
            'total_bookings' => $bookings->count(),
            'bookings_by_status' => $bookings->groupBy('status')->map->count(),
            'releases_made' => $bookings->where('status', 'released')->count(),
            'substitute_bookings' => $bookings->where('is_substitute_booking', true)->count(),
            'average_utilization' => $this->calculateTeamUtilization($team, $startDate, $endDate),
            'most_used_halls' => $bookings->groupBy('gymTimeSlot.gymHall.name')->map->count()->sortDesc()->take(3),
        ];
    }

    /**
     * Calculate team utilization rate.
     */
    protected function calculateTeamUtilization(Team $team, Carbon $startDate, Carbon $endDate): float
    {
        $totalSlots = GymTimeSlot::forTeam($team->id)->active()->count();
        if ($totalSlots === 0) {
            return 0.0;
        }

        $usedSlots = GymBooking::forTeam($team->id)
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->whereIn('status', ['reserved', 'confirmed', 'completed'])
            ->count();

        $weeksBetween = $startDate->diffInWeeks($endDate) + 1;
        $expectedBookings = $totalSlots * $weeksBetween;

        return $expectedBookings > 0 ? round(($usedSlots / $expectedBookings) * 100, 1) : 0.0;
    }

    // ============================
    // MULTI-COURT & 30-MIN GRID METHODS
    // ============================

    /**
     * Generate a time grid for a gym hall with 30-minute slots.
     */
    public function generateDailyTimeGrid(GymHall $gymHall, Carbon $date, int $slotDuration = 30): array
    {
        return $gymHall->generateTimeGrid($date, $slotDuration);
    }

    /**
     * Find available slots for multiple teams (parallel booking).
     */
    public function findAvailableSlots(GymHall $gymHall, Carbon $date, int $duration = 30, int $teamCount = 1): array
    {
        if (!$gymHall->supports_parallel_bookings && $teamCount > 1) {
            return [];
        }

        $dayOfWeek = strtolower($date->format('l'));
        
        // Get operating hours for the day
        $operatingHours = $gymHall->operating_hours[$dayOfWeek] ?? null;
        
        if (!$operatingHours || !$operatingHours['is_open']) {
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
                            'color' => $court->color_code
                        ];
                    })->toArray(),
                    'can_accommodate_teams' => min($teamCount, $availableCourts->count())
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
                if (!isset($bookingData[$field])) {
                    throw new \InvalidArgumentException("Feld '{$field}' ist erforderlich.");
                }
            }

            $timeSlot = GymTimeSlot::findOrFail($bookingData['gym_time_slot_id']);
            $team = Team::findOrFail($bookingData['team_id']);
            $bookedBy = User::findOrFail($bookingData['booked_by_user_id']);
            $date = Carbon::parse($bookingData['booking_date']);
            $startTime = $bookingData['start_time'];
            $duration = (int) $bookingData['duration_minutes'];
            $courtIds = $bookingData['court_ids'] ?? [];

            // Validate booking time
            $errors = $this->validateFlexibleBookingTime($timeSlot, $date, $startTime, $duration);
            if (!empty($errors)) {
                throw new \InvalidArgumentException('Buchungsfehler: ' . implode(', ', $errors));
            }

            // Validate courts if specified
            if (!empty($courtIds)) {
                $validationErrors = $this->validateCourtSelection($timeSlot->gymHall, $courtIds, $date, $startTime, $duration);
                if (!empty($validationErrors)) {
                    throw new \InvalidArgumentException('Court-Fehler: ' . implode(', ', $validationErrors));
                }
            }

            // Create the booking using the enhanced method
            $booking = $timeSlot->createFlexibleBookingForDate($date, $team, $bookedBy, $startTime, $duration, $courtIds);

            return $booking;
        });
    }

    /**
     * Validate flexible booking time against custom times and 30-min slots.
     */
    public function validateFlexibleBookingTime(GymTimeSlot $timeSlot, Carbon $date, string $startTime, int $duration): array
    {
        $errors = [];
        
        // Use time slot's validation method
        $timeSlotErrors = $timeSlot->canBookAtTime($date, $startTime, $duration) 
            ? [] 
            : ['Buchung zu dieser Zeit nicht möglich für dieses Zeitfenster.'];
        
        // Use gym hall's validation method
        $gymHallErrors = $timeSlot->gymHall->validateBookingTime(
            $date->copy()->setTimeFromTimeString($startTime),
            $duration
        );
        
        return array_merge($timeSlotErrors, $gymHallErrors);
    }

    /**
     * Validate court selection for booking.
     */
    public function validateCourtSelection(GymHall $gymHall, array $courtIds, Carbon $date, string $startTime, int $duration): array
    {
        $errors = [];
        
        // Check if courts exist and belong to this hall
        $validCourts = GymHallCourt::whereIn('id', $courtIds)
            ->where('gym_hall_id', $gymHall->id)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        if (count($validCourts) !== count($courtIds)) {
            $errors[] = 'Einige der ausgewählten Courts sind ungültig oder nicht verfügbar.';
            return $errors;
        }

        // Check court availability
        $dateTime = $date->copy()->setTimeFromTimeString($startTime);
        foreach ($courtIds as $courtId) {
            $court = GymHallCourt::find($courtId);
            if (!$court->isAvailableAt($dateTime, $duration)) {
                $errors[] = "Court {$court->court_identifier} ist zu dieser Zeit nicht verfügbar.";
            }
        }

        return $errors;
    }

    /**
     * Get comprehensive court schedule for a date range.
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
                'courts' => []
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
                                'booking_type' => $booking->booking_type
                            ];
                        })->toArray()
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
            if (!$timeSlot->team_id) continue;

            // Get available segments for this time slot
            $segments = $timeSlot->getAvailableSegments($date);
            
            foreach ($segments as $segment) {
                if (!$segment['is_available']) continue;

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
                                'name' => $court->court_name
                            ];
                        })->toArray()
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
        if (!$timeSlot->is_recurring || !$timeSlot->team_id || !$timeSlot->supports_30_min_slots) {
            return 0;
        }

        $created = 0;
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            if (!$timeSlot->isAvailableForDate($current)) {
                $current->addDay();
                continue;
            }

            // Get available segments for this day
            $segments = $timeSlot->getAvailableSegments($current);
            
            // Find the best segment (prefer earlier times)
            $bestSegment = collect($segments)->first(function ($segment) {
                return $segment['is_available'];
            });

            if ($bestSegment && !$timeSlot->hasBookingForDate($current)) {
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
                            : []
                    ]);
                    
                    $created++;
                } catch (\Exception $e) {
                    // Log error but continue with next date
                    \Log::warning("Failed to create flexible booking for time slot {$timeSlot->id} on {$current->toDateString()}: " . $e->getMessage());
                }
            }

            $current->addDay();
        }

        return $created;
    }
}