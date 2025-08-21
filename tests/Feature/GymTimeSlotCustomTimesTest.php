<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\GymHall;
use App\Models\GymTimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GymTimeSlotCustomTimesTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $club;
    protected $gymHall;

    protected function setUp(): void
    {
        parent::setUp();

        $this->club = Club::factory()->create([
            'name' => 'Test Club',
        ]);

        $this->user = User::factory()->create();
        $this->user->assignRole('club_admin');
        
        $this->gymHall = GymHall::factory()->create([
            'club_id' => $this->club->id,
            'name' => 'Test Hall',
        ]);
    }

    /** @test */
    public function it_can_create_time_slot_with_custom_times()
    {
        $customTimes = [
            'monday' => ['start_time' => '08:00', 'end_time' => '20:00'],
            'tuesday' => ['start_time' => '09:00', 'end_time' => '21:00'],
            'wednesday' => ['start_time' => '08:00', 'end_time' => '20:00'],
        ];

        $timeSlot = GymTimeSlot::create([
            'uuid' => \Str::uuid(),
            'gym_hall_id' => $this->gymHall->id,
            'title' => 'Custom Opening Hours',
            'uses_custom_times' => true,
            'custom_times' => $customTimes,
            'slot_type' => 'training',
            'valid_from' => now()->toDateString(),
            'status' => 'active',
            'is_recurring' => true,
        ]);

        $this->assertTrue($timeSlot->uses_custom_times);
        $this->assertEquals($customTimes, $timeSlot->custom_times);

        // Test getTimesForDay method
        $mondayTimes = $timeSlot->getTimesForDay('monday');
        $this->assertEquals('08:00', $mondayTimes['start_time']);
        $this->assertEquals('20:00', $mondayTimes['end_time']);

        // Test day without custom times
        $sundayTimes = $timeSlot->getTimesForDay('sunday');
        $this->assertNull($sundayTimes);
    }

    /** @test */
    public function it_can_validate_custom_times()
    {
        $validTimes = [
            'monday' => ['start_time' => '08:00', 'end_time' => '20:00'],
            'tuesday' => ['start_time' => '09:00', 'end_time' => '21:00'],
        ];

        $errors = GymTimeSlot::validateCustomTimes($validTimes);
        $this->assertEmpty($errors);

        $invalidTimes = [
            'monday' => ['start_time' => '20:00', 'end_time' => '08:00'], // End before start
            'tuesday' => ['start_time' => '09:00', 'end_time' => '09:15'], // Too short (less than 30 min)
            'invalidday' => ['start_time' => '08:00', 'end_time' => '20:00'], // Invalid day
        ];

        $errors = GymTimeSlot::validateCustomTimes($invalidTimes);
        $this->assertNotEmpty($errors);
        $this->assertContains('Startzeit muss vor Endzeit liegen für monday', $errors);
        $this->assertContains('Mindestöffnungszeit von 30 Minuten für tuesday unterschritten', $errors);
        $this->assertContains('Ungültiger Wochentag: invalidday', $errors);
    }

    /** @test */
    public function it_can_detect_overlapping_slots()
    {
        // Create first time slot
        $timeSlot1 = GymTimeSlot::create([
            'uuid' => \Str::uuid(),
            'gym_hall_id' => $this->gymHall->id,
            'title' => 'First Slot',
            'uses_custom_times' => true,
            'custom_times' => [
                'monday' => ['start_time' => '08:00', 'end_time' => '12:00'],
            ],
            'slot_type' => 'training',
            'valid_from' => now()->toDateString(),
            'status' => 'active',
            'is_recurring' => true,
        ]);

        // Test overlapping times
        $overlappingTimes = [
            'monday' => ['start_time' => '10:00', 'end_time' => '14:00'], // Overlaps with first slot
        ];

        $conflicts = GymTimeSlot::hasOverlappingSlots($this->gymHall->id, $overlappingTimes);
        $this->assertNotEmpty($conflicts);
        $this->assertEquals('monday', $conflicts[0]['day']);
        $this->assertEquals('First Slot', $conflicts[0]['existing_slot_title']);

        // Test non-overlapping times
        $nonOverlappingTimes = [
            'monday' => ['start_time' => '14:00', 'end_time' => '18:00'], // No overlap
        ];

        $conflicts = GymTimeSlot::hasOverlappingSlots($this->gymHall->id, $nonOverlappingTimes);
        $this->assertEmpty($conflicts);
    }

    /** @test */
    public function it_can_get_all_day_times()
    {
        $customTimes = [
            'monday' => ['start_time' => '08:00', 'end_time' => '20:00'],
            'wednesday' => ['start_time' => '09:00', 'end_time' => '21:00'],
            'friday' => ['start_time' => '08:00', 'end_time' => '20:00'],
        ];

        $timeSlot = GymTimeSlot::create([
            'uuid' => \Str::uuid(),
            'gym_hall_id' => $this->gymHall->id,
            'title' => 'Custom Opening Hours',
            'uses_custom_times' => true,
            'custom_times' => $customTimes,
            'slot_type' => 'training',
            'valid_from' => now()->toDateString(),
            'status' => 'active',
            'is_recurring' => true,
        ]);

        $allDayTimes = $timeSlot->getAllDayTimes();

        $this->assertCount(3, $allDayTimes);
        $this->assertArrayHasKey('monday', $allDayTimes);
        $this->assertArrayHasKey('wednesday', $allDayTimes);
        $this->assertArrayHasKey('friday', $allDayTimes);
        $this->assertArrayNotHasKey('tuesday', $allDayTimes);
        $this->assertArrayNotHasKey('sunday', $allDayTimes);

        $this->assertEquals('08:00', $allDayTimes['monday']['start_time']);
        $this->assertEquals('20:00', $allDayTimes['monday']['end_time']);
    }

    /** @test */
    public function it_can_check_availability_on_day_and_time()
    {
        $timeSlot = GymTimeSlot::create([
            'uuid' => \Str::uuid(),
            'gym_hall_id' => $this->gymHall->id,
            'title' => 'Custom Opening Hours',
            'uses_custom_times' => true,
            'custom_times' => [
                'monday' => ['start_time' => '08:00', 'end_time' => '20:00'],
            ],
            'slot_type' => 'training',
            'valid_from' => now()->toDateString(),
            'status' => 'active',
            'is_recurring' => true,
        ]);

        // Test time within availability
        $this->assertTrue($timeSlot->isAvailableOnDayAndTime('monday', '10:00'));
        $this->assertTrue($timeSlot->isAvailableOnDayAndTime('monday', '08:00'));
        $this->assertTrue($timeSlot->isAvailableOnDayAndTime('monday', '20:00'));

        // Test time outside availability
        $this->assertFalse($timeSlot->isAvailableOnDayAndTime('monday', '07:00'));
        $this->assertFalse($timeSlot->isAvailableOnDayAndTime('monday', '21:00'));

        // Test day without availability
        $this->assertFalse($timeSlot->isAvailableOnDayAndTime('tuesday', '10:00'));
    }

    /** @test */
    public function it_can_set_custom_times_for_multiple_days()
    {
        $timeSlot = GymTimeSlot::create([
            'uuid' => \Str::uuid(),
            'gym_hall_id' => $this->gymHall->id,
            'title' => 'Test Slot',
            'slot_type' => 'training',
            'valid_from' => now()->toDateString(),
            'status' => 'active',
            'is_recurring' => true,
        ]);

        $this->assertFalse($timeSlot->uses_custom_times);

        $dayTimes = [
            'monday' => ['start_time' => '08:00', 'end_time' => '18:00'],
            'tuesday' => ['start_time' => '09:00', 'end_time' => '19:00'],
            'wednesday' => ['start_time' => '08:00', 'end_time' => '18:00'],
        ];

        $timeSlot->setCustomTimes($dayTimes);
        $timeSlot->refresh();

        $this->assertTrue($timeSlot->uses_custom_times);
        $this->assertEquals($dayTimes, $timeSlot->custom_times);
    }

    /** @test */
    public function it_returns_formatted_time_range_for_day()
    {
        $timeSlot = GymTimeSlot::create([
            'uuid' => \Str::uuid(),
            'gym_hall_id' => $this->gymHall->id,
            'title' => 'Test Slot',
            'uses_custom_times' => true,
            'custom_times' => [
                'monday' => ['start_time' => '08:00', 'end_time' => '18:00'],
            ],
            'slot_type' => 'training',
            'valid_from' => now()->toDateString(),
            'status' => 'active',
            'is_recurring' => true,
        ]);

        $formattedTime = $timeSlot->getFormattedTimeRangeForDay('monday');
        $this->assertEquals('08:00 - 18:00', $formattedTime);

        $noTime = $timeSlot->getFormattedTimeRangeForDay('sunday');
        $this->assertNull($noTime);
    }

    /** @test */
    public function custom_time_slots_have_null_duration_minutes()
    {
        $timeSlot = GymTimeSlot::create([
            'uuid' => \Str::uuid(),
            'gym_hall_id' => $this->gymHall->id,
            'title' => 'Custom Time Slot',
            'uses_custom_times' => true,
            'custom_times' => [
                'monday' => ['start_time' => '18:00', 'end_time' => '22:00'],
                'tuesday' => ['start_time' => '18:00', 'end_time' => '22:00'],
                'wednesday' => ['start_time' => '18:00', 'end_time' => '22:00'],
            ],
            'slot_type' => 'training',
            'valid_from' => now()->toDateString(),
            'status' => 'active',
            'is_recurring' => true,
        ]);

        // Custom time slots should have null duration_minutes since they have different durations per day
        $this->assertNull($timeSlot->duration_minutes);
        $this->assertTrue($timeSlot->uses_custom_times);
    }

    /** @test */
    public function standard_time_slots_have_calculated_duration_minutes()
    {
        $timeSlot = GymTimeSlot::create([
            'uuid' => \Str::uuid(),
            'gym_hall_id' => $this->gymHall->id,
            'title' => 'Standard Time Slot',
            'day_of_week' => 'monday',
            'start_time' => '18:00',
            'end_time' => '20:00',
            'uses_custom_times' => false,
            'slot_type' => 'training',
            'valid_from' => now()->toDateString(),
            'status' => 'active',
            'is_recurring' => true,
        ]);

        // Standard time slots should have calculated duration_minutes
        $this->assertEquals(120, $timeSlot->duration_minutes); // 2 hours = 120 minutes
        $this->assertFalse($timeSlot->uses_custom_times);
    }
}