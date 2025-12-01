<?php

namespace Tests\Unit\ValueObjects\Game;

use App\ValueObjects\Game\GameSchedule;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class GameScheduleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2024-06-15 14:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_create_empty_schedule(): void
    {
        $schedule = GameSchedule::create();

        $this->assertNull($schedule->scheduledAt());
        $this->assertNull($schedule->actualStartTime());
        $this->assertNull($schedule->actualEndTime());
    }

    public function test_for_scheduled_game(): void
    {
        $scheduledAt = Carbon::parse('2024-06-20 18:00:00');
        $schedule = GameSchedule::forScheduledGame($scheduledAt);

        $this->assertEquals($scheduledAt, $schedule->scheduledAt());
        $this->assertTrue($schedule->isScheduled());
    }

    public function test_from_array(): void
    {
        $schedule = GameSchedule::fromArray([
            'scheduled_at' => '2024-06-20 18:00:00',
            'actual_start_time' => '2024-06-20 18:05:00',
            'actual_end_time' => '2024-06-20 20:00:00',
            'registration_deadline_hours' => 48,
            'lineup_deadline_hours' => 4,
        ]);

        $this->assertNotNull($schedule->scheduledAt());
        $this->assertNotNull($schedule->actualStartTime());
        $this->assertNotNull($schedule->actualEndTime());
        $this->assertEquals(48, $schedule->registrationDeadlineHours());
    }

    public function test_is_upcoming(): void
    {
        $future = GameSchedule::forScheduledGame(Carbon::parse('2024-06-20 18:00:00'));
        $past = GameSchedule::forScheduledGame(Carbon::parse('2024-06-10 18:00:00'));

        $this->assertTrue($future->isUpcoming());
        $this->assertFalse($past->isUpcoming());
    }

    public function test_is_past(): void
    {
        $past = GameSchedule::forScheduledGame(Carbon::parse('2024-06-10 18:00:00'));
        $future = GameSchedule::forScheduledGame(Carbon::parse('2024-06-20 18:00:00'));

        $this->assertTrue($past->isPast());
        $this->assertFalse($future->isPast());
    }

    public function test_is_today(): void
    {
        $today = GameSchedule::forScheduledGame(Carbon::parse('2024-06-15 18:00:00'));
        $tomorrow = GameSchedule::forScheduledGame(Carbon::parse('2024-06-16 18:00:00'));

        $this->assertTrue($today->isToday());
        $this->assertFalse($tomorrow->isToday());
    }

    public function test_has_started_and_ended(): void
    {
        $notStarted = GameSchedule::create();
        $started = GameSchedule::fromArray([
            'actual_start_time' => '2024-06-15 12:00:00',
        ]);
        $ended = GameSchedule::fromArray([
            'actual_start_time' => '2024-06-15 10:00:00',
            'actual_end_time' => '2024-06-15 12:00:00',
        ]);

        $this->assertFalse($notStarted->hasStarted());
        $this->assertTrue($started->hasStarted());
        $this->assertFalse($started->hasEnded());
        $this->assertTrue($ended->hasEnded());
    }

    public function test_duration_minutes(): void
    {
        $schedule = GameSchedule::fromArray([
            'actual_start_time' => '2024-06-15 18:00:00',
            'actual_end_time' => '2024-06-15 20:30:00',
        ]);

        $this->assertEquals(150, $schedule->durationMinutes());
    }

    public function test_delay_minutes(): void
    {
        $schedule = GameSchedule::fromArray([
            'scheduled_at' => '2024-06-15 18:00:00',
            'actual_start_time' => '2024-06-15 18:15:00',
        ]);

        $this->assertEquals(15, $schedule->delayMinutes());
        $this->assertTrue($schedule->wasDelayed());
    }

    public function test_registration_deadline(): void
    {
        $schedule = GameSchedule::fromArray([
            'scheduled_at' => '2024-06-20 18:00:00',
            'registration_deadline_hours' => 24,
        ]);

        $deadline = $schedule->registrationDeadline();
        $this->assertEquals('2024-06-19 18:00:00', $deadline->format('Y-m-d H:i:s'));
    }

    public function test_lineup_deadline(): void
    {
        $schedule = GameSchedule::fromArray([
            'scheduled_at' => '2024-06-20 18:00:00',
            'lineup_deadline_hours' => 2,
        ]);

        $deadline = $schedule->lineupDeadline();
        $this->assertEquals('2024-06-20 16:00:00', $deadline->format('Y-m-d H:i:s'));
    }

    public function test_is_registration_open(): void
    {
        // Scheduled in 5 days with 24h deadline = registration still open
        $open = GameSchedule::fromArray([
            'scheduled_at' => '2024-06-20 18:00:00',
            'registration_deadline_hours' => 24,
        ]);

        // Scheduled in 12 hours with 24h deadline = registration closed
        $closed = GameSchedule::fromArray([
            'scheduled_at' => '2024-06-16 02:00:00',
            'registration_deadline_hours' => 24,
        ]);

        $this->assertTrue($open->isRegistrationOpen());
        $this->assertFalse($closed->isRegistrationOpen());
    }

    public function test_can_start_game(): void
    {
        // Can start 30 min before scheduled time
        $canStart = GameSchedule::forScheduledGame(Carbon::parse('2024-06-15 14:20:00'));
        $tooEarly = GameSchedule::forScheduledGame(Carbon::parse('2024-06-15 15:00:00'));

        $this->assertTrue($canStart->canStartGame());
        $this->assertFalse($tooEarly->canStartGame());
    }

    public function test_formatted_schedule(): void
    {
        $schedule = GameSchedule::forScheduledGame(Carbon::parse('2024-06-20 18:00:00'));

        $this->assertEquals('20.06.2024 18:00', $schedule->formattedScheduledAt());
        $this->assertEquals('20.06.2024', $schedule->formattedDate());
        $this->assertEquals('18:00', $schedule->formattedTime());
        $this->assertEquals('Thursday', $schedule->dayOfWeek());
        $this->assertEquals('Donnerstag', $schedule->dayOfWeekGerman());
    }

    public function test_formatted_duration(): void
    {
        $short = GameSchedule::fromArray([
            'actual_start_time' => '2024-06-15 18:00:00',
            'actual_end_time' => '2024-06-15 18:45:00',
        ]);

        $long = GameSchedule::fromArray([
            'actual_start_time' => '2024-06-15 18:00:00',
            'actual_end_time' => '2024-06-15 20:30:00',
        ]);

        $this->assertEquals('45 min', $short->formattedDuration());
        $this->assertEquals('2:30 h', $long->formattedDuration());
    }

    public function test_with_actual_start_is_immutable(): void
    {
        $original = GameSchedule::forScheduledGame(Carbon::parse('2024-06-20 18:00:00'));
        $updated = $original->withActualStart(Carbon::parse('2024-06-20 18:05:00'));

        $this->assertNull($original->actualStartTime());
        $this->assertNotNull($updated->actualStartTime());
    }

    public function test_with_rescheduled(): void
    {
        $original = GameSchedule::fromArray([
            'scheduled_at' => '2024-06-20 18:00:00',
            'actual_start_time' => '2024-06-20 18:05:00',
        ]);
        $rescheduled = $original->withRescheduled(Carbon::parse('2024-06-25 19:00:00'));

        $this->assertEquals('2024-06-25 19:00:00', $rescheduled->scheduledAt()->format('Y-m-d H:i:s'));
        $this->assertNull($rescheduled->actualStartTime());
    }

    public function test_to_array(): void
    {
        $schedule = GameSchedule::forScheduledGame(Carbon::parse('2024-06-20 18:00:00'));
        $array = $schedule->toArray();

        $this->assertArrayHasKey('scheduled_at', $array);
        $this->assertArrayHasKey('is_upcoming', $array);
        $this->assertArrayHasKey('is_past', $array);
        $this->assertArrayHasKey('registration_deadline', $array);
        $this->assertArrayHasKey('lineup_deadline', $array);
    }
}
