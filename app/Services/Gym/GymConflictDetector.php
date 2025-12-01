<?php

namespace App\Services\Gym;

use App\Models\GymHall;
use App\Models\GymHallCourt;
use App\Models\GymTimeSlot;
use App\Models\GymTimeSlotTeamAssignment;
use Carbon\Carbon;

/**
 * Service für Konflikt-Erkennung und Validierung von Gym-Buchungen.
 *
 * Verantwortlichkeiten:
 * - Erkennung von Zeitslot-Überschneidungen
 * - Validierung von Zeitslot-Daten gegen Hallenöffnungszeiten
 * - Validierung von flexiblen Buchungszeiten
 * - Validierung von Court-Auswahl
 * - Validierung von Custom Times
 * - Prüfung auf Buchungs-Konflikte bei Zeitänderungen
 * - Validierung von Team-Segment-Zuweisungen
 */
class GymConflictDetector
{
    /**
     * Get conflicts for a time slot.
     *
     * @return array<int, array{type: string, message: string, conflicting_slot: GymTimeSlot}>
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
     * Validate time slot data against gym hall operating hours.
     *
     * @return array<int, string>
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
     * Validate flexible booking time against custom times and 30-min slots.
     *
     * @return array<int, string>
     */
    public function validateFlexibleBookingTime(
        GymTimeSlot $timeSlot,
        Carbon $date,
        string $startTime,
        int $duration
    ): array {
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
     *
     * @param  array<int, int>  $courtIds
     * @return array<int, string>
     */
    public function validateCourtSelection(
        GymHall $gymHall,
        array $courtIds,
        Carbon $date,
        string $startTime,
        int $duration
    ): array {
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
            if (! $court->isAvailableAt($dateTime, $duration)) {
                $errors[] = "Court {$court->court_identifier} ist zu dieser Zeit nicht verfügbar.";
            }
        }

        return $errors;
    }

    // ============================
    // VALIDATION METHODS (extracted from GymTimeSlot model)
    // ============================

    /**
     * Validate custom times structure and ranges.
     *
     * @return array<int, string>
     */
    public function validateCustomTimes(array $customTimes): array
    {
        $errors = [];
        $validDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($customTimes as $day => $times) {
            if (! in_array($day, $validDays)) {
                $errors[] = "Ungültiger Wochentag: {$day}";

                continue;
            }

            if (! is_array($times) || ! isset($times['start_time']) || ! isset($times['end_time'])) {
                $errors[] = "Ungültige Zeitstruktur für {$day}";

                continue;
            }

            // Validate time format
            if (! preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $times['start_time'])) {
                $errors[] = "Ungültiges Startzeit-Format für {$day}: {$times['start_time']}";
            }

            if (! preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $times['end_time'])) {
                $errors[] = "Ungültiges Endzeit-Format für {$day}: {$times['end_time']}";
            }

