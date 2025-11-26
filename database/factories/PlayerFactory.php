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

        // Generate stats
        $fgm = $this->faker->numberBetween(0, 200);
        $fga = $this->faker->numberBetween($fgm, 400);
        $tpm = $this->faker->numberBetween(0, 80);
        $tpa = $this->faker->numberBetween($tpm, 160);
        $ftm = $this->faker->numberBetween(0, 100);
        $fta = $this->faker->numberBetween($ftm, 150);
        $rebOff = $this->faker->numberBetween(0, 50);
        $rebDef = $this->faker->numberBetween(0, 150);

        return [
            'uuid' => $this->faker->uuid(),
            'user_id' => User::factory(),

            // Physical attributes
            'height_cm' => $height,
            'weight_kg' => $this->faker->numberBetween(60, 140),
            'shoe_size' => $this->faker->numberBetween(38, 52),

            // Playing attributes
            'dominant_hand' => $this->faker->randomElement(['right', 'left', 'ambidextrous']),

            // Status (general player status)
            'status' => $this->faker->randomElement(['active', 'inactive', 'injured', 'suspended']),
            'is_rookie' => $this->faker->boolean(15),

            // Experience and ratings
            'years_experience' => $this->faker->numberBetween(0, 15),
            'shooting_rating' => $this->faker->optional(0.7)->randomFloat(1, 1.0, 10.0),
            'defense_rating' => $this->faker->optional(0.7)->randomFloat(1, 1.0, 10.0),
            'passing_rating' => $this->faker->optional(0.7)->randomFloat(1, 1.0, 10.0),
            'rebounding_rating' => $this->faker->optional(0.7)->randomFloat(1, 1.0, 10.0),
            'speed_rating' => $this->faker->optional(0.7)->randomFloat(1, 1.0, 10.0),
            'overall_rating' => $this->faker->optional(0.7)->randomFloat(1, 1.0, 10.0),

            // Statistics (individual columns, not JSON)
            'field_goals_made' => $fgm,
            'field_goals_attempted' => $fga,
            'three_pointers_made' => $tpm,
            'three_pointers_attempted' => $tpa,
            'free_throws_made' => $ftm,
            'free_throws_attempted' => $fta,
            'rebounds_offensive' => $rebOff,
            'rebounds_defensive' => $rebDef,
            'rebounds_total' => $rebOff + $rebDef,
            'assists' => $this->faker->numberBetween(0, 200),
            'steals' => $this->faker->numberBetween(0, 80),
            'blocks' => $this->faker->numberBetween(0, 50),
            'turnovers' => $this->faker->numberBetween(0, 100),
            'fouls_personal' => $this->faker->numberBetween(0, 100),
            'fouls_technical' => $this->faker->numberBetween(0, 5),

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
            'medical_clearance' => $this->faker->boolean(90),

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

            // Academic eligibility
            'academic_eligibility' => $this->faker->boolean(95),

            // Media permissions
            'allow_photos' => $this->faker->boolean(90),
            'allow_media_interviews' => $this->faker->boolean(70),

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
            'status' => 'injured',
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
        ]);
    }

    /**
     * Create an active player.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Create an inactive player.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
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