<?php

namespace App\Services\Stripe;

use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use App\Models\StripeConnectTransfer;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

class StripeConnectCheckoutService
{
    protected StripeClient $stripe;

    public function __construct(
        protected StripeClientManager $clientManager,
        protected StripeConnectService $connectService
    ) {
        $this->stripe = $clientManager->getDefaultClient();
    }

    /**
     * Create a checkout session that pays the connected account with application fee.
     */
    public function createConnectedCheckoutSession(
        Club $club,
        ClubSubscriptionPlan $plan,
        string $successUrl,
        string $cancelUrl,
        array $options = []
    ): Session {
        $tenant = $club->tenant;

        if (! $tenant->hasActiveStripeConnect()) {
            throw new \RuntimeException('Tenant does not have an active Stripe Connect account');
        }

        if (! $this->connectService->canReceivePayments($tenant)) {
            throw new \RuntimeException('Tenant\'s Stripe Connect account cannot receive payments');
        }

        $priceInCents = $this->getPriceInCents($plan, $options['billing_interval'] ?? 'monthly');
        $applicationFee = $tenant->calculateApplicationFee($priceInCents);

        try {
            $sessionData = [
                'mode' => 'subscription',
                'customer_email' => $club->billing_email ?? $club->email,
                'client_reference_id' => $club->id,
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'line_items' => [
                    [
                        'price' => $this->getStripePriceId($plan, $options['billing_interval'] ?? 'monthly'),
                        'quantity' => 1,
                    ],
                ],
                'subscription_data' => [
                    'application_fee_percent' => $tenant->getApplicationFeePercent(),
                    'metadata' => [
                        'club_id' => $club->id,
                        'tenant_id' => $tenant->id,
                        'plan_id' => $plan->id,
                    ],
                    'transfer_data' => [
                        'destination' => $tenant->stripe_connect_account_id,
                    ],
                ],
                'metadata' => [
                    'club_id' => $club->id,
                    'tenant_id' => $tenant->id,
                    'plan_id' => $plan->id,
                    'application_fee' => $applicationFee,
                ],
                'payment_method_types' => $this->getPaymentMethodTypes(),
                'locale' => 'de',
                'allow_promotion_codes' => true,
            ];

            // Add customer if exists
            if ($club->stripe_customer_id) {
                $sessionData['customer'] = $club->stripe_customer_id;
                unset($sessionData['customer_email']);
            }

            // Add trial period if applicable
            if ($plan->trial_period_days > 0 && ! $club->hasHadTrial()) {
                $sessionData['subscription_data']['trial_period_days'] = $plan->trial_period_days;
            }

            // Tax handling
            if (config('stripe.tax.automatic_tax', false)) {
                $sessionData['automatic_tax'] = ['enabled' => true];
            }

            $session = $this->stripe->checkout->sessions->create($sessionData);

            Log::info('Connected checkout session created', [
                'session_id' => $session->id,
                'club_id' => $club->id,
                'tenant_id' => $tenant->id,
                'connected_account' => $tenant->stripe_connect_account_id,
                'application_fee_percent' => $tenant->getApplicationFeePercent(),
            ]);

            return $session;
        } catch (ApiErrorException $e) {
            Log::error('Failed to create connected checkout session', [
                'club_id' => $club->id,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create a one-time payment checkout session with destination charge.
     */
    public function createOneTimePaymentSession(
        Club $club,
        int $amountInCents,
        string $description,
        string $successUrl,
        string $cancelUrl,
        array $metadata = []
    ): Session {
        $tenant = $club->tenant;

        if (! $tenant->hasActiveStripeConnect()) {
            throw new \RuntimeException('Tenant does not have an active Stripe Connect account');
        }

        $applicationFee = $tenant->calculateApplicationFee($amountInCents);

        try {
            $sessionData = [
                'mode' => 'payment',
                'customer_email' => $club->billing_email ?? $club->email,
                'client_reference_id' => $club->id,
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'eur',
                            'product_data' => [
                                'name' => $description,
                            ],
                            'unit_amount' => $amountInCents,
                        ],
                        'quantity' => 1,
                    ],
                ],
                'payment_intent_data' => [
                    'application_fee_amount' => $applicationFee,
                    'transfer_data' => [
                        'destination' => $tenant->stripe_connect_account_id,
                    ],
                    'metadata' => array_merge($metadata, [
                        'club_id' => $club->id,
                        'tenant_id' => $tenant->id,
                    ]),
                ],
                'metadata' => array_merge($metadata, [
                    'club_id' => $club->id,
                    'tenant_id' => $tenant->id,
                    'application_fee' => $applicationFee,
                ]),
                'payment_method_types' => $this->getPaymentMethodTypes(),
                'locale' => 'de',
            ];

            // Add customer if exists
            if ($club->stripe_customer_id) {
                $sessionData['customer'] = $club->stripe_customer_id;
                unset($sessionData['customer_email']);
            }

            $session = $this->stripe->checkout->sessions->create($sessionData);

            Log::info('Connected one-time payment session created', [
                'session_id' => $session->id,
                'club_id' => $club->id,
                'tenant_id' => $tenant->id,
                'amount' => $amountInCents,
                'application_fee' => $applicationFee,
            ]);

            return $session;
        } catch (ApiErrorException $e) {
            Log::error('Failed to create one-time payment session', [
                'club_id' => $club->id,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Record a transfer for tracking.
     */
    public function recordTransfer(
        PaymentIntent $paymentIntent,
        Tenant $tenant,
        Club $club,
        int $applicationFee
    ): StripeConnectTransfer {
        $grossAmount = $paymentIntent->amount;
        $netAmount = $grossAmount - $applicationFee;

        return StripeConnectTransfer::create([
            'tenant_id' => $tenant->id,
            'club_id' => $club->id,
            'stripe_payment_intent_id' => $paymentIntent->id,
            'stripe_charge_id' => $paymentIntent->latest_charge,
            'stripe_transfer_id' => $paymentIntent->transfer_data?->destination ?? null,
            'gross_amount' => $grossAmount,
            'application_fee_amount' => $applicationFee,
            'net_amount' => $netAmount,
            'currency' => $paymentIntent->currency,
            'status' => $this->mapPaymentIntentStatus($paymentIntent->status),
            'description' => $paymentIntent->description,
            'metadata' => $paymentIntent->metadata->toArray(),
        ]);
    }

    /**
     * Update transfer status.
     */
    public function updateTransferStatus(string $paymentIntentId, string $status): void
    {
        $transfer = StripeConnectTransfer::where('stripe_payment_intent_id', $paymentIntentId)->first();

        if ($transfer) {
            $transfer->update(['status' => $status]);
        }
    }

    /**
     * Get the Stripe price ID for a plan.
     */
    protected function getStripePriceId(ClubSubscriptionPlan $plan, string $interval): string
    {
        return $interval === 'yearly'
            ? $plan->stripe_price_id_yearly
            : $plan->stripe_price_id_monthly;
    }

    /**
     * Get the price in cents.
     */
    protected function getPriceInCents(ClubSubscriptionPlan $plan, string $interval): int
    {
        $price = $interval === 'yearly'
            ? ($plan->price * 12 * 0.85) // 15% yearly discount
            : $plan->price;

        return (int) round($price * 100);
    }

    /**
     * Get available payment method types.
     */
    protected function getPaymentMethodTypes(): array
    {
        return [
            'card',
            'sepa_debit',
            'sofort',
            'giropay',
            'eps',
            'bancontact',
            'ideal',
        ];
    }

    /**
     * Map Stripe payment intent status to our status.
     */
    protected function mapPaymentIntentStatus(string $status): string
    {
        return match ($status) {
            'succeeded' => 'succeeded',
            'canceled' => 'failed',
            'requires_payment_method', 'requires_confirmation', 'requires_action', 'processing' => 'pending',
            default => 'pending',
        };
    }

    /**
     * Calculate preview of fees for a given amount.
     */
    public function previewFees(Tenant $tenant, int $amountInCents): array
    {
        $applicationFee = $tenant->calculateApplicationFee($amountInCents);
        $netAmount = $amountInCents - $applicationFee;

        return [
            'gross_amount' => $amountInCents,
            'application_fee' => $applicationFee,
            'net_amount' => $netAmount,
            'currency' => 'eur',
            'fee_percent' => $tenant->getApplicationFeePercent(),
            'fee_fixed' => $tenant->getApplicationFeeFixed(),
        ];
    }
}
