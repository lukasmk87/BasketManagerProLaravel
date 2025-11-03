<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Initial Tenant Seeder
 *
 * Creates the first tenant for a new installation based on .env configuration.
 * This is essential for SaaS/White-Label deployments where each customer
 * gets their own installation.
 *
 * Usage:
 *   php artisan db:seed --class=InitialTenantSeeder
 *
 * Or include in DatabaseSeeder for automatic setup during installation.
 */
class InitialTenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🏀 Initializing tenant from environment configuration...');

        // Extract domain from APP_URL
        $appUrl = config('app.url');
        $domain = $this->extractDomainFromUrl($appUrl);

        if (!$domain) {
            $this->command->error('❌ Could not extract domain from APP_URL: ' . $appUrl);
            $this->command->warn('Please set APP_URL in .env (e.g., APP_URL=https://your-domain.com)');
            return;
        }

        // Check if tenant already exists for this domain
        $existingTenant = Tenant::where('domain', $domain)->first();

        if ($existingTenant) {
            $this->command->info('✅ Tenant already exists for domain: ' . $domain);
            $this->command->info('   Tenant Name: ' . $existingTenant->name);
            $this->command->info('   Tenant ID: ' . $existingTenant->id);
            $this->command->info('   Subscription: ' . $existingTenant->subscription_tier);
            return;
        }

        // Get tenant configuration from environment
        $tenantName = env('TENANT_NAME', config('app.name', 'BasketManager Pro'));
        $billingEmail = env('TENANT_BILLING_EMAIL', env('MAIL_FROM_ADDRESS', 'admin@' . $domain));
        $subscriptionTier = env('TENANT_SUBSCRIPTION_TIER', 'professional');

        // Validate subscription tier
        $validTiers = ['free', 'basic', 'professional', 'enterprise'];
        if (!in_array($subscriptionTier, $validTiers)) {
            $this->command->warn('⚠️  Invalid TENANT_SUBSCRIPTION_TIER: ' . $subscriptionTier);
            $this->command->info('   Using default: professional');
            $subscriptionTier = 'professional';
        }

        // Generate unique slug from domain
        $slug = Str::slug(str_replace('.', '-', $domain));

        // Ensure slug is unique
        $originalSlug = $slug;
        $counter = 1;
        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        // Get subscription limits based on tier
        $limits = $this->getSubscriptionLimits($subscriptionTier);

        // Create the tenant
        $tenant = Tenant::create([
            'name' => $tenantName,
            'slug' => $slug,
            'domain' => $domain,
            'billing_email' => $billingEmail,
            'country_code' => env('TENANT_COUNTRY', 'DE'),
            'timezone' => config('app.timezone', 'Europe/Berlin'),
            'locale' => config('app.locale', 'de'),
            'currency' => env('TENANT_CURRENCY', 'EUR'),
            'subscription_tier' => $subscriptionTier,
            'is_active' => true,
            'trial_ends_at' => env('TENANT_TRIAL_DAYS')
                ? now()->addDays((int) env('TENANT_TRIAL_DAYS'))
                : null,

            // Resource limits
            'max_users' => $limits['max_users'],
            'max_teams' => $limits['max_teams'],
            'max_storage_gb' => $limits['max_storage_gb'],
            'max_api_calls_per_hour' => $limits['max_api_calls_per_hour'],

            // Settings
            'settings' => [
                'features' => $limits['features'],
                'branding' => [
                    'primary_color' => env('TENANT_PRIMARY_COLOR', '#4F46E5'),
                    'logo_url' => env('TENANT_LOGO_URL'),
                ],
                'contact' => [
                    'support_email' => env('TENANT_SUPPORT_EMAIL', $billingEmail),
                    'phone' => env('TENANT_PHONE'),
                ],
            ],
        ]);

        $this->command->info('✅ Successfully created initial tenant!');
        $this->command->newLine();
        $this->command->table(
            ['Property', 'Value'],
            [
                ['Tenant ID', $tenant->id],
                ['Name', $tenant->name],
                ['Slug', $tenant->slug],
                ['Domain', $tenant->domain],
                ['Billing Email', $tenant->billing_email],
                ['Subscription', $tenant->subscription_tier],
                ['Max Users', $tenant->max_users],
                ['Max Teams', $tenant->max_teams],
                ['Max Storage', $tenant->max_storage_gb . ' GB'],
                ['Status', $tenant->is_active ? 'Active' : 'Inactive'],
            ]
        );

        if ($tenant->trial_ends_at) {
            $this->command->info('🎁 Trial Period: ' . $tenant->trial_ends_at->diffForHumans());
        }

        $this->command->newLine();
        $this->command->info('🎉 Your BasketManager Pro installation is ready!');
        $this->command->info('📍 Visit: ' . config('app.url'));
    }

    /**
     * Extract domain from URL
     */
    private function extractDomainFromUrl(string $url): ?string
    {
        // Remove protocol
        $domain = preg_replace('#^https?://#', '', $url);

        // Remove port
        $domain = preg_replace('#:\d+$#', '', $domain);

        // Remove path
        $domain = explode('/', $domain)[0];

        // Remove www if present
        $domain = preg_replace('#^www\.#', '', $domain);

        return $domain ?: null;
    }

    /**
     * Get subscription limits for a given tier
     */
    private function getSubscriptionLimits(string $tier): array
    {
        $limits = [
            'free' => [
                'max_users' => 10,
                'max_teams' => 5,
                'max_storage_gb' => 5,
                'max_api_calls_per_hour' => 100,
                'features' => [
                    'basic_stats' => true,
                    'team_management' => true,
                    'player_roster' => true,
                ],
            ],
            'basic' => [
                'max_users' => 50,
                'max_teams' => 20,
                'max_storage_gb' => 50,
                'max_api_calls_per_hour' => 1000,
                'features' => [
                    'basic_stats' => true,
                    'advanced_stats' => true,
                    'team_management' => true,
                    'player_roster' => true,
                    'training_management' => true,
                    'game_scheduling' => true,
                ],
            ],
            'professional' => [
                'max_users' => 200,
                'max_teams' => 50,
                'max_storage_gb' => 200,
                'max_api_calls_per_hour' => 5000,
                'features' => [
                    'basic_stats' => true,
                    'advanced_stats' => true,
                    'team_management' => true,
                    'player_roster' => true,
                    'training_management' => true,
                    'game_scheduling' => true,
                    'live_scoring' => true,
                    'video_analysis' => true,
                    'tournament_management' => true,
                    'api_access' => true,
                ],
            ],
            'enterprise' => [
                'max_users' => -1, // unlimited
                'max_teams' => -1, // unlimited
                'max_storage_gb' => -1, // unlimited
                'max_api_calls_per_hour' => -1, // unlimited
                'features' => [
                    'basic_stats' => true,
                    'advanced_stats' => true,
                    'team_management' => true,
                    'player_roster' => true,
                    'training_management' => true,
                    'game_scheduling' => true,
                    'live_scoring' => true,
                    'video_analysis' => true,
                    'tournament_management' => true,
                    'api_access' => true,
                    'white_label' => true,
                    'custom_domain' => true,
                    'priority_support' => true,
                    'sla_guarantee' => true,
                ],
            ],
        ];

        return $limits[$tier] ?? $limits['professional'];
    }
}
