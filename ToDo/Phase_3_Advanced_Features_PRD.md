# Phase 3: Advanced Features PRD - BasketManager Pro Laravel

> **Product Requirements Document (PRD) - Phase 3**  
> **Version**: 1.0  
> **Datum**: 28. Juli 2025  
> **Status**: Entwicklungsbereit  
> **Autor**: Claude Code Assistant  
> **Dauer**: 3 Monate (Monate 7-9)

---

## 📋 Inhaltsverzeichnis

1. [Phase 3 Übersicht](#phase-3-übersicht)
2. [Training & Drill Management](#training--drill-management)
3. [Tournament Management System](#tournament-management-system)
4. [Video Analysis Integration](#video-analysis-integration)
5. [Predictive Analytics Engine](#predictive-analytics-engine)
6. [Advanced Tactics & Strategy](#advanced-tactics--strategy)
7. [Shot Chart & Heat Maps](#shot-chart--heat-maps)
8. [Performance Monitoring](#performance-monitoring)
9. [API Erweiterungen](#api-erweiterungen)
10. [Frontend Components](#frontend-components)
11. [Machine Learning Integration](#machine-learning-integration)
12. [Testing Strategy](#testing-strategy)
13. [Phase 3 Deliverables](#phase-3-deliverables)

---

## 🎯 Phase 3 Übersicht

### Ziele der Advanced Features Phase

Phase 3 erweitert BasketManager Pro um hochentwickelte Features, die das System zu einer vollständigen Basketball-Analytics-Plattform machen. Diese Phase konzentriert sich auf KI-gestützte Analysen, Video-Integration und erweiterte Trainingstools.

### Kernziele

1. **Training System**: Umfassendes Training- und Drill-Management mit Performance-Tracking
2. **Tournament Platform**: Vollständige Turnierorganisation mit automatischen Brackets
3. **Video Analytics**: KI-gestützte Video-Analyse mit Frame-Level-Annotations
4. **Predictive Analytics**: Machine Learning für Performance-Vorhersagen
5. **Tactical Tools**: Interaktive Taktik-Designer und Spielzug-Bibliothek
6. **Advanced Visualization**: Heat Maps, Shot Charts und Performance-Trends
7. **Performance Monitoring**: Umfassendes Spieler-Monitoring und Load-Management

### Success Metrics

- ✅ Training-System mit 100+ vorkonfigurierten Drills
- ✅ Tournament-Management für alle gängigen Formate
- ✅ Video-Processing mit AI-Annotation (<2min per Video)
- ✅ ML-Modelle mit >85% Prediction-Accuracy
- ✅ Interactive Shot Charts mit Real-time Updates
- ✅ Performance Dashboard mit 50+ Metriken
- ✅ Mobile-optimierte Trainer-Tools

---

## 🏋️ Training & Drill Management

### Training Models & Database Design

#### Training Sessions Migration

```php
<?php
// database/migrations/2024_03_01_000000_create_training_sessions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('trainer_id')->constrained('users');
            $table->foreignId('assistant_trainer_id')->nullable()->constrained('users');
            
            // Session Information
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('scheduled_at');
            $table->dateTime('actual_start_time')->nullable();
            $table->dateTime('actual_end_time')->nullable();
            $table->integer('planned_duration')->default(90); // minutes
            $table->integer('actual_duration')->nullable();
            
            // Location
            $table->string('venue');
            $table->text('venue_address')->nullable();
            $table->string('court_type')->nullable(); // indoor, outdoor, gym
            
            // Session Type and Focus
            $table->enum('session_type', [
                'training', 'scrimmage', 'conditioning', 'tactical', 
                'individual', 'team_building', 'recovery'
            ])->default('training');
            
            $table->json('focus_areas')->nullable(); // offense, defense, conditioning, etc.
            $table->enum('intensity_level', ['low', 'medium', 'high', 'maximum'])->default('medium');
            $table->integer('max_participants')->nullable();
            
            // Status
            $table->enum('status', [
                'scheduled', 'in_progress', 'completed', 'cancelled', 'postponed'
            ])->default('scheduled');
            
            // Weather (for outdoor sessions)
            $table->string('weather_conditions')->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->boolean('weather_appropriate')->default(true);
            
            // Equipment and Requirements
            $table->json('required_equipment')->nullable();
            $table->text('special_requirements')->nullable();
            $table->text('safety_notes')->nullable();
            
            // Evaluation
            $table->integer('overall_rating')->nullable(); // 1-10
            $table->text('trainer_notes')->nullable();
            $table->text('session_feedback')->nullable();
            $table->json('goals_achieved')->nullable();
            
            // Settings
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('allows_late_arrival')->default(false);
            $table->boolean('requires_medical_clearance')->default(false);
            $table->json('notification_settings')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['team_id', 'scheduled_at']);
            $table->index(['trainer_id', 'status']);
            $table->index(['scheduled_at', 'status']);
            $table->index('session_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_sessions');
    }
};
```

#### Drills Migration

```php
<?php
// database/migrations/2024_03_02_000000_create_drills_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by_user_id')->constrained('users');
            
            // Basic Information
            $table->string('name');
            $table->text('description');
            $table->text('objectives'); // What this drill aims to achieve
            $table->text('instructions'); // Step-by-step instructions
            
            // Classification
            $table->enum('category', [
                'ball_handling', 'shooting', 'passing', 'defense', 'rebounding',
                'conditioning', 'agility', 'footwork', 'team_offense', 'team_defense',
                'transition', 'set_plays', 'scrimmage', 'warm_up', 'cool_down'
            ]);
            
            $table->enum('sub_category', [
                'fundamental', 'advanced', 'position_specific', 'game_situation',
                'individual', 'small_group', 'team', 'competitive'
            ])->nullable();
            
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced', 'expert']);
            $table->enum('age_group', ['U8', 'U10', 'U12', 'U14', 'U16', 'U18', 'adult', 'all']);
            
            // Logistics
            $table->integer('min_players')->default(1);
            $table->integer('max_players')->nullable();
            $table->integer('optimal_players')->nullable();
            $table->integer('estimated_duration')->default(10); // minutes
            $table->decimal('space_required', 5, 2)->nullable(); // square meters
            
            // Equipment
            $table->json('required_equipment')->nullable(); // balls, cones, etc.
            $table->json('optional_equipment')->nullable();
            $table->boolean('requires_full_court')->default(false);
            $table->boolean('requires_half_court')->default(false);
            
            // Variations and Progressions
            $table->text('variations')->nullable();
            $table->text('progressions')->nullable(); // How to make it harder
            $table->text('regressions')->nullable(); // How to make it easier
            $table->json('coaching_points')->nullable(); // Key points to emphasize
            
            // Metrics and Evaluation
            $table->json('measurable_outcomes')->nullable(); // What can be measured
            $table->json('success_criteria')->nullable();
            $table->boolean('is_competitive')->default(false);
            $table->text('scoring_system')->nullable();
            
            // Media and Diagrams
            $table->string('diagram_path')->nullable();
            $table->json('diagram_annotations')->nullable();
            $table->boolean('has_video')->default(false);
            $table->integer('video_duration')->nullable(); // seconds
            
            // Usage and Popularity
            $table->integer('usage_count')->default(0);
            $table->decimal('average_rating', 3, 2)->nullable();
            $table->integer('rating_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_public')->default(true);
            
            // Tags and Search
            $table->json('tags')->nullable();
            $table->text('search_keywords')->nullable();
            $table->string('source')->nullable(); // Where this drill comes from
            $table->string('author')->nullable(); // Original author/coach
            
            // Status and Approval
            $table->enum('status', ['draft', 'pending_review', 'approved', 'rejected', 'archived']);
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['category', 'difficulty_level']);
            $table->index(['age_group', 'status']);
            $table->index(['is_public', 'status']);
            $table->index('usage_count');
            $table->fullText(['name', 'description', 'search_keywords']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drills');
    }
};
```

#### Training Drills Pivot Migration

```php
<?php
// database/migrations/2024_03_03_000000_create_training_drills_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_drills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('drill_id')->constrained();
            
            // Order and Timing
            $table->integer('order_in_session')->default(1);
            $table->integer('planned_duration')->default(10); // minutes
            $table->integer('actual_duration')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            
            // Drill Configuration
            $table->integer('participants_count')->nullable();
            $table->json('participating_players')->nullable(); // player IDs
            $table->text('specific_instructions')->nullable();
            $table->text('modifications')->nullable(); // Changes from original drill
            
            // Performance Metrics
            $table->json('success_metrics')->nullable(); // Actual measurements
            $table->integer('drill_rating')->nullable(); // 1-10 how well it went
            $table->text('performance_notes')->nullable();
            $table->text('trainer_observations')->nullable();
            
            // Completion Status
            $table->enum('status', ['planned', 'in_progress', 'completed', 'skipped', 'modified']);
            $table->text('skip_reason')->nullable();
            $table->boolean('goals_achieved')->default(false);
            
            // Player Feedback
            $table->decimal('player_difficulty_rating', 3, 2)->nullable(); // 1-10
            $table->decimal('player_enjoyment_rating', 3, 2)->nullable(); // 1-10
            $table->text('player_feedback')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['training_session_id', 'order_in_session']);
            $table->index(['drill_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_drills');
    }
};
```

### Training Models Implementation

#### TrainingSession Model

```php
<?php
// app/Models/TrainingSession.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Carbon\Carbon;

class TrainingSession extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'team_id',
        'trainer_id',
        'assistant_trainer_id',
        'title',
        'description',
        'scheduled_at',
        'actual_start_time',
        'actual_end_time',
        'planned_duration',
        'actual_duration',
        'venue',
        'venue_address',
        'court_type',
        'session_type',
        'focus_areas',
        'intensity_level',
        'max_participants',
        'status',
        'weather_conditions',
        'temperature',
        'weather_appropriate',
        'required_equipment',
        'special_requirements',
        'safety_notes',
        'overall_rating',
        'trainer_notes',
        'session_feedback',
        'goals_achieved',
        'is_mandatory',
        'allows_late_arrival',
        'requires_medical_clearance',
        'notification_settings',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'actual_start_time' => 'datetime',
        'actual_end_time' => 'datetime',
        'planned_duration' => 'integer',
        'actual_duration' => 'integer',
        'focus_areas' => 'array',
        'max_participants' => 'integer',
        'temperature' => 'decimal:1',
        'weather_appropriate' => 'boolean',
        'required_equipment' => 'array',
        'overall_rating' => 'integer',
        'goals_achieved' => 'array',
        'is_mandatory' => 'boolean',
        'allows_late_arrival' => 'boolean',
        'requires_medical_clearance' => 'boolean',
        'notification_settings' => 'array',
    ];

    // Relationships
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function assistantTrainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assistant_trainer_id');
    }

    public function drills(): BelongsToMany
    {
        return $this->belongsToMany(Drill::class, 'training_drills')
                    ->withPivot([
                        'order_in_session', 'planned_duration', 'actual_duration',
                        'participants_count', 'participating_players',
                        'specific_instructions', 'modifications',
                        'success_metrics', 'drill_rating', 'performance_notes',
                        'trainer_observations', 'status', 'skip_reason',
                        'goals_achieved', 'player_difficulty_rating',
                        'player_enjoyment_rating', 'player_feedback',
                        'start_time', 'end_time'
                    ])
                    ->withTimestamps()
                    ->orderBy('training_drills.order_in_session');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(TrainingAttendance::class);
    }

    public function playerPerformances(): HasMany
    {
        return $this->hasMany(PlayerTrainingPerformance::class);
    }

    // Scopes
    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>', now())
                    ->where('status', 'scheduled');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('scheduled_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeByTrainer($query, int $trainerId)
    {
        return $query->where('trainer_id', $trainerId)
                    ->orWhere('assistant_trainer_id', $trainerId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('session_type', $type);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Accessors
    public function duration(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->actual_duration ?? $this->planned_duration,
        );
    }

    public function isCompleted(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === 'completed',
        );
    }

    public function isUpcoming(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->scheduled_at > now() && $this->status === 'scheduled',
        );
    }

    public function isInProgress(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === 'in_progress',
        );
    }

    public function attendanceRate(): Attribute
    {
        return Attribute::make(
            get: function () {
                $totalPlayers = $this->team->activePlayers()->count();
                $attendees = $this->attendance()->where('status', 'present')->count();
                
                return $totalPlayers > 0 ? round(($attendees / $totalPlayers) * 100, 1) : 0;
            },
        );
    }

    public function averageDrillRating(): Attribute
    {
        return Attribute::make(
            get: function () {
                $ratings = $this->drills()
                    ->wherePivotNotNull('drill_rating')
                    ->get()
                    ->pluck('pivot.drill_rating');
                    
                return $ratings->count() > 0 ? round($ratings->average(), 1) : null;
            },
        );
    }

    // Helper Methods
    public function canStart(): bool
    {
        return $this->status === 'scheduled' && 
               $this->scheduled_at <= now()->addMinutes(15);
    }

    public function canComplete(): bool
    {
        return $this->status === 'in_progress';
    }

    public function start(): void
    {
        if (!$this->canStart()) {
            throw new \Exception('Training session cannot be started');
        }

        $this->update([
            'status' => 'in_progress',
            'actual_start_time' => now(),
        ]);
    }

    public function complete(): void
    {
        if (!$this->canComplete()) {
            throw new \Exception('Training session cannot be completed');
        }

        $this->update([
            'status' => 'completed',
            'actual_end_time' => now(),
            'actual_duration' => $this->actual_start_time 
                ? $this->actual_start_time->diffInMinutes(now()) 
                : null,
        ]);
    }

    public function addDrill(Drill $drill, array $pivotData = []): void
    {
        $defaultOrder = $this->drills()->count() + 1;
        
        $this->drills()->attach($drill->id, array_merge([
            'order_in_session' => $defaultOrder,
            'planned_duration' => $drill->estimated_duration,
            'status' => 'planned',
        ], $pivotData));
    }

    public function removeDrill(Drill $drill): void
    {
        $this->drills()->detach($drill->id);
        
        // Reorder remaining drills
        $this->reorderDrills();
    }

    public function reorderDrills(): void
    {
        $drills = $this->drills()->orderBy('training_drills.order_in_session')->get();
        
        foreach ($drills as $index => $drill) {
            $this->drills()->updateExistingPivot($drill->id, [
                'order_in_session' => $index + 1
            ]);
        }
    }

    public function calculateTotalPlannedDuration(): int
    {
        return $this->drills()->sum('training_drills.planned_duration');
    }

    public function calculateTotalActualDuration(): int
    {
        return $this->drills()
            ->wherePivotNotNull('actual_duration')
            ->sum('training_drills.actual_duration');
    }

    public function getParticipationStats(): array
    {
        $attendance = $this->attendance()->with('player')->get();
        
        return [
            'total_invited' => $this->team->activePlayers()->count(),
            'present' => $attendance->where('status', 'present')->count(),
            'absent' => $attendance->where('status', 'absent')->count(),
            'late' => $attendance->where('status', 'late')->count(),
            'excused' => $attendance->where('status', 'excused')->count(),
            'attendance_rate' => $this->attendance_rate,
        ];
    }

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status', 'actual_start_time', 'actual_end_time',
                'overall_rating', 'trainer_notes'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
```

#### Drill Model

```php
<?php
// app/Models/Drill.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Laravel\Scout\Searchable;

class Drill extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia, Searchable;

    protected $fillable = [
        'created_by_user_id',
        'name',
        'description',
        'objectives',
        'instructions',
        'category',
        'sub_category',
        'difficulty_level',
        'age_group',
        'min_players',
        'max_players',
        'optimal_players',
        'estimated_duration',
        'space_required',
        'required_equipment',
        'optional_equipment',
        'requires_full_court',
        'requires_half_court',
        'variations',
        'progressions',
        'regressions',
        'coaching_points',
        'measurable_outcomes',
        'success_criteria',
        'is_competitive',
        'scoring_system',
        'diagram_path',
        'diagram_annotations',
        'has_video',
        'video_duration',
        'usage_count',
        'average_rating',
        'rating_count',
        'is_featured',
        'is_public',
        'tags',
        'search_keywords',
        'source',
        'author',
        'status',
        'reviewed_by_user_id',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'min_players' => 'integer',
        'max_players' => 'integer',
        'optimal_players' => 'integer',
        'estimated_duration' => 'integer',
        'space_required' => 'decimal:2',
        'required_equipment' => 'array',
        'optional_equipment' => 'array',
        'requires_full_court' => 'boolean',
        'requires_half_court' => 'boolean',
        'coaching_points' => 'array',
        'measurable_outcomes' => 'array',
        'success_criteria' => 'array',
        'is_competitive' => 'boolean',
        'diagram_annotations' => 'array',
        'has_video' => 'boolean',
        'video_duration' => 'integer',
        'usage_count' => 'integer',
        'average_rating' => 'decimal:2',
        'rating_count' => 'integer',
        'is_featured' => 'boolean',
        'is_public' => 'boolean',
        'tags' => 'array',
        'reviewed_at' => 'datetime',
    ];

    // Relationships
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function trainingSessions(): BelongsToMany
    {
        return $this->belongsToMany(TrainingSession::class, 'training_drills')
                    ->withPivot([
                        'order_in_session', 'planned_duration', 'actual_duration',
                        'participants_count', 'specific_instructions', 'modifications',
                        'success_metrics', 'drill_rating', 'performance_notes',
                        'status', 'goals_achieved'
                    ])
                    ->withTimestamps();
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(DrillRating::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(DrillFavorite::class);
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('is_public', true)->where('status', 'approved');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty_level', $difficulty);
    }

    public function scopeByAgeGroup($query, string $ageGroup)
    {
        return $query->where('age_group', $ageGroup)->orWhere('age_group', 'all');
    }

    public function scopeForPlayerCount($query, int $playerCount)
    {
        return $query->where('min_players', '<=', $playerCount)
                    ->where(function ($q) use ($playerCount) {
                        $q->whereNull('max_players')
                          ->orWhere('max_players', '>=', $playerCount);
                    });
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopePopular($query)
    {
        return $query->orderBy('usage_count', 'desc');
    }

    public function scopeHighlyRated($query)
    {
        return $query->where('rating_count', '>=', 5)
                    ->orderBy('average_rating', 'desc');
    }

    // Accessors
    public function isApproved(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === 'approved',
        );
    }

    public function canBeUsed(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->is_public && $this->is_approved,
        );
    }

    public function displayDuration(): Attribute
    {
        return Attribute::make(
            get: function () {
                $minutes = $this->estimated_duration;
                if ($minutes < 60) {
                    return "{$minutes} Min";
                } else {
                    $hours = floor($minutes / 60);
                    $remainingMinutes = $minutes % 60;
                    return $remainingMinutes > 0 
                        ? "{$hours}h {$remainingMinutes}m" 
                        : "{$hours}h";
                }
            },
        );
    }

    public function playerCountRange(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->max_players) {
                    return "{$this->min_players}-{$this->max_players} Spieler";
                } else {
                    return "{$this->min_players}+ Spieler";
                }
            },
        );
    }

    public function categoryDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $categories = [
                    'ball_handling' => 'Ballhandling',
                    'shooting' => 'Wurf',
                    'passing' => 'Passen',
                    'defense' => 'Verteidigung',
                    'rebounding' => 'Rebound',
                    'conditioning' => 'Kondition',
                    'agility' => 'Beweglichkeit',
                    'footwork' => 'Beinarbeit',
                    'team_offense' => 'Team-Offense',
                    'team_defense' => 'Team-Defense',
                    'transition' => 'Transition',
                    'set_plays' => 'Spielzüge',
                    'scrimmage' => 'Scrimmage',
                    'warm_up' => 'Aufwärmen',
                    'cool_down' => 'Abwärmen',
                ];
                
                return $categories[$this->category] ?? $this->category;
            },
        );
    }

    // Scout Search
    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'objectives' => $this->objectives,
            'category' => $this->category,
            'difficulty_level' => $this->difficulty_level,
            'age_group' => $this->age_group,
            'tags' => $this->tags,
            'search_keywords' => $this->search_keywords,
            'author' => $this->author,
        ];
    }

    // Media Collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('diagrams')
              ->singleFile()
              ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/svg+xml']);

        $this->addMediaCollection('videos')
              ->singleFile()
              ->acceptsMimeTypes(['video/mp4', 'video/webm', 'video/quicktime']);

        $this->addMediaCollection('thumbnails')
              ->singleFile()
              ->acceptsMimeTypes(['image/jpeg', 'image/png']);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
              ->width(300)
              ->height(200)
              ->performOnCollections('diagrams', 'thumbnails');

        $this->addMediaConversion('preview')
              ->width(150)
              ->height(100)
              ->performOnCollections('videos');
    }

    // Helper Methods
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function addRating(int $rating, ?string $comment = null, ?int $userId = null): void
    {
        $this->ratings()->create([
            'user_id' => $userId ?? auth()->id(),
            'rating' => $rating,
            'comment' => $comment,
        ]);

        $this->recalculateAverageRating();
    }

    public function recalculateAverageRating(): void
    {
        $ratings = $this->ratings();
        
        $this->update([
            'average_rating' => $ratings->avg('rating'),
            'rating_count' => $ratings->count(),
        ]);
    }

    public function duplicate(?int $userId = null): self
    {
        $duplicate = $this->replicate();
        $duplicate->name = $this->name . ' (Kopie)';
        $duplicate->created_by_user_id = $userId ?? auth()->id();
        $duplicate->status = 'draft';
        $duplicate->is_public = false;
        $duplicate->usage_count = 0;
        $duplicate->average_rating = null;
        $duplicate->rating_count = 0;
        $duplicate->save();

        // Copy media files
        foreach ($this->getMedia() as $media) {
            $media->copy($duplicate);
        }

        return $duplicate;
    }

    public function getSimilarDrills(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('id', '!=', $this->id)
                  ->where('category', $this->category)
                  ->where('status', 'approved')
                  ->where('is_public', true)
                  ->where('difficulty_level', $this->difficulty_level)
                  ->orderBy('average_rating', 'desc')
                  ->orderBy('usage_count', 'desc')
                  ->limit($limit)
                  ->get();
    }

    public function isApplicableForTeam(Team $team): bool
    {
        // Check age group compatibility
        if ($this->age_group !== 'all') {
            if ($team->category !== $this->age_group) {
                return false;
            }
        }

        // Check player count
        $teamPlayerCount = $team->activePlayers()->count();
        if ($teamPlayerCount < $this->min_players) {
            return false;
        }

        if ($this->max_players && $teamPlayerCount > $this->max_players) {
            return false;
        }

        return true;
    }
}
```

### Training Service

```php
<?php
// app/Services/TrainingService.php

namespace App\Services;

use App\Models\TrainingSession;
use App\Models\Drill;
use App\Models\Team;
use App\Models\Player;
use App\Models\TrainingAttendance;
use App\Jobs\GenerateTrainingReport;
use App\Jobs\SendTrainingReminders;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TrainingService
{
    public function createTrainingSession(array $data): TrainingSession
    {
        return DB::transaction(function () use ($data) {
            $session = TrainingSession::create($data);

            // Auto-add default drills based on session type and focus
            if (isset($data['auto_add_drills']) && $data['auto_add_drills']) {
                $this->addDefaultDrills($session);
            }

            // Schedule reminder notifications
            if ($session->notification_settings['send_reminders'] ?? true) {
                $this->scheduleReminders($session);
            }

            return $session;
        });
    }

    public function updateTrainingSession(TrainingSession $session, array $data): TrainingSession
    {
        return DB::transaction(function () use ($session, $data) {
            $session->update($data);

            // Update reminders if date changed
            if ($session->wasChanged('scheduled_at')) {
                $this->rescheduleReminders($session);
            }

            return $session;
        });
    }

    public function addDrillToSession(TrainingSession $session, int $drillId, array $config = []): void
    {
        $drill = Drill::findOrFail($drillId);
        
        // Validate drill compatibility
        if (!$drill->isApplicableForTeam($session->team)) {
            throw new \Exception('Drill ist nicht für dieses Team geeignet.');
        }

        $session->addDrill($drill, $config);
        $drill->incrementUsage();
    }

    public function removeDrillFromSession(TrainingSession $session, int $drillId): void
    {
        $drill = Drill::findOrFail($drillId);
        $session->removeDrill($drill);
    }

    public function reorderSessionDrills(TrainingSession $session, array $drillOrder): void
    {
        DB::transaction(function () use ($session, $drillOrder) {
            foreach ($drillOrder as $index => $drillId) {
                $session->drills()->updateExistingPivot($drillId, [
                    'order_in_session' => $index + 1
                ]);
            }
        });
    }

    public function startTrainingSession(TrainingSession $session): TrainingSession
    {
        if (!$session->canStart()) {
            throw new \Exception('Training kann nicht gestartet werden.');
        }

        $session->start();

        // Mark attendance for present players
        $this->initializeAttendance($session);

        return $session;
    }

    public function completeTrainingSession(TrainingSession $session, array $completionData = []): TrainingSession
    {
        if (!$session->canComplete()) {
            throw new \Exception('Training kann nicht abgeschlossen werden.');
        }

        DB::transaction(function () use ($session, $completionData) {
            $session->complete();

            // Update session with completion data
            if (!empty($completionData)) {
                $session->update($completionData);
            }

            // Generate training report
            GenerateTrainingReport::dispatch($session);
        });

        return $session;
    }

    public function recordDrillPerformance(TrainingSession $session, int $drillId, array $performanceData): void
    {
        $session->drills()->updateExistingPivot($drillId, array_merge($performanceData, [
            'status' => 'completed'
        ]));
    }

    public function markAttendance(TrainingSession $session, int $playerId, string $status, ?string $notes = null): void
    {
        TrainingAttendance::updateOrCreate(
            [
                'training_session_id' => $session->id,
                'player_id' => $playerId,
            ],
            [
                'status' => $status,
                'arrival_time' => $status === 'present' ? now() : null,
                'notes' => $notes,
                'recorded_by_user_id' => auth()->id(),
            ]
        );
    }

    public function bulkMarkAttendance(TrainingSession $session, array $attendanceData): void
    {
        DB::transaction(function () use ($session, $attendanceData) {
            foreach ($attendanceData as $playerId => $data) {
                $this->markAttendance($session, $playerId, $data['status'], $data['notes'] ?? null);
            }
        });
    }

    public function getTrainingPlan(Team $team, Carbon $startDate, Carbon $endDate): array
    {
        $sessions = TrainingSession::where('team_id', $team->id)
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->with(['drills', 'attendance.player'])
            ->orderBy('scheduled_at')
            ->get();

        return [
            'sessions' => $sessions,
            'total_sessions' => $sessions->count(),
            'completed_sessions' => $sessions->where('status', 'completed')->count(),
            'total_training_hours' => $sessions->sum('actual_duration') / 60,
            'average_attendance' => $sessions->avg('attendance_rate'),
            'focus_areas_covered' => $this->analyzeFocusAreas($sessions),
        ];
    }

    public function getPlayerTrainingStats(Player $player, string $season): array
    {
        $sessions = TrainingSession::whereHas('team', function ($query) use ($player) {
                $query->where('id', $player->team_id);
            })
            ->whereHas('attendance', function ($query) use ($player) {
                $query->where('player_id', $player->id);
            })
            ->with(['attendance' => function ($query) use ($player) {
                $query->where('player_id', $player->id);
            }])
            ->get();

        $attendance = $sessions->pluck('attendance')->flatten();

        return [
            'total_sessions' => $sessions->count(),
            'attended_sessions' => $attendance->where('status', 'present')->count(),
            'absent_sessions' => $attendance->where('status', 'absent')->count(),
            'late_arrivals' => $attendance->where('status', 'late')->count(),
            'attendance_rate' => $sessions->count() > 0 
                ? round(($attendance->where('status', 'present')->count() / $sessions->count()) * 100, 1) 
                : 0,
            'training_hours' => $sessions->sum('actual_duration') / 60,
            'average_session_rating' => $sessions->where('overall_rating', '>', 0)->avg('overall_rating'),
        ];
    }

    public function recommendDrills(Team $team, array $criteria = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Drill::public()
            ->byAgeGroup($team->category)
            ->forPlayerCount($team->activePlayers()->count());

        // Apply criteria filters
        if (isset($criteria['category'])) {
            $query->byCategory($criteria['category']);
        }

        if (isset($criteria['difficulty'])) {
            $query->byDifficulty($criteria['difficulty']);
        }

        if (isset($criteria['duration'])) {
            $query->where('estimated_duration', '<=', $criteria['duration']);
        }

        if (isset($criteria['focus_areas'])) {
            $query->whereJsonContains('tags', $criteria['focus_areas']);
        }

        return $query->highlyRated()
                    ->limit($criteria['limit'] ?? 10)
                    ->get();
    }

    public function generateSessionTemplate(Team $team, string $sessionType, int $duration = 90): array
    {
        $template = [];
        
        switch ($sessionType) {
            case 'training':
                $template = [
                    'warm_up' => $this->getWarmupDrills($team, 15),
                    'skill_development' => $this->getSkillDrills($team, 45),
                    'conditioning' => $this->getConditioningDrills($team, 20),
                    'cool_down' => $this->getCooldownDrills($team, 10),
                ];
                break;
                
            case 'tactical':
                $template = [
                    'warm_up' => $this->getWarmupDrills($team, 10),
                    'tactical_drills' => $this->getTacticalDrills($team, 60),
                    'scrimmage' => $this->getScrimmageOptions($team, 15),
                    'review' => $this->getReviewActivities($team, 5),
                ];
                break;
                
            case 'conditioning':
                $template = [
                    'warm_up' => $this->getWarmupDrills($team, 10),
                    'cardio' => $this->getCardioDrills($team, 30),
                    'strength' => $this->getStrengthDrills($team, 30),
                    'flexibility' => $this->getFlexibilityDrills($team, 20),
                ];
                break;
        }

        return $template;
    }

    private function addDefaultDrills(TrainingSession $session): void
    {
        $drills = $this->recommendDrills($session->team, [
            'category' => $session->focus_areas[0] ?? null,
            'duration' => 15,
            'limit' => 5,
        ]);

        foreach ($drills as $index => $drill) {
            $session->addDrill($drill, [
                'order_in_session' => $index + 1,
                'planned_duration' => $drill->estimated_duration,
            ]);
        }
    }

    private function scheduleReminders(TrainingSession $session): void
    {
        // Send reminder 24 hours before
        SendTrainingReminders::dispatch($session)
            ->delay($session->scheduled_at->subDay());
            
        // Send reminder 2 hours before
        SendTrainingReminders::dispatch($session)
            ->delay($session->scheduled_at->subHours(2));
    }

    private function rescheduleReminders(TrainingSession $session): void
    {
        // Cancel existing reminders and schedule new ones
        // Implementation would depend on your queue system
        $this->scheduleReminders($session);
    }

    private function initializeAttendance(TrainingSession $session): void
    {
        $players = $session->team->activePlayers;
        
        foreach ($players as $player) {
            TrainingAttendance::firstOrCreate([
                'training_session_id' => $session->id,
                'player_id' => $player->id,
            ], [
                'status' => 'unknown',
                'recorded_by_user_id' => auth()->id(),
            ]);
        }
    }

    private function analyzeFocusAreas(\Illuminate\Database\Eloquent\Collection $sessions): array
    {
        $focusAreas = [];
        
        foreach ($sessions as $session) {
            if ($session->focus_areas) {
                foreach ($session->focus_areas as $area) {
                    $focusAreas[$area] = ($focusAreas[$area] ?? 0) + 1;
                }
            }
        }
        
        arsort($focusAreas);
        
        return $focusAreas;
    }

    private function getWarmupDrills(Team $team, int $duration): \Illuminate\Database\Eloquent\Collection
    {
        return Drill::public()
            ->byCategory('warm_up')
            ->byAgeGroup($team->category)
            ->where('estimated_duration', '<=', $duration)
            ->popular()
            ->limit(3)
            ->get();
    }

    private function getSkillDrills(Team $team, int $duration): \Illuminate\Database\Eloquent\Collection
    {
        return Drill::public()
            ->whereIn('category', ['ball_handling', 'shooting', 'passing'])
            ->byAgeGroup($team->category)
            ->where('estimated_duration', '<=', $duration / 3)
            ->highlyRated()
            ->limit(3)
            ->get();
    }

    private function getConditioningDrills(Team $team, int $duration): \Illuminate\Database\Eloquent\Collection
    {
        return Drill::public()
            ->byCategory('conditioning')
            ->byAgeGroup($team->category)
            ->where('estimated_duration', '<=', $duration)
            ->popular()
            ->limit(2)
            ->get();
    }

    private function getCooldownDrills(Team $team, int $duration): \Illuminate\Database\Eloquent\Collection
    {
        return Drill::public()
            ->byCategory('cool_down')
            ->byAgeGroup($team->category)
            ->where('estimated_duration', '<=', $duration)
            ->limit(2)
            ->get();
    }

    // Additional helper methods for other drill types...
    private function getTacticalDrills(Team $team, int $duration): \Illuminate\Database\Eloquent\Collection
    {
        return Drill::public()
            ->whereIn('category', ['team_offense', 'team_defense', 'set_plays'])
            ->byAgeGroup($team->category)
            ->highlyRated()
            ->limit(4)
            ->get();
    }

    private function getScrimmageOptions(Team $team, int $duration): \Illuminate\Database\Eloquent\Collection
    {
        return Drill::public()
            ->byCategory('scrimmage')
            ->byAgeGroup($team->category)
            ->where('estimated_duration', '<=', $duration)
            ->limit(2)
            ->get();
    }

    private function getReviewActivities(Team $team, int $duration): \Illuminate\Database\Eloquent\Collection
    {
        return collect(); // Placeholder for review activities
    }

    private function getCardioDrills(Team $team, int $duration): \Illuminate\Database\Eloquent\Collection
    {
        return Drill::public()
            ->whereJsonContains('tags', 'cardio')
            ->byAgeGroup($team->category)
            ->where('estimated_duration', '<=', $duration / 2)
            ->limit(2)
            ->get();
    }

    private function getStrengthDrills(Team $team, int $duration): \Illuminate\Database\Eloquent\Collection
    {
        return Drill::public()
            ->whereJsonContains('tags', 'strength')
            ->byAgeGroup($team->category)
            ->where('estimated_duration', '<=', $duration / 2)
            ->limit(2)
            ->get();
    }

    private function getFlexibilityDrills(Team $team, int $duration): \Illuminate\Database\Eloquent\Collection
    {
        return Drill::public()
            ->whereJsonContains('tags', 'flexibility')
            ->byAgeGroup($team->category)
            ->where('estimated_duration', '<=', $duration)
            ->limit(2)
            ->get();
    }
}
```

---

## 🏆 Tournament Management System

### Tournament Models & Database Design

#### Tournaments Migration

```php
<?php
// database/migrations/2024_03_10_000000_create_tournaments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->constrained('users');
            $table->foreignId('club_id')->nullable()->constrained();
            
            // Basic Information
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            
            // Tournament Type and Format
            $table->enum('type', [
                'single_elimination', 'double_elimination', 'round_robin',
                'swiss_system', 'group_stage_knockout', 'league'
            ]);
            $table->enum('category', [
                'U8', 'U10', 'U12', 'U14', 'U16', 'U18', 'U20',
                'Herren', 'Damen', 'Senioren', 'Mixed', 'Open'
            ]);
            $table->enum('gender', ['male', 'female', 'mixed']);
            
            // Dates and Schedule
            $table->date('start_date');
            $table->date('end_date');
            $table->date('registration_start');
            $table->date('registration_end');
            $table->time('daily_start_time')->default('09:00');
            $table->time('daily_end_time')->default('18:00');
            
            // Participation
            $table->integer('min_teams')->default(4);
            $table->integer('max_teams')->default(32);
            $table->integer('registered_teams')->default(0);
            $table->decimal('entry_fee', 8, 2)->nullable();
            $table->string('currency', 3)->default('EUR');
            
            // Venue Information
            $table->string('primary_venue');
            $table->text('venue_address')->nullable();
            $table->json('additional_venues')->nullable();
            $table->integer('available_courts')->default(1);
            
            // Game Rules and Settings
            $table->json('game_rules')->nullable();
            $table->integer('game_duration')->default(40); // minutes
            $table->integer('periods')->default(4);
            $table->integer('period_length')->default(10); // minutes
            $table->boolean('overtime_enabled')->default(true);
            $table->integer('overtime_length')->default(5);
            $table->boolean('shot_clock_enabled')->default(true);
            $table->integer('shot_clock_seconds')->default(24);
            
            // Tournament Structure
            $table->integer('groups_count')->nullable(); // For group stage tournaments
            $table->json('seeding_rules')->nullable();
            $table->boolean('third_place_game')->default(true);
            $table->json('advancement_rules')->nullable();
            
            // Prizes and Awards
            $table->json('prizes')->nullable(); // 1st, 2nd, 3rd place prizes
            $table->json('awards')->nullable(); // MVP, Best Player, etc.
            $table->decimal('total_prize_money', 10, 2)->nullable();
            
            // Status and Workflow
            $table->enum('status', [
                'draft', 'registration_open', 'registration_closed',
                'in_progress', 'completed', 'cancelled'
            ])->default('draft');
            
            $table->boolean('is_public')->default(true);
            $table->boolean('requires_approval')->default(false);
            $table->boolean('allows_spectators')->default(true);
            $table->decimal('spectator_fee', 6, 2)->nullable();
            
            // Streaming and Media
            $table->boolean('livestream_enabled')->default(false);
            $table->string('livestream_url')->nullable();
            $table->json('social_media_links')->nullable();
            $table->boolean('photography_allowed')->default(true);
            
            // Contact and Support
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('special_instructions')->nullable();
            $table->text('covid_requirements')->nullable();
            
            // Statistics and Analytics
            $table->integer('total_games')->default(0);
            $table->integer('completed_games')->default(0);
            $table->decimal('average_game_duration', 5, 2)->nullable();
            $table->integer('total_spectators')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['status', 'start_date']);
            $table->index(['category', 'gender']);
            $table->index(['registration_start', 'registration_end']);
            $table->index('is_public');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
```

#### Tournament Teams Migration

```php
<?php
// database/migrations/2024_03_11_000000_create_tournament_teams_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->constrained();
            
            // Registration Information
            $table->timestamp('registered_at');
            $table->foreignId('registered_by_user_id')->constrained('users');
            $table->text('registration_notes')->nullable();
            
            // Status
            $table->enum('status', [
                'pending', 'approved', 'rejected', 'withdrawn', 'disqualified'
            ])->default('pending');
            $table->text('status_reason')->nullable();
            $table->timestamp('status_updated_at')->nullable();
            
            // Tournament Position
            $table->integer('seed')->nullable(); // Seeding position
            $table->string('group_name')->nullable(); // For group stage tournaments
            $table->integer('group_position')->nullable();
            
            // Performance Tracking
            $table->integer('games_played')->default(0);
            $table->integer('wins')->default(0);
            $table->integer('losses')->default(0);
            $table->integer('points_for')->default(0);
            $table->integer('points_against')->default(0);
            $table->integer('tournament_points')->default(0); // Points in tournament standings
            
            // Final Results
            $table->integer('final_position')->nullable();
            $table->enum('elimination_round', [
                'group_stage', 'round_of_32', 'round_of_16', 'quarterfinal',
                'semifinal', 'final', 'winner'
            ])->nullable();
            
            // Financial
            $table->boolean('entry_fee_paid')->default(false);
            $table->timestamp('payment_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->decimal('prize_money', 8, 2)->nullable();
            
            // Contact and Logistics
            $table->string('contact_person');
            $table->string('contact_email');
            $table->string('contact_phone');
            $table->text('special_requirements')->nullable();
            $table->json('travel_information')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['tournament_id', 'status']);
            $table->index(['tournament_id', 'seed']);
            $table->index(['tournament_id', 'group_name']);
            $table->unique(['tournament_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_teams');
    }
};
```

#### Tournament Brackets Migration

```php
<?php
// database/migrations/2024_03_12_000000_create_tournament_brackets_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_brackets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            $table->foreignId('game_id')->constrained();
            
            // Bracket Structure
            $table->string('bracket_type')->default('main'); // main, consolation, third_place
            $table->integer('round'); // 1 = First round, 2 = Second round, etc.
            $table->string('round_name'); // "Round of 16", "Quarterfinal", etc.
            $table->integer('position_in_round'); // Position within the round
            $table->integer('total_rounds'); // Total rounds in this bracket
            
            // Team Progression
            $table->foreignId('team1_id')->nullable()->constrained('tournament_teams');
            $table->foreignId('team2_id')->nullable()->constrained('tournament_teams');
            $table->foreignId('winner_team_id')->nullable()->constrained('tournament_teams');
            $table->foreignId('loser_team_id')->nullable()->constrained('tournament_teams');
            
            // Advancement Rules
            $table->foreignId('winner_advances_to')->nullable()->constrained('tournament_brackets');
            $table->foreignId('loser_advances_to')->nullable()->constrained('tournament_brackets');
            
            // Game Details
            $table->dateTime('scheduled_at')->nullable();
            $table->string('venue')->nullable();
            $table->string('court')->nullable();
            $table->string('referee')->nullable();
            
            // Status
            $table->enum('status', [
                'pending', 'scheduled', 'in_progress', 'completed', 'bye'
            ])->default('pending');
            
            // Seeding Information
            $table->integer('team1_seed')->nullable();
            $table->integer('team2_seed')->nullable();
            
            // Results
            $table->integer('team1_score')->nullable();
            $table->integer('team2_score')->nullable();
            $table->json('score_by_period')->nullable();
            $table->boolean('overtime')->default(false);
            $table->text('game_notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['tournament_id', 'bracket_type', 'round']);
            $table->index(['tournament_id', 'status']);
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_brackets');
    }
};
```

### Tournament Model Implementation

```php
<?php
// app/Models/Tournament.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Carbon\Carbon;

class Tournament extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'organizer_id',
        'club_id',
        'name',
        'description',
        'logo_path',
        'type',
        'category',
        'gender',
        'start_date',
        'end_date',
        'registration_start',
        'registration_end',
        'daily_start_time',
        'daily_end_time',
        'min_teams',
        'max_teams',
        'registered_teams',
        'entry_fee',
        'currency',
        'primary_venue',
        'venue_address',
        'additional_venues',
        'available_courts',
        'game_rules',
        'game_duration',
        'periods',
        'period_length',
        'overtime_enabled',
        'overtime_length',
        'shot_clock_enabled',
        'shot_clock_seconds',
        'groups_count',
        'seeding_rules',
        'third_place_game',
        'advancement_rules',
        'prizes',
        'awards',
        'total_prize_money',
        'status',
        'is_public',
        'requires_approval',
        'allows_spectators',
        'spectator_fee',
        'livestream_enabled',
        'livestream_url',
        'social_media_links',
        'photography_allowed',
        'contact_email',
        'contact_phone',
        'special_instructions',
        'covid_requirements',
        'total_games',
        'completed_games',
        'average_game_duration',
        'total_spectators',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_start' => 'date',
        'registration_end' => 'date',
        'daily_start_time' => 'datetime:H:i',
        'daily_end_time' => 'datetime:H:i',
        'min_teams' => 'integer',
        'max_teams' => 'integer',
        'registered_teams' => 'integer',
        'entry_fee' => 'decimal:2',
        'additional_venues' => 'array',
        'available_courts' => 'integer',
        'game_rules' => 'array',
        'game_duration' => 'integer',
        'periods' => 'integer',
        'period_length' => 'integer',
        'overtime_enabled' => 'boolean',
        'overtime_length' => 'integer',
        'shot_clock_enabled' => 'boolean',
        'shot_clock_seconds' => 'integer',
        'groups_count' => 'integer',
        'seeding_rules' => 'array',
        'third_place_game' => 'boolean',
        'advancement_rules' => 'array',
        'prizes' => 'array',
        'awards' => 'array',
        'total_prize_money' => 'decimal:2',
        'is_public' => 'boolean',
        'requires_approval' => 'boolean',
        'allows_spectators' => 'boolean',
        'spectator_fee' => 'decimal:2',
        'livestream_enabled' => 'boolean',
        'social_media_links' => 'array',
        'photography_allowed' => 'boolean',
        'total_games' => 'integer',
        'completed_games' => 'integer',
        'average_game_duration' => 'decimal:2',
        'total_spectators' => 'integer',
    ];

    // Relationships
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'tournament_teams')
                    ->withPivot([
                        'registered_at', 'registered_by_user_id', 'registration_notes',
                        'status', 'status_reason', 'status_updated_at',
                        'seed', 'group_name', 'group_position',
                        'games_played', 'wins', 'losses',
                        'points_for', 'points_against', 'tournament_points',
                        'final_position', 'elimination_round',
                        'entry_fee_paid', 'payment_date', 'payment_method',
                        'prize_money', 'contact_person', 'contact_email',
                        'contact_phone', 'special_requirements', 'travel_information'
                    ])
                    ->withTimestamps();
    }

    public function approvedTeams(): BelongsToMany
    {
        return $this->teams()->wherePivot('status', 'approved');
    }

    public function brackets(): HasMany
    {
        return $this->hasMany(TournamentBracket::class);
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeRegistrationOpen($query)
    {
        return $query->where('status', 'registration_open')
                    ->where('registration_start', '<=', now())
                    ->where('registration_end', '>=', now());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now());
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByGender($query, string $gender)
    {
        return $query->where('gender', $gender);
    }

    // Accessors
    public function isRegistrationOpen(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === 'registration_open' &&
                        $this->registration_start <= now() &&
                        $this->registration_end >= now(),
        );
    }

    public function canAcceptTeams(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->is_registration_open && 
                        $this->registered_teams < $this->max_teams,
        );
    }

    public function daysUntilStart(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->start_date > now() 
                ? now()->diffInDays($this->start_date)
                : 0,
        );
    }

    public function duration(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->start_date->diffInDays($this->end_date) + 1,
        );
    }

    public function progressPercentage(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->total_games > 0 
                ? round(($this->completed_games / $this->total_games) * 100, 1)
                : 0,
        );
    }

    public function typeDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $types = [
                    'single_elimination' => 'Single Elimination',
                    'double_elimination' => 'Double Elimination',
                    'round_robin' => 'Jeder gegen Jeden',
                    'swiss_system' => 'Schweizer System',
                    'group_stage_knockout' => 'Gruppenphase + K.O.',
                    'league' => 'Liga',
                ];
                
                return $types[$this->type] ?? $this->type;
            },
        );
    }

    public function statusDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $statuses = [
                    'draft' => 'Entwurf',
                    'registration_open' => 'Anmeldung offen',
                    'registration_closed' => 'Anmeldung geschlossen',
                    'in_progress' => 'Läuft',
                    'completed' => 'Abgeschlossen',
                    'cancelled' => 'Abgesagt',
                ];
                
                return $statuses[$this->status] ?? $this->status;
            },
        );
    }

    // Media Collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
              ->singleFile()
              ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/svg+xml']);

        $this->addMediaCollection('documents')
              ->acceptsMimeTypes(['application/pdf', 'application/msword']);

        $this->addMediaCollection('photos')
              ->acceptsMimeTypes(['image/jpeg', 'image/png']);
    }

    // Helper Methods
    public function canRegisterTeam(Team $team): bool
    {
        // Check if registration is open
        if (!$this->can_accept_teams) {
            return false;
        }

        // Check if team is already registered
        if ($this->teams()->where('team_id', $team->id)->exists()) {
            return false;
        }

        // Check category compatibility
        if ($this->category !== 'Open' && $this->category !== $team->category) {
            return false;
        }

        // Check gender compatibility
        if ($this->gender !== 'mixed' && $this->gender !== $team->gender) {
            return false;
        }

        return true;
    }

    public function registerTeam(Team $team, array $registrationData): void
    {
        if (!$this->canRegisterTeam($team)) {
            throw new \Exception('Team kann nicht registriert werden.');
        }

        $this->teams()->attach($team->id, array_merge([
            'registered_at' => now(),
            'registered_by_user_id' => auth()->id(),
            'status' => $this->requires_approval ? 'pending' : 'approved',
        ], $registrationData));

        $this->increment('registered_teams');
    }

    public function approveTeam(int $teamId): void
    {
        $this->teams()->updateExistingPivot($teamId, [
            'status' => 'approved',
            'status_updated_at' => now(),
        ]);
    }

    public function rejectTeam(int $teamId, string $reason): void
    {
        $this->teams()->updateExistingPivot($teamId, [
            'status' => 'rejected',
            'status_reason' => $reason,
            'status_updated_at' => now(),
        ]);

        $this->decrement('registered_teams');
    }

    public function generateSeeds(): void
    {
        $teams = $this->approvedTeams()->get();
        
        // Simple seeding based on team statistics or random
        $teams->each(function ($team, $index) {
            $this->teams()->updateExistingPivot($team->id, [
                'seed' => $index + 1
            ]);
        });
    }

    public function generateBrackets(): void
    {
        switch ($this->type) {
            case 'single_elimination':
                $this->generateSingleEliminationBracket();
                break;
            case 'double_elimination':
                $this->generateDoubleEliminationBracket();
                break;
            case 'round_robin':
                $this->generateRoundRobinBracket();
                break;
            case 'group_stage_knockout':
                $this->generateGroupStageKnockoutBracket();
                break;
        }
    }

    public function updateStandings(): void
    {
        $teams = $this->approvedTeams()->get();
        
        foreach ($teams as $team) {
            $stats = $this->calculateTeamStats($team);
            
            $this->teams()->updateExistingPivot($team->id, [
                'games_played' => $stats['games_played'],
                'wins' => $stats['wins'],
                'losses' => $stats['losses'],
                'points_for' => $stats['points_for'],
                'points_against' => $stats['points_against'],
                'tournament_points' => $this->calculateTournamentPoints($stats),
            ]);
        }
    }

    public function getStandings(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->approvedTeams()
                   ->orderBy('tournament_points', 'desc')
                   ->orderBy('wins', 'desc')
                   ->orderByRaw('(points_for - points_against) DESC')
                   ->get();
    }

    public function getCurrentRound(): ?int
    {
        return $this->brackets()
                   ->where('status', 'in_progress')
                   ->orWhere('status', 'scheduled')
                   ->min('round');
    }

    public function getScheduleForDate(Carbon $date): \Illuminate\Database\Eloquent\Collection
    {
        return $this->games()
                   ->whereDate('scheduled_at', $date)
                   ->with(['homeTeam', 'awayTeam'])
                   ->orderBy('scheduled_at')
                   ->get();
    }

    private function generateSingleEliminationBracket(): void
    {
        $teams = $this->approvedTeams()->orderBy('pivot_seed')->get();
        $teamCount = $teams->count();
        
        // Calculate number of rounds needed
        $rounds = ceil(log($teamCount, 2));
        
        // Create first round matchups
        $this->createFirstRoundMatchups($teams, 'single_elimination');
        
        // Create subsequent rounds
        for ($round = 2; $round <= $rounds; $round++) {
            $this->createSubsequentRound($round, 'single_elimination');
        }
        
        // Create third place game if enabled
        if ($this->third_place_game) {
            $this->createThirdPlaceGame();
        }
    }

    private function generateDoubleEliminationBracket(): void
    {
        // Implementation for double elimination
        // More complex bracket generation...
    }

    private function generateRoundRobinBracket(): void
    {
        $teams = $this->approvedTeams()->get();
        $teamCount = $teams->count();
        
        // Create all possible matchups
        for ($i = 0; $i < $teamCount; $i++) {
            for ($j = $i + 1; $j < $teamCount; $j++) {
                $this->createGame($teams[$i], $teams[$j], 1, 'round_robin');
            }
        }
    }

    private function generateGroupStageKnockoutBracket(): void
    {
        // First create groups
        $this->createGroups();
        
        // Generate round robin within each group
        $groups = $this->approvedTeams()->groupBy('pivot.group_name');
        
        foreach ($groups as $groupName => $teams) {
            $this->generateGroupMatches($teams, $groupName);
        }
        
        // Create knockout stage brackets
        $this->createKnockoutStageFromGroups();
    }

    private function createFirstRoundMatchups($teams, string $bracketType): void
    {
        $teamCount = $teams->count();
        $matchupsCount = $teamCount / 2;
        
        for ($i = 0; $i < $matchupsCount; $i++) {
            $team1 = $teams[$i];
            $team2 = $teams[$teamCount - 1 - $i];
            
            $this->createBracketEntry($team1, $team2, 1, $bracketType);
        }
    }

    private function createBracketEntry($team1, $team2, int $round, string $bracketType): TournamentBracket
    {
        // Create game first
        $game = Game::create([
            'tournament_id' => $this->id,
            'home_team_id' => $team1->id,
            'away_team_id' => $team2->id,
            'season' => date('Y') . '-' . (date('Y') + 1),
            'game_type' => 'tournament',
            'venue' => $this->primary_venue,
            'periods' => $this->periods,
            'period_length' => $this->period_length,
            'overtime_enabled' => $this->overtime_enabled,
            'overtime_length' => $this->overtime_length,
            'shot_clock_enabled' => $this->shot_clock_enabled,
            'shot_clock_seconds' => $this->shot_clock_seconds,
            'status' => 'scheduled',
        ]);

        return TournamentBracket::create([
            'tournament_id' => $this->id,
            'game_id' => $game->id,
            'bracket_type' => 'main',
            'round' => $round,
            'round_name' => $this->getRoundName($round),
            'position_in_round' => $this->getNextPositionInRound($round, $bracketType),
            'total_rounds' => $this->calculateTotalRounds(),
            'team1_id' => $team1->pivot->id,
            'team2_id' => $team2->pivot->id,
            'team1_seed' => $team1->pivot->seed,
            'team2_seed' => $team2->pivot->seed,
            'status' => 'scheduled',
        ]);
    }

    private function calculateTeamStats(Team $team): array
    {
        $games = $this->games()
                     ->where(function ($query) use ($team) {
                         $query->where('home_team_id', $team->id)
                               ->orWhere('away_team_id', $team->id);
                     })
                     ->where('status', 'finished')
                     ->get();

        $stats = [
            'games_played' => $games->count(),
            'wins' => 0,
            'losses' => 0,
            'points_for' => 0,
            'points_against' => 0,
        ];

        foreach ($games as $game) {
            $isHome = $game->home_team_id === $team->id;
            $teamScore = $isHome ? $game->final_score_home : $game->final_score_away;
            $opponentScore = $isHome ? $game->final_score_away : $game->final_score_home;

            $stats['points_for'] += $teamScore;
            $stats['points_against'] += $opponentScore;

            if ($teamScore > $opponentScore) {
                $stats['wins']++;
            } else {
                $stats['losses']++;
            }
        }

        return $stats;
    }

    private function calculateTournamentPoints(array $stats): int
    {
        // Standard tournament points: 2 for win, 1 for loss, 0 for forfeit
        return ($stats['wins'] * 2) + $stats['losses'];
    }

    private function getRoundName(int $round): string
    {
        $roundNames = [
            1 => 'Erste Runde',
            2 => 'Zweite Runde',
            3 => 'Achtelfinale',
            4 => 'Viertelfinale',
            5 => 'Halbfinale',
            6 => 'Finale',
        ];

        return $roundNames[$round] ?? "Runde {$round}";
    }

    private function getNextPositionInRound(int $round, string $bracketType): int
    {
        return $this->brackets()
                   ->where('round', $round)
                   ->where('bracket_type', $bracketType)
                   ->count() + 1;
    }

    private function calculateTotalRounds(): int
    {
        $teamCount = $this->approvedTeams()->count();
        return ceil(log($teamCount, 2));
    }

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status', 'registered_teams', 'start_date', 'end_date'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
```

### Tournament Service

```php
<?php
// app/Services/TournamentService.php

namespace App\Services;

use App\Models\Tournament;
use App\Models\Team;
use App\Models\TournamentBracket;
use App\Models\Game;
use App\Jobs\GenerateTournamentSchedule;
use App\Jobs\SendTournamentNotifications;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TournamentService
{
    public function createTournament(array $data): Tournament
    {
        return DB::transaction(function () use ($data) {
            $tournament = Tournament::create($data);

            // Create initial setup based on tournament type
            $this->initializeTournamentStructure($tournament);

            return $tournament;
        });
    }

    public function registerTeam(Tournament $tournament, Team $team, array $registrationData): void
    {
        if (!$tournament->canRegisterTeam($team)) {
            throw new \Exception('Team kann nicht für dieses Turnier registriert werden.');
        }

        DB::transaction(function () use ($tournament, $team, $registrationData) {
            $tournament->registerTeam($team, $registrationData);

            // Send confirmation email
            SendTournamentNotifications::dispatch($tournament, 'team_registered', $team);
        });
    }

    public function approveTeam(Tournament $tournament, int $teamId): void
    {
        DB::transaction(function () use ($tournament, $teamId) {
            $tournament->approveTeam($teamId);

            // Notify team of approval
            $team = Team::find($teamId);
            SendTournamentNotifications::dispatch($tournament, 'team_approved', $team);
        });
    }

    public function startTournament(Tournament $tournament): Tournament
    {
        if ($tournament->status !== 'registration_closed') {
            throw new \Exception('Turnier kann nicht gestartet werden. Status: ' . $tournament->status);
        }

        return DB::transaction(function () use ($tournament) {
            // Generate seeding
            $tournament->generateSeeds();

            // Generate brackets
            $tournament->generateBrackets();

            // Update status
            $tournament->update(['status' => 'in_progress']);

            // Generate initial schedule
            GenerateTournamentSchedule::dispatch($tournament);

            // Notify all participants
            SendTournamentNotifications::dispatch($tournament, 'tournament_started');

            return $tournament;
        });
    }

    public function advanceTournament(Tournament $tournament): void
    {
        DB::transaction(function () use ($tournament) {
            // Update standings
            $tournament->updateStandings();

            // Advance winners to next round
            $this->advanceWinners($tournament);

            // Check if tournament is complete
            if ($this->isTournamentComplete($tournament)) {
                $this->completeTournament($tournament);
            }
        });
    }

    public function generateSchedule(Tournament $tournament, Carbon $startDate = null): array
    {
        $startDate = $startDate ?? $tournament->start_date;
        $schedule = [];

        $brackets = $tournament->brackets()
                              ->with(['team1.team', 'team2.team', 'game'])
                              ->where('status', 'scheduled')
                              ->orderBy('round')
                              ->orderBy('position_in_round')
                              ->get();

        $currentDate = $startDate->copy();
        $gamesPerDay = $tournament->available_courts * 8; // Assuming 8 games per court per day
        $gameTime = $tournament->daily_start_time;
        $gameInterval = 90; // 90 minutes between games

        foreach ($brackets as $index => $bracket) {
            if ($index > 0 && $index % $gamesPerDay === 0) {
                $currentDate->addDay();
                $gameTime = $tournament->daily_start_time;
            }

            $scheduledTime = $currentDate->copy()->setTimeFromTimeString($gameTime);

            // Update game schedule
            $bracket->game->update([
                'scheduled_at' => $scheduledTime,
                'venue' => $this->assignVenue($tournament, $bracket),
            ]);

            $bracket->update([
                'scheduled_at' => $scheduledTime,
                'venue' => $bracket->game->venue,
            ]);

            $schedule[] = [
                'bracket' => $bracket,
                'date' => $scheduledTime->toDateString(),
                'time' => $scheduledTime->toTimeString(),
                'venue' => $bracket->game->venue,
            ];

            // Increment time for next game
            $gameTime = Carbon::parse($gameTime)->addMinutes($gameInterval)->toTimeString();
        }

        return $schedule;
    }

    public function getStandings(Tournament $tournament, string $format = 'overall'): array
    {
        switch ($format) {
            case 'groups':
                return $this->getGroupStandings($tournament);
            case 'bracket':
                return $this->getBracketStandings($tournament);
            default:
                return $this->getOverallStandings($tournament);
        }
    }

    public function getTournamentStatistics(Tournament $tournament): array
    {
        $teams = $tournament->approvedTeams()->get();
        $games = $tournament->games()->where('status', 'finished')->get();

        $stats = [
            'total_teams' => $teams->count(),
            'total_games' => $games->count(),
            'games_completed' => $games->count(),
            'games_remaining' => $tournament->total_games - $games->count(),
            'total_points_scored' => $games->sum(function ($game) {
                return $game->final_score_home + $game->final_score_away;
            }),
            'average_points_per_game' => 0,
            'highest_scoring_game' => null,
            'lowest_scoring_game' => null,
            'most_wins' => null,
            'best_offense' => null,
            'best_defense' => null,
        ];

        if ($games->count() > 0) {
            $stats['average_points_per_game'] = round($stats['total_points_scored'] / $games->count(), 1);
            
            $stats['highest_scoring_game'] = $games->sortByDesc(function ($game) {
                return $game->final_score_home + $game->final_score_away;
            })->first();
            
            $stats['lowest_scoring_game'] = $games->sortBy(function ($game) {
                return $game->final_score_home + $game->final_score_away;
            })->first();
        }

        // Team statistics
        $teamStats = [];
        foreach ($teams as $team) {
            $teamStats[] = array_merge([
                'team' => $team,
            ], $tournament->calculateTeamStats($team));
        }

        if (!empty($teamStats)) {
            $stats['most_wins'] = collect($teamStats)->sortByDesc('wins')->first();
            $stats['best_offense'] = collect($teamStats)->sortByDesc(function ($team) {
                return $team['games_played'] > 0 ? $team['points_for'] / $team['games_played'] : 0;
            })->first();
            $stats['best_defense'] = collect($teamStats)->sortBy(function ($team) {
                return $team['games_played'] > 0 ? $team['points_against'] / $team['games_played'] : 999;
            })->first();
        }

        return $stats;
    }

    public function exportTournamentData(Tournament $tournament, string $format = 'pdf'): string
    {
        $data = [
            'tournament' => $tournament,
            'standings' => $this->getStandings($tournament),
            'statistics' => $this->getTournamentStatistics($tournament),
            'schedule' => $this->getCompleteSchedule($tournament),
            'brackets' => $this->getBracketStructure($tournament),
        ];

        switch ($format) {
            case 'pdf':
                return $this->generatePdfReport($data);
            case 'excel':
                return $this->generateExcelReport($data);
            case 'json':
                return json_encode($data);
            default:
                throw new \Exception('Unsupported export format: ' . $format);
        }
    }

    private function initializeTournamentStructure(Tournament $tournament): void
    {
        // Set default game rules based on category
        $defaultRules = $this->getDefaultGameRules($tournament->category);
        $tournament->update(['game_rules' => $defaultRules]);

        // Initialize groups if needed
        if (in_array($tournament->type, ['group_stage_knockout']) && !$tournament->groups_count) {
            $tournament->update(['groups_count' => 4]); // Default to 4 groups
        }
    }

    private function advanceWinners(Tournament $tournament): void
    {
        $completedBrackets = $tournament->brackets()
                                       ->where('status', 'completed')
                                       ->whereNotNull('winner_team_id')
                                       ->whereNotNull('winner_advances_to')
                                       ->with(['winnerAdvancesTo'])
                                       ->get();

        foreach ($completedBrackets as $bracket) {
            $nextBracket = $bracket->winnerAdvancesTo;
            
            if ($nextBracket && !$nextBracket->team1_id) {
                $nextBracket->update(['team1_id' => $bracket->winner_team_id]);
            } elseif ($nextBracket && !$nextBracket->team2_id) {
                $nextBracket->update(['team2_id' => $bracket->winner_team_id]);
                
                // Both teams are now set, schedule the game
                $this->scheduleNextRoundGame($nextBracket);
            }

            // Handle loser advancement for double elimination
            if ($bracket->loser_advances_to && $bracket->loser_team_id) {
                $consolationBracket = TournamentBracket::find($bracket->loser_advances_to);
                if ($consolationBracket && !$consolationBracket->team1_id) {
                    $consolationBracket->update(['team1_id' => $bracket->loser_team_id]);
                } elseif ($consolationBracket && !$consolationBracket->team2_id) {
                    $consolationBracket->update(['team2_id' => $bracket->loser_team_id]);
                    $this->scheduleNextRoundGame($consolationBracket);
                }
            }
        }
    }

    private function isTournamentComplete(Tournament $tournament): bool
    {
        $finalBracket = $tournament->brackets()
                                  ->where('bracket_type', 'main')
                                  ->where('round', $tournament->calculateTotalRounds())
                                  ->first();

        return $finalBracket && $finalBracket->status === 'completed';
    }

    private function completeTournament(Tournament $tournament): void
    {
        $tournament->update(['status' => 'completed']);

        // Determine final positions
        $this->determineFinalPositions($tournament);

        // Award prizes
        $this->awardPrizes($tournament);

        // Send completion notifications
        SendTournamentNotifications::dispatch($tournament, 'tournament_completed');
    }

    private function assignVenue(Tournament $tournament, TournamentBracket $bracket): string
    {
        $venues = array_merge([$tournament->primary_venue], $tournament->additional_venues ?? []);
        
        // Simple round-robin venue assignment
        $venueIndex = $bracket->id % count($venues);
        
        return $venues[$venueIndex];
    }

    private function getDefaultGameRules(string $category): array
    {
        $rules = [
            'periods' => 4,
            'period_length' => 10,
            'overtime_enabled' => true,
            'overtime_length' => 5,
            'shot_clock_enabled' => true,
            'shot_clock_seconds' => 24,
            'timeouts_per_team' => 5,
            'foul_limit' => 5,
        ];

        // Adjust rules for youth categories
        if (in_array($category, ['U8', 'U10', 'U12'])) {
            $rules['period_length'] = 8;
            $rules['shot_clock_enabled'] = false;
        }

        return $rules;
    }

    private function getOverallStandings(Tournament $tournament): array
    {
        return $tournament->getStandings()->map(function ($team) use ($tournament) {
            return array_merge([
                'team' => $team,
                'position' => $team->pivot->final_position,
            ], $tournament->calculateTeamStats($team));
        })->toArray();
    }

    private function getGroupStandings(Tournament $tournament): array
    {
        $groups = $tournament->approvedTeams()->groupBy('pivot.group_name');
        $standings = [];

        foreach ($groups as $groupName => $teams) {
            $groupStandings = $teams->map(function ($team) use ($tournament) {
                return array_merge([
                    'team' => $team,
                ], $tournament->calculateTeamStats($team));
            })->sortByDesc('tournament_points')->values();

            $standings[$groupName] = $groupStandings;
        }

        return $standings;
    }

    private function getBracketStandings(Tournament $tournament): array
    {
        // Return bracket progression information
        return $tournament->brackets()
                         ->with(['team1.team', 'team2.team', 'winnerTeam.team'])
                         ->orderBy('round')
                         ->orderBy('position_in_round')
                         ->get()
                         ->groupBy('round')
                         ->toArray();
    }

    private function scheduleNextRoundGame(TournamentBracket $bracket): void
    {
        // Schedule logic for next round games
        $game = $bracket->game;
        $nextAvailableTime = $this->getNextAvailableTimeSlot($bracket->tournament);
        
        $game->update([
            'scheduled_at' => $nextAvailableTime,
            'status' => 'scheduled',
        ]);

        $bracket->update([
            'scheduled_at' => $nextAvailableTime,
            'status' => 'scheduled',
        ]);
    }

    private function getNextAvailableTimeSlot(Tournament $tournament): Carbon
    {
        $lastScheduledGame = $tournament->games()
                                       ->where('status', 'scheduled')
                                       ->orderBy('scheduled_at', 'desc')
                                       ->first();

        if ($lastScheduledGame) {
            return $lastScheduledGame->scheduled_at->addMinutes(90);
        }

        return $tournament->start_date->copy()->setTimeFromTimeString($tournament->daily_start_time);
    }

    private function determineFinalPositions(Tournament $tournament): void
    {
        // Determine winner (1st place)
        $finalBracket = $tournament->brackets()
                                  ->where('bracket_type', 'main')
                                  ->where('round', $tournament->calculateTotalRounds())
                                  ->first();

        if ($finalBracket && $finalBracket->winner_team_id) {
            $tournament->teams()->updateExistingPivot($finalBracket->winner_team_id, [
                'final_position' => 1,
                'elimination_round' => 'winner'
            ]);

            $tournament->teams()->updateExistingPivot($finalBracket->loser_team_id, [
                'final_position' => 2,
                'elimination_round' => 'final'
            ]);
        }

        // Determine 3rd place if third place game exists
        if ($tournament->third_place_game) {
            $thirdPlaceBracket = $tournament->brackets()
                                           ->where('bracket_type', 'third_place')
                                           ->first();

            if ($thirdPlaceBracket && $thirdPlaceBracket->winner_team_id) {
                $tournament->teams()->updateExistingPivot($thirdPlaceBracket->winner_team_id, [
                    'final_position' => 3
                ]);

                $tournament->teams()->updateExistingPivot($thirdPlaceBracket->loser_team_id, [
                    'final_position' => 4
                ]);
            }
        }

        // Determine other positions based on elimination round
        // This would be more complex logic based on tournament type
    }

    private function awardPrizes(Tournament $tournament): void
    {
        if (!$tournament->prizes) {
            return;
        }

        foreach ($tournament->prizes as $position => $prize) {
            $team = $tournament->teams()
                              ->wherePivot('final_position', $position)
                              ->first();

            if ($team && isset($prize['money'])) {
                $tournament->teams()->updateExistingPivot($team->id, [
                    'prize_money' => $prize['money']
                ]);
            }
        }
    }

    private function getCompleteSchedule(Tournament $tournament): array
    {
        return $tournament->games()
                         ->with(['homeTeam', 'awayTeam'])
                         ->orderBy('scheduled_at')
                         ->get()
                         ->groupBy(function ($game) {
                             return $game->scheduled_at->toDateString();
                         })
                         ->toArray();
    }

    private function getBracketStructure(Tournament $tournament): array
    {
        return $tournament->brackets()
                         ->with(['team1.team', 'team2.team', 'winnerTeam.team'])
                         ->orderBy('bracket_type')
                         ->orderBy('round')
                         ->orderBy('position_in_round')
                         ->get()
                         ->groupBy(['bracket_type', 'round'])
                         ->toArray();
    }

    private function generatePdfReport(array $data): string
    {
        // PDF generation logic using Laravel DomPDF
        // Return file path
        return storage_path('app/tournament_reports/tournament_' . $data['tournament']->id . '.pdf');
    }

    private function generateExcelReport(array $data): string
    {
        // Excel generation logic using Laravel Excel
        // Return file path
        return storage_path('app/tournament_reports/tournament_' . $data['tournament']->id . '.xlsx');
    }
}
```

---

---

## 🎥 Video Analysis Integration

### Video Management System

Phase 3 integriert ein umfassendes Video-Analyse-System, das KI-gestützte Annotations, automatische Spielerkennung und Frame-Level-Analysen ermöglicht.

#### Video Models & Database Design

##### Video Files Migration

```php
<?php
// database/migrations/2024_03_20_000000_create_video_files_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by_user_id')->constrained('users');
            $table->foreignId('team_id')->nullable()->constrained();
            $table->foreignId('game_id')->nullable()->constrained();
            $table->foreignId('training_session_id')->nullable()->constrained();
            
            // File Information
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('thumbnail_path')->nullable();
            $table->string('processed_path')->nullable(); // Optimized version
            
            // Video Properties
            $table->string('mime_type');
            $table->bigInteger('file_size'); // bytes
            $table->integer('duration'); // seconds
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->decimal('frame_rate', 5, 2)->nullable();
            $table->string('codec')->nullable();
            $table->integer('bitrate')->nullable();
            
            // Video Classification
            $table->enum('video_type', [
                'full_game', 'game_highlights', 'training_session', 'drill_demo',
                'player_analysis', 'tactical_analysis', 'scouting_report',
                'instructional', 'warm_up', 'cool_down', 'interview'
            ]);
            
            $table->enum('recording_angle', [
                'baseline', 'sideline', 'elevated', 'court_level', 'overhead',
                'corner', 'multiple_angles', 'mobile', 'fixed_camera'
            ])->nullable();
            
            $table->json('recording_equipment')->nullable(); // Camera specs, etc.
            $table->timestamp('recorded_at')->nullable();
            $table->string('recording_location')->nullable();
            
            // Processing Status
            $table->enum('processing_status', [
                'uploaded', 'queued', 'processing', 'completed', 'failed', 'archived'
            ])->default('uploaded');
            
            $table->json('processing_metadata')->nullable(); // FFmpeg output, etc.
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processing_completed_at')->nullable();
            $table->text('processing_error')->nullable();
            
            // AI Analysis
            $table->boolean('ai_analysis_enabled')->default(true);
            $table->enum('ai_analysis_status', [
                'pending', 'in_progress', 'completed', 'failed', 'disabled'
            ])->default('pending');
            
            $table->json('ai_analysis_results')->nullable(); // Detected plays, players, etc.
            $table->decimal('ai_confidence_score', 5, 4)->nullable(); // 0-1
            $table->timestamp('ai_analysis_completed_at')->nullable();
            
            // Access Control
            $table->enum('visibility', ['public', 'team_only', 'private', 'archived']);
            $table->boolean('downloadable')->default(false);
            $table->boolean('embeddable')->default(true);
            $table->json('sharing_permissions')->nullable();
            
            // Engagement Metrics
            $table->integer('view_count')->default(0);
            $table->integer('like_count')->default(0);
            $table->integer('share_count')->default(0);
            $table->integer('annotation_count')->default(0);
            $table->decimal('average_rating', 3, 2)->nullable();
            
            // Metadata and Tags
            $table->json('tags')->nullable();
            $table->json('custom_metadata')->nullable();
            $table->text('transcription')->nullable(); // AI-generated or manual
            $table->string('language', 5)->default('de');
            
            // Quality and Technical
            $table->enum('quality_rating', ['low', 'medium', 'high', 'excellent'])->nullable();
            $table->boolean('has_audio')->default(true);
            $table->boolean('has_subtitles')->default(false);
            $table->string('encoding_profile')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['team_id', 'video_type']);
            $table->index(['game_id', 'processing_status']);
            $table->index(['training_session_id', 'ai_analysis_status']);
            $table->index(['uploaded_by_user_id', 'visibility']);
            $table->index(['processing_status', 'created_at']);
            $table->index('recorded_at');
            $table->fullText(['title', 'description', 'tags']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_files');
    }
};
```

##### Video Annotations Migration

```php
<?php
// database/migrations/2024_03_21_000000_create_video_annotations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_annotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_file_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->foreignId('player_id')->nullable()->constrained();
            
            // Temporal Information
            $table->decimal('start_time', 8, 3); // seconds with millisecond precision
            $table->decimal('end_time', 8, 3)->nullable(); // null for point annotations
            $table->integer('frame_start')->nullable();
            $table->integer('frame_end')->nullable();
            
            // Annotation Type and Content
            $table->enum('annotation_type', [
                'play_action', 'player_highlight', 'tactical_note', 'mistake',
                'good_play', 'foul', 'timeout', 'substitution', 'coaching_point',
                'statistical_event', 'injury', 'technical_issue', 'custom'
            ]);
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('coaching_notes')->nullable();
            
            // Basketball-specific Data
            $table->enum('play_type', [
                'offense', 'defense', 'transition', 'set_play', 'fast_break',
                'rebound', 'shot', 'pass', 'dribble', 'screen', 'cut'
            ])->nullable();
            
            $table->enum('court_area', [
                'paint', 'three_point_line', 'free_throw_line', 'baseline',
                'sideline', 'center_court', 'backcourt', 'frontcourt'
            ])->nullable();
            
            $table->json('players_involved')->nullable(); // Array of player IDs
            $table->enum('outcome', ['successful', 'unsuccessful', 'neutral'])->nullable();
            $table->integer('points_scored')->nullable();
            
            // Visual Annotations
            $table->json('visual_markers')->nullable(); // Shapes, arrows, circles on video
            $table->json('coordinate_data')->nullable(); // X,Y coordinates for court positions
            $table->string('marker_color', 7)->default('#FF0000'); // Hex color
            $table->enum('marker_style', ['circle', 'rectangle', 'arrow', 'line', 'polygon'])->nullable();
            
            // AI/Manual Classification
            $table->boolean('is_ai_generated')->default(false);
            $table->decimal('ai_confidence', 5, 4)->nullable(); // 0-1
            $table->boolean('human_verified')->default(false);
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            
            // Status and Workflow
            $table->enum('status', ['draft', 'pending_review', 'approved', 'rejected']);
            $table->boolean('is_public')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->integer('priority')->default(0); // Higher = more important
            
            // Engagement and Learning
            $table->integer('helpful_votes')->default(0);
            $table->integer('view_count')->default(0);
            $table->json('linked_drills')->nullable(); // Related drill IDs
            $table->json('learning_objectives')->nullable();
            
            // Metadata
            $table->json('custom_data')->nullable();
            $table->string('external_reference')->nullable(); // Link to external systems
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['video_file_id', 'start_time']);
            $table->index(['player_id', 'annotation_type']);
            $table->index(['created_by_user_id', 'status']);
            $table->index(['is_ai_generated', 'human_verified']);
            $table->index(['annotation_type', 'play_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_annotations');
    }
};
```

##### Video Analysis Sessions Migration

```php
<?php
// database/migrations/2024_03_22_000000_create_video_analysis_sessions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_analysis_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_file_id')->constrained()->onDelete('cascade');
            $table->foreignId('analyst_user_id')->constrained('users');
            $table->foreignId('team_id')->nullable()->constrained();
            
            // Session Information
            $table->string('session_name');
            $table->text('session_description')->nullable();
            $table->text('analysis_objectives')->nullable();
            
            // Session Type and Focus
            $table->enum('analysis_type', [
                'player_performance', 'team_tactics', 'opponent_scouting',
                'drill_effectiveness', 'game_breakdown', 'skill_development',
                'injury_analysis', 'referee_decisions', 'custom_analysis'
            ]);
            
            $table->json('focus_areas')->nullable(); // What to analyze
            $table->json('analysis_criteria')->nullable(); // Specific metrics to track
            
            // Session Status and Progress
            $table->enum('status', [
                'planned', 'in_progress', 'paused', 'completed', 'cancelled'
            ])->default('planned');
            
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('total_duration')->nullable(); // minutes spent analyzing
            
            // Participants
            $table->json('invited_users')->nullable(); // User IDs who can participate
            $table->json('participant_roles')->nullable(); // Role assignments
            $table->boolean('allow_collaborative_editing')->default(true);
            
            // Analysis Results
            $table->json('key_findings')->nullable();
            $table->json('recommendations')->nullable();
            $table->json('action_items')->nullable();
            $table->text('summary_notes')->nullable();
            
            // Presentation and Sharing
            $table->boolean('presentation_ready')->default(false);
            $table->string('presentation_template')->nullable();
            $table->json('presentation_slides')->nullable();
            $table->boolean('is_shareable')->default(false);
            $table->json('sharing_settings')->nullable();
            
            // Integration with Training
            $table->json('linked_training_sessions')->nullable();
            $table->json('suggested_drills')->nullable();
            $table->json('improvement_plan')->nullable();
            
            // Quality and Review
            $table->enum('quality_rating', ['needs_work', 'good', 'excellent'])->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_comments')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['video_file_id', 'status']);
            $table->index(['analyst_user_id', 'analysis_type']);
            $table->index(['team_id', 'completed_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_analysis_sessions');
    }
};
```

### Video Models Implementation

#### VideoFile Model

```php
<?php
// app/Models/VideoFile.php

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
        'file_size' => 'integer',
        'duration' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'frame_rate' => 'decimal:2',
        'bitrate' => 'integer',
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

    public function scopeByType($query, string $type)
    {
        return $query->where('video_type', $type);
    }

    public function scopeWithAI($query)
    {
        return $query->where('ai_analysis_enabled', true);
    }

    public function scopeAIAnalyzed($query)
    {
        return $query->where('ai_analysis_status', 'completed');
    }

    // Accessors
    public function durationFormatted(): Attribute
    {
        return Attribute::make(
            get: function () {
                $minutes = floor($this->duration / 60);
                $seconds = $this->duration % 60;
                return sprintf('%02d:%02d', $minutes, $seconds);
            },
        );
    }

    public function fileSizeFormatted(): Attribute
    {
        return Attribute::make(
            get: function () {
                $bytes = $this->file_size;
                $units = ['B', 'KB', 'MB', 'GB', 'TB'];
                
                for ($i = 0; $bytes > 1024; $i++) {
                    $bytes /= 1024;
                }
                
                return round($bytes, 2) . ' ' . $units[$i];
            },
        );
    }

    public function isProcessed(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->processing_status === 'completed',
        );
    }

    public function isAIAnalyzed(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->ai_analysis_status === 'completed',
        );
    }

    public function aspectRatio(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->width || !$this->height) {
                    return null;
                }
                
                $gcd = $this->gcd($this->width, $this->height);
                return ($this->width / $gcd) . ':' . ($this->height / $gcd);
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
                    'training_session' => 'Trainingseinheit',
                    'drill_demo' => 'Übungs-Demo',
                    'player_analysis' => 'Spieler-Analyse',
                    'tactical_analysis' => 'Taktik-Analyse',
                    'scouting_report' => 'Scouting-Report',
                    'instructional' => 'Lehrvideos',
                    'warm_up' => 'Aufwärmen',
                    'cool_down' => 'Abwärmen',
                    'interview' => 'Interview',
                ];
                
                return $types[$this->video_type] ?? $this->video_type;
            },
        );
    }

    // Scout Search
    public function toSearchableArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'video_type' => $this->video_type,
            'tags' => $this->tags,
            'transcription' => $this->transcription,
        ];
    }

    // Media Collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnails')
              ->acceptsMimeTypes(['image/jpeg', 'image/png']);

        $this->addMediaCollection('processed_videos')
              ->singleFile()
              ->acceptsMimeTypes(['video/mp4', 'video/webm']);

        $this->addMediaCollection('subtitles')
              ->acceptsMimeTypes(['text/vtt', 'text/srt']);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('preview')
              ->width(640)
              ->height(360)
              ->performOnCollections('processed_videos')
              ->nonQueued();

        $this->addMediaConversion('thumbnail')
              ->width(300)
              ->height(200)
              ->performOnCollections('thumbnails')
              ->nonQueued();
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

    public function canBeViewedBy(User $user): bool
    {
        switch ($this->visibility) {
            case 'public':
                return true;
            case 'team_only':
                return $user->hasTeamAccess($this->team);
            case 'private':
                return $user->id === $this->uploaded_by_user_id;
            case 'archived':
                return $user->hasPermissionTo('view archived videos');
            default:
                return false;
        }
    }

    public function getStreamingUrl(): ?string
    {
        if (!$this->is_processed) {
            return null;
        }

        return $this->processed_path 
            ? Storage::url($this->processed_path)
            : Storage::url($this->file_path);
    }

    public function getThumbnailUrl(): ?string
    {
        if ($this->thumbnail_path) {
            return Storage::url($this->thumbnail_path);
        }

        return $this->getFirstMediaUrl('thumbnails', 'thumbnail');
    }

    public function generateThumbnail(): bool
    {
        if (!$this->is_processed) {
            return false;
        }

        // Use FFmpeg to generate thumbnail at 25% of video duration
        $thumbnailTime = $this->duration * 0.25;
        
        // Implementation would use FFmpeg
        return VideoProcessingService::generateThumbnail($this, $thumbnailTime);
    }

    public function getAIDetectedPlays(): array
    {
        if (!$this->is_ai_analyzed || !$this->ai_analysis_results) {
            return [];
        }

        return $this->ai_analysis_results['detected_plays'] ?? [];
    }

    public function getAIDetectedPlayers(): array
    {
        if (!$this->is_ai_analyzed || !$this->ai_analysis_results) {
            return [];
        }

        return $this->ai_analysis_results['detected_players'] ?? [];
    }

    public function startAIAnalysis(): void
    {
        if (!$this->is_processed || !$this->ai_analysis_enabled) {
            throw new \Exception('Video muss verarbeitet sein und AI-Analyse aktiviert haben.');
        }

        $this->update([
            'ai_analysis_status' => 'in_progress',
            'ai_analysis_completed_at' => null,
        ]);

        // Dispatch AI analysis job
        ProcessVideoAIAnalysisJob::dispatch($this);
    }

    public function completeAIAnalysis(array $results, float $confidence): void
    {
        $this->update([
            'ai_analysis_status' => 'completed',
            'ai_analysis_results' => $results,
            'ai_confidence_score' => $confidence,
            'ai_analysis_completed_at' => now(),
        ]);
    }

    public function createAnnotationFromAI(array $detectedEvent): VideoAnnotation
    {
        return $this->annotations()->create([
            'created_by_user_id' => 1, // System user
            'start_time' => $detectedEvent['start_time'],
            'end_time' => $detectedEvent['end_time'],
            'annotation_type' => $detectedEvent['type'],
            'title' => $detectedEvent['title'],
            'description' => $detectedEvent['description'],
            'play_type' => $detectedEvent['play_type'] ?? null,
            'players_involved' => $detectedEvent['players'] ?? null,
            'is_ai_generated' => true,
            'ai_confidence' => $detectedEvent['confidence'],
            'status' => 'pending_review',
        ]);
    }

    private function gcd(int $a, int $b): int
    {
        return $b ? $this->gcd($b, $a % $b) : $a;
    }

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'processing_status', 'ai_analysis_status', 'visibility'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
```

#### VideoAnnotation Model

```php
<?php
// app/Models/VideoAnnotation.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class VideoAnnotation extends Model
{
    use HasFactory, SoftDeletes;

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
        'helpful_votes',
        'view_count',
        'linked_drills',
        'learning_objectives',
        'custom_data',
        'external_reference',
    ];

    protected $casts = [
        'start_time' => 'decimal:3',
        'end_time' => 'decimal:3',
        'frame_start' => 'integer',
        'frame_end' => 'integer',
        'players_involved' => 'array',
        'points_scored' => 'integer',
        'visual_markers' => 'array',
        'coordinate_data' => 'array',
        'is_ai_generated' => 'boolean',
        'ai_confidence' => 'decimal:4',
        'human_verified' => 'boolean',
        'verified_at' => 'datetime',
        'is_public' => 'boolean',
        'is_featured' => 'boolean',
        'priority' => 'integer',
        'helpful_votes' => 'integer',
        'view_count' => 'integer',
        'linked_drills' => 'array',
        'learning_objectives' => 'array',
        'custom_data' => 'array',
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
        return $this->belongsTo(Player::class);
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

    public function scopeByType($query, string $type)
    {
        return $query->where('annotation_type', $type);
    }

    public function scopeAIGenerated($query)
    {
        return $query->where('is_ai_generated', true);
    }

    public function scopeHumanVerified($query)
    {
        return $query->where('human_verified', true);
    }

    public function scopeInTimeRange($query, float $startTime, float $endTime)
    {
        return $query->where(function ($q) use ($startTime, $endTime) {
            $q->whereBetween('start_time', [$startTime, $endTime])
              ->orWhereBetween('end_time', [$startTime, $endTime])
              ->orWhere(function ($q2) use ($startTime, $endTime) {
                  $q2->where('start_time', '<=', $startTime)
                     ->where('end_time', '>=', $endTime);
              });
        });
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByPlayer($query, int $playerId)
    {
        return $query->where('player_id', $playerId)
                    ->orWhereJsonContains('players_involved', $playerId);
    }

    // Accessors
    public function durationFormatted(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->end_time) {
                    return 'Zeitpunkt';
                }
                
                $duration = $this->end_time - $this->start_time;
                return round($duration, 1) . 's';
            },
        );
    }

    public function startTimeFormatted(): Attribute
    {
        return Attribute::make(
            get: function () {
                $minutes = floor($this->start_time / 60);
                $seconds = $this->start_time % 60;
                return sprintf('%02d:%05.2f', $minutes, $seconds);
            },
        );
    }

    public function endTimeFormatted(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->end_time) {
                    return null;
                }
                
                $minutes = floor($this->end_time / 60);
                $seconds = $this->end_time % 60;
                return sprintf('%02d:%05.2f', $minutes, $seconds);
            },
        );
    }

    public function isPointAnnotation(): Attribute
    {
        return Attribute::make(
            get: fn() => is_null($this->end_time),
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
                    'good_play' => 'Gute Aktion',
                    'foul' => 'Foul',
                    'timeout' => 'Auszeit',
                    'substitution' => 'Auswechslung',
                    'coaching_point' => 'Trainer-Hinweis',
                    'statistical_event' => 'Statistik-Event',
                    'injury' => 'Verletzung',
                    'technical_issue' => 'Technisches Problem',
                    'custom' => 'Benutzerdefiniert',
                ];
                
                return $types[$this->annotation_type] ?? $this->annotation_type;
            },
        );
    }

    public function confidencePercentage(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->ai_confidence ? round($this->ai_confidence * 100, 1) : null,
        );
    }

    // Helper Methods
    public function verify(User $user): void
    {
        if ($this->human_verified) {
            throw new \Exception('Annotation ist bereits verifiziert.');
        }

        $this->update([
            'human_verified' => true,
            'verified_by_user_id' => $user->id,
            'verified_at' => now(),
            'status' => 'approved',
        ]);
    }

    public function incrementHelpfulVotes(): void
    {
        $this->increment('helpful_votes');
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function addVisualMarker(array $marker): void
    {
        $markers = $this->visual_markers ?? [];
        $markers[] = array_merge($marker, [
            'id' => uniqid(),
            'created_at' => now()->toISOString(),
        ]);
        
        $this->update(['visual_markers' => $markers]);
    }

    public function removeVisualMarker(string $markerId): void
    {
        $markers = $this->visual_markers ?? [];
        $markers = array_filter($markers, fn($marker) => $marker['id'] !== $markerId);
        
        $this->update(['visual_markers' => array_values($markers)]);
    }

    public function linkToDrill(int $drillId): void
    {
        $drills = $this->linked_drills ?? [];
        if (!in_array($drillId, $drills)) {
            $drills[] = $drillId;
            $this->update(['linked_drills' => $drills]);
        }
    }

    public function unlinkFromDrill(int $drillId): void
    {
        $drills = $this->linked_drills ?? [];
        $drills = array_filter($drills, fn($id) => $id !== $drillId);
        
        $this->update(['linked_drills' => array_values($drills)]);
    }

    public function getInvolvedPlayers(): \Illuminate\Database\Eloquent\Collection
    {
        if (!$this->players_involved || empty($this->players_involved)) {
            return collect();
        }

        return Player::whereIn('id', $this->players_involved)->get();
    }

    public function getLinkedDrills(): \Illuminate\Database\Eloquent\Collection
    {
        if (!$this->linked_drills || empty($this->linked_drills)) {
            return collect();
        }

        return Drill::whereIn('id', $this->linked_drills)->get();
    }

    public function generatePlaybackUrl(): string
    {
        $videoUrl = $this->videoFile->getStreamingUrl();
        
        if ($this->is_point_annotation) {
            return $videoUrl . '#t=' . $this->start_time;
        } else {
            return $videoUrl . '#t=' . $this->start_time . ',' . $this->end_time;
        }
    }

    public function exportToCoachingNotes(): array
    {
        return [
            'timestamp' => $this->start_time_formatted,
            'duration' => $this->duration_formatted,
            'type' => $this->annotation_type_display,
            'title' => $this->title,
            'description' => $this->description,
            'coaching_notes' => $this->coaching_notes,
            'players' => $this->getInvolvedPlayers()->pluck('full_name')->toArray(),
            'drills' => $this->getLinkedDrills()->pluck('name')->toArray(),
            'learning_objectives' => $this->learning_objectives,
        ];
    }
}
```

### Video Processing Service

```php
<?php
// app/Services/VideoProcessingService.php

namespace App\Services;

use App\Models\VideoFile;
use App\Jobs\ProcessVideoJob;
use App\Jobs\ProcessVideoAIAnalysisJob;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Coordinate\Dimension;

class VideoProcessingService
{
    public function processVideo(VideoFile $videoFile): void
    {
        try {
            $videoFile->update([
                'processing_status' => 'processing',
                'processing_started_at' => now(),
            ]);

            // Process video with FFmpeg
            $this->optimizeVideo($videoFile);
            $this->generateThumbnail($videoFile);
            $this->extractMetadata($videoFile);

            $videoFile->update([
                'processing_status' => 'completed',
                'processing_completed_at' => now(),
            ]);

            // Start AI analysis if enabled
            if ($videoFile->ai_analysis_enabled) {
                ProcessVideoAIAnalysisJob::dispatch($videoFile);
            }

        } catch (\Exception $e) {
            $videoFile->update([
                'processing_status' => 'failed',
                'processing_error' => $e->getMessage(),
            ]);

            Log::error('Video processing failed', [
                'video_id' => $videoFile->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function optimizeVideo(VideoFile $videoFile): bool
    {
        $ffmpeg = FFMpeg::create();
        $video = $ffmpeg->open(Storage::path($videoFile->file_path));

        // Create optimized version
        $optimizedPath = 'videos/processed/' . $videoFile->id . '_optimized.mp4';
        
        $video->filters()
              ->resize(new Dimension(1280, 720))
              ->synchronize();

        $video->save(new \FFMpeg\Format\Video\X264('aac'), Storage::path($optimizedPath));

        $videoFile->update(['processed_path' => $optimizedPath]);

        return true;
    }

    public static function generateThumbnail(VideoFile $videoFile, float $time = null): bool
    {
        $time = $time ?? ($videoFile->duration * 0.25);
        
        $ffmpeg = FFMpeg::create();
        $video = $ffmpeg->open(Storage::path($videoFile->file_path));

        $thumbnailPath = 'thumbnails/' . $videoFile->id . '_thumbnail.jpg';

        $video->frame(TimeCode::fromSeconds($time))
              ->save(Storage::path($thumbnailPath));

        $videoFile->update(['thumbnail_path' => $thumbnailPath]);

        return true;
    }

    private function extractMetadata(VideoFile $videoFile): void
    {
        $ffprobe = \FFMpeg\FFProbe::create();
        $videoPath = Storage::path($videoFile->file_path);

        $videoFile->update([
            'width' => $ffprobe->streams($videoPath)->videos()->first()->get('width'),
            'height' => $ffprobe->streams($videoPath)->videos()->first()->get('height'),
            'frame_rate' => $ffprobe->streams($videoPath)->videos()->first()->get('r_frame_rate'),
            'codec' => $ffprobe->streams($videoPath)->videos()->first()->get('codec_name'),
            'bitrate' => $ffprobe->streams($videoPath)->videos()->first()->get('bit_rate'),
        ]);
    }
}
```

---

## 🧠 Predictive Analytics Engine

### Machine Learning Pipeline für Basketball Analytics

Phase 3 implementiert eine fortschrittliche Predictive Analytics Engine, die Machine Learning nutzt, um Performance-Vorhersagen, Verletzungsrisiken und Spielergebnisse zu prognostizieren.

#### ML Models & Database Design

##### ML Models Migration

```php
<?php
// database/migrations/2024_03_25_000000_create_ml_models_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ml_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by_user_id')->constrained('users');
            
            // Model Information
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('model_type'); // regression, classification, clustering, etc.
            $table->string('algorithm'); // random_forest, neural_network, etc.
            $table->string('version', 10)->default('1.0.0');
            
            // Model Purpose and Domain
            $table->enum('prediction_category', [
                'player_performance', 'injury_risk', 'game_outcome',
                'team_chemistry', 'player_development', 'load_management',
                'tactical_effectiveness', 'recruitment', 'season_projection'
            ]);
            
            $table->enum('prediction_timeframe', [
                'next_game', 'next_week', 'next_month', 'season_end',
                'career_projection', 'real_time'
            ]);
            
            $table->json('target_variables')->nullable(); // What we're predicting
            $table->json('feature_variables')->nullable(); // Input features
            $table->json('categorical_variables')->nullable();
            $table->json('numerical_variables')->nullable();
            
            // Training Configuration
            $table->json('training_config')->nullable(); // Hyperparameters, etc.
            $table->json('preprocessing_steps')->nullable(); // Data cleaning pipeline
            $table->json('feature_engineering')->nullable(); // Feature creation rules
            $table->boolean('auto_retrain')->default(false);
            $table->integer('retrain_frequency_days')->nullable();
            
            // Model Performance Metrics
            $table->decimal('accuracy', 5, 4)->nullable();
            $table->decimal('precision', 5, 4)->nullable();
            $table->decimal('recall', 5, 4)->nullable();
            $table->decimal('f1_score', 5, 4)->nullable();
            $table->decimal('auc_roc', 5, 4)->nullable();
            $table->decimal('mse', 10, 6)->nullable(); // Mean Squared Error
            $table->decimal('mae', 10, 6)->nullable(); // Mean Absolute Error
            $table->decimal('r_squared', 5, 4)->nullable();
            
            // Cross-validation Results
            $table->json('cv_scores')->nullable();
            $table->decimal('cv_mean', 5, 4)->nullable();
            $table->decimal('cv_std', 5, 4)->nullable();
            
            // Training Data Information
            $table->integer('training_samples')->nullable();
            $table->integer('validation_samples')->nullable();
            $table->integer('test_samples')->nullable();
            $table->date('training_data_start')->nullable();
            $table->date('training_data_end')->nullable();
            
            // Model Files and Storage
            $table->string('model_file_path')->nullable(); // Serialized model
            $table->string('scaler_file_path')->nullable(); // Feature scaler
            $table->string('encoder_file_path')->nullable(); // Label encoder
            $table->bigInteger('model_file_size')->nullable(); // bytes
            
            // Deployment and Status
            $table->enum('status', [
                'training', 'trained', 'deployed', 'deprecated', 'failed'
            ])->default('training');
            
            $table->boolean('is_production')->default(false);
            $table->timestamp('deployed_at')->nullable();
            $table->timestamp('last_prediction_at')->nullable();
            $table->integer('total_predictions')->default(0);
            
            // Performance Monitoring
            $table->decimal('current_accuracy', 5, 4)->nullable(); // Real-world performance
            $table->integer('correct_predictions')->default(0);
            $table->integer('incorrect_predictions')->default(0);
            $table->json('prediction_distribution')->nullable();
            
            // Model Lineage and Versioning
            $table->foreignId('parent_model_id')->nullable()->constrained('ml_models');
            $table->text('change_notes')->nullable();
            $table->json('experiment_metadata')->nullable();
            
            // Business Impact
            $table->text('business_value')->nullable();
            $table->json('stakeholders')->nullable(); // Who uses this model
            $table->decimal('roi_estimate', 10, 2)->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['prediction_category', 'status']);
            $table->index(['is_production', 'deployed_at']);
            $table->index(['created_by_user_id', 'model_type']);
            $table->index('last_prediction_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ml_models');
    }
};
```

##### ML Predictions Migration

```php
<?php
// database/migrations/2024_03_26_000000_create_ml_predictions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ml_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ml_model_id')->constrained()->onDelete('cascade');
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users');
            
            // Subject of Prediction
            $table->foreignId('player_id')->nullable()->constrained();
            $table->foreignId('team_id')->nullable()->constrained();
            $table->foreignId('game_id')->nullable()->constrained();
            
            // Prediction Request
            $table->string('prediction_id')->unique(); // UUID for tracking
            $table->timestamp('prediction_requested_at');
            $table->timestamp('prediction_completed_at')->nullable();
            $table->integer('processing_time_ms')->nullable();
            
            // Input Data
            $table->json('input_features'); // Features used for prediction
            $table->json('feature_metadata')->nullable(); // Data source info
            $table->string('data_version')->nullable(); // For reproducibility
            
            // Prediction Results
            $table->json('raw_prediction'); // Model output
            $table->json('processed_prediction'); // Business-friendly format
            $table->decimal('confidence_score', 5, 4)->nullable(); // 0-1
            $table->json('confidence_intervals')->nullable(); // Upper/lower bounds
            $table->json('feature_importance')->nullable(); // What drove the prediction
            
            // Prediction Metadata
            $table->string('prediction_type'); // point_estimate, probability, ranking
            $table->json('prediction_context')->nullable(); // Game situation, etc.
            $table->date('prediction_target_date')->nullable(); // When prediction is for
            $table->boolean('is_batch_prediction')->default(false);
            $table->string('batch_id')->nullable();
            
            // Validation and Accuracy
            $table->json('actual_outcome')->nullable(); // Real result when available
            $table->boolean('was_accurate')->nullable();
            $table->decimal('prediction_error', 10, 6)->nullable();
            $table->timestamp('outcome_recorded_at')->nullable();
            $table->text('accuracy_notes')->nullable();
            
            // Business Impact
            $table->boolean('action_taken')->default(false);
            $table->text('action_description')->nullable();
            $table->json('business_impact')->nullable();
            $table->decimal('value_generated', 10, 2)->nullable();
            
            // Status and Workflow
            $table->enum('status', [
                'pending', 'processing', 'completed', 'failed', 'validated'
            ])->default('pending');
            
            $table->text('error_message')->nullable();
            $table->json('debug_info')->nullable();
            
            // Usage and Feedback
            $table->integer('view_count')->default(0);
            $table->decimal('user_rating', 3, 2)->nullable(); // 1-5 stars
            $table->text('user_feedback')->nullable();
            $table->boolean('is_favorite')->default(false);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['ml_model_id', 'prediction_requested_at']);
            $table->index(['player_id', 'prediction_target_date']);
            $table->index(['team_id', 'status']);
            $table->index(['game_id', 'prediction_type']);
            $table->index('prediction_id');
            $table->index(['batch_id', 'is_batch_prediction']);
            $table->index(['was_accurate', 'outcome_recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ml_predictions');
    }
};
```

##### Training Data Migration

```php
<?php
// database/migrations/2024_03_27_000000_create_ml_training_data_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ml_training_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ml_model_id')->constrained()->onDelete('cascade');
            
            // Data Source
            $table->string('data_source'); // games, training_sessions, player_stats, etc.
            $table->foreignId('source_record_id')->nullable(); // ID of source record
            $table->string('source_table')->nullable();
            
            // Data Point Information
            $table->date('data_date');
            $table->timestamp('data_timestamp')->nullable();
            $table->string('data_version', 10)->default('1.0');
            
            // Features (Input Variables)
            $table->json('features'); // All feature values
            $table->json('feature_names'); // Feature column names
            $table->json('feature_types')->nullable(); // Data types
            
            // Targets (Output Variables)
            $table->json('targets'); // What we're trying to predict
            $table->json('target_names'); // Target column names
            
            // Data Quality Metrics
            $table->decimal('completeness_score', 5, 4)->nullable(); // 0-1
            $table->integer('missing_values_count')->default(0);
            $table->json('outlier_flags')->nullable(); // Which features have outliers
            $table->boolean('is_anomaly')->default(false);
            
            // Data Split Assignment
            $table->enum('split_assignment', ['train', 'validation', 'test', 'holdout']);
            $table->decimal('split_ratio', 3, 2)->nullable();
            $table->string('cross_validation_fold')->nullable();
            
            // Data Preprocessing
            $table->json('original_values')->nullable(); // Before preprocessing
            $table->json('preprocessing_applied')->nullable(); // What was done
            $table->boolean('is_synthetic')->default(false); // Generated/augmented data
            
            // Temporal Information (for time series)
            $table->integer('lag_period')->nullable(); // Days/games back
            $table->json('temporal_features')->nullable(); // Time-based features
            $table->boolean('is_sequence_data')->default(false);
            
            // Contextual Information
            $table->foreignId('player_id')->nullable()->constrained();
            $table->foreignId('team_id')->nullable()->constrained();
            $table->foreignId('game_id')->nullable()->constrained();
            $table->string('season')->nullable();
            $table->json('context_metadata')->nullable();
            
            // Data Lineage
            $table->json('transformation_history')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by_user_id')->nullable()->constrained('users');
            
            // Usage Tracking
            $table->integer('times_used_in_training')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['ml_model_id', 'data_date']);
            $table->index(['split_assignment', 'is_active']);
            $table->index(['player_id', 'season']);
            $table->index(['team_id', 'data_source']);
            $table->index(['game_id', 'data_timestamp']);
            $table->index('source_record_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ml_training_data');
    }
};
```

### ML Models Implementation

#### MLModel Model

```php
<?php
// app/Models/MLModel.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MLModel extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'created_by_user_id',
        'name',
        'description',
        'model_type',
        'algorithm',
        'version',
        'prediction_category',
        'prediction_timeframe',
        'target_variables',
        'feature_variables',
        'categorical_variables',
        'numerical_variables',
        'training_config',
        'preprocessing_steps',
        'feature_engineering',
        'auto_retrain',
        'retrain_frequency_days',
        'accuracy',
        'precision',
        'recall',
        'f1_score',
        'auc_roc',
        'mse',
        'mae',
        'r_squared',
        'cv_scores',
        'cv_mean',
        'cv_std',
        'training_samples',
        'validation_samples',
        'test_samples',
        'training_data_start',
        'training_data_end',
        'model_file_path',
        'scaler_file_path',
        'encoder_file_path',
        'model_file_size',
        'status',
        'is_production',
        'deployed_at',
        'last_prediction_at',
        'total_predictions',
        'current_accuracy',
        'correct_predictions',
        'incorrect_predictions',
        'prediction_distribution',
        'parent_model_id',
        'change_notes',
        'experiment_metadata',
        'business_value',
        'stakeholders',
        'roi_estimate',
    ];

    protected $casts = [
        'target_variables' => 'array',
        'feature_variables' => 'array',
        'categorical_variables' => 'array',
        'numerical_variables' => 'array',
        'training_config' => 'array',
        'preprocessing_steps' => 'array',
        'feature_engineering' => 'array',
        'auto_retrain' => 'boolean',
        'retrain_frequency_days' => 'integer',
        'accuracy' => 'decimal:4',
        'precision' => 'decimal:4',
        'recall' => 'decimal:4',
        'f1_score' => 'decimal:4',
        'auc_roc' => 'decimal:4',
        'mse' => 'decimal:6',
        'mae' => 'decimal:6',
        'r_squared' => 'decimal:4',
        'cv_scores' => 'array',
        'cv_mean' => 'decimal:4',
        'cv_std' => 'decimal:4',
        'training_samples' => 'integer',
        'validation_samples' => 'integer',
        'test_samples' => 'integer',
        'training_data_start' => 'date',
        'training_data_end' => 'date',
        'model_file_size' => 'integer',
        'is_production' => 'boolean',
        'deployed_at' => 'datetime',
        'last_prediction_at' => 'datetime',
        'total_predictions' => 'integer',
        'current_accuracy' => 'decimal:4',
        'correct_predictions' => 'integer',
        'incorrect_predictions' => 'integer',
        'prediction_distribution' => 'array',
        'experiment_metadata' => 'array',
        'stakeholders' => 'array',
        'roi_estimate' => 'decimal:2',
    ];

    // Relationships
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function parentModel(): BelongsTo
    {
        return $this->belongsTo(MLModel::class, 'parent_model_id');
    }

    public function childModels(): HasMany
    {
        return $this->hasMany(MLModel::class, 'parent_model_id');
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(MLPrediction::class);
    }

    public function trainingData(): HasMany
    {
        return $this->hasMany(MLTrainingData::class);
    }

    // Scopes
    public function scopeProduction($query)
    {
        return $query->where('is_production', true)
                    ->where('status', 'deployed');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('prediction_category', $category);
    }

    public function scopeTrained($query)
    {
        return $query->whereIn('status', ['trained', 'deployed']);
    }

    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'deprecated');
    }

    // Accessors
    public function accuracyPercentage(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->accuracy ? round($this->accuracy * 100, 2) : null,
        );
    }

    public function modelTypeDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $types = [
                    'regression' => 'Regression',
                    'classification' => 'Klassifikation',
                    'clustering' => 'Clustering',
                    'time_series' => 'Zeitreihen',
                    'neural_network' => 'Neuronales Netz',
                    'ensemble' => 'Ensemble',
                ];
                
                return $types[$this->model_type] ?? $this->model_type;
            },
        );
    }

    public function predictionCategoryDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $categories = [
                    'player_performance' => 'Spieler-Performance',
                    'injury_risk' => 'Verletzungsrisiko',
                    'game_outcome' => 'Spielergebnis',
                    'team_chemistry' => 'Team-Chemie',
                    'player_development' => 'Spieler-Entwicklung',
                    'load_management' => 'Belastungsmanagement',
                    'tactical_effectiveness' => 'Taktik-Effektivität',
                    'recruitment' => 'Rekrutierung',
                    'season_projection' => 'Saison-Prognose',
                ];
                
                return $categories[$this->prediction_category] ?? $this->prediction_category;
            },
        );
    }

    public function isHealthy(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->is_production) {
                    return true;
                }
                
                // Check if model is performing well
                $recentAccuracy = $this->current_accuracy ?? $this->accuracy;
                $accuracyThreshold = 0.7; // 70% threshold
                
                return $recentAccuracy >= $accuracyThreshold;
            },
        );
    }

    public function needsRetraining(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->auto_retrain || !$this->retrain_frequency_days) {
                    return false;
                }
                
                $daysSinceTraining = $this->updated_at->diffInDays(now());
                return $daysSinceTraining >= $this->retrain_frequency_days;
            },
        );
    }

    // Helper Methods
    public function deploy(): void
    {
        if ($this->status !== 'trained') {
            throw new \Exception('Model muss trainiert sein, bevor es deployed werden kann.');
        }

        // Retire current production model of same category
        self::where('prediction_category', $this->prediction_category)
            ->where('is_production', true)
            ->update(['is_production' => false]);

        $this->update([
            'is_production' => true,
            'status' => 'deployed',
            'deployed_at' => now(),
        ]);
    }

    public function retire(): void
    {
        $this->update([
            'is_production' => false,
            'status' => 'deprecated',
        ]);
    }

    public function makePrediction(array $features): MLPrediction
    {
        if (!$this->is_production) {
            throw new \Exception('Nur produktive Modelle können Vorhersagen machen.');
        }

        // Create prediction record
        $prediction = $this->predictions()->create([
            'prediction_id' => \Str::uuid(),
            'input_features' => $features,
            'prediction_requested_at' => now(),
            'status' => 'processing',
        ]);

        // Dispatch prediction job
        ProcessMLPredictionJob::dispatch($prediction);

        return $prediction;
    }

    public function updatePerformanceMetrics(bool $wasAccurate): void
    {
        if ($wasAccurate) {
            $this->increment('correct_predictions');
        } else {
            $this->increment('incorrect_predictions');
        }

        $total = $this->correct_predictions + $this->incorrect_predictions;
        $newAccuracy = $total > 0 ? $this->correct_predictions / $total : null;

        $this->update([
            'current_accuracy' => $newAccuracy,
            'last_prediction_at' => now(),
        ]);
    }

    public function getFeatureImportance(): array
    {
        if (!$this->experiment_metadata || !isset($this->experiment_metadata['feature_importance'])) {
            return [];
        }

        return $this->experiment_metadata['feature_importance'];
    }

    public function getModelSummary(): array
    {
        return [
            'model_info' => [
                'name' => $this->name,
                'version' => $this->version,
                'algorithm' => $this->algorithm,
                'category' => $this->prediction_category_display,
            ],
            'performance' => [
                'accuracy' => $this->accuracy_percentage,
                'precision' => $this->precision,
                'recall' => $this->recall,
                'f1_score' => $this->f1_score,
            ],
            'deployment' => [
                'status' => $this->status,
                'is_production' => $this->is_production,
                'deployed_at' => $this->deployed_at,
                'total_predictions' => $this->total_predictions,
            ],
            'health' => [
                'is_healthy' => $this->is_healthy,
                'needs_retraining' => $this->needs_retraining,
                'current_accuracy' => $this->current_accuracy,
            ],
        ];
    }

    public function exportModel(): string
    {
        // Export model configuration and metadata for backup/versioning
        $export = [
            'model_metadata' => $this->toArray(),
            'training_config' => $this->training_config,
            'feature_engineering' => $this->feature_engineering,
            'performance_metrics' => [
                'accuracy' => $this->accuracy,
                'precision' => $this->precision,
                'recall' => $this->recall,
                'f1_score' => $this->f1_score,
                'cv_scores' => $this->cv_scores,
            ],
            'exported_at' => now()->toISOString(),
        ];

        $filename = "model_export_{$this->id}_{$this->version}.json";
        $path = storage_path("app/ml_exports/{$filename}");
        
        file_put_contents($path, json_encode($export, JSON_PRETTY_PRINT));
        
        return $path;
    }

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status', 'is_production', 'accuracy', 'version'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
```

#### MLPrediction Model

```php
<?php
// app/Models/MLPrediction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

class MLPrediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'ml_model_id',
        'requested_by_user_id',
        'player_id',
        'team_id',
        'game_id',
        'prediction_id',
        'prediction_requested_at',
        'prediction_completed_at',
        'processing_time_ms',
        'input_features',
        'feature_metadata',
        'data_version',
        'raw_prediction',
        'processed_prediction',
        'confidence_score',
        'confidence_intervals',
        'feature_importance',
        'prediction_type',
        'prediction_context',
        'prediction_target_date',
        'is_batch_prediction',
        'batch_id',
        'actual_outcome',
        'was_accurate',
        'prediction_error',
        'outcome_recorded_at',
        'accuracy_notes',
        'action_taken',
        'action_description',
        'business_impact',
        'value_generated',
        'status',
        'error_message',
        'debug_info',
        'view_count',
        'user_rating',
        'user_feedback',
        'is_favorite',
    ];

    protected $casts = [
        'prediction_requested_at' => 'datetime',
        'prediction_completed_at' => 'datetime',
        'processing_time_ms' => 'integer',
        'input_features' => 'array',
        'feature_metadata' => 'array',
        'raw_prediction' => 'array',
        'processed_prediction' => 'array',
        'confidence_score' => 'decimal:4',
        'confidence_intervals' => 'array',
        'feature_importance' => 'array',
        'prediction_target_date' => 'date',
        'is_batch_prediction' => 'boolean',
        'actual_outcome' => 'array',
        'was_accurate' => 'boolean',
        'prediction_error' => 'decimal:6',
        'outcome_recorded_at' => 'datetime',
        'action_taken' => 'boolean',
        'business_impact' => 'array',
        'value_generated' => 'decimal:2',
        'debug_info' => 'array',
        'view_count' => 'integer',
        'user_rating' => 'decimal:2',
        'is_favorite' => 'boolean',
    ];

    // Relationships
    public function mlModel(): BelongsTo
    {
        return $this->belongsTo(MLModel::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    // Helper Methods
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function getPredictionExplanation(): array
    {
        return [
            'prediction' => $this->processed_prediction['summary'] ?? 'Vorhersage abgeschlossen',
            'confidence' => $this->confidence_score ? round($this->confidence_score * 100, 1) . '%' : null,
            'model_info' => [
                'name' => $this->mlModel->name,
                'category' => $this->mlModel->prediction_category_display,
                'accuracy' => $this->mlModel->accuracy_percentage . '%',
            ],
        ];
    }

    public static function generatePredictionId(): string
    {
        return 'pred_' . Str::random(16);
    }
}
```

### Predictive Analytics Service

```php
<?php
// app/Services/PredictiveAnalyticsService.php

namespace App\Services;

use App\Models\MLModel;
use App\Models\MLPrediction;
use App\Models\Player;
use App\Models\Team;
use App\Models\Game;
use App\Jobs\TrainMLModelJob;
use App\Jobs\ProcessMLPredictionJob;
use Illuminate\Support\Facades\DB;

class PredictiveAnalyticsService
{
    public function createModel(array $modelConfig): MLModel
    {
        return DB::transaction(function () use ($modelConfig) {
            return MLModel::create($modelConfig);
        });
    }

    public function trainModel(MLModel $model, array $trainingConfig = []): void
    {
        if ($model->status !== 'training') {
            $model->update(['status' => 'training']);
        }

        TrainMLModelJob::dispatch($model, $trainingConfig);
    }

    public function makePrediction(MLModel $model, array $features, ?int $userId = null): MLPrediction
    {
        if (!$model->is_production) {
            throw new \Exception('Model ist nicht für Produktions-Vorhersagen verfügbar.');
        }

        $prediction = MLPrediction::create([
            'ml_model_id' => $model->id,
            'requested_by_user_id' => $userId ?? auth()->id(),
            'prediction_id' => MLPrediction::generatePredictionId(),
            'input_features' => $features,
            'prediction_requested_at' => now(),
            'status' => 'pending',
        ]);

        ProcessMLPredictionJob::dispatch($prediction);

        return $prediction;
    }

    public function predictPlayerPerformance(Player $player, ?Game $game = null): MLPrediction
    {
        $model = MLModel::production()
                        ->byCategory('player_performance')
                        ->first();

        if (!$model) {
            throw new \Exception('Kein Player Performance Model verfügbar.');
        }

        $features = $this->extractPlayerFeatures($player, $game);

        $prediction = $this->makePrediction($model, $features);
        $prediction->update(['player_id' => $player->id]);

        if ($game) {
            $prediction->update(['game_id' => $game->id]);
        }

        return $prediction;
    }

    public function predictGameOutcome(Game $game): MLPrediction
    {
        $model = MLModel::production()
                        ->byCategory('game_outcome')
                        ->first();

        if (!$model) {
            throw new \Exception('Kein Game Outcome Model verfügbar.');
        }

        $features = $this->extractGameFeatures($game);

        $prediction = $this->makePrediction($model, $features);
        $prediction->update(['game_id' => $game->id]);

        return $prediction;
    }

    public function assessInjuryRisk(Player $player): MLPrediction
    {
        $model = MLModel::production()
                        ->byCategory('injury_risk')
                        ->first();

        if (!$model) {
            throw new \Exception('Kein Injury Risk Model verfügbar.');
        }

        $features = $this->extractInjuryRiskFeatures($player);

        $prediction = $this->makePrediction($model, $features);
        $prediction->update([
            'player_id' => $player->id,
            'prediction_type' => 'probability',
        ]);

        return $prediction;
    }

    private function extractPlayerFeatures(Player $player, ?Game $game = null): array
    {
        $features = [
            'age' => $player->age,
            'position' => $player->position,
            'height' => $player->height,
            'weight' => $player->weight,
            'avg_points_recent' => $this->getRecentAveragePoints($player, 5),
            'avg_minutes_recent' => $this->getRecentAverageMinutes($player, 5),
            'rest_days' => $this->getRestDaysSinceLastGame($player),
        ];

        if ($game) {
            $opponent = $game->home_team_id === $player->team_id 
                ? $game->awayTeam 
                : $game->homeTeam;
                
            $features['opponent_defensive_rating'] = $opponent->currentSeasonDefensiveRating();
            $features['home_game'] = $game->home_team_id === $player->team_id;
        }

        return $features;
    }

    private function extractGameFeatures(Game $game): array
    {
        return [
            'home_team_wins' => $game->homeTeam->currentSeasonWins(),
            'home_team_losses' => $game->homeTeam->currentSeasonLosses(),
            'away_team_wins' => $game->awayTeam->currentSeasonWins(),
            'away_team_losses' => $game->awayTeam->currentSeasonLosses(),
            'home_offensive_rating' => $game->homeTeam->currentSeasonOffensiveRating(),
            'away_defensive_rating' => $game->awayTeam->currentSeasonDefensiveRating(),
        ];
    }

    private function extractInjuryRiskFeatures(Player $player): array
    {
        return [
            'age' => $player->age,
            'total_minutes_season' => $this->getTotalMinutesThisSeason($player),
            'games_played_season' => $this->getGamesPlayedThisSeason($player),
            'previous_injuries' => $this->getPreviousInjuriesCount($player),
            'fatigue_score' => $this->calculateFatigueScore($player),
        ];
    }

    // Placeholder methods for feature extraction
    private function getRecentAveragePoints(Player $player, int $games): float
    {
        // Implementation would calculate from actual game data
        return 15.5;
    }

    private function getRecentAverageMinutes(Player $player, int $games): float
    {
        return 28.3;
    }

    private function getRestDaysSinceLastGame(Player $player): int
    {
        return 2;
    }

    private function getTotalMinutesThisSeason(Player $player): int
    {
        return 750;
    }

    private function getGamesPlayedThisSeason(Player $player): int
    {
        return 25;
    }

    private function getPreviousInjuriesCount(Player $player): int
    {
        return 1;
    }

    private function calculateFatigueScore(Player $player): float
    {
        return 0.65;
    }
}
```

---

## 📊 Shot Chart & Heat Maps System

### Advanced Basketball Visualization

Das Shot Chart & Heat Maps System bietet detaillierte visuelle Analysen der Spieler-Performance auf dem Basketballfeld mit Real-time Updates und interaktiven Visualisierungen.

#### Shot Data Models & Database Design

##### Shot Chart Data Migration

```php
<?php
// database/migrations/2024_03_30_000000_create_shot_chart_data_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shot_chart_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained();
            $table->foreignId('team_id')->constrained();
            $table->foreignId('game_id')->nullable()->constrained();
            $table->foreignId('training_session_id')->nullable()->constrained();
            
            // Shot Location (Normalized Court Coordinates)
            $table->decimal('x_coordinate', 6, 3); // -25.000 to 25.000 (feet from center)
            $table->decimal('y_coordinate', 6, 3); // 0.000 to 47.000 (feet from baseline)
            $table->decimal('distance_to_basket', 5, 2); // Distance in feet
            
            // Court Zone Classification
            $table->enum('zone_type', [
                'paint', 'free_throw_line', 'mid_range', 'three_point_line',
                'corner_three', 'wing_three', 'top_three', 'deep_three'
            ]);
            
            $table->enum('court_side', ['left', 'right', 'center']);
            $table->enum('court_area', ['backcourt', 'frontcourt']);
            
            // Shot Details
            $table->enum('shot_type', [
                'layup', 'dunk', 'hook_shot', 'jump_shot', 'three_pointer',
                'free_throw', 'bank_shot', 'fadeaway', 'step_back',
                'catch_and_shoot', 'pull_up', 'turnaround'
            ]);
            
            $table->boolean('shot_made')->default(false);
            $table->integer('shot_value')->default(2); // Points if made (2 or 3)
            
            // Game Context
            $table->integer('quarter')->nullable();
            $table->time('time_remaining')->nullable();
            $table->integer('shot_clock_remaining')->nullable();
            $table->integer('team_score_before')->nullable();
            $table->integer('opponent_score_before')->nullable();
            $table->integer('score_differential')->nullable(); // Team lead/deficit
            
            // Defensive Context
            $table->foreignId('closest_defender_id')->nullable()->constrained('players');
            $table->decimal('defender_distance', 4, 2)->nullable(); // Feet
            $table->enum('defensive_pressure', ['none', 'light', 'medium', 'heavy']);
            $table->boolean('contested_shot')->default(false);
            
            // Shot Process
            $table->enum('shot_preparation', [
                'spot_up', 'off_dribble', 'transition', 'post_up',
                'pick_and_roll', 'isolation', 'cut', 'offensive_rebound'
            ]);
            
            $table->integer('dribbles_before_shot')->nullable();
            $table->decimal('time_of_possession', 4, 2)->nullable(); // Seconds
            $table->boolean('assisted_shot')->default(false);
            $table->foreignId('assist_player_id')->nullable()->constrained('players');
            
            // Performance Context
            $table->enum('shooter_fatigue_level', ['fresh', 'moderate', 'tired', 'exhausted'])->nullable();
            $table->boolean('fast_break_shot')->default(false);
            $table->boolean('second_chance_shot')->default(false);
            
            // Video and Analytics
            $table->foreignId('video_annotation_id')->nullable()->constrained('video_annotations');
            $table->json('tracking_data')->nullable(); // Player movement data
            $table->decimal('shot_arc_angle', 5, 2)->nullable(); // Shot trajectory
            $table->decimal('shot_velocity', 5, 2)->nullable(); // mph
            
            // Shot Quality Metrics
            $table->decimal('expected_points', 4, 3)->nullable(); // AI-calculated expected value
            $table->decimal('shot_quality_score', 4, 3)->nullable(); // 0-1 scale
            $table->json('shot_factors')->nullable(); // Contributing factors to quality
            
            // Real-time Data
            $table->timestamp('shot_time');
            $table->boolean('is_live_tracked')->default(false);
            $table->string('data_source')->default('manual'); // manual, video_analysis, live_tracking
            
            // Aggregation Support
            $table->string('season', 7); // e.g., "2024-25"
            $table->date('game_date')->nullable();
            $table->boolean('is_playoff')->default(false);
            $table->boolean('is_practice')->default(false);
            
            // Quality Control
            $table->boolean('verified')->default(false);
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for Performance
            $table->index(['player_id', 'shot_time']);
            $table->index(['game_id', 'quarter']);
            $table->index(['team_id', 'season']);
            $table->index(['zone_type', 'shot_made']);
            $table->index(['x_coordinate', 'y_coordinate']);
            $table->index(['distance_to_basket', 'shot_type']);
            $table->index(['shot_time', 'is_live_tracked']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shot_chart_data');
    }
};
```

##### Heat Map Data Migration

```php
<?php
// database/migrations/2024_03_31_000000_create_heat_map_data_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heat_map_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->nullable()->constrained();
            $table->foreignId('team_id')->constrained();
            
            // Heat Map Configuration
            $table->string('heat_map_type'); // shots, movement, defensive_actions, rebounds
            $table->string('time_period'); // game, quarter, season, career
            $table->string('period_identifier')->nullable(); // specific game_id, season, etc.
            
            // Coordinate Grid (Court divided into zones)
            $table->decimal('grid_x', 6, 3); // Court X coordinate
            $table->decimal('grid_y', 6, 3); // Court Y coordinate
            $table->decimal('grid_size', 4, 2)->default(1.00); // Size of grid cell in feet
            
            // Heat Map Metrics
            $table->integer('frequency_count')->default(0); // How many times in this zone
            $table->decimal('success_rate', 5, 4)->nullable(); // Success percentage in this zone
            $table->decimal('intensity_score', 8, 4)->default(0); // Heat intensity (0-1)
            $table->integer('total_attempts')->default(0);
            $table->integer('successful_attempts')->default(0);
            
            // Time-based Aggregation
            $table->integer('minutes_spent')->nullable(); // For movement heat maps
            $table->decimal('points_per_possession', 6, 3)->nullable();
            $table->decimal('efficiency_rating', 6, 3)->nullable();
            
            // Context Filters
            $table->json('context_filters')->nullable(); // home/away, quarter, opponent, etc.
            $table->string('opponent_team')->nullable();
            $table->enum('game_situation', ['all', 'clutch', 'blowout', 'close'])->nullable();
            
            // Data Aggregation Period
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->integer('games_included')->default(0);
            
            // Heat Map Generation
            $table->timestamp('last_updated');
            $table->boolean('needs_refresh')->default(false);
            $table->json('generation_parameters')->nullable();
            
            // Comparative Data
            $table->decimal('league_average', 5, 4)->nullable(); // For comparison
            $table->decimal('position_average', 5, 4)->nullable();
            $table->decimal('team_average', 5, 4)->nullable();
            $table->integer('percentile_rank')->nullable(); // 0-100
            
            $table->timestamps();
            
            // Indexes
            $table->index(['player_id', 'heat_map_type', 'time_period']);
            $table->index(['team_id', 'period_start', 'period_end']);
            $table->index(['grid_x', 'grid_y', 'heat_map_type']);
            $table->index(['last_updated', 'needs_refresh']);
            $table->unique(['player_id', 'heat_map_type', 'time_period', 'period_identifier', 'grid_x', 'grid_y'], 'unique_heat_map_point');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heat_map_data');
    }
};
```

### Advanced Statistics Engine

#### Basketball-specific Advanced Metrics

```php
<?php
// app/Services/AdvancedStatisticsService.php

namespace App\Services;

use App\Models\Player;
use App\Models\Team;
use App\Models\Game;
use App\Models\ShotChartData;
use App\Models\GameAction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdvancedStatisticsService
{
    /**
     * Calculate Player Efficiency Rating (PER)
     * A measure of per-minute production standardized such that the league average is 15
     */
    public function calculatePER(Player $player, string $season): float
    {
        $stats = $this->getPlayerSeasonStats($player, $season);
        
        if ($stats['minutes_played'] == 0) return 0;
        
        // PER calculation components
        $pace = $this->getTeamPace($player->team, $season);
        $minutesPerGame = $stats['minutes_played'] / $stats['games_played'];
        
        // Positive contributions
        $positiveActions = 
            $stats['field_goals_made'] * 85.910 +
            $stats['steals'] * 53.897 +
            $stats['three_points_made'] * 51.757 +
            $stats['free_throws_made'] * 46.845 +
            $stats['blocks'] * 39.190 +
            $stats['offensive_rebounds'] * 39.190 +
            $stats['assists'] * 34.677 +
            $stats['defensive_rebounds'] * 14.707;
        
        // Negative contributions
        $negativeActions = 
            $stats['field_goals_missed'] * 39.190 +
            $stats['free_throws_missed'] * 20.091 +
            $stats['turnovers'] * 53.897 +
            $stats['fouls'] * 17.174;
        
        $per = ($positiveActions - $negativeActions) / $stats['minutes_played'] * $pace / 1000;
        
        return round($per, 2);
    }

    /**
     * Calculate Box Plus/Minus (BPM)
     * Estimates the points per 100 possessions that a player contributed above a league-average player
     */
    public function calculateBPM(Player $player, string $season): array
    {
        $stats = $this->getPlayerSeasonStats($player, $season);
        $teamStats = $this->getTeamSeasonStats($player->team, $season);
        
        if ($stats['minutes_played'] == 0) {
            return ['offensive_bpm' => 0, 'defensive_bpm' => 0, 'total_bpm' => 0];
        }
        
        // Offensive BPM calculation
        $offensiveBPM = $this->calculateOffensiveBPM($stats, $teamStats);
        
        // Defensive BPM calculation
        $defensiveBPM = $this->calculateDefensiveBPM($stats, $teamStats);
        
        $totalBPM = $offensiveBPM + $defensiveBPM;
        
        return [
            'offensive_bpm' => round($offensiveBPM, 2),
            'defensive_bpm' => round($defensiveBPM, 2),
            'total_bpm' => round($totalBPM, 2),
        ];
    }

    /**
     * Calculate Value Over Replacement Player (VORP)
     * Estimate of how many points per 100 team possessions a player contributed above a replacement player
     */
    public function calculateVORP(Player $player, string $season): float
    {
        $bpm = $this->calculateBPM($player, $season);
        $stats = $this->getPlayerSeasonStats($player, $season);
        
        // Replacement level is approximately -2.0 BPM
        $replacementLevel = -2.0;
        $totalBPM = $bpm['total_bpm'];
        
        // Team possessions estimate
        $teamPossessions = $this->getTeamPossessions($player->team, $season);
        $playerPossessionPercentage = $stats['minutes_played'] / ($stats['games_played'] * 48) * 0.2; // Rough estimate
        
        $vorp = ($totalBPM - $replacementLevel) * $playerPossessionPercentage * $teamPossessions / 100;
        
        return round($vorp, 2);
    }

    /**
     * Calculate Effective Field Goal Percentage (eFG%)
     * Adjusts field goal percentage to account for the fact that 3-point shots are worth more
     */
    public function calculateEffectiveFieldGoalPercentage(Player $player, string $season): float
    {
        $stats = $this->getPlayerSeasonStats($player, $season);
        
        if ($stats['field_goals_attempted'] == 0) return 0;
        
        $efg = ($stats['field_goals_made'] + 0.5 * $stats['three_points_made']) / $stats['field_goals_attempted'];
        
        return round($efg * 100, 1);
    }

    /**
     * Calculate True Shooting Percentage (TS%)
     * Measure of shooting efficiency that takes into account field goals, 3-point field goals, and free throws
     */
    public function calculateTrueShootingPercentage(Player $player, string $season): float
    {
        $stats = $this->getPlayerSeasonStats($player, $season);
        
        $totalPoints = $stats['points'];
        $totalShots = $stats['field_goals_attempted'] + 0.44 * $stats['free_throws_attempted'];
        
        if ($totalShots == 0) return 0;
        
        $ts = $totalPoints / (2 * $totalShots);
        
        return round($ts * 100, 1);
    }

    /**
     * Calculate Usage Rate (USG%)
     * Estimate of the percentage of team plays used by a player while he was on the floor
     */
    public function calculateUsageRate(Player $player, string $season): float
    {
        $stats = $this->getPlayerSeasonStats($player, $season);
        $teamStats = $this->getTeamSeasonStats($player->team, $season);
        
        if ($stats['minutes_played'] == 0) return 0;
        
        $playerPossessions = $stats['field_goals_attempted'] + 0.44 * $stats['free_throws_attempted'] + $stats['turnovers'];
        $teamPossessions = $this->getTeamPossessions($player->team, $season);
        $minutesPercentage = $stats['minutes_played'] / ($teamStats['games_played'] * 240); // 240 = 5 players * 48 minutes
        
        $usageRate = $playerPossessions / ($teamPossessions * $minutesPercentage);
        
        return round($usageRate * 100, 1);
    }

    /**
     * Calculate Assist Rate (AST%)
     * Estimate of the percentage of teammate field goals a player assisted while on the floor
     */
    public function calculateAssistRate(Player $player, string $season): float
    {
        $stats = $this->getPlayerSeasonStats($player, $season);
        $teamStats = $this->getTeamSeasonStats($player->team, $season);
        
        if ($stats['minutes_played'] == 0) return 0;
        
        $minutesPercentage = $stats['minutes_played'] / ($teamStats['games_played'] * 240);
        $teamFieldGoals = $teamStats['field_goals_made'] - $stats['field_goals_made']; // Teammate FG
        
        if ($teamFieldGoals == 0) return 0;
        
        $assistRate = $stats['assists'] / ($teamFieldGoals * $minutesPercentage);
        
        return round($assistRate * 100, 1);
    }

    /**
     * Calculate Turnover Rate (TOV%)
     * Estimate of turnovers per 100 plays
     */
    public function calculateTurnoverRate(Player $player, string $season): float
    {
        $stats = $this->getPlayerSeasonStats($player, $season);
        
        $possessions = $stats['field_goals_attempted'] + 0.44 * $stats['free_throws_attempted'] + $stats['turnovers'];
        
        if ($possessions == 0) return 0;
        
        $turnoverRate = $stats['turnovers'] / $possessions;
        
        return round($turnoverRate * 100, 1);
    }

    /**
     * Calculate Rebound Rate (REB%)
     * Estimate of the percentage of available rebounds a player grabbed while on the floor
     */
    public function calculateReboundRate(Player $player, string $season, string $type = 'total'): float
    {
        $stats = $this->getPlayerSeasonStats($player, $season);
        $teamStats = $this->getTeamSeasonStats($player->team, $season);
        
        if ($stats['minutes_played'] == 0) return 0;
        
        $minutesPercentage = $stats['minutes_played'] / ($teamStats['games_played'] * 240);
        
        switch ($type) {
            case 'offensive':
                $playerRebounds = $stats['offensive_rebounds'];
                $availableRebounds = $teamStats['offensive_rebounds'] + $teamStats['opponent_defensive_rebounds'];
                break;
            case 'defensive':
                $playerRebounds = $stats['defensive_rebounds'];
                $availableRebounds = $teamStats['defensive_rebounds'] + $teamStats['opponent_offensive_rebounds'];
                break;
            default: // total
                $playerRebounds = $stats['total_rebounds'];
                $availableRebounds = $teamStats['total_rebounds'] + $teamStats['opponent_total_rebounds'];
                break;
        }
        
        if ($availableRebounds == 0) return 0;
        
        $reboundRate = $playerRebounds / ($availableRebounds * $minutesPercentage);
        
        return round($reboundRate * 100, 1);
    }

    /**
     * Calculate Steal Rate (STL%)
     * Estimate of the percentage of opponent possessions that end with a steal by the player
     */
    public function calculateStealRate(Player $player, string $season): float
    {
        $stats = $this->getPlayerSeasonStats($player, $season);
        $teamStats = $this->getTeamSeasonStats($player->team, $season);
        
        if ($stats['minutes_played'] == 0) return 0;
        
        $minutesPercentage = $stats['minutes_played'] / ($teamStats['games_played'] * 240);
        $opponentPossessions = $this->getOpponentPossessions($player->team, $season);
        
        $stealRate = $stats['steals'] / ($opponentPossessions * $minutesPercentage);
        
        return round($stealRate * 100, 1);
    }

    /**
     * Calculate Block Rate (BLK%)
     * Estimate of the percentage of opponent two-point field goal attempts blocked by the player
     */
    public function calculateBlockRate(Player $player, string $season): float
    {
        $stats = $this->getPlayerSeasonStats($player, $season);
        $teamStats = $this->getTeamSeasonStats($player->team, $season);
        
        if ($stats['minutes_played'] == 0) return 0;
        
        $minutesPercentage = $stats['minutes_played'] / ($teamStats['games_played'] * 240);
        $opponentTwoPointAttempts = $teamStats['opponent_field_goals_attempted'] - $teamStats['opponent_three_points_attempted'];
        
        if ($opponentTwoPointAttempts == 0) return 0;
        
        $blockRate = $stats['blocks'] / ($opponentTwoPointAttempts * $minutesPercentage);
        
        return round($blockRate * 100, 1);
    }

    /**
     * Calculate Game Score
     * A rough measure of a player's productivity for a single game
     */
    public function calculateGameScore(array $gameStats): float
    {
        $gameScore = $gameStats['points'] +
                    0.4 * $gameStats['field_goals_made'] -
                    0.7 * $gameStats['field_goals_attempted'] -
                    0.4 * ($gameStats['free_throws_attempted'] - $gameStats['free_throws_made']) +
                    0.7 * $gameStats['offensive_rebounds'] +
                    0.3 * $gameStats['defensive_rebounds'] +
                    $gameStats['steals'] +
                    0.7 * $gameStats['assists'] +
                    0.7 * $gameStats['blocks'] -
                    0.4 * $gameStats['fouls'] -
                    $gameStats['turnovers'];
        
        return round($gameScore, 1);
    }

    /**
     * Calculate Pace (Team Stat)
     * Estimate of possessions per 48 minutes
     */
    public function getTeamPace(Team $team, string $season): float
    {
        $stats = $this->getTeamSeasonStats($team, $season);
        
        if ($stats['games_played'] == 0) return 100; // League average fallback
        
        $totalPossessions = $this->getTeamPossessions($team, $season);
        $totalMinutes = $stats['games_played'] * 240; // 240 = 48 minutes * 5 players
        
        $pace = $totalPossessions / $totalMinutes * 48;
        
        return round($pace, 1);
    }

    /**
     * Calculate Offensive Rating (Team Stat)
     * Points scored per 100 possessions
     */
    public function calculateOffensiveRating(Team $team, string $season): float
    {
        $stats = $this->getTeamSeasonStats($team, $season);
        $possessions = $this->getTeamPossessions($team, $season);
        
        if ($possessions == 0) return 0;
        
        $offensiveRating = ($stats['points'] / $possessions) * 100;
        
        return round($offensiveRating, 1);
    }

    /**
     * Calculate Defensive Rating (Team Stat)
     * Points allowed per 100 possessions
     */
    public function calculateDefensiveRating(Team $team, string $season): float
    {
        $stats = $this->getTeamSeasonStats($team, $season);
        $opponentPossessions = $this->getOpponentPossessions($team, $season);
        
        if ($opponentPossessions == 0) return 0;
        
        $defensiveRating = ($stats['opponent_points'] / $opponentPossessions) * 100;
        
        return round($defensiveRating, 1);
    }

    // Helper methods for data retrieval
    private function getPlayerSeasonStats(Player $player, string $season): array
    {
        // This would typically query the database for aggregated player stats
        // Returning mock data structure for now
        return [
            'games_played' => 65,
            'minutes_played' => 2000,
            'points' => 1300,
            'field_goals_made' => 500,
            'field_goals_attempted' => 1100,
            'three_points_made' => 150,
            'three_points_attempted' => 400,
            'free_throws_made' => 150,
            'free_throws_attempted' => 200,
            'offensive_rebounds' => 80,
            'defensive_rebounds' => 320,
            'total_rebounds' => 400,
            'assists' => 250,
            'steals' => 90,
            'blocks' => 45,
            'turnovers' => 180,
            'fouls' => 150,
            'field_goals_missed' => 600,
            'free_throws_missed' => 50,
        ];
    }

    private function getTeamSeasonStats(Team $team, string $season): array
    {
        // Mock team stats
        return [
            'games_played' => 82,
            'points' => 9200,
            'field_goals_made' => 3600,
            'field_goals_attempted' => 7800,
            'three_points_made' => 900,
            'three_points_attempted' => 2400,
            'offensive_rebounds' => 800,
            'defensive_rebounds' => 2800,
            'total_rebounds' => 3600,
            'opponent_points' => 8900,
            'opponent_field_goals_attempted' => 7600,
            'opponent_three_points_attempted' => 2200,
            'opponent_offensive_rebounds' => 750,
            'opponent_defensive_rebounds' => 2850,
            'opponent_total_rebounds' => 3600,
        ];
    }

    private function getTeamPossessions(Team $team, string $season): int
    {
        $stats = $this->getTeamSeasonStats($team, $season);
        
        // Dean Oliver's possession formula
        $possessions = $stats['field_goals_attempted'] - 
                     $stats['offensive_rebounds'] + 
                     $stats['turnovers'] + 
                     0.4 * $stats['free_throws_attempted'];
        
        return (int) $possessions;
    }

    private function getOpponentPossessions(Team $team, string $season): int
    {
        $stats = $this->getTeamSeasonStats($team, $season);
        
        $possessions = $stats['opponent_field_goals_attempted'] - 
                      $stats['opponent_offensive_rebounds'] + 
                      $stats['opponent_turnovers'] + 
                      0.4 * $stats['opponent_free_throws_attempted'];
        
        return (int) $possessions;
    }

    private function calculateOffensiveBPM(array $playerStats, array $teamStats): float
    {
        // Simplified offensive BPM calculation
        // Real calculation would be more complex with regression coefficients
        
        $per = $this->calculatePlayerPER($playerStats);
        $usage = $this->calculatePlayerUsage($playerStats, $teamStats);
        
        $offensiveBPM = ($per - 15) * 0.4 + ($usage - 20) * 0.1;
        
        return $offensiveBPM;
    }

    private function calculateDefensiveBPM(array $playerStats, array $teamStats): float
    {
        // Simplified defensive BPM calculation
        // Real calculation would include team defensive performance when player is on/off court
        
        $stealRate = ($playerStats['steals'] / $playerStats['minutes_played']) * 48;
        $blockRate = ($playerStats['blocks'] / $playerStats['minutes_played']) * 48;
        $reboundRate = ($playerStats['defensive_rebounds'] / $playerStats['minutes_played']) * 48;
        
        $defensiveBPM = ($stealRate * 2) + ($blockRate * 1.5) + ($reboundRate * 0.5) - 2;
        
        return $defensiveBPM;
    }

    private function calculatePlayerPER(array $stats): float
    {
        // Simplified PER calculation for helper method
        $minutes = $stats['minutes_played'];
        
        if ($minutes == 0) return 0;
        
        $per = ($stats['points'] + $stats['total_rebounds'] + $stats['assists'] + 
                $stats['steals'] + $stats['blocks']) / $minutes * 48;
        
        return $per;
    }

    private function calculatePlayerUsage(array $playerStats, array $teamStats): float
    {
        $playerPossessions = $playerStats['field_goals_attempted'] + 
                           0.44 * $playerStats['free_throws_attempted'] + 
                           $playerStats['turnovers'];
        
        $teamPossessions = $this->getTeamPossessionsFromStats($teamStats);
        $minutesPercentage = $playerStats['minutes_played'] / ($teamStats['games_played'] * 240);
        
        if ($teamPossessions * $minutesPercentage == 0) return 0;
        
        return ($playerPossessions / ($teamPossessions * $minutesPercentage)) * 100;
    }

    private function getTeamPossessionsFromStats(array $stats): int
    {
        return $stats['field_goals_attempted'] - 
               $stats['offensive_rebounds'] + 
               ($stats['turnovers'] ?? 1200) + // Fallback if not provided
               0.4 * ($stats['free_throws_attempted'] ?? 1800);
    }
}
```

---

## 🎨 Frontend Components

### React/Vue.js Components für Advanced Features

Phase 3 erweitert die Benutzeroberfläche um hochmoderne, interaktive Frontend-Komponenten, die eine nahtlose Benutzererfahrung für alle erweiterten Features bieten.

#### Training Management Components

##### TrainingSessionPlanner Component

```tsx
// src/components/training/TrainingSessionPlanner.tsx

import React, { useState, useEffect } from 'react';
import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';
import { Calendar, Clock, MapPin, Users, Target, ChevronDown } from 'lucide-react';

interface TrainingSessionPlannerProps {
  teamId: number;
  onSave: (session: TrainingSession) => void;
  initialSession?: TrainingSession;
}

interface Drill {
  id: number;
  name: string;
  category: string;
  estimatedDuration: number;
  minPlayers: number;
  maxPlayers: number;
  difficulty: string;
  description: string;
  hasVideo: boolean;
}

interface TrainingSession {
  id?: number;
  title: string;
  scheduledAt: string;
  venue: string;
  duration: number;
  focusAreas: string[];
  drills: Drill[];
  notes: string;
}

export const TrainingSessionPlanner: React.FC<TrainingSessionPlannerProps> = ({
  teamId,
  onSave,
  initialSession
}) => {
  const [session, setSession] = useState<TrainingSession>({
    title: '',
    scheduledAt: '',
    venue: '',
    duration: 90,
    focusAreas: [],
    drills: [],
    notes: ''
  });

  const [availableDrills, setAvailableDrills] = useState<Drill[]>([]);
  const [selectedCategory, setSelectedCategory] = useState<string>('all');
  const [searchTerm, setSearchTerm] = useState<string>('');
  const [showDrillLibrary, setShowDrillLibrary] = useState<boolean>(false);

  const drillCategories = [
    'all', 'warm_up', 'ball_handling', 'shooting', 'passing', 
    'defense', 'conditioning', 'scrimmage', 'cool_down'
  ];

  const focusAreaOptions = [
    'offense', 'defense', 'transition', 'rebounding', 
    'conditioning', 'team_chemistry', 'individual_skills'
  ];

  useEffect(() => {
    if (initialSession) {
      setSession(initialSession);
    }
    fetchAvailableDrills();
  }, [initialSession, teamId]);

  const fetchAvailableDrills = async () => {
    try {
      const response = await fetch(`/api/v2/teams/${teamId}/drills/recommended`);
      const drills = await response.json();
      setAvailableDrills(drills.data);
    } catch (error) {
      console.error('Error fetching drills:', error);
    }
  };

  const filteredDrills = availableDrills.filter(drill => {
    const matchesCategory = selectedCategory === 'all' || drill.category === selectedCategory;
    const matchesSearch = drill.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         drill.description.toLowerCase().includes(searchTerm.toLowerCase());
    return matchesCategory && matchesSearch && !session.drills.find(d => d.id === drill.id);
  });

  const handleDragEnd = (result: any) => {
    if (!result.destination) return;

    const { source, destination } = result;

    if (source.droppableId === 'drill-library' && destination.droppableId === 'session-drills') {
      // Add drill to session
      const drill = filteredDrills[source.index];
      const newDrills = [...session.drills];
      newDrills.splice(destination.index, 0, drill);
      setSession(prev => ({ ...prev, drills: newDrills }));
    } else if (source.droppableId === 'session-drills' && destination.droppableId === 'session-drills') {
      // Reorder drills within session
      const newDrills = [...session.drills];
      const [removed] = newDrills.splice(source.index, 1);
      newDrills.splice(destination.index, 0, removed);
      setSession(prev => ({ ...prev, drills: newDrills }));
    }
  };

  const removeDrillFromSession = (drillId: number) => {
    setSession(prev => ({
      ...prev,
      drills: prev.drills.filter(drill => drill.id !== drillId)
    }));
  };

  const getTotalDuration = () => {
    return session.drills.reduce((total, drill) => total + drill.estimatedDuration, 0);
  };

  const handleFocusAreaToggle = (area: string) => {
    setSession(prev => ({
      ...prev,
      focusAreas: prev.focusAreas.includes(area)
        ? prev.focusAreas.filter(a => a !== area)
        : [...prev.focusAreas, area]
    }));
  };

  const handleSave = () => {
    if (!session.title || !session.scheduledAt || !session.venue) {
      alert('Bitte füllen Sie alle erforderlichen Felder aus.');
      return;
    }
    onSave(session);
  };

  return (
    <div className="training-session-planner bg-white rounded-lg shadow-lg p-6">
      <div className="mb-8">
        <h2 className="text-2xl font-bold text-gray-900 mb-6">Trainingseinheit planen</h2>

        {/* Basic Session Info */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Titel der Trainingseinheit *
            </label>
            <input
              type="text"
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              value={session.title}
              onChange={(e) => setSession(prev => ({ ...prev, title: e.target.value }))}
              placeholder="z.B. Offensive Grundlagen"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              <Calendar className="inline w-4 h-4 mr-1" />
              Datum & Uhrzeit *
            </label>
            <input
              type="datetime-local"
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              value={session.scheduledAt}
              onChange={(e) => setSession(prev => ({ ...prev, scheduledAt: e.target.value }))}
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              <MapPin className="inline w-4 h-4 mr-1" />
              Ort *
            </label>
            <input
              type="text"
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              value={session.venue}
              onChange={(e) => setSession(prev => ({ ...prev, venue: e.target.value }))}
              placeholder="Vereinshalle"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              <Clock className="inline w-4 h-4 mr-1" />
              Geplante Dauer (Minuten)
            </label>
            <input
              type="number"
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              value={session.duration}
              onChange={(e) => setSession(prev => ({ ...prev, duration: parseInt(e.target.value) }))}
              min="30"
              max="180"
            />
          </div>
        </div>

        {/* Focus Areas */}
        <div className="mb-6">
          <label className="block text-sm font-medium text-gray-700 mb-3">
            <Target className="inline w-4 h-4 mr-1" />
            Trainingsschwerpunkte
          </label>
          <div className="flex flex-wrap gap-2">
            {focusAreaOptions.map(area => (
              <button
                key={area}
                type="button"
                onClick={() => handleFocusAreaToggle(area)}
                className={`px-3 py-1 rounded-full text-sm font-medium transition-colors ${
                  session.focusAreas.includes(area)
                    ? 'bg-blue-600 text-white'
                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                }`}
              >
                {area.charAt(0).toUpperCase() + area.slice(1).replace('_', ' ')}
              </button>
            ))}
          </div>
        </div>
      </div>

      <DragDropContext onDragEnd={handleDragEnd}>
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          {/* Drill Library */}
          <div className="bg-gray-50 rounded-lg p-4">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-lg font-semibold text-gray-900">Übungsbibliothek</h3>
              <button
                onClick={() => setShowDrillLibrary(!showDrillLibrary)}
                className="text-gray-500 hover:text-gray-700"
              >
                <ChevronDown className={`w-5 h-5 transition-transform ${showDrillLibrary ? 'rotate-180' : ''}`} />
              </button>
            </div>

            {showDrillLibrary && (
              <>
                {/* Filters */}
                <div className="mb-4 space-y-3">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Kategorie</label>
                    <select
                      className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                      value={selectedCategory}
                      onChange={(e) => setSelectedCategory(e.target.value)}
                    >
                      {drillCategories.map(category => (
                        <option key={category} value={category}>
                          {category === 'all' ? 'Alle Kategorien' : category.replace('_', ' ').toUpperCase()}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <input
                      type="text"
                      placeholder="Übung suchen..."
                      className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                      value={searchTerm}
                      onChange={(e) => setSearchTerm(e.target.value)}
                    />
                  </div>
                </div>

                {/* Available Drills */}
                <Droppable droppableId="drill-library">
                  {(provided) => (
                    <div {...provided.droppableProps} ref={provided.innerRef} className="space-y-2 max-h-96 overflow-y-auto">
                      {filteredDrills.map((drill, index) => (
                        <Draggable key={drill.id} draggableId={`drill-${drill.id}`} index={index}>
                          {(provided, snapshot) => (
                            <div
                              ref={provided.innerRef}
                              {...provided.draggableProps}
                              {...provided.dragHandleProps}
                              className={`p-3 bg-white border rounded-lg cursor-move hover:shadow-md transition-shadow ${
                                snapshot.isDragging ? 'shadow-lg rotate-2' : ''
                              }`}
                            >
                              <div className="flex justify-between items-start mb-2">
                                <h4 className="font-medium text-gray-900 text-sm">{drill.name}</h4>
                                <div className="flex items-center space-x-2 text-xs text-gray-500">
                                  <Clock className="w-3 h-3" />
                                  <span>{drill.estimatedDuration}min</span>
                                  {drill.hasVideo && (
                                    <span className="bg-green-100 text-green-800 px-1 rounded">Video</span>
                                  )}
                                </div>
                              </div>
                              <p className="text-xs text-gray-600 mb-2">{drill.description}</p>
                              <div className="flex justify-between items-center text-xs">
                                <span className="bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                  {drill.category.replace('_', ' ')}
                                </span>
                                <div className="flex items-center space-x-1">
                                  <Users className="w-3 h-3" />
                                  <span>{drill.minPlayers}-{drill.maxPlayers || '∞'}</span>
                                </div>
                              </div>
                            </div>
                          )}
                        </Draggable>
                      ))}
                      {provided.placeholder}
                    </div>
                  )}
                </Droppable>
              </>
            )}
          </div>

          {/* Session Drills */}
          <div className="bg-white border rounded-lg p-4">
            <div className="flex justify-between items-center mb-4">
              <h3 className="text-lg font-semibold text-gray-900">Trainingsablauf</h3>
              <div className="text-sm text-gray-600">
                Geplante Dauer: {getTotalDuration()} min / {session.duration} min
                <div className={`w-full bg-gray-200 rounded-full h-2 mt-1 ${getTotalDuration() > session.duration ? 'bg-red-200' : ''}`}>
                  <div
                    className={`h-2 rounded-full transition-all ${
                      getTotalDuration() > session.duration ? 'bg-red-500' : 'bg-green-500'
                    }`}
                    style={{ width: `${Math.min((getTotalDuration() / session.duration) * 100, 100)}%` }}
                  ></div>
                </div>
              </div>
            </div>

            <Droppable droppableId="session-drills">
              {(provided) => (
                <div {...provided.droppableProps} ref={provided.innerRef} className="space-y-2 min-h-32">
                  {session.drills.map((drill, index) => (
                    <Draggable key={drill.id} draggableId={`session-drill-${drill.id}`} index={index}>
                      {(provided, snapshot) => (
                        <div
                          ref={provided.innerRef}
                          {...provided.draggableProps}
                          {...provided.dragHandleProps}
                          className={`p-3 bg-gray-50 border rounded-lg ${
                            snapshot.isDragging ? 'shadow-lg' : ''
                          }`}
                        >
                          <div className="flex justify-between items-start">
                            <div className="flex-1">
                              <div className="flex items-center space-x-2 mb-1">
                                <span className="bg-blue-600 text-white text-xs px-2 py-1 rounded font-medium">
                                  {index + 1}
                                </span>
                                <h4 className="font-medium text-gray-900">{drill.name}</h4>
                              </div>
                              <p className="text-sm text-gray-600 mb-2">{drill.description}</p>
                              <div className="flex items-center space-x-4 text-xs text-gray-500">
                                <div className="flex items-center space-x-1">
                                  <Clock className="w-3 h-3" />
                                  <span>{drill.estimatedDuration} min</span>
                                </div>
                                <div className="flex items-center space-x-1">
                                  <Users className="w-3 h-3" />
                                  <span>{drill.minPlayers}-{drill.maxPlayers || '∞'}</span>
                                </div>
                                <span className="bg-gray-200 px-2 py-1 rounded">
                                  {drill.category.replace('_', ' ')}
                                </span>
                              </div>
                            </div>
                            <button
                              onClick={() => removeDrillFromSession(drill.id)}
                              className="text-red-500 hover:text-red-700 ml-2"
                            >
                              ✕
                            </button>
                          </div>
                        </div>
                      )}
                    </Draggable>
                  ))}
                  {provided.placeholder}
                  {session.drills.length === 0 && (
                    <div className="text-center py-8 text-gray-500">
                      <p>Ziehen Sie Übungen aus der Bibliothek hierher</p>
                    </div>
                  )}
                </div>
              )}
            </Droppable>
          </div>
        </div>
      </DragDropContext>

      {/* Notes */}
      <div className="mt-6">
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Notizen & besondere Hinweise
        </label>
        <textarea
          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          rows={3}
          value={session.notes}
          onChange={(e) => setSession(prev => ({ ...prev, notes: e.target.value }))}
          placeholder="Zusätzliche Informationen, Ausrüstung, besondere Beachtungen..."
        />
      </div>

      {/* Action Buttons */}
      <div className="flex justify-end space-x-4 mt-8">
        <button
          type="button"
          className="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors"
        >
          Abbrechen
        </button>
        <button
          onClick={handleSave}
          className="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
        >
          Trainingseinheit speichern
        </button>
      </div>
    </div>
  );
};
```

#### Interactive Shot Chart Visualizations

##### ShotChart Component

```tsx
// src/components/analytics/ShotChart.tsx

import React, { useState, useEffect, useRef } from 'react';
import { Crosshair, Filter, BarChart3, Download, RotateCcw } from 'lucide-react';
import * as d3 from 'd3';

interface ShotData {
  id: number;
  x: number;
  y: number;
  made: boolean;
  points: number;
  shotType: string;
  distance: number;
  quarter: number;
  timeRemaining: string;
  gameId: number;
  gameDate: string;
  opponent: string;
}

interface ShotChartProps {
  playerId?: number;
  teamId?: number;
  season?: string;
  gameId?: number;
  onShotClick?: (shot: ShotData) => void;
  interactive?: boolean;
  showHeatMap?: boolean;
  colorBy?: 'made' | 'points' | 'frequency';
}

export const ShotChart: React.FC<ShotChartProps> = ({
  playerId,
  teamId,
  season,
  gameId,
  onShotClick,
  interactive = true,
  showHeatMap = false,
  colorBy = 'made'
}) => {
  const svgRef = useRef<SVGSVGElement>(null);
  const [shots, setShots] = useState<ShotData[]>([]);
  const [filteredShots, setFilteredShots] = useState<ShotData[]>([]);
  const [loading, setLoading] = useState(false);
  const [filters, setFilters] = useState({
    shotType: 'all',
    made: 'all',
    quarter: 'all',
    minDistance: 0,
    maxDistance: 30
  });
  const [showFilters, setShowFilters] = useState(false);
  const [hoveredShot, setHoveredShot] = useState<ShotData | null>(null);

  // Court dimensions (standard NBA court scaled to fit SVG)
  const courtWidth = 500;
  const courtHeight = 470;
  const basketX = courtWidth / 2;
  const basketY = 50;

  useEffect(() => {
    fetchShotData();
  }, [playerId, teamId, season, gameId]);

  useEffect(() => {
    applyFilters();
  }, [shots, filters]);

  useEffect(() => {
    if (filteredShots.length > 0) {
      drawShotChart();
    }
  }, [filteredShots, showHeatMap, colorBy]);

  const fetchShotData = async () => {
    setLoading(true);
    try {
      let url = '/api/v2/shot-chart';
      const params = new URLSearchParams();
      
      if (playerId) params.append('player_id', playerId.toString());
      if (teamId) params.append('team_id', teamId.toString());
      if (season) params.append('season', season);
      if (gameId) params.append('game_id', gameId.toString());
      
      const response = await fetch(`${url}?${params}`);
      const data = await response.json();
      setShots(data.data);
    } catch (error) {
      console.error('Error fetching shot data:', error);
    } finally {
      setLoading(false);
    }
  };

  const applyFilters = () => {
    let filtered = [...shots];

    if (filters.shotType !== 'all') {
      filtered = filtered.filter(shot => shot.shotType === filters.shotType);
    }

    if (filters.made !== 'all') {
      filtered = filtered.filter(shot => 
        filters.made === 'made' ? shot.made : !shot.made
      );
    }

    if (filters.quarter !== 'all') {
      filtered = filtered.filter(shot => shot.quarter === parseInt(filters.quarter));
    }

    filtered = filtered.filter(shot => 
      shot.distance >= filters.minDistance && shot.distance <= filters.maxDistance
    );

    setFilteredShots(filtered);
  };

  const drawShotChart = () => {
    const svg = d3.select(svgRef.current);
    svg.selectAll('*').remove();

    // Draw court
    drawCourt(svg);

    if (showHeatMap) {
      drawHeatMap(svg);
    } else {
      drawShots(svg);
    }

    // Add legend
    drawLegend(svg);
  };

  const drawCourt = (svg: d3.Selection<SVGSVGElement | null, unknown, null, undefined>) => {
    const court = svg.append('g').attr('class', 'court');

    // Court outline
    court.append('rect')
      .attr('width', courtWidth)
      .attr('height', courtHeight)
      .attr('fill', '#f5f5f5')
      .attr('stroke', '#333')
      .attr('stroke-width', 2);

    // Three-point arc
    court.append('path')
      .attr('d', `M 60 ${basketY} A 175 175 0 0 1 ${courtWidth - 60} ${basketY}`)
      .attr('fill', 'none')
      .attr('stroke', '#333')
      .attr('stroke-width', 2);

    // Free throw circle
    court.append('circle')
      .attr('cx', basketX)
      .attr('cy', basketY + 140)
      .attr('r', 60)
      .attr('fill', 'none')
      .attr('stroke', '#333')
      .attr('stroke-width', 2);

    // Paint (the key)
    court.append('rect')
      .attr('x', basketX - 80)
      .attr('y', basketY)
      .attr('width', 160)
      .attr('height', 190)
      .attr('fill', 'none')
      .attr('stroke', '#333')
      .attr('stroke-width', 2);

    // Basket
    court.append('circle')
      .attr('cx', basketX)
      .attr('cy', basketY)
      .attr('r', 7.5)
      .attr('fill', '#ff6b35')
      .attr('stroke', '#333')
      .attr('stroke-width', 2);

    // Baseline
    court.append('line')
      .attr('x1', 0)
      .attr('y1', basketY - 15)
      .attr('x2', courtWidth)
      .attr('y2', basketY - 15)
      .attr('stroke', '#333')
      .attr('stroke-width', 3);
  };

  const drawShots = (svg: d3.Selection<SVGSVGElement | null, unknown, null, undefined>) => {
    const shots = svg.append('g').attr('class', 'shots');

    // Convert court coordinates to SVG coordinates
    const xScale = d3.scaleLinear()
      .domain([-25, 25]) // Half court width in feet
      .range([0, courtWidth]);

    const yScale = d3.scaleLinear()
      .domain([0, 47]) // Full court length in feet
      .range([basketY, courtHeight]);

    // Color scale based on colorBy prop
    let colorScale: d3.ScaleOrdinal<string, string> | d3.ScaleSequential<string>;
    
    switch (colorBy) {
      case 'made':
        colorScale = d3.scaleOrdinal<string>()
          .domain(['true', 'false'])
          .range(['#22c55e', '#ef4444']);
        break;
      case 'points':
        colorScale = d3.scaleSequential(d3.interpolateBlues)
          .domain([1, 3]);
        break;
      case 'frequency':
        const frequencies = d3.rollup(filteredShots, v => v.length, d => `${Math.round(d.x)},${Math.round(d.y)}`);
        colorScale = d3.scaleSequential(d3.interpolateReds)
          .domain([1, d3.max(Array.from(frequencies.values())) || 1]);
        break;
      default:
        colorScale = d3.scaleOrdinal<string>().range(['#3b82f6']);
    }

    shots.selectAll('circle')
      .data(filteredShots)
      .enter()
      .append('circle')
      .attr('cx', d => xScale(d.x))
      .attr('cy', d => yScale(d.y))
      .attr('r', 4)
      .attr('fill', d => {
        switch (colorBy) {
          case 'made':
            return colorScale(d.made.toString()) as string;
          case 'points':
            return colorScale(d.points) as string;
          case 'frequency':
            const freq = d3.rollup(filteredShots.filter(s => 
              Math.abs(s.x - d.x) < 1 && Math.abs(s.y - d.y) < 1
            ), v => v.length);
            return colorScale(freq.size) as string;
          default:
            return '#3b82f6';
        }
      })
      .attr('stroke', '#fff')
      .attr('stroke-width', 1)
      .attr('opacity', 0.8)
      .style('cursor', interactive ? 'pointer' : 'default')
      .on('mouseover', function(event, d) {
        if (interactive) {
          setHoveredShot(d);
          d3.select(this)
            .attr('r', 6)
            .attr('stroke-width', 2);
        }
      })
      .on('mouseout', function() {
        if (interactive) {
          setHoveredShot(null);
          d3.select(this)
            .attr('r', 4)
            .attr('stroke-width', 1);
        }
      })
      .on('click', function(event, d) {
        if (interactive && onShotClick) {
          onShotClick(d);
        }
      });
  };

  const drawHeatMap = (svg: d3.Selection<SVGSVGElement | null, unknown, null, undefined>) => {
    // Create grid for heat map
    const gridSize = 15;
    const heatMapData: { x: number; y: number; value: number; made: number; total: number }[] = [];

    // Create grid cells
    for (let x = 0; x < courtWidth; x += gridSize) {
      for (let y = basketY; y < courtHeight; y += gridSize) {
        const shotsInCell = filteredShots.filter(shot => {
          const shotX = (shot.x + 25) * (courtWidth / 50);
          const shotY = basketY + (shot.y * (courtHeight - basketY) / 47);
          return shotX >= x && shotX < x + gridSize && shotY >= y && shotY < y + gridSize;
        });

        if (shotsInCell.length > 0) {
          heatMapData.push({
            x,
            y,
            value: shotsInCell.length,
            made: shotsInCell.filter(s => s.made).length,
            total: shotsInCell.length
          });
        }
      }
    }

    if (heatMapData.length === 0) return;

    const colorScale = d3.scaleSequential(d3.interpolateReds)
      .domain([1, d3.max(heatMapData, d => d.value) || 1]);

    const heatMap = svg.append('g').attr('class', 'heat-map');

    heatMap.selectAll('rect')
      .data(heatMapData)
      .enter()
      .append('rect')
      .attr('x', d => d.x)
      .attr('y', d => d.y)
      .attr('width', gridSize)
      .attr('height', gridSize)
      .attr('fill', d => colorScale(d.value))
      .attr('opacity', 0.7)
      .on('mouseover', function(event, d) {
        const tooltip = d3.select('body').append('div')
          .attr('class', 'shot-tooltip')
          .style('position', 'absolute')
          .style('background', 'rgba(0,0,0,0.8)')
          .style('color', 'white')
          .style('padding', '8px')
          .style('border-radius', '4px')
          .style('font-size', '12px')
          .style('pointer-events', 'none')
          .style('left', (event.pageX + 10) + 'px')
          .style('top', (event.pageY - 10) + 'px')
          .html(`
            <div>Versuche: ${d.total}</div>
            <div>Getroffen: ${d.made}</div>
            <div>Quote: ${((d.made / d.total) * 100).toFixed(1)}%</div>
          `);
      })
      .on('mouseout', function() {
        d3.select('.shot-tooltip').remove();
      });
  };

  const drawLegend = (svg: d3.Selection<SVGSVGElement | null, unknown, null, undefined>) => {
    const legend = svg.append('g')
      .attr('class', 'legend')
      .attr('transform', `translate(${courtWidth - 120}, 20)`);

    if (colorBy === 'made') {
      const legendData = [
        { label: 'Getroffen', color: '#22c55e' },
        { label: 'Verfehlt', color: '#ef4444' }
      ];

      legend.selectAll('.legend-item')
        .data(legendData)
        .enter()
        .append('g')
        .attr('class', 'legend-item')
        .attr('transform', (d, i) => `translate(0, ${i * 20})`)
        .each(function(d) {
          const item = d3.select(this);
          item.append('circle')
            .attr('r', 6)
            .attr('fill', d.color);
          item.append('text')
            .attr('x', 15)
            .attr('y', 4)
            .attr('font-size', '12px')
            .text(d.label);
        });
    }

    // Add statistics
    const stats = legend.append('g')
      .attr('transform', `translate(0, ${colorBy === 'made' ? 60 : 20})`);

    const totalShots = filteredShots.length;
    const madeShots = filteredShots.filter(s => s.made).length;
    const shootingPercentage = totalShots > 0 ? ((madeShots / totalShots) * 100).toFixed(1) : '0.0';

    stats.append('text')
      .attr('y', 0)
      .attr('font-size', '12px')
      .attr('font-weight', 'bold')
      .text('Statistiken:');

    stats.append('text')
      .attr('y', 15)
      .attr('font-size', '11px')
      .text(`Versuche: ${totalShots}`);

    stats.append('text')
      .attr('y', 30)
      .attr('font-size', '11px')
      .text(`Quote: ${shootingPercentage}%`);
  };

  const resetFilters = () => {
    setFilters({
      shotType: 'all',
      made: 'all',
      quarter: 'all',
      minDistance: 0,
      maxDistance: 30
    });
  };

  const exportChart = () => {
    const svgData = new XMLSerializer().serializeToString(svgRef.current!);
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d')!;
    const img = new Image();
    
    img.onload = () => {
      canvas.width = img.width;
      canvas.height = img.height;
      ctx.drawImage(img, 0, 0);
      
      const link = document.createElement('a');
      link.download = 'shot-chart.png';
      link.href = canvas.toDataURL();
      link.click();
    };
    
    img.src = 'data:image/svg+xml;base64,' + btoa(svgData);
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="shot-chart bg-white rounded-lg shadow-lg p-6">
      <div className="flex justify-between items-center mb-6">
        <h3 className="text-xl font-bold text-gray-900">Shot Chart</h3>
        <div className="flex items-center space-x-2">
          <button
            onClick={() => setShowFilters(!showFilters)}
            className="flex items-center space-x-2 px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
          >
            <Filter className="w-4 h-4" />
            <span>Filter</span>
          </button>
          <button
            onClick={resetFilters}
            className="flex items-center space-x-2 px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
          >
            <RotateCcw className="w-4 h-4" />
            <span>Reset</span>
          </button>
          <button
            onClick={exportChart}
            className="flex items-center space-x-2 px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
          >
            <Download className="w-4 h-4" />
            <span>Export</span>
          </button>
        </div>
      </div>

      {/* Filters */}
      {showFilters && (
        <div className="grid grid-cols-2 md:grid-cols-5 gap-4 p-4 bg-gray-50 rounded-lg mb-6">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Wurfart</label>
            <select
              value={filters.shotType}
              onChange={(e) => setFilters(prev => ({ ...prev, shotType: e.target.value }))}
              className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            >
              <option value="all">Alle</option>
              <option value="layup">Layup</option>
              <option value="jump_shot">Jump Shot</option>
              <option value="three_pointer">Dreier</option>
              <option value="dunk">Dunk</option>
              <option value="hook_shot">Hook Shot</option>
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Ergebnis</label>
            <select
              value={filters.made}
              onChange={(e) => setFilters(prev => ({ ...prev, made: e.target.value }))}
              className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            >
              <option value="all">Alle</option>
              <option value="made">Getroffen</option>
              <option value="missed">Verfehlt</option>
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Viertel</label>
            <select
              value={filters.quarter}
              onChange={(e) => setFilters(prev => ({ ...prev, quarter: e.target.value }))}
              className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            >
              <option value="all">Alle</option>
              <option value="1">1. Viertel</option>
              <option value="2">2. Viertel</option>
              <option value="3">3. Viertel</option>
              <option value="4">4. Viertel</option>
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Min. Distanz</label>
            <input
              type="range"
              min="0"
              max="30"
              value={filters.minDistance}
              onChange={(e) => setFilters(prev => ({ ...prev, minDistance: parseInt(e.target.value) }))}
              className="w-full"
            />
            <span className="text-xs text-gray-500">{filters.minDistance} ft</span>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Max. Distanz</label>
            <input
              type="range"
              min="0"
              max="30"
              value={filters.maxDistance}
              onChange={(e) => setFilters(prev => ({ ...prev, maxDistance: parseInt(e.target.value) }))}
              className="w-full"
            />
            <span className="text-xs text-gray-500">{filters.maxDistance} ft</span>
          </div>
        </div>
      )}

      {/* Chart Controls */}
      <div className="flex justify-center space-x-4 mb-4">
        <label className="flex items-center">
          <input
            type="checkbox"
            checked={showHeatMap}
            onChange={(e) => setShowHeatMap(e.target.checked)}
            className="mr-2"
          />
          Heat Map anzeigen
        </label>
        
        <div className="flex items-center space-x-2">
          <span className="text-sm text-gray-700">Farbe nach:</span>
          <select
            value={colorBy}
            onChange={(e) => setColorBy(e.target.value as 'made' | 'points' | 'frequency')}
            className="px-2 py-1 border border-gray-300 rounded text-sm"
          >
            <option value="made">Getroffen/Verfehlt</option>
            <option value="points">Punkte</option>
            <option value="frequency">Häufigkeit</option>
          </select>
        </div>
      </div>

      {/* SVG Chart */}
      <div className="flex justify-center">
        <svg
          ref={svgRef}
          width={courtWidth}
          height={courtHeight}
          className="border border-gray-200 rounded"
        />
      </div>

      {/* Hover Tooltip */}
      {hoveredShot && (
        <div className="mt-4 p-3 bg-gray-100 rounded-lg">
          <h4 className="font-semibold text-sm mb-2">Wurf-Details</h4>
          <div className="grid grid-cols-2 gap-2 text-xs">
            <div>Art: {hoveredShot.shotType}</div>
            <div>Ergebnis: {hoveredShot.made ? 'Getroffen' : 'Verfehlt'}</div>
            <div>Punkte: {hoveredShot.points}</div>
            <div>Distanz: {hoveredShot.distance.toFixed(1)} ft</div>
            <div>Viertel: {hoveredShot.quarter}</div>
            <div>Zeit: {hoveredShot.timeRemaining}</div>
          </div>
        </div>
      )}

      {/* Summary Stats */}
      <div className="mt-6 grid grid-cols-3 gap-4 text-center">
        <div className="p-3 bg-blue-50 rounded-lg">
          <div className="text-2xl font-bold text-blue-600">{filteredShots.length}</div>
          <div className="text-sm text-gray-600">Würfe gesamt</div>
        </div>
        <div className="p-3 bg-green-50 rounded-lg">
          <div className="text-2xl font-bold text-green-600">
            {filteredShots.filter(s => s.made).length}
          </div>
          <div className="text-sm text-gray-600">Getroffen</div>
        </div>
        <div className="p-3 bg-yellow-50 rounded-lg">
          <div className="text-2xl font-bold text-yellow-600">
            {filteredShots.length > 0 
              ? ((filteredShots.filter(s => s.made).length / filteredShots.length) * 100).toFixed(1)
              : '0.0'
            }%
          </div>
          <div className="text-sm text-gray-600">Trefferquote</div>
        </div>
      </div>
    </div>
  );
};
```

##### HeatMapVisualization Component

```tsx
// src/components/analytics/HeatMapVisualization.tsx

import React, { useState, useEffect, useRef } from 'react';
import { TrendingUp, Filter, Download, RefreshCw } from 'lucide-react';
import * as d3 from 'd3';

interface HeatMapData {
  x: number;
  y: number;
  intensity: number;
  frequency: number;
  efficiency: number;
  avgPoints: number;
}

interface HeatMapVisualizationProps {
  playerId?: number;
  teamId?: number;
  season?: string;
  heatMapType: 'shots' | 'movement' | 'defensive_actions' | 'rebounds';
  metric: 'frequency' | 'efficiency' | 'success_rate';
  onZoneClick?: (zone: HeatMapData) => void;
}

export const HeatMapVisualization: React.FC<HeatMapVisualizationProps> = ({
  playerId,
  teamId,
  season,
  heatMapType,
  metric,
  onZoneClick
}) => {
  const svgRef = useRef<SVGSVGElement>(null);
  const [heatMapData, setHeatMapData] = useState<HeatMapData[]>([]);
  const [loading, setLoading] = useState(false);
  const [selectedZone, setSelectedZone] = useState<HeatMapData | null>(null);
  const [viewMode, setViewMode] = useState<'2d' | '3d'>('2d');

  const courtWidth = 500;
  const courtHeight = 470;
  const gridSize = 20;

  useEffect(() => {
    fetchHeatMapData();
  }, [playerId, teamId, season, heatMapType, metric]);

  useEffect(() => {
    if (heatMapData.length > 0) {
      drawHeatMap();
    }
  }, [heatMapData, viewMode]);

  const fetchHeatMapData = async () => {
    setLoading(true);
    try {
      const params = new URLSearchParams();
      if (playerId) params.append('player_id', playerId.toString());
      if (teamId) params.append('team_id', teamId.toString());
      if (season) params.append('season', season);
      params.append('type', heatMapType);
      params.append('metric', metric);

      const response = await fetch(`/api/v2/heat-map?${params}`);
      const data = await response.json();
      setHeatMapData(data.data);
    } catch (error) {
      console.error('Error fetching heat map data:', error);
    } finally {
      setLoading(false);
    }
  };

  const drawHeatMap = () => {
    const svg = d3.select(svgRef.current);
    svg.selectAll('*').remove();

    // Draw court outline
    drawCourtOutline(svg);

    // Create color scale
    const maxValue = d3.max(heatMapData, d => d[metric as keyof HeatMapData] as number) || 1;
    const colorScale = d3.scaleSequential(d3.interpolateReds)
      .domain([0, maxValue]);

    // Draw heat map cells
    const heatMap = svg.append('g').attr('class', 'heat-map');

    heatMap.selectAll('rect')
      .data(heatMapData)
      .enter()
      .append('rect')
      .attr('x', d => d.x)
      .attr('y', d => d.y)
      .attr('width', gridSize)
      .attr('height', gridSize)
      .attr('fill', d => colorScale(d[metric as keyof HeatMapData] as number))
      .attr('opacity', 0.8)
      .attr('stroke', '#fff')
      .attr('stroke-width', 0.5)
      .style('cursor', 'pointer')
      .on('mouseover', function(event, d) {
        setSelectedZone(d);
        d3.select(this)
          .attr('stroke-width', 2)
          .attr('stroke', '#333');
      })
      .on('mouseout', function() {
        setSelectedZone(null);
        d3.select(this)
          .attr('stroke-width', 0.5)
          .attr('stroke', '#fff');
      })
      .on('click', function(event, d) {
        if (onZoneClick) {
          onZoneClick(d);
        }
      });

    // Add intensity values for high-value zones
    heatMap.selectAll('text')
      .data(heatMapData.filter(d => (d[metric as keyof HeatMapData] as number) > maxValue * 0.7))
      .enter()
      .append('text')
      .attr('x', d => d.x + gridSize / 2)
      .attr('y', d => d.y + gridSize / 2)
      .attr('text-anchor', 'middle')
      .attr('dominant-baseline', 'central')
      .attr('font-size', '10px')
      .attr('font-weight', 'bold')
      .attr('fill', 'white')
      .attr('text-shadow', '1px 1px 1px rgba(0,0,0,0.5)')
      .text(d => Math.round(d[metric as keyof HeatMapData] as number));

    // Add color legend
    drawColorLegend(svg, colorScale, maxValue);
  };

  const drawCourtOutline = (svg: d3.Selection<SVGSVGElement | null, unknown, null, undefined>) => {
    const court = svg.append('g').attr('class', 'court');

    // Court outline
    court.append('rect')
      .attr('width', courtWidth)
      .attr('height', courtHeight)
      .attr('fill', 'none')
      .attr('stroke', '#333')
      .attr('stroke-width', 2);

    // Three-point line
    court.append('path')
      .attr('d', `M 60 50 A 175 175 0 0 1 ${courtWidth - 60} 50`)
      .attr('fill', 'none')
      .attr('stroke', '#333')
      .attr('stroke-width', 2);

    // Free throw circle
    court.append('circle')
      .attr('cx', courtWidth / 2)
      .attr('cy', 190)
      .attr('r', 60)
      .attr('fill', 'none')
      .attr('stroke', '#333')
      .attr('stroke-width', 2);

    // Paint
    court.append('rect')
      .attr('x', courtWidth / 2 - 80)
      .attr('y', 50)
      .attr('width', 160)
      .attr('height', 190)
      .attr('fill', 'none')
      .attr('stroke', '#333')
      .attr('stroke-width', 2);

    // Basket
    court.append('circle')
      .attr('cx', courtWidth / 2)
      .attr('cy', 50)
      .attr('r', 7.5)
      .attr('fill', '#ff6b35')
      .attr('stroke', '#333')
      .attr('stroke-width', 2);
  };

  const drawColorLegend = (
    svg: d3.Selection<SVGSVGElement | null, unknown, null, undefined>,
    colorScale: d3.ScaleSequential<string>,
    maxValue: number
  ) => {
    const legendWidth = 200;
    const legendHeight = 20;
    const legendX = courtWidth - legendWidth - 20;
    const legendY = 20;

    const legend = svg.append('g')
      .attr('class', 'legend')
      .attr('transform', `translate(${legendX}, ${legendY})`);

    // Create gradient
    const gradient = svg.append('defs')
      .append('linearGradient')
      .attr('id', 'heat-gradient')
      .attr('gradientUnits', 'userSpaceOnUse')
      .attr('x1', 0).attr('y1', 0)
      .attr('x2', legendWidth).attr('y2', 0);

    const numStops = 10;
    for (let i = 0; i <= numStops; i++) {
      gradient.append('stop')
        .attr('offset', `${(i / numStops) * 100}%`)
        .attr('stop-color', colorScale(maxValue * i / numStops));
    }

    // Legend rectangle
    legend.append('rect')
      .attr('width', legendWidth)
      .attr('height', legendHeight)
      .attr('fill', 'url(#heat-gradient)')
      .attr('stroke', '#333')
      .attr('stroke-width', 1);

    // Legend labels
    legend.append('text')
      .attr('x', 0)
      .attr('y', legendHeight + 15)
      .attr('font-size', '12px')
      .text('0');

    legend.append('text')
      .attr('x', legendWidth)
      .attr('y', legendHeight + 15)
      .attr('text-anchor', 'end')
      .attr('font-size', '12px')
      .text(maxValue.toFixed(1));

    // Legend title
    legend.append('text')
      .attr('x', legendWidth / 2)
      .attr('y', -5)
      .attr('text-anchor', 'middle')
      .attr('font-size', '12px')
      .attr('font-weight', 'bold')
      .text(getMetricDisplayName(metric));
  };

  const getMetricDisplayName = (metric: string): string => {
    switch (metric) {
      case 'frequency': return 'Häufigkeit';
      case 'efficiency': return 'Effizienz';
      case 'success_rate': return 'Erfolgsquote (%)';
      default: return metric;
    }
  };

  const getHeatMapTypeDisplayName = (type: string): string => {
    switch (type) {
      case 'shots': return 'Würfe';
      case 'movement': return 'Bewegung';
      case 'defensive_actions': return 'Defensive Aktionen';
      case 'rebounds': return 'Rebounds';
      default: return type;
    }
  };

  const exportHeatMap = () => {
    const svgData = new XMLSerializer().serializeToString(svgRef.current!);
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d')!;
    const img = new Image();
    
    img.onload = () => {
      canvas.width = img.width;
      canvas.height = img.height;
      ctx.drawImage(img, 0, 0);
      
      const link = document.createElement('a');
      link.download = `heat-map-${heatMapType}-${metric}.png`;
      link.href = canvas.toDataURL();
      link.click();
    };
    
    img.src = 'data:image/svg+xml;base64,' + btoa(svgData);
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-red-600"></div>
      </div>
    );
  }

  return (
    <div className="heat-map-visualization bg-white rounded-lg shadow-lg p-6">
      <div className="flex justify-between items-center mb-6">
        <div>
          <h3 className="text-xl font-bold text-gray-900">
            Heat Map: {getHeatMapTypeDisplayName(heatMapType)}
          </h3>
          <p className="text-sm text-gray-600">
            Metrik: {getMetricDisplayName(metric)}
          </p>
        </div>
        <div className="flex items-center space-x-2">
          <button
            onClick={fetchHeatMapData}
            className="flex items-center space-x-2 px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
          >
            <RefreshCw className="w-4 h-4" />
            <span>Aktualisieren</span>
          </button>
          <button
            onClick={exportHeatMap}
            className="flex items-center space-x-2 px-3 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
          >
            <Download className="w-4 h-4" />
            <span>Export</span>
          </button>
        </div>
      </div>

      {/* Heat Map */}
      <div className="flex justify-center mb-6">
        <svg
          ref={svgRef}
          width={courtWidth}
          height={courtHeight}
          className="border border-gray-200 rounded"
        />
      </div>

      {/* Zone Details */}
      {selectedZone && (
        <div className="p-4 bg-gray-50 rounded-lg">
          <h4 className="font-semibold text-sm mb-3">Zone-Details</h4>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
              <span className="text-gray-600">Häufigkeit:</span>
              <div className="font-semibold">{selectedZone.frequency}</div>
            </div>
            <div>
              <span className="text-gray-600">Intensität:</span>
              <div className="font-semibold">{selectedZone.intensity.toFixed(2)}</div>
            </div>
            <div>
              <span className="text-gray-600">Effizienz:</span>
              <div className="font-semibold">{selectedZone.efficiency.toFixed(1)}%</div>
            </div>
            <div>
              <span className="text-gray-600">Ø Punkte:</span>
              <div className="font-semibold">{selectedZone.avgPoints.toFixed(1)}</div>
            </div>
          </div>
        </div>
      )}

      {/* Summary Statistics */}
      <div className="grid grid-cols-3 gap-4 mt-6">
        <div className="text-center p-3 bg-red-50 rounded-lg">
          <div className="text-2xl font-bold text-red-600">
            {heatMapData.reduce((sum, d) => sum + d.frequency, 0)}
          </div>
          <div className="text-sm text-gray-600">Gesamt-Aktivität</div>
        </div>
        <div className="text-center p-3 bg-orange-50 rounded-lg">
          <div className="text-2xl font-bold text-orange-600">
            {heatMapData.length > 0 
              ? (heatMapData.reduce((sum, d) => sum + d.efficiency, 0) / heatMapData.length).toFixed(1)
              : '0.0'
            }%
          </div>
          <div className="text-sm text-gray-600">Ø Effizienz</div>
        </div>
        <div className="text-center p-3 bg-yellow-50 rounded-lg">
          <div className="text-2xl font-bold text-yellow-600">
            {heatMapData.filter(d => d.intensity > 0.7).length}
          </div>
          <div className="text-sm text-gray-600">Hot Spots</div>
        </div>
      </div>
    </div>
  );
};
```

---

## 🎯 Advanced Tactics & Strategy

### Taktik-Designer & Spielzug-Bibliothek

Phase 3 führt ein umfassendes Taktik-Management-System ein, das Trainern ermöglicht, interaktive Spielzüge zu erstellen, zu verwalten und zu analysieren. Das System kombiniert visuelle Spielzug-Designer mit Performance-Analytics.

#### Tactical Plays Migration

```php
<?php
// database/migrations/2024_03_30_000000_create_tactical_plays_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tactical_plays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->foreignId('team_id')->nullable()->constrained();
            
            // Basic Information
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('objectives')->nullable(); // What this play aims to achieve
            $table->text('coaching_notes')->nullable();
            
            // Play Classification
            $table->enum('play_type', [
                'offensive_set', 'defensive_set', 'inbound_play', 'special_situation',
                'fast_break', 'press_break', 'zone_offense', 'man_to_man_offense',
                'pick_and_roll', 'motion_offense', 'isolation', 'transition'
            ]);
            
            $table->enum('play_category', [
                'primary', 'secondary', 'counter', 'baseline', 'sideline',
                'corner', 'elbow', 'post_up', 'perimeter', 'custom'
            ])->nullable();
            
            $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced', 'expert']);
            $table->enum('age_group', ['U8', 'U10', 'U12', 'U14', 'U16', 'U18', 'adult', 'all']);
            
            // Game Situation
            $table->json('game_situations')->nullable(); // When to use this play
            $table->enum('court_area', [
                'full_court', 'half_court', 'paint', 'perimeter', 'baseline',
                'sideline', 'corner', 'elbow', 'center_court'
            ])->nullable();
            
            $table->integer('min_players')->default(5);
            $table->integer('max_players')->default(5);
            $table->integer('estimated_duration')->default(15); // seconds
            
            // Visual Design Data
            $table->json('court_diagram')->nullable(); // SVG or coordinate data
            $table->json('player_positions')->nullable(); // Initial positions
            $table->json('movement_sequences')->nullable(); // Step-by-step movements
            $table->json('animation_data')->nullable(); // For animated playback
            
            // Play Variations
            $table->json('variations')->nullable(); // Different ways to run the play
            $table->json('counters')->nullable(); // Counter-plays for defense
            $table->json('prerequisites')->nullable(); // Skills/drills needed first
            
            // Success Metrics
            $table->json('success_criteria')->nullable(); // How to measure success
            $table->json('key_performance_indicators')->nullable();
            $table->decimal('success_rate', 5, 2)->nullable(); // Historical success %
            $table->integer('usage_count')->default(0);
            
            // Tags and Categories
            $table->json('tags')->nullable();
            $table->json('position_requirements')->nullable(); // Required player positions
            $table->json('skill_requirements')->nullable(); // Required skills
            
            // Media and Documentation
            $table->string('diagram_path')->nullable();
            $table->boolean('has_video')->default(false);
            $table->integer('video_duration')->nullable(); // seconds
            $table->text('step_by_step_instructions')->nullable();
            
            // Sharing and Collaboration
            $table->enum('visibility', ['public', 'team_only', 'private']);
            $table->boolean('is_featured')->default(false);
            $table->boolean('allow_modifications')->default(false);
            $table->foreignId('original_play_id')->nullable()->constrained('tactical_plays');
            
            // Usage and Analytics
            $table->decimal('average_rating', 3, 2)->nullable();
            $table->integer('rating_count')->default(0);
            $table->integer('view_count')->default(0);
            $table->integer('copy_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            
            // Status and Approval
            $table->enum('status', ['draft', 'pending_review', 'approved', 'rejected', 'archived']);
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['team_id', 'play_type']);
            $table->index(['created_by_user_id', 'status']);
            $table->index(['play_type', 'difficulty_level']);
            $table->index(['age_group', 'visibility']);
            $table->index('usage_count');
            $table->fullText(['name', 'description', 'tags']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tactical_plays');
    }
};
```

#### Play Executions Migration

```php
<?php
// database/migrations/2024_03_31_000000_create_play_executions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('play_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tactical_play_id')->constrained()->onDelete('cascade');
            $table->foreignId('game_id')->nullable()->constrained();
            $table->foreignId('training_session_id')->nullable()->constrained();
            $table->foreignId('executed_by_user_id')->constrained('users'); // Coach who called the play
            
            // Execution Context
            $table->timestamp('executed_at');
            $table->enum('context', ['game', 'training', 'scrimmage', 'drill']);
            $table->enum('game_situation', [
                'timeout', 'inbound', 'regular_play', 'special_situation',
                'end_of_quarter', 'comeback', 'lead_protection', 'tie_game'
            ])->nullable();
            
            $table->integer('quarter')->nullable(); // Game quarter/period
            $table->time('time_remaining')->nullable();
            $table->integer('score_home')->nullable();
            $table->integer('score_away')->nullable();
            $table->integer('score_differential')->nullable();
            
            // Players Involved
            $table->json('players_on_court')->nullable(); // Player IDs
            $table->json('position_assignments')->nullable(); // Who played which position
            $table->json('substitutions_made')->nullable(); // Any subs for the play
            
            // Execution Analysis
            $table->enum('execution_quality', ['excellent', 'good', 'fair', 'poor']);
            $table->boolean('successful')->default(false);
            $table->enum('outcome', [
                'score', 'assist', 'foul_drawn', 'turnover', 'missed_shot',
                'defensive_stop', 'timeout_taken', 'violation', 'other'
            ])->nullable();
            
            $table->integer('points_scored')->default(0);
            $table->decimal('execution_time', 5, 2)->nullable(); // How long it took
            $table->json('mistakes_made')->nullable(); // What went wrong
            $table->json('coaching_observations')->nullable();
            
            // Performance Metrics
            $table->json('player_performance_ratings')->nullable(); // Individual ratings
            $table->decimal('overall_execution_rating', 3, 2)->nullable(); // 1-10 scale
            $table->boolean('resulted_in_score')->default(false);
            $table->boolean('resulted_in_turnover')->default(false);
            $table->boolean('resulted_in_foul')->default(false);
            
            // Video and Analysis
            $table->string('video_clip_path')->nullable(); // If recorded
            $table->decimal('video_start_time', 8, 3)->nullable(); // Seconds
            $table->decimal('video_end_time', 8, 3)->nullable();
            $table->json('video_annotations')->nullable();
            
            // Learning and Improvement
            $table->text('lessons_learned')->nullable();
            $table->json('improvement_areas')->nullable();
            $table->json('follow_up_drills')->nullable(); // Recommended drills
            $table->boolean('requires_more_practice')->default(false);
            
            // External References
            $table->string('external_reference')->nullable(); // Link to game footage, etc.
            $table->json('related_executions')->nullable(); // Similar play executions
            
            $table->timestamps();
            
            // Indexes
            $table->index(['tactical_play_id', 'executed_at']);
            $table->index(['game_id', 'quarter']);
            $table->index(['training_session_id', 'context']);
            $table->index(['executed_by_user_id', 'successful']);
            $table->index('execution_quality');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('play_executions');
    }
};
```

#### TacticalPlay Model

```php
<?php
// app/Models/TacticalPlay.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\Traits\LogsActivity;

class TacticalPlay extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia, Searchable, LogsActivity;

    protected $fillable = [
        'created_by_user_id', 'team_id', 'name', 'description', 'objectives',
        'coaching_notes', 'play_type', 'play_category', 'difficulty_level',
        'age_group', 'game_situations', 'court_area', 'min_players', 'max_players',
        'estimated_duration', 'court_diagram', 'player_positions', 'movement_sequences',
        'animation_data', 'variations', 'counters', 'prerequisites', 'success_criteria',
        'key_performance_indicators', 'success_rate', 'usage_count', 'tags',
        'position_requirements', 'skill_requirements', 'diagram_path', 'has_video',
        'video_duration', 'step_by_step_instructions', 'visibility', 'is_featured',
        'allow_modifications', 'original_play_id', 'average_rating', 'rating_count',
        'view_count', 'copy_count', 'last_used_at', 'status', 'reviewed_by_user_id',
        'reviewed_at', 'review_notes',
    ];

    protected $casts = [
        'game_situations' => 'array', 'min_players' => 'integer', 'max_players' => 'integer',
        'estimated_duration' => 'integer', 'court_diagram' => 'array', 'player_positions' => 'array',
        'movement_sequences' => 'array', 'animation_data' => 'array', 'variations' => 'array',
        'counters' => 'array', 'prerequisites' => 'array', 'success_criteria' => 'array',
        'key_performance_indicators' => 'array', 'success_rate' => 'decimal:2',
        'usage_count' => 'integer', 'tags' => 'array', 'position_requirements' => 'array',
        'skill_requirements' => 'array', 'has_video' => 'boolean', 'video_duration' => 'integer',
        'is_featured' => 'boolean', 'allow_modifications' => 'boolean', 'average_rating' => 'decimal:2',
        'rating_count' => 'integer', 'view_count' => 'integer', 'copy_count' => 'integer',
        'last_used_at' => 'datetime', 'reviewed_at' => 'datetime',
    ];

    // Relationships
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(PlayExecution::class);
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('visibility', 'public')->where('status', 'approved');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('play_type', $type);
    }

    public function scopePopular($query)
    {
        return $query->orderBy('usage_count', 'desc');
    }

    // Helper Methods
    public function incrementUsage(): void
    {
        $this->update([
            'usage_count' => $this->usage_count + 1,
            'last_used_at' => now(),
        ]);
    }

    public function calculateSuccessRate(): float
    {
        $executions = $this->executions();
        $totalExecutions = $executions->count();
        
        if ($totalExecutions === 0) {
            return 0.0;
        }
        
        $successfulExecutions = $executions->where('successful', true)->count();
        
        return round(($successfulExecutions / $totalExecutions) * 100, 2);
    }

    public function generateCourtDiagram(): array
    {
        $courtWidth = 940; // Standard court width in pixels
        $courtHeight = 500; // Standard court height in pixels
        
        return [
            'width' => $courtWidth,
            'height' => $courtHeight,
            'viewBox' => "0 0 {$courtWidth} {$courtHeight}",
            'players' => $this->player_positions ?? [],
            'movements' => $this->movement_sequences ?? [],
            'court_lines' => $this->generateCourtLines(),
        ];
    }

    public function isApplicableForTeam(Team $team): bool
    {
        // Check age group compatibility
        if ($this->age_group !== 'all' && $team->category !== $this->age_group) {
            return false;
        }

        // Check player count
        $teamPlayerCount = $team->activePlayers()->count();
        if ($teamPlayerCount < $this->min_players || $teamPlayerCount > $this->max_players) {
            return false;
        }

        return true;
    }

    private function generateCourtLines(): array
    {
        return [
            'three_point_line' => [
                'path' => 'M 50 150 A 250 250 0 0 1 890 150',
                'stroke' => '#000', 'fill' => 'none', 'stroke-width' => 2,
            ],
            'free_throw_circle' => [
                'cx' => 470, 'cy' => 190, 'r' => 60,
                'stroke' => '#000', 'fill' => 'none', 'stroke-width' => 2,
            ],
            'paint' => [
                'x' => 410, 'y' => 50, 'width' => 120, 'height' => 190,
                'stroke' => '#000', 'fill' => 'none', 'stroke-width' => 2,
            ],
        ];
    }
}
```

#### TacticalPlayService

```php
<?php
// app/Services/TacticalPlayService.php

namespace App\Services;

use App\Models\TacticalPlay;
use App\Models\Team;
use App\Models\PlayExecution;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class TacticalPlayService
{
    public function createTacticalPlay(array $data): TacticalPlay
    {
        return DB::transaction(function () use ($data) {
            $play = TacticalPlay::create($data);

            // Generate default court diagram if not provided
            if (!isset($data['court_diagram'])) {
                $play->update([
                    'court_diagram' => $this->generateDefaultCourtDiagram($play)
                ]);
            }

            // Auto-tag based on play characteristics
            if (!isset($data['tags'])) {
                $play->update([
                    'tags' => $this->generateAutoTags($play)
                ]);
            }

            return $play;
        });
    }

    public function executePlay(
        TacticalPlay $play, 
        array $executionData, 
        ?int $gameId = null, 
        ?int $trainingSessionId = null
    ): PlayExecution {
        return DB::transaction(function () use ($play, $executionData, $gameId, $trainingSessionId) {
            $execution = PlayExecution::create(array_merge($executionData, [
                'tactical_play_id' => $play->id,
                'game_id' => $gameId,
                'training_session_id' => $trainingSessionId,
                'executed_by_user_id' => auth()->id(),
            ]));

            // Update play usage statistics
            $play->incrementUsage();
            
            // Update success rate if this was a completed execution
            if (isset($executionData['successful'])) {
                $play->updateSuccessRate();
            }

            return $execution;
        });
    }

    public function recommendPlaysForSituation(Team $team, array $situation): Collection
    {
        $query = TacticalPlay::public()
            ->where('age_group', $team->category)
            ->orWhere('age_group', 'all');

        // Filter by game situation
        if (isset($situation['play_type'])) {
            $query->where('play_type', $situation['play_type']);
        }

        if (isset($situation['court_area'])) {
            $query->where('court_area', $situation['court_area']);
        }

        if (isset($situation['time_pressure'])) {
            if ($situation['time_pressure'] === 'high') {
                $query->where('estimated_duration', '<=', 10);
            } elseif ($situation['time_pressure'] === 'low') {
                $query->where('estimated_duration', '>=', 20);
            }
        }

        return $query->popular()
                    ->limit($situation['limit'] ?? 5)
                    ->get();
    }

    public function analyzePlayEffectiveness(TacticalPlay $play, array $filters = []): array
    {
        $executions = $play->executions();

        // Apply filters
        if (isset($filters['date_from'])) {
            $executions->where('executed_at', '>=', $filters['date_from']);
        }

        if (isset($filters['context'])) {
            $executions->where('context', $filters['context']);
        }

        $executionData = $executions->get();

        if ($executionData->isEmpty()) {
            return [
                'success_rate' => 0,
                'usage_frequency' => 0,
                'effectiveness_score' => 0,
                'recommendations' => ['Spielzug wurde noch nicht ausgeführt'],
            ];
        }

        return [
            'success_rate' => $this->calculateSuccessRate($executionData),
            'usage_frequency' => $this->calculateUsageFrequency($executionData, $filters),
            'effectiveness_score' => $this->calculateEffectivenessScore($executionData),
            'situation_analysis' => $this->analyzeBySituation($executionData),
            'recommendations' => $this->generateExecutionRecommendations($executionData),
        ];
    }

    public function generatePlaybook(Team $team, array $criteria = []): array
    {
        $sections = [
            'offensive_sets' => 'Offensiv-Systeme',
            'defensive_sets' => 'Defensiv-Systeme',
            'inbound_plays' => 'Einwurf-Spielzüge',
            'special_situations' => 'Spezial-Situationen',
        ];

        $playbook = [
            'team' => $team,
            'generated_at' => now(),
            'sections' => [],
        ];

        foreach ($sections as $type => $title) {
            $plays = $this->getPlaysForSection($team, $type, $criteria);
            
            if ($plays->isNotEmpty()) {
                $playbook['sections'][] = [
                    'type' => $type,
                    'title' => $title,
                    'plays' => $plays,
                ];
            }
        }

        return $playbook;
    }

    // Private helper methods
    private function generateDefaultCourtDiagram(TacticalPlay $play): array
    {
        return [
            'court_width' => 940,
            'court_height' => 500,
            'player_positions' => $this->generateStandardPositions(),
            'movement_sequences' => [],
        ];
    }

    private function generateAutoTags(TacticalPlay $play): array
    {
        $tags = [];
        $tags[] = $play->play_type;
        $tags[] = $play->difficulty_level;
        
        if ($play->estimated_duration <= 10) {
            $tags[] = 'quick_play';
        } elseif ($play->estimated_duration >= 25) {
            $tags[] = 'extended_play';
        }
        
        return array_unique($tags);
    }

    private function calculateSuccessRate(Collection $executions): float
    {
        if ($executions->isEmpty()) {
            return 0;
        }
        
        $successful = $executions->where('successful', true)->count();
        return round(($successful / $executions->count()) * 100, 1);
    }

    private function calculateUsageFrequency(Collection $executions, array $filters): int
    {
        return $executions->count();
    }

    private function calculateEffectivenessScore(Collection $executions): float
    {
        if ($executions->isEmpty()) {
            return 0;
        }
        
        $successRate = $this->calculateSuccessRate($executions);
        $avgRating = $executions->avg('overall_execution_rating') ?? 0;
        $pointsPerExecution = $executions->avg('points_scored') ?? 0;
        
        return round(($successRate * 0.4) + ($avgRating * 10 * 0.4) + ($pointsPerExecution * 10 * 0.2), 1);
    }

    private function analyzeBySituation(Collection $executions): array
    {
        return $executions->groupBy('game_situation')
                         ->map(fn($group) => [
                             'count' => $group->count(),
                             'success_rate' => $this->calculateSuccessRate($group),
                         ])
                         ->toArray();
    }

    private function generateExecutionRecommendations(Collection $executions): array
    {
        $recommendations = [];
        
        $successRate = $this->calculateSuccessRate($executions);
        
        if ($successRate < 50) {
            $recommendations[] = 'Spielzug benötigt mehr Training - Erfolgsrate unter 50%';
        }
        
        if ($executions->where('context', 'training')->count() < 3) {
            $recommendations[] = 'Mehr Training empfohlen bevor Einsatz im Spiel';
        }
        
        return $recommendations;
    }

    private function getPlaysForSection(Team $team, string $type, array $criteria): Collection
    {
        return TacticalPlay::public()
            ->where('play_type', $type)
            ->where('age_group', $team->category)
            ->orWhere('age_group', 'all')
            ->popular()
            ->limit(10)
            ->get();
    }

    private function generateStandardPositions(): array
    {
        return [
            ['position' => 'PG', 'x' => 470, 'y' => 400, 'number' => 1],
            ['position' => 'SG', 'x' => 350, 'y' => 300, 'number' => 2],
            ['position' => 'SF', 'x' => 590, 'y' => 300, 'number' => 3],
            ['position' => 'PF', 'x' => 380, 'y' => 180, 'number' => 4],
            ['position' => 'C', 'x' => 470, 'y' => 120, 'number' => 5],
        ];
    }
}
```

---

## ⚡ Performance Monitoring

### Umfassendes Load-Management & Fitness-Tracking

Phase 3 integriert ein hochentwickeltes Performance-Monitoring-System, das Spielerbelastung überwacht, Verletzungsrisiken reduziert und die optimale Leistungsfähigkeit sicherstellt. Das System kombiniert physiologische Metriken mit spielspezifischen Leistungsdaten.

#### Player Performance Metrics Migration

```php
<?php
// database/migrations/2024_04_01_000000_create_player_performance_metrics_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->foreignId('game_id')->nullable()->constrained();
            $table->foreignId('training_session_id')->nullable()->constrained();
            $table->foreignId('recorded_by_user_id')->constrained('users');
            
            // Temporal Information
            $table->timestamp('recorded_at');
            $table->date('measurement_date');
            $table->enum('measurement_context', [
                'pre_game', 'post_game', 'pre_training', 'post_training',
                'rest_day', 'medical_check', 'fitness_test', 'recovery_session'
            ]);
            
            // Physical Metrics
            $table->integer('heart_rate_resting')->nullable(); // BPM
            $table->integer('heart_rate_max')->nullable(); // BPM
            $table->integer('heart_rate_avg_session')->nullable(); // BPM
            $table->decimal('heart_rate_variability', 5, 2)->nullable(); // HRV in ms
            
            // Body Composition
            $table->decimal('weight', 5, 2)->nullable(); // kg
            $table->decimal('body_fat_percentage', 4, 2)->nullable(); // %
            $table->decimal('muscle_mass', 5, 2)->nullable(); // kg
            $table->decimal('hydration_level', 4, 2)->nullable(); // %
            $table->integer('height')->nullable(); // cm - can change for youth players
            
            // Performance Metrics
            $table->decimal('vo2_max', 5, 2)->nullable(); // ml/kg/min
            $table->decimal('lactate_threshold', 5, 2)->nullable(); // mmol/L
            $table->integer('vertical_jump', 4)->nullable(); // cm
            $table->decimal('sprint_speed_20m', 4, 2)->nullable(); // seconds
            $table->decimal('agility_t_test', 4, 2)->nullable(); // seconds
            $table->integer('bench_press_max', 4)->nullable(); // kg
            $table->integer('squat_max', 4)->nullable(); // kg
            
            // Basketball-Specific Metrics
            $table->decimal('shooting_accuracy_percentage', 5, 2)->nullable(); // %
            $table->decimal('free_throw_accuracy', 5, 2)->nullable(); // %
            $table->decimal('three_point_accuracy', 5, 2)->nullable(); // %
            $table->integer('defensive_reaction_time')->nullable(); // milliseconds
            $table->decimal('court_sprint_time', 4, 2)->nullable(); // seconds
            $table->integer('consecutive_shots_made')->nullable();
            
            // Load and Fatigue Indicators
            $table->integer('perceived_exertion_rpe')->nullable(); // 1-10 scale
            $table->integer('wellness_score')->nullable(); // 1-10 scale
            $table->integer('sleep_quality')->nullable(); // 1-10 scale
            $table->decimal('sleep_duration', 3, 1)->nullable(); // hours
            $table->integer('stress_level')->nullable(); // 1-10 scale
            $table->integer('motivation_level')->nullable(); // 1-10 scale
            $table->integer('energy_level')->nullable(); // 1-10 scale
            
            // Recovery Metrics
            $table->integer('muscle_soreness')->nullable(); // 1-10 scale
            $table->json('soreness_areas')->nullable(); // Body areas with soreness
            $table->decimal('flexibility_score', 4, 2)->nullable(); // cm - sit and reach
            $table->integer('recovery_readiness')->nullable(); // 1-10 scale
            $table->boolean('injury_risk_flag')->default(false);
            $table->text('recovery_notes')->nullable();
            
            // Training Load
            $table->integer('training_impulse_trimp')->nullable(); // TRIMP score
            $table->decimal('session_load', 8, 2)->nullable(); // RPE * Duration
            $table->decimal('monotony_index', 4, 2)->nullable();
            $table->decimal('strain_index', 8, 2)->nullable();
            $table->integer('weekly_load')->nullable();
            $table->integer('acute_chronic_workload_ratio', 3)->nullable(); // x100 for precision
            
            // Technology Integration
            $table->json('wearable_data')->nullable(); // Data from fitness trackers
            $table->string('data_source')->nullable(); // manual, heart_rate_monitor, app, etc.
            $table->decimal('gps_distance_covered', 6, 2)->nullable(); // meters
            $table->integer('steps_count')->nullable();
            $table->integer('calories_burned')->nullable();
            $table->json('movement_patterns')->nullable(); // Accelerometer data analysis
            
            // Medical and Health
            $table->decimal('blood_pressure_systolic', 5, 2)->nullable(); // mmHg
            $table->decimal('blood_pressure_diastolic', 5, 2)->nullable(); // mmHg
            $table->decimal('body_temperature', 4, 1)->nullable(); // Celsius
            $table->boolean('illness_symptoms')->default(false);
            $table->json('medication_taken')->nullable();
            $table->text('medical_notes')->nullable();
            
            // Environmental Factors
            $table->decimal('ambient_temperature', 4, 1)->nullable(); // Celsius
            $table->integer('humidity_percentage')->nullable(); // %
            $table->string('weather_conditions')->nullable();
            $table->string('training_surface')->nullable(); // court, grass, etc.
            
            // Goals and Targets
            $table->json('personal_targets')->nullable(); // What player is working toward
            $table->json('coach_recommendations')->nullable();
            $table->boolean('target_achieved')->default(false);
            $table->text('improvement_notes')->nullable();
            
            // Data Quality and Validation
            $table->enum('data_quality', ['excellent', 'good', 'fair', 'poor']);
            $table->boolean('manually_verified')->default(false);
            $table->json('measurement_conditions')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['player_id', 'measurement_date']);
            $table->index(['game_id', 'measurement_context']);
            $table->index(['training_session_id', 'recorded_at']);
            $table->index(['measurement_context', 'recorded_at']);
            $table->index('injury_risk_flag');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_performance_metrics');
    }
};
```

#### Load Management Migration

```php
<?php
// database/migrations/2024_04_02_000000_create_load_management_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('load_management', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->constrained();
            $table->foreignId('created_by_user_id')->constrained('users');
            
            // Time Period
            $table->date('week_starting');
            $table->date('week_ending');
            $table->string('season');
            $table->integer('week_number'); // Week of season
            
            // Load Calculations
            $table->decimal('acute_load', 8, 2)->default(0); // 7-day rolling average
            $table->decimal('chronic_load', 8, 2)->default(0); // 28-day rolling average
            $table->decimal('acwr', 4, 2)->nullable(); // Acute:Chronic Workload Ratio
            $table->decimal('training_stress_balance', 8, 2)->nullable();
            
            // Weekly Totals
            $table->integer('total_training_minutes')->default(0);
            $table->integer('total_game_minutes')->default(0);
            $table->integer('total_sessions')->default(0);
            $table->decimal('total_session_load', 8, 2)->default(0);
            $table->decimal('average_rpe', 3, 1)->nullable();
            
            // Load Distribution
            $table->json('daily_loads')->nullable(); // Load for each day of week
            $table->json('session_types')->nullable(); // Training vs game loads
            $table->json('intensity_zones')->nullable(); // Time in different zones
            
            // Risk Assessment
            $table->enum('injury_risk_level', ['low', 'moderate', 'high', 'very_high']);
            $table->decimal('injury_risk_score', 5, 2)->nullable(); // 0-100
            $table->json('risk_factors')->nullable();
            $table->text('risk_explanation')->nullable();
            
            // Recommendations
            $table->enum('load_recommendation', [
                'maintain', 'increase_gradually', 'reduce_load', 'rest_recommended',
                'medical_check', 'modify_intensity', 'focus_recovery'
            ])->nullable();
            
            $table->text('coach_recommendations')->nullable();
            $table->json('suggested_modifications')->nullable();
            $table->boolean('requires_medical_clearance')->default(false);
            
            // Recovery Indicators
            $table->decimal('recovery_score', 4, 1)->nullable(); // 1-10
            $table->integer('sleep_quality_avg', 3)->nullable(); // 1-10
            $table->integer('wellness_score_avg', 3)->nullable(); // 1-10
            $table->integer('soreness_level_avg', 3)->nullable(); // 1-10
            
            // Performance Trends
            $table->enum('performance_trend', [
                'improving', 'stable', 'declining', 'fluctuating', 'unknown'
            ])->nullable();
            
            $table->decimal('performance_score', 5, 2)->nullable();
            $table->json('key_performance_indicators')->nullable();
            
            // Compliance and Adherence
            $table->integer('training_attendance_rate')->nullable(); // Percentage
            $table->boolean('followed_recommendations')->default(false);
            $table->text('compliance_notes')->nullable();
            
            // Goals and Periodization
            $table->string('training_phase')->nullable(); // pre-season, in-season, etc.
            $table->json('phase_objectives')->nullable();
            $table->boolean('goals_on_track')->default(true);
            
            // Alerts and Notifications
            $table->boolean('alert_triggered')->default(false);
            $table->string('alert_type')->nullable(); // overload, underload, injury_risk
            $table->timestamp('alert_triggered_at')->nullable();
            $table->boolean('alert_acknowledged')->default(false);
            
            // External Factors
            $table->integer('academic_stress')->nullable(); // 1-10 for student athletes
            $table->json('life_events')->nullable(); // Personal factors affecting load
            $table->text('external_notes')->nullable();
            
            // Validation and Quality
            $table->enum('data_completeness', ['complete', 'mostly_complete', 'partial', 'minimal']);
            $table->boolean('validated_by_coach')->default(false);
            $table->timestamp('validated_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['player_id', 'week_starting']);
            $table->index(['team_id', 'season', 'week_number']);
            $table->index(['injury_risk_level', 'alert_triggered']);
            $table->index(['season', 'week_number']);
            $table->unique(['player_id', 'week_starting']); // One record per player per week
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('load_management');
    }
};
```

#### PerformanceMonitoringService

```php
<?php
// app/Services/PerformanceMonitoringService.php

namespace App\Services;

use App\Models\Player;
use App\Models\PlayerPerformanceMetric;
use App\Models\LoadManagement;
use App\Models\FitnessTest;
use App\Jobs\CalculateLoadManagement;
use App\Jobs\SendInjuryRiskAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class PerformanceMonitoringService
{
    public function recordPerformanceMetric(Player $player, array $metricData): PlayerPerformanceMetric
    {
        return DB::transaction(function () use ($player, $metricData) {
            $metric = PlayerPerformanceMetric::create(array_merge($metricData, [
                'player_id' => $player->id,
                'recorded_by_user_id' => auth()->id(),
                'measurement_date' => $metricData['measurement_date'] ?? now()->toDateString(),
            ]));

            // Check for injury risk flags
            if ($this->shouldFlagInjuryRisk($metric)) {
                $metric->update(['injury_risk_flag' => true]);
                
                // Trigger alert if risk is high
                if ($this->calculateInjuryRiskScore($player) > 70) {
                    SendInjuryRiskAlert::dispatch($player, $metric);
                }
            }

            // Update weekly load management
            CalculateLoadManagement::dispatch($player, Carbon::parse($metric->measurement_date));

            return $metric;
        });
    }

    public function calculateAcuteChronicWorkloadRatio(Player $player, Carbon $date): float
    {
        // Get acute load (7-day rolling average)
        $acuteLoad = $this->calculateAcuteLoad($player, $date);
        
        // Get chronic load (28-day rolling average)
        $chronicLoad = $this->calculateChronicLoad($player, $date);
        
        if ($chronicLoad == 0) {
            return 0;
        }
        
        return round($acuteLoad / $chronicLoad, 2);
    }

    public function assessInjuryRisk(Player $player, ?Carbon $date = null): array
    {
        $date = $date ?? now();
        
        $acwr = $this->calculateAcuteChronicWorkloadRatio($player, $date);
        $recentMetrics = $this->getRecentMetrics($player, $date, 7);
        
        $riskFactors = [];
        $riskScore = 0;
        
        // ACWR risk assessment
        if ($acwr > 1.5) {
            $riskFactors[] = 'Akute Belastung zu hoch (ACWR: ' . $acwr . ')';
            $riskScore += 30;
        } elseif ($acwr < 0.8) {
            $riskFactors[] = 'Trainingsbelastung zu niedrig (ACWR: ' . $acwr . ')';
            $riskScore += 15;
        }
        
        // Wellness indicators
        $avgWellness = $recentMetrics->avg('wellness_score');
        if ($avgWellness && $avgWellness < 6) {
            $riskFactors[] = 'Wellness-Score niedrig (' . round($avgWellness, 1) . ')';
            $riskScore += 20;
        }
        
        // Sleep quality
        $avgSleep = $recentMetrics->avg('sleep_quality');
        if ($avgSleep && $avgSleep < 6) {
            $riskFactors[] = 'Schlafqualität schlecht (' . round($avgSleep, 1) . ')';
            $riskScore += 15;
        }
        
        // Muscle soreness
        $avgSoreness = $recentMetrics->avg('muscle_soreness');
        if ($avgSoreness && $avgSoreness > 7) {
            $riskFactors[] = 'Muskelkater hoch (' . round($avgSoreness, 1) . ')';
            $riskScore += 25;
        }
        
        // Determine risk level
        $riskLevel = 'low';
        if ($riskScore >= 70) {
            $riskLevel = 'very_high';
        } elseif ($riskScore >= 50) {
            $riskLevel = 'high';
        } elseif ($riskScore >= 30) {
            $riskLevel = 'moderate';
        }
        
        return [
            'risk_level' => $riskLevel,
            'risk_score' => $riskScore,
            'risk_factors' => $riskFactors,
            'acwr' => $acwr,
            'recommendations' => $this->generateRiskRecommendations($riskLevel, $riskFactors),
        ];
    }

    public function createLoadManagementPlan(Player $player, Carbon $weekStart): LoadManagement
    {
        $weekEnd = $weekStart->copy()->endOfWeek();
        
        // Calculate load metrics for the week
        $weeklyMetrics = $this->calculateWeeklyLoadMetrics($player, $weekStart, $weekEnd);
        
        // Calculate ACWR
        $acwr = $this->calculateAcuteChronicWorkloadRatio($player, $weekEnd);
        
        // Assess injury risk
        $riskAssessment = $this->assessInjuryRisk($player, $weekEnd);
        
        return LoadManagement::updateOrCreate(
            [
                'player_id' => $player->id,
                'week_starting' => $weekStart->toDateString(),
            ],
            array_merge($weeklyMetrics, [
                'team_id' => $player->team_id,
                'created_by_user_id' => auth()->id(),
                'week_ending' => $weekEnd->toDateString(),
                'season' => $this->getCurrentSeason(),
                'week_number' => $weekStart->weekOfYear,
                'acwr' => $acwr,
                'injury_risk_level' => $riskAssessment['risk_level'],
                'injury_risk_score' => $riskAssessment['risk_score'],
                'risk_factors' => $riskAssessment['risk_factors'],
                'load_recommendation' => $this->generateLoadRecommendation($riskAssessment),
            ])
        );
    }

    public function conductFitnessTest(Player $player, array $testData): FitnessTest
    {
        return DB::transaction(function () use ($player, $testData) {
            $test = FitnessTest::create(array_merge($testData, [
                'player_id' => $player->id,
                'conducted_by_user_id' => auth()->id(),
                'team_id' => $player->team_id,
                'season' => $this->getCurrentSeason(),
            ]));

            // Analyze test results
            $analysis = $this->analyzeFitnessTestResults($test);
            
            $test->update([
                'overall_rating' => $analysis['overall_rating'],
                'strength_areas' => $analysis['strength_areas'],
                'improvement_areas' => $analysis['improvement_areas'],
                'trainer_recommendations' => $analysis['recommendations'],
            ]);

            return $test;
        });
    }

    public function generatePerformanceReport(Player $player, Carbon $startDate, Carbon $endDate): array
    {
        $metrics = PlayerPerformanceMetric::where('player_id', $player->id)
            ->whereBetween('measurement_date', [$startDate, $endDate])
            ->orderBy('measurement_date')
            ->get();

        $loadManagement = LoadManagement::where('player_id', $player->id)
            ->whereBetween('week_starting', [$startDate, $endDate])
            ->orderBy('week_starting')
            ->get();

        $fitnessTests = FitnessTest::where('player_id', $player->id)
            ->whereBetween('test_date', [$startDate, $endDate])
            ->orderBy('test_date')
            ->get();

        return [
            'player' => $player,
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'days' => $startDate->diffInDays($endDate) + 1,
            ],
            'summary' => $this->calculatePerformanceSummary($metrics, $loadManagement, $fitnessTests),
            'metrics_trends' => $this->analyzeMetricsTrends($metrics),
            'load_analysis' => $this->analyzeLoadTrends($loadManagement),
            'fitness_progression' => $this->analyzeFitnessProgression($fitnessTests),
            'injury_risk_analysis' => $this->analyzeInjuryRiskHistory($player, $startDate, $endDate),
            'recommendations' => $this->generatePerformanceRecommendations($player, $metrics, $loadManagement),
        ];
    }

    public function optimizeTrainingLoad(Player $player, array $upcomingSchedule): array
    {
        $currentLoad = $this->getCurrentWeekLoad($player);
        $riskAssessment = $this->assessInjuryRisk($player);
        
        $optimizations = [];
        
        // Analyze upcoming schedule
        foreach ($upcomingSchedule as $day => $activities) {
            $dayOptimization = $this->optimizeDayLoad($player, $day, $activities, $currentLoad, $riskAssessment);
            if (!empty($dayOptimization)) {
                $optimizations[$day] = $dayOptimization;
            }
        }
        
        return [
            'current_status' => [
                'acwr' => $this->calculateAcuteChronicWorkloadRatio($player, now()),
                'risk_level' => $riskAssessment['risk_level'],
                'weekly_load' => $currentLoad,
            ],
            'optimizations' => $optimizations,
            'weekly_recommendations' => $this->generateWeeklyLoadRecommendations($player, $riskAssessment),
        ];
    }

    // Private helper methods
    private function calculateAcuteLoad(Player $player, Carbon $date): float
    {
        $startDate = $date->copy()->subDays(6); // 7-day window
        
        return PlayerPerformanceMetric::where('player_id', $player->id)
            ->whereBetween('measurement_date', [$startDate, $date])
            ->avg('session_load') ?? 0;
    }

    private function calculateChronicLoad(Player $player, Carbon $date): float
    {
        $startDate = $date->copy()->subDays(27); // 28-day window
        
        return PlayerPerformanceMetric::where('player_id', $player->id)
            ->whereBetween('measurement_date', [$startDate, $date])
            ->avg('session_load') ?? 0;
    }

    private function shouldFlagInjuryRisk(PlayerPerformanceMetric $metric): bool
    {
        // Multiple criteria for injury risk flagging
        $riskIndicators = 0;
        
        if ($metric->wellness_score && $metric->wellness_score < 5) $riskIndicators++;
        if ($metric->sleep_quality && $metric->sleep_quality < 5) $riskIndicators++;
        if ($metric->muscle_soreness && $metric->muscle_soreness > 8) $riskIndicators++;
        if ($metric->perceived_exertion_rpe && $metric->perceived_exertion_rpe > 8) $riskIndicators++;
        if ($metric->recovery_readiness && $metric->recovery_readiness < 5) $riskIndicators++;
        
        return $riskIndicators >= 2;
    }

    private function calculateInjuryRiskScore(Player $player): float
    {
        $riskAssessment = $this->assessInjuryRisk($player);
        return $riskAssessment['risk_score'];
    }

    private function getRecentMetrics(Player $player, Carbon $date, int $days): Collection
    {
        $startDate = $date->copy()->subDays($days - 1);
        
        return PlayerPerformanceMetric::where('player_id', $player->id)
            ->whereBetween('measurement_date', [$startDate, $date])
            ->get();
    }

    private function generateRiskRecommendations(string $riskLevel, array $riskFactors): array
    {
        $recommendations = [];
        
        switch ($riskLevel) {
            case 'very_high':
                $recommendations[] = 'Sofortige Trainingspause empfohlen';
                $recommendations[] = 'Medizinische Untersuchung erforderlich';
                $recommendations[] = 'Fokus auf Regeneration und Erholung';
                break;
                
            case 'high':
                $recommendations[] = 'Trainingsintensität reduzieren';
                $recommendations[] = 'Zusätzliche Erholungsmaßnahmen einplanen';
                $recommendations[] = 'Tägliches Monitoring verstärken';
                break;
                
            case 'moderate':
                $recommendations[] = 'Trainingsbelastung moderat anpassen';
                $recommendations[] = 'Schlafqualität und Regeneration optimieren';
                break;
                
            default:
                $recommendations[] = 'Aktuelle Trainingsbelastung beibehalten';
                $recommendations[] = 'Regelmäßiges Monitoring fortsetzen';
        }
        
        // Add specific recommendations based on risk factors
        foreach ($riskFactors as $factor) {
            if (str_contains($factor, 'Schlafqualität')) {
                $recommendations[] = 'Schlafhygiene verbessern (8+ Stunden Schlaf)';
            }
            if (str_contains($factor, 'Wellness-Score')) {
                $recommendations[] = 'Stressmanagement und mentale Erholung priorisieren';
            }
            if (str_contains($factor, 'Muskelkater')) {
                $recommendations[] = 'Regenerative Maßnahmen verstärken (Massage, Kältetherapie)';
            }
        }
        
        return array_unique($recommendations);
    }

    private function generateLoadRecommendation(array $riskAssessment): string
    {
        switch ($riskAssessment['risk_level']) {
            case 'very_high':
                return 'rest_recommended';
            case 'high':
                return 'reduce_load';
            case 'moderate':
                return 'modify_intensity';
            default:
                return 'maintain';
        }
    }

    private function getCurrentSeason(): string
    {
        $year = now()->year;
        $month = now()->month;
        
        // Season runs from September to August
        if ($month >= 9) {
            return $year . '-' . ($year + 1);
        } else {
            return ($year - 1) . '-' . $year;
        }
    }

    // Additional helper methods would be implemented here...
}
```

---