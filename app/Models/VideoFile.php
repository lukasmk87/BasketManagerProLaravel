<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class VideoFile extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia, Searchable, LogsActivity;

    protected $fillable = [
        'uploaded_by_user_id',
        'team_id',
        'game_id',
        'training_session_id',
        'title',
        'description',
        'original_filename',
        'file_path',
        'thumbnail_path',
        'processed_path',
        'mime_type',
        'file_size',
        'duration',
        'width',
        'height',
        'frame_rate',
        'codec',
        'bitrate',
        'video_type',
        'recording_angle',
        'recording_equipment',
        'recorded_at',
        'recording_location',
        'processing_status',
        'processing_metadata',
        'processing_started_at',
        'processing_completed_at',
        'processing_error',
        'ai_analysis_enabled',
        'ai_analysis_status',
        'ai_analysis_results',
        'ai_confidence_score',
        'ai_analysis_completed_at',
        'visibility',
        'downloadable',
        'embeddable',
        'sharing_permissions',
        'view_count',
        'like_count',
        'share_count',
        'annotation_count',
        'average_rating',
        'tags',
        'custom_metadata',
        'transcription',
        'language',
        'quality_rating',
        'has_audio',
        'has_subtitles',
        'encoding_profile',
    ];

    protected $casts = [
        'recording_equipment' => 'array',
        'recorded_at' => 'datetime',
        'processing_metadata' => 'array',
        'processing_started_at' => 'datetime',
        'processing_completed_at' => 'datetime',
        'ai_analysis_enabled' => 'boolean',
        'ai_analysis_results' => 'array',
        'ai_confidence_score' => 'decimal:4',
        'ai_analysis_completed_at' => 'datetime',
        'downloadable' => 'boolean',
        'embeddable' => 'boolean',
        'sharing_permissions' => 'array',
        'view_count' => 'integer',
        'like_count' => 'integer',
        'share_count' => 'integer',
        'annotation_count' => 'integer',
        'average_rating' => 'decimal:2',
        'tags' => 'array',
        'custom_metadata' => 'array',
        'has_audio' => 'boolean',
        'has_subtitles' => 'boolean',
    ];

    // Relationships
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function trainingSession(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class);
    }

    public function annotations(): HasMany
    {
        return $this->hasMany(VideoAnnotation::class);
    }

    public function analysisSessions(): HasMany
    {
        return $this->hasMany(VideoAnalysisSession::class);
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    public function scopeProcessed($query)
    {
        return $query->where('processing_status', 'completed');
    }

    public function scopeByTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeByVideoType($query, $type)
    {
        return $query->where('video_type', $type);
    }

    public function scopePendingAnalysis($query)
    {
        return $query->where('ai_analysis_enabled', true)
                    ->where('ai_analysis_status', 'pending');
    }

    public function scopeAnalyzed($query)
    {
        return $query->where('ai_analysis_status', 'completed');
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByQuality($query, $quality)
    {
        return $query->where('quality_rating', $quality);
    }

    public function scopeWithAudio($query)
    {
        return $query->where('has_audio', true);
    }

    public function scopePopular($query)
    {
        return $query->orderBy('view_count', 'desc');
    }

    public function scopeHighlyRated($query)
    {
        return $query->whereNotNull('average_rating')
                    ->where('average_rating', '>=', 4.0)
                    ->orderBy('average_rating', 'desc');
    }

    // Accessors and Mutators
    public function isProcessed(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->processing_status === 'completed',
        );
    }

    public function canAnalyze(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->is_processed && $this->ai_analysis_enabled && 
                         $this->ai_analysis_status !== 'completed',
        );
    }

    public function isAIReady(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->ai_analysis_status === 'completed' && 
                         $this->ai_confidence_score > 0.7,
        );
    }

    public function displayDuration(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->duration) return 'Unbekannt';
                
                $hours = floor($this->duration / 3600);
                $minutes = floor(($this->duration % 3600) / 60);
                $seconds = $this->duration % 60;
                
                if ($hours > 0) {
                    return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
                } else {
                    return sprintf('%d:%02d', $minutes, $seconds);
                }
            },
        );
    }

    public function displayFileSize(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->file_size) return 'Unbekannt';
                
                $units = ['B', 'KB', 'MB', 'GB', 'TB'];
                $size = $this->file_size;
                $unit = 0;
                
                while ($size >= 1024 && $unit < count($units) - 1) {
                    $size /= 1024;
                    $unit++;
                }
                
                return round($size, 2) . ' ' . $units[$unit];
            },
        );
    }

    public function videoTypeDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $types = [
                    'full_game' => 'Vollständiges Spiel',
                    'game_highlights' => 'Spiel-Highlights',
                    'training_session' => 'Training',
                    'drill_demo' => 'Drill-Demo',
                    'player_analysis' => 'Spieleranalyse',
                    'tactical_analysis' => 'Taktikanalyse',
                    'scouting_report' => 'Scouting-Bericht',
                    'instructional' => 'Lernvideo',
                    'warm_up' => 'Aufwärmen',
                    'cool_down' => 'Abwärmen',
                    'interview' => 'Interview',
                ];
                
                return $types[$this->video_type] ?? $this->video_type;
            },
        );
    }

    public function processingStatusDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $statuses = [
                    'uploaded' => 'Hochgeladen',
                    'queued' => 'In Warteschlange',
                    'processing' => 'Wird verarbeitet',
                    'completed' => 'Verarbeitung abgeschlossen',
                    'failed' => 'Verarbeitung fehlgeschlagen',
                    'archived' => 'Archiviert',
                ];
                
                return $statuses[$this->processing_status] ?? $this->processing_status;
            },
        );
    }

    // Helper Methods
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function incrementLikeCount(): void
    {
        $this->increment('like_count');
    }

    public function incrementShareCount(): void
    {
        $this->increment('share_count');
    }

    public function updateAnnotationCount(): void
    {
        $this->update([
            'annotation_count' => $this->annotations()->count()
        ]);
    }

    public function generateThumbnail(): bool
    {
        if (!$this->is_processed) {
            return false;
        }

        // This would integrate with FFmpeg service
        // For now, just mark as needing thumbnail generation
        return true;
    }

    public function optimizeForStreaming(): bool
    {
        if (!$this->is_processed) {
            return false;
        }

        // This would create web-optimized versions
        return true;
    }

    public function extractMetadata(): array
    {
        $metadata = [];
        
        if ($this->processing_metadata) {
            $metadata = $this->processing_metadata;
        }
        
        // Add computed metadata
        $metadata['duration_display'] = $this->display_duration;
        $metadata['file_size_display'] = $this->display_file_size;
        $metadata['aspect_ratio'] = $this->width && $this->height ? 
            round($this->width / $this->height, 2) : null;
        
        return $metadata;
    }

    public function getStreamingUrl(): ?string
    {
        if (!$this->embeddable || $this->visibility === 'private') {
            return null;
        }
        
        // Return URL for streaming - would integrate with CDN
        return $this->processed_path ?: $this->file_path;
    }

    public function canBeDownloadedBy(User $user): bool
    {
        if (!$this->downloadable) {
            return false;
        }
        
        // Check permissions
        if ($this->uploaded_by_user_id === $user->id) {
            return true;
        }
        
        if ($this->team && $user->teams->contains($this->team)) {
            return true;
        }
        
        return $user->can('download-videos');
    }

    public function canBeViewedBy(User $user): bool
    {
        if ($this->visibility === 'public') {
            return true;
        }
        
        if ($this->uploaded_by_user_id === $user->id) {
            return true;
        }
        
        if ($this->visibility === 'team_only' && $this->team) {
            return $user->teams->contains($this->team);
        }
        
        return $user->can('view-all-videos');
    }

    public function getAIInsights(): array
    {
        if ($this->ai_analysis_status !== 'completed') {
            return [];
        }
        
        $results = $this->ai_analysis_results ?? [];
        
        return [
            'players_detected' => $results['players'] ?? [],
            'plays_identified' => $results['plays'] ?? [],
            'court_areas' => $results['court_areas'] ?? [],
            'confidence_score' => $this->ai_confidence_score,
            'analysis_completed' => $this->ai_analysis_completed_at,
        ];
    }

    public function getRelatedVideos(int $limit = 5)
    {
        return self::where('id', '!=', $this->id)
                  ->where(function ($query) {
                      if ($this->team_id) {
                          $query->where('team_id', $this->team_id);
                      }
                      if ($this->game_id) {
                          $query->orWhere('game_id', $this->game_id);
                      }
                      if ($this->training_session_id) {
                          $query->orWhere('training_session_id', $this->training_session_id);
                      }
                  })
                  ->where('video_type', $this->video_type)
                  ->where('visibility', 'public')
                  ->where('processing_status', 'completed')
                  ->orderBy('view_count', 'desc')
                  ->limit($limit)
                  ->get();
    }

    // Scout Search
    public function toSearchableArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'tags' => $this->tags,
            'video_type' => $this->video_type,
            'transcription' => $this->transcription,
            'uploader' => $this->uploadedBy->full_name ?? '',
            'team' => $this->team->name ?? '',
        ];
    }

    // Media Collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnails')
              ->acceptsMimeTypes(['image/jpeg', 'image/png'])
              ->singleFile();

        $this->addMediaCollection('previews')
              ->acceptsMimeTypes(['video/mp4', 'video/webm'])
              ->singleFile();

        $this->addMediaCollection('subtitles')
              ->acceptsMimeTypes(['text/vtt', 'application/x-subrip'])
              ->singleFile();
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
              ->width(300)
              ->height(200)
              ->quality(85)
              ->performOnCollections('thumbnails');

        $this->addMediaConversion('large-thumb')
              ->width(600)
              ->height(400)
              ->quality(90)
              ->performOnCollections('thumbnails');

        $this->addMediaConversion('preview')
              ->width(640)
              ->height(360)
              ->quality(70)
              ->performOnCollections('previews');
    }

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
                         ->logOnly([
                             'title', 'description', 'visibility', 'processing_status',
                             'ai_analysis_status', 'downloadable', 'embeddable'
                         ])
                         ->logOnlyDirty()
                         ->dontSubmitEmptyLogs();
    }
}