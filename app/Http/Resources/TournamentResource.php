<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TournamentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'logo_path' => $this->logo_path,
            
            // Tournament Configuration
            'configuration' => [
                'type' => $this->type,
                'type_display' => $this->type_display,
                'category' => $this->category,
                'category_display' => $this->category_display,
                'gender' => $this->gender,
                'gender_display' => $this->gender_display,
            ],
            
            // Schedule Information
            'schedule' => [
                'start_date' => $this->start_date?->toDateString(),
                'end_date' => $this->end_date?->toDateString(),
                'registration_start' => $this->registration_start?->toDateString(),
                'registration_end' => $this->registration_end?->toDateString(),
                'daily_start_time' => $this->daily_start_time?->format('H:i'),
                'daily_end_time' => $this->daily_end_time?->format('H:i'),
                'duration' => $this->duration,
                'duration_text' => $this->duration_text,
            ],
            
            // Team Information
            'teams' => [
                'min_teams' => $this->min_teams,
                'max_teams' => $this->max_teams,
                'registered_teams' => $this->registered_teams,
                'registration_progress' => $this->registration_progress,
                'can_register' => $this->can_register,
                'is_registration_open' => $this->is_registration_open,
            ],
            
            // Financial Information
            'financial' => [
                'entry_fee' => $this->entry_fee,
                'currency' => $this->currency,
                'spectator_fee' => $this->spectator_fee,
                'total_prize_money' => $this->total_prize_money,
            ],
            
            // Venue Information
            'venue' => [
                'primary_venue' => $this->primary_venue,
                'venue_address' => $this->venue_address,
                'additional_venues' => $this->additional_venues,
                'available_courts' => $this->available_courts,
            ],
            
            // Game Rules and Settings
            'game_rules' => [
                'game_duration' => $this->game_duration,
                'periods' => $this->periods,
                'period_length' => $this->period_length,
                'overtime_enabled' => $this->overtime_enabled,
                'overtime_length' => $this->overtime_length,
                'shot_clock_enabled' => $this->shot_clock_enabled,
                'shot_clock_seconds' => $this->shot_clock_seconds,
                'custom_rules' => $this->game_rules,
            ],
            
            // Tournament Structure
            'structure' => [
                'groups_count' => $this->groups_count,
                'seeding_rules' => $this->seeding_rules,
                'third_place_game' => $this->third_place_game,
                'advancement_rules' => $this->advancement_rules,
            ],
            
            // Status and Progress
            'status' => [
                'status' => $this->status,
                'status_display' => $this->status_display,
                'is_upcoming' => $this->is_upcoming,
                'is_in_progress' => $this->is_in_progress,
                'is_completed' => $this->is_completed,
                'can_start' => $this->canStart(),
                'can_complete' => $this->canComplete(),
                'can_generate_brackets' => $this->canGenerateBrackets(),
            ],
            
            // Progress Information
            'progress' => [
                'total_games' => $this->total_games,
                'completed_games' => $this->completed_games,
                'completion_percentage' => $this->completion_progress,
                'average_game_duration' => $this->average_game_duration,
                'total_spectators' => $this->total_spectators,
            ],
            
            // Prizes and Awards
            'awards_info' => [
                'prizes' => $this->prizes,
                'awards' => $this->awards,
                'total_prize_money' => $this->total_prize_money,
            ],
            
            // Settings
            'settings' => [
                'is_public' => $this->is_public,
                'requires_approval' => $this->requires_approval,
                'allows_spectators' => $this->allows_spectators,
                'photography_allowed' => $this->photography_allowed,
            ],
            
            // Media and Streaming
            'media' => [
                'livestream_enabled' => $this->livestream_enabled,
                'livestream_url' => $this->livestream_url,
                'social_media_links' => $this->social_media_links,
            ],
            
            // Contact Information
            'contact' => [
                'contact_email' => $this->contact_email,
                'contact_phone' => $this->contact_phone,
                'special_instructions' => $this->special_instructions,
                'covid_requirements' => $this->covid_requirements,
            ],
            
            // Organizer Information
            'organizer' => [
                'id' => $this->organizer_id,
                'name' => $this->whenLoaded('organizer', fn() => $this->organizer->full_name),
                'email' => $this->whenLoaded('organizer', fn() => $this->organizer->email),
            ],
            
            // Club Information
            'club' => $this->when($this->club_id, [
                'id' => $this->club_id,
                'name' => $this->whenLoaded('club', fn() => $this->club->name),
                'city' => $this->whenLoaded('club', fn() => $this->club->city),
            ]),
            
            // Teams (when loaded)
            'teams_list' => $this->when($this->relationLoaded('tournamentTeams'), function () {
                return $this->tournamentTeams->map(function ($tournamentTeam) {
                    return [
                        'id' => $tournamentTeam->id,
                        'team_id' => $tournamentTeam->team_id,
                        'team_name' => $tournamentTeam->team->name,
                        'status' => $tournamentTeam->status,
                        'status_display' => $tournamentTeam->status_display,
                        'seed' => $tournamentTeam->seed,
                        'group_name' => $tournamentTeam->group_name,
                        'registered_at' => $tournamentTeam->registered_at->toISOString(),
                        'performance' => [
                            'games_played' => $tournamentTeam->games_played,
                            'wins' => $tournamentTeam->wins,
                            'losses' => $tournamentTeam->losses,
                            'win_percentage' => $tournamentTeam->win_percentage,
                            'points_for' => $tournamentTeam->points_for,
                            'points_against' => $tournamentTeam->points_against,
                            'point_differential' => $tournamentTeam->point_differential,
                            'final_position' => $tournamentTeam->final_position,
                        ],
                    ];
                });
            }),
            
            // Brackets (when loaded)
            'brackets_summary' => $this->when($this->relationLoaded('brackets'), function () {
                $brackets = $this->brackets;
                return [
                    'total_brackets' => $brackets->count(),
                    'completed_brackets' => $brackets->where('status', 'completed')->count(),
                    'scheduled_brackets' => $brackets->where('status', 'scheduled')->count(),
                    'pending_brackets' => $brackets->where('status', 'pending')->count(),
                    'rounds' => $brackets->max('round'),
                    'bracket_types' => $brackets->pluck('bracket_type')->unique()->values(),
                ];
            }),
            
            // Officials (when loaded)
            'officials_summary' => $this->when($this->relationLoaded('officials'), function () {
                $officials = $this->officials;
                return [
                    'total_officials' => $officials->count(),
                    'confirmed_officials' => $officials->where('status', 'confirmed')->count(),
                    'pending_officials' => $officials->where('status', 'invited')->count(),
                    'by_role' => $officials->groupBy('role')->map->count(),
                ];
            }),
            
            // Awards (when loaded)
            'awards_summary' => $this->when($this->relationLoaded('awards'), function () {
                $awards = $this->awards;
                return [
                    'total_awards' => $awards->count(),
                    'presented_awards' => $awards->where('presented', true)->count(),
                    'team_awards' => $awards->where('award_type', 'team_award')->count(),
                    'individual_awards' => $awards->where('award_type', 'individual_award')->count(),
                    'record_setting' => $awards->where('record_setting', true)->count(),
                ];
            }),
            
            // User Permissions (when authenticated)
            'permissions' => $this->when(auth()->check(), [
                'can_edit' => $this->canUserEdit(),
                'can_delete' => $this->canUserDelete(),
                'can_manage_teams' => $this->canUserManageTeams(),
                'can_manage_brackets' => $this->canUserManageBrackets(),
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
                'self' => route('tournaments.show', $this->id),
                'standings' => route('tournaments.standings', $this->id),
                'brackets' => route('tournaments.brackets', $this->id),
                'teams' => route('tournaments.teams.index', $this->id),
                'statistics' => route('tournaments.statistics', $this->id),
            ],
        ];
    }
    
    /**
     * Check if user can edit this tournament.
     */
    private function canUserEdit(): bool
    {
        if (!auth()->check()) return false;
        
        return $this->organizer_id === auth()->id() || 
               auth()->user()->can('edit-tournaments');
    }
    
    /**
     * Check if user can delete this tournament.
     */
    private function canUserDelete(): bool
    {
        if (!auth()->check()) return false;
        
        return $this->organizer_id === auth()->id() || 
               auth()->user()->can('delete-tournaments');
    }
    
    /**
     * Check if user can manage teams for this tournament.
     */
    private function canUserManageTeams(): bool
    {
        if (!auth()->check()) return false;
        
        return $this->organizer_id === auth()->id() || 
               auth()->user()->can('manage-tournament-teams');
    }
    
    /**
     * Check if user can manage brackets for this tournament.
     */
    private function canUserManageBrackets(): bool
    {
        if (!auth()->check()) return false;
        
        return $this->organizer_id === auth()->id() || 
               auth()->user()->can('manage-tournament-brackets');
    }
}