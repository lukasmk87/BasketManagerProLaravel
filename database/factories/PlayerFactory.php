<?php

namespace Database\Factories;

use App\Models\Player;
use App\Models\User;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Player>
 */
class PlayerFactory extends Factory
{
    protected $model = Player::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $positions = ['PG', 'SG', 'SF', 'PF', 'C'];
        $position = $this->faker->randomElement($positions);
        
        // Height varies by position
        $heightRange = [
            'PG' => [170, 190],
            'SG' => [180, 200], 
            'SF' => [190, 210],
            'PF' => [200, 220],
            'C' => [210, 230]
        ];
        
        $height = $this->faker->numberBetween($heightRange[$position][0], $heightRange[$position][1]);

        return [
            'uuid' => $this->faker->uuid(),
            'user_id' => User::factory(),
            
            // Note: team-specific fields (jersey_number, primary_position, secondary_positions, 
            // is_starter, is_captain) are now in the player_team pivot table
            
            // Physical attributes
            'height_cm' => $height,
            'weight_kg' => $this->faker->numberBetween(60, 140),
            'shoe_size' => $this->faker->numberBetween(38, 52),
            
            // Playing attributes
            'dominant_hand' => $this->faker->randomElement(['right', 'left', 'ambidextrous']),
            'playing_style' => $this->faker->optional(0.7)->randomElement([
                'aggressive', 'defensive', 'playmaker', 'shooter', 'athletic'
            ]),
            
            // Status (general player status, not team-specific)
            'is_active' => $this->faker->boolean(95),
            'is_injured' => $this->faker->boolean(5),
            'status' => $this->faker->randomElement(['active', 'inactive', 'injured', 'suspended']),
            'is_rookie' => $this->faker->boolean(15),
            
            // Note: Contract information (contract_start, contract_end) moved to player_team pivot table
            
            // Experience and ratings
            'years_experience' => $this->faker->numberBetween(0, 15),
            'overall_rating' => $this->faker->optional(0.7)->randomFloat(1, 1.0, 10.0),
            
            // Season statistics (current season)
            'season_stats' => json_encode([
                'games_played' => $games = $this->faker->numberBetween(0, 30),
                'games_started' => $this->faker->numberBetween(0, $games),
                'minutes_played' => $this->faker->numberBetween(0, $games * 35),
                'points' => $this->faker->numberBetween(0, $games * 25),
                'rebounds' => $this->faker->numberBetween(0, $games * 12),
                'assists' => $this->faker->numberBetween(0, $games * 8),
                'steals' => $this->faker->numberBetween(0, $games * 3),
                'blocks' => $this->faker->numberBetween(0, $games * 2),
                'turnovers' => $this->faker->numberBetween(0, $games * 4),
                'fouls' => $this->faker->numberBetween(0, $games * 5),
                'field_goals_made' => $fgm = $this->faker->numberBetween(0, $games * 10),
                'field_goals_attempted' => $this->faker->numberBetween($fgm, $games * 20),
                'three_pointers_made' => $tpm = $this->faker->numberBetween(0, $games * 4),
                'three_pointers_attempted' => $this->faker->numberBetween($tpm, $games * 10),
                'free_throws_made' => $ftm = $this->faker->numberBetween(0, $games * 5),
                'free_throws_attempted' => $this->faker->numberBetween($ftm, $games * 8),
            ]),
            
            // Career statistics
            'career_stats' => json_encode([
                'total_games' => $totalGames = $this->faker->numberBetween($games, 200),
                'total_points' => $this->faker->numberBetween(0, $totalGames * 20),
                'total_rebounds' => $this->faker->numberBetween(0, $totalGames * 10),
                'total_assists' => $this->faker->numberBetween(0, $totalGames * 6),
                'career_high_points' => $this->faker->numberBetween(10, 50),
                'career_high_rebounds' => $this->faker->numberBetween(5, 20),
                'career_high_assists' => $this->faker->numberBetween(3, 15),
            ]),
            
            // Training and development
            'training_focus_areas' => json_encode($this->faker->optional(0.8)->randomElements([
                'shooting', 'defense', 'ball_handling', 'rebounding', 
                'passing', 'footwork', 'conditioning', 'mental_game'
            ], $this->faker->numberBetween(1, 4))),
            
            'development_goals' => json_encode($this->faker->optional(0.6)->randomElements([
                'Improve free throw percentage',
                'Increase three-point accuracy',
                'Better defensive positioning',
                'Develop leadership skills',
                'Enhance court vision',
                'Build muscle mass',
                'Improve endurance'
            ], $this->faker->numberBetween(1, 3))),
            
            // Medical information
            'medical_conditions' => json_encode($this->faker->optional(0.2)->randomElements([
                'asthma', 'diabetes', 'previous_knee_injury', 'ankle_problems'
            ], 1)),
            
            'injury_history' => json_encode($this->faker->optional(0.3)->randomElements([
                ['injury' => 'sprained ankle', 'date' => $this->faker->date(), 'recovered' => true],
                ['injury' => 'minor knee strain', 'date' => $this->faker->date(), 'recovered' => true],
            ], 1)),
            
            // Personal preferences
            'preferences' => json_encode([
                'preferred_practice_time' => $this->faker->randomElement(['morning', 'afternoon', 'evening']),
                'position_flexibility' => $this->faker->boolean(60),
                'leadership_interest' => $this->faker->boolean(40),
            ]),
            
            // Notes from coaches
            'coach_notes' => $this->faker->optional(0.5)->paragraph(2),
            
            // Emergency medical contact
            'emergency_medical_contact' => $this->faker->optional(0.8)->name(),
            'emergency_medical_phone' => $this->faker->optional(0.8)->phoneNumber(),
            
            'created_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
        ];
    }

    /**
     * Create a player with starter status.
     * Note: is_starter is now a team-specific attribute in the pivot table.
     * This method now just sets general attributes for a high-performing player.
     */
    public function starter(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'status' => 'active',
            'overall_rating' => $this->faker->randomFloat(1, 6.0, 10.0),
        ]);
    }

    /**
     * Create a player with captain-like attributes.
     * Note: is_captain is now a team-specific attribute in the pivot table.
     * This method sets general attributes for a leadership-quality player.
     */
    public function captain(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'status' => 'active',
            'years_experience' => $this->faker->numberBetween(5, 15),
            'overall_rating' => $this->faker->randomFloat(1, 7.0, 10.0),
        ]);
    }

    /**
     * Create an injured player.
     */
    public function injured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_injured' => true,
            'status' => 'injured',
            'injury_history' => json_encode([
                [
                    'injury' => $this->faker->randomElement(['sprained ankle', 'knee strain', 'back pain', 'shoulder injury']),
                    'date' => $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
                    'recovered' => false,
                    'expected_return' => $this->faker->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
                ]
            ]),
        ]);
    }

    /**
     * Create a rookie player.
     */
    public function rookie(): static
    {
        return $this->state(fn (array $attributes) => [
            'years_experience' => $this->faker->numberBetween(0, 2),
            'is_rookie' => true,
            'overall_rating' => $this->faker->randomFloat(1, 3.0, 6.0),
            'season_stats' => json_encode([
                'games_played' => $games = $this->faker->numberBetween(0, 15),
                'games_started' => 0,
                'minutes_played' => $this->faker->numberBetween(0, $games * 15),
                'points' => $this->faker->numberBetween(0, $games * 8),
                'rebounds' => $this->faker->numberBetween(0, $games * 4),
                'assists' => $this->faker->numberBetween(0, $games * 2),
            ]),
        ]);
    }

    /**
     * Create a veteran player.
     */
    public function veteran(): static
    {
        return $this->state(fn (array $attributes) => [
            'years_experience' => $this->faker->numberBetween(8, 20),
            'overall_rating' => $this->faker->randomFloat(1, 6.0, 9.5),
            'career_stats' => json_encode([
                'total_games' => $totalGames = $this->faker->numberBetween(100, 500),
                'total_points' => $this->faker->numberBetween($totalGames * 8, $totalGames * 25),
                'total_rebounds' => $this->faker->numberBetween($totalGames * 3, $totalGames * 12),
                'total_assists' => $this->faker->numberBetween($totalGames * 2, $totalGames * 8),
                'career_high_points' => $this->faker->numberBetween(25, 50),
                'career_high_rebounds' => $this->faker->numberBetween(10, 20),
                'career_high_assists' => $this->faker->numberBetween(8, 15),
            ]),
        ]);
    }

    /**
     * Create a player suited for a specific position (height-wise).
     * Note: Position is now stored in the player_team pivot table.
     * This method only sets physical attributes suitable for the position.
     */
    public function forPosition(string $position): static
    {
        $heightRanges = [
            'PG' => [170, 190],
            'SG' => [180, 200], 
            'SF' => [190, 210],
            'PF' => [200, 220],
            'C' => [210, 230]
        ];

        $height = $this->faker->numberBetween(
            $heightRanges[$position][0] ?? 170, 
            $heightRanges[$position][1] ?? 210
        );

        return $this->state(fn (array $attributes) => [
            'height_cm' => $height,
            'weight_kg' => $this->faker->numberBetween(
                max(60, $height - 80), 
                min(140, $height - 40)
            ),
        ]);
    }
}