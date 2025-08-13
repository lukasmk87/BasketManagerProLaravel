<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\Stripe\CashierTenantManager;
use App\Services\Stripe\WebhookEventProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

/**
 * Multi-tenant Stripe webhook controller
 * Handles webhook events for both User and Tenant billing
 */
class StripeWebhookController extends CashierWebhookController
{
    private CashierTenantManager $cashierManager;
    private WebhookEventProcessor $eventProcessor;

    public function __construct(
        CashierTenantManager $cashierManager,
        WebhookEventProcessor $eventProcessor
    ) {
        $this->cashierManager = $cashierManager;
        $this->eventProcessor = $eventProcessor;
    }

    /**
     * Handle a Stripe webhook call with multi-tenant support
     *
     * @param Request $request
     * @return Response
     */
    public function handleWebhook(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            // Verify webhook signature
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                config('services.stripe.webhook.secret')
            );

            Log::info('Stripe webhook received', [
                'event_type' => $event->type,
                'event_id' => $event->id,
                'livemode' => $event->livemode,
            ]);

            // Process the event through the event processor
            $processed = $this->eventProcessor->processEvent($event);

            return response($processed ? 'Webhook handled' : 'Webhook processing failed', 
                          $processed ? 200 : 500);

        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
                'signature' => $sigHeader,
            ]);

            return response('Invalid signature', 400);
        } catch (\Exception $e) {
            Log::error('Stripe webhook processing failed', [
                'error' => $e->getMessage(),
                'event_type' => $event->type ?? 'unknown',
                'event_id' => $event->id ?? 'unknown',
            ]);

            return response('Webhook processing failed', 500);
        }
    }

    /**
     * Handle Stripe event with tenant context resolution
     *
     * @param \Stripe\Event $event
     * @return void
     */
    protected function handleStripeEvent(\Stripe\Event $event): void
    {
        // Resolve tenant context from event metadata
        $tenant = $this->resolveTenantFromEvent($event);
        
        if ($tenant) {
            // Configure Stripe for this tenant
            $this->cashierManager->configureStripeForTenant($tenant);
            
            Log::info('Processing webhook for tenant', [
                'tenant_id' => $tenant->id,
                'event_type' => $event->type,
            ]);
        }

        // Process the event based on type
        switch ($event->type) {
            // Subscription events
            case 'customer.subscription.created':
                $this->handleSubscriptionCreated($event->data->object, $tenant);
                break;
            
            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($event->data->object, $tenant);
                break;
            
            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event->data->object, $tenant);
                break;

            // Invoice events
            case 'invoice.payment_succeeded':
                $this->handleInvoicePaymentSucceeded($event->data->object, $tenant);
                break;
            
            case 'invoice.payment_failed':
                $this->handleInvoicePaymentFailed($event->data->object, $tenant);
                break;

            // Payment method events
            case 'payment_method.attached':
                $this->handlePaymentMethodAttached($event->data->object, $tenant);
                break;

            // Customer events
            case 'customer.created':
                $this->handleCustomerCreatedForTenant($event->data->object, $tenant);
                break;
            
            case 'customer.updated':
                $this->handleCustomerUpdatedForTenant($event->data->object, $tenant);
                break;

            // Checkout events
            case 'checkout.session.completed':
                $this->handleCheckoutSessionCompleted($event->data->object, $tenant);
                break;

            // Setup intent events
            case 'setup_intent.succeeded':
                $this->handleSetupIntentSucceeded($event->data->object, $tenant);
                break;

            default:
                Log::info('Unhandled Stripe webhook event', [
                    'event_type' => $event->type,
                    'tenant_id' => $tenant?->id,
                ]);
        }
    }

    /**
     * Resolve tenant from Stripe event metadata
     *
     * @param \Stripe\Event $event
     * @return Tenant|null
     */
    protected function resolveTenantFromEvent(\Stripe\Event $event): ?Tenant
    {
        $object = $event->data->object;
        
        // Try to get tenant_id from metadata
        $tenantId = $object->metadata->tenant_id ?? null;
        
        if ($tenantId) {
            return Tenant::find($tenantId);
        }

        // Try to resolve from customer
        if (isset($object->customer)) {
            $customerId = is_string($object->customer) ? $object->customer : $object->customer->id;
            
            // Check tenants first
            $tenant = Tenant::where('stripe_id', $customerId)->first();
            if ($tenant) {
                return $tenant;
            }
            
            // Check users and get their tenant
            $user = User::where('stripe_id', $customerId)->first();
            if ($user && $user->current_team_id) {
                return Tenant::find($user->current_team_id);
            }
        }

        return null;
    }

    /**
     * Handle subscription created event
     *
     * @param \Stripe\Subscription $subscription
     * @param Tenant|null $tenant
     * @return void
     */
    protected function handleSubscriptionCreated(\Stripe\Subscription $subscription, ?Tenant $tenant): void
    {
        Log::info('Subscription created', [
            'subscription_id' => $subscription->id,
            'customer' => $subscription->customer,
            'status' => $subscription->status,
            'tenant_id' => $tenant?->id,
        ]);

        if ($tenant) {
            // Update tenant subscription tier based on price
            $priceId = $subscription->items->data[0]->price->id ?? null;
            $tier = $this->mapPriceToTier($priceId);
            
            if ($tier) {
                $tenant->update([
                    'subscription_tier' => $tier,
                    'subscription_status' => $subscription->status,
                    'trial_ends_at' => $subscription->trial_end ? 
                        \Carbon\Carbon::createFromTimestamp($subscription->trial_end) : null,
                ]);

                Log::info('Tenant subscription tier updated', [
                    'tenant_id' => $tenant->id,
                    'tier' => $tier,
                    'status' => $subscription->status,
                ]);
            }
        }
    }

    /**
     * Handle subscription updated event
     *
     * @param \Stripe\Subscription $subscription
     * @param Tenant|null $tenant
     * @return void
     */
    protected function handleSubscriptionUpdated(\Stripe\Subscription $subscription, ?Tenant $tenant): void
    {
        Log::info('Subscription updated', [
            'subscription_id' => $subscription->id,
            'status' => $subscription->status,
            'tenant_id' => $tenant?->id,
        ]);

        if ($tenant) {
            // Update subscription status
            $tenant->update([
                'subscription_status' => $subscription->status,
            ]);

            // Handle status changes
            if ($subscription->status === 'canceled') {
                $this->handleSubscriptionCancellation($tenant);
            } elseif ($subscription->status === 'active') {
                $this->handleSubscriptionReactivation($tenant);
            }
        }
    }

    /**
     * Handle subscription deleted event
     *
     * @param \Stripe\Subscription $subscription
     * @param Tenant|null $tenant
     * @return void
     */
    protected function handleSubscriptionDeleted(\Stripe\Subscription $subscription, ?Tenant $tenant): void
    {
        Log::info('Subscription deleted', [
            'subscription_id' => $subscription->id,
            'tenant_id' => $tenant?->id,
        ]);

        if ($tenant) {
            $tenant->update([
                'subscription_tier' => 'trial',
                'subscription_status' => 'canceled',
                'trial_ends_at' => now()->addDays(7), // Grace period
            ]);

            $this->handleSubscriptionCancellation($tenant);
        }
    }

    /**
     * Handle invoice payment succeeded event
     *
     * @param \Stripe\Invoice $invoice
     * @param Tenant|null $tenant
     * @return void
     */
    protected function handleInvoicePaymentSucceeded(\Stripe\Invoice $invoice, ?Tenant $tenant): void
    {
        Log::info('Invoice payment succeeded', [
            'invoice_id' => $invoice->id,
            'amount' => $invoice->amount_paid,
            'tenant_id' => $tenant?->id,
        ]);

        if ($tenant) {
            // Reset any usage limits for new billing period
            $this->resetTenantUsage($tenant);
            
            // Update payment status
            $tenant->update([
                'last_payment_at' => now(),
                'payment_status' => 'paid',
            ]);
        }
    }

    /**
     * Handle invoice payment failed event
     *
     * @param \Stripe\Invoice $invoice
     * @param Tenant|null $tenant
     * @return void
     */
    protected function handleInvoicePaymentFailed(\Stripe\Invoice $invoice, ?Tenant $tenant): void
    {
        Log::warning('Invoice payment failed', [
            'invoice_id' => $invoice->id,
            'amount' => $invoice->amount_due,
            'tenant_id' => $tenant?->id,
        ]);

        if ($tenant) {
            // Update payment status
            $tenant->update([
                'payment_status' => 'failed',
                'payment_failed_at' => now(),
            ]);

            // Send notification to tenant admin
            $this->notifyPaymentFailure($tenant, $invoice);
        }
    }

    /**
     * Handle payment method attached event
     *
     * @param \Stripe\PaymentMethod $paymentMethod
     * @param Tenant|null $tenant
     * @return void
     */
    protected function handlePaymentMethodAttached(\Stripe\PaymentMethod $paymentMethod, ?Tenant $tenant): void
    {
        Log::info('Payment method attached', [
            'payment_method_id' => $paymentMethod->id,
            'type' => $paymentMethod->type,
            'customer' => $paymentMethod->customer,
            'tenant_id' => $tenant?->id,
        ]);
    }

    /**
     * Handle customer created event for tenant context
     *
     * @param \Stripe\Customer $customer
     * @param Tenant|null $tenant
     * @return void
     */
    protected function handleCustomerCreatedForTenant(\Stripe\Customer $customer, ?Tenant $tenant): void
    {
        Log::info('Customer created', [
            'customer_id' => $customer->id,
            'email' => $customer->email,
            'tenant_id' => $tenant?->id,
        ]);
    }

    /**
     * Handle customer updated event for tenant context
     *
     * @param \Stripe\Customer $customer
     * @param Tenant|null $tenant
     * @return void
     */
    protected function handleCustomerUpdatedForTenant(\Stripe\Customer $customer, ?Tenant $tenant): void
    {
        Log::info('Customer updated', [
            'customer_id' => $customer->id,
            'email' => $customer->email,
            'tenant_id' => $tenant?->id,
        ]);
    }

    /**
     * Handle checkout session completed event
     *
     * @param \Stripe\Checkout\Session $session
     * @param Tenant|null $tenant
     * @return void
     */
    protected function handleCheckoutSessionCompleted(\Stripe\Checkout\Session $session, ?Tenant $tenant): void
    {
        Log::info('Checkout session completed', [
            'session_id' => $session->id,
            'mode' => $session->mode,
            'payment_status' => $session->payment_status,
            'tenant_id' => $tenant?->id,
        ]);

        if ($tenant && $session->mode === 'subscription' && $session->subscription) {
            // Mark trial as completed if this was a trial checkout
            if ($tenant->subscription_tier === 'trial') {
                $tenant->update([
                    'trial_ends_at' => now(),
                ]);
            }
        }
    }

    /**
     * Handle setup intent succeeded event
     *
     * @param \Stripe\SetupIntent $setupIntent
     * @param Tenant|null $tenant
     * @return void
     */
    protected function handleSetupIntentSucceeded(\Stripe\SetupIntent $setupIntent, ?Tenant $tenant): void
    {
        Log::info('Setup intent succeeded', [
            'setup_intent_id' => $setupIntent->id,
            'payment_method' => $setupIntent->payment_method,
            'customer' => $setupIntent->customer,
            'tenant_id' => $tenant?->id,
        ]);
    }

    /**
     * Map Stripe price ID to subscription tier
     *
     * @param string|null $priceId
     * @return string|null
     */
    protected function mapPriceToTier(?string $priceId): ?string
    {
        $priceMapping = [
            config('services.stripe.prices.basic_monthly') => 'basic',
            config('services.stripe.prices.basic_yearly') => 'basic',
            config('services.stripe.prices.professional_monthly') => 'professional',
            config('services.stripe.prices.professional_yearly') => 'professional',
            config('services.stripe.prices.enterprise_monthly') => 'enterprise',
            config('services.stripe.prices.enterprise_yearly') => 'enterprise',
        ];

        return $priceMapping[$priceId] ?? null;
    }

    /**
     * Handle subscription cancellation
     *
     * @param Tenant $tenant
     * @return void
     */
    protected function handleSubscriptionCancellation(Tenant $tenant): void
    {
        Log::info('Processing subscription cancellation', [
            'tenant_id' => $tenant->id,
        ]);

        // Reset feature usage to trial limits
        $tenant->tenantUsage()->delete();
        
        // Notify tenant users about cancellation
        $this->notifySubscriptionCancellation($tenant);
    }

    /**
     * Handle subscription reactivation
     *
     * @param Tenant $tenant
     * @return void
     */
    protected function handleSubscriptionReactivation(Tenant $tenant): void
    {
        Log::info('Processing subscription reactivation', [
            'tenant_id' => $tenant->id,
        ]);

        // Reset payment failure status
        $tenant->update([
            'payment_status' => 'paid',
            'payment_failed_at' => null,
        ]);

        // Notify tenant users about reactivation
        $this->notifySubscriptionReactivation($tenant);
    }

    /**
     * Reset tenant usage for new billing period
     *
     * @param Tenant $tenant
     * @return void
     */
    protected function resetTenantUsage(Tenant $tenant): void
    {
        $tenant->tenantUsage()->delete();
        
        Log::info('Tenant usage reset for new billing period', [
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Notify tenant of payment failure
     *
     * @param Tenant $tenant
     * @param \Stripe\Invoice $invoice
     * @return void
     */
    protected function notifyPaymentFailure(Tenant $tenant, \Stripe\Invoice $invoice): void
    {
        // This would typically send an email notification
        // For now, just log the event
        Log::warning('Payment failure notification needed', [
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
            'amount' => $invoice->amount_due,
        ]);
    }

    /**
     * Notify tenant of subscription cancellation
     *
     * @param Tenant $tenant
     * @return void
     */
    protected function notifySubscriptionCancellation(Tenant $tenant): void
    {
        Log::info('Subscription cancellation notification needed', [
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Notify tenant of subscription reactivation
     *
     * @param Tenant $tenant
     * @return void
     */
    protected function notifySubscriptionReactivation(Tenant $tenant): void
    {
        Log::info('Subscription reactivation notification needed', [
            'tenant_id' => $tenant->id,
        ]);
    }
}