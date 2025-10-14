<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\ClubSubscriptionPlan;
use Illuminate\Database\Seeder;

class ClubSubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $defaultPlans = config('club_plans.default_plans');

        // Create default plans for all existing active tenants
        Tenant::where('is_active', true)->each(function (Tenant $tenant) use ($defaultPlans) {
            $this->command->info("Creating club plans for tenant: {$tenant->name}");

            foreach ($defaultPlans as $planData) {
                // Validate plan against tenant capabilities
                $errors = ClubSubscriptionPlan::validateAgainstTenant($planData, $tenant);

                if (empty($errors)) {
                    ClubSubscriptionPlan::create(array_merge($planData, [
                        'tenant_id' => $tenant->id,
                    ]));

                    $this->command->info("  ✓ Created plan: {$planData['name']}");
                } else {
                    $this->command->warn("  ✗ Skipping plan '{$planData['name']}': " . json_encode($errors));
                }
            }
        });

        $this->command->info("Club subscription plan seeding completed!");
    }
}
