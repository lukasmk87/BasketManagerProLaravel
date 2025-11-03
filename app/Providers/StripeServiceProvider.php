<?php

namespace App\Providers;

use App\Services\Stripe\StripeClientManager;
use App\Services\Stripe\StripePaymentService;
use App\Services\Stripe\StripeSubscriptionService;
use App\Services\Stripe\StripeWebhookService;
use App\Services\Stripe\CashierTenantManager;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class StripeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register Stripe configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/stripe.php', 'stripe'
        );

        // Register Stripe Client Manager as singleton
        $this->app->singleton(StripeClientManager::class, function ($app) {
            return new StripeClientManager();
        });

        // Register default Stripe Client
        $this->app->singleton(StripeClient::class, function ($app) {
            $manager = $app->make(StripeClientManager::class);
            return $manager->getDefaultClient();
        });

        // Register Stripe services
        $this->app->singleton(StripePaymentService::class, function ($app) {
            return new StripePaymentService(
                $app->make(StripeClientManager::class)
            );
        });

        $this->app->singleton(StripeSubscriptionService::class, function ($app) {
            return new StripeSubscriptionService(
                $app->make(StripeClientManager::class)
            );
        });

        $this->app->singleton(StripeWebhookService::class, function ($app) {
            return new StripeWebhookService(
                $app->make(StripeClientManager::class)
            );
        });

        $this->app->singleton(CashierTenantManager::class, function ($app) {
            return new CashierTenantManager();
        });

        // Register aliases
        $this->app->alias(StripeClientManager::class, 'stripe.manager');
        $this->app->alias(StripePaymentService::class, 'stripe.payments');
        $this->app->alias(StripeSubscriptionService::class, 'stripe.subscriptions');
        $this->app->alias(StripeWebhookService::class, 'stripe.webhooks');
        $this->app->alias(CashierTenantManager::class, 'stripe.cashier');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configure Stripe settings
        $this->configureStripe();

        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/stripe.php' => config_path('stripe.php'),
            ], 'stripe-config');
        }
    }

    /**
     * Configure global Stripe settings.
     */
    private function configureStripe(): void
    {
        // Skip Stripe configuration during installation
        if (!file_exists(storage_path('installed')) || file_exists(storage_path('installing'))) {
            return;
        }

        // Set global Stripe API version
        \Stripe\Stripe::setApiVersion(config('stripe.api_version'));

        // Configure network retries
        \Stripe\Stripe::setMaxNetworkRetries(config('stripe.performance.max_network_retries', 2));

        // Configure telemetry
        \Stripe\Stripe::setEnableTelemetry(config('stripe.performance.enable_telemetry', true));

        // Set application info for debugging - use safe app_name() with fallback
        try {
            $appName = app_name();
        } catch (\Exception $e) {
            $appName = config('app.name', 'BasketManager Pro');
        }

        \Stripe\Stripe::setAppInfo(
            $appName,
            config('app.version', '4.0'),
            'https://basketmanager-pro.com',
            'pp_partner_MQxxxxxxxxxxxxxxxxxx' // Stripe Partner ID if applicable
        );

        // Configure logging in development
        if (config('stripe.development.log_requests')) {
            \Stripe\Stripe::setLogger(logger());
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            StripeClientManager::class,
            StripeClient::class,
            StripePaymentService::class,
            StripeSubscriptionService::class,
            StripeWebhookService::class,
            CashierTenantManager::class,
            'stripe.manager',
            'stripe.payments',
            'stripe.subscriptions',
            'stripe.webhooks',
            'stripe.cashier',
        ];
    }
}