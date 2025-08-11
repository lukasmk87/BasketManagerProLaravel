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

class GenerateThumbnails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected VideoFile $videoFile;
    protected array $customTimestamps;
    protected bool $generateBasketballKeyframes;
    
    public int $timeout = 600; // 10 minutes
    public int $tries = 2;
    public array $backoff = [30, 120]; // 30s, 2min

    /**
     * Create a new job instance.
     */
    public function __construct(VideoFile $videoFile, array $customTimestamps = [], bool $generateBasketballKeyframes = true)
    {
        $this->videoFile = $videoFile;
        $this->customTimestamps = $customTimestamps;
        $this->generateBasketballKeyframes = $generateBasketballKeyframes;
        
        $this->onQueue('thumbnails');
    }

    /**
     * Execute the job.
     */
    public function handle(VideoProcessingService $videoProcessingService): void
    {
        Log::info("Starting thumbnail generation for video {$this->videoFile->id}");
        
        try {
            // Validate video is ready for thumbnail generation
            $this->validateVideoForThumbnails();
            
            // Generate standard thumbnails
            $standardThumbnails = $this->generateStandardThumbnails();
            
            // Generate basketball-specific keyframe thumbnails
            $basketballThumbnails = [];
            if ($this->generateBasketballKeyframes && $this->isBasketballVideo()) {
                $basketballThumbnails = $this->generateBasketballKeyframes();
            }
            
            // Generate custom timestamp thumbnails if provided
            $customThumbnails = [];
            if (!empty($this->customTimestamps)) {
                $customThumbnails = $this->generateCustomThumbnails();
            }
            
            // Combine all thumbnails
            $allThumbnails = array_merge($standardThumbnails, $basketballThumbnails, $customThumbnails);
            
            // Update video file with thumbnail information
            $this->updateVideoWithThumbnails($allThumbnails);
            
            // Generate thumbnail sprite for video scrubbing
            $this->generateThumbnailSprite($allThumbnails);
            
            // Update thumbnail count in video file
            $this->videoFile->update(['annotation_count' => count($allThumbnails)]);
            
            Log::info("Thumbnail generation completed for video {$this->videoFile->id}", [
                'thumbnails_generated' => count($allThumbnails),
                'basketball_keyframes' => count($basketballThumbnails),
            ]);
            
        } catch (Exception $e) {
            Log::error("Thumbnail generation failed for video {$this->videoFile->id}: " . $e->getMessage(), [
                'video_id' => $this->videoFile->id,
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
        Log::error("GenerateThumbnails job failed for video {$this->videoFile->id}", [
            'video_id' => $this->videoFile->id,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Validate video is ready for thumbnail generation.
     */
    private function validateVideoForThumbnails(): void
    {
        // Check if source file exists
        if (!Storage::exists($this->videoFile->file_path)) {
            throw new Exception("Source video file not found: {$this->videoFile->file_path}");
        }
        
        // Ensure we have duration information
        if (!$this->videoFile->duration || $this->videoFile->duration <= 0) {
            throw new Exception("Video duration not available or invalid");
        }
        
        // Check if video has been processed
        if ($this->videoFile->processing_status !== 'completed' && !$this->videoFile->duration) {
            throw new Exception("Video must be processed before thumbnail generation");
        }
        
        Log::info("Video validation passed for thumbnail generation {$this->videoFile->id}");
    }

    /**
     * Generate standard thumbnails at predefined intervals.
     */
    private function generateStandardThumbnails(): array
    {
        $duration = $this->videoFile->duration;
        $thumbnails = [];
        
        // Define standard thumbnail timestamps (percentage of video duration)
        $standardTimestamps = [
            'start' => max(1, $duration * 0.02),    // 2% - avoid black frames at start
            'quarter' => $duration * 0.25,          // 25%
            'half' => $duration * 0.5,              // 50%
            'three_quarter' => $duration * 0.75,    // 75%
            'near_end' => min($duration - 5, $duration * 0.95), // 95% or 5s before end
        ];
        
        foreach ($standardTimestamps as $label => $timestamp) {
            try {
                $thumbnailPath = $this->generateThumbnailAtTime($timestamp, $label, 'standard');
                if ($thumbnailPath) {
                    $thumbnails[$label] = [
                        'path' => $thumbnailPath,
                        'timestamp' => $timestamp,
                        'type' => 'standard',
                        'label' => $label,
                    ];
                }
            } catch (Exception $e) {
                Log::warning("Failed to generate standard thumbnail '{$label}' for video {$this->videoFile->id}: " . $e->getMessage());
            }
        }
        
        Log::info("Generated " . count($thumbnails) . " standard thumbnails for video {$this->videoFile->id}");
        return $thumbnails;
    }

    /**
     * Generate basketball-specific keyframe thumbnails.
     */
    private function generateBasketballKeyframes(): array
    {
        $thumbnails = [];
        $duration = $this->videoFile->duration;
        
        // Basketball-specific timestamps based on video type
        $basketballTimestamps = $this->getBasketballSpecificTimestamps($duration);
        
        foreach ($basketballTimestamps as $label => $timestamp) {
            try {
                $thumbnailPath = $this->generateThumbnailAtTime($timestamp, $label, 'basketball');
                if ($thumbnailPath) {
                    $thumbnails[$label] = [
                        'path' => $thumbnailPath,
                        'timestamp' => $timestamp,
                        'type' => 'basketball',
                        'label' => $label,
                    ];
                }
            } catch (Exception $e) {
                Log::warning("Failed to generate basketball keyframe '{$label}' for video {$this->videoFile->id}: " . $e->getMessage());
            }
        }
        
        Log::info("Generated " . count($thumbnails) . " basketball keyframes for video {$this->videoFile->id}");
        return $thumbnails;
    }

    /**
     * Get basketball-specific timestamps based on video type.
     */
    private function getBasketballSpecificTimestamps(int $duration): array
    {
        $timestamps = [];
        
        switch ($this->videoFile->video_type) {
            case 'full_game':
                // For full games, generate thumbnails at quarter intervals
                $quarterDuration = $duration / 4;
                $timestamps = [
                    'q1_start' => max(30, $quarterDuration * 0.1),
                    'q1_mid' => $quarterDuration * 0.5,
                    'q2_start' => $quarterDuration + 30,
                    'halftime' => $quarterDuration * 2,
                    'q3_start' => ($quarterDuration * 2) + 30,
                    'q3_mid' => $quarterDuration * 2.5,
                    'q4_start' => ($quarterDuration * 3) + 30,
                    'q4_final' => max($duration - 60, $quarterDuration * 3.8),
                ];
                break;
                
            case 'game_highlights':
                // For highlights, focus on action moments
                $segmentDuration = $duration / 6;
                $timestamps = [
                    'highlight_1' => $segmentDuration * 0.5,
                    'highlight_2' => $segmentDuration * 1.5,
                    'highlight_3' => $segmentDuration * 2.5,
                    'highlight_4' => $segmentDuration * 3.5,
                    'highlight_5' => $segmentDuration * 4.5,
                    'highlight_6' => $segmentDuration * 5.5,
                ];
                break;
                
            case 'training_session':
                // For training, focus on different drill phases
                $timestamps = [
                    'warmup' => min(300, $duration * 0.15),      // Warmup phase
                    'drill_1' => $duration * 0.3,                // First drill
                    'drill_2' => $duration * 0.5,                // Main drill
                    'scrimmage' => $duration * 0.7,              // Scrimmage/game play
                    'cooldown' => max($duration - 300, $duration * 0.9), // Cooldown
                ];
                break;
                
            case 'drill_demo':
                // For drill demos, capture key instruction moments
                $timestamps = [
                    'setup' => min(10, $duration * 0.1),
                    'demonstration' => $duration * 0.3,
                    'execution' => $duration * 0.6,
                    'variation' => min($duration - 10, $duration * 0.9),
                ];
                break;
                
            case 'player_analysis':
                // For player analysis, capture different skill demonstrations
                $skillSegment = $duration / 5;
                $timestamps = [
                    'skill_1' => $skillSegment * 0.5,
                    'skill_2' => $skillSegment * 1.5,
                    'skill_3' => $skillSegment * 2.5,
                    'skill_4' => $skillSegment * 3.5,
                    'skill_5' => $skillSegment * 4.5,
                ];
                break;
                
            default:
                // Default basketball timestamps
                $timestamps = [
                    'action_1' => $duration * 0.2,
                    'action_2' => $duration * 0.4,
                    'action_3' => $duration * 0.6,
                    'action_4' => $duration * 0.8,
                ];
        }
        
        return $timestamps;
    }

    /**
     * Generate thumbnails at custom timestamps.
     */
    private function generateCustomThumbnails(): array
    {
        $thumbnails = [];
        
        foreach ($this->customTimestamps as $index => $timestamp) {
            $label = "custom_" . ($index + 1);
            
            try {
                $thumbnailPath = $this->generateThumbnailAtTime($timestamp, $label, 'custom');
                if ($thumbnailPath) {
                    $thumbnails[$label] = [
                        'path' => $thumbnailPath,
                        'timestamp' => $timestamp,
                        'type' => 'custom',
                        'label' => $label,
                    ];
                }
            } catch (Exception $e) {
                Log::warning("Failed to generate custom thumbnail at {$timestamp}s for video {$this->videoFile->id}: " . $e->getMessage());
            }
        }
        
        Log::info("Generated " . count($thumbnails) . " custom thumbnails for video {$this->videoFile->id}");
        return $thumbnails;
    }

    /**
     * Generate single thumbnail at specific time.
     */
    private function generateThumbnailAtTime(float $timestamp, string $label, string $type): ?string
    {
        $inputPath = Storage::path($this->videoFile->file_path);
        $outputFilename = "thumbnails/{$this->videoFile->id}/" . Str::uuid() . "_{$label}_{$type}.jpg";
        $outputPath = Storage::path($outputFilename);
        
        // Ensure directory exists
        $this->ensureDirectoryExists(dirname($outputPath));
        
        // Generate different sizes for different use cases
        $sizes = [
            'small' => ['width' => 320, 'height' => 180],   // For video scrubbing
            'medium' => ['width' => 640, 'height' => 360],  // For thumbnails
            'large' => ['width' => 1280, 'height' => 720],  // For previews
        ];
        
        $thumbnailPaths = [];
        
        foreach ($sizes as $size => $dimensions) {
            $sizedOutputFilename = str_replace('.jpg', "_{$size}.jpg", $outputFilename);
            $sizedOutputPath = Storage::path($sizedOutputFilename);
            
            $success = $this->generateSingleThumbnail($inputPath, $sizedOutputPath, $timestamp, $dimensions);
            
            if ($success) {
                $thumbnailPaths[$size] = $sizedOutputFilename;
            }
        }
        
        // Return the medium size as the primary thumbnail
        return $thumbnailPaths['medium'] ?? null;
    }

    /**
     * Generate single thumbnail with specific dimensions.
     */
    private function generateSingleThumbnail(string $inputPath, string $outputPath, float $timestamp, array $dimensions): bool
    {
        $command = [
            'ffmpeg',
            '-i', $inputPath,
            '-ss', (string) $timestamp,
            '-vframes', '1',
            '-vf', "scale={$dimensions['width']}:{$dimensions['height']}:force_original_aspect_ratio=decrease,pad={$dimensions['width']}:{$dimensions['height']}:(ow-iw)/2:(oh-ih)/2,unsharp=5:5:1.0:5:5:0.0",
            '-q:v', '2', // High quality
            '-y', // Overwrite
            $outputPath
        ];

        $process = new Process($command);
        $process->setTimeout(60);
        $process->run();

        if ($process->isSuccessful() && file_exists($outputPath)) {
            // Optimize the thumbnail for web
            $this->optimizeThumbnail($outputPath);
            return true;
        }

        Log::warning("Failed to generate thumbnail at {$timestamp}s: " . $process->getErrorOutput());
        return false;
    }

    /**
     * Optimize thumbnail for web delivery.
     */
    private function optimizeThumbnail(string $thumbnailPath): void
    {
        // Simple optimization - could be enhanced with more sophisticated algorithms
        $command = [
            'ffmpeg',
            '-i', $thumbnailPath,
            '-vf', 'format=yuv420p',
            '-qscale:v', '3',
            '-y',
            $thumbnailPath . '_optimized.jpg'
        ];

        $process = new Process($command);
        $process->setTimeout(30);
        $process->run();

        if ($process->isSuccessful() && file_exists($thumbnailPath . '_optimized.jpg')) {
            // Replace original with optimized version
            rename($thumbnailPath . '_optimized.jpg', $thumbnailPath);
        }
    }

    /**
     * Generate thumbnail sprite for video scrubbing.
     */
    private function generateThumbnailSprite(array $thumbnails): ?string
    {
        if (empty($thumbnails)) {
            return null;
        }
        
        try {
            $spritePath = $this->createVideoSprite($thumbnails);
            
            // Update video file with sprite information
            $customMetadata = $this->videoFile->custom_metadata ?? [];
            $customMetadata['thumbnail_sprite'] = $spritePath;
            $customMetadata['sprite_thumbnails'] = count($thumbnails);
            
            $this->videoFile->update(['custom_metadata' => $customMetadata]);
            
            Log::info("Generated thumbnail sprite for video {$this->videoFile->id} with " . count($thumbnails) . " thumbnails");
            return $spritePath;
            
        } catch (Exception $e) {
            Log::warning("Failed to generate thumbnail sprite for video {$this->videoFile->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create video sprite from thumbnails.
     */
    private function createVideoSprite(array $thumbnails): string
    {
        $spriteFilename = "sprites/{$this->videoFile->id}_sprite.jpg";
        $spritePath = Storage::path($spriteFilename);
        
        $this->ensureDirectoryExists(dirname($spritePath));
        
        // Calculate sprite dimensions (4 thumbnails per row)
        $thumbnailsPerRow = 4;
        $rows = ceil(count($thumbnails) / $thumbnailsPerRow);
        $thumbnailWidth = 160;
        $thumbnailHeight = 90;
        
        $spriteWidth = $thumbnailsPerRow * $thumbnailWidth;
        $spriteHeight = $rows * $thumbnailHeight;
        
        // Use ImageMagick or GD to create sprite
        // For now, use FFmpeg to create a simple sprite
        $thumbnailPaths = array_column($thumbnails, 'path');
        $firstFourThumbnails = array_slice($thumbnailPaths, 0, 4);
        
        if (count($firstFourThumbnails) >= 2) {
            $command = [
                'ffmpeg',
                '-i', Storage::path($firstFourThumbnails[0]),
                '-i', Storage::path($firstFourThumbnails[1]),
            ];
            
            if (count($firstFourThumbnails) >= 3) {
                $command[] = '-i';
                $command[] = Storage::path($firstFourThumbnails[2]);
            }
            
            if (count($firstFourThumbnails) >= 4) {
                $command[] = '-i';
                $command[] = Storage::path($firstFourThumbnails[3]);
            }
            
            $filterComplex = count($firstFourThumbnails) === 2 ? 
                '[0:v][1:v]hstack=inputs=2[v]' :
                (count($firstFourThumbnails) === 3 ?
                    '[0:v][1:v][2:v]hstack=inputs=3[v]' :
                    '[0:v][1:v][2:v][3:v]hstack=inputs=4[v]'
                );
            
            $command = array_merge($command, [
                '-filter_complex', $filterComplex,
                '-map', '[v]',
                '-y',
                $spritePath
            ]);
            
            $process = new Process($command);
            $process->setTimeout(120);
            $process->run();
            
            if ($process->isSuccessful()) {
                return $spriteFilename;
            }
        }
        
        throw new Exception("Failed to create thumbnail sprite");
    }

    /**
     * Update video file with thumbnail information.
     */
    private function updateVideoWithThumbnails(array $thumbnails): void
    {
        // Set the primary thumbnail (prefer basketball keyframes, then standard)
        $primaryThumbnail = null;
        
        // Prefer half/mid-point thumbnail as primary
        if (isset($thumbnails['half'])) {
            $primaryThumbnail = $thumbnails['half']['path'];
        } elseif (isset($thumbnails['q2_start'])) {
            $primaryThumbnail = $thumbnails['q2_start']['path'];
        } elseif (isset($thumbnails['action_2'])) {
            $primaryThumbnail = $thumbnails['action_2']['path'];
        } else {
            // Use first available thumbnail
            $primaryThumbnail = reset($thumbnails)['path'] ?? null;
        }
        
        // Update video file
        $updates = [
            'thumbnail_path' => $primaryThumbnail,
        ];
        
        // Store all thumbnails in custom metadata
        $customMetadata = $this->videoFile->custom_metadata ?? [];
        $customMetadata['all_thumbnails'] = $thumbnails;
        $customMetadata['thumbnail_generation_completed_at'] = now()->toISOString();
        $updates['custom_metadata'] = $customMetadata;
        
        $this->videoFile->update($updates);
        
        Log::info("Updated video {$this->videoFile->id} with thumbnail information", [
            'primary_thumbnail' => $primaryThumbnail,
            'total_thumbnails' => count($thumbnails),
        ]);
    }

    /**
     * Check if this is a basketball video.
     */
    private function isBasketballVideo(): bool
    {
        // Check explicit video types
        $basketballVideoTypes = [
            'full_game', 'game_highlights', 'training_session', 'drill_demo',
            'player_analysis', 'tactical_analysis'
        ];
        
        if (in_array($this->videoFile->video_type, $basketballVideoTypes)) {
            return true;
        }
        
        // Check if associated with basketball entities
        if ($this->videoFile->team_id || $this->videoFile->game_id || $this->videoFile->training_session_id) {
            return true;
        }
        
        // Check tags
        $tags = $this->videoFile->tags ?? [];
        $basketballTags = ['basketball', 'game', 'training', 'drill', 'court'];
        
        return !empty(array_intersect($tags, $basketballTags));
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
        return "thumbnails_{$this->videoFile->id}";
    }

    /**
     * Get tags for monitoring.
     */
    public function tags(): array
    {
        return [
            'thumbnail_generation',
            'video_id:' . $this->videoFile->id,
            'video_type:' . $this->videoFile->video_type,
        ];
    }
}