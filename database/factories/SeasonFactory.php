<?php

namespace Database\Factories;

use App\Models\Club;
use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Season>
 */
class SeasonFactory extends Factory
{
    protected $model = Season::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Use unique year to avoid duplicate name constraint
        $startYear = $this->faker->unique()->numberBetween(2000, 2099);
        $endYear = $startYear + 1;

        return [
            'club_id' => Club::factory(),
            'name' => "{$startYear}/{$endYear}",
            'start_date' => "{$startYear}-09-01",
            'end_date' => "{$endYear}-06-30",
            'status' => 'draft',
            'is_current' => false,
            'description' => $this->faker->optional(0.7)->sentence(),
            'settings' => [
                'game_duration' => 40,
                'quarters' => 4,
                'overtime_duration' => 5,
            ],
        ];
    }

    /**
     * Indicate that the season is in draft status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'is_current' => false,
        ]);
    }

    /**
     * Indicate that the season is active.
     */
    public function active(): static
    {
        // Ensure dates cover current date for valid activation
        $now = now();

        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'is_current' => true,
            'start_date' => $now->copy()->subMonths(3),
            'end_date' => $now->copy()->addMonths(6),
        ]);
    }

    /**
     * Indicate that the season is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'is_current' => false,
            'start_date' => now()->subYear()->subMonths(3),
            'end_date' => now()->subMonths(3),
        ]);
    }

    /**
     * Set a specific club for the season.
     */
    public function forClub(Club $club): static
    {
        return $this->state(fn (array $attributes) => [
            'club_id' => $club->id,
        ]);
    }

    /**
     * Create a season with a specific name (e.g., "2024/2025").
     */
    public function named(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }

    /**
     * Create a season with specific date range.
     */
    public function withDates(string $startDate, string $endDate): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /**
     * Create a season that can be activated (draft, dates include today).
     */
    public function activatable(): static
    {
        $now = now();

        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'is_current' => false,
            'start_date' => $now->copy()->subDays(30),
            'end_date' => $now->copy()->addMonths(6),
        ]);
    }

    /**
     * Create a season that can be completed (active, end_date in the past).
     */
    public function completable(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'is_current' => true,
            'start_date' => now()->subYear(),
            'end_date' => now()->subDays(1),
        ]);
    }

    /**
     * Create a future season (dates in the future).
     */
    public function future(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'is_current' => false,
            'start_date' => now()->addMonths(3),
            'end_date' => now()->addMonths(12),
        ]);
    }
}
