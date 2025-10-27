<?php

namespace Tests\Unit;

use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use App\Models\Tenant;
use App\Services\Stripe\ClubStripeCustomerService;
use App\Services\Stripe\ClubSubscriptionService;
use App\Services\Stripe\StripeClientManager;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\Exception\InvalidRequestException;
use Stripe\Product;
use Stripe\StripeClient;
use Stripe\Subscription;
use Tests\TestCase;

class ClubSubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ClubSubscriptionService $service;

    protected $mockClientManager;

    protected $mockCustomerService;

    protected $mockStripeClient;

    protected $mockSubscriptionsService;

    protected $mockProductsService;

    protected $mockPricesService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock services
        $this->mockSubscriptionsService = Mockery::mock();
        $this->mockProductsService = Mockery::mock();
        $this->mockPricesService = Mockery::mock();

        // Create mock Stripe client
        $this->mockStripeClient = Mockery::mock(StripeClient::class);
        $this->mockStripeClient->subscriptions = $this->mockSubscriptionsService;
        $this->mockStripeClient->products = $this->mockProductsService;
        $this->mockStripeClient->prices = $this->mockPricesService;

        // Create mock StripeClientManager
        $this->mockClientManager = Mockery::mock(StripeClientManager::class);
        $this->mockClientManager->shouldReceive('getCurrentTenantClient')
            ->andReturn($this->mockStripeClient);

        // Create mock ClubStripeCustomerService
        $this->mockCustomerService = Mockery::mock(ClubStripeCustomerService::class);

        // Create service with mocked dependencies
        $this->service = new ClubSubscriptionService(
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
    public function it_assigns_plan_to_club()
    {
        $tenant = Tenant::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Premium Plan',
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'club_subscription_plan_id' => null,
        ]);

        $this->service->assignPlanToClub($club, $plan);

        $club->refresh();
        $this->assertEquals($plan->id, $club->club_subscription_plan_id);
    }

    /** @test */
    public function it_throws_exception_for_mismatched_tenant_on_assignment()
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
        $this->expectExceptionMessage("Plan does not belong to club's tenant");

        $this->service->assignPlanToClub($club, $plan);
    }

    /** @test */
    public function it_cancels_subscription_immediately()
    {
        $tenant = Tenant::factory()->create();
        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'stripe_subscription_id' => 'sub_test_123',
            'subscription_status' => 'active',
            'club_subscription_plan_id' => 1,
        ]);

        $mockSubscription = Mockery::mock(Subscription::class);
        $mockSubscription->id = 'sub_test_123';
        $mockSubscription->status = 'canceled';

        $this->mockSubscriptionsService->shouldReceive('cancel')
            ->once()
            ->with('sub_test_123')
            ->andReturn($mockSubscription);

        $this->service->cancelSubscription($club, true);

        $club->refresh();
        $this->assertEquals('canceled', $club->subscription_status);
        $this->assertNull($club->club_subscription_plan_id);
        $this->assertNotNull($club->subscription_ends_at);
    }

    /** @test */
    public function it_cancels_subscription_at_period_end()
    {
        $tenant = Tenant::factory()->create();
        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'stripe_subscription_id' => 'sub_test_456',
            'subscription_status' => 'active',
        ]);

        $mockSubscription = Mockery::mock(Subscription::class);
        $mockSubscription->id = 'sub_test_456';
        $mockSubscription->status = 'active';
        $mockSubscription->current_period_end = Carbon::now()->addMonth()->timestamp;
        $mockSubscription->cancel_at_period_end = true;

        $this->mockSubscriptionsService->shouldReceive('update')
            ->once()
            ->with('sub_test_456', ['cancel_at_period_end' => true])
            ->andReturn($mockSubscription);

        $this->service->cancelSubscription($club, false);

        $club->refresh();
        $this->assertEquals('active', $club->subscription_status);
        $this->assertNotNull($club->subscription_ends_at);
    }

    /** @test */
    public function it_throws_exception_when_canceling_without_subscription()
    {
        $tenant = Tenant::factory()->create();
        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'stripe_subscription_id' => null,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Club has no active subscription');

        $this->service->cancelSubscription($club);
    }

    /** @test */
    public function it_resumes_canceled_subscription()
    {
        $tenant = Tenant::factory()->create();
        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'stripe_subscription_id' => 'sub_test_789',
            'subscription_status' => 'active',
            'subscription_ends_at' => Carbon::now()->addMonth(),
        ]);

        $mockSubscription = Mockery::mock(Subscription::class);
        $mockSubscription->id = 'sub_test_789';
        $mockSubscription->status = 'active';
        $mockSubscription->cancel_at_period_end = false;

        $this->mockSubscriptionsService->shouldReceive('update')
            ->once()
            ->with('sub_test_789', ['cancel_at_period_end' => false])
            ->andReturn($mockSubscription);

        $this->service->resumeSubscription($club);

        $club->refresh();
        $this->assertEquals('active', $club->subscription_status);
        $this->assertNull($club->subscription_ends_at);
    }

    /** @test */
    public function it_throws_exception_when_resuming_without_subscription()
    {
        $tenant = Tenant::factory()->create();
        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'stripe_subscription_id' => null,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Club has no subscription to resume');

        $this->service->resumeSubscription($club);
    }

    /** @test */
    public function it_swaps_plan_with_proration()
    {
        $tenant = Tenant::factory()->create();
        $oldPlan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'stripe_price_id_monthly' => 'price_old_123',
        ]);

        $newPlan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'is_stripe_synced' => true,
            'stripe_price_id_monthly' => 'price_new_456',
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'stripe_subscription_id' => 'sub_test_swap',
            'club_subscription_plan_id' => $oldPlan->id,
        ]);

        // Mock subscription retrieval
        $mockSubscription = Mockery::mock(Subscription::class);
        $mockItem = Mockery::mock();
        $mockItem->id = 'si_test_123';
        $mockPrice = Mockery::mock();
        $mockPrice->id = 'price_old_123';
        $mockItem->price = $mockPrice;
        $mockSubscription->items = (object) ['data' => [$mockItem]];

        $this->mockSubscriptionsService->shouldReceive('retrieve')
            ->once()
            ->with('sub_test_swap')
            ->andReturn($mockSubscription);

        // Mock subscription update
        $mockUpdatedSubscription = Mockery::mock(Subscription::class);

        $this->mockSubscriptionsService->shouldReceive('update')
            ->once()
            ->with('sub_test_swap', Mockery::on(function ($data) {
                return $data['items'][0]['id'] === 'si_test_123'
                    && $data['items'][0]['price'] === 'price_new_456'
                    && $data['proration_behavior'] === 'create_prorations';
            }))
            ->andReturn($mockUpdatedSubscription);

        $this->service->swapPlan($club, $newPlan);

        $club->refresh();
        $this->assertEquals($newPlan->id, $club->club_subscription_plan_id);
    }

    /** @test */
    public function it_throws_exception_for_swap_without_active_subscription()
    {
        $tenant = Tenant::factory()->create();
        $newPlan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'is_stripe_synced' => true,
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'stripe_subscription_id' => null,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Club must have active subscription to swap plans');

        $this->service->swapPlan($club, $newPlan);
    }

    /** @test */
    public function it_validates_tenant_match_on_swap()
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $newPlan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant2->id,
            'is_stripe_synced' => true,
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $tenant1->id,
            'stripe_subscription_id' => 'sub_test_123',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("New plan does not belong to club's tenant");

        $this->service->swapPlan($club, $newPlan);
    }

    /** @test */
    public function it_validates_plan_is_synced_on_swap()
    {
        $tenant = Tenant::factory()->create();
        $newPlan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'is_stripe_synced' => false,
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'stripe_subscription_id' => 'sub_test_123',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('New plan is not synced with Stripe');

        $this->service->swapPlan($club, $newPlan);
    }

    /** @test */
    public function it_syncs_plan_with_stripe_creates_product_and_prices()
    {
        $tenant = Tenant::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Premium Club Plan',
            'description' => 'Full featured plan',
            'price' => 99.00,
            'currency' => 'EUR',
            'stripe_product_id' => null,
        ]);

        // Mock product creation
        $mockProduct = Mockery::mock(Product::class);
        $mockProduct->id = 'prod_test_123';

        $this->mockProductsService->shouldReceive('create')
            ->once()
            ->andReturn($mockProduct);

        // Mock monthly price creation
        $mockPriceMonthly = Mockery::mock(\Stripe\Price::class);
        $mockPriceMonthly->id = 'price_monthly_123';

        $this->mockPricesService->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['unit_amount'] === 9900
                    && $data['recurring']['interval'] === 'month';
            }))
            ->andReturn($mockPriceMonthly);

        // Mock yearly price creation
        $mockPriceYearly = Mockery::mock(\Stripe\Price::class);
        $mockPriceYearly->id = 'price_yearly_456';

        $this->mockPricesService->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['unit_amount'] === 10692 // 99 * 12 * 0.9 * 100
                    && $data['recurring']['interval'] === 'year';
            }))
            ->andReturn($mockPriceYearly);

        $result = $this->service->syncPlanWithStripe($plan);

        $this->assertEquals('prod_test_123', $result['product']->id);
        $this->assertEquals('price_monthly_123', $result['price_monthly']->id);
        $this->assertEquals('price_yearly_456', $result['price_yearly']->id);

        $plan->refresh();
        $this->assertTrue($plan->is_stripe_synced);
        $this->assertEquals('prod_test_123', $plan->stripe_product_id);
        $this->assertEquals('price_monthly_123', $plan->stripe_price_id_monthly);
        $this->assertEquals('price_yearly_456', $plan->stripe_price_id_yearly);
    }

    /** @test */
    public function it_updates_existing_product_on_resync()
    {
        $tenant = Tenant::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Updated Plan Name',
            'price' => 49.00,
            'stripe_product_id' => 'prod_existing_123',
            'stripe_price_id_monthly' => 'price_existing_monthly',
            'stripe_price_id_yearly' => 'price_existing_yearly',
        ]);

        // Mock product update
        $mockProduct = Mockery::mock(Product::class);
        $mockProduct->id = 'prod_existing_123';

        $this->mockProductsService->shouldReceive('update')
            ->once()
            ->with('prod_existing_123', Mockery::on(function ($data) {
                return $data['name'] === 'Updated Plan Name';
            }))
            ->andReturn($mockProduct);

        // Mock price retrieval (existing prices)
        $mockPriceMonthly = Mockery::mock(\Stripe\Price::class);
        $mockPriceYearly = Mockery::mock(\Stripe\Price::class);

        $this->mockPricesService->shouldReceive('retrieve')
            ->twice()
            ->andReturn($mockPriceMonthly, $mockPriceYearly);

        $result = $this->service->syncPlanWithStripe($plan);

        $this->assertEquals('prod_existing_123', $result['product']->id);
    }

    /** @test */
    public function it_creates_yearly_price_with_10_percent_discount()
    {
        $tenant = Tenant::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'price' => 100.00, // €100/month
            'stripe_product_id' => null,
        ]);

        $mockProduct = Mockery::mock(Product::class);
        $mockProduct->id = 'prod_test';

        $this->mockProductsService->shouldReceive('create')
            ->once()
            ->andReturn($mockProduct);

        $mockPriceMonthly = Mockery::mock(\Stripe\Price::class);
        $mockPriceMonthly->id = 'price_monthly';

        // Expect monthly price: 100 * 100 = 10000 cents
        $this->mockPricesService->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['unit_amount'] === 10000;
            }))
            ->andReturn($mockPriceMonthly);

        $mockPriceYearly = Mockery::mock(\Stripe\Price::class);
        $mockPriceYearly->id = 'price_yearly';

        // Expect yearly price: 100 * 12 * 0.9 * 100 = 108000 cents (€1080 instead of €1200)
        $this->mockPricesService->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['unit_amount'] === 108000;
            }))
            ->andReturn($mockPriceYearly);

        $this->service->syncPlanWithStripe($plan);
    }

    /** @test */
    public function it_handles_free_plans_without_creating_prices()
    {
        $tenant = Tenant::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'price' => 0.00, // Free plan
            'stripe_product_id' => null,
        ]);

        $mockProduct = Mockery::mock(Product::class);
        $mockProduct->id = 'prod_free_123';

        $this->mockProductsService->shouldReceive('create')
            ->once()
            ->andReturn($mockProduct);

        // Should NOT create any prices
        $this->mockPricesService->shouldNotReceive('create');

        $result = $this->service->syncPlanWithStripe($plan);

        $this->assertEquals('prod_free_123', $result['product']->id);
        $this->assertNull($result['price_monthly']);
        $this->assertNull($result['price_yearly']);
    }

    /** @test */
    public function it_handles_stripe_api_errors_gracefully()
    {
        $tenant = Tenant::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'stripe_product_id' => null,
        ]);

        $this->mockProductsService->shouldReceive('create')
            ->once()
            ->andThrow(new InvalidRequestException('Stripe API Error'));

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Stripe API Error');

        $this->service->syncPlanWithStripe($plan);
    }
}
