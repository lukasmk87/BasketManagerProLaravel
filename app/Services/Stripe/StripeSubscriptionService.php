<?php

namespace App\Services\Stripe;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\StripeException;
use Stripe\Subscription;
use Stripe\Price;
use Stripe\Product;
use Stripe\Checkout\Session;

class StripeSubscriptionService
{
    public function __construct(
        private StripeClientManager $clientManager
    ) {}

    /**
     * Create a subscription for a customer.
     */
    public function createSubscription(
        string $customerId,
        string $priceId,
        array $options = []
    ): Subscription {
        $client = $this->clientManager->getCurrentTenantClient();
        
        $params = [
            'customer' => $this->getCustomerId($customerId),
            'items' => [
                ['price' => $priceId]
            ],
        ];
        
        // Add trial period if specified
        if (isset($options['trial_period_days'])) {
            $params['trial_period_days'] = $options['trial_period_days'];
        } elseif (isset($options['trial_end'])) {
            $params['trial_end'] = $options['trial_end'];
        }
        
        // Add payment behavior
        $params['payment_behavior'] = $options['payment_behavior'] ?? 'default_incomplete';
        
        // Add expand to get full objects
        $params['expand'] = ['latest_invoice.payment_intent'];
        
        // Add metadata
        $params['metadata'] = array_merge([
            'tenant_id' => app('tenant')?->id,
            'created_by' => 'basketmanager_pro',
        ], $options['metadata'] ?? []);
        
        // Add coupon if provided
        if (isset($options['coupon'])) {
            $params['coupon'] = $options['coupon'];
        }
        
        // Add tax rates if provided
        if (isset($options['default_tax_rates'])) {
            $params['default_tax_rates'] = $options['default_tax_rates'];
        }
        
        // Add payment method if provided
        if (isset($options['default_payment_method'])) {
            $params['default_payment_method'] = $options['default_payment_method'];
        }
        
        try {
            $subscription = $client->subscriptions->create($params);
            
            Log::info('Stripe subscription created', [
                'subscription_id' => $subscription->id,
                'customer_id' => $customerId,
                'price_id' => $priceId,
                'tenant_id' => app('tenant')?->id,
            ]);
            
            return $subscription;
        } catch (StripeException $e) {
            Log::error('Failed to create subscription', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId,
                'price_id' => $priceId,
                'tenant_id' => app('tenant')?->id,
            ]);
            
            throw $e;
        }
    }

    /**
     * Create a subscription checkout session.
     */
    public function createSubscriptionCheckout(
        string $priceId,
        array $options = []
    ): Session {
        $client = $this->clientManager->getCurrentTenantClient();
        
        $params = [
            'mode' => 'subscription',
            'line_items' => [
                [
                    'price' => $priceId,
                    'quantity' => $options['quantity'] ?? 1,
                ]
            ],
            'success_url' => $options['success_url'] ?? url('/subscription/success'),
            'cancel_url' => $options['cancel_url'] ?? url('/subscription/cancel'),
            'payment_method_types' => $this->getEnabledPaymentMethods(),
        ];
        
        // Add customer if provided
        if (isset($options['customer_id'])) {
            $params['customer'] = $this->getCustomerId($options['customer_id']);
        } elseif (isset($options['customer_email'])) {
            $params['customer_email'] = $options['customer_email'];
        }
        
        // Add trial period
        if (isset($options['subscription_data'])) {
            $params['subscription_data'] = $options['subscription_data'];
        } else {
            $trialDays = $this->getTrialDaysForPrice($priceId);
            if ($trialDays > 0) {
                $params['subscription_data'] = [
                    'trial_period_days' => $trialDays,
                ];
            }
        }
        
        // Add coupon
        if (isset($options['discounts'])) {
            $params['discounts'] = $options['discounts'];
        }
        
        // Add tax rates
        $taxRates = $this->getTaxRatesForTenant();
        if (!empty($taxRates)) {
            $params['subscription_data']['default_tax_rates'] = $taxRates;
        }
        
        // Add metadata
        $params['metadata'] = array_merge([
            'tenant_id' => app('tenant')?->id,
            'price_id' => $priceId,
        ], $options['metadata'] ?? []);
        
        // Configure subscription data metadata
        if (!isset($params['subscription_data'])) {
            $params['subscription_data'] = [];
        }
        $params['subscription_data']['metadata'] = $params['metadata'];
        
        try {
            return $client->checkout->sessions->create($params);
        } catch (StripeException $e) {
            Log::error('Failed to create subscription checkout', [
                'error' => $e->getMessage(),
                'price_id' => $priceId,
                'tenant_id' => app('tenant')?->id,
            ]);
            
            throw $e;
        }
    }

    /**
     * Update a subscription.
     */
    public function updateSubscription(
        string $subscriptionId,
        array $params
    ): Subscription {
        $client = $this->clientManager->getCurrentTenantClient();
        
        // Add proration behavior
        if (!isset($params['proration_behavior'])) {
            $params['proration_behavior'] = 'create_prorations';
        }
        
        try {
            $subscription = $client->subscriptions->update($subscriptionId, $params);
            
            Log::info('Stripe subscription updated', [
                'subscription_id' => $subscriptionId,
                'tenant_id' => app('tenant')?->id,
            ]);
            
            return $subscription;
        } catch (StripeException $e) {
            Log::error('Failed to update subscription', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscriptionId,
                'tenant_id' => app('tenant')?->id,
            ]);
            
            throw $e;
        }
    }

    /**
     * Cancel a subscription.
     */
    public function cancelSubscription(
        string $subscriptionId,
        bool $immediately = false
    ): Subscription {
        $client = $this->clientManager->getCurrentTenantClient();
        
        if ($immediately) {
            $params = [];
        } else {
            $params = ['cancel_at_period_end' => true];
        }
        
        try {
            if ($immediately) {
                $subscription = $client->subscriptions->cancel($subscriptionId, $params);
            } else {
                $subscription = $client->subscriptions->update($subscriptionId, $params);
            }
            
            Log::info('Stripe subscription cancelled', [
                'subscription_id' => $subscriptionId,
                'immediately' => $immediately,
                'tenant_id' => app('tenant')?->id,
            ]);
            
            return $subscription;
        } catch (StripeException $e) {
            Log::error('Failed to cancel subscription', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscriptionId,
                'tenant_id' => app('tenant')?->id,
            ]);
            
            throw $e;
        }
    }

    /**
     * Resume a canceled subscription.
     */
    public function resumeSubscription(string $subscriptionId): Subscription
    {
        $client = $this->clientManager->getCurrentTenantClient();
        
        try {
            $subscription = $client->subscriptions->update($subscriptionId, [
                'cancel_at_period_end' => false,
            ]);
            
            Log::info('Stripe subscription resumed', [
                'subscription_id' => $subscriptionId,
                'tenant_id' => app('tenant')?->id,
            ]);
            
            return $subscription;
        } catch (StripeException $e) {
            Log::error('Failed to resume subscription', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscriptionId,
                'tenant_id' => app('tenant')?->id,
            ]);
            
            throw $e;
        }
    }

    /**
     * Swap subscription to a different price.
     */
    public function swapSubscription(
        string $subscriptionId,
        string $newPriceId,
        array $options = []
    ): Subscription {
        $client = $this->clientManager->getCurrentTenantClient();
        
        // Get current subscription
        $subscription = $client->subscriptions->retrieve($subscriptionId);
        $currentItem = $subscription->items->data[0];
        
        $params = [
            'items' => [
                [
                    'id' => $currentItem->id,
                    'price' => $newPriceId,
                ]
            ],
            'proration_behavior' => $options['proration_behavior'] ?? 'create_prorations',
        ];
        
        // Add billing cycle anchor if changing billing frequency
        if (isset($options['billing_cycle_anchor'])) {
            $params['billing_cycle_anchor'] = $options['billing_cycle_anchor'];
        }
        
        try {
            $subscription = $client->subscriptions->update($subscriptionId, $params);
            
            Log::info('Stripe subscription swapped', [
                'subscription_id' => $subscriptionId,
                'old_price' => $currentItem->price->id,
                'new_price' => $newPriceId,
                'tenant_id' => app('tenant')?->id,
            ]);
            
            return $subscription;
        } catch (StripeException $e) {
            Log::error('Failed to swap subscription', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscriptionId,
                'new_price_id' => $newPriceId,
                'tenant_id' => app('tenant')?->id,
            ]);
            
            throw $e;
        }
    }

    /**
     * Get or create Stripe products for subscription tiers.
     */
    public function syncSubscriptionProducts(): array
    {
        $client = $this->clientManager->getCurrentTenantClient();
        $results = [];
        
        $tiers = config('tenants.tiers');
        
        foreach ($tiers as $tier => $config) {
            if ($tier === 'free' || !isset($config['price'])) {
                continue;
            }
            
            try {
                // Check if product exists
                $productId = "basketmanager_pro_{$tier}";
                
                try {
                    $product = $client->products->retrieve($productId);
                } catch (StripeException $e) {
                    // Create product if not exists
                    $product = $client->products->create([
                        'id' => $productId,
                        'name' => "BasketManager Pro - {$config['name']}",
                        'description' => "Basketball club management subscription - {$config['name']} tier",
                        'metadata' => [
                            'tier' => $tier,
                            'tenant_id' => app('tenant')?->id,
                        ],
                    ]);
                }
                
                // Check if price exists
                $priceId = "price_{$productId}_monthly";
                
                try {
                    $price = $client->prices->retrieve($priceId);
                } catch (StripeException $e) {
                    // Create price if not exists
                    $price = $client->prices->create([
                        'id' => $priceId,
                        'product' => $product->id,
                        'unit_amount' => $config['price'] * 100, // Convert to cents
                        'currency' => strtolower($config['currency']),
                        'recurring' => [
                            'interval' => 'month',
                        ],
                        'metadata' => [
                            'tier' => $tier,
                            'tenant_id' => app('tenant')?->id,
                        ],
                    ]);
                }
                
                $results[$tier] = [
                    'product' => $product,
                    'price' => $price,
                ];
                
                Log::info('Synced subscription product', [
                    'tier' => $tier,
                    'product_id' => $product->id,
                    'price_id' => $price->id,
                ]);
                
            } catch (StripeException $e) {
                Log::error('Failed to sync subscription product', [
                    'error' => $e->getMessage(),
                    'tier' => $tier,
                    'tenant_id' => app('tenant')?->id,
                ]);
                
                throw $e;
            }
        }
        
        return $results;
    }

    /**
     * Get trial days for a price ID.
     */
    private function getTrialDaysForPrice(string $priceId): int
    {
        $tier = $this->getTierForPriceId($priceId);
        
        if (!$tier) {
            return config('stripe.subscriptions.trial_period_days', 14);
        }
        
        return config("tenants.tiers.{$tier}.trial_days", 14);
    }

    /**
     * Get tier name from price ID.
     */
    private function getTierForPriceId(string $priceId): ?string
    {
        $tiers = config('stripe.subscriptions.tiers', []);
        
        return array_search($priceId, $tiers) ?: null;
    }

    /**
     * Get tax rates for current tenant.
     */
    private function getTaxRatesForTenant(): array
    {
        $tenant = app('tenant');
        
        if (!$tenant) {
            return [];
        }
        
        $countryCode = strtolower($tenant->country_code);
        $taxRates = config('stripe.tax.stripe_tax_rates', []);
        
        return isset($taxRates[$countryCode]) ? [$taxRates[$countryCode]] : [];
    }

    /**
     * Get enabled payment methods.
     */
    private function getEnabledPaymentMethods(): array
    {
        $methods = [];
        
        foreach (config('stripe.payment_methods') as $method => $config) {
            if ($config['enabled'] ?? false) {
                $methods[] = $method;
            }
        }
        
        return $methods;
    }

    /**
     * Get customer ID with tenant prefix if needed.
     */
    private function getCustomerId(string $customerId): string
    {
        return $this->clientManager->getPrefixedCustomerId($customerId);
    }

    /**
     * Get subscription usage for metered billing.
     */
    public function getSubscriptionUsage(string $subscriptionId): array
    {
        $client = $this->clientManager->getCurrentTenantClient();
        
        try {
            $subscription = $client->subscriptions->retrieve($subscriptionId);
            $usage = [];
            
            foreach ($subscription->items->data as $item) {
                if ($item->price->billing_scheme === 'per_unit') {
                    continue; // Skip non-metered items
                }
                
                $usageRecords = $client->subscriptionItems->allUsageRecordSummaries(
                    $item->id,
                    ['limit' => 1]
                );
                
                if (!empty($usageRecords->data)) {
                    $usage[$item->id] = $usageRecords->data[0];
                }
            }
            
            return $usage;
        } catch (StripeException $e) {
            Log::error('Failed to get subscription usage', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscriptionId,
                'tenant_id' => app('tenant')?->id,
            ]);
            
            throw $e;
        }
    }

    /**
     * Report usage for metered billing.
     */
    public function reportUsage(
        string $subscriptionItemId,
        int $quantity,
        int $timestamp = null
    ): \Stripe\UsageRecord {
        $client = $this->clientManager->getCurrentTenantClient();
        
        $params = [
            'quantity' => $quantity,
            'timestamp' => $timestamp ?? time(),
        ];
        
        try {
            return $client->subscriptionItems->createUsageRecord(
                $subscriptionItemId,
                $params
            );
        } catch (StripeException $e) {
            Log::error('Failed to report usage', [
                'error' => $e->getMessage(),
                'subscription_item_id' => $subscriptionItemId,
                'quantity' => $quantity,
                'tenant_id' => app('tenant')?->id,
            ]);
            
            throw $e;
        }
    }
}