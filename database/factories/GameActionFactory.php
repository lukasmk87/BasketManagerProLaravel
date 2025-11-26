<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\GameAction;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GameAction>
 */
class GameActionFactory extends Factory
{
    protected $model = GameAction::class;

    public function definition(): array
    {
        $actionTypes = [
            'field_goal_made', 'field_goal_missed',
            'three_point_made', 'three_point_missed',
            'free_throw_made', 'free_throw_missed',
            'rebound_offensive', 'rebound_defensive',
            'assist', 'steal', 'block', 'turnover',
            'foul_personal', 'foul_technical',
        ];

        return [
            'game_id' => Game::factory(),
            'player_id' => Player::factory(),
            'team_id' => Team::factory(),
            'action_type' => $this->faker->randomElement($actionTypes),
            'period' => $this->faker->numberBetween(1, 4),
            'time_remaining' => sprintf('%02d:%02d:00', $this->faker->numberBetween(0, 9), $this->faker->numberBetween(0, 59)),
            'game_clock_seconds' => $this->faker->numberBetween(0, 2400),
            'shot_clock_remaining' => $this->faker->numberBetween(1, 24),
            'points' => 0,
            'is_successful' => null,
            'is_assisted' => false,
            'assisted_by_player_id' => null,
            'shot_x' => null,
            'shot_y' => null,
            'shot_distance' => null,
            'shot_zone' => null,
            'foul_type' => null,
            'foul_results_in_free_throws' => false,
            'free_throws_awarded' => 0,
            'substituted_player_id' => null,
            'substitution_reason' => null,
            'description' => null,
            'notes' => null,
            'additional_data' => null,
            'recorded_by_user_id' => User::factory(),
            'recorded_from_ip' => $this->faker->ipv4(),
            'recorded_at' => now(),
            'is_reviewed' => false,
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
            'is_corrected' => false,
            'corrected_by_user_id' => null,
            'correction_reason' => null,
        ];
    }

    /**
     * Field goal made (2 points).
     */
    public function fieldGoalMade(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => 'field_goal_made',
            'points' => 2,
            'is_successful' => true,
            'shot_x' => $this->faker->randomFloat(2, 0, 100),
            'shot_y' => $this->faker->randomFloat(2, 0, 100),
            'shot_zone' => $this->faker->randomElement(['paint', 'mid_range']),
        ]);
    }

    /**
     * Field goal missed.
     */
    public function fieldGoalMissed(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => 'field_goal_missed',
            'points' => 0,
            'is_successful' => false,
            'shot_x' => $this->faker->randomFloat(2, 0, 100),
            'shot_y' => $this->faker->randomFloat(2, 0, 100),
            'shot_zone' => $this->faker->randomElement(['paint', 'mid_range']),
        ]);
    }

    /**
     * Three pointer made (3 points).
     */
    public function threePointMade(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => 'three_point_made',
            'points' => 3,
            'is_successful' => true,
            'shot_x' => $this->faker->randomFloat(2, 0, 100),
            'shot_y' => $this->faker->randomFloat(2, 0, 100),
            'shot_zone' => 'three_point',
        ]);
    }

    /**
     * Free throw made (1 point).
     */
    public function freeThrowMade(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => 'free_throw_made',
            'points' => 1,
            'is_successful' => true,
            'shot_zone' => 'free_throw',
        ]);
    }

    /**
     * Personal foul.
     */
    public function foul(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => 'foul_personal',
            'points' => 0,
            'foul_type' => 'shooting',
            'foul_results_in_free_throws' => $this->faker->boolean(30),
            'free_throws_awarded' => $this->faker->boolean(30) ? 2 : 0,
        ]);
    }

    /**
     * Technical foul.
     */
    public function technicalFoul(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => 'foul_technical',
            'points' => 0,
            'foul_type' => 'technical',
            'foul_results_in_free_throws' => true,
            'free_throws_awarded' => 2,
        ]);
    }

    /**
     * Defensive rebound.
     */
    public function defensiveRebound(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => 'rebound_defensive',
            'points' => 0,
        ]);
    }

    /**
     * Offensive rebound.
     */
    public function offensiveRebound(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => 'rebound_offensive',
            'points' => 0,
        ]);
    }

    /**
     * Assist.
     */
    public function assist(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => 'assist',
            'points' => 0,
        ]);
    }

    /**
     * Steal.
     */
    public function steal(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => 'steal',
            'points' => 0,
        ]);
    }

    /**
     * Block.
     */
    public function block(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => 'block',
            'points' => 0,
        ]);
    }

    /**
     * Turnover.
     */
    public function turnover(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => 'turnover',
            'points' => 0,
        ]);
    }

    /**
     * Substitution in.
     */
    public function substitutionIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => 'substitution_in',
            'points' => 0,
        ]);
    }

    /**
     * Substitution out.
     */
    public function substitutionOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'action_type' => 'substitution_out',
            'points' => 0,
        ]);
    }

    /**
     * Set specific period.
     */
    public function inPeriod(int $period): static
    {
        return $this->state(fn (array $attributes) => [
            'period' => $period,
        ]);
    }
}
