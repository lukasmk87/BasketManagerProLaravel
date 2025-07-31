<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\Club;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $teamNames = [
            'Lions', 'Eagles', 'Panthers', 'Wolves', 'Tigers', 'Bears', 
            'Hawks', 'Bulls', 'Sharks', 'Dragons', 'Thunder', 'Lightning',
            'Warriors', 'Knights', 'Rebels', 'Storm', 'Fire', 'Ice'
        ];
        
        $name = $this->faker->randomElement($teamNames);
        $ageGroup = $this->faker->randomElement(['u12', 'u14', 'u16', 'u18', 'u20', 'senior']);
        $gender = $this->faker->randomElement(['male', 'female', 'mixed']);
        
        if ($ageGroup !== 'senior') {
            $fullName = $name . ' ' . strtoupper($ageGroup);
        } else {
            $fullName = $name . ($gender !== 'mixed' ? ' ' . ucfirst($gender) : '');
        }

        $currentYear = date('Y');
        $season = $currentYear . '-' . substr($currentYear + 1, 2);

        return [
            'uuid' => $this->faker->uuid(),
            'club_id' => Club::factory(),
            'name' => $fullName,
            'short_name' => strtoupper(substr($name, 0, 3)),
            'slug' => Str::slug($fullName . '-' . $season),
            'description' => $this->faker->optional(0.7)->paragraph(2),
            'logo_path' => null,
            
            // Team classification
            'gender' => $gender,
            'age_group' => $ageGroup,
            'division' => $this->faker->optional(0.6)->randomElement(['A', 'B', 'C']),
            'league' => $this->faker->randomElement([
                'Bundesliga', 'Regionalliga', 'Landesliga', 
                'Bezirksliga', 'Kreisliga', 'Jugendliga'
            ]),
            
            // Season information
            'season' => $season,
            'season_start' => $this->faker->dateTimeBetween('now', '+2 months'),
            'season_end' => $this->faker->dateTimeBetween('+8 months', '+10 months'),
            
            // Team colors
            'primary_color' => $this->faker->hexColor(),
            'secondary_color' => $this->faker->hexColor(),
            'jersey_home_color' => $this->faker->hexColor(),
            'jersey_away_color' => $this->faker->hexColor(),
            
            // Team settings
            'max_players' => $this->faker->numberBetween(12, 20),
            'min_players' => $this->faker->numberBetween(8, 12),
            
            // Training schedule
            'training_schedule' => json_encode([
                [
                    'day' => 'tuesday',
                    'start_time' => '18:00',
                    'end_time' => '20:00',
                    'venue' => 'Sporthalle 1',
                ],
                [
                    'day' => 'thursday',
                    'start_time' => '18:00',
                    'end_time' => '20:00',
                    'venue' => 'Sporthalle 1',
                ],
            ]),
            
            'practice_times' => json_encode([
                'warmup_duration' => 15,
                'skills_duration' => 30,
                'scrimmage_duration' => 45,
                'cooldown_duration' => 10,
            ]),
            
            // Coach assignments
            'head_coach_id' => null,
            'assistant_coaches' => json_encode([]),
            
            // Team statistics (initial values)
            'games_played' => 0,
            'games_won' => 0,
            'games_lost' => 0,
            'games_tied' => 0,
            'points_scored' => 0,
            'points_allowed' => 0,
            
            // Team preferences
            'preferences' => json_encode([
                'preferred_game_time' => $this->faker->randomElement(['morning', 'afternoon', 'evening']),
                'home_court_advantage' => $this->faker->boolean(80),
                'travel_distance_max' => $this->faker->numberBetween(50, 200),
            ]),
            
            'settings' => json_encode([
                'allow_public_stats' => $this->faker->boolean(70),
                'enable_live_scoring' => $this->faker->boolean(60),
                'require_parent_permission' => $ageGroup !== 'senior',
            ]),
            
            // Status
            'is_active' => $this->faker->boolean(90),
            'is_recruiting' => $this->faker->boolean(40),
            'status' => $this->faker->randomElement(['active', 'inactive', 'suspended']),
            
            // Home venue information
            'home_venue' => $this->faker->optional(0.8)->words(3, true) . ' Sporthalle',
            'home_venue_address' => $this->faker->optional(0.8)->address(),
            'venue_details' => json_encode([
                'capacity' => $this->faker->numberBetween(100, 2000),
                'court_type' => $this->faker->randomElement(['hardwood', 'synthetic', 'concrete']),
                'parking_available' => $this->faker->boolean(80),
                'accessibility' => $this->faker->boolean(70),
            ]),
            
            // Registration and certification
            'registration_number' => $this->faker->optional(0.6)->regexify('T[0-9]{8}'),
            'is_certified' => $this->faker->boolean(70),
            'certified_at' => $this->faker->optional(0.7)->dateTimeBetween('-1 year', 'now'),
            
            // Emergency contacts
            'emergency_contacts' => json_encode([
                [
                    'name' => $this->faker->name(),
                    'phone' => $this->faker->phoneNumber(),
                    'relationship' => 'Teammanager',
                ],
            ]),
            
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the team has played some games.
     */
    public function withGames(): static
    {
        $gamesPlayed = $this->faker->numberBetween(5, 25);
        $gamesWon = $this->faker->numberBetween(0, $gamesPlayed);
        $gamesLost = $gamesPlayed - $gamesWon;
        
        return $this->state(fn (array $attributes) => [
            'games_played' => $gamesPlayed,
            'games_won' => $gamesWon,
            'games_lost' => $gamesLost,
            'games_tied' => 0,
            'points_scored' => $this->faker->numberBetween($gamesPlayed * 60, $gamesPlayed * 120),
            'points_allowed' => $this->faker->numberBetween($gamesPlayed * 55, $gamesPlayed * 115),
        ]);
    }

    /**
     * Create a youth team.
     */
    public function youth(): static
    {
        $ageGroup = $this->faker->randomElement(['u12', 'u14', 'u16', 'u18']);
        
        return $this->state(fn (array $attributes) => [
            'age_group' => $ageGroup,
            'max_players' => $this->faker->numberBetween(10, 15),
            'min_players' => $this->faker->numberBetween(8, 10),
            'league' => 'Jugendliga',
            'settings' => json_encode([
                'allow_public_stats' => $this->faker->boolean(50),
                'enable_live_scoring' => $this->faker->boolean(30),
                'require_parent_permission' => true,
            ]),
        ]);
    }

    /**
     * Create a senior team.
     */
    public function senior(): static
    {
        return $this->state(fn (array $attributes) => [
            'age_group' => 'senior',
            'max_players' => $this->faker->numberBetween(15, 20),
            'min_players' => $this->faker->numberBetween(10, 12),
            'league' => $this->faker->randomElement(['Bundesliga', 'Regionalliga', 'Landesliga']),
            'settings' => json_encode([
                'allow_public_stats' => $this->faker->boolean(80),
                'enable_live_scoring' => $this->faker->boolean(70),
                'require_parent_permission' => false,
            ]),
        ]);
    }

    /**
     * Create an inactive team.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'status' => 'inactive',
            'is_recruiting' => false,
        ]);
    }

    /**
     * Create a team that's currently recruiting.
     */
    public function recruiting(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_recruiting' => true,
            'status' => 'active',
            'is_active' => true,
        ]);
    }
}