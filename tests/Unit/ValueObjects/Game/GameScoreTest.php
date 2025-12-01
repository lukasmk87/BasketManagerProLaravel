<?php

namespace Tests\Unit\ValueObjects\Game;

use App\ValueObjects\Game\GameScore;
use PHPUnit\Framework\TestCase;

class GameScoreTest extends TestCase
{
    public function test_create_default_score(): void
    {
        $score = GameScore::create();

        $this->assertEquals(0, $score->homeTeamScore());
        $this->assertEquals(0, $score->awayTeamScore());
        $this->assertNull($score->periodScores());
    }

    public function test_create_with_scores(): void
    {
        $score = GameScore::create(85, 78);

        $this->assertEquals(85, $score->homeTeamScore());
        $this->assertEquals(78, $score->awayTeamScore());
    }

    public function test_from_array(): void
    {
        $score = GameScore::fromArray([
            'home_team_score' => 92,
            'away_team_score' => 88,
            'period_scores' => [
                1 => ['home' => 22, 'away' => 20],
                2 => ['home' => 25, 'away' => 24],
            ],
        ]);

        $this->assertEquals(92, $score->homeTeamScore());
        $this->assertEquals(88, $score->awayTeamScore());
        $this->assertCount(2, $score->periodScores());
    }

    public function test_total_score(): void
    {
        $score = GameScore::create(85, 78);

        $this->assertEquals(163, $score->totalScore());
    }

    public function test_point_differential(): void
    {
        $score = GameScore::create(85, 78);

        $this->assertEquals(7, $score->pointDifferential());
    }

    public function test_home_leading(): void
    {
        $score = GameScore::create(85, 78);

        $this->assertTrue($score->isHomeLeading());
        $this->assertFalse($score->isAwayLeading());
        $this->assertFalse($score->isTied());
        $this->assertEquals('home', $score->leadingTeamSide());
    }

    public function test_away_leading(): void
    {
        $score = GameScore::create(78, 85);

        $this->assertFalse($score->isHomeLeading());
        $this->assertTrue($score->isAwayLeading());
        $this->assertFalse($score->isTied());
        $this->assertEquals('away', $score->leadingTeamSide());
    }

    public function test_tied_game(): void
    {
        $score = GameScore::create(80, 80);

        $this->assertFalse($score->isHomeLeading());
        $this->assertFalse($score->isAwayLeading());
        $this->assertTrue($score->isTied());
        $this->assertNull($score->leadingTeamSide());
    }

    public function test_with_added_home_score_is_immutable(): void
    {
        $original = GameScore::create(80, 75);
        $updated = $original->withAddedHomeScore(3);

        $this->assertEquals(80, $original->homeTeamScore());
        $this->assertEquals(83, $updated->homeTeamScore());
    }

    public function test_with_added_away_score_is_immutable(): void
    {
        $original = GameScore::create(80, 75);
        $updated = $original->withAddedAwayScore(2);

        $this->assertEquals(75, $original->awayTeamScore());
        $this->assertEquals(77, $updated->awayTeamScore());
    }

    public function test_with_period_score(): void
    {
        $score = GameScore::create(45, 42);
        $updated = $score->withPeriodScore(1, 22, 20);

        $this->assertEquals(['home' => 22, 'away' => 20], $updated->getScoreForPeriod(1));
    }

    public function test_formatted(): void
    {
        $score = GameScore::create(85, 78);

        $this->assertEquals('85 : 78', $score->formatted());
        $this->assertEquals('78 : 85', $score->formattedReversed());
    }

    public function test_to_array(): void
    {
        $score = GameScore::create(85, 78);
        $array = $score->toArray();

        $this->assertArrayHasKey('home_team_score', $array);
        $this->assertArrayHasKey('away_team_score', $array);
        $this->assertArrayHasKey('total_score', $array);
        $this->assertArrayHasKey('point_differential', $array);
        $this->assertArrayHasKey('is_tied', $array);
    }
}
