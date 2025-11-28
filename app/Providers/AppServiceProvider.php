<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register OpenAPI documentation services
        $this->app->singleton(\App\Services\OpenApi\OpenApiDocumentationService::class);

        // Register SDK generators
        $this->app->bind('App\Services\OpenApi\SDK\phpSDKGenerator', \App\Services\OpenApi\SDK\phpSDKGenerator::class);
        $this->app->bind('App\Services\OpenApi\SDK\javascriptSDKGenerator', \App\Services\OpenApi\SDK\javascriptSDKGenerator::class);
        $this->app->bind('App\Services\OpenApi\SDK\pythonSDKGenerator', \App\Services\OpenApi\SDK\pythonSDKGenerator::class);

        // Register API versioning services
        $this->app->singleton(\App\Services\Api\RouteVersionResolver::class);

        // ========================================================================
        // Subscription Analytics Services (Refactored - REFACTOR-003)
        // ========================================================================

        // Register Analytics Cache Manager (shared across all analytics services)
        $this->app->singleton(\App\Services\Stripe\Analytics\AnalyticsCacheManager::class);

        // Register MRR Calculator Service
        $this->app->singleton(\App\Services\Stripe\Analytics\MRRCalculatorService::class);

        // Register Churn Analyzer Service
        $this->app->singleton(\App\Services\Stripe\Analytics\ChurnAnalyzerService::class);

        // Register LTV Calculator Service
        $this->app->singleton(\App\Services\Stripe\Analytics\LTVCalculatorService::class);

        // Register Subscription Health Metrics Service
        $this->app->singleton(\App\Services\Stripe\Analytics\SubscriptionHealthMetricsService::class);

        // Register new Subscription Analytics Service (Facade/Orchestrator)
        $this->app->singleton(\App\Services\Stripe\Analytics\SubscriptionAnalyticsService::class);

        // Backward compatibility alias: Old namespace → New namespace
        // This allows existing code using the old namespace to continue working
        $this->app->alias(
            \App\Services\Stripe\Analytics\SubscriptionAnalyticsService::class,
            \App\Services\Stripe\SubscriptionAnalyticsService::class
        );

        // Register Club Subscription Notification Service
        $this->app->singleton(\App\Services\ClubSubscriptionNotificationService::class);

        // ========================================================================
        // Club Services (Refactored - REFACTOR-005)
        // ========================================================================

        // Register Club Membership Service (no dependencies)
        $this->app->singleton(\App\Services\Club\ClubMembershipService::class);

        // Register Club CRUD Service (depends on ClubMembershipService)
        $this->app->singleton(\App\Services\Club\ClubCrudService::class);

        // Register Club Statistics Service (no dependencies)
        $this->app->singleton(\App\Services\Club\ClubStatisticsService::class);

        // Register Club Subscription Plan Service (no dependencies)
        $this->app->singleton(\App\Services\Club\ClubSubscriptionPlanService::class);

        // Register Redis Availability Service
        $this->app->singleton(\App\Services\RedisAvailabilityService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Skip all boot logic during installation to prevent 500 errors
        // when database tables don't exist yet
        if ($this->isInstalling()) {
            return;
        }

        // Register Artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\GenerateOpenApiDocsCommand::class,
            ]);
        }

        // Auto-detect Redis availability and adjust configuration for graceful degradation
        $this->configureRedisGracefulDegradation();

        // Check if tenant exists (for new installations)
        $this->checkTenantSetup();
    }

    /**
     * Configure graceful degradation when Redis is not available
     *
     * This method checks if Redis is available and automatically adjusts
     * cache, session, and queue drivers to use database fallbacks if needed.
     * This ensures the application works on both shared hosting (without Redis)
     * and dedicated servers (with Redis).
     */
    private function configureRedisGracefulDegradation(): void
    {
        // Only run if environment variables explicitly request Redis
        $cacheStore = env('CACHE_STORE', 'database');
        $sessionDriver = env('SESSION_DRIVER', 'database');
        $queueConnection = env('QUEUE_CONNECTION', 'database');

        // If none of the drivers are set to Redis, skip the check
        if ($cacheStore !== 'redis' && $sessionDriver !== 'redis' && $queueConnection !== 'redis') {
            return;
        }

        try {
            // Check Redis availability
            $redisService = $this->app->make(\App\Services\RedisAvailabilityService::class);

            if (!$redisService->isAvailable()) {
                // Redis is not available - apply fallbacks
                $this->applyRedisFallbacks($cacheStore, $sessionDriver, $queueConnection);
            }
        } catch (\Exception $e) {
            // If Redis check fails, apply fallbacks as safety measure
            \Illuminate\Support\Facades\Log::warning('Redis availability check failed, applying fallbacks', [
                'error' => $e->getMessage()
            ]);
            $this->applyRedisFallbacks($cacheStore, $sessionDriver, $queueConnection);
        }
    }

    /**
     * Apply fallback drivers when Redis is not available
     *
     * @param string $cacheStore
     * @param string $sessionDriver
     * @param string $queueConnection
     */
    private function applyRedisFallbacks(string $cacheStore, string $sessionDriver, string $queueConnection): void
    {
        // Update cache driver
        if ($cacheStore === 'redis') {
            config(['cache.default' => 'database']);
            \Illuminate\Support\Facades\Log::info('Redis not available: Switched cache driver from redis to database');
        }

        // Update session driver
        if ($sessionDriver === 'redis') {
            config(['session.driver' => 'database']);
            \Illuminate\Support\Facades\Log::info('Redis not available: Switched session driver from redis to database');
        }

        // Update queue connection
        if ($queueConnection === 'redis') {
            config(['queue.default' => 'database']);
            \Illuminate\Support\Facades\Log::info('Redis not available: Switched queue connection from redis to database');
        }
    }

    /**
     * Check if tenant setup is required (for new installations)
     *
     * This method checks if at least one tenant exists in the database.
     * If not, it logs a helpful message guiding administrators to initialize
     * the first tenant. This is essential for SaaS/White-Label deployments.
     */
    private function checkTenantSetup(): void
    {
        // Skip if installation is in progress to prevent database access before migrations
        if ($this->isInstalling()) {
            return;
        }

        // Skip check if running in console (migrations, seeders, etc.)
        // or if the request is for the tenant initialization itself
        if ($this->app->runningInConsole()) {
            return;
        }

        try {
            // Check if tenants table exists (might be during initial migration)
            if (!\Illuminate\Support\Facades\Schema::hasTable('tenants')) {
                return;
            }

            // Check if any tenant exists
            $tenantCount = \App\Models\Tenant::count();

            if ($tenantCount === 0) {
                // No tenants exist - log helpful message
                \Illuminate\Support\Facades\Log::warning(
                    '⚠️  No tenants found in database. This is a fresh installation that needs tenant initialization.',
                    [
                        'app_url' => config('app.url'),
                        'suggestion' => 'Run: php artisan tenant:initialize',
                        'alternative' => 'Or: php artisan db:seed --class=InitialTenantSeeder',
                        'documentation' => 'See SHARED_HOSTING_DEPLOYMENT.md for details'
                    ]
                );
            }
        } catch (\Exception $e) {
            // Silently fail - don't break the application boot
            // This might happen during migrations or if database is not yet set up
            \Illuminate\Support\Facades\Log::debug('Tenant setup check skipped', [
                'reason' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if installation is in progress
     *
     * @return bool
     */
    private function isInstalling(): bool
    {
        // Check if installed marker exists
        if (!file_exists(storage_path('installed'))) {
            return true;
        }

        // Check if temporary installing marker exists
        if (file_exists(storage_path('installing'))) {
            return true;
        }

        // Check current request path (if available)
        if (request()->is('install') || request()->is('install/*')) {
            return true;
        }

        return false;
    }
}
