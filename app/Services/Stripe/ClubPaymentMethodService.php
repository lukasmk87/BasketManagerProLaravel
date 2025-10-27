<?php

namespace App\Services\Stripe;

use App\Models\Club;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Stripe\Collection as StripeCollection;
use Stripe\Exception\StripeException;
use Stripe\PaymentMethod;
use Stripe\SetupIntent;

/**
 * Service for managing Stripe payment methods for club subscriptions.
 *
 * Handles adding, removing, and managing payment methods specifically
 * for clubs with Stripe subscriptions. Supports German payment methods
 * like SEPA, Giropay, and SOFORT.
 */
class ClubPaymentMethodService
{
    public function __construct(
        private StripeClientManager $clientManager,
        private ClubStripeCustomerService $customerService
    ) {}

    /**
     * Create a SetupIntent for adding payment methods.
     *
     * SetupIntents are used to collect payment method details without charging.
     *
     * @param  Club  $club
     * @param  array  $options  Optional: usage, payment_method_types, return_url
     * @return SetupIntent
     *
     * @throws \Exception
     */
    public function createSetupIntent(Club $club, array $options = []): SetupIntent
    {
        // Get or create Stripe customer
        $customer = $this->customerService->getOrCreateCustomer($club);

        $client = $this->clientManager->getCurrentTenantClient();

        $params = [
            'customer' => $customer->id,
            'usage' => $options['usage'] ?? 'off_session',
            'automatic_payment_methods' => [
                'enabled' => true,
                'allow_redirects' => 'never', // Disable redirect-based methods for better UX
            ],
        ];

        // Add return URL if provided (needed for redirect-based payment methods)
        if (isset($options['return_url'])) {
            $params['automatic_payment_methods']['allow_redirects'] = 'always';
            $params['return_url'] = $options['return_url'];
        }

        // Override with specific payment method types if provided
        if (isset($options['payment_method_types'])) {
            unset($params['automatic_payment_methods']);
            $params['payment_method_types'] = $options['payment_method_types'];
        }

        // Add metadata
        $params['metadata'] = [
            'club_id' => $club->id,
            'club_uuid' => $club->uuid,
            'club_name' => $club->name,
            'tenant_id' => $club->tenant_id,
        ];

        try {
            $setupIntent = $client->setupIntents->create($params);

            Log::info('Club SetupIntent created', [
                'club_id' => $club->id,
                'setup_intent_id' => $setupIntent->id,
                'customer_id' => $customer->id,
                'tenant_id' => $club->tenant_id,
            ]);

            return $setupIntent;
        } catch (StripeException $e) {
            Log::error('Failed to create club SetupIntent', [
                'club_id' => $club->id,
                'tenant_id' => $club->tenant_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * List all payment methods for a club.
     *
     * @param  Club  $club
     * @param  string  $type  Payment method type: card, sepa_debit, giropay, sofort
     * @return Collection Collection of formatted payment methods
     *
     * @throws \Exception
     */
    public function listPaymentMethods(Club $club, string $type = 'card'): Collection
    {
        if (! $club->stripe_customer_id) {
            throw new \Exception('Club has no Stripe customer');
        }

        $client = $this->clientManager->getCurrentTenantClient();

        try {
            $paymentMethods = $client->paymentMethods->all([
                'customer' => $club->stripe_customer_id,
                'type' => $type,
            ]);

            Log::info('Club payment methods retrieved', [
                'club_id' => $club->id,
                'type' => $type,
                'count' => count($paymentMethods->data),
                'tenant_id' => $club->tenant_id,
            ]);

            return collect($paymentMethods->data)->map(function ($pm) use ($club) {
                return $this->formatPaymentMethod($pm, $club);
            });
        } catch (StripeException $e) {
            Log::error('Failed to list club payment methods', [
                'club_id' => $club->id,
                'type' => $type,
                'tenant_id' => $club->tenant_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Attach a payment method to club's customer.
     *
     * @param  Club  $club
     * @param  string  $paymentMethodId
     * @param  bool  $setAsDefault  Whether to set as default payment method
     * @return PaymentMethod
     *
     * @throws \Exception
     */
    public function attachPaymentMethod(
        Club $club,
        string $paymentMethodId,
        bool $setAsDefault = false
    ): PaymentMethod {
        // Ensure club has Stripe customer
        $customer = $this->customerService->getOrCreateCustomer($club);

        $client = $this->clientManager->getCurrentTenantClient();

        try {
            // Attach payment method to customer
            $paymentMethod = $client->paymentMethods->attach($paymentMethodId, [
                'customer' => $customer->id,
            ]);

            Log::info('Payment method attached to club', [
                'club_id' => $club->id,
                'payment_method_id' => $paymentMethodId,
                'customer_id' => $customer->id,
                'set_as_default' => $setAsDefault,
                'tenant_id' => $club->tenant_id,
            ]);

            // Set as default if requested
            if ($setAsDefault) {
                $this->setDefaultPaymentMethod($club, $paymentMethodId);
            }

            return $paymentMethod;
        } catch (StripeException $e) {
            Log::error('Failed to attach payment method to club', [
                'club_id' => $club->id,
                'payment_method_id' => $paymentMethodId,
                'tenant_id' => $club->tenant_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Detach a payment method from club's customer.
     *
     * @param  Club  $club
     * @param  string  $paymentMethodId
     * @return PaymentMethod
     *
     * @throws \Exception
     */
    public function detachPaymentMethod(Club $club, string $paymentMethodId): PaymentMethod
    {
        if (! $club->stripe_customer_id) {
            throw new \Exception('Club has no Stripe customer');
        }

        $client = $this->clientManager->getCurrentTenantClient();

        try {
            // Retrieve payment method to verify ownership
            $paymentMethod = $client->paymentMethods->retrieve($paymentMethodId);

            if ($paymentMethod->customer !== $club->stripe_customer_id) {
                throw new \Exception('Payment method does not belong to this club');
            }

            // Detach payment method
            $paymentMethod = $client->paymentMethods->detach($paymentMethodId);

            Log::info('Payment method detached from club', [
                'club_id' => $club->id,
                'payment_method_id' => $paymentMethodId,
                'tenant_id' => $club->tenant_id,
            ]);

            // If this was the default payment method, clear it
            if ($club->payment_method_id === $paymentMethodId) {
                $club->update(['payment_method_id' => null]);
            }

            return $paymentMethod;
        } catch (StripeException $e) {
            Log::error('Failed to detach payment method from club', [
                'club_id' => $club->id,
                'payment_method_id' => $paymentMethodId,
                'tenant_id' => $club->tenant_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Set default payment method for club's subscription.
     *
     * @param  Club  $club
     * @param  string  $paymentMethodId
     * @return void
     *
     * @throws \Exception
     */
    public function setDefaultPaymentMethod(Club $club, string $paymentMethodId): void
    {
        if (! $club->stripe_customer_id) {
            throw new \Exception('Club has no Stripe customer');
        }

        $client = $this->clientManager->getCurrentTenantClient();

        try {
            // Update customer's default payment method
            $client->customers->update($club->stripe_customer_id, [
                'invoice_settings' => [
                    'default_payment_method' => $paymentMethodId,
                ],
            ]);

            // Update club's payment_method_id
            $club->update(['payment_method_id' => $paymentMethodId]);

            // If club has an active subscription, update its default payment method
            if ($club->stripe_subscription_id) {
                $client->subscriptions->update($club->stripe_subscription_id, [
                    'default_payment_method' => $paymentMethodId,
                ]);
            }

            Log::info('Default payment method set for club', [
                'club_id' => $club->id,
                'payment_method_id' => $paymentMethodId,
                'customer_id' => $club->stripe_customer_id,
                'subscription_id' => $club->stripe_subscription_id,
                'tenant_id' => $club->tenant_id,
            ]);
        } catch (StripeException $e) {
            Log::error('Failed to set default payment method for club', [
                'club_id' => $club->id,
                'payment_method_id' => $paymentMethodId,
                'tenant_id' => $club->tenant_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update payment method billing details.
     *
     * @param  Club  $club
     * @param  string  $paymentMethodId
     * @param  array  $billingDetails  name, email, phone, address
     * @return PaymentMethod
     *
     * @throws \Exception
     */
    public function updatePaymentMethod(
        Club $club,
        string $paymentMethodId,
        array $billingDetails
    ): PaymentMethod {
        if (! $club->stripe_customer_id) {
            throw new \Exception('Club has no Stripe customer');
        }

        $client = $this->clientManager->getCurrentTenantClient();

        try {
            // Retrieve to verify ownership
            $paymentMethod = $client->paymentMethods->retrieve($paymentMethodId);

            if ($paymentMethod->customer !== $club->stripe_customer_id) {
                throw new \Exception('Payment method does not belong to this club');
            }

            // Update billing details
            $paymentMethod = $client->paymentMethods->update($paymentMethodId, [
                'billing_details' => $billingDetails,
            ]);

            Log::info('Payment method billing details updated', [
                'club_id' => $club->id,
                'payment_method_id' => $paymentMethodId,
                'tenant_id' => $club->tenant_id,
            ]);

            return $paymentMethod;
        } catch (StripeException $e) {
            Log::error('Failed to update payment method', [
                'club_id' => $club->id,
                'payment_method_id' => $paymentMethodId,
                'tenant_id' => $club->tenant_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get supported German payment methods.
     *
     * @return array
     */
    public function getGermanPaymentMethods(): array
    {
        return [
            'card',           // Credit/Debit cards
            'sepa_debit',     // SEPA Direct Debit (SEPA Lastschrift)
            'sofort',         // SOFORT (Klarna)
            'giropay',        // Giropay
            'eps',            // EPS (Austria)
            'bancontact',     // Bancontact (Belgium)
            'ideal',          // iDEAL (Netherlands)
        ];
    }

    /**
     * Get localized payment method names for German users.
     *
     * @return array
     */
    public function getLocalizedPaymentMethodNames(): array
    {
        return [
            'card' => 'Kreditkarte / EC-Karte',
            'sepa_debit' => 'SEPA Lastschrift',
            'sofort' => 'SOFORT Überweisung',
            'giropay' => 'Giropay',
            'eps' => 'EPS',
            'bancontact' => 'Bancontact',
            'ideal' => 'iDEAL',
        ];
    }

    /**
     * Format payment method for consistent API responses.
     *
     * @param  PaymentMethod  $paymentMethod
     * @param  Club  $club
     * @return array Formatted payment method data
     */
    protected function formatPaymentMethod(PaymentMethod $paymentMethod, Club $club): array
    {
        $formatted = [
            'id' => $paymentMethod->id,
            'type' => $paymentMethod->type,
            'created' => $paymentMethod->created,
            'customer' => $paymentMethod->customer,
            'is_default' => $club->payment_method_id === $paymentMethod->id,
            'billing_details' => [
                'name' => $paymentMethod->billing_details?->name,
                'email' => $paymentMethod->billing_details?->email,
                'phone' => $paymentMethod->billing_details?->phone,
                'address' => $paymentMethod->billing_details?->address ? [
                    'line1' => $paymentMethod->billing_details->address->line1,
                    'line2' => $paymentMethod->billing_details->address->line2,
                    'city' => $paymentMethod->billing_details->address->city,
                    'state' => $paymentMethod->billing_details->address->state,
                    'postal_code' => $paymentMethod->billing_details->address->postal_code,
                    'country' => $paymentMethod->billing_details->address->country,
                ] : null,
            ],
        ];

        // Add type-specific details
        switch ($paymentMethod->type) {
            case 'card':
                $formatted['card'] = [
                    'brand' => $paymentMethod->card?->brand,
                    'last4' => $paymentMethod->card?->last4,
                    'exp_month' => $paymentMethod->card?->exp_month,
                    'exp_year' => $paymentMethod->card?->exp_year,
                    'country' => $paymentMethod->card?->country,
                    'funding' => $paymentMethod->card?->funding,
                ];
                $formatted['display_name'] = ucfirst($paymentMethod->card?->brand).' •••• '.$paymentMethod->card?->last4;
                break;

            case 'sepa_debit':
                $formatted['sepa_debit'] = [
                    'bank_code' => $paymentMethod->sepa_debit?->bank_code,
                    'country' => $paymentMethod->sepa_debit?->country,
                    'last4' => $paymentMethod->sepa_debit?->last4,
                    'fingerprint' => $paymentMethod->sepa_debit?->fingerprint,
                ];
                $formatted['display_name'] = 'SEPA •••• '.$paymentMethod->sepa_debit?->last4;
                break;

            case 'sofort':
                $formatted['sofort'] = [
                    'country' => $paymentMethod->sofort?->country,
                ];
                $formatted['display_name'] = 'SOFORT';
                break;

            case 'giropay':
                $formatted['display_name'] = 'Giropay';
                break;

            case 'eps':
                $formatted['eps'] = [
                    'bank' => $paymentMethod->eps?->bank,
                ];
                $formatted['display_name'] = 'EPS';
                break;

            case 'bancontact':
                $formatted['display_name'] = 'Bancontact';
                break;

            case 'ideal':
                $formatted['ideal'] = [
                    'bank' => $paymentMethod->ideal?->bank,
                    'bic' => $paymentMethod->ideal?->bic,
                ];
                $formatted['display_name'] = 'iDEAL';
                break;

            default:
                $formatted['display_name'] = ucfirst($paymentMethod->type);
        }

        return $formatted;
    }
}
