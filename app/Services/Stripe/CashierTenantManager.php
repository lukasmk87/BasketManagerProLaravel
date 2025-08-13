<?php

namespace App\Services\Stripe;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Laravel\Cashier\Cashier;

class CashierTenantManager
{
    /**
     * Current tenant instance.
     */
    private ?Tenant $currentTenant = null;

    /**
     * Set the current tenant for Stripe operations.
     */
    public function setCurrentTenant(?Tenant $tenant): void
    {
        $this->currentTenant = $tenant;
        
        if ($tenant) {
            $this->configureStripeForTenant($tenant);
        } else {
            $this->configureDefaultStripe();
        }
    }

    /**
     * Get the current tenant.
     */
    public function getCurrentTenant(): ?Tenant
    {
        return $this->currentTenant ?: app('tenant');
    }

    /**
     * Configure Stripe for a specific tenant.
     */
    private function configureStripeForTenant(Tenant $tenant): void
    {
        $stripeConfig = $tenant->getStripeConfig();
        
        if (!empty($stripeConfig['secret_key'])) {
            // Set tenant-specific Stripe key
            Config::set('cashier.secret', $stripeConfig['secret_key']);
            Config::set('cashier.key', $stripeConfig['publishable_key']);
            
            // Update Stripe global configuration
            \Stripe\Stripe::setApiKey($stripeConfig['secret_key']);
        }
        
        // Set webhook secret for tenant
        if (!empty($stripeConfig['webhook_secret'])) {
            Config::set('cashier.webhook.secret', $stripeConfig['webhook_secret']);
        }
    }

    /**
     * Configure default Stripe settings.
     */
    private function configureDefaultStripe(): void
    {
        Config::set('cashier.secret', config('stripe.secret'));
        Config::set('cashier.key', config('stripe.api_key'));
        Config::set('cashier.webhook.secret', config('stripe.webhooks.signing_secret'));
        
        \Stripe\Stripe::setApiKey(config('stripe.secret'));
    }

    /**
     * Get Stripe configuration for current tenant.
     */
    public function getStripeConfig(): array
    {
        $tenant = $this->getCurrentTenant();
        
        if (!$tenant) {
            return [
                'key' => config('stripe.api_key'),
                'secret' => config('stripe.secret'),
                'webhook_secret' => config('stripe.webhooks.signing_secret'),
            ];
        }
        
        return $tenant->getStripeConfig();
    }

    /**
     * Create a subscription for the current tenant.
     */
    public function createTenantSubscription(string $priceId, array $options = []): \Laravel\Cashier\Subscription
    {
        $tenant = $this->getCurrentTenant();
        
        if (!$tenant) {
            throw new \Exception('No tenant context available for subscription creation');
        }
        
        // Ensure Stripe is configured for this tenant
        $this->configureStripeForTenant($tenant);
        
        // Create or get Stripe customer for tenant
        if (!$tenant->hasStripeId()) {
            $tenant->createAsStripeCustomer([
                'name' => $tenant->name,
                'email' => $tenant->billing_email,
                'address' => [
                    'line1' => $tenant->billing_address,
                    'country' => $tenant->country_code,
                ],
                'metadata' => [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                ],
            ]);
        }
        
        // Create subscription
        $subscription = $tenant->newSubscription('default', $priceId);
        
        // Add trial if eligible
        if ($tenant->isEligibleForTrial()) {
            $trialDays = config('stripe.subscriptions.trial_period_days', 14);
            $subscription->trialDays($trialDays);
        }
        
        // Add metadata
        $subscription->metadata([
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'subscription_tier' => $tenant->subscription_tier,
        ]);
        
        // Apply options
        foreach ($options as $method => $value) {
            if (method_exists($subscription, $method)) {
                $subscription->{$method}($value);
            }
        }
        
        return $subscription->create();
    }

