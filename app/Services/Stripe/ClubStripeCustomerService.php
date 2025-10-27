<?php

namespace App\Services\Stripe;

use App\Models\Club;
use Stripe\Customer;
use Stripe\Exception\StripeException;
use Illuminate\Support\Facades\Log;

class ClubStripeCustomerService
{
    public function __construct(
        private StripeClientManager $clientManager
    ) {}

    /**
     * Create or retrieve Stripe Customer for Club.
     */
    public function getOrCreateCustomer(Club $club): Customer
    {
        // If club already has Stripe Customer, retrieve it
        if ($club->stripe_customer_id) {
            try {
                $client = $this->clientManager->getCurrentTenantClient();
                return $client->customers->retrieve($club->stripe_customer_id);
            } catch (StripeException $e) {
                Log::warning('Club Stripe Customer not found, creating new', [
                    'club_id' => $club->id,
                    'old_stripe_customer_id' => $club->stripe_customer_id,
                    'tenant_id' => $club->tenant_id,
                    'error' => $e->getMessage(),
                ]);
                // Customer not found, create new one
            }
        }

        return $this->createCustomer($club);
    }

    /**
     * Create new Stripe Customer for Club.
     */
    public function createCustomer(Club $club): Customer
    {
        $client = $this->clientManager->getCurrentTenantClient();

        $customerData = [
            'name' => $club->name,
            'email' => $club->billing_email ?? $club->email,
            'description' => "Club: {$club->name} (ID: {$club->id})",
            'metadata' => [
                'club_id' => $club->id,
                'club_uuid' => $club->uuid,
                'tenant_id' => $club->tenant_id,
                'club_name' => $club->name,
                'created_by' => 'basketmanager_pro',
            ],
        ];

        // Add phone if available
        if ($club->phone) {
            $customerData['phone'] = $club->phone;
        }

        // Add address if available
        if ($club->address_street && $club->address_city) {
            $customerData['address'] = [
                'line1' => $club->address_street,
                'city' => $club->address_city,
                'postal_code' => $club->address_zip,
                'state' => $club->address_state,
                'country' => $club->address_country ?? 'DE',
            ];
        }

        // Add preferred locales
        $customerData['preferred_locales'] = [$club->tenant?->locale ?? 'de'];

        try {
            $customer = $client->customers->create($customerData);

            // Save Stripe Customer ID to Club
            $club->update([
                'stripe_customer_id' => $customer->id,
            ]);

            Log::info('Club Stripe Customer created', [
                'club_id' => $club->id,
                'stripe_customer_id' => $customer->id,
                'tenant_id' => $club->tenant_id,
            ]);

            return $customer;
        } catch (StripeException $e) {
            Log::error('Failed to create Club Stripe Customer', [
                'club_id' => $club->id,
                'tenant_id' => $club->tenant_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update Stripe Customer information.
     */
    public function updateCustomer(Club $club, array $data): Customer
    {
        if (!$club->stripe_customer_id) {
            throw new \Exception('Club has no Stripe Customer ID');
        }

        $client = $this->clientManager->getCurrentTenantClient();

        try {
            $customer = $client->customers->update(
                $club->stripe_customer_id,
                $data
            );

            Log::info('Club Stripe Customer updated', [
                'club_id' => $club->id,
                'stripe_customer_id' => $customer->id,
                'tenant_id' => $club->tenant_id,
            ]);

            return $customer;
        } catch (StripeException $e) {
            Log::error('Failed to update Club Stripe Customer', [
                'club_id' => $club->id,
                'stripe_customer_id' => $club->stripe_customer_id,
                'tenant_id' => $club->tenant_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Delete Stripe Customer (when club is deleted).
     */
    public function deleteCustomer(Club $club): void
    {
        if (!$club->stripe_customer_id) {
            return;
        }

        $client = $this->clientManager->getCurrentTenantClient();

        try {
            $client->customers->delete($club->stripe_customer_id);

            Log::info('Club Stripe Customer deleted', [
                'club_id' => $club->id,
                'stripe_customer_id' => $club->stripe_customer_id,
                'tenant_id' => $club->tenant_id,
            ]);
        } catch (StripeException $e) {
            Log::error('Failed to delete Club Stripe Customer', [
                'club_id' => $club->id,
                'stripe_customer_id' => $club->stripe_customer_id,
                'tenant_id' => $club->tenant_id,
                'error' => $e->getMessage(),
            ]);

            // Don't throw - deletion should continue even if Stripe fails
        }
    }
}