            // Check if start time is before end time
            try {
                $start = Carbon::createFromTimeString($times['start_time']);
                $end = Carbon::createFromTimeString($times['end_time']);

                if ($start->gte($end)) {
                    $errors[] = "Startzeit muss vor Endzeit liegen für {$day}";
                }

                // Check for reasonable opening hours (not longer than 18 hours)
                if ($start->diffInHours($end) > 18) {
                    $errors[] = "Öffnungszeiten für {$day} sind zu lang (maximal 18 Stunden)";
                }

                // Check for minimum duration (at least 30 minutes)
                if ($start->diffInMinutes($end) < 30) {
                    $errors[] = "Mindestöffnungszeit von 30 Minuten für {$day} unterschritten";
                }

            } catch (\Exception $e) {
                $errors[] = "Fehler beim Validieren der Zeiten für {$day}: ".$e->getMessage();
            }
        }

        return $errors;
    }

    /**
     * Check for overlapping time slots in the same hall.
     *
     * @param  int|array<int>|null  $excludeSlotIds
     * @return array<int, array{day: string, new_time: string, existing_time: string, existing_slot_id: int, existing_slot_title: string}>
     */
    public function hasOverlappingSlots(int $gymHallId, array $customTimes, int|array|null $excludeSlotIds = null): array
    {
        $conflicts = [];

        $existingSlots = GymTimeSlot::where('gym_hall_id', $gymHallId)
            ->when($excludeSlotIds, function ($query, $excludeSlotIds) {
                // Handle both single ID and array of IDs
                if (is_array($excludeSlotIds)) {
                    $query->whereNotIn('id', $excludeSlotIds);
                } else {
                    $query->where('id', '!=', $excludeSlotIds);
                }
            })
            ->get();

        foreach ($customTimes as $day => $newTimes) {
            if (! isset($newTimes['start_time']) || ! isset($newTimes['end_time'])) {
                continue;
            }

            $newStart = Carbon::createFromTimeString($newTimes['start_time']);
            $newEnd = Carbon::createFromTimeString($newTimes['end_time']);

            foreach ($existingSlots as $existingSlot) {
                $existingTimes = $existingSlot->getTimesForDay($day);

                if (! $existingTimes || ! $existingTimes['start_time'] || ! $existingTimes['end_time']) {
                    continue;
                }

                $existingStart = Carbon::createFromTimeString($existingTimes['start_time']);
                $existingEnd = Carbon::createFromTimeString($existingTimes['end_time']);

                // Check for overlap
                if ($newStart->lt($existingEnd) && $newEnd->gt($existingStart)) {
                    $conflicts[] = [
                        'day' => $day,
                        'new_time' => $newTimes['start_time'].' - '.$newTimes['end_time'],
                        'existing_time' => $existingTimes['start_time'].' - '.$existingTimes['end_time'],
                        'existing_slot_id' => $existingSlot->id,
                        'existing_slot_title' => $existingSlot->title,
                    ];
                }
            }
        }

        return $conflicts;
    }

    /**
     * Validate booking time against time slot constraints.
     *
     * @return array<int, string>
     */
    public function validateBookingTimeForSlot(GymTimeSlot $timeSlot, Carbon $date, string $startTime, string $endTime): array
    {
        $errors = [];
        $dayOfWeek = strtolower($date->format('l'));

        try {
            $start = Carbon::createFromTimeString($startTime);
            $end = Carbon::createFromTimeString($endTime);
            $duration = $start->diffInMinutes($end);

            // Check if booking is possible for this time slot
            if (! $timeSlot->canBookAtTime($date, $startTime, $duration)) {
                $errors[] = 'Buchung zu dieser Zeit nicht möglich für dieses Zeitfenster.';
            }

            // Additional custom time validations
            $allowedTimes = $timeSlot->getTimesForDay($dayOfWeek);
            if ($allowedTimes) {
                $allowedStart = Carbon::createFromTimeString($allowedTimes['start_time']);
                $allowedEnd = Carbon::createFromTimeString($allowedTimes['end_time']);

                if ($start->lt($allowedStart)) {
                    $errors[] = "Startzeit liegt vor der erlaubten Zeit ({$allowedTimes['start_time']}).";
                }

                if ($end->gt($allowedEnd)) {
                    $errors[] = "Endzeit liegt nach der erlaubten Zeit ({$allowedTimes['end_time']}).";
                }
            }

        } catch (\Exception $e) {
            $errors[] = 'Ungültiges Zeitformat.';
        }

        return $errors;
    }

    /**
     * Get conflicting bookings when changing time slot times.
     *
     * @return array<string, array<int, array{id: int, date: string, time: string, team: string, status: string}>>
     */
    public function getConflictingBookings(GymTimeSlot $timeSlot, array $newCustomTimes): array
    {
        $conflicts = [];

        foreach ($newCustomTimes as $day => $times) {
            if (! isset($times['start_time']) || ! isset($times['end_time'])) {
                continue;
            }

            $newStart = $times['start_time'];
            $newEnd = $times['end_time'];

            // Get current times for this day
            $currentTimes = $timeSlot->getTimesForDay($day);

            // Only check if times are being restricted (new times are more restrictive)
            if ($currentTimes &&
                ($newStart > $currentTimes['start_time'] || $newEnd < $currentTimes['end_time'])) {

                $dayNumber = $this->getDayNumberForName($day);

                // Find bookings that would be outside new time range
                $conflictingBookings = $timeSlot->bookings()
                    ->whereRaw('DAYOFWEEK(booking_date) - 1 = ?', [$dayNumber])
                    ->where(function ($query) use ($newStart, $newEnd) {
                        $query->where('start_time', '<', $newStart)
                            ->orWhere('end_time', '>', $newEnd);
                    })
                    ->whereIn('status', ['reserved', 'confirmed'])
                    ->with(['team'])
                    ->get();

                if ($conflictingBookings->count() > 0) {
                    $conflicts[$day] = $conflictingBookings->map(function ($booking) {
                        return [
                            'id' => $booking->id,
                            'date' => $booking->booking_date,
                            'time' => $booking->start_time.' - '.$booking->end_time,
                            'team' => $booking->team->name ?? 'Unbekannt',
                            'status' => $booking->status,
                        ];
                    })->toArray();
                }
            }
        }

        return $conflicts;
    }

    /**
     * Check if a team can be assigned to a time slot segment.
     *
     * @return array<int, string>
     */
    public function canAssignTeamToSegment(
        GymTimeSlot $timeSlot,
        int $teamId,
        string $dayOfWeek,
        string $startTime,
        string $endTime,
        ?int $gymCourtId = null
    ): array {
        $errors = [];

        // Check for team conflicts
        if (GymTimeSlotTeamAssignment::hasConflictForTeam(
            $timeSlot->id,
            $teamId,
            $dayOfWeek,
            $startTime,
            $endTime
        )) {
            $errors[] = 'Team hat bereits eine Zuordnung zu dieser Zeit.';
        }

        // Validate against time slot operating hours
        $times = $timeSlot->getTimesForDay($dayOfWeek);
        if (! $times) {
            $errors[] = 'Keine Öffnungszeiten für diesen Tag definiert.';
        } else {
            try {
                $slotStart = Carbon::createFromTimeString($times['start_time']);
                $slotEnd = Carbon::createFromTimeString($times['end_time']);
                $requestStart = Carbon::createFromTimeString($startTime);
                $requestEnd = Carbon::createFromTimeString($endTime);

                if ($requestStart->lt($slotStart) || $requestEnd->gt($slotEnd)) {
                    $errors[] = "Zeitfenster liegt außerhalb der Öffnungszeiten ({$times['start_time']} - {$times['end_time']}).";
                }
            } catch (\Exception $e) {
                $errors[] = 'Ungültiges Zeitformat.';
            }
        }

        // Validate time format and duration
        try {
            $startCarbon = Carbon::createFromTimeString($startTime);
            $endCarbon = Carbon::createFromTimeString($endTime);
            $duration = $startCarbon->diffInMinutes($endCarbon);

            if ($duration < 30) {
                $errors[] = 'Minimale Buchungsdauer von 30 Minuten unterschritten.';
            }

            if ($duration % 30 !== 0) {
                $errors[] = 'Buchungsdauer muss in 30-Minuten-Schritten erfolgen.';
            }
        } catch (\Exception $e) {
            $errors[] = 'Ungültiges Zeitformat für Start- oder Endzeit.';

            return $errors; // Return early if time format is invalid
        }

        // Check parallel bookings restrictions
        $gymHall = $timeSlot->gymHall;

        if (! $gymHall) {
            $errors[] = 'Zugehörige Sporthalle nicht gefunden.';

            return $errors;
        }

        // Check main court logic and parallel booking rules
        $parallelBookingErrors = $this->validateParallelBookingRules(
            $timeSlot,
            $gymHall,
            $teamId,
            $dayOfWeek,
            $startTime,
            $endTime,
            $gymCourtId
        );

        return array_merge($errors, $parallelBookingErrors);
    }

    /**
     * Validate parallel booking rules for gym hall.
     *
     * @return array<int, string>
     */
    private function validateParallelBookingRules(
        GymTimeSlot $timeSlot,
        GymHall $gymHall,
        int $teamId,
        string $dayOfWeek,
        string $startTime,
        string $endTime,
        ?int $gymCourtId
    ): array {
        $errors = [];

        // Check if we're trying to book the main court
        $mainCourt = $gymHall->getMainCourt();
        $isBookingMainCourt = $mainCourt && $gymCourtId == $mainCourt->id;

        // Check if main court is already booked during this time
        $mainCourtIsBooked = $gymHall->hasMainCourtBooking($dayOfWeek, $startTime, $endTime);

        // Get effective parallel booking rules
        $effectiveParallelBookingsAllowed = $gymHall->allowsParallelBookingsForTime($dayOfWeek, $startTime, $endTime);

        if ($isBookingMainCourt) {
            // If trying to book main court, check if any other courts are occupied
            $otherCourtAssignments = $timeSlot->activeTeamAssignments()
                ->where('day_of_week', $dayOfWeek)
                ->where('team_id', '!=', $teamId)
                ->where('gym_court_id', '!=', $mainCourt->id)
                ->whereNotNull('gym_court_id')
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                })
                ->with(['team', 'gymCourt'])
                ->get();

            if ($otherCourtAssignments->count() > 0) {
                $occupiedCourts = $otherCourtAssignments->map(function ($assignment) {
                    return $assignment->team->name.' ('.$assignment->gymCourt->name.')';
                })->join(', ');
                $errors[] = "Hauptplatz kann nicht gebucht werden - andere Felder sind bereits belegt: {$occupiedCourts}";
            }
        } elseif ($mainCourtIsBooked) {
            // If main court is booked, no other bookings allowed
            $errors[] = 'Keine weiteren Buchungen möglich - der Hauptplatz ist zu dieser Zeit belegt.';
        } elseif (! $effectiveParallelBookingsAllowed) {
            // Standard parallel booking rules
            $existingAssignments = $timeSlot->activeTeamAssignments()
                ->where('day_of_week', $dayOfWeek)
                ->where('team_id', '!=', $teamId)
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                })
                ->with(['team'])
                ->get();

            if ($existingAssignments->count() > 0) {
                $teamNames = $existingAssignments->pluck('team.name')->join(', ');
                $errors[] = "Für diesen Tag sind keine Parallel-Buchungen erlaubt. Bereits belegt von: {$teamNames}";
            }
        } else {
            // Parallel bookings allowed - check effective capacity
            $effectiveMaxTeams = $gymHall->getEffectiveMaxParallelTeams($dayOfWeek, $startTime, $endTime);

            $overlappingAssignments = $timeSlot->activeTeamAssignments()
                ->where('day_of_week', $dayOfWeek)
                ->where('team_id', '!=', $teamId)
                ->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                })
                ->count();

            if ($overlappingAssignments >= $effectiveMaxTeams) {
                $errors[] = "Maximale Anzahl paralleler Teams ({$effectiveMaxTeams}) für diesen Tag bereits erreicht.";
            }

            // If a court is specified, check for court conflicts
            if ($gymCourtId) {
                $courtConflict = GymTimeSlotTeamAssignment::hasConflictForCourt(
                    $timeSlot->id,
                    $gymCourtId,
                    $dayOfWeek,
                    $startTime,
                    $endTime
                );

                if ($courtConflict) {
                    $errors[] = 'Das ausgewählte Feld ist zu dieser Zeit bereits belegt.';
                }
            }
        }

        return $errors;
    }

    /**
     * Get day number for day name (0 = Sunday in MySQL DAYOFWEEK).
     */
    private function getDayNumberForName(string $dayName): int
    {
        $days = [
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            'sunday' => 0,
        ];

        return $days[$dayName] ?? 1;
    }
}
