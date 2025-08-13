<?php

namespace App\Services\Stripe;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentMethod;
use Stripe\SetupIntent;
use Stripe\Stripe;

/**
 * Service for managing Stripe payment methods with German payment compliance
 * Supports SEPA, Credit Cards, and other European payment methods
 */
class PaymentMethodService
{
    private CashierTenantManager $cashierManager;

    public function __construct(CashierTenantManager $cashierManager)
    {
        $this->cashierManager = $cashierManager;
    }

    /**
     * Create setup intent for adding payment methods
     *
     * @param User|Tenant $billable
     * @param array $options
     * @return SetupIntent
     * @throws ApiErrorException
     */
    public function createSetupIntent($billable, array $options = []): SetupIntent
    {
        if ($billable instanceof Tenant) {
            $this->cashierManager->configureStripeForTenant($billable);
        }

        $defaultOptions = [
            'customer' => $billable->stripe_id ?? $billable->createAsStripeCustomer()->stripe_id,
            'usage' => 'off_session',
            'payment_method_types' => $this->getGermanPaymentMethods(),
        ];

        $setupIntentData = array_merge($defaultOptions, $options);

        Log::info('Creating setup intent', [
            'customer' => $setupIntentData['customer'],
            'payment_methods' => $setupIntentData['payment_method_types'],
            'tenant_id' => $billable instanceof Tenant ? $billable->id : null,
        ]);

        return SetupIntent::create($setupIntentData);
    }

