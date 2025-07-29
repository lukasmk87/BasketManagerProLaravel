<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClubResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'description' => $this->description,
            
            // Contact information
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            
            // Address information
            'address' => $this->address,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            
            // Settings
            'settings' => $this->settings,
            'status' => $this->status,
            
            // Logo and branding
            'logo' => $this->when(
                $this->hasMedia('logos'),
                fn() => $this->getFirstMediaUrl('logos')
            ),
            
            // Team count
            'teams_count' => $this->when(
                $this->relationLoaded('teams'),
                fn() => $this->teams->count()
            ),
            
            // Active teams
            'active_teams' => TeamResource::collection($this->whenLoaded('activeTeams')),
            
            // Club statistics (when requested)
            'season_stats' => $this->when(
                $request->has('include_stats'),
                fn() => $this->season_stats
            ),
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}