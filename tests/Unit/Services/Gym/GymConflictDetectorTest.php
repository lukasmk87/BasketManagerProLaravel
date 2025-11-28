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
}
