<?php

namespace Tests\Unit\Services;

use App\Models\Game;
use App\Models\GameAction;
use App\Models\Player;
use App\Models\Team;
use App\Models\Club;
use App\Models\User;
use App\Services\Statistics\StatisticsService;
use App\Exports\PlayerStatsExport;
use App\Exports\TeamStatsExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use ReflectionClass;
use Tests\TestCase;

/**
 * PERF-008: Unit tests for memory optimization through chunking.
 *
 * Tests that:
 * 1. Export classes use FromQuery with chunking instead of FromCollection
 * 2. StatisticsService uses chunked aggregation for season stats
 * 3. Chunk sizes are appropriate for the data type
 */
class ChunkingPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private StatisticsService $statisticsService;
    private Team $team;
    private Player $player;
    private Game $game;

    protected function setUp(): void
    {
        parent::setUp();

        $this->statisticsService = app(StatisticsService::class);

        // Create test data
        $club = Club::factory()->create();
        $this->team = Team::factory()->create(['club_id' => $club->id]);

        $user = User::factory()->create();
        $this->player = Player::factory()->create([
            'team_id' => $this->team->id,
            'user_id' => $user->id,
        ]);

        $awayTeam = Team::factory()->create(['club_id' => $club->id]);

        $this->game = Game::factory()->create([
            'home_team_id' => $this->team->id,
            'away_team_id' => $awayTeam->id,
            'status' => 'finished',
            'season' => '2024-25',
            'home_team_score' => 85,
            'away_team_score' => 78,
        ]);
    }

    // ========================================================================
    // Export Class Chunking Tests
    // ========================================================================

    /** @test */
    public function test_player_game_log_sheet_implements_chunking_interfaces(): void
    {
        $export = new PlayerStatsExport($this->player, '2024-25', $this->statisticsService);
        $sheets = $export->sheets();

        // Get the Game Log sheet
        $gameLogSheet = $sheets['Game Log'];

        // Verify it implements the chunking interfaces
        $this->assertInstanceOf(FromQuery::class, $gameLogSheet);
        $this->assertInstanceOf(WithMapping::class, $gameLogSheet);
        $this->assertInstanceOf(WithCustomChunkSize::class, $gameLogSheet);
    }

    /** @test */
    public function test_player_game_log_sheet_has_correct_chunk_size(): void
    {
        $export = new PlayerStatsExport($this->player, '2024-25', $this->statisticsService);
        $sheets = $export->sheets();

        $gameLogSheet = $sheets['Game Log'];

        // Verify chunk size is reasonable (100 for game logs)
        $this->assertEquals(100, $gameLogSheet->chunkSize());
    }

    /** @test */
    public function test_team_player_stats_sheet_implements_chunking_interfaces(): void
    {
        $export = new TeamStatsExport($this->team, '2024-25', $this->statisticsService);
        $sheets = $export->sheets();

        // Get the Player Stats sheet
        $playerStatsSheet = $sheets['Player Stats'];

        // Verify it implements the chunking interfaces
        $this->assertInstanceOf(FromQuery::class, $playerStatsSheet);
        $this->assertInstanceOf(WithMapping::class, $playerStatsSheet);
        $this->assertInstanceOf(WithCustomChunkSize::class, $playerStatsSheet);
    }

    /** @test */
    public function test_team_player_stats_sheet_has_correct_chunk_size(): void
    {
        $export = new TeamStatsExport($this->team, '2024-25', $this->statisticsService);
        $sheets = $export->sheets();

        $playerStatsSheet = $sheets['Player Stats'];

        // Verify chunk size is reasonable (50 for player lists)
        $this->assertEquals(50, $playerStatsSheet->chunkSize());
    }

    /** @test */
    public function test_team_game_log_sheet_implements_chunking_interfaces(): void
    {
        $export = new TeamStatsExport($this->team, '2024-25', $this->statisticsService);
        $sheets = $export->sheets();

        // Get the Game Log sheet
        $gameLogSheet = $sheets['Game Log'];

        // Verify it implements the chunking interfaces
        $this->assertInstanceOf(FromQuery::class, $gameLogSheet);
        $this->assertInstanceOf(WithMapping::class, $gameLogSheet);
        $this->assertInstanceOf(WithCustomChunkSize::class, $gameLogSheet);
    }

    /** @test */
    public function test_team_game_log_sheet_has_correct_chunk_size(): void
    {
        $export = new TeamStatsExport($this->team, '2024-25', $this->statisticsService);
        $sheets = $export->sheets();

        $gameLogSheet = $sheets['Game Log'];

        // Verify chunk size is reasonable (100 for game logs)
        $this->assertEquals(100, $gameLogSheet->chunkSize());
    }

    // ========================================================================
    // StatisticsService Chunking Tests
    // ========================================================================

    /** @test */
    public function test_statistics_service_has_chunking_helper_methods(): void
    {
        $reflection = new ReflectionClass(StatisticsService::class);

        // Verify chunking helper methods exist
        $this->assertTrue($reflection->hasMethod('initializePlayerStatsArray'));
        $this->assertTrue($reflection->hasMethod('aggregateActionToStats'));
        $this->assertTrue($reflection->hasMethod('finalizePlayerStats'));
    }

    /** @test */
    public function test_player_season_stats_returns_correct_structure(): void
    {
        // Create some game actions
        GameAction::factory()->create([
            'game_id' => $this->game->id,
            'player_id' => $this->player->id,
            'team_id' => $this->team->id,
            'action_type' => 'field_goal_made',
        ]);

        GameAction::factory()->create([
            'game_id' => $this->game->id,
            'player_id' => $this->player->id,
            'team_id' => $this->team->id,
            'action_type' => 'rebound_defensive',
        ]);

        $stats = $this->statisticsService->getPlayerSeasonStats($this->player, '2024-25');

        // Verify the structure is correct (chunking produces same results)
        $this->assertArrayHasKey('total_points', $stats);
        $this->assertArrayHasKey('field_goals_made', $stats);
        $this->assertArrayHasKey('field_goals_attempted', $stats);
        $this->assertArrayHasKey('total_rebounds', $stats);
        $this->assertArrayHasKey('games_played', $stats);
        $this->assertArrayHasKey('avg_points', $stats);
        $this->assertArrayHasKey('field_goal_percentage', $stats);
        $this->assertArrayHasKey('true_shooting_percentage', $stats);
        $this->assertArrayHasKey('player_efficiency_rating', $stats);
    }

    /** @test */
    public function test_team_season_stats_returns_correct_structure(): void
    {
        // Create some game actions
        GameAction::factory()->create([
            'game_id' => $this->game->id,
            'player_id' => $this->player->id,
            'team_id' => $this->team->id,
            'action_type' => 'field_goal_made',
        ]);

        $stats = $this->statisticsService->getTeamSeasonStats($this->team, '2024-25');

        // Verify the structure is correct (chunking produces same results)
        $this->assertArrayHasKey('games_played', $stats);
        $this->assertArrayHasKey('wins', $stats);
        $this->assertArrayHasKey('losses', $stats);
        $this->assertArrayHasKey('points_for', $stats);
        $this->assertArrayHasKey('points_against', $stats);
        $this->assertArrayHasKey('field_goals_made', $stats);
        $this->assertArrayHasKey('field_goal_percentage', $stats);
        $this->assertArrayHasKey('offensive_rating', $stats);
        $this->assertArrayHasKey('defensive_rating', $stats);
    }

    /** @test */
    public function test_player_season_stats_calculates_correctly_with_multiple_games(): void
    {
        // Create a second game
        $awayTeam = Team::factory()->create(['club_id' => $this->team->club_id]);
        $game2 = Game::factory()->create([
            'home_team_id' => $this->team->id,
            'away_team_id' => $awayTeam->id,
            'status' => 'finished',
            'season' => '2024-25',
            'home_team_score' => 90,
            'away_team_score' => 88,
        ]);

        // Game 1: 2 field goals made (4 points)
        GameAction::factory()->create([
            'game_id' => $this->game->id,
            'player_id' => $this->player->id,
            'team_id' => $this->team->id,
            'action_type' => 'field_goal_made',
        ]);
        GameAction::factory()->create([
            'game_id' => $this->game->id,
            'player_id' => $this->player->id,
            'team_id' => $this->team->id,
            'action_type' => 'field_goal_made',
        ]);

        // Game 2: 1 three-pointer made (3 points)
        GameAction::factory()->create([
            'game_id' => $game2->id,
            'player_id' => $this->player->id,
            'team_id' => $this->team->id,
            'action_type' => 'three_point_made',
        ]);

        $stats = $this->statisticsService->getPlayerSeasonStats($this->player, '2024-25');

        // Verify calculations
        $this->assertEquals(2, $stats['games_played']);
        $this->assertEquals(7, $stats['total_points']); // 4 + 3
        $this->assertEquals(2, $stats['field_goals_made']);
        $this->assertEquals(1, $stats['three_points_made']);
        $this->assertEquals(3.5, $stats['avg_points']); // 7 / 2 = 3.5
    }
}
