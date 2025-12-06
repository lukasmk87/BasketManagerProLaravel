<?php

namespace App\Services\Club;

use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class ClubSubscriptionPlanService
{
    /**
     * Create a new club subscription plan for a tenant.
     */
    public function createClubPlan(Tenant $tenant, array $data): ClubSubscriptionPlan
    {
        // Validate against tenant capabilities
        $errors = ClubSubscriptionPlan::validateAgainstTenant($data, $tenant);

        if (!empty($errors)) {
            throw new \InvalidArgumentException(
                'Plan validation failed: ' . json_encode($errors)
            );
        }

        $plan = ClubSubscriptionPlan::create(array_merge($data, [
            'tenant_id' => $tenant->id,
        ]));

        Log::info("Club subscription plan created", [
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'tenant_id' => $tenant->id,
        ]);

        return $plan;
    }

    /**
     * Update a club subscription plan.
     */
    public function updateClubPlan(ClubSubscriptionPlan $plan, array $data): ClubSubscriptionPlan
    {
        // Validate against tenant capabilities
        $errors = ClubSubscriptionPlan::validateAgainstTenant($data, $plan->tenant);

        if (!empty($errors)) {
            throw new \InvalidArgumentException(
                'Plan validation failed: ' . json_encode($errors)
            );
        }

        $plan->update($data);

        Log::info("Club subscription plan updated", [
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
        ]);

        return $plan->fresh();
    }

    /**
     * Assign a plan to a club.
     */
    public function assignPlanToClub(Club $club, ClubSubscriptionPlan $plan): void
    {
        $club->assignPlan($plan);

        Log::info("Club subscription plan assigned", [
            'club_id' => $club->id,
            'club_name' => $club->name,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
        ]);
    }

    /**
     * Get clubs without a subscription plan for a tenant.
     */
    public function getClubsWithoutPlan(Tenant $tenant)
    {
        return Club::where('tenant_id', $tenant->id)
            ->whereNull('club_subscription_plan_id')
            ->get();
    }

    /**
     * Get usage statistics for a club.
     */
    public function getClubUsageStats(Club $club): array
    {
        return $club->getSubscriptionLimits();
    }

    /**
     * Get publicly available plans (featured + active) for a tenant.
     * These are shown on the landing page and available for new club registration.
     */
    public function getPublicPlans(Tenant $tenant): Collection
    {
        return ClubSubscriptionPlan::forTenant($tenant->id)
            ->publiclyAvailable()
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();
    }

    /**
     * Get all active plans (including non-featured) for admin use.
     */
    public function getAllPlansForAdmin(Tenant $tenant): Collection
    {
        return ClubSubscriptionPlan::forTenant($tenant->id)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();
    }

    /**
     * Check if a user can assign a specific plan to a club.
     * Featured plans can be assigned by anyone (during onboarding).
     * Non-featured plans can only be assigned by Super Admin or Tenant Admin.
     */
    public function canAssignPlan(User $user, ClubSubscriptionPlan $plan): bool
    {
        // Featured plans can be assigned by anyone (during onboarding)
        if ($plan->is_featured && $plan->is_active) {
            return true;
        }

        // Non-featured plans require admin privileges
        return $user->hasRole(['super_admin', 'tenant_admin']);
    }

    /**
     * Get featured plans for a tenant's landing page.
     */
    public function getFeaturedPlans(Tenant $tenant): Collection
    {
        return ClubSubscriptionPlan::forTenant($tenant->id)
            ->publiclyAvailable()
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();
    }
}
