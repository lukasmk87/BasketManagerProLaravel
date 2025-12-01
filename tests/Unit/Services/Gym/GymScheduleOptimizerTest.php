<?php

namespace Tests\Unit\Services\Gym;

use App\Models\Club;
use App\Models\GymBooking;
use App\Models\GymHall;
use App\Models\GymHallCourt;
use App\Models\GymTimeSlot;
use App\Models\GymTimeSlotTeamAssignment;
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

    // ============================
    // getAvailableSegmentsForTimeSlot Tests (NEW - extracted from model)
    // ============================

    public function test_get_available_segments_for_time_slot_returns_segments(): void
    {
        $gymHall = GymHall::factory()->create();
        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'day_of_week' => 'monday',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'booking_increment_minutes' => 30,
            'uses_custom_times' => false,
        ]);

        $date = Carbon::parse('next monday');
        $segments = $this->optimizer->getAvailableSegmentsForTimeSlot($timeSlot, $date);

        $this->assertCount(4, $segments); // 2 hours / 30 min = 4 segments
        $this->assertEquals('10:00', $segments[0]['start_time']);
        $this->assertEquals('10:30', $segments[0]['end_time']);
        $this->assertEquals(30, $segments[0]['duration_minutes']);
        $this->assertArrayHasKey('is_available', $segments[0]);
    }

    public function test_get_available_segments_returns_empty_for_wrong_day(): void
    {
        $gymHall = GymHall::factory()->create();
        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'day_of_week' => 'monday',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'uses_custom_times' => false,
        ]);

        // Query for a Tuesday
        $date = Carbon::parse('next tuesday');
        $segments = $this->optimizer->getAvailableSegmentsForTimeSlot($timeSlot, $date);

        $this->assertEmpty($segments);
    }

    public function test_get_available_segments_marks_booked_as_unavailable(): void
    {
        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);
        $user = User::factory()->create();
        $gymHall = GymHall::factory()->create(['club_id' => $club->id]);

        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'day_of_week' => 'monday',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'booking_increment_minutes' => 30,
            'uses_custom_times' => false,
        ]);

        $date = Carbon::parse('next monday');

        // Create a booking for the first segment
        GymBooking::factory()->create([
            'gym_time_slot_id' => $timeSlot->id,
            'team_id' => $team->id,
            'booked_by_user_id' => $user->id,
            'booking_date' => $date,
            'start_time' => '10:00',
            'end_time' => '10:30',
            'status' => 'confirmed',
        ]);

        $segments = $this->optimizer->getAvailableSegmentsForTimeSlot($timeSlot, $date);

        // First segment should be unavailable
        $this->assertFalse($segments[0]['is_available']);
        // Second segment should be available
        $this->assertTrue($segments[1]['is_available']);
    }

    // ============================
    // getAvailableSegmentsForDay Tests (NEW - extracted from model)
    // ============================

    public function test_get_available_segments_for_day_returns_segments_with_team_assignments(): void
    {
        $gymHall = GymHall::factory()->create();
        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'day_of_week' => 'monday',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'uses_custom_times' => false,
        ]);

        $segments = $this->optimizer->getAvailableSegmentsForDay($timeSlot, 'monday', 30);

        $this->assertCount(4, $segments);
        $this->assertArrayHasKey('assigned_teams', $segments[0]);
        $this->assertArrayHasKey('is_available', $segments[0]);
    }

    public function test_get_available_segments_for_day_returns_empty_for_invalid_day(): void
    {
        $gymHall = GymHall::factory()->create();
        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'day_of_week' => 'monday',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'uses_custom_times' => false,
        ]);

        $segments = $this->optimizer->getAvailableSegmentsForDay($timeSlot, 'tuesday', 30);

        $this->assertEmpty($segments);
    }

    // ============================
    // getTimeGridForTimeSlot Tests (NEW - extracted from model)
    // ============================

    public function test_get_time_grid_for_time_slot_returns_grid(): void
    {
        $gymHall = GymHall::factory()->create();
        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'day_of_week' => 'monday',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'booking_increment_minutes' => 30,
            'uses_custom_times' => false,
        ]);

        $grid = $this->optimizer->getTimeGridForTimeSlot($timeSlot, 'monday');

        $this->assertCount(4, $grid); // 2 hours / 30 min = 4 slots
        $this->assertEquals('09:00', $grid[0]['start_time']);
        $this->assertEquals('09:30', $grid[0]['end_time']);
        $this->assertEquals('0900', $grid[0]['time_key']);
    }

    public function test_get_time_grid_returns_empty_for_wrong_day(): void
    {
        $gymHall = GymHall::factory()->create();
        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'day_of_week' => 'monday',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'uses_custom_times' => false,
        ]);

        $grid = $this->optimizer->getTimeGridForTimeSlot($timeSlot, 'wednesday');

        $this->assertEmpty($grid);
    }

    // ============================
    // getTeamsAssignedToSegment Tests (NEW - extracted from model)
    // ============================

    public function test_get_teams_assigned_to_segment_returns_empty_without_assignments(): void
    {
        $gymHall = GymHall::factory()->create();
        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);

        $teams = $this->optimizer->getTeamsAssignedToSegment(
            $timeSlot,
            'monday',
            '10:00',
            '10:30'
        );

        $this->assertEmpty($teams);
    }

    // ============================
    // createBookingForTimeSlot Tests (NEW - extracted from model)
    // ============================

    public function test_create_booking_for_time_slot_creates_booking(): void
    {
        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);
        $user = User::factory()->create();
        $gymHall = GymHall::factory()->create(['club_id' => $club->id]);

        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'start_time' => '14:00',
            'end_time' => '16:00',
            'duration_minutes' => 120,
        ]);

        $date = Carbon::tomorrow();
        $booking = $this->optimizer->createBookingForTimeSlot($timeSlot, $date, $team, $user);

        $this->assertInstanceOf(GymBooking::class, $booking);
        $this->assertEquals($team->id, $booking->team_id);
        $this->assertEquals($user->id, $booking->booked_by_user_id);
        $this->assertEquals($date->toDateString(), $booking->booking_date->toDateString());
        $this->assertEquals('reserved', $booking->status);
    }

    // ============================
    // createFlexibleBooking Tests (NEW - extracted from model)
    // ============================

    public function test_create_flexible_booking_creates_with_custom_times(): void
    {
        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);
        $user = User::factory()->create();
        $gymHall = GymHall::factory()->create([
            'club_id' => $club->id,
            'court_count' => 2,
        ]);

        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'start_time' => '10:00',
            'end_time' => '18:00',
        ]);

        $date = Carbon::tomorrow();
        $booking = $this->optimizer->createFlexibleBooking(
            $timeSlot,
            $date,
            $team,
            $user,
            '14:00',
            60, // 1 hour
            []
        );

        $this->assertInstanceOf(GymBooking::class, $booking);
        $this->assertEquals('14:00', Carbon::parse($booking->start_time)->format('H:i'));
        $this->assertEquals('15:00', Carbon::parse($booking->end_time)->format('H:i'));
        $this->assertEquals(60, $booking->duration_minutes);
    }

    public function test_create_flexible_booking_with_court_selection(): void
    {
        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);
        $user = User::factory()->create();
        $gymHall = GymHall::factory()->create([
            'club_id' => $club->id,
            'court_count' => 2,
        ]);

        $court = GymHallCourt::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'is_active' => true,
        ]);

        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'start_time' => '10:00',
            'end_time' => '18:00',
        ]);

        $date = Carbon::tomorrow();
        $booking = $this->optimizer->createFlexibleBooking(
            $timeSlot,
            $date,
            $team,
            $user,
            '11:00',
            90,
            [$court->id]
        );

        $this->assertInstanceOf(GymBooking::class, $booking);
        $this->assertTrue($booking->is_partial_court);
        $this->assertCount(1, $booking->courts);
    }

    // ============================
    // generateRecurringBookingsForPeriod Tests (NEW - extracted from model)
    // ============================

    public function test_generate_recurring_bookings_returns_zero_for_non_recurring(): void
    {
        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);
        $gymHall = GymHall::factory()->create(['club_id' => $club->id]);

        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'team_id' => $team->id,
            'is_recurring' => false,
        ]);

        $count = $this->optimizer->generateRecurringBookingsForPeriod(
            $timeSlot,
            Carbon::now(),
            Carbon::now()->addMonth()
        );

        $this->assertEquals(0, $count);
    }

    public function test_generate_recurring_bookings_returns_zero_without_team(): void
    {
        $gymHall = GymHall::factory()->create();

        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'team_id' => null,
            'is_recurring' => true,
        ]);

        $count = $this->optimizer->generateRecurringBookingsForPeriod(
            $timeSlot,
            Carbon::now(),
            Carbon::now()->addMonth()
        );

        $this->assertEquals(0, $count);
    }
}
