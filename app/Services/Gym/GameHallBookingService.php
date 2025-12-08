<?php

namespace App\Services\Gym;

use App\Models\Game;
use App\Models\GymBooking;
use App\Models\GymHall;
use App\Models\GymTimeSlot;
use App\Models\GymTimeSlotTeamAssignment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service für automatische Hallenbuchung bei Spielen.
 *
 * Verantwortlichkeiten:
 * - Automatische Buchung einer Halle wenn ein Spiel importiert wird
 * - Konflikt-Erkennung mit bestehenden Trainings
 * - Training verschieben bei Spielkonflikten
 * - Alternative Zeitslots finden
 */
class GameHallBookingService
{
    public const GAME_PRIORITY = 10;

    public const TRAINING_PRIORITY = 0;

    public const GAME_BUFFER_MINUTES = 30; // Buffer vor und nach dem Spiel

    /**
     * Create a booking for a game.
     */
    public function createBookingForGame(Game $game): ?GymBooking
    {
        if (! $game->gym_hall_id) {
            Log::info('GameHallBookingService: No gym_hall_id for game', ['game_id' => $game->id]);

            return null;
        }

        $gymHall = GymHall::find($game->gym_hall_id);
        if (! $gymHall) {
            Log::warning('GameHallBookingService: Gym hall not found', ['gym_hall_id' => $game->gym_hall_id]);

            return null;
        }

        // Calculate game time window with buffer
        $gameDate = $game->scheduled_at->toDateString();
        $gameStartTime = $game->scheduled_at->copy()->subMinutes(self::GAME_BUFFER_MINUTES)->format('H:i');
        $gameEndTime = $game->scheduled_at->copy()->addHours(2)->addMinutes(self::GAME_BUFFER_MINUTES)->format('H:i');

        return DB::transaction(function () use ($game, $gymHall, $gameDate, $gameStartTime, $gameEndTime) {
            // Check for conflicts
            $conflicts = $this->checkForConflicts($gymHall, $gameDate, $gameStartTime, $gameEndTime);

            // Resolve training conflicts
            foreach ($conflicts as $conflict) {
                if ($conflict->is_training_booking) {
                    $this->resolveTrainingConflict($conflict, $game);
                }
            }

            // Find or create appropriate time slot
            $timeSlot = $this->findOrCreateTimeSlot($gymHall, $gameDate, $gameStartTime, $gameEndTime);

            // Create the game booking
            $booking = GymBooking::create([
                'uuid' => Str::uuid(),
                'gym_time_slot_id' => $timeSlot?->id,
                'team_id' => $game->home_team_id,
                'game_id' => $game->id,
                'booking_date' => $gameDate,
                'start_time' => $gameStartTime,
                'end_time' => $gameEndTime,
                'duration_minutes' => $this->calculateDuration($gameStartTime, $gameEndTime),
                'status' => 'confirmed',
                'booking_type' => 'game',
                'priority' => self::GAME_PRIORITY,
                'booking_notes' => "Automatisch erstellt für Spiel: {$game->getHomeTeamDisplayName()} vs {$game->getAwayTeamDisplayName()}",
            ]);

            Log::info('GameHallBookingService: Game booking created', [
                'booking_id' => $booking->id,
                'game_id' => $game->id,
                'gym_hall_id' => $gymHall->id,
            ]);

            return $booking;
        });
    }

