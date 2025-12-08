<?php

namespace Tests\Feature\Api\V2;

use App\Models\Player;
use App\Models\PlayerAbsence;
use Tests\BasketballTestCase;

class PlayerAbsenceTest extends BasketballTestCase
{
    /**
     * Test player can create own absence.
     */
    public function test_player_can_create_own_absence(): void
    {
        $this->actingAsPlayer();

        $response = $this->postJson('/api/v2/absences', [
            'type' => 'vacation',
            'start_date' => now()->addDays(7)->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'notes' => 'Familienurlaub',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'type_display',
                    'start_date',
                    'end_date',
                    'notes',
                ],
                'message',
            ]);

        $this->assertDatabaseHas('player_absences', [
            'player_id' => $this->testPlayer->id,
            'type' => 'vacation',
            'notes' => 'Familienurlaub',
        ]);
    }

    /**
     * Test player can list own absences.
     */
    public function test_player_can_list_own_absences(): void
    {
        $this->actingAsPlayer();

        PlayerAbsence::factory()->count(3)->create([
            'player_id' => $this->testPlayer->id,
        ]);

        $response = $this->getJson('/api/v2/absences/my');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'type_display',
                        'start_date',
                        'end_date',
                    ],
                ],
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /**
     * Test player can update own absence.
     */
    public function test_player_can_update_own_absence(): void
    {
        $this->actingAsPlayer();

        $absence = PlayerAbsence::factory()->create([
            'player_id' => $this->testPlayer->id,
            'type' => 'vacation',
        ]);

        $response = $this->putJson("/api/v2/absences/{$absence->id}", [
            'type' => 'illness',
            'start_date' => now()->addDays(1)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'notes' => 'Grippe',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'type' => 'illness',
                ],
            ]);

        $this->assertDatabaseHas('player_absences', [
            'id' => $absence->id,
            'type' => 'illness',
            'notes' => 'Grippe',
        ]);
    }

    /**
     * Test player can delete own absence.
     */
    public function test_player_can_delete_own_absence(): void
    {
        $this->actingAsPlayer();

        $absence = PlayerAbsence::factory()->create([
            'player_id' => $this->testPlayer->id,
        ]);

        $response = $this->deleteJson("/api/v2/absences/{$absence->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Abwesenheit wurde gelöscht.',
            ]);

        $this->assertSoftDeleted('player_absences', [
            'id' => $absence->id,
        ]);
    }

    /**
     * Test player cannot update other player's absence.
     */
    public function test_player_cannot_update_other_players_absence(): void
    {
        $this->actingAsPlayer();

        $otherPlayer = Player::factory()->create([
            'team_id' => $this->testTeam->id,
        ]);

        $absence = PlayerAbsence::factory()->create([
            'player_id' => $otherPlayer->id,
        ]);

        $response = $this->putJson("/api/v2/absences/{$absence->id}", [
            'type' => 'illness',
            'start_date' => now()->addDays(1)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test player cannot delete other player's absence.
     */
    public function test_player_cannot_delete_other_players_absence(): void
    {
        $this->actingAsPlayer();

        $otherPlayer = Player::factory()->create([
            'team_id' => $this->testTeam->id,
        ]);

        $absence = PlayerAbsence::factory()->create([
            'player_id' => $otherPlayer->id,
        ]);

        $response = $this->deleteJson("/api/v2/absences/{$absence->id}");

        $response->assertStatus(403);
    }

    /**
     * Test trainer can view team absences.
     */
    public function test_trainer_can_view_team_absences(): void
    {
        $this->actingAsTrainer();

        PlayerAbsence::factory()->count(2)->create([
            'player_id' => $this->testPlayer->id,
        ]);

        $response = $this->getJson("/api/v2/absences/team/{$this->testTeam->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'player',
                        'type',
                        'start_date',
                        'end_date',
                    ],
                ],
            ]);
    }

    /**
     * Test absence validation - start date must be before end date.
     */
    public function test_absence_validation_start_before_end(): void
    {
        $this->actingAsPlayer();

        $response = $this->postJson('/api/v2/absences', [
            'type' => 'vacation',
            'start_date' => now()->addDays(14)->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    /**
     * Test absence validation - type must be valid.
     */
    public function test_absence_validation_valid_type(): void
    {
        $this->actingAsPlayer();

        $response = $this->postJson('/api/v2/absences', [
            'type' => 'invalid_type',
            'start_date' => now()->addDays(7)->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    /**
     * Test unauthenticated user cannot create absence.
     */
    public function test_unauthenticated_cannot_create_absence(): void
    {
        $response = $this->postJson('/api/v2/absences', [
            'type' => 'vacation',
            'start_date' => now()->addDays(7)->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
        ]);

        $response->assertStatus(401);
    }
}
