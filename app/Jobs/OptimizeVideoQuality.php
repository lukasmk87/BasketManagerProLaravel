<?php

namespace App\Jobs;

use App\Models\VideoFile;
use App\Services\VideoProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Exception;

class OptimizeVideoQuality implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected VideoFile $videoFile;
    protected string $targetQuality;
    protected bool $generateMultipleQualities;
    protected array $customSettings;
    
    public int $timeout = 7200; // 2 hours for large files
    public int $tries = 2;
    public array $backoff = [300, 1800]; // 5min, 30min

    /**
     * Quality presets for different use cases.
     */
    private array $qualityPresets = [
        'mobile' => [
            'width' => 480,
            'height' => 270,
            'bitrate' => '300k',
            'audio_bitrate' => '64k',
            'fps' => 24,
            'profile' => 'baseline',
            'level' => '3.0',
        ],
        'low' => [
            'width' => 640,
            'height' => 360,
            'bitrate' => '500k',
            'audio_bitrate' => '64k',
            'fps' => 25,
            'profile' => 'main',
            'level' => '3.1',
        ],
        'medium' => [
            'width' => 1280,
            'height' => 720,
            'bitrate' => '2000k',
            'audio_bitrate' => '128k',
            'fps' => 30,
            'profile' => 'main',
            'level' => '4.0',
        ],
        'high' => [
            'width' => 1920,
            'height' => 1080,
            'bitrate' => '5000k',
            'audio_bitrate' => '192k',
            'fps' => 30,
            'profile' => 'high',
            'level' => '4.2',
        ],
        'ultra' => [
            'width' => 3840,
            'height' => 2160,
            'bitrate' => '15000k',
            'audio_bitrate' => '256k',
            'fps' => 60,
            'profile' => 'high',
            'level' => '5.2',
        ]
    ];

    /**
     * Basketball-specific optimization settings.
     */
    private array $basketballOptimizations = [
        'motion_settings' => [
            'me_method' => 'hex',
            'me_range' => '32',
            'subq' => '8',
            'trellis' => '2',
        ],
        'denoising' => [
            'nr' => '25',
            'tune' => 'film', // Better for sports content
        ],
        'color_settings' => [
            'colorspace' => 'bt709',
            'color_primaries' => 'bt709',
            'color_trc' => 'bt709',
        ]
    ];

    /**
     * Create a new job instance.
     */
    public function __construct(VideoFile $videoFile, string $targetQuality = 'medium', bool $generateMultipleQualities = false, array $customSettings = [])
    {
        $this->videoFile = $videoFile;
        $this->targetQuality = $targetQuality;
        $this->generateMultipleQualities = $generateMultipleQualities;
        $this->customSettings = $customSettings;
        
        $this->onQueue('optimization');
    }

    /**
     * Execute the job.
     */
    public function handle(VideoProcessingService $videoProcessingService): void
    {
        Log::info("Starting video optimization for video {$this->videoFile->id} with quality: {$this->targetQuality}");
        
        try {
            // Validate video is ready for optimization
            $this->validateVideoForOptimization();
            
            // Detect basketball-specific content for optimization
            $basketballOptimizations = $this->detectBasketballOptimizations();
            
            // Generate primary optimized version
            $primaryOptimized = $this->optimizeVideo($this->targetQuality, $basketballOptimizations);
            
            // Generate additional quality versions if requested
            $additionalQualities = [];
            if ($this->generateMultipleQualities) {
                $additionalQualities = $this->generateMultipleQualityVersions($basketballOptimizations);
            }
            
            // Generate streaming-optimized version (HLS/DASH preparation)
            $streamingVersion = $this->prepareForStreaming($primaryOptimized, $basketballOptimizations);
            
            // Update video file with optimization results
            $this->updateVideoWithOptimizedVersions($primaryOptimized, $additionalQualities, $streamingVersion);
            
            // Generate quality report
            $qualityReport = $this->generateQualityReport($primaryOptimized, $additionalQualities);
            
            // Clean up temporary files
            $this->cleanupTemporaryFiles();
            
            Log::info("Video optimization completed for video {$this->videoFile->id}", [
                'primary_quality' => $this->targetQuality,
                'additional_qualities' => count($additionalQualities),
                'file_size_reduction' => $qualityReport['size_reduction'] ?? 'unknown',
            ]);
            
        } catch (Exception $e) {
            Log::error("Video optimization failed for video {$this->videoFile->id}: " . $e->getMessage(), [
                'video_id' => $this->videoFile->id,
                'target_quality' => $this->targetQuality,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error("OptimizeVideoQuality job failed for video {$this->videoFile->id}", [
            'video_id' => $this->videoFile->id,
            'target_quality' => $this->targetQuality,
            'error' => $exception->getMessage(),
        ]);
        
        // Clean up any partial optimization files
        $this->cleanupFailedOptimization();
    }

    /**
     * Validate video is ready for optimization.
     */
    private function validateVideoForOptimization(): void
    {
        // Check if source file exists
        if (!Storage::exists($this->videoFile->file_path)) {
            throw new Exception("Source video file not found: {$this->videoFile->file_path}");
        }
        
        // Ensure we have metadata
        if (!$this->videoFile->width || !$this->videoFile->height || !$this->videoFile->duration) {
            throw new Exception("Video metadata required for optimization");
        }
        
        // Check if target quality is valid
        if (!isset($this->qualityPresets[$this->targetQuality])) {
            throw new Exception("Invalid target quality: {$this->targetQuality}");
        }
        
        // Check available disk space
        $availableSpace = disk_free_space(Storage::path(''));
        $estimatedOutputSize = $this->estimateOutputSize();
        
        if ($availableSpace < ($estimatedOutputSize * 3)) { // 3x for safety margin
            throw new Exception("Insufficient disk space for optimization");
        }
        
        Log::info("Video validation passed for optimization {$this->videoFile->id}");
    }

    /**
     * Detect basketball-specific optimizations needed.
     */
    private function detectBasketballOptimizations(): array
    {
        $optimizations = [];
        
        // Check if this is basketball content
        if ($this->isBasketballContent()) {
            $optimizations = array_merge($optimizations, $this->basketballOptimizations);
            
            // Court detection optimizations
            if ($this->hasCourtContent()) {
                $optimizations['court_optimizations'] = [
                    'noise_reduction' => 'strong', // Courts can be noisy
                    'color_enhancement' => 'sports', // Enhance court colors
                    'edge_enhancement' => 'mild', // Better player definition
                ];
            }
            
            // Fast action optimizations
            if ($this->hasFastAction()) {
                $optimizations['motion_optimizations'] = [
                    'motion_estimation' => 'advanced',
                    'reference_frames' => '4',
                    'b_frames' => '2',
                    'keyframe_interval' => '60', // More frequent keyframes
                ];
            }
            
            // Player tracking optimizations
            if ($this->hasPlayerTracking()) {
                $optimizations['tracking_optimizations'] = [
                    'temporal_denoise' => 'light',
                    'spatial_denoise' => 'medium',
                    'motion_blur_reduction' => 'enabled',
                ];
            }
        }
        
        Log::info("Basketball optimizations detected for video {$this->videoFile->id}", $optimizations);
        return $optimizations;
    }

    /**
     * Optimize video with specific quality settings.
     */
    private function optimizeVideo(string $quality, array $basketballOptimizations = []): array
    {
        $preset = $this->qualityPresets[$quality];
        $inputPath = Storage::path($this->videoFile->file_path);
        
        $outputFilename = "optimized/{$this->videoFile->id}/" . Str::uuid() . "_{$quality}.mp4";
        $outputPath = Storage::path($outputFilename);
        
        // Ensure directory exists
        $this->ensureDirectoryExists(dirname($outputPath));
        
        // Build FFmpeg command with optimizations
        $command = $this->buildOptimizationCommand($inputPath, $outputPath, $preset, $basketballOptimizations);
        
        // Execute optimization
        $process = new Process($command);
        $process->setTimeout($this->timeout);
        
        // Add progress callback for large files
        $this->setupProgressTracking($process);
        
        $process->run();
        
        if (!$process->isSuccessful()) {
            throw new Exception("Video optimization failed: " . $process->getErrorOutput());
        }
        
        // Validate output file
        if (!file_exists($outputPath) || filesize($outputPath) === 0) {
            throw new Exception("Optimization produced invalid output file");
        }
        
        // Generate file information
        $fileInfo = $this->getOptimizedFileInfo($outputPath, $quality);
        
        return [
            'quality' => $quality,
            'path' => $outputFilename,
            'file_info' => $fileInfo,
        ];
    }

    /**
     * Build FFmpeg optimization command.
     */
    private function buildOptimizationCommand(string $inputPath, string $outputPath, array $preset, array $basketballOpts = []): array
    {
        $command = [
            'ffmpeg',
            '-i', $inputPath,
            
            // Video codec settings
            '-c:v', 'libx264',
            '-preset', 'medium',
            '-crf', '23',
            
            // Resolution and frame rate
            '-vf', $this->buildVideoFilters($preset, $basketballOpts),
            '-r', (string) $preset['fps'],
            
            // Bitrate settings
            '-b:v', $preset['bitrate'],
            '-maxrate', $this->calculateMaxRate($preset['bitrate']),
            '-bufsize', $this->calculateBufSize($preset['bitrate']),
            
            // Audio settings
            '-c:a', 'aac',
            '-b:a', $preset['audio_bitrate'],
            '-ar', '44100',
            
            // H.264 specific settings
            '-profile:v', $preset['profile'],
            '-level:v', $preset['level'],
            '-pix_fmt', 'yuv420p',
            
            // Streaming optimization
            '-movflags', '+faststart',
            '-force_key_frames', 'expr:gte(t,n_forced*2)', // Keyframe every 2 seconds
        ];
        
        // Add basketball-specific optimizations
        if (!empty($basketballOpts)) {
            $command = array_merge($command, $this->buildBasketballOptimizations($basketballOpts));
        }
        
        // Add custom settings
        if (!empty($this->customSettings)) {
            $command = array_merge($command, $this->customSettings);
        }
        
        $command = array_merge($command, ['-y', $outputPath]);
        
        return $command;
    }

    /**
     * Build video filters for optimization.
     */
    private function buildVideoFilters(array $preset, array $basketballOpts = []): string
    {
        $filters = [];
        
        // Scaling filter
        $filters[] = "scale={$preset['width']}:{$preset['height']}:force_original_aspect_ratio=decrease";
        $filters[] = "pad={$preset['width']}:{$preset['height']}:(ow-iw)/2:(oh-ih)/2";
        
        // Basketball-specific filters
        if (isset($basketballOpts['court_optimizations'])) {
            $courtOpts = $basketballOpts['court_optimizations'];
            
            if ($courtOpts['noise_reduction'] === 'strong') {
                $filters[] = 'hqdn3d=4:3:6:4.5';
            }
            
            if ($courtOpts['color_enhancement'] === 'sports') {
                $filters[] = 'eq=contrast=1.1:brightness=0.02:saturation=1.05';
            }
            
            if ($courtOpts['edge_enhancement'] === 'mild') {
                $filters[] = 'unsharp=5:5:0.3:5:5:0.0';
            }
        }
        
        // Motion blur reduction for fast action
        if (isset($basketballOpts['tracking_optimizations']['motion_blur_reduction'])) {
            $filters[] = 'bwdif=1:0:1'; // Deinterlace and reduce motion blur
        }
        
        // Denoising filters
        if (isset($basketballOpts['motion_optimizations'])) {
            $filters[] = 'hqdn3d=2:1:2:3'; // Temporal and spatial denoising
        }
        
        return implode(',', $filters);
    }

    /**
     * Build basketball-specific FFmpeg options.
     */
    private function buildBasketballOptimizations(array $basketballOpts): array
    {
        $options = [];
        
        // Motion estimation settings
        if (isset($basketballOpts['motion_settings'])) {
            $motion = $basketballOpts['motion_settings'];
            $options = array_merge($options, [
                '-me_method', $motion['me_method'],
                '-me_range', $motion['me_range'],
                '-subq', $motion['subq'],
                '-trellis', $motion['trellis'],
            ]);
        }
        
        // Denoising settings
        if (isset($basketballOpts['denoising'])) {
            $denoise = $basketballOpts['denoising'];
            $options = array_merge($options, [
                '-tune', $denoise['tune'],
            ]);
        }
        
        // Color space settings
        if (isset($basketballOpts['color_settings'])) {
            $color = $basketballOpts['color_settings'];
            $options = array_merge($options, [
                '-colorspace', $color['colorspace'],
                '-color_primaries', $color['color_primaries'],
                '-color_trc', $color['color_trc'],
            ]);
        }
        
        // Motion-specific optimizations
        if (isset($basketballOpts['motion_optimizations'])) {
            $motion = $basketballOpts['motion_optimizations'];
            $options = array_merge($options, [
                '-refs', $motion['reference_frames'],
                '-bf', $motion['b_frames'],
                '-g', $motion['keyframe_interval'],
            ]);
        }
        
        return $options;
    }

    /**
     * Generate multiple quality versions.
     */
    private function generateMultipleQualityVersions(array $basketballOptimizations = []): array
    {
        $qualities = ['mobile', 'low', 'medium', 'high'];
        $versions = [];
        
        // Remove the target quality from the list to avoid duplication
        $qualities = array_filter($qualities, fn($q) => $q !== $this->targetQuality);
        
        // Limit based on source video quality
        $sourceWidth = $this->videoFile->width;
        if ($sourceWidth < 1920) {
            $qualities = array_filter($qualities, fn($q) => $q !== 'ultra');
        }
        if ($sourceWidth < 1280) {
            $qualities = array_filter($qualities, fn($q) => $q !== 'high');
        }
        
        foreach ($qualities as $quality) {
            try {
                Log::info("Generating {$quality} quality version for video {$this->videoFile->id}");
                $version = $this->optimizeVideo($quality, $basketballOptimizations);
                $versions[] = $version;
            } catch (Exception $e) {
                Log::warning("Failed to generate {$quality} quality version for video {$this->videoFile->id}: " . $e->getMessage());
            }
        }
        
        return $versions;
    }

    /**
     * Prepare video for streaming (HLS segments).
     */
    private function prepareForStreaming(array $primaryOptimized, array $basketballOptimizations = []): ?array
    {
        try {
            $inputPath = Storage::path($primaryOptimized['path']);
            $outputDir = "streaming/{$this->videoFile->id}/";
            $outputPath = Storage::path($outputDir);
            
            $this->ensureDirectoryExists($outputPath);
            
            // Generate HLS playlist and segments
            $command = [
                'ffmpeg',
                '-i', $inputPath,
                '-c:v', 'libx264',
                '-c:a', 'aac',
                '-hls_time', '6', // 6-second segments
                '-hls_playlist_type', 'vod',
                '-hls_segment_filename', $outputPath . 'segment_%03d.ts',
                '-f', 'hls',
                $outputPath . 'playlist.m3u8'
            ];
            
            $process = new Process($command);
            $process->setTimeout(1800); // 30 minutes
            $process->run();
            
            if ($process->isSuccessful()) {
                return [
                    'type' => 'hls',
                    'playlist' => $outputDir . 'playlist.m3u8',
                    'segments_dir' => $outputDir,
                ];
            }
            
        } catch (Exception $e) {
            Log::warning("Failed to prepare streaming version for video {$this->videoFile->id}: " . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Update video file with optimization results.
     */
    private function updateVideoWithOptimizedVersions(array $primary, array $additional, ?array $streaming): void
    {
        // Update primary optimized path
        $updates = [
            'processed_path' => $primary['path'],
            'quality_rating' => $primary['quality'],
        ];
        
        // Store all versions in custom metadata
        $customMetadata = $this->videoFile->custom_metadata ?? [];
        $customMetadata['optimized_versions'] = [
            'primary' => $primary,
            'additional' => $additional,
            'streaming' => $streaming,
            'optimization_completed_at' => now()->toISOString(),
        ];
        $updates['custom_metadata'] = $customMetadata;
        
        $this->videoFile->update($updates);
        
        Log::info("Updated video {$this->videoFile->id} with optimization results", [
            'primary_quality' => $primary['quality'],
            'additional_versions' => count($additional),
            'streaming_enabled' => $streaming !== null,
        ]);
    }

    /**
     * Generate quality report.
     */
    private function generateQualityReport(array $primary, array $additional): array
    {
        $originalSize = $this->videoFile->file_size;
        $optimizedSize = $primary['file_info']['file_size'] ?? 0;
        
        $report = [
            'original_size' => $originalSize,
            'optimized_size' => $optimizedSize,
            'size_reduction' => $originalSize > 0 ? round((($originalSize - $optimizedSize) / $originalSize) * 100, 2) : 0,
            'quality_versions' => count($additional) + 1,
            'target_quality' => $this->targetQuality,
            'basketball_optimizations_applied' => !empty($this->detectBasketballOptimizations()),
        ];
        
        Log::info("Quality report for video {$this->videoFile->id}", $report);
        return $report;
    }

    /**
     * Check if this is basketball content.
     */
    private function isBasketballContent(): bool
    {
        $basketballVideoTypes = [
            'full_game', 'game_highlights', 'training_session', 'drill_demo',
            'player_analysis', 'tactical_analysis'
        ];
        
        return in_array($this->videoFile->video_type, $basketballVideoTypes) ||
               $this->videoFile->team_id ||
               $this->videoFile->game_id ||
               $this->videoFile->training_session_id;
    }

    /**
     * Check if video has court content.
     */
    private function hasCourtContent(): bool
    {
        return in_array($this->videoFile->video_type, ['full_game', 'game_highlights', 'training_session']);
    }

    /**
     * Check if video has fast action content.
     */
    private function hasFastAction(): bool
    {
        return in_array($this->videoFile->video_type, ['full_game', 'game_highlights', 'drill_demo']);
    }

    /**
     * Check if video needs player tracking optimization.
     */
    private function hasPlayerTracking(): bool
    {
        return in_array($this->videoFile->video_type, ['player_analysis', 'tactical_analysis']);
    }

    /**
     * Estimate output file size.
     */
    private function estimateOutputSize(): int
    {
        $preset = $this->qualityPresets[$this->targetQuality];
        $duration = $this->videoFile->duration ?? 60;
        
        // Rough estimate based on bitrate
        $videoBitrate = (int) str_replace('k', '000', $preset['bitrate']);
        $audioBitrate = (int) str_replace('k', '000', $preset['audio_bitrate']);
        
        return (int) (($videoBitrate + $audioBitrate) * $duration / 8); // bytes
    }

    /**
     * Get optimized file information.
     */
    private function getOptimizedFileInfo(string $filePath, string $quality): array
    {
        return [
            'file_size' => filesize($filePath),
            'quality' => $quality,
            'created_at' => now()->toISOString(),
            'optimization_preset' => $this->qualityPresets[$quality],
        ];
    }

    /**
     * Calculate max rate from bitrate.
     */
    private function calculateMaxRate(string $bitrate): string
    {
        $value = (int) str_replace('k', '', $bitrate);
        return ($value * 1.5) . 'k'; // 1.5x for buffer
    }

    /**
     * Calculate buffer size from bitrate.
     */
    private function calculateBufSize(string $bitrate): string
    {
        $value = (int) str_replace('k', '', $bitrate);
        return ($value * 2) . 'k'; // 2x for buffer
    }

    /**
     * Setup progress tracking for long-running processes.
     */
    private function setupProgressTracking(Process $process): void
    {
        // This could integrate with a progress tracking system
        // For now, just log periodically
    }

    /**
     * Clean up temporary files.
     */
    private function cleanupTemporaryFiles(): void
    {
        // Clean up any temporary files created during optimization
        $tempDir = Storage::path("temp/{$this->videoFile->id}/");
        if (is_dir($tempDir)) {
            $this->removeDirectory($tempDir);
        }
    }

    /**
     * Clean up files from failed optimization.
     */
    private function cleanupFailedOptimization(): void
    {
        $optimizedDir = Storage::path("optimized/{$this->videoFile->id}/");
        if (is_dir($optimizedDir)) {
            $this->removeDirectory($optimizedDir);
        }
        
        $streamingDir = Storage::path("streaming/{$this->videoFile->id}/");
        if (is_dir($streamingDir)) {
            $this->removeDirectory($streamingDir);
        }
    }

    /**
     * Remove directory recursively.
     */
    private function removeDirectory(string $dir): void
    {
        if (is_dir($dir)) {
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                $path = $dir . DIRECTORY_SEPARATOR . $file;
                is_dir($path) ? $this->removeDirectory($path) : unlink($path);
            }
            rmdir($dir);
        }
    }

    /**
     * Ensure directory exists.
     */
    private function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Get unique job identifier.
     */
    public function uniqueId(): string
    {
        return "optimize_{$this->videoFile->id}_{$this->targetQuality}";
    }

    /**
     * Get tags for monitoring.
     */
    public function tags(): array
    {
        return [
            'video_optimization',
            'video_id:' . $this->videoFile->id,
            'quality:' . $this->targetQuality,
            'video_type:' . $this->videoFile->video_type,
        ];
    }
}