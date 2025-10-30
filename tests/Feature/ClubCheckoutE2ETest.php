<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Club;
use App\Models\Tenant;
use App\Models\User;
use App\Models\ClubSubscriptionPlan;
use App\Services\Stripe\StripeClientManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\Checkout\Session as StripeSession;
use Stripe\BillingPortal\Session as BillingPortalSession;
use Stripe\Subscription as StripeSubscription;

/**
 * End-to-End Tests for Club Subscription Checkout Flow
 *
 * Tests the complete checkout journey from plan selection to subscription activation,
 * including payment processing, German payment methods, 3D Secure, and error handling.
 *
 * Stripe Test Cards Used:
 * - 4242 4242 4242 4242: Success (any CVC, any future date)
 * - 4000 0000 0000 0002: Generic decline
 * - 4000 0000 0000 9995: Insufficient funds
 * - 4000 0027 6000 3184: 3D Secure required
 * - 4000 0082 6000 0000: SEPA Direct Debit (Germany)
 */
class ClubCheckoutE2ETest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Club $club;
    protected ClubSubscriptionPlan $plan;
    protected User $user;
    protected $mockClientManager;
    protected $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test tenant
        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Basketball Federation',
        ]);

        // Create test plan with trial
        $this->plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Premium Club',
            'price' => 49.99,
            'currency' => 'EUR',
            'billing_interval' => 'monthly',
            'is_active' => true,
            'is_stripe_synced' => true,
            'stripe_product_id' => 'prod_test_premium',
            'stripe_price_id_monthly' => 'price_test_monthly_123',
            'stripe_price_id_yearly' => 'price_test_yearly_123',
            'trial_period_days' => 14,
            'features' => ['live_scoring', 'advanced_stats', 'video_analysis'],
            'limits' => [
                'max_teams' => 10,
                'max_players' => 150,
                'max_games' => 100,
            ],
        ]);

        // Create test club
        $this->club = Club::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Basketball Club',
            'email' => 'club@test.com',
            'billing_email' => 'billing@test.com',
        ]);

        // Create test user with admin role
        $this->user = User::factory()->create([
            'email' => 'admin@test.com',
        ]);

        // Assign user to club as admin
        $this->user->assignRole('club_admin');
        $this->club->users()->attach($this->user->id, [
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Mock Stripe services
        $this->mockStripeServices();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Mock Stripe API services
     */
    protected function mockStripeServices(): void
    {
        $this->mockClientManager = Mockery::mock(StripeClientManager::class);
        $this->mockClient = Mockery::mock(\Stripe\StripeClient::class);

        $this->mockClientManager
            ->shouldReceive('getCurrentTenantClient')
            ->andReturn($this->mockClient);

        $this->app->instance(StripeClientManager::class, $this->mockClientManager);
    }

    /**
     * Create mock Stripe checkout session
     */
    protected function createMockCheckoutSession(array $overrides = []): StripeSession
    {
        $mockSession = Mockery::mock(StripeSession::class);

        $defaults = [
            'id' => 'cs_test_' . uniqid(),
            'url' => 'https://checkout.stripe.com/test_session',
            'customer' => $this->club->stripe_customer_id ?? 'cus_test_' . uniqid(),
            'mode' => 'subscription',
            'status' => 'open',
        ];

        $data = array_merge($defaults, $overrides);

        foreach ($data as $key => $value) {
            $mockSession->$key = $value;
        }

        return $mockSession;
    }

    /**
     * Create mock Stripe subscription
     */
    protected function createMockSubscription(array $overrides = []): StripeSubscription
    {
        $mockSubscription = Mockery::mock(StripeSubscription::class);

        $defaults = [
            'id' => 'sub_test_' . uniqid(),
            'customer' => $this->club->stripe_customer_id ?? 'cus_test_' . uniqid(),
            'status' => 'active',
            'current_period_start' => now()->timestamp,
            'current_period_end' => now()->addMonth()->timestamp,
        ];

        $data = array_merge($defaults, $overrides);

        foreach ($data as $key => $value) {
            $mockSubscription->$key = $value;
        }

        return $mockSubscription;
    }

    /**
     * Create mock billing portal session
     */
    protected function createMockBillingPortalSession(): BillingPortalSession
    {
        $mockSession = Mockery::mock(BillingPortalSession::class);
        $mockSession->id = 'bps_test_' . uniqid();
        $mockSession->url = 'https://billing.stripe.com/session/test';

        return $mockSession;
    }

    /**
     * Assert subscription was successfully activated
     */
    protected function assertCheckoutSuccessful(Club $club, ClubSubscriptionPlan $plan): void
    {
        $club->refresh();

        $this->assertNotNull($club->stripe_customer_id);
        $this->assertNotNull($club->stripe_subscription_id);
        $this->assertEquals('active', $club->subscription_status);
        $this->assertEquals($plan->id, $club->club_subscription_plan_id);
        $this->assertNotNull($club->subscription_started_at);
    }

    /**
     * Assert feature is activated for club
     */
    protected function assertFeatureActivated(Club $club, string $feature): void
    {
        $club->refresh();
        $this->assertTrue($club->hasFeature($feature));
    }

    // ==========================================
    // GROUP 1: HAPPY PATH TESTS (5 tests)
    // ==========================================

    /**
     * Test: User can complete checkout with monthly billing
     */
    public function test_user_can_complete_checkout_with_monthly_billing(): void
    {
        // Setup mock checkout session
        $mockSession = $this->createMockCheckoutSession([
            'id' => 'cs_monthly_test',
            'url' => 'https://checkout.stripe.com/pay/cs_monthly_test',
        ]);

        // Mock Stripe API - Checkout Sessions
        $mockCheckout = Mockery::mock();
        $mockSessions = Mockery::mock();
        $this->mockClient->checkout = $mockCheckout;
        $mockCheckout->sessions = $mockSessions;

        $mockSessions->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['mode'] === 'subscription'
                    && $data['metadata']['billing_interval'] === 'monthly'
                    && $data['metadata']['club_id'] == $this->club->id
                    && $data['line_items'][0]['price'] === $this->plan->stripe_price_id_monthly;
            }))
            ->andReturn($mockSession);

        // Act: Initiate checkout
        $response = $this->actingAs($this->user)
            ->postJson(route('club.checkout', ['club' => $this->club->id]), [
                'plan_id' => $this->plan->id,
                'billing_interval' => 'monthly',
            ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'checkout_url' => 'https://checkout.stripe.com/pay/cs_monthly_test',
            'session_id' => 'cs_monthly_test',
        ]);
    }

    /**
     * Test: User can complete checkout with yearly billing
     */
    public function test_user_can_complete_checkout_with_yearly_billing(): void
    {
        // Setup mock checkout session for yearly
        $mockSession = $this->createMockCheckoutSession([
            'id' => 'cs_yearly_test',
            'url' => 'https://checkout.stripe.com/pay/cs_yearly_test',
        ]);

        // Mock Stripe API
        $mockCheckout = Mockery::mock();
        $mockSessions = Mockery::mock();
        $this->mockClient->checkout = $mockCheckout;
        $mockCheckout->sessions = $mockSessions;

        $mockSessions->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['mode'] === 'subscription'
                    && $data['metadata']['billing_interval'] === 'yearly'
                    && $data['line_items'][0]['price'] === $this->plan->stripe_price_id_yearly;
            }))
            ->andReturn($mockSession);

        // Act: Initiate checkout with yearly billing
        $response = $this->actingAs($this->user)
            ->postJson(route('club.checkout', ['club' => $this->club->id]), [
                'plan_id' => $this->plan->id,
                'billing_interval' => 'yearly',
            ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'checkout_url' => 'https://checkout.stripe.com/pay/cs_yearly_test',
            'session_id' => 'cs_yearly_test',
        ]);
    }

    /**
     * Test: Checkout applies trial period when configured
     */
    public function test_checkout_applies_trial_period_when_configured(): void
    {
        // Ensure plan has trial period
        $this->plan->update(['trial_period_days' => 14]);

        // Setup mock
        $mockSession = $this->createMockCheckoutSession();
        $mockCheckout = Mockery::mock();
        $mockSessions = Mockery::mock();
        $this->mockClient->checkout = $mockCheckout;
        $mockCheckout->sessions = $mockSessions;

        $mockSessions->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return isset($data['subscription_data']['trial_period_days'])
                    && $data['subscription_data']['trial_period_days'] === 14;
            }))
            ->andReturn($mockSession);

        // Act
        $response = $this->actingAs($this->user)
            ->postJson(route('club.checkout', ['club' => $this->club->id]), [
                'plan_id' => $this->plan->id,
                'billing_interval' => 'monthly',
            ]);

        // Assert
        $response->assertStatus(200);
    }

    /**
     * Test: Success page displays subscription details
     */
    public function test_success_page_displays_subscription_details(): void
    {
        // Setup club with active subscription
        $this->club->update([
            'stripe_customer_id' => 'cus_success_test',
            'stripe_subscription_id' => 'sub_success_test',
            'subscription_status' => 'active',
            'subscription_started_at' => now(),
            'club_subscription_plan_id' => $this->plan->id,
        ]);

        // Act: Visit success page
        $response = $this->actingAs($this->user)
            ->get(route('club.checkout.success', [
                'club' => $this->club->id,
                'session_id' => 'cs_success_test',
            ]));

        // Assert: Success page renders with subscription data
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Club/Checkout/Success')
            ->has('club')
            ->has('subscription')
            ->where('subscription.status', 'active')
            ->where('subscription.plan.name', 'Premium Club')
        );
    }

    /**
     * Test: Features are activated after successful payment
     */
    public function test_features_are_activated_after_successful_payment(): void
    {
        // Setup club with active subscription and plan
        $this->club->update([
            'stripe_customer_id' => 'cus_features_test',
            'stripe_subscription_id' => 'sub_features_test',
            'subscription_status' => 'active',
            'club_subscription_plan_id' => $this->plan->id,
        ]);

        // Assert: Features from plan are accessible
        $this->assertFeatureActivated($this->club, 'live_scoring');
        $this->assertFeatureActivated($this->club, 'advanced_stats');
        $this->assertFeatureActivated($this->club, 'video_analysis');

        // Assert: Limits from plan are enforced
        $this->assertEquals(10, $this->club->getLimit('max_teams'));
        $this->assertEquals(150, $this->club->getLimit('max_players'));
        $this->assertEquals(100, $this->club->getLimit('max_games'));
    }

    // ==========================================
    // GROUP 2: PAYMENT FAILURE TESTS (3 tests)
    // ==========================================

    /**
     * Test: Declined card shows appropriate error
     */
    public function test_declined_card_shows_appropriate_error(): void
    {
        // Mock Stripe API to throw decline error
        $mockCheckout = Mockery::mock();
        $mockSessions = Mockery::mock();
        $this->mockClient->checkout = $mockCheckout;
        $mockCheckout->sessions = $mockSessions;

        $mockSessions->shouldReceive('create')
            ->once()
            ->andThrow(new \Stripe\Exception\CardException(
                'Your card was declined.',
                'card_declined',
                ['code' => 'card_declined']
            ));

        // Act: Attempt checkout with declined card
        $response = $this->actingAs($this->user)
            ->postJson(route('club.checkout', ['club' => $this->club->id]), [
                'plan_id' => $this->plan->id,
                'billing_interval' => 'monthly',
                'test_card' => '4000000000000002', // Declined card
            ]);

        // Assert: Error response
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payment']);
    }

    /**
     * Test: Insufficient funds card is handled gracefully
     */
    public function test_insufficient_funds_card_is_handled_gracefully(): void
    {
        // Mock Stripe API to throw insufficient funds error
        $mockCheckout = Mockery::mock();
        $mockSessions = Mockery::mock();
        $this->mockClient->checkout = $mockCheckout;
        $mockCheckout->sessions = $mockSessions;

        $mockSessions->shouldReceive('create')
            ->once()
            ->andThrow(new \Stripe\Exception\CardException(
                'Your card has insufficient funds.',
                'insufficient_funds',
                ['code' => 'insufficient_funds']
            ));

        // Act
        $response = $this->actingAs($this->user)
            ->postJson(route('club.checkout', ['club' => $this->club->id]), [
                'plan_id' => $this->plan->id,
                'billing_interval' => 'monthly',
                'test_card' => '4000000000009995', // Insufficient funds card
            ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Your card has insufficient funds.']);
    }

    /**
     * Test: Cancel page displays retry options
     */
    public function test_cancel_page_displays_retry_options(): void
    {
        // Act: Visit cancel page
        $response = $this->actingAs($this->user)
            ->get(route('club.checkout.cancel', [
                'club' => $this->club->id,
                'session_id' => 'cs_canceled_test',
            ]));

        // Assert: Cancel page renders with retry options
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Club/Checkout/Cancel')
            ->has('club')
            ->has('plans') // Shows available plans for retry
        );
    }

    // ==========================================
    // GROUP 3: 3D SECURE TESTS (2 tests)
    // ==========================================

    /**
     * Test: 3D Secure card requires authentication
     */
    public function test_three_d_secure_card_requires_authentication(): void
    {
        // Setup mock session that requires action
        $mockSession = $this->createMockCheckoutSession([
            'id' => 'cs_3ds_test',
            'url' => 'https://checkout.stripe.com/pay/cs_3ds_test',
            'payment_status' => 'unpaid',
            'status' => 'open',
        ]);

        // Mock Stripe API
        $mockCheckout = Mockery::mock();
        $mockSessions = Mockery::mock();
        $this->mockClient->checkout = $mockCheckout;
        $mockCheckout->sessions = $mockSessions;

        $mockSessions->shouldReceive('create')
            ->once()
            ->andReturn($mockSession);

        // Act: Initiate checkout with 3DS card
        $response = $this->actingAs($this->user)
            ->postJson(route('club.checkout', ['club' => $this->club->id]), [
                'plan_id' => $this->plan->id,
                'billing_interval' => 'monthly',
                'test_card' => '4000002760003184', // 3D Secure required card
            ]);

        // Assert: Returns checkout URL (user will complete 3DS on Stripe's page)
        $response->assertStatus(200);
        $response->assertJson([
            'checkout_url' => 'https://checkout.stripe.com/pay/cs_3ds_test',
            'requires_action' => true,
        ]);
    }

    /**
     * Test: Payment completes after authentication
     */
    public function test_payment_completes_after_authentication(): void
    {
        // Simulate scenario where 3DS is completed and webhook received
        $this->club->update([
            'stripe_customer_id' => 'cus_3ds_completed',
            'stripe_subscription_id' => 'sub_3ds_completed',
            'subscription_status' => 'active',
            'club_subscription_plan_id' => $this->plan->id,
        ]);

        // Act: Check subscription status after 3DS completion
        $response = $this->actingAs($this->user)
            ->get(route('club.subscription.index', ['club' => $this->club->id]));

        // Assert: Subscription is active
        $response->assertStatus(200);
        $this->assertCheckoutSuccessful($this->club, $this->plan);
    }

    // ==========================================
    // GROUP 4: GERMAN PAYMENT METHODS (2 tests)
    // ==========================================

    /**
     * Test: SEPA Direct Debit payment method works
     */
    public function test_sepa_direct_debit_payment_method_works(): void
    {
        // Setup mock session with SEPA
        $mockSession = $this->createMockCheckoutSession([
            'id' => 'cs_sepa_test',
            'payment_method_types' => ['sepa_debit'],
        ]);

        // Mock Stripe API
        $mockCheckout = Mockery::mock();
        $mockSessions = Mockery::mock();
        $this->mockClient->checkout = $mockCheckout;
        $mockCheckout->sessions = $mockSessions;

        $mockSessions->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return in_array('sepa_debit', $data['payment_method_types']);
            }))
            ->andReturn($mockSession);

        // Act: Initiate checkout with SEPA
        $response = $this->actingAs($this->user)
            ->postJson(route('club.checkout', ['club' => $this->club->id]), [
                'plan_id' => $this->plan->id,
                'billing_interval' => 'monthly',
                'payment_method_types' => ['card', 'sepa_debit'],
            ]);

        // Assert
        $response->assertStatus(200);
    }

    /**
     * Test: German payment metadata is stored
     */
    public function test_german_payment_metadata_is_stored(): void
    {
        // Setup mock with German locale metadata
        $mockSession = $this->createMockCheckoutSession();
        $mockCheckout = Mockery::mock();
        $mockSessions = Mockery::mock();
        $this->mockClient->checkout = $mockCheckout;
        $mockCheckout->sessions = $mockSessions;

        $mockSessions->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['locale'] === 'de'
                    && $data['currency'] === 'eur'
                    && in_array('sepa_debit', $data['payment_method_types']);
            }))
            ->andReturn($mockSession);

        // Act
        $response = $this->actingAs($this->user)
            ->postJson(route('club.checkout', ['club' => $this->club->id]), [
                'plan_id' => $this->plan->id,
                'billing_interval' => 'monthly',
            ]);

        // Assert
        $response->assertStatus(200);
    }

    // ==========================================
    // GROUP 5: SYSTEM TESTS (5 tests)
    // ==========================================

    /**
     * Test: Billing portal accessible for active subscriptions
     */
    public function test_billing_portal_accessible_for_active_subscriptions(): void
    {
        // Setup club with active subscription
        $this->club->update([
            'stripe_customer_id' => 'cus_portal_test',
            'stripe_subscription_id' => 'sub_portal_test',
            'subscription_status' => 'active',
        ]);

        // Mock billing portal session
        $mockPortalSession = $this->createMockBillingPortalSession();
        $mockBillingPortal = Mockery::mock();
        $mockPortalSessions = Mockery::mock();
        $this->mockClient->billingPortal = $mockBillingPortal;
        $mockBillingPortal->sessions = $mockPortalSessions;

        $mockPortalSessions->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['customer'] === 'cus_portal_test';
            }))
            ->andReturn($mockPortalSession);

        // Act: Access billing portal
        $response = $this->actingAs($this->user)
            ->postJson(route('club.billing-portal', ['club' => $this->club->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'url' => 'https://billing.stripe.com/session/test',
        ]);
    }

    /**
     * Test: Checkout respects tenant isolation
     */
    public function test_checkout_respects_tenant_isolation(): void
    {
        // Create another tenant with club
        $otherTenant = Tenant::factory()->create();
        $otherClub = Club::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherPlan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $otherTenant->id,
            'is_active' => true,
            'is_stripe_synced' => true,
        ]);

        // Act: Try to checkout other tenant's club with our plan (cross-tenant attack)
        $response = $this->actingAs($this->user)
            ->postJson(route('club.checkout', ['club' => $otherClub->id]), [
                'plan_id' => $this->plan->id, // Our tenant's plan
                'billing_interval' => 'monthly',
            ]);

        // Assert: Forbidden (plan doesn't belong to club's tenant)
        $response->assertStatus(403);
    }

    /**
     * Test: Inactive plans cannot be checked out
     */
    public function test_inactive_plans_cannot_be_checked_out(): void
    {
        // Deactivate plan
        $this->plan->update(['is_active' => false]);

        // Act: Try to checkout inactive plan
        $response = $this->actingAs($this->user)
            ->postJson(route('club.checkout', ['club' => $this->club->id]), [
                'plan_id' => $this->plan->id,
                'billing_interval' => 'monthly',
            ]);

        // Assert: Validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['plan_id']);
    }

    /**
     * Test: Pricing differs between monthly and yearly
     */
    public function test_pricing_differs_between_monthly_and_yearly(): void
    {
        // Monthly price: 49.99
        // Yearly price should be: 49.99 * 12 * 0.9 = 539.89 (10% discount)

        // Setup mocks for both billing intervals
        $mockSessionMonthly = $this->createMockCheckoutSession(['id' => 'cs_monthly']);
        $mockSessionYearly = $this->createMockCheckoutSession(['id' => 'cs_yearly']);

        $mockCheckout = Mockery::mock();
        $mockSessions = Mockery::mock();
        $this->mockClient->checkout = $mockCheckout;
        $mockCheckout->sessions = $mockSessions;

        // Monthly checkout
        $mockSessions->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['line_items'][0]['price'] === $this->plan->stripe_price_id_monthly;
            }))
            ->andReturn($mockSessionMonthly);

        $responseMonthly = $this->actingAs($this->user)
            ->postJson(route('club.checkout', ['club' => $this->club->id]), [
                'plan_id' => $this->plan->id,
                'billing_interval' => 'monthly',
            ]);

        // Yearly checkout (new mock)
        $mockSessions->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['line_items'][0]['price'] === $this->plan->stripe_price_id_yearly;
            }))
            ->andReturn($mockSessionYearly);

        $responseYearly = $this->actingAs($this->user)
            ->postJson(route('club.checkout', ['club' => $this->club->id]), [
                'plan_id' => $this->plan->id,
                'billing_interval' => 'yearly',
            ]);

        // Assert: Both succeeded but used different price IDs
        $responseMonthly->assertStatus(200);
        $responseYearly->assertStatus(200);
    }

    /**
     * Test: Unauthorized users cannot initiate checkout
     */
    public function test_unauthorized_users_cannot_initiate_checkout(): void
    {
        // Create user without club admin role
        $unauthorizedUser = User::factory()->create();

        // Act: Try to checkout without permission
        $response = $this->actingAs($unauthorizedUser)
            ->postJson(route('club.checkout', ['club' => $this->club->id]), [
                'plan_id' => $this->plan->id,
                'billing_interval' => 'monthly',
            ]);

        // Assert: Forbidden
        $response->assertStatus(403);
    }
}
