<?php

namespace App\Services\Gym;

use App\Models\GymCourt;
use App\Models\GymTimeSlot;
use App\Models\GymTimeSlotTeamAssignment;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Service für Team-Zuweisungen zu Zeitfenstern.
 *
 * Verantwortlichkeiten:
 * - Team-Zuweisung zu kompletten Zeitfenstern
 * - Team-Zuweisung zu Segmenten (30-Min-Blöcke)
 * - Verwaltung von Team-Assignments
 * - Abfrage von Team-Zuweisungen
 *
 * Extrahiert aus GymTimeSlot Model zur Reduktion der LOC und Verbesserung der Testbarkeit.
 */
class GymTimeSlotAssignmentService
{
    public function __construct(
        private GymConflictDetector $conflictDetector
    ) {}

    // ============================
    // TEAM ASSIGNMENT TO TIME SLOT
    // ============================

    /**
     * Assign a time slot to a team.
     */
    public function assignToTeam(
        GymTimeSlot $timeSlot,
        Team $team,
        User $assignedBy,
        ?string $reason = null
    ): bool {
        return DB::transaction(function () use ($timeSlot, $team, $assignedBy, $reason) {
            $timeSlot->update([
                'team_id' => $team->id,
                'assigned_by' => $assignedBy->id,
                'assigned_at' => now(),
                'metadata' => array_merge($timeSlot->metadata ?? [], [
                    'assignment_reason' => $reason,
                    'assigned_by_name' => $assignedBy->name,
                ]),
            ]);

            return true;
        });
    }

    /**
     * Unassign a time slot from a team.
     */
    public function unassignFromTeam(
        GymTimeSlot $timeSlot,
        User $unassignedBy,
        ?string $reason = null
    ): bool {
        return DB::transaction(function () use ($timeSlot, $unassignedBy, $reason) {
            $timeSlot->update([
                'team_id' => null,
                'metadata' => array_merge($timeSlot->metadata ?? [], [
                    'last_unassigned_at' => now(),
                    'unassignment_reason' => $reason,
                    'unassigned_by' => $unassignedBy->id,
                    'unassigned_by_name' => $unassignedBy->name,
                ]),
            ]);

            return true;
        });
    }

    // ============================
    // SEGMENT-BASED TEAM ASSIGNMENT
    // ============================

    /**
     * Assign a team to a specific time segment within a time slot.
     */
    public function assignTeamToSegment(
        GymTimeSlot $timeSlot,
        Team $team,
        string $dayOfWeek,
        string $startTime,
        string $endTime,
        User $assignedBy,
        ?string $notes = null,
        ?GymCourt $gymCourt = null
    ): GymTimeSlotTeamAssignment {
        // First validate the assignment
        $errors = $this->conflictDetector->canAssignTeamToSegment(
            $timeSlot,
            $team->id,
            $dayOfWeek,
            $startTime,
            $endTime,
            $gymCourt?->id
        );

        if (! empty($errors)) {
            throw new \InvalidArgumentException(implode(' ', $errors));
        }

        return DB::transaction(function () use ($timeSlot, $team, $dayOfWeek, $startTime, $endTime, $assignedBy, $notes, $gymCourt) {
            $startCarbon = Carbon::createFromTimeString($startTime);
            $endCarbon = Carbon::createFromTimeString($endTime);
            $duration = $startCarbon->diffInMinutes($endCarbon);

            return $timeSlot->teamAssignments()->create([
                'uuid' => Str::uuid(),
                'gym_time_slot_id' => $timeSlot->id,
                'team_id' => $team->id,
                'gym_court_id' => $gymCourt?->id,
                'day_of_week' => $dayOfWeek,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration_minutes' => $duration,
                'status' => 'active',
                'notes' => $notes,
                'assigned_by' => $assignedBy->id,
                'assigned_at' => now(),
                'valid_from' => now()->toDateString(),
            ]);
        });
    }

    /**
     * Remove a team assignment from a time slot segment.
     */
    public function removeTeamAssignment(GymTimeSlot $timeSlot, int $assignmentId): bool
    {
        $assignment = $timeSlot->teamAssignments()->find($assignmentId);

        if ($assignment) {
            return (bool) $assignment->delete();
        }

        return false;
    }

    /**
     * Deactivate a team assignment instead of deleting it.
     */
    public function deactivateTeamAssignment(
        GymTimeSlot $timeSlot,
        int $assignmentId,
        User $deactivatedBy,
        ?string $reason = null
    ): bool {
        $assignment = $timeSlot->teamAssignments()->find($assignmentId);

        if ($assignment) {
            $assignment->update([
                'status' => 'inactive',
                'valid_until' => now()->toDateString(),
                'notes' => $assignment->notes
                    ? $assignment->notes."\n[Deaktiviert: ".$reason.']'
                    : '[Deaktiviert: '.$reason.']',
            ]);

            return true;
        }

        return false;
    }

    // ============================
    // TEAM ASSIGNMENT QUERIES
    // ============================

