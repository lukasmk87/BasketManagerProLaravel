<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use App\Models\Tenant;
use App\Services\Stripe\ClubSubscriptionCheckoutService;
use App\Services\Stripe\StripeClientManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\BillingPortal\Session as BillingPortalSession;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\StripeClient;
use Tests\TestCase;

class ClubSubscriptionCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected $mockClientManager;

    protected $mockStripeClient;

    protected $mockCheckoutSessionsService;

    protected $mockBillingPortalSessionsService;

    protected $mockCustomersService;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup mocked Stripe services
        $this->mockCustomersService = Mockery::mock();
        $this->mockCheckoutSessionsService = Mockery::mock();
        $this->mockBillingPortalSessionsService = Mockery::mock();

        $this->mockStripeClient = Mockery::mock(StripeClient::class);
        $this->mockStripeClient->customers = $this->mockCustomersService;
        $this->mockStripeClient->checkout = (object) ['sessions' => $this->mockCheckoutSessionsService];
        $this->mockStripeClient->billingPortal = (object) ['sessions' => $this->mockBillingPortalSessionsService];

        $this->mockClientManager = Mockery::mock(StripeClientManager::class);
        $this->mockClientManager->shouldReceive('getCurrentTenantClient')
            ->andReturn($this->mockStripeClient);

        // Bind mocked StripeClientManager to container
        $this->app->instance(StripeClientManager::class, $this->mockClientManager);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function complete_checkout_flow_for_club_subscription()
    {
        // Create tenant and plan
        $tenant = Tenant::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Premium Club Plan',
            'price' => 149.00,
            'is_active' => true,
            'is_stripe_synced' => true,
            'stripe_product_id' => 'prod_test_123',
            'stripe_price_id_monthly' => 'price_monthly_123',
            'stripe_price_id_yearly' => 'price_yearly_456',
            'trial_period_days' => 14,
        ]);

        // Create club
        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Basketball Club',
            'email' => 'club@test.com',
            'billing_email' => 'billing@test.com',
        ]);

        // Mock Stripe customer creation
        $mockCustomer = Mockery::mock(Customer::class);
        $mockCustomer->id = 'cus_test_123';

        $this->mockCustomersService->shouldReceive('create')
            ->once()
            ->andReturn($mockCustomer);

        // Mock checkout session creation
        $mockSession = Mockery::mock(Session::class);
        $mockSession->id = 'cs_test_123';
        $mockSession->url = 'https://checkout.stripe.com/pay/cs_test_123';

        $this->mockCheckoutSessionsService->shouldReceive('create')
            ->once()
            ->andReturn($mockSession);

        // Create checkout session
        $service = app(ClubSubscriptionCheckoutService::class);
        $session = $service->createCheckoutSession($club, $plan);

        // Verify session was created
        $this->assertEquals('cs_test_123', $session->id);
        $this->assertNotNull($session->url);

        // Verify club was updated with Stripe customer ID
        $club->refresh();
        $this->assertEquals('cus_test_123', $club->stripe_customer_id);
    }

    /** @test */
    public function checkout_with_trial_period()
    {
        $tenant = Tenant::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'is_stripe_synced' => true,
            'stripe_price_id_monthly' => 'price_monthly_trial',
            'trial_period_days' => 30,
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        // Mock customer and session
        $mockCustomer = Mockery::mock(Customer::class);
        $mockCustomer->id = 'cus_trial_123';

        $this->mockCustomersService->shouldReceive('create')
            ->once()
            ->andReturn($mockCustomer);

        $mockSession = Mockery::mock(Session::class);
        $mockSession->id = 'cs_trial_123';

        $this->mockCheckoutSessionsService->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return isset($data['subscription_data']['trial_period_days'])
                    && $data['subscription_data']['trial_period_days'] === 30;
            }))
            ->andReturn($mockSession);

        $service = app(ClubSubscriptionCheckoutService::class);
        $session = $service->createCheckoutSession($club, $plan);

        $this->assertEquals('cs_trial_123', $session->id);
    }

    /** @test */
    public function multiple_clubs_can_checkout_different_plans()
    {
        $tenant = Tenant::factory()->create();

        // Create two different plans
        $basicPlan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Basic Plan',
            'price' => 49.00,
            'is_active' => true,
            'is_stripe_synced' => true,
            'stripe_price_id_monthly' => 'price_basic_123',
        ]);

        $premiumPlan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Premium Plan',
            'price' => 149.00,
            'is_active' => true,
            'is_stripe_synced' => true,
            'stripe_price_id_monthly' => 'price_premium_456',
        ]);

        // Create two clubs
        $club1 = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Club 1',
        ]);

        $club2 = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Club 2',
        ]);

        // Mock Stripe for club 1 (basic plan)
        $mockCustomer1 = Mockery::mock(Customer::class);
        $mockCustomer1->id = 'cus_club1_123';

        $mockSession1 = Mockery::mock(Session::class);
        $mockSession1->id = 'cs_club1_123';

        // Mock Stripe for club 2 (premium plan)
        $mockCustomer2 = Mockery::mock(Customer::class);
        $mockCustomer2->id = 'cus_club2_456';

        $mockSession2 = Mockery::mock(Session::class);
        $mockSession2->id = 'cs_club2_456';

        $this->mockCustomersService->shouldReceive('create')
            ->twice()
            ->andReturn($mockCustomer1, $mockCustomer2);

        $this->mockCheckoutSessionsService->shouldReceive('create')
            ->twice()
            ->andReturn($mockSession1, $mockSession2);

        $service = app(ClubSubscriptionCheckoutService::class);

        // Club 1 checkouts basic plan
        $session1 = $service->createCheckoutSession($club1, $basicPlan);
        $this->assertEquals('cs_club1_123', $session1->id);

        // Club 2 checkouts premium plan
        $session2 = $service->createCheckoutSession($club2, $premiumPlan);
        $this->assertEquals('cs_club2_456', $session2->id);

        // Verify both clubs have different customer IDs
        $club1->refresh();
        $club2->refresh();
        $this->assertEquals('cus_club1_123', $club1->stripe_customer_id);
        $this->assertEquals('cus_club2_456', $club2->stripe_customer_id);
    }

    /** @test */
    public function billing_portal_session_for_subscription_management()
    {
        $tenant = Tenant::factory()->create();
        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'stripe_customer_id' => 'cus_existing_123',
            'stripe_subscription_id' => 'sub_existing_123',
        ]);

        // Mock billing portal session
        $mockPortalSession = Mockery::mock(BillingPortalSession::class);
        $mockPortalSession->id = 'bps_test_123';
        $mockPortalSession->url = 'https://billing.stripe.com/session/bps_test_123';

        $this->mockBillingPortalSessionsService->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['customer'] === 'cus_existing_123';
            }))
            ->andReturn($mockPortalSession);

        $service = app(ClubSubscriptionCheckoutService::class);
        $session = $service->createPortalSession($club, 'https://example.com/return');

        $this->assertEquals('bps_test_123', $session->id);
        $this->assertNotNull($session->url);
    }

    /** @test */
    public function tenant_isolation_prevents_cross_tenant_checkout()
    {
        // Create two separate tenants
        $tenant1 = Tenant::factory()->create(['name' => 'Tenant 1']);
        $tenant2 = Tenant::factory()->create(['name' => 'Tenant 2']);

        // Create plan for tenant 1
        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant1->id,
            'is_active' => true,
            'is_stripe_synced' => true,
        ]);

        // Create club in tenant 2
        $club = Club::factory()->create([
            'tenant_id' => $tenant2->id,
        ]);

        $service = app(ClubSubscriptionCheckoutService::class);

        // Attempt to checkout should fail
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Plan does not belong to club\'s tenant');

        $service->createCheckoutSession($club, $plan);
    }

    /** @test */
    public function inactive_plans_are_rejected()
    {
        $tenant = Tenant::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => false,
            'is_stripe_synced' => true,
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $service = app(ClubSubscriptionCheckoutService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Plan is not active');

        $service->createCheckoutSession($club, $plan);
    }

    /** @test */
    public function unsynced_plans_are_rejected()
    {
        $tenant = Tenant::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'is_stripe_synced' => false,
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $service = app(ClubSubscriptionCheckoutService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Plan is not synced with Stripe');

        $service->createCheckoutSession($club, $plan);
    }

    /** @test */
    public function yearly_billing_uses_correct_price_id()
    {
        $tenant = Tenant::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'is_stripe_synced' => true,
            'stripe_price_id_monthly' => 'price_monthly_123',
            'stripe_price_id_yearly' => 'price_yearly_456',
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        // Mock customer
        $mockCustomer = Mockery::mock(Customer::class);
        $mockCustomer->id = 'cus_yearly_123';

        $this->mockCustomersService->shouldReceive('create')
            ->once()
            ->andReturn($mockCustomer);

        // Mock session - verify it uses yearly price
        $mockSession = Mockery::mock(Session::class);
        $mockSession->id = 'cs_yearly_123';

        $this->mockCheckoutSessionsService->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['line_items'][0]['price'] === 'price_yearly_456'
                    && $data['metadata']['billing_interval'] === 'yearly';
            }))
            ->andReturn($mockSession);

        $service = app(ClubSubscriptionCheckoutService::class);
        $session = $service->createCheckoutSession($club, $plan, [
            'billing_interval' => 'yearly',
        ]);

        $this->assertEquals('cs_yearly_123', $session->id);
    }
}
