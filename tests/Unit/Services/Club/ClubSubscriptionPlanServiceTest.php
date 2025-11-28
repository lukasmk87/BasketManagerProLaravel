<?php

namespace Tests\Unit\Services\Club;

use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use App\Models\Tenant;
use App\Services\Club\ClubSubscriptionPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubSubscriptionPlanServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClubSubscriptionPlanService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ClubSubscriptionPlanService();
    }

    public function test_creates_club_plan(): void
    {
        $tenant = Tenant::factory()->create([
            'subscription_tier' => 'professional',
        ]);

        $data = [
            'name' => 'Basic Plan',
            'slug' => 'basic',
            'description' => 'Basic club plan',
            'price' => 49.99,
            'currency' => 'EUR',
            'billing_interval' => 'month',
            'is_active' => true,
        ];

        $plan = $this->service->createClubPlan($tenant, $data);

        $this->assertInstanceOf(ClubSubscriptionPlan::class, $plan);
        $this->assertEquals('Basic Plan', $plan->name);
        $this->assertEquals($tenant->id, $plan->tenant_id);
        $this->assertEquals(49.99, $plan->price);
    }

    public function test_updates_club_plan(): void
    {
        $tenant = Tenant::factory()->create([
            'subscription_tier' => 'professional',
        ]);

        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Original Name',
            'price' => 49.99,
        ]);

        $updatedPlan = $this->service->updateClubPlan($plan, [
            'name' => 'Updated Name',
            'price' => 99.99,
        ]);

        $this->assertEquals('Updated Name', $updatedPlan->name);
        $this->assertEquals(99.99, $updatedPlan->price);
    }

    public function test_assigns_plan_to_club(): void
    {
        $tenant = Tenant::factory()->create();

        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->service->assignPlanToClub($club, $plan);

        $club->refresh();
        $this->assertEquals($plan->id, $club->club_subscription_plan_id);
    }

    public function test_gets_clubs_without_plan(): void
    {
        $tenant = Tenant::factory()->create();

        $clubWithPlan = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'club_subscription_plan_id' => ClubSubscriptionPlan::factory()->create([
                'tenant_id' => $tenant->id,
            ])->id,
        ]);

        $clubWithoutPlan = Club::factory()->create([
            'tenant_id' => $tenant->id,
            'club_subscription_plan_id' => null,
        ]);

        $clubsWithoutPlan = $this->service->getClubsWithoutPlan($tenant);

        $this->assertCount(1, $clubsWithoutPlan);
        $this->assertEquals($clubWithoutPlan->id, $clubsWithoutPlan->first()->id);
    }

    public function test_gets_club_usage_stats(): void
    {
        $tenant = Tenant::factory()->create();

        $club = Club::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $stats = $this->service->getClubUsageStats($club);

        $this->assertIsArray($stats);
    }
}
