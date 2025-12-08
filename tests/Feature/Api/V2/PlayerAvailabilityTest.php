<?php

namespace Tests\Feature\Api\V2;

use App\Models\Game;
use App\Models\GameRegistration;
use App\Models\PlayerAbsence;
use App\Models\TrainingSession;
use App\Models\TrainingRegistration;
use Tests\BasketballTestCase;

class PlayerAvailabilityTest extends BasketballTestCase
{
    /**
     * Test player can get upcoming events.
     */
    public function test_player_can_get_upcoming_events(): void
    {
        $this->actingAsPlayer();

        // Create a game
        $game = $this->createTestGame();

        // Create a training
        TrainingSession::factory()->create([
            'team_id' => $this->testTeam->id,
            'scheduled_at' => now()->addDays(3),
            'status' => 'scheduled',
        ]);

        $response = $this->getJson('/api/v2/availability/my-events');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'type',
                        'id',
                        'title',
                        'scheduled_at',
                        'my_response',
                    ],
                ],
            ]);
    }

    /**
     * Test player can respond to a game.
     */
    public function test_player_can_respond_to_game(): void
    {
        $this->actingAsPlayer();

        $game = $this->createTestGame();

        $response = $this->postJson('/api/v2/availability/respond', [
            'event_type' => 'game',
            'event_id' => $game->id,
            'response' => 'available',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Antwort wurde gespeichert.',
            ]);

        $this->assertDatabaseHas('game_registrations', [
            'game_id' => $game->id,
            'player_id' => $this->testPlayer->id,
            'availability' => 'available',
        ]);
    }

    /**
     * Test player can respond to a training.
     */
    public function test_player_can_respond_to_training(): void
    {
        $this->actingAsPlayer();

        $training = TrainingSession::factory()->create([
            'team_id' => $this->testTeam->id,
            'scheduled_at' => now()->addDays(3),
            'status' => 'scheduled',
        ]);

        $response = $this->postJson('/api/v2/availability/respond', [
            'event_type' => 'training',
            'event_id' => $training->id,
            'response' => 'unavailable',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Antwort wurde gespeichert.',
            ]);

        $this->assertDatabaseHas('training_registrations', [
            'training_session_id' => $training->id,
            'player_id' => $this->testPlayer->id,
            'availability' => 'unavailable',
        ]);
    }

    /**
     * Test player can change response.
     */
    public function test_player_can_change_response(): void
    {
        $this->actingAsPlayer();

        $game = $this->createTestGame();

        // Initial response
        GameRegistration::create([
            'game_id' => $game->id,
            'player_id' => $this->testPlayer->id,
            'team_id' => $this->testTeam->id,
            'availability' => 'available',
        ]);

        // Change response
        $response = $this->postJson('/api/v2/availability/respond', [
            'event_type' => 'game',
            'event_id' => $game->id,
            'response' => 'unavailable',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('game_registrations', [
            'game_id' => $game->id,
            'player_id' => $this->testPlayer->id,
            'availability' => 'unavailable',
        ]);
    }

    /**
     * Test trainer can view team availability.
     */
    public function test_trainer_can_view_team_availability(): void
    {
        $this->actingAsTrainer();

        $game = $this->createTestGame();

        $response = $this->getJson("/api/v2/availability/team/{$this->testTeam->id}?event_type=game&event_id={$game->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'event',
                    'players' => [
                        '*' => [
                            'id',
                            'name',
                            'availability',
                            'has_absence',
                        ],
                    ],
                    'summary',
                ],
            ]);
    }

    /**
     * Test absence affects availability response.
     */
    public function test_absence_affects_availability(): void
    {
        $this->actingAsPlayer();

        $game = $this->createTestGame();

        // Create an absence covering the game date
        PlayerAbsence::factory()->create([
            'player_id' => $this->testPlayer->id,
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(10),
            'type' => 'vacation',
        ]);

        $response = $this->getJson('/api/v2/availability/my-events');

        $response->assertStatus(200);

        // The game at +7 days should show the absence blocking it
        $events = collect($response->json('data'));
        $gameEvent = $events->firstWhere('type', 'game');

        if ($gameEvent) {
            $this->assertTrue(
                $gameEvent['blocked_by_absence'] ?? false,
                'Game should be marked as blocked by absence'
            );
        }
    }

    /**
     * Test validation for respond endpoint.
     */
    public function test_respond_validation(): void
    {
        $this->actingAsPlayer();

        $response = $this->postJson('/api/v2/availability/respond', [
            'event_type' => 'invalid',
            'event_id' => 999,
            'response' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['event_type', 'response']);
    }

    /**
     * Test player cannot respond to non-existent event.
     */
    public function test_cannot_respond_to_nonexistent_event(): void
    {
        $this->actingAsPlayer();

        $response = $this->postJson('/api/v2/availability/respond', [
            'event_type' => 'game',
            'event_id' => 999999,
            'response' => 'available',
        ]);

        $response->assertStatus(404);
    }

    /**
     * Test trainer can get pending responses.
     */
    public function test_trainer_can_get_pending_responses(): void
    {
        $this->actingAsTrainer();

        $game = $this->createTestGame();

        $response = $this->getJson("/api/v2/availability/pending-responses/{$this->testTeam->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'event_type',
                        'event_id',
                        'title',
                        'scheduled_at',
                        'pending_count',
                    ],
                ],
            ]);
    }

    /**
     * Test unauthenticated user cannot access availability.
     */
    public function test_unauthenticated_cannot_access_availability(): void
    {
        $response = $this->getJson('/api/v2/availability/my-events');

        $response->assertStatus(401);
    }

    /**
     * Test player without team cannot access availability.
     */
    public function test_player_without_player_profile_gets_empty_events(): void
    {
        // Create a user without player profile
        $user = \App\Models\User::factory()->create();

        $this->actingAs($user);

        $response = $this->getJson('/api/v2/availability/my-events');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [],
            ]);
    }
}
