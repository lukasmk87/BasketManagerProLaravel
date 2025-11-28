<?php

namespace App\Services\ML\VideoAnalysis;

use App\Models\VideoFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

/**
 * Handles video frame extraction for AI analysis.
 *
 * Extracted from AIVideoAnalysisService during REFACTOR-001.
 * Includes SEC-007 security validation for path traversal prevention.
 */
class VideoFrameExtractionService
{
    private string $tempDirectory;
    private int $maxTimestamp;
    private array $extractionStrategies;

    public function __construct()
    {
        $this->tempDirectory = config('video_analysis.execution.temp_directory', 'temp/ai_analysis');
        $this->maxTimestamp = config('video_analysis.execution.max_timestamp', 86400);
        $this->extractionStrategies = config('video_analysis.extraction_strategies', []);
    }

    /**
     * Extract frames from video for analysis.
     */
    public function extractFramesForAnalysis(VideoFile $videoFile, string $analysisType): array
    {
        $videoPath = Storage::path($videoFile->file_path);
        $outputDir = storage_path("app/{$this->tempDirectory}/{$videoFile->id}/frames/");

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

    /**
     * Get frame extraction strategy for analysis type.
     */
    public function getFrameExtractionStrategy(string $analysisType, int $duration): array
    {
        $strategy = $this->extractionStrategies[$analysisType]
            ?? $this->extractionStrategies['basic_basketball_analysis']
            ?? ['interval' => 60, 'max_frames' => 50];

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
    public function extractSingleFrame(string $videoPath, int $timestamp, string $outputPath): bool
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

        // SEC-007: Validate timestamp is within safe range (0 to max timestamp)
        $timestamp = max(0, min($this->maxTimestamp, $timestamp));

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
            $outputPath,
        ];

        $result = Process::run($command);

        return $result->successful() && file_exists($outputPath);
    }

    /**
     * Cleanup temporary files after analysis.
     */
    public function cleanupTemporaryFiles(array $extractedFrames): void
    {
        if (empty($extractedFrames)) {
            return;
        }

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

    /**
     * Get output directory for a video's frames.
     */
    public function getFrameOutputDirectory(VideoFile $videoFile): string
    {
        return storage_path("app/{$this->tempDirectory}/{$videoFile->id}/frames/");
    }
}
