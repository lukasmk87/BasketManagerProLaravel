<?php

namespace Database\Factories;

use App\Models\SubscriptionMRRSnapshot;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubscriptionMRRSnapshot>
 */
class SubscriptionMRRSnapshotFactory extends Factory
{
    protected $model = SubscriptionMRRSnapshot::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $clubMrr = $this->faker->randomFloat(2, 500, 5000);
        $tenantMrr = $this->faker->randomFloat(2, 1000, 10000);
        $totalMrr = $clubMrr + $tenantMrr;
        $mrrGrowth = $this->faker->randomFloat(2, -500, 1000);
        $mrrGrowthRate = $mrrGrowth > 0 ? $this->faker->randomFloat(2, 0, 25) : $this->faker->randomFloat(2, -15, 0);

        return [
            'tenant_id' => Tenant::factory(),
            'snapshot_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'snapshot_type' => $this->faker->randomElement(['daily', 'monthly']),
            'club_mrr' => $clubMrr,
            'club_count' => $this->faker->numberBetween(1, 50),
            'tenant_mrr' => $tenantMrr,
            'total_mrr' => $totalMrr,
            'mrr_growth' => $mrrGrowth,
            'mrr_growth_rate' => $mrrGrowthRate,
            'new_business_mrr' => $this->faker->randomFloat(2, 0, 500),
            'expansion_mrr' => $this->faker->randomFloat(2, 0, 300),
            'contraction_mrr' => $this->faker->randomFloat(2, 0, 200),
            'churned_mrr' => $this->faker->randomFloat(2, 0, 400),
            'active_subscriptions' => $this->faker->numberBetween(5, 100),
            'new_subscriptions' => $this->faker->numberBetween(0, 10),
            'churned_subscriptions' => $this->faker->numberBetween(0, 5),
            'metadata' => null,
        ];
    }

    /**
     * Indicate that the snapshot is daily.
     */
    public function daily(): static
    {
        return $this->state(fn (array $attributes) => [
            'snapshot_type' => 'daily',
        ]);
    }

    /**
     * Indicate that the snapshot is monthly.
     */
    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'snapshot_type' => 'monthly',
        ]);
    }

    /**
     * Indicate that the MRR is growing.
     */
    public function growing(): static
    {
        return $this->state(function (array $attributes) {
            $growth = $this->faker->randomFloat(2, 100, 1000);
            return [
                'mrr_growth' => $growth,
                'mrr_growth_rate' => $this->faker->randomFloat(2, 5, 25),
            ];
        });
    }

    /**
     * Indicate that the MRR is declining.
     */
    public function declining(): static
    {
        return $this->state(function (array $attributes) {
            $growth = $this->faker->randomFloat(2, -1000, -100);
            return [
                'mrr_growth' => $growth,
                'mrr_growth_rate' => $this->faker->randomFloat(2, -25, -5),
            ];
        });
    }

    /**
     * Set snapshot for specific date.
     */
    public function forDate(\DateTime $date): static
    {
        return $this->state(fn (array $attributes) => [
            'snapshot_date' => $date,
        ]);
    }

    /**
     * Set snapshot for specific month.
     */
    public function forMonth(int $year, int $month): static
    {
        return $this->state(fn (array $attributes) => [
            'snapshot_date' => \Carbon\Carbon::create($year, $month, 1)->startOfMonth(),
            'snapshot_type' => 'monthly',
        ]);
    }

    /**
     * Set snapshot with specific MRR value.
     */
    public function withMRR(float $mrr): static
    {
        return $this->state(fn (array $attributes) => [
            'total_mrr' => $mrr,
            'club_mrr' => $mrr * 0.6,
            'tenant_mrr' => $mrr * 0.4,
        ]);
    }

    /**
     * Set snapshot with metadata.
     */
    public function withMetadata(array $metadata): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => $metadata,
        ]);
    }
}
