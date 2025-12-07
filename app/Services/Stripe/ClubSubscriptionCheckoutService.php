<?php

namespace App\Services\Stripe;

use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Exception\StripeException;

class ClubSubscriptionCheckoutService
{
    public function __construct(
        private StripeClientManager $clientManager,
        private ClubStripeCustomerService $customerService,
        private ?StripeConnectCheckoutService $connectCheckoutService = null
    ) {}

    /**
     * Create Stripe Checkout Session for Club Subscription.
     *
     * @throws \Exception
     */
    public function createCheckoutSession(
        Club $club,
        ClubSubscriptionPlan $plan,
        array $options = []
    ): Session {
        // Validate plan belongs to same tenant
        if ($plan->tenant_id !== $club->tenant_id) {
            throw new \Exception('Plan does not belong to club\'s tenant');
        }

        // Validate plan is active and synced with Stripe
        if (! $plan->is_active) {
            throw new \Exception('Plan is not active');
        }

        if (! $plan->is_stripe_synced) {
            throw new \Exception('Plan is not synced with Stripe');
        }

        // Check if tenant has active Stripe Connect - route payments through Connect
        $tenant = $club->tenant;
        if ($tenant && $tenant->hasActiveStripeConnect() && $this->connectCheckoutService) {
            Log::info('Using Stripe Connect for club checkout', [
                'club_id' => $club->id,
                'tenant_id' => $tenant->id,
                'connect_account' => $tenant->stripe_connect_account_id,
            ]);

            return $this->connectCheckoutService->createConnectedCheckoutSession(
                $club,
                $plan,
                $options['success_url'] ?? $this->getDefaultSuccessUrl($club),
                $options['cancel_url'] ?? $this->getDefaultCancelUrl($club),
                $options
            );
        }

        // Fallback: Use platform account (existing logic)
        return $this->createPlatformCheckoutSession($club, $plan, $options);
    }

    /**
     * Create checkout session using platform Stripe account.
     *
     * @throws \Exception
     */
    protected function createPlatformCheckoutSession(
        Club $club,
        ClubSubscriptionPlan $plan,
        array $options = []
    ): Session {
        // Get billing interval
        $billingInterval = $options['billing_interval'] ?? 'monthly';
        $priceId = $billingInterval === 'yearly'
            ? $plan->stripe_price_id_yearly
            : $plan->stripe_price_id_monthly;

        if (! $priceId) {
            throw new \Exception("No Stripe Price ID for {$billingInterval} billing");
        }

        // Get or create Stripe Customer
        $customer = $this->customerService->getOrCreateCustomer($club);

        $client = $this->clientManager->getCurrentTenantClient();

        $sessionData = [
            'mode' => 'subscription',
            'customer' => $customer->id,
            'line_items' => [
                [
                    'price' => $priceId,
                    'quantity' => 1,
                ],
            ],
            'success_url' => $options['success_url'] ?? $this->getDefaultSuccessUrl($club),
            'cancel_url' => $options['cancel_url'] ?? $this->getDefaultCancelUrl($club),
            'metadata' => [
                'club_id' => $club->id,
                'club_uuid' => $club->uuid,
                'club_subscription_plan_id' => $plan->id,
                'tenant_id' => $club->tenant_id,
                'billing_interval' => $billingInterval,
            ],
            'subscription_data' => [
                'metadata' => [
                    'club_id' => $club->id,
                    'club_uuid' => $club->uuid,
                    'plan_id' => $plan->id,
                    'tenant_id' => $club->tenant_id,
                ],
            ],
            'locale' => 'de',
            'billing_address_collection' => 'required',
            'phone_number_collection' => [
                'enabled' => true,
            ],
        ];

        // Add trial period if configured
        if ($plan->trial_period_days > 0) {
            $sessionData['subscription_data']['trial_period_days'] = $plan->trial_period_days;
        }

        // Add customer's email
        if ($club->billing_email ?? $club->email) {
            $sessionData['customer_email'] = $club->billing_email ?? $club->email;
        }

        // Add payment method types
        $sessionData['payment_method_types'] = ['card', 'sepa_debit'];

        // Add automatic tax calculation
        $sessionData['automatic_tax'] = [
            'enabled' => true,
        ];

        // Add tax ID collection
        $sessionData['tax_id_collection'] = [
            'enabled' => true,
        ];

        // Merge custom options
        if (isset($options['session_data'])) {
            $sessionData = array_merge($sessionData, $options['session_data']);
        }

        try {
            $session = $client->checkout->sessions->create($sessionData);

            Log::info('Club Checkout Session created', [
                'club_id' => $club->id,
                'plan_id' => $plan->id,
                'session_id' => $session->id,
                'billing_interval' => $billingInterval,
                'trial_period_days' => $plan->trial_period_days,
                'tenant_id' => $club->tenant_id,
            ]);

            return $session;
        } catch (StripeException $e) {
            Log::error('Failed to create Club Checkout Session', [
                'club_id' => $club->id,
                'plan_id' => $plan->id,
                'billing_interval' => $billingInterval,
                'tenant_id' => $club->tenant_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create Stripe Portal Session for managing subscription.
     *
     * @throws \Exception
     */
    public function createPortalSession(Club $club, string $returnUrl): \Stripe\BillingPortal\Session
    {
        if (! $club->stripe_customer_id) {
            throw new \Exception('Club has no Stripe Customer');
        }

        $client = $this->clientManager->getCurrentTenantClient();

        try {
            $session = $client->billingPortal->sessions->create([
                'customer' => $club->stripe_customer_id,
                'return_url' => $returnUrl,
                'locale' => 'de',
            ]);

            Log::info('Billing Portal Session created', [
                'club_id' => $club->id,
                'customer_id' => $club->stripe_customer_id,
                'session_id' => $session->id,
                'tenant_id' => $club->tenant_id,
            ]);

            return $session;
        } catch (StripeException $e) {
            Log::error('Failed to create Billing Portal Session', [
                'club_id' => $club->id,
                'customer_id' => $club->stripe_customer_id,
                'tenant_id' => $club->tenant_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get default success URL for checkout.
     */
    protected function getDefaultSuccessUrl(Club $club): string
    {
        return route('club.checkout.success', ['club' => $club->id]).'?session_id={CHECKOUT_SESSION_ID}';
    }

    /**
     * Get default cancel URL for checkout.
     */
    protected function getDefaultCancelUrl(Club $club): string
    {
        return route('club.checkout.cancel', ['club' => $club->id]);
    }
}
