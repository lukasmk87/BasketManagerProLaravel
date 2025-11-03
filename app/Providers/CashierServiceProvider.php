<?php

namespace App\Providers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Subscription;

class CashierServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Skip during installation to prevent 500 errors when database is not yet set up
        if (!file_exists(storage_path('installed')) || file_exists(storage_path('installing'))) {
            return;
        }

        // Set custom customer model if needed (default is User)
        // Cashier::useCustomerModel(User::class);

        // Set custom subscription model if needed
        // Cashier::useSubscriptionModel(Subscription::class);

        // Configure Cashier to use tenant-aware Stripe configuration
        $this->configureTenantAwareStripe();

        // Set up German locale for currency formatting
        $this->configureLocalization();
    }
    
    /**
     * Configure tenant-aware Stripe settings.
     */
    private function configureTenantAwareStripe(): void
    {
        // Set global Stripe configuration
        \Stripe\Stripe::setApiKey(config('stripe.secret'));
        \Stripe\Stripe::setApiVersion(config('stripe.api_version', '2023-10-16'));
    }
    
    /**
     * Configure localization settings.
     */
    private function configureLocalization(): void
    {
        // Set German as default locale for Cashier
        if (app()->getLocale() === 'de') {
            \Carbon\Carbon::setLocale('de');
        }
    }
}