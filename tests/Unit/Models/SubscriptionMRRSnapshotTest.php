<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\SubscriptionMRRSnapshot;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubscriptionMRRSnapshotTest extends TestCase
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
    public function daily_scope_filters_correctly()
    {
        SubscriptionMRRSnapshot::factory()
            ->for($this->tenant)
            ->daily()
            ->count(3)
            ->create();

        SubscriptionMRRSnapshot::factory()
            ->for($this->tenant)
            ->monthly()
            ->count(2)
            ->create();

        $dailySnapshots = SubscriptionMRRSnapshot::daily()->get();

        $this->assertCount(3, $dailySnapshots);
        $this->assertTrue($dailySnapshots->every(fn($s) => $s->snapshot_type === 'daily'));
    }

    /** @test */
    /** @test */
    public function monthly_scope_filters_correctly()
    {
        SubscriptionMRRSnapshot::factory()
            ->for($this->tenant)
            ->daily()
            ->count(2)
            ->create();

        SubscriptionMRRSnapshot::factory()
            ->for($this->tenant)
            ->monthly()
            ->count(3)
            ->create();

        $monthlySnapshots = SubscriptionMRRSnapshot::monthly()->get();

        $this->assertCount(3, $monthlySnapshots);
        $this->assertTrue($monthlySnapshots->every(fn($s) => $s->snapshot_type === 'monthly'));
    }

    /** @test */
    /** @test */
    public function date_range_scope()
    {
        $startDate = Carbon::create(2025, 1, 1);
        $endDate = Carbon::create(2025, 3, 31);

        // Within range
        SubscriptionMRRSnapshot::factory()
            ->for($this->tenant)
            ->forDate(Carbon::create(2025, 2, 15))
            ->create();

        // Before range
        SubscriptionMRRSnapshot::factory()
            ->for($this->tenant)
            ->forDate(Carbon::create(2024, 12, 15))
            ->create();

        // After range
        SubscriptionMRRSnapshot::factory()
            ->for($this->tenant)
            ->forDate(Carbon::create(2025, 4, 15))
            ->create();

        $snapshots = SubscriptionMRRSnapshot::dateRange($startDate, $endDate)->get();

        $this->assertCount(1, $snapshots);
        $this->assertTrue($snapshots->first()->snapshot_date->between($startDate, $endDate));
    }

    /** @test */
    /** @test */
    public function lafor_tenant_scope()
    {
        $otherTenant = Tenant::factory()->create();

        // Create snapshots for main tenant
        SubscriptionMRRSnapshot::factory()
            ->for($this->tenant)
            ->daily()
            ->forDate(Carbon::create(2025, 1, 1))
            ->create();

        $latestSnapshot = SubscriptionMRRSnapshot::factory()
            ->for($this->tenant)
            ->daily()
            ->forDate(Carbon::create(2025, 3, 1))
            ->create();

        // Create snapshot for other tenant (should be excluded)
        SubscriptionMRRSnapshot::factory()
            ->for($otherTenant)
            ->daily()
            ->forDate(Carbon::create(2025, 4, 1))
            ->create();

        $result = SubscriptionMRRSnapshot::latestForTenant($this->tenant->id, 'daily')->first();

        $this->assertEquals($latestSnapshot->id, $result->id);
        $this->assertEquals($this->tenant->id, $result->tenant_id);
    }

    /** @test */
    /** @test */
    public function net_new_mrr_attribute_calculation()
    {
        $snapshot = SubscriptionMRRSnapshot::factory()
            ->for($this->tenant)
            ->create([
                'new_business_mrr' => 500.0,
                'expansion_mrr' => 200.0,
                'contraction_mrr' => 100.0,
                'churned_mrr' => 150.0,
            ]);

        // Net New MRR = 500 + 200 - 100 - 150 = 450
        $this->assertEquals(450.0, $snapshot->net_new_mrr);
    }

    /** @test */
    /** @test */
    public function is_growing_and_is_declining_methods()
    {
        $growingSnapshot = SubscriptionMRRSnapshot::factory()
            ->for($this->tenant)
            ->growing()
            ->create();

        $decliningSnapshot = SubscriptionMRRSnapshot::factory()
            ->for($this->tenant)
            ->declining()
            ->create();

        $flatSnapshot = SubscriptionMRRSnapshot::factory()
            ->for($this->tenant)
            ->create(['mrr_growth_rate' => 0.0]);

        $this->assertTrue($growingSnapshot->isGrowing());
        $this->assertFalse($growingSnapshot->isDeclining());

        $this->assertTrue($decliningSnapshot->isDeclining());
        $this->assertFalse($decliningSnapshot->isGrowing());

        $this->assertFalse($flatSnapshot->isGrowing());
        $this->assertFalse($flatSnapshot->isDeclining());
    }

    /** @test */
    /** @test */
    public function formatted_growth_rate_attribute()
    {
        $positiveSnapshot = SubscriptionMRRSnapshot::factory()
            ->for($this->tenant)
            ->create(['mrr_growth_rate' => 15.50]);

        $negativeSnapshot = SubscriptionMRRSnapshot::factory()
            ->for($this->tenant)
            ->create(['mrr_growth_rate' => -10.25]);

        $this->assertEquals('+15.50%', $positiveSnapshot->formatted_growth_rate);
        $this->assertEquals('-10.25%', $negativeSnapshot->formatted_growth_rate);
    }

    /** @test */
    /** @test */
    public function formatted_mrr_with_currency()
    {
        $snapshot = SubscriptionMRRSnapshot::factory()
            ->for($this->tenant)
            ->withMRR(12345.67)
            ->create();

        $this->assertEquals('12,345.67 EUR', $snapshot->getFormattedMRR());
        $this->assertEquals('12,345.67 USD', $snapshot->getFormattedMRR('USD'));
    }
}
