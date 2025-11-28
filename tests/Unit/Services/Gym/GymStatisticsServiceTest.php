<?php

namespace Tests\Unit\Services\Gym;

use App\Models\Club;
use App\Models\GymBooking;
use App\Models\GymHall;
use App\Models\GymTimeSlot;
use App\Models\Team;
use App\Services\Gym\GymStatisticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GymStatisticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private GymStatisticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GymStatisticsService();
    }

    public function test_gets_club_weekly_schedule(): void
    {
        $club = Club::factory()->create();
        $gymHall = GymHall::factory()->create([
            'club_id' => $club->id,
            'is_active' => true,
        ]);

        GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'day_of_week' => 'monday',
            'status' => 'active',
        ]);

        $weekStart = now()->startOfWeek();
        $schedule = $this->service->getClubWeeklySchedule($club, $weekStart);

        $this->assertIsArray($schedule);
        $this->assertArrayHasKey($gymHall->id, $schedule);
        $this->assertArrayHasKey('gym_hall', $schedule[$gymHall->id]);
        $this->assertArrayHasKey('weekly_schedule', $schedule[$gymHall->id]);
    }

    public function test_gets_club_utilization_stats(): void
    {
        $club = Club::factory()->create();
        $gymHall = GymHall::factory()->create([
            'club_id' => $club->id,
            'is_active' => true,
        ]);

        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
        ]);

        // Create some bookings
        GymBooking::factory()->count(5)->create([
            'gym_time_slot_id' => $timeSlot->id,
            'status' => 'confirmed',
            'booking_date' => now(),
        ]);

        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();

        $stats = $this->service->getClubUtilizationStats($club, $startDate, $endDate);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('overview', $stats);
        $this->assertArrayHasKey('total_halls', $stats['overview']);
        $this->assertArrayHasKey('total_bookings', $stats['overview']);
        $this->assertArrayHasKey('average_utilization', $stats['overview']);
    }

    public function test_gets_team_booking_stats(): void
    {
        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);

        $gymHall = GymHall::factory()->create(['club_id' => $club->id]);
        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'team_id' => $team->id,
        ]);

        // Create bookings for the team
        GymBooking::factory()->count(3)->create([
            'gym_time_slot_id' => $timeSlot->id,
            'team_id' => $team->id,
            'status' => 'confirmed',
            'booking_date' => now(),
        ]);

        GymBooking::factory()->create([
            'gym_time_slot_id' => $timeSlot->id,
            'team_id' => $team->id,
            'status' => 'released',
            'booking_date' => now(),
        ]);

        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();

        $stats = $this->service->getTeamBookingStats($team, $startDate, $endDate);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_bookings', $stats);
        $this->assertArrayHasKey('bookings_by_status', $stats);
        $this->assertArrayHasKey('releases_made', $stats);
        $this->assertEquals(4, $stats['total_bookings']);
        $this->assertEquals(1, $stats['releases_made']);
    }

    public function test_processes_past_bookings(): void
    {
        $gymHall = GymHall::factory()->create();
        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
        ]);

        // Create past bookings with reserved status
        GymBooking::factory()->count(3)->create([
            'gym_time_slot_id' => $timeSlot->id,
            'status' => 'reserved',
            'booking_date' => now()->subDays(2),
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);

        $results = $this->service->processPastBookings();

        $this->assertIsArray($results);
        $this->assertArrayHasKey('completed', $results);
        $this->assertArrayHasKey('no_show', $results);
        $this->assertEquals(3, $results['completed']);
    }

    public function test_handles_empty_club_schedule(): void
    {
        $club = Club::factory()->create();

        $schedule = $this->service->getClubWeeklySchedule($club);

        $this->assertIsArray($schedule);
        $this->assertEmpty($schedule);
    }

    public function test_handles_club_with_no_active_halls(): void
    {
        $club = Club::factory()->create();

        // Create inactive hall
        GymHall::factory()->create([
            'club_id' => $club->id,
            'is_active' => false,
        ]);

        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();

        $stats = $this->service->getClubUtilizationStats($club, $startDate, $endDate);

        $this->assertIsArray($stats);
        $this->assertEquals(0, $stats['overview']['total_halls']);
        $this->assertEquals(0, $stats['overview']['average_utilization']);
    }

    public function test_team_stats_with_no_bookings(): void
    {
        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);

        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();

        $stats = $this->service->getTeamBookingStats($team, $startDate, $endDate);

        $this->assertIsArray($stats);
        $this->assertEquals(0, $stats['total_bookings']);
        $this->assertEquals(0, $stats['releases_made']);
        $this->assertEquals(0.0, $stats['average_utilization']);
    }
}
