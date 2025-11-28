<?php

namespace App\Services\ML\VideoAnalysis;

use App\Models\VideoFile;
use App\Jobs\AnalyzeVideoWithAI;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Main orchestrator for AI video analysis.
 *
 * This facade service coordinates all video analysis sub-services
 * and provides a unified public API. Refactored from the original
 * AIVideoAnalysisService during REFACTOR-001.
 *
 * @see \App\Services\AIVideoAnalysisService (deprecated)
 */
class VideoAnalysisService
{
    public function __construct(
        private VideoFrameExtractionService $frameExtractor,
        private PythonScriptExecutorService $pythonExecutor,
        private VideoAnalysisResultProcessor $resultProcessor,
        private VideoAnnotationGeneratorService $annotationGenerator,
        private VideoAnalysisReportService $reportService,
        private VideoAnalysisConfigService $configService
    ) {}

    /**
     * Trigger comprehensive AI analysis for a video.
     */
    public function analyzeVideo(VideoFile $videoFile, array $analysisOptions = []): array
    {
        Log::info("Starting AI analysis for video {$videoFile->id}");

        try {
            // Validate video is ready for AI analysis
            $this->configService->validateVideoForAnalysis($videoFile);

            // Update video status
            $videoFile->update([
                'ai_analysis_status' => 'in_progress',
                'ai_analysis_completed_at' => null,
            ]);

            // Determine analysis type based on video type and options
            $analysisType = $this->configService->determineAnalysisType($videoFile, $analysisOptions);

            // Extract frames for analysis
            $extractedFrames = $this->frameExtractor->extractFramesForAnalysis($videoFile, $analysisType);

            // Perform AI analysis
            $analysisResults = $this->performAIAnalysis($videoFile, $extractedFrames, $analysisType);

            // Post-process and validate results
            $processedResults = $this->resultProcessor->postProcessResults($analysisResults, $videoFile);

            // Generate automatic annotations
            $autoAnnotations = $this->annotationGenerator->generateAutoAnnotations($processedResults, $videoFile);

            // Update video with results
            $this->annotationGenerator->updateVideoWithResults($videoFile, $processedResults, $autoAnnotations);

            // Clean up temporary files
            $this->frameExtractor->cleanupTemporaryFiles($extractedFrames);

            Log::info("AI analysis completed for video {$videoFile->id}", [
                'analysis_type' => $analysisType,
                'confidence_score' => $processedResults['overall_confidence'] ?? 0,
                'annotations_created' => count($autoAnnotations),
            ]);

            return $processedResults;
        } catch (Exception $e) {
            Log::error("AI analysis failed for video {$videoFile->id}: " . $e->getMessage());

            $videoFile->update([
                'ai_analysis_status' => 'failed',
                'ai_analysis_completed_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Queue AI analysis for background processing.
     */
    public function queueAnalysis(VideoFile $videoFile, array $options = []): void
    {
        $videoFile->update(['ai_analysis_status' => 'pending']);

        AnalyzeVideoWithAI::dispatch($videoFile, $options)->onQueue('ai-analysis');

        Log::info("Queued AI analysis for video {$videoFile->id}");
    }

    /**
     * Perform player detection and tracking.
     */
    public function detectPlayers(VideoFile $videoFile, array $frameData = []): array
    {
        Log::info("Starting player detection for video {$videoFile->id}");

        try {
            $scriptPath = $this->pythonExecutor->getScriptPath('player_detection');
            $inputData = $this->pythonExecutor->prepareInputData(
                $videoFile,
                $frameData,
                'player_detection',
                $this->configService->getAnalysisCapabilities()
            );

            $results = $this->pythonExecutor->executePythonScript($scriptPath, $inputData);

            return $this->resultProcessor->processPlayerDetectionResults($results['players'] ?? $results);
        } catch (Exception $e) {
            Log::error("Player detection failed for video {$videoFile->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Perform court detection and mapping.
     */
    public function detectCourt(VideoFile $videoFile, array $frameData = []): array
    {
        Log::info("Starting court detection for video {$videoFile->id}");

        try {
            $scriptPath = $this->pythonExecutor->getScriptPath('court_detection');
            $inputData = $this->pythonExecutor->prepareInputData(
                $videoFile,
                $frameData,
                'court_detection',
                $this->configService->getAnalysisCapabilities()
            );

            $results = $this->pythonExecutor->executePythonScript($scriptPath, $inputData);

            return $this->resultProcessor->processCourtDetectionResults($results['court'] ?? $results);
        } catch (Exception $e) {
            Log::error("Court detection failed for video {$videoFile->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Perform basketball tracking and trajectory analysis.
     */
    public function trackBall(VideoFile $videoFile, array $frameData = []): array
    {
        Log::info("Starting ball tracking for video {$videoFile->id}");

        try {
            $scriptPath = $this->pythonExecutor->getScriptPath('ball_tracking');
            $inputData = $this->pythonExecutor->prepareInputData(
                $videoFile,
                $frameData,
                'ball_tracking',
                $this->configService->getAnalysisCapabilities()
            );

            $results = $this->pythonExecutor->executePythonScript($scriptPath, $inputData);

            return $this->resultProcessor->processBallTrackingResults($results['ball_trajectory'] ?? $results);
        } catch (Exception $e) {
            Log::error("Ball tracking failed for video {$videoFile->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Perform action recognition (shots, passes, etc.).
     */
    public function recognizeActions(VideoFile $videoFile, array $frameData = []): array
    {
        Log::info("Starting action recognition for video {$videoFile->id}");

        try {
            $scriptPath = $this->pythonExecutor->getScriptPath('action_recognition');
            $inputData = $this->pythonExecutor->prepareInputData(
                $videoFile,
                $frameData,
                'action_recognition',
                $this->configService->getAnalysisCapabilities()
            );

            $results = $this->pythonExecutor->executePythonScript($scriptPath, $inputData);

            return $this->resultProcessor->processActionRecognitionResults($results['actions'] ?? $results);
        } catch (Exception $e) {
            Log::error("Action recognition failed for video {$videoFile->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Analyze shot attempts and outcomes.
     */
    public function analyzeShotAttempts(VideoFile $videoFile, array $frameData = []): array
    {
        Log::info("Starting shot analysis for video {$videoFile->id}");

        try {
            $scriptPath = $this->pythonExecutor->getScriptPath('shot_analysis');
            $inputData = $this->pythonExecutor->prepareInputData(
                $videoFile,
                $frameData,
                'shot_analysis',
                $this->configService->getAnalysisCapabilities()
            );

            $results = $this->pythonExecutor->executePythonScript($scriptPath, $inputData);

            return $this->resultProcessor->processShotAnalysisResults($results['shots'] ?? $results);
        } catch (Exception $e) {
            Log::error("Shot analysis failed for video {$videoFile->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get analysis capabilities and configuration.
     */
    public function getAnalysisCapabilities(): array
    {
        return $this->configService->getAnalysisCapabilities();
    }

    /**
     * Update analysis capabilities.
     */
    public function updateCapabilities(array $capabilities): void
    {
        $this->configService->updateCapabilities($capabilities);
    }

    /**
     * Get analysis history for a video.
     */
    public function getAnalysisHistory(VideoFile $videoFile): array
    {
        return $this->configService->getAnalysisHistory($videoFile);
    }

    /**
     * Generate analysis report for a video.
     */
    public function generateAnalysisReport(VideoFile $videoFile): array
    {
        return $this->reportService->generateAnalysisReport($videoFile);
    }

    /**
     * Perform comprehensive AI analysis using Python script.
     */
    private function performAIAnalysis(VideoFile $videoFile, array $extractedFrames, string $analysisType): array
    {
        $scriptPath = $this->pythonExecutor->getScriptPath('comprehensive_analysis');

        $inputData = $this->pythonExecutor->prepareComprehensiveInputData(
            $videoFile,
            $extractedFrames,
            $analysisType,
            $this->configService->getAnalysisCapabilities()
        );

        return $this->pythonExecutor->executePythonScript($scriptPath, $inputData);
    }
}
