<?php

namespace App\Services\ML\VideoAnalysis;

use App\Models\VideoFile;

/**
 * Processes and transforms AI analysis results.
 *
 * Extracted from AIVideoAnalysisService during REFACTOR-001.
 * Handles result validation, cleaning, and transformation.
 */
class VideoAnalysisResultProcessor
{
    /**
     * Post-process and validate analysis results.
     */
    public function postProcessResults(array $results, VideoFile $videoFile): array
    {
        // Validate and clean results
        $processedResults = [
            'analysis_timestamp' => now()->toISOString(),
            'video_id' => $videoFile->id,
            'overall_confidence' => $results['overall_confidence'] ?? 0,
            'analysis_type' => $results['analysis_type'] ?? 'unknown',
            'processing_time' => $results['processing_time'] ?? 0,
        ];

        // Process player detection results
        if (isset($results['players_detected'])) {
            $processedResults['players'] = $this->processPlayerDetectionResults($results['players_detected']);
        }

        // Process court detection results
        if (isset($results['court_detected'])) {
            $processedResults['court'] = $this->processCourtDetectionResults($results['court_detected']);
        }

        // Process action recognition results
        if (isset($results['actions_recognized'])) {
            $processedResults['actions'] = $this->processActionRecognitionResults($results['actions_recognized']);
        }

        // Process shot analysis results
        if (isset($results['shots_analyzed'])) {
            $processedResults['shots'] = $this->processShotAnalysisResults($results['shots_analyzed']);
        }

        return $processedResults;
    }

    /**
     * Process player detection results.
     */
    public function processPlayerDetectionResults(array $results): array
    {
        $processed = [
            'total_players_detected' => count($results),
            'average_confidence' => 0,
            'players' => [],
        ];

        $confidenceSum = 0;
        foreach ($results as $player) {
            $processed['players'][] = [
                'player_id' => $player['player_id'] ?? null,
                'confidence' => $player['confidence'] ?? 0,
                'bounding_box' => $player['bounding_box'] ?? [],
                'jersey_number' => $player['jersey_number'] ?? null,
                'team' => $player['team'] ?? null,
            ];
            $confidenceSum += $player['confidence'] ?? 0;
        }

        $processed['average_confidence'] = count($results) > 0
            ? round($confidenceSum / count($results), 3)
            : 0;

        return $processed;
    }

    /**
     * Process court detection results.
     */
    public function processCourtDetectionResults(array $results): array
    {
        return [
            'court_detected' => $results['detected'] ?? false,
            'confidence' => $results['confidence'] ?? 0,
            'boundaries' => $results['boundaries'] ?? [],
            'key_points' => $results['key_points'] ?? [],
        ];
    }

    /**
     * Process ball tracking results.
     */
    public function processBallTrackingResults(array $results): array
    {
        return [
            'trajectory_points' => count($results),
            'tracking_confidence' => array_sum(array_column($results, 'confidence')) / max(1, count($results)),
            'trajectory' => $results,
        ];
    }

    /**
     * Process action recognition results.
     */
    public function processActionRecognitionResults(array $results): array
    {
        $actionCounts = [];
        foreach ($results as $action) {
            $actionType = $action['action'] ?? 'unknown';
            $actionCounts[$actionType] = ($actionCounts[$actionType] ?? 0) + 1;
        }

        return [
            'total_actions' => count($results),
            'action_counts' => $actionCounts,
            'actions' => $results,
        ];
    }

    /**
     * Process shot analysis results.
     */
    public function processShotAnalysisResults(array $results): array
    {
        $madeShots = array_filter($results, fn ($shot) => ($shot['outcome'] ?? '') === 'made');
        $shotsByType = [];

        foreach ($results as $shot) {
            $type = $shot['shot_type'] ?? 'unknown';
            $shotsByType[$type] = ($shotsByType[$type] ?? 0) + 1;
        }

        return [
            'total_shots' => count($results),
            'made_shots' => count($madeShots),
            'shooting_percentage' => count($results) > 0
                ? round(count($madeShots) / count($results) * 100, 1)
                : 0,
            'shots_by_type' => $shotsByType,
            'shots' => $results,
        ];
    }

    /**
     * Validate result structure.
     */
    public function validateResultStructure(array $results): bool
    {
        // Basic validation - check for required fields
        return isset($results['analysis_type']) || isset($results['overall_confidence']);
    }

    /**
     * Calculate overall confidence from multiple results.
     */
    public function calculateOverallConfidence(array $results): float
    {
        $confidences = [];

        if (isset($results['players']['average_confidence'])) {
            $confidences[] = $results['players']['average_confidence'];
        }

        if (isset($results['court']['confidence'])) {
            $confidences[] = $results['court']['confidence'];
        }

        if (isset($results['actions']['actions'])) {
            $actionConfidences = array_column($results['actions']['actions'], 'confidence');
            if (!empty($actionConfidences)) {
                $confidences[] = array_sum($actionConfidences) / count($actionConfidences);
            }
        }

        return empty($confidences) ? 0 : round(array_sum($confidences) / count($confidences), 3);
    }
}
