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
use Exception;

class ProcessVideoUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected VideoFile $videoFile;
    
    public int $timeout = 3600; // 1 hour
    public int $tries = 3;
    public int $maxExceptions = 3;
    public array $backoff = [60, 300, 900]; // 1min, 5min, 15min

    /**
     * Create a new job instance.
     */
    public function __construct(VideoFile $videoFile)
    {
        $this->videoFile = $videoFile;
        
        // Set queue based on video priority
        $this->onQueue($this->determineQueue());
    }

    /**
     * Execute the job.
     */
    public function handle(VideoProcessingService $videoProcessingService): void
    {
        Log::info("Starting video processing job for video {$this->videoFile->id}");
        
        try {
            // Step 1: Validate video file
            $this->validateVideoFile();
            
            // Step 2: Update status to processing
            $this->updateProcessingStatus('processing');
            
            // Step 3: Extract metadata first (critical for other jobs)
            Log::info("Dispatching metadata extraction for video {$this->videoFile->id}");
            ExtractVideoMetadata::dispatch($this->videoFile)->onQueue('metadata');
            
            // Wait for metadata extraction to complete (with timeout)
            $this->waitForMetadataExtraction();
            
            // Step 4: Dispatch thumbnail generation
            Log::info("Dispatching thumbnail generation for video {$this->videoFile->id}");
            GenerateThumbnails::dispatch($this->videoFile)->onQueue('thumbnails');
            
            // Step 5: Dispatch video optimization
            Log::info("Dispatching video optimization for video {$this->videoFile->id}");
            $quality = $this->determineOptimizationQuality();
            OptimizeVideoQuality::dispatch($this->videoFile, $quality)->onQueue('optimization');
            
            // Step 6: Perform additional basketball-specific processing
            $this->performBasketballSpecificProcessing();
            
            // Step 7: Update final status
            $this->updateProcessingStatus('completed');
            
            // Step 8: Trigger AI analysis if enabled
            if ($this->videoFile->ai_analysis_enabled) {
                $this->triggerAIAnalysis();
            }
            
            // Step 9: Send notification to user
            $this->sendProcessingCompleteNotification();
            
            Log::info("Video processing job completed successfully for video {$this->videoFile->id}");
            
        } catch (Exception $e) {
            Log::error("Video processing job failed for video {$this->videoFile->id}: " . $e->getMessage(), [
                'video_id' => $this->videoFile->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->handleProcessingFailure($e);
            throw $e;
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error("ProcessVideoUpload job failed permanently for video {$this->videoFile->id}", [
            'video_id' => $this->videoFile->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
        
        $this->videoFile->update([
            'processing_status' => 'failed',
            'processing_error' => $exception->getMessage(),
            'processing_completed_at' => now(),
        ]);
        
        // Clean up any partial files
        $this->cleanupPartialFiles();
        
        // Notify user of failure
        $this->sendProcessingFailureNotification($exception);
    }

    /**
     * Validate the video file before processing.
     */
    private function validateVideoFile(): void
    {
        // Check if file exists
        if (!Storage::exists($this->videoFile->file_path)) {
            throw new Exception("Video file not found: {$this->videoFile->file_path}");
        }
        
        // Check file size
        $fileSize = Storage::size($this->videoFile->file_path);
        if ($fileSize === false || $fileSize === 0) {
            throw new Exception("Invalid file size for video: {$this->videoFile->id}");
        }
        
        // Update file size if not set
        if (!$this->videoFile->file_size) {
            $this->videoFile->update(['file_size' => $fileSize]);
        }
        
        // Check if file is corrupted (basic check)
        $mimeType = Storage::mimeType($this->videoFile->file_path);
        if (!str_starts_with($mimeType, 'video/')) {
            throw new Exception("Invalid file type. Expected video, got: {$mimeType}");
        }
        
        // Security scan for malicious content
        $this->performSecurityScan();
        
        Log::info("Video file validation passed for video {$this->videoFile->id}");
    }

    /**
     * Perform security scan on video file.
     */
    private function performSecurityScan(): void
    {
        $filePath = Storage::path($this->videoFile->file_path);
        
        // Check file extension matches mime type
        $expectedExtensions = [
            'video/mp4' => ['mp4', 'm4v'],
            'video/avi' => ['avi'],
            'video/quicktime' => ['mov', 'qt'],
            'video/x-msvideo' => ['avi'],
            'video/webm' => ['webm'],
            'video/x-flv' => ['flv'],
        ];
        
        $mimeType = mime_content_type($filePath);
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if (isset($expectedExtensions[$mimeType]) && 
            !in_array($extension, $expectedExtensions[$mimeType])) {
            throw new Exception("File extension doesn't match mime type");
        }
        
        // Additional security checks can be added here
        Log::info("Security scan passed for video {$this->videoFile->id}");
    }

    /**
     * Update processing status with timestamp.
     */
    private function updateProcessingStatus(string $status): void
    {
        $updates = ['processing_status' => $status];
        
        switch ($status) {
            case 'processing':
                $updates['processing_started_at'] = now();
                break;
            case 'completed':
                $updates['processing_completed_at'] = now();
                break;
        }
        
        $this->videoFile->update($updates);
    }

    /**
     * Wait for metadata extraction to complete.
     */
    private function waitForMetadataExtraction(int $maxWaitTime = 300): void
    {
        $startTime = time();
        
        while (time() - $startTime < $maxWaitTime) {
            $this->videoFile->refresh();
            
            if ($this->videoFile->duration !== null && $this->videoFile->width !== null) {
                Log::info("Metadata extraction completed for video {$this->videoFile->id}");
                return;
            }
            
            sleep(10); // Wait 10 seconds before checking again
        }
        
        Log::warning("Metadata extraction timed out for video {$this->videoFile->id}");
    }

    /**
     * Determine video optimization quality based on video properties.
     */
    private function determineOptimizationQuality(): string
    {
        // Refresh to get latest metadata
        $this->videoFile->refresh();
        
        $width = $this->videoFile->width ?? 1920;
        $fileSize = $this->videoFile->file_size ?? 0;
        $duration = $this->videoFile->duration ?? 60;
        
        // Calculate bitrate if not available
        $estimatedBitrate = $duration > 0 ? ($fileSize * 8) / $duration : 0;
        
        // Basketball-specific quality decisions
        if ($this->videoFile->video_type === 'full_game') {
            // Full games need higher quality for analysis
            return $width >= 1920 ? 'high' : 'medium';
        }
        
        if ($this->videoFile->video_type === 'drill_demo') {
            // Drill demos can use lower quality for faster loading
            return 'medium';
        }
        
        // General quality determination
        if ($width >= 1920 && $estimatedBitrate > 3000000) {
            return 'high';
        } elseif ($width >= 1280) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Perform basketball-specific processing.
     */
    private function performBasketballSpecificProcessing(): void
    {
        Log::info("Performing basketball-specific processing for video {$this->videoFile->id}");
        
        // Detect if this is a basketball video based on metadata
        $isBasketballVideo = $this->detectBasketballContent();
        
        if ($isBasketballVideo) {
            // Generate basketball-specific keyframes
            $this->generateBasketballKeyframes();
            
            // Associate with game or training if possible
            $this->associateWithGameOrTraining();
            
            // Tag with basketball-specific metadata
            $this->tagWithBasketballMetadata();
        }
        
        Log::info("Basketball-specific processing completed for video {$this->videoFile->id}");
    }

    /**
     * Detect if this is basketball content.
     */
    private function detectBasketballContent(): bool
    {
        // Simple heuristics for now - can be enhanced with AI later
        $indicators = 0;
        
        // Check filename for basketball terms
        $basketballTerms = ['game', 'training', 'drill', 'basketball', 'court', 'match'];
        $filename = strtolower($this->videoFile->original_filename);
        
        foreach ($basketballTerms as $term) {
            if (str_contains($filename, $term)) {
                $indicators++;
            }
        }
        
        // Check if associated with team, game, or training
        if ($this->videoFile->team_id || $this->videoFile->game_id || $this->videoFile->training_session_id) {
            $indicators += 2;
        }
        
        // Check video type
        if (in_array($this->videoFile->video_type, [
            'full_game', 'game_highlights', 'training_session', 'drill_demo', 
            'player_analysis', 'tactical_analysis'
        ])) {
            $indicators += 3;
        }
        
        return $indicators >= 2;
    }

    /**
     * Generate basketball-specific keyframes.
     */
    private function generateBasketballKeyframes(): void
    {
        // This would integrate with AI analysis later
        // For now, just mark that basketball keyframes are needed
        $metadata = $this->videoFile->processing_metadata ?? [];
        $metadata['basketball_keyframes_needed'] = true;
        $metadata['basketball_content_detected'] = true;
        
        $this->videoFile->update(['processing_metadata' => $metadata]);
    }

    /**
     * Associate video with game or training session.
     */
    private function associateWithGameOrTraining(): void
    {
        // If not already associated, try to auto-associate based on timing and team
        if (!$this->videoFile->game_id && !$this->videoFile->training_session_id && $this->videoFile->team_id) {
            
            // Look for games or training sessions around the recording time
            if ($this->videoFile->recorded_at) {
                $searchStart = $this->videoFile->recorded_at->subHours(2);
                $searchEnd = $this->videoFile->recorded_at->addHours(2);
                
                // Try to find matching game
                $game = \App\Models\Game::where('team_id', $this->videoFile->team_id)
                                      ->whereBetween('scheduled_at', [$searchStart, $searchEnd])
                                      ->first();
                
                if ($game) {
                    $this->videoFile->update(['game_id' => $game->id]);
                    Log::info("Auto-associated video {$this->videoFile->id} with game {$game->id}");
                    return;
                }
                
                // Try to find matching training session
                $training = \App\Models\TrainingSession::where('team_id', $this->videoFile->team_id)
                                                     ->whereBetween('scheduled_at', [$searchStart, $searchEnd])
                                                     ->first();
                
                if ($training) {
                    $this->videoFile->update(['training_session_id' => $training->id]);
                    Log::info("Auto-associated video {$this->videoFile->id} with training {$training->id}");
                }
            }
        }
    }

    /**
     * Tag video with basketball metadata.
     */
    private function tagWithBasketballMetadata(): void
    {
        $tags = $this->videoFile->tags ?? [];
        
        // Add basketball tag if not present
        if (!in_array('basketball', $tags)) {
            $tags[] = 'basketball';
        }
        
        // Add specific tags based on video type
        $videoTypeTags = [
            'full_game' => ['game', 'full-game', 'match'],
            'game_highlights' => ['highlights', 'best-plays'],
            'training_session' => ['training', 'practice'],
            'drill_demo' => ['drill', 'exercise', 'tutorial'],
            'player_analysis' => ['analysis', 'performance'],
            'tactical_analysis' => ['tactics', 'strategy'],
        ];
        
        if (isset($videoTypeTags[$this->videoFile->video_type])) {
            $tags = array_merge($tags, $videoTypeTags[$this->videoFile->video_type]);
        }
        
        // Remove duplicates and update
        $this->videoFile->update(['tags' => array_unique($tags)]);
    }

    /**
     * Trigger AI analysis if conditions are met.
     */
    private function triggerAIAnalysis(): void
    {
        // Update AI analysis status to pending
        $this->videoFile->update(['ai_analysis_status' => 'pending']);
        
        // This would dispatch AI analysis job in Phase 4
        Log::info("AI analysis triggered for video {$this->videoFile->id}");
    }

    /**
     * Determine appropriate queue based on video properties.
     */
    private function determineQueue(): string
    {
        // Priority queue for game videos
        if ($this->videoFile->video_type === 'full_game' || $this->videoFile->game_id) {
            return 'videos-priority';
        }
        
        // Standard queue for training and drills
        return 'videos';
    }

    /**
     * Handle processing failure.
     */
    private function handleProcessingFailure(Exception $exception): void
    {
        $this->videoFile->update([
            'processing_status' => 'failed',
            'processing_error' => $exception->getMessage(),
        ]);
        
        // Clean up any partial files created during processing
        $this->cleanupPartialFiles();
    }

    /**
     * Clean up partial files created during failed processing.
     */
    private function cleanupPartialFiles(): void
    {
        $filesToCleanup = [
            $this->videoFile->processed_path,
            $this->videoFile->thumbnail_path,
        ];
        
        foreach ($filesToCleanup as $filePath) {
            if ($filePath && Storage::exists($filePath)) {
                Storage::delete($filePath);
                Log::info("Cleaned up partial file: {$filePath}");
            }
        }
    }

    /**
     * Send notification that processing is complete.
     */
    private function sendProcessingCompleteNotification(): void
    {
        // This would integrate with notification system
        Log::info("Sending processing complete notification for video {$this->videoFile->id}");
        
        // Could dispatch notification job here
        // \App\Jobs\SendVideoProcessingNotification::dispatch($this->videoFile, 'completed');
    }

    /**
     * Send notification that processing failed.
     */
    private function sendProcessingFailureNotification(Exception $exception): void
    {
        // This would integrate with notification system
        Log::info("Sending processing failure notification for video {$this->videoFile->id}");
        
        // Could dispatch notification job here
        // \App\Jobs\SendVideoProcessingNotification::dispatch($this->videoFile, 'failed', $exception->getMessage());
    }

    /**
     * Get unique job identifier for monitoring.
     */
    public function uniqueId(): string
    {
        return "process_video_{$this->videoFile->id}";
    }
    
    /**
     * Get tags for queue monitoring.
     */
    public function tags(): array
    {
        return [
            'video_processing',
            'video_id:' . $this->videoFile->id,
            'video_type:' . $this->videoFile->video_type,
            'team_id:' . ($this->videoFile->team_id ?? 'none'),
        ];
    }
}