<?php

namespace Tests\Unit\Services\Gym;

use App\Models\Club;
use App\Models\GymHall;
use App\Models\GymHallCourt;
use App\Models\GymTimeSlot;
use App\Models\Team;
use App\Models\User;
use App\Services\Gym\GymConflictDetector;
use App\Services\Gym\GymScheduleOptimizer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GymScheduleOptimizerTest extends TestCase
{
    use RefreshDatabase;

    private GymScheduleOptimizer $optimizer;

    protected function setUp(): void
    {
        parent::setUp();
        $conflictDetector = new GymConflictDetector();
        $this->optimizer = new GymScheduleOptimizer($conflictDetector);
    }

    public function test_creates_time_slot_for_gym_hall(): void
    {
        $club = Club::factory()->create();
        $gymHall = GymHall::factory()->create(['club_id' => $club->id]);

        $data = [
            'title' => 'Training U16',
            'day_of_week' => 'monday',
            'start_time' => '16:00',
            'end_time' => '18:00',
            'slot_type' => 'training',
            'is_recurring' => false,
            'valid_from' => now()->toDateString(),
        ];

        $timeSlot = $this->optimizer->createTimeSlot($gymHall, $data);

        $this->assertInstanceOf(GymTimeSlot::class, $timeSlot);
        $this->assertEquals('Training U16', $timeSlot->title);
        $this->assertEquals('monday', $timeSlot->day_of_week);
        $this->assertEquals($gymHall->id, $timeSlot->gym_hall_id);
    }

    public function test_assigns_time_slot_to_team(): void
    {
        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);
        $user = User::factory()->create();

        $gymHall = GymHall::factory()->create(['club_id' => $club->id]);
        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'is_recurring' => false,
        ]);

        $result = $this->optimizer->assignTimeSlotToTeam($timeSlot, $team, $user, 'Regular training');

        $this->assertTrue($result);
        $timeSlot->refresh();
        $this->assertEquals($team->id, $timeSlot->team_id);
    }

    public function test_generates_daily_time_grid(): void
    {
        $gymHall = GymHall::factory()->create([
            'operating_hours' => [
                'monday' => [
                    'is_open' => true,
                    'open_time' => '08:00',
                    'close_time' => '22:00',
                ],
            ],
        ]);

        $date = Carbon::parse('next monday');
        $grid = $this->optimizer->generateDailyTimeGrid($gymHall, $date, 30);

        $this->assertIsArray($grid);
    }

    public function test_finds_available_slots(): void
    {
        $gymHall = GymHall::factory()->create([
            'supports_parallel_bookings' => true,
            'court_count' => 2,
            'booking_increment' => 30,
            'operating_hours' => [
                'monday' => [
                    'is_open' => true,
                    'open_time' => '08:00',
                    'close_time' => '22:00',
                ],
            ],
        ]);

        // Create courts
        GymHallCourt::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'is_active' => true,
        ]);
        GymHallCourt::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'is_active' => true,
        ]);

        $date = Carbon::parse('next monday');
        $availableSlots = $this->optimizer->findAvailableSlots($gymHall, $date, 60, 1);

        $this->assertIsArray($availableSlots);
    }

    public function test_returns_empty_for_closed_day(): void
    {
        $gymHall = GymHall::factory()->create([
            'operating_hours' => [
                'monday' => [
                    'is_open' => false,
                ],
            ],
        ]);

        $date = Carbon::parse('next monday');
        $availableSlots = $this->optimizer->findAvailableSlots($gymHall, $date, 30, 1);

        $this->assertEmpty($availableSlots);
    }

    public function test_returns_empty_for_parallel_booking_without_support(): void
    {
        $gymHall = GymHall::factory()->create([
            'supports_parallel_bookings' => false,
            'operating_hours' => [
                'monday' => [
                    'is_open' => true,
                    'open_time' => '08:00',
                    'close_time' => '22:00',
                ],
            ],
        ]);

        $date = Carbon::parse('next monday');
        // Request slots for 2 teams on a hall that doesn't support parallel bookings
        $availableSlots = $this->optimizer->findAvailableSlots($gymHall, $date, 30, 2);

        $this->assertEmpty($availableSlots);
    }

    public function test_gets_court_schedule_for_date_range(): void
    {
        $gymHall = GymHall::factory()->create([
            'operating_hours' => [
                'monday' => [
                    'is_open' => true,
                    'open_time' => '08:00',
                    'close_time' => '22:00',
                ],
                'tuesday' => [
                    'is_open' => true,
                    'open_time' => '08:00',
                    'close_time' => '22:00',
                ],
            ],
        ]);

        $startDate = Carbon::parse('next monday');
        $endDate = $startDate->copy()->addDays(2);

        $schedule = $this->optimizer->getCourtSchedule($gymHall, $startDate, $endDate);

        $this->assertIsArray($schedule);
        $this->assertCount(3, $schedule); // 3 days
    }

    public function test_gets_optimal_court_assignments(): void
    {
        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);

        $gymHall = GymHall::factory()->create([
            'club_id' => $club->id,
            'operating_hours' => [
                'monday' => [
                    'is_open' => true,
                    'open_time' => '08:00',
                    'close_time' => '22:00',
                ],
            ],
        ]);

        GymHallCourt::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'is_active' => true,
        ]);

        GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'team_id' => $team->id,
            'day_of_week' => 'monday',
            'start_time' => '14:00',
            'end_time' => '16:00',
            'status' => 'active',
            'valid_from' => now()->subMonth(),
            'valid_until' => null,
        ]);

        $date = Carbon::parse('next monday');
        $assignments = $this->optimizer->getOptimalCourtAssignments($gymHall, $date, 30);

        $this->assertIsArray($assignments);
    }
}
