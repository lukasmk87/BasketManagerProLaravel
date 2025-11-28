<?php

namespace App\Jobs;

use App\Models\VideoFile;
use App\Services\ML\VideoAnalysis\VideoAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class AnalyzeVideoWithAI implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected VideoFile $videoFile;
    protected array $analysisOptions;
    
    public int $timeout = 3600; // 1 hour
    public int $tries = 2;
    public array $backoff = [300, 900]; // 5min, 15min

    /**
     * Create a new job instance.
     */
    public function __construct(VideoFile $videoFile, array $analysisOptions = [])
    {
        $this->videoFile = $videoFile;
        $this->analysisOptions = $analysisOptions;
        
        $this->onQueue('ai-analysis');
    }

    /**
     * Execute the job.
     */
    public function handle(VideoAnalysisService $videoAnalysisService): void
    {
        Log::info("Starting AI video analysis job for video {$this->videoFile->id}");

        try {
            // Perform AI analysis
            $results = $videoAnalysisService->analyzeVideo($this->videoFile, $this->analysisOptions);
            
            Log::info("AI video analysis completed successfully for video {$this->videoFile->id}", [
                'overall_confidence' => $results['overall_confidence'] ?? 'unknown',
                'analysis_type' => $results['analysis_type'] ?? 'unknown',
            ]);
            
        } catch (Exception $e) {
            Log::error("AI video analysis job failed for video {$this->videoFile->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error("AnalyzeVideoWithAI job failed permanently for video {$this->videoFile->id}", [
            'video_id' => $this->videoFile->id,
            'error' => $exception->getMessage(),
            'options' => $this->analysisOptions,
        ]);
        
        // Update video status
        $this->videoFile->update([
            'ai_analysis_status' => 'failed',
            'ai_analysis_completed_at' => now(),
        ]);
    }

    /**
     * Get unique job identifier.
     */
    public function uniqueId(): string
    {
        return "ai_analyze_{$this->videoFile->id}";
    }

    /**
     * Get tags for monitoring.
     */
    public function tags(): array
    {
        return [
            'ai_video_analysis',
            'video_id:' . $this->videoFile->id,
            'video_type:' . $this->videoFile->video_type,
        ];
    }
}