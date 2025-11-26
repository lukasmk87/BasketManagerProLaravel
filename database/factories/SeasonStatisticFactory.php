<?php

namespace Database\Factories;

use App\Models\BasketballTeam;
use App\Models\Club;
use App\Models\Player;
use App\Models\Season;
use App\Models\SeasonStatistic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SeasonStatistic>
 */
class SeasonStatisticFactory extends Factory
{
    protected $model = SeasonStatistic::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gamesPlayed = $this->faker->numberBetween(10, 30);
        $gamesStarted = $this->faker->numberBetween(0, $gamesPlayed);
        $minutesPlayed = $gamesPlayed * $this->faker->numberBetween(15, 35);

        // Field Goals
        $fgAttempted = $gamesPlayed * $this->faker->numberBetween(5, 15);
        $fgMade = (int) ($fgAttempted * $this->faker->randomFloat(2, 0.35, 0.55));
        $fgPercentage = $fgAttempted > 0 ? round(($fgMade / $fgAttempted) * 100, 2) : 0;

        // Three Pointers
        $threePAttempted = $gamesPlayed * $this->faker->numberBetween(1, 8);
        $threePMade = (int) ($threePAttempted * $this->faker->randomFloat(2, 0.25, 0.45));
        $threePPercentage = $threePAttempted > 0 ? round(($threePMade / $threePAttempted) * 100, 2) : 0;

        // Free Throws
        $ftAttempted = $gamesPlayed * $this->faker->numberBetween(2, 8);
        $ftMade = (int) ($ftAttempted * $this->faker->randomFloat(2, 0.65, 0.90));
        $ftPercentage = $ftAttempted > 0 ? round(($ftMade / $ftAttempted) * 100, 2) : 0;

        // Calculate points
        $points = ($fgMade - $threePMade) * 2 + $threePMade * 3 + $ftMade;

        // Rebounds
        $reboundsOffensive = $gamesPlayed * $this->faker->numberBetween(0, 3);
        $reboundsDefensive = $gamesPlayed * $this->faker->numberBetween(1, 6);
        $reboundsTotal = $reboundsOffensive + $reboundsDefensive;

        // Assists and Turnovers
        $assists = $gamesPlayed * $this->faker->numberBetween(1, 8);
        $turnovers = $gamesPlayed * $this->faker->numberBetween(1, 4);
        $astToRatio = $turnovers > 0 ? round($assists / $turnovers, 2) : $assists;

