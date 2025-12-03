<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'description' => $this->description,
            'court_type' => $this->court_type,
            'court_type_display' => $this->court_type_display,
            'play_data' => $this->play_data,
            'animation_data' => $this->animation_data,
            'has_animation' => $this->has_animation,
            'thumbnail_path' => $this->thumbnail_path,
            'category' => $this->category,
            'category_display' => $this->category_display,
            'tags' => $this->tags,
            'is_public' => $this->is_public,
            'status' => $this->status,
            'usage_count' => $this->usage_count,
            'player_count' => $this->getPlayerCount(),
            'animation_duration' => $this->getAnimationDuration(),
            'created_by' => $this->whenLoaded('createdBy', fn() => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),
            'playbooks_count' => $this->whenCounted('playbooks'),
            'drills_count' => $this->whenCounted('drills'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
