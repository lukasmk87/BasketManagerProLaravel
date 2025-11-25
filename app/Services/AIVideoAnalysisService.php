<?php

namespace App\Services;

use App\Models\VideoFile;
use App\Models\VideoAnnotation;
use App\Models\VideoAnalysisSession;
use App\Jobs\AnalyzeVideoWithAI;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Exception;

class AIVideoAnalysisService
{
    /**
     * Basketball-specific AI analysis capabilities.
     */
    private array $analysisCapabilities = [
        'player_detection' => [
            'enabled' => true,
            'confidence_threshold' => 0.7,
            'max_players_per_frame' => 12,
            'tracking_enabled' => true,
        ],
        'court_detection' => [
            'enabled' => true,
            'confidence_threshold' => 0.8,
            'court_segmentation' => true,
            'boundary_detection' => true,
        ],
        'ball_detection' => [
            'enabled' => true,
            'confidence_threshold' => 0.6,
            'tracking_enabled' => true,
            'trajectory_analysis' => true,
        ],
        'action_recognition' => [
            'enabled' => true,
            'actions' => ['shot', 'pass', 'dribble', 'rebound', 'steal', 'block'],
            'confidence_threshold' => 0.75,
        ],
        'play_classification' => [
            'enabled' => true,
            'play_types' => ['offense', 'defense', 'transition', 'set_play', 'fast_break'],
            'confidence_threshold' => 0.7,
        ],
        'shot_analysis' => [
            'enabled' => true,
            'shot_detection' => true,
            'shot_outcome' => true,
            'shot_location' => true,
            'release_point_analysis' => true,
        ],
    ];

    /**
     * Python script paths for different analysis types.
     */
    private array $pythonScripts = [
        'player_detection' => 'python/basketball_ai/detect_players.py',
        'court_detection' => 'python/basketball_ai/detect_court.py',
        'ball_tracking' => 'python/basketball_ai/track_ball.py',
        'action_recognition' => 'python/basketball_ai/recognize_actions.py',
        'play_analysis' => 'python/basketball_ai/analyze_plays.py',
        'shot_analysis' => 'python/basketball_ai/analyze_shots.py',
        'comprehensive_analysis' => 'python/basketball_ai/comprehensive_analysis.py',
    ];

