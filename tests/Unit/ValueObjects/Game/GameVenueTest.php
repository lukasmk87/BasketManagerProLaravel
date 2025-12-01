<?php

namespace Tests\Unit\ValueObjects\Game;

use App\ValueObjects\Game\GameVenue;
use PHPUnit\Framework\TestCase;

class GameVenueTest extends TestCase
{
    public function test_create_empty_venue(): void
    {
        $venue = GameVenue::create();

        $this->assertNull($venue->name());
        $this->assertNull($venue->address());
        $this->assertNull($venue->code());
    }

    public function test_create_with_basic_info(): void
    {
        $venue = GameVenue::create(
            name: 'Sportarena Hamburg',
            address: 'Musterstraße 123, 20099 Hamburg',
            code: 'HAM-01'
        );

        $this->assertEquals('Sportarena Hamburg', $venue->name());
        $this->assertEquals('Musterstraße 123, 20099 Hamburg', $venue->address());
        $this->assertEquals('HAM-01', $venue->code());
    }

    public function test_from_array(): void
    {
        $venue = GameVenue::fromArray([
            'venue' => 'Sportarena Hamburg',
            'venue_address' => 'Musterstraße 123',
            'venue_code' => 'HAM-01',
            'attendance' => 5000,
            'capacity' => 8000,
            'weather_conditions' => 'Indoor',
            'temperature' => 22,
        ]);

        $this->assertEquals('Sportarena Hamburg', $venue->name());
        $this->assertEquals(5000, $venue->attendance());
        $this->assertEquals(8000, $venue->capacity());
        $this->assertEquals(22, $venue->temperature());
    }

    public function test_has_venue(): void
    {
        $empty = GameVenue::create();
        $withName = GameVenue::create(name: 'Arena');

        $this->assertFalse($empty->hasVenue());
        $this->assertTrue($withName->hasVenue());
    }

    public function test_attendance_percentage(): void
    {
        $venue = GameVenue::fromArray([
            'attendance' => 4000,
            'capacity' => 8000,
        ]);

        $this->assertEquals(50.0, $venue->attendancePercentage());
    }

    public function test_attendance_percentage_null_without_data(): void
    {
        $venue = GameVenue::create();

        $this->assertNull($venue->attendancePercentage());
    }

    public function test_is_sold_out(): void
    {
        $soldOut = GameVenue::fromArray([
            'attendance' => 8000,
            'capacity' => 8000,
        ]);

        $notSoldOut = GameVenue::fromArray([
            'attendance' => 5000,
            'capacity' => 8000,
        ]);

        $this->assertTrue($soldOut->isSoldOut());
        $this->assertFalse($notSoldOut->isSoldOut());
    }

    public function test_available_seats(): void
    {
        $venue = GameVenue::fromArray([
            'attendance' => 5000,
            'capacity' => 8000,
        ]);

        $this->assertEquals(3000, $venue->availableSeats());
    }

    public function test_formatted_attendance(): void
    {
        $venue = GameVenue::fromArray([
            'attendance' => 5000,
            'capacity' => 8000,
        ]);

        $this->assertEquals('5.000 / 8.000', $venue->formattedAttendance());
    }

    public function test_formatted_temperature(): void
    {
        $venue = GameVenue::fromArray(['temperature' => 22]);

        $this->assertEquals('22°C', $venue->formattedTemperature());
    }

    public function test_full_address(): void
    {
        $venue = GameVenue::create(
            name: 'Arena',
            address: 'Musterstraße 1'
        );

        $this->assertEquals('Arena, Musterstraße 1', $venue->fullAddress());
    }

    public function test_with_attendance_is_immutable(): void
    {
        $original = GameVenue::fromArray(['capacity' => 8000]);
        $updated = $original->withAttendance(5000);

        $this->assertNull($original->attendance());
        $this->assertEquals(5000, $updated->attendance());
    }

    public function test_with_weather(): void
    {
        $venue = GameVenue::create();
        $updated = $venue->withWeather('Sunny', 25);

        $this->assertEquals('Sunny', $updated->weatherConditions());
        $this->assertEquals(25, $updated->temperature());
    }

    public function test_with_court_conditions(): void
    {
        $venue = GameVenue::create();
        $updated = $venue->withCourtConditions('Excellent');

        $this->assertEquals('Excellent', $updated->courtConditions());
    }

    public function test_to_array(): void
    {
        $venue = GameVenue::fromArray([
            'venue' => 'Arena',
            'attendance' => 5000,
            'capacity' => 8000,
        ]);
        $array = $venue->toArray();

        $this->assertArrayHasKey('venue', $array);
        $this->assertArrayHasKey('attendance', $array);
        $this->assertArrayHasKey('capacity', $array);
        $this->assertArrayHasKey('attendance_percentage', $array);
        $this->assertArrayHasKey('available_seats', $array);
    }
}
