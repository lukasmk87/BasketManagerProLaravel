<?php

namespace Tests\Unit\Observers;

use App\Models\Game;
use App\Models\GameAction;
use App\Models\Player;
use App\Models\Team;
use App\Models\Club;
use App\Models\User;
use App\Observers\GameActionObserver;
use App\Services\StatisticsService;
use App\Services\BasketballCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

/**
 * PERF-007: Integration tests for GameActionObserver.
 *
 * Tests that cache is automatically invalidated when game actions are created,
 * updated, or deleted.
 */
class GameActionObserverTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;
    private Player $player;
    private Game $game;

    protected function setUp(): void
    {
        parent::setUp();

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
            'status' => 'live',
            'season' => '2024-25',
        ]);
    }

    /** @test */
    public function test_creating_game_action_invalidates_player_cache(): void
    {
        // Pre-populate player cache
        $cacheKey = "basketball:stats:player:{$this->player->id}:game:{$this->game->id}";
        Cache::put($cacheKey, ['test' => 'data'], 3600);

        $this->assertTrue(Cache::has($cacheKey));

        // Create a game action (this should trigger observer)
        GameAction::create([
            'game_id' => $this->game->id,
            'player_id' => $this->player->id,
            'team_id' => $this->team->id,
            'action_type' => 'field_goal_made',
            'period' => 1,
            'time_remaining' => '10:00',
        ]);

        // Cache should be invalidated
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function test_creating_game_action_invalidates_team_cache(): void
    {
        // Pre-populate team cache
        $cacheKey = "basketball:stats:team:{$this->team->id}:game:{$this->game->id}";
        Cache::put($cacheKey, ['test' => 'data'], 3600);

        $this->assertTrue(Cache::has($cacheKey));

        // Create a game action (this should trigger observer)
        GameAction::create([
            'game_id' => $this->game->id,
            'player_id' => $this->player->id,
            'team_id' => $this->team->id,
            'action_type' => 'rebound',
            'period' => 1,
            'time_remaining' => '09:30',
        ]);

        // Cache should be invalidated
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function test_creating_game_action_invalidates_game_cache(): void
    {
        // Pre-populate caches
        $playerCacheKey = "basketball:stats:player:{$this->player->id}:game:{$this->game->id}";
        $teamCacheKey = "basketball:stats:team:{$this->team->id}:game:{$this->game->id}";

        Cache::put($playerCacheKey, ['test' => 'player'], 3600);
        Cache::put($teamCacheKey, ['test' => 'team'], 3600);

        // Create a game action
        GameAction::create([
            'game_id' => $this->game->id,
            'player_id' => $this->player->id,
            'team_id' => $this->team->id,
            'action_type' => 'assist',
            'period' => 2,
            'time_remaining' => '05:00',
        ]);

        // All related caches should be invalidated
        $this->assertFalse(Cache::has($playerCacheKey));
        $this->assertFalse(Cache::has($teamCacheKey));
    }

    /** @test */
    public function test_updating_game_action_invalidates_cache(): void
    {
        // Create a game action first
        $gameAction = GameAction::create([
            'game_id' => $this->game->id,
            'player_id' => $this->player->id,
            'team_id' => $this->team->id,
            'action_type' => 'field_goal_missed',
            'period' => 1,
            'time_remaining' => '08:00',
        ]);

        // Pre-populate cache after creation
        $cacheKey = "basketball:stats:player:{$this->player->id}:game:{$this->game->id}";
        Cache::put($cacheKey, ['test' => 'data'], 3600);

        $this->assertTrue(Cache::has($cacheKey));

        // Update the game action (change from missed to made)
        $gameAction->update([
            'action_type' => 'field_goal_made',
        ]);

        // Cache should be invalidated
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function test_deleting_game_action_invalidates_cache(): void
    {
        // Create a game action first
        $gameAction = GameAction::create([
            'game_id' => $this->game->id,
            'player_id' => $this->player->id,
            'team_id' => $this->team->id,
            'action_type' => 'three_point_made',
            'period' => 3,
            'time_remaining' => '02:00',
        ]);

        // Pre-populate cache after creation
        $cacheKey = "basketball:stats:player:{$this->player->id}:game:{$this->game->id}";
        Cache::put($cacheKey, ['test' => 'data'], 3600);

        $this->assertTrue(Cache::has($cacheKey));

        // Delete the game action
        $gameAction->delete();

        // Cache should be invalidated
        $this->assertFalse(Cache::has($cacheKey));
    }
}
