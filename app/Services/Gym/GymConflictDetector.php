<?php

namespace App\Services\Gym;

use App\Models\GymHall;
use App\Models\GymHallCourt;
use App\Models\GymTimeSlot;
use Carbon\Carbon;

/**
 * Service für Konflikt-Erkennung und Validierung von Gym-Buchungen.
 *
 * Verantwortlichkeiten:
 * - Erkennung von Zeitslot-Überschneidungen
 * - Validierung von Zeitslot-Daten gegen Hallenöffnungszeiten
 * - Validierung von flexiblen Buchungszeiten
 * - Validierung von Court-Auswahl
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
}
