<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ClubUsageTrackingService;
use App\Models\Club;
use App\Models\ClubUsage;
use App\Models\Tenant;
use App\Models\ClubSubscriptionPlan;
use App\Exceptions\UsageQuotaExceededException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;

class ClubUsageTrackingServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClubUsageTrackingService $service;
    private Club $club;
    private Tenant $tenant;
    private ClubSubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ClubUsageTrackingService();

        // Create tenant
        $this->tenant = Tenant::factory()->create([
            'subscription_tier' => 'professional',
        ]);

        // Set tenant context
        app()->instance('tenant', $this->tenant);

        // Create subscription plan with limits
        $this->plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Plan',
            'limits' => [
                'max_teams' => 10,
                'max_players' => 100,
                'max_games_per_month' => 50,
            ],
        ]);

        // Create club
        $this->club = Club::factory()->create([
            'tenant_id' => $this->tenant->id,
            'club_subscription_plan_id' => $this->plan->id,
        ]);
    }

    protected function tearDown(): void
    {
        // Clean up Redis
        Redis::flushdb();
        parent::tearDown();
    }

    /** @test */
    public function it_tracks_resource_usage()
    {
        $this->service->trackResource($this->club, 'max_teams', 1);

        // Check Redis
        $cacheKey = $this->getCacheKey('max_teams');
        $this->assertEquals(1, Redis::get($cacheKey));

        // Check database
        $this->assertDatabaseHas('club_usages', [
            'club_id' => $this->club->id,
            'tenant_id' => $this->tenant->id,
            'metric' => 'max_teams',
            'usage_count' => 1,
        ]);
    }

    /** @test */
    public function it_tracks_multiple_increments()
    {
        $this->service->trackResource($this->club, 'max_teams', 3);
        $this->service->trackResource($this->club, 'max_teams', 2);

        $usage = $this->service->getCurrentUsage($this->club, 'max_teams');
        $this->assertEquals(5, $usage);
    }

    /** @test */
    public function it_untracks_resource_usage()
    {
        // Track 5, then untrack 2
        $this->service->trackResource($this->club, 'max_teams', 5);
        $this->service->untrackResource($this->club, 'max_teams', 2);

        $usage = $this->service->getCurrentUsage($this->club, 'max_teams');
        $this->assertEquals(3, $usage);
    }

    /** @test */
    public function it_prevents_negative_usage_on_untrack()
    {
        // Try to untrack more than exists
        $this->service->trackResource($this->club, 'max_teams', 2);
        $this->service->untrackResource($this->club, 'max_teams', 5);

        $usage = $this->service->getCurrentUsage($this->club, 'max_teams');
        $this->assertEquals(0, $usage); // Should not go negative
    }

    /** @test */
    public function it_syncs_usage_from_actual_database_counts()
    {
        // Create actual teams
        $team1 = \App\Models\Team::factory()->create(['club_id' => $this->club->id]);
        $team2 = \App\Models\Team::factory()->create(['club_id' => $this->club->id]);
        $team3 = \App\Models\Team::factory()->create(['club_id' => $this->club->id]);

        $synced = $this->service->syncClubUsage($this->club, ['max_teams']);

        $this->assertEquals(3, $synced['max_teams']);
        $this->assertEquals(3, $this->service->getCurrentUsage($this->club, 'max_teams'));
    }

    /** @test */
    public function it_checks_limit_returns_true_when_within_limit()
    {
        $this->service->trackResource($this->club, 'max_teams', 5);

        $canUse = $this->service->checkLimit($this->club, 'max_teams', 3);
        $this->assertTrue($canUse); // 5 + 3 = 8, limit is 10
    }

    /** @test */
    public function it_checks_limit_returns_false_when_over_limit()
    {
        $this->service->trackResource($this->club, 'max_teams', 9);

        $canUse = $this->service->checkLimit($this->club, 'max_teams', 2);
        $this->assertFalse($canUse); // 9 + 2 = 11, exceeds limit of 10
    }

    /** @test */
    public function it_checks_limit_returns_true_for_unlimited()
    {
        // Create plan with unlimited teams
        $unlimitedPlan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'limits' => ['max_teams' => -1],
        ]);

        $this->club->update(['club_subscription_plan_id' => $unlimitedPlan->id]);

        $this->service->trackResource($this->club, 'max_teams', 1000);

        $canUse = $this->service->checkLimit($this->club, 'max_teams', 1000);
        $this->assertTrue($canUse); // Unlimited should always return true
    }

    /** @test */
    public function it_throws_exception_when_require_limit_exceeds_quota()
    {
        $this->expectException(UsageQuotaExceededException::class);
        $this->expectExceptionMessage("has exceeded the usage quota for 'max_teams'");

        $this->service->trackResource($this->club, 'max_teams', 9);
        $this->service->requireLimit($this->club, 'max_teams', 2); // Would exceed 10
    }

    /** @test */
    public function it_does_not_throw_exception_when_require_limit_is_within_quota()
    {
        $this->service->trackResource($this->club, 'max_teams', 5);

        // Should not throw
        $this->service->requireLimit($this->club, 'max_teams', 3);

        $this->assertTrue(true); // If we reach here, no exception was thrown
    }

    /** @test */
    public function it_gets_current_usage_from_redis_cache()
    {
        $cacheKey = $this->getCacheKey('max_teams');
        Redis::setex($cacheKey, 86400, 7);

        $usage = $this->service->getCurrentUsage($this->club, 'max_teams');
        $this->assertEquals(7, $usage);
    }

    /** @test */
    public function it_falls_back_to_database_when_redis_empty()
    {
        ClubUsage::create([
            'club_id' => $this->club->id,
            'tenant_id' => $this->tenant->id,
            'metric' => 'max_teams',
            'usage_count' => 5,
            'period_start' => now()->startOfMonth(),
        ]);

        Redis::flushdb(); // Clear Redis cache

        $usage = $this->service->getCurrentUsage($this->club, 'max_teams');
        $this->assertEquals(5, $usage);
    }

    /** @test */
    public function it_calculates_usage_percentage_correctly()
    {
        $this->service->trackResource($this->club, 'max_teams', 5);

        $percentage = $this->service->getUsagePercentage($this->club, 'max_teams');
        $this->assertEquals(50.0, $percentage); // 5 / 10 * 100 = 50%
    }

    /** @test */
    public function it_caps_usage_percentage_at_100()
    {
        $this->service->trackResource($this->club, 'max_teams', 15); // Over limit

        $percentage = $this->service->getUsagePercentage($this->club, 'max_teams');
        $this->assertEquals(100.0, $percentage); // Capped at 100%, not 150%
    }

    /** @test */
    public function it_returns_zero_percentage_for_unlimited_limit()
    {
        $unlimitedPlan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'limits' => ['max_teams' => -1],
        ]);

        $this->club->update(['club_subscription_plan_id' => $unlimitedPlan->id]);

        $this->service->trackResource($this->club, 'max_teams', 100);

        $percentage = $this->service->getUsagePercentage($this->club, 'max_teams');
        $this->assertEquals(0, $percentage); // Unlimited = 0% shown
    }

    /** @test */
    public function it_gets_all_usage_with_details()
    {
        $this->service->trackResource($this->club, 'max_teams', 5);
        $this->service->trackResource($this->club, 'max_players', 60);

        $allUsage = $this->service->getAllUsage($this->club);

        $this->assertArrayHasKey('max_teams', $allUsage);
        $this->assertArrayHasKey('max_players', $allUsage);

        $this->assertEquals(5, $allUsage['max_teams']['current']);
        $this->assertEquals(10, $allUsage['max_teams']['limit']);
        $this->assertEquals(5, $allUsage['max_teams']['remaining']);
        $this->assertEquals(50.0, $allUsage['max_teams']['percentage']);
        $this->assertFalse($allUsage['max_teams']['near_limit']);

        $this->assertEquals(60, $allUsage['max_players']['current']);
        $this->assertEquals(100, $allUsage['max_players']['limit']);
        $this->assertFalse($allUsage['max_players']['near_limit']); // 60% is not near limit
    }

    /** @test */
    public function it_detects_near_limit_usage()
    {
        $this->service->trackResource($this->club, 'max_teams', 9); // 90% of limit

        $allUsage = $this->service->getAllUsage($this->club);

        $this->assertTrue($allUsage['max_teams']['near_limit']); // >80%
        $this->assertFalse($allUsage['max_teams']['over_limit']);
    }

    /** @test */
    public function it_detects_over_limit_usage()
    {
        $this->service->trackResource($this->club, 'max_teams', 12); // Over limit of 10

        $allUsage = $this->service->getAllUsage($this->club);

        $this->assertTrue($allUsage['max_teams']['over_limit']);
    }

    /** @test */
    public function it_resets_usage_for_metric()
    {
        $this->service->trackResource($this->club, 'max_teams', 7);
        $this->assertEquals(7, $this->service->getCurrentUsage($this->club, 'max_teams'));

        $this->service->resetUsage($this->club, 'max_teams');

        $this->assertEquals(0, $this->service->getCurrentUsage($this->club, 'max_teams'));
        $this->assertDatabaseMissing('club_usages', [
            'club_id' => $this->club->id,
            'metric' => 'max_teams',
            'period_start' => now()->startOfMonth(),
        ]);
    }

    /** @test */
    public function it_gets_approaching_limits()
    {
        $this->service->trackResource($this->club, 'max_teams', 9); // 90% - approaching
        $this->service->trackResource($this->club, 'max_players', 30); // 30% - not approaching

        $approaching = $this->service->getApproachingLimits($this->club);

        $this->assertArrayHasKey('max_teams', $approaching);
        $this->assertArrayNotHasKey('max_players', $approaching);
    }

    /** @test */
    public function it_provides_upgrade_recommendation_when_near_limit()
    {
        $this->service->trackResource($this->club, 'max_teams', 9); // 90%

        $recommendation = $this->service->getUpgradeRecommendation($this->club);

        $this->assertNotNull($recommendation);
        $this->assertEquals('Test Plan', $recommendation['current_plan']);
        $this->assertContains('max_teams', $recommendation['affected_metrics']);
        $this->assertStringContainsString('upgrade', strtolower($recommendation['recommendation']));
    }

    /** @test */
    public function it_returns_null_recommendation_when_usage_is_low()
    {
        $this->service->trackResource($this->club, 'max_teams', 3); // 30% - low usage

        $recommendation = $this->service->getUpgradeRecommendation($this->club);

        $this->assertNull($recommendation);
    }

    /** @test */
    public function it_gets_usage_statistics_for_analytics()
    {
        // Create multiple usage records
        ClubUsage::create([
            'club_id' => $this->club->id,
            'tenant_id' => $this->tenant->id,
            'metric' => 'max_teams',
            'usage_count' => 5,
            'period_start' => now()->startOfMonth(),
            'created_at' => now()->subDays(10),
        ]);

        ClubUsage::create([
            'club_id' => $this->club->id,
            'tenant_id' => $this->tenant->id,
            'metric' => 'max_teams',
            'usage_count' => 7,
            'period_start' => now()->startOfMonth(),
            'created_at' => now()->subDays(5),
        ]);

        $stats = $this->service->getUsageStats($this->club, 30);

        $this->assertArrayHasKey('max_teams', $stats);
        $this->assertEquals(12, $stats['max_teams']['total_usage']); // 5 + 7
    }

    /**
     * Helper method to get Redis cache key.
     */
    private function getCacheKey(string $metric): string
    {
        return "club_usage:{$this->club->id}:{$metric}:" . now()->format('Y-m');
    }
}
