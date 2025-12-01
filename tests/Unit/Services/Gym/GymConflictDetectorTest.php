<?php

namespace Tests\Unit\Services\Gym;

use App\Models\GymHall;
use App\Models\GymHallCourt;
use App\Models\GymTimeSlot;
use App\Services\Gym\GymConflictDetector;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GymConflictDetectorTest extends TestCase
{
    use RefreshDatabase;

    private GymConflictDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new GymConflictDetector();
    }

    public function test_detects_overlapping_time_slots(): void
    {
        $gymHall = GymHall::factory()->create();

        // Create first time slot
        $slot1 = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'day_of_week' => 'monday',
            'start_time' => '14:00',
            'end_time' => '16:00',
            'status' => 'active',
        ]);

        // Create overlapping time slot
        $slot2 = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'day_of_week' => 'monday',
            'start_time' => '15:00',
            'end_time' => '17:00',
            'status' => 'active',
        ]);

        $conflicts = $this->detector->getTimeSlotConflicts($slot1);

        $this->assertCount(1, $conflicts);
        $this->assertEquals('time_overlap', $conflicts[0]['type']);
    }

    public function test_no_conflict_for_non_overlapping_slots(): void
    {
        $gymHall = GymHall::factory()->create();

        $slot1 = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'day_of_week' => 'monday',
            'start_time' => '14:00',
            'end_time' => '15:00',
            'status' => 'active',
        ]);

        GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'day_of_week' => 'monday',
            'start_time' => '16:00',
            'end_time' => '17:00',
            'status' => 'active',
        ]);

        $conflicts = $this->detector->getTimeSlotConflicts($slot1);

        $this->assertEmpty($conflicts);
    }

    public function test_no_conflict_for_different_days(): void
    {
        $gymHall = GymHall::factory()->create();

        $slot1 = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'day_of_week' => 'monday',
            'start_time' => '14:00',
            'end_time' => '16:00',
            'status' => 'active',
        ]);

        GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'day_of_week' => 'tuesday',
            'start_time' => '14:00',
            'end_time' => '16:00',
            'status' => 'active',
        ]);

        $conflicts = $this->detector->getTimeSlotConflicts($slot1);

        $this->assertEmpty($conflicts);
    }

    public function test_validates_time_slot_against_opening_hours(): void
    {
        $gymHall = GymHall::factory()->create([
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);

        // Valid time slot
        $validData = [
            'start_time' => '09:00',
            'end_time' => '11:00',
        ];

        $errors = $this->detector->validateTimeSlot($validData, $gymHall);
        $this->assertEmpty($errors);

        // Invalid time slot - before opening
        $invalidData = [
            'start_time' => '07:00',
            'end_time' => '09:00',
        ];

        $errors = $this->detector->validateTimeSlot($invalidData, $gymHall);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Startzeit', $errors[0]);
    }

    public function test_validates_time_slot_after_closing(): void
    {
        $gymHall = GymHall::factory()->create([
            'opening_time' => '08:00',
            'closing_time' => '22:00',
        ]);

        $invalidData = [
            'start_time' => '21:00',
            'end_time' => '23:00',
        ];

        $errors = $this->detector->validateTimeSlot($invalidData, $gymHall);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Endzeit', $errors[0]);
    }

    public function test_validates_court_selection(): void
    {
        $gymHall = GymHall::factory()->create();
        $court = GymHallCourt::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'is_active' => true,
        ]);

        $date = Carbon::tomorrow();
        $startTime = '10:00';
        $duration = 60;

        $errors = $this->detector->validateCourtSelection(
            $gymHall,
            [$court->id],
            $date,
            $startTime,
            $duration
        );

        $this->assertEmpty($errors);
    }

    public function test_rejects_invalid_court_ids(): void
    {
        $gymHall = GymHall::factory()->create();

        $date = Carbon::tomorrow();
        $startTime = '10:00';
        $duration = 60;

        $errors = $this->detector->validateCourtSelection(
            $gymHall,
            [99999], // Non-existent court ID
            $date,
            $startTime,
            $duration
        );

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('ungültig', $errors[0]);
    }

    // ============================
    // validateCustomTimes Tests (NEW - extracted from model)
    // ============================

    public function test_validate_custom_times_accepts_valid_times(): void
    {
        $customTimes = [
            'monday' => ['start_time' => '08:00', 'end_time' => '20:00'],
            'tuesday' => ['start_time' => '09:00', 'end_time' => '21:00'],
        ];

        $errors = $this->detector->validateCustomTimes($customTimes);

        $this->assertEmpty($errors);
    }

    public function test_validate_custom_times_rejects_invalid_day(): void
    {
        $customTimes = [
            'invalid_day' => ['start_time' => '08:00', 'end_time' => '20:00'],
        ];

        $errors = $this->detector->validateCustomTimes($customTimes);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Ungültiger Wochentag', $errors[0]);
    }

    public function test_validate_custom_times_rejects_invalid_time_format(): void
    {
        $customTimes = [
            'monday' => ['start_time' => '25:00', 'end_time' => '20:00'],
        ];

        $errors = $this->detector->validateCustomTimes($customTimes);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Ungültiges Startzeit-Format', $errors[0]);
    }

    public function test_validate_custom_times_rejects_start_after_end(): void
    {
        $customTimes = [
            'monday' => ['start_time' => '20:00', 'end_time' => '08:00'],
        ];

        $errors = $this->detector->validateCustomTimes($customTimes);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Startzeit muss vor Endzeit liegen', $errors[0]);
    }

    public function test_validate_custom_times_rejects_duration_too_long(): void
    {
        $customTimes = [
            'monday' => ['start_time' => '00:00', 'end_time' => '23:59'],
        ];

        $errors = $this->detector->validateCustomTimes($customTimes);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('zu lang', $errors[0]);
    }

    public function test_validate_custom_times_rejects_duration_too_short(): void
    {
        $customTimes = [
            'monday' => ['start_time' => '10:00', 'end_time' => '10:15'],
        ];

        $errors = $this->detector->validateCustomTimes($customTimes);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Mindestöffnungszeit', $errors[0]);
    }

    public function test_validate_custom_times_rejects_missing_structure(): void
    {
        $customTimes = [
            'monday' => ['start_time' => '08:00'], // Missing end_time
        ];

        $errors = $this->detector->validateCustomTimes($customTimes);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Ungültige Zeitstruktur', $errors[0]);
    }

    // ============================
    // hasOverlappingSlots Tests (NEW - extracted from model)
    // ============================

    public function test_has_overlapping_slots_excludes_specified_ids(): void
    {
        $gymHall = GymHall::factory()->create();

        $existingSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'start_time' => '10:00',
            'end_time' => '12:00',
            'uses_custom_times' => false,
        ]);

        // Same times as existing - would overlap if not excluded
        $newCustomTimes = [
            'monday' => ['start_time' => '10:00', 'end_time' => '12:00'],
        ];

        // Exclude the existing slot from conflict checking
        $conflicts = $this->detector->hasOverlappingSlots(
            $gymHall->id,
            $newCustomTimes,
            $existingSlot->id
        );

        $this->assertEmpty($conflicts);
    }

    public function test_has_overlapping_slots_handles_array_of_excluded_ids(): void
    {
        $gymHall = GymHall::factory()->create();

        $slot1 = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'start_time' => '10:00',
            'end_time' => '12:00',
            'uses_custom_times' => false,
        ]);

        $slot2 = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'start_time' => '14:00',
            'end_time' => '16:00',
            'uses_custom_times' => false,
        ]);

        $newCustomTimes = [
            'monday' => ['start_time' => '10:00', 'end_time' => '16:00'],
        ];

        // Exclude both slots
        $conflicts = $this->detector->hasOverlappingSlots(
            $gymHall->id,
            $newCustomTimes,
            [$slot1->id, $slot2->id]
        );

        $this->assertEmpty($conflicts);
    }

    // ============================
    // validateBookingTimeForSlot Tests (NEW - extracted from model)
    // ============================

    public function test_validate_booking_time_for_slot_rejects_invalid_time_format(): void
    {
        $gymHall = GymHall::factory()->create();
        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
        ]);

        $date = Carbon::now()->next('monday');

        $errors = $this->detector->validateBookingTimeForSlot(
            $timeSlot,
            $date,
            'invalid',
            '12:00'
        );

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Ungültiges Zeitformat', $errors[0]);
    }

    // ============================
    // getConflictingBookings Tests (NEW - extracted from model)
    // ============================

    public function test_get_conflicting_bookings_returns_empty_when_no_conflicts(): void
    {
        $gymHall = GymHall::factory()->create();
        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'start_time' => '08:00',
            'end_time' => '20:00',
            'uses_custom_times' => false,
        ]);

        $newCustomTimes = [
            'monday' => ['start_time' => '08:00', 'end_time' => '20:00'],
        ];

        $conflicts = $this->detector->getConflictingBookings($timeSlot, $newCustomTimes);

        $this->assertEmpty($conflicts);
    }

    // ============================
    // canAssignTeamToSegment Tests (NEW - extracted from model)
    // ============================

    public function test_can_assign_team_to_segment_validates_minimum_duration(): void
    {
        $gymHall = GymHall::factory()->create([
            'supports_parallel_bookings' => true,
            'max_parallel_teams' => 3,
        ]);
        $team = \App\Models\Team::factory()->create();
        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'start_time' => '08:00',
            'end_time' => '20:00',
            'uses_custom_times' => false,
        ]);

        // Duration less than 30 minutes
        $errors = $this->detector->canAssignTeamToSegment(
            $timeSlot,
            $team->id,
            'monday',
            '10:00',
            '10:15'
        );

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Minimale Buchungsdauer', $errors[0]);
    }

    public function test_can_assign_team_to_segment_validates_30_minute_increments(): void
    {
        $gymHall = GymHall::factory()->create([
            'supports_parallel_bookings' => true,
            'max_parallel_teams' => 3,
        ]);
        $team = \App\Models\Team::factory()->create();
        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'start_time' => '08:00',
            'end_time' => '20:00',
            'uses_custom_times' => false,
        ]);

        // Duration not in 30-minute increments (45 minutes)
        $errors = $this->detector->canAssignTeamToSegment(
            $timeSlot,
            $team->id,
            'monday',
            '10:00',
            '10:45'
        );

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('30-Minuten-Schritten', $errors[0]);
    }

    public function test_can_assign_team_to_segment_validates_time_format(): void
    {
        $gymHall = GymHall::factory()->create();
        $team = \App\Models\Team::factory()->create();
        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
        ]);

        $errors = $this->detector->canAssignTeamToSegment(
            $timeSlot,
            $team->id,
            'monday',
            'invalid',
            '12:00'
        );

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Ungültiges Zeitformat', $errors[0]);
    }

    public function test_can_assign_team_to_segment_checks_operating_hours(): void
    {
        $gymHall = GymHall::factory()->create([
            'supports_parallel_bookings' => true,
            'max_parallel_teams' => 3,
        ]);
        $team = \App\Models\Team::factory()->create();
        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'start_time' => '10:00',
            'end_time' => '18:00',
            'uses_custom_times' => false,
        ]);

        // Time outside operating hours
        $errors = $this->detector->canAssignTeamToSegment(
            $timeSlot,
            $team->id,
            'monday',
            '08:00', // Before 10:00
            '09:00'
        );

        // Should have error about operating hours or minimum duration
        $this->assertNotEmpty($errors);
    }
}
