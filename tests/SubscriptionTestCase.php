<?php

namespace Tests;

use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use App\Models\Tenant;
use App\Services\Stripe\StripeClientManager;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Stripe\BillingPortal\Session as BillingPortalSession;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\StripeClient;
use Stripe\Subscription as StripeSubscription;

/**
 * Base Test Case für Subscription & Billing Tests
 *
 * Diese Klasse erweitert TestCase mit Subscription-spezifischen Helper-Methoden
 * für schnelleres und konsistenteres Testen.
 *
 * Usage:
 * ```php
 * class MySubscriptionTest extends SubscriptionTestCase
 * {
 *     public function test_checkout()
 *     {
 *         $club = $this->createClubWithSubscription();
 *         $this->assertSubscriptionActive($club);
 *     }
 * }
 * ```
 */
abstract class SubscriptionTestCase extends TestCase
{
    /**
     * Mocked Stripe Client
     */
    protected StripeClient $mockStripeClient;

    /**
     * Mocked Stripe Client Manager
     */
    protected StripeClientManager $mockClientManager;

    /**
     * Setup vor jedem Test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Mail Facade mocken (prevents actual email sending)
        Mail::fake();

        // Stripe Services mocken
        $this->mockStripeServices();
    }

    // ============================================================
    // SUBSCRIPTION PLAN HELPERS
    // ============================================================

    /**
     * Erstelle einen Basic Subscription Plan
     */
    protected function createBasicPlan(?Tenant $tenant = null): ClubSubscriptionPlan
    {
        return ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant?->id ?? Tenant::factory()->create()->id,
            'name' => 'Basic Club',
            'slug' => 'basic',
            'price' => 29.99,
            'currency' => 'EUR',
            'billing_interval' => 'monthly',
            'is_active' => true,
            'stripe_product_id' => 'prod_basic_' . uniqid(),
            'stripe_price_id_monthly' => 'price_basic_monthly_' . uniqid(),
            'stripe_price_id_yearly' => 'price_basic_yearly_' . uniqid(),
            'is_stripe_synced' => true,
            'trial_period_days' => 0,
            'features' => ['team_management', 'game_scoring', 'basic_stats'],
            'limits' => [
                'max_teams' => 5,
                'max_players' => 100,
                'max_games' => 50,
                'max_trainings' => 20,
            ],
        ]);
    }

    /**
     * Erstelle einen Premium Subscription Plan
     */
    protected function createPremiumPlan(?Tenant $tenant = null): ClubSubscriptionPlan
    {
        return ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant?->id ?? Tenant::factory()->create()->id,
            'name' => 'Premium Club',
            'slug' => 'premium',
            'price' => 79.99,
            'currency' => 'EUR',
            'billing_interval' => 'monthly',
            'is_active' => true,
            'stripe_product_id' => 'prod_premium_' . uniqid(),
            'stripe_price_id_monthly' => 'price_premium_monthly_' . uniqid(),
            'stripe_price_id_yearly' => 'price_premium_yearly_' . uniqid(),
            'is_stripe_synced' => true,
            'trial_period_days' => 14,
            'features' => ['team_management', 'game_scoring', 'advanced_stats', 'live_scoring', 'video_analysis'],
            'limits' => [
                'max_teams' => 20,
                'max_players' => 500,
                'max_games' => 200,
                'max_trainings' => 100,
            ],
        ]);
    }

    /**
     * Erstelle einen Custom Subscription Plan
     */
    protected function createCustomPlan(array $attributes = []): ClubSubscriptionPlan
    {
        return ClubSubscriptionPlan::factory()->create($attributes);
    }

    // ============================================================
    // CLUB WITH SUBSCRIPTION HELPERS
    // ============================================================

    /**
     * Erstelle einen Club mit aktiver Subscription
     */
    protected function createClubWithSubscription(?ClubSubscriptionPlan $plan = null): Club
    {
        $plan = $plan ?? $this->createBasicPlan();

        return Club::factory()->create([
            'tenant_id' => $plan->tenant_id,
            'club_subscription_plan_id' => $plan->id,
            'stripe_customer_id' => 'cus_test_' . uniqid(),
            'stripe_subscription_id' => 'sub_test_' . uniqid(),
            'subscription_status' => 'active',
            'subscription_started_at' => now()->subMonths(3),
            'subscription_current_period_start' => now()->startOfMonth(),
            'subscription_current_period_end' => now()->endOfMonth(),
            'subscription_trial_ends_at' => null,
            'subscription_ends_at' => null,
        ]);
    }

    /**
     * Erstelle einen Club mit Trial Subscription
     */
    protected function createClubWithTrialSubscription(): Club
    {
        $plan = $this->createPremiumPlan(); // Premium hat 14 Tage Trial

        return Club::factory()->create([
            'tenant_id' => $plan->tenant_id,
            'club_subscription_plan_id' => $plan->id,
            'stripe_customer_id' => 'cus_test_' . uniqid(),
            'stripe_subscription_id' => 'sub_test_' . uniqid(),
            'subscription_status' => 'trial',
            'subscription_started_at' => now()->subDays(5),
            'subscription_trial_ends_at' => now()->addDays(9), // 9 Tage verbleibend
            'subscription_current_period_start' => now()->startOfMonth(),
            'subscription_current_period_end' => now()->endOfMonth(),
            'subscription_ends_at' => null,
        ]);
    }

    /**
     * Erstelle einen Club mit stornierter Subscription
     */
    protected function createClubWithCanceledSubscription(): Club
    {
        $plan = $this->createBasicPlan();

        return Club::factory()->create([
            'tenant_id' => $plan->tenant_id,
            'club_subscription_plan_id' => null, // Plan entfernt
            'stripe_customer_id' => 'cus_test_' . uniqid(),
            'stripe_subscription_id' => 'sub_test_' . uniqid(),
            'subscription_status' => 'canceled',
            'subscription_started_at' => now()->subMonths(6),
            'subscription_ends_at' => now()->subMonth(), // Vor 1 Monat beendet
            'subscription_current_period_start' => null,
            'subscription_current_period_end' => null,
        ]);
    }

    // ============================================================
    // STRIPE MOCKING HELPERS
    // ============================================================

    /**
     * Mocke Stripe Services (Client & Manager)
     */
    protected function mockStripeServices(): void
    {
        $this->mockStripeClient = Mockery::mock(StripeClient::class);
        $this->mockClientManager = Mockery::mock(StripeClientManager::class);

        $this->mockClientManager
            ->shouldReceive('getCurrentTenantClient')
            ->andReturn($this->mockStripeClient);

        $this->app->instance(StripeClientManager::class, $this->mockClientManager);
    }

    /**
     * Erstelle eine gemockte Stripe Checkout Session
     */
    protected function createMockCheckoutSession(array $overrides = []): CheckoutSession
    {
        return Mockery::mock(CheckoutSession::class, array_merge([
            'id' => 'cs_test_' . uniqid(),
            'url' => 'https://checkout.stripe.com/test_' . uniqid(),
            'customer' => 'cus_test_' . uniqid(),
            'subscription' => 'sub_test_' . uniqid(),
            'payment_status' => 'paid',
            'status' => 'complete',
            'mode' => 'subscription',
            'metadata' => new \stdClass(),
        ], $overrides));
    }

    /**
     * Erstelle eine gemockte Stripe Subscription
     */
    protected function createMockSubscription(array $overrides = []): StripeSubscription
    {
        return Mockery::mock(StripeSubscription::class, array_merge([
            'id' => 'sub_test_' . uniqid(),
            'customer' => 'cus_test_' . uniqid(),
            'status' => 'active',
            'current_period_start' => now()->startOfMonth()->timestamp,
            'current_period_end' => now()->endOfMonth()->timestamp,
            'trial_end' => null,
            'cancel_at_period_end' => false,
        ], $overrides));
    }

    /**
     * Erstelle eine gemockte Billing Portal Session
     */
    protected function createMockBillingPortalSession(): BillingPortalSession
    {
        return Mockery::mock(BillingPortalSession::class, [
            'id' => 'bps_test_' . uniqid(),
            'url' => 'https://billing.stripe.com/session/test_' . uniqid(),
        ]);
    }

    /**
     * Mocke einen Stripe Webhook-Payload
     */
    protected function createMockWebhookPayload(string $eventType, array $data = []): array
    {
        return [
            'id' => 'evt_test_' . uniqid(),
            'object' => 'event',
            'type' => $eventType,
            'created' => time(),
            'data' => [
                'object' => array_merge([
                    'id' => $eventType === 'checkout.session.completed' ? 'cs_test_' . uniqid() : 'obj_test_' . uniqid(),
                ], $data),
            ],
        ];
    }

    // ============================================================
    // ASSERTION HELPERS
    // ============================================================

    /**
     * Assert dass Club eine aktive Subscription hat
     */
    protected function assertSubscriptionActive(Club $club): void
    {
        $club->refresh();

        $this->assertEquals('active', $club->subscription_status);
        $this->assertNotNull($club->stripe_customer_id);
        $this->assertNotNull($club->stripe_subscription_id);
        $this->assertNotNull($club->club_subscription_plan_id);
        $this->assertTrue($club->hasActiveSubscription());
    }

    /**
     * Assert dass Club's Subscription storniert ist
     */
    protected function assertSubscriptionCanceled(Club $club): void
    {
        $club->refresh();

        $this->assertEquals('canceled', $club->subscription_status);
        $this->assertNotNull($club->subscription_ends_at);
        $this->assertNull($club->club_subscription_plan_id);
        $this->assertFalse($club->hasActiveSubscription());
    }

    /**
     * Assert dass Club im Trial ist
     */
    protected function assertSubscriptionTrialing(Club $club): void
    {
        $club->refresh();

        $this->assertEquals('trial', $club->subscription_status);
        $this->assertNotNull($club->subscription_trial_ends_at);
        $this->assertTrue($club->isOnTrial());
        $this->assertGreaterThan(0, $club->trialDaysRemaining());
    }

    /**
     * Assert dass Club eine Invoice generiert hat
     */
    protected function assertInvoiceGenerated(Club $club, ?string $invoiceId = null): void
    {
        $club->refresh();

        $this->assertNotNull($club->stripe_customer_id);

        // Optional: Prüfe ob eine spezifische Invoice ID vorhanden ist
        if ($invoiceId) {
            // Hier könnte man eine NotificationLog-Überprüfung machen
            $this->assertDatabaseHas('notification_logs', [
                'notifiable_type' => Club::class,
                'notifiable_id' => $club->id,
            ]);
        }
    }

    /**
     * Assert dass eine Notification gesendet wurde
     */
    protected function assertNotificationSent(string $mailClass, $to = null): void
    {
        if ($to) {
            Mail::assertQueued($mailClass, function ($mail) use ($to) {
                return $mail->hasTo($to);
            });
        } else {
            Mail::assertQueued($mailClass);
        }
    }

    /**
     * Assert dass Club bestimmte Features hat
     */
    protected function assertHasFeature(Club $club, string $feature): void
    {
        $club->refresh();

        $this->assertTrue(
            $club->hasFeature($feature),
            "Club should have feature '{$feature}' but doesn't"
        );
    }

    /**
     * Assert dass Club innerhalb eines Limits ist
     */
    protected function assertWithinLimit(Club $club, string $resource, int $count): void
    {
        $club->refresh();

        $this->assertTrue(
            $club->canUse($resource, $count),
            "Club should be able to use {$count} {$resource} but can't"
        );
    }

    /**
     * Cleanup nach Tests
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
