<?php

namespace Tests\Feature;

use App\Models\ApiUsageTracking;
use App\Models\Club;
use App\Models\ClubSubscriptionCohort;
use App\Models\ClubSubscriptionEvent;
use App\Models\ClubUsage;
use App\Models\DBBIntegration;
use App\Models\FIBAIntegration;
use App\Models\LandingPageContent;
use App\Models\SubscriptionMRRSnapshot;
use App\Models\Tenant;
use App\Models\TenantPlanCustomization;
use App\Models\TenantUsage;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * SEC-003: Tenant Isolation Tests
 *
 * Diese Tests verifizieren, dass alle Models mit tenant_id das BelongsToTenant
 * Trait verwenden und damit automatisch tenant-isoliert sind.
 *
 * TDD-Ansatz: Tests werden ZUERST geschrieben (werden initial rot sein),
 * dann wird das BelongsToTenant Trait zu jedem Model hinzugefügt.
 */
class SEC003TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected User $userA;
    protected User $userB;
    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupRoles();
        $this->setupTenants();
    }

    protected function setupRoles(): void
    {
        // Create required roles for testing
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    }

    protected function setupTenants(): void
    {
        // Create two isolated tenants
        $this->tenantA = Tenant::factory()->create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'is_active' => true,
        ]);

        $this->tenantB = Tenant::factory()->create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
            'is_active' => true,
        ]);

        // Create users for each tenant
        $this->userA = User::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'email' => 'user@tenant-a.test',
        ]);

        $this->userB = User::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'email' => 'user@tenant-b.test',
        ]);

        // Create super admin (tenant_id = null)
        $this->superAdmin = User::factory()->create([
            'tenant_id' => null,
            'email' => 'super@admin.test',
        ]);
        $this->superAdmin->assignRole('super_admin');
    }

    /**
     * Helper: Set current tenant context
     */
    protected function setTenantContext(Tenant $tenant): void
    {
        app()->instance('tenant', $tenant);
    }

    /**
     * Helper: Clear tenant context
     */
    protected function clearTenantContext(): void
    {
        app()->forgetInstance('tenant');
    }

    // =========================================================================
    // WebhookEvent Tests
    // =========================================================================

    /** @test */
    public function webhook_events_are_scoped_to_tenant(): void
    {
        // Arrange: Create webhook events for both tenants
        WebhookEvent::create([
            'stripe_event_id' => 'evt_tenant_a_1',
            'event_type' => 'customer.subscription.created',
            'tenant_id' => $this->tenantA->id,
            'status' => WebhookEvent::STATUS_PROCESSED,
            'payload' => ['test' => 'data'],
            'livemode' => false,
        ]);

        WebhookEvent::create([
            'stripe_event_id' => 'evt_tenant_b_1',
            'event_type' => 'invoice.payment_succeeded',
            'tenant_id' => $this->tenantB->id,
            'status' => WebhookEvent::STATUS_PROCESSED,
            'payload' => ['test' => 'data'],
            'livemode' => false,
        ]);

        // Act: Set tenant context to Tenant A and query
        $this->setTenantContext($this->tenantA);
        $this->actingAs($this->userA);

        $events = WebhookEvent::all();

        // Assert: Only Tenant A's events should be returned
        $this->assertCount(1, $events);
        $this->assertEquals($this->tenantA->id, $events->first()->tenant_id);
        $this->assertEquals('evt_tenant_a_1', $events->first()->stripe_event_id);
    }

    /** @test */
    public function super_admin_can_see_all_webhook_events(): void
    {
        // Arrange: Create webhook events for both tenants
        WebhookEvent::create([
            'stripe_event_id' => 'evt_tenant_a_2',
            'event_type' => 'customer.subscription.created',
            'tenant_id' => $this->tenantA->id,
            'status' => WebhookEvent::STATUS_PROCESSED,
            'payload' => [],
            'livemode' => false,
        ]);

        WebhookEvent::create([
            'stripe_event_id' => 'evt_tenant_b_2',
            'event_type' => 'invoice.payment_succeeded',
            'tenant_id' => $this->tenantB->id,
            'status' => WebhookEvent::STATUS_PROCESSED,
            'payload' => [],
            'livemode' => false,
        ]);

        // Act: Authenticate as super admin (no tenant context)
        $this->actingAs($this->superAdmin);

        $events = WebhookEvent::all();

        // Assert: Super admin should see all events
        $this->assertCount(2, $events);
    }

    // =========================================================================
    // ApiUsageTracking Tests
    // =========================================================================

    /** @test */
    public function api_usage_tracking_is_scoped_to_tenant(): void
    {
        // Arrange
        ApiUsageTracking::create([
            'tenant_id' => $this->tenantA->id,
            'endpoint' => '/api/v4/teams',
            'method' => 'GET',
            'request_count' => 100,
            'date' => now()->toDateString(),
        ]);

        ApiUsageTracking::create([
            'tenant_id' => $this->tenantB->id,
            'endpoint' => '/api/v4/teams',
            'method' => 'GET',
            'request_count' => 200,
            'date' => now()->toDateString(),
        ]);

        // Act
        $this->setTenantContext($this->tenantA);
        $this->actingAs($this->userA);

        $tracking = ApiUsageTracking::all();

        // Assert
        $this->assertCount(1, $tracking);
        $this->assertEquals($this->tenantA->id, $tracking->first()->tenant_id);
        $this->assertEquals(100, $tracking->first()->request_count);
    }

    // =========================================================================
    // ClubSubscriptionEvent Tests
    // =========================================================================

    /** @test */
    public function club_subscription_events_are_scoped_to_tenant(): void
    {
        // Arrange: Create clubs first
        $clubA = Club::factory()->create(['tenant_id' => $this->tenantA->id]);
        $clubB = Club::factory()->create(['tenant_id' => $this->tenantB->id]);

        ClubSubscriptionEvent::create([
            'club_id' => $clubA->id,
            'tenant_id' => $this->tenantA->id,
            'event_type' => 'subscription_created',
            'plan_name' => 'Premium',
            'amount' => 4900,
            'currency' => 'EUR',
        ]);

        ClubSubscriptionEvent::create([
            'club_id' => $clubB->id,
            'tenant_id' => $this->tenantB->id,
            'event_type' => 'subscription_created',
            'plan_name' => 'Basic',
            'amount' => 2900,
            'currency' => 'EUR',
        ]);

        // Act
        $this->setTenantContext($this->tenantA);
        $this->actingAs($this->userA);

        $events = ClubSubscriptionEvent::all();

        // Assert
        $this->assertCount(1, $events);
        $this->assertEquals($this->tenantA->id, $events->first()->tenant_id);
        $this->assertEquals('Premium', $events->first()->plan_name);
    }

    // =========================================================================
    // ClubSubscriptionCohort Tests
    // =========================================================================

    /** @test */
    public function club_subscription_cohorts_are_scoped_to_tenant(): void
    {
        // Arrange
        ClubSubscriptionCohort::create([
            'tenant_id' => $this->tenantA->id,
            'cohort_month' => '2025-01',
            'plan_name' => 'Premium',
            'initial_clubs' => 10,
            'current_clubs' => 8,
        ]);

        ClubSubscriptionCohort::create([
            'tenant_id' => $this->tenantB->id,
            'cohort_month' => '2025-01',
            'plan_name' => 'Basic',
            'initial_clubs' => 20,
            'current_clubs' => 15,
        ]);

        // Act
        $this->setTenantContext($this->tenantA);
        $this->actingAs($this->userA);

        $cohorts = ClubSubscriptionCohort::all();

        // Assert
        $this->assertCount(1, $cohorts);
        $this->assertEquals($this->tenantA->id, $cohorts->first()->tenant_id);
        $this->assertEquals(10, $cohorts->first()->initial_clubs);
    }

    // =========================================================================
    // SubscriptionMRRSnapshot Tests
    // =========================================================================

    /** @test */
    public function subscription_mrr_snapshots_are_scoped_to_tenant(): void
    {
        // Arrange
        SubscriptionMRRSnapshot::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'snapshot_date' => now()->toDateString(),
            'total_mrr' => 10000,
        ]);

        SubscriptionMRRSnapshot::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'snapshot_date' => now()->toDateString(),
            'total_mrr' => 20000,
        ]);

        // Act
        $this->setTenantContext($this->tenantA);
        $this->actingAs($this->userA);

        $snapshots = SubscriptionMRRSnapshot::all();

        // Assert
        $this->assertCount(1, $snapshots);
        $this->assertEquals($this->tenantA->id, $snapshots->first()->tenant_id);
        $this->assertEquals(10000, $snapshots->first()->total_mrr);
    }

    // =========================================================================
    // ClubUsage Tests
    // =========================================================================

    /** @test */
    public function club_usage_is_scoped_to_tenant(): void
    {
        // Arrange
        $clubA = Club::factory()->create(['tenant_id' => $this->tenantA->id]);
        $clubB = Club::factory()->create(['tenant_id' => $this->tenantB->id]);

        ClubUsage::create([
            'club_id' => $clubA->id,
            'tenant_id' => $this->tenantA->id,
            'feature' => 'max_teams',
            'usage_count' => 5,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
        ]);

        ClubUsage::create([
            'club_id' => $clubB->id,
            'tenant_id' => $this->tenantB->id,
            'feature' => 'max_teams',
            'usage_count' => 10,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
        ]);

        // Act
        $this->setTenantContext($this->tenantA);
        $this->actingAs($this->userA);

        $usage = ClubUsage::all();

        // Assert
        $this->assertCount(1, $usage);
        $this->assertEquals($this->tenantA->id, $usage->first()->tenant_id);
        $this->assertEquals(5, $usage->first()->usage_count);
    }

    // =========================================================================
    // TenantUsage Tests
    // =========================================================================

    /** @test */
    public function tenant_usage_is_scoped_to_tenant(): void
    {
        // Arrange
        TenantUsage::create([
            'tenant_id' => $this->tenantA->id,
            'feature' => 'api_calls',
            'usage_count' => 1000,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
        ]);

        TenantUsage::create([
            'tenant_id' => $this->tenantB->id,
            'feature' => 'api_calls',
            'usage_count' => 2000,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
        ]);

        // Act
        $this->setTenantContext($this->tenantA);
        $this->actingAs($this->userA);

        $usage = TenantUsage::all();

        // Assert
        $this->assertCount(1, $usage);
        $this->assertEquals($this->tenantA->id, $usage->first()->tenant_id);
        $this->assertEquals(1000, $usage->first()->usage_count);
    }

    // =========================================================================
    // TenantPlanCustomization Tests
    // =========================================================================

    /** @test */
    public function tenant_plan_customizations_are_scoped_to_tenant(): void
    {
        // Arrange
        TenantPlanCustomization::create([
            'tenant_id' => $this->tenantA->id,
            'plan_id' => 'plan_premium',
            'custom_limits' => ['max_users' => 100],
            'custom_features' => ['video_analysis' => true],
        ]);

        TenantPlanCustomization::create([
            'tenant_id' => $this->tenantB->id,
            'plan_id' => 'plan_basic',
            'custom_limits' => ['max_users' => 50],
            'custom_features' => ['video_analysis' => false],
        ]);

        // Act
        $this->setTenantContext($this->tenantA);
        $this->actingAs($this->userA);

        $customizations = TenantPlanCustomization::all();

        // Assert
        $this->assertCount(1, $customizations);
        $this->assertEquals($this->tenantA->id, $customizations->first()->tenant_id);
        $this->assertEquals('plan_premium', $customizations->first()->plan_id);
    }

    // =========================================================================
    // LandingPageContent Tests
    // =========================================================================

    /** @test */
    public function landing_page_content_is_scoped_to_tenant(): void
    {
        // Arrange
        LandingPageContent::create([
            'tenant_id' => $this->tenantA->id,
            'section' => 'hero',
            'content' => ['title' => 'Welcome to Tenant A'],
            'is_active' => true,
        ]);

        LandingPageContent::create([
            'tenant_id' => $this->tenantB->id,
            'section' => 'hero',
            'content' => ['title' => 'Welcome to Tenant B'],
            'is_active' => true,
        ]);

        // Act
        $this->setTenantContext($this->tenantA);
        $this->actingAs($this->userA);

        $content = LandingPageContent::all();

        // Assert
        $this->assertCount(1, $content);
        $this->assertEquals($this->tenantA->id, $content->first()->tenant_id);
        $this->assertEquals('Welcome to Tenant A', $content->first()->content['title']);
    }

    // =========================================================================
    // FIBAIntegration Tests
    // =========================================================================

    /** @test */
    public function fiba_integrations_are_scoped_to_tenant(): void
    {
        // Arrange
        FIBAIntegration::create([
            'tenant_id' => $this->tenantA->id,
            'api_key' => 'fiba_key_a',
            'organization_id' => 'org_a',
            'is_active' => true,
        ]);

        FIBAIntegration::create([
            'tenant_id' => $this->tenantB->id,
            'api_key' => 'fiba_key_b',
            'organization_id' => 'org_b',
            'is_active' => true,
        ]);

        // Act
        $this->setTenantContext($this->tenantA);
        $this->actingAs($this->userA);

        $integrations = FIBAIntegration::all();

        // Assert
        $this->assertCount(1, $integrations);
        $this->assertEquals($this->tenantA->id, $integrations->first()->tenant_id);
        $this->assertEquals('fiba_key_a', $integrations->first()->api_key);
    }

    // =========================================================================
    // DBBIntegration Tests
    // =========================================================================

    /** @test */
    public function dbb_integrations_are_scoped_to_tenant(): void
    {
        // Arrange
        DBBIntegration::create([
            'tenant_id' => $this->tenantA->id,
            'api_key' => 'dbb_key_a',
            'club_number' => 'DBB001',
            'is_active' => true,
        ]);

        DBBIntegration::create([
            'tenant_id' => $this->tenantB->id,
            'api_key' => 'dbb_key_b',
            'club_number' => 'DBB002',
            'is_active' => true,
        ]);

        // Act
        $this->setTenantContext($this->tenantA);
        $this->actingAs($this->userA);

        $integrations = DBBIntegration::all();

        // Assert
        $this->assertCount(1, $integrations);
        $this->assertEquals($this->tenantA->id, $integrations->first()->tenant_id);
        $this->assertEquals('dbb_key_a', $integrations->first()->api_key);
    }

    // =========================================================================
    // Cross-Tenant Access Prevention Tests
    // =========================================================================

    /** @test */
    public function user_cannot_access_other_tenants_webhook_events_by_id(): void
    {
        // Arrange
        $eventB = WebhookEvent::create([
            'stripe_event_id' => 'evt_tenant_b_secret',
            'event_type' => 'customer.subscription.deleted',
            'tenant_id' => $this->tenantB->id,
            'status' => WebhookEvent::STATUS_PROCESSED,
            'payload' => ['secret' => 'data'],
            'livemode' => false,
        ]);

        // Act
        $this->setTenantContext($this->tenantA);
        $this->actingAs($this->userA);

        // Direct ID access should not return the record
        $foundEvent = WebhookEvent::find($eventB->id);

        // Assert
        $this->assertNull($foundEvent);
    }

    /** @test */
    public function user_cannot_access_other_tenants_api_usage_by_id(): void
    {
        // Arrange
        $trackingB = ApiUsageTracking::create([
            'tenant_id' => $this->tenantB->id,
            'endpoint' => '/api/v4/secret',
            'method' => 'GET',
            'request_count' => 999,
            'date' => now()->toDateString(),
        ]);

        // Act
        $this->setTenantContext($this->tenantA);
        $this->actingAs($this->userA);

        $foundTracking = ApiUsageTracking::find($trackingB->id);

        // Assert
        $this->assertNull($foundTracking);
    }

    // =========================================================================
    // Auto-Assignment Tests
    // =========================================================================

    /** @test */
    public function webhook_event_auto_assigns_tenant_id_on_create(): void
    {
        // Arrange
        $this->setTenantContext($this->tenantA);
        $this->actingAs($this->userA);

        // Act: Create without explicit tenant_id
        $event = WebhookEvent::create([
            'stripe_event_id' => 'evt_auto_assign',
            'event_type' => 'test.event',
            'status' => WebhookEvent::STATUS_PENDING,
            'payload' => [],
            'livemode' => false,
        ]);

        // Assert: tenant_id should be automatically set
        $this->assertEquals($this->tenantA->id, $event->tenant_id);
    }

    /** @test */
    public function api_usage_tracking_auto_assigns_tenant_id_on_create(): void
    {
        // Arrange
        $this->setTenantContext($this->tenantA);
        $this->actingAs($this->userA);

        // Act
        $tracking = ApiUsageTracking::create([
            'endpoint' => '/api/v4/auto-test',
            'method' => 'POST',
            'request_count' => 1,
            'date' => now()->toDateString(),
        ]);

        // Assert
        $this->assertEquals($this->tenantA->id, $tracking->tenant_id);
    }
}
