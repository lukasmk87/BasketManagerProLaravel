<?php

namespace Database\Factories;

use App\Models\Player;
use App\Models\PlayerAbsence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlayerAbsence>
 */
class PlayerAbsenceFactory extends Factory
{
    protected $model = PlayerAbsence::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('now', '+2 months');
        $duration = $this->faker->numberBetween(1, 14);
        $endDate = (clone $startDate)->modify("+{$duration} days");

        return [
            'player_id' => Player::factory(),
            'type' => $this->faker->randomElement(['vacation', 'illness', 'injury', 'personal', 'other']),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'notes' => $this->faker->optional(0.5)->sentence(),
            'reason' => $this->faker->optional(0.7)->randomElement([
                'Familienurlaub',
                'Erkältung',
                'Knöchelverletzung',
                'Schulveranstaltung',
                'Arzttermin',
                'Familienfeier',
                'Prüfungen',
            ]),
        ];
    }

    /**
     * Create a vacation absence.
     */
    public function vacation(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'vacation',
            'reason' => $this->faker->randomElement([
                'Familienurlaub',
                'Sommerferien',
                'Winterurlaub',
                'Kurztrip',
            ]),
        ]);
    }

    /**
     * Create an illness absence.
     */
    public function illness(): static
    {
        $startDate = $this->faker->dateTimeBetween('now', '+1 month');
        $duration = $this->faker->numberBetween(1, 7);
        $endDate = (clone $startDate)->modify("+{$duration} days");

        return $this->state(fn (array $attributes) => [
            'type' => 'illness',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => $this->faker->randomElement([
                'Erkältung',
                'Grippe',
                'Magen-Darm',
                'Fieber',
            ]),
        ]);
    }

    /**
     * Create an injury absence.
     */
    public function injury(): static
    {
        $startDate = $this->faker->dateTimeBetween('now', '+1 month');
        $duration = $this->faker->numberBetween(7, 42);
        $endDate = (clone $startDate)->modify("+{$duration} days");

        return $this->state(fn (array $attributes) => [
            'type' => 'injury',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => $this->faker->randomElement([
                'Knöchelverletzung',
                'Knieprobleme',
                'Muskelzerrung',
                'Verstauchung',
                'Prellungen',
            ]),
        ]);
    }

    /**
     * Create a personal absence.
     */
    public function personal(): static
    {
        $startDate = $this->faker->dateTimeBetween('now', '+2 months');
        $duration = $this->faker->numberBetween(1, 3);
        $endDate = (clone $startDate)->modify("+{$duration} days");

        return $this->state(fn (array $attributes) => [
            'type' => 'personal',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => $this->faker->randomElement([
                'Familienangelegenheit',
                'Arzttermin',
                'Schulveranstaltung',
                'Prüfungen',
            ]),
        ]);
    }

    /**
     * Create a current (active) absence.
     */
    public function current(): static
    {
        $daysAgo = $this->faker->numberBetween(1, 5);
        $startDate = now()->subDays($daysAgo);
        $daysRemaining = $this->faker->numberBetween(1, 10);
        $endDate = now()->addDays($daysRemaining);

        return $this->state(fn (array $attributes) => [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /**
     * Create an upcoming absence.
     */
    public function upcoming(): static
    {
        $daysUntilStart = $this->faker->numberBetween(1, 30);
        $startDate = now()->addDays($daysUntilStart);
        $duration = $this->faker->numberBetween(1, 14);
        $endDate = $startDate->copy()->addDays($duration);

        return $this->state(fn (array $attributes) => [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /**
     * Create a past absence.
     */
    public function past(): static
    {
        $daysAgo = $this->faker->numberBetween(7, 60);
        $startDate = now()->subDays($daysAgo);
        $duration = $this->faker->numberBetween(1, 7);
        $endDate = $startDate->copy()->addDays($duration);

        return $this->state(fn (array $attributes) => [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /**
     * Create a single-day absence.
     */
    public function singleDay(): static
    {
        $date = $this->faker->dateTimeBetween('now', '+1 month');

        return $this->state(fn (array $attributes) => [
            'start_date' => $date,
            'end_date' => $date,
        ]);
    }

    /**
     * Create a long-term absence (e.g., serious injury).
     */
    public function longTerm(): static
    {
        $startDate = $this->faker->dateTimeBetween('now', '+1 month');
        $duration = $this->faker->numberBetween(30, 90);
        $endDate = (clone $startDate)->modify("+{$duration} days");

        return $this->state(fn (array $attributes) => [
            'type' => 'injury',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reason' => $this->faker->randomElement([
                'Kreuzbandriss',
                'Meniskus-OP',
                'Knochenbruch',
            ]),
        ]);
    }
}
