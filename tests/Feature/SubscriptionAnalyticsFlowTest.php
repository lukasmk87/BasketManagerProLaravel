<?php

namespace Tests\Feature;

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

class SubscriptionAnalyticsFlowTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionAnalyticsService $service;
    private Tenant $tenant;
    private ClubSubscriptionPlan $basicPlan;
    private ClubSubscriptionPlan $proPlan;
    private $stripeClientMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Basketball Federation',
        ]);

        $this->basicPlan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Basic',
            'price' => 50.00,
            'currency' => 'EUR',
        ]);

        $this->proPlan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pro',
            'price' => 150.00,
            'currency' => 'EUR',
        ]);

        // Mock Stripe Client
        $this->stripeClientMock = Mockery::mock(StripeClientManager::class);
        $usageServiceMock = Mockery::mock(ClubUsageTrackingService::class);

        $this->service = new SubscriptionAnalyticsService(
            $this->stripeClientMock,
            $usageServiceMock
        );

        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function test_complete_subscription_lifecycle_tracks_analytics()
    {
        // Step 1: Create club with trial
        $club = Club::factory()->for($this->tenant)->create([
            'subscription_status' => 'trialing',
            'club_subscription_plan_id' => $this->basicPlan->id,
            'subscription_started_at' => now()->subDays(7),
            'subscription_trial_ends_at' => now()->addDays(7),
        ]);

        ClubSubscriptionEvent::factory()->for($this->tenant)->for($club)->create([
            'event_type' => ClubSubscriptionEvent::TYPE_TRIAL_STARTED,
            'event_date' => now()->subDays(7),
            'mrr_change' => 0,
        ]);

        // Step 2: Trial converts to paid
        $club->update([
            'subscription_status' => 'active',
            'subscription_trial_ends_at' => null,
        ]);

        ClubSubscriptionEvent::factory()->for($this->tenant)->for($club)->create([
            'event_type' => ClubSubscriptionEvent::TYPE_TRIAL_CONVERTED,
            'event_date' => now()->subDays(1),
            'mrr_change' => 50.00,
        ]);

        // Step 3: Upgrade to Pro
        $club->update(['club_subscription_plan_id' => $this->proPlan->id]);

        ClubSubscriptionEvent::factory()->for($this->tenant)->for($club)->create([
            'event_type' => ClubSubscriptionEvent::TYPE_PLAN_UPGRADED,
            'old_plan_id' => $this->basicPlan->id,
            'new_plan_id' => $this->proPlan->id,
            'event_date' => now(),
            'mrr_change' => 100.00,
        ]);

        // Verify lifecycle events
        $lifecycleEvents = ClubSubscriptionEvent::where('club_id', $club->id)->orderBy('event_date')->get();

        $this->assertCount(3, $lifecycleEvents);
        $this->assertEquals(ClubSubscriptionEvent::TYPE_TRIAL_STARTED, $lifecycleEvents[0]->event_type);
        $this->assertEquals(ClubSubscriptionEvent::TYPE_TRIAL_CONVERTED, $lifecycleEvents[1]->event_type);
        $this->assertEquals(ClubSubscriptionEvent::TYPE_PLAN_UPGRADED, $lifecycleEvents[2]->event_type);

        // Verify MRR calculation
        $mrr = $this->service->calculateClubMRR($club);
        $this->assertEquals(150.00, $mrr);
    }

    /** @test */
    public function test_mrr_snapshot_creation_and_retrieval()
    {
        // Create snapshots for 6 months
        $snapshots = [];
        for ($i = 0; $i < 6; $i++) {
            $month = now()->subMonths($i);
            $snapshots[] = SubscriptionMRRSnapshot::factory()->for($this->tenant)->create([
                'snapshot_date' => $month->startOfMonth(),
                'snapshot_type' => 'monthly',
                'total_mrr' => 1000 + ($i * 100), // Increasing MRR
                'mrr_growth_rate' => 10.0,
                'active_subscriptions' => 10 + $i,
            ]);
        }

        // Retrieve historical MRR
        $historical = $this->service->getHistoricalMRR($this->tenant, 6);

        $this->assertCount(6, $historical);
        $this->assertEquals(1000.00, $historical[0]['mrr']); // Latest (current month)
        $this->assertEquals(1500.00, $historical[5]['mrr']); // Oldest (6 months ago)
    }

    /** @test */
    public function test_subscription_events_logged_correctly()
    {
        $club = Club::factory()->for($this->tenant)->create([
            'subscription_status' => 'active',
            'club_subscription_plan_id' => $this->basicPlan->id,
        ]);

        // Log various subscription events
        $events = [
            ClubSubscriptionEvent::TYPE_SUBSCRIPTION_CREATED,
            ClubSubscriptionEvent::TYPE_PAYMENT_SUCCEEDED,
            ClubSubscriptionEvent::TYPE_SUBSCRIPTION_RENEWED,
        ];

        foreach ($events as $eventType) {
            ClubSubscriptionEvent::factory()->for($this->tenant)->for($club)->create([
                'event_type' => $eventType,
                'event_date' => now(),
            ]);
        }

        // Verify events are logged
        $loggedEvents = ClubSubscriptionEvent::where('club_id', $club->id)->get();

        $this->assertCount(3, $loggedEvents);
        $this->assertTrue($loggedEvents->pluck('event_type')->contains(ClubSubscriptionEvent::TYPE_SUBSCRIPTION_CREATED));
        $this->assertTrue($loggedEvents->pluck('event_type')->contains(ClubSubscriptionEvent::TYPE_PAYMENT_SUCCEEDED));
        $this->assertTrue($loggedEvents->pluck('event_type')->contains(ClubSubscriptionEvent::TYPE_SUBSCRIPTION_RENEWED));
    }

    /** @test */
    public function test_cohort_analysis_for_multiple_clubs()
    {
        $cohortMonth = Carbon::create(2025, 1, 1);

        // Create 20 clubs in January 2025 cohort
        $clubs = Club::factory()->for($this->tenant)->count(20)->create([
            'subscription_status' => 'active',
            'subscription_started_at' => $cohortMonth->copy()->addDays(rand(1, 28)),
            'club_subscription_plan_id' => $this->basicPlan->id,
        ]);

        // Create cohort analysis
        $cohort = ClubSubscriptionCohort::factory()->for($this->tenant)->create([
            'cohort_month' => $cohortMonth,
            'cohort_size' => 20,
            'retention_month_1' => 100.0,
            'retention_month_2' => 95.0,
            'retention_month_3' => 90.0,
            'retention_month_6' => 85.0,
            'retention_month_12' => 75.0,
            'cumulative_revenue' => 24000.00,
            'avg_ltv' => 1200.00,
        ]);

        // Retrieve cohort analysis
        $analysis = $this->service->getCohortAnalysis($this->tenant, '2025-01');

        $this->assertEquals('2025-01', $analysis['cohort']);
        $this->assertEquals(20, $analysis['cohort_size']);
        $this->assertEquals(100.0, $analysis['retention_by_month'][1]);
        $this->assertEquals(75.0, $analysis['retention_by_month'][12]);
        $this->assertEquals('good', $analysis['retention_trend']);
    }

    /** @test */
    public function test_churn_analysis_with_real_cancellations()
    {
        $month = Carbon::create(2025, 3, 1);

        // Create 50 active clubs
        $clubs = Club::factory()->for($this->tenant)->count(50)->create([
            'subscription_status' => 'active',
            'subscription_started_at' => $month->copy()->subMonths(6),
            'club_subscription_plan_id' => $this->basicPlan->id,
        ]);

        // 5 clubs cancel voluntarily
        $canceledClubs = $clubs->take(5);
        foreach ($canceledClubs as $club) {
            $club->update(['subscription_status' => 'canceled']);

            ClubSubscriptionEvent::factory()->for($this->tenant)->for($club)->create([
                'event_type' => ClubSubscriptionEvent::TYPE_SUBSCRIPTION_CANCELED,
                'event_date' => $month->copy()->addDays(rand(1, 28)),
                'cancellation_reason' => ClubSubscriptionEvent::REASON_VOLUNTARY,
                'mrr_change' => -50.00,
            ]);
        }

        // 3 clubs fail payment (involuntary)
        $failedClubs = $clubs->slice(5, 3);
        foreach ($failedClubs as $club) {
            ClubSubscriptionEvent::factory()->for($this->tenant)->for($club)->create([
                'event_type' => ClubSubscriptionEvent::TYPE_SUBSCRIPTION_CANCELED,
                'event_date' => $month->copy()->addDays(rand(1, 28)),
                'cancellation_reason' => ClubSubscriptionEvent::REASON_PAYMENT_FAILED,
                'mrr_change' => -50.00,
            ]);
        }

        // Calculate churn
        $churnData = $this->service->calculateMonthlyChurnRate($this->tenant, $month);

        $this->assertEquals(5, $churnData['voluntary_churn']);
        $this->assertEquals(3, $churnData['involuntary_churn']);
        $this->assertEquals(8, $churnData['churned_customers']);

        // Verify churn reasons
        $reasons = $this->service->getChurnReasons($this->tenant, 1);

        $this->assertEquals(5, $reasons['voluntary']['count']);
        $this->assertEquals(3, $reasons['payment_failed']['count']);
    }

    /** @test */
    public function test_ltv_calculation_with_real_revenue_data()
    {
        // Create clubs with varying subscription durations
        $shortLivedClub = Club::factory()->for($this->tenant)->create([
            'subscription_started_at' => now()->subMonths(6),
            'subscription_ends_at' => now(),
            'club_subscription_plan_id' => $this->basicPlan->id,
        ]);

        $mediumLivedClub = Club::factory()->for($this->tenant)->create([
            'subscription_started_at' => now()->subMonths(12),
            'subscription_ends_at' => null,
            'subscription_status' => 'active',
            'club_subscription_plan_id' => $this->basicPlan->id,
        ]);

        $longLivedClub = Club::factory()->for($this->tenant)->create([
            'subscription_started_at' => now()->subMonths(24),
            'subscription_ends_at' => null,
            'subscription_status' => 'active',
            'club_subscription_plan_id' => $this->proPlan->id,
        ]);

        // Calculate average LTV
        $avgLTV = $this->service->calculateAverageLTV($this->tenant);

        $this->assertGreaterThan(0, $avgLTV);

        // Get customer lifetime stats
        $stats = $this->service->getCustomerLifetimeStats($this->tenant);

        $this->assertArrayHasKey('avg_subscription_duration_days', $stats);
        $this->assertArrayHasKey('avg_ltv', $stats);
        $this->assertGreaterThan(0, $stats['total_lifetime_revenue']);
    }

    /** @test */
    public function test_plan_upgrades_tracked_in_analytics()
    {
        // Create club on Basic plan
        $club = Club::factory()->for($this->tenant)->create([
            'subscription_status' => 'active',
            'club_subscription_plan_id' => $this->basicPlan->id,
            'subscription_started_at' => now()->subMonths(3),
        ]);

        // Upgrade to Pro plan
        $club->update(['club_subscription_plan_id' => $this->proPlan->id]);

        ClubSubscriptionEvent::factory()->for($this->tenant)->for($club)->create([
            'event_type' => ClubSubscriptionEvent::TYPE_PLAN_UPGRADED,
            'old_plan_id' => $this->basicPlan->id,
            'new_plan_id' => $this->proPlan->id,
            'event_date' => now(),
            'mrr_change' => 100.00, // 150 - 50
        ]);

        // Verify upgrade/downgrade rates
        $rates = $this->service->getUpgradeDowngradeRates($this->tenant, 1);

        $this->assertEquals(1, $rates['upgrades']);
        $this->assertEquals(0, $rates['downgrades']);
        $this->assertEquals(1, $rates['net_change']);
    }

    /** @test */
    public function test_trial_conversion_tracking_end_to_end()
    {
        // 10 trials started
        $trialClubs = Club::factory()->for($this->tenant)->count(10)->create([
            'subscription_status' => 'trialing',
            'subscription_started_at' => now()->subDays(25),
            'subscription_trial_ends_at' => now()->addDays(5),
            'club_subscription_plan_id' => $this->basicPlan->id,
        ]);

        foreach ($trialClubs as $club) {
            ClubSubscriptionEvent::factory()->for($this->tenant)->for($club)->create([
                'event_type' => ClubSubscriptionEvent::TYPE_TRIAL_STARTED,
                'event_date' => now()->subDays(25),
            ]);
        }

        // 7 trials convert to paid
        $convertedClubs = $trialClubs->take(7);
        foreach ($convertedClubs as $club) {
            $club->update([
                'subscription_status' => 'active',
                'subscription_trial_ends_at' => null,
            ]);

            ClubSubscriptionEvent::factory()->for($this->tenant)->for($club)->create([
                'event_type' => ClubSubscriptionEvent::TYPE_TRIAL_CONVERTED,
                'event_date' => now()->subDays(rand(1, 15)),
            ]);
        }

        // 3 trials expire without payment
        $expiredClubs = $trialClubs->slice(7, 3);
        foreach ($expiredClubs as $club) {
            $club->update(['subscription_status' => 'canceled']);

            ClubSubscriptionEvent::factory()->for($this->tenant)->for($club)->create([
                'event_type' => ClubSubscriptionEvent::TYPE_TRIAL_EXPIRED,
                'event_date' => now()->subDays(rand(1, 10)),
            ]);
        }

        // Calculate trial conversion rate
        $conversionRate = $this->service->getTrialConversionRate($this->tenant, 30);

        $this->assertEquals(70.0, $conversionRate); // 7/10 * 100
    }

    /** @test */
    public function test_multi_tenant_analytics_isolation()
    {
        // Create second tenant with clubs
        $tenant2 = Tenant::factory()->create(['name' => 'Tenant 2']);

        $plan2 = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant2->id,
            'price' => 75.00,
        ]);

        // Tenant 1: 5 clubs @ 50 EUR = 250 EUR MRR
        Club::factory()->for($this->tenant)->count(5)->create([
            'subscription_status' => 'active',
            'club_subscription_plan_id' => $this->basicPlan->id,
        ]);

        // Tenant 2: 3 clubs @ 75 EUR = 225 EUR MRR
        Club::factory()->for($tenant2)->count(3)->create([
            'subscription_status' => 'active',
            'club_subscription_plan_id' => $plan2->id,
        ]);

        // Calculate MRR for each tenant
        $mrr1 = $this->service->calculateTenantMRR($this->tenant);
        $mrr2 = $this->service->calculateTenantMRR($tenant2);

        // Verify isolation
        $this->assertEquals(250.00, $mrr1);
        $this->assertEquals(225.00, $mrr2);
        $this->assertNotEquals($mrr1, $mrr2);

        // Verify active subscription counts
        $count1 = $this->service->getActiveSubscriptionsCount($this->tenant);
        $count2 = $this->service->getActiveSubscriptionsCount($tenant2);

        $this->assertEquals(5, $count1);
        $this->assertEquals(3, $count2);
    }

    /** @test */
    public function test_analytics_caching_behavior()
    {
        Club::factory()->for($this->tenant)->count(10)->create([
            'subscription_status' => 'active',
            'club_subscription_plan_id' => $this->basicPlan->id,
        ]);

        // First call - should calculate and cache
        $startTime = microtime(true);
        $mrr1 = $this->service->calculateTenantMRR($this->tenant);
        $duration1 = microtime(true) - $startTime;

        // Verify cache was set
        $cacheKey = "subscription:mrr:{$this->tenant->id}";
        $this->assertTrue(Cache::has($cacheKey));

        // Second call - should use cache (faster)
        $startTime = microtime(true);
        $mrr2 = $this->service->calculateTenantMRR($this->tenant);
        $duration2 = microtime(true) - $startTime;

        // Verify same result
        $this->assertEquals($mrr1, $mrr2);

        // Verify cached call was faster (though timing can be flaky in tests)
        // Just verify cache is working by checking Cache::has
        $this->assertTrue(Cache::has($cacheKey));

        // Clear cache
        Cache::flush();
        $this->assertFalse(Cache::has($cacheKey));

        // Third call - should recalculate
        $mrr3 = $this->service->calculateTenantMRR($this->tenant);

        $this->assertEquals($mrr1, $mrr3);
        $this->assertTrue(Cache::has($cacheKey));
    }
}
