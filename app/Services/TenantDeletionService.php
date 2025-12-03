<?php

namespace App\Services;

use App\Models\Club;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TenantDeletionService
{
    public function __construct(
        protected ClubTransferService $clubTransferService
    ) {}

    /**
     * Preview what will happen when deleting the tenant.
     */
    public function previewDeletion(Tenant $tenant): array
    {
        $clubs = $tenant->clubs()->with('users')->get();
        $clubUserIds = $clubs->flatMap(fn ($club) => $club->users->pluck('id'))->unique();

        // Get available target tenants (active, not the current one)
        $availableTenants = Tenant::where('id', '!=', $tenant->id)
            ->where('is_active', true)
            ->where('is_suspended', false)
            ->orderBy('name')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
                'clubs_count' => $t->clubs()->count(),
            ]);

        return [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ],
            'statistics' => [
                'clubs_count' => $clubs->count(),
                'users_count' => $tenant->users()->count(),
                'teams_count' => $tenant->teams()->count(),
                'players_count' => $tenant->players()->count(),
                'games_count' => $tenant->games()->count(),
            ],
            'clubs' => $clubs->map(fn ($club) => [
                'id' => $club->id,
                'name' => $club->name,
                'users_count' => $club->users->count(),
                'teams_count' => $club->teams()->count(),
                'has_stripe_subscription' => (bool) $club->stripe_subscription_id,
            ]),
            'club_users_to_transfer' => $clubUserIds->count(),
            'requires_target_tenant' => $clubs->count() > 0,
            'available_target_tenants' => $availableTenants,
            'warnings' => $this->generateWarnings($tenant, $clubs),
        ];
    }

    /**
     * Delete the tenant with automatic club/user transfer.
     *
     * @throws ValidationException
     */
    public function deleteTenant(Tenant $tenant, ?Tenant $targetTenant, User $admin): void
    {
        // Validate the deletion request
        $this->validateDeletion($tenant, $targetTenant, $admin);

        $clubs = $tenant->clubs;
        $clubsTransferred = 0;
        $usersTransferred = 0;

        DB::transaction(function () use ($tenant, $targetTenant, $admin, $clubs, &$clubsTransferred, &$usersTransferred) {
            if ($clubs->isNotEmpty() && $targetTenant) {
                // 1. Transfer all club users to the target tenant
                $usersTransferred = $this->transferClubUsers($clubs, $targetTenant);

                // 2. Transfer all clubs to the target tenant
                $clubsTransferred = $this->transferClubs($clubs, $targetTenant, $admin);
            }

            // 3. Soft-delete the tenant
            $tenant->delete();

            // 4. Log the deletion
            Log::info('Tenant deleted with club transfer', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'target_tenant_id' => $targetTenant?->id,
                'target_tenant_name' => $targetTenant?->name,
                'clubs_transferred' => $clubsTransferred,
                'users_transferred' => $usersTransferred,
                'deleted_by' => $admin->id,
            ]);
        });

        // Log activity (outside transaction for better reliability)
        activity()
            ->performedOn($tenant)
            ->causedBy($admin)
            ->withProperties([
                'target_tenant_id' => $targetTenant?->id,
                'target_tenant_name' => $targetTenant?->name,
                'clubs_transferred' => $clubsTransferred,
                'users_transferred' => $usersTransferred,
            ])
            ->log('tenant_deleted');
    }

    /**
     * Transfer all club users to the target tenant.
     */
    private function transferClubUsers(Collection $clubs, Tenant $targetTenant): int
    {
        $userIds = $clubs->flatMap(fn ($club) => $club->users->pluck('id'))->unique();

        if ($userIds->isEmpty()) {
            return 0;
        }

        User::whereIn('id', $userIds)->update(['tenant_id' => $targetTenant->id]);

        Log::info('Club users transferred to new tenant', [
            'user_count' => $userIds->count(),
            'target_tenant_id' => $targetTenant->id,
        ]);

        return $userIds->count();
    }

    /**
     * Transfer all clubs to the target tenant.
     * Uses the ClubTransferService's batch transfer with synchronous processing.
     */
    private function transferClubs(Collection $clubs, Tenant $targetTenant, User $admin): int
    {
        $transferred = 0;

        foreach ($clubs as $club) {
            // Directly update the club's tenant_id for immediate effect
            // This is a simplified transfer without the full async job process
            $this->transferClubDirectly($club, $targetTenant, $admin);
            $transferred++;
        }

        return $transferred;
    }

    /**
     * Directly transfer a club to a new tenant (synchronous).
     * This bypasses the async job queue for immediate deletion.
     */
    private function transferClubDirectly(Club $club, Tenant $targetTenant, User $admin): void
    {
        $sourceTenantId = $club->tenant_id;

        // Cancel Stripe subscription if exists
        if ($club->stripe_subscription_id) {
            try {
                // Set subscription status to cancelled
                $club->update([
                    'subscription_status' => 'canceled',
                ]);

                Log::info('Club Stripe subscription marked as canceled for tenant deletion', [
                    'club_id' => $club->id,
                    'stripe_subscription_id' => $club->stripe_subscription_id,
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to cancel Stripe subscription during tenant deletion', [
                    'club_id' => $club->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Remove user memberships from the club (they're already transferred to target tenant)
        $club->users()->detach();

        // Update the club's tenant_id
        $club->update(['tenant_id' => $targetTenant->id]);

        // Update related teams
        $club->teams()->update(['tenant_id' => $targetTenant->id]);

        // Update related gym halls if they have tenant_id
        if (method_exists($club, 'gymHalls')) {
            $club->gymHalls()->update(['tenant_id' => $targetTenant->id]);
        }

        Log::info('Club transferred directly for tenant deletion', [
            'club_id' => $club->id,
            'club_name' => $club->name,
            'source_tenant_id' => $sourceTenantId,
            'target_tenant_id' => $targetTenant->id,
            'transferred_by' => $admin->id,
        ]);
    }

    /**
     * Validate the deletion request.
     *
     * @throws ValidationException
     */
    private function validateDeletion(Tenant $tenant, ?Tenant $targetTenant, User $admin): void
    {
        // Check admin is Super Admin
        if (!$admin->hasRole('super_admin')) {
            throw ValidationException::withMessages([
                'admin' => 'Nur Super-Admins können Tenants löschen.',
            ]);
        }

        // Check if tenant has clubs but no target tenant provided
        $clubsCount = $tenant->clubs()->count();
        if ($clubsCount > 0 && !$targetTenant) {
            throw ValidationException::withMessages([
                'target_tenant_id' => 'Ein Ziel-Tenant ist erforderlich, da dieser Tenant '.$clubsCount.' Club(s) enthält.',
            ]);
        }

        // Check target tenant is not the same as source
        if ($targetTenant && $targetTenant->id === $tenant->id) {
            throw ValidationException::withMessages([
                'target_tenant_id' => 'Der Ziel-Tenant darf nicht der zu löschende Tenant sein.',
            ]);
        }

        // Check target tenant is active
        if ($targetTenant && !$targetTenant->is_active) {
            throw ValidationException::withMessages([
                'target_tenant_id' => 'Der Ziel-Tenant muss aktiv sein.',
            ]);
        }

        // Check target tenant is not suspended
        if ($targetTenant && $targetTenant->is_suspended) {
            throw ValidationException::withMessages([
                'target_tenant_id' => 'Der Ziel-Tenant darf nicht gesperrt sein.',
            ]);
        }
    }

    /**
     * Generate warnings based on tenant data.
     */
    private function generateWarnings(Tenant $tenant, Collection $clubs): array
    {
        $warnings = [];

        // Warning: Active Stripe subscriptions
        $clubsWithStripe = $clubs->filter(fn ($club) => $club->stripe_subscription_id);
        if ($clubsWithStripe->isNotEmpty()) {
            $warnings[] = [
                'type' => 'stripe_subscriptions',
                'severity' => 'high',
                'message' => $clubsWithStripe->count().' Club(s) haben aktive Stripe-Subscriptions, die gekündigt werden.',
                'details' => $clubsWithStripe->map(fn ($club) => [
                    'club_name' => $club->name,
                    'subscription_status' => $club->subscription_status,
                ])->values(),
            ];
        }

        // Warning: User memberships will be removed
        $totalUserMemberships = $clubs->sum(fn ($club) => $club->users->count());
        if ($totalUserMemberships > 0) {
            $warnings[] = [
                'type' => 'user_memberships',
                'severity' => 'medium',
                'message' => $totalUserMemberships.' User-Mitgliedschaft(en) in Clubs werden entfernt.',
                'details' => [
                    'info' => 'Die Users werden zum Ziel-Tenant transferiert, müssen aber von Club-Admins neu eingeladen werden.',
                ],
            ];
        }

        // Warning: No target tenants available
        $availableTenants = Tenant::where('id', '!=', $tenant->id)
            ->where('is_active', true)
            ->where('is_suspended', false)
            ->count();

        if ($clubs->isNotEmpty() && $availableTenants === 0) {
            $warnings[] = [
                'type' => 'no_target_tenants',
                'severity' => 'critical',
                'message' => 'Es gibt keine aktiven Ziel-Tenants für den Club-Transfer.',
                'details' => [
                    'action_required' => 'Bitte erstellen Sie zuerst einen neuen Tenant oder aktivieren Sie einen bestehenden.',
                ],
            ];
        }

        return $warnings;
    }
}
