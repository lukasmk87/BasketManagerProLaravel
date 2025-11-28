<?php

namespace App\Services\Club;

use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use App\Models\Tenant;
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
}
