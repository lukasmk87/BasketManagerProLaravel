<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingSessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            
            // Team and Trainers
            'team' => [
                'id' => $this->team_id,
                'name' => $this->whenLoaded('team', fn() => $this->team->name),
                'category' => $this->whenLoaded('team', fn() => $this->team->category),
            ],
            'trainer' => [
                'id' => $this->trainer_id,
                'name' => $this->whenLoaded('trainer', fn() => $this->trainer->full_name),
                'email' => $this->whenLoaded('trainer', fn() => $this->trainer->email),
            ],
            'assistant_trainer' => $this->when($this->assistant_trainer_id, [
                'id' => $this->assistant_trainer_id,
                'name' => $this->whenLoaded('assistantTrainer', fn() => $this->assistantTrainer->full_name),
                'email' => $this->whenLoaded('assistantTrainer', fn() => $this->assistantTrainer->email),
            ]),
            
            // Schedule and Timing
            'schedule' => [
                'scheduled_at' => $this->scheduled_at?->toISOString(),
                'scheduled_at_formatted' => $this->scheduled_at?->format('d.m.Y H:i'),
                'actual_start_time' => $this->actual_start_time?->toISOString(),
                'actual_end_time' => $this->actual_end_time?->toISOString(),
                'planned_duration' => $this->planned_duration,
                'actual_duration' => $this->actual_duration,
                'duration' => $this->duration,
                'duration_formatted' => $this->formatDuration($this->duration),
            ],
            
            // Location
            'location' => [
                'venue' => $this->venue,
                'venue_address' => $this->venue_address,
                'court_type' => $this->court_type,
                'court_type_display' => $this->getCourtTypeDisplay(),
            ],
            
            // Session Details
            'session_type' => $this->session_type,
            'session_type_display' => $this->getSessionTypeDisplay(),
            'focus_areas' => $this->focus_areas,
            'intensity_level' => $this->intensity_level,
            'intensity_display' => $this->getIntensityDisplay(),
            'max_participants' => $this->max_participants,
            
            // Status and Progress
            'status' => $this->status,
            'status_display' => $this->getStatusDisplay(),
            'is_completed' => $this->is_completed,
            'is_upcoming' => $this->is_upcoming,
            'is_in_progress' => $this->is_in_progress,
            'can_start' => $this->canStart(),
            'can_complete' => $this->canComplete(),
            
            // Weather (if applicable)
            'weather' => $this->when($this->weather_conditions, [
                'conditions' => $this->weather_conditions,
                'temperature' => $this->temperature,
                'weather_appropriate' => $this->weather_appropriate,
            ]),
            
            // Equipment and Requirements
            'requirements' => [
                'required_equipment' => $this->required_equipment,
                'special_requirements' => $this->special_requirements,
                'safety_notes' => $this->safety_notes,
                'is_mandatory' => $this->is_mandatory,
                'allows_late_arrival' => $this->allows_late_arrival,
                'requires_medical_clearance' => $this->requires_medical_clearance,
            ],
            
            // Evaluation and Feedback
            'evaluation' => [
                'overall_rating' => $this->overall_rating,
                'trainer_notes' => $this->trainer_notes,
                'session_feedback' => $this->session_feedback,
                'goals_achieved' => $this->goals_achieved,
            ],
            
            // Settings
            'notification_settings' => $this->notification_settings,
            
            // Statistics (when loaded)
            'statistics' => $this->when($this->relationLoaded('drills'), [
                'total_drills' => $this->drills->count(),
                'completed_drills' => $this->drills->where('pivot.status', 'completed')->count(),
                'average_drill_rating' => $this->average_drill_rating,
                'total_planned_duration' => $this->calculateTotalPlannedDuration(),
                'total_actual_duration' => $this->calculateTotalActualDuration(),
            ]),
            
            // Attendance (when loaded)
            'attendance_stats' => $this->when($this->relationLoaded('attendance'), [
                'attendance_rate' => $this->attendance_rate,
                'participation_stats' => $this->getParticipationStats(),
            ]),
            
            // Drills (when specifically loaded)
            'drills' => $this->when($this->relationLoaded('drills'), function () {
                return $this->drills->map(function ($drill) {
                    return [
                        'drill' => new DrillResource($drill),
                        'session_config' => [
                            'order_in_session' => $drill->pivot->order_in_session,
                            'planned_duration' => $drill->pivot->planned_duration,
                            'actual_duration' => $drill->pivot->actual_duration,
                            'status' => $drill->pivot->status,
                            'drill_rating' => $drill->pivot->drill_rating,
                            'participants_count' => $drill->pivot->participants_count,
                            'specific_instructions' => $drill->pivot->specific_instructions,
                            'modifications' => $drill->pivot->modifications,
                            'performance_notes' => $drill->pivot->performance_notes,
                            'trainer_observations' => $drill->pivot->trainer_observations,
                            'goals_achieved' => $drill->pivot->goals_achieved,
                        ]
                    ];
                });
            }),
            
            // Attendance details (when specifically loaded)
            'attendance' => $this->when($this->relationLoaded('attendance'), function () {
                return $this->attendance->map(function ($attendance) {
                    return [
                        'id' => $attendance->id,
                        'player' => [
                            'id' => $attendance->player_id,
                            'name' => $attendance->player->full_name ?? null,
                        ],
                        'status' => $attendance->status,
                        'status_display' => $attendance->status_display,
                        'arrival_time' => $attendance->arrival_time?->toISOString(),
                        'minutes_late' => $attendance->minutes_late,
                        'participation_level' => $attendance->participation_level,
                        'effort_rating' => $attendance->effort_rating,
                        'attitude_rating' => $attendance->attitude_rating,
                        'notes' => $attendance->notes,
                    ];
                });
            }),
            
            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Format duration in minutes to human readable format.
     */
    private function formatDuration(?int $minutes): ?string
    {
        if (!$minutes) return null;
        
        if ($minutes < 60) {
            return "{$minutes} Min";
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        if ($remainingMinutes === 0) {
            return "{$hours}h";
        }
        
        return "{$hours}h {$remainingMinutes}m";
    }

    /**
     * Get court type display text.
     */
    private function getCourtTypeDisplay(): ?string
    {
        if (!$this->court_type) return null;
        
        return match($this->court_type) {
            'indoor' => 'Hallensport',
            'outdoor' => 'Outdoor',
            'gym' => 'Sporthalle',
            default => $this->court_type,
        };
    }

    /**
     * Get session type display text.
     */
    private function getSessionTypeDisplay(): string
    {
        return match($this->session_type) {
            'training' => 'Training',
            'scrimmage' => 'Scrimmage',
            'conditioning' => 'Kondition',
            'tactical' => 'Taktik',
            'individual' => 'Individual',
            'team_building' => 'Team Building',
            'recovery' => 'Regeneration',
            default => $this->session_type,
        };
    }

    /**
     * Get intensity display text.
     */
    private function getIntensityDisplay(): string
    {
        return match($this->intensity_level) {
            'low' => 'Niedrig',
            'medium' => 'Mittel',
            'high' => 'Hoch',
            'maximum' => 'Maximum',
            default => $this->intensity_level,
        };
    }

    /**
     * Get status display text.
     */
    private function getStatusDisplay(): string
    {
        return match($this->status) {
            'scheduled' => 'Geplant',
            'in_progress' => 'Läuft',
            'completed' => 'Abgeschlossen',
            'cancelled' => 'Abgesagt',
            'postponed' => 'Verschoben',
            default => $this->status,
        };
    }
}