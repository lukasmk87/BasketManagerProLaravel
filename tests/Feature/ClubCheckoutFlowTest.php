<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Stripe\ClubSubscriptionCheckoutService;
use App\Services\Stripe\StripeClientManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\BillingPortal\Session as BillingPortalSession;
use Stripe\Checkout\Session;
use Stripe\StripeClient;
use Tests\TestCase;

class ClubCheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $mockClientManager;

    protected $mockStripeClient;

    protected $mockCheckoutService;

    protected $mockBillingPortalService;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup mocked Stripe services
        $this->mockCheckoutService = Mockery::mock();
        $this->mockBillingPortalService = Mockery::mock();

        $this->mockStripeClient = Mockery::mock(StripeClient::class);
        $this->mockStripeClient->checkout = (object) ['sessions' => $this->mockCheckoutService];
        $this->mockStripeClient->billingPortal = (object) ['sessions' => $this->mockBillingPortalService];

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
    public function club_admin_can_initiate_checkout()
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

        $user = User::factory()->create();
        $club->users()->attach($user->id, ['role' => 'admin', 'is_active' => true]);

        // Mock Stripe Customer creation/retrieval
        $mockCustomer = Mockery::mock();
        $mockCustomer->id = 'cus_test_123';

        // Mock Checkout Session creation
        $mockSession = Mockery::mock(Session::class);
        $mockSession->id = 'cs_test_123';
        $mockSession->url = 'https://checkout.stripe.com/test';

        $this->mockCheckoutService->shouldReceive('create')
            ->once()
            ->andReturn($mockSession);

        $response = $this->actingAs($user)->postJson(route('club.checkout', $club), [
            'plan_id' => $plan->id,
            'billing_interval' => 'monthly',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'checkout_url' => 'https://checkout.stripe.com/test',
                'session_id' => 'cs_test_123',
            ]);
    }

    /** @test */
    public function checkout_requires_authentication()
    {
        $tenant = Tenant::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create(['tenant_id' => $tenant->id]);
        $club = Club::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->postJson(route('club.checkout', $club), [
            'plan_id' => $plan->id,
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function checkout_validates_plan_belongs_to_tenant()
    {
        $tenant1 = Tenant::factory()->create(['name' => 'Tenant 1']);
        $tenant2 = Tenant::factory()->create(['name' => 'Tenant 2']);

        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant1->id,
            'is_active' => true,
            'is_stripe_synced' => true,
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $tenant2->id,
        ]);

        $user = User::factory()->create();
        $club->users()->attach($user->id, ['role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($user)->postJson(route('club.checkout', $club), [
            'plan_id' => $plan->id,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'Plan does not belong to club\'s tenant',
            ]);
    }

    /** @test */
    public function checkout_validates_plan_is_active()
    {
        $tenant = Tenant::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => false, // Inactive plan
            'is_stripe_synced' => true,
        ]);

        $club = Club::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create();
        $club->users()->attach($user->id, ['role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($user)->postJson(route('club.checkout', $club), [
            'plan_id' => $plan->id,
        ]);

        $response->assertStatus(500);
    }

    /** @test */
    public function successful_checkout_redirects_to_success_page()
    {
        $tenant = Tenant::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Premium Plan',
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'club_subscription_plan_id' => $plan->id,
        ]);

        $user = User::factory()->create();
        $club->users()->attach($user->id, ['role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($user)->get(route('club.checkout.success', [
            'club' => $club,
            'session_id' => 'cs_test_success_123',
        ]));

        $response->assertStatus(200);
    }

    /** @test */
    public function canceled_checkout_shows_cancel_page()
    {
        $tenant = Tenant::factory()->create();
        $club = Club::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create();
        $club->users()->attach($user->id, ['role' => 'member', 'is_active' => true]);

        $response = $this->actingAs($user)->get(route('club.checkout.cancel', $club));

        $response->assertStatus(200);
    }

    /** @test */
    public function billing_portal_requires_active_stripe_customer()
    {
        $tenant = Tenant::factory()->create();
        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'stripe_customer_id' => null, // No Stripe customer
        ]);

        $user = User::factory()->create();
        $club->users()->attach($user->id, ['role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($user)->postJson(route('club.billing-portal', $club), [
            'return_url' => route('club.subscription.index', $club),
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Club has no active billing account',
            ]);
    }

    /** @test */
    public function billing_portal_creates_session_for_existing_customer()
    {
        $tenant = Tenant::factory()->create();
        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'stripe_customer_id' => 'cus_existing_123',
        ]);

        $user = User::factory()->create();
        $club->users()->attach($user->id, ['role' => 'admin', 'is_active' => true]);

        // Mock billing portal session creation
        $mockPortalSession = Mockery::mock(BillingPortalSession::class);
        $mockPortalSession->id = 'bps_test_123';
        $mockPortalSession->url = 'https://billing.stripe.com/session/test';

        $this->mockBillingPortalService->shouldReceive('create')
            ->once()
            ->andReturn($mockPortalSession);

        $response = $this->actingAs($user)->postJson(route('club.billing-portal', $club), [
            'return_url' => route('club.subscription.index', $club),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'portal_url' => 'https://billing.stripe.com/session/test',
            ]);
    }

    /** @test */
    public function subscription_index_page_shows_current_plan()
    {
        $tenant = Tenant::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Premium Plan',
            'price' => 99.00,
        ]);

        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'club_subscription_plan_id' => $plan->id,
        ]);

        $user = User::factory()->create();
        $club->users()->attach($user->id, ['role' => 'member', 'is_active' => true]);

        $response = $this->actingAs($user)->get(route('club.subscription.index', $club));

        $response->assertStatus(200);
    }

    /** @test */
    public function only_authorized_users_can_manage_billing()
    {
        $tenant = Tenant::factory()->create();
        $club = Club::factory()->create(['tenant_id' => $tenant->id]);

        // User without club membership
        $unauthorizedUser = User::factory()->create();

        $response = $this->actingAs($unauthorizedUser)->get(route('club.subscription.index', $club));

        $response->assertStatus(403);
    }

    /** @test */
    public function checkout_supports_yearly_billing()
    {
        $tenant = Tenant::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'is_stripe_synced' => true,
            'stripe_price_id_monthly' => 'price_monthly_123',
            'stripe_price_id_yearly' => 'price_yearly_456',
        ]);

        $club = Club::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create();
        $club->users()->attach($user->id, ['role' => 'admin', 'is_active' => true]);

        $mockSession = Mockery::mock(Session::class);
        $mockSession->id = 'cs_yearly_123';
        $mockSession->url = 'https://checkout.stripe.com/yearly';

        $this->mockCheckoutService->shouldReceive('create')
            ->once()
            ->andReturn($mockSession);

        $response = $this->actingAs($user)->postJson(route('club.checkout', $club), [
            'plan_id' => $plan->id,
            'billing_interval' => 'yearly',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'session_id' => 'cs_yearly_123',
            ]);
    }
}
