<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->company() . ' Basketball Club';
        $slug = Str::slug($name);
        $tier = $this->faker->randomElement(['free', 'basic', 'professional', 'enterprise']);
        
        $tierLimits = config("tenants.tiers.{$tier}.limits");
        
        return [
            'name' => $name,
            'slug' => $slug,
            'domain' => $this->faker->boolean(30) ? $this->faker->domainName() : null,
            'subdomain' => $this->faker->boolean(50) ? $slug : null,
            'billing_email' => $this->faker->companyEmail(),
            'billing_name' => $this->faker->name(),
            'billing_address' => $this->faker->address(),
            'vat_number' => $this->faker->optional()->regexify('DE[0-9]{9}'),
            'country_code' => $this->faker->randomElement(['DE', 'AT', 'CH', 'FR', 'NL']),
            'timezone' => $this->faker->randomElement(['Europe/Berlin', 'Europe/Vienna', 'Europe/Zurich', 'Europe/Paris']),
            'locale' => $this->faker->randomElement(['de', 'en', 'fr']),
            'currency' => $this->faker->randomElement(['EUR', 'CHF']),
            'subscription_tier' => $tier,
            'trial_ends_at' => $tier === 'free' ? $this->faker->dateTimeBetween('now', '+14 days') : null,
            'is_active' => true,
            'is_suspended' => false,
            'suspension_reason' => null,
            'features' => $tier === 'enterprise' ? ['custom_feature_1', 'custom_feature_2'] : null,
            'settings' => null, // Will be set after model creation to avoid encryption issues during seeding
            'branding' => [
                'primary_color' => $this->faker->hexColor(),
                'secondary_color' => $this->faker->hexColor(),
                'logo_url' => $this->faker->imageUrl(200, 200, 'sports'),
            ],
            'security_settings' => null, // Will be set after model creation to avoid encryption issues during seeding
            'max_users' => $tierLimits['users'] ?? 10,
            'max_teams' => $tierLimits['teams'] ?? 5,
            'max_storage_gb' => $tierLimits['storage_gb'] ?? 10,
            'max_api_calls_per_hour' => $tierLimits['api_calls_per_hour'] ?? 1000,
            'current_users_count' => $this->faker->numberBetween(1, $tierLimits['users'] ?? 10),
            'current_teams_count' => $this->faker->numberBetween(1, $tierLimits['teams'] ?? 5),
            'current_storage_gb' => $this->faker->randomFloat(2, 0, $tierLimits['storage_gb'] ?? 10),
            'database_name' => null,
            'database_host' => null,
            'database_port' => null,
            'database_password' => null,
            'schema_name' => null,
            'webhook_url' => $this->faker->optional()->url(),
            'allowed_domains' => $this->faker->optional()->randomElements(['*.example.com', 'app.example.com'], 2),
            'blocked_ips' => null,
            'last_login_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'last_activity_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'total_logins' => $this->faker->numberBetween(10, 1000),
            'total_revenue' => $this->faker->randomFloat(2, 0, 10000),
            'monthly_recurring_revenue' => $tier === 'free' ? 0 : config("tenants.tiers.{$tier}.price"),
            'gdpr_accepted' => true,
            'gdpr_accepted_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'terms_accepted' => true,
            'terms_accepted_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'data_retention_policy' => [
                'game_data' => '7 years',
                'player_data' => '10 years',
                'audit_logs' => '3 years',
            ],
            'data_processing_agreement_signed' => $tier !== 'free',
            'created_by' => null,
            'onboarded_by' => null,
            'onboarded_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'notes' => $this->faker->optional()->sentence(),
            'tags' => $this->faker->optional()->words(3),
        ];
    }

    /**
     * Indicate that the tenant is on a free trial.
     */
    public function trial(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_tier' => 'free',
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    /**
     * Indicate that the tenant is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'is_suspended' => true,
            'suspension_reason' => $this->faker->randomElement([
                'Payment failed',
                'Terms violation',
                'Requested by owner',
                'Under investigation',
            ]),
        ]);
    }

    /**
     * Indicate that the tenant is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'last_activity_at' => $this->faker->dateTimeBetween('-6 months', '-3 months'),
        ]);
    }

    /**
     * Indicate that the tenant has expired trial.
     */
    public function expiredTrial(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_tier' => 'free',
            'trial_ends_at' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    /**
     * Indicate that the tenant is on basic tier.
     */
    public function basic(): static
    {
        $tierLimits = config('tenants.tiers.basic.limits');
        
        return $this->state(fn (array $attributes) => [
            'subscription_tier' => 'basic',
            'trial_ends_at' => null,
            'max_users' => $tierLimits['users'],
            'max_teams' => $tierLimits['teams'],
            'max_storage_gb' => $tierLimits['storage_gb'],
            'max_api_calls_per_hour' => $tierLimits['api_calls_per_hour'],
            'monthly_recurring_revenue' => config('tenants.tiers.basic.price'),
        ]);
    }

    /**
     * Indicate that the tenant is on professional tier.
     */
    public function professional(): static
    {
        $tierLimits = config('tenants.tiers.professional.limits');
        
        return $this->state(fn (array $attributes) => [
            'subscription_tier' => 'professional',
            'trial_ends_at' => null,
            'max_users' => $tierLimits['users'],
            'max_teams' => $tierLimits['teams'],
            'max_storage_gb' => $tierLimits['storage_gb'],
            'max_api_calls_per_hour' => $tierLimits['api_calls_per_hour'],
            'monthly_recurring_revenue' => config('tenants.tiers.professional.price'),
        ]);
    }

    /**
     * Indicate that the tenant is on enterprise tier.
     */
    public function enterprise(): static
    {
        $tierLimits = config('tenants.tiers.enterprise.limits');
        
        return $this->state(fn (array $attributes) => [
            'subscription_tier' => 'enterprise',
            'trial_ends_at' => null,
            'max_users' => $tierLimits['users'],
            'max_teams' => $tierLimits['teams'],
            'max_storage_gb' => $tierLimits['storage_gb'],
            'max_api_calls_per_hour' => $tierLimits['api_calls_per_hour'],
            'monthly_recurring_revenue' => config('tenants.tiers.enterprise.price'),
            'features' => [
                'white_label',
                'dedicated_support',
                'custom_integrations',
                'advanced_analytics',
            ],
        ]);
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Tenant $tenant) {
            // Set encrypted settings after model creation
            $tier = $tenant->subscription_tier;
            
            $tenant->updateSettings([
                'theme' => $this->faker->randomElement(['light', 'dark', 'auto']),
                'language' => $this->faker->randomElement(['de', 'en']),
                'notifications' => [
                    'email' => true,
                    'push' => $tier !== 'free',
                ],
            ]);
            
            // Set security settings
            $tenant->security_settings = [
                'require_2fa' => $tier === 'enterprise',
                'ip_whitelist_enabled' => false,
                'session_timeout' => 60,
            ];
            
            // Update tenant counts after creation
            $tenant->current_users_count = $tenant->users()->count();
            $tenant->current_teams_count = $tenant->teams()->count();
            $tenant->save();
        });
    }
}
