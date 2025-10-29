<?php

namespace Database\Factories;

use App\Models\ClubSubscriptionEvent;
use App\Models\Club;
use App\Models\Tenant;
use App\Models\ClubSubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClubSubscriptionEvent>
 */
class ClubSubscriptionEventFactory extends Factory
{
    protected $model = ClubSubscriptionEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'club_id' => Club::factory(),
            'event_type' => $this->faker->randomElement([
                ClubSubscriptionEvent::TYPE_SUBSCRIPTION_CREATED,
                ClubSubscriptionEvent::TYPE_SUBSCRIPTION_RENEWED,
                ClubSubscriptionEvent::TYPE_PAYMENT_SUCCEEDED,
            ]),
            'stripe_subscription_id' => 'sub_' . $this->faker->unique()->numerify('##########'),
            'stripe_event_id' => 'evt_' . $this->faker->unique()->numerify('##########'),
            'old_plan_id' => null,
            'new_plan_id' => null,
            'mrr_change' => $this->faker->randomFloat(2, -500, 500),
            'cancellation_reason' => null,
            'cancellation_feedback' => null,
            'metadata' => null,
            'event_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the event is a subscription creation.
     */
    public function subscriptionCreated(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => ClubSubscriptionEvent::TYPE_SUBSCRIPTION_CREATED,
            'new_plan_id' => ClubSubscriptionPlan::factory(),
            'mrr_change' => $this->faker->randomFloat(2, 50, 500),
        ]);
    }

    /**
     * Indicate that the event is a subscription cancellation.
     */
    public function subscriptionCanceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => ClubSubscriptionEvent::TYPE_SUBSCRIPTION_CANCELED,
            'old_plan_id' => ClubSubscriptionPlan::factory(),
            'mrr_change' => $this->faker->randomFloat(2, -500, -50),
            'cancellation_reason' => $this->faker->randomElement([
                ClubSubscriptionEvent::REASON_VOLUNTARY,
                ClubSubscriptionEvent::REASON_PAYMENT_FAILED,
                ClubSubscriptionEvent::REASON_DOWNGRADE_TO_FREE,
            ]),
        ]);
    }

    /**
     * Indicate that the event is a voluntary cancellation.
     */
    public function voluntaryCancellation(): static
    {
        return $this->subscriptionCanceled()->state(fn (array $attributes) => [
            'cancellation_reason' => ClubSubscriptionEvent::REASON_VOLUNTARY,
            'cancellation_feedback' => $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the event is an involuntary cancellation (payment failed).
     */
    public function involuntaryCancellation(): static
    {
        return $this->subscriptionCanceled()->state(fn (array $attributes) => [
            'cancellation_reason' => ClubSubscriptionEvent::REASON_PAYMENT_FAILED,
        ]);
    }

    /**
     * Indicate that the event is a plan upgrade.
     */
    public function planUpgraded(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => ClubSubscriptionEvent::TYPE_PLAN_UPGRADED,
            'old_plan_id' => ClubSubscriptionPlan::factory(),
            'new_plan_id' => ClubSubscriptionPlan::factory(),
            'mrr_change' => $this->faker->randomFloat(2, 50, 300),
        ]);
    }

    /**
     * Indicate that the event is a plan downgrade.
     */
    public function planDowngraded(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => ClubSubscriptionEvent::TYPE_PLAN_DOWNGRADED,
            'old_plan_id' => ClubSubscriptionPlan::factory(),
            'new_plan_id' => ClubSubscriptionPlan::factory(),
            'mrr_change' => $this->faker->randomFloat(2, -300, -50),
        ]);
    }

    /**
     * Indicate that the event is a trial start.
     */
    public function trialStarted(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => ClubSubscriptionEvent::TYPE_TRIAL_STARTED,
            'new_plan_id' => ClubSubscriptionPlan::factory(),
            'mrr_change' => 0,
        ]);
    }

    /**
     * Indicate that the event is a trial conversion.
     */
    public function trialConverted(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => ClubSubscriptionEvent::TYPE_TRIAL_CONVERTED,
            'old_plan_id' => ClubSubscriptionPlan::factory(),
            'new_plan_id' => ClubSubscriptionPlan::factory(),
            'mrr_change' => $this->faker->randomFloat(2, 50, 500),
        ]);
    }

    /**
     * Indicate that the event is a trial expiration.
     */
    public function trialExpired(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => ClubSubscriptionEvent::TYPE_TRIAL_EXPIRED,
            'old_plan_id' => ClubSubscriptionPlan::factory(),
            'mrr_change' => 0,
            'cancellation_reason' => ClubSubscriptionEvent::REASON_TRIAL_EXPIRED,
        ]);
    }

    /**
     * Indicate that the event is a payment success.
     */
    public function paymentSucceeded(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => ClubSubscriptionEvent::TYPE_PAYMENT_SUCCEEDED,
            'mrr_change' => 0,
        ]);
    }

    /**
     * Indicate that the event is a payment failure.
     */
    public function paymentFailed(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => ClubSubscriptionEvent::TYPE_PAYMENT_FAILED,
            'mrr_change' => 0,
        ]);
    }

    /**
     * Set event for specific date.
     */
    public function forDate(\DateTime $date): static
    {
        return $this->state(fn (array $attributes) => [
            'event_date' => $date,
        ]);
    }

    /**
     * Set event for specific month.
     */
    public function inMonth(int $year, int $month): static
    {
        $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        return $this->state(fn (array $attributes) => [
            'event_date' => $this->faker->dateTimeBetween($startDate, $endDate),
        ]);
    }

    /**
     * Set event with specific MRR change.
     */
    public function withMRRChange(float $mrrChange): static
    {
        return $this->state(fn (array $attributes) => [
            'mrr_change' => $mrrChange,
        ]);
    }

    /**
     * Set event with metadata.
     */
    public function withMetadata(array $metadata): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => $metadata,
        ]);
    }
}
