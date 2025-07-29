<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameActionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            
            // Basic Action Info
            'action_type' => $this->action_type,
            'description' => $this->action_description,
            'display_time' => $this->display_time,
            
            // Game Context
            'game_id' => $this->game_id,
            'period' => $this->period,
            'time_remaining' => $this->time_remaining,
            'game_clock_seconds' => $this->game_clock_seconds,
            'shot_clock_remaining' => $this->shot_clock_remaining,
            
            // Player & Team
            'player' => new PlayerResource($this->whenLoaded('player')),
            'player_id' => $this->player_id,
            'team_id' => $this->team_id,
            'team' => new TeamResource($this->whenLoaded('team')),
            
            // Points & Success
            'points' => $this->points,
            'point_value' => $this->getPointValue(),
            'is_successful' => $this->is_successful,
            
            // Assist Information
            'is_assisted' => $this->is_assisted,
            'assisted_by_player' => new PlayerResource($this->whenLoaded('assistedByPlayer')),
            'assisted_by_player_id' => $this->assisted_by_player_id,
            
            // Shot Chart Data
            'shot_data' => $this->when(
                $this->is_shot,
                [
                    'shot_x' => $this->shot_x,
                    'shot_y' => $this->shot_y,
                    'shot_distance' => $this->shot_distance,
                    'shot_zone' => $this->shot_zone,
                    'requires_coordinates' => $this->requiresCoordinates(),
                ]
            ),
            
            // Foul Details
            'foul_details' => $this->when(
                $this->is_foul,
                [
                    'foul_type' => $this->foul_type,
                    'foul_results_in_free_throws' => $this->foul_results_in_free_throws,
                    'free_throws_awarded' => $this->free_throws_awarded,
                ]
            ),
            
            // Substitution Details
            'substitution_details' => $this->when(
                in_array($this->action_type, ['substitution_in', 'substitution_out']),
                [
                    'substituted_player' => new PlayerResource($this->whenLoaded('substitutedPlayer')),
                    'substituted_player_id' => $this->substituted_player_id,
                    'substitution_reason' => $this->substitution_reason,
                ]
            ),
            
            // Action Flags
            'flags' => [
                'is_shot' => $this->is_shot,
                'is_three_pointer' => $this->is_three_pointer,
                'is_free_throw' => $this->is_free_throw,
                'is_foul' => $this->is_foul,
                'is_positive_action' => $this->isPositiveAction(),
                'is_negative_action' => $this->isNegativeAction(),
            ],
            
            // Context & Notes
            'description_custom' => $this->description,
            'notes' => $this->notes,
            'additional_data' => $this->additional_data,
            
            // Recording Information
            'recorded_by' => new UserResource($this->whenLoaded('recordedBy')),
            'recorded_by_user_id' => $this->recorded_by_user_id,
            'recorded_from_ip' => $this->when(
                $request->user()?->can('viewTechnicalDetails', $this->resource),
                $this->recorded_from_ip
            ),
            'recorded_at' => $this->recorded_at,
            
            // Review & Correction
            'review_status' => [
                'is_reviewed' => $this->is_reviewed,
                'reviewed_by' => new UserResource($this->whenLoaded('reviewedBy')),
                'reviewed_by_user_id' => $this->reviewed_by_user_id,
                'reviewed_at' => $this->reviewed_at,
                'is_corrected' => $this->is_corrected,
                'corrected_by' => new UserResource($this->whenLoaded('correctedBy')),
                'corrected_by_user_id' => $this->corrected_by_user_id,
                'correction_reason' => $this->correction_reason,
            ],
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with($request)
    {
        return [
            'meta' => [
                'can_correct' => $request->user()?->can('correctAction', $this->resource) ?? false,
                'can_delete' => $request->user()?->can('deleteAction', $this->resource) ?? false,
                'can_review' => $request->user()?->can('reviewAction', $this->resource) ?? false,
            ],
        ];
    }
}