<?php

namespace Database\Factories;

use App\Models\Club;
use App\Models\Tenant;
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
            'tenant_id' => Tenant::factory(),
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
            'founded_at' => $this->faker->optional(0.8)->dateTimeBetween('-70 years', '-5 years'),
            'primary_color' => $this->faker->hexColor(),
            'secondary_color' => $this->faker->hexColor(),
            'accent_color' => $this->faker->optional(0.5)->hexColor(),
            
            // Financial information
            'membership_fee' => $this->faker->randomFloat(2, 50, 500),
            
            // Facilities
            'facilities' => json_encode([
                'courts' => $this->faker->numberBetween(1, 4),
                'gym' => $this->faker->boolean(80),
                'locker_rooms' => $this->faker->boolean(90),
                'parking' => $this->faker->boolean(70),
            ]),
            
            // Social media
            'social_links' => json_encode([
                'facebook' => $this->faker->optional(0.6)->url(),
                'instagram' => $this->faker->optional(0.5)->userName(),
                'twitter' => $this->faker->optional(0.3)->userName(),
            ]),
            
            // League information
            'league' => $this->faker->randomElement(['Regionalliga', 'Landesliga', 'Bezirksliga', 'Kreisliga']),
            'division' => $this->faker->optional(0.7)->randomElement(['A', 'B', 'C']),
            'season' => '2024-25',
            
            // Settings
            'settings' => json_encode([
                'allow_public_registration' => $this->faker->boolean(70),
                'require_parent_consent' => $this->faker->boolean(90),
                'enable_emergency_system' => $this->faker->boolean(80),
            ]),
            
            // Preferences
            'preferences' => json_encode([
                'notification_preferences' => [
                    'email_notifications' => $this->faker->boolean(90),
                    'sms_notifications' => $this->faker->boolean(60),
                ],
            ]),
            
            // Status
            'is_active' => $this->faker->boolean(95),
            'is_verified' => $this->faker->boolean(80),
            'verified_at' => $this->faker->optional(0.8)->dateTimeBetween('-2 years', 'now'),
            
            // Language settings
            'default_language' => 'de',
            'supported_languages' => json_encode(['de', 'en']),
            
            // Currency
            'currency' => 'EUR',
            
            // Emergency contact (single contact instead of array)
            'emergency_contact_name' => $this->faker->name(),
            'emergency_contact_phone' => $this->faker->phoneNumber(),
            'emergency_contact_email' => $this->faker->email(),
            
            // GDPR compliance
            'gdpr_compliant' => $this->faker->boolean(95),
            'privacy_policy_updated_at' => $this->faker->optional(0.8)->dateTimeBetween('-1 year', 'now'),
            'terms_updated_at' => $this->faker->optional(0.8)->dateTimeBetween('-1 year', 'now'),
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
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the club is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
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
        ]);
    }

    /**
     * Create a small local club.
     */
    public function small(): static
    {
        return $this->state(fn (array $attributes) => [
            'membership_fee' => $this->faker->randomFloat(2, 30, 150),
            'facilities' => json_encode([
                'courts' => 1,
                'gym' => false,
                'locker_rooms' => true,
                'parking' => $this->faker->boolean(50),
            ]),
            'league' => 'Kreisliga',
            'division' => null,
        ]);
    }

    /**
     * Create a large professional club.
     */
    public function large(): static
    {
        return $this->state(fn (array $attributes) => [
            'membership_fee' => $this->faker->randomFloat(2, 200, 800),
            'facilities' => json_encode([
                'courts' => $this->faker->numberBetween(2, 6),
                'gym' => true,
                'locker_rooms' => true,
                'parking' => true,
                'restaurant' => true,
                'pro_shop' => true,
            ]),
            'league' => $this->faker->randomElement(['Bundesliga', 'Regionalliga']),
            'division' => $this->faker->randomElement(['A', 'B']),
            'is_verified' => true,
            'verified_at' => $this->faker->dateTimeBetween('-2 years', '-6 months'),
        ]);
    }

    /**
     * Set a specific tenant for the club.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }
}