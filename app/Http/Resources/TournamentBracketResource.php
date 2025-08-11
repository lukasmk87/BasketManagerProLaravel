<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TournamentBracketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tournament_id' => $this->tournament_id,
            'game_id' => $this->game_id,
            
            // Bracket Structure
            'structure' => [
                'bracket_type' => $this->bracket_type,
                'bracket_type_display' => $this->bracket_type_display,
                'round' => $this->round,
                'round_name' => $this->round_name,
                'position_in_round' => $this->position_in_round,
                'total_rounds' => $this->total_rounds,
                'matchup_description' => $this->matchup_description,
            ],
            
            // Teams Information
            'teams' => [
                'team1' => $this->when($this->team1_id, [
                    'id' => $this->team1_id,
                    'team_id' => $this->team1?->team_id,
                    'team_name' => $this->whenLoaded('team1.team', fn() => $this->team1->team->name),
                    'team_short_name' => $this->whenLoaded('team1.team', fn() => $this->team1->team->short_name),
                    'seed' => $this->team1_seed,
                    'logo_path' => $this->whenLoaded('team1.team', fn() => $this->team1->team->logo_path),
                ]),
                'team2' => $this->when($this->team2_id, [
                    'id' => $this->team2_id,
                    'team_id' => $this->team2?->team_id,
                    'team_name' => $this->whenLoaded('team2.team', fn() => $this->team2->team->name),
                    'team_short_name' => $this->whenLoaded('team2.team', fn() => $this->team2->team->short_name),
                    'seed' => $this->team2_seed,
                    'logo_path' => $this->whenLoaded('team2.team', fn() => $this->team2->team->logo_path),
                ]),
                'winner' => $this->when($this->winner_team_id, [
                    'id' => $this->winner_team_id,
                    'team_id' => $this->winnerTeam?->team_id,
                    'team_name' => $this->whenLoaded('winnerTeam.team', fn() => $this->winnerTeam->team->name),
                ]),
                'loser' => $this->when($this->loser_team_id, [
                    'id' => $this->loser_team_id,
                    'team_id' => $this->loserTeam?->team_id,
                    'team_name' => $this->whenLoaded('loserTeam.team', fn() => $this->loserTeam->team->name),
                ]),
            ],
            
            // Status Information
            'status' => [
                'status' => $this->status,
                'status_display' => $this->status_display,
                'is_pending' => $this->is_pending,
                'is_scheduled' => $this->is_scheduled,
                'is_in_progress' => $this->is_in_progress,
                'is_completed' => $this->is_completed,
                'is_bye' => $this->is_bye,
                'has_both_teams' => $this->has_both_teams,
                'can_start' => $this->can_start,
                'can_complete' => $this->can_complete,
            ],
            
            // Game Scheduling
            'schedule' => [
                'scheduled_at' => $this->scheduled_at?->toISOString(),
                'scheduled_at_formatted' => $this->scheduled_at?->format('d.m.Y H:i'),
                'venue' => $this->venue,
                'court' => $this->court,
                'actual_start_time' => $this->actual_start_time?->toISOString(),
                'actual_end_time' => $this->actual_end_time?->toISOString(),
                'actual_duration' => $this->actual_duration,
            ],
            
            // Game Results
            'result' => [
                'team1_score' => $this->team1_score,
                'team2_score' => $this->team2_score,
                'score_by_period' => $this->score_by_period,
                'overtime' => $this->overtime,
                'overtime_periods' => $this->overtime_periods,
                'winner_score' => $this->winner_score,
                'loser_score' => $this->loser_score,
                'margin_of_victory' => $this->margin_of_victory,
                'game_notes' => $this->game_notes,
            ],
            
            // Officials
            'officials' => [
                'primary_referee_id' => $this->primary_referee_id,
                'primary_referee_name' => $this->whenLoaded('primaryReferee', fn() => $this->primaryReferee->full_name),
                'secondary_referee_id' => $this->secondary_referee_id,
                'secondary_referee_name' => $this->whenLoaded('secondaryReferee', fn() => $this->secondaryReferee->full_name),
                'scorekeeper' => $this->scorekeeper,
            ],
            
            // Forfeit Information
            'forfeit' => $this->when($this->status === 'forfeit', [
                'forfeit_team_id' => $this->forfeit_team_id,
                'forfeit_team_name' => $this->forfeitTeam?->team->name,
                'forfeit_reason' => $this->forfeit_reason,
            ]),
            
            // Advancement Rules
            'advancement' => [
                'winner_advances_to' => $this->winner_advances_to,
                'loser_advances_to' => $this->loser_advances_to,
                'feeds_to_winner' => $this->when($this->winner_advances_to, function () {
                    return [
                        'bracket_id' => $this->winner_advances_to,
                        'round' => $this->winnerAdvancesTo?->round,
                        'round_name' => $this->winnerAdvancesTo?->round_name,
                    ];
                }),
                'feeds_to_loser' => $this->when($this->loser_advances_to, function () {
                    return [
                        'bracket_id' => $this->loser_advances_to,
                        'round' => $this->loserAdvancesTo?->round,
                        'round_name' => $this->loserAdvancesTo?->round_name,
                    ];
                }),
            ],
            
            // Special Format Information
            'group_stage' => $this->when($this->group_name, [
                'group_name' => $this->group_name,
                'group_round' => $this->group_round,
            ]),
            
            'swiss_system' => $this->when($this->swiss_round, [
                'swiss_round' => $this->swiss_round,
                'swiss_rating_change' => $this->swiss_rating_change,
            ]),
            
            // Game Quality Metrics
            'metrics' => [
                'importance_level' => $this->whenLoaded('game.tournamentGame', fn() => $this->game->tournamentGame->importance_level),
                'is_featured_game' => $this->whenLoaded('game.tournamentGame', fn() => $this->game->tournamentGame->is_featured_game),
                'elimination_game' => $this->whenLoaded('game.tournamentGame', fn() => $this->game->tournamentGame->elimination_game),
                'championship_implications' => $this->whenLoaded('game.tournamentGame', fn() => $this->game->tournamentGame->championship_implications),
                'expected_spectators' => $this->whenLoaded('game.tournamentGame', fn() => $this->game->tournamentGame->expected_spectators),
                'actual_spectators' => $this->whenLoaded('game.tournamentGame', fn() => $this->game->tournamentGame->actual_spectators),
            ],
            
            // Media Information
            'media' => $this->when($this->relationLoaded('game.tournamentGame'), function () {
                $tournamentGame = $this->game->tournamentGame;
                return [
                    'livestream_scheduled' => $tournamentGame->livestream_scheduled,
                    'livestream_url' => $tournamentGame->livestream_url,
                    'recording_enabled' => $tournamentGame->recording_enabled,
                    'photography_allowed' => $this->tournament->photography_allowed,
                ];
            }),
            
            // Competitive Analysis
            'analysis' => [
                'is_upset' => $this->when($this->is_completed, function () {
                    if (!$this->team1_seed || !$this->team2_seed) return null;
                    $higherSeed = min($this->team1_seed, $this->team2_seed);
                    $lowerSeed = max($this->team1_seed, $this->team2_seed);
                    $higherSeedWon = ($this->team1_seed < $this->team2_seed && $this->winner_team_id === $this->team1_id) ||
                                    ($this->team2_seed < $this->team1_seed && $this->winner_team_id === $this->team2_id);
                    return !$higherSeedWon && $lowerSeed - $higherSeed >= 3;
                }),
                'seed_differential' => $this->when($this->team1_seed && $this->team2_seed, 
                    abs($this->team1_seed - $this->team2_seed)
                ),
                'is_close_game' => $this->when($this->is_completed && $this->margin_of_victory, 
                    $this->margin_of_victory <= 5
                ),
                'is_blowout' => $this->when($this->is_completed && $this->margin_of_victory, 
                    $this->margin_of_victory >= 20
                ),
            ],
            
            // User Context (when authenticated)
            'user_context' => $this->when(auth()->check(), function () {
                $user = auth()->user();
                return [
                    'is_user_team' => $this->isUserTeam($user),
                    'user_team_side' => $this->getUserTeamSide($user),
                    'can_edit_result' => $this->canUserEditResult($user),
                    'can_assign_officials' => $this->canUserAssignOfficials($user),
                ];
            }),
            
            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
    
    /**
     * Get additional data that should be merged into the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'links' => [
                'self' => route('tournaments.brackets.show', [$this->tournament_id, $this->id]),
                'tournament' => route('tournaments.show', $this->tournament_id),
                'progression' => route('tournaments.brackets.progression', [$this->tournament_id, $this->id]),
            ],
        ];
    }
    
    /**
     * Check if this bracket involves a user's team.
     */
    private function isUserTeam($user): bool
    {
        $userTeamIds = $user->teams->pluck('id')->toArray();
        
        return in_array($this->team1?->team_id, $userTeamIds) ||
               in_array($this->team2?->team_id, $userTeamIds);
    }
    
    /**
     * Get which side the user's team is on.
     */
    private function getUserTeamSide($user): ?string
    {
        $userTeamIds = $user->teams->pluck('id')->toArray();
        
        if (in_array($this->team1?->team_id, $userTeamIds)) return 'team1';
        if (in_array($this->team2?->team_id, $userTeamIds)) return 'team2';
        
        return null;
    }
    
    /**
     * Check if user can edit game result.
     */
    private function canUserEditResult($user): bool
    {
        // Tournament organizer
        if ($this->tournament && $this->tournament->organizer_id === $user->id) return true;
        
        // Officials
        if ($this->primary_referee_id === $user->id || $this->secondary_referee_id === $user->id) return true;
        
        // Admin permissions
        return $user->can('edit-tournament-results');
    }
    
    /**
     * Check if user can assign officials.
     */
    private function canUserAssignOfficials($user): bool
    {
        // Tournament organizer
        if ($this->tournament && $this->tournament->organizer_id === $user->id) return true;
        
        // Admin permissions
        return $user->can('assign-tournament-officials');
    }
}