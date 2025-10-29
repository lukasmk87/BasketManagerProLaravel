<?php

namespace Database\Factories;

use App\Models\ClubSubscriptionCohort;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClubSubscriptionCohort>
 */
class ClubSubscriptionCohortFactory extends Factory
{
    protected $model = ClubSubscriptionCohort::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cohortSize = $this->faker->numberBetween(10, 100);

        // Generate realistic retention rates (declining over time)
        $retention1 = 100.0; // Month 1 always 100%
        $retention2 = $this->faker->randomFloat(2, 85, 95);
        $retention3 = $this->faker->randomFloat(2, 70, $retention2);
        $retention6 = $this->faker->randomFloat(2, 50, $retention3);
        $retention12 = $this->faker->randomFloat(2, 30, $retention6);

        $avgLTV = $this->faker->randomFloat(2, 500, 5000);
        $cumulativeRevenue = $avgLTV * $cohortSize;

        return [
            'tenant_id' => Tenant::factory(),
            'cohort_month' => $this->faker->dateTimeBetween('-2 years', '-1 month')->format('Y-m-01'),
            'cohort_size' => $cohortSize,
            'retention_month_1' => $retention1,
            'retention_month_2' => $retention2,
            'retention_month_3' => $retention3,
            'retention_month_6' => $retention6,
            'retention_month_12' => $retention12,
            'cumulative_revenue' => $cumulativeRevenue,
            'avg_ltv' => $avgLTV,
            'last_calculated_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ];
    }

    /**
     * Indicate that the cohort has excellent retention.
     */
    public function excellentRetention(): static
    {
        return $this->state(fn (array $attributes) => [
            'retention_month_1' => 100.0,
            'retention_month_2' => 95.0,
            'retention_month_3' => 92.0,
            'retention_month_6' => 88.0,
            'retention_month_12' => 82.0, // > 80% = excellent
        ]);
    }

    /**
     * Indicate that the cohort has good retention.
     */
    public function goodRetention(): static
    {
        return $this->state(fn (array $attributes) => [
            'retention_month_1' => 100.0,
            'retention_month_2' => 90.0,
            'retention_month_3' => 82.0,
            'retention_month_6' => 72.0,
            'retention_month_12' => 65.0, // 60-80% = good
        ]);
    }

    /**
     * Indicate that the cohort has moderate retention.
     */
    public function moderateRetention(): static
    {
        return $this->state(fn (array $attributes) => [
            'retention_month_1' => 100.0,
            'retention_month_2' => 85.0,
            'retention_month_3' => 70.0,
            'retention_month_6' => 55.0,
            'retention_month_12' => 45.0, // 40-60% = moderate
        ]);
    }

    /**
     * Indicate that the cohort has poor retention.
     */
    public function poorRetention(): static
    {
        return $this->state(fn (array $attributes) => [
            'retention_month_1' => 100.0,
            'retention_month_2' => 75.0,
            'retention_month_3' => 55.0,
            'retention_month_6' => 35.0,
            'retention_month_12' => 20.0, // < 40% = poor
        ]);
    }

    /**
     * Set cohort for specific month.
     */
    public function forMonth(int $year, int $month): static
    {
        return $this->state(fn (array $attributes) => [
            'cohort_month' => \Carbon\Carbon::create($year, $month, 1)->startOfMonth(),
        ]);
    }

    /**
     * Indicate that the cohort is mature (>= 12 months old).
     */
    public function mature(): static
    {
        return $this->state(fn (array $attributes) => [
            'cohort_month' => $this->faker->dateTimeBetween('-2 years', '-12 months')->format('Y-m-01'),
        ]);
    }

    /**
     * Indicate that the cohort is immature (< 12 months old).
     */
    public function immature(): static
    {
        return $this->state(fn (array $attributes) => [
            'cohort_month' => $this->faker->dateTimeBetween('-11 months', '-1 month')->format('Y-m-01'),
        ]);
    }

    /**
     * Indicate that the cohort data is stale (needs recalculation).
     */
    public function stale(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_calculated_at' => $this->faker->dateTimeBetween('-30 days', '-8 days'),
        ]);
    }

    /**
     * Indicate that the cohort data is fresh (recently calculated).
     */
    public function fresh(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_calculated_at' => $this->faker->dateTimeBetween('-3 days', 'now'),
        ]);
    }

    /**
     * Set cohort with specific size.
     */
    public function withSize(int $size): static
    {
        return $this->state(function (array $attributes) use ($size) {
            $avgLTV = $attributes['avg_ltv'] ?? $this->faker->randomFloat(2, 500, 5000);
            return [
                'cohort_size' => $size,
                'cumulative_revenue' => $avgLTV * $size,
            ];
        });
    }

    /**
     * Set cohort with specific LTV.
     */
    public function withLTV(float $ltv): static
    {
        return $this->state(function (array $attributes) use ($ltv) {
            $cohortSize = $attributes['cohort_size'] ?? $this->faker->numberBetween(10, 100);
            return [
                'avg_ltv' => $ltv,
                'cumulative_revenue' => $ltv * $cohortSize,
            ];
        });
    }

    /**
     * Set cohort without last calculation date (never calculated).
     */
    public function neverCalculated(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_calculated_at' => null,
        ]);
    }
}
