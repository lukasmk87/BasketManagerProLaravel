<?php

namespace Database\Factories;

use App\Models\TournamentTeam;
use App\Models\Tournament;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TournamentTeam>
 */
class TournamentTeamFactory extends Factory
{
    protected $model = TournamentTeam::class;

    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::factory(),
            'team_id' => Team::factory(),
            'registered_at' => now(),
            'registered_by_user_id' => User::factory(),
            'registration_notes' => $this->faker->optional(0.3)->sentence(),
            'status' => 'pending',
            'status_reason' => null,
            'status_updated_at' => null,
            'seed' => null,
            'group_name' => null,
            'group_position' => null,
            'games_played' => 0,
            'wins' => 0,
            'losses' => 0,
            'draws' => 0,
            'points_for' => 0,
            'points_against' => 0,
            'tournament_points' => 0,
            'point_differential' => 0,
            'final_position' => null,
            'elimination_round' => null,
            'eliminated_at' => null,
            'entry_fee_paid' => false,
            'payment_date' => null,
            'payment_method' => null,
            'prize_money' => null,
            'contact_person' => $this->faker->name(),
            'contact_email' => $this->faker->email(),
            'contact_phone' => $this->faker->phoneNumber(),
            'special_requirements' => null,
            'travel_information' => null,
            'roster_players' => [],
            'emergency_contacts' => [],
            'medical_forms_complete' => false,
            'insurance_verified' => false,
            'individual_awards' => [],
            'team_awards' => [],
        ];
    }

    /**
     * Team is pending approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Team is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'status_updated_at' => now(),
        ]);
    }

    /**
     * Team was rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'status_reason' => 'Not eligible for this tournament',
            'status_updated_at' => now(),
        ]);
    }

    /**
     * Team has withdrawn.
     */
    public function withdrawn(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'withdrawn',
            'status_reason' => 'Team withdrew',
            'status_updated_at' => now(),
        ]);
    }

    /**
     * Team with a seed.
     */
    public function seeded(int $seed = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'seed' => $seed,
        ]);
    }

    /**
     * Team in a group.
     */
    public function inGroup(string $groupName, int $position = null): static
    {
        return $this->state(fn (array $attributes) => [
            'group_name' => $groupName,
            'group_position' => $position,
        ]);
    }

    /**
     * Team with games played.
     */
    public function withStats(int $wins = 2, int $losses = 1): static
    {
        $games = $wins + $losses;
        $pointsFor = $wins * 75 + $losses * 60;
        $pointsAgainst = $wins * 65 + $losses * 78;

        return $this->state(fn (array $attributes) => [
            'games_played' => $games,
            'wins' => $wins,
            'losses' => $losses,
            'points_for' => $pointsFor,
            'points_against' => $pointsAgainst,
            'tournament_points' => $wins * 2,
            'point_differential' => $pointsFor - $pointsAgainst,
        ]);
    }

    /**
     * Team was eliminated.
     */
    public function eliminated(string $round = 'quarterfinal'): static
    {
        return $this->state(fn (array $attributes) => [
            'elimination_round' => $round,
            'eliminated_at' => now(),
        ]);
    }

    /**
     * Team has paid entry fee.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'entry_fee_paid' => true,
            'payment_date' => now(),
            'payment_method' => 'bank_transfer',
        ]);
    }
}
