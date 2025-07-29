<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            
            // Basic Info
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'jersey_number' => $this->jersey_number,
            'position' => $this->position,
            
            // Physical Info
            'birth_date' => $this->birth_date,
            'age' => $this->age,
            'height' => $this->height,
            'weight' => $this->weight,
            
            // Team relationship
            'team' => new TeamResource($this->whenLoaded('team')),
            'team_id' => $this->team_id,
            
            // User relationship
            'user' => new UserResource($this->whenLoaded('user')),
            'user_id' => $this->user_id,
            
            // Season statistics (when requested)
            'season_statistics' => $this->when(
                $request->has('include_stats'),
                fn() => $this->getSeasonStatistics($request->season ?? current_season())
            ),
            
            // Emergency contacts (only for authorized users)
            'emergency_contacts' => $this->when(
                $request->user()?->can('viewEmergencyContacts', $this->resource),
                EmergencyContactResource::collection($this->whenLoaded('emergencyContacts'))
            ),
            
            // Profile photo
            'profile_photo' => $this->when(
                $this->hasMedia('profile_photos'),
                fn() => $this->getFirstMediaUrl('profile_photos')
            ),
            
            // Training videos (for coaches/trainers)
            'training_videos' => $this->when(
                $request->user()?->can('viewTrainingContent', $this->resource),
                fn() => $this->getMedia('training_videos')->map(fn($media) => [
                    'id' => $media->id,
                    'name' => $media->name,
                    'url' => $media->getUrl(),
                    'duration' => $media->getCustomProperty('duration'),
                ])
            ),
            
            // Status flags
            'is_active' => $this->is_active ?? true,
            'is_starter' => $this->is_starter ?? false,
            'is_captain' => $this->is_captain ?? false,
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get season statistics (placeholder method).
     */
    private function getSeasonStatistics(string $season): array
    {
        // This would use your StatisticsService
        return [
            'games_played' => 0,
            'total_points' => 0,
            'avg_points' => 0,
            'field_goal_percentage' => 0,
            'three_point_percentage' => 0,
            'free_throw_percentage' => 0,
            'total_rebounds' => 0,
            'avg_rebounds' => 0,
            'assists' => 0,
            'avg_assists' => 0,
            'steals' => 0,
            'blocks' => 0,
            'turnovers' => 0,
            'personal_fouls' => 0,
        ];
    }
}