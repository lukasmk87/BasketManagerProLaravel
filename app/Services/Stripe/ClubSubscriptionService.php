<?php

namespace App\Services\Stripe;

use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\StripeException;
use Stripe\Subscription;

class ClubSubscriptionService
{
    public function __construct(
        private StripeClientManager $clientManager,
        private ClubStripeCustomerService $customerService
    ) {}

    /**
     * Assign plan to club and update Stripe subscription.
     *
     * @throws \Exception
     */
    public function assignPlanToClub(Club $club, ClubSubscriptionPlan $plan): void
    {
        // Validate tenant match
        if ($plan->tenant_id !== $club->tenant_id) {
            throw new \Exception("Plan does not belong to club's tenant");
        }

        $club->update(['club_subscription_plan_id' => $plan->id]);

        Log::info('Plan assigned to club', [
            'club_id' => $club->id,
            'plan_id' => $plan->id,
            'tenant_id' => $club->tenant_id,
        ]);
    }

    /**
     * Cancel club subscription.
     *
     * @throws \Exception
     */
    public function cancelSubscription(Club $club, bool $immediately = false): void
    {
        if (! $club->stripe_subscription_id) {
            throw new \Exception('Club has no active subscription');
        }

        $client = $this->clientManager->getCurrentTenantClient();

        try {
            if ($immediately) {
                // Cancel immediately
                $subscription = $client->subscriptions->cancel($club->stripe_subscription_id);

                $club->update([
                    'subscription_status' => 'canceled',
                    'subscription_ends_at' => now(),
                    'club_subscription_plan_id' => null,
                ]);
            } else {
                // Cancel at period end
                $subscription = $client->subscriptions->update($club->stripe_subscription_id, [
                    'cancel_at_period_end' => true,
                ]);

                $club->update([
                    'subscription_status' => 'active', // Still active until period end
                    'subscription_ends_at' => $subscription->current_period_end
                        ? \Carbon\Carbon::createFromTimestamp($subscription->current_period_end)
                        : null,
                ]);
            }

            Log::info('Club subscription canceled', [
                'club_id' => $club->id,
                'subscription_id' => $club->stripe_subscription_id,
                'immediately' => $immediately,
                'tenant_id' => $club->tenant_id,
            ]);
        } catch (StripeException $e) {
            Log::error('Failed to cancel club subscription', [
                'club_id' => $club->id,
                'subscription_id' => $club->stripe_subscription_id,
                'tenant_id' => $club->tenant_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Resume a canceled subscription.
     *
     * @throws \Exception
     */
    public function resumeSubscription(Club $club): void
    {
        if (! $club->stripe_subscription_id) {
            throw new \Exception('Club has no subscription to resume');
        }

        $client = $this->clientManager->getCurrentTenantClient();

        try {
            $subscription = $client->subscriptions->update($club->stripe_subscription_id, [
                'cancel_at_period_end' => false,
            ]);

            $club->update([
                'subscription_status' => 'active',
                'subscription_ends_at' => null,
            ]);

            Log::info('Club subscription resumed', [
                'club_id' => $club->id,
                'subscription_id' => $club->stripe_subscription_id,
                'tenant_id' => $club->tenant_id,
            ]);
        } catch (StripeException $e) {
            Log::error('Failed to resume club subscription', [
                'club_id' => $club->id,
                'subscription_id' => $club->stripe_subscription_id,
                'tenant_id' => $club->tenant_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Upgrade/Downgrade club to different plan.
     *
     * @throws \Exception
     */
    public function swapPlan(Club $club, ClubSubscriptionPlan $newPlan, array $options = []): void
    {
        if (! $club->stripe_subscription_id) {
            throw new \Exception('Club must have active subscription to swap plans');
        }

        // Validate new plan
        if ($newPlan->tenant_id !== $club->tenant_id) {
            throw new \Exception("New plan does not belong to club's tenant");
        }

        if (! $newPlan->is_stripe_synced) {
            throw new \Exception('New plan is not synced with Stripe');
        }

        $billingInterval = $options['billing_interval'] ?? 'monthly';
        $newPriceId = $billingInterval === 'yearly'
            ? $newPlan->stripe_price_id_yearly
            : $newPlan->stripe_price_id_monthly;

        if (! $newPriceId) {
            throw new \Exception("New plan has no Stripe Price ID for {$billingInterval}");
        }

        $client = $this->clientManager->getCurrentTenantClient();

        try {
            // Get current subscription
            $subscription = $client->subscriptions->retrieve($club->stripe_subscription_id);
            $currentItem = $subscription->items->data[0];

            // Update subscription
            $updatedSubscription = $client->subscriptions->update($club->stripe_subscription_id, [
                'items' => [
                    [
                        'id' => $currentItem->id,
                        'price' => $newPriceId,
                    ],
                ],
                'proration_behavior' => $options['proration_behavior'] ?? 'create_prorations',
            ]);

            // Update club
            $club->update([
                'club_subscription_plan_id' => $newPlan->id,
            ]);

            Log::info('Club plan swapped', [
                'club_id' => $club->id,
                'old_plan_id' => $club->club_subscription_plan_id,
                'new_plan_id' => $newPlan->id,
                'subscription_id' => $club->stripe_subscription_id,
                'proration_behavior' => $options['proration_behavior'] ?? 'create_prorations',
                'tenant_id' => $club->tenant_id,
            ]);
        } catch (StripeException $e) {
            Log::error('Failed to swap club plan', [
                'club_id' => $club->id,
                'new_plan_id' => $newPlan->id,
                'subscription_id' => $club->stripe_subscription_id,
                'tenant_id' => $club->tenant_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync plan with Stripe (create Product & Prices).
     *
     * @return array{product: \Stripe\Product, price_monthly: \Stripe\Price|null, price_yearly: \Stripe\Price|null}
     *
     * @throws StripeException
     */
    public function syncPlanWithStripe(ClubSubscriptionPlan $plan): array
    {
        $client = $this->clientManager->getCurrentTenantClient();

        try {
            // Create or update Product
            if ($plan->stripe_product_id) {
                $product = $client->products->update($plan->stripe_product_id, [
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'metadata' => [
                        'plan_id' => $plan->id,
                        'tenant_id' => $plan->tenant_id,
                        'plan_slug' => $plan->slug,
                    ],
                ]);
            } else {
                $product = $client->products->create([
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'metadata' => [
                        'plan_id' => $plan->id,
                        'tenant_id' => $plan->tenant_id,
                        'plan_slug' => $plan->slug,
                    ],
                ]);

                $plan->update(['stripe_product_id' => $product->id]);
            }

            $priceMonthly = null;
            $priceYearly = null;

            // Create or update Monthly Price (only if price > 0)
            if ($plan->price > 0) {
                if ($plan->stripe_price_id_monthly) {
                    $priceMonthly = $client->prices->retrieve($plan->stripe_price_id_monthly);
                } else {
                    $priceMonthly = $client->prices->create([
                        'product' => $product->id,
                        'unit_amount' => (int) ($plan->price * 100), // Convert to cents
                        'currency' => strtolower($plan->currency),
                        'recurring' => ['interval' => 'month'],
                        'metadata' => [
                            'plan_id' => $plan->id,
                            'billing_interval' => 'monthly',
                        ],
                    ]);

                    $plan->update(['stripe_price_id_monthly' => $priceMonthly->id]);
                }

                // Create Yearly Price (with 10% discount)
                $yearlyAmount = (int) ($plan->price * 12 * 0.9 * 100); // 10% discount
                if ($plan->stripe_price_id_yearly) {
                    $priceYearly = $client->prices->retrieve($plan->stripe_price_id_yearly);
                } else {
                    $priceYearly = $client->prices->create([
                        'product' => $product->id,
                        'unit_amount' => $yearlyAmount,
                        'currency' => strtolower($plan->currency),
                        'recurring' => ['interval' => 'year'],
                        'metadata' => [
                            'plan_id' => $plan->id,
                            'billing_interval' => 'yearly',
                            'discount' => '10%',
                        ],
                    ]);

                    $plan->update(['stripe_price_id_yearly' => $priceYearly->id]);
                }
            }

            // Mark as synced
            $plan->update([
                'is_stripe_synced' => true,
                'last_stripe_sync_at' => now(),
            ]);

            Log::info('Club plan synced with Stripe', [
                'plan_id' => $plan->id,
                'stripe_product_id' => $product->id,
                'has_monthly_price' => $priceMonthly !== null,
                'has_yearly_price' => $priceYearly !== null,
                'tenant_id' => $plan->tenant_id,
            ]);

            return [
                'product' => $product,
                'price_monthly' => $priceMonthly,
                'price_yearly' => $priceYearly,
            ];
        } catch (StripeException $e) {
            Log::error('Failed to sync plan with Stripe', [
                'plan_id' => $plan->id,
                'tenant_id' => $plan->tenant_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
