<?php

namespace App\Services\Stripe;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\StripeException;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\SetupIntent;
use Stripe\Customer;

class StripePaymentService
{
    public function __construct(
        private StripeClientManager $clientManager
    ) {}

    /**
     * Create a payment intent for a one-time payment.
     */
    public function createPaymentIntent(
        int $amount,
        string $currency = 'eur',
        array $options = []
    ): PaymentIntent {
        $client = $this->clientManager->getCurrentTenantClient();
        
        $params = [
            'amount' => $amount,
            'currency' => strtolower($currency),
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ];
        
        // Add customer if provided
        if (isset($options['customer_id'])) {
            $params['customer'] = $this->getCustomerId($options['customer_id']);
        }
        
        // Add payment method if provided
        if (isset($options['payment_method'])) {
            $params['payment_method'] = $options['payment_method'];
            $params['confirmation_method'] = 'manual';
            $params['confirm'] = $options['confirm'] ?? false;
        }
        
        // Add metadata
        $params['metadata'] = array_merge([
            'tenant_id' => app('tenant')?->id,
            'created_by' => 'basketmanager_pro',
        ], $options['metadata'] ?? []);
        
        // Add description
        if (isset($options['description'])) {
            $params['description'] = $options['description'];
        }
        
        // Add receipt email
        if (isset($options['receipt_email'])) {
            $params['receipt_email'] = $options['receipt_email'];
        }
        
        // Add application fee for platform model
        if ($this->isPlatformMode() && isset($options['application_fee'])) {
            $params['application_fee_amount'] = $options['application_fee'];
        }
        
        try {
            return $client->paymentIntents->create($params);
        } catch (StripeException $e) {
            Log::error('Failed to create payment intent', [
                'error' => $e->getMessage(),
                'tenant_id' => app('tenant')?->id,
                'amount' => $amount,
                'currency' => $currency,
            ]);
            
            throw $e;
        }
    }

