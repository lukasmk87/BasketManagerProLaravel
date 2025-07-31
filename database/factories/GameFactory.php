<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\Team;
use App\Models\User;
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
        $gameTypes = ['regular', 'playoff', 'friendly', 'tournament', 'cup'];
        $venues = [
            'Hauptsporthalle', 'Vereinshalle', 'Schulsporthalle', 
            'Mehrzweckhalle', 'Basketballzentrum', 'Gemeindehalle'
        ];

        $currentYear = date('Y');
        $season = $currentYear . '-' . substr($currentYear + 1, 2);

        return [
            'uuid' => $this->faker->uuid(),
            'home_team_id' => Team::factory(),
            'away_team_id' => Team::factory(),
            
            // Game scheduling
            'scheduled_at' => $this->faker->dateTimeBetween('-1 month', '+3 months'),
            'actual_start_time' => null,
            'actual_end_time' => null,
            
            // Game information
            'season' => $season,
            'game_type' => $this->faker->randomElement($gameTypes),
            'league' => $this->faker->randomElement([
                'Bundesliga', 'Regionalliga', 'Landesliga', 
                'Bezirksliga', 'Kreisliga', 'Jugendliga'
            ]),
            'round' => $this->faker->optional(0.7)->numberBetween(1, 30),
            
            // Venue information
            'venue' => $this->faker->randomElement($venues),
            'venue_address' => $this->faker->address(),
            'venue_details' => json_encode([
                'capacity' => $this->faker->numberBetween(100, 3000),
                'court_type' => $this->faker->randomElement(['hardwood', 'synthetic']),
                'parking_available' => $this->faker->boolean(80),
            ]),
            
            // Officials
            'referee_1' => $this->faker->optional(0.8)->name(),
            'referee_2' => $this->faker->optional(0.6)->name(),
            'table_official' => $this->faker->optional(0.7)->name(),
            'timekeeper' => $this->faker->optional(0.5)->name(),
            'scorekeeper' => $this->faker->optional(0.5)->name(),
            
            // Game status and results
            'status' => $this->faker->randomElement(['scheduled', 'in_progress', 'completed', 'cancelled', 'postponed']),
            'home_team_score' => null,
            'away_team_score' => null,
            'winning_team_id' => null,
            
            // Game periods/quarters
            'periods_played' => 0,
            'overtime_periods' => 0,
            'period_scores' => json_encode([]),
            
            // Game settings
            'period_duration' => 10, // minutes
            'total_periods' => 4,
            'shot_clock_duration' => 24, // seconds
            'timeout_duration' => 60, // seconds
            
            // Statistics tracking
            'enable_live_scoring' => $this->faker->boolean(70),
            'public_scoring' => $this->faker->boolean(80),
            'statistics_enabled' => $this->faker->boolean(90),
            
            // Streaming and media
            'is_streamed' => $this->faker->boolean(20),
            'stream_url' => $this->faker->optional(0.2)->url(),
            'video_replay_available' => $this->faker->boolean(30),
            
            // Tournament information (if applicable)
            'tournament_id' => null,
            'tournament_round' => $this->faker->optional(0.3)->randomElement([
                'group_stage', 'round_of_16', 'quarterfinals', 'semifinals', 'finals'
            ]),
            
            // Weather conditions (for outdoor games)
            'weather_conditions' => $this->faker->optional(0.1)->randomElement([
                'sunny', 'cloudy', 'rainy', 'windy'
            ]),
            'temperature' => $this->faker->optional(0.1)->numberBetween(-5, 35),
            
            // Ticket and admission
            'admission_fee' => $this->faker->optional(0.6)->randomFloat(2, 0, 25),
            'ticket_sales_enabled' => $this->faker->boolean(40),
            'expected_attendance' => $this->faker->optional(0.7)->numberBetween(20, 500),
            'actual_attendance' => null,
            
            // Game notes and reports
            'pre_game_notes' => $this->faker->optional(0.3)->paragraph(1),
            'post_game_notes' => null,
            'referee_report' => null,
            
            // Technical information
            'game_sheet_submitted' => false,
            'statistics_verified' => false,
            'result_confirmed' => false,
            
            // Social media and coverage
            'social_media_coverage' => $this->faker->boolean(50),
            'media_coverage' => $this->faker->optional(0.2)->randomElement([
                'local_newspaper', 'radio', 'tv', 'online'
            ]),
            
            // Emergency and safety
            'medical_staff_present' => $this->faker->boolean(60),
            'security_present' => $this->faker->boolean(30),
            'emergency_contacts' => json_encode([
                [
                    'name' => $this->faker->name(),
                    'phone' => $this->faker->phoneNumber(),
                    'role' => 'Spielleiter',
                ],
            ]),
            
            // Additional metadata
            'metadata' => json_encode([
                'created_by' => 'system',
                'importance_level' => $this->faker->randomElement(['low', 'medium', 'high']),
                'fan_interest_level' => $this->faker->randomElement(['low', 'medium', 'high']),
            ]),
            
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
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
        
        $winningTeamId = $homeScore > $awayScore ? 1 : 2; // Will be properly set in afterCreating

        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'home_team_score' => $homeScore,
            'away_team_score' => $awayScore,
            'winning_team_id' => null, // Will be set after creation
            'periods_played' => 4,
            'overtime_periods' => $this->faker->boolean(10) ? $this->faker->numberBetween(1, 2) : 0,
            'actual_start_time' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'actual_end_time' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'period_scores' => json_encode([
                'Q1' => ['home' => $this->faker->numberBetween(10, 30), 'away' => $this->faker->numberBetween(10, 30)],
                'Q2' => ['home' => $this->faker->numberBetween(10, 30), 'away' => $this->faker->numberBetween(10, 30)],
                'Q3' => ['home' => $this->faker->numberBetween(10, 30), 'away' => $this->faker->numberBetween(10, 30)],
                'Q4' => ['home' => $this->faker->numberBetween(10, 30), 'away' => $this->faker->numberBetween(10, 30)],
            ]),
            'actual_attendance' => $this->faker->numberBetween(20, 300),
            'game_sheet_submitted' => true,
            'statistics_verified' => $this->faker->boolean(80),
            'result_confirmed' => true,
            'post_game_notes' => $this->faker->optional(0.7)->paragraph(2),
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
            'home_team_score' => null,
            'away_team_score' => null,
            'winning_team_id' => null,
            'actual_start_time' => null,
            'actual_end_time' => null,
            'periods_played' => 0,
            'actual_attendance' => null,
        ]);
    }

    /**
     * Create a live game in progress.
     */
    public function live(): static
    {
        $periodsPlayed = $this->faker->numberBetween(1, 4);
        $homeScore = $this->faker->numberBetween(20, 80);
        $awayScore = $this->faker->numberBetween(20, 80);

        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'scheduled_at' => $this->faker->dateTimeBetween('-2 hours', 'now'),
            'actual_start_time' => $this->faker->dateTimeBetween('-2 hours', 'now'),
            'home_team_score' => $homeScore,
            'away_team_score' => $awayScore,
            'periods_played' => $periodsPlayed,
            'enable_live_scoring' => true,
            'public_scoring' => true,
            'statistics_enabled' => true,
            'actual_attendance' => $this->faker->numberBetween(50, 200),
        ]);
    }

    /**
     * Create a cancelled game.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'home_team_score' => null,
            'away_team_score' => null,
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
            'game_type' => 'playoff',
            'tournament_round' => $this->faker->randomElement([
                'round_of_16', 'quarterfinals', 'semifinals', 'finals'
            ]),
            'importance_level' => 'high',
            'expected_attendance' => $this->faker->numberBetween(100, 1000),
            'admission_fee' => $this->faker->randomFloat(2, 5, 20),
            'is_streamed' => $this->faker->boolean(60),
            'media_coverage' => $this->faker->randomElement(['radio', 'tv', 'online']),
        ]);
    }

    /**
     * Create a friendly/exhibition game.
     */
    public function friendly(): static
    {
        return $this->state(fn (array $attributes) => [
            'game_type' => 'friendly',
            'league' => null,
            'round' => null,
            'admission_fee' => $this->faker->optional(0.3)->randomFloat(2, 0, 5),
            'statistics_enabled' => $this->faker->boolean(70),
            'public_scoring' => $this->faker->boolean(60),
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