<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Initialize Tenant Command
 *
 * Interactive command to set up the initial tenant for a new installation.
 * Essential for SaaS/White-Label deployments.
 *
 * Usage:
 *   php artisan tenant:initialize
 *   php artisan tenant:initialize --force (non-interactive)
 */
class InitializeTenantCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:initialize
                            {--force : Run without interaction, using environment variables}
                            {--domain= : Override domain (defaults to APP_URL)}
                            {--name= : Override tenant name}
                            {--email= : Override billing email}
                            {--tier= : Override subscription tier (free/basic/professional/enterprise)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize the first tenant for this installation';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🏀 BasketManager Pro - Tenant Initialization');
        $this->newLine();

        // Extract domain from APP_URL or option
        $domain = $this->option('domain') ?? $this->extractDomainFromUrl(config('app.url'));

        if (!$domain) {
            $this->error('❌ Could not extract domain from APP_URL: ' . config('app.url'));
            $this->warn('Please set APP_URL in .env or use --domain option');
            return self::FAILURE;
        }

        // Check if tenant already exists
        $existingTenant = Tenant::where('domain', $domain)->first();

        if ($existingTenant) {
            $this->warn('⚠️  A tenant already exists for domain: ' . $domain);
            $this->newLine();
            $this->table(
                ['Property', 'Value'],
                [
                    ['Tenant ID', $existingTenant->id],
                    ['Name', $existingTenant->name],
                    ['Slug', $existingTenant->slug],
                    ['Domain', $existingTenant->domain],
                    ['Billing Email', $existingTenant->billing_email],
                    ['Subscription', $existingTenant->subscription_tier],
                    ['Status', $existingTenant->is_active ? '✅ Active' : '❌ Inactive'],
                ]
            );

            if (!$this->option('force') && !$this->confirm('Do you want to create a new tenant anyway?', false)) {
                $this->info('Tenant initialization cancelled.');
                return self::SUCCESS;
            }

            // If user wants to proceed, ask for different domain
            if (!$this->option('force')) {
                $domain = $this->ask('Enter a different domain for the new tenant');
            }
        }

        // Interactive mode or force mode
        if ($this->option('force')) {
            return $this->createTenantFromEnvironment($domain);
        } else {
            return $this->createTenantInteractive($domain);
        }
    }

    /**
     * Create tenant from environment variables (non-interactive)
     */
    private function createTenantFromEnvironment(string $domain): int
    {
        $tenantName = $this->option('name') ?? env('TENANT_NAME', config('app.name', 'BasketManager Pro'));
        $billingEmail = $this->option('email') ?? env('TENANT_BILLING_EMAIL', env('MAIL_FROM_ADDRESS', 'admin@' . $domain));
        $subscriptionTier = $this->option('tier') ?? env('TENANT_SUBSCRIPTION_TIER', 'professional');

        return $this->createTenant($domain, $tenantName, $billingEmail, $subscriptionTier);
    }

    /**
     * Create tenant interactively
     */
    private function createTenantInteractive(string $domain): int
    {
        $this->info('Let\'s set up your tenant...');
        $this->newLine();

        // Ask for tenant name
        $defaultName = env('TENANT_NAME', config('app.name', 'BasketManager Pro'));
        $tenantName = $this->ask('Tenant Name', $defaultName);

        // Ask for billing email
        $defaultEmail = env('TENANT_BILLING_EMAIL', env('MAIL_FROM_ADDRESS', 'admin@' . $domain));
        $billingEmail = $this->ask('Billing Email', $defaultEmail);

        // Validate email
        while (!filter_var($billingEmail, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address. Please try again.');
            $billingEmail = $this->ask('Billing Email', $defaultEmail);
        }

        // Ask for subscription tier
        $subscriptionTier = $this->choice(
            'Subscription Tier',
            ['free', 'basic', 'professional', 'enterprise'],
            env('TENANT_SUBSCRIPTION_TIER', 'professional')
        );

        // Show tier details
        $this->newLine();
        $this->showTierDetails($subscriptionTier);
        $this->newLine();

        // Confirm
        if (!$this->confirm('Create tenant with these settings?', true)) {
            $this->info('Tenant initialization cancelled.');
            return self::SUCCESS;
        }

        return $this->createTenant($domain, $tenantName, $billingEmail, $subscriptionTier);
    }

    /**
     * Create the tenant
     */
    private function createTenant(string $domain, string $name, string $email, string $tier): int
    {
        try {
            // Generate slug
            $slug = Str::slug(str_replace('.', '-', $domain));
            $originalSlug = $slug;
            $counter = 1;
            while (Tenant::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            // Get limits
            $limits = $this->getSubscriptionLimits($tier);

            // Create tenant
            $tenant = Tenant::create([
                'name' => $name,
                'slug' => $slug,
                'domain' => $domain,
                'billing_email' => $email,
                'country_code' => env('TENANT_COUNTRY', 'DE'),
                'timezone' => config('app.timezone', 'Europe/Berlin'),
                'locale' => config('app.locale', 'de'),
                'currency' => env('TENANT_CURRENCY', 'EUR'),
                'subscription_tier' => $tier,
                'is_active' => true,
                'trial_ends_at' => env('TENANT_TRIAL_DAYS')
                    ? now()->addDays((int) env('TENANT_TRIAL_DAYS'))
                    : null,
                'max_users' => $limits['max_users'],
                'max_teams' => $limits['max_teams'],
                'max_storage_gb' => $limits['max_storage_gb'],
                'max_api_calls_per_hour' => $limits['max_api_calls_per_hour'],
                'settings' => [
                    'features' => $limits['features'],
                    'branding' => [
                        'primary_color' => env('TENANT_PRIMARY_COLOR', '#4F46E5'),
                        'logo_url' => env('TENANT_LOGO_URL'),
                    ],
                    'contact' => [
                        'support_email' => env('TENANT_SUPPORT_EMAIL', $email),
                        'phone' => env('TENANT_PHONE'),
                    ],
                ],
            ]);

            $this->newLine();
            $this->info('✅ Tenant created successfully!');
            $this->newLine();

            $this->table(
                ['Property', 'Value'],
                [
                    ['Tenant ID', $tenant->id],
                    ['Name', $tenant->name],
                    ['Slug', $tenant->slug],
                    ['Domain', $tenant->domain],
                    ['Billing Email', $tenant->billing_email],
                    ['Subscription', $tenant->subscription_tier],
                    ['Max Users', $tenant->max_users == -1 ? 'Unlimited' : $tenant->max_users],
                    ['Max Teams', $tenant->max_teams == -1 ? 'Unlimited' : $tenant->max_teams],
                    ['Max Storage', $tenant->max_storage_gb == -1 ? 'Unlimited' : $tenant->max_storage_gb . ' GB'],
                    ['Status', '✅ Active'],
                ]
            );

            if ($tenant->trial_ends_at) {
                $this->info('🎁 Trial Period: ' . $tenant->trial_ends_at->diffForHumans());
            }

            $this->newLine();
            $this->info('🎉 Your BasketManager Pro installation is ready!');
            $this->info('📍 Visit: ' . config('app.url'));

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to create tenant: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return self::FAILURE;
        }
    }

    /**
     * Show tier details
     */
    private function showTierDetails(string $tier): void
    {
        $limits = $this->getSubscriptionLimits($tier);

        $this->info('📊 ' . strtoupper($tier) . ' Tier Details:');
        $this->line('   Max Users: ' . ($limits['max_users'] == -1 ? 'Unlimited' : $limits['max_users']));
        $this->line('   Max Teams: ' . ($limits['max_teams'] == -1 ? 'Unlimited' : $limits['max_teams']));
        $this->line('   Max Storage: ' . ($limits['max_storage_gb'] == -1 ? 'Unlimited' : $limits['max_storage_gb'] . ' GB'));
        $this->line('   API Calls: ' . ($limits['max_api_calls_per_hour'] == -1 ? 'Unlimited' : number_format($limits['max_api_calls_per_hour']) . '/hour'));
        $this->line('   Features: ' . count($limits['features']) . ' enabled');
    }

    /**
     * Extract domain from URL
     */
    private function extractDomainFromUrl(string $url): ?string
    {
        $domain = preg_replace('#^https?://#', '', $url);
        $domain = preg_replace('#:\d+$#', '', $domain);
        $domain = explode('/', $domain)[0];
        $domain = preg_replace('#^www\.#', '', $domain);
        return $domain ?: null;
    }

    /**
     * Get subscription limits
     */
    private function getSubscriptionLimits(string $tier): array
    {
        $limits = [
            'free' => [
                'max_users' => 10,
                'max_teams' => 5,
                'max_storage_gb' => 5,
                'max_api_calls_per_hour' => 100,
                'features' => ['basic_stats' => true, 'team_management' => true, 'player_roster' => true],
            ],
            'basic' => [
                'max_users' => 50,
                'max_teams' => 20,
                'max_storage_gb' => 50,
                'max_api_calls_per_hour' => 1000,
                'features' => ['basic_stats' => true, 'advanced_stats' => true, 'team_management' => true, 'player_roster' => true, 'training_management' => true, 'game_scheduling' => true],
            ],
            'professional' => [
                'max_users' => 200,
                'max_teams' => 50,
                'max_storage_gb' => 200,
                'max_api_calls_per_hour' => 5000,
                'features' => ['basic_stats' => true, 'advanced_stats' => true, 'team_management' => true, 'player_roster' => true, 'training_management' => true, 'game_scheduling' => true, 'live_scoring' => true, 'video_analysis' => true, 'tournament_management' => true, 'api_access' => true],
            ],
            'enterprise' => [
                'max_users' => -1,
                'max_teams' => -1,
                'max_storage_gb' => -1,
                'max_api_calls_per_hour' => -1,
                'features' => ['basic_stats' => true, 'advanced_stats' => true, 'team_management' => true, 'player_roster' => true, 'training_management' => true, 'game_scheduling' => true, 'live_scoring' => true, 'video_analysis' => true, 'tournament_management' => true, 'api_access' => true, 'white_label' => true, 'custom_domain' => true, 'priority_support' => true, 'sla_guarantee' => true],
            ],
        ];

        return $limits[$tier] ?? $limits['professional'];
    }
}