    /**
     * Confirm a payment intent.
     */
    public function confirmPaymentIntent(
        string $paymentIntentId,
        ?string $paymentMethod = null
    ): PaymentIntent {
        $client = $this->clientManager->getCurrentTenantClient();
        
        $params = [];
        
        if ($paymentMethod) {
            $params['payment_method'] = $paymentMethod;
        }
        
        try {
            return $client->paymentIntents->confirm($paymentIntentId, $params);
        } catch (StripeException $e) {
            Log::error('Failed to confirm payment intent', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $paymentIntentId,
                'tenant_id' => app('tenant')?->id,
            ]);
            
            throw $e;
        }
    }

    /**
     * Create a setup intent for saving payment methods.
     */
    public function createSetupIntent(
        ?string $customerId = null,
        array $options = []
    ): SetupIntent {
        $client = $this->clientManager->getCurrentTenantClient();
        
        $params = [
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ];
        
        if ($customerId) {
            $params['customer'] = $this->getCustomerId($customerId);
        }
        
        // Add usage hint
        if (isset($options['usage'])) {
            $params['usage'] = $options['usage']; // 'on_session', 'off_session'
        }
        
        // Add metadata
        $params['metadata'] = array_merge([
            'tenant_id' => app('tenant')?->id,
            'created_by' => 'basketmanager_pro',
        ], $options['metadata'] ?? []);
        
        try {
            return $client->setupIntents->create($params);
        } catch (StripeException $e) {
            Log::error('Failed to create setup intent', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId,
                'tenant_id' => app('tenant')?->id,
            ]);
            
            throw $e;
        }
    }

    /**
     * Create a Stripe customer.
     */
    public function createCustomer(
        User $user,
        array $options = []
    ): Customer {
        $client = $this->clientManager->getCurrentTenantClient();
        
        $params = [
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => $user->id,
                'tenant_id' => app('tenant')?->id,
                'created_by' => 'basketmanager_pro',
            ],
        ];
        
        // Add phone if available
        if ($user->phone) {
            $params['phone'] = $user->phone;
        }
        
        // Add address if provided
        if (isset($options['address'])) {
            $params['address'] = $options['address'];
        }
        
        // Add payment method if provided
        if (isset($options['payment_method'])) {
            $params['payment_method'] = $options['payment_method'];
        }
        
        // Add invoice settings
        if (isset($options['invoice_settings'])) {
            $params['invoice_settings'] = $options['invoice_settings'];
        }
        
        // Add preferred locales
        $params['preferred_locales'] = [app('tenant')?->locale ?? 'de'];
        
        try {
            $customer = $client->customers->create($params);
            
            // Update user with Stripe customer ID
            $user->update([
                'stripe_id' => $customer->id,
            ]);
            
            Log::info('Stripe customer created', [
                'customer_id' => $customer->id,
                'user_id' => $user->id,
                'tenant_id' => app('tenant')?->id,
            ]);
            
            return $customer;
        } catch (StripeException $e) {
            Log::error('Failed to create Stripe customer', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'tenant_id' => app('tenant')?->id,
            ]);
            
            throw $e;
        }
    }

    /**
     * Retrieve a Stripe customer.
     */
    public function retrieveCustomer(string $customerId): Customer
    {
        $client = $this->clientManager->getCurrentTenantClient();
        
        try {
            return $client->customers->retrieve($this->getCustomerId($customerId));
        } catch (StripeException $e) {
            Log::error('Failed to retrieve Stripe customer', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId,
                'tenant_id' => app('tenant')?->id,
            ]);
            
            throw $e;
        }
    }

    /**
     * List payment methods for a customer.
     */
    public function listPaymentMethods(
        string $customerId,
        string $type = 'card'
    ): \Stripe\Collection {
        $client = $this->clientManager->getCurrentTenantClient();
        
        try {
            return $client->paymentMethods->all([
                'customer' => $this->getCustomerId($customerId),
                'type' => $type,
            ]);
        } catch (StripeException $e) {
            Log::error('Failed to list payment methods', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId,
                'type' => $type,
                'tenant_id' => app('tenant')?->id,
            ]);
            
            throw $e;
        }
    }

    /**
     * Attach payment method to customer.
     */
    public function attachPaymentMethod(
        string $paymentMethodId,
        string $customerId
    ): PaymentMethod {
        $client = $this->clientManager->getCurrentTenantClient();
        
        try {
            return $client->paymentMethods->attach($paymentMethodId, [
                'customer' => $this->getCustomerId($customerId),
            ]);
        } catch (StripeException $e) {
            Log::error('Failed to attach payment method', [
                'error' => $e->getMessage(),
                'payment_method_id' => $paymentMethodId,
                'customer_id' => $customerId,
                'tenant_id' => app('tenant')?->id,
            ]);
            
            throw $e;
        }
    }

    /**
     * Detach payment method from customer.
     */
    public function detachPaymentMethod(string $paymentMethodId): PaymentMethod
    {
        $client = $this->clientManager->getCurrentTenantClient();
        
        try {
            return $client->paymentMethods->detach($paymentMethodId);
        } catch (StripeException $e) {
            Log::error('Failed to detach payment method', [
                'error' => $e->getMessage(),
                'payment_method_id' => $paymentMethodId,
                'tenant_id' => app('tenant')?->id,
            ]);
            
            throw $e;
        }
    }

    /**
     * Create a checkout session.
     */
    public function createCheckoutSession(array $params): \Stripe\Checkout\Session
    {
        $client = $this->clientManager->getCurrentTenantClient();
        
        // Add default settings
        $params = array_merge([
            'payment_method_types' => $this->getEnabledPaymentMethods(),
            'mode' => 'payment',
        ], $params);
        
        // Add metadata
        if (!isset($params['metadata'])) {
            $params['metadata'] = [];
        }
        
        $params['metadata'] = array_merge([
            'tenant_id' => app('tenant')?->id,
            'created_by' => 'basketmanager_pro',
        ], $params['metadata']);
        
        try {
            return $client->checkout->sessions->create($params);
        } catch (StripeException $e) {
            Log::error('Failed to create checkout session', [
                'error' => $e->getMessage(),
                'tenant_id' => app('tenant')?->id,
            ]);
            
            throw $e;
        }
    }

    /**
     * Get enabled payment methods for current tenant.
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
     * Check if platform mode is enabled.
     */
    private function isPlatformMode(): bool
    {
        return config('stripe.multi_tenant.mode') === 'separate';
    }

    /**
     * Process SEPA Direct Debit payment.
     */
    public function createSepaPayment(
        int $amount,
        string $customerId,
        array $options = []
    ): PaymentIntent {
        $params = array_merge($options, [
            'payment_method_types' => ['sepa_debit'],
            'mandate_data' => [
                'customer_acceptance' => [
                    'type' => 'online',
                    'online' => [
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ],
                ],
            ],
        ]);
        
        return $this->createPaymentIntent($amount, 'eur', $params);
    }

    /**
     * Calculate application fee for platform mode.
     */
    public function calculateApplicationFee(int $amount, ?float $feePercentage = null): int
    {
        $feePercentage = $feePercentage ?? config('stripe.multi_tenant.separate.platform_fee_percentage', 2.5);
        
        return (int) round($amount * ($feePercentage / 100));
    }
}