<?php

namespace Database\Factories;

use App\Models\Club;
use App\Models\ClubTransfer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClubTransfer>
 */
class ClubTransferFactory extends Factory
{
    protected $model = ClubTransfer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'club_id' => Club::factory(),
            'source_tenant_id' => Tenant::factory(),
            'target_tenant_id' => Tenant::factory(),
            'initiated_by' => User::factory(),
            'status' => ClubTransfer::STATUS_PENDING,
            'started_at' => null,
            'completed_at' => null,
            'failed_at' => null,
            'rolled_back_at' => null,
            'metadata' => [
                'club_name' => $this->faker->company() . ' Basketball Club',
                'source_tenant_name' => $this->faker->company(),
                'target_tenant_name' => $this->faker->company(),
                'initiated_at' => now()->toDateTimeString(),
            ],
            'can_rollback' => true,
            'rollback_expires_at' => now()->addHours(24),
        ];
    }

    /**
     * Indicate that the transfer is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClubTransfer::STATUS_PENDING,
            'started_at' => null,
            'completed_at' => null,
            'failed_at' => null,
            'rolled_back_at' => null,
        ]);
    }

    /**
     * Indicate that the transfer is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClubTransfer::STATUS_PROCESSING,
            'started_at' => now(),
            'completed_at' => null,
            'failed_at' => null,
            'rolled_back_at' => null,
        ]);
    }

    /**
     * Indicate that the transfer is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClubTransfer::STATUS_COMPLETED,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'failed_at' => null,
            'rolled_back_at' => null,
            'can_rollback' => true,
            'rollback_expires_at' => now()->addHours(24),
        ]);
    }

    /**
     * Indicate that the transfer is failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClubTransfer::STATUS_FAILED,
            'started_at' => now()->subMinutes(2),
            'completed_at' => null,
            'failed_at' => now(),
            'rolled_back_at' => null,
            'can_rollback' => true,
            'rollback_expires_at' => now()->addHours(24),
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'failure_reason' => 'Test failure reason',
            ]),
        ]);
    }

    /**
     * Indicate that the transfer has been rolled back.
     */
    public function rolledBack(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClubTransfer::STATUS_ROLLED_BACK,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHours(1),
            'failed_at' => null,
            'rolled_back_at' => now(),
            'can_rollback' => false,
        ]);
    }

    /**
     * Indicate that the transfer can be rolled back (within 24h window).
     */
    public function canRollback(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClubTransfer::STATUS_COMPLETED,
            'started_at' => now()->subMinutes(30),
            'completed_at' => now()->subMinutes(25),
            'can_rollback' => true,
            'rollback_expires_at' => now()->addHours(23),
        ]);
    }

    /**
     * Indicate that the rollback window has expired.
     */
    public function expiredRollback(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClubTransfer::STATUS_COMPLETED,
            'started_at' => now()->subHours(25),
            'completed_at' => now()->subHours(24)->subMinutes(30),
            'can_rollback' => true,
            'rollback_expires_at' => now()->subMinutes(30),
        ]);
    }

    /**
     * Set a specific club for the transfer.
     */
    public function forClub(Club $club): static
    {
        return $this->state(fn (array $attributes) => [
            'club_id' => $club->id,
            'source_tenant_id' => $club->tenant_id,
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'club_name' => $club->name,
            ]),
        ]);
    }

    /**
     * Set the source tenant for the transfer.
     */
    public function fromTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'source_tenant_id' => $tenant->id,
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'source_tenant_name' => $tenant->name,
            ]),
        ]);
    }

    /**
     * Set the target tenant for the transfer.
     */
    public function toTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'target_tenant_id' => $tenant->id,
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'target_tenant_name' => $tenant->name,
            ]),
        ]);
    }

    /**
     * Set the user who initiated the transfer.
     */
    public function initiatedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'initiated_by' => $user->id,
        ]);
    }

    /**
     * Set custom metadata.
     */
    public function withMetadata(array $metadata): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => array_merge($attributes['metadata'] ?? [], $metadata),
        ]);
    }
}
