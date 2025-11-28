<?php

namespace App\Services\ML\VideoAnalysis;

use App\Models\VideoFile;
use App\Models\VideoAnnotation;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Generates automatic annotations from AI analysis results.
 *
 * Extracted from AIVideoAnalysisService during REFACTOR-001.
 * Handles annotation creation, play type mapping, and video updates.
 */
class VideoAnnotationGeneratorService
{
    private float $minConfidenceThreshold;
    private int $defaultActionDuration;
    private int $defaultShotDuration;
    private int $systemUserId;
    private array $actionPlayTypeMapping;
    private array $shotPoints;

    public function __construct()
    {
        $this->minConfidenceThreshold = config('video_analysis.annotation.min_confidence_threshold', 0.8);
        $this->defaultActionDuration = config('video_analysis.annotation.default_action_duration', 3);
        $this->defaultShotDuration = config('video_analysis.annotation.default_shot_duration', 5);
        $this->systemUserId = config('video_analysis.annotation.system_user_id', 1);
        $this->actionPlayTypeMapping = config('video_analysis.action_play_type_mapping', []);
        $this->shotPoints = config('video_analysis.shot_points', []);
    }

    /**
     * Generate automatic annotations from analysis results.
     */
    public function generateAutoAnnotations(array $results, VideoFile $videoFile): array
    {
        $annotations = [];

        // Generate annotations for detected actions
        if (isset($results['actions'])) {
            foreach ($results['actions']['actions'] as $action) {
                if (($action['confidence'] ?? 0) >= $this->minConfidenceThreshold) {
                    $annotations[] = [
                        'video_file_id' => $videoFile->id,
                        'created_by_user_id' => $this->systemUserId,
                        'start_time' => $action['timestamp'] ?? 0,
                        'end_time' => ($action['timestamp'] ?? 0) + $this->defaultActionDuration,
                        'annotation_type' => 'play_action',
                        'title' => ucfirst($action['action'] ?? 'Action') . ' detected',
                        'description' => 'AI-detected ' . ($action['action'] ?? 'action'),
                        'play_type' => $this->mapActionToPlayType($action['action'] ?? ''),
                        'is_ai_generated' => true,
                        'ai_confidence' => $action['confidence'] ?? 0,
                        'status' => 'pending_review',
                    ];
                }
            }
        }

        // Generate annotations for shot attempts
        if (isset($results['shots'])) {
            foreach ($results['shots']['shots'] as $shot) {
                if (($shot['confidence'] ?? 0) >= $this->minConfidenceThreshold) {
                    $annotations[] = [
                        'video_file_id' => $videoFile->id,
                        'created_by_user_id' => $this->systemUserId,
                        'start_time' => $shot['timestamp'] ?? 0,
                        'end_time' => ($shot['timestamp'] ?? 0) + $this->defaultShotDuration,
                        'annotation_type' => 'statistical_event',
                        'title' => ucfirst($shot['shot_type'] ?? 'Shot') . ' attempt',
                        'description' => 'AI-detected shot attempt - ' . ($shot['outcome'] ?? 'unknown'),
                        'play_type' => 'shot',
                        'outcome' => $shot['outcome'] === 'made' ? 'successful' : 'unsuccessful',
                        'points_scored' => $this->calculatePointsForShot($shot),
                        'is_ai_generated' => true,
                        'ai_confidence' => $shot['confidence'] ?? 0,
                        'status' => 'pending_review',
                    ];
                }
            }
        }

        // Create the annotations
        $createdAnnotations = [];
        foreach ($annotations as $annotationData) {
            try {
                $annotation = VideoAnnotation::create($annotationData);
                $createdAnnotations[] = $annotation;
            } catch (Exception $e) {
                Log::warning("Failed to create auto-annotation for video {$videoFile->id}: " . $e->getMessage());
            }
        }

        return $createdAnnotations;
    }

    /**
     * Update video with analysis results.
     */
    public function updateVideoWithResults(VideoFile $videoFile, array $results, array $annotations): void
    {
        $updates = [
            'ai_analysis_status' => 'completed',
            'ai_analysis_results' => $results,
            'ai_confidence_score' => $results['overall_confidence'] ?? 0,
            'ai_analysis_completed_at' => now(),
            'annotation_count' => $videoFile->annotations()->count(),
        ];

        $videoFile->update($updates);

        Log::info("Updated video {$videoFile->id} with AI analysis results", [
            'confidence_score' => $results['overall_confidence'] ?? 0,
            'annotations_created' => count($annotations),
        ]);
    }

    /**
     * Map action type to play type.
     */
    public function mapActionToPlayType(string $action): string
    {
        return $this->actionPlayTypeMapping[$action] ?? 'offense';
    }

    /**
     * Calculate points for a shot.
     */
    public function calculatePointsForShot(array $shot): int
    {
        if (($shot['outcome'] ?? '') !== 'made') {
            return 0;
        }

        $shotType = $shot['shot_type'] ?? 'two_point';

        return $this->shotPoints[$shotType] ?? 2;
    }

    /**
     * Get the minimum confidence threshold for annotation generation.
     */
    public function getMinConfidenceThreshold(): float
    {
        return $this->minConfidenceThreshold;
    }

    /**
     * Set the minimum confidence threshold for annotation generation.
     */
    public function setMinConfidenceThreshold(float $threshold): void
    {
        $this->minConfidenceThreshold = $threshold;
    }
}
