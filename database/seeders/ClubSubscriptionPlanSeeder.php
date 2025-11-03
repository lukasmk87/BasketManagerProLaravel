<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\ClubSubscriptionPlan;
use App\Services\Stripe\ClubSubscriptionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class ClubSubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeder.
     *
     * Options:
     * - --sync-stripe : Automatically sync plans with Stripe after creation
     * - --tenant-id=X : Only seed plans for specific tenant
     * - --skip-existing : Skip plans that already exist
     */
    public function run(): void
    {
        $defaultPlans = config('club_plans.default_plans');
        $syncStripe = $this->command->option('sync-stripe') ?? false;
        $tenantId = $this->command->option('tenant-id');
        $skipExisting = $this->command->option('skip-existing') ?? false;

        // Get tenants to seed
        $tenantsQuery = Tenant::where('is_active', true);
        if ($tenantId) {
            $tenantsQuery->where('id', $tenantId);
        }

        $this->command->info("🌱 Starting Club Subscription Plan Seeding...");
        $this->command->info("Options: sync-stripe={$syncStripe}, tenant-id={$tenantId}, skip-existing={$skipExisting}");

        $tenantsQuery->each(function (Tenant $tenant) use ($defaultPlans, $syncStripe, $skipExisting) {
            $this->command->info("\n📦 Processing tenant: {$tenant->name} (ID: {$tenant->id})");

            foreach ($defaultPlans as $planData) {
                $this->seedPlanForTenant($tenant, $planData, $syncStripe, $skipExisting);
            }
        });

        $this->command->info("\n✅ Club subscription plan seeding completed!");
    }

    /**
     * Seed a single plan for a tenant.
     */
    private function seedPlanForTenant(Tenant $tenant, array $planData, bool $syncStripe, bool $skipExisting): void
    {
        // Check if plan already exists
        $existingPlan = ClubSubscriptionPlan::where('tenant_id', $tenant->id)
            ->where('slug', $planData['slug'])
            ->first();

        if ($existingPlan && $skipExisting) {
            $this->command->warn("  ⊘ Skipping existing plan: {$planData['name']}");
            return;
        }

        if ($existingPlan) {
            $this->command->info("  🔄 Updating existing plan: {$planData['name']}");
            $plan = $this->updatePlan($existingPlan, $planData);
        } else {
            $this->command->info("  ➕ Creating new plan: {$planData['name']}");
            $plan = $this->createPlan($tenant, $planData);
        }

        // Sync with Stripe if requested and plan has price > 0
        if ($syncStripe && $plan && $plan->price > 0) {
            $this->syncPlanWithStripe($plan);
        }
    }

    /**
     * Create a new plan.
     */
    private function createPlan(Tenant $tenant, array $planData): ?ClubSubscriptionPlan
    {
        // Validate plan against tenant capabilities
        $errors = ClubSubscriptionPlan::validateAgainstTenant($planData, $tenant);

        if (!empty($errors)) {
            $this->command->error("     ✗ Validation failed: " . implode(', ', $errors));
            return null;
        }

        try {
            $plan = ClubSubscriptionPlan::create(array_merge($planData, [
                'tenant_id' => $tenant->id,
                'is_active' => true,
                'currency' => 'EUR',
            ]));

            $this->command->info("     ✓ Created: {$plan->name} (€{$plan->price}/{$plan->billing_interval})");
            return $plan;
        } catch (\Exception $e) {
            $this->command->error("     ✗ Error creating plan: {$e->getMessage()}");
            Log::error('Failed to create club subscription plan', [
                'tenant_id' => $tenant->id,
                'plan_data' => $planData,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Update an existing plan.
     */
    private function updatePlan(ClubSubscriptionPlan $existingPlan, array $planData): ClubSubscriptionPlan
    {
        // Don't update if already synced with Stripe (to avoid breaking existing subscriptions)
        if ($existingPlan->is_stripe_synced) {
            $this->command->warn("     ⚠ Plan already synced with Stripe. Skipping update to avoid breaking subscriptions.");
            return $existingPlan;
        }

        try {
            $existingPlan->update([
                'description' => $planData['description'],
                'color' => $planData['color'],
                'icon' => $planData['icon'],
                'features' => $planData['features'],
                'limits' => $planData['limits'],
                'trial_period_days' => $planData['trial_period_days'] ?? 0,
            ]);

            $this->command->info("     ✓ Updated: {$existingPlan->name}");
            return $existingPlan;
        } catch (\Exception $e) {
            $this->command->error("     ✗ Error updating plan: {$e->getMessage()}");
            Log::error('Failed to update club subscription plan', [
                'plan_id' => $existingPlan->id,
                'plan_data' => $planData,
                'error' => $e->getMessage(),
            ]);
            return $existingPlan;
        }
    }

    /**
     * Sync plan with Stripe (create Product & Prices).
     */
    private function syncPlanWithStripe(ClubSubscriptionPlan $plan): void
    {
        $this->command->info("     🔗 Syncing with Stripe...");

        try {
            $subscriptionService = app(ClubSubscriptionService::class);
            $result = $subscriptionService->syncPlanWithStripe($plan);

            $this->command->info("     ✓ Stripe sync successful:");
            $this->command->info("        Product ID: {$result['product']->id}");
            if (isset($result['price_monthly'])) {
                $this->command->info("        Monthly Price ID: {$result['price_monthly']->id}");
            }
            if (isset($result['price_yearly'])) {
                $this->command->info("        Yearly Price ID: {$result['price_yearly']->id}");
            }
        } catch (\Exception $e) {
            $this->command->error("     ✗ Stripe sync failed: {$e->getMessage()}");
            $this->command->warn("     ⚠ Plan created in database but not synced with Stripe. Run sync manually later.");
            Log::error('Failed to sync club subscription plan with Stripe', [
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
