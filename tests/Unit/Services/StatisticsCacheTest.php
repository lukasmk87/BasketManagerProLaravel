<?php

namespace Tests\Unit\Services;

use App\Models\Game;
use App\Models\GameAction;
use App\Models\Player;
use App\Models\Team;
use App\Models\Club;
use App\Models\User;
use App\Services\Statistics\StatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * PERF-007: Unit tests for StatisticsService cache improvements.
 *
 * Tests dynamic TTL, selective cache invalidation, and cache key building.
 */
class StatisticsCacheTest extends TestCase
{
    use RefreshDatabase;

    private StatisticsService $statisticsService;
    private Team $team;
    private Player $player;
    private Game $liveGame;
    private Game $finishedGame;

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

        $this->liveGame = Game::factory()->create([
            'home_team_id' => $this->team->id,
            'away_team_id' => $awayTeam->id,
            'status' => 'live',
            'season' => '2024-25',
        ]);

        $this->finishedGame = Game::factory()->create([
            'home_team_id' => $this->team->id,
            'away_team_id' => $awayTeam->id,
            'status' => 'finished',
            'season' => '2024-25',
        ]);
    }

    /** @test */
    public function test_cache_uses_dynamic_ttl_for_live_games(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->withArgs(function ($key, $ttl, $callback) {
                // Live games should use 5 minute TTL (300 seconds)
                return $ttl === 300;
            })
            ->andReturn([]);

        $this->statisticsService->getPlayerGameStats($this->player, $this->liveGame);
    }

    /** @test */
    public function test_cache_uses_dynamic_ttl_for_finished_games(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->withArgs(function ($key, $ttl, $callback) {
                // Finished games should use 1 hour TTL (3600 seconds)
                return $ttl === 3600;
            })
            ->andReturn([]);

        $this->statisticsService->getPlayerGameStats($this->player, $this->finishedGame);
    }

    /** @test */
    public function test_cache_uses_season_ttl_for_season_stats(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->withArgs(function ($key, $ttl, $callback) {
                // Season stats should use 24 hour TTL (86400 seconds)
                return $ttl === 86400;
            })
            ->andReturn([]);

        $this->statisticsService->getPlayerSeasonStats($this->player, '2024-25');
    }

    /** @test */
    public function test_cache_key_is_correctly_built(): void
    {
        $expectedKey = "basketball:stats:player:{$this->player->id}:game:{$this->liveGame->id}";

        Cache::shouldReceive('remember')
            ->once()
            ->withArgs(function ($key, $ttl, $callback) use ($expectedKey) {
                return $key === $expectedKey;
            })
            ->andReturn([]);

        $this->statisticsService->getPlayerGameStats($this->player, $this->liveGame);
    }

    /** @test */
    public function test_clear_player_cache_removes_correct_keys(): void
    {
        // First, populate the cache
        Cache::put("basketball:stats:player:{$this->player->id}:game:{$this->liveGame->id}", ['test' => 'data'], 3600);
        Cache::put("basketball:stats:shotchart:player:{$this->player->id}:game:{$this->liveGame->id}", ['test' => 'data'], 3600);

        // Clear the player cache
        $this->statisticsService->clearPlayerCache($this->player, $this->liveGame);

        // Verify keys are removed
        $this->assertFalse(Cache::has("basketball:stats:player:{$this->player->id}:game:{$this->liveGame->id}"));
        $this->assertFalse(Cache::has("basketball:stats:shotchart:player:{$this->player->id}:game:{$this->liveGame->id}"));
    }

    /** @test */
    public function test_clear_team_cache_removes_correct_keys(): void
    {
        // First, populate the cache
        Cache::put("basketball:stats:team:{$this->team->id}:game:{$this->liveGame->id}", ['test' => 'data'], 3600);

        // Clear the team cache
        $this->statisticsService->clearTeamCache($this->team, $this->liveGame);

        // Verify key is removed
        $this->assertFalse(Cache::has("basketball:stats:team:{$this->team->id}:game:{$this->liveGame->id}"));
    }

    /** @test */
    public function test_clear_game_cache_removes_all_related_keys(): void
    {
        // Create some game actions
        GameAction::factory()->create([
            'game_id' => $this->liveGame->id,
            'player_id' => $this->player->id,
            'team_id' => $this->team->id,
            'action_type' => 'field_goal_made',
        ]);

        // Populate various caches
        Cache::put("basketball:stats:player:{$this->player->id}:game:{$this->liveGame->id}", ['test' => 'data'], 3600);
        Cache::put("basketball:stats:team:{$this->team->id}:game:{$this->liveGame->id}", ['test' => 'data'], 3600);
        Cache::put("basketball:stats:shotchart:player:{$this->player->id}:game:{$this->liveGame->id}", ['test' => 'data'], 3600);

        // Clear the game cache
        $this->statisticsService->clearGameCache($this->liveGame);

        // Verify all related keys are removed
        $this->assertFalse(Cache::has("basketball:stats:player:{$this->player->id}:game:{$this->liveGame->id}"));
        $this->assertFalse(Cache::has("basketball:stats:team:{$this->team->id}:game:{$this->liveGame->id}"));
        $this->assertFalse(Cache::has("basketball:stats:shotchart:player:{$this->player->id}:game:{$this->liveGame->id}"));
    }

    /** @test */
    public function test_cache_hit_returns_cached_data(): void
    {
        $cachedData = ['total_points' => 25, 'rebounds' => 10];
        $cacheKey = "basketball:stats:player:{$this->player->id}:game:{$this->finishedGame->id}";

        // Pre-populate cache
        Cache::put($cacheKey, $cachedData, 3600);

        // Get stats - should return cached data
        $result = $this->statisticsService->getPlayerGameStats($this->player, $this->finishedGame);

        $this->assertEquals($cachedData, $result);
    }
}
