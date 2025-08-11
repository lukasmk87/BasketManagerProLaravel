<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class VideoAnnotation extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'video_file_id',
        'created_by_user_id',
        'player_id',
        'start_time',
        'end_time',
        'frame_start',
        'frame_end',
        'annotation_type',
        'title',
        'description',
        'coaching_notes',
        'play_type',
        'court_area',
        'players_involved',
        'outcome',
        'points_scored',
        'visual_markers',
        'coordinate_data',
        'marker_color',
        'marker_style',
        'is_ai_generated',
        'ai_confidence',
        'human_verified',
        'verified_by_user_id',
        'verified_at',
        'status',
        'is_public',
        'is_featured',
        'priority',
        'view_count',
        'like_count',
        'educational_value',
        'learning_objectives',
        'skill_tags',
    ];

    protected $casts = [
        'start_time' => 'decimal:3',
        'end_time' => 'decimal:3',
        'players_involved' => 'array',
        'visual_markers' => 'array',
        'coordinate_data' => 'array',
        'is_ai_generated' => 'boolean',
        'ai_confidence' => 'decimal:4',
        'human_verified' => 'boolean',
        'verified_at' => 'datetime',
        'is_public' => 'boolean',
        'is_featured' => 'boolean',
        'learning_objectives' => 'array',
        'skill_tags' => 'array',
    ];

    // Basketball court dimensions (in pixels for standard court visualization)
    const COURT_WIDTH = 940;  // NBA court width in pixels
    const COURT_HEIGHT = 500; // NBA court height in pixels
    
    // Court areas mapping
    const COURT_AREAS = [
        'paint' => ['x_min' => 190, 'x_max' => 750, 'y_min' => 170, 'y_max' => 330],
        'three_point_line' => ['radius' => 237.5, 'center_x' => 470, 'center_y' => 250],
        'free_throw_line' => ['x_min' => 280, 'x_max' => 660, 'y' => 170],
        'baseline' => ['y' => 0],
        'sideline' => ['x' => 0],
        'center_court' => ['x' => 470, 'y' => 250, 'radius' => 60],
        'backcourt' => ['x_max' => 470],
        'frontcourt' => ['x_min' => 470],
    ];

    // Relationships
    public function videoFile(): BelongsTo
    {
        return $this->belongsTo(VideoFile::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true)->where('status', 'approved');
    }

    public function scopeByAnnotationType($query, $type)
    {
        return $query->where('annotation_type', $type);
    }

    public function scopeByPlayType($query, $playType)
    {
        return $query->where('play_type', $playType);
    }

    public function scopeByCourtArea($query, $area)
    {
        return $query->where('court_area', $area);
    }

    public function scopeAIGenerated($query)
    {
        return $query->where('is_ai_generated', true);
    }

    public function scopeHumanVerified($query)
    {
        return $query->where('human_verified', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeEducational($query)
    {
        return $query->where('educational_value', '>=', 7);
    }

    public function scopeInTimeRange($query, $startTime, $endTime)
    {
        return $query->where('start_time', '>=', $startTime)
                    ->where('start_time', '<=', $endTime);
    }

    public function scopeByPlayer($query, $playerId)
    {
        return $query->where('player_id', $playerId);
    }

    public function scopeHighConfidence($query, $threshold = 0.8)
    {
        return $query->where('ai_confidence', '>=', $threshold);
    }

    // Accessors and Mutators
    public function isPointAnnotation(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->end_time === null || $this->start_time === $this->end_time,
        );
    }

    public function duration(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->end_time ? $this->end_time - $this->start_time : 0,
        );
    }

    public function annotationTypeDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $types = [
                    'play_action' => 'Spielaktion',
                    'player_highlight' => 'Spieler-Highlight',
                    'tactical_note' => 'Taktische Notiz',
                    'mistake' => 'Fehler',
                    'good_play' => 'Guter Spielzug',
                    'foul' => 'Foul',
                    'timeout' => 'Auszeit',
                    'substitution' => 'Auswechslung',
                    'coaching_point' => 'Trainer-Hinweis',
                    'statistical_event' => 'Statistisches Ereignis',
                    'injury' => 'Verletzung',
                    'technical_issue' => 'Technisches Problem',
                    'custom' => 'Benutzerdefiniert',
                ];
                
                return $types[$this->annotation_type] ?? $this->annotation_type;
            },
        );
    }

    public function playTypeDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $types = [
                    'offense' => 'Angriff',
                    'defense' => 'Verteidigung',
                    'transition' => 'Übergang',
                    'set_play' => 'Spielzug',
                    'fast_break' => 'Schnellangriff',
                    'rebound' => 'Rebound',
                    'shot' => 'Wurf',
                    'pass' => 'Pass',
                    'dribble' => 'Dribbling',
                    'screen' => 'Block',
                    'cut' => 'Schnitt',
                ];
                
                return $types[$this->play_type] ?? $this->play_type;
            },
        );
    }

    public function courtAreaDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $areas = [
                    'paint' => 'Zone',
                    'three_point_line' => 'Dreierlinie',
                    'free_throw_line' => 'Freiwurflinie',
                    'baseline' => 'Grundlinie',
                    'sideline' => 'Seitenlinie',
                    'center_court' => 'Mittellinie',
                    'backcourt' => 'Rückfeld',
                    'frontcourt' => 'Vorderfeld',
                ];
                
                return $areas[$this->court_area] ?? $this->court_area;
            },
        );
    }

    public function formattedTime(): Attribute
    {
        return Attribute::make(
            get: function () {
                $minutes = floor($this->start_time / 60);
                $seconds = $this->start_time % 60;
                return sprintf('%d:%05.2f', $minutes, $seconds);
            },
        );
    }

    // Basketball-specific Methods
    public function convertCoordinatesToCourt(array $videoCoordinates): array
    {
        if (!isset($videoCoordinates['x']) || !isset($videoCoordinates['y'])) {
            return [];
        }

        // Get video dimensions from the associated video file
        $video = $this->videoFile;
        $videoWidth = $video->width ?? 1920;
        $videoHeight = $video->height ?? 1080;

        // Convert video coordinates to court coordinates
        $courtX = ($videoCoordinates['x'] / $videoWidth) * self::COURT_WIDTH;
        $courtY = ($videoCoordinates['y'] / $videoHeight) * self::COURT_HEIGHT;

        return [
            'x' => round($courtX, 2),
            'y' => round($courtY, 2),
            'court_width' => self::COURT_WIDTH,
            'court_height' => self::COURT_HEIGHT,
        ];
    }

    public function determineCourtAreaFromCoordinates(array $courtCoordinates): ?string
    {
        if (!isset($courtCoordinates['x']) || !isset($courtCoordinates['y'])) {
            return null;
        }

        $x = $courtCoordinates['x'];
        $y = $courtCoordinates['y'];

        // Check each court area
        foreach (self::COURT_AREAS as $area => $bounds) {
            if ($this->isPointInArea($x, $y, $bounds)) {
                return $area;
            }
        }

        return null;
    }

    private function isPointInArea(float $x, float $y, array $bounds): bool
    {
        // Handle different area types
        if (isset($bounds['radius'])) {
            // Circular area (like three-point line, center court)
            $centerX = $bounds['center_x'];
            $centerY = $bounds['center_y'];
            $radius = $bounds['radius'];
            
            $distance = sqrt(pow($x - $centerX, 2) + pow($y - $centerY, 2));
            return $distance <= $radius;
        }
        
        if (isset($bounds['x_min']) && isset($bounds['x_max']) && 
            isset($bounds['y_min']) && isset($bounds['y_max'])) {
            // Rectangular area
            return $x >= $bounds['x_min'] && $x <= $bounds['x_max'] &&
                   $y >= $bounds['y_min'] && $y <= $bounds['y_max'];
        }
        
        if (isset($bounds['y']) && !isset($bounds['x_min'])) {
            // Horizontal line (baseline)
            return abs($y - $bounds['y']) <= 10; // 10px tolerance
        }
        
        if (isset($bounds['x']) && !isset($bounds['y_min'])) {
            // Vertical line (sideline)
            return abs($x - $bounds['x']) <= 10; // 10px tolerance
        }
        
        return false;
    }

    public function determinePlayEffectiveness(): string
    {
        if (!$this->outcome) {
            return 'neutral';
        }

        $effectiveness = 'neutral';

        switch ($this->play_type) {
            case 'shot':
                $effectiveness = $this->outcome === 'successful' ? 'effective' : 'ineffective';
                break;
            case 'pass':
                $effectiveness = $this->outcome === 'successful' ? 'effective' : 'turnover';
                break;
            case 'defense':
                $effectiveness = $this->outcome === 'successful' ? 'good_defense' : 'defensive_breakdown';
                break;
            case 'rebound':
                $effectiveness = $this->outcome === 'successful' ? 'secured' : 'lost';
                break;
        }

        return $effectiveness;
    }

    public function generateMarkerSVG(): string
    {
        if (!$this->visual_markers || !$this->coordinate_data) {
            return '';
        }

        $svg = '<svg viewBox="0 0 ' . self::COURT_WIDTH . ' ' . self::COURT_HEIGHT . '">';
        
        foreach ($this->visual_markers as $marker) {
            $svg .= $this->createMarkerElement($marker);
        }
        
        $svg .= '</svg>';
        
        return $svg;
    }

    private function createMarkerElement(array $marker): string
    {
        $color = $this->marker_color;
        $style = $this->marker_style ?? 'circle';
        
        switch ($style) {
            case 'circle':
                return sprintf(
                    '<circle cx="%s" cy="%s" r="10" fill="%s" opacity="0.7"/>',
                    $marker['x'], $marker['y'], $color
                );
            case 'rectangle':
                return sprintf(
                    '<rect x="%s" y="%s" width="20" height="20" fill="%s" opacity="0.7"/>',
                    $marker['x'] - 10, $marker['y'] - 10, $color
                );
            case 'arrow':
                return sprintf(
                    '<polygon points="%s,%s %s,%s %s,%s" fill="%s" opacity="0.7"/>',
                    $marker['x'], $marker['y'] - 15,
                    $marker['x'] - 10, $marker['y'] + 10,
                    $marker['x'] + 10, $marker['y'] + 10,
                    $color
                );
            default:
                return '';
        }
    }

    public function getCoachingInsights(): array
    {
        $insights = [];
        
        if ($this->play_type && $this->outcome) {
            $insights[] = $this->generatePlayTypeInsight();
        }
        
        if ($this->court_area) {
            $insights[] = $this->generateCourtAreaInsight();
        }
        
        if ($this->players_involved) {
            $insights[] = $this->generatePlayerInsight();
        }
        
        return array_filter($insights);
    }

    private function generatePlayTypeInsight(): string
    {
        $effectiveness = $this->determinePlayEffectiveness();
        
        $insights = [
            'shot' => [
                'effective' => 'Guter Wurfversuch - Technik und Positionierung beachten',
                'ineffective' => 'Wurfversuch analysieren - Verbesserungsmöglichkeiten identifizieren',
            ],
            'pass' => [
                'effective' => 'Gelungener Pass - Timing und Präzision hervorheben',
                'turnover' => 'Ballverlust - Entscheidungsfindung und Technik überprüfen',
            ],
            'defense' => [
                'good_defense' => 'Starke Defensivleistung - als Beispiel nutzen',
                'defensive_breakdown' => 'Defensive Schwäche - Verbesserungsansätze besprechen',
            ]
        ];
        
        return $insights[$this->play_type][$effectiveness] ?? 'Spielsituation für Analyse nutzen';
    }

    private function generateCourtAreaInsight(): string
    {
        $areaInsights = [
            'paint' => 'Spiel in der Zone - Körperkontakt und Positionierung wichtig',
            'three_point_line' => 'Dreierversuch - Wurfauswahl und -technik beachten',
            'free_throw_line' => 'Mitteldistanz - Balance zwischen Aggresivität und Kontrolle',
            'baseline' => 'Grundlinienspiel - Winkel und Raumausnutzung analysieren',
        ];
        
        return $areaInsights[$this->court_area] ?? 'Positionsspiel analysieren';
    }

    private function generatePlayerInsight(): string
    {
        $playerCount = count($this->players_involved);
        
        if ($playerCount === 1) {
            return 'Individuelle Aktion - Technik und Entscheidung fokussieren';
        } elseif ($playerCount <= 3) {
            return 'Kleine Gruppe - Zusammenspiel und Kommunikation wichtig';
        } else {
            return 'Teamaktion - Koordination und Systemverständnis entscheidend';
        }
    }

    public function suggestImprovements(): array
    {
        $improvements = [];
        
        if ($this->outcome === 'unsuccessful') {
            $improvements[] = $this->generateImprovementSuggestion();
        }
        
        if ($this->ai_confidence && $this->ai_confidence < 0.7) {
            $improvements[] = 'Annotation sollte von Trainer überprüft werden';
        }
        
        if ($this->educational_value < 5) {
            $improvements[] = 'Lernwert dieser Szene könnte durch Details erhöht werden';
        }
        
        return $improvements;
    }

    private function generateImprovementSuggestion(): string
    {
        $suggestions = [
            'shot' => 'Wurfbewegung und Standposition verbessern',
            'pass' => 'Passgenauigkeit und Timing üben',
            'defense' => 'Defensive Positionierung und Antizipation trainieren',
            'dribble' => 'Ballkontrolle und Körperbeherrschung stärken',
        ];
        
        return $suggestions[$this->play_type] ?? 'Grundlagen dieser Spielsituation wiederholen';
    }

    public function validateAIAnnotation(): bool
    {
        // Basic validation rules for AI-generated annotations
        $validationPassed = true;
        
        // Check confidence threshold
        if ($this->ai_confidence < 0.5) {
            $validationPassed = false;
        }
        
        // Check if annotation makes basketball sense
        if ($this->play_type === 'shot' && $this->court_area === 'backcourt') {
            $validationPassed = false; // Shots from backcourt are very rare
        }
        
        // Check temporal consistency
        if ($this->end_time && $this->start_time > $this->end_time) {
            $validationPassed = false;
        }
        
        return $validationPassed;
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

    public function markAsVerified(User $user): bool
    {
        return $this->update([
            'human_verified' => true,
            'verified_by_user_id' => $user->id,
            'verified_at' => now(),
            'status' => 'approved',
        ]);
    }

    public function canBeEditedBy(User $user): bool
    {
        if ($this->created_by_user_id === $user->id) {
            return true;
        }
        
        if ($user->can('edit-video-annotations')) {
            return true;
        }
        
        return false;
    }

    public function getSimilarAnnotations(int $limit = 5)
    {
        return self::where('id', '!=', $this->id)
                  ->where('video_file_id', $this->video_file_id)
                  ->where('play_type', $this->play_type)
                  ->where('status', 'approved')
                  ->orderBy('educational_value', 'desc')
                  ->limit($limit)
                  ->get();
    }

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
                         ->logOnly([
                             'title', 'description', 'play_type', 'outcome', 
                             'status', 'human_verified', 'is_featured'
                         ])
                         ->logOnlyDirty()
                         ->dontSubmitEmptyLogs();
    }
}