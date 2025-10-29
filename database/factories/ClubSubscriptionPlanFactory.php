<?php

namespace Database\Factories;

use App\Models\ClubSubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClubSubscriptionPlan>
 */
class ClubSubscriptionPlanFactory extends Factory
{
    protected $model = ClubSubscriptionPlan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->randomElement(['Basic', 'Professional', 'Premium', 'Enterprise', 'Starter', 'Team', 'Pro', 'Ultimate']);

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->paragraph(2),
            'price' => $this->faker->randomFloat(2, 9.99, 199.99),
            'currency' => 'EUR',
            'billing_interval' => $this->faker->randomElement(['monthly', 'yearly']),

            // Features
            'features' => [
                'live_scoring' => $this->faker->boolean(80),
                'statistics' => $this->faker->boolean(90),
                'training_management' => $this->faker->boolean(70),
                'tournament_management' => $this->faker->boolean(60),
                'player_profiles' => $this->faker->boolean(95),
                'team_management' => $this->faker->boolean(100),
            ],

            // Limits
            'limits' => [
                'teams' => $this->faker->randomElement([5, 10, 25, 50, -1]),
                'players' => $this->faker->randomElement([50, 100, 250, 500, -1]),
                'games_per_month' => $this->faker->randomElement([10, 25, 50, 100, -1]),
                'storage_gb' => $this->faker->randomElement([5, 10, 25, 50, 100, -1]),
                'api_calls_per_hour' => $this->faker->randomElement([100, 500, 1000, 5000, -1]),
            ],

            // Status
            'is_active' => $this->faker->boolean(90),
            'is_default' => false,
            'sort_order' => $this->faker->numberBetween(0, 10),

            // Appearance
            'color' => $this->faker->hexColor(),
            'icon' => $this->faker->randomElement(['star', 'rocket', 'shield', 'crown', 'trophy']),

            // Stripe integration
            'stripe_product_id' => null,
            'stripe_price_id_monthly' => null,
            'stripe_price_id_yearly' => null,
            'is_stripe_synced' => false,
            'last_stripe_sync_at' => null,
            'trial_period_days' => $this->faker->randomElement([0, 7, 14, 30]), // 0 = no trial
        ];
    }

    /**
     * Indicate that the plan is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the plan is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the plan is the default plan.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Set a specific tenant for the plan.
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Create a basic/free tier plan.
     */
    public function basic(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Basic',
            'slug' => 'basic',
            'price' => 0.00,
            'features' => [
                'player_profiles' => true,
                'team_management' => true,
                'statistics' => true,
            ],
            'limits' => [
                'teams' => 5,
                'players' => 50,
                'games_per_month' => 10,
                'storage_gb' => 5,
                'api_calls_per_hour' => 100,
            ],
        ]);
    }

    /**
     * Create a professional tier plan.
     */
    public function professional(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Professional',
            'slug' => 'professional',
            'price' => 49.99,
            'features' => [
                'live_scoring' => true,
                'statistics' => true,
                'training_management' => true,
                'tournament_management' => true,
                'player_profiles' => true,
                'team_management' => true,
            ],
            'limits' => [
                'teams' => 25,
                'players' => 250,
                'games_per_month' => 50,
                'storage_gb' => 25,
                'api_calls_per_hour' => 1000,
            ],
        ]);
    }

    /**
     * Create an enterprise tier plan.
     */
    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'price' => 199.99,
            'features' => [
                'live_scoring' => true,
                'statistics' => true,
                'training_management' => true,
                'tournament_management' => true,
                'player_profiles' => true,
                'team_management' => true,
                'advanced_analytics' => true,
                'custom_branding' => true,
                'priority_support' => true,
            ],
            'limits' => [
                'teams' => -1,
                'players' => -1,
                'games_per_month' => -1,
                'storage_gb' => -1,
                'api_calls_per_hour' => -1,
            ],
        ]);
    }

    /**
     * Create a plan synced with Stripe.
     */
    public function stripeSynced(): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_product_id' => 'prod_' . Str::random(14),
            'stripe_price_id_monthly' => 'price_' . Str::random(14),
            'stripe_price_id_yearly' => 'price_' . Str::random(14),
            'is_stripe_synced' => true,
            'last_stripe_sync_at' => now(),
        ]);
    }

    /**
     * Create a plan with trial period.
     */
    public function withTrial(int $days = 14): static
    {
        return $this->state(fn (array $attributes) => [
            'trial_period_days' => $days,
        ]);
    }

    /**
     * Create a monthly plan.
     */
    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_interval' => 'monthly',
        ]);
    }

    /**
     * Create a yearly plan.
     */
    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_interval' => 'yearly',
            'price' => $attributes['price'] * 10, // 2 months free discount
        ]);
    }
}