    /**
     * Get subscription for current tenant.
     */
    public function getTenantSubscription(?string $type = 'default'): ?\Laravel\Cashier\Subscription
    {
        $tenant = $this->getCurrentTenant();
        
        return $tenant?->subscription($type);
    }

    /**
     * Cancel subscription for current tenant.
     */
    public function cancelTenantSubscription(?string $type = 'default', bool $immediately = false): bool
    {
        $subscription = $this->getTenantSubscription($type);
        
        if (!$subscription) {
            return false;
        }
        
        if ($immediately) {
            $subscription->cancelNow();
        } else {
            $subscription->cancel();
        }
        
        return true;
    }

    /**
     * Resume subscription for current tenant.
     */
    public function resumeTenantSubscription(?string $type = 'default'): bool
    {
        $subscription = $this->getTenantSubscription($type);
        
        if (!$subscription || !$subscription->onGracePeriod()) {
            return false;
        }
        
        $subscription->resume();
        
        return true;
    }

    /**
     * Swap subscription plan for current tenant.
     */
    public function swapTenantSubscription(
        string $newPriceId, 
        ?string $type = 'default',
        array $options = []
    ): ?\Laravel\Cashier\Subscription {
        $subscription = $this->getTenantSubscription($type);
        
        if (!$subscription) {
            return null;
        }
        
        // Update tenant's subscription tier based on new price
        $newTier = $this->getPriceTier($newPriceId);
        if ($newTier) {
            $this->getCurrentTenant()->update(['subscription_tier' => $newTier]);
        }
        
        return $subscription->swap($newPriceId, $options);
    }

    /**
     * Get tier name from price ID.
     */
    private function getPriceTier(string $priceId): ?string
    {
        $tiers = config('stripe.subscriptions.tiers', []);
        
        return array_search($priceId, $tiers) ?: null;
    }

    /**
     * Check if tenant can upgrade to a specific tier.
     */
    public function canUpgradeTo(string $tier): bool
    {
        $tenant = $this->getCurrentTenant();
        
        if (!$tenant) {
            return false;
        }
        
        $currentTier = $tenant->subscription_tier;
        
        $tierHierarchy = ['free', 'basic', 'professional', 'enterprise'];
        
        $currentIndex = array_search($currentTier, $tierHierarchy);
        $targetIndex = array_search($tier, $tierHierarchy);
        
        return $targetIndex > $currentIndex;
    }

    /**
     * Get pricing information for all tiers.
     */
    public function getPricingInfo(): array
    {
        $tiers = config('tenants.tiers', []);
        $stripePrices = config('stripe.subscriptions.tiers', []);
        
        $pricing = [];
        
        foreach ($tiers as $tier => $config) {
            if ($tier === 'free') {
                $pricing[$tier] = [
                    'name' => $config['name'],
                    'price' => 0,
                    'currency' => 'EUR',
                    'stripe_price_id' => null,
                    'features' => $config['features'] ?? [],
                    'limits' => $config['limits'] ?? [],
                ];
                continue;
            }
            
            $pricing[$tier] = [
                'name' => $config['name'],
                'price' => $config['price'] ?? 0,
                'currency' => $config['currency'] ?? 'EUR',
                'stripe_price_id' => $stripePrices[$tier] ?? null,
                'features' => $config['features'] ?? [],
                'limits' => $config['limits'] ?? [],
            ];
        }
        
        return $pricing;
    }

    /**
     * Create a checkout session for tenant subscription.
     */
    public function createCheckoutSession(
        string $priceId,
        string $successUrl,
        string $cancelUrl,
        array $options = []
    ): \Stripe\Checkout\Session {
        $tenant = $this->getCurrentTenant();
        
        if (!$tenant) {
            throw new \Exception('No tenant context available');
        }
        
        $this->configureStripeForTenant($tenant);
        
        return $tenant->checkoutCharges($priceId, [
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'customer_email' => $tenant->billing_email,
            'metadata' => [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
            ],
        ] + $options);
    }
}