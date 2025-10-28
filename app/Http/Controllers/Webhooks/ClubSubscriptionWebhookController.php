<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\ClubSubscriptionEvent;
use App\Models\ClubSubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class ClubSubscriptionWebhookController extends Controller
{
    /**
     * Handle incoming Stripe webhooks for club subscriptions.
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('stripe.webhooks.signing_secret_club');

        // Fallback to main webhook secret if club-specific secret not configured
        if (! $webhookSecret) {
            $webhookSecret = config('stripe.webhooks.signing_secret');
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            Log::error('Club Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\Exception $e) {
            Log::error('Club Stripe webhook parsing failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Webhook error'], 400);
        }

        Log::info('Club Stripe webhook received', [
            'type' => $event->type,
            'event_id' => $event->id,
        ]);

        // Handle event
        try {
            match ($event->type) {
                'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object),
                'customer.subscription.created' => $this->handleSubscriptionCreated($event->data->object),
                'customer.subscription.updated' => $this->handleSubscriptionUpdated($event->data->object),
                'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event->data->object),
                'invoice.payment_succeeded' => $this->handlePaymentSucceeded($event->data->object),
                'invoice.payment_failed' => $this->handlePaymentFailed($event->data->object),
                // Phase 2: Additional invoice events
                'invoice.created' => $this->handleInvoiceCreated($event->data->object),
                'invoice.finalized' => $this->handleInvoiceFinalized($event->data->object),
                'invoice.payment_action_required' => $this->handlePaymentActionRequired($event->data->object),
                // Phase 2: Payment method events
                'payment_method.attached' => $this->handlePaymentMethodAttached($event->data->object),
                'payment_method.detached' => $this->handlePaymentMethodDetached($event->data->object),
                default => Log::info('Unhandled club webhook event', ['type' => $event->type]),
            };
        } catch (\Exception $e) {
            Log::error('Club webhook handler failed', [
                'type' => $event->type,
                'event_id' => $event->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Handler failed'], 500);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle checkout.session.completed event.
     */
    protected function handleCheckoutCompleted($session): void
    {
        $clubId = $session->metadata->club_id ?? null;
        $planId = $session->metadata->club_subscription_plan_id ?? null;

        if (! $clubId || ! $planId) {
            Log::warning('Club checkout completed without club_id or plan_id', [
                'session_id' => $session->id,
                'metadata' => $session->metadata,
            ]);

            return;
        }

        $club = Club::find($clubId);
        if (! $club) {
            Log::error('Club not found for checkout session', [
                'club_id' => $clubId,
                'session_id' => $session->id,
            ]);

            return;
        }

        // Update club with subscription info
        $club->update([
            'stripe_customer_id' => $session->customer,
            'stripe_subscription_id' => $session->subscription,
            'subscription_status' => 'active',
            'subscription_started_at' => now(),
            'club_subscription_plan_id' => $planId,
        ]);

        // Track subscription creation event for analytics
        $plan = ClubSubscriptionPlan::find($planId);
        $this->trackSubscriptionEvent($club, ClubSubscriptionEvent::TYPE_SUBSCRIPTION_CREATED, [
            'stripe_subscription_id' => $session->subscription,
            'stripe_event_id' => null, // Checkout session doesn't have event_id
            'new_plan_id' => $planId,
            'mrr_change' => $this->calculateMRRFromPlan($plan),
            'metadata' => [
                'checkout_session_id' => $session->id,
                'customer_id' => $session->customer,
            ],
        ]);

        Log::info('Club checkout completed', [
            'club_id' => $clubId,
            'club_name' => $club->name,
            'plan_id' => $planId,
            'subscription_id' => $session->subscription,
            'customer_id' => $session->customer,
            'tenant_id' => $club->tenant_id,
        ]);

        // TODO: Send confirmation email to club admin
    }

    /**
     * Handle customer.subscription.created event.
     */
    protected function handleSubscriptionCreated($subscription): void
    {
        $clubId = $subscription->metadata->club_id ?? null;
        if (! $clubId) {
            Log::debug('Subscription created without club_id metadata', [
                'subscription_id' => $subscription->id,
            ]);

            return;
        }

        $club = Club::find($clubId);
        if (! $club) {
            Log::warning('Club not found for subscription created event', [
                'club_id' => $clubId,
                'subscription_id' => $subscription->id,
            ]);

            return;
        }

        $updateData = [
            'stripe_subscription_id' => $subscription->id,
            'subscription_status' => $subscription->status,
        ];

        // Update current period timestamps
        if ($subscription->current_period_start) {
            $updateData['subscription_current_period_start'] = \Carbon\Carbon::createFromTimestamp($subscription->current_period_start);
        }

        if ($subscription->current_period_end) {
            $updateData['subscription_current_period_end'] = \Carbon\Carbon::createFromTimestamp($subscription->current_period_end);
        }

        // Update trial end if applicable
        if ($subscription->trial_end) {
            $updateData['subscription_trial_ends_at'] = \Carbon\Carbon::createFromTimestamp($subscription->trial_end);
        }

        $club->update($updateData);

        // Track event - differentiate between trial start and paid subscription
        $eventType = $subscription->status === 'trialing'
            ? ClubSubscriptionEvent::TYPE_TRIAL_STARTED
            : ClubSubscriptionEvent::TYPE_SUBSCRIPTION_CREATED;

        $plan = $club->subscriptionPlan;
        $this->trackSubscriptionEvent($club, $eventType, [
            'stripe_subscription_id' => $subscription->id,
            'new_plan_id' => $club->club_subscription_plan_id,
            'mrr_change' => $eventType === ClubSubscriptionEvent::TYPE_SUBSCRIPTION_CREATED
                ? $this->calculateMRRFromPlan($plan)
                : 0, // Trials don't contribute to MRR yet
            'metadata' => [
                'subscription_status' => $subscription->status,
                'trial_end' => $subscription->trial_end,
            ],
        ]);

        Log::info('Club subscription created', [
            'club_id' => $clubId,
            'club_name' => $club->name,
            'subscription_id' => $subscription->id,
            'status' => $subscription->status,
            'tenant_id' => $club->tenant_id,
        ]);
    }

    /**
     * Handle customer.subscription.updated event.
     */
    protected function handleSubscriptionUpdated($subscription): void
    {
        $club = Club::where('stripe_subscription_id', $subscription->id)->first();
        if (! $club) {
            Log::debug('Club not found for subscription update', [
                'subscription_id' => $subscription->id,
            ]);

            return;
        }

        $updateData = [
            'subscription_status' => $subscription->status,
        ];

        // Update current period timestamps
        if ($subscription->current_period_start) {
            $updateData['subscription_current_period_start'] = \Carbon\Carbon::createFromTimestamp($subscription->current_period_start);
        }

        if ($subscription->current_period_end) {
            $updateData['subscription_current_period_end'] = \Carbon\Carbon::createFromTimestamp($subscription->current_period_end);
        }

        // Check if subscription is scheduled for cancellation
        if ($subscription->cancel_at_period_end) {
            $updateData['subscription_ends_at'] = \Carbon\Carbon::createFromTimestamp($subscription->current_period_end);
        } else {
            $updateData['subscription_ends_at'] = null;
        }

        // Update trial end if changed
        if ($subscription->trial_end) {
            $updateData['subscription_trial_ends_at'] = \Carbon\Carbon::createFromTimestamp($subscription->trial_end);
        }

        $club->update($updateData);

        Log::info('Club subscription updated', [
            'club_id' => $club->id,
            'club_name' => $club->name,
            'subscription_id' => $subscription->id,
            'status' => $subscription->status,
            'cancel_at_period_end' => $subscription->cancel_at_period_end,
            'tenant_id' => $club->tenant_id,
        ]);
    }

    /**
     * Handle customer.subscription.deleted event.
     */
    protected function handleSubscriptionDeleted($subscription): void
    {
        $club = Club::where('stripe_subscription_id', $subscription->id)->first();
        if (! $club) {
            Log::debug('Club not found for subscription deletion', [
                'subscription_id' => $subscription->id,
            ]);

            return;
        }

        // Get plan before updating to calculate MRR loss
        $oldPlan = $club->subscriptionPlan;

        $club->update([
            'subscription_status' => 'canceled',
            'subscription_ends_at' => now(),
            'club_subscription_plan_id' => null, // Remove plan assignment
        ]);

        // Track cancellation event (churn)
        $this->trackSubscriptionEvent($club, ClubSubscriptionEvent::TYPE_SUBSCRIPTION_CANCELED, [
            'stripe_subscription_id' => $subscription->id,
            'old_plan_id' => $oldPlan?->id,
            'mrr_change' => -$this->calculateMRRFromPlan($oldPlan), // Negative for MRR loss
            'cancellation_reason' => ClubSubscriptionEvent::REASON_VOLUNTARY, // Default to voluntary
            'metadata' => [
                'subscription_status' => $subscription->status,
                'cancel_at_period_end' => $subscription->cancel_at_period_end ?? false,
            ],
        ]);

        Log::info('Club subscription deleted', [
            'club_id' => $club->id,
            'club_name' => $club->name,
            'subscription_id' => $subscription->id,
            'tenant_id' => $club->tenant_id,
        ]);

        // TODO: Send cancellation notification to club admin
    }

    /**
     * Handle invoice.payment_succeeded event.
     */
    protected function handlePaymentSucceeded($invoice): void
    {
        $club = Club::where('stripe_customer_id', $invoice->customer)->first();
        if (! $club) {
            Log::debug('Club not found for payment succeeded event', [
                'customer_id' => $invoice->customer,
                'invoice_id' => $invoice->id,
            ]);

            return;
        }

        // Update subscription status to active (in case it was past_due)
        $wasPastDue = $club->subscription_status === 'past_due';
        $club->update([
            'subscription_status' => 'active',
        ]);

        // Track payment success event
        $eventType = $wasPastDue
            ? ClubSubscriptionEvent::TYPE_PAYMENT_RECOVERED
            : ClubSubscriptionEvent::TYPE_PAYMENT_SUCCEEDED;

        $this->trackSubscriptionEvent($club, $eventType, [
            'stripe_subscription_id' => $club->stripe_subscription_id,
            'metadata' => [
                'invoice_id' => $invoice->id,
                'amount_paid' => $invoice->amount_paid / 100,
                'currency' => $invoice->currency,
                'was_past_due' => $wasPastDue,
            ],
        ]);

        Log::info('Club payment succeeded', [
            'club_id' => $club->id,
            'club_name' => $club->name,
            'invoice_id' => $invoice->id,
            'amount_paid' => $invoice->amount_paid / 100,
            'currency' => $invoice->currency,
            'tenant_id' => $club->tenant_id,
        ]);

        // TODO: Send payment confirmation email to club admin
    }

    /**
     * Handle invoice.payment_failed event.
     */
    protected function handlePaymentFailed($invoice): void
    {
        $club = Club::where('stripe_customer_id', $invoice->customer)->first();
        if (! $club) {
            Log::debug('Club not found for payment failed event', [
                'customer_id' => $invoice->customer,
                'invoice_id' => $invoice->id,
            ]);

            return;
        }

        // Mark subscription as past due
        $club->update([
            'subscription_status' => 'past_due',
        ]);

        // Track payment failed event (potential involuntary churn)
        $this->trackSubscriptionEvent($club, ClubSubscriptionEvent::TYPE_PAYMENT_FAILED, [
            'stripe_subscription_id' => $club->stripe_subscription_id,
            'cancellation_reason' => ClubSubscriptionEvent::REASON_PAYMENT_FAILED,
            'metadata' => [
                'invoice_id' => $invoice->id,
                'amount_due' => $invoice->amount_due / 100,
                'currency' => $invoice->currency,
                'attempt_count' => $invoice->attempt_count,
                'next_payment_attempt' => $invoice->next_payment_attempt,
            ],
        ]);

        Log::warning('Club payment failed', [
            'club_id' => $club->id,
            'club_name' => $club->name,
            'invoice_id' => $invoice->id,
            'amount_due' => $invoice->amount_due / 100,
            'currency' => $invoice->currency,
            'attempt_count' => $invoice->attempt_count,
            'tenant_id' => $club->tenant_id,
        ]);

        // TODO: Send payment failure notification to club admin
    }

    /**
     * Handle invoice.created event (Phase 2).
     */
    protected function handleInvoiceCreated($invoice): void
    {
        $club = Club::where('stripe_customer_id', $invoice->customer)->first();
        if (! $club) {
            Log::debug('Club not found for invoice created event', [
                'customer_id' => $invoice->customer,
                'invoice_id' => $invoice->id,
            ]);

            return;
        }

        Log::info('Club invoice created', [
            'club_id' => $club->id,
            'club_name' => $club->name,
            'invoice_id' => $invoice->id,
            'amount_due' => $invoice->amount_due / 100,
            'currency' => $invoice->currency,
            'tenant_id' => $club->tenant_id,
        ]);

        // TODO: Send notification about new invoice
    }

    /**
     * Handle invoice.finalized event (Phase 2).
     */
    protected function handleInvoiceFinalized($invoice): void
    {
        $club = Club::where('stripe_customer_id', $invoice->customer)->first();
        if (! $club) {
            Log::debug('Club not found for invoice finalized event', [
                'customer_id' => $invoice->customer,
                'invoice_id' => $invoice->id,
            ]);

            return;
        }

        Log::info('Club invoice finalized', [
            'club_id' => $club->id,
            'club_name' => $club->name,
            'invoice_id' => $invoice->id,
            'amount_due' => $invoice->amount_due / 100,
            'currency' => $invoice->currency,
            'status' => $invoice->status,
            'tenant_id' => $club->tenant_id,
        ]);

        // TODO: Send invoice notification to club admin
    }

    /**
     * Handle invoice.payment_action_required event (Phase 2).
     *
     * This occurs when 3D Secure authentication is required.
     */
    protected function handlePaymentActionRequired($invoice): void
    {
        $club = Club::where('stripe_customer_id', $invoice->customer)->first();
        if (! $club) {
            Log::debug('Club not found for payment action required event', [
                'customer_id' => $invoice->customer,
                'invoice_id' => $invoice->id,
            ]);

            return;
        }

        Log::warning('Club payment action required (3D Secure)', [
            'club_id' => $club->id,
            'club_name' => $club->name,
            'invoice_id' => $invoice->id,
            'payment_intent' => $invoice->payment_intent,
            'tenant_id' => $club->tenant_id,
        ]);

        // TODO: Send 3D Secure authentication request to club admin
    }

    /**
     * Handle payment_method.attached event (Phase 2).
     */
    protected function handlePaymentMethodAttached($paymentMethod): void
    {
        $club = Club::where('stripe_customer_id', $paymentMethod->customer)->first();
        if (! $club) {
            Log::debug('Club not found for payment method attached event', [
                'customer_id' => $paymentMethod->customer,
                'payment_method_id' => $paymentMethod->id,
            ]);

            return;
        }

        Log::info('Payment method attached to club', [
            'club_id' => $club->id,
            'club_name' => $club->name,
            'payment_method_id' => $paymentMethod->id,
            'payment_method_type' => $paymentMethod->type,
            'tenant_id' => $club->tenant_id,
        ]);

        // TODO: Send confirmation notification
    }

    /**
     * Handle payment_method.detached event (Phase 2).
     */
    protected function handlePaymentMethodDetached($paymentMethod): void
    {
        // Note: When detached, payment method no longer has customer reference
        Log::info('Payment method detached', [
            'payment_method_id' => $paymentMethod->id,
            'payment_method_type' => $paymentMethod->type,
        ]);

        // Find club by payment_method_id (if stored)
        $club = Club::where('payment_method_id', $paymentMethod->id)->first();
        if ($club) {
            // Clear default payment method if this was it
            $club->update(['payment_method_id' => null]);

            Log::info('Default payment method cleared for club', [
                'club_id' => $club->id,
                'club_name' => $club->name,
                'payment_method_id' => $paymentMethod->id,
                'tenant_id' => $club->tenant_id,
            ]);
        }

        // TODO: Send notification about payment method removal
    }

    /**
     * Track subscription event for analytics.
     *
     * @param Club $club
     * @param string $eventType
     * @param array $data
     * @return void
     */
    protected function trackSubscriptionEvent(Club $club, string $eventType, array $data = []): void
    {
        try {
            ClubSubscriptionEvent::create([
                'tenant_id' => $club->tenant_id,
                'club_id' => $club->id,
                'event_type' => $eventType,
                'stripe_subscription_id' => $data['stripe_subscription_id'] ?? null,
                'stripe_event_id' => $data['stripe_event_id'] ?? null,
                'old_plan_id' => $data['old_plan_id'] ?? null,
                'new_plan_id' => $data['new_plan_id'] ?? null,
                'mrr_change' => $data['mrr_change'] ?? 0,
                'cancellation_reason' => $data['cancellation_reason'] ?? null,
                'cancellation_feedback' => $data['cancellation_feedback'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'event_date' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to track subscription event', [
                'club_id' => $club->id,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate MRR from a plan (normalized to monthly).
     *
     * @param ClubSubscriptionPlan|null $plan
     * @return float
     */
    protected function calculateMRRFromPlan(?ClubSubscriptionPlan $plan): float
    {
        if (! $plan) {
            return 0;
        }

        // Normalize to monthly recurring revenue
        if ($plan->billing_interval === 'yearly') {
            return round($plan->price / 12, 2);
        }

        return (float) $plan->price;
    }

    /**
     * Calculate MRR change between two plans.
     *
     * @param ClubSubscriptionPlan|null $oldPlan
     * @param ClubSubscriptionPlan|null $newPlan
     * @return float
     */
    protected function calculateMRRChange(?ClubSubscriptionPlan $oldPlan, ?ClubSubscriptionPlan $newPlan): float
    {
        $oldMRR = $this->calculateMRRFromPlan($oldPlan);
        $newMRR = $this->calculateMRRFromPlan($newPlan);

        return $newMRR - $oldMRR;
    }
}
