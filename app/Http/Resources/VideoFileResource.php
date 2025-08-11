<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoFileResource extends JsonResource
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
            'video_type' => $this->video_type,
            'visibility' => $this->visibility,
            
            // File information
            'file_name' => $this->file_name,
            'file_size' => $this->file_size,
            'file_size_human' => $this->getHumanReadableSize($this->file_size),
            'mime_type' => $this->mime_type,
            'file_path' => $this->when(
                $request->user()?->can('view', $this->resource),
                $this->file_path
            ),
            
            // Video metadata
            'duration' => $this->duration,
            'duration_formatted' => $this->formatDuration($this->duration),
            'width' => $this->width,
            'height' => $this->height,
            'frame_rate' => $this->frame_rate,
            'bitrate' => $this->bitrate,
            'aspect_ratio' => $this->calculateAspectRatio(),
            
            // Processing status
            'processing_status' => $this->processing_status,
            'processing_progress' => $this->processing_progress,
            'processing_error' => $this->when(
                $this->processing_status === 'failed',
                $this->processing_error
            ),
            'processing_completed_at' => $this->processing_completed_at?->toISOString(),
            
            // AI Analysis
            'ai_analysis_enabled' => $this->ai_analysis_enabled,
            'ai_analysis_status' => $this->ai_analysis_status,
            'ai_confidence_score' => $this->ai_confidence_score,
            'ai_analysis_completed_at' => $this->ai_analysis_completed_at?->toISOString(),
            'ai_analysis_summary' => $this->when(
                $this->ai_analysis_status === 'completed',
                $this->getAiAnalysisSummary()
            ),
            
            // Thumbnails and previews
            'thumbnail_path' => $this->thumbnail_path,
            'thumbnail_url' => $this->getThumbnailUrl(),
            'preview_frames' => $this->preview_frames,
            'poster_frame_time' => $this->poster_frame_time,
            
            // Streaming URLs (when available)
            'streaming_urls' => $this->when(
                $this->processing_status === 'completed',
                $this->getStreamingUrls()
            ),
            
            // Basketball-specific metadata
            'game_period' => $this->game_period,
            'game_clock_start' => $this->game_clock_start,
            'game_clock_end' => $this->game_clock_end,
            'court_side' => $this->court_side,
            'camera_angle' => $this->camera_angle,
            'recording_quality' => $this->recording_quality,
            
            // Tags and categorization
            'tags' => $this->tags ? json_decode($this->tags, true) : [],
            'keywords' => $this->keywords ? explode(',', $this->keywords) : [],
            
            // Statistics
            'views_count' => $this->views_count,
            'annotation_count' => $this->annotations_count ?? $this->annotations()->count(),
            'download_count' => $this->download_count,
            'share_count' => $this->share_count,
            
            // Relationships
            'uploader' => new UserResource($this->whenLoaded('uploader')),
            'game' => new GameResource($this->whenLoaded('game')),
            'team' => new TeamResource($this->whenLoaded('team')),
            'tournament' => new TournamentResource($this->whenLoaded('tournament')),
            
            // Annotations (limited to prevent large responses)
            'annotations' => VideoAnnotationResource::collection(
                $this->whenLoaded('annotations')
            ),
            'recent_annotations' => VideoAnnotationResource::collection(
                $this->when(
                    !$this->relationLoaded('annotations') && $request->get('include_recent_annotations'),
                    $this->annotations()->latest()->limit(5)->get()
                )
            ),
            
            // Analysis sessions
            'analysis_sessions' => VideoAnalysisSessionResource::collection(
                $this->whenLoaded('analysisSessions')
            ),
            
            // Media library attachments
            'media' => $this->when(
                $this->relationLoaded('media'),
                $this->getMedia()->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'collection_name' => $media->collection_name,
                        'name' => $media->name,
                        'file_name' => $media->file_name,
                        'mime_type' => $media->mime_type,
                        'size' => $media->size,
                        'url' => $media->getUrl(),
                        'custom_properties' => $media->custom_properties,
                    ];
                })
            ),
            
            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            
            // Additional computed fields
            'is_processing' => $this->processing_status === 'processing',
            'is_ready_for_playback' => $this->processing_status === 'completed',
            'has_ai_analysis' => $this->ai_analysis_status === 'completed',
            'supports_streaming' => $this->supportsStreaming(),
            'estimated_processing_time' => $this->when(
                $this->processing_status === 'pending',
                $this->estimateProcessingTime()
            ),
            
            // Quality information
            'quality_variants' => $this->when(
                $this->processing_status === 'completed',
                $this->getQualityVariants()
            ),
            
            // Permissions
            'can_edit' => $request->user()?->can('update', $this->resource) ?? false,
            'can_delete' => $request->user()?->can('delete', $this->resource) ?? false,
            'can_download' => $request->user()?->can('download', $this->resource) ?? false,
            'can_analyze' => $request->user()?->can('analyze', $this->resource) ?? false,
        ];
    }

    /**
     * Get human readable file size.
     */
    private function getHumanReadableSize(?int $bytes): ?string
    {
        if (!$bytes) return null;
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor(log($bytes) / log(1024));
        
        return round($bytes / pow(1024, $factor), 2) . ' ' . $units[$factor];
    }

    /**
     * Format duration in human readable format.
     */
    private function formatDuration(?int $seconds): ?string
    {
        if (!$seconds) return null;
        
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
        }
        
        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }

    /**
     * Calculate aspect ratio.
     */
    private function calculateAspectRatio(): ?string
    {
        if (!$this->width || !$this->height) return null;
        
        $gcd = gcd($this->width, $this->height);
        $ratioWidth = $this->width / $gcd;
        $ratioHeight = $this->height / $gcd;
        
        return "{$ratioWidth}:{$ratioHeight}";
    }

    /**
     * Get AI analysis summary.
     */
    private function getAiAnalysisSummary(): array
    {
        $results = $this->ai_analysis_results;
        if (!$results || !is_array($results)) return [];
        
        return [
            'players_detected' => $results['players']['total_players_detected'] ?? 0,
            'actions_recognized' => $results['actions']['total_actions'] ?? 0,
            'shots_analyzed' => $results['shots']['total_shots'] ?? 0,
            'court_detected' => $results['court']['court_detected'] ?? false,
            'confidence_score' => $results['overall_confidence'] ?? 0,
            'key_insights' => $this->extractKeyInsights($results),
        ];
    }

    /**
     * Extract key insights from AI analysis.
     */
    private function extractKeyInsights(array $results): array
    {
        $insights = [];
        
        // Player insights
        if (isset($results['players']['total_players_detected'])) {
            $playerCount = $results['players']['total_players_detected'];
            if ($playerCount >= 8) {
                $insights[] = 'Vollständiges Team-Scrimmage erkannt';
            } elseif ($playerCount >= 4) {
                $insights[] = 'Kleingruppen-Training erkannt';
            }
        }
        
        // Shot insights
        if (isset($results['shots']['shooting_percentage'])) {
            $shootingPct = $results['shots']['shooting_percentage'];
            if ($shootingPct > 50) {
                $insights[] = 'Gute Wurfquote (' . $shootingPct . '%)';
            }
        }
        
        // Action insights
        if (isset($results['actions']['action_counts'])) {
            $actions = $results['actions']['action_counts'];
            if (isset($actions['shot']) && $actions['shot'] > 10) {
                $insights[] = 'Viele Wurfversuche erkannt';
            }
        }
        
        return $insights;
    }

    /**
     * Get thumbnail URL.
     */
    private function getThumbnailUrl(): ?string
    {
        if (!$this->thumbnail_path) return null;
        
        // Return signed URL for private storage
        return url("/api/videos/{$this->id}/thumbnail");
    }

    /**
     * Get streaming URLs.
     */
    private function getStreamingUrls(): array
    {
        if ($this->processing_status !== 'completed') return [];
        
        return [
            'hls' => url("/api/videos/{$this->id}/stream/hls"),
            'mp4_720p' => url("/api/videos/{$this->id}/stream/mp4?quality=720p"),
            'mp4_480p' => url("/api/videos/{$this->id}/stream/mp4?quality=480p"),
            'mp4_360p' => url("/api/videos/{$this->id}/stream/mp4?quality=360p"),
        ];
    }

    /**
     * Check if video supports streaming.
     */
    private function supportsStreaming(): bool
    {
        return $this->processing_status === 'completed' && 
               in_array($this->mime_type, ['video/mp4', 'video/webm', 'video/ogg']);
    }

    /**
     * Estimate processing time based on file size and duration.
     */
    private function estimateProcessingTime(): array
    {
        $sizeMinutes = ($this->file_size ?? 0) / (1024 * 1024) * 0.1; // ~0.1 min per MB
        $durationMinutes = ($this->duration ?? 0) / 60 * 0.5; // ~0.5x duration
        
        $estimatedMinutes = max($sizeMinutes, $durationMinutes);
        $estimatedMinutes = max(2, min(30, $estimatedMinutes)); // Between 2-30 minutes
        
        return [
            'estimated_minutes' => round($estimatedMinutes),
            'estimated_completion' => now()->addMinutes($estimatedMinutes)->toISOString()
        ];
    }

    /**
     * Get available quality variants.
     */
    private function getQualityVariants(): array
    {
        $variants = [];
        
        if ($this->width >= 1280) {
            $variants[] = ['quality' => '720p', 'width' => 1280, 'height' => 720];
        }
        if ($this->width >= 854) {
            $variants[] = ['quality' => '480p', 'width' => 854, 'height' => 480];
        }
        if ($this->width >= 640) {
            $variants[] = ['quality' => '360p', 'width' => 640, 'height' => 360];
        }
        
        $variants[] = ['quality' => 'original', 'width' => $this->width, 'height' => $this->height];
        
        return $variants;
    }
}

/**
 * Helper function to calculate GCD.
 */
if (!function_exists('gcd')) {
    function gcd(int $a, int $b): int {
        return $b ? gcd($b, $a % $b) : $a;
    }
}