    /**
     * Get supported German payment methods
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
     * Retrieve all payment methods for a customer
     *
     * @param User|Tenant $billable
     * @param string|null $type
     * @return array
     */
    public function getPaymentMethods($billable, string $type = null): array
    {
        if ($billable instanceof Tenant) {
            $this->cashierManager->configureStripeForTenant($billable);
        }

        if (!$billable->stripe_id) {
            return [];
        }

        try {
            $params = ['customer' => $billable->stripe_id];
            if ($type) {
                $params['type'] = $type;
            }

            $paymentMethods = PaymentMethod::all($params);
            
            return array_map(function ($pm) {
                return $this->formatPaymentMethod($pm);
            }, $paymentMethods->data);

        } catch (ApiErrorException $e) {
            Log::error('Failed to retrieve payment methods', [
                'customer' => $billable->stripe_id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Add payment method to customer
     *
     * @param User|Tenant $billable
     * @param string $paymentMethodId
     * @param bool $setAsDefault
     * @return PaymentMethod
     * @throws ApiErrorException
     */
    public function attachPaymentMethod($billable, string $paymentMethodId, bool $setAsDefault = false): PaymentMethod
    {
        if ($billable instanceof Tenant) {
            $this->cashierManager->configureStripeForTenant($billable);
        }

        // Ensure customer exists
        if (!$billable->stripe_id) {
            $billable->createAsStripeCustomer();
        }

        $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
        $paymentMethod->attach(['customer' => $billable->stripe_id]);

        if ($setAsDefault) {
            $this->setDefaultPaymentMethod($billable, $paymentMethodId);
        }

        Log::info('Payment method attached', [
            'payment_method' => $paymentMethodId,
            'customer' => $billable->stripe_id,
            'default' => $setAsDefault,
        ]);

        return $paymentMethod;
    }

    /**
     * Remove payment method from customer
     *
     * @param User|Tenant $billable
     * @param string $paymentMethodId
     * @return PaymentMethod
     * @throws ApiErrorException
     */
    public function detachPaymentMethod($billable, string $paymentMethodId): PaymentMethod
    {
        if ($billable instanceof Tenant) {
            $this->cashierManager->configureStripeForTenant($billable);
        }

        $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
        $paymentMethod->detach();

        Log::info('Payment method detached', [
            'payment_method' => $paymentMethodId,
            'customer' => $billable->stripe_id,
        ]);

        return $paymentMethod;
    }

    /**
     * Set default payment method for customer
     *
     * @param User|Tenant $billable
     * @param string $paymentMethodId
     * @return void
     * @throws ApiErrorException
     */
    public function setDefaultPaymentMethod($billable, string $paymentMethodId): void
    {
        if ($billable instanceof Tenant) {
            $this->cashierManager->configureStripeForTenant($billable);
        }

        $billable->updateDefaultPaymentMethod($paymentMethodId);

        Log::info('Default payment method updated', [
            'payment_method' => $paymentMethodId,
            'customer' => $billable->stripe_id,
        ]);
    }

    /**
     * Process one-time payment with German compliance
     *
     * @param User|Tenant $billable
     * @param int $amount Amount in cents
     * @param string $currency
     * @param string|null $paymentMethodId
     * @param array $options
     * @return \Laravel\Cashier\Payment
     * @throws IncompletePayment
     */
    public function processPayment($billable, int $amount, string $currency = 'eur', string $paymentMethodId = null, array $options = []): \Laravel\Cashier\Payment
    {
        if ($billable instanceof Tenant) {
            $this->cashierManager->configureStripeForTenant($billable);
        }

        $defaultOptions = [
            'currency' => $currency,
            'description' => 'BasketManager Pro Payment',
            'metadata' => [
                'tenant_id' => $billable instanceof Tenant ? $billable->id : null,
                'user_id' => $billable instanceof User ? $billable->id : null,
                'platform' => 'BasketManager Pro',
            ],
            'statement_descriptor' => 'BasketManager Pro',
        ];

        $paymentOptions = array_merge($defaultOptions, $options);

        Log::info('Processing payment', [
            'amount' => $amount,
            'currency' => $currency,
            'customer' => $billable->stripe_id,
            'payment_method' => $paymentMethodId,
        ]);

        if ($paymentMethodId) {
            return $billable->charge($amount, $paymentMethodId, $paymentOptions);
        }

        return $billable->charge($amount, null, $paymentOptions);
    }

    /**
     * Validate German VAT requirements
     *
     * @param array $billingData
     * @return bool
     */
    public function validateGermanVAT(array $billingData): bool
    {
        // German VAT (MwSt.) validation
        if (isset($billingData['country']) && $billingData['country'] === 'DE') {
            // German customers need valid postal code and state
            return isset($billingData['postal_code']) && isset($billingData['state']);
        }

        // EU VAT validation for B2B customers
        if (isset($billingData['vat_number']) && !empty($billingData['vat_number'])) {
            return $this->validateEUVATNumber($billingData['vat_number']);
        }

        return true;
    }

    /**
     * Validate EU VAT number format
     *
     * @param string $vatNumber
     * @return bool
     */
    private function validateEUVATNumber(string $vatNumber): bool
    {
        // Basic EU VAT number format validation
        $patterns = [
            'DE' => '/^DE[0-9]{9}$/',     // Germany
            'AT' => '/^ATU[0-9]{8}$/',    // Austria
            'FR' => '/^FR[0-9A-Z]{2}[0-9]{9}$/', // France
            'NL' => '/^NL[0-9]{9}B[0-9]{2}$/',   // Netherlands
            'BE' => '/^BE[0-9]{10}$/',    // Belgium
        ];

        foreach ($patterns as $country => $pattern) {
            if (preg_match($pattern, $vatNumber)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Format payment method for frontend display
     *
     * @param PaymentMethod $paymentMethod
     * @return array
     */
    private function formatPaymentMethod(PaymentMethod $paymentMethod): array
    {
        $formatted = [
            'id' => $paymentMethod->id,
            'type' => $paymentMethod->type,
            'created' => $paymentMethod->created,
        ];

        switch ($paymentMethod->type) {
            case 'card':
                $formatted['card'] = [
                    'brand' => $paymentMethod->card->brand,
                    'last4' => $paymentMethod->card->last4,
                    'exp_month' => $paymentMethod->card->exp_month,
                    'exp_year' => $paymentMethod->card->exp_year,
                    'country' => $paymentMethod->card->country,
                ];
                $formatted['display_name'] = ucfirst($paymentMethod->card->brand) . ' •••• ' . $paymentMethod->card->last4;
                break;

            case 'sepa_debit':
                $formatted['sepa_debit'] = [
                    'bank_code' => $paymentMethod->sepa_debit->bank_code,
                    'country' => $paymentMethod->sepa_debit->country,
                    'last4' => $paymentMethod->sepa_debit->last4,
                ];
                $formatted['display_name'] = 'SEPA •••• ' . $paymentMethod->sepa_debit->last4;
                break;

            case 'sofort':
                $formatted['sofort'] = [
                    'country' => $paymentMethod->sofort->country,
                ];
                $formatted['display_name'] = 'SOFORT';
                break;

            case 'giropay':
                $formatted['display_name'] = 'Giropay';
                break;

            default:
                $formatted['display_name'] = ucfirst($paymentMethod->type);
        }

        return $formatted;
    }

    /**
     * Get localized payment method names for German users
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
}