    /**
     * Get all team assignments for a specific day.
     *
     * @return array<int, array{id: int, team_id: int, team_name: string, start_time: string, end_time: string, duration_minutes: int, notes: ?string}>
     */
    public function getTeamAssignmentsForDay(GymTimeSlot $timeSlot, string $dayOfWeek): array
    {
        return $timeSlot->activeTeamAssignments()
            ->where('day_of_week', $dayOfWeek)
            ->with(['team'])
            ->orderBy('start_time')
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'team_id' => $assignment->team_id,
                    'team_name' => $assignment->team->name,
                    'start_time' => $assignment->start_time->format('H:i'),
                    'end_time' => $assignment->end_time->format('H:i'),
                    'duration_minutes' => $assignment->duration_minutes,
                    'notes' => $assignment->notes,
                    'gym_court_id' => $assignment->gym_court_id,
                ];
            })
            ->toArray();
    }

    /**
     * Get teams assigned to a specific time segment.
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
     * Get available segments for a day with team assignment information.
     *
     * @return array<int, array{start_time: string, end_time: string, duration_minutes: int, segment_id: string, is_available: bool, assigned_teams: array}>
     */
    public function getAvailableSegmentsForDay(
        GymTimeSlot $timeSlot,
        string $dayOfWeek,
        int $incrementMinutes = 30
    ): array {
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
     * Get all assignments for a team across all time slots.
     *
     * @return array<int, array{time_slot_id: int, time_slot_title: string, assignments: array}>
     */
    public function getTeamAssignmentsAcrossSlots(Team $team, ?Carbon $fromDate = null, ?Carbon $untilDate = null): array
    {
        $query = GymTimeSlotTeamAssignment::query()
            ->where('team_id', $team->id)
            ->where('status', 'active')
            ->with(['gymTimeSlot', 'gymTimeSlot.gymHall']);

        if ($fromDate) {
            $query->where(function ($q) use ($fromDate) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $fromDate);
            });
        }

        if ($untilDate) {
            $query->where('valid_from', '<=', $untilDate);
        }

        return $query->get()
            ->groupBy('gym_time_slot_id')
            ->map(function ($assignments, $timeSlotId) {
                $firstAssignment = $assignments->first();

                return [
                    'time_slot_id' => $timeSlotId,
                    'time_slot_title' => $firstAssignment->gymTimeSlot->title ?? 'Unbenannt',
                    'gym_hall_name' => $firstAssignment->gymTimeSlot->gymHall->name ?? 'Unbekannt',
                    'assignments' => $assignments->map(function ($assignment) {
                        return [
                            'id' => $assignment->id,
                            'day_of_week' => $assignment->day_of_week,
                            'start_time' => $assignment->start_time->format('H:i'),
                            'end_time' => $assignment->end_time->format('H:i'),
                            'duration_minutes' => $assignment->duration_minutes,
                            'valid_from' => $assignment->valid_from,
                            'valid_until' => $assignment->valid_until,
                        ];
                    })->toArray(),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Bulk assign a team to multiple segments.
     *
     * @param  array<int, array{day_of_week: string, start_time: string, end_time: string, gym_court_id?: int}>  $segments
     * @return array{created: int, failed: array<int, array{segment: array, error: string}>}
     */
    public function bulkAssignTeamToSegments(
        GymTimeSlot $timeSlot,
        Team $team,
        array $segments,
        User $assignedBy,
        ?string $notes = null
    ): array {
        $created = 0;
        $failed = [];

        DB::transaction(function () use ($timeSlot, $team, $segments, $assignedBy, $notes, &$created, &$failed) {
            foreach ($segments as $segment) {
                try {
                    $gymCourt = null;
                    if (isset($segment['gym_court_id'])) {
                        $gymCourt = GymCourt::find($segment['gym_court_id']);
                    }

                    $this->assignTeamToSegment(
                        $timeSlot,
                        $team,
                        $segment['day_of_week'],
                        $segment['start_time'],
                        $segment['end_time'],
                        $assignedBy,
                        $notes,
                        $gymCourt
                    );
                    $created++;
                } catch (\Exception $e) {
                    $failed[] = [
                        'segment' => $segment,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        });

        return [
            'created' => $created,
            'failed' => $failed,
        ];
    }

    /**
     * Transfer all assignments from one team to another.
     */
    public function transferAssignments(Team $fromTeam, Team $toTeam, User $transferredBy): int
    {
        return DB::transaction(function () use ($fromTeam, $toTeam, $transferredBy) {
            $updated = GymTimeSlotTeamAssignment::where('team_id', $fromTeam->id)
                ->where('status', 'active')
                ->update([
                    'team_id' => $toTeam->id,
                    'notes' => DB::raw("CONCAT(COALESCE(notes, ''), '\n[Übertragen von Team {$fromTeam->name} durch {$transferredBy->name} am ".now()->format('d.m.Y')."]')"),
                ]);

            // Also update time slots that are directly assigned
            GymTimeSlot::where('team_id', $fromTeam->id)
                ->update([
                    'team_id' => $toTeam->id,
                    'metadata' => DB::raw("JSON_SET(COALESCE(metadata, '{}'), '$.transferred_from_team_id', {$fromTeam->id})"),
                ]);

            return $updated;
        });
    }
}
