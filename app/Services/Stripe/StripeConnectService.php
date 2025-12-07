<?php

namespace App\Services\Stripe;

use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Stripe\Account;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripeConnectService
{
    protected ?StripeClient $stripe = null;

    public function __construct(
        protected StripeClientManager $clientManager
    ) {
        // Lazy loading - Client wird erst bei Bedarf initialisiert
    }

    /**
     * Get Stripe client (lazy loaded).
     */
    protected function getStripeClient(): StripeClient
    {
        if ($this->stripe === null) {
            $this->stripe = $this->clientManager->getDefaultClient();
        }

        return $this->stripe;
    }

    /**
     * Create a new Express account for a tenant.
     */
    public function createExpressAccount(Tenant $tenant): Account
    {
        try {
            $account = $this->getStripeClient()->accounts->create([
                'type' => 'express',
                'country' => $tenant->country_code ?? 'DE',
                'email' => $tenant->billing_email,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
                'business_type' => 'company',
                'business_profile' => [
                    'name' => $tenant->name,
                    'url' => $tenant->getUrl(),
                ],
                'metadata' => [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                ],
                'settings' => [
                    'payouts' => [
                        'schedule' => [
                            'interval' => 'daily',
                            'delay_days' => 7,
                        ],
                    ],
                ],
            ]);

            // Update tenant with account ID
            $tenant->update([
                'stripe_connect_account_id' => $account->id,
                'stripe_connect_status' => 'pending',
            ]);

            Log::info('Stripe Connect Express account created', [
                'tenant_id' => $tenant->id,
                'account_id' => $account->id,
            ]);

            return $account;
        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe Connect account', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create an account link for onboarding.
     */
    public function createAccountLink(Tenant $tenant, string $returnUrl, string $refreshUrl): string
    {
        // Create account if not exists
        if (! $tenant->stripe_connect_account_id) {
            $this->createExpressAccount($tenant);
            $tenant->refresh();
        }

        try {
            $accountLink = $this->getStripeClient()->accountLinks->create([
                'account' => $tenant->stripe_connect_account_id,
                'refresh_url' => $refreshUrl,
                'return_url' => $returnUrl,
                'type' => 'account_onboarding',
                'collect' => 'eventually_due',
            ]);

            return $accountLink->url;
        } catch (ApiErrorException $e) {
            Log::error('Failed to create account link', [
                'tenant_id' => $tenant->id,
                'account_id' => $tenant->stripe_connect_account_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get the Stripe account for a tenant.
     */
    public function getAccount(Tenant $tenant): ?Account
    {
        if (! $tenant->stripe_connect_account_id) {
            return null;
        }

        try {
            return $this->getStripeClient()->accounts->retrieve($tenant->stripe_connect_account_id);
        } catch (ApiErrorException $e) {
            Log::error('Failed to retrieve Stripe Connect account', [
                'tenant_id' => $tenant->id,
                'account_id' => $tenant->stripe_connect_account_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Refresh account status from Stripe.
     */
    public function refreshAccountStatus(Tenant $tenant): void
    {
        $account = $this->getAccount($tenant);

        if (! $account) {
            return;
        }

        $status = $this->determineAccountStatus($account);

        $tenant->update([
            'stripe_connect_status' => $status,
            'stripe_connect_charges_enabled' => $account->charges_enabled,
            'stripe_connect_payouts_enabled' => $account->payouts_enabled,
            'stripe_connect_details_submitted' => $account->details_submitted,
            'stripe_connect_last_webhook_at' => now(),
        ]);

        // Mark as connected if first time becoming active
        if ($status === 'active' && ! $tenant->stripe_connect_connected_at) {
            $tenant->update(['stripe_connect_connected_at' => now()]);
        }

        Log::info('Stripe Connect account status refreshed', [
            'tenant_id' => $tenant->id,
            'account_id' => $account->id,
            'status' => $status,
            'charges_enabled' => $account->charges_enabled,
            'payouts_enabled' => $account->payouts_enabled,
        ]);
    }

    /**
     * Determine account status from Stripe account object.
     */
    protected function determineAccountStatus(Account $account): string
    {
        if ($account->charges_enabled && $account->payouts_enabled) {
            return 'active';
        }

        if ($account->requirements->disabled_reason ?? null) {
            return 'restricted';
        }

        if ($account->details_submitted) {
            return 'pending';
        }

        return 'pending';
    }

    /**
     * Get Express Dashboard login link.
     */
    public function getExpressDashboardLink(Tenant $tenant): string
    {
        if (! $tenant->stripe_connect_account_id) {
            throw new \RuntimeException('Tenant has no connected Stripe account');
        }

        try {
            $loginLink = $this->getStripeClient()->accounts->createLoginLink(
                $tenant->stripe_connect_account_id
            );

            return $loginLink->url;
        } catch (ApiErrorException $e) {
            Log::error('Failed to create Express dashboard link', [
                'tenant_id' => $tenant->id,
                'account_id' => $tenant->stripe_connect_account_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Disconnect (revoke access to) a Connect account.
     */
    public function disconnectAccount(Tenant $tenant): void
    {
        if (! $tenant->stripe_connect_account_id) {
            return;
        }

        try {
            // Revoke platform's access (deauthorize)
            $this->getStripeClient()->oauth->deauthorize([
                'client_id' => config('stripe.connect.client_id', config('stripe.client_id')),
                'stripe_user_id' => $tenant->stripe_connect_account_id,
            ]);
        } catch (ApiErrorException $e) {
            // Log but continue - account might already be disconnected
            Log::warning('Failed to deauthorize Stripe Connect account', [
                'tenant_id' => $tenant->id,
                'account_id' => $tenant->stripe_connect_account_id,
                'error' => $e->getMessage(),
            ]);
        }

        // Update tenant record
        $tenant->disconnectStripeConnect();

        Log::info('Stripe Connect account disconnected', [
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Check if an account can receive payments.
     */
    public function canReceivePayments(Tenant $tenant): bool
    {
        if (! $tenant->stripe_connect_account_id) {
            return false;
        }

        // Use cached status first
        if ($tenant->stripe_connect_charges_enabled) {
            return true;
        }

        // Refresh from Stripe if needed
        $account = $this->getAccount($tenant);

        return $account?->charges_enabled ?? false;
    }

    /**
     * Get account balance.
     */
    public function getAccountBalance(Tenant $tenant): array
    {
        if (! $tenant->stripe_connect_account_id) {
            return ['available' => 0, 'pending' => 0, 'currency' => 'eur'];
        }

        try {
            $balance = $this->getStripeClient()->balance->retrieve(
                [],
                ['stripe_account' => $tenant->stripe_connect_account_id]
            );

            $available = 0;
            $pending = 0;
            $currency = 'eur';

            foreach ($balance->available as $fund) {
                if ($fund->currency === 'eur') {
                    $available = $fund->amount;
                    $currency = $fund->currency;
                }
            }

            foreach ($balance->pending as $fund) {
                if ($fund->currency === 'eur') {
                    $pending = $fund->amount;
                }
            }

            return [
                'available' => $available,
                'pending' => $pending,
                'currency' => $currency,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to retrieve account balance', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return ['available' => 0, 'pending' => 0, 'currency' => 'eur'];
        }
    }

    /**
     * Get recent payouts for an account.
     */
    public function getRecentPayouts(Tenant $tenant, int $limit = 10): array
    {
        if (! $tenant->stripe_connect_account_id) {
            return [];
        }

        try {
            $payouts = $this->getStripeClient()->payouts->all(
                ['limit' => $limit],
                ['stripe_account' => $tenant->stripe_connect_account_id]
            );

            return $payouts->data;
        } catch (ApiErrorException $e) {
            Log::error('Failed to retrieve payouts', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get all connected accounts (for admin).
     */
    public function getAllConnectedAccounts(int $limit = 100): array
    {
        try {
            $accounts = $this->getStripeClient()->accounts->all([
                'limit' => $limit,
            ]);

            return $accounts->data;
        } catch (ApiErrorException $e) {
            Log::error('Failed to retrieve connected accounts', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
