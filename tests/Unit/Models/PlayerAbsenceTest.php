<?php

namespace Tests\Unit\Models;

use App\Models\Player;
use App\Models\PlayerAbsence;
use Tests\BasketballTestCase;

class PlayerAbsenceTest extends BasketballTestCase
{
    /** @test */
    public function it_has_correct_fillable_attributes(): void
    {
        $absence = new PlayerAbsence;

        $expected = [
            'tenant_id',
            'player_id',
            'type',
            'start_date',
            'end_date',
            'notes',
            'reason',
        ];

        $this->assertEquals($expected, $absence->getFillable());
    }

    /** @test */
    public function it_casts_dates_correctly(): void
    {
        $absence = PlayerAbsence::factory()->create([
            'player_id' => $this->testPlayer->id,
            'start_date' => '2024-01-15',
            'end_date' => '2024-01-20',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $absence->start_date);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $absence->end_date);
    }

    /** @test */
    public function it_belongs_to_a_player(): void
    {
        $absence = PlayerAbsence::factory()->create([
            'player_id' => $this->testPlayer->id,
        ]);

        $this->assertInstanceOf(Player::class, $absence->player);
        $this->assertEquals($this->testPlayer->id, $absence->player->id);
    }

    /** @test */
    public function it_returns_german_type_display(): void
    {
        $types = [
            'vacation' => 'Urlaub',
            'illness' => 'Krankheit',
            'injury' => 'Verletzung',
            'personal' => 'Persönlich',
            'other' => 'Sonstiges',
        ];

        foreach ($types as $type => $expectedDisplay) {
            $absence = PlayerAbsence::factory()->create([
                'player_id' => $this->testPlayer->id,
                'type' => $type,
            ]);

            $this->assertEquals($expectedDisplay, $absence->type_display);
        }
    }

    /** @test */
    public function it_scopes_current_absences(): void
    {
        // Create past absence
        PlayerAbsence::factory()->create([
            'player_id' => $this->testPlayer->id,
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDays(5),
        ]);

        // Create current absence
        $currentAbsence = PlayerAbsence::factory()->create([
            'player_id' => $this->testPlayer->id,
            'start_date' => now()->subDays(2),
            'end_date' => now()->addDays(5),
        ]);

        // Create future absence
        PlayerAbsence::factory()->create([
            'player_id' => $this->testPlayer->id,
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(15),
        ]);

        $current = PlayerAbsence::current()->get();

        $this->assertCount(1, $current);
        $this->assertEquals($currentAbsence->id, $current->first()->id);
    }

    /** @test */
    public function it_scopes_upcoming_absences(): void
    {
        // Create past absence
        PlayerAbsence::factory()->create([
            'player_id' => $this->testPlayer->id,
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDays(5),
        ]);

        // Create current absence (also included in upcoming)
        $currentAbsence = PlayerAbsence::factory()->create([
            'player_id' => $this->testPlayer->id,
            'start_date' => now()->subDays(2),
            'end_date' => now()->addDays(5),
        ]);

        // Create future absence
        $futureAbsence = PlayerAbsence::factory()->create([
            'player_id' => $this->testPlayer->id,
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(15),
        ]);

        $upcoming = PlayerAbsence::upcoming()->get();

        $this->assertCount(2, $upcoming);
        $this->assertTrue($upcoming->contains($currentAbsence));
        $this->assertTrue($upcoming->contains($futureAbsence));
    }

    /** @test */
    public function it_scopes_overlapping_absences(): void
    {
        // Absence that doesn't overlap
        PlayerAbsence::factory()->create([
            'player_id' => $this->testPlayer->id,
            'start_date' => now()->addDays(20),
            'end_date' => now()->addDays(25),
        ]);

        // Absence that overlaps
        $overlapping = PlayerAbsence::factory()->create([
            'player_id' => $this->testPlayer->id,
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(15),
        ]);

        $result = PlayerAbsence::overlapping(
            now()->addDays(7),
            now()->addDays(12)
        )->get();

        $this->assertCount(1, $result);
        $this->assertEquals($overlapping->id, $result->first()->id);
    }

    /** @test */
    public function it_checks_if_absence_covers_date(): void
    {
        $absence = PlayerAbsence::factory()->create([
            'player_id' => $this->testPlayer->id,
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(10),
        ]);

        // Date within range
        $this->assertTrue($absence->coversDate(now()->addDays(7)));

        // Start date
        $this->assertTrue($absence->coversDate(now()->addDays(5)));

        // End date
        $this->assertTrue($absence->coversDate(now()->addDays(10)));

        // Date before range
        $this->assertFalse($absence->coversDate(now()->addDays(3)));

        // Date after range
        $this->assertFalse($absence->coversDate(now()->addDays(12)));
    }

    /** @test */
    public function it_uses_soft_deletes(): void
    {
        $absence = PlayerAbsence::factory()->create([
            'player_id' => $this->testPlayer->id,
        ]);

        $absenceId = $absence->id;

        $absence->delete();

        $this->assertSoftDeleted('player_absences', ['id' => $absenceId]);

        // Can be restored
        $absence->restore();

        $this->assertDatabaseHas('player_absences', [
            'id' => $absenceId,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function player_has_absences_relationship(): void
    {
        PlayerAbsence::factory()->count(3)->create([
            'player_id' => $this->testPlayer->id,
        ]);

        $this->testPlayer->refresh();

        $this->assertCount(3, $this->testPlayer->absences);
    }

    /** @test */
    public function player_has_absence_on_helper(): void
    {
        PlayerAbsence::factory()->create([
            'player_id' => $this->testPlayer->id,
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(10),
        ]);

        $this->testPlayer->refresh();

        $this->assertTrue($this->testPlayer->hasAbsenceOn(now()->addDays(7)));
        $this->assertFalse($this->testPlayer->hasAbsenceOn(now()->addDays(15)));
    }
}