    /**
     * Check for conflicting bookings in the time window.
     */
    public function checkForConflicts(
        GymHall $hall,
        string $date,
        string $startTime,
        string $endTime
    ): Collection {
        return GymBooking::whereHas('gymTimeSlot', function ($query) use ($hall) {
            $query->where('gym_hall_id', $hall->id);
        })
            ->where('booking_date', $date)
            ->whereIn('status', ['reserved', 'confirmed'])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    // Booking starts before our end and ends after our start (overlap)
                    $q->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                });
            })
            ->get();
    }

    /**
     * Resolve a training conflict by moving the training to an alternative slot.
     */
    public function resolveTrainingConflict(GymBooking $trainingBooking, Game $game): bool
    {
        // Only resolve training conflicts, not game conflicts
        if ($trainingBooking->is_game_booking) {
            Log::warning('GameHallBookingService: Cannot resolve game conflict', [
                'conflicting_booking_id' => $trainingBooking->id,
                'new_game_id' => $game->id,
            ]);

            return false;
        }

        // Try to find an alternative slot
        $assignment = $this->findTeamAssignmentForBooking($trainingBooking);
        if (! $assignment) {
            // No assignment found, just cancel the training
            return $this->cancelTrainingBooking($trainingBooking, $game);
        }

        $alternativeSlot = $this->findAlternativeSlot(
            $assignment,
            Carbon::parse($trainingBooking->booking_date)
        );

        if ($alternativeSlot) {
            // Move training to alternative slot
            return $this->moveTrainingToAlternativeSlot($trainingBooking, $alternativeSlot);
        }

        // No alternative found, cancel the training
        return $this->cancelTrainingBooking($trainingBooking, $game);
    }

    /**
     * Find an alternative time slot for a team assignment on the given date.
     */
    public function findAlternativeSlot(
        GymTimeSlotTeamAssignment $assignment,
        Carbon $conflictDate
    ): ?array {
        $dayOfWeek = strtolower($conflictDate->englishDayOfWeek);
        $gymHall = $assignment->gymTimeSlot->gymHall;

        // Get all time slots for this day at the same gym hall
        $availableSlots = GymTimeSlot::where('gym_hall_id', $gymHall->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->where('id', '!=', $assignment->gym_time_slot_id)
            ->get();

        foreach ($availableSlots as $slot) {
            // Check if this slot is free from conflicts on the given date
            $slotStartTime = $slot->start_time instanceof Carbon
                ? $slot->start_time->format('H:i')
                : $slot->start_time;
            $slotEndTime = $slot->end_time instanceof Carbon
                ? $slot->end_time->format('H:i')
                : $slot->end_time;

            $conflicts = $this->checkForConflicts(
                $gymHall,
                $conflictDate->toDateString(),
                $slotStartTime,
                $slotEndTime
            );

            if ($conflicts->isEmpty()) {
                return [
                    'time_slot' => $slot,
                    'start_time' => $slotStartTime,
                    'end_time' => $slotEndTime,
                    'date' => $conflictDate->toDateString(),
                ];
            }
        }

        // No alternative slot available on the same day
        return null;
    }

    /**
     * Move a training booking to an alternative slot.
     */
    protected function moveTrainingToAlternativeSlot(GymBooking $booking, array $alternativeSlot): bool
    {
        try {
            $oldSlot = $booking->gymTimeSlot;

            $booking->update([
                'gym_time_slot_id' => $alternativeSlot['time_slot']->id,
                'start_time' => $alternativeSlot['start_time'],
                'end_time' => $alternativeSlot['end_time'],
                'duration_minutes' => $this->calculateDuration(
                    $alternativeSlot['start_time'],
                    $alternativeSlot['end_time']
                ),
                'booking_notes' => ($booking->booking_notes ?? '').
                    "\n[Automatisch verschoben von {$oldSlot?->start_time}-{$oldSlot?->end_time} wegen Spielkonfllikt]",
            ]);

            Log::info('GameHallBookingService: Training moved to alternative slot', [
                'booking_id' => $booking->id,
                'new_slot_id' => $alternativeSlot['time_slot']->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('GameHallBookingService: Failed to move training', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Cancel a training booking due to game conflict.
     */
    protected function cancelTrainingBooking(GymBooking $booking, Game $game): bool
    {
        try {
            $booking->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => "Automatisch storniert wegen Spielbuchung: {$game->getHomeTeamDisplayName()} vs {$game->getAwayTeamDisplayName()} am {$game->scheduled_at->format('d.m.Y H:i')}",
            ]);

            Log::info('GameHallBookingService: Training cancelled due to game', [
                'booking_id' => $booking->id,
                'game_id' => $game->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('GameHallBookingService: Failed to cancel training', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Find the team assignment that created this booking.
     */
    protected function findTeamAssignmentForBooking(GymBooking $booking): ?GymTimeSlotTeamAssignment
    {
        if (! $booking->gym_time_slot_id || ! $booking->team_id) {
            return null;
        }

        return GymTimeSlotTeamAssignment::where('gym_time_slot_id', $booking->gym_time_slot_id)
            ->where('team_id', $booking->team_id)
            ->first();
    }

    /**
     * Find or create a time slot for the game.
     */
    protected function findOrCreateTimeSlot(
        GymHall $gymHall,
        string $date,
        string $startTime,
        string $endTime
    ): ?GymTimeSlot {
        $dayOfWeek = strtolower(Carbon::parse($date)->englishDayOfWeek);

        // Try to find an existing time slot that covers the game time
        $existingSlot = GymTimeSlot::where('gym_hall_id', $gymHall->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('start_time', '<=', $startTime)
            ->where('end_time', '>=', $endTime)
            ->where('is_active', true)
            ->first();

        if ($existingSlot) {
            return $existingSlot;
        }

        // For games, we might not need a specific time slot
        // Return the first active slot for that day
        return GymTimeSlot::where('gym_hall_id', $gymHall->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Calculate duration in minutes between two times.
     */
    protected function calculateDuration(string $startTime, string $endTime): int
    {
        $start = Carbon::createFromFormat('H:i', $startTime);
        $end = Carbon::createFromFormat('H:i', $endTime);

        return $start->diffInMinutes($end);
    }

    /**
     * Match a venue code to a gym hall.
     */
    public function matchVenueCodeToGymHall(string $venueCode, ?int $clubId = null): ?GymHall
    {
        $query = GymHall::where('hall_number', $venueCode)
            ->where('is_active', true);

        if ($clubId) {
            $query->where('club_id', $clubId);
        }

        return $query->first();
    }

    /**
     * Get conflict report for a potential game booking.
     */
    public function getConflictReport(
        GymHall $hall,
        Carbon $gameDateTime,
        int $durationMinutes = 120
    ): array {
        $date = $gameDateTime->toDateString();
        $startTime = $gameDateTime->copy()->subMinutes(self::GAME_BUFFER_MINUTES)->format('H:i');
        $endTime = $gameDateTime->copy()->addMinutes($durationMinutes + self::GAME_BUFFER_MINUTES)->format('H:i');

        $conflicts = $this->checkForConflicts($hall, $date, $startTime, $endTime);

        return [
            'has_conflicts' => $conflicts->isNotEmpty(),
            'conflict_count' => $conflicts->count(),
            'training_conflicts' => $conflicts->filter(fn ($b) => $b->is_training_booking)->count(),
            'game_conflicts' => $conflicts->filter(fn ($b) => $b->is_game_booking)->count(),
            'conflicts' => $conflicts->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'type' => $booking->is_game_booking ? 'game' : 'training',
                    'team' => $booking->team?->name ?? 'Unbekannt',
                    'time' => "{$booking->start_time->format('H:i')} - {$booking->end_time->format('H:i')}",
                    'can_be_moved' => $booking->is_training_booking,
                ];
            }),
        ];
    }
}
