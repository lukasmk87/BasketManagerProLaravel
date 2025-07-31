<?php

namespace Database\Factories;

use App\Models\Club;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Club>
 */
class ClubFactory extends Factory
{
    protected $model = Club::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(3, true) . ' Basketball Club';
        $shortName = strtoupper(substr(str_replace(' ', '', $name), 0, 3));

        return [
            'uuid' => $this->faker->uuid(),
            'name' => $name,
            'short_name' => $shortName,
            'slug' => Str::slug($name),
            'description' => $this->faker->paragraph(3),
            'logo_path' => null,
            
            // Contact information
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'website' => $this->faker->optional(0.7)->url(),
            
            // Address
            'address_street' => $this->faker->streetAddress(),
            'address_city' => $this->faker->city(),
            'address_state' => $this->faker->state(),
            'address_zip' => $this->faker->postcode(),
            'address_country' => 'Deutschland',
            
            // Club details
            'founded_year' => $this->faker->numberBetween(1950, 2020),
            'colors' => json_encode([
                'primary' => $this->faker->hexColor(),
                'secondary' => $this->faker->hexColor(),
            ]),
            
            // Financial information
            'membership_fee_annual' => $this->faker->randomFloat(2, 50, 500),
            'membership_fee_monthly' => $this->faker->randomFloat(2, 5, 50),
            'membership_fee_family' => $this->faker->randomFloat(2, 80, 400),
            
            // Facilities
            'facilities' => json_encode([
                'courts' => $this->faker->numberBetween(1, 4),
                'gym' => $this->faker->boolean(80),
                'locker_rooms' => $this->faker->boolean(90),
                'parking' => $this->faker->boolean(70),
            ]),
            
            // Social media
            'social_media' => json_encode([
                'facebook' => $this->faker->optional(0.6)->url(),
                'instagram' => $this->faker->optional(0.5)->userName(),
                'twitter' => $this->faker->optional(0.3)->userName(),
            ]),
            
            // League information
            'league_memberships' => json_encode([
                $this->faker->randomElement(['Regionalliga', 'Landesliga', 'Bezirksliga', 'Kreisliga']),
            ]),
            
            // Settings
            'settings' => json_encode([
                'allow_public_registration' => $this->faker->boolean(70),
                'require_parent_consent' => $this->faker->boolean(90),
                'enable_emergency_system' => $this->faker->boolean(80),
            ]),
            
            // Status
            'is_active' => $this->faker->boolean(95),
            'is_verified' => $this->faker->boolean(80),
            'verified_at' => $this->faker->optional(0.8)->dateTimeBetween('-2 years', 'now'),
            'status' => $this->faker->randomElement(['active', 'inactive', 'pending']),
            
            // Registration
            'registration_number' => $this->faker->optional(0.7)->regexify('[A-Z]{2}[0-9]{6}'),
            'tax_number' => $this->faker->optional(0.6)->regexify('[0-9]{11}'),
            
            // Emergency contacts
            'emergency_contacts' => json_encode([
                [
                    'name' => $this->faker->name(),
                    'phone' => $this->faker->phoneNumber(),
                    'email' => $this->faker->email(),
                    'role' => 'Präsident',
                ],
                [
                    'name' => $this->faker->name(),
                    'phone' => $this->faker->phoneNumber(),
                    'email' => $this->faker->email(),
                    'role' => 'Geschäftsführer',
                ],
            ]),
            
            'created_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the club is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
            'verified_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the club is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the club is pending verification.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => false,
            'verified_at' => null,
            'status' => 'pending',
        ]);
    }

    /**
     * Create a small local club.
     */
    public function small(): static
    {
        return $this->state(fn (array $attributes) => [
            'membership_fee_annual' => $this->faker->randomFloat(2, 30, 150),
            'membership_fee_monthly' => $this->faker->randomFloat(2, 3, 15),
            'facilities' => json_encode([
                'courts' => 1,
                'gym' => false,
                'locker_rooms' => true,
                'parking' => $this->faker->boolean(50),
            ]),
            'league_memberships' => json_encode(['Kreisliga']),
        ]);
    }

    /**
     * Create a large professional club.
     */
    public function large(): static
    {
        return $this->state(fn (array $attributes) => [
            'membership_fee_annual' => $this->faker->randomFloat(2, 200, 800),
            'membership_fee_monthly' => $this->faker->randomFloat(2, 20, 80),
            'facilities' => json_encode([
                'courts' => $this->faker->numberBetween(2, 6),
                'gym' => true,
                'locker_rooms' => true,
                'parking' => true,
                'restaurant' => true,
                'pro_shop' => true,
            ]),
            'league_memberships' => json_encode(['Bundesliga', 'Regionalliga']),
            'is_verified' => true,
            'verified_at' => $this->faker->dateTimeBetween('-2 years', '-6 months'),
        ]);
    }
}