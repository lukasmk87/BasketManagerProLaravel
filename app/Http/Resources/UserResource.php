<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            
            // Profile information
            'profile_photo_path' => $this->profile_photo_path,
            'profile_photo_url' => $this->profile_photo_url,
            
            // Roles and permissions (if loaded)
            'roles' => $this->when(
                $this->relationLoaded('roles'),
                fn() => $this->roles->pluck('name')
            ),
            
            'permissions' => $this->when(
                $this->relationLoaded('permissions'),
                fn() => $this->permissions->pluck('name')
            ),
            
            // Player relationship (if exists)
            'player' => new PlayerResource($this->whenLoaded('player')),
            
            // Basic info for scoring/officiating contexts
            'display_name' => $this->name,
            'initials' => $this->getInitials(),
            
            // Timestamps (only show to self or admins)
            'created_at' => $this->when(
                $request->user()?->id === $this->id || $request->user()?->hasRole('admin'),
                $this->created_at
            ),
            'updated_at' => $this->when(
                $request->user()?->id === $this->id || $request->user()?->hasRole('admin'),
                $this->updated_at
            ),
        ];
    }

    /**
     * Get user initials.
     */
    private function getInitials(): string
    {
        $nameParts = explode(' ', $this->name);
        return strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
    }
}