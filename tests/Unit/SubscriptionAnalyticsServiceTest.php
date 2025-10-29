<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Stripe\SubscriptionAnalyticsService;
use App\Services\Stripe\StripeClientManager;
use App\Services\ClubUsageTrackingService;
use App\Models\Club;
use App\Models\Tenant;
use App\Models\ClubSubscriptionPlan;
use App\Models\SubscriptionMRRSnapshot;
use App\Models\ClubSubscriptionEvent;
use App\Models\ClubSubscriptionCohort;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;

class SubscriptionAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionAnalyticsService $service;
    private Tenant $tenant;
    private ClubSubscriptionPlan $plan;
    private $stripeClientMock;
    private $usageServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();

        // Mock StripeClientManager
        $this->stripeClientMock = Mockery::mock(StripeClientManager::class);

        // Mock ClubUsageTrackingService
        $this->usageServiceMock = Mockery::mock(ClubUsageTrackingService::class);

        $this->service = new SubscriptionAnalyticsService(
            $this->stripeClientMock,
            $this->usageServiceMock
        );

        $this->plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 100.00,
            'currency' => 'EUR',
        ]);

        // Clear cache before each test
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ========================================================================
    // MRR (Monthly Recurring Revenue) Tests
    // ========================================================================

    /** @test */
    /** @test */
    public function calculate_club_mrr_returns_zero_for_inactive_subscription()
    {
        $club = Club::factory()->for($this->tenant)->create([
            'subscription_status' => 'canceled',
            'club_subscription_plan_id' => $this->plan->id,
        ]);

        $mrr = $this->service->calculateClubMRR($club);

        $this->assertEquals(0.0, $mrr);
    }

    /** @test */
    /** @test */
    public function calculate_club_mrr_for_monthly_subscription()
    {
        $club = Club::factory()->for($this->tenant)->create([
            'subscription_status' => 'active',
            'club_subscription_plan_id' => $this->plan->id,
            'stripe_subscription_id' => 'sub_test123',
        ]);

        // Mock Stripe subscription with monthly interval
        $subscriptionMock = (object) [
            'items' => (object) [
                'data' => [
                    (object) [
                        'price' => (object) [
                            'id' => 'price_monthly',
                            'unit_amount' => 10000, // 100.00 EUR in cents
                            'recurring' => (object) [
                                'interval' => 'month',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $stripeClient = Mockery::mock();
        $stripeClient->subscriptions = Mockery::mock();
        $stripeClient->subscriptions->shouldReceive('retrieve')
            ->with('sub_test123')
            ->andReturn($subscriptionMock);

        $this->stripeClientMock->shouldReceive('getCurrentTenantClient')
            ->andReturn($stripeClient);

        $mrr = $this->service->calculateClubMRR($club);

        $this->assertEquals(100.00, $mrr);
    }

    /** @test */
    /** @test */
    public function calculate_club_mrr_for_yearly_subscription_normalized_to_monthly()
    {
        $club = Club::factory()->for($this->tenant)->create([
            'subscription_status' => 'active',
            'club_subscription_plan_id' => $this->plan->id,
            'stripe_subscription_id' => 'sub_test123',
        ]);

        // Mock Stripe subscription with yearly interval
        $subscriptionMock = (object) [
            'items' => (object) [
                'data' => [
                    (object) [
                        'price' => (object) [
                            'id' => 'price_yearly',
                            'unit_amount' => 120000, // 1200.00 EUR in cents
                            'recurring' => (object) [
                                'interval' => 'year',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $stripeClient = Mockery::mock();
        $stripeClient->subscriptions = Mockery::mock();
        $stripeClient->subscriptions->shouldReceive('retrieve')
            ->with('sub_test123')
            ->andReturn($subscriptionMock);

        $this->stripeClientMock->shouldReceive('getCurrentTenantClient')
            ->andReturn($stripeClient);

        $mrr = $this->service->calculateClubMRR($club);

        // 1200 / 12 = 100
        $this->assertEquals(100.00, $mrr);
    }

    /** @test */
    /** @test */
    public function calculate_club_mrr_fallback_when_stripe_fails()
    {
        $club = Club::factory()->for($this->tenant)->create([
            'subscription_status' => 'active',
            'club_subscription_plan_id' => $this->plan->id,
            'stripe_subscription_id' => 'sub_test123',
        ]);

        $stripeClient = Mockery::mock();
        $stripeClient->subscriptions = Mockery::mock();
        $stripeClient->subscriptions->shouldReceive('retrieve')
            ->with('sub_test123')
            ->andThrow(new \Exception('Stripe API error'));

        $this->stripeClientMock->shouldReceive('getCurrentTenantClient')
            ->andReturn($stripeClient);

        $mrr = $this->service->calculateClubMRR($club);

        // Should fallback to plan price
        $this->assertEquals(100.00, $mrr);
    }

    /** @test */
    /** @test */
    public function calculate_tenant_mrr_aggregates_all_active_clubs()
    {
        // Create 3 active clubs
        Club::factory()->for($this->tenant)->create([
            'subscription_status' => 'active',
            'club_subscription_plan_id' => $this->plan->id,
        ]);

        Club::factory()->for($this->tenant)->create([
            'subscription_status' => 'active',
            'club_subscription_plan_id' => $this->plan->id,
        ]);

        Club::factory()->for($this->tenant)->create([
            'subscription_status' => 'trialing',
            'club_subscription_plan_id' => $this->plan->id,
        ]);

        // Create 1 canceled club (should be excluded)
        Club::factory()->for($this->tenant)->create([
            'subscription_status' => 'canceled',
            'club_subscription_plan_id' => $this->plan->id,
        ]);

        $totalMRR = $this->service->calculateTenantMRR($this->tenant);

        // 3 active clubs * 100 = 300
        $this->assertEquals(300.00, $totalMRR);
    }

    /** @test */
    /** @test */
    public function calculate_tenant_mrr_caches_result()
    {
        Club::factory()->for($this->tenant)->create([
            'subscription_status' => 'active',
            'club_subscription_plan_id' => $this->plan->id,
        ]);

        // First call should calculate
        $mrr1 = $this->service->calculateTenantMRR($this->tenant);

        // Second call should use cache
        $mrr2 = $this->service->calculateTenantMRR($this->tenant);

        $this->assertEquals($mrr1, $mrr2);
        $this->assertTrue(Cache::has("subscription:mrr:{$this->tenant->id}"));
    }

    /** @test */
    /** @test */
    public function get_historical_mrr_from_snapshots()
    {
        // Create monthly snapshots
        SubscriptionMRRSnapshot::factory()->for($this->tenant)
            ->monthly()
            ->forMonth(2025, 1)
            ->withMRR(1000.00)
            ->create();

        SubscriptionMRRSnapshot::factory()->for($this->tenant)
            ->monthly()
            ->forMonth(2025, 2)
            ->withMRR(1200.00)
            ->create();

        SubscriptionMRRSnapshot::factory()->for($this->tenant)
            ->monthly()
            ->forMonth(2025, 3)
            ->withMRR(1500.00)
            ->create();

        $historical = $this->service->getHistoricalMRR($this->tenant, 3);

        $this->assertCount(3, $historical);
        $this->assertEquals(1500.00, $historical[0]['mrr']); // Latest first
        $this->assertEquals('2025-03', $historical[0]['month']);
    }

    /** @test */
    /** @test */
    public function get_historical_mrr_fallback_from_events()
    {
        // No snapshots available, should calculate from clubs
        $club = Club::factory()->for($this->tenant)->create([
            'subscription_status' => 'active',
            'club_subscription_plan_id' => $this->plan->id,
            'subscription_started_at' => now()->subMonths(6),
        ]);

        $historical = $this->service->getHistoricalMRR($this->tenant, 3);

        $this->assertIsArray($historical);
        $this->assertCount(3, $historical);
    }

    /** @test */
    /** @test */
    public function get_mrr_growth_rate_calculates_percentage()
    {
        SubscriptionMRRSnapshot::factory()->for($this->tenant)
            ->monthly()
            ->forMonth(now()->year, now()->month)
            ->withMRR(1200.00)
            ->create(['mrr_growth_rate' => 20.0]);

        SubscriptionMRRSnapshot::factory()->for($this->tenant)
            ->monthly()
            ->forMonth(now()->subMonths(3)->year, now()->subMonths(3)->month)
            ->withMRR(1000.00)
            ->create(['mrr_growth_rate' => 0.0]);

        $growthRate = $this->service->getMRRGrowthRate($this->tenant, 3);

        $this->assertGreaterThan(0, $growthRate);
    }

    /** @test */
    /** @test */
    public function get_mrr_growth_rate_returns_zero_with_insufficient_data()
    {
        // Only 1 snapshot
        SubscriptionMRRSnapshot::factory()->for($this->tenant)
            ->monthly()
            ->forMonth(now()->year, now()->month)
            ->withMRR(1000.00)
            ->create();

        $growthRate = $this->service->getMRRGrowthRate($this->tenant, 3);

        $this->assertEquals(0.0, $growthRate);
    }

    /** @test */
    /** @test */
    public function get_mrr_by_plan_breakdown()
    {
        $plan1 = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Basic',
            'price' => 50.00,
        ]);

        $plan2 = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pro',
            'price' => 150.00,
        ]);

        // 2 clubs on Basic
        Club::factory()->for($this->tenant)->count(2)->create([
            'subscription_status' => 'active',
            'club_subscription_plan_id' => $plan1->id,
        ]);

        // 1 club on Pro
        Club::factory()->for($this->tenant)->create([
            'subscription_status' => 'active',
            'club_subscription_plan_id' => $plan2->id,
        ]);

        $breakdown = $this->service->getMRRByPlan($this->tenant);

        $this->assertArrayHasKey($plan1->id, $breakdown);
        $this->assertArrayHasKey($plan2->id, $breakdown);
        $this->assertEquals(100.00, $breakdown[$plan1->id]['mrr']); // 2 * 50
        $this->assertEquals(150.00, $breakdown[$plan2->id]['mrr']); // 1 * 150
    }

    /** @test */
    /** @test */
    public function get_mrr_by_plan_sorted_by_mrr_descending()
    {
        $planLow = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 50.00,
        ]);

        $planHigh = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 200.00,
        ]);

        Club::factory()->for($this->tenant)->create([
            'subscription_status' => 'active',
            'club_subscription_plan_id' => $planLow->id,
        ]);

        Club::factory()->for($this->tenant)->create([
            'subscription_status' => 'active',
            'club_subscription_plan_id' => $planHigh->id,
        ]);

        $breakdown = $this->service->getMRRByPlan($this->tenant);

        $planIds = array_keys($breakdown);
        $this->assertEquals($planHigh->id, $planIds[0]); // Highest MRR first
    }

    /** @test */
    /** @test */
    public function mrr_includes_trialing_subscriptions()
    {
        $club = Club::factory()->for($this->tenant)->create([
            'subscription_status' => 'trialing',
            'club_subscription_plan_id' => $this->plan->id,
        ]);

        $mrr = $this->service->calculateClubMRR($club);

        $this->assertGreaterThan(0, $mrr);
    }

    /** @test */
    /** @test */
    public function mrr_excludes_canceled_subscriptions()
    {
        $club = Club::factory()->for($this->tenant)->create([
            'subscription_status' => 'canceled',
            'club_subscription_plan_id' => $this->plan->id,
        ]);

        $mrr = $this->service->calculateClubMRR($club);

        $this->assertEquals(0.0, $mrr);
    }

    /** @test */
    /** @test */
    public function mrr_excludes_past_due_subscriptions()
    {
        $club = Club::factory()->for($this->tenant)->create([
            'subscription_status' => 'past_due',
            'club_subscription_plan_id' => $this->plan->id,
        ]);

        $mrr = $this->service->calculateClubMRR($club);

        $this->assertEquals(0.0, $mrr);
    }

    // ========================================================================
    // Churn Tests
    // ========================================================================

    /** @test */
    /** @test */
    public function calculate_monthly_churn_rate()
    {
        $month = Carbon::create(2025, 3, 1);

        // 10 clubs at start of month
        Club::factory()->for($this->tenant)->count(10)->create([
            'subscription_status' => 'active',
            'subscription_started_at' => $month->copy()->subMonths(2),
        ]);

        // 2 churn events in March
        ClubSubscriptionEvent::factory()->for($this->tenant)
            ->subscriptionCanceled()
            ->inMonth(2025, 3)
            ->count(2)
            ->create();

        $churnData = $this->service->calculateMonthlyChurnRate($this->tenant, $month);

        $this->assertEquals(20.0, $churnData['churn_rate']); // 2/10 * 100
        $this->assertEquals(2, $churnData['churned_customers']);
    }

    /** @test */
    /** @test */
    public function calculate_monthly_churn_rate_with_voluntary_involuntary_breakdown()
    {
        $month = Carbon::create(2025, 3, 1);

        Club::factory()->for($this->tenant)->count(10)->create([
            'subscription_status' => 'active',
            'subscription_started_at' => $month->copy()->subMonths(2),
        ]);

        // 3 voluntary cancellations
        ClubSubscriptionEvent::factory()->for($this->tenant)
            ->voluntaryCancellation()
            ->inMonth(2025, 3)
            ->count(3)
            ->create();

        // 2 involuntary (payment failed)
        ClubSubscriptionEvent::factory()->for($this->tenant)
            ->involuntaryCancellation()
            ->inMonth(2025, 3)
            ->count(2)
            ->create();

        $churnData = $this->service->calculateMonthlyChurnRate($this->tenant, $month);

        $this->assertEquals(3, $churnData['voluntary_churn']);
        $this->assertEquals(2, $churnData['involuntary_churn']);
    }

    /** @test */
    /** @test */
    public function calculate_monthly_churn_rate_returns_zero_for_no_customers()
    {
        $month = Carbon::create(2025, 3, 1);

        $churnData = $this->service->calculateMonthlyChurnRate($this->tenant, $month);

        $this->assertEquals(0.0, $churnData['churn_rate']);
    }

    /** @test */
    /** @test */
    public function calculate_monthly_churn_rate_caches_result()
    {
        $month = Carbon::create(2025, 3, 1);

        Club::factory()->for($this->tenant)->count(10)->create([
            'subscription_status' => 'active',
            'subscription_started_at' => $month->copy()->subMonths(2),
        ]);

        $churnData1 = $this->service->calculateMonthlyChurnRate($this->tenant, $month);
        $churnData2 = $this->service->calculateMonthlyChurnRate($this->tenant, $month);

        $this->assertEquals($churnData1, $churnData2);
        $this->assertTrue(Cache::has("subscription:churn:{$this->tenant->id}:" . $month->format('Y-m')));
    }

    /** @test */
    /** @test */
    public function get_churn_by_plan_identifies_high_churn_plans()
    {
        $planA = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Plan A',
        ]);

        $planB = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Plan B',
        ]);

        // 5 churn events from Plan A
        ClubSubscriptionEvent::factory()->for($this->tenant)
            ->subscriptionCanceled()
            ->count(5)
            ->create(['old_plan_id' => $planA->id]);

        // 2 churn events from Plan B
        ClubSubscriptionEvent::factory()->for($this->tenant)
            ->subscriptionCanceled()
            ->count(2)
            ->create(['old_plan_id' => $planB->id]);

        $churnByPlan = $this->service->getChurnByPlan($this->tenant, 12);

        $this->assertArrayHasKey($planA->id, $churnByPlan);
        $this->assertArrayHasKey($planB->id, $churnByPlan);
        $this->assertEquals(5, $churnByPlan[$planA->id]['churned_count']);
        $this->assertEquals(2, $churnByPlan[$planB->id]['churned_count']);
    }

    /** @test */
    /** @test */
    public function get_churn_by_plan_sorted_by_rate_descending()
    {
        $planLowChurn = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $planHighChurn = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        ClubSubscriptionEvent::factory()->for($this->tenant)
            ->subscriptionCanceled()
            ->count(2)
            ->create(['old_plan_id' => $planLowChurn->id]);

        ClubSubscriptionEvent::factory()->for($this->tenant)
            ->subscriptionCanceled()
            ->count(8)
            ->create(['old_plan_id' => $planHighChurn->id]);

        $churnByPlan = $this->service->getChurnByPlan($this->tenant, 12);

        $planIds = array_keys($churnByPlan);
        $this->assertEquals($planHighChurn->id, $planIds[0]); // Highest churn first
    }

    /** @test */
    /** @test */
    public function get_churn_reasons_breakdown()
    {
        ClubSubscriptionEvent::factory()->for($this->tenant)
            ->voluntaryCancellation()
            ->count(5)
            ->create();

        ClubSubscriptionEvent::factory()->for($this->tenant)
            ->involuntaryCancellation()
            ->count(3)
            ->create();

        ClubSubscriptionEvent::factory()->for($this->tenant)
            ->trialExpired()
            ->count(2)
            ->create();

        $reasons = $this->service->getChurnReasons($this->tenant, 6);

        $this->assertEquals(5, $reasons['voluntary']['count']);
        $this->assertEquals(3, $reasons['payment_failed']['count']);
        $this->assertEquals(2, $reasons['trial_expired']['count']);
    }

    /** @test */
    /** @test */
    public function get_churn_reasons_with_percentages()
    {
        ClubSubscriptionEvent::factory()->for($this->tenant)
            ->voluntaryCancellation()
            ->count(7)
            ->create();

        ClubSubscriptionEvent::factory()->for($this->tenant)
            ->involuntaryCancellation()
            ->count(3)
            ->create();

        $reasons = $this->service->getChurnReasons($this->tenant, 6);

        $this->assertEquals(70.0, $reasons['voluntary']['percentage']); // 7/10 * 100
        $this->assertEquals(30.0, $reasons['payment_failed']['percentage']); // 3/10 * 100
    }

    /** @test */
    /** @test */
    public function calculate_revenue_churn_from_mrr_loss()
    {
        $month = Carbon::create(2025, 3, 1);

        // Create snapshot with MRR at start
        SubscriptionMRRSnapshot::factory()->for($this->tenant)
            ->monthly()
            ->forMonth(2025, 3)
            ->create(['total_mrr' => 1000.00]);

        // Create churn events with MRR loss
        ClubSubscriptionEvent::factory()->for($this->tenant)
            ->subscriptionCanceled()
            ->inMonth(2025, 3)
            ->count(2)
            ->create(['mrr_change' => -100.00]);

        $revenueChurn = $this->service->calculateRevenueChurn($this->tenant, $month);

        // 200 lost / 1000 start = 20%
        $this->assertEquals(20.0, $revenueChurn);
    }

    /** @test */
    /** @test */
    public function calculate_revenue_churn_returns_zero_when_no_mrr()
    {
        $month = Carbon::create(2025, 3, 1);

        $revenueChurn = $this->service->calculateRevenueChurn($this->tenant, $month);

        $this->assertEquals(0.0, $revenueChurn);
    }

    /** @test */
    /** @test */
    public function churn_distinguishes_voluntary_vs_involuntary()
    {
        $voluntaryEvent = ClubSubscriptionEvent::factory()->for($this->tenant)
            ->voluntaryCancellation()
            ->create();

        $involuntaryEvent = ClubSubscriptionEvent::factory()->for($this->tenant)
            ->involuntaryCancellation()
            ->create();

        $this->assertTrue($voluntaryEvent->isVoluntaryChurn());
        $this->assertFalse($voluntaryEvent->isInvoluntaryChurn());

        $this->assertTrue($involuntaryEvent->isInvoluntaryChurn());
        $this->assertFalse($involuntaryEvent->isVoluntaryChurn());
    }

    /** @test */
    /** @test */
    public function churn_includes_trial_expirations()
    {
        ClubSubscriptionEvent::factory()->for($this->tenant)
            ->trialExpired()
            ->count(3)
            ->create();

        $churnEvents = ClubSubscriptionEvent::churnEvents()->get();

        $this->assertCount(3, $churnEvents);
    }

    // ========================================================================
    // LTV (Lifetime Value) Tests
    // ========================================================================

    /** @test */
    /** @test */
    public function calculate_average_ltv_from_mrr_and_duration()
    {
        Club::factory()->for($this->tenant)->count(5)->create([
            'subscription_status' => 'active',
            'club_subscription_plan_id' => $this->plan->id,
            'subscription_started_at' => now()->subMonths(12),
        ]);

        $avgLTV = $this->service->calculateAverageLTV($this->tenant);

        $this->assertGreaterThan(0, $avgLTV);
    }

    /** @test */
    /** @test */
    public function calculate_average_ltv_uses_default_duration_when_no_data()
    {
        Club::factory()->for($this->tenant)->create([
            'subscription_status' => 'active',
            'club_subscription_plan_id' => $this->plan->id,
            'subscription_started_at' => null, // No start date
        ]);

        $avgLTV = $this->service->calculateAverageLTV($this->tenant);

        // Should use default 12 months
        $this->assertGreaterThan(0, $avgLTV);
    }

    /** @test */
    /** @test */
    public function calculate_average_ltv_caches_result()
    {
        Club::factory()->for($this->tenant)->create([
            'subscription_status' => 'active',
            'club_subscription_plan_id' => $this->plan->id,
            'subscription_started_at' => now()->subMonths(6),
        ]);

        $ltv1 = $this->service->calculateAverageLTV($this->tenant);
        $ltv2 = $this->service->calculateAverageLTV($this->tenant);

        $this->assertEquals($ltv1, $ltv2);
        $this->assertTrue(Cache::has("subscription:ltv:{$this->tenant->id}"));
    }

    /** @test */
    /** @test */
    public function get_ltv_by_plan_breakdown()
    {
        $planLow = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 50.00,
        ]);

        $planHigh = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 200.00,
        ]);

        Club::factory()->for($this->tenant)->count(3)->create([
            'club_subscription_plan_id' => $planLow->id,
            'subscription_started_at' => now()->subMonths(10),
        ]);

        Club::factory()->for($this->tenant)->count(2)->create([
            'club_subscription_plan_id' => $planHigh->id,
            'subscription_started_at' => now()->subMonths(15),
        ]);

        $ltvByPlan = $this->service->getLTVByPlan($this->tenant);

        $this->assertArrayHasKey($planLow->id, $ltvByPlan);
        $this->assertArrayHasKey($planHigh->id, $ltvByPlan);
    }

    /** @test */
    /** @test */
    public function get_ltv_by_plan_sorted_by_ltv_descending()
    {
        $planLow = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 50.00,
        ]);

        $planHigh = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 200.00,
        ]);

        Club::factory()->for($this->tenant)->create([
            'club_subscription_plan_id' => $planLow->id,
            'subscription_started_at' => now()->subMonths(6),
        ]);

        Club::factory()->for($this->tenant)->create([
            'club_subscription_plan_id' => $planHigh->id,
            'subscription_started_at' => now()->subMonths(12),
        ]);

        $ltvByPlan = $this->service->getLTVByPlan($this->tenant);

        $planIds = array_keys($ltvByPlan);
        $this->assertEquals($planHigh->id, $planIds[0]); // Highest LTV first
    }

    /** @test */
    /** @test */
    public function get_cohort_analysis_from_precomputed_data()
    {
        $cohort = ClubSubscriptionCohort::factory()->for($this->tenant)
            ->forMonth(2025, 1)
            ->excellentRetention()
            ->create();

        $analysis = $this->service->getCohortAnalysis($this->tenant, '2025-01');

        $this->assertEquals('2025-01', $analysis['cohort']);
        $this->assertEquals($cohort->cohort_size, $analysis['cohort_size']);
        $this->assertEquals('excellent', $analysis['retention_trend']);
    }

    /** @test */
    /** @test */
    public function get_cohort_analysis_calculates_on_the_fly_when_no_data()
    {
        // Create clubs in cohort month
        Club::factory()->for($this->tenant)->count(10)->create([
            'subscription_started_at' => Carbon::create(2025, 1, 15),
            'subscription_status' => 'active',
        ]);

        $analysis = $this->service->getCohortAnalysis($this->tenant, '2025-01');

        $this->assertEquals('2025-01', $analysis['cohort']);
        $this->assertEquals(10, $analysis['cohort_size']);
    }

    /** @test */
    /** @test */
    public function get_cohort_analysis_retention_by_month()
    {
        $cohort = ClubSubscriptionCohort::factory()->for($this->tenant)
            ->forMonth(2025, 1)
            ->create([
                'retention_month_1' => 100.0,
                'retention_month_2' => 90.0,
                'retention_month_3' => 85.0,
                'retention_month_6' => 75.0,
                'retention_month_12' => 65.0,
            ]);

        $analysis = $this->service->getCohortAnalysis($this->tenant, '2025-01');

        $this->assertEquals(100.0, $analysis['retention_by_month'][1]);
        $this->assertEquals(90.0, $analysis['retention_by_month'][2]);
        $this->assertEquals(85.0, $analysis['retention_by_month'][3]);
        $this->assertEquals(75.0, $analysis['retention_by_month'][6]);
        $this->assertEquals(65.0, $analysis['retention_by_month'][12]);
    }

    /** @test */
    /** @test */
    public function get_cohort_analysis_determines_retention_trend()
    {
        $excellent = ClubSubscriptionCohort::factory()->for($this->tenant)
            ->forMonth(2025, 1)
            ->excellentRetention()
            ->create();

        $good = ClubSubscriptionCohort::factory()->for($this->tenant)
            ->forMonth(2025, 2)
            ->goodRetention()
            ->create();

        $moderate = ClubSubscriptionCohort::factory()->for($this->tenant)
            ->forMonth(2025, 3)
            ->moderateRetention()
            ->create();

        $poor = ClubSubscriptionCohort::factory()->for($this->tenant)
            ->forMonth(2025, 4)
            ->poorRetention()
            ->create();

        $this->assertEquals('excellent', $this->service->getCohortAnalysis($this->tenant, '2025-01')['retention_trend']);
        $this->assertEquals('good', $this->service->getCohortAnalysis($this->tenant, '2025-02')['retention_trend']);
        $this->assertEquals('moderate', $this->service->getCohortAnalysis($this->tenant, '2025-03')['retention_trend']);
        $this->assertEquals('poor', $this->service->getCohortAnalysis($this->tenant, '2025-04')['retention_trend']);
    }

    /** @test */
    /** @test */
    public function get_cohort_analysis_returns_no_data_for_empty_cohort()
    {
        $analysis = $this->service->getCohortAnalysis($this->tenant, '2025-01');

        $this->assertEquals(0, $analysis['cohort_size']);
        $this->assertEquals('no_data', $analysis['retention_trend']);
    }

    /** @test */
    /** @test */
    public function get_customer_lifetime_stats_aggregate_metrics()
    {
        Club::factory()->for($this->tenant)->count(10)->create([
            'subscription_started_at' => now()->subMonths(12),
            'club_subscription_plan_id' => $this->plan->id,
        ]);

        $stats = $this->service->getCustomerLifetimeStats($this->tenant);

        $this->assertArrayHasKey('avg_subscription_duration_days', $stats);
        $this->assertArrayHasKey('median_subscription_duration_days', $stats);
        $this->assertArrayHasKey('avg_ltv', $stats);
        $this->assertArrayHasKey('median_ltv', $stats);
        $this->assertArrayHasKey('total_lifetime_revenue', $stats);
        $this->assertArrayHasKey('total_active_clubs', $stats);
    }

    /** @test */
    /** @test */
    public function get_customer_lifetime_stats_calculates_median_values()
    {
        Club::factory()->for($this->tenant)->count(5)->create([
            'subscription_started_at' => now()->subMonths(6),
            'club_subscription_plan_id' => $this->plan->id,
        ]);

        $stats = $this->service->getCustomerLifetimeStats($this->tenant);

        $this->assertGreaterThan(0, $stats['median_subscription_duration_days']);
        $this->assertGreaterThan(0, $stats['median_ltv']);
    }

    /** @test */
    /** @test */
    public function ltv_accounts_for_active_and_ended_subscriptions()
    {
        // Active subscription
        Club::factory()->for($this->tenant)->create([
            'subscription_started_at' => now()->subMonths(12),
            'subscription_ends_at' => null,
            'subscription_status' => 'active',
            'club_subscription_plan_id' => $this->plan->id,
        ]);

        // Ended subscription
        Club::factory()->for($this->tenant)->create([
            'subscription_started_at' => now()->subMonths(24),
            'subscription_ends_at' => now()->subMonths(6),
            'subscription_status' => 'canceled',
            'club_subscription_plan_id' => $this->plan->id,
        ]);

        $avgLTV = $this->service->calculateAverageLTV($this->tenant);

        $this->assertGreaterThan(0, $avgLTV);
    }

    /** @test */
    /** @test */
    public function cohort_analysis_mature_vs_immature_cohorts()
    {
        $mature = ClubSubscriptionCohort::factory()->for($this->tenant)
            ->mature()
            ->create();

        $immature = ClubSubscriptionCohort::factory()->for($this->tenant)
            ->immature()
            ->create();

        $this->assertTrue($mature->isMature());
        $this->assertFalse($immature->isMature());
    }

    // ========================================================================
    // Health Metrics Tests
    // ========================================================================

    /** @test */
    /** @test */
    public function get_active_subscriptions_count()
    {
        Club::factory()->for($this->tenant)->count(8)->create([
            'subscription_status' => 'active',
        ]);

        Club::factory()->for($this->tenant)->count(2)->create([
            'subscription_status' => 'canceled',
        ]);

        $count = $this->service->getActiveSubscriptionsCount($this->tenant);

        $this->assertEquals(8, $count);
    }

    /** @test */
    /** @test */
    public function get_active_subscriptions_includes_trialing()
    {
        Club::factory()->for($this->tenant)->count(5)->create([
            'subscription_status' => 'active',
        ]);

        Club::factory()->for($this->tenant)->count(3)->create([
            'subscription_status' => 'trialing',
        ]);

        $count = $this->service->getActiveSubscriptionsCount($this->tenant);

        $this->assertEquals(8, $count); // 5 active + 3 trialing
    }

    /** @test */
    /** @test */
    public function get_trial_conversion_rate()
    {
        // 10 trials started
        ClubSubscriptionEvent::factory()->for($this->tenant)
            ->trialStarted()
            ->count(10)
            ->create(['event_date' => now()->subDays(20)]);

        // 7 trials converted
        ClubSubscriptionEvent::factory()->for($this->tenant)
            ->trialConverted()
            ->count(7)
            ->create(['event_date' => now()->subDays(15)]);

        $conversionRate = $this->service->getTrialConversionRate($this->tenant, 30);

        $this->assertEquals(70.0, $conversionRate); // 7/10 * 100
    }

    /** @test */
    /** @test */
    public function get_trial_conversion_rate_returns_zero_with_no_trials()
    {
        $conversionRate = $this->service->getTrialConversionRate($this->tenant, 30);

        $this->assertEquals(0.0, $conversionRate);
    }

    /** @test */
    /** @test */
    public function get_average_subscription_duration()
    {
        Club::factory()->for($this->tenant)->count(5)->create([
            'subscription_started_at' => now()->subMonths(12),
            'subscription_ends_at' => now(),
        ]);

        $avgDuration = $this->service->getAverageSubscriptionDuration($this->tenant);

        $this->assertGreaterThan(0, $avgDuration);
    }

    /** @test */
    /** @test */
    public function get_average_subscription_duration_includes_active_subscriptions()
    {
        Club::factory()->for($this->tenant)->count(3)->create([
            'subscription_started_at' => now()->subMonths(6),
            'subscription_ends_at' => null, // Still active
            'subscription_status' => 'active',
        ]);

        $avgDuration = $this->service->getAverageSubscriptionDuration($this->tenant);

        $this->assertGreaterThan(0, $avgDuration);
    }

    /** @test */
    /** @test */
    public function get_upgrade_downgrade_rates()
    {
        Club::factory()->for($this->tenant)->count(10)->create([
            'subscription_status' => 'active',
        ]);

        // 3 upgrades
        ClubSubscriptionEvent::factory()->for($this->tenant)
            ->planUpgraded()
            ->count(3)
            ->create();

        // 1 downgrade
        ClubSubscriptionEvent::factory()->for($this->tenant)
            ->planDowngraded()
            ->count(1)
            ->create();

        $rates = $this->service->getUpgradeDowngradeRates($this->tenant, 3);

        $this->assertEquals(3, $rates['upgrades']);
        $this->assertEquals(1, $rates['downgrades']);
        $this->assertEquals(2, $rates['net_change']); // 3 - 1
    }

    /** @test */
    /** @test */
    public function get_upgrade_downgrade_rates_based_on_price_comparison()
    {
        $lowPlan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 50.00,
        ]);

        $highPlan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 150.00,
        ]);

        // Upgrade (from low to high price)
        ClubSubscriptionEvent::factory()->for($this->tenant)->create([
            'event_type' => ClubSubscriptionEvent::TYPE_PLAN_UPGRADED,
            'old_plan_id' => $lowPlan->id,
            'new_plan_id' => $highPlan->id,
            'event_date' => now()->subDays(5),
        ]);

        // Downgrade (from high to low price)
        ClubSubscriptionEvent::factory()->for($this->tenant)->create([
            'event_type' => ClubSubscriptionEvent::TYPE_PLAN_DOWNGRADED,
            'old_plan_id' => $highPlan->id,
            'new_plan_id' => $lowPlan->id,
            'event_date' => now()->subDays(3),
        ]);

        $rates = $this->service->getUpgradeDowngradeRates($this->tenant, 1);

        $this->assertEquals(1, $rates['upgrades']);
        $this->assertEquals(1, $rates['downgrades']);
    }

    /** @test */
    /** @test */
    public function get_upgrade_downgrade_rates_calculates_percentages()
    {
        Club::factory()->for($this->tenant)->count(100)->create([
            'subscription_status' => 'active',
        ]);

        ClubSubscriptionEvent::factory()->for($this->tenant)
            ->planUpgraded()
            ->count(10)
            ->create();

        $rates = $this->service->getUpgradeDowngradeRates($this->tenant, 3);

        $this->assertEquals(10.0, $rates['upgrade_rate']); // 10/100 * 100
    }

    /** @test */
    /** @test */
    public function upgrade_downgrade_net_change_calculation()
    {
        ClubSubscriptionEvent::factory()->for($this->tenant)
            ->planUpgraded()
            ->count(15)
            ->create();

        ClubSubscriptionEvent::factory()->for($this->tenant)
            ->planDowngraded()
            ->count(5)
            ->create();

        $rates = $this->service->getUpgradeDowngradeRates($this->tenant, 3);

        $this->assertEquals(10, $rates['net_change']); // 15 - 5
    }

    /** @test */
    /** @test */
    public function health_metrics_with_no_subscription_data()
    {
        $count = $this->service->getActiveSubscriptionsCount($this->tenant);
        $conversionRate = $this->service->getTrialConversionRate($this->tenant);
        $avgDuration = $this->service->getAverageSubscriptionDuration($this->tenant);

        $this->assertEquals(0, $count);
        $this->assertEquals(0.0, $conversionRate);
        $this->assertEquals(0.0, $avgDuration);
    }
}
