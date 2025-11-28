<?php

namespace App\Services\ML\VideoAnalysis;

use App\Models\VideoFile;
use App\Models\VideoAnalysisSession;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

/**
 * Manages AI video analysis configuration and history.
 *
 * Extracted from AIVideoAnalysisService during REFACTOR-001.
 * Handles analysis capabilities, validation, and session history.
 */
class VideoAnalysisConfigService
{
    private array $analysisCapabilities;

    public function __construct()
    {
        $this->analysisCapabilities = config('video_analysis.capabilities', []);
    }

    /**
     * Get analysis capabilities and configuration.
     */
    public function getAnalysisCapabilities(): array
    {
        return $this->analysisCapabilities;
    }

    /**
     * Update analysis capabilities.
     */
    public function updateCapabilities(array $capabilities): void
    {
        $this->analysisCapabilities = array_merge($this->analysisCapabilities, $capabilities);

        Log::info('Updated AI analysis capabilities', $capabilities);
    }

    /**
     * Get specific capability configuration.
     */
    public function getCapability(string $type): array
    {
        return $this->analysisCapabilities[$type] ?? [];
    }

    /**
     * Check if a capability is enabled.
     */
    public function isCapabilityEnabled(string $type): bool
    {
        return ($this->analysisCapabilities[$type]['enabled'] ?? false) === true;
    }

    /**
     * Get analysis history for a video.
     */
    public function getAnalysisHistory(VideoFile $videoFile): array
    {
        $history = [];

        $analysisSessions = $videoFile->analysisSessions()
            ->where('analysis_type', 'ai_analysis')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($analysisSessions as $session) {
            $history[] = [
                'session_id' => $session->id,
                'created_at' => $session->created_at,
                'status' => $session->status,
                'analysis_completeness' => $session->analysis_completeness,
                'key_findings' => $session->key_findings,
            ];
        }

        return $history;
    }

    /**
     * Determine analysis type based on video type and options.
     */
    public function determineAnalysisType(VideoFile $videoFile, array $options): string
    {
        // Check if specific analysis type is requested
        if (isset($options['analysis_type'])) {
            return $options['analysis_type'];
        }

        // Get mapping from config
        $mapping = config('video_analysis.video_type_mapping', []);

        return $mapping[$videoFile->video_type] ?? 'basic_basketball_analysis';
    }

    /**
     * Validate video is ready for AI analysis.
     *
     * @throws Exception
     */
    public function validateVideoForAnalysis(VideoFile $videoFile): void
    {
        if (!Storage::exists($videoFile->file_path)) {
            throw new Exception('Video file not found for analysis');
        }

        if ($videoFile->processing_status !== 'completed') {
            throw new Exception('Video must be processed before AI analysis');
        }

        if (!$videoFile->duration || $videoFile->duration < 1) {
            throw new Exception('Video too short for meaningful analysis');
        }

        if (!$videoFile->ai_analysis_enabled) {
            throw new Exception('AI analysis not enabled for this video');
        }
    }

    /**
     * Get the confidence threshold for a specific capability.
     */
    public function getConfidenceThreshold(string $type): float
    {
        return $this->analysisCapabilities[$type]['confidence_threshold'] ?? 0.7;
    }

    /**
     * Get the minimum confidence threshold for annotations.
     */
    public function getAnnotationConfidenceThreshold(): float
    {
        return config('video_analysis.annotation.min_confidence_threshold', 0.8);
    }
}
