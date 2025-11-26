<?php

namespace Tests\Unit\Services;

use App\Models\Game;
use App\Models\GameAction;
use App\Models\LiveGame;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Services\LiveScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class LiveScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LiveScoringService $service;
    protected Game $game;
    protected Team $homeTeam;
    protected Team $awayTeam;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Redis facade to avoid needing Redis extension
        Redis::shouldReceive('get')->andReturn(0);
        Redis::shouldReceive('setex')->andReturn(true);
        Redis::shouldReceive('del')->andReturn(true);
        Redis::shouldReceive('incr')->andReturn(1);
        Redis::shouldReceive('decr')->andReturn(0);

        $this->service = new LiveScoringService();

        // Create teams
        $this->homeTeam = Team::factory()->create();
        $this->awayTeam = Team::factory()->create();

        // Create game with proper settings
        $this->game = Game::factory()->create([
            'home_team_id' => $this->homeTeam->id,
            'away_team_id' => $this->awayTeam->id,
            'status' => 'scheduled',
            'total_periods' => 4,
            'period_length_minutes' => 10,
            'overtime_length_minutes' => 5,
        ]);
    }

    // ========================================
    // GAME LIFECYCLE TESTS
    // ========================================

    /** @test */
    public function start_game_creates_live_game_with_initial_state(): void
    {
        $liveGame = $this->service->startGame($this->game);

        $this->assertInstanceOf(LiveGame::class, $liveGame);
        $this->assertEquals($this->game->id, $liveGame->game_id);
        $this->assertEquals(1, $liveGame->current_period);
        $this->assertEquals(0, $liveGame->current_score_home);
        $this->assertEquals(0, $liveGame->current_score_away);
        $this->assertFalse($liveGame->period_is_running);
        $this->assertEquals(5, $liveGame->timeouts_home_remaining);
        $this->assertEquals(5, $liveGame->timeouts_away_remaining);
        $this->assertEquals('pregame', $liveGame->game_phase);
        $this->assertTrue($liveGame->is_being_broadcasted);

        // Game status should be updated
        $this->game->refresh();
        $this->assertEquals('live', $this->game->status);
    }

    /** @test */
    public function start_game_throws_exception_if_game_not_scheduled(): void
    {
        $this->game->update(['status' => 'finished']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Das Spiel kann nicht gestartet werden');

        $this->service->startGame($this->game);
    }

    /** @test */
    public function start_game_throws_exception_if_game_already_live(): void
    {
        $this->game->update(['status' => 'live']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Das Spiel kann nicht gestartet werden');

        $this->service->startGame($this->game);
    }

    /** @test */
    public function finish_game_updates_final_scores(): void
    {
        // Start game and create live game state
        $liveGame = $this->service->startGame($this->game);
        $liveGame->update([
            'current_score_home' => 85,
            'current_score_away' => 78,
            'fouls_home_total' => 15,
            'fouls_away_total' => 12,
            'timeouts_home_remaining' => 2,
            'timeouts_away_remaining' => 3,
        ]);

        $result = $this->service->finishGame($this->game);

        $this->assertEquals('finished', $result->status);
        $this->assertEquals(85, $result->home_team_score);
        $this->assertEquals(78, $result->away_team_score);

        // Live game should stop broadcasting
        $liveGame->refresh();
        $this->assertFalse($liveGame->is_being_broadcasted);
        $this->assertEquals('postgame', $liveGame->game_phase);
    }

    // ========================================
    // GAME ACTIONS TESTS
    // ========================================

    /** @test */
    public function add_game_action_creates_action_with_correct_data(): void
    {
        $this->service->startGame($this->game);
        $player = Player::factory()->create();
        $this->actingAs(User::factory()->create());

        $actionData = [
            'player_id' => $player->id,
            'team_id' => $this->homeTeam->id,
            'action_type' => 'field_goal_made',
            'points' => 2,
        ];

        $action = $this->service->addGameAction($this->game, $actionData);

        $this->assertInstanceOf(GameAction::class, $action);
        $this->assertEquals($this->game->id, $action->game_id);
        $this->assertEquals($player->id, $action->player_id);
        $this->assertEquals('field_goal_made', $action->action_type);
        $this->assertEquals(2, $action->points);
        $this->assertEquals(1, $action->period);
    }

    /** @test */
    public function add_game_action_throws_exception_if_game_not_live(): void
    {
        // Create a fresh game without live game using fresh teams
        $newHomeTeam = Team::factory()->create();
        $newAwayTeam = Team::factory()->create();
        $freshGame = Game::factory()->create([
            'home_team_id' => $newHomeTeam->id,
            'away_team_id' => $newAwayTeam->id,
            'status' => 'scheduled',
        ]);

        // Explicitly delete any live game that might exist for this game
        LiveGame::where('game_id', $freshGame->id)->delete();
        $freshGame = $freshGame->fresh();

        $player = Player::factory()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Spiel ist nicht live');

        $this->service->addGameAction($freshGame, [
            'player_id' => $player->id,
            'team_id' => $newHomeTeam->id,
            'action_type' => 'field_goal_made',
        ]);
    }

    /** @test */
    public function add_game_action_increments_action_count(): void
    {
        $liveGame = $this->service->startGame($this->game);
        $player = Player::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->assertEquals(0, $liveGame->actions_count);

        $this->service->addGameAction($this->game, [
            'player_id' => $player->id,
            'team_id' => $this->homeTeam->id,
            'action_type' => 'rebound_defensive',
        ]);

        $liveGame->refresh();
        $this->assertEquals(1, $liveGame->actions_count);
    }

    /** @test */
    public function update_live_game_state_updates_score_for_scoring_action(): void
    {
        $liveGame = $this->service->startGame($this->game);
        $player = Player::factory()->create();

        $action = GameAction::factory()->create([
            'game_id' => $this->game->id,
            'player_id' => $player->id,
            'team_id' => $this->homeTeam->id,
            'action_type' => 'three_point_made',
            'points' => 3,
        ]);

        $result = $this->service->updateLiveGameState($this->game, $action);

        $this->assertEquals(3, $result->current_score_home);
        $this->assertEquals(0, $result->current_score_away);
    }

    /** @test */
    public function update_live_game_state_increments_fouls_for_foul_action(): void
    {
        $liveGame = $this->service->startGame($this->game);
        $player = Player::factory()->create();

        $action = GameAction::factory()->foul()->create([
            'game_id' => $this->game->id,
            'player_id' => $player->id,
            'team_id' => $this->awayTeam->id,
        ]);

        $result = $this->service->updateLiveGameState($this->game, $action);

        $this->assertEquals(0, $result->fouls_home_period);
        $this->assertEquals(1, $result->fouls_away_period);
        $this->assertEquals(1, $result->fouls_away_total);
    }

    /** @test */
    public function correct_action_recalculates_live_game_state(): void
    {
        $liveGame = $this->service->startGame($this->game);
        $player = Player::factory()->create();
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create action with points
        $action = GameAction::factory()->fieldGoalMade()->create([
            'game_id' => $this->game->id,
            'player_id' => $player->id,
            'team_id' => $this->homeTeam->id,
        ]);

        // Update live game to reflect the action
        $liveGame->update(['current_score_home' => 2]);

        // Correct action - change points from 2 to 3
        $correctedAction = $this->service->correctAction($action, ['points' => 3], 'Incorrect shot location');

        $this->assertTrue($correctedAction->is_corrected);
        $this->assertEquals('Incorrect shot location', $correctedAction->correction_reason);

        // Live game should be recalculated
        $liveGame->refresh();
        $this->assertEquals(3, $liveGame->current_score_home);
    }

    /** @test */
    public function delete_action_recalculates_game_state(): void
    {
        $liveGame = $this->service->startGame($this->game);
        $player = Player::factory()->create();

        // Create action
        $action = GameAction::factory()->threePointMade()->create([
            'game_id' => $this->game->id,
            'player_id' => $player->id,
            'team_id' => $this->homeTeam->id,
        ]);

        // Manually update score
        $liveGame->update(['current_score_home' => 3]);

        // Delete action
        $this->service->deleteAction($action);

        // Score should be recalculated to 0
        $liveGame->refresh();
        $this->assertEquals(0, $liveGame->current_score_home);
        $this->assertDatabaseMissing('game_actions', ['id' => $action->id]);
    }

    // ========================================
    // PERIOD CONTROL TESTS
    // ========================================

    /** @test */
    public function start_period_sets_period_running_to_true(): void
    {
        $liveGame = $this->service->startGame($this->game);
        $this->assertFalse($liveGame->period_is_running);

        $result = $this->service->startPeriod($this->game);

        $this->assertTrue($result->period_is_running);
        $this->assertEquals('period', $result->game_phase);
        $this->assertNotNull($result->period_started_at);
    }

    /** @test */
    public function pause_period_stops_period_timer(): void
    {
        $liveGame = $this->service->startGame($this->game);
        $this->service->startPeriod($this->game);

        $result = $this->service->pausePeriod($this->game);

        $this->assertFalse($result->period_is_running);
        $this->assertNotNull($result->period_paused_at);
    }

    /** @test */
    public function pause_period_throws_exception_if_not_running(): void
    {
        $this->service->startGame($this->game);
        // Period not started

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Periode läuft nicht');

        $this->service->pausePeriod($this->game);
    }

    /** @test */
    public function resume_period_resumes_period_timer(): void
    {
        $liveGame = $this->service->startGame($this->game);
        $this->service->startPeriod($this->game);
        $this->service->pausePeriod($this->game);

        $result = $this->service->resumePeriod($this->game);

        $this->assertTrue($result->period_is_running);
        $this->assertNull($result->period_paused_at);
    }

    /** @test */
    public function end_period_advances_to_next_period(): void
    {
        $liveGame = $this->service->startGame($this->game);
        $liveGame->update(['current_score_home' => 20, 'current_score_away' => 18]);
        $this->service->startPeriod($this->game);

        $result = $this->service->endPeriod($this->game);

        $this->assertEquals(2, $result->current_period);
        $this->assertFalse($result->period_is_running);
        $this->assertEquals(0, $result->fouls_home_period); // Fouls reset per period
    }

    /** @test */
    public function end_period_finishes_game_if_last_period_and_not_tied(): void
    {
        $liveGame = $this->service->startGame($this->game);
        $liveGame->update([
            'current_period' => 4,
            'current_score_home' => 85,
            'current_score_away' => 78,
        ]);

        $result = $this->service->endPeriod($this->game);

        $this->game->refresh();
        $this->assertEquals('finished', $this->game->status);
        $this->assertEquals(85, $this->game->home_team_score);
    }

    /** @test */
    public function end_period_starts_overtime_if_tied_after_last_period(): void
    {
        $liveGame = $this->service->startGame($this->game);
        $liveGame->update([
            'current_period' => 4,
            'current_score_home' => 80,
            'current_score_away' => 80, // Tied!
        ]);

        $result = $this->service->endPeriod($this->game);

        // Should go to overtime (period 5)
        $this->assertEquals(5, $result->current_period);
        $this->game->refresh();
        $this->assertNotEquals('finished', $this->game->status);
    }

    // ========================================
    // TIMEOUT TESTS
    // ========================================

    /** @test */
    public function start_timeout_decrements_timeout_count(): void
    {
        $liveGame = $this->service->startGame($this->game);
        $this->assertEquals(5, $liveGame->timeouts_home_remaining);

        $result = $this->service->startTimeout($this->game, 'home');

        $this->assertTrue($result->is_in_timeout);
        $this->assertEquals('home', $result->timeout_team);
        $this->assertEquals(4, $result->timeouts_home_remaining);
        $this->assertEquals('timeout', $result->game_phase);
    }

    /** @test */
    public function start_timeout_throws_exception_if_no_timeouts_remaining(): void
    {
        $liveGame = $this->service->startGame($this->game);
        $liveGame->update(['timeouts_home_remaining' => 0]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('hat keine Timeouts mehr');

        $this->service->startTimeout($this->game, 'home');
    }

    /** @test */
    public function end_timeout_restores_period_state(): void
    {
        $liveGame = $this->service->startGame($this->game);
        $this->service->startPeriod($this->game);
        $this->service->startTimeout($this->game, 'away');

        $result = $this->service->endTimeout($this->game);

        $this->assertFalse($result->is_in_timeout);
        $this->assertNull($result->timeout_team);
        $this->assertEquals('period', $result->game_phase);
    }

    // ========================================
    // SUBSTITUTION TESTS
    // ========================================

    /** @test */
    public function process_substitution_creates_substitution_actions(): void
    {
        $this->service->startGame($this->game);
        $playerIn = Player::factory()->create();
        $playerOut = Player::factory()->create();
        $this->actingAs(User::factory()->create());

        $this->service->processSubstitution($this->game, 'home', $playerIn->id, $playerOut->id, 'Fatigue');

        // Should create two actions: one for out, one for in
        $this->assertDatabaseHas('game_actions', [
            'game_id' => $this->game->id,
            'player_id' => $playerOut->id,
            'action_type' => 'substitution_out',
            'substitution_reason' => 'Fatigue',
        ]);

        $this->assertDatabaseHas('game_actions', [
            'game_id' => $this->game->id,
            'player_id' => $playerIn->id,
            'action_type' => 'substitution_in',
            'substitution_reason' => 'Fatigue',
        ]);
    }

    /** @test */
    public function process_substitution_updates_players_on_court(): void
    {
        $liveGame = $this->service->startGame($this->game);
        $playerIn = Player::factory()->create();
        $playerOut = Player::factory()->create();
        $this->actingAs(User::factory()->create());

        // Set initial players on court
        $liveGame->update(['players_on_court_home' => [$playerOut->id, 2, 3, 4, 5]]);

        $this->service->processSubstitution($this->game, 'home', $playerIn->id, $playerOut->id);

        $liveGame->refresh();
        $playersOnCourt = $liveGame->players_on_court_home;

        $this->assertContains($playerIn->id, $playersOnCourt);
        $this->assertNotContains($playerOut->id, $playersOnCourt);
    }

    // ========================================
    // SCORE UPDATE TESTS
    // ========================================

    /** @test */
    public function update_score_increments_team_score(): void
    {
        $liveGame = $this->service->startGame($this->game);
        $this->service->startPeriod($this->game);
        $player = Player::factory()->create();

        $result = $this->service->updateScore($this->game, 'home', 2, $player->id);

        $this->assertEquals(2, $result->current_score_home);
        $this->assertEquals(0, $result->current_score_away);
    }

    /** @test */
    public function update_score_throws_exception_if_period_not_running(): void
    {
        $this->service->startGame($this->game);
        // Period not started
        $player = Player::factory()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Spielstand kann nur während laufendem Spiel aktualisiert werden');

        $this->service->updateScore($this->game, 'home', 2, $player->id);
    }
}
