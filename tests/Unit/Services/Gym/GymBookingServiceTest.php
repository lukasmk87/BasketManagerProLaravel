<?php

namespace Tests\Unit\Services\Gym;

use App\Models\Club;
use App\Models\GymBooking;
use App\Models\GymHall;
use App\Models\GymTimeSlot;
use App\Models\Team;
use App\Models\User;
use App\Services\Gym\GymBookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GymBookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private GymBookingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GymBookingService();
    }

    public function test_can_check_user_release_permission(): void
    {
        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);
        $trainer = User::factory()->create();

        // Attach trainer to team
        $team->users()->attach($trainer->id, ['role' => 'trainer']);

        $gymHall = GymHall::factory()->create(['club_id' => $club->id]);
        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'team_id' => $team->id,
        ]);

        $booking = GymBooking::factory()->create([
            'gym_time_slot_id' => $timeSlot->id,
            'team_id' => $team->id,
            'status' => 'confirmed',
            'booking_date' => Carbon::tomorrow(),
        ]);

        $canRelease = $this->service->canUserReleaseBooking($booking, $trainer);

        $this->assertTrue($canRelease);
    }

    public function test_denies_release_for_non_trainer(): void
    {
        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);
        $player = User::factory()->create();

        // Attach as player, not trainer
        $team->users()->attach($player->id, ['role' => 'player']);

        $gymHall = GymHall::factory()->create(['club_id' => $club->id]);
        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'team_id' => $team->id,
        ]);

        $booking = GymBooking::factory()->create([
            'gym_time_slot_id' => $timeSlot->id,
            'team_id' => $team->id,
            'status' => 'confirmed',
            'booking_date' => Carbon::tomorrow(),
        ]);

        $canRelease = $this->service->canUserReleaseBooking($booking, $player);

        $this->assertFalse($canRelease);
    }

    public function test_can_check_user_cancel_permission(): void
    {
        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);
        $trainer = User::factory()->create();

        $team->users()->attach($trainer->id, ['role' => 'trainer']);

        $gymHall = GymHall::factory()->create(['club_id' => $club->id]);
        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'team_id' => $team->id,
        ]);

        $booking = GymBooking::factory()->create([
            'gym_time_slot_id' => $timeSlot->id,
            'team_id' => $team->id,
            'status' => 'confirmed',
            'booking_date' => Carbon::tomorrow(),
        ]);

        $canCancel = $this->service->canUserCancelBooking($booking, $trainer);

        $this->assertTrue($canCancel);
    }

    public function test_user_who_booked_can_cancel(): void
    {
        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);
        $booker = User::factory()->create();

        $gymHall = GymHall::factory()->create(['club_id' => $club->id]);
        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'team_id' => $team->id,
        ]);

        $booking = GymBooking::factory()->create([
            'gym_time_slot_id' => $timeSlot->id,
            'team_id' => $team->id,
            'booked_by_user_id' => $booker->id,
            'status' => 'confirmed',
            'booking_date' => Carbon::tomorrow(),
        ]);

        $canCancel = $this->service->canUserCancelBooking($booking, $booker);

        $this->assertTrue($canCancel);
    }

    public function test_gets_available_slots_for_team(): void
    {
        $club = Club::factory()->create();
        $team1 = Team::factory()->create(['club_id' => $club->id]);
        $team2 = Team::factory()->create(['club_id' => $club->id]);

        $gymHall = GymHall::factory()->create(['club_id' => $club->id]);
        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'team_id' => $team1->id,
        ]);

        // Create released booking from team1
        GymBooking::factory()->create([
            'gym_time_slot_id' => $timeSlot->id,
            'team_id' => $team1->id,
            'original_team_id' => $team1->id,
            'status' => 'released',
            'booking_date' => Carbon::tomorrow(),
        ]);

        $startDate = Carbon::today();
        $endDate = Carbon::today()->addWeek();

        $availableSlots = $this->service->getAvailableTimeSlotsForTeam($team2, $startDate, $endDate);

        $this->assertCount(1, $availableSlots);
    }

    public function test_excludes_own_releases_from_available_slots(): void
    {
        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);

        $gymHall = GymHall::factory()->create(['club_id' => $club->id]);
        $timeSlot = GymTimeSlot::factory()->create([
            'gym_hall_id' => $gymHall->id,
            'team_id' => $team->id,
        ]);

        // Create released booking from same team
        GymBooking::factory()->create([
            'gym_time_slot_id' => $timeSlot->id,
            'team_id' => $team->id,
            'original_team_id' => $team->id,
            'status' => 'released',
            'booking_date' => Carbon::tomorrow(),
        ]);

        $startDate = Carbon::today();
        $endDate = Carbon::today()->addWeek();

        $availableSlots = $this->service->getAvailableTimeSlotsForTeam($team, $startDate, $endDate);

        // Should not include own releases
        $this->assertCount(0, $availableSlots);
    }
}
