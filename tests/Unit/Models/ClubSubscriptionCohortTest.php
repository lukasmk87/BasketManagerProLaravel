<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\ClubSubscriptionCohort;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ClubSubscriptionCohortTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
    }

    /** @test */
    /** @test */
    public function by_year_scope()
    {
        ClubSubscriptionCohort::factory()
            ->for($this->tenant)
            ->forMonth(2025, 3)
            ->create();

        ClubSubscriptionCohort::factory()
            ->for($this->tenant)
            ->forMonth(2025, 6)
            ->create();

        ClubSubscriptionCohort::factory()
            ->for($this->tenant)
            ->forMonth(2024, 12)
            ->create();

        $cohorts2025 = ClubSubscriptionCohort::byYear(2025)->get();

        $this->assertCount(2, $cohorts2025);
        $this->assertTrue($cohorts2025->every(function ($cohort) {
            return $cohort->cohort_month->year === 2025;
        }));
    }

    /** @test */
    /** @test */
    public function recent_scope()
    {
        // Create cohorts from last 6 months
        for ($i = 1; $i <= 6; $i++) {
            ClubSubscriptionCohort::factory()
                ->for($this->tenant)
                ->forMonth(now()->subMonths($i)->year, now()->subMonths($i)->month)
                ->create();
        }

        // Create old cohort (should be excluded)
        ClubSubscriptionCohort::factory()
            ->for($this->tenant)
            ->forMonth(now()->subMonths(13)->year, now()->subMonths(13)->month)
            ->create();

        $recentCohorts = ClubSubscriptionCohort::recent(12)->get();

        $this->assertCount(6, $recentCohorts);
    }

    /** @test */
    /** @test */
    public function needs_recalculation_scope()
    {
        // Fresh cohorts (recently calculated)
        ClubSubscriptionCohort::factory()
            ->for($this->tenant)
            ->fresh()
            ->count(2)
            ->create();

        // Stale cohorts (needs recalculation)
        ClubSubscriptionCohort::factory()
            ->for($this->tenant)
            ->stale()
            ->count(3)
            ->create();

        // Never calculated
        ClubSubscriptionCohort::factory()
            ->for($this->tenant)
            ->neverCalculated()
            ->create();

        $needsRecalc = ClubSubscriptionCohort::needsRecalculation(7)->get();

        $this->assertCount(4, $needsRecalc); // 3 stale + 1 never calculated
    }

    /** @test */
    /** @test */
    public function get_retention_for_month()
    {
        $cohort = ClubSubscriptionCohort::factory()
            ->for($this->tenant)
            ->create([
                'retention_month_1' => 100.0,
                'retention_month_2' => 90.0,
                'retention_month_3' => 80.0,
                'retention_month_6' => 70.0,
                'retention_month_12' => 60.0,
            ]);

        $this->assertEquals(100.0, $cohort->getRetentionForMonth(1));
        $this->assertEquals(90.0, $cohort->getRetentionForMonth(2));
        $this->assertEquals(80.0, $cohort->getRetentionForMonth(3));
        $this->assertEquals(70.0, $cohort->getRetentionForMonth(6));
        $this->assertEquals(60.0, $cohort->getRetentionForMonth(12));
        $this->assertNull($cohort->getRetentionForMonth(5)); // Not tracked
    }

    /** @test */
    /** @test */
    public function retention_data_attribute()
    {
        $cohort = ClubSubscriptionCohort::factory()
            ->for($this->tenant)
            ->excellentRetention()
            ->create();

        $retentionData = $cohort->retention_data;

        $this->assertIsArray($retentionData);
        $this->assertArrayHasKey(1, $retentionData);
        $this->assertArrayHasKey(2, $retentionData);
        $this->assertArrayHasKey(3, $retentionData);
        $this->assertArrayHasKey(6, $retentionData);
        $this->assertArrayHasKey(12, $retentionData);
    }

    /** @test */
    /** @test */
    public function retention_drop_attribute()
    {
        $cohort = ClubSubscriptionCohort::factory()
            ->for($this->tenant)
            ->create([
                'retention_month_1' => 100.0,
                'retention_month_12' => 65.0,
            ]);

        $this->assertEquals(35.0, $cohort->retention_drop); // 100 - 65 = 35
    }

    /** @test */
    /** @test */
    public function age_in_months_attribute()
    {
        Carbon::setTestNow(Carbon::create(2025, 6, 1));

        $cohort = ClubSubscriptionCohort::factory()
            ->for($this->tenant)
            ->forMonth(2025, 1) // 5 months ago
            ->create();

        $this->assertEquals(5, $cohort->age_in_months);

        Carbon::setTestNow(); // Reset
    }

    /** @test */
    /** @test */
    public function is_mature_method()
    {
        $matureCohort = ClubSubscriptionCohort::factory()
            ->for($this->tenant)
            ->mature()
            ->create();

        $immatureCohort = ClubSubscriptionCohort::factory()
            ->for($this->tenant)
            ->immature()
            ->create();

        $this->assertTrue($matureCohort->isMature());
        $this->assertFalse($immatureCohort->isMature());
    }

    /** @test */
    /** @test */
    public function is_stale_method()
    {
        $staleCohort = ClubSubscriptionCohort::factory()
            ->for($this->tenant)
            ->stale()
            ->create();

        $freshCohort = ClubSubscriptionCohort::factory()
            ->for($this->tenant)
            ->fresh()
            ->create();

        $neverCalculatedCohort = ClubSubscriptionCohort::factory()
            ->for($this->tenant)
            ->neverCalculated()
            ->create();

        $this->assertTrue($staleCohort->isStale(7));
        $this->assertFalse($freshCohort->isStale(7));
        $this->assertTrue($neverCalculatedCohort->isStale(7));
    }

    /** @test */
    /** @test */
    public function retention_trend_attribute()
    {
        $excellentCohort = ClubSubscriptionCohort::factory()
            ->for($this->tenant)
            ->mature()
            ->excellentRetention()
            ->create();

        $goodCohort = ClubSubscriptionCohort::factory()
            ->for($this->tenant)
            ->mature()
            ->goodRetention()
            ->create();

        $moderateCohort = ClubSubscriptionCohort::factory()
            ->for($this->tenant)
            ->mature()
            ->moderateRetention()
            ->create();

        $poorCohort = ClubSubscriptionCohort::factory()
            ->for($this->tenant)
            ->mature()
            ->poorRetention()
            ->create();

        $immatureCohort = ClubSubscriptionCohort::factory()
            ->for($this->tenant)
            ->immature()
            ->create();

        $this->assertEquals('excellent', $excellentCohort->retention_trend);
        $this->assertEquals('good', $goodCohort->retention_trend);
        $this->assertEquals('moderate', $moderateCohort->retention_trend);
        $this->assertEquals('poor', $poorCohort->retention_trend);
        $this->assertEquals('too_early', $immatureCohort->retention_trend);
    }
}
