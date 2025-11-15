<?php

namespace App\Services;

use App\Events\ClubTransferCompleted;
use App\Events\ClubTransferFailed;
use App\Events\ClubTransferInitiated;
use App\Events\ClubTransferRolledBack;
use App\Jobs\ProcessBatchClubTransferJob;
use App\Jobs\ProcessClubTransferJob;
use App\Models\Club;
use App\Models\ClubTransfer;
use App\Models\ClubTransferLog;
use App\Models\ClubTransferRollbackData;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Stripe\ClubSubscriptionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ClubTransferService
{
    public function __construct(
        protected ClubSubscriptionService $subscriptionService,
        protected TenantService $tenantService
    ) {}

    /**
     * Preview transfer impact and provide analysis.
     */
    public function previewTransfer(Club $club, Tenant $targetTenant): array
    {
        $sourceTenant = $club->tenant;

        // Basic information
        $preview = [
            'club' => [
                'id' => $club->id,
                'name' => $club->name,
                'current_tenant' => $sourceTenant->name,
                'target_tenant' => $targetTenant->name,
            ],
            'data_to_transfer' => [],
            'data_to_remove' => [],
            'warnings' => [],
            'target_tenant_capacity' => [],
        ];

        // Count related data
        $teamsCount = $club->teams()->count();
        $usersCount = $club->users()->count();
        $gymHallsCount = $club->gymHalls()->count();

        // Calculate media size
        $mediaSize = $this->calculateMediaSize($club);

        $preview['data_to_transfer'] = [
            'club_record' => 1,
            'teams' => $teamsCount,
            'gym_halls' => $gymHallsCount,
            'media_files' => $club->getMedia()->count(),
            'media_size_mb' => round($mediaSize / 1024 / 1024, 2),
        ];

        $preview['data_to_remove'] = [
            'user_memberships' => $usersCount,
            'stripe_subscription' => $club->stripe_subscription_id ? 'yes' : 'no',
            'subscription_events' => $club->subscriptionEvents()->count(),
        ];

        // Check Stripe subscription
        if ($club->stripe_subscription_id) {
            $preview['warnings'][] = [
                'type' => 'stripe_subscription',
                'severity' => 'high',
                'message' => 'Aktive Stripe-Subscription wird gekündigt',
                'details' => [
                    'subscription_id' => $club->stripe_subscription_id,
                    'plan' => $club->clubSubscriptionPlan?->name ?? 'Unknown',
                    'status' => $club->subscription_status,
                ],
            ];
        }

        // Check user memberships
        if ($usersCount > 0) {
            $preview['warnings'][] = [
                'type' => 'user_memberships',
                'severity' => 'medium',
                'message' => "Alle {$usersCount} User-Zuordnungen werden entfernt",
                'details' => [
                    'affected_users' => $usersCount,
                    'action_required' => 'Club-Admin muss User im neuen Tenant neu einladen',
                ],
            ];
        }

        // Check target tenant capacity
        $targetTenantClubs = $targetTenant->clubs()->count();
        $targetTenantLimits = $this->getTenantLimits($targetTenant);

        $preview['target_tenant_capacity'] = [
            'current_clubs' => $targetTenantClubs,
            'max_clubs' => $targetTenantLimits['max_clubs'] ?? 'unlimited',
            'has_capacity' => $targetTenantLimits['max_clubs'] === null || $targetTenantClubs < $targetTenantLimits['max_clubs'],
        ];

        // Check if target tenant has capacity
        if (isset($targetTenantLimits['max_clubs']) && $targetTenantClubs >= $targetTenantLimits['max_clubs']) {
            $preview['warnings'][] = [
                'type' => 'tenant_capacity',
                'severity' => 'critical',
                'message' => 'Ziel-Tenant hat keine Kapazität für weitere Clubs',
                'details' => [
                    'current' => $targetTenantClubs,
                    'max' => $targetTenantLimits['max_clubs'],
                ],
            ];
        }

        // Rollback information
        $preview['rollback_info'] = [
            'available' => true,
            'window' => '24 hours',
            'expires_at' => now()->addHours(24)->toDateTimeString(),
        ];

        return $preview;
    }

    /**
     * Transfer a club to a different tenant.
     */
    public function transferClub(Club $club, Tenant $targetTenant, User $admin): ClubTransfer
    {
        // Validation
        $this->validateTransfer($club, $targetTenant, $admin);

        // Create transfer record
        $transfer = ClubTransfer::create([
            'club_id' => $club->id,
            'source_tenant_id' => $club->tenant_id,
            'target_tenant_id' => $targetTenant->id,
            'initiated_by' => $admin->id,
            'status' => ClubTransfer::STATUS_PENDING,
            'can_rollback' => true,
            'rollback_expires_at' => now()->addHours(24),
            'metadata' => [
                'club_name' => $club->name,
                'source_tenant_name' => $club->tenant->name,
                'target_tenant_name' => $targetTenant->name,
                'initiated_at' => now()->toDateTimeString(),
            ],
        ]);

        // Log initiation
        $transfer->addLog(
            ClubTransferLog::STEP_VALIDATION,
            ClubTransferLog::STATUS_STARTED,
            'Transfer initiated by Super Admin',
            ['admin_id' => $admin->id, 'admin_name' => $admin->name]
        );

        // Dispatch job for async processing
        ProcessClubTransferJob::dispatch($transfer);

        // Fire event
        event(new ClubTransferInitiated($transfer));

        return $transfer;
    }

    /**
     * Process the actual transfer (called by job).
     */
    public function processTransfer(ClubTransfer $transfer): void
    {
        $startTime = microtime(true);

        try {
            // Mark as processing
            $transfer->markAsProcessing();

            $club = $transfer->club;
            $targetTenant = $transfer->targetTenant;

            DB::beginTransaction();

            // Step 1: Validation
            $this->logStep($transfer, ClubTransferLog::STEP_VALIDATION, function() use ($club, $targetTenant, $transfer) {
                $this->validateTransfer($club, $targetTenant, $transfer->initiatedBy);
            });

            // Step 2: Create rollback snapshot
            $this->logStep($transfer, ClubTransferLog::STEP_ROLLBACK_SNAPSHOT, function() use ($transfer, $club) {
                $this->createRollbackSnapshot($transfer, $club);
            });

            // Step 3: Cancel Stripe subscription
            if ($club->stripe_subscription_id) {
                $this->logStep($transfer, ClubTransferLog::STEP_STRIPE_CANCELLATION, function() use ($transfer, $club) {
                    $this->cancelStripeSubscription($transfer, $club);
                });
            }

            // Step 4: Remove user memberships
            $this->logStep($transfer, ClubTransferLog::STEP_MEMBERSHIP_REMOVAL, function() use ($transfer, $club) {
                $this->removeAllMemberships($transfer, $club);
            });

            // Step 5: Migrate media files
            $this->logStep($transfer, ClubTransferLog::STEP_MEDIA_MIGRATION, function() use ($transfer, $club, $targetTenant) {
                $this->migrateMediaFiles($transfer, $club, $targetTenant);
            });

            // Step 6: Update club tenant_id
            $this->logStep($transfer, ClubTransferLog::STEP_CLUB_UPDATE, function() use ($transfer, $club, $targetTenant) {
                $this->updateClubTenant($transfer, $club, $targetTenant);
            });

            // Step 7: Update related records
            $this->logStep($transfer, ClubTransferLog::STEP_RELATED_RECORDS_UPDATE, function() use ($transfer, $club, $targetTenant) {
                $this->updateRelatedRecords($transfer, $club, $targetTenant);
            });

            // Step 8: Clear caches
            $this->logStep($transfer, ClubTransferLog::STEP_CACHE_CLEAR, function() use ($transfer, $club, $targetTenant) {
                $this->clearCaches($transfer, $club, $targetTenant);
            });

            DB::commit();

            // Mark as completed
            $transfer->markAsCompleted();

            $duration = round((microtime(true) - $startTime) * 1000);
            $transfer->addLog(
                ClubTransferLog::STEP_COMPLETION,
                ClubTransferLog::STATUS_COMPLETED,
                'Transfer completed successfully',
                ['total_duration_ms' => $duration]
            );

            // Fire event
            event(new ClubTransferCompleted($transfer));

        } catch (\Exception $e) {
            DB::rollBack();

            $duration = round((microtime(true) - $startTime) * 1000);

            // Mark as failed
            $transfer->markAsFailed($e->getMessage());

            $transfer->addLog(
                ClubTransferLog::STEP_COMPLETION,
                ClubTransferLog::STATUS_FAILED,
                'Transfer failed: ' . $e->getMessage(),
                [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'total_duration_ms' => $duration,
                ]
            );

            // Fire event
            event(new ClubTransferFailed($transfer, $e));

            Log::error('Club transfer failed', [
                'transfer_id' => $transfer->id,
                'club_id' => $transfer->club_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Rollback a transfer within the 24h window.
     */
    public function rollbackTransfer(ClubTransfer $transfer): void
    {
        $startTime = microtime(true);

        // Validate rollback is possible
        if (!$transfer->canBeRolledBack()) {
            throw new \Exception('Transfer cannot be rolled back (either expired or already rolled back)');
        }

        try {
            DB::beginTransaction();

            $transfer->addLog(
                ClubTransferLog::STEP_ROLLBACK,
                ClubTransferLog::STATUS_STARTED,
                'Rollback initiated'
            );

            // Get all rollback data
            $rollbackData = $transfer->rollbackData()->get();

            // Group by table and operation type
            $updates = $rollbackData->where('operation_type', ClubTransferRollbackData::OPERATION_UPDATE);
            $deletes = $rollbackData->where('operation_type', ClubTransferRollbackData::OPERATION_DELETE);

            // Restore updates (reverse changes)
            foreach ($updates as $snapshot) {
                DB::table($snapshot->table_name)
                    ->where('id', $snapshot->record_id)
                    ->update($snapshot->record_data);
            }

            // Restore deletes (recreate records)
            foreach ($deletes as $snapshot) {
                DB::table($snapshot->table_name)->insert($snapshot->record_data);
            }

            // Clear caches for both tenants
            $club = $transfer->club;
            Cache::tags(['tenant:' . $transfer->source_tenant_id])->flush();
            Cache::tags(['tenant:' . $transfer->target_tenant_id])->flush();

            DB::commit();

            // Mark as rolled back
            $transfer->markAsRolledBack();

            $duration = round((microtime(true) - $startTime) * 1000);
            $transfer->addLog(
                ClubTransferLog::STEP_ROLLBACK,
                ClubTransferLog::STATUS_COMPLETED,
                'Rollback completed successfully',
                [
                    'records_restored' => $rollbackData->count(),
                    'duration_ms' => $duration,
                ]
            );

            // Fire event
            event(new ClubTransferRolledBack($transfer));

        } catch (\Exception $e) {
            DB::rollBack();

            $transfer->addLog(
                ClubTransferLog::STEP_ROLLBACK,
                ClubTransferLog::STATUS_FAILED,
                'Rollback failed: ' . $e->getMessage(),
                [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]
            );

            Log::error('Club transfer rollback failed', [
                'transfer_id' => $transfer->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Transfer multiple clubs in batch.
     */
    public function batchTransfer(array $clubIds, Tenant $targetTenant, User $admin): array
    {
        $transfers = [];

        foreach ($clubIds as $clubId) {
            $club = Club::findOrFail($clubId);
            $transfer = $this->transferClub($club, $targetTenant, $admin);
            $transfers[] = $transfer;
        }

        // Optionally dispatch batch job
        // ProcessBatchClubTransferJob::dispatch($transfers);

        return $transfers;
    }

    /**
     * Private helper methods
     */

    /**
     * Validate transfer is possible.
     */
    private function validateTransfer(Club $club, Tenant $targetTenant, User $admin): void
    {
        // Check admin is Super Admin
        if (!$admin->hasRole('super_admin')) {
            throw new \Exception('Only Super Admins can transfer clubs');
        }

        // Check club exists
        if (!$club->exists) {
            throw new \Exception('Club does not exist');
        }

        // Check target tenant exists and is active
        if (!$targetTenant->exists || $targetTenant->status !== 'active') {
            throw new \Exception('Target tenant does not exist or is not active');
        }

        // Check club is not already in target tenant
        if ($club->tenant_id === $targetTenant->id) {
            throw new \Exception('Club is already in the target tenant');
        }

        // Check target tenant capacity
        $limits = $this->getTenantLimits($targetTenant);
        if (isset($limits['max_clubs'])) {
            $currentClubs = $targetTenant->clubs()->count();
            if ($currentClubs >= $limits['max_clubs']) {
                throw new \Exception('Target tenant has reached maximum club capacity');
            }
        }
    }

    /**
     * Create rollback snapshot of current state.
     */
    private function createRollbackSnapshot(ClubTransfer $transfer, Club $club): void
    {
        // Snapshot club record
        ClubTransferRollbackData::createSnapshot(
            $transfer->id,
            'clubs',
            $club->id,
            $club->toArray(),
            ClubTransferRollbackData::OPERATION_UPDATE
        );

        // Snapshot user memberships (will be deleted)
        $memberships = DB::table('club_user')->where('club_id', $club->id)->get();
        foreach ($memberships as $membership) {
            ClubTransferRollbackData::createSnapshot(
                $transfer->id,
                'club_user',
                $membership->id ?? uniqid(),
                (array) $membership,
                ClubTransferRollbackData::OPERATION_DELETE
            );
        }

        // Snapshot teams
        foreach ($club->teams as $team) {
            ClubTransferRollbackData::createSnapshot(
                $transfer->id,
                'teams',
                $team->id,
                $team->toArray(),
                ClubTransferRollbackData::OPERATION_UPDATE
            );
        }

        // Snapshot gym halls
        foreach ($club->gymHalls as $gymHall) {
            ClubTransferRollbackData::createSnapshot(
                $transfer->id,
                'gym_halls',
                $gymHall->id,
                $gymHall->toArray(),
                ClubTransferRollbackData::OPERATION_UPDATE
            );
        }
    }

    /**
     * Cancel Stripe subscription.
     */
    private function cancelStripeSubscription(ClubTransfer $transfer, Club $club): void
    {
        if (!$club->stripe_subscription_id) {
            return;
        }

        try {
            // Cancel subscription immediately
            $this->subscriptionService->cancel($club, true);

            $transfer->setMetadata('stripe_subscription_cancelled', true);
            $transfer->setMetadata('cancelled_subscription_id', $club->stripe_subscription_id);

        } catch (\Exception $e) {
            Log::warning('Failed to cancel Stripe subscription during transfer', [
                'club_id' => $club->id,
                'subscription_id' => $club->stripe_subscription_id,
                'error' => $e->getMessage(),
            ]);

            // Continue with transfer even if Stripe cancellation fails
            $transfer->setMetadata('stripe_cancellation_error', $e->getMessage());
        }
    }

    /**
     * Remove all user memberships.
     */
    private function removeAllMemberships(ClubTransfer $transfer, Club $club): void
    {
        $count = DB::table('club_user')->where('club_id', $club->id)->count();

        DB::table('club_user')->where('club_id', $club->id)->delete();

        $transfer->setMetadata('removed_memberships_count', $count);
    }

    /**
     * Migrate media files to target tenant storage.
     */
    private function migrateMediaFiles(ClubTransfer $transfer, Club $club, Tenant $targetTenant): void
    {
        $sourceTenant = $club->tenant;
        $mediaItems = $club->getMedia();

        $migratedCount = 0;
        $failedCount = 0;

        foreach ($mediaItems as $media) {
            try {
                // Old path: tenants/{source_id}/...
                // New path: tenants/{target_id}/...
                $oldPath = $media->getPath();
                $newPath = str_replace(
                    "tenants/{$sourceTenant->id}",
                    "tenants/{$targetTenant->id}",
                    $oldPath
                );

                // Copy file if it exists
                if (Storage::exists($oldPath)) {
                    Storage::copy($oldPath, $newPath);
                    $migratedCount++;
                }

                // Update media record path
                $media->update([
                    'custom_properties' => array_merge(
                        $media->custom_properties ?? [],
                        ['original_tenant_id' => $sourceTenant->id]
                    )
                ]);

            } catch (\Exception $e) {
                $failedCount++;
                Log::warning('Failed to migrate media file', [
                    'media_id' => $media->id,
                    'club_id' => $club->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $transfer->setMetadata('media_migration', [
            'total' => $mediaItems->count(),
            'migrated' => $migratedCount,
            'failed' => $failedCount,
        ]);
    }

    /**
     * Update club's tenant_id.
     */
    private function updateClubTenant(ClubTransfer $transfer, Club $club, Tenant $targetTenant): void
    {
        // Update without triggering tenant scope
        DB::table('clubs')
            ->where('id', $club->id)
            ->update([
                'tenant_id' => $targetTenant->id,
                'updated_at' => now(),
            ]);

        // Refresh club instance
        $club->refresh();
    }

    /**
     * Update related records (teams, gym halls, etc.).
     */
    private function updateRelatedRecords(ClubTransfer $transfer, Club $club, Tenant $targetTenant): void
    {
        // Update teams if they have tenant_id
        if (Schema::hasColumn('teams', 'tenant_id')) {
            DB::table('teams')
                ->where('club_id', $club->id)
                ->update(['tenant_id' => $targetTenant->id]);
        }

        // Update gym halls if they have tenant_id
        if (Schema::hasColumn('gym_halls', 'tenant_id')) {
            DB::table('gym_halls')
                ->where('club_id', $club->id)
                ->update(['tenant_id' => $targetTenant->id]);
        }

        // Additional related records can be added here
    }

    /**
     * Clear caches for both tenants.
     */
    private function clearCaches(ClubTransfer $transfer, Club $club, Tenant $targetTenant): void
    {
        $sourceTenantId = $transfer->source_tenant_id;

        // Clear tenant caches
        Cache::tags(['tenant:' . $sourceTenantId])->flush();
        Cache::tags(['tenant:' . $targetTenant->id])->flush();

        // Clear club-specific caches
        Cache::forget("club:{$club->id}");
        Cache::forget("club:{$club->id}:teams");
        Cache::forget("club:{$club->id}:users");

        // Use TenantService to clear tenant caches
        $this->tenantService->clearTenantCache($targetTenant);
    }

    /**
     * Calculate total media size in bytes.
     */
    private function calculateMediaSize(Club $club): int
    {
        $totalSize = 0;

        foreach ($club->getMedia() as $media) {
            $totalSize += $media->size;
        }

        return $totalSize;
    }

    /**
     * Get tenant limits.
     */
    private function getTenantLimits(Tenant $tenant): array
    {
        // This would integrate with your existing tenant limits system
        return [
            'max_clubs' => $tenant->subscription_plan_limits['max_clubs'] ?? null,
            'max_users' => $tenant->subscription_plan_limits['max_users'] ?? null,
        ];
    }

    /**
     * Helper to log a step with timing.
     */
    private function logStep(ClubTransfer $transfer, string $step, callable $callback): void
    {
        $startTime = microtime(true);

        $transfer->addLog($step, ClubTransferLog::STATUS_STARTED, "Starting {$step}");

        try {
            $callback();

            $duration = round((microtime(true) - $startTime) * 1000);
            $transfer->addLog(
                $step,
                ClubTransferLog::STATUS_COMPLETED,
                "Completed {$step}",
                null,
                $duration
            );

        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000);
            $transfer->addLog(
                $step,
                ClubTransferLog::STATUS_FAILED,
                "Failed {$step}: " . $e->getMessage(),
                ['error' => $e->getMessage()],
                $duration
            );

            throw $e;
        }
    }
}
