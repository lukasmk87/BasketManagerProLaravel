<?php

namespace Database\Factories;

use App\Models\BasketballTeam;
use App\Models\Club;
use App\Models\Season;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BasketballTeam>
 */
class BasketballTeamFactory extends Factory
{
    protected $model = BasketballTeam::class;

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
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'personal_team' => false,
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
            'season_id' => null,
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

            // Practice times (note: training_schedule does not exist in DB)
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
            'is_active' => true,
            'is_recruiting' => $this->faker->boolean(40),
            'status' => 'active',

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

            'created_at' => now(),
        ];
    }

    /**
     * Set a specific club for the team.
     */
    public function forClub(Club $club): static
    {
        return $this->state(fn (array $attributes) => [
            'club_id' => $club->id,
        ]);
    }

    /**
     * Set a specific season for the team.
     */
    public function forSeason(Season $season): static
    {
        return $this->state(fn (array $attributes) => [
            'season_id' => $season->id,
            'season' => $season->name,
            'season_start' => $season->start_date,
            'season_end' => $season->end_date,
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
}
