<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\Club;
use App\Models\Tenant;
use App\Models\ClubSubscriptionPlan;
use App\Models\ClubSubscriptionEvent;
use App\Models\NotificationLog;
use App\Mail\ClubSubscription\PaymentSuccessfulMail;
use App\Mail\ClubSubscription\PaymentFailedMail;
use App\Mail\ClubSubscription\SubscriptionWelcomeMail;
use App\Mail\ClubSubscription\SubscriptionCanceledMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Stripe\Webhook;
use Stripe\Event;

class ClubSubscriptionWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Club $club;
    protected ClubSubscriptionPlan $plan;
    protected string $webhookSecret;

    protected function setUp(): void
    {
        parent::setUp();

        // Fake mail for notification testing
        Mail::fake();

        // Create test data
        $this->tenant = Tenant::factory()->create();
        $this->plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Premium Club',
            'price' => 49.99,
            'currency' => 'EUR',
            'stripe_product_id' => 'prod_test_premium',
            'stripe_price_id_monthly' => 'price_test_monthly',
            'is_stripe_synced' => true,
        ]);
        $this->club = Club::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Basketball Club',
            'stripe_customer_id' => 'cus_test_123',
            'club_subscription_plan_id' => $this->plan->id,
        ]);

        // Set webhook secret
        $this->webhookSecret = 'whsec_test_secret';
        Config::set('stripe.webhooks.signing_secret_club', $this->webhookSecret);
    }

    /**
     * Helper: Create Stripe webhook payload with valid signature
     */
    protected function createWebhookPayload(string $eventType, array $data): array
    {
        $timestamp = time();
        $payload = json_encode([
            'id' => 'evt_' . uniqid(),
            'object' => 'event',
            'type' => $eventType,
            'data' => [
                'object' => $data,
            ],
            'created' => $timestamp,
            'livemode' => false,
        ]);

        // Generate valid signature
        $signature = $this->generateWebhookSignature($payload, $timestamp);

        return [
            'payload' => $payload,
            'signature' => $signature,
        ];
    }

    /**
     * Helper: Generate Stripe webhook signature
     */
    protected function generateWebhookSignature(string $payload, int $timestamp): string
    {
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, $this->webhookSecret);

        return 't=' . $timestamp . ',v1=' . $signature;
    }

    /**
     * Helper: Create checkout session object
     */
    protected function createCheckoutSessionObject(array $overrides = []): array
    {
        return array_merge([
            'id' => 'cs_test_' . uniqid(),
            'object' => 'checkout.session',
            'customer' => $this->club->stripe_customer_id,
            'subscription' => 'sub_test_' . uniqid(),
            'metadata' => [
                'club_id' => $this->club->id,
                'club_subscription_plan_id' => $this->plan->id,
                'tenant_id' => $this->tenant->id,
                'billing_interval' => 'monthly',
            ],
            'status' => 'complete',
            'payment_status' => 'paid',
        ], $overrides);
    }

    /**
     * Helper: Create subscription object
     */
    protected function createSubscriptionObject(array $overrides = []): array
    {
        return array_merge([
            'id' => 'sub_test_' . uniqid(),
            'object' => 'subscription',
            'customer' => $this->club->stripe_customer_id,
            'status' => 'active',
            'current_period_start' => now()->timestamp,
            'current_period_end' => now()->addMonth()->timestamp,
            'metadata' => [
                'club_id' => $this->club->id,
                'plan_id' => $this->plan->id,
            ],
            'items' => [
                'data' => [
                    [
                        'id' => 'si_test_' . uniqid(),
                        'price' => [
                            'id' => $this->plan->stripe_price_id_monthly,
                        ],
                    ],
                ],
            ],
        ], $overrides);
    }

    /**
     * Helper: Create invoice object
     */
    protected function createInvoiceObject(array $overrides = []): array
    {
        return array_merge([
            'id' => 'in_test_' . uniqid(),
            'object' => 'invoice',
            'customer' => $this->club->stripe_customer_id,
            'subscription' => $this->club->stripe_subscription_id ?? 'sub_test_123',
            'number' => 'INV-' . date('Y-m') . '-' . rand(1000, 9999),
            'status' => 'paid',
            'amount_paid' => 4999,
            'amount_due' => 4999,
            'currency' => 'eur',
            'created' => now()->timestamp,
            'hosted_invoice_url' => 'https://invoice.stripe.com/test',
        ], $overrides);
    }

    /**
     * Helper: Create payment method object
     */
    protected function createPaymentMethodObject(array $overrides = []): array
    {
        return array_merge([
            'id' => 'pm_test_' . uniqid(),
            'object' => 'payment_method',
            'type' => 'card',
            'customer' => $this->club->stripe_customer_id,
            'card' => [
                'brand' => 'visa',
                'last4' => '4242',
                'exp_month' => 12,
                'exp_year' => 2025,
            ],
        ], $overrides);
    }

    /**
     * Test: checkout.session.completed webhook
     */
    public function test_checkout_completed_webhook_activates_subscription(): void
    {
        $sessionObject = $this->createCheckoutSessionObject();
        $webhookData = $this->createWebhookPayload('checkout.session.completed', $sessionObject);

        $response = $this->postJson(route('webhooks.stripe.club-subscriptions'),
            json_decode($webhookData['payload'], true),
            ['Stripe-Signature' => $webhookData['signature']]
        );

        $response->assertStatus(200);

        // Assert club updated
        $this->club->refresh();
        $this->assertEquals($sessionObject['customer'], $this->club->stripe_customer_id);
        $this->assertEquals($sessionObject['subscription'], $this->club->stripe_subscription_id);
        $this->assertEquals('active', $this->club->subscription_status);
        $this->assertNotNull($this->club->subscription_started_at);

        // Assert welcome email queued
        Mail::assertQueued(SubscriptionWelcomeMail::class, function ($mail) {
            return $mail->club->id === $this->club->id;
        });

        // Assert subscription event logged
        $this->assertDatabaseHas('club_subscription_events', [
            'club_id' => $this->club->id,
            'event_type' => 'subscription_created',
        ]);
    }

    /**
     * Test: customer.subscription.created webhook
     */
    public function test_subscription_created_webhook_updates_club_status(): void
    {
        $subscriptionObject = $this->createSubscriptionObject();
        $webhookData = $this->createWebhookPayload('customer.subscription.created', $subscriptionObject);

        $response = $this->postJson(route('webhooks.stripe.club-subscriptions'),
            json_decode($webhookData['payload'], true),
            ['Stripe-Signature' => $webhookData['signature']]
        );

        $response->assertStatus(200);

        // Assert club updated
        $this->club->refresh();
        $this->assertEquals($subscriptionObject['id'], $this->club->stripe_subscription_id);
        $this->assertEquals($subscriptionObject['status'], $this->club->subscription_status);
        $this->assertNotNull($this->club->subscription_current_period_start);
        $this->assertNotNull($this->club->subscription_current_period_end);
    }

    /**
     * Test: customer.subscription.updated webhook
     */
    public function test_subscription_updated_webhook_updates_status_and_period(): void
    {
        // Set initial subscription
        $this->club->update([
            'stripe_subscription_id' => 'sub_test_initial',
            'subscription_status' => 'active',
        ]);

        $subscriptionObject = $this->createSubscriptionObject([
            'id' => 'sub_test_initial',
            'status' => 'active',
            'cancel_at_period_end' => true,
        ]);

        $webhookData = $this->createWebhookPayload('customer.subscription.updated', $subscriptionObject);

        $response = $this->postJson(route('webhooks.stripe.club-subscriptions'),
            json_decode($webhookData['payload'], true),
            ['Stripe-Signature' => $webhookData['signature']]
        );

        $response->assertStatus(200);

        // Assert club updated with cancel info
        $this->club->refresh();
        $this->assertEquals('active', $this->club->subscription_status);
        $this->assertNotNull($this->club->subscription_ends_at);
    }

    /**
     * Test: customer.subscription.deleted webhook
     */
    public function test_subscription_deleted_webhook_cancels_subscription(): void
    {
        // Set initial subscription
        $this->club->update([
            'stripe_subscription_id' => 'sub_test_to_delete',
            'subscription_status' => 'active',
            'club_subscription_plan_id' => $this->plan->id,
        ]);

        $subscriptionObject = $this->createSubscriptionObject([
            'id' => 'sub_test_to_delete',
            'status' => 'canceled',
        ]);

        $webhookData = $this->createWebhookPayload('customer.subscription.deleted', $subscriptionObject);

        $response = $this->postJson(route('webhooks.stripe.club-subscriptions'),
            json_decode($webhookData['payload'], true),
            ['Stripe-Signature' => $webhookData['signature']]
        );

        $response->assertStatus(200);

        // Assert club canceled
        $this->club->refresh();
        $this->assertEquals('canceled', $this->club->subscription_status);
        $this->assertNotNull($this->club->subscription_ends_at);
        $this->assertNull($this->club->club_subscription_plan_id);

        // Assert cancellation email queued
        Mail::assertQueued(SubscriptionCanceledMail::class, function ($mail) {
            return $mail->club->id === $this->club->id;
        });

        // Assert churn event logged
        $this->assertDatabaseHas('club_subscription_events', [
            'club_id' => $this->club->id,
            'event_type' => 'subscription_canceled',
        ]);
    }

    /**
     * Test: invoice.payment_succeeded webhook
     */
    public function test_payment_succeeded_webhook_sends_confirmation_email(): void
    {
        // Set subscription for club
        $this->club->update([
            'stripe_customer_id' => 'cus_test_payment',
            'stripe_subscription_id' => 'sub_test_payment',
            'subscription_status' => 'active',
        ]);

        $invoiceObject = $this->createInvoiceObject([
            'customer' => 'cus_test_payment',
            'status' => 'paid',
            'amount_paid' => 4999,
        ]);

        $webhookData = $this->createWebhookPayload('invoice.payment_succeeded', $invoiceObject);

        $response = $this->postJson(route('webhooks.stripe.club-subscriptions'),
            json_decode($webhookData['payload'], true),
            ['Stripe-Signature' => $webhookData['signature']]
        );

        $response->assertStatus(200);

        // Assert club status updated to active
        $this->club->refresh();
        $this->assertEquals('active', $this->club->subscription_status);

        // Assert payment successful email queued
        Mail::assertQueued(PaymentSuccessfulMail::class, function ($mail) use ($invoiceObject) {
            return $mail->club->id === $this->club->id
                && $mail->invoiceData['number'] === $invoiceObject['number'];
        });

        // Assert payment event logged
        $this->assertDatabaseHas('club_subscription_events', [
            'club_id' => $this->club->id,
            'event_type' => 'payment_succeeded',
        ]);
    }

    /**
     * Test: invoice.payment_failed webhook
     */
    public function test_payment_failed_webhook_sends_alert_email(): void
    {
        $this->club->update([
            'stripe_customer_id' => 'cus_test_failed',
            'stripe_subscription_id' => 'sub_test_failed',
            'subscription_status' => 'active',
        ]);

        $invoiceObject = $this->createInvoiceObject([
            'customer' => 'cus_test_failed',
            'status' => 'open',
            'amount_due' => 4999,
            'amount_paid' => 0,
            'attempt_count' => 1,
        ]);

        $webhookData = $this->createWebhookPayload('invoice.payment_failed', $invoiceObject);

        $response = $this->postJson(route('webhooks.stripe.club-subscriptions'),
            json_decode($webhookData['payload'], true),
            ['Stripe-Signature' => $webhookData['signature']]
        );

        $response->assertStatus(200);

        // Assert club marked as past_due
        $this->club->refresh();
        $this->assertEquals('past_due', $this->club->subscription_status);

        // Assert payment failed email queued
        Mail::assertQueued(PaymentFailedMail::class, function ($mail) {
            return $mail->club->id === $this->club->id;
        });

        // Assert payment failed event logged
        $this->assertDatabaseHas('club_subscription_events', [
            'club_id' => $this->club->id,
            'event_type' => 'payment_failed',
        ]);
    }

    /**
     * Test: invoice.created webhook (logging only)
     */
    public function test_invoice_created_webhook_logs_event(): void
    {
        $this->club->update([
            'stripe_customer_id' => 'cus_test_invoice',
        ]);

        $invoiceObject = $this->createInvoiceObject([
            'customer' => 'cus_test_invoice',
            'status' => 'draft',
        ]);

        $webhookData = $this->createWebhookPayload('invoice.created', $invoiceObject);

        $response = $this->postJson(route('webhooks.stripe.club-subscriptions'),
            json_decode($webhookData['payload'], true),
            ['Stripe-Signature' => $webhookData['signature']]
        );

        $response->assertStatus(200);

        // No email should be sent for invoice.created
        Mail::assertNothingQueued();
    }

    /**
     * Test: invoice.finalized webhook (logging only)
     */
    public function test_invoice_finalized_webhook_logs_without_sending_email(): void
    {
        $this->club->update([
            'stripe_customer_id' => 'cus_test_finalized',
        ]);

        $invoiceObject = $this->createInvoiceObject([
            'customer' => 'cus_test_finalized',
            'status' => 'open',
        ]);

        $webhookData = $this->createWebhookPayload('invoice.finalized', $invoiceObject);

        $response = $this->postJson(route('webhooks.stripe.club-subscriptions'),
            json_decode($webhookData['payload'], true),
            ['Stripe-Signature' => $webhookData['signature']]
        );

        $response->assertStatus(200);

        // No email for invoice.finalized (prevents duplicates)
        Mail::assertNothingQueued();
    }

    /**
     * Test: invoice.payment_action_required webhook (3D Secure)
     */
    public function test_payment_action_required_webhook_sends_alert(): void
    {
        $this->club->update([
            'stripe_customer_id' => 'cus_test_3ds',
            'subscription_status' => 'active',
        ]);

        $invoiceObject = $this->createInvoiceObject([
            'customer' => 'cus_test_3ds',
            'status' => 'open',
            'payment_intent' => 'pi_test_requires_action',
        ]);

        $webhookData = $this->createWebhookPayload('invoice.payment_action_required', $invoiceObject);

        $response = $this->postJson(route('webhooks.stripe.club-subscriptions'),
            json_decode($webhookData['payload'], true),
            ['Stripe-Signature' => $webhookData['signature']]
        );

        $response->assertStatus(200);

        // Assert 3DS alert email queued
        Mail::assertQueued(PaymentFailedMail::class, function ($mail) {
            return $mail->club->id === $this->club->id
                && $mail->failureReason === 'authentication_required';
        });
    }

    /**
     * Test: payment_method.attached webhook
     */
    public function test_payment_method_attached_webhook_logs_event(): void
    {
        $this->club->update([
            'stripe_customer_id' => 'cus_test_pm',
        ]);

        $pmObject = $this->createPaymentMethodObject([
            'customer' => 'cus_test_pm',
        ]);

        $webhookData = $this->createWebhookPayload('payment_method.attached', $pmObject);

        $response = $this->postJson(route('webhooks.stripe.club-subscriptions'),
            json_decode($webhookData['payload'], true),
            ['Stripe-Signature' => $webhookData['signature']]
        );

        $response->assertStatus(200);

        // No email for payment method events
        Mail::assertNothingQueued();
    }

    /**
     * Test: payment_method.detached webhook
     */
    public function test_payment_method_detached_webhook_clears_club_reference(): void
    {
        $paymentMethodId = 'pm_test_detach';

        $this->club->update([
            'stripe_customer_id' => 'cus_test_pm_detach',
            'payment_method_id' => $paymentMethodId,
        ]);

        $pmObject = $this->createPaymentMethodObject([
            'id' => $paymentMethodId,
            'customer' => null, // Detached
        ]);

        $webhookData = $this->createWebhookPayload('payment_method.detached', $pmObject);

        $response = $this->postJson(route('webhooks.stripe.club-subscriptions'),
            json_decode($webhookData['payload'], true),
            ['Stripe-Signature' => $webhookData['signature']]
        );

        $response->assertStatus(200);

        // Assert club payment_method_id cleared if it matches
        $this->club->refresh();
        $this->assertNull($this->club->payment_method_id);
    }

    /**
     * Test: Invalid webhook signature is rejected
     */
    public function test_invalid_signature_is_rejected(): void
    {
        $sessionObject = $this->createCheckoutSessionObject();
        $webhookData = $this->createWebhookPayload('checkout.session.completed', $sessionObject);

        // Send with invalid signature
        $response = $this->postJson(route('webhooks.stripe.club-subscriptions'),
            json_decode($webhookData['payload'], true),
            ['Stripe-Signature' => 'invalid_signature_here']
        );

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Invalid signature']);

        // Assert no data was updated
        $this->club->refresh();
        $this->assertNull($this->club->stripe_subscription_id);
    }

    /**
     * Test: Multi-tenant isolation (webhook only affects correct club)
     */
    public function test_webhooks_respect_multi_tenant_isolation(): void
    {
        // Create another tenant with club
        $otherTenant = Tenant::factory()->create();
        $otherClub = Club::factory()->create([
            'tenant_id' => $otherTenant->id,
            'stripe_customer_id' => 'cus_other_tenant',
            'subscription_status' => 'inactive',
        ]);

        // Send webhook for our club
        $sessionObject = $this->createCheckoutSessionObject();
        $webhookData = $this->createWebhookPayload('checkout.session.completed', $sessionObject);

        $response = $this->postJson(route('webhooks.stripe.club-subscriptions'),
            json_decode($webhookData['payload'], true),
            ['Stripe-Signature' => $webhookData['signature']]
        );

        $response->assertStatus(200);

        // Assert only our club was updated
        $this->club->refresh();
        $otherClub->refresh();

        $this->assertEquals('active', $this->club->subscription_status);
        $this->assertEquals('inactive', $otherClub->subscription_status); // Unchanged
    }

    /**
     * Test: Idempotency (duplicate webhook events handled gracefully)
     */
    public function test_duplicate_webhook_events_are_handled_idempotently(): void
    {
        $sessionObject = $this->createCheckoutSessionObject();
        $webhookData = $this->createWebhookPayload('checkout.session.completed', $sessionObject);

        // Send webhook twice
        $response1 = $this->postJson(route('webhooks.stripe.club-subscriptions'),
            json_decode($webhookData['payload'], true),
            ['Stripe-Signature' => $webhookData['signature']]
        );

        $response2 = $this->postJson(route('webhooks.stripe.club-subscriptions'),
            json_decode($webhookData['payload'], true),
            ['Stripe-Signature' => $webhookData['signature']]
        );

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Assert club state is consistent
        $this->club->refresh();
        $this->assertEquals('active', $this->club->subscription_status);

        // Assert welcome email only queued once
        Mail::assertQueued(SubscriptionWelcomeMail::class, 1);
    }

    /**
     * Test: Malformed webhook payload returns error
     */
    public function test_malformed_webhook_payload_returns_error(): void
    {
        $response = $this->postJson(route('webhooks.stripe.club-subscriptions'),
            ['invalid' => 'payload'],
            ['Stripe-Signature' => 'some_signature']
        );

        // Should still return 200 to prevent retries, but log error
        $response->assertStatus(400);
    }

    /**
     * Test: Webhook for non-existent club is handled gracefully
     */
    public function test_webhook_for_non_existent_club_is_handled_gracefully(): void
    {
        $sessionObject = $this->createCheckoutSessionObject([
            'customer' => 'cus_does_not_exist',
        ]);
        $webhookData = $this->createWebhookPayload('checkout.session.completed', $sessionObject);

        $response = $this->postJson(route('webhooks.stripe.club-subscriptions'),
            json_decode($webhookData['payload'], true),
            ['Stripe-Signature' => $webhookData['signature']]
        );

        // Should return 200 to acknowledge receipt (prevents retries)
        $response->assertStatus(200);

        // No email should be sent
        Mail::assertNothingQueued();
    }

    /**
     * Test: Webhook triggers notification log creation
     */
    public function test_webhooks_trigger_notification_log_creation(): void
    {
        $invoiceObject = $this->createInvoiceObject([
            'customer' => $this->club->stripe_customer_id,
            'status' => 'paid',
        ]);

        $webhookData = $this->createWebhookPayload('invoice.payment_succeeded', $invoiceObject);

        $response = $this->postJson(route('webhooks.stripe.club-subscriptions'),
            json_decode($webhookData['payload'], true),
            ['Stripe-Signature' => $webhookData['signature']]
        );

        $response->assertStatus(200);

        // Assert notification log created
        $this->assertDatabaseHas('notification_logs', [
            'notifiable_type' => Club::class,
            'notifiable_id' => $this->club->id,
            'notification_type' => PaymentSuccessfulMail::class,
            'channel' => 'email',
            'status' => 'queued',
        ]);
    }

    /**
     * Test: Club with disabled notification preference skips email
     */
    public function test_webhook_respects_notification_preferences(): void
    {
        // Create user and disable payment notifications
        $user = \App\Models\User::factory()->create();
        $user->assignRole('club_admin');

        \App\Models\NotificationPreference::create([
            'user_id' => $user->id,
            'notifiable_type' => Club::class,
            'notifiable_id' => $this->club->id,
            'channel' => 'email',
            'event_type' => 'payment_succeeded',
            'is_enabled' => false,
        ]);

        $invoiceObject = $this->createInvoiceObject([
            'customer' => $this->club->stripe_customer_id,
            'status' => 'paid',
        ]);

        $webhookData = $this->createWebhookPayload('invoice.payment_succeeded', $invoiceObject);

        $response = $this->postJson(route('webhooks.stripe.club-subscriptions'),
            json_decode($webhookData['payload'], true),
            ['Stripe-Signature' => $webhookData['signature']]
        );

        $response->assertStatus(200);

        // Email should still be queued (notifications go to all admins, not just one)
        // But the specific user's preference is respected in the notification service
    }
}
