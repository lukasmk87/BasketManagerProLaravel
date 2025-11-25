<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\ClubSubscriptionEvent;
use App\Models\ClubSubscriptionPlan;
use App\Services\ClubSubscriptionNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class ClubSubscriptionWebhookController extends Controller
{
    /**
     * @param ClubSubscriptionNotificationService $notificationService
     */
    public function __construct(
        protected ClubSubscriptionNotificationService $notificationService
    ) {}

    /**
     * Validate tenant ownership and find club with proper security checks.
     *
     * SEC-004: This method ensures cross-tenant webhook attacks are prevented.
     *
     * @param string|int|null $clubId
     * @param string|int|null $tenantId
     * @param string|null $stripeSubscriptionId
     * @param string|null $stripeCustomerId
     * @return Club|null
     */
    protected function validateAndFindClub(
        string|int|null $clubId,
        string|int|null $tenantId,
        ?string $stripeSubscriptionId = null,
        ?string $stripeCustomerId = null
    ): ?Club {
        // Reject if basic metadata is missing
        if (!$clubId || !$tenantId) {
            Log::warning('Webhook tenant validation failed: missing metadata', [
                'club_id' => $clubId,
                'tenant_id' => $tenantId,
            ]);
            return null;
        }

        $query = Club::where('id', $clubId)
            ->where('tenant_id', $tenantId);

        // Add optional Stripe cross-checks for stronger validation
        if ($stripeSubscriptionId) {
            $query->where('stripe_subscription_id', $stripeSubscriptionId);
        }

        if ($stripeCustomerId) {
            $query->where('stripe_customer_id', $stripeCustomerId);
        }

        $club = $query->first();

        if (!$club) {
            Log::error('Webhook tenant validation failed: club not found or tenant mismatch', [
                'club_id' => $clubId,
                'claimed_tenant_id' => $tenantId,
                'stripe_subscription_id' => $stripeSubscriptionId,
                'stripe_customer_id' => $stripeCustomerId,
                'reason' => 'tenant_mismatch_or_not_found',
            ]);
        }

        return $club;
    }

    /**
     * Extract tenant_id from subscription or session metadata.
     *
     * @param object $stripeObject Stripe subscription, session, or invoice
     * @return string|int|null
     */
    protected function extractTenantIdFromMetadata(object $stripeObject): string|int|null
    {
        return $stripeObject->metadata->tenant_id ?? null;
    }

    /**
     * Extract club_id from subscription or session metadata.
     *
     * @param object $stripeObject Stripe subscription, session, or invoice
     * @return string|int|null
     */
    protected function extractClubIdFromMetadata(object $stripeObject): string|int|null
    {
        return $stripeObject->metadata->club_id ?? null;
    }

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
     *
     * SEC-004: Validates tenant ownership before updating club.
     */
    protected function handleCheckoutCompleted($session): void
    {
        $clubId = $this->extractClubIdFromMetadata($session);
        $tenantId = $this->extractTenantIdFromMetadata($session);
        $planId = $session->metadata->club_subscription_plan_id ?? null;

        if (! $clubId || ! $planId) {
            Log::warning('Club checkout completed without club_id or plan_id', [
                'session_id' => $session->id,
                'metadata' => $session->metadata,
            ]);

            return;
        }

        // SEC-004: Validate tenant ownership
        $club = $this->validateAndFindClub(
            $clubId,
            $tenantId,
            null, // No subscription ID yet
            $session->customer // Cross-check with customer ID if already set
        );

        if (! $club) {
            Log::error('Club checkout tenant validation failed', [
                'club_id' => $clubId,
                'tenant_id' => $tenantId,
                'session_id' => $session->id,
            ]);

            return;
        }

        // SEC-004: Verify plan belongs to same tenant
        $plan = ClubSubscriptionPlan::where('id', $planId)
            ->where('tenant_id', $club->tenant_id)
            ->first();

        if (! $plan) {
            Log::error('Plan tenant mismatch in checkout', [
                'club_id' => $clubId,
                'club_tenant_id' => $club->tenant_id,
                'plan_id' => $planId,
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

        // Send welcome email to club admin
        try {
            $plan = ClubSubscriptionPlan::find($planId);
            if ($plan) {
                $this->notificationService->sendSubscriptionWelcome($club, $plan);

                Log::info('Club subscription welcome email sent', [
                    'club_id' => $club->id,
                    'plan_id' => $plan->id,
                ]);
            }
        } catch (\Exception $e) {
            // Don't fail webhook if email fails
            Log::error('Failed to send club subscription welcome email', [
                'club_id' => $club->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle customer.subscription.created event.
     *
     * SEC-004: Validates tenant ownership before updating club.
     */
    protected function handleSubscriptionCreated($subscription): void
    {
        $clubId = $this->extractClubIdFromMetadata($subscription);
        $tenantId = $this->extractTenantIdFromMetadata($subscription);

        if (! $clubId) {
            Log::debug('Subscription created without club_id metadata', [
                'subscription_id' => $subscription->id,
            ]);

            return;
        }

        // SEC-004: Validate tenant ownership
        $club = $this->validateAndFindClub(
            $clubId,
            $tenantId,
            null, // Subscription ID will be set by this event
            $subscription->customer
        );

        if (! $club) {
            Log::warning('Subscription created tenant validation failed', [
                'club_id' => $clubId,
                'tenant_id' => $tenantId,
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
     *
     * SEC-004: Validates tenant ownership before updating club.
     */
    protected function handleSubscriptionUpdated($subscription): void
    {
        $clubId = $this->extractClubIdFromMetadata($subscription);
        $tenantId = $this->extractTenantIdFromMetadata($subscription);

        // SEC-004: Validate tenant ownership with full cross-check
        $club = $this->validateAndFindClub(
            $clubId,
            $tenantId,
            $subscription->id, // Must match existing subscription ID
            $subscription->customer
        );

        if (! $club) {
            Log::warning('Subscription update tenant validation failed', [
                'club_id' => $clubId,
                'tenant_id' => $tenantId,
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
     *
     * SEC-004: Validates tenant ownership before updating club.
     */
    protected function handleSubscriptionDeleted($subscription): void
    {
        $clubId = $this->extractClubIdFromMetadata($subscription);
        $tenantId = $this->extractTenantIdFromMetadata($subscription);

        // SEC-004: Validate tenant ownership with full cross-check
        $club = $this->validateAndFindClub(
            $clubId,
            $tenantId,
            $subscription->id, // Must match existing subscription ID
            $subscription->customer
        );

        if (! $club) {
            Log::warning('Subscription deletion tenant validation failed', [
                'club_id' => $clubId,
                'tenant_id' => $tenantId,
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

        // Send cancellation notification to club admin
        try {
            $cancellationReason = 'voluntary'; // Default to voluntary cancellation
            $accessUntil = null; // Subscription ended immediately

            $this->notificationService->sendSubscriptionCanceled(
                $club,
                $cancellationReason,
                $accessUntil
            );

            Log::info('Club subscription cancellation email sent', [
                'club_id' => $club->id,
                'reason' => $cancellationReason,
            ]);
        } catch (\Exception $e) {
            // Don't fail webhook if email fails
            Log::error('Failed to send club subscription cancellation email', [
                'club_id' => $club->id,
                'error' => $e->getMessage(),
            ]);
        }
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

        // Send payment confirmation email to club admin
        try {
            $invoiceData = [
                'number' => $invoice->number ?? $invoice->id,
                'amount' => $invoice->amount_paid / 100, // Convert from cents
                'currency' => strtoupper($invoice->currency),
                'paid_at' => now(),
                'next_billing_date' => $club->subscription_current_period_end,
                'billing_interval' => 'monthly', // Default, will be determined by subscription
                'pdf_url' => $invoice->invoice_pdf ?? null,
            ];

            $this->notificationService->sendPaymentSuccessful($club, $invoiceData);

            Log::info('Club payment success email sent', [
                'club_id' => $club->id,
                'invoice_id' => $invoice->id,
            ]);
        } catch (\Exception $e) {
            // Don't fail webhook if email fails
            Log::error('Failed to send club payment success email', [
                'club_id' => $club->id,
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }
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

        // Send payment failure notification to club admin
        try {
            $invoiceData = [
                'number' => $invoice->number ?? $invoice->id,
                'amount' => $invoice->amount_due / 100, // Convert from cents
                'currency' => strtoupper($invoice->currency),
                'attempted_at' => now(),
                'grace_period_days' => 3, // Stripe default grace period
                'retry_attempts' => $invoice->attempt_count ?? 0,
            ];

            // Extract failure reason from invoice
            $failureReason = 'generic_decline';
            if (isset($invoice->charge) && isset($invoice->charge->failure_code)) {
                $failureReason = $invoice->charge->failure_code;
            } elseif (isset($invoice->last_finalization_error)) {
                $failureReason = $invoice->last_finalization_error->code ?? 'generic_decline';
            }

            $this->notificationService->sendPaymentFailed($club, $invoiceData, $failureReason);

            Log::info('Club payment failed email sent', [
                'club_id' => $club->id,
                'invoice_id' => $invoice->id,
                'failure_reason' => $failureReason,
            ]);
        } catch (\Exception $e) {
            // Don't fail webhook if email fails
            Log::error('Failed to send club payment failed email', [
                'club_id' => $club->id,
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }
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

        // NOTE: Optional notification - currently not implemented (low priority).
        // Invoice creation is an internal event. Users are notified when payment succeeds/fails.
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

        // NOTE: No email sent here - invoice.finalized is informational only.
        // Emails are sent when payment succeeds (invoice.payment_succeeded) or fails (invoice.payment_failed).
        // This prevents duplicate emails for the same invoice.
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

        // Send 3D Secure authentication request to club admin
        try {
            $invoiceData = [
                'number' => $invoice->number ?? $invoice->id,
                'amount' => $invoice->amount_due / 100, // Convert from cents
                'currency' => strtoupper($invoice->currency),
                'attempted_at' => now(),
                'grace_period_days' => 3,
                'retry_attempts' => $invoice->attempt_count ?? 0,
                'payment_intent' => $invoice->payment_intent,
            ];

            // Use 3DS-specific failure reason
            $failureReason = 'authentication_required';

            $this->notificationService->sendPaymentFailed($club, $invoiceData, $failureReason);

            Log::info('Club 3D Secure authentication email sent', [
                'club_id' => $club->id,
                'invoice_id' => $invoice->id,
                'payment_intent' => $invoice->payment_intent,
            ]);
        } catch (\Exception $e) {
            // Don't fail webhook if email fails
            Log::error('Failed to send club 3D Secure authentication email', [
                'club_id' => $club->id,
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }
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

        // NOTE: Optional notification - currently not implemented (low priority).
        // Payment method changes are user-initiated actions and receive immediate UI feedback.
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

            // NOTE: Optional notification - currently not implemented (low priority).
            // Payment method removal is user-initiated and receives immediate UI feedback.
        }
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
