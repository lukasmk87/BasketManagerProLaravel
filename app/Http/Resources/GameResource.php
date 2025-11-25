<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            
            // Teams
            'home_team' => new TeamResource($this->whenLoaded('homeTeam')),
            'away_team' => new TeamResource($this->whenLoaded('awayTeam')),
            
            // Game Info
            'scheduled_at' => $this->scheduled_at,
            'actual_start_time' => $this->actual_start_time,
            'actual_end_time' => $this->actual_end_time,
            'venue' => $this->venue,
            'venue_address' => $this->venue_address,
            'type' => $this->type,
            'season' => $this->season,
            'league' => $this->league,
            'division' => $this->division,
            
            // Status & Scores
            'status' => $this->status,
            'home_team_score' => $this->home_team_score,
            'away_team_score' => $this->away_team_score,
            'total_score' => $this->total_score,
            'point_differential' => $this->point_differential,
            'result' => $this->result,
            'winning_team_id' => $this->winning_team_id,
            
            // Computed properties
            'winner' => $this->winner?->name,
            'loser' => $this->loser?->name,
            'is_tie' => $this->is_tie,
            'went_to_overtime' => $this->went_to_overtime,
            'duration' => $this->duration,
            'matchup' => $this->home_team->name . ' vs ' . $this->away_team->name,
            
            // Game Timing
            'current_period' => $this->current_period,
            'total_periods' => $this->total_periods,
            'period_length_minutes' => $this->period_length_minutes,
            'time_remaining_seconds' => $this->time_remaining_seconds,
            'formatted_time_remaining' => $this->formatted_time_remaining,
            'clock_running' => $this->clock_running,
            'overtime_periods' => $this->overtime_periods,
            'overtime_length_minutes' => $this->overtime_length_minutes,
            
            // Period Scores
            'period_scores' => $this->period_scores,
            
            // Officials
            'referees' => $this->referees,
            'scorekeepers' => $this->scorekeepers,
            'timekeepers' => $this->timekeepers,
            'referee_users' => UserResource::collection($this->whenLoaded('refereeUsers')),
            
            // Live Game Data
            'live_game' => new LiveGameResource($this->whenLoaded('liveGame')),
            'is_live' => $this->is_live,
            'can_be_scored' => $this->canBeScored(),
            'can_be_started' => $this->canBeStarted(),
            
            // Game Actions
            'game_actions' => GameActionResource::collection($this->whenLoaded('gameActions')),
            // PERF-003: Avoid N+1 by checking for loaded relation or withCount attribute first
            'actions_count' => $this->when(
                $request->has('include_counts'),
                fn() => $this->relationLoaded('gameActions')
                    ? $this->gameActions->count()
                    : ($this->game_actions_count ?? $this->gameActions()->count())
            ),
            
            // Statistics
            'team_stats' => $this->when(
                $request->has('include_stats') || $request->routeIs('*.statistics'),
                fn() => $this->team_stats
            ),
            'player_stats' => $this->when(
                $request->has('include_stats') || $request->routeIs('*.statistics'),
                fn() => $this->player_stats
            ),
            
            // Tournament Info
            'tournament_id' => $this->tournament_id,
            'tournament_round' => $this->tournament_round,
            'tournament_game_number' => $this->tournament_game_number,
            
            // Conditions
            'weather_conditions' => $this->weather_conditions,
            'temperature' => $this->temperature,
            'court_conditions' => $this->court_conditions,
            
            // Media & Streaming
            'is_streamed' => $this->is_streamed,
            'stream_url' => $this->stream_url,
            'media_links' => $this->media_links,
            'media' => $this->when(
                $this->relationLoaded('media'),
                fn() => $this->getMedia('*')->map(fn($media) => [
                    'id' => $media->id,
                    'name' => $media->name,
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'url' => $media->getUrl(),
                    'collection_name' => $media->collection_name,
                ])
            ),
            
            // Notes & Reports
            'pre_game_notes' => $this->pre_game_notes,
            'post_game_notes' => $this->post_game_notes,
            'referee_report' => $this->referee_report,
            'incident_report' => $this->incident_report,
            
            // Attendance
            'attendance' => $this->attendance,
            'capacity' => $this->capacity,
            'attendance_percentage' => $this->attendance_percentage,
            'ticket_prices' => $this->ticket_prices,
            
            // Settings & Rules
            'game_rules' => $this->game_rules,
            'allow_spectators' => $this->allow_spectators,
            'allow_media' => $this->allow_media,
            'allow_recording' => $this->allow_recording,
            'allow_photos' => $this->allow_photos,
            'allow_streaming' => $this->allow_streaming,
            
            // Emergency & Medical
            'emergency_contacts' => $this->emergency_contacts,
            'medical_staff_present' => $this->medical_staff_present,
            
            // Verification
            'stats_verified' => $this->stats_verified,
            'stats_verified_at' => $this->stats_verified_at,
            'stats_verified_by' => new UserResource($this->whenLoaded('statsVerifiedBy')),
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Customize the outgoing response for the resource.
     */
    public function withResponse($request, $response)
    {
        // Add custom headers for live games
        if ($this->resource->is_live) {
            $response->header('X-Game-Live', 'true');
            $response->header('X-Game-Status', $this->resource->status);
        }
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with($request)
    {
        return [
            'meta' => [
                'can_score' => $request->user()?->can('score', $this->resource) ?? false,
                'can_control' => $request->user()?->can('controlGame', $this->resource) ?? false,
                'can_edit' => $request->user()?->can('update', $this->resource) ?? false,
                'can_delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ];
    }
}