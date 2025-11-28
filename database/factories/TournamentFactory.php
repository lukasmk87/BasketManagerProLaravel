<?php

namespace Database\Factories;

use App\Models\Tournament;
use App\Models\User;
use App\Models\Club;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tournament>
 */
class TournamentFactory extends Factory
{
    protected $model = Tournament::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('+1 week', '+3 months');
        $endDate = (clone $startDate)->modify('+2 days');
        $registrationStart = (clone $startDate)->modify('-4 weeks');
        $registrationEnd = (clone $startDate)->modify('-1 week');

        return [
            'organizer_id' => User::factory(),
            'club_id' => Club::factory(),
            'name' => $this->faker->words(3, true) . ' Turnier ' . date('Y'),
            'description' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['single_elimination', 'double_elimination', 'round_robin', 'group_stage_knockout']),
            'category' => $this->faker->randomElement(['adult', 'u18', 'u16', 'u14', 'mixed']),
            'gender' => $this->faker->randomElement(['male', 'female', 'mixed']),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'registration_start' => $registrationStart,
            'registration_end' => $registrationEnd,
            'daily_start_time' => '09:00',
            'daily_end_time' => '21:00',
            'min_teams' => 4,
            'max_teams' => 16,
            'registered_teams' => 0,
            'entry_fee' => $this->faker->randomElement([0, 50, 100, 150]),
            'currency' => 'EUR',
            'primary_venue' => $this->faker->company() . ' Sporthalle',
            'venue_address' => $this->faker->address(),
            'available_courts' => $this->faker->numberBetween(1, 4),
            'game_duration' => 40,
            'periods' => 4,
            'period_length' => 10,
            'overtime_enabled' => true,
            'overtime_length' => 5,
            'shot_clock_enabled' => true,
            'shot_clock_seconds' => 24,
            'groups_count' => null,
            'third_place_game' => true,
            'status' => 'draft',
            'is_public' => true,
            'requires_approval' => false,
            'allows_spectators' => true,
            'spectator_fee' => 0,
            'livestream_enabled' => false,
            'photography_allowed' => true,
            'contact_email' => $this->faker->email(),
            'contact_phone' => $this->faker->phoneNumber(),
            'total_games' => 0,
            'completed_games' => 0,
        ];
    }

    /**
     * Tournament is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Registration is open.
     */
    public function registrationOpen(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'registration_open',
            'registration_start' => now()->subDays(7),
            'registration_end' => now()->addDays(14),
        ]);
    }

    /**
     * Registration is closed.
     */
    public function registrationClosed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'registration_closed',
            'registration_start' => now()->subDays(30),
            'registration_end' => now()->subDays(7),
            'registered_teams' => 8,
        ]);
    }

    /**
     * Tournament is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'start_date' => now()->subDays(1),
            'end_date' => now()->addDays(2),
            'registration_start' => now()->subDays(30),
            'registration_end' => now()->subDays(7),
            'registered_teams' => 8,
            'total_games' => 7,
            'completed_games' => 3,
        ]);
    }

    /**
     * Tournament is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'start_date' => now()->subDays(7),
            'end_date' => now()->subDays(5),
            'registration_start' => now()->subDays(35),
            'registration_end' => now()->subDays(14),
            'registered_teams' => 8,
            'total_games' => 7,
            'completed_games' => 7,
        ]);
    }

    /**
     * Single elimination tournament.
     */
    public function singleElimination(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'single_elimination',
            'groups_count' => null,
        ]);
    }

    /**
     * Round robin tournament.
     */
    public function roundRobin(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'round_robin',
            'groups_count' => null,
        ]);
    }

    /**
     * Group stage + knockout tournament.
     */
    public function groupStageKnockout(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'group_stage_knockout',
            'groups_count' => 2,
        ]);
    }

    /**
     * Requires team approval.
     */
    public function requiresApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_approval' => true,
        ]);
    }

    /**
     * With minimum teams registered.
     */
    public function withMinTeams(): static
    {
        return $this->state(fn (array $attributes) => [
            'registered_teams' => $attributes['min_teams'] ?? 4,
        ]);
    }
}
