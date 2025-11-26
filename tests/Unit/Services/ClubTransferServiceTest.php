<?php

namespace Tests\Unit\Services;

use App\Events\ClubTransferCompleted;
use App\Events\ClubTransferFailed;
use App\Events\ClubTransferInitiated;
use App\Events\ClubTransferRolledBack;
use App\Jobs\ProcessClubTransferJob;
use App\Models\Club;
use App\Models\ClubTransfer;
use App\Models\ClubTransferLog;
use App\Models\ClubTransferRollbackData;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ClubTransferService;
use App\Services\Stripe\ClubSubscriptionService;
use App\Services\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ClubTransferServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ClubTransferService $service;
    protected ClubSubscriptionService $subscriptionService;
    protected TenantService $tenantService;
    protected User $superAdmin;
    protected User $regularUser;
    protected Tenant $sourceTenant;
    protected Tenant $targetTenant;
    protected Club $club;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::findOrCreate('super_admin', 'web');

        // Create SuperAdmin user
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');

        // Create regular user
        $this->regularUser = User::factory()->create();

        // Create source and target tenants (is_active = true by default)
        $this->sourceTenant = Tenant::factory()->create(['is_active' => true]);
        $this->targetTenant = Tenant::factory()->create(['is_active' => true]);

        // Create club in source tenant
        $this->club = Club::factory()->forTenant($this->sourceTenant)->create();

        // Mock services
        $this->subscriptionService = Mockery::mock(ClubSubscriptionService::class);
        $this->tenantService = Mockery::mock(TenantService::class);

        // Default mock behaviors
        $this->tenantService->shouldReceive('clearTenantCache')->andReturn(null)->byDefault();
        $this->subscriptionService->shouldReceive('cancel')->andReturn(null)->byDefault();

        // Create service instance
        $this->service = new ClubTransferService(
            $this->subscriptionService,
            $this->tenantService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // GRUPPE 1: PREVIEW TESTS (4 Tests)
    // =========================================================================

    /** @test */
    public function it_can_preview_transfer_with_valid_data(): void
    {
        $preview = $this->service->previewTransfer($this->club, $this->targetTenant);

        $this->assertIsArray($preview);
        $this->assertArrayHasKey('club', $preview);
        $this->assertArrayHasKey('data_to_transfer', $preview);
        $this->assertArrayHasKey('data_to_remove', $preview);
        $this->assertArrayHasKey('warnings', $preview);
        $this->assertArrayHasKey('target_tenant_capacity', $preview);
        $this->assertArrayHasKey('rollback_info', $preview);

        $this->assertEquals($this->club->id, $preview['club']['id']);
        $this->assertEquals($this->club->name, $preview['club']['name']);
        $this->assertEquals($this->sourceTenant->name, $preview['club']['current_tenant']);
        $this->assertEquals($this->targetTenant->name, $preview['club']['target_tenant']);
    }

    /** @test */
    public function it_includes_stripe_warning_in_preview_when_club_has_subscription(): void
    {
        // Set club with Stripe subscription
        $this->club->update(['stripe_subscription_id' => 'sub_test123']);

        $preview = $this->service->previewTransfer($this->club, $this->targetTenant);

        $this->assertNotEmpty($preview['warnings']);

        $stripeWarning = collect($preview['warnings'])->firstWhere('type', 'stripe_subscription');
        $this->assertNotNull($stripeWarning);
        $this->assertEquals('high', $stripeWarning['severity']);
        $this->assertStringContainsString('Stripe', $stripeWarning['message']);
    }

    /** @test */
    public function it_includes_membership_warning_in_preview_when_club_has_users(): void
    {
        // Add users to club
        $users = User::factory()->count(3)->create();
        foreach ($users as $user) {
            DB::table('club_user')->insert([
                'club_id' => $this->club->id,
                'user_id' => $user->id,
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $preview = $this->service->previewTransfer($this->club, $this->targetTenant);

        $membershipWarning = collect($preview['warnings'])->firstWhere('type', 'user_memberships');
        $this->assertNotNull($membershipWarning);
        $this->assertEquals('medium', $membershipWarning['severity']);
        $this->assertEquals(3, $preview['data_to_remove']['user_memberships']);
    }

    /** @test */
    public function it_shows_capacity_warning_when_target_tenant_is_at_max_clubs(): void
    {
        // Set target tenant with 'free' tier which has max_clubs = 1 in config
        $this->targetTenant->update(['subscription_tier' => 'free']);

        // Create a club already in target tenant (hitting the limit of 1)
        Club::factory()->forTenant($this->targetTenant)->create();

        $preview = $this->service->previewTransfer($this->club, $this->targetTenant);

        $capacityWarning = collect($preview['warnings'])->firstWhere('type', 'tenant_capacity');
        $this->assertNotNull($capacityWarning);
        $this->assertEquals('critical', $capacityWarning['severity']);
    }

    // =========================================================================
    // GRUPPE 2: VALIDATION TESTS (5 Tests)
    // =========================================================================

    /** @test */
    public function it_throws_exception_for_non_super_admin_user(): void
    {
        Queue::fake();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only Super Admins can transfer clubs');

        $this->service->transferClub($this->club, $this->targetTenant, $this->regularUser);
    }

    /** @test */
    public function it_throws_exception_for_inactive_target_tenant(): void
    {
        Queue::fake();

        $this->targetTenant->update(['is_active' => false]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Target tenant does not exist or is not active');

        $this->service->transferClub($this->club, $this->targetTenant, $this->superAdmin);
    }

    /** @test */
    public function it_throws_exception_when_club_already_in_target_tenant(): void
    {
        Queue::fake();

        // Move club to target tenant first
        $this->club->update(['tenant_id' => $this->targetTenant->id]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Club is already in the target tenant');

        $this->service->transferClub($this->club, $this->targetTenant, $this->superAdmin);
    }

    /** @test */
    public function it_throws_exception_when_target_tenant_at_capacity(): void
    {
        Queue::fake();

        // Set target tenant with 'free' tier which has max_clubs = 1 in config
        $this->targetTenant->update(['subscription_tier' => 'free']);

        // Create club in target tenant to reach capacity
        Club::factory()->forTenant($this->targetTenant)->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Target tenant has reached maximum club capacity');

        $this->service->transferClub($this->club, $this->targetTenant, $this->superAdmin);
    }

    /** @test */
    public function it_validates_club_exists_before_transfer(): void
    {
        Queue::fake();

        // Create a mock club that doesn't "exist"
        $nonExistentClub = new Club();
        $nonExistentClub->tenant_id = $this->sourceTenant->id;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Club does not exist');

        $this->service->transferClub($nonExistentClub, $this->targetTenant, $this->superAdmin);
    }

    // =========================================================================
    // GRUPPE 3: TRANSFER INITIATION TESTS (5 Tests)
    // =========================================================================

    /** @test */
    public function it_creates_transfer_record_with_pending_status(): void
    {
        Queue::fake();
        Event::fake();

        $transfer = $this->service->transferClub($this->club, $this->targetTenant, $this->superAdmin);

        $this->assertInstanceOf(ClubTransfer::class, $transfer);
        $this->assertEquals(ClubTransfer::STATUS_PENDING, $transfer->status);
        $this->assertEquals($this->club->id, $transfer->club_id);
        $this->assertEquals($this->sourceTenant->id, $transfer->source_tenant_id);
        $this->assertEquals($this->targetTenant->id, $transfer->target_tenant_id);
        $this->assertEquals($this->superAdmin->id, $transfer->initiated_by);
    }

    /** @test */
    public function it_stores_initial_metadata_correctly(): void
    {
        Queue::fake();
        Event::fake();

        $transfer = $this->service->transferClub($this->club, $this->targetTenant, $this->superAdmin);

        $metadata = $transfer->metadata;

        $this->assertIsArray($metadata);
        $this->assertEquals($this->club->name, $metadata['club_name']);
        $this->assertEquals($this->sourceTenant->name, $metadata['source_tenant_name']);
        $this->assertEquals($this->targetTenant->name, $metadata['target_tenant_name']);
        $this->assertArrayHasKey('initiated_at', $metadata);
    }

    /** @test */
    public function it_dispatches_process_job(): void
    {
        Queue::fake();
        Event::fake();

        $transfer = $this->service->transferClub($this->club, $this->targetTenant, $this->superAdmin);

        Queue::assertPushed(ProcessClubTransferJob::class, function ($job) use ($transfer) {
            return $job->transfer->id === $transfer->id;
        });
    }

    /** @test */
    public function it_fires_transfer_initiated_event(): void
    {
        Queue::fake();
        Event::fake();

        $transfer = $this->service->transferClub($this->club, $this->targetTenant, $this->superAdmin);

        Event::assertDispatched(ClubTransferInitiated::class, function ($event) use ($transfer) {
            return $event->transfer->id === $transfer->id;
        });
    }

    /** @test */
    public function it_sets_rollback_window_correctly(): void
    {
        Queue::fake();
        Event::fake();

        $transfer = $this->service->transferClub($this->club, $this->targetTenant, $this->superAdmin);

        $this->assertTrue($transfer->can_rollback);
        $this->assertNotNull($transfer->rollback_expires_at);
        $this->assertTrue($transfer->rollback_expires_at->isFuture());
        // Should be approximately 24 hours from now
        $this->assertEqualsWithDelta(24, now()->diffInHours($transfer->rollback_expires_at), 1);
    }

    // =========================================================================
    // GRUPPE 4: PROCESSING LIFECYCLE TESTS (5 Tests)
    // =========================================================================

    /** @test */
    public function it_transitions_status_through_lifecycle(): void
    {
        Event::fake();
        Cache::shouldReceive('tags->flush')->andReturn(null);
        Cache::shouldReceive('forget')->andReturn(null);

        // Create pending transfer
        $transfer = ClubTransfer::factory()
            ->forClub($this->club)
            ->fromTenant($this->sourceTenant)
            ->toTenant($this->targetTenant)
            ->initiatedBy($this->superAdmin)
            ->pending()
            ->create();

        // Process the transfer
        $this->service->processTransfer($transfer);

        $transfer->refresh();

        $this->assertEquals(ClubTransfer::STATUS_COMPLETED, $transfer->status);
        $this->assertNotNull($transfer->started_at);
        $this->assertNotNull($transfer->completed_at);
    }

    /** @test */
    public function it_logs_all_processing_steps(): void
    {
        Event::fake();
        Cache::shouldReceive('tags->flush')->andReturn(null);
        Cache::shouldReceive('forget')->andReturn(null);

        $transfer = ClubTransfer::factory()
            ->forClub($this->club)
            ->fromTenant($this->sourceTenant)
            ->toTenant($this->targetTenant)
            ->initiatedBy($this->superAdmin)
            ->pending()
            ->create();

        $this->service->processTransfer($transfer);

        // Check that processing steps were logged
        $logs = $transfer->logs()->orderBy('created_at')->get();

        $this->assertGreaterThan(0, $logs->count());

        // Check for key steps
        $steps = $logs->pluck('step')->unique()->toArray();
        $this->assertContains(ClubTransferLog::STEP_VALIDATION, $steps);
        $this->assertContains(ClubTransferLog::STEP_ROLLBACK_SNAPSHOT, $steps);
        $this->assertContains(ClubTransferLog::STEP_MEMBERSHIP_REMOVAL, $steps);
        $this->assertContains(ClubTransferLog::STEP_CLUB_UPDATE, $steps);
    }

    /** @test */
    public function it_updates_club_tenant_id(): void
    {
        Event::fake();
        Cache::shouldReceive('tags->flush')->andReturn(null);
        Cache::shouldReceive('forget')->andReturn(null);

        $transfer = ClubTransfer::factory()
            ->forClub($this->club)
            ->fromTenant($this->sourceTenant)
            ->toTenant($this->targetTenant)
            ->initiatedBy($this->superAdmin)
            ->pending()
            ->create();

        $this->assertEquals($this->sourceTenant->id, $this->club->tenant_id);

        $this->service->processTransfer($transfer);

        $this->club->refresh();
        $this->assertEquals($this->targetTenant->id, $this->club->tenant_id);
    }

    /** @test */
    public function it_removes_user_memberships(): void
    {
        Event::fake();
        Cache::shouldReceive('tags->flush')->andReturn(null);
        Cache::shouldReceive('forget')->andReturn(null);

        // Add users to club
        $users = User::factory()->count(3)->create();
        foreach ($users as $user) {
            DB::table('club_user')->insert([
                'club_id' => $this->club->id,
                'user_id' => $user->id,
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->assertEquals(3, DB::table('club_user')->where('club_id', $this->club->id)->count());

        $transfer = ClubTransfer::factory()
            ->forClub($this->club)
            ->fromTenant($this->sourceTenant)
            ->toTenant($this->targetTenant)
            ->initiatedBy($this->superAdmin)
            ->pending()
            ->create();

        $this->service->processTransfer($transfer);

        $this->assertEquals(0, DB::table('club_user')->where('club_id', $this->club->id)->count());
    }

    /** @test */
    public function it_fires_completed_event_on_success(): void
    {
        Event::fake();
        Cache::shouldReceive('tags->flush')->andReturn(null);
        Cache::shouldReceive('forget')->andReturn(null);

        $transfer = ClubTransfer::factory()
            ->forClub($this->club)
            ->fromTenant($this->sourceTenant)
            ->toTenant($this->targetTenant)
            ->initiatedBy($this->superAdmin)
            ->pending()
            ->create();

        $this->service->processTransfer($transfer);

        Event::assertDispatched(ClubTransferCompleted::class, function ($event) use ($transfer) {
            return $event->transfer->id === $transfer->id;
        });
    }

    // =========================================================================
    // GRUPPE 5: ROLLBACK TESTS (5 Tests)
    // =========================================================================

    /** @test */
    public function it_creates_rollback_snapshots_during_processing(): void
    {
        Event::fake();
        Cache::shouldReceive('tags->flush')->andReturn(null);
        Cache::shouldReceive('forget')->andReturn(null);

        $transfer = ClubTransfer::factory()
            ->forClub($this->club)
            ->fromTenant($this->sourceTenant)
            ->toTenant($this->targetTenant)
            ->initiatedBy($this->superAdmin)
            ->pending()
            ->create();

        $this->service->processTransfer($transfer);

        // Check rollback data was created
        $rollbackData = $transfer->rollbackData()->get();

        $this->assertGreaterThan(0, $rollbackData->count());

        // Should have club snapshot
        $clubSnapshot = $rollbackData->where('table_name', 'clubs')->first();
        $this->assertNotNull($clubSnapshot);
        $this->assertEquals(ClubTransferRollbackData::OPERATION_UPDATE, $clubSnapshot->operation_type);
    }

    /** @test */
    public function it_can_rollback_within_24h_window(): void
    {
        Event::fake();
        Cache::shouldReceive('tags->flush')->andReturn(null);
        Cache::shouldReceive('forget')->andReturn(null);

        // First process a transfer
        $transfer = ClubTransfer::factory()
            ->forClub($this->club)
            ->fromTenant($this->sourceTenant)
            ->toTenant($this->targetTenant)
            ->initiatedBy($this->superAdmin)
            ->pending()
            ->create();

        $this->service->processTransfer($transfer);

        $transfer->refresh();
        $this->assertEquals(ClubTransfer::STATUS_COMPLETED, $transfer->status);
        $this->assertTrue($transfer->canBeRolledBack());

        // Now rollback
        $this->service->rollbackTransfer($transfer);

        $transfer->refresh();
        $this->assertEquals(ClubTransfer::STATUS_ROLLED_BACK, $transfer->status);
        $this->assertFalse($transfer->can_rollback);
    }

    /** @test */
    public function it_throws_exception_for_expired_rollback(): void
    {
        Event::fake();

        // Create completed transfer with expired rollback window
        $transfer = ClubTransfer::factory()
            ->forClub($this->club)
            ->fromTenant($this->sourceTenant)
            ->toTenant($this->targetTenant)
            ->initiatedBy($this->superAdmin)
            ->expiredRollback()
            ->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Transfer cannot be rolled back');

        $this->service->rollbackTransfer($transfer);
    }

    /** @test */
    public function it_restores_original_data_on_rollback(): void
    {
        Event::fake();
        Cache::shouldReceive('tags->flush')->andReturn(null);
        Cache::shouldReceive('forget')->andReturn(null);

        $originalTenantId = $this->club->tenant_id;

        // Process transfer
        $transfer = ClubTransfer::factory()
            ->forClub($this->club)
            ->fromTenant($this->sourceTenant)
            ->toTenant($this->targetTenant)
            ->initiatedBy($this->superAdmin)
            ->pending()
            ->create();

        $this->service->processTransfer($transfer);

        $this->club->refresh();
        $this->assertEquals($this->targetTenant->id, $this->club->tenant_id);

        // Rollback
        $transfer->refresh();
        $this->service->rollbackTransfer($transfer);

        $this->club->refresh();
        $this->assertEquals($originalTenantId, $this->club->tenant_id);
    }

    /** @test */
    public function it_fires_rollback_event(): void
    {
        Event::fake();
        Cache::shouldReceive('tags->flush')->andReturn(null);
        Cache::shouldReceive('forget')->andReturn(null);

        // Process transfer first
        $transfer = ClubTransfer::factory()
            ->forClub($this->club)
            ->fromTenant($this->sourceTenant)
            ->toTenant($this->targetTenant)
            ->initiatedBy($this->superAdmin)
            ->pending()
            ->create();

        $this->service->processTransfer($transfer);

        // Rollback
        $transfer->refresh();
        $this->service->rollbackTransfer($transfer);

        Event::assertDispatched(ClubTransferRolledBack::class, function ($event) use ($transfer) {
            return $event->transfer->id === $transfer->id;
        });
    }

    // =========================================================================
    // GRUPPE 6: ERROR HANDLING TESTS (3 Tests)
    // =========================================================================

    /** @test */
    public function it_rolls_back_transaction_on_failure(): void
    {
        Event::fake();

        // Create transfer
        $transfer = ClubTransfer::factory()
            ->forClub($this->club)
            ->fromTenant($this->sourceTenant)
            ->toTenant($this->targetTenant)
            ->initiatedBy($this->superAdmin)
            ->pending()
            ->create();

        // Force an error by mocking Cache to throw exception
        Cache::shouldReceive('tags->flush')->andThrow(new \Exception('Simulated cache error'));

        $originalTenantId = $this->club->tenant_id;

        try {
            $this->service->processTransfer($transfer);
        } catch (\Exception $e) {
            // Expected exception
        }

        // Club should still be in original tenant due to transaction rollback
        $this->club->refresh();
        $this->assertEquals($originalTenantId, $this->club->tenant_id);
    }

    /** @test */
    public function it_marks_transfer_as_failed_with_reason(): void
    {
        Event::fake();

        $transfer = ClubTransfer::factory()
            ->forClub($this->club)
            ->fromTenant($this->sourceTenant)
            ->toTenant($this->targetTenant)
            ->initiatedBy($this->superAdmin)
            ->pending()
            ->create();

        // Force an error
        Cache::shouldReceive('tags->flush')->andThrow(new \Exception('Test failure reason'));

        try {
            $this->service->processTransfer($transfer);
        } catch (\Exception $e) {
            // Expected
        }

        $transfer->refresh();
        $this->assertEquals(ClubTransfer::STATUS_FAILED, $transfer->status);
        $this->assertNotNull($transfer->failed_at);
        $this->assertStringContainsString('Test failure reason', $transfer->metadata['failure_reason'] ?? '');
    }

    /** @test */
    public function it_fires_failed_event_on_error(): void
    {
        Event::fake();

        $transfer = ClubTransfer::factory()
            ->forClub($this->club)
            ->fromTenant($this->sourceTenant)
            ->toTenant($this->targetTenant)
            ->initiatedBy($this->superAdmin)
            ->pending()
            ->create();

        Cache::shouldReceive('tags->flush')->andThrow(new \Exception('Test failure'));

        try {
            $this->service->processTransfer($transfer);
        } catch (\Exception $e) {
            // Expected
        }

        Event::assertDispatched(ClubTransferFailed::class, function ($event) use ($transfer) {
            return $event->transfer->id === $transfer->id;
        });
    }
}
