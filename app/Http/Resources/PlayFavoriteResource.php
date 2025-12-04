<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayFavoriteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'play_id' => $this->play_id,
            'user_id' => $this->user_id,
            'play' => new PlayResource($this->whenLoaded('play')),
            'team' => $this->whenLoaded('team', fn() => [
                'id' => $this->team->id,
                'name' => $this->team->name,
            ]),
            'notes' => $this->notes,
            'tags' => $this->tags,
            'tags_display' => $this->tags_display,
            'favorite_type' => $this->favorite_type,
            'favorite_type_display' => $this->favorite_type_display,
            'use_cases' => $this->use_cases,
            'category_override' => $this->category_override,
            'category_to_use' => $this->category_to_use,
            'personal_priority' => $this->personal_priority,
            'priority_display' => $this->priority_display,
            'is_quick_access' => $this->is_quick_access,
            'is_team_specific' => $this->is_team_specific,
            'has_use_cases' => $this->has_use_cases,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
