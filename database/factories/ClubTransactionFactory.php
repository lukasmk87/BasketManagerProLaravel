<?php

namespace Database\Factories;

use App\Models\Club;
use App\Models\ClubTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClubTransaction>
 */
class ClubTransactionFactory extends Factory
{
    protected $model = ClubTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement([ClubTransaction::TYPE_INCOME, ClubTransaction::TYPE_EXPENSE]);

        return [
            'club_id' => Club::factory(),
            'type' => $type,
            'category' => $this->faker->randomElement(array_keys(ClubTransaction::getCategories())),
            'amount' => $this->faker->randomFloat(2, 10, 5000),
            'currency' => 'EUR',
            'description' => $this->faker->optional(0.7)->sentence(),
            'transaction_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'reference_number' => $this->faker->optional(0.5)->numerify('INV-####'),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the transaction is income.
     */
    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ClubTransaction::TYPE_INCOME,
        ]);
    }

    /**
     * Indicate that the transaction is an expense.
     */
    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ClubTransaction::TYPE_EXPENSE,
        ]);
    }

    /**
     * Set a specific category.
     */
    public function category(string $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }

    /**
     * Set a specific amount.
     */
    public function amount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
        ]);
    }

    /**
     * Set a specific club.
     */
    public function forClub(Club $club): static
    {
        return $this->state(fn (array $attributes) => [
            'club_id' => $club->id,
        ]);
    }

    /**
     * Set a specific creator.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $user->id,
        ]);
    }

    /**
     * Create a membership fee income.
     */
    public function membershipFee(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ClubTransaction::TYPE_INCOME,
            'category' => ClubTransaction::CATEGORY_MEMBERSHIP_FEE,
            'description' => 'Mitgliedsbeitrag',
        ]);
    }

    /**
     * Create a facility expense.
     */
    public function facilityExpense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ClubTransaction::TYPE_EXPENSE,
            'category' => ClubTransaction::CATEGORY_FACILITY,
            'description' => 'Hallenmiete',
        ]);
    }

    /**
     * Set transaction date.
     */
    public function onDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_date' => $date,
        ]);
    }
}
