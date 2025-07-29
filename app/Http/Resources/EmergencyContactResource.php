<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmergencyContactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contact_name' => $this->contact_name,
            'phone_number' => $this->phone_number,
            'relationship' => $this->relationship,
            'is_primary' => $this->is_primary,
            'notes' => $this->notes,
            
            // GDPR compliance
            'consent_given' => $this->consent_given,
            'consent_given_at' => $this->consent_given_at,
            
            // Player relationship
            'player_id' => $this->player_id,
            'player' => new PlayerResource($this->whenLoaded('player')),
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Determine if the user is authorized to view this resource.
     */
    public function authorized($request)
    {
        return $request->user()?->can('viewEmergencyContacts', $this->resource) ?? false;
    }
}