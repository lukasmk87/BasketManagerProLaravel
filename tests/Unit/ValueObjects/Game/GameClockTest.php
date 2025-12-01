<?php

namespace Tests\Unit\ValueObjects\Game;

use App\ValueObjects\Game\GameClock;
use PHPUnit\Framework\TestCase;

class GameClockTest extends TestCase
{
    public function test_create_default_clock(): void
    {
        $clock = GameClock::create();

        $this->assertNull($clock->timeRemainingSeconds());
        $this->assertEquals(1, $clock->currentPeriod());
        $this->assertFalse($clock->isClockRunning());
    }

    public function test_for_new_game(): void
    {
        $clock = GameClock::forNewGame(4, 10);

        $this->assertEquals(600, $clock->timeRemainingSeconds()); // 10 min * 60 sec
        $this->assertEquals(1, $clock->currentPeriod());
        $this->assertFalse($clock->isClockRunning());
        $this->assertEquals(4, $clock->totalPeriods());
    }

    public function test_from_array(): void
    {
        $clock = GameClock::fromArray([
            'time_remaining_seconds' => 120,
            'current_period' => 3,
            'clock_running' => true,
            'total_periods' => 4,
            'period_length_minutes' => 10,
            'overtime_periods' => 0,
        ]);

        $this->assertEquals(120, $clock->timeRemainingSeconds());
        $this->assertEquals(3, $clock->currentPeriod());
        $this->assertTrue($clock->isClockRunning());
    }

    public function test_formatted_time_remaining(): void
    {
        $clock = GameClock::create(timeRemainingSeconds: 125);

        $this->assertEquals('02:05', $clock->formattedTimeRemaining());
    }

    public function test_formatted_time_remaining_null(): void
    {
        $clock = GameClock::create();

        $this->assertEquals('00:00', $clock->formattedTimeRemaining());
    }

    public function test_is_regulation_time(): void
    {
        $clock = GameClock::fromArray([
            'current_period' => 3,
            'total_periods' => 4,
        ]);

        $this->assertTrue($clock->isRegulationTime());
        $this->assertFalse($clock->isOvertime());
    }

    public function test_is_overtime(): void
    {
        $clock = GameClock::fromArray([
            'current_period' => 5,
            'total_periods' => 4,
            'overtime_periods' => 1,
        ]);

        $this->assertFalse($clock->isRegulationTime());
        $this->assertTrue($clock->isOvertime());
        $this->assertTrue($clock->wentToOvertime());
    }

    public function test_period_display_regular(): void
    {
        $clock = GameClock::fromArray([
            'current_period' => 2,
            'total_periods' => 4,
        ]);

        $this->assertEquals('Q2', $clock->periodDisplay());
    }

    public function test_period_display_overtime(): void
    {
        $clock = GameClock::fromArray([
            'current_period' => 5,
            'total_periods' => 4,
        ]);

        $this->assertEquals('OT1', $clock->periodDisplay());
    }

    public function test_with_started_clock(): void
    {
        $clock = GameClock::forNewGame();
        $started = $clock->withStartedClock();

        $this->assertFalse($clock->isClockRunning());
        $this->assertTrue($started->isClockRunning());
    }

    public function test_with_stopped_clock(): void
    {
        $clock = GameClock::forNewGame()->withStartedClock();
        $stopped = $clock->withStoppedClock();

        $this->assertTrue($clock->isClockRunning());
        $this->assertFalse($stopped->isClockRunning());
    }

    public function test_with_decremented_time(): void
    {
        $clock = GameClock::create(timeRemainingSeconds: 600);
        $updated = $clock->withDecrementedTime(30);

        $this->assertEquals(600, $clock->timeRemainingSeconds());
        $this->assertEquals(570, $updated->timeRemainingSeconds());
    }

    public function test_with_decremented_time_does_not_go_negative(): void
    {
        $clock = GameClock::create(timeRemainingSeconds: 10);
        $updated = $clock->withDecrementedTime(30);

        $this->assertEquals(0, $updated->timeRemainingSeconds());
    }

    public function test_with_next_period(): void
    {
        $clock = GameClock::fromArray([
            'current_period' => 1,
            'total_periods' => 4,
            'period_length_minutes' => 10,
        ]);

        $nextPeriod = $clock->withNextPeriod();

        $this->assertEquals(1, $clock->currentPeriod());
        $this->assertEquals(2, $nextPeriod->currentPeriod());
        $this->assertEquals(600, $nextPeriod->timeRemainingSeconds());
    }

    public function test_with_next_period_to_overtime(): void
    {
        $clock = GameClock::fromArray([
            'current_period' => 4,
            'total_periods' => 4,
            'overtime_length_minutes' => 5,
        ]);

        $overtime = $clock->withNextPeriod();

        $this->assertEquals(5, $overtime->currentPeriod());
        $this->assertTrue($overtime->isOvertime());
        $this->assertEquals(300, $overtime->timeRemainingSeconds()); // 5 min
        $this->assertEquals(1, $overtime->overtimePeriods());
    }

    public function test_periods_remaining(): void
    {
        $clock = GameClock::fromArray([
            'current_period' => 2,
            'total_periods' => 4,
        ]);

        $this->assertEquals(2, $clock->periodsRemaining());
    }

    public function test_is_last_period(): void
    {
        $clock = GameClock::fromArray([
            'current_period' => 4,
            'total_periods' => 4,
        ]);

        $this->assertTrue($clock->isLastPeriod());
    }

    public function test_to_array(): void
    {
        $clock = GameClock::forNewGame();
        $array = $clock->toArray();

        $this->assertArrayHasKey('time_remaining_seconds', $array);
        $this->assertArrayHasKey('current_period', $array);
        $this->assertArrayHasKey('clock_running', $array);
        $this->assertArrayHasKey('formatted_time', $array);
        $this->assertArrayHasKey('period_display', $array);
        $this->assertArrayHasKey('is_overtime', $array);
    }
}
