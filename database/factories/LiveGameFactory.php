<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\LiveGame;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LiveGame>
 */
class LiveGameFactory extends Factory
{
    protected $model = LiveGame::class;

    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'current_period' => 1,
            'period_time_remaining' => '10:00:00',
            'period_time_elapsed_seconds' => 0,
            'period_is_running' => false,
            'period_started_at' => null,
            'period_paused_at' => null,
            'shot_clock_remaining' => 24,
            'shot_clock_is_running' => false,
            'shot_clock_started_at' => null,
            'current_score_home' => 0,
            'current_score_away' => 0,
            'period_scores' => [],
            'fouls_home_period' => 0,
            'fouls_away_period' => 0,
            'fouls_home_total' => 0,
            'fouls_away_total' => 0,
            'timeouts_home_remaining' => 5,
            'timeouts_away_remaining' => 5,
            'players_on_court_home' => [],
            'players_on_court_away' => [],
            'game_phase' => 'pregame',
            'is_in_timeout' => false,
            'timeout_team' => null,
            'timeout_started_at' => null,
            'timeout_duration_seconds' => 60,
            'last_action_id' => null,
            'last_action_at' => null,
            'viewers_count' => 0,
            'is_being_broadcasted' => true,
            'broadcast_settings' => [],
            'actions_count' => 0,
            'last_update_at' => now(),
        ];
    }

    /**
     * Live game with period running.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'period_is_running' => true,
            'period_started_at' => now(),
            'shot_clock_is_running' => true,
            'shot_clock_started_at' => now(),
            'game_phase' => 'period',
        ]);
    }

    /**
     * Live game in a specific period.
     */
    public function inPeriod(int $period): static
    {
        return $this->state(fn (array $attributes) => [
            'current_period' => $period,
        ]);
    }

    /**
     * Live game with scores.
     */
    public function withScores(int $home, int $away): static
    {
        return $this->state(fn (array $attributes) => [
            'current_score_home' => $home,
            'current_score_away' => $away,
        ]);
    }

    /**
     * Live game in timeout.
     */
    public function inTimeout(string $team = 'home'): static
    {
        return $this->state(fn (array $attributes) => [
            'is_in_timeout' => true,
            'timeout_team' => $team,
            'timeout_started_at' => now(),
            'timeout_duration_seconds' => 60,
            'game_phase' => 'timeout',
            'period_is_running' => false,
        ]);
    }

    /**
     * Live game at halftime.
     */
    public function halftime(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_period' => 2,
            'game_phase' => 'halftime',
            'period_is_running' => false,
            'period_time_remaining' => '00:00:00',
        ]);
    }

    /**
     * Live game with limited timeouts.
     */
    public function withTimeouts(int $home, int $away): static
    {
        return $this->state(fn (array $attributes) => [
            'timeouts_home_remaining' => $home,
            'timeouts_away_remaining' => $away,
        ]);
    }

    /**
     * Live game with fouls.
     */
    public function withFouls(int $homePeriod, int $awayPeriod, int $homeTotal = null, int $awayTotal = null): static
    {
        return $this->state(fn (array $attributes) => [
            'fouls_home_period' => $homePeriod,
            'fouls_away_period' => $awayPeriod,
            'fouls_home_total' => $homeTotal ?? $homePeriod,
            'fouls_away_total' => $awayTotal ?? $awayPeriod,
        ]);
    }
}
