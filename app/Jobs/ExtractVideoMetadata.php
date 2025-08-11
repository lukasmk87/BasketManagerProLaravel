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
use Symfony\Component\Process\Process;
use Exception;

class ExtractVideoMetadata implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected VideoFile $videoFile;
    protected bool $includeBasketballAnalysis;
    protected bool $generateQualityReport;
    
    public int $timeout = 300; // 5 minutes
    public int $tries = 3;
    public array $backoff = [30, 60, 120]; // 30s, 1min, 2min

    /**
     * Create a new job instance.
     */
    public function __construct(VideoFile $videoFile, bool $includeBasketballAnalysis = true, bool $generateQualityReport = true)
    {
        $this->videoFile = $videoFile;
        $this->includeBasketballAnalysis = $includeBasketballAnalysis;
        $this->generateQualityReport = $generateQualityReport;
        
        $this->onQueue('metadata');
    }

    /**
     * Execute the job.
     */
    public function handle(VideoProcessingService $videoProcessingService): void
    {
        Log::info("Starting metadata extraction for video {$this->videoFile->id}");
        
        try {
            // Validate video file exists and is accessible
            $this->validateVideoFile();
            
            // Extract comprehensive technical metadata using FFprobe
            $technicalMetadata = $this->extractTechnicalMetadata();
            
            // Extract content-based metadata
            $contentMetadata = $this->extractContentMetadata();
            
            // Generate quality assessment
            $qualityAssessment = $this->generateQualityReport ? 
                $this->generateQualityAssessment($technicalMetadata) : [];
            
            // Basketball-specific analysis
            $basketballAnalysis = $this->includeBasketballAnalysis ? 
                $this->performBasketballAnalysis($technicalMetadata, $contentMetadata) : [];
            
            // Audio analysis
            $audioAnalysis = $this->analyzeAudioContent($technicalMetadata);
            
            // Frame analysis for keyframe detection
            $frameAnalysis = $this->analyzeFrameContent();
            
            // Compile all metadata
            $completeMetadata = $this->compileMetadata(
                $technicalMetadata,
                $contentMetadata,
                $qualityAssessment,
                $basketballAnalysis,
                $audioAnalysis,
                $frameAnalysis
            );
            
            // Update video file with extracted metadata
            $this->updateVideoFileWithMetadata($completeMetadata);
            
            // Generate metadata report
            $this->generateMetadataReport($completeMetadata);
            
            Log::info("Metadata extraction completed for video {$this->videoFile->id}", [
                'duration' => $completeMetadata['duration'] ?? 'unknown',
                'resolution' => ($completeMetadata['width'] ?? 'unknown') . 'x' . ($completeMetadata['height'] ?? 'unknown'),
                'quality_score' => $qualityAssessment['overall_score'] ?? 'unknown',
                'basketball_detected' => $basketballAnalysis['is_basketball_content'] ?? false,
            ]);
            
        } catch (Exception $e) {
            Log::error("Metadata extraction failed for video {$this->videoFile->id}: " . $e->getMessage(), [
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
        Log::error("ExtractVideoMetadata job failed for video {$this->videoFile->id}", [
            'video_id' => $this->videoFile->id,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Validate video file is accessible.
     */
    private function validateVideoFile(): void
    {
        if (!Storage::exists($this->videoFile->file_path)) {
            throw new Exception("Video file not found: {$this->videoFile->file_path}");
        }
        
        $filePath = Storage::path($this->videoFile->file_path);
        
        if (!is_readable($filePath)) {
            throw new Exception("Video file is not readable: {$filePath}");
        }
        
        if (filesize($filePath) === 0) {
            throw new Exception("Video file is empty: {$filePath}");
        }
        
        Log::info("Video file validation passed for metadata extraction {$this->videoFile->id}");
    }

    /**
     * Extract comprehensive technical metadata using FFprobe.
     */
    private function extractTechnicalMetadata(): array
    {
        $filePath = Storage::path($this->videoFile->file_path);
        
        // Extended FFprobe command for comprehensive metadata
        $command = [
            'ffprobe',
            '-v', 'quiet',
            '-print_format', 'json',
            '-show_format',
            '-show_streams',
            '-show_chapters',
            '-show_programs',
            '-count_frames',
            '-select_streams', 'v:0', // Focus on first video stream for frame count
            $filePath
        ];

        $process = new Process($command);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new Exception("FFprobe failed: " . $process->getErrorOutput());
        }

        $rawMetadata = json_decode($process->getOutput(), true);
        
        if (!$rawMetadata) {
            throw new Exception("Failed to parse FFprobe output");
        }
        
        return $this->parseTechnicalMetadata($rawMetadata);
    }

    /**
     * Parse and structure technical metadata from FFprobe output.
     */
    private function parseTechnicalMetadata(array $rawMetadata): array
    {
        $metadata = [
            'format_info' => $rawMetadata['format'] ?? [],
            'streams' => $rawMetadata['streams'] ?? [],
            'chapters' => $rawMetadata['chapters'] ?? [],
            'programs' => $rawMetadata['programs'] ?? [],
        ];

        // Extract video stream information
        $videoStreams = array_filter($rawMetadata['streams'], fn($s) => $s['codec_type'] === 'video');
        $primaryVideoStream = reset($videoStreams);

        if ($primaryVideoStream) {
            $metadata['video'] = [
                'codec_name' => $primaryVideoStream['codec_name'] ?? null,
                'codec_long_name' => $primaryVideoStream['codec_long_name'] ?? null,
                'width' => $primaryVideoStream['width'] ?? null,
                'height' => $primaryVideoStream['height'] ?? null,
                'coded_width' => $primaryVideoStream['coded_width'] ?? null,
                'coded_height' => $primaryVideoStream['coded_height'] ?? null,
                'sample_aspect_ratio' => $primaryVideoStream['sample_aspect_ratio'] ?? null,
                'display_aspect_ratio' => $primaryVideoStream['display_aspect_ratio'] ?? null,
                'pixel_format' => $primaryVideoStream['pix_fmt'] ?? null,
                'color_range' => $primaryVideoStream['color_range'] ?? null,
                'color_space' => $primaryVideoStream['color_space'] ?? null,
                'color_transfer' => $primaryVideoStream['color_transfer'] ?? null,
                'color_primaries' => $primaryVideoStream['color_primaries'] ?? null,
                'field_order' => $primaryVideoStream['field_order'] ?? null,
                'level' => $primaryVideoStream['level'] ?? null,
                'profile' => $primaryVideoStream['profile'] ?? null,
                'bit_rate' => $primaryVideoStream['bit_rate'] ?? null,
                'max_bit_rate' => $primaryVideoStream['max_bit_rate'] ?? null,
                'frame_rate' => $this->parseFrameRate($primaryVideoStream['r_frame_rate'] ?? '0/0'),
                'avg_frame_rate' => $this->parseFrameRate($primaryVideoStream['avg_frame_rate'] ?? '0/0'),
                'time_base' => $primaryVideoStream['time_base'] ?? null,
                'start_pts' => $primaryVideoStream['start_pts'] ?? null,
                'start_time' => $primaryVideoStream['start_time'] ?? null,
                'duration_ts' => $primaryVideoStream['duration_ts'] ?? null,
                'duration' => $primaryVideoStream['duration'] ?? null,
                'nb_frames' => $primaryVideoStream['nb_frames'] ?? null,
                'nb_read_frames' => $primaryVideoStream['nb_read_frames'] ?? null,
                'is_avc' => $primaryVideoStream['is_avc'] ?? null,
                'nal_length_size' => $primaryVideoStream['nal_length_size'] ?? null,
            ];
        }

        // Extract audio stream information
        $audioStreams = array_filter($rawMetadata['streams'], fn($s) => $s['codec_type'] === 'audio');
        $metadata['audio'] = [];

        foreach ($audioStreams as $index => $audioStream) {
            $metadata['audio'][$index] = [
                'codec_name' => $audioStream['codec_name'] ?? null,
                'codec_long_name' => $audioStream['codec_long_name'] ?? null,
                'sample_fmt' => $audioStream['sample_fmt'] ?? null,
                'sample_rate' => $audioStream['sample_rate'] ?? null,
                'channels' => $audioStream['channels'] ?? null,
                'channel_layout' => $audioStream['channel_layout'] ?? null,
                'bits_per_sample' => $audioStream['bits_per_sample'] ?? null,
                'bit_rate' => $audioStream['bit_rate'] ?? null,
                'duration' => $audioStream['duration'] ?? null,
                'language' => $audioStream['tags']['language'] ?? null,
                'title' => $audioStream['tags']['title'] ?? null,
            ];
        }

        // Extract format-level information
        $format = $rawMetadata['format'] ?? [];
        $metadata['container'] = [
            'format_name' => $format['format_name'] ?? null,
            'format_long_name' => $format['format_long_name'] ?? null,
            'duration' => isset($format['duration']) ? (int) round($format['duration']) : null,
            'size' => $format['size'] ?? null,
            'bit_rate' => $format['bit_rate'] ?? null,
            'nb_streams' => $format['nb_streams'] ?? null,
            'nb_programs' => $format['nb_programs'] ?? null,
            'probe_score' => $format['probe_score'] ?? null,
            'tags' => $format['tags'] ?? [],
        ];

        return $metadata;
    }

    /**
     * Extract content-based metadata.
     */
    private function extractContentMetadata(): array
    {
        $contentMetadata = [];
        
        // Analyze filename for content hints
        $filename = $this->videoFile->original_filename;
        $contentMetadata['filename_analysis'] = $this->analyzeFilename($filename);
        
        // Analyze video duration patterns
        $duration = $this->videoFile->duration ?? 0;
        $contentMetadata['duration_analysis'] = $this->analyzeDuration($duration);
        
        // Analyze aspect ratio
        if ($this->videoFile->width && $this->videoFile->height) {
            $contentMetadata['aspect_ratio_analysis'] = $this->analyzeAspectRatio(
                $this->videoFile->width, 
                $this->videoFile->height
            );
        }
        
        return $contentMetadata;
    }

    /**
     * Generate comprehensive quality assessment.
     */
    private function generateQualityAssessment(array $technicalMetadata): array
    {
        $assessment = [
            'video_quality' => $this->assessVideoQuality($technicalMetadata),
            'audio_quality' => $this->assessAudioQuality($technicalMetadata),
            'encoding_quality' => $this->assessEncodingQuality($technicalMetadata),
            'structural_quality' => $this->assessStructuralQuality($technicalMetadata),
        ];
        
        // Calculate overall quality score (0-100)
        $scores = array_filter([
            $assessment['video_quality']['score'] ?? null,
            $assessment['audio_quality']['score'] ?? null,
            $assessment['encoding_quality']['score'] ?? null,
            $assessment['structural_quality']['score'] ?? null,
        ]);
        
        $assessment['overall_score'] = !empty($scores) ? round(array_sum($scores) / count($scores)) : null;
        $assessment['quality_rating'] = $this->getQualityRating($assessment['overall_score']);
        
        return $assessment;
    }

    /**
     * Perform basketball-specific content analysis.
     */
    private function performBasketballAnalysis(array $technicalMetadata, array $contentMetadata): array
    {
        $analysis = [
            'is_basketball_content' => false,
            'confidence_score' => 0,
            'detected_features' => [],
            'recommendations' => [],
        ];
        
        $confidence = 0;
        $features = [];
        
        // Check video type
        $basketballVideoTypes = [
            'full_game', 'game_highlights', 'training_session', 'drill_demo',
            'player_analysis', 'tactical_analysis'
        ];
        
        if (in_array($this->videoFile->video_type, $basketballVideoTypes)) {
            $confidence += 30;
            $features[] = 'explicit_basketball_video_type';
        }
        
        // Check associations
        if ($this->videoFile->team_id) {
            $confidence += 25;
            $features[] = 'associated_with_team';
        }
        
        if ($this->videoFile->game_id) {
            $confidence += 25;
            $features[] = 'associated_with_game';
        }
        
        if ($this->videoFile->training_session_id) {
            $confidence += 20;
            $features[] = 'associated_with_training';
        }
        
        // Analyze filename for basketball keywords
        $filenameAnalysis = $contentMetadata['filename_analysis'] ?? [];
        if ($filenameAnalysis['basketball_keywords'] ?? 0 > 0) {
            $confidence += 15;
            $features[] = 'basketball_keywords_in_filename';
        }
        
        // Check tags
        $tags = $this->videoFile->tags ?? [];
        $basketballTags = ['basketball', 'game', 'training', 'drill', 'court', 'match'];
        $matchingTags = array_intersect($tags, $basketballTags);
        if (!empty($matchingTags)) {
            $confidence += count($matchingTags) * 5;
            $features[] = 'basketball_tags_present';
        }
        
        // Analyze video dimensions for basketball court ratios
        $aspectRatio = $contentMetadata['aspect_ratio_analysis'] ?? [];
        if (isset($aspectRatio['is_sports_friendly']) && $aspectRatio['is_sports_friendly']) {
            $confidence += 10;
            $features[] = 'sports_friendly_aspect_ratio';
        }
        
        // Duration analysis for basketball content
        $durationAnalysis = $contentMetadata['duration_analysis'] ?? [];
        if (isset($durationAnalysis['likely_content_type'])) {
            $contentType = $durationAnalysis['likely_content_type'];
            if (in_array($contentType, ['full_game', 'quarter', 'training_session', 'drill'])) {
                $confidence += 15;
                $features[] = 'basketball_typical_duration';
            }
        }
        
        $analysis['confidence_score'] = min(100, $confidence);
        $analysis['is_basketball_content'] = $confidence >= 50;
        $analysis['detected_features'] = $features;
        
        // Generate recommendations
        $analysis['recommendations'] = $this->generateBasketballRecommendations($analysis, $technicalMetadata);
        
        return $analysis;
    }

    /**
     * Analyze audio content for basketball-specific audio cues.
     */
    private function analyzeAudioContent(array $technicalMetadata): array
    {
        $audioAnalysis = [
            'has_audio' => false,
            'audio_channels' => 0,
            'audio_quality' => 'unknown',
            'likely_contains_crowd_noise' => false,
            'likely_contains_whistle' => false,
            'likely_contains_commentary' => false,
        ];
        
        $audioStreams = $technicalMetadata['audio'] ?? [];
        
        if (!empty($audioStreams)) {
            $audioAnalysis['has_audio'] = true;
            $primaryAudio = reset($audioStreams);
            
            $audioAnalysis['audio_channels'] = $primaryAudio['channels'] ?? 0;
            $audioAnalysis['sample_rate'] = $primaryAudio['sample_rate'] ?? 0;
            $audioAnalysis['bit_rate'] = $primaryAudio['bit_rate'] ?? 0;
            
            // Basic audio quality assessment
            $sampleRate = $primaryAudio['sample_rate'] ?? 0;
            $bitRate = $primaryAudio['bit_rate'] ?? 0;
            
            if ($sampleRate >= 44100 && $bitRate >= 128000) {
                $audioAnalysis['audio_quality'] = 'high';
            } elseif ($sampleRate >= 22050 && $bitRate >= 64000) {
                $audioAnalysis['audio_quality'] = 'medium';
            } else {
                $audioAnalysis['audio_quality'] = 'low';
            }
            
            // Heuristics for basketball audio content
            if ($audioAnalysis['audio_channels'] >= 2) {
                $audioAnalysis['likely_contains_crowd_noise'] = true;
            }
            
            // These would be enhanced with actual audio analysis in the future
            $audioAnalysis['likely_contains_whistle'] = $this->videoFile->video_type === 'full_game';
            $audioAnalysis['likely_contains_commentary'] = in_array($this->videoFile->video_type, ['full_game', 'game_highlights']);
        }
        
        return $audioAnalysis;
    }

    /**
     * Analyze frame content for keyframe detection.
     */
    private function analyzeFrameContent(): array
    {
        $frameAnalysis = [
            'total_frames' => 0,
            'keyframe_interval' => 0,
            'estimated_keyframes' => 0,
            'frame_rate_consistency' => 'unknown',
        ];
        
        // Get frame information from technical metadata
        $duration = $this->videoFile->duration ?? 0;
        $frameRate = $this->videoFile->frame_rate ?? 0;
        
        if ($duration > 0 && $frameRate > 0) {
            $frameAnalysis['total_frames'] = (int) ($duration * $frameRate);
            $frameAnalysis['estimated_keyframes'] = (int) ($duration / 2); // Assume keyframe every 2 seconds
        }
        
        return $frameAnalysis;
    }

    /**
     * Compile all metadata into structured format.
     */
    private function compileMetadata(
        array $technical,
        array $content,
        array $quality,
        array $basketball,
        array $audio,
        array $frame
    ): array {
        return [
            'extraction_timestamp' => now()->toISOString(),
            'technical_metadata' => $technical,
            'content_metadata' => $content,
            'quality_assessment' => $quality,
            'basketball_analysis' => $basketball,
            'audio_analysis' => $audio,
            'frame_analysis' => $frame,
            
            // Flatten key technical data for easy database access
            'duration' => $technical['container']['duration'] ?? null,
            'width' => $technical['video']['width'] ?? null,
            'height' => $technical['video']['height'] ?? null,
            'frame_rate' => $technical['video']['frame_rate'] ?? null,
            'codec' => $technical['video']['codec_name'] ?? null,
            'bitrate' => $technical['video']['bit_rate'] ?? null,
            'file_size' => $technical['container']['size'] ?? null,
            'has_audio' => $audio['has_audio'] ?? false,
            
            // Summary fields
            'overall_quality_score' => $quality['overall_score'] ?? null,
            'is_basketball_content' => $basketball['is_basketball_content'] ?? false,
            'basketball_confidence' => $basketball['confidence_score'] ?? 0,
        ];
    }

    /**
     * Update video file with extracted metadata.
     */
    private function updateVideoFileWithMetadata(array $metadata): void
    {
        $updates = [
            'duration' => $metadata['duration'],
            'width' => $metadata['width'],
            'height' => $metadata['height'],
            'frame_rate' => $metadata['frame_rate'],
            'codec' => $metadata['codec'],
            'bitrate' => $metadata['bitrate'],
            'file_size' => $metadata['file_size'] ?? $this->videoFile->file_size,
            'has_audio' => $metadata['has_audio'],
            'processing_metadata' => $metadata,
        ];
        
        // Update quality rating if we have a quality assessment
        if (isset($metadata['quality_assessment']['quality_rating'])) {
            $updates['quality_rating'] = $metadata['quality_assessment']['quality_rating'];
        }
        
        $this->videoFile->update($updates);
        
        Log::info("Updated video {$this->videoFile->id} with extracted metadata", [
            'duration' => $metadata['duration'],
            'resolution' => $metadata['width'] . 'x' . $metadata['height'],
            'quality_score' => $metadata['overall_quality_score'],
        ]);
    }

    /**
     * Generate metadata extraction report.
     */
    private function generateMetadataReport(array $metadata): void
    {
        $report = [
            'video_id' => $this->videoFile->id,
            'extraction_date' => now()->toISOString(),
            'technical_summary' => [
                'format' => $metadata['technical_metadata']['container']['format_name'] ?? 'unknown',
                'duration' => $metadata['duration'] . ' seconds',
                'resolution' => ($metadata['width'] ?? 'unknown') . 'x' . ($metadata['height'] ?? 'unknown'),
                'codec' => $metadata['codec'] ?? 'unknown',
                'bitrate' => isset($metadata['bitrate']) ? round($metadata['bitrate'] / 1000) . ' kbps' : 'unknown',
            ],
            'quality_summary' => [
                'overall_score' => $metadata['overall_quality_score'] . '/100',
                'quality_rating' => $metadata['quality_assessment']['quality_rating'] ?? 'unknown',
            ],
            'basketball_summary' => [
                'is_basketball_content' => $metadata['is_basketball_content'] ? 'Yes' : 'No',
                'confidence' => $metadata['basketball_confidence'] . '%',
                'features_detected' => count($metadata['basketball_analysis']['detected_features'] ?? []),
            ],
        ];
        
        Log::info("Metadata extraction report for video {$this->videoFile->id}", $report);
    }

    // Helper methods for analysis
    
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

    private function analyzeFilename(string $filename): array
    {
        $basketballKeywords = [
            'basketball', 'game', 'match', 'training', 'practice', 'drill', 
            'court', 'quarter', 'half', 'timeout', 'scrimmage'
        ];
        
        $filename = strtolower($filename);
        $keywordCount = 0;
        $foundKeywords = [];
        
        foreach ($basketballKeywords as $keyword) {
            if (str_contains($filename, $keyword)) {
                $keywordCount++;
                $foundKeywords[] = $keyword;
            }
        }
        
        return [
            'basketball_keywords' => $keywordCount,
            'found_keywords' => $foundKeywords,
            'filename_length' => strlen($filename),
        ];
    }

    private function analyzeDuration(int $duration): array
    {
        $analysis = ['duration_category' => 'unknown', 'likely_content_type' => 'unknown'];
        
        if ($duration < 60) {
            $analysis['duration_category'] = 'very_short';
            $analysis['likely_content_type'] = 'highlight_clip';
        } elseif ($duration < 300) {
            $analysis['duration_category'] = 'short';
            $analysis['likely_content_type'] = 'drill';
        } elseif ($duration < 900) {
            $analysis['duration_category'] = 'medium';
            $analysis['likely_content_type'] = 'quarter';
        } elseif ($duration < 3600) {
            $analysis['duration_category'] = 'long';
            $analysis['likely_content_type'] = 'training_session';
        } else {
            $analysis['duration_category'] = 'very_long';
            $analysis['likely_content_type'] = 'full_game';
        }
        
        return $analysis;
    }

    private function analyzeAspectRatio(int $width, int $height): array
    {
        $ratio = $width / $height;
        $analysis = ['aspect_ratio' => round($ratio, 2)];
        
        // Common aspect ratios
        if (abs($ratio - 16/9) < 0.1) {
            $analysis['standard'] = '16:9';
            $analysis['is_sports_friendly'] = true;
        } elseif (abs($ratio - 4/3) < 0.1) {
            $analysis['standard'] = '4:3';
            $analysis['is_sports_friendly'] = false;
        } else {
            $analysis['standard'] = 'custom';
            $analysis['is_sports_friendly'] = $ratio > 1.5;
        }
        
        return $analysis;
    }

    private function assessVideoQuality(array $metadata): array
    {
        $score = 50; // Base score
        $notes = [];
        
        $video = $metadata['video'] ?? [];
        
        // Resolution scoring
        $width = $video['width'] ?? 0;
        if ($width >= 1920) {
            $score += 25;
            $notes[] = 'High resolution (1080p+)';
        } elseif ($width >= 1280) {
            $score += 15;
            $notes[] = 'Good resolution (720p)';
        } elseif ($width >= 640) {
            $score += 5;
            $notes[] = 'Adequate resolution (480p)';
        } else {
            $score -= 10;
            $notes[] = 'Low resolution';
        }
        
        // Bitrate scoring
        $bitrate = $video['bit_rate'] ?? 0;
        if ($bitrate >= 5000000) {
            $score += 15;
            $notes[] = 'High bitrate';
        } elseif ($bitrate >= 2000000) {
            $score += 10;
            $notes[] = 'Good bitrate';
        } elseif ($bitrate >= 500000) {
            $score += 5;
            $notes[] = 'Adequate bitrate';
        } else {
            $score -= 5;
            $notes[] = 'Low bitrate';
        }
        
        // Codec quality
        $codec = $video['codec_name'] ?? '';
        if (in_array($codec, ['h264', 'hevc', 'vp9'])) {
            $score += 10;
            $notes[] = 'Modern codec';
        } elseif (in_array($codec, ['mpeg4', 'xvid'])) {
            $score -= 5;
            $notes[] = 'Older codec';
        }
        
        return [
            'score' => max(0, min(100, $score)),
            'notes' => $notes,
        ];
    }

    private function assessAudioQuality(array $metadata): array
    {
        $score = 50; // Base score
        $notes = [];
        
        $audio = $metadata['audio'][0] ?? [];
        
        if (empty($audio)) {
            return ['score' => 0, 'notes' => ['No audio stream']];
        }
        
        // Sample rate scoring
        $sampleRate = $audio['sample_rate'] ?? 0;
        if ($sampleRate >= 48000) {
            $score += 25;
            $notes[] = 'High sample rate (48kHz+)';
        } elseif ($sampleRate >= 44100) {
            $score += 20;
            $notes[] = 'CD quality sample rate (44.1kHz)';
        } elseif ($sampleRate >= 22050) {
            $score += 10;
            $notes[] = 'Adequate sample rate';
        } else {
            $score -= 10;
            $notes[] = 'Low sample rate';
        }
        
        // Bitrate scoring
        $bitrate = $audio['bit_rate'] ?? 0;
        if ($bitrate >= 192000) {
            $score += 15;
            $notes[] = 'High audio bitrate';
        } elseif ($bitrate >= 128000) {
            $score += 10;
            $notes[] = 'Good audio bitrate';
        } elseif ($bitrate >= 64000) {
            $score += 5;
            $notes[] = 'Adequate audio bitrate';
        } else {
            $score -= 5;
            $notes[] = 'Low audio bitrate';
        }
        
        // Channel count
        $channels = $audio['channels'] ?? 0;
        if ($channels >= 2) {
            $score += 10;
            $notes[] = 'Stereo audio';
        } else {
            $score -= 5;
            $notes[] = 'Mono audio';
        }
        
        return [
            'score' => max(0, min(100, $score)),
            'notes' => $notes,
        ];
    }

    private function assessEncodingQuality(array $metadata): array
    {
        $score = 70; // Base score for encoding
        $notes = [];
        
        // Check for encoding issues
        $video = $metadata['video'] ?? [];
        
        // Profile and level check
        $profile = $video['profile'] ?? '';
        if (in_array(strtolower($profile), ['high', 'main'])) {
            $score += 15;
            $notes[] = 'Good encoding profile';
        } elseif (strtolower($profile) === 'baseline') {
            $score += 5;
            $notes[] = 'Basic encoding profile';
        }
        
        return [
            'score' => max(0, min(100, $score)),
            'notes' => $notes,
        ];
    }

    private function assessStructuralQuality(array $metadata): array
    {
        $score = 80; // Base score for structure
        $notes = [];
        
        $format = $metadata['container'] ?? [];
        
        // Check for standard container format
        $formatName = $format['format_name'] ?? '';
        if (str_contains($formatName, 'mp4')) {
            $score += 10;
            $notes[] = 'Standard MP4 container';
        } elseif (str_contains($formatName, 'avi')) {
            $score -= 5;
            $notes[] = 'Legacy AVI container';
        }
        
        // Check for corruption indicators
        $probeScore = $format['probe_score'] ?? 100;
        if ($probeScore < 50) {
            $score -= 20;
            $notes[] = 'Potential file corruption';
        }
        
        return [
            'score' => max(0, min(100, $score)),
            'notes' => $notes,
        ];
    }

    private function getQualityRating(?int $score): string
    {
        if ($score === null) return 'unknown';
        
        if ($score >= 80) return 'excellent';
        if ($score >= 65) return 'high';
        if ($score >= 45) return 'medium';
        return 'low';
    }

    private function generateBasketballRecommendations(array $analysis, array $technicalMetadata): array
    {
        $recommendations = [];
        
        if ($analysis['is_basketball_content']) {
            $recommendations[] = 'Enable AI analysis for basketball-specific features';
            $recommendations[] = 'Generate basketball keyframes for highlights';
            
            if (isset($technicalMetadata['video']['width']) && $technicalMetadata['video']['width'] < 1280) {
                $recommendations[] = 'Consider upscaling for better court visibility';
            }
            
            $recommendations[] = 'Associate with relevant games or training sessions';
        }
        
        return $recommendations;
    }

    /**
     * Get unique job identifier.
     */
    public function uniqueId(): string
    {
        return "metadata_{$this->videoFile->id}";
    }

    /**
     * Get tags for monitoring.
     */
    public function tags(): array
    {
        return [
            'metadata_extraction',
            'video_id:' . $this->videoFile->id,
            'video_type:' . $this->videoFile->video_type,
        ];
    }
}