    /**
     * Trigger comprehensive AI analysis for a video.
     */
    public function analyzeVideo(VideoFile $videoFile, array $analysisOptions = []): array
    {
        Log::info("Starting AI analysis for video {$videoFile->id}");
        
        try {
            // Validate video is ready for AI analysis
            $this->validateVideoForAnalysis($videoFile);
            
            // Update video status
            $videoFile->update([
                'ai_analysis_status' => 'in_progress',
                'ai_analysis_completed_at' => null,
            ]);
            
            // Determine analysis type based on video type and options
            $analysisType = $this->determineAnalysisType($videoFile, $analysisOptions);
            
            // Extract frames for analysis
            $extractedFrames = $this->extractFramesForAnalysis($videoFile, $analysisType);
            
            // Perform AI analysis
            $analysisResults = $this->performAIAnalysis($videoFile, $extractedFrames, $analysisType);
            
            // Post-process and validate results
            $processedResults = $this->postProcessResults($analysisResults, $videoFile);
            
            // Generate automatic annotations
            $autoAnnotations = $this->generateAutoAnnotations($processedResults, $videoFile);
            
            // Update video with results
            $this->updateVideoWithResults($videoFile, $processedResults, $autoAnnotations);
            
            // Clean up temporary files
            $this->cleanupTemporaryFiles($extractedFrames);
            
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
            $scriptPath = $this->getScriptPath('player_detection');
            $inputData = $this->prepareInputData($videoFile, $frameData, 'player_detection');
            
            $results = $this->executePythonScript($scriptPath, $inputData);
            
            return $this->processPlayerDetectionResults($results);
            
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
            $scriptPath = $this->getScriptPath('court_detection');
            $inputData = $this->prepareInputData($videoFile, $frameData, 'court_detection');
            
            $results = $this->executePythonScript($scriptPath, $inputData);
            
            return $this->processCourtDetectionResults($results);
            
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
            $scriptPath = $this->getScriptPath('ball_tracking');
            $inputData = $this->prepareInputData($videoFile, $frameData, 'ball_tracking');
            
            $results = $this->executePythonScript($scriptPath, $inputData);
            
            return $this->processBallTrackingResults($results);
            
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
            $scriptPath = $this->getScriptPath('action_recognition');
            $inputData = $this->prepareInputData($videoFile, $frameData, 'action_recognition');
            
            $results = $this->executePythonScript($scriptPath, $inputData);
            
            return $this->processActionRecognitionResults($results);
            
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
            $scriptPath = $this->getScriptPath('shot_analysis');
            $inputData = $this->prepareInputData($videoFile, $frameData, 'shot_analysis');
            
            $results = $this->executePythonScript($scriptPath, $inputData);
            
            return $this->processShotAnalysisResults($results);
            
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
        return $this->analysisCapabilities;
    }

    /**
     * Update analysis capabilities.
     */
    public function updateCapabilities(array $capabilities): void
    {
        $this->analysisCapabilities = array_merge($this->analysisCapabilities, $capabilities);
        
        Log::info("Updated AI analysis capabilities", $capabilities);
    }

    /**
     * Get analysis history for a video.
     */
    public function getAnalysisHistory(VideoFile $videoFile): array
    {
        $history = [];
        
        // Get analysis sessions
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
     * Generate analysis report for a video.
     */
    public function generateAnalysisReport(VideoFile $videoFile): array
    {
        $results = $videoFile->ai_analysis_results ?? [];
        
        $report = [
            'video_id' => $videoFile->id,
            'analysis_date' => $videoFile->ai_analysis_completed_at,
            'overall_confidence' => $results['overall_confidence'] ?? 0,
            'analysis_summary' => $this->generateAnalysisSummary($results),
            'detected_elements' => $this->summarizeDetectedElements($results),
            'basketball_insights' => $this->generateBasketballInsights($results),
            'recommendations' => $this->generateRecommendations($results, $videoFile),
        ];
        
        Log::info("Generated AI analysis report for video {$videoFile->id}");
        
        return $report;
    }

    // Private helper methods

    private function validateVideoForAnalysis(VideoFile $videoFile): void
    {
        if (!Storage::exists($videoFile->file_path)) {
            throw new Exception("Video file not found for analysis");
        }
        
        if ($videoFile->processing_status !== 'completed') {
            throw new Exception("Video must be processed before AI analysis");
        }
        
        if (!$videoFile->duration || $videoFile->duration < 1) {
            throw new Exception("Video too short for meaningful analysis");
        }
        
        if (!$videoFile->ai_analysis_enabled) {
            throw new Exception("AI analysis not enabled for this video");
        }
    }

    private function determineAnalysisType(VideoFile $videoFile, array $options): string
    {
        // Check if specific analysis type is requested
        if (isset($options['analysis_type'])) {
            return $options['analysis_type'];
        }
        
        // Determine based on video type
        switch ($videoFile->video_type) {
            case 'full_game':
                return 'comprehensive_game_analysis';
            case 'game_highlights':
                return 'highlight_analysis';
            case 'training_session':
                return 'training_analysis';
            case 'drill_demo':
                return 'drill_analysis';
            case 'player_analysis':
                return 'player_performance_analysis';
            case 'tactical_analysis':
                return 'tactical_analysis';
            default:
                return 'basic_basketball_analysis';
        }
    }

    private function extractFramesForAnalysis(VideoFile $videoFile, string $analysisType): array
    {
        $videoPath = Storage::path($videoFile->file_path);
        $outputDir = storage_path("app/temp/ai_analysis/{$videoFile->id}/frames/");
        
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        // Determine frame extraction strategy based on analysis type
        $extractionStrategy = $this->getFrameExtractionStrategy($analysisType, $videoFile->duration);
        
        $extractedFrames = [];
        
        foreach ($extractionStrategy['timestamps'] as $index => $timestamp) {
            $framePath = $outputDir . "frame_{$index}_{$timestamp}.jpg";
            
            if ($this->extractSingleFrame($videoPath, $timestamp, $framePath)) {
                $extractedFrames[] = [
                    'timestamp' => $timestamp,
                    'path' => $framePath,
                    'frame_number' => $index,
                ];
            }
        }
        
        Log::info("Extracted " . count($extractedFrames) . " frames for AI analysis of video {$videoFile->id}");
        
        return $extractedFrames;
    }

    private function getFrameExtractionStrategy(string $analysisType, int $duration): array
    {
        $strategies = [
            'comprehensive_game_analysis' => [
                'interval' => 30, // Every 30 seconds
                'max_frames' => 200,
            ],
            'highlight_analysis' => [
                'interval' => 5, // Every 5 seconds for highlights
                'max_frames' => 100,
            ],
            'training_analysis' => [
                'interval' => 20, // Every 20 seconds
                'max_frames' => 150,
            ],
            'drill_analysis' => [
                'interval' => 10, // Every 10 seconds for drills
                'max_frames' => 50,
            ],
            'basic_basketball_analysis' => [
                'interval' => 60, // Every minute
                'max_frames' => 50,
            ],
        ];
        
        $strategy = $strategies[$analysisType] ?? $strategies['basic_basketball_analysis'];
        
        // Generate timestamps
        $timestamps = [];
        $interval = $strategy['interval'];
        $maxFrames = $strategy['max_frames'];
        
        for ($time = $interval; $time < $duration && count($timestamps) < $maxFrames; $time += $interval) {
            $timestamps[] = $time;
        }
        
        return ['timestamps' => $timestamps, 'strategy' => $strategy];
    }

    /**
     * Extract a single frame from a video at a specific timestamp.
     *
     * SEC-007: Validates video path is within allowed storage directory
     * and sanitizes timestamp parameter before passing to ffmpeg.
     */
    private function extractSingleFrame(string $videoPath, int $timestamp, string $outputPath): bool
    {
        // SEC-007: Validate video path is within allowed storage directory
        $allowedBasePath = realpath(storage_path('app'));
        $realVideoPath = realpath($videoPath);

        // If realpath returns false, the file doesn't exist or path is invalid
        if ($realVideoPath === false) {
            Log::warning('SEC-007: Video file does not exist', ['path' => $videoPath]);
            return false;
        }

        // Ensure path is within allowed storage directory (prevent path traversal)
        if (!str_starts_with($realVideoPath, $allowedBasePath)) {
            Log::warning('SEC-007: Attempted path traversal in video extraction', [
                'attempted_path' => $videoPath,
                'real_path' => $realVideoPath,
                'allowed_base' => $allowedBasePath,
            ]);
            return false;
        }

        // SEC-007: Validate timestamp is within safe range (0 to 24 hours max)
        $timestamp = max(0, min(86400, $timestamp));

        // SEC-007: Validate output path is within temp directory
        $allowedOutputBase = realpath(storage_path('app/temp'));
        $outputDir = dirname($outputPath);
        if (!str_starts_with(realpath($outputDir) ?: '', $allowedOutputBase ?: storage_path('app/temp'))) {
            Log::warning('SEC-007: Invalid output path for frame extraction', ['path' => $outputPath]);
            return false;
        }

        $command = [
            'ffmpeg',
            '-i', $realVideoPath, // Use validated real path
            '-ss', (string) $timestamp,
            '-vframes', '1',
            '-vf', 'scale=1280:720:force_original_aspect_ratio=decrease',
            '-q:v', '2',
            '-y',
            $outputPath
        ];

        $result = Process::run($command);

        return $result->successful() && file_exists($outputPath);
    }

    private function performAIAnalysis(VideoFile $videoFile, array $extractedFrames, string $analysisType): array
    {
        $scriptPath = $this->getScriptPath('comprehensive_analysis');
        
        $inputData = [
            'video_id' => $videoFile->id,
            'video_path' => Storage::path($videoFile->file_path),
            'analysis_type' => $analysisType,
            'frames' => $extractedFrames,
            'video_metadata' => [
                'duration' => $videoFile->duration,
                'width' => $videoFile->width,
                'height' => $videoFile->height,
                'fps' => $videoFile->frame_rate,
            ],
            'capabilities' => $this->analysisCapabilities,
            'video_type' => $videoFile->video_type,
        ];
        
        return $this->executePythonScript($scriptPath, $inputData);
    }

    private function getScriptPath(string $scriptType): string
    {
        $relativePath = $this->pythonScripts[$scriptType] ?? null;
        
        if (!$relativePath) {
            throw new Exception("Unknown script type: {$scriptType}");
        }
        
        $fullPath = base_path($relativePath);
        
        if (!file_exists($fullPath)) {
            // Create placeholder script for development
            $this->createPlaceholderScript($fullPath, $scriptType);
        }
        
        return $fullPath;
    }

    private function prepareInputData(VideoFile $videoFile, array $frameData, string $analysisType): array
    {
        return [
            'video_id' => $videoFile->id,
            'video_path' => Storage::path($videoFile->file_path),
            'analysis_type' => $analysisType,
            'frame_data' => $frameData,
            'capabilities' => $this->analysisCapabilities[$analysisType] ?? [],
            'video_metadata' => [
                'duration' => $videoFile->duration,
                'width' => $videoFile->width,
                'height' => $videoFile->height,
                'video_type' => $videoFile->video_type,
            ],
        ];
    }

    private function executePythonScript(string $scriptPath, array $inputData): array
    {
        // Create temporary input file
        $inputFile = storage_path('app/temp/ai_input_' . Str::uuid() . '.json');
        file_put_contents($inputFile, json_encode($inputData, JSON_PRETTY_PRINT));
        
        // Create output file path
        $outputFile = storage_path('app/temp/ai_output_' . Str::uuid() . '.json');
        
        try {
            // Execute Python script
            $command = ['python3', $scriptPath, $inputFile, $outputFile];
            
            $result = Process::timeout(1800) // 30 minutes timeout
                           ->run($command);
            
            if (!$result->successful()) {
                throw new Exception("Python script execution failed: " . $result->errorOutput());
            }
            
            // Read results
            if (!file_exists($outputFile)) {
                throw new Exception("Python script did not produce output file");
            }
            
            $output = json_decode(file_get_contents($outputFile), true);
            
            if (!$output) {
                throw new Exception("Invalid JSON output from Python script");
            }
            
            return $output;
            
        } finally {
            // Clean up temporary files
            if (file_exists($inputFile)) {
                unlink($inputFile);
            }
            if (file_exists($outputFile)) {
                unlink($outputFile);
            }
        }
    }

    private function createPlaceholderScript(string $scriptPath, string $scriptType): void
    {
        $dir = dirname($scriptPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Create a basic Python script that returns mock data for development
        $placeholderScript = $this->generatePlaceholderScript($scriptType);
        file_put_contents($scriptPath, $placeholderScript);
        chmod($scriptPath, 0755);
        
        Log::info("Created placeholder Python script: {$scriptPath}");
    }

    private function generatePlaceholderScript(string $scriptType): string
    {
        return <<<PYTHON
#!/usr/bin/env python3
"""
Placeholder AI analysis script for {$scriptType}
This is a development placeholder that returns mock data.
Replace with actual AI/ML implementation.
"""

import json
import sys
import random
from datetime import datetime

def main():
    if len(sys.argv) != 3:
        print("Usage: python script.py input.json output.json")
        sys.exit(1)
    
    input_file = sys.argv[1]
    output_file = sys.argv[2]
    
    # Read input
    with open(input_file, 'r') as f:
        input_data = json.load(f)
    
    # Generate mock results based on script type
    results = generate_mock_results(input_data)
    
    # Write output
    with open(output_file, 'w') as f:
        json.dump(results, f, indent=2)

def generate_mock_results(input_data):
    """Generate mock AI analysis results for development."""
    
    script_type = "{$scriptType}"
    
    base_result = {
        "analysis_type": script_type,
        "video_id": input_data.get("video_id"),
        "timestamp": datetime.now().isoformat(),
        "confidence_score": round(random.uniform(0.6, 0.95), 3),
        "processing_time": round(random.uniform(1.0, 5.0), 2)
    }
    
    if script_type == "comprehensive_analysis":
        return {
            **base_result,
            "players_detected": generate_mock_players(),
            "court_detected": generate_mock_court(),
            "actions_recognized": generate_mock_actions(),
            "shots_analyzed": generate_mock_shots(),
            "overall_confidence": round(random.uniform(0.7, 0.9), 3)
        }
    
    elif script_type == "player_detection":
        return {
            **base_result,
            "players": generate_mock_players()
        }
    
    elif script_type == "court_detection":
        return {
            **base_result,
            "court": generate_mock_court()
        }
        
    elif script_type == "ball_tracking":
        return {
            **base_result,
            "ball_trajectory": generate_mock_ball_trajectory()
        }
    
    else:
        return base_result

def generate_mock_players():
    return [
        {
            "player_id": i,
            "confidence": round(random.uniform(0.7, 0.95), 3),
            "bounding_box": [
                random.randint(50, 300),
                random.randint(50, 200),
                random.randint(80, 120),
                random.randint(150, 200)
            ],
            "jersey_number": str(random.randint(1, 99)) if random.random() > 0.3 else None,
            "team": "team_" + random.choice(["A", "B"])
        }
        for i in range(random.randint(4, 10))
    ]

def generate_mock_court():
    return {
        "detected": True,
        "confidence": round(random.uniform(0.8, 0.95), 3),
        "boundaries": {
            "left_sideline": 100,
            "right_sideline": 900,
            "baseline_1": 50,
            "baseline_2": 650
        },
        "key_points": {
            "center_circle": [500, 350],
            "free_throw_line_1": [500, 200],
            "free_throw_line_2": [500, 500],
            "three_point_arc_1": [[200, 150], [800, 150]],
            "three_point_arc_2": [[200, 550], [800, 550]]
        }
    }

def generate_mock_actions():
    actions = ["shot", "pass", "dribble", "rebound", "steal", "block"]
    return [
        {
            "action": random.choice(actions),
            "timestamp": round(random.uniform(10, 300), 1),
            "confidence": round(random.uniform(0.6, 0.9), 3),
            "player_id": random.randint(1, 10),
            "location": [random.randint(100, 900), random.randint(50, 650)]
        }
        for _ in range(random.randint(10, 25))
    ]

def generate_mock_shots():
    return [
        {
            "timestamp": round(random.uniform(10, 300), 1),
            "player_id": random.randint(1, 10),
            "shot_location": [random.randint(200, 800), random.randint(100, 600)],
            "shot_type": random.choice(["two_point", "three_point", "free_throw"]),
            "outcome": random.choice(["made", "missed"]),
            "confidence": round(random.uniform(0.7, 0.95), 3),
            "release_angle": round(random.uniform(35, 55), 1),
            "arc_height": round(random.uniform(3, 5), 1)
        }
        for _ in range(random.randint(5, 15))
    ]

def generate_mock_ball_trajectory():
    return [
        {
            "timestamp": t,
            "position": [
                random.randint(200, 800),
                random.randint(100, 600)
            ],
            "confidence": round(random.uniform(0.6, 0.9), 3)
        }
        for t in range(0, random.randint(30, 120), 2)
    ]

if __name__ == "__main__":
    main()
PYTHON;
    }

    private function postProcessResults(array $results, VideoFile $videoFile): array
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

    private function processPlayerDetectionResults(array $results): array
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
        
        $processed['average_confidence'] = count($results) > 0 ? 
            round($confidenceSum / count($results), 3) : 0;
        
        return $processed;
    }

    private function processCourtDetectionResults(array $results): array
    {
        return [
            'court_detected' => $results['detected'] ?? false,
            'confidence' => $results['confidence'] ?? 0,
            'boundaries' => $results['boundaries'] ?? [],
            'key_points' => $results['key_points'] ?? [],
        ];
    }

    private function processBallTrackingResults(array $results): array
    {
        return [
            'trajectory_points' => count($results),
            'tracking_confidence' => array_sum(array_column($results, 'confidence')) / max(1, count($results)),
            'trajectory' => $results,
        ];
    }

    private function processActionRecognitionResults(array $results): array
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

    private function processShotAnalysisResults(array $results): array
    {
        $madeShots = array_filter($results, fn($shot) => ($shot['outcome'] ?? '') === 'made');
        $shotsByType = [];
        
        foreach ($results as $shot) {
            $type = $shot['shot_type'] ?? 'unknown';
            $shotsByType[$type] = ($shotsByType[$type] ?? 0) + 1;
        }
        
        return [
            'total_shots' => count($results),
            'made_shots' => count($madeShots),
            'shooting_percentage' => count($results) > 0 ? 
                round(count($madeShots) / count($results) * 100, 1) : 0,
            'shots_by_type' => $shotsByType,
            'shots' => $results,
        ];
    }

    private function generateAutoAnnotations(array $results, VideoFile $videoFile): array
    {
        $annotations = [];
        
        // Generate annotations for detected actions
        if (isset($results['actions'])) {
            foreach ($results['actions']['actions'] as $action) {
                if (($action['confidence'] ?? 0) >= 0.8) {
                    $annotations[] = [
                        'video_file_id' => $videoFile->id,
                        'created_by_user_id' => 1, // System user
                        'start_time' => $action['timestamp'] ?? 0,
                        'end_time' => ($action['timestamp'] ?? 0) + 3, // 3-second annotation
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
                if (($shot['confidence'] ?? 0) >= 0.8) {
                    $annotations[] = [
                        'video_file_id' => $videoFile->id,
                        'created_by_user_id' => 1, // System user
                        'start_time' => $shot['timestamp'] ?? 0,
                        'end_time' => ($shot['timestamp'] ?? 0) + 5, // 5-second annotation for shots
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

    private function updateVideoWithResults(VideoFile $videoFile, array $results, array $annotations): void
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

    private function cleanupTemporaryFiles(array $extractedFrames): void
    {
        foreach ($extractedFrames as $frame) {
            if (file_exists($frame['path'])) {
                unlink($frame['path']);
            }
        }
        
        // Remove empty directories
        $frameDir = dirname($extractedFrames[0]['path'] ?? '');
        if (is_dir($frameDir) && count(scandir($frameDir)) === 2) {
            rmdir($frameDir);
        }
    }

    private function generateAnalysisSummary(array $results): array
    {
        return [
            'players_detected' => $results['players']['total_players_detected'] ?? 0,
            'actions_recognized' => $results['actions']['total_actions'] ?? 0,
            'shots_analyzed' => $results['shots']['total_shots'] ?? 0,
            'court_detected' => $results['court']['court_detected'] ?? false,
            'overall_confidence' => $results['overall_confidence'] ?? 0,
        ];
    }

    private function summarizeDetectedElements(array $results): array
    {
        $elements = [];
        
        if (isset($results['players'])) {
            $elements['players'] = $results['players']['total_players_detected'] ?? 0;
        }
        
        if (isset($results['court']) && $results['court']['court_detected']) {
            $elements['court'] = 'detected';
        }
        
        if (isset($results['actions'])) {
            $elements['actions'] = $results['actions']['action_counts'] ?? [];
        }
        
        return $elements;
    }

    private function generateBasketballInsights(array $results): array
    {
        $insights = [];
        
        // Player insights
        if (isset($results['players'])) {
            $playerCount = $results['players']['total_players_detected'] ?? 0;
            if ($playerCount >= 8) {
                $insights[] = 'Full team scrimmage detected';
            } elseif ($playerCount >= 4) {
                $insights[] = 'Small group training detected';
            }
        }
        
        // Shot insights
        if (isset($results['shots'])) {
            $shootingPct = $results['shots']['shooting_percentage'] ?? 0;
            if ($shootingPct > 50) {
                $insights[] = 'Good shooting performance';
            } elseif ($shootingPct < 30) {
                $insights[] = 'Low shooting percentage - may need practice';
            }
        }
        
        return $insights;
    }

    private function generateRecommendations(array $results, VideoFile $videoFile): array
    {
        $recommendations = [];
        
        $confidence = $results['overall_confidence'] ?? 0;
        
        if ($confidence < 0.7) {
            $recommendations[] = 'Consider manual review due to low AI confidence';
        }
        
        if (isset($results['shots']) && $results['shots']['total_shots'] > 5) {
            $recommendations[] = 'Create shot chart analysis session';
        }
        
        if (isset($results['actions']) && $results['actions']['total_actions'] > 10) {
            $recommendations[] = 'Generate training recommendations based on detected actions';
        }
        
        return $recommendations;
    }

    private function mapActionToPlayType(string $action): string
    {
        $mapping = [
            'shot' => 'shot',
            'pass' => 'pass',
            'dribble' => 'dribble',
            'rebound' => 'rebound',
            'steal' => 'defense',
            'block' => 'defense',
        ];
        
        return $mapping[$action] ?? 'offense';
    }

    private function calculatePointsForShot(array $shot): int
    {
        if (($shot['outcome'] ?? '') !== 'made') {
            return 0;
        }
        
        switch ($shot['shot_type'] ?? '') {
            case 'three_point':
                return 3;
            case 'free_throw':
                return 1;
            case 'two_point':
            default:
                return 2;
        }
    }
}