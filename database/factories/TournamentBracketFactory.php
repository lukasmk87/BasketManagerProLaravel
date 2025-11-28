<?php

namespace Database\Factories;

use App\Models\TournamentBracket;
use App\Models\Tournament;
use App\Models\TournamentTeam;
use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TournamentBracket>
 */
class TournamentBracketFactory extends Factory
{
    protected $model = TournamentBracket::class;

    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::factory(),
            'game_id' => null,
            'bracket_type' => 'winners',
            'round' => 1,
            'round_name' => 'Round 1',
            'position_in_round' => 1,
            'total_rounds' => 3,
            'team1_id' => null,
            'team2_id' => null,
            'winner_team_id' => null,
            'loser_team_id' => null,
            'winner_advances_to' => null,
            'loser_advances_to' => null,
            'scheduled_at' => now()->addDays(7),
            'venue' => $this->faker->company() . ' Arena',
            'court' => 'Court ' . $this->faker->numberBetween(1, 4),
            'primary_referee_id' => null,
            'secondary_referee_id' => null,
            'scorekeeper' => null,
            'status' => 'pending',
            'team1_seed' => null,
            'team2_seed' => null,
            'matchup_description' => null,
            'team1_score' => null,
            'team2_score' => null,
            'score_by_period' => null,
            'overtime' => false,
            'overtime_periods' => 0,
            'game_notes' => null,
            'forfeit_team_id' => null,
            'forfeit_reason' => null,
            'actual_start_time' => null,
            'actual_end_time' => null,
            'actual_duration' => null,
            'group_name' => null,
            'group_round' => null,
            'swiss_round' => null,
            'swiss_rating_change' => null,
        ];
    }

    /**
     * Pending status.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Scheduled status.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'scheduled_at' => now()->addDays($this->faker->numberBetween(1, 14)),
        ]);
    }

    /**
     * In progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'actual_start_time' => now(),
        ]);
    }

    /**
     * Completed bracket.
     */
    public function completed(): static
    {
        $team1Score = $this->faker->numberBetween(60, 100);
        $team2Score = $this->faker->numberBetween(55, 95);

        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'team1_score' => $team1Score,
            'team2_score' => $team2Score,
            'actual_start_time' => now()->subHours(2),
            'actual_end_time' => now()->subHour(),
            'actual_duration' => 90,
        ]);
    }

    /**
     * Quarterfinal round.
     */
    public function quarterfinal(): static
    {
        return $this->state(fn (array $attributes) => [
            'round' => 2,
            'round_name' => 'Quarterfinal',
        ]);
    }

    /**
     * Semifinal round.
     */
    public function semifinal(): static
    {
        return $this->state(fn (array $attributes) => [
            'round' => 3,
            'round_name' => 'Semifinal',
        ]);
    }

    /**
     * Final round.
     */
    public function final(): static
    {
        return $this->state(fn (array $attributes) => [
            'round' => 4,
            'round_name' => 'Final',
        ]);
    }

    /**
     * Group stage match.
     */
    public function groupStage(string $groupName = 'A'): static
    {
        return $this->state(fn (array $attributes) => [
            'bracket_type' => 'group',
            'group_name' => $groupName,
            'group_round' => 1,
        ]);
    }

    /**
     * With teams.
     */
    public function withTeams(TournamentTeam $team1, TournamentTeam $team2): static
    {
        return $this->state(fn (array $attributes) => [
            'team1_id' => $team1->id,
            'team2_id' => $team2->id,
            'team1_seed' => $team1->seed,
            'team2_seed' => $team2->seed,
        ]);
    }
}
