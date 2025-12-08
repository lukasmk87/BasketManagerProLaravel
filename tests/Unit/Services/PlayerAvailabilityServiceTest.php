<?php

namespace Tests\Unit\Services;

use App\Models\Game;
use App\Models\GameRegistration;
use App\Models\Player;
use App\Models\PlayerAbsence;
use App\Models\TrainingSession;
use App\Models\TrainingRegistration;
use App\Services\PlayerAvailabilityService;
use Tests\BasketballTestCase;

class PlayerAvailabilityServiceTest extends BasketballTestCase
{
    private PlayerAvailabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PlayerAvailabilityService::class);
    }

    /** @test */
    public function it_detects_active_absence_on_date(): void
    {
        // Create an absence covering a specific period
        PlayerAbsence::factory()->create([
            'player_id' => $this->testPlayer->id,
            'start_date' => now()->subDays(2),
            'end_date' => now()->addDays(5),
            'type' => 'vacation',
        ]);

        // Today should be covered
        $this->assertTrue(
            $this->service->hasActiveAbsence($this->testPlayer->id, now())
        );

        // Day after end date should not be covered
        $this->assertFalse(
            $this->service->hasActiveAbsence($this->testPlayer->id, now()->addDays(10))
        );
    }

    /** @test */
    public function it_returns_correct_effective_availability_without_absence(): void
    {
        $game = $this->createTestGame();

        // No registration yet
        $availability = $this->service->getEffectiveAvailability(
            $this->testPlayer,
            'game',
            $game->id,
            $game->scheduled_at
        );

        $this->assertEquals('no_response', $availability['effective']);
        $this->assertFalse($availability['has_absence']);
    }

    /** @test */
    public function it_returns_blocked_when_absence_exists(): void
    {
        $game = $this->createTestGame();

        // Create an absence covering the game date
        PlayerAbsence::factory()->create([
            'player_id' => $this->testPlayer->id,
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(10),
            'type' => 'illness',
        ]);

        $availability = $this->service->getEffectiveAvailability(
            $this->testPlayer,
            'game',
            $game->id,
            $game->scheduled_at
        );

        $this->assertTrue($availability['has_absence']);
        $this->assertEquals('unavailable', $availability['effective']);
    }

    /** @test */
    public function it_gets_upcoming_events_for_player(): void
    {
        // Create game
        $game = $this->createTestGame();

        // Create training
        TrainingSession::factory()->create([
            'team_id' => $this->testTeam->id,
            'scheduled_at' => now()->addDays(3),
            'status' => 'scheduled',
        ]);

        $events = $this->service->getUpcomingEventsWithAvailability($this->testPlayer, 30);

        $this->assertIsArray($events);
        $this->assertGreaterThanOrEqual(2, count($events));

        // Events should be sorted by date
        $prevDate = null;
        foreach ($events as $event) {
            if ($prevDate !== null) {
                $this->assertGreaterThanOrEqual($prevDate, $event['scheduled_at']);
            }
            $prevDate = $event['scheduled_at'];
        }
    }

    /** @test */
    public function it_creates_absence_successfully(): void
    {
        $data = [
            'player_id' => $this->testPlayer->id,
            'type' => 'vacation',
            'start_date' => now()->addDays(7)->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'notes' => 'Sommerurlaub',
        ];

        $absence = $this->service->createAbsence($data);

        $this->assertInstanceOf(PlayerAbsence::class, $absence);
        $this->assertEquals($this->testPlayer->id, $absence->player_id);
        $this->assertEquals('vacation', $absence->type);
        $this->assertEquals('Sommerurlaub', $absence->notes);
    }

    /** @test */
    public function it_updates_game_availability(): void
    {
        $game = $this->createTestGame();

        $this->service->updateGameAvailability(
            $this->testPlayer,
            $game,
            'available'
        );

        $this->assertDatabaseHas('game_registrations', [
            'game_id' => $game->id,
            'player_id' => $this->testPlayer->id,
            'availability' => 'available',
        ]);
    }

    /** @test */
    public function it_updates_existing_game_availability(): void
    {
        $game = $this->createTestGame();

        // Create initial registration
        GameRegistration::create([
            'game_id' => $game->id,
            'player_id' => $this->testPlayer->id,
            'team_id' => $this->testTeam->id,
            'availability' => 'available',
        ]);

        // Update to unavailable
        $this->service->updateGameAvailability(
            $this->testPlayer,
            $game,
            'unavailable'
        );

        $this->assertDatabaseHas('game_registrations', [
            'game_id' => $game->id,
            'player_id' => $this->testPlayer->id,
            'availability' => 'unavailable',
        ]);
    }

    /** @test */
    public function it_updates_training_availability(): void
    {
        $training = TrainingSession::factory()->create([
            'team_id' => $this->testTeam->id,
            'scheduled_at' => now()->addDays(3),
            'status' => 'scheduled',
        ]);

        $this->service->updateTrainingAvailability(
            $this->testPlayer,
            $training,
            'maybe'
        );

        $this->assertDatabaseHas('training_registrations', [
            'training_session_id' => $training->id,
            'player_id' => $this->testPlayer->id,
            'availability' => 'maybe',
        ]);
    }

    /** @test */
    public function it_gets_team_availability_overview(): void
    {
        $game = $this->createTestGame();

        // Add some players with responses
        $players = $this->createTeamRoster($this->testTeam, 5);

        // Set different availability statuses
        GameRegistration::create([
            'game_id' => $game->id,
            'player_id' => $players[0]->id,
            'team_id' => $this->testTeam->id,
            'availability' => 'available',
        ]);

        GameRegistration::create([
            'game_id' => $game->id,
            'player_id' => $players[1]->id,
            'team_id' => $this->testTeam->id,
            'availability' => 'unavailable',
        ]);

        $overview = $this->service->getTeamAvailabilityOverview(
            $this->testTeam->id,
            'game',
            $game->id
        );

        $this->assertArrayHasKey('available', $overview);
        $this->assertArrayHasKey('unavailable', $overview);
        $this->assertArrayHasKey('maybe', $overview);
        $this->assertArrayHasKey('no_response', $overview);

        $this->assertGreaterThanOrEqual(1, count($overview['available']));
        $this->assertGreaterThanOrEqual(1, count($overview['unavailable']));
    }

    /** @test */
    public function it_gets_players_without_response(): void
    {
        $game = $this->createTestGame();

        // Add some players
        $players = $this->createTeamRoster($this->testTeam, 3);

        // Only one player responds
        GameRegistration::create([
            'game_id' => $game->id,
            'player_id' => $players[0]->id,
            'team_id' => $this->testTeam->id,
            'availability' => 'available',
        ]);

        $playerIds = collect($players)->pluck('id')->toArray();
        $playerIds[] = $this->testPlayer->id;

        $withoutResponse = $this->service->getPlayersWithoutResponse(
            'game',
            $game->id,
            $playerIds
        );

        // Should not include the player who responded
        $this->assertNotContains($players[0]->id, $withoutResponse);

        // Should include players who didn't respond
        $this->assertContains($players[1]->id, $withoutResponse);
        $this->assertContains($players[2]->id, $withoutResponse);
    }

    /** @test */
    public function it_handles_multiple_absences_correctly(): void
    {
        // Create multiple absences for same player
        PlayerAbsence::factory()->create([
            'player_id' => $this->testPlayer->id,
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(10),
            'type' => 'vacation',
        ]);

        PlayerAbsence::factory()->create([
            'player_id' => $this->testPlayer->id,
            'start_date' => now()->addDays(20),
            'end_date' => now()->addDays(25),
            'type' => 'illness',
        ]);

        // Day 7 should be covered by first absence
        $this->assertTrue(
            $this->service->hasActiveAbsence($this->testPlayer->id, now()->addDays(7))
        );

        // Day 22 should be covered by second absence
        $this->assertTrue(
            $this->service->hasActiveAbsence($this->testPlayer->id, now()->addDays(22))
        );

        // Day 15 should not be covered
        $this->assertFalse(
            $this->service->hasActiveAbsence($this->testPlayer->id, now()->addDays(15))
        );
    }
}
