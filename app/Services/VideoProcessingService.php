<?php

namespace App\Services;

use App\Models\VideoFile;
use App\Jobs\ProcessVideoUpload;
use App\Jobs\GenerateThumbnails;
use App\Jobs\OptimizeVideoQuality;
use App\Jobs\ExtractVideoMetadata;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class VideoProcessingService
{
    private array $supportedInputFormats = [
        'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', 'mpeg', 'mpg'
    ];

    private array $supportedOutputFormats = [
        'mp4', 'webm', 'mov'
    ];

    private array $qualityPresets = [
        'low' => [
            'width' => 640,
            'height' => 360,
            'bitrate' => '500k',
            'audio_bitrate' => '64k',
        ],
        'medium' => [
            'width' => 1280,
            'height' => 720,
            'bitrate' => '2000k',
            'audio_bitrate' => '128k',
        ],
        'high' => [
            'width' => 1920,
            'height' => 1080,
            'bitrate' => '5000k',
            'audio_bitrate' => '192k',
        ],
        'ultra' => [
            'width' => 3840,
            'height' => 2160,
            'bitrate' => '15000k',
            'audio_bitrate' => '256k',
        ],
    ];

    public function __construct()
    {
        $this->ensureDirectoriesExist();
    }

    /**
     * Process uploaded video file
     */
    public function processVideo(VideoFile $videoFile): bool
    {
        try {
            Log::info("Starting video processing for video {$videoFile->id}");
            
            $videoFile->update([
                'processing_status' => 'processing',
                'processing_started_at' => now(),
            ]);

            // Extract metadata first
            $metadata = $this->extractVideoMetadata($videoFile->file_path);
            $videoFile->update([
                'processing_metadata' => $metadata,
                'width' => $metadata['width'] ?? null,
                'height' => $metadata['height'] ?? null,
                'duration' => $metadata['duration'] ?? null,
                'frame_rate' => $metadata['frame_rate'] ?? null,
                'codec' => $metadata['codec'] ?? null,
                'bitrate' => $metadata['bitrate'] ?? null,
            ]);

            // Generate optimized version
            $optimizedPath = $this->optimizeVideo($videoFile);
            
            // Generate thumbnails
            $thumbnailPaths = $this->generateThumbnails($videoFile);
            
            // Update video file record
            $videoFile->update([
                'processed_path' => $optimizedPath,
                'thumbnail_path' => $thumbnailPaths['default'] ?? null,
                'processing_status' => 'completed',
                'processing_completed_at' => now(),
                'quality_rating' => $this->assessVideoQuality($metadata),
            ]);

            Log::info("Video processing completed for video {$videoFile->id}");
            return true;

        } catch (\Exception $e) {
            Log::error("Video processing failed for video {$videoFile->id}: " . $e->getMessage());
            
            $videoFile->update([
                'processing_status' => 'failed',
                'processing_error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Extract comprehensive video metadata using FFmpeg
     */
    public function extractVideoMetadata(string $filePath): array
    {
        $absolutePath = Storage::path($filePath);
        
        if (!file_exists($absolutePath)) {
            throw new \Exception("Video file not found: {$absolutePath}");
        }

        $command = [
            'ffprobe',
            '-v', 'quiet',
            '-print_format', 'json',
            '-show_format',
            '-show_streams',
            $absolutePath
        ];

        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = json_decode($process->getOutput(), true);
        
        return $this->parseFFprobeOutput($output);
    }

    /**
     * Parse FFprobe output into structured metadata
     */
    private function parseFFprobeOutput(array $output): array
    {
        $metadata = [
            'format_info' => $output['format'] ?? [],
            'streams' => $output['streams'] ?? [],
            'video_streams' => [],
            'audio_streams' => [],
        ];

        // Extract video stream information
        foreach ($output['streams'] as $stream) {
            if ($stream['codec_type'] === 'video') {
                $metadata['video_streams'][] = $stream;
                
                // Primary video stream data
                if (empty($metadata['width'])) {
                    $metadata['width'] = $stream['width'] ?? null;
                    $metadata['height'] = $stream['height'] ?? null;
                    $metadata['codec'] = $stream['codec_name'] ?? null;
                    $metadata['frame_rate'] = $this->parseFrameRate($stream['r_frame_rate'] ?? '0/0');
                    $metadata['bitrate'] = $stream['bit_rate'] ?? null;
                    $metadata['pixel_format'] = $stream['pix_fmt'] ?? null;
                    $metadata['aspect_ratio'] = $stream['display_aspect_ratio'] ?? null;
                }
            } elseif ($stream['codec_type'] === 'audio') {
                $metadata['audio_streams'][] = $stream;
            }
        }

        // Format-level information
        $format = $output['format'] ?? [];
        $metadata['duration'] = isset($format['duration']) ? (int) round($format['duration']) : null;
        $metadata['file_size'] = $format['size'] ?? null;
        $metadata['format_name'] = $format['format_name'] ?? null;
        $metadata['container'] = $this->extractContainer($format['format_name'] ?? '');

        // Calculate additional metrics
        $metadata['has_video'] = !empty($metadata['video_streams']);
        $metadata['has_audio'] = !empty($metadata['audio_streams']);
        $metadata['total_streams'] = count($output['streams']);
        $metadata['estimated_frames'] = $this->calculateEstimatedFrames($metadata);

        return $metadata;
    }

    /**
     * Optimize video for web streaming
     */
    public function optimizeVideo(VideoFile $videoFile, string $quality = 'medium'): string
    {
        $inputPath = Storage::path($videoFile->file_path);
        $outputFilename = 'processed/' . Str::uuid() . '_optimized.mp4';
        $outputPath = Storage::path($outputFilename);

        $preset = $this->qualityPresets[$quality];
        
        // Ensure output directory exists
        $this->ensureDirectoryExists(dirname($outputPath));

        $command = [
            'ffmpeg',
            '-i', $inputPath,
            '-c:v', 'libx264',
            '-c:a', 'aac',
            '-vf', "scale={$preset['width']}:{$preset['height']}:force_original_aspect_ratio=decrease,pad={$preset['width']}:{$preset['height']}:(ow-iw)/2:(oh-ih)/2",
            '-b:v', $preset['bitrate'],
            '-b:a', $preset['audio_bitrate'],
            '-preset', 'medium',
            '-movflags', '+faststart', // Enable progressive download
            '-y', // Overwrite output file
            $outputPath
        ];

        $process = new Process($command);
        $process->setTimeout(3600); // 1 hour timeout
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $outputFilename;
    }

    /**
     * Generate multiple thumbnail images at different timestamps
     */
    public function generateThumbnails(VideoFile $videoFile, array $timestamps = null): array
    {
        $inputPath = Storage::path($videoFile->file_path);
        $thumbnails = [];
        
        // Default timestamps (10%, 25%, 50%, 75%, 90% of video)
        if ($timestamps === null) {
            $duration = $videoFile->duration ?? 60;
            $timestamps = [
                'default' => $duration * 0.1,
                'quarter' => $duration * 0.25,
                'half' => $duration * 0.5,
                'three_quarter' => $duration * 0.75,
                'near_end' => $duration * 0.9,
            ];
        }

        foreach ($timestamps as $label => $timestamp) {
            $thumbnailPath = $this->generateThumbnailAtTime($inputPath, $timestamp, $label);
            if ($thumbnailPath) {
                $thumbnails[$label] = $thumbnailPath;
            }
        }

        return $thumbnails;
    }

    /**
     * Generate single thumbnail at specific timestamp
     */
    private function generateThumbnailAtTime(string $inputPath, float $timestamp, string $label): ?string
    {
        $outputFilename = 'thumbnails/' . Str::uuid() . "_{$label}.jpg";
        $outputPath = Storage::path($outputFilename);
        
        $this->ensureDirectoryExists(dirname($outputPath));

        $command = [
            'ffmpeg',
            '-i', $inputPath,
            '-ss', (string) $timestamp,
            '-vframes', '1',
            '-vf', 'scale=640:360:force_original_aspect_ratio=decrease,pad=640:360:(ow-iw)/2:(oh-ih)/2',
            '-q:v', '2', // High quality
            '-y',
            $outputPath
        ];

        $process = new Process($command);
        $process->setTimeout(60);
        $process->run();

        if ($process->isSuccessful() && file_exists($outputPath)) {
            return $outputFilename;
        }

        Log::warning("Failed to generate thumbnail at {$timestamp}s for {$inputPath}");
        return null;
    }

    /**
     * Create video preview/trailer (first 30 seconds)
     */
    public function createPreview(VideoFile $videoFile, int $duration = 30): string
    {
        $inputPath = Storage::path($videoFile->file_path);
        $outputFilename = 'previews/' . Str::uuid() . '_preview.mp4';
        $outputPath = Storage::path($outputFilename);
        
        $this->ensureDirectoryExists(dirname($outputPath));

        $command = [
            'ffmpeg',
            '-i', $inputPath,
            '-t', (string) $duration,
            '-c:v', 'libx264',
            '-c:a', 'aac',
            '-vf', 'scale=1280:720:force_original_aspect_ratio=decrease',
            '-b:v', '1000k',
            '-b:a', '128k',
            '-preset', 'fast',
            '-y',
            $outputPath
        ];

        $process = new Process($command);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $outputFilename;
    }

    /**
     * Extract audio track from video
     */
    public function extractAudio(VideoFile $videoFile, string $format = 'mp3'): string
    {
        $inputPath = Storage::path($videoFile->file_path);
        $outputFilename = 'audio/' . Str::uuid() . ".{$format}";
        $outputPath = Storage::path($outputFilename);
        
        $this->ensureDirectoryExists(dirname($outputPath));

        $command = [
            'ffmpeg',
            '-i', $inputPath,
            '-vn', // No video
            '-acodec', $format === 'mp3' ? 'libmp3lame' : 'aac',
            '-ab', '192k',
            '-y',
            $outputPath
        ];

        $process = new Process($command);
        $process->setTimeout(600);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $outputFilename;
    }

    /**
     * Convert video to different format
     */
    public function convertFormat(VideoFile $videoFile, string $targetFormat): string
    {
        if (!in_array($targetFormat, $this->supportedOutputFormats)) {
            throw new \InvalidArgumentException("Unsupported output format: {$targetFormat}");
        }

        $inputPath = Storage::path($videoFile->file_path);
        $outputFilename = 'conversions/' . Str::uuid() . ".{$targetFormat}";
        $outputPath = Storage::path($outputFilename);
        
        $this->ensureDirectoryExists(dirname($outputPath));

        $codecOptions = $this->getCodecOptions($targetFormat);

        $command = [
            'ffmpeg',
            '-i', $inputPath,
            ...$codecOptions,
            '-y',
            $outputPath
        ];

        $process = new Process($command);
        $process->setTimeout(3600);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $outputFilename;
    }

    /**
     * Trim video to specific time range
     */
    public function trimVideo(VideoFile $videoFile, float $startTime, float $endTime): string
    {
        $inputPath = Storage::path($videoFile->file_path);
        $outputFilename = 'trimmed/' . Str::uuid() . '_trimmed.mp4';
        $outputPath = Storage::path($outputFilename);
        
        $this->ensureDirectoryExists(dirname($outputPath));

        $duration = $endTime - $startTime;

        $command = [
            'ffmpeg',
            '-i', $inputPath,
            '-ss', (string) $startTime,
            '-t', (string) $duration,
            '-c', 'copy', // Stream copy for fast processing
            '-avoid_negative_ts', 'make_zero',
            '-y',
            $outputPath
        ];

        $process = new Process($command);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $outputFilename;
    }

    /**
     * Concatenate multiple video segments
     */
    public function concatenateVideos(array $videoPaths): string
    {
        $outputFilename = 'concatenated/' . Str::uuid() . '_combined.mp4';
        $outputPath = Storage::path($outputFilename);
        $fileListPath = Storage::path('temp/' . Str::uuid() . '_filelist.txt');
        
        $this->ensureDirectoryExists(dirname($outputPath));
        $this->ensureDirectoryExists(dirname($fileListPath));

        // Create file list for FFmpeg
        $fileList = '';
        foreach ($videoPaths as $videoPath) {
            $absolutePath = Storage::path($videoPath);
            $fileList .= "file '{$absolutePath}'\n";
        }
        file_put_contents($fileListPath, $fileList);

        $command = [
            'ffmpeg',
            '-f', 'concat',
            '-safe', '0',
            '-i', $fileListPath,
            '-c', 'copy',
            '-y',
            $outputPath
        ];

        $process = new Process($command);
        $process->setTimeout(1800);
        $process->run();

        // Clean up temp file
        unlink($fileListPath);

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $outputFilename;
    }

    /**
     * Queue video processing job
     */
    public function queueProcessing(VideoFile $videoFile): void
    {
        $videoFile->update(['processing_status' => 'queued']);
        
        ProcessVideoUpload::dispatch($videoFile);
    }

    /**
     * Queue thumbnail generation job
     */
    public function queueThumbnailGeneration(VideoFile $videoFile): void
    {
        GenerateThumbnails::dispatch($videoFile);
    }

    /**
     * Queue video optimization job
     */
    public function queueOptimization(VideoFile $videoFile, string $quality = 'medium'): void
    {
        OptimizeVideoQuality::dispatch($videoFile, $quality);
    }

    /**
     * Assess video quality based on metadata
     */
    private function assessVideoQuality(array $metadata): string
    {
        $width = $metadata['width'] ?? 0;
        $bitrate = $metadata['bitrate'] ?? 0;
        
        if ($width >= 1920 && $bitrate >= 3000000) {
            return 'excellent';
        } elseif ($width >= 1280 && $bitrate >= 1500000) {
            return 'high';
        } elseif ($width >= 720 && $bitrate >= 500000) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Parse frame rate from FFprobe format
     */
    private function parseFrameRate(string $frameRate): ?float
    {
        if (strpos($frameRate, '/') !== false) {
            [$numerator, $denominator] = explode('/', $frameRate);
            if ($denominator > 0) {
                return round($numerator / $denominator, 2);
            }
        }
        
        return null;
    }

    /**
     * Extract container format from format name
     */
    private function extractContainer(string $formatName): string
    {
        $formats = explode(',', $formatName);
        return $formats[0] ?? 'unknown';
    }

    /**
     * Calculate estimated frame count
     */
    private function calculateEstimatedFrames(array $metadata): ?int
    {
        if (isset($metadata['duration'], $metadata['frame_rate'])) {
            return (int) round($metadata['duration'] * $metadata['frame_rate']);
        }
        
        return null;
    }

    /**
     * Get codec options for different formats
     */
    private function getCodecOptions(string $format): array
    {
        switch ($format) {
            case 'mp4':
                return ['-c:v', 'libx264', '-c:a', 'aac', '-preset', 'medium'];
            case 'webm':
                return ['-c:v', 'libvpx-vp9', '-c:a', 'libvorbis'];
            case 'mov':
                return ['-c:v', 'libx264', '-c:a', 'aac', '-preset', 'medium'];
            default:
                return ['-c:v', 'libx264', '-c:a', 'aac'];
        }
    }

    /**
     * Ensure required directories exist
     */
    private function ensureDirectoriesExist(): void
    {
        $directories = [
            'processed', 'thumbnails', 'previews', 'audio', 
            'conversions', 'trimmed', 'concatenated', 'temp'
        ];

        foreach ($directories as $directory) {
            $this->ensureDirectoryExists(Storage::path($directory));
        }
    }

    /**
     * Ensure single directory exists
     */
    private function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Get file format information
     */
    public function getFileInfo(string $filePath): array
    {
        $absolutePath = Storage::path($filePath);
        
        if (!file_exists($absolutePath)) {
            throw new \Exception("File not found: {$absolutePath}");
        }

        return [
            'size' => filesize($absolutePath),
            'mime_type' => mime_content_type($absolutePath),
            'extension' => pathinfo($absolutePath, PATHINFO_EXTENSION),
            'is_supported' => $this->isFormatSupported(pathinfo($absolutePath, PATHINFO_EXTENSION)),
        ];
    }

    /**
     * Check if file format is supported
     */
    public function isFormatSupported(string $extension): bool
    {
        return in_array(strtolower($extension), $this->supportedInputFormats);
    }

    /**
     * Get processing progress for video
     */
    public function getProcessingProgress(VideoFile $videoFile): array
    {
        return [
            'status' => $videoFile->processing_status,
            'started_at' => $videoFile->processing_started_at,
            'completed_at' => $videoFile->processing_completed_at,
            'error' => $videoFile->processing_error,
            'percentage' => $this->calculateProgressPercentage($videoFile),
        ];
    }

    /**
     * Calculate processing progress percentage
     */
    private function calculateProgressPercentage(VideoFile $videoFile): int
    {
        switch ($videoFile->processing_status) {
            case 'uploaded':
                return 0;
            case 'queued':
                return 10;
            case 'processing':
                return 50;
            case 'completed':
                return 100;
            case 'failed':
                return 0;
            default:
                return 0;
        }
    }

    /**
     * Clean up temporary and old processed files
     */
    public function cleanup(): void
    {
        // Clean up temp files older than 1 hour
        $tempPath = Storage::path('temp');
        $this->cleanupOldFiles($tempPath, 3600);

        // Clean up failed processing files older than 24 hours
        $this->cleanupFailedProcessingFiles();
    }

    /**
     * Clean up old files in directory
     */
    private function cleanupOldFiles(string $directory, int $maxAge): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $files = glob($directory . '/*');
        $now = time();

        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) > $maxAge) {
                unlink($file);
            }
        }
    }

    /**
     * Clean up files from failed processing attempts
     */
    private function cleanupFailedProcessingFiles(): void
    {
        $failedVideos = VideoFile::where('processing_status', 'failed')
                                ->where('processing_started_at', '<', now()->subHours(24))
                                ->get();

        foreach ($failedVideos as $video) {
            if ($video->processed_path && Storage::exists($video->processed_path)) {
                Storage::delete($video->processed_path);
                $video->update(['processed_path' => null]);
            }
        }
    }
}