<?php

namespace Tests\Unit\Services;

use App\Models\BasketballTeam;
use App\Models\Club;
use App\Models\Player;
use App\Models\Season;
use App\Models\SeasonStatistic;
use App\Services\PlayerService;
use App\Services\SeasonService;
use App\Services\StatisticsService;
use App\Services\TeamService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Tests\TestCase;

class SeasonServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private SeasonService $seasonService;
    private StatisticsService $statisticsServiceMock;
    private Club $club;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock StatisticsService to avoid complex dependencies
        $this->statisticsServiceMock = Mockery::mock(StatisticsService::class);
        $this->app->instance(StatisticsService::class, $this->statisticsServiceMock);

        // Get the SeasonService with mocked dependencies
        $this->seasonService = app(SeasonService::class);

        // Create a test club
        $this->club = Club::factory()->create();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ========================================
    // GRUPPE 1: CRUD-Tests (5 Tests)
    // ========================================

    /** @test */
    public function it_can_create_a_season()
    {
        $startDate = Carbon::parse('2024-09-01');
        $endDate = Carbon::parse('2025-06-30');

        $season = $this->seasonService->createNewSeason(
            $this->club,
            '2024/2025',
            $startDate,
            $endDate,
            'Test Saison Beschreibung',
            ['custom_setting' => 'value']
        );

        $this->assertInstanceOf(Season::class, $season);
        $this->assertEquals('2024/2025', $season->name);
        $this->assertEquals($this->club->id, $season->club_id);
        $this->assertEquals('draft', $season->status);
        $this->assertFalse($season->is_current);
        $this->assertEquals('Test Saison Beschreibung', $season->description);
        $this->assertArrayHasKey('custom_setting', $season->settings);

        $this->assertDatabaseHas('seasons', [
            'id' => $season->id,
            'name' => '2024/2025',
            'status' => 'draft',
        ]);
    }

    /** @test */
    public function it_throws_exception_for_duplicate_season_name()
    {
        $startDate = Carbon::parse('2024-09-01');
        $endDate = Carbon::parse('2025-06-30');

        // Create first season
        $this->seasonService->createNewSeason($this->club, '2024/2025', $startDate, $endDate);

        // Try to create duplicate - should throw exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Eine Saison mit dem Namen '2024/2025' existiert bereits");

        $this->seasonService->createNewSeason($this->club, '2024/2025', $startDate, $endDate);
    }

    /** @test */
    public function it_can_get_active_season_for_club()
    {
        $season = Season::factory()->active()->forClub($this->club)->create();

        $activeSeason = $this->seasonService->getActiveSeason($this->club);

        $this->assertNotNull($activeSeason);
        $this->assertEquals($season->id, $activeSeason->id);
        $this->assertTrue($activeSeason->is_current);
        $this->assertEquals('active', $activeSeason->status);
    }

    /** @test */
    public function it_returns_null_when_no_active_season()
    {
        // Create only a draft season
        Season::factory()->draft()->forClub($this->club)->create();

        $activeSeason = $this->seasonService->getActiveSeason($this->club);

        $this->assertNull($activeSeason);
    }

    /** @test */
    public function it_can_get_all_seasons_for_club()
    {
        // Create multiple seasons
        Season::factory()->draft()->forClub($this->club)->named('2025/2026')->create();
        Season::factory()->active()->forClub($this->club)->named('2024/2025')->create();
        Season::factory()->completed()->forClub($this->club)->named('2023/2024')->create();

        $allSeasons = $this->seasonService->getAllSeasonsForClub($this->club);
        $this->assertCount(3, $allSeasons);

        // Without completed
        $nonCompletedSeasons = $this->seasonService->getAllSeasonsForClub($this->club, false);
        $this->assertCount(2, $nonCompletedSeasons);
    }

    // ========================================
    // GRUPPE 2: Lifecycle-Tests (6 Tests)
    // ========================================

    /** @test */
    public function it_can_activate_a_draft_season()
    {
        $season = Season::factory()->activatable()->forClub($this->club)->create();

        $result = $this->seasonService->activateSeason($season);

        $this->assertTrue($result);
        $season->refresh();
        $this->assertEquals('active', $season->status);
        $this->assertTrue($season->is_current);
    }

    /** @test */
    public function it_throws_exception_when_activating_non_draft_season()
    {
        $season = Season::factory()->completed()->forClub($this->club)->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Saison kann nicht aktiviert werden");

        $this->seasonService->activateSeason($season);
    }

    /** @test */
    public function it_deactivates_other_seasons_when_activating_one()
    {
        // Create an active season
        $oldSeason = Season::factory()->active()->forClub($this->club)->named('2023/2024')->create();

        // Create a new activatable season
        $newSeason = Season::factory()->activatable()->forClub($this->club)->named('2024/2025')->create();

        $this->seasonService->activateSeason($newSeason);

        $oldSeason->refresh();
        $newSeason->refresh();

        $this->assertFalse($oldSeason->is_current);
        $this->assertTrue($newSeason->is_current);
    }

    /** @test */
    public function it_can_complete_an_active_season()
    {
        // Mock statisticsService for snapshot creation
        $this->statisticsServiceMock
            ->shouldReceive('getPlayerSeasonStats')
            ->andReturn($this->getDefaultPlayerStats());

        $season = Season::factory()->completable()->forClub($this->club)->create();

        $result = $this->seasonService->completeSeason($season, false);

        $this->assertTrue($result);
        $season->refresh();
        $this->assertEquals('completed', $season->status);
        $this->assertFalse($season->is_current);
    }

    /** @test */
    public function it_throws_exception_when_completing_non_active_season()
    {
        $season = Season::factory()->draft()->forClub($this->club)->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Nur aktive Saisons können abgeschlossen werden");

        $this->seasonService->completeSeason($season);
    }

    /** @test */
    public function it_archives_rosters_when_completing_season()
    {
        $this->statisticsServiceMock
            ->shouldReceive('getPlayerSeasonStats')
            ->andReturn($this->getDefaultPlayerStats());

        $season = Season::factory()->completable()->forClub($this->club)->create();
        $team = BasketballTeam::factory()->create([
            'club_id' => $this->club->id,
            'season_id' => $season->id,
        ]);
        $player = Player::factory()->create();
        $team->players()->attach($player->id, [
            'is_active' => true,
            'jersey_number' => 10,
            'primary_position' => 'PG',
        ]);

        $this->seasonService->completeSeason($season);

        // Verify roster was archived
        $this->assertDatabaseHas('player_team', [
            'team_id' => $team->id,
            'player_id' => $player->id,
            'is_active' => false,
        ]);
    }

    // ========================================
    // GRUPPE 3: Rollover-Tests (5 Tests)
    // ========================================

    /** @test */
    public function it_can_rollover_teams_to_new_season()
    {
        $oldSeason = Season::factory()->completed()->forClub($this->club)->create();
        $newSeason = Season::factory()->draft()->forClub($this->club)->create();

        // Create teams in old season
        BasketballTeam::factory()->count(3)->create([
            'club_id' => $this->club->id,
            'season_id' => $oldSeason->id,
        ]);

        $copiedTeams = $this->seasonService->rolloverTeams($oldSeason, $newSeason, false);

        $this->assertCount(3, $copiedTeams);

        // Verify teams belong to new season
        foreach ($copiedTeams as $team) {
            $this->assertEquals($newSeason->id, $team->season_id);
        }
    }

    /** @test */
    public function it_can_copy_single_team_to_new_season()
    {
        $oldSeason = Season::factory()->completed()->forClub($this->club)->create();
        $newSeason = Season::factory()->draft()->forClub($this->club)->create();

        $oldTeam = BasketballTeam::factory()->create([
            'club_id' => $this->club->id,
            'season_id' => $oldSeason->id,
            'name' => 'Original Team',
        ]);

        $newTeam = $this->seasonService->copyTeamToNewSeason($oldTeam, $newSeason);

        $this->assertNotEquals($oldTeam->id, $newTeam->id);
        $this->assertEquals('Original Team', $newTeam->name);
        $this->assertEquals($newSeason->id, $newTeam->season_id);
        $this->assertEquals($newSeason->name, $newTeam->season);
    }

    /** @test */
    public function it_can_rollover_roster_preserving_player_data()
    {
        $oldSeason = Season::factory()->completed()->forClub($this->club)->create();
        $newSeason = Season::factory()->draft()->forClub($this->club)->create();

        $oldTeam = BasketballTeam::factory()->create([
            'club_id' => $this->club->id,
            'season_id' => $oldSeason->id,
        ]);
        $newTeam = BasketballTeam::factory()->create([
            'club_id' => $this->club->id,
            'season_id' => $newSeason->id,
        ]);

        $player = Player::factory()->create();
        $oldTeam->players()->attach($player->id, [
            'jersey_number' => 23,
            'primary_position' => 'SF',
            'is_active' => true,
            'is_starter' => true,
            'is_captain' => true,
        ]);

        $transferredCount = $this->seasonService->rolloverRoster($oldTeam, $newTeam);

        $this->assertEquals(1, $transferredCount);

        // Verify player data was preserved in new team
        $this->assertDatabaseHas('player_team', [
            'team_id' => $newTeam->id,
            'player_id' => $player->id,
            'jersey_number' => 23,
            'primary_position' => 'SF',
            'is_starter' => true,
            'is_captain' => true,
            'is_active' => true,
            'is_registered' => false, // Must re-register
        ]);
    }

    /** @test */
    public function it_marks_old_roster_as_inactive_after_rollover()
    {
        $oldSeason = Season::factory()->completed()->forClub($this->club)->create();
        $newSeason = Season::factory()->draft()->forClub($this->club)->create();

        $oldTeam = BasketballTeam::factory()->create([
            'club_id' => $this->club->id,
            'season_id' => $oldSeason->id,
        ]);
        $newTeam = BasketballTeam::factory()->create([
            'club_id' => $this->club->id,
            'season_id' => $newSeason->id,
        ]);

        $player = Player::factory()->create();
        $oldTeam->players()->attach($player->id, [
            'is_active' => true,
            'jersey_number' => 10,
            'primary_position' => 'PG',
        ]);

        $this->seasonService->rolloverRoster($oldTeam, $newTeam);

        // Verify old assignment is now inactive
        $this->assertDatabaseHas('player_team', [
            'team_id' => $oldTeam->id,
            'player_id' => $player->id,
            'is_active' => false,
        ]);
    }

    /** @test */
    public function it_can_start_new_season_with_full_workflow()
    {
        $this->statisticsServiceMock
            ->shouldReceive('getPlayerSeasonStats')
            ->andReturn($this->getDefaultPlayerStats());

        // Create and activate an old season
        $oldSeason = Season::factory()->completable()->forClub($this->club)->create();

        // Create a team with players
        $team = BasketballTeam::factory()->create([
            'club_id' => $this->club->id,
            'season_id' => $oldSeason->id,
        ]);
        $player = Player::factory()->create();
        $team->players()->attach($player->id, [
            'is_active' => true,
            'jersey_number' => 5,
            'primary_position' => 'C',
        ]);

        $startDate = Carbon::parse('2025-09-01');
        $endDate = Carbon::parse('2026-06-30');

        $newSeason = $this->seasonService->startNewSeasonForClub(
            $this->club,
            '2025/2026',
            $startDate,
            $endDate,
            $oldSeason,
            true,
            true
        );

        // Verify new season is active
        $this->assertEquals('active', $newSeason->status);
        $this->assertTrue($newSeason->is_current);

        // Verify old season is completed
        $oldSeason->refresh();
        $this->assertEquals('completed', $oldSeason->status);
        $this->assertFalse($oldSeason->is_current);

        // Verify team was copied
        $this->assertDatabaseHas('teams', [
            'season_id' => $newSeason->id,
            'club_id' => $this->club->id,
        ]);
    }

    // ========================================
    // GRUPPE 4: Statistics-Tests (4 Tests)
    // ========================================

    /** @test */
    public function it_creates_season_statistics_snapshot_on_completion()
    {
        $this->statisticsServiceMock
            ->shouldReceive('getPlayerSeasonStats')
            ->once()
            ->andReturn($this->getDefaultPlayerStats());

        $season = Season::factory()->active()->forClub($this->club)->create();
        $team = BasketballTeam::factory()->create([
            'club_id' => $this->club->id,
            'season_id' => $season->id,
        ]);
        $player = Player::factory()->create();
        $team->players()->attach($player->id, [
            'is_active' => true,
            'jersey_number' => 7,
            'primary_position' => 'SG',
        ]);

        $snapshotCount = $this->seasonService->createSeasonStatisticsSnapshot($season);

        $this->assertEquals(1, $snapshotCount);
        $this->assertDatabaseHas('season_statistics', [
            'season_id' => $season->id,
            'player_id' => $player->id,
            'team_id' => $team->id,
        ]);
    }

    /** @test */
    public function it_calculates_percentages_correctly_in_snapshot()
    {
        $stats = [
            'games_played' => 20,
            'games_started' => 15,
            'minutes_played' => 500,
            'points' => 200,
            'field_goals_made' => 80,
            'field_goals_attempted' => 160, // 50% FG
            'three_pointers_made' => 20,
            'three_pointers_attempted' => 50, // 40% 3P
            'free_throws_made' => 40,
            'free_throws_attempted' => 50, // 80% FT
            'rebounds_offensive' => 30,
            'rebounds_defensive' => 70,
            'rebounds_total' => 100,
            'assists' => 80,
            'turnovers' => 40, // 2.0 AST/TO
            'steals' => 25,
            'blocks' => 10,
            'fouls_personal' => 45,
            'fouls_technical' => 0,
            'fouls_flagrant' => 0,
        ];

        $this->statisticsServiceMock
            ->shouldReceive('getPlayerSeasonStats')
            ->once()
            ->andReturn($stats);

        $season = Season::factory()->active()->forClub($this->club)->create();
        $team = BasketballTeam::factory()->create([
            'club_id' => $this->club->id,
            'season_id' => $season->id,
        ]);
        $player = Player::factory()->create();
        $team->players()->attach($player->id, [
            'is_active' => true,
            'jersey_number' => 1,
            'primary_position' => 'PG',
        ]);

        $this->seasonService->createSeasonStatisticsSnapshot($season);

        $snapshot = SeasonStatistic::where('player_id', $player->id)
            ->where('season_id', $season->id)
            ->first();

        $this->assertEquals(50.00, $snapshot->field_goal_percentage);
        $this->assertEquals(40.00, $snapshot->three_point_percentage);
        $this->assertEquals(80.00, $snapshot->free_throw_percentage);
        $this->assertEquals(2.00, $snapshot->assist_turnover_ratio);
    }

    /** @test */
    public function it_handles_zero_division_in_percentage_calculations()
    {
        $stats = [
            'games_played' => 0,
            'games_started' => 0,
            'minutes_played' => 0,
            'points' => 0,
            'field_goals_made' => 0,
            'field_goals_attempted' => 0, // Zero division case
            'three_pointers_made' => 0,
            'three_pointers_attempted' => 0,
            'free_throws_made' => 0,
            'free_throws_attempted' => 0,
            'rebounds_offensive' => 0,
            'rebounds_defensive' => 0,
            'rebounds_total' => 0,
            'assists' => 0,
            'turnovers' => 0, // Zero division case
            'steals' => 0,
            'blocks' => 0,
            'fouls_personal' => 0,
            'fouls_technical' => 0,
            'fouls_flagrant' => 0,
        ];

        $this->statisticsServiceMock
            ->shouldReceive('getPlayerSeasonStats')
            ->once()
            ->andReturn($stats);

        $season = Season::factory()->active()->forClub($this->club)->create();
        $team = BasketballTeam::factory()->create([
            'club_id' => $this->club->id,
            'season_id' => $season->id,
        ]);
        $player = Player::factory()->create();
        $team->players()->attach($player->id, [
            'is_active' => true,
            'jersey_number' => 99,
            'primary_position' => 'C',
        ]);

        // Should not throw exception
        $snapshotCount = $this->seasonService->createSeasonStatisticsSnapshot($season);

        $this->assertEquals(1, $snapshotCount);

        $snapshot = SeasonStatistic::where('player_id', $player->id)->first();

        $this->assertEquals(0, $snapshot->field_goal_percentage);
        $this->assertEquals(0, $snapshot->three_point_percentage);
        $this->assertEquals(0, $snapshot->free_throw_percentage);
        $this->assertEquals(0, $snapshot->assist_turnover_ratio);
    }

    /** @test */
    public function it_returns_correct_season_statistics_summary()
    {
        $season = Season::factory()->active()->forClub($this->club)->create();

        // Create teams with players
        $team1 = BasketballTeam::factory()->create([
            'club_id' => $this->club->id,
            'season_id' => $season->id,
        ]);
        $team2 = BasketballTeam::factory()->create([
            'club_id' => $this->club->id,
            'season_id' => $season->id,
        ]);

        // Add players to teams
        $player1 = Player::factory()->create();
        $player2 = Player::factory()->create();
        $player3 = Player::factory()->create();

        $team1->players()->attach($player1->id, ['is_active' => true, 'jersey_number' => 1, 'primary_position' => 'PG']);
        $team1->players()->attach($player2->id, ['is_active' => true, 'jersey_number' => 2, 'primary_position' => 'SG']);
        $team2->players()->attach($player3->id, ['is_active' => true, 'jersey_number' => 3, 'primary_position' => 'SF']);

        $stats = $this->seasonService->getSeasonStatistics($season);

        $this->assertEquals(2, $stats['teams_count']);
        $this->assertEquals(3, $stats['players_count']);
        $this->assertTrue($stats['is_active']);
        $this->assertFalse($stats['is_completed']);
    }

    // ========================================
    // GRUPPE 5: Edge Cases (5 Tests)
    // ========================================

    /** @test */
    public function it_isolates_seasons_between_clubs()
    {
        $club1 = Club::factory()->create();
        $club2 = Club::factory()->create();

        // Create seasons for each club with the same name
        $season1 = Season::factory()->active()->forClub($club1)->named('2024/2025')->create();
        $season2 = Season::factory()->active()->forClub($club2)->named('2024/2025')->create();

        // Get active season for club1
        $activeSeason = $this->seasonService->getActiveSeason($club1);

        $this->assertEquals($season1->id, $activeSeason->id);
        $this->assertNotEquals($season2->id, $activeSeason->id);

        // Get all seasons for club2
        $club2Seasons = $this->seasonService->getAllSeasonsForClub($club2);
        $this->assertCount(1, $club2Seasons);
        $this->assertEquals($season2->id, $club2Seasons->first()->id);
    }

    /** @test */
    public function it_rolls_back_on_error_during_season_completion()
    {
        $this->statisticsServiceMock
            ->shouldReceive('getPlayerSeasonStats')
            ->andThrow(new \Exception('Statistics service error'));

        $season = Season::factory()->completable()->forClub($this->club)->create();
        $team = BasketballTeam::factory()->create([
            'club_id' => $this->club->id,
            'season_id' => $season->id,
        ]);
        $player = Player::factory()->create();
        $team->players()->attach($player->id, [
            'is_active' => true,
            'jersey_number' => 42,
            'primary_position' => 'PF',
        ]);

        try {
            $this->seasonService->completeSeason($season, true);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $this->assertEquals('Statistics service error', $e->getMessage());
        }

        // Season should still be active (rollback)
        $season->refresh();
        $this->assertEquals('active', $season->status);
        $this->assertTrue($season->is_current);

        // Player should still be active (rollback)
        $this->assertDatabaseHas('player_team', [
            'team_id' => $team->id,
            'player_id' => $player->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_only_transfers_active_players_in_rollover()
    {
        $oldSeason = Season::factory()->completed()->forClub($this->club)->create();
        $newSeason = Season::factory()->draft()->forClub($this->club)->create();

        $oldTeam = BasketballTeam::factory()->create([
            'club_id' => $this->club->id,
            'season_id' => $oldSeason->id,
        ]);
        $newTeam = BasketballTeam::factory()->create([
            'club_id' => $this->club->id,
            'season_id' => $newSeason->id,
        ]);

        $activePlayer = Player::factory()->create();
        $inactivePlayer = Player::factory()->create();

        $oldTeam->players()->attach($activePlayer->id, [
            'is_active' => true,
            'jersey_number' => 1,
            'primary_position' => 'PG',
        ]);
        $oldTeam->players()->attach($inactivePlayer->id, [
            'is_active' => false,
            'jersey_number' => 2,
            'primary_position' => 'SG',
        ]);

        $transferredCount = $this->seasonService->rolloverRoster($oldTeam, $newTeam);

        $this->assertEquals(1, $transferredCount);

        // Only active player should be in new team
        $this->assertDatabaseHas('player_team', [
            'team_id' => $newTeam->id,
            'player_id' => $activePlayer->id,
        ]);
        $this->assertDatabaseMissing('player_team', [
            'team_id' => $newTeam->id,
            'player_id' => $inactivePlayer->id,
        ]);
    }

    /** @test */
    public function it_handles_empty_team_roster_gracefully()
    {
        $oldSeason = Season::factory()->completed()->forClub($this->club)->create();
        $newSeason = Season::factory()->draft()->forClub($this->club)->create();

        $oldTeam = BasketballTeam::factory()->create([
            'club_id' => $this->club->id,
            'season_id' => $oldSeason->id,
        ]);
        $newTeam = BasketballTeam::factory()->create([
            'club_id' => $this->club->id,
            'season_id' => $newSeason->id,
        ]);

        // No players attached to oldTeam

        $transferredCount = $this->seasonService->rolloverRoster($oldTeam, $newTeam);

        $this->assertEquals(0, $transferredCount);
    }

    /** @test */
    public function it_validates_date_range_for_season_activation()
    {
        // Create a season with dates in the future
        $season = Season::factory()->future()->forClub($this->club)->create();

        $this->expectException(\Exception::class);

        $this->seasonService->activateSeason($season);
    }

    // ========================================
    // Helper Methods
    // ========================================

    private function getDefaultPlayerStats(): array
    {
        return [
            'games_played' => 10,
            'games_started' => 5,
            'minutes_played' => 200,
            'points' => 100,
            'field_goals_made' => 40,
            'field_goals_attempted' => 80,
            'three_pointers_made' => 10,
            'three_pointers_attempted' => 30,
            'free_throws_made' => 20,
            'free_throws_attempted' => 25,
            'rebounds_offensive' => 15,
            'rebounds_defensive' => 35,
            'rebounds_total' => 50,
            'assists' => 30,
            'turnovers' => 15,
            'steals' => 10,
            'blocks' => 5,
            'fouls_personal' => 20,
            'fouls_technical' => 0,
            'fouls_flagrant' => 0,
            'advanced_stats' => ['per' => 15.5],
            'game_highs' => ['points' => 25],
        ];
    }
}
