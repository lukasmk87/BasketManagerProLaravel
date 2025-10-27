<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use App\Models\Tenant;
use App\Services\Stripe\ClubSubscriptionService;
use App\Services\Stripe\StripeClientManager;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\Price;
use Stripe\Product;
use Stripe\StripeClient;
use Stripe\Subscription;
use Tests\TestCase;

class ClubSubscriptionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected $mockClientManager;

    protected $mockStripeClient;

    protected $mockSubscriptionsService;

    protected $mockProductsService;

    protected $mockPricesService;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup mocked Stripe services
        $this->mockSubscriptionsService = Mockery::mock();
        $this->mockProductsService = Mockery::mock();
        $this->mockPricesService = Mockery::mock();

        $this->mockStripeClient = Mockery::mock(StripeClient::class);
        $this->mockStripeClient->subscriptions = $this->mockSubscriptionsService;
        $this->mockStripeClient->products = $this->mockProductsService;
        $this->mockStripeClient->prices = $this->mockPricesService;

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
    public function complete_subscription_lifecycle_assign_cancel_resume()
    {
        $tenant = Tenant::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Premium Plan',
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'stripe_subscription_id' => 'sub_lifecycle_123',
            'subscription_status' => 'active',
        ]);

        $service = app(ClubSubscriptionService::class);

        // 1. Assign plan
        $service->assignPlanToClub($club, $plan);
        $club->refresh();
        $this->assertEquals($plan->id, $club->club_subscription_plan_id);

        // 2. Cancel subscription (at period end)
        $mockSubscription = Mockery::mock(Subscription::class);
        $mockSubscription->id = 'sub_lifecycle_123';
        $mockSubscription->current_period_end = Carbon::now()->addMonth()->timestamp;

        $this->mockSubscriptionsService->shouldReceive('update')
            ->once()
            ->with('sub_lifecycle_123', ['cancel_at_period_end' => true])
            ->andReturn($mockSubscription);

        $service->cancelSubscription($club, false);
        $club->refresh();
        $this->assertEquals('active', $club->subscription_status);
        $this->assertNotNull($club->subscription_ends_at);

        // 3. Resume subscription
        $mockResumedSubscription = Mockery::mock(Subscription::class);
        $mockResumedSubscription->cancel_at_period_end = false;

        $this->mockSubscriptionsService->shouldReceive('update')
            ->once()
            ->with('sub_lifecycle_123', ['cancel_at_period_end' => false])
            ->andReturn($mockResumedSubscription);

        $service->resumeSubscription($club);
        $club->refresh();
        $this->assertEquals('active', $club->subscription_status);
        $this->assertNull($club->subscription_ends_at);
    }

    /** @test */
    public function plan_swapping_upgrade_and_downgrade()
    {
        $tenant = Tenant::factory()->create();

        $basicPlan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Basic Plan',
            'price' => 49.00,
            'is_stripe_synced' => true,
            'stripe_price_id_monthly' => 'price_basic_123',
        ]);

        $premiumPlan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Premium Plan',
            'price' => 99.00,
            'is_stripe_synced' => true,
            'stripe_price_id_monthly' => 'price_premium_456',
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'stripe_subscription_id' => 'sub_swap_123',
            'club_subscription_plan_id' => $basicPlan->id,
        ]);

        // Mock subscription retrieval and update for upgrade
        $mockSubscription = Mockery::mock(Subscription::class);
        $mockItem = Mockery::mock();
        $mockItem->id = 'si_item_123';
        $mockSubscription->items = (object) ['data' => [$mockItem]];

        $this->mockSubscriptionsService->shouldReceive('retrieve')
            ->once()
            ->with('sub_swap_123')
            ->andReturn($mockSubscription);

        $mockUpdatedSubscription = Mockery::mock(Subscription::class);

        $this->mockSubscriptionsService->shouldReceive('update')
            ->once()
            ->andReturn($mockUpdatedSubscription);

        $service = app(ClubSubscriptionService::class);
        $service->swapPlan($club, $premiumPlan);

        $club->refresh();
        $this->assertEquals($premiumPlan->id, $club->club_subscription_plan_id);
    }

    /** @test */
    public function proration_calculations_on_plan_swap()
    {
        $tenant = Tenant::factory()->create();
        $oldPlan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'is_stripe_synced' => true,
            'stripe_price_id_monthly' => 'price_old',
        ]);

        $newPlan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'is_stripe_synced' => true,
            'stripe_price_id_monthly' => 'price_new',
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'stripe_subscription_id' => 'sub_prorate_123',
            'club_subscription_plan_id' => $oldPlan->id,
        ]);

        $mockSubscription = Mockery::mock(Subscription::class);
        $mockItem = Mockery::mock();
        $mockItem->id = 'si_123';
        $mockSubscription->items = (object) ['data' => [$mockItem]];

        $this->mockSubscriptionsService->shouldReceive('retrieve')
            ->once()
            ->andReturn($mockSubscription);

        $this->mockSubscriptionsService->shouldReceive('update')
            ->once()
            ->with('sub_prorate_123', Mockery::on(function ($data) {
                return $data['proration_behavior'] === 'none';
            }))
            ->andReturn($mockSubscription);

        $service = app(ClubSubscriptionService::class);
        $service->swapPlan($club, $newPlan, ['proration_behavior' => 'none']);

        // Test passed if no exception thrown
        $this->assertTrue(true);
    }

    /** @test */
    public function stripe_product_sync_creates_products_and_prices()
    {
        $tenant = Tenant::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Standard Plan',
            'description' => 'Standard features',
            'price' => 79.00,
            'currency' => 'EUR',
            'stripe_product_id' => null,
        ]);

        // Mock product creation
        $mockProduct = Mockery::mock(Product::class);
        $mockProduct->id = 'prod_standard_123';

        $this->mockProductsService->shouldReceive('create')
            ->once()
            ->andReturn($mockProduct);

        // Mock price creation (monthly)
        $mockPriceMonthly = Mockery::mock(Price::class);
        $mockPriceMonthly->id = 'price_monthly_standard';

        $this->mockPricesService->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['recurring']['interval'] === 'month';
            }))
            ->andReturn($mockPriceMonthly);

        // Mock price creation (yearly)
        $mockPriceYearly = Mockery::mock(Price::class);
        $mockPriceYearly->id = 'price_yearly_standard';

        $this->mockPricesService->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['recurring']['interval'] === 'year';
            }))
            ->andReturn($mockPriceYearly);

        $service = app(ClubSubscriptionService::class);
        $result = $service->syncPlanWithStripe($plan);

        $this->assertEquals('prod_standard_123', $result['product']->id);
        $this->assertEquals('price_monthly_standard', $result['price_monthly']->id);
        $this->assertEquals('price_yearly_standard', $result['price_yearly']->id);

        $plan->refresh();
        $this->assertTrue($plan->is_stripe_synced);
        $this->assertNotNull($plan->last_stripe_sync_at);
    }

    /** @test */
    public function free_plans_handled_correctly_without_prices()
    {
        $tenant = Tenant::factory()->create();
        $freePlan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Free Plan',
            'price' => 0.00,
            'stripe_product_id' => null,
        ]);

        // Mock product creation only
        $mockProduct = Mockery::mock(Product::class);
        $mockProduct->id = 'prod_free_123';

        $this->mockProductsService->shouldReceive('create')
            ->once()
            ->andReturn($mockProduct);

        // Should NOT create any prices for free plan
        $this->mockPricesService->shouldNotReceive('create');

        $service = app(ClubSubscriptionService::class);
        $result = $service->syncPlanWithStripe($freePlan);

        $this->assertEquals('prod_free_123', $result['product']->id);
        $this->assertNull($result['price_monthly']);
        $this->assertNull($result['price_yearly']);
    }

    /** @test */
    public function tenant_isolation_enforced_cant_assign_plan_from_other_tenant()
    {
        $tenant1 = Tenant::factory()->create(['name' => 'Tenant 1']);
        $tenant2 = Tenant::factory()->create(['name' => 'Tenant 2']);

        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant1->id,
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $tenant2->id,
        ]);

        $service = app(ClubSubscriptionService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Plan does not belong to club's tenant");

        $service->assignPlanToClub($club, $plan);
    }

    /** @test */
    public function multiple_clubs_with_different_plans()
    {
        $tenant = Tenant::factory()->create();

        $basicPlan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Basic',
        ]);

        $premiumPlan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Premium',
        ]);

        $club1 = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Club 1',
        ]);

        $club2 = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Club 2',
        ]);

        $service = app(ClubSubscriptionService::class);

        $service->assignPlanToClub($club1, $basicPlan);
        $service->assignPlanToClub($club2, $premiumPlan);

        $club1->refresh();
        $club2->refresh();

        $this->assertEquals($basicPlan->id, $club1->club_subscription_plan_id);
        $this->assertEquals($premiumPlan->id, $club2->club_subscription_plan_id);
    }

    /** @test */
    public function immediate_vs_period_end_cancellation()
    {
        $tenant = Tenant::factory()->create();
        $club1 = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'stripe_subscription_id' => 'sub_immediate_123',
            'club_subscription_plan_id' => 1,
        ]);

        $club2 = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'stripe_subscription_id' => 'sub_period_end_456',
            'club_subscription_plan_id' => 1,
        ]);

        $mockSubscription1 = Mockery::mock(Subscription::class);
        $mockSubscription1->status = 'canceled';

        // Immediate cancellation
        $this->mockSubscriptionsService->shouldReceive('cancel')
            ->once()
            ->with('sub_immediate_123')
            ->andReturn($mockSubscription1);

        $mockSubscription2 = Mockery::mock(Subscription::class);
        $mockSubscription2->current_period_end = Carbon::now()->addMonth()->timestamp;

        // Period end cancellation
        $this->mockSubscriptionsService->shouldReceive('update')
            ->once()
            ->with('sub_period_end_456', ['cancel_at_period_end' => true])
            ->andReturn($mockSubscription2);

        $service = app(ClubSubscriptionService::class);

        // Cancel club1 immediately
        $service->cancelSubscription($club1, true);
        $club1->refresh();
        $this->assertEquals('canceled', $club1->subscription_status);
        $this->assertNull($club1->club_subscription_plan_id);

        // Cancel club2 at period end
        $service->cancelSubscription($club2, false);
        $club2->refresh();
        $this->assertEquals('active', $club2->subscription_status);
        $this->assertNotNull($club2->subscription_ends_at);
        $this->assertNotNull($club2->club_subscription_plan_id);
    }

    /** @test */
    public function yearly_vs_monthly_billing_on_swap()
    {
        $tenant = Tenant::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'is_stripe_synced' => true,
            'stripe_price_id_monthly' => 'price_monthly_123',
            'stripe_price_id_yearly' => 'price_yearly_456',
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'stripe_subscription_id' => 'sub_billing_123',
        ]);

        $mockSubscription = Mockery::mock(Subscription::class);
        $mockItem = Mockery::mock();
        $mockItem->id = 'si_123';
        $mockSubscription->items = (object) ['data' => [$mockItem]];

        $this->mockSubscriptionsService->shouldReceive('retrieve')
            ->once()
            ->andReturn($mockSubscription);

        // Verify yearly price is used
        $this->mockSubscriptionsService->shouldReceive('update')
            ->once()
            ->with('sub_billing_123', Mockery::on(function ($data) {
                return $data['items'][0]['price'] === 'price_yearly_456';
            }))
            ->andReturn($mockSubscription);

        $service = app(ClubSubscriptionService::class);
        $service->swapPlan($club, $plan, ['billing_interval' => 'yearly']);

        // Test passed if no exception thrown
        $this->assertTrue(true);
    }
}
