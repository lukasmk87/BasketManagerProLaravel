<?php

namespace App\Services\Stripe;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Laravel\Cashier\Subscription;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Price;

/**
 * Service for managing Stripe Checkout flows with German compliance
 * Handles subscription checkouts, one-time payments, and German tax requirements
 */
class CheckoutService
{
    private CashierTenantManager $cashierManager;
    private PaymentMethodService $paymentMethodService;

    public function __construct(
        CashierTenantManager $cashierManager,
        PaymentMethodService $paymentMethodService
    ) {
        $this->cashierManager = $cashierManager;
        $this->paymentMethodService = $paymentMethodService;
    }

    /**
     * Create subscription checkout session with German compliance
     *
     * @param User|Tenant $billable
     * @param string $priceId
     * @param array $options
     * @return Session
     * @throws ApiErrorException
     */
    public function createSubscriptionCheckout($billable, string $priceId, array $options = []): Session
    {
        if ($billable instanceof Tenant) {
            $this->cashierManager->configureStripeForTenant($billable);
        }

        // Ensure customer exists
        if (!$billable->stripe_id) {
            $billable->createAsStripeCustomer();
        }

        $defaultOptions = [
            'customer' => $billable->stripe_id,
            'payment_method_types' => $this->paymentMethodService->getGermanPaymentMethods(),
            'line_items' => [
                [
                    'price' => $priceId,
                    'quantity' => 1,
                ],
            ],
            'mode' => 'subscription',
            'success_url' => URL::to('/subscription/success?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => URL::to('/subscription/cancel'),
            'billing_address_collection' => 'required',
            'customer_update' => [
                'address' => 'auto',
                'name' => 'auto',
            ],
            'automatic_tax' => [
                'enabled' => true,
            ],
            'tax_id_collection' => [
                'enabled' => true,
            ],
            'locale' => 'de',
            'metadata' => [
                'tenant_id' => $billable instanceof Tenant ? $billable->id : null,
                'user_id' => $billable instanceof User ? $billable->id : null,
                'platform' => app_name(),
                'checkout_type' => 'subscription',
            ],
        ];

        // Add German-specific configuration
        $defaultOptions = $this->addGermanComplianceOptions($defaultOptions, $options);

        $sessionData = array_merge($defaultOptions, $options);

        Log::info('Creating subscription checkout session', [
            'customer' => $billable->stripe_id,
            'price_id' => $priceId,
            'tenant_id' => $billable instanceof Tenant ? $billable->id : null,
        ]);

        return Session::create($sessionData);
    }

    /**
     * Create one-time payment checkout session
     *
     * @param User|Tenant $billable
     * @param array $lineItems
     * @param array $options
     * @return Session
     * @throws ApiErrorException
     */
    public function createPaymentCheckout($billable, array $lineItems, array $options = []): Session
    {
        if ($billable instanceof Tenant) {
            $this->cashierManager->configureStripeForTenant($billable);
        }

        $defaultOptions = [
            'payment_method_types' => $this->paymentMethodService->getGermanPaymentMethods(),
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => URL::to('/payment/success?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => URL::to('/payment/cancel'),
            'billing_address_collection' => 'required',
            'automatic_tax' => [
                'enabled' => true,
            ],
            'tax_id_collection' => [
                'enabled' => true,
            ],
            'locale' => 'de',
            'metadata' => [
                'tenant_id' => $billable instanceof Tenant ? $billable->id : null,
                'user_id' => $billable instanceof User ? $billable->id : null,
                'platform' => app_name(),
                'checkout_type' => 'payment',
            ],
        ];

        if ($billable->stripe_id) {
            $defaultOptions['customer'] = $billable->stripe_id;
        }

        $defaultOptions = $this->addGermanComplianceOptions($defaultOptions, $options);
        $sessionData = array_merge($defaultOptions, $options);

        Log::info('Creating payment checkout session', [
            'customer' => $billable->stripe_id ?? 'new',
            'line_items_count' => count($lineItems),
            'tenant_id' => $billable instanceof Tenant ? $billable->id : null,
        ]);

        return Session::create($sessionData);
    }

    /**
     * Create setup mode checkout for payment method collection
     *
     * @param User|Tenant $billable
     * @param array $options
     * @return Session
     * @throws ApiErrorException
     */
    public function createSetupCheckout($billable, array $options = []): Session
    {
        if ($billable instanceof Tenant) {
            $this->cashierManager->configureStripeForTenant($billable);
        }

        // Ensure customer exists
        if (!$billable->stripe_id) {
            $billable->createAsStripeCustomer();
        }

        $defaultOptions = [
            'customer' => $billable->stripe_id,
            'payment_method_types' => $this->paymentMethodService->getGermanPaymentMethods(),
            'mode' => 'setup',
            'success_url' => URL::to('/payment-methods/success?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => URL::to('/payment-methods'),
            'locale' => 'de',
            'metadata' => [
                'tenant_id' => $billable instanceof Tenant ? $billable->id : null,
                'user_id' => $billable instanceof User ? $billable->id : null,
                'platform' => app_name(),
                'checkout_type' => 'setup',
            ],
        ];

        $sessionData = array_merge($defaultOptions, $options);

        Log::info('Creating setup checkout session', [
            'customer' => $billable->stripe_id,
            'tenant_id' => $billable instanceof Tenant ? $billable->id : null,
        ]);

        return Session::create($sessionData);
    }

    /**
     * Retrieve checkout session
     *
     * @param string $sessionId
     * @return Session
     * @throws ApiErrorException
     */
    public function retrieveSession(string $sessionId): Session
    {
        return Session::retrieve($sessionId);
    }

    /**
     * Create line item for checkout
     *
     * @param string $name
     * @param int $amount Amount in cents
     * @param string $currency
     * @param int $quantity
     * @param array $options
     * @return array
     */
    public function createLineItem(string $name, int $amount, string $currency = 'eur', int $quantity = 1, array $options = []): array
    {
        $lineItem = [
            'price_data' => [
                'currency' => $currency,
                'product_data' => [
                    'name' => $name,
                ],
                'unit_amount' => $amount,
            ],
            'quantity' => $quantity,
        ];

        // Add optional fields
        if (isset($options['description'])) {
            $lineItem['price_data']['product_data']['description'] = $options['description'];
        }

        if (isset($options['images'])) {
            $lineItem['price_data']['product_data']['images'] = $options['images'];
        }

        if (isset($options['metadata'])) {
            $lineItem['price_data']['product_data']['metadata'] = $options['metadata'];
        }

        if (isset($options['tax_behavior'])) {
            $lineItem['price_data']['tax_behavior'] = $options['tax_behavior'];
        }

        return $lineItem;
    }

    /**
     * Get subscription prices for checkout display
     *
     * @param array $priceIds
     * @return array
     * @throws ApiErrorException
     */
    public function getSubscriptionPrices(array $priceIds): array
    {
        $prices = [];

        foreach ($priceIds as $priceId) {
            try {
                $price = Price::retrieve($priceId);
                $prices[] = $this->formatPriceForDisplay($price);
            } catch (ApiErrorException $e) {
                Log::warning('Failed to retrieve price', [
                    'price_id' => $priceId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $prices;
    }

    /**
     * Add German compliance options to checkout session
     *
     * @param array $defaultOptions
     * @param array $userOptions
     * @return array
     */
    private function addGermanComplianceOptions(array $defaultOptions, array $userOptions): array
    {
        // German privacy policy and terms links
        $defaultOptions['consent_collection'] = [
            'terms_of_service' => 'required',
        ];

        // Add custom fields for German requirements
        $defaultOptions['custom_fields'] = [
            [
                'key' => 'newsletter_opt_in',
                'label' => [
                    'type' => 'string',
                    'value' => 'Newsletter abonnieren (optional)',
                ],
                'type' => 'dropdown',
                'dropdown' => [
                    'options' => [
                        ['label' => 'Ja, ich möchte den Newsletter erhalten', 'value' => 'yes'],
                        ['label' => 'Nein, danke', 'value' => 'no'],
                    ],
                ],
                'optional' => true,
            ],
        ];

        // Configure phone number collection for German users
        $defaultOptions['phone_number_collection'] = [
            'enabled' => true,
        ];

        // Set invoice creation for German tax compliance
        $defaultOptions['invoice_creation'] = [
            'enabled' => true,
            'invoice_data' => [
                'description' => app_name() . ' Subscription',
                'metadata' => [
                    'created_via' => 'checkout',
                ],
            ],
        ];

        return $defaultOptions;
    }

    /**
     * Format price object for frontend display
     *
     * @param Price $price
     * @return array
     */
    private function formatPriceForDisplay(Price $price): array
    {
        return [
            'id' => $price->id,
            'currency' => strtoupper($price->currency),
            'unit_amount' => $price->unit_amount,
            'unit_amount_decimal' => $price->unit_amount_decimal,
            'recurring' => $price->recurring ? [
                'interval' => $price->recurring->interval,
                'interval_count' => $price->recurring->interval_count,
            ] : null,
            'product' => $price->product,
            'active' => $price->active,
            'display_amount' => $this->formatAmountForDisplay($price->unit_amount, $price->currency),
        ];
    }

    /**
     * Format amount for German currency display
     *
     * @param int $amount Amount in cents
     * @param string $currency
     * @return string
     */
    private function formatAmountForDisplay(int $amount, string $currency): string
    {
        $formatted = number_format($amount / 100, 2, ',', '.');
        
        switch (strtoupper($currency)) {
            case 'EUR':
                return $formatted . ' €';
            case 'USD':
                return '$' . str_replace(',', '.', number_format($amount / 100, 2));
            default:
                return $formatted . ' ' . strtoupper($currency);
        }
    }

    /**
     * Handle successful subscription checkout
     *
     * @param Session $session
     * @param User|Tenant $billable
     * @return Subscription|null
     */
    public function handleSuccessfulSubscriptionCheckout(Session $session, $billable): ?Subscription
    {
        if ($billable instanceof Tenant) {
            $this->cashierManager->configureStripeForTenant($billable);
        }

        try {
            // Retrieve the subscription from Stripe
            $stripeSubscription = \Stripe\Subscription::retrieve($session->subscription);
            
            // Find the local subscription
            $subscription = $billable->subscriptions()
                ->where('stripe_id', $stripeSubscription->id)
                ->first();

            if ($subscription) {
                Log::info('Subscription checkout completed successfully', [
                    'session_id' => $session->id,
                    'subscription_id' => $subscription->id,
                    'customer' => $billable->stripe_id,
                ]);

                return $subscription;
            }

        } catch (\Exception $e) {
            Log::error('Failed to handle successful subscription checkout', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }
}