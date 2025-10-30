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

        // Register Subscription Analytics Service
        $this->app->singleton(\App\Services\Stripe\SubscriptionAnalyticsService::class, function ($app) {
            return new \App\Services\Stripe\SubscriptionAnalyticsService(
                $app->make(\App\Services\Stripe\StripeClientManager::class),
                $app->make(\App\Services\ClubUsageTrackingService::class)
            );
        });

        // Register Club Subscription Notification Service
        $this->app->singleton(\App\Services\ClubSubscriptionNotificationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\GenerateOpenApiDocsCommand::class,
            ]);
        }
    }
}
