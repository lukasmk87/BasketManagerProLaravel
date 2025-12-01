<?php

namespace Tests\Unit\Services\Gym;

use App\Models\Club;
use App\Models\GymHall;
use App\Models\GymHallCourt;
use App\Models\GymTimeSlot;
use App\Models\GymTimeSlotTeamAssignment;
use App\Models\Team;
use App\Models\User;
use App\Services\Gym\GymConflictDetector;
use App\Services\Gym\GymTimeSlotAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GymTimeSlotAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private GymTimeSlotAssignmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $conflictDetector = new GymConflictDetector();
        $this->service = new GymTimeSlotAssignmentService($conflictDetector);
    }

    // ============================
    // assignToTeam Tests
    // ============================

    public function test_assign_to_team_updates_time_slot(): void
    {
        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);
        $user = User::factory()->create();
        $gymHall = GymHall::factory()->create(['club_id' => $club->id]);

        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'team_id' => null,
        ]);

        $result = $this->service->assignToTeam($timeSlot, $team, $user, 'Regular training');

        $this->assertTrue($result);
        $timeSlot->refresh();
        $this->assertEquals($team->id, $timeSlot->team_id);
        $this->assertEquals($user->id, $timeSlot->assigned_by);
        $this->assertNotNull($timeSlot->assigned_at);
    }

    public function test_assign_to_team_stores_reason_in_metadata(): void
    {
        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);
        $user = User::factory()->create(['name' => 'Coach Smith']);
        $gymHall = GymHall::factory()->create(['club_id' => $club->id]);

        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'team_id' => null,
        ]);

        $this->service->assignToTeam($timeSlot, $team, $user, 'Weekly training session');

        $timeSlot->refresh();
        $this->assertEquals('Weekly training session', $timeSlot->metadata['assignment_reason']);
        $this->assertEquals('Coach Smith', $timeSlot->metadata['assigned_by_name']);
    }

    // ============================
    // unassignFromTeam Tests
    // ============================

    public function test_unassign_from_team_clears_team_id(): void
    {
        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);
        $user = User::factory()->create();
        $gymHall = GymHall::factory()->create(['club_id' => $club->id]);

        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'team_id' => $team->id,
        ]);

        $result = $this->service->unassignFromTeam($timeSlot, $user, 'Season ended');

        $this->assertTrue($result);
        $timeSlot->refresh();
        $this->assertNull($timeSlot->team_id);
    }

    public function test_unassign_from_team_stores_reason_in_metadata(): void
    {
        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);
        $user = User::factory()->create(['name' => 'Admin User']);
        $gymHall = GymHall::factory()->create(['club_id' => $club->id]);

        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'team_id' => $team->id,
        ]);

        $this->service->unassignFromTeam($timeSlot, $user, 'Season ended');

        $timeSlot->refresh();
        $this->assertEquals('Season ended', $timeSlot->metadata['unassignment_reason']);
        $this->assertEquals('Admin User', $timeSlot->metadata['unassigned_by_name']);
        $this->assertEquals($user->id, $timeSlot->metadata['unassigned_by']);
    }

    // ============================
    // removeTeamAssignment Tests
    // ============================

    public function test_remove_team_assignment_deletes_assignment(): void
    {
        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);
        $user = User::factory()->create();
        $gymHall = GymHall::factory()->create([
            'club_id' => $club->id,
            'supports_parallel_bookings' => true,
            'max_parallel_teams' => 3,
        ]);

        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'start_time' => '08:00',
            'end_time' => '20:00',
            'uses_custom_times' => false,
        ]);

        $assignment = GymTimeSlotTeamAssignment::factory()->create([
            'gym_time_slot_id' => $timeSlot->id,
            'team_id' => $team->id,
            'day_of_week' => 'monday',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => 'active',
        ]);

        $result = $this->service->removeTeamAssignment($timeSlot, $assignment->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('gym_time_slot_team_assignments', [
            'id' => $assignment->id,
        ]);
    }

    public function test_remove_team_assignment_returns_false_for_invalid_id(): void
    {
        $gymHall = GymHall::factory()->create();
        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
        ]);

        $result = $this->service->removeTeamAssignment($timeSlot, 99999);

        $this->assertFalse($result);
    }

    // ============================
    // getTeamAssignmentsForDay Tests
    // ============================

    public function test_get_team_assignments_for_day_returns_assignments(): void
    {
        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id, 'name' => 'U16 Team']);
        $gymHall = GymHall::factory()->create(['club_id' => $club->id]);

        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
        ]);

        GymTimeSlotTeamAssignment::factory()->create([
            'gym_time_slot_id' => $timeSlot->id,
            'team_id' => $team->id,
            'day_of_week' => 'monday',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'duration_minutes' => 60,
            'status' => 'active',
        ]);

        $assignments = $this->service->getTeamAssignmentsForDay($timeSlot, 'monday');

        $this->assertCount(1, $assignments);
        $this->assertEquals('U16 Team', $assignments[0]['team_name']);
        $this->assertEquals('10:00', $assignments[0]['start_time']);
        $this->assertEquals('11:00', $assignments[0]['end_time']);
    }

    public function test_get_team_assignments_for_day_returns_empty_for_different_day(): void
    {
        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);
        $gymHall = GymHall::factory()->create(['club_id' => $club->id]);

        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
        ]);

        GymTimeSlotTeamAssignment::factory()->create([
            'gym_time_slot_id' => $timeSlot->id,
            'team_id' => $team->id,
            'day_of_week' => 'monday',
            'status' => 'active',
        ]);

        $assignments = $this->service->getTeamAssignmentsForDay($timeSlot, 'tuesday');

        $this->assertEmpty($assignments);
    }

    public function test_get_team_assignments_for_day_excludes_inactive(): void
    {
        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);
        $gymHall = GymHall::factory()->create(['club_id' => $club->id]);

        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
        ]);

        GymTimeSlotTeamAssignment::factory()->create([
            'gym_time_slot_id' => $timeSlot->id,
            'team_id' => $team->id,
            'day_of_week' => 'monday',
            'status' => 'inactive',
        ]);

        $assignments = $this->service->getTeamAssignmentsForDay($timeSlot, 'monday');

        $this->assertEmpty($assignments);
    }

    // ============================
    // getTeamsAssignedToSegment Tests
    // ============================

    public function test_get_teams_assigned_to_segment_returns_overlapping_teams(): void
    {
        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id, 'name' => 'Team A']);
        $gymHall = GymHall::factory()->create(['club_id' => $club->id]);

        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
        ]);

        GymTimeSlotTeamAssignment::factory()->create([
            'gym_time_slot_id' => $timeSlot->id,
            'team_id' => $team->id,
            'day_of_week' => 'monday',
            'start_time' => '10:00',
            'end_time' => '11:30',
            'status' => 'active',
        ]);

        // Query for overlapping segment
        $teams = $this->service->getTeamsAssignedToSegment(
            $timeSlot,
            'monday',
            '10:30',
            '11:00'
        );

        $this->assertCount(1, $teams);
        $this->assertEquals('Team A', $teams[0]['team_name']);
    }

    public function test_get_teams_assigned_to_segment_returns_empty_for_non_overlapping(): void
    {
        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);
        $gymHall = GymHall::factory()->create(['club_id' => $club->id]);

        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
        ]);

        GymTimeSlotTeamAssignment::factory()->create([
            'gym_time_slot_id' => $timeSlot->id,
            'team_id' => $team->id,
            'day_of_week' => 'monday',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => 'active',
        ]);

        // Query for non-overlapping segment
        $teams = $this->service->getTeamsAssignedToSegment(
            $timeSlot,
            'monday',
            '14:00',
            '15:00'
        );

        $this->assertEmpty($teams);
    }

    // ============================
    // getAvailableSegmentsForDay Tests
    // ============================

    public function test_get_available_segments_for_day_returns_segments(): void
    {
        $gymHall = GymHall::factory()->create();
        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'day_of_week' => 'monday',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'uses_custom_times' => false,
        ]);

        $segments = $this->service->getAvailableSegmentsForDay($timeSlot, 'monday', 30);

        $this->assertCount(4, $segments); // 2 hours / 30 min = 4 segments
        $this->assertArrayHasKey('is_available', $segments[0]);
        $this->assertArrayHasKey('assigned_teams', $segments[0]);
    }

    public function test_get_available_segments_marks_occupied_as_unavailable(): void
    {
        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);
        $gymHall = GymHall::factory()->create(['club_id' => $club->id]);

        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'day_of_week' => 'monday',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'uses_custom_times' => false,
        ]);

        // Assign team to first segment
        GymTimeSlotTeamAssignment::factory()->create([
            'gym_time_slot_id' => $timeSlot->id,
            'team_id' => $team->id,
            'day_of_week' => 'monday',
            'start_time' => '10:00',
            'end_time' => '10:30',
            'status' => 'active',
        ]);

        $segments = $this->service->getAvailableSegmentsForDay($timeSlot, 'monday', 30);

        // First segment should be unavailable
        $this->assertFalse($segments[0]['is_available']);
        $this->assertNotEmpty($segments[0]['assigned_teams']);

        // Second segment should be available
        $this->assertTrue($segments[1]['is_available']);
        $this->assertEmpty($segments[1]['assigned_teams']);
    }

    // ============================
    // deactivateTeamAssignment Tests
    // ============================

    public function test_deactivate_team_assignment_sets_status_inactive(): void
    {
        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);
        $user = User::factory()->create();
        $gymHall = GymHall::factory()->create(['club_id' => $club->id]);

        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
        ]);

        $assignment = GymTimeSlotTeamAssignment::factory()->create([
            'gym_time_slot_id' => $timeSlot->id,
            'team_id' => $team->id,
            'status' => 'active',
        ]);

        $result = $this->service->deactivateTeamAssignment(
            $timeSlot,
            $assignment->id,
            $user,
            'Season ended'
        );

        $this->assertTrue($result);
        $assignment->refresh();
        $this->assertEquals('inactive', $assignment->status);
        $this->assertNotNull($assignment->valid_until);
    }
}
