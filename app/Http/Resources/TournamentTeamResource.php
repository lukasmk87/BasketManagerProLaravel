<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TournamentTeamResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tournament_id' => $this->tournament_id,
            'team_id' => $this->team_id,
            
            // Team Information
            'team' => [
                'id' => $this->team_id,
                'name' => $this->whenLoaded('team', fn() => $this->team->name),
                'short_name' => $this->whenLoaded('team', fn() => $this->team->short_name),
                'logo_path' => $this->whenLoaded('team', fn() => $this->team->logo_path),
                'club_name' => $this->whenLoaded('team.club', fn() => $this->team->club->name),
                'city' => $this->whenLoaded('team.club', fn() => $this->team->club->city),
            ],
            
            // Registration Information
            'registration' => [
                'registered_at' => $this->registered_at?->toISOString(),
                'registered_by_user_id' => $this->registered_by_user_id,
                'registered_by_name' => $this->whenLoaded('registeredBy', fn() => $this->registeredBy->full_name),
                'registration_notes' => $this->registration_notes,
            ],
            
            // Status Information
            'status' => [
                'status' => $this->status,
                'status_display' => $this->status_display,
                'status_reason' => $this->status_reason,
                'status_updated_at' => $this->status_updated_at?->toISOString(),
                'is_approved' => $this->is_approved,
                'is_pending' => $this->is_pending,
                'is_eliminated' => $this->is_eliminated,
                'is_still_active' => $this->is_still_active,
            ],
            
            // Tournament Position
            'tournament_position' => [
                'seed' => $this->seed,
                'group_name' => $this->group_name,
                'group_position' => $this->group_position,
                'final_position' => $this->final_position,
                'elimination_round' => $this->elimination_round,
                'elimination_round_display' => $this->elimination_round_display,
                'eliminated_at' => $this->eliminated_at?->toISOString(),
            ],
            
            // Performance Statistics
            'performance' => [
                'games_played' => $this->games_played,
                'wins' => $this->wins,
                'losses' => $this->losses,
                'draws' => $this->draws,
                'win_percentage' => $this->win_percentage,
                'points_for' => $this->points_for,
                'points_against' => $this->points_against,
                'point_differential' => $this->point_differential,
                'average_points_for' => $this->average_points_for,
                'average_points_against' => $this->average_points_against,
                'tournament_points' => $this->tournament_points,
            ],
            
            // Financial Information
            'financial' => [
                'entry_fee_paid' => $this->entry_fee_paid,
                'payment_date' => $this->payment_date?->toISOString(),
                'payment_method' => $this->payment_method,
                'prize_money' => $this->prize_money,
            ],
            
            // Contact Information
            'contact' => [
                'contact_person' => $this->contact_person,
                'contact_email' => $this->contact_email,
                'contact_phone' => $this->contact_phone,
                'special_requirements' => $this->special_requirements,
                'travel_information' => $this->travel_information,
            ],
            
            // Roster Information
            'roster' => [
                'roster_players' => $this->roster_players,
                'emergency_contacts' => $this->emergency_contacts,
                'medical_forms_complete' => $this->medical_forms_complete,
                'insurance_verified' => $this->insurance_verified,
            ],
            
            // Awards Information
            'awards' => [
                'individual_awards' => $this->individual_awards,
                'team_awards' => $this->team_awards,
                'awards_count' => $this->when($this->relationLoaded('awards'), fn() => $this->awards->count()),
            ],
            
            // Recent Games (when loaded)
            'recent_games' => $this->when(isset($this->recent_games), function () {
                return collect($this->recent_games)->map(function ($game) {
                    return [
                        'bracket_id' => $game['bracket_id'],
                        'round' => $game['round'],
                        'round_name' => $game['round_name'],
                        'opponent' => $game['opponent'],
                        'result' => $game['result'],
                        'played_at' => $game['played_at'],
                    ];
                });
            }),
            
            // Upcoming Games (when loaded)
            'upcoming_games' => $this->when(isset($this->upcoming_games), function () {
                return collect($this->upcoming_games)->map(function ($game) {
                    return [
                        'bracket_id' => $game['bracket_id'],
                        'round' => $game['round'],
                        'round_name' => $game['round_name'],
                        'opponent' => $game['opponent'],
                        'scheduled_at' => $game['scheduled_at'],
                        'venue' => $game['venue'],
                        'court' => $game['court'],
                    ];
                });
            }),
            
            // Performance Trends (when loaded)
            'trends' => $this->when(isset($this->performance_trends), $this->performance_trends),
            
            // User Permissions (when authenticated)
            'permissions' => $this->when(auth()->check(), [
                'can_edit' => $this->canUserEdit(),
                'can_withdraw' => $this->canUserWithdraw(),
                'can_view_roster' => $this->canUserViewRoster(),
            ]),
            
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
                'self' => route('tournaments.teams.show', [$this->tournament_id, $this->id]),
                'tournament' => route('tournaments.show', $this->tournament_id),
                'team' => route('teams.show', $this->team_id),
                'performance' => route('tournaments.teams.performance', [$this->tournament_id, $this->id]),
                'upcoming_games' => route('tournaments.teams.upcoming-games', [$this->tournament_id, $this->id]),
            ],
        ];
    }
    
    /**
     * Check if user can edit this tournament team.
     */
    private function canUserEdit(): bool
    {
        if (!auth()->check()) return false;
        
        $user = auth()->user();
        
        // Registered by user can edit
        if ($this->registered_by_user_id === $user->id) return true;
        
        // Tournament organizer can edit
        if ($this->tournament && $this->tournament->organizer_id === $user->id) return true;
        
        // Team manager/coach can edit
        if ($this->team && $this->team->user_id === $user->id) return true;
        
        // Admin permissions
        return $user->can('edit-tournament-teams');
    }
    
    /**
     * Check if user can withdraw this team.
     */
    private function canUserWithdraw(): bool
    {
        if (!auth()->check()) return false;
        if (!$this->is_still_active) return false;
        
        $user = auth()->user();
        
        // Registered by user can withdraw
        if ($this->registered_by_user_id === $user->id) return true;
        
        // Team manager/coach can withdraw
        if ($this->team && $this->team->user_id === $user->id) return true;
        
        // Tournament organizer can withdraw
        if ($this->tournament && $this->tournament->organizer_id === $user->id) return true;
        
        return $user->can('withdraw-tournament-teams');
    }
    
    /**
     * Check if user can view roster details.
     */
    private function canUserViewRoster(): bool
    {
        if (!auth()->check()) return false;
        
        $user = auth()->user();
        
        // Own team roster
        if ($this->registered_by_user_id === $user->id) return true;
        if ($this->team && $this->team->user_id === $user->id) return true;
        
        // Tournament organizer can view
        if ($this->tournament && $this->tournament->organizer_id === $user->id) return true;
        
        // Admin permissions
        return $user->can('view-tournament-rosters');
    }
}