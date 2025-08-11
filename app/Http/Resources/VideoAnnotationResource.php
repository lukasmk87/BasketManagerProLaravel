<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoAnnotationResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'annotation_type' => $this->annotation_type,
            'annotation_type_label' => $this->getAnnotationTypeLabel(),
            
            // Time information
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'duration' => $this->end_time - $this->start_time,
            'start_time_formatted' => $this->formatTime($this->start_time),
            'end_time_formatted' => $this->formatTime($this->end_time),
            'duration_formatted' => $this->formatDuration($this->end_time - $this->start_time),
            
            // Basketball-specific data
            'play_type' => $this->play_type,
            'play_type_label' => $this->getPlayTypeLabel(),
            'outcome' => $this->outcome,
            'outcome_label' => $this->getOutcomeLabel(),
            'points_scored' => $this->points_scored,
            'player_involved' => $this->player_involved,
            'team_involved' => $this->team_involved,
            
            // Court positioning
            'court_position_x' => $this->court_position_x,
            'court_position_y' => $this->court_position_y,
            'court_zone' => $this->calculateCourtZone(),
            'court_coordinates' => $this->when(
                $this->court_position_x && $this->court_position_y,
                [
                    'x' => $this->court_position_x,
                    'y' => $this->court_position_y,
                    'zone' => $this->calculateCourtZone(),
                    'percentage' => $this->getCourtPositionPercentage(),
                ]
            ),
            
            // AI Analysis data
            'is_ai_generated' => $this->is_ai_generated,
            'ai_confidence' => $this->ai_confidence,
            'ai_confidence_label' => $this->getConfidenceLabel(),
            'ai_model_version' => $this->ai_model_version,
            'ai_processing_metadata' => $this->when(
                $this->is_ai_generated && $this->ai_processing_metadata,
                json_decode($this->ai_processing_metadata, true)
            ),
            
            // Tags and keywords
            'tags' => $this->tags ? json_decode($this->tags, true) : [],
            'keywords' => $this->keywords ? explode(',', $this->keywords) : [],
            'color_code' => $this->color_code,
            
            // Status and workflow
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'priority' => $this->priority,
            'priority_label' => $this->getPriorityLabel(),
            'review_required' => $this->review_required,
            'reviewed_by_user_id' => $this->reviewed_by_user_id,
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            
            // Visibility and access
            'visibility' => $this->visibility,
            'is_public' => $this->visibility === 'public',
            'share_url' => $this->when(
                $this->visibility === 'public',
                url("/annotations/{$this->id}/share")
            ),
            
            // Frame data
            'frame_start' => $this->frame_start,
            'frame_end' => $this->frame_end,
            'frame_count' => $this->frame_end ? ($this->frame_end - ($this->frame_start ?? 0)) : null,
            'thumbnail_frame' => $this->thumbnail_frame,
            'thumbnail_url' => $this->getThumbnailUrl(),
            
            // Statistical data
            'statistical_data' => $this->when(
                $this->statistical_data,
                json_decode($this->statistical_data, true)
            ),
            
            // Video context
            'video_file' => new VideoFileResource($this->whenLoaded('videoFile')),
            'video_file_id' => $this->video_file_id,
            
            // User relationships
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_by_user_id' => $this->created_by_user_id,
            'reviewer' => new UserResource($this->whenLoaded('reviewer')),
            
            // Related annotations
            'related_annotations' => $this->when(
                $this->relationLoaded('relatedAnnotations'),
                VideoAnnotationResource::collection($this->relatedAnnotations)
            ),
            'parent_annotation_id' => $this->parent_annotation_id,
            'has_child_annotations' => $this->children()->exists(),
            
            // Interaction data
            'view_count' => $this->view_count,
            'comment_count' => $this->comments_count ?? 0,
            'reaction_summary' => $this->getReactionSummary(),
            
            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Computed properties
            'is_long_annotation' => $this->duration > 30,
            'is_recent' => $this->created_at->isAfter(now()->subDays(7)),
            'overlaps_with_others' => $this->checkForOverlaps(),
            'annotation_quality_score' => $this->calculateQualityScore(),
            
            // Export data
            'export_data' => $this->when(
                $request->get('include_export_data'),
                [
                    'csv_line' => $this->toCsvLine(),
                    'srt_entry' => $this->toSrtEntry(),
                    'vtt_entry' => $this->toVttEntry(),
                ]
            ),
            
            // Permissions
            'can_edit' => $request->user()?->can('update', $this->resource) ?? false,
            'can_delete' => $request->user()?->can('delete', $this->resource) ?? false,
            'can_review' => $request->user()?->can('review', $this->resource) ?? false,
        ];
    }

    /**
     * Get human-readable annotation type label.
     */
    private function getAnnotationTypeLabel(): string
    {
        $labels = [
            'play_action' => 'Spielaktion',
            'statistical_event' => 'Statistisches Ereignis',
            'coaching_note' => 'Trainer-Notiz',
            'tactical_analysis' => 'Taktische Analyse',
            'player_performance' => 'Spieler-Leistung',
            'referee_decision' => 'Schiedsrichter-Entscheidung',
            'technical_issue' => 'Technisches Problem',
            'highlight_moment' => 'Highlight-Moment',
        ];

        return $labels[$this->annotation_type] ?? 'Unbekannt';
    }

    /**
     * Get human-readable play type label.
     */
    private function getPlayTypeLabel(): ?string
    {
        if (!$this->play_type) return null;

        $labels = [
            'shot' => 'Wurf',
            'pass' => 'Pass',
            'dribble' => 'Dribbling',
            'rebound' => 'Rebound',
            'steal' => 'Ballgewinn',
            'block' => 'Block',
            'foul' => 'Foul',
            'turnover' => 'Ballverlust',
            'timeout' => 'Auszeit',
            'substitution' => 'Wechsel',
            'free_throw' => 'Freiwurf',
            'jump_ball' => 'Sprungball',
        ];

        return $labels[$this->play_type] ?? ucfirst($this->play_type);
    }

    /**
     * Get human-readable outcome label.
     */
    private function getOutcomeLabel(): ?string
    {
        if (!$this->outcome) return null;

        $labels = [
            'successful' => 'Erfolgreich',
            'unsuccessful' => 'Erfolglos',
            'neutral' => 'Neutral',
            'positive' => 'Positiv',
            'negative' => 'Negativ',
        ];

        return $labels[$this->outcome] ?? ucfirst($this->outcome);
    }

    /**
     * Get human-readable status label.
     */
    private function getStatusLabel(): string
    {
        $labels = [
            'draft' => 'Entwurf',
            'published' => 'Veröffentlicht',
            'pending_review' => 'Prüfung ausstehend',
            'approved' => 'Genehmigt',
            'rejected' => 'Abgelehnt',
            'archived' => 'Archiviert',
        ];

        return $labels[$this->status] ?? 'Unbekannt';
    }

    /**
     * Get human-readable priority label.
     */
    private function getPriorityLabel(): string
    {
        $labels = [
            'low' => 'Niedrig',
            'normal' => 'Normal',
            'high' => 'Hoch',
            'urgent' => 'Dringend',
        ];

        return $labels[$this->priority] ?? 'Normal';
    }

    /**
     * Get confidence level label for AI annotations.
     */
    private function getConfidenceLabel(): ?string
    {
        if (!$this->is_ai_generated || !$this->ai_confidence) return null;

        $confidence = $this->ai_confidence;

        if ($confidence >= 0.9) return 'Sehr hoch';
        if ($confidence >= 0.8) return 'Hoch';
        if ($confidence >= 0.7) return 'Mittel';
        if ($confidence >= 0.6) return 'Niedrig';
        
        return 'Sehr niedrig';
    }

    /**
     * Calculate court zone based on position.
     */
    private function calculateCourtZone(): ?string
    {
        if (!$this->court_position_x || !$this->court_position_y) return null;

        $x = $this->court_position_x;
        $y = $this->court_position_y;

        // Simplified basketball court zones
        // Assume court is 1000x600 units with (0,0) at top-left
        
        if ($y < 190) {
            if ($x < 200) return 'Ecke links oben';
            if ($x > 800) return 'Ecke rechts oben';
            return 'Obere Zone';
        }
        
        if ($y > 410) {
            if ($x < 200) return 'Ecke links unten';
            if ($x > 800) return 'Ecke rechts unten';
            return 'Untere Zone';
        }
        
        if ($x < 250) return 'Linke Seite';
        if ($x > 750) return 'Rechte Seite';
        
        // Center area
        if ($y >= 250 && $y <= 350 && $x >= 400 && $x <= 600) {
            return 'Mittelkreis';
        }
        
        return 'Zentrale Zone';
    }

    /**
     * Get court position as percentages.
     */
    private function getCourtPositionPercentage(): ?array
    {
        if (!$this->court_position_x || !$this->court_position_y) return null;

        return [
            'x_percent' => round(($this->court_position_x / 1000) * 100, 1),
            'y_percent' => round(($this->court_position_y / 600) * 100, 1),
        ];
    }

    /**
     * Format time in MM:SS format.
     */
    private function formatTime(int $seconds): string
    {
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }

    /**
     * Format duration in human readable format.
     */
    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' Sek.';
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($remainingSeconds === 0) {
            return $minutes . ' Min.';
        }
        
        return $minutes . ' Min. ' . $remainingSeconds . ' Sek.';
    }

    /**
     * Get thumbnail URL for the annotation.
     */
    private function getThumbnailUrl(): ?string
    {
        if (!$this->thumbnail_frame && !$this->start_time) return null;

        $timestamp = $this->thumbnail_frame ?? $this->start_time;
        return url("/api/videos/{$this->video_file_id}/thumbnail?timestamp={$timestamp}");
    }

    /**
     * Get reaction summary.
     */
    private function getReactionSummary(): array
    {
        // This would be populated from a reactions table
        // For now, return empty structure
        return [
            'total_reactions' => 0,
            'likes' => 0,
            'dislikes' => 0,
            'helpful' => 0,
            'not_helpful' => 0,
        ];
    }

    /**
     * Check if annotation overlaps with others.
     */
    private function checkForOverlaps(): bool
    {
        if (!$this->video_file_id) return false;

        $overlapping = \App\Models\VideoAnnotation::where('video_file_id', $this->video_file_id)
            ->where('id', '!=', $this->id)
            ->where('status', 'published')
            ->where(function($query) {
                $query->whereBetween('start_time', [$this->start_time, $this->end_time])
                      ->orWhereBetween('end_time', [$this->start_time, $this->end_time])
                      ->orWhere(function($subQuery) {
                          $subQuery->where('start_time', '<=', $this->start_time)
                                   ->where('end_time', '>=', $this->end_time);
                      });
            })
            ->exists();

        return $overlapping;
    }

    /**
     * Calculate annotation quality score.
     */
    private function calculateQualityScore(): int
    {
        $score = 50; // Base score
        
        // Title and description quality
        if (strlen($this->title) >= 10) $score += 10;
        if ($this->description && strlen($this->description) >= 20) $score += 10;
        
        // Basketball-specific data
        if ($this->play_type) $score += 5;
        if ($this->outcome) $score += 5;
        if ($this->court_position_x && $this->court_position_y) $score += 10;
        
        // Duration appropriateness
        $duration = $this->end_time - $this->start_time;
        if ($duration >= 3 && $duration <= 30) $score += 10;
        
        // AI confidence (for AI-generated)
        if ($this->is_ai_generated && $this->ai_confidence) {
            $score += (int)($this->ai_confidence * 10);
        }
        
        // Status bonus
        if ($this->status === 'published') $score += 5;
        
        return min(100, max(0, $score));
    }

    /**
     * Convert to CSV line format.
     */
    private function toCsvLine(): string
    {
        return implode(',', [
            $this->id,
            $this->start_time,
            $this->end_time,
            '"' . str_replace('"', '""', $this->title) . '"',
            '"' . str_replace('"', '""', $this->description ?? '') . '"',
            $this->annotation_type,
            $this->play_type ?? '',
            $this->outcome ?? '',
            $this->points_scored ?? 0,
            $this->is_ai_generated ? 'AI' : 'Manual',
            $this->ai_confidence ?? '',
        ]);
    }

    /**
     * Convert to SRT subtitle entry.
     */
    private function toSrtEntry(): string
    {
        $startTime = $this->formatSrtTime($this->start_time);
        $endTime = $this->formatSrtTime($this->end_time);
        
        $text = $this->title;
        if ($this->description) {
            $text .= "\n" . $this->description;
        }
        
        return "{$startTime} --> {$endTime}\n{$text}\n\n";
    }

    /**
     * Convert to WebVTT entry.
     */
    private function toVttEntry(): string
    {
        $startTime = $this->formatVttTime($this->start_time);
        $endTime = $this->formatVttTime($this->end_time);
        
        return "{$startTime} --> {$endTime}\n{$this->title}\n\n";
    }

    /**
     * Format time for SRT format.
     */
    private function formatSrtTime(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        return sprintf('%02d:%02d:%02d,000', $hours, $minutes, $secs);
    }

    /**
     * Format time for WebVTT format.
     */
    private function formatVttTime(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        return sprintf('%02d:%02d:%02d.000', $hours, $minutes, $secs);
    }
}