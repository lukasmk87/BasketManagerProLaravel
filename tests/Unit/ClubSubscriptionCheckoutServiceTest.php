<?php

namespace Tests\Unit;

use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use App\Models\Tenant;
use App\Services\Stripe\ClubStripeCustomerService;
use App\Services\Stripe\ClubSubscriptionCheckoutService;
use App\Services\Stripe\StripeClientManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\BillingPortal\Session as BillingPortalSession;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Exception\InvalidRequestException;
use Stripe\StripeClient;
use Tests\TestCase;

class ClubSubscriptionCheckoutServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ClubSubscriptionCheckoutService $service;

    protected $mockClientManager;

    protected $mockCustomerService;

    protected $mockStripeClient;

    protected $mockCheckoutSessionsService;

    protected $mockBillingPortalSessionsService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock checkout sessions service
        $this->mockCheckoutSessionsService = Mockery::mock();

        // Create mock billing portal sessions service
        $this->mockBillingPortalSessionsService = Mockery::mock();

        // Create mock Stripe client
        $this->mockStripeClient = Mockery::mock(StripeClient::class);
        $this->mockStripeClient->checkout = (object) ['sessions' => $this->mockCheckoutSessionsService];
        $this->mockStripeClient->billingPortal = (object) ['sessions' => $this->mockBillingPortalSessionsService];

        // Create mock StripeClientManager
        $this->mockClientManager = Mockery::mock(StripeClientManager::class);
        $this->mockClientManager->shouldReceive('getCurrentTenantClient')
            ->andReturn($this->mockStripeClient);

        // Create mock ClubStripeCustomerService
        $this->mockCustomerService = Mockery::mock(ClubStripeCustomerService::class);

        // Create service with mocked dependencies
        $this->service = new ClubSubscriptionCheckoutService(
            $this->mockClientManager,
            $this->mockCustomerService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_checkout_session_with_monthly_billing()
    {
        $tenant = Tenant::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'is_stripe_synced' => true,
            'stripe_product_id' => 'prod_test_123',
            'stripe_price_id_monthly' => 'price_monthly_123',
            'stripe_price_id_yearly' => 'price_yearly_123',
            'trial_period_days' => 0,
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Club',
            'email' => 'test@club.com',
        ]);

        // Mock customer creation
        $mockCustomer = Mockery::mock(Customer::class);
        $mockCustomer->id = 'cus_test_123';

        $this->mockCustomerService->shouldReceive('getOrCreateCustomer')
            ->once()
            ->with($club)
            ->andReturn($mockCustomer);

        // Mock checkout session creation
        $mockSession = Mockery::mock(Session::class);
        $mockSession->id = 'cs_test_123';

        $this->mockCheckoutSessionsService->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) use ($club, $plan) {
                return $data['mode'] === 'subscription'
                    && $data['customer'] === 'cus_test_123'
                    && $data['line_items'][0]['price'] === 'price_monthly_123'
                    && $data['metadata']['club_id'] === $club->id
                    && $data['metadata']['club_subscription_plan_id'] === $plan->id
                    && $data['metadata']['billing_interval'] === 'monthly';
            }))
            ->andReturn($mockSession);

        $result = $this->service->createCheckoutSession($club, $plan);

        $this->assertEquals('cs_test_123', $result->id);
    }

    /** @test */
    public function it_creates_checkout_session_with_yearly_billing()
    {
        $tenant = Tenant::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'is_stripe_synced' => true,
            'stripe_product_id' => 'prod_test_123',
            'stripe_price_id_monthly' => 'price_monthly_123',
            'stripe_price_id_yearly' => 'price_yearly_123',
            'trial_period_days' => 0,
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $mockCustomer = Mockery::mock(Customer::class);
        $mockCustomer->id = 'cus_test_456';

        $this->mockCustomerService->shouldReceive('getOrCreateCustomer')
            ->once()
            ->andReturn($mockCustomer);

        $mockSession = Mockery::mock(Session::class);
        $mockSession->id = 'cs_test_456';

        $this->mockCheckoutSessionsService->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['line_items'][0]['price'] === 'price_yearly_123'
                    && $data['metadata']['billing_interval'] === 'yearly';
            }))
            ->andReturn($mockSession);

        $result = $this->service->createCheckoutSession($club, $plan, [
            'billing_interval' => 'yearly',
        ]);

        $this->assertEquals('cs_test_456', $result->id);
    }

    /** @test */
    public function it_includes_trial_period_when_configured()
    {
        $tenant = Tenant::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'is_stripe_synced' => true,
            'stripe_price_id_monthly' => 'price_monthly_123',
            'trial_period_days' => 14,
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $mockCustomer = Mockery::mock(Customer::class);
        $mockCustomer->id = 'cus_test_789';

        $this->mockCustomerService->shouldReceive('getOrCreateCustomer')
            ->once()
            ->andReturn($mockCustomer);

        $mockSession = Mockery::mock(Session::class);
        $mockSession->id = 'cs_test_789';

        $this->mockCheckoutSessionsService->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return isset($data['subscription_data']['trial_period_days'])
                    && $data['subscription_data']['trial_period_days'] === 14;
            }))
            ->andReturn($mockSession);

        $result = $this->service->createCheckoutSession($club, $plan);

        $this->assertEquals('cs_test_789', $result->id);
    }

    /** @test */
    public function it_throws_exception_for_mismatched_tenant()
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant1->id,
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $tenant2->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Plan does not belong to club\'s tenant');

        $this->service->createCheckoutSession($club, $plan);
    }

    /** @test */
    public function it_throws_exception_for_inactive_plan()
    {
        $tenant = Tenant::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => false,
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Plan is not active');

        $this->service->createCheckoutSession($club, $plan);
    }

    /** @test */
    public function it_throws_exception_for_unsynced_plan()
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

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Plan is not synced with Stripe');

        $this->service->createCheckoutSession($club, $plan);
    }

    /** @test */
    public function it_throws_exception_when_missing_price_id()
    {
        $tenant = Tenant::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'is_stripe_synced' => true,
            'stripe_price_id_monthly' => null,
            'stripe_price_id_yearly' => null,
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No Stripe Price ID for monthly billing');

        $this->service->createCheckoutSession($club, $plan);
    }

    /** @test */
    public function it_uses_billing_email_when_available()
    {
        $tenant = Tenant::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'is_stripe_synced' => true,
            'stripe_price_id_monthly' => 'price_monthly_123',
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'regular@club.com',
            'billing_email' => 'billing@club.com',
        ]);

        $mockCustomer = Mockery::mock(Customer::class);
        $mockCustomer->id = 'cus_test_123';

        $this->mockCustomerService->shouldReceive('getOrCreateCustomer')
            ->once()
            ->andReturn($mockCustomer);

        $mockSession = Mockery::mock(Session::class);
        $mockSession->id = 'cs_test_123';

        $this->mockCheckoutSessionsService->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['customer_email'] === 'billing@club.com';
            }))
            ->andReturn($mockSession);

        $this->service->createCheckoutSession($club, $plan);
    }

    /** @test */
    public function it_creates_billing_portal_session()
    {
        $tenant = Tenant::factory()->create();
        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'stripe_customer_id' => 'cus_test_123',
        ]);

        $mockPortalSession = Mockery::mock(BillingPortalSession::class);
        $mockPortalSession->id = 'bps_test_123';

        $this->mockBillingPortalSessionsService->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['customer'] === 'cus_test_123'
                    && $data['locale'] === 'de'
                    && isset($data['return_url']);
            }))
            ->andReturn($mockPortalSession);

        $result = $this->service->createPortalSession($club, 'https://example.com/return');

        $this->assertEquals('bps_test_123', $result->id);
    }

    /** @test */
    public function it_throws_exception_when_creating_portal_without_customer()
    {
        $tenant = Tenant::factory()->create();
        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'stripe_customer_id' => null,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Club has no Stripe Customer');

        $this->service->createPortalSession($club, 'https://example.com/return');
    }

    /** @test */
    public function it_includes_correct_metadata_in_checkout_session()
    {
        $tenant = Tenant::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'is_stripe_synced' => true,
            'stripe_price_id_monthly' => 'price_monthly_123',
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $mockCustomer = Mockery::mock(Customer::class);
        $mockCustomer->id = 'cus_test_123';

        $this->mockCustomerService->shouldReceive('getOrCreateCustomer')
            ->once()
            ->andReturn($mockCustomer);

        $mockSession = Mockery::mock(Session::class);
        $mockSession->id = 'cs_test_123';

        $this->mockCheckoutSessionsService->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) use ($club, $plan, $tenant) {
                return isset($data['metadata'])
                    && $data['metadata']['club_id'] === $club->id
                    && $data['metadata']['club_uuid'] === $club->uuid
                    && $data['metadata']['club_subscription_plan_id'] === $plan->id
                    && $data['metadata']['tenant_id'] === $tenant->id
                    && isset($data['subscription_data']['metadata'])
                    && $data['subscription_data']['metadata']['club_id'] === $club->id
                    && $data['subscription_data']['metadata']['plan_id'] === $plan->id;
            }))
            ->andReturn($mockSession);

        $this->service->createCheckoutSession($club, $plan);
    }

    /** @test */
    public function it_handles_stripe_api_errors_gracefully()
    {
        $tenant = Tenant::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'is_stripe_synced' => true,
            'stripe_price_id_monthly' => 'price_monthly_123',
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $mockCustomer = Mockery::mock(Customer::class);
        $mockCustomer->id = 'cus_test_123';

        $this->mockCustomerService->shouldReceive('getOrCreateCustomer')
            ->once()
            ->andReturn($mockCustomer);

        $this->mockCheckoutSessionsService->shouldReceive('create')
            ->once()
            ->andThrow(new InvalidRequestException('Stripe API Error'));

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Stripe API Error');

        $this->service->createCheckoutSession($club, $plan);
    }
}
