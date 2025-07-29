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
            'name' => $this->name,
            'category' => $this->category,
            'league' => $this->league,
            'season' => $this->season,
            'status' => $this->status,
            
            // Club relationship
            'club' => new ClubResource($this->whenLoaded('club')),
            'club_id' => $this->club_id,
            
            // Coach information
            'head_coach' => new UserResource($this->whenLoaded('headCoach')),
            'head_coach_id' => $this->head_coach_id,
            
            // Player count
            'players_count' => $this->when(
                $this->relationLoaded('players'),
                fn() => $this->players->count()
            ),
            
            // Active players for live games
            'active_players' => PlayerResource::collection($this->whenLoaded('activePlayers')),
            
            // Basic statistics (when requested)
            'current_season_stats' => $this->when(
                $request->has('include_stats'),
                fn() => $this->getCurrentSeasonStats()
            ),
            
            // Team settings
            'settings' => $this->settings,
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get current season statistics (placeholder method).
     */
    private function getCurrentSeasonStats(): array
    {
        // This would be implemented based on your actual statistics system
        return [
            'games_played' => 0,
            'wins' => 0,
            'losses' => 0,
            'win_percentage' => 0,
            'avg_points_for' => 0,
            'avg_points_against' => 0,
        ];
    }
}