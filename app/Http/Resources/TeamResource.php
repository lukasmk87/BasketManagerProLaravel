<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            
            // Basic Information
            'name' => $this->name,
            'short_name' => $this->short_name,
            'slug' => $this->slug,
            'uuid' => $this->uuid,
            'description' => $this->description,
            
            // Team Classification
            'gender' => $this->gender,
            'age_group' => $this->age_group,
            'division' => $this->division,
            'league' => $this->league,
            'season' => $this->season,
            'season_start' => $this->season_start,
            'season_end' => $this->season_end,
            
            // Visual Identity
            'logo_path' => $this->logo_path,
            'logo_url' => $this->when(
                $this->hasMedia('logo'),
                fn() => $this->getFirstMediaUrl('logo')
            ),
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'jersey_home_color' => $this->jersey_home_color,
            'jersey_away_color' => $this->jersey_away_color,
            
            // Team Structure
            'max_players' => $this->max_players,
            'min_players' => $this->min_players,
            'current_roster_size' => $this->current_roster_size,
            'players_slots_available' => $this->players_slots_available,
            'average_player_age' => $this->average_player_age,
            
            // Training & Venue
            'practice_times' => $this->practice_times,
            'home_venue' => $this->home_venue,
            'home_venue_address' => $this->home_venue_address,
            'venue_details' => $this->venue_details,
            
            // Coaching Staff
            'head_coach_id' => $this->head_coach_id,
            'assistant_coaches' => $this->assistant_coaches,
            
            // Team Statistics
            'games_played' => $this->games_played,
            'games_won' => $this->games_won,
            'games_lost' => $this->games_lost,
            'games_tied' => $this->games_tied,
            'win_percentage' => $this->win_percentage,
            'points_scored' => $this->points_scored,
            'points_allowed' => $this->points_allowed,
            'points_per_game' => $this->when(
                $this->games_played > 0,
                fn() => round($this->points_scored / $this->games_played, 1)
            ),
            'points_allowed_per_game' => $this->when(
                $this->games_played > 0,
                fn() => round($this->points_allowed / $this->games_played, 1)
            ),
            
            // Status & Settings
            'status' => $this->status,
            'is_active' => $this->is_active,
            'is_recruiting' => $this->is_recruiting,
            'is_certified' => $this->is_certified,
            'certified_at' => $this->certified_at,
            'accepts_new_players' => $this->canAcceptNewPlayer(),
            'registration_number' => $this->registration_number,
            
            // Preferences & Configuration
            'settings' => $this->settings,
            'preferences' => $this->preferences,
            'emergency_contacts' => $this->emergency_contacts,
            
            // Relationships
            'club' => new ClubResource($this->whenLoaded('club')),
            'club_id' => $this->club_id,
            
            'head_coach' => new UserResource($this->whenLoaded('headCoach')),
            
            'players' => PlayerResource::collection($this->whenLoaded('players')),
            'active_players' => PlayerResource::collection($this->whenLoaded('activePlayers')),
            'starters' => PlayerResource::collection($this->whenLoaded('starters')),
            'captains' => PlayerResource::collection($this->whenLoaded('captains')),
            
            // Counts (when relations are loaded)
            'players_count' => $this->when(
                $this->relationLoaded('players'),
                fn() => $this->players->count()
            ),
            'active_players_count' => $this->when(
                $this->relationLoaded('activePlayers'),
                fn() => $this->activePlayers->count()
            ),
            'home_games_count' => $this->when(
                $this->relationLoaded('homeGames'),
                fn() => $this->homeGames->count()
            ),
            'away_games_count' => $this->when(
                $this->relationLoaded('awayGames'),
                fn() => $this->awayGames->count()
            ),
            
            // Recent Games (when requested)
            'recent_games' => $this->when(
                $request->has('include_recent_games'),
                fn() => $this->allGames()->latest('scheduled_at')->limit(5)->get()->map(function ($game) {
                    return [
                        'id' => $game->id,
                        'opponent' => $game->home_team_id === $this->id ? $game->awayTeam?->name : $game->homeTeam?->name,
                        'is_home' => $game->home_team_id === $this->id,
                        'scheduled_at' => $game->scheduled_at,
                        'status' => $game->status,
                        'home_score' => $game->final_score_home,
                        'away_score' => $game->final_score_away,
                    ];
                })
            ),
            
            // Next Game (when requested)
            'next_game' => $this->when(
                $request->has('include_next_game'),
                fn() => $this->allGames()
                    ->where('scheduled_at', '>', now())
                    ->where('status', 'scheduled')
                    ->orderBy('scheduled_at')
                    ->first()?->only([
                        'id', 'scheduled_at', 'venue', 'home_team_id', 'away_team_id'
                    ])
            ),
            
            // Team Roster Summary
            'roster_summary' => $this->when(
                $request->has('include_roster_summary'),
                fn() => [
                    'total_players' => $this->current_roster_size,
                    'starters' => $this->starters()->count(),
                    'captains' => $this->captains()->count(),
                    'positions' => $this->players()->select('primary_position')
                        ->whereNotNull('primary_position')
                        ->groupBy('primary_position')
                        ->selectRaw('primary_position, COUNT(*) as count')
                        ->pluck('count', 'primary_position'),
                    'average_age' => $this->average_player_age,
                ]
            ),
            
            // Helper Methods Results
            'display_name' => $this->display_name,
            'full_name' => $this->when(
                $this->relationLoaded('club'),
                fn() => $this->club?->short_name . ' ' . $this->name
            ),
            'can_accept_new_player' => $this->canAcceptNewPlayer(),
            
            // Jetstream Compatibility (if needed)
            'personal_team' => $this->personal_team ?? false,
            'user_id' => $this->user_id,
            
            // Media Gallery (when loaded)
            'gallery' => $this->when(
                $this->hasMedia('gallery'),
                fn() => $this->getMedia('gallery')->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'name' => $media->name,
                        'url' => $media->getUrl(),
                        'thumb_url' => $media->getUrl('thumb'),
                    ];
                })
            ),
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}