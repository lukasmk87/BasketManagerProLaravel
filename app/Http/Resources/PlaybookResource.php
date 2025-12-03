<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaybookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'category_display' => $this->category_display,
            'is_default' => $this->is_default,
            'play_count' => $this->plays->count(),
            'team' => $this->whenLoaded('team', fn() => [
                'id' => $this->team->id,
                'name' => $this->team->name,
            ]),
            'created_by' => $this->whenLoaded('createdBy', fn() => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),
            'plays' => PlayResource::collection($this->whenLoaded('plays')),
            'games_count' => $this->whenCounted('games'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
