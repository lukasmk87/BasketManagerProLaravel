<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game>
 */
class GameFactory extends Factory
{
    protected $model = Game::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gameTypes = ['regular_season', 'playoff', 'championship', 'friendly', 'tournament', 'scrimmage'];
        $venues = [
            'Hauptsporthalle', 'Vereinshalle', 'Schulsporthalle',
            'Mehrzweckhalle', 'Basketballzentrum', 'Gemeindehalle'
        ];

        $currentYear = date('Y');
        $season = $currentYear . '-' . substr($currentYear + 1, 2);

        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => null,
            'home_team_id' => Team::factory(),
            'away_team_id' => Team::factory(),
            'home_team_name' => null,
            'away_team_name' => null,

            // Game scheduling
            'scheduled_at' => $this->faker->dateTimeBetween('-1 month', '+3 months'),
            'actual_start_time' => null,
            'actual_end_time' => null,

            // Venue information
            'venue' => $this->faker->randomElement($venues),
            'venue_address' => $this->faker->address(),
            'venue_code' => null,

            // Import information
            'import_source' => 'manual',
            'external_game_id' => null,
            'import_metadata' => null,
            'external_url' => null,
            'is_home_game' => $this->faker->boolean(50),

            // Game information
            'type' => $this->faker->randomElement($gameTypes),
            'season' => $season,
            'season_id' => null,
            'league' => $this->faker->randomElement([
                'Bundesliga', 'Regionalliga', 'Landesliga',
                'Bezirksliga', 'Kreisliga', 'Jugendliga'
            ]),
            'division' => $this->faker->optional(0.5)->randomElement(['A', 'B', 'C']),

            // Game status and results
            'status' => 'scheduled',
            'home_team_score' => 0,
            'away_team_score' => 0,
            'period_scores' => [],
            'current_period' => 0,
            'total_periods' => 4,
            'period_length_minutes' => 10,
            'time_remaining_seconds' => 600,
            'clock_running' => false,
            'overtime_periods' => 0,
            'overtime_length_minutes' => 5,

            // Officials (JSON arrays)
            'referees' => [],
            'scorekeepers' => [],
            'timekeepers' => [],

            // Statistics (JSON)
            'team_stats' => null,
            'player_stats' => null,
            'live_commentary' => null,
            'play_by_play' => null,
            'substitutions' => null,
            'timeouts' => null,
            'team_fouls' => null,
            'technical_fouls' => null,
            'ejections' => null,

            // Result
            'result' => null,
            'winning_team_id' => null,
            'point_differential' => null,

            // Tournament information
            'tournament_id' => null,
            'tournament_round' => null,
            'tournament_game_number' => null,

            // Conditions
            'weather_conditions' => null,
            'temperature' => null,
            'court_conditions' => null,

            // Streaming and media
            'is_streamed' => $this->faker->boolean(20),
            'stream_url' => null,
            'media_links' => null,

            // Game notes
            'pre_game_notes' => null,
            'post_game_notes' => null,
            'referee_report' => null,
            'incident_report' => null,

            // Attendance
            'attendance' => null,
            'capacity' => null,
            'ticket_prices' => null,

            // Game rules
            'game_rules' => null,
            'allow_spectators' => true,
            'allow_media' => true,
            'emergency_contacts' => null,
            'medical_staff_present' => $this->faker->boolean(60),
            'allow_recording' => true,
            'allow_photos' => true,
            'allow_streaming' => true,

            // Registration settings
            'registration_deadline_hours' => 24,
            'max_roster_size' => 15,
            'min_roster_size' => 8,
            'allow_player_registrations' => true,
            'auto_confirm_registrations' => false,
            'lineup_deadline_hours' => 1,

            // Stats verification
            'stats_verified' => false,
            'stats_verified_at' => null,
            'stats_verified_by' => null,
        ];
    }

    /**
     * Create a completed game with scores.
     */
    public function completed(): static
    {
        $homeScore = $this->faker->numberBetween(60, 120);
        $awayScore = $this->faker->numberBetween(60, 120);

        // Ensure different scores
        if ($homeScore === $awayScore) {
            $awayScore += $this->faker->randomElement([-3, -2, -1, 1, 2, 3]);
        }

        return $this->state(fn (array $attributes) => [
            'status' => 'finished',
            'home_team_score' => $homeScore,
            'away_team_score' => $awayScore,
            'overtime_periods' => $this->faker->boolean(10) ? $this->faker->numberBetween(1, 2) : 0,
            'actual_start_time' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'actual_end_time' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'period_scores' => [
                'Q1' => ['home' => $this->faker->numberBetween(10, 30), 'away' => $this->faker->numberBetween(10, 30)],
                'Q2' => ['home' => $this->faker->numberBetween(10, 30), 'away' => $this->faker->numberBetween(10, 30)],
                'Q3' => ['home' => $this->faker->numberBetween(10, 30), 'away' => $this->faker->numberBetween(10, 30)],
                'Q4' => ['home' => $this->faker->numberBetween(10, 30), 'away' => $this->faker->numberBetween(10, 30)],
            ],
            'attendance' => $this->faker->numberBetween(20, 300),
            'stats_verified' => $this->faker->boolean(80),
            'result' => 'final',
        ]);
    }

    /**
     * Create a scheduled upcoming game.
     */
    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+2 months'),
            'home_team_score' => 0,
            'away_team_score' => 0,
            'winning_team_id' => null,
            'actual_start_time' => null,
            'actual_end_time' => null,
        ]);
    }

    /**
     * Create a live game in progress.
     */
    public function live(): static
    {
        $currentPeriod = $this->faker->numberBetween(1, 4);
        $homeScore = $this->faker->numberBetween(20, 80);
        $awayScore = $this->faker->numberBetween(20, 80);

        return $this->state(fn (array $attributes) => [
            'status' => 'live',
            'scheduled_at' => $this->faker->dateTimeBetween('-2 hours', 'now'),
            'actual_start_time' => $this->faker->dateTimeBetween('-2 hours', 'now'),
            'home_team_score' => $homeScore,
            'away_team_score' => $awayScore,
            'current_period' => $currentPeriod,
            'clock_running' => true,
            'attendance' => $this->faker->numberBetween(50, 200),
        ]);
    }

    /**
     * Create a cancelled game.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'home_team_score' => 0,
            'away_team_score' => 0,
            'winning_team_id' => null,
            'post_game_notes' => $this->faker->randomElement([
                'Weather conditions',
                'Venue unavailable',
                'Team unavailable',
                'Official unavailable',
                'Safety concerns'
            ]),
        ]);
    }

    /**
     * Create a playoff game.
     */
    public function playoff(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'playoff',
            'tournament_round' => $this->faker->randomElement([
                'round_of_16', 'quarterfinals', 'semifinals', 'finals'
            ]),
            'is_streamed' => $this->faker->boolean(60),
        ]);
    }

    /**
     * Create a friendly/exhibition game.
     */
    public function friendly(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'friendly',
            'league' => null,
        ]);
    }

    /**
     * Configure the game after creation.
     */
    public function configure()
    {
        return $this->afterCreating(function (Game $game) {
            // Set winning team if game is completed
            if ($game->status === 'completed' && $game->home_team_score !== null && $game->away_team_score !== null) {
                $winningTeamId = $game->home_team_score > $game->away_team_score
                    ? $game->home_team_id
                    : $game->away_team_id;

                $game->update(['winning_team_id' => $winningTeamId]);
            }
        });
    }
}