        return [
            'player_id' => Player::factory(),
            'season_id' => Season::factory(),
            'team_id' => BasketballTeam::factory(),
            'club_id' => Club::factory(),
            'games_played' => $gamesPlayed,
            'games_started' => $gamesStarted,
            'minutes_played' => $minutesPlayed,
            'points' => $points,
            'field_goals_made' => $fgMade,
            'field_goals_attempted' => $fgAttempted,
            'field_goal_percentage' => $fgPercentage,
            'three_pointers_made' => $threePMade,
            'three_pointers_attempted' => $threePAttempted,
            'three_point_percentage' => $threePPercentage,
            'free_throws_made' => $ftMade,
            'free_throws_attempted' => $ftAttempted,
            'free_throw_percentage' => $ftPercentage,
            'rebounds_offensive' => $reboundsOffensive,
            'rebounds_defensive' => $reboundsDefensive,
            'rebounds_total' => $reboundsTotal,
            'assists' => $assists,
            'turnovers' => $turnovers,
            'assist_turnover_ratio' => $astToRatio,
            'steals' => $gamesPlayed * $this->faker->numberBetween(0, 3),
            'blocks' => $gamesPlayed * $this->faker->numberBetween(0, 2),
            'fouls_personal' => $gamesPlayed * $this->faker->numberBetween(1, 4),
            'fouls_technical' => $this->faker->numberBetween(0, 2),
            'fouls_flagrant' => 0,
            'advanced_stats' => [
                'per' => $this->faker->randomFloat(1, 8, 25),
                'ts_percentage' => $this->faker->randomFloat(1, 45, 65),
                'usage_rate' => $this->faker->randomFloat(1, 15, 30),
            ],
            'game_highs' => [
                'points' => $this->faker->numberBetween(15, 40),
                'rebounds' => $this->faker->numberBetween(8, 18),
                'assists' => $this->faker->numberBetween(5, 15),
            ],
            'metadata' => [],
            'snapshot_date' => now(),
        ];
    }

    /**
     * Create a high-scoring player statistics.
     */
    public function highScorer(): static
    {
        return $this->state(function (array $attributes) {
            $gamesPlayed = $this->faker->numberBetween(25, 35);
            $fgAttempted = $gamesPlayed * 18;
            $fgMade = (int) ($fgAttempted * 0.52);
            $threePAttempted = $gamesPlayed * 7;
            $threePMade = (int) ($threePAttempted * 0.42);
            $ftAttempted = $gamesPlayed * 8;
            $ftMade = (int) ($ftAttempted * 0.88);
            $points = ($fgMade - $threePMade) * 2 + $threePMade * 3 + $ftMade;

            return [
                'games_played' => $gamesPlayed,
                'games_started' => $gamesPlayed,
                'minutes_played' => $gamesPlayed * 35,
                'points' => $points,
                'field_goals_made' => $fgMade,
                'field_goals_attempted' => $fgAttempted,
                'field_goal_percentage' => 52.0,
                'three_pointers_made' => $threePMade,
                'three_pointers_attempted' => $threePAttempted,
                'three_point_percentage' => 42.0,
                'free_throws_made' => $ftMade,
                'free_throws_attempted' => $ftAttempted,
                'free_throw_percentage' => 88.0,
                'advanced_stats' => [
                    'per' => 24.5,
                    'ts_percentage' => 62.0,
                    'usage_rate' => 28.0,
                ],
                'game_highs' => [
                    'points' => 45,
                    'rebounds' => 12,
                    'assists' => 10,
                ],
            ];
        });
    }

    /**
     * Create rookie statistics (low games played, developing stats).
     */
    public function rookie(): static
    {
        return $this->state(function (array $attributes) {
            $gamesPlayed = $this->faker->numberBetween(5, 10);

            return [
                'games_played' => $gamesPlayed,
                'games_started' => 0,
                'minutes_played' => $gamesPlayed * 12,
                'points' => $gamesPlayed * 4,
                'field_goals_made' => $gamesPlayed * 2,
                'field_goals_attempted' => $gamesPlayed * 5,
                'field_goal_percentage' => 40.0,
                'three_pointers_made' => $gamesPlayed,
                'three_pointers_attempted' => $gamesPlayed * 3,
                'three_point_percentage' => 33.0,
                'free_throws_made' => $gamesPlayed,
                'free_throws_attempted' => $gamesPlayed * 2,
                'free_throw_percentage' => 50.0,
                'rebounds_total' => $gamesPlayed * 2,
                'assists' => $gamesPlayed,
                'turnovers' => $gamesPlayed * 2,
                'assist_turnover_ratio' => 0.5,
            ];
        });
    }

    /**
     * Create statistics with zero attempts (to test division by zero).
     */
    public function zeroAttempts(): static
    {
        return $this->state(fn (array $attributes) => [
            'games_played' => 0,
            'games_started' => 0,
            'minutes_played' => 0,
            'points' => 0,
            'field_goals_made' => 0,
            'field_goals_attempted' => 0,
            'field_goal_percentage' => 0,
            'three_pointers_made' => 0,
            'three_pointers_attempted' => 0,
            'three_point_percentage' => 0,
            'free_throws_made' => 0,
            'free_throws_attempted' => 0,
            'free_throw_percentage' => 0,
            'rebounds_total' => 0,
            'assists' => 0,
            'turnovers' => 0,
            'assist_turnover_ratio' => 0,
        ]);
    }

    /**
     * Set a specific player for the statistics.
     */
    public function forPlayer(Player $player): static
    {
        return $this->state(fn (array $attributes) => [
            'player_id' => $player->id,
        ]);
    }

    /**
     * Set a specific season for the statistics.
     */
    public function forSeason(Season $season): static
    {
        return $this->state(fn (array $attributes) => [
            'season_id' => $season->id,
        ]);
    }

    /**
     * Set a specific team for the statistics.
     */
    public function forTeam(BasketballTeam $team): static
    {
        return $this->state(fn (array $attributes) => [
            'team_id' => $team->id,
        ]);
    }

    /**
     * Set a specific club for the statistics.
     */
    public function forClub(Club $club): static
    {
        return $this->state(fn (array $attributes) => [
            'club_id' => $club->id,
        ]);
    }
}
