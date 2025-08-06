# Phase 2: Game & Statistics Management PRD - BasketManager Pro Laravel

> **Product Requirements Document (PRD) - Phase 2**  
> **Version**: 1.0  
> **Datum**: 28. Juli 2025  
> **Status**: Entwicklungsbereit  
> **Autor**: Claude Code Assistant  
> **Dauer**: 3 Monate (Monate 4-6)

---

## 📋 Inhaltsverzeichnis

1. [Phase 2 Übersicht](#phase-2-übersicht)
2. [Game Management System](#game-management-system)
3. [Live-Scoring Features](#live-scoring-features)
4. [Statistics Engine](#statistics-engine)
5. [Real-time Broadcasting](#real-time-broadcasting)
6. [Reporting System](#reporting-system)
7. [Export-Funktionen](#export-funktionen)
8. [Scoresheet Processing](#scoresheet-processing)
9. [API Erweiterungen](#api-erweiterungen)
10. [Frontend Components](#frontend-components)
11. [Performance Optimierungen](#performance-optimierungen)
12. [Testing Strategy](#testing-strategy)
13. [Phase 2 Deliverables](#phase-2-deliverables)

---

## 🎯 Phase 2 Übersicht

### Ziele der Game & Statistics Phase

Phase 2 erweitert das BasketManager Pro System um das Herzstück jeder Basketball-Anwendung: Game Management und umfassende Statistiken. Diese Phase fokussiert auf Live-Scoring, Real-time Updates und fortgeschrittene Analytics.

### Kernziele

1. **Game Management**: Vollständiges Spielverwaltungssystem mit Scheduling und Scoring
2. **Live-Scoring**: Real-time Spielstatistiken mit WebSocket-Integration
3. **Statistics Engine**: Umfassende Statistik-Berechnungen für Spieler und Teams
4. **Broadcasting**: Live-Updates für alle verbundenen Clients
5. **Advanced Reporting**: Detaillierte Berichte und Export-Funktionen
6. **Mobile Optimization**: Optimierte Scorer-Interfaces für Tablets/Smartphones
7. **Performance**: Hochperformante Datenverarbeitung für Live-Spiele

### Success Metrics

- ✅ Vollständiges Game CRUD mit Live-Scoring
- ✅ Real-time Statistics Updates (<500ms Latency)
- ✅ WebSocket Broadcasting für Live-Games
- ✅ 20+ Basketball-Statistiken automatisch berechnet
- ✅ Export in PDF, Excel, CSV Formaten
- ✅ Mobile-optimierte Scorer-Interface
- ✅ 95%+ Test Coverage für kritische Features

---

## 🏀 Game Management System

### Game Models & Database Design

#### Games Migration

```php
<?php
// database/migrations/2024_02_01_000000_create_games_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            
            // Teams
            $table->foreignId('home_team_id')->constrained('teams');
            $table->foreignId('away_team_id')->constrained('teams');
            $table->foreignId('tournament_id')->nullable()->constrained();
            
            // Game Information
            $table->dateTime('scheduled_at');
            $table->dateTime('actual_start_time')->nullable();
            $table->dateTime('actual_end_time')->nullable();
            $table->string('venue');
            $table->text('venue_address')->nullable();
            
            // Officials
            $table->string('referee_1')->nullable();
            $table->string('referee_2')->nullable();
            $table->string('referee_3')->nullable();
            $table->string('table_official')->nullable();
            $table->string('timekeeper')->nullable();
            
            // Game Status
            $table->enum('status', [
                'scheduled', 'warmup', 'live', 'halftime', 
                'overtime', 'finished', 'cancelled', 'postponed'
            ])->default('scheduled');
            
            // Scores
            $table->integer('final_score_home')->nullable();
            $table->integer('final_score_away')->nullable();
            $table->json('quarter_scores')->nullable(); // [Q1: [home, away], Q2: [...]]
            $table->json('overtime_scores')->nullable();
            
            // Game Configuration
            $table->string('season');
            $table->enum('game_type', [
                'regular', 'playoff', 'friendly', 'tournament', 'cup'
            ])->default('regular');
            $table->integer('periods')->default(4); // Quarters or Periods
            $table->integer('period_length')->default(10); // Minutes per period
            $table->boolean('overtime_enabled')->default(true);
            $table->integer('overtime_length')->default(5); // Minutes
            
            // Game Settings & Rules
            $table->json('game_settings')->nullable();
            $table->json('league_rules')->nullable();
            $table->boolean('shot_clock_enabled')->default(true);
            $table->integer('shot_clock_seconds')->default(24);
            
            // Weather (for outdoor games)
            $table->string('weather_conditions')->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            
            // Administrative
            $table->text('notes')->nullable();
            $table->text('pregame_notes')->nullable();
            $table->text('postgame_notes')->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('livestream_enabled')->default(false);
            $table->string('livestream_url')->nullable();
            
            // Statistics
            $table->integer('attendance')->nullable();
            $table->decimal('duration_minutes', 5, 2)->nullable();
            $table->integer('total_fouls_home')->default(0);
            $table->integer('total_fouls_away')->default(0);
            $table->integer('timeouts_used_home')->default(0);
            $table->integer('timeouts_used_away')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['scheduled_at', 'status']);
            $table->index(['home_team_id', 'season']);
            $table->index(['away_team_id', 'season']);
            $table->index(['venue', 'scheduled_at']);
            $table->index(['season', 'game_type']);
            
            // Constraints
            $table->check('home_team_id != away_team_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
```

#### Game Actions Migration (Live-Scoring Events)

```php
<?php
// database/migrations/2024_02_02_000000_create_game_actions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->foreignId('player_id')->constrained();
            $table->foreignId('team_id')->constrained();
            
            // Action Details
            $table->enum('action_type', [
                // Scoring
                'field_goal_made', 'field_goal_missed',
                'three_point_made', 'three_point_missed',
                'free_throw_made', 'free_throw_missed',
                
                // Rebounds
                'rebound_offensive', 'rebound_defensive',
                
                // Assists & Plays
                'assist', 'steal', 'block', 'turnover',
                
                // Fouls
                'foul_personal', 'foul_technical', 'foul_flagrant',
                'foul_unsportsmanlike', 'foul_offensive',
                
                // Substitutions
                'substitution_in', 'substitution_out',
                
                // Timeouts
                'timeout_team', 'timeout_official',
                
                // Other
                'jump_ball_won', 'jump_ball_lost',
                'ejection', 'injury_timeout'
            ]);
            
            // Game Time
            $table->integer('period'); // Quarter/Period number
            $table->time('time_remaining'); // Time left in period (MM:SS)
            $table->integer('game_clock_seconds')->nullable(); // Total seconds elapsed
            $table->integer('shot_clock_remaining')->nullable();
            
            // Points and Impact
            $table->integer('points')->default(0);
            $table->boolean('is_successful')->nullable();
            $table->boolean('is_assisted')->default(false);
            $table->foreignId('assisted_by_player_id')->nullable()->constrained('players');
            
            // Shot Chart Data
            $table->decimal('shot_x', 5, 2)->nullable(); // Court X coordinate
            $table->decimal('shot_y', 5, 2)->nullable(); // Court Y coordinate
            $table->decimal('shot_distance', 4, 1)->nullable(); // Distance in feet/meters
            $table->string('shot_zone')->nullable(); // Paint, Mid-range, Three-point, etc.
            
            // Foul Details
            $table->enum('foul_type', [
                'shooting', 'non_shooting', 'technical', 'flagrant_1', 
                'flagrant_2', 'unsportsmanlike', 'offensive'
            ])->nullable();
            $table->boolean('foul_results_in_free_throws')->default(false);
            $table->integer('free_throws_awarded')->default(0);
            
            // Substitution Details
            $table->foreignId('substituted_player_id')->nullable()->constrained('players');
            $table->string('substitution_reason')->nullable();
            
            // Context and Notes
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->json('additional_data')->nullable();
            
            // Scorer Information
            $table->foreignId('recorded_by_user_id')->constrained('users');
            $table->ipAddress('recorded_from_ip')->nullable();
            $table->timestamp('recorded_at');
            
            // Review and Corrections
            $table->boolean('is_reviewed')->default(false);
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->boolean('is_corrected')->default(false);
            $table->foreignId('corrected_by_user_id')->nullable()->constrained('users');
            $table->text('correction_reason')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['game_id', 'period', 'time_remaining']);
            $table->index(['player_id', 'action_type']);
            $table->index(['team_id', 'action_type']);
            $table->index(['game_id', 'recorded_at']);
            $table->index(['shot_x', 'shot_y']); // For shot charts
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_actions');
    }
};
```

#### Live Games Migration (Real-time Game State)

```php
<?php
// database/migrations/2024_02_03_000000_create_live_games_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->unique()->constrained()->onDelete('cascade');
            
            // Current Game State
            $table->integer('current_period')->default(1);
            $table->time('period_time_remaining')->default('10:00');
            $table->integer('period_time_elapsed_seconds')->default(0);
            $table->boolean('period_is_running')->default(false);
            $table->timestamp('period_started_at')->nullable();
            $table->timestamp('period_paused_at')->nullable();
            
            // Shot Clock
            $table->integer('shot_clock_remaining')->default(24);
            $table->boolean('shot_clock_is_running')->default(false);
            $table->timestamp('shot_clock_started_at')->nullable();
            
            // Current Scores
            $table->integer('current_score_home')->default(0);
            $table->integer('current_score_away')->default(0);
            $table->json('period_scores')->nullable(); // Scores by period
            
            // Team Status
            $table->integer('fouls_home_period')->default(0);
            $table->integer('fouls_away_period')->default(0);
            $table->integer('fouls_home_total')->default(0);
            $table->integer('fouls_away_total')->default(0);
            $table->integer('timeouts_home_remaining')->default(5);
            $table->integer('timeouts_away_remaining')->default(5);
            
            // Current Players on Court
            $table->json('players_on_court_home')->nullable(); // Array of player IDs
            $table->json('players_on_court_away')->nullable();
            
            // Game Flow Control
            $table->enum('game_phase', [
                'pregame', 'period', 'halftime', 'overtime', 
                'timeout', 'break', 'postgame'
            ])->default('pregame');
            
            $table->boolean('is_in_timeout')->default(false);
            $table->string('timeout_team')->nullable(); // 'home', 'away', 'official'
            $table->timestamp('timeout_started_at')->nullable();
            $table->integer('timeout_duration_seconds')->default(60);
            
            // Last Action Reference
            $table->foreignId('last_action_id')->nullable()->constrained('game_actions');
            $table->timestamp('last_action_at')->nullable();
            
            // Broadcasting
            $table->integer('viewers_count')->default(0);
            $table->boolean('is_being_broadcasted')->default(false);
            $table->json('broadcast_settings')->nullable();
            
            // Performance Tracking
            $table->integer('actions_count')->default(0);
            $table->timestamp('last_update_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['game_id', 'is_being_broadcasted']);
            $table->index('last_update_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_games');
    }
};
```

### Game Model Implementation

```php
<?php
// app/Models/Game.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Laravel\Scout\Searchable;

class Game extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, Searchable;

    protected $fillable = [
        'home_team_id',
        'away_team_id',
        'tournament_id',
        'scheduled_at',
        'actual_start_time',
        'actual_end_time',
        'venue',
        'venue_address',
        'referee_1',
        'referee_2',
        'referee_3',
        'table_official',
        'timekeeper',
        'status',
        'final_score_home',
        'final_score_away',
        'quarter_scores',
        'overtime_scores',
        'season',
        'game_type',
        'periods',
        'period_length',
        'overtime_enabled',
        'overtime_length',
        'game_settings',
        'league_rules',
        'shot_clock_enabled',
        'shot_clock_seconds',
        'weather_conditions',
        'temperature',
        'notes',
        'pregame_notes',
        'postgame_notes',
        'is_public',
        'livestream_enabled',
        'livestream_url',
        'attendance',
        'duration_minutes',
        'total_fouls_home',
        'total_fouls_away',
        'timeouts_used_home',
        'timeouts_used_away',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'actual_start_time' => 'datetime',
        'actual_end_time' => 'datetime',
        'quarter_scores' => 'array',
        'overtime_scores' => 'array',
        'game_settings' => 'array',
        'league_rules' => 'array',
        'periods' => 'integer',
        'period_length' => 'integer',
        'overtime_length' => 'integer',
        'shot_clock_seconds' => 'integer',
        'overtime_enabled' => 'boolean',
        'shot_clock_enabled' => 'boolean',
        'temperature' => 'decimal:1',
        'is_public' => 'boolean',
        'livestream_enabled' => 'boolean',
        'attendance' => 'integer',
        'duration_minutes' => 'decimal:2',
    ];

    // Relationships
    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function gameActions(): HasMany
    {
        return $this->hasMany(GameAction::class);
    }

    public function liveGame(): HasOne
    {
        return $this->hasOne(LiveGame::class);
    }

    public function scorekeeperAssignments(): HasMany
    {
        return $this->hasMany(ScorekeeperAssignment::class);
    }

    // Scopes
    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>', now())
                    ->where('status', 'scheduled');
    }

    public function scopeLive($query)
    {
        return $query->whereIn('status', ['warmup', 'live', 'halftime', 'overtime']);
    }

    public function scopeFinished($query)
    {
        return $query->where('status', 'finished');
    }

    public function scopeBySeason($query, string $season)
    {
        return $query->where('season', $season);
    }

    public function scopeByTeam($query, int $teamId)
    {
        return $query->where(function ($q) use ($teamId) {
            $q->where('home_team_id', $teamId)
              ->orWhere('away_team_id', $teamId);
        });
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    // Accessors & Mutators
    public function matchup(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->homeTeam->name . ' vs ' . $this->awayTeam->name,
        );
    }

    public function isLive(): Attribute
    {
        return Attribute::make(
            get: fn() => in_array($this->status, ['warmup', 'live', 'halftime', 'overtime']),
        );
    }

    public function isFinished(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === 'finished',
        );
    }

    public function hasStarted(): Attribute
    {
        return Attribute::make(
            get: fn() => !in_array($this->status, ['scheduled', 'cancelled', 'postponed']),
        );
    }

    public function winner(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->is_finished || $this->final_score_home === $this->final_score_away) {
                    return null;
                }
                
                return $this->final_score_home > $this->final_score_away 
                    ? $this->homeTeam 
                    : $this->awayTeam;
            },
        );
    }

    public function loser(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->is_finished || $this->final_score_home === $this->final_score_away) {
                    return null;
                }
                
                return $this->final_score_home < $this->final_score_away 
                    ? $this->homeTeam 
                    : $this->awayTeam;
            },
        );
    }

    public function totalPoints(): Attribute
    {
        return Attribute::make(
            get: fn() => ($this->final_score_home ?? 0) + ($this->final_score_away ?? 0),
        );
    }

    public function marginOfVictory(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->is_finished) {
                    return null;
                }
                
                return abs(($this->final_score_home ?? 0) - ($this->final_score_away ?? 0));
            },
        );
    }

    // Scout Search
    public function toSearchableArray(): array
    {
        return [
            'home_team' => $this->homeTeam->name,
            'away_team' => $this->awayTeam->name,
            'matchup' => $this->matchup,
            'venue' => $this->venue,
            'scheduled_at' => $this->scheduled_at,
            'season' => $this->season,
            'game_type' => $this->game_type,
            'status' => $this->status,
        ];
    }

    // Helper Methods
    public function getTeamForSide(string $side): Team
    {
        return $side === 'home' ? $this->homeTeam : $this->awayTeam;
    }

    public function getOpponentTeam(Team $team): Team
    {
        if ($this->home_team_id === $team->id) {
            return $this->awayTeam;
        } elseif ($this->away_team_id === $team->id) {
            return $this->homeTeam;
        }
        
        throw new \InvalidArgumentException('Team is not participating in this game');
    }

    public function isHomeTeam(Team $team): bool
    {
        return $this->home_team_id === $team->id;
    }

    public function isAwayTeam(Team $team): bool
    {
        return $this->away_team_id === $team->id;
    }

    public function canBeScored(): bool
    {
        return in_array($this->status, ['live', 'overtime']);
    }

    public function canBeStarted(): bool
    {
        return $this->status === 'scheduled' && 
               $this->scheduled_at <= now()->addMinutes(30);
    }

    public function needsOfficials(): bool
    {
        return empty($this->referee_1) || empty($this->table_official);
    }

    public function calculateDuration(): ?float
    {
        if (!$this->actual_start_time || !$this->actual_end_time) {
            return null;
        }
        
        return $this->actual_start_time->diffInMinutes($this->actual_end_time);
    }

    public function updateDuration(): void
    {
        $duration = $this->calculateDuration();
        if ($duration !== null) {
            $this->update(['duration_minutes' => $duration]);
        }
    }

    // Event Broadcasting
    protected $dispatchesEvents = [
        'updated' => GameUpdated::class,
        'created' => GameCreated::class,
    ];

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status', 'final_score_home', 'final_score_away',
                'scheduled_at', 'venue', 'referee_1'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
```

### Game Action Model

```php
<?php
// app/Models/GameAction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class GameAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'player_id',
        'team_id',
        'action_type',
        'period',
        'time_remaining',
        'game_clock_seconds',
        'shot_clock_remaining',
        'points',
        'is_successful',
        'is_assisted',
        'assisted_by_player_id',
        'shot_x',
        'shot_y',
        'shot_distance',
        'shot_zone',
        'foul_type',
        'foul_results_in_free_throws',
        'free_throws_awarded',
        'substituted_player_id',
        'substitution_reason',
        'description',
        'notes',
        'additional_data',
        'recorded_by_user_id',
        'recorded_from_ip',
        'recorded_at',
        'is_reviewed',
        'reviewed_by_user_id',
        'reviewed_at',
        'is_corrected',
        'corrected_by_user_id',
        'correction_reason',
    ];

    protected $casts = [
        'time_remaining' => 'datetime:H:i:s',
        'period' => 'integer',
        'game_clock_seconds' => 'integer',
        'shot_clock_remaining' => 'integer',
        'points' => 'integer',
        'is_successful' => 'boolean',
        'is_assisted' => 'boolean',
        'shot_x' => 'decimal:2',
        'shot_y' => 'decimal:2',
        'shot_distance' => 'decimal:1',
        'foul_results_in_free_throws' => 'boolean',
        'free_throws_awarded' => 'integer',
        'additional_data' => 'array',
        'recorded_at' => 'datetime',
        'is_reviewed' => 'boolean',
        'reviewed_at' => 'datetime',
        'is_corrected' => 'boolean',
    ];

    // Relationships
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function assistedByPlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'assisted_by_player_id');
    }

    public function substitutedPlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'substituted_player_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by_user_id');
    }

    // Scopes
    public function scopeScoring($query)
    {
        return $query->whereIn('action_type', [
            'field_goal_made', 'three_point_made', 'free_throw_made'
        ]);
    }

    public function scopeDefensive($query)
    {
        return $query->whereIn('action_type', [
            'rebound_defensive', 'steal', 'block'
        ]);
    }

    public function scopeFouls($query)
    {
        return $query->where('action_type', 'like', 'foul_%');
    }

    public function scopeByPeriod($query, int $period)
    {
        return $query->where('period', $period);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('is_successful', true);
    }

    public function scopeUnsuccessful($query)
    {
        return $query->where('is_successful', false);
    }

    // Accessors
    public function isShot(): Attribute
    {
        return Attribute::make(
            get: fn() => in_array($this->action_type, [
                'field_goal_made', 'field_goal_missed',
                'three_point_made', 'three_point_missed',
                'free_throw_made', 'free_throw_missed'
            ]),
        );
    }

    public function isThreePointer(): Attribute
    {
        return Attribute::make(
            get: fn() => in_array($this->action_type, [
                'three_point_made', 'three_point_missed'
            ]),
        );
    }

    public function isFreeThrow(): Attribute
    {
        return Attribute::make(
            get: fn() => in_array($this->action_type, [
                'free_throw_made', 'free_throw_missed'
            ]),
        );
    }

    public function isFoul(): Attribute
    {
        return Attribute::make(
            get: fn() => str_starts_with($this->action_type, 'foul_'),
        );
    }

    public function gameTimeElapsed(): Attribute
    {
        return Attribute::make(
            get: function () {
                $periodLength = $this->game->period_length * 60; // Convert to seconds
                $periodsCompleted = $this->period - 1;
                $timeRemainingSeconds = Carbon::parse($this->time_remaining)->secondsSinceMidnight();
                $periodTimeElapsed = $periodLength - $timeRemainingSeconds;
                
                return ($periodsCompleted * $periodLength) + $periodTimeElapsed;
            },
        );
    }

    public function displayTime(): Attribute
    {
        return Attribute::make(
            get: fn() => "Q{$this->period} {$this->time_remaining->format('i:s')}",
        );
    }

    public function actionDescription(): Attribute
    {
        return Attribute::make(
            get: function () {
                $descriptions = [
                    'field_goal_made' => '2-Punkte-Wurf getroffen',
                    'field_goal_missed' => '2-Punkte-Wurf verfehlt',
                    'three_point_made' => '3-Punkte-Wurf getroffen',
                    'three_point_missed' => '3-Punkte-Wurf verfehlt',
                    'free_throw_made' => 'Freiwurf getroffen',
                    'free_throw_missed' => 'Freiwurf verfehlt',
                    'rebound_offensive' => 'Offensiv-Rebound',
                    'rebound_defensive' => 'Defensiv-Rebound',
                    'assist' => 'Assist',
                    'steal' => 'Steal',
                    'block' => 'Block',
                    'turnover' => 'Ballverlust',
                    'foul_personal' => 'Persönliches Foul',
                    'foul_technical' => 'Technisches Foul',
                    'substitution_in' => 'Einwechslung',
                    'substitution_out' => 'Auswechslung',
                ];
                
                return $descriptions[$this->action_type] ?? $this->action_type;
            },
        );
    }

    // Helper Methods
    public function isPositiveAction(): bool
    {
        $positiveActions = [
            'field_goal_made', 'three_point_made', 'free_throw_made',
            'rebound_offensive', 'rebound_defensive', 'assist', 'steal', 'block'
        ];
        
        return in_array($this->action_type, $positiveActions);
    }

    public function isNegativeAction(): bool
    {
        $negativeActions = [
            'field_goal_missed', 'three_point_missed', 'free_throw_missed',
            'turnover', 'foul_personal', 'foul_technical', 'foul_flagrant'
        ];
        
        return in_array($this->action_type, $negativeActions);
    }

    public function getPointValue(): int
    {
        return match ($this->action_type) {
            'three_point_made' => 3,
            'field_goal_made' => 2,
            'free_throw_made' => 1,
            default => 0,
        };
    }

    public function requiresCoordinates(): bool
    {
        return $this->is_shot && !$this->is_free_throw;
    }

    public function calculateShotDistance(): ?float
    {
        if (!$this->shot_x || !$this->shot_y) {
            return null;
        }
        
        // Basketball court dimensions and calculations
        // Implementation would depend on court coordinate system
        return $this->shot_distance;
    }
}
```

### Live Game Model

```php
<?php
// app/Models/LiveGame.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class LiveGame extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'current_period',
        'period_time_remaining',
        'period_time_elapsed_seconds',
        'period_is_running',
        'period_started_at',
        'period_paused_at',
        'shot_clock_remaining',
        'shot_clock_is_running',
        'shot_clock_started_at',
        'current_score_home',
        'current_score_away',
        'period_scores',
        'fouls_home_period',
        'fouls_away_period',
        'fouls_home_total',
        'fouls_away_total',
        'timeouts_home_remaining',
        'timeouts_away_remaining',
        'players_on_court_home',
        'players_on_court_away',
        'game_phase',
        'is_in_timeout',
        'timeout_team',
        'timeout_started_at',
        'timeout_duration_seconds',
        'last_action_id',
        'last_action_at',
        'viewers_count',
        'is_being_broadcasted',
        'broadcast_settings',
        'actions_count',
        'last_update_at',
    ];

    protected $casts = [
        'current_period' => 'integer',
        'period_time_remaining' => 'datetime:H:i:s',
        'period_time_elapsed_seconds' => 'integer',
        'period_is_running' => 'boolean',
        'period_started_at' => 'datetime',
        'period_paused_at' => 'datetime',
        'shot_clock_remaining' => 'integer',
        'shot_clock_is_running' => 'boolean',
        'shot_clock_started_at' => 'datetime',
        'current_score_home' => 'integer',
        'current_score_away' => 'integer',
        'period_scores' => 'array',
        'fouls_home_period' => 'integer',
        'fouls_away_period' => 'integer',
        'fouls_home_total' => 'integer',
        'fouls_away_total' => 'integer',
        'timeouts_home_remaining' => 'integer',
        'timeouts_away_remaining' => 'integer',
        'players_on_court_home' => 'array',
        'players_on_court_away' => 'array',
        'is_in_timeout' => 'boolean',
        'timeout_started_at' => 'datetime',
        'timeout_duration_seconds' => 'integer',
        'last_action_at' => 'datetime',
        'viewers_count' => 'integer',
        'is_being_broadcasted' => 'boolean',
        'broadcast_settings' => 'array',
        'actions_count' => 'integer',
        'last_update_at' => 'datetime',
    ];

    // Relationships
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function lastAction(): BelongsTo
    {
        return $this->belongsTo(GameAction::class, 'last_action_id');
    }

    // Accessors
    public function isHalftime(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->game_phase === 'halftime',
        );
    }

    public function isOvertime(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->current_period > $this->game->periods,
        );
    }

    public function currentScoreDifference(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->current_score_home - $this->current_score_away,
        );
    }

    public function leadingTeam(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->current_score_home > $this->current_score_away) {
                    return 'home';
                } elseif ($this->current_score_away > $this->current_score_home) {
                    return 'away';
                } else {
                    return 'tied';
                }
            },
        );
    }

    public function periodProgressPercent(): Attribute
    {
        return Attribute::make(
            get: function () {
                $totalSeconds = $this->game->period_length * 60;
                $remainingSeconds = Carbon::parse($this->period_time_remaining)->secondsSinceMidnight();
                $elapsedSeconds = $totalSeconds - $remainingSeconds;
                
                return ($elapsedSeconds / $totalSeconds) * 100;
            },
        );
    }

    // Helper Methods
    public function startPeriod(): void
    {
        $this->update([
            'period_is_running' => true,
            'period_started_at' => now(),
            'period_paused_at' => null,
            'game_phase' => 'period',
            'last_update_at' => now(),
        ]);
    }

    public function pausePeriod(): void
    {
        if ($this->period_is_running) {
            $this->calculateElapsedTime();
            
            $this->update([
                'period_is_running' => false,
                'period_paused_at' => now(),
                'last_update_at' => now(),
            ]);
        }
    }

    public function resumePeriod(): void
    {
        $this->update([
            'period_is_running' => true,
            'period_started_at' => now(),
            'period_paused_at' => null,
            'last_update_at' => now(),
        ]);
    }

    public function endPeriod(): void
    {
        $this->update([
            'period_is_running' => false,
            'period_time_remaining' => '00:00:00',
            'game_phase' => $this->determineNextPhase(),
            'fouls_home_period' => 0,
            'fouls_away_period' => 0,
            'last_update_at' => now(),
        ]);
    }

    public function startTimeout(string $team, int $duration = 60): void
    {
        $this->pausePeriod();
        
        $this->update([
            'is_in_timeout' => true,
            'timeout_team' => $team,
            'timeout_started_at' => now(),
            'timeout_duration_seconds' => $duration,
            'game_phase' => 'timeout',
        ]);
        
        if (in_array($team, ['home', 'away'])) {
            $timeoutsField = "timeouts_{$team}_remaining";
            $this->decrement($timeoutsField);
        }
    }

    public function endTimeout(): void
    {
        $this->update([
            'is_in_timeout' => false,
            'timeout_team' => null,
            'timeout_started_at' => null,
            'timeout_duration_seconds' => 60,
            'game_phase' => 'period',
        ]);
        
        $this->resumePeriod();
    }

    public function updateScore(string $team, int $points): void
    {
        $scoreField = "current_score_{$team}";
        $this->increment($scoreField, $points);
        $this->touch('last_update_at');
    }

    public function resetShotClock(int $seconds = null): void
    {
        $seconds = $seconds ?? $this->game->shot_clock_seconds;
        
        $this->update([
            'shot_clock_remaining' => $seconds,
            'shot_clock_started_at' => $this->period_is_running ? now() : null,
            'shot_clock_is_running' => $this->period_is_running,
        ]);
    }

    public function addFoul(string $team): void
    {
        $this->increment("fouls_{$team}_period");
        $this->increment("fouls_{$team}_total");
        $this->touch('last_update_at');
    }

    public function updatePlayersOnCourt(string $team, array $playerIds): void
    {
        $this->update([
            "players_on_court_{$team}" => $playerIds,
            'last_update_at' => now(),
        ]);
    }

    public function incrementViewers(): void
    {
        $this->increment('viewers_count');
    }

    public function decrementViewers(): void
    {
        $this->decrement('viewers_count');
    }

    private function calculateElapsedTime(): void
    {
        if ($this->period_started_at) {
            $elapsed = $this->period_started_at->diffInSeconds(now());
            $this->increment('period_time_elapsed_seconds', $elapsed);
            
            // Update time remaining
            $totalSeconds = $this->game->period_length * 60;
            $remainingSeconds = max(0, $totalSeconds - $this->period_time_elapsed_seconds);
            
            $this->update([
                'period_time_remaining' => gmdate('H:i:s', $remainingSeconds)
            ]);
        }
    }

    private function determineNextPhase(): string
    {
        if ($this->current_period < $this->game->periods) {
            return $this->current_period == 2 ? 'halftime' : 'break';
        } elseif ($this->current_score_home === $this->current_score_away && $this->game->overtime_enabled) {
            return 'overtime';
        } else {
            return 'postgame';
        }
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'period_progress_percent' => $this->period_progress_percent,
            'leading_team' => $this->leading_team,
            'current_score_difference' => $this->current_score_difference,
            'is_halftime' => $this->is_halftime,
            'is_overtime' => $this->is_overtime,
        ]);
    }
}
```

---

## ⚡ Live-Scoring Features

### Live Scoring Controller

```php
<?php
// app/Http/Controllers/LiveScoringController.php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateGameScoreRequest;
use App\Http\Requests\AddGameActionRequest;
use App\Models\Game;
use App\Models\GameAction;
use App\Models\LiveGame;
use App\Services\LiveScoringService;
use App\Services\StatisticsService;
use App\Events\GameScoreUpdated;
use App\Events\GameActionAdded;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LiveScoringController extends Controller
{
    public function __construct(
        private LiveScoringService $liveScoringService,
        private StatisticsService $statisticsService
    ) {
        $this->middleware(['auth', 'can:score games']);
    }

    public function show(Game $game): Response
    {
        $this->authorize('score', $game);

        $game->load([
            'homeTeam.activePlayers',
            'awayTeam.activePlayers',
            'liveGame',
            'gameActions' => function ($query) {
                $query->with(['player', 'assistedByPlayer'])
                      ->latest()
                      ->limit(20);
            }
        ]);

        return Inertia::render('Games/LiveScoring', [
            'game' => $game,
            'liveGame' => $game->liveGame,
            'recentActions' => $game->gameActions,
            'homeRoster' => $game->homeTeam->activePlayers,
            'awayRoster' => $game->awayTeam->activePlayers,
            'canControl' => auth()->user()->can('controlGame', $game),
        ]);
    }

    public function startGame(Game $game): JsonResponse
    {
        $this->authorize('controlGame', $game);

        try {
            $liveGame = $this->liveScoringService->startGame($game);
            
            broadcast(new GameStarted($game, $liveGame));
            
            return response()->json([
                'success' => true,
                'message' => 'Spiel gestartet.',
                'liveGame' => $liveGame
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Spiel konnte nicht gestartet werden: ' . $e->getMessage()
            ], 400);
        }
    }

    public function addAction(AddGameActionRequest $request, Game $game): JsonResponse
    {
        $this->authorize('score', $game);

        try {
            DB::beginTransaction();

            $action = $this->liveScoringService->addGameAction($game, $request->validated());
            
            // Update live game state
            $liveGame = $this->liveScoringService->updateLiveGameState($game, $action);
            
            // Broadcast the action
            broadcast(new GameActionAdded($game, $action, $liveGame));
            
            // Update statistics asynchronously
            UpdateGameStatistics::dispatch($game)->onQueue('high');
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Aktion hinzugefügt.',
                'action' => $action->load(['player', 'assistedByPlayer']),
                'liveGame' => $liveGame
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Aktion konnte nicht hinzugefügt werden: ' . $e->getMessage()
            ], 400);
        }
    }

    public function updateScore(UpdateGameScoreRequest $request, Game $game): JsonResponse
    {
        $this->authorize('score', $game);

        try {
            $liveGame = $this->liveScoringService->updateScore(
                $game, 
                $request->team,
                $request->points,
                $request->player_id
            );
            
            broadcast(new GameScoreUpdated($game, $liveGame));
            
            return response()->json([
                'success' => true,
                'message' => 'Spielstand aktualisiert.',
                'liveGame' => $liveGame
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Spielstand konnte nicht aktualisiert werden: ' . $e->getMessage()
            ], 400);
        }
    }

    public function controlClock(Request $request, Game $game): JsonResponse
    {
        $this->authorize('controlGame', $game);

        $request->validate([
            'action' => 'required|in:start,pause,resume,end_period',
        ]);

        try {
            $liveGame = match ($request->action) {
                'start' => $this->liveScoringService->startPeriod($game),
                'pause' => $this->liveScoringService->pausePeriod($game),
                'resume' => $this->liveScoringService->resumePeriod($game),
                'end_period' => $this->liveScoringService->endPeriod($game),
            };
            
            broadcast(new GameClockUpdated($game, $liveGame, $request->action));
            
            return response()->json([
                'success' => true,
                'message' => 'Spielzeit aktualisiert.',
                'liveGame' => $liveGame
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Spielzeit konnte nicht aktualisiert werden: ' . $e->getMessage()
            ], 400);
        }
    }

    public function timeout(Request $request, Game $game): JsonResponse
    {
        $this->authorize('controlGame', $game);

        $request->validate([
            'team' => 'required|in:home,away,official',
            'duration' => 'integer|min:30|max:300',
        ]);

        try {
            $liveGame = $this->liveScoringService->startTimeout(
                $game,
                $request->team,
                $request->duration ?? 60
            );
            
            broadcast(new GameTimeoutStarted($game, $liveGame, $request->team));
            
            return response()->json([
                'success' => true,
                'message' => 'Timeout gestartet.',
                'liveGame' => $liveGame
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Timeout konnte nicht gestartet werden: ' . $e->getMessage()
            ], 400);
        }
    }

    public function endTimeout(Game $game): JsonResponse
    {
        $this->authorize('controlGame', $game);

        try {
            $liveGame = $this->liveScoringService->endTimeout($game);
            
            broadcast(new GameTimeoutEnded($game, $liveGame));
            
            return response()->json([
                'success' => true,
                'message' => 'Timeout beendet.',
                'liveGame' => $liveGame
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Timeout konnte nicht beendet werden: ' . $e->getMessage()
            ], 400);
        }
    }

    public function substitution(Request $request, Game $game): JsonResponse
    {
        $this->authorize('score', $game);

        $request->validate([
            'team' => 'required|in:home,away',
            'player_in_id' => 'required|exists:players,id',
            'player_out_id' => 'required|exists:players,id',
            'reason' => 'string|max:255',
        ]);

        try {
            $this->liveScoringService->processSubstitution(
                $game,
                $request->team,
                $request->player_in_id,
                $request->player_out_id,
                $request->reason
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Auswechslung durchgeführt.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Auswechslung konnte nicht durchgeführt werden: ' . $e->getMessage()
            ], 400);
        }
    }

    public function correctAction(Request $request, GameAction $action): JsonResponse
    {
        $this->authorize('correctAction', $action);

        $request->validate([
            'correction_reason' => 'required|string|max:500',
            'corrected_data' => 'array',
        ]);

        try {
            $correctedAction = $this->liveScoringService->correctAction(
                $action,
                $request->corrected_data ?? [],
                $request->correction_reason
            );
            
            broadcast(new GameActionCorrected($action->game, $correctedAction));
            
            return response()->json([
                'success' => true,
                'message' => 'Aktion korrigiert.',
                'action' => $correctedAction
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Aktion konnte nicht korrigiert werden: ' . $e->getMessage()
            ], 400);
        }
    }

    public function deleteAction(GameAction $action): JsonResponse
    {
        $this->authorize('deleteAction', $action);

        try {
            $game = $action->game;
            
            $this->liveScoringService->deleteAction($action);
            
            broadcast(new GameActionDeleted($game, $action->id));
            
            return response()->json([
                'success' => true,
                'message' => 'Aktion gelöscht.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Aktion konnte nicht gelöscht werden: ' . $e->getMessage()
            ], 400);
        }
    }

    public function finishGame(Game $game): JsonResponse
    {
        $this->authorize('controlGame', $game);

        try {
            $finishedGame = $this->liveScoringService->finishGame($game);
            
            broadcast(new GameFinished($finishedGame));
            
            // Generate final statistics
            GenerateFinalGameStatistics::dispatch($finishedGame);
            
            return response()->json([
                'success' => true,
                'message' => 'Spiel beendet.',
                'game' => $finishedGame
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Spiel konnte nicht beendet werden: ' . $e->getMessage()
            ], 400);
        }
    }

    public function getLiveData(Game $game): JsonResponse
    {
        $liveGame = $game->liveGame;
        
        if (!$liveGame) {
            return response()->json([
                'success' => false,
                'message' => 'Spiel ist nicht live.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'liveGame' => $liveGame,
                'recentActions' => $game->gameActions()
                    ->with(['player', 'assistedByPlayer'])
                    ->latest()
                    ->limit(10)
                    ->get(),
                'currentStats' => $this->statisticsService->getCurrentGameStats($game),
            ]
        ]);
    }
}
```

### Live Scoring Service

```php
<?php
// app/Services/LiveScoringService.php

namespace App\Services;

use App\Models\Game;
use App\Models\GameAction;
use App\Models\LiveGame;
use App\Models\Player;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LiveScoringService
{
    public function startGame(Game $game): LiveGame
    {
        if ($game->status !== 'scheduled') {
            throw new \Exception('Das Spiel kann nicht gestartet werden. Status: ' . $game->status);
        }

        return DB::transaction(function () use ($game) {
            // Update game status
            $game->update([
                'status' => 'warmup',
                'actual_start_time' => now(),
            ]);

            // Create or update live game
            $liveGame = LiveGame::updateOrCreate(
                ['game_id' => $game->id],
                [
                    'current_period' => 1,
                    'period_time_remaining' => sprintf('%02d:00:00', $game->period_length),
                    'period_is_running' => false,
                    'shot_clock_remaining' => $game->shot_clock_seconds,
                    'shot_clock_is_running' => false,
                    'current_score_home' => 0,
                    'current_score_away' => 0,
                    'period_scores' => [],
                    'fouls_home_period' => 0,
                    'fouls_away_period' => 0,
                    'fouls_home_total' => 0,
                    'fouls_away_total' => 0,
                    'timeouts_home_remaining' => 5,
                    'timeouts_away_remaining' => 5,
                    'game_phase' => 'pregame',
                    'is_being_broadcasted' => true,
                    'last_update_at' => now(),
                ]
            );

            return $liveGame;
        });
    }

    public function addGameAction(Game $game, array $actionData): GameAction
    {
        $liveGame = $game->liveGame;
        
        if (!$liveGame) {
            throw new \Exception('Spiel ist nicht live.');
        }

        return DB::transaction(function () use ($game, $liveGame, $actionData) {
            $action = GameAction::create(array_merge($actionData, [
                'game_id' => $game->id,
                'period' => $liveGame->current_period,
                'time_remaining' => $liveGame->period_time_remaining,
                'game_clock_seconds' => $this->calculateGameClockSeconds($liveGame),
                'shot_clock_remaining' => $liveGame->shot_clock_remaining,
                'recorded_by_user_id' => auth()->id(),
                'recorded_from_ip' => request()->ip(),
                'recorded_at' => now(),
            ]));

            // Update action count
            $liveGame->increment('actions_count');
            $liveGame->update([
                'last_action_id' => $action->id,
                'last_action_at' => now(),
                'last_update_at' => now(),
            ]);

            return $action;
        });
    }

    public function updateLiveGameState(Game $game, GameAction $action): LiveGame
    {
        $liveGame = $game->liveGame;

        return DB::transaction(function () use ($liveGame, $action) {
            // Update score if scoring action
            if ($action->points > 0) {
                $team = $action->team_id === $action->game->home_team_id ? 'home' : 'away';
                $liveGame->increment("current_score_{$team}", $action->points);
            }

            // Update fouls
            if ($action->is_foul) {
                $team = $action->team_id === $action->game->home_team_id ? 'home' : 'away';
                $liveGame->increment("fouls_{$team}_period");
                $liveGame->increment("fouls_{$team}_total");
            }

            // Reset shot clock on certain actions
            if ($this->shouldResetShotClock($action)) {
                $liveGame->update([
                    'shot_clock_remaining' => $action->game->shot_clock_seconds,
                    'shot_clock_started_at' => $liveGame->period_is_running ? now() : null,
                ]);
            }

            $liveGame->touch('last_update_at');
            
            return $liveGame;
        });
    }

    public function updateScore(Game $game, string $team, int $points, int $playerId): LiveGame
    {
        $liveGame = $game->liveGame;
        
        if (!$liveGame || !$liveGame->period_is_running) {
            throw new \Exception('Spielstand kann nur während laufendem Spiel aktualisiert werden.');
        }

        return DB::transaction(function () use ($liveGame, $team, $points, $playerId) {
            $liveGame->increment("current_score_{$team}", $points);
            $liveGame->touch('last_update_at');
            
            return $liveGame;
        });
    }

    public function startPeriod(Game $game): LiveGame
    {
        $liveGame = $game->liveGame;
        
        if (!$liveGame) {
            throw new \Exception('Spiel ist nicht live.');
        }

        return DB::transaction(function () use ($game, $liveGame) {
            $liveGame->startPeriod();
            
            // Update game status if first period
            if ($liveGame->current_period === 1 && $game->status === 'warmup') {
                $game->update(['status' => 'live']);
            }
            
            return $liveGame;
        });
    }

    public function pausePeriod(Game $game): LiveGame
    {
        $liveGame = $game->liveGame;
        
        if (!$liveGame || !$liveGame->period_is_running) {
            throw new \Exception('Periode läuft nicht und kann nicht pausiert werden.');
        }

        $liveGame->pausePeriod();
        
        return $liveGame;
    }

    public function resumePeriod(Game $game): LiveGame
    {
        $liveGame = $game->liveGame;
        
        if (!$liveGame || $liveGame->period_is_running) {
            throw new \Exception('Periode läuft bereits oder Spiel ist nicht pausiert.');
        }

        $liveGame->resumePeriod();
        
        return $liveGame;
    }

    public function endPeriod(Game $game): LiveGame
    {
        $liveGame = $game->liveGame;
        
        if (!$liveGame) {
            throw new \Exception('Spiel ist nicht live.');
        }

        return DB::transaction(function () use ($game, $liveGame) {
            // Save period score
            $periodScores = $liveGame->period_scores ?? [];
            $periodScores["period_{$liveGame->current_period}"] = [
                'home' => $liveGame->current_score_home,
                'away' => $liveGame->current_score_away,
            ];
            
            $liveGame->update(['period_scores' => $periodScores]);
            $liveGame->endPeriod();
            
            // Check if game should end or go to next period
            if ($this->shouldEndGame($liveGame)) {
                return $this->finishGame($game);
            } else {
                // Advance to next period
                $liveGame->update([
                    'current_period' => $liveGame->current_period + 1,
                    'period_time_remaining' => sprintf('%02d:00:00', 
                        $liveGame->is_overtime ? $game->overtime_length : $game->period_length
                    ),
                    'period_time_elapsed_seconds' => 0,
                ]);
                
                // Update game status for halftime
                if ($liveGame->current_period === 3 && $game->periods === 4) {
                    $game->update(['status' => 'halftime']);
                    $liveGame->update(['game_phase' => 'halftime']);
                }
            }
            
            return $liveGame;
        });
    }

    public function startTimeout(Game $game, string $team, int $duration = 60): LiveGame
    {
        $liveGame = $game->liveGame;
        
        if (!$liveGame) {
            throw new \Exception('Spiel ist nicht live.');
        }

        if ($team !== 'official' && $liveGame->{"timeouts_{$team}_remaining"} <= 0) {
            throw new \Exception("Team {$team} hat keine Timeouts mehr.");
        }

        $liveGame->startTimeout($team, $duration);
        
        return $liveGame;
    }

    public function endTimeout(Game $game): LiveGame
    {
        $liveGame = $game->liveGame;
        
        if (!$liveGame || !$liveGame->is_in_timeout) {
            throw new \Exception('Kein aktives Timeout.');
        }

        $liveGame->endTimeout();
        
        return $liveGame;
    }

    public function processSubstitution(Game $game, string $team, int $playerInId, int $playerOutId, ?string $reason = null): void
    {
        $liveGame = $game->liveGame;
        
        if (!$liveGame) {
            throw new \Exception('Spiel ist nicht live.');
        }

        DB::transaction(function () use ($game, $liveGame, $team, $playerInId, $playerOutId, $reason) {
            // Record substitution out
            GameAction::create([
                'game_id' => $game->id,
                'player_id' => $playerOutId,
                'team_id' => $team === 'home' ? $game->home_team_id : $game->away_team_id,
                'action_type' => 'substitution_out',
                'period' => $liveGame->current_period,
                'time_remaining' => $liveGame->period_time_remaining,
                'substituted_player_id' => $playerInId,
                'substitution_reason' => $reason,
                'recorded_by_user_id' => auth()->id(),
                'recorded_at' => now(),
            ]);

            // Record substitution in
            GameAction::create([
                'game_id' => $game->id,
                'player_id' => $playerInId,
                'team_id' => $team === 'home' ? $game->home_team_id : $game->away_team_id,
                'action_type' => 'substitution_in',
                'period' => $liveGame->current_period,
                'time_remaining' => $liveGame->period_time_remaining,
                'substituted_player_id' => $playerOutId,
                'substitution_reason' => $reason,
                'recorded_by_user_id' => auth()->id(),
                'recorded_at' => now(),
            ]);

            // Update players on court
            $playersOnCourt = $liveGame->{"players_on_court_{$team}"} ?? [];
            $playersOnCourt = array_map('intval', $playersOnCourt);
            
            // Remove player out and add player in
            $playersOnCourt = array_diff($playersOnCourt, [$playerOutId]);
            $playersOnCourt[] = $playerInId;
            
            $liveGame->updatePlayersOnCourt($team, array_values($playersOnCourt));
        });
    }

    public function correctAction(GameAction $action, array $correctedData, string $reason): GameAction
    {
        return DB::transaction(function () use ($action, $correctedData, $reason) {
            // Mark as corrected
            $action->update([
                'is_corrected' => true,
                'corrected_by_user_id' => auth()->id(),
                'correction_reason' => $reason,
            ]);

            // Update the corrected fields
            $action->update(array_intersect_key($correctedData, array_flip($action->getFillable())));

            // Recalculate live game state if necessary
            if (isset($correctedData['points']) || isset($correctedData['action_type'])) {
                $this->recalculateLiveGameState($action->game);
            }

            return $action;
        });
    }

    public function deleteAction(GameAction $action): void
    {
        DB::transaction(function () use ($action) {
            $game = $action->game;
            
            $action->delete();
            
            // Recalculate live game state
            $this->recalculateLiveGameState($game);
        });
    }

    public function finishGame(Game $game): Game
    {
        return DB::transaction(function () use ($game) {
            $liveGame = $game->liveGame;
            
            if (!$liveGame) {
                throw new \Exception('Spiel ist nicht live.');
            }

            // Update final scores
            $game->update([
                'status' => 'finished',
                'final_score_home' => $liveGame->current_score_home,
                'final_score_away' => $liveGame->current_score_away,
                'quarter_scores' => $liveGame->period_scores,
                'actual_end_time' => now(),
                'total_fouls_home' => $liveGame->fouls_home_total,
                'total_fouls_away' => $liveGame->fouls_away_total,
                'timeouts_used_home' => 5 - $liveGame->timeouts_home_remaining,
                'timeouts_used_away' => 5 - $liveGame->timeouts_away_remaining,
            ]);

            // Calculate duration
            $game->updateDuration();

            // Stop broadcasting
            $liveGame->update([
                'is_being_broadcasted' => false,
                'game_phase' => 'postgame',
                'period_is_running' => false,
                'shot_clock_is_running' => false,
            ]);

            return $game;
        });
    }

    private function calculateGameClockSeconds(LiveGame $liveGame): int
    {
        $periodLength = $liveGame->game->period_length * 60;
        $periodsCompleted = $liveGame->current_period - 1;
        $currentPeriodElapsed = $liveGame->period_time_elapsed_seconds;
        
        return ($periodsCompleted * $periodLength) + $currentPeriodElapsed;
    }

    private function shouldResetShotClock(GameAction $action): bool
    {
        $resetActions = [
            'field_goal_made', 'three_point_made',
            'rebound_offensive', 'foul_personal', 'foul_technical'
        ];
        
        return in_array($action->action_type, $resetActions);
    }

    private function shouldEndGame(LiveGame $liveGame): bool
    {
        $game = $liveGame->game;
        
        // Regular time finished
        if ($liveGame->current_period >= $game->periods) {
            // Check if tied and overtime enabled
            if ($liveGame->current_score_home === $liveGame->current_score_away && $game->overtime_enabled) {
                return false; // Go to overtime
            }
            return true; // Game ends
        }
        
        return false;
    }

    private function recalculateLiveGameState(Game $game): void
    {
        $liveGame = $game->liveGame;
        
        if (!$liveGame) {
            return;
        }

        // Recalculate scores from actions
        $homeScore = $game->gameActions()
            ->where('team_id', $game->home_team_id)
            ->sum('points');
            
        $awayScore = $game->gameActions()
            ->where('team_id', $game->away_team_id)
            ->sum('points');

        // Recalculate fouls
        $homeFouls = $game->gameActions()
            ->where('team_id', $game->home_team_id)
            ->where('action_type', 'like', 'foul_%')
            ->count();
            
        $awayFouls = $game->gameActions()
            ->where('team_id', $game->away_team_id)
            ->where('action_type', 'like', 'foul_%')
            ->count();

        $liveGame->update([
            'current_score_home' => $homeScore,
            'current_score_away' => $awayScore,
            'fouls_home_total' => $homeFouls,
            'fouls_away_total' => $awayFouls,
            'last_update_at' => now(),
        ]);
    }
}
```

---

## 📊 Statistics Engine

### Statistics Service

```php
<?php
// app/Services/StatisticsService.php

namespace App\Services;

use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Models\GameAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class StatisticsService
{
    private string $cachePrefix = 'basketball:stats:';
    private int $defaultCacheTtl = 3600; // 1 hour

    public function getPlayerGameStats(Player $player, Game $game): array
    {
        $cacheKey = $this->cachePrefix . "player:{$player->id}:game:{$game->id}";
        
        return Cache::remember($cacheKey, $this->defaultCacheTtl, function () use ($player, $game) {
            $actions = GameAction::where('game_id', $game->id)
                ->where('player_id', $player->id)
                ->get();

            return $this->calculatePlayerStatsFromActions($actions);
        });
    }

    public function getPlayerSeasonStats(Player $player, string $season): array
    {
        $cacheKey = $this->cachePrefix . "player:{$player->id}:season:{$season}";
        
        return Cache::remember($cacheKey, $this->defaultCacheTtl, function () use ($player, $season) {
            $actions = GameAction::whereHas('game', function ($query) use ($season) {
                    $query->where('season', $season)->where('status', 'finished');
                })
                ->where('player_id', $player->id)
                ->with('game')
                ->get();

            $stats = $this->calculatePlayerStatsFromActions($actions);
            
            // Add season-specific calculations
            $gamesPlayed = $actions->groupBy('game_id')->count();
            $stats['games_played'] = $gamesPlayed;
            
            // Calculate averages
            if ($gamesPlayed > 0) {
                $stats['avg_points'] = round($stats['total_points'] / $gamesPlayed, 1);
                $stats['avg_rebounds'] = round($stats['total_rebounds'] / $gamesPlayed, 1);
                $stats['avg_assists'] = round($stats['assists'] / $gamesPlayed, 1);
                $stats['avg_steals'] = round($stats['steals'] / $gamesPlayed, 1);
                $stats['avg_blocks'] = round($stats['blocks'] / $gamesPlayed, 1);
                $stats['avg_turnovers'] = round($stats['turnovers'] / $gamesPlayed, 1);
                $stats['avg_fouls'] = round($stats['personal_fouls'] / $gamesPlayed, 1);
                $stats['avg_minutes'] = round($stats['minutes_played'] / $gamesPlayed, 1);
            }

            return $stats;
        });
    }

    public function getTeamGameStats(Team $team, Game $game): array
    {
        $cacheKey = $this->cachePrefix . "team:{$team->id}:game:{$game->id}";
        
        return Cache::remember($cacheKey, $this->defaultCacheTtl, function () use ($team, $game) {
            $actions = GameAction::where('game_id', $game->id)
                ->where('team_id', $team->id)
                ->with('player')
                ->get();

            $stats = $this->calculateTeamStatsFromActions($actions);
            
            // Add game-specific info
            $stats['opponent'] = $game->getOpponentTeam($team)->name;
            $stats['final_score'] = $game->isHomeTeam($team) ? $game->final_score_home : $game->final_score_away;
            $stats['opponent_score'] = $game->isHomeTeam($team) ? $game->final_score_away : $game->final_score_home;
            $stats['is_win'] = $stats['final_score'] > $stats['opponent_score'];
            $stats['margin'] = $stats['final_score'] - $stats['opponent_score'];
            
            return $stats;
        });
    }

    public function getTeamSeasonStats(Team $team, string $season): array
    {
        $cacheKey = $this->cachePrefix . "team:{$team->id}:season:{$season}";
        
        return Cache::remember($cacheKey, $this->defaultCacheTtl, function () use ($team, $season) {
            // Get all games for this team in the season
            $games = Game::where('season', $season)
                ->where('status', 'finished')
                ->where(function ($query) use ($team) {
                    $query->where('home_team_id', $team->id)
                          ->orWhere('away_team_id', $team->id);
                })
                ->with(['gameActions' => function ($query) use ($team) {
                    $query->where('team_id', $team->id);
                }])
                ->get();

            $stats = [
                'games_played' => $games->count(),
                'wins' => 0,
                'losses' => 0,
                'points_for' => 0,
                'points_against' => 0,
                'total_rebounds' => 0,
                'assists' => 0,
                'steals' => 0,
                'blocks' => 0,
                'turnovers' => 0,
                'personal_fouls' => 0,
                'field_goals_made' => 0,
                'field_goals_attempted' => 0,
                'three_points_made' => 0,
                'three_points_attempted' => 0,
                'free_throws_made' => 0,
                'free_throws_attempted' => 0,
            ];

            foreach ($games as $game) {
                $teamScore = $game->isHomeTeam($team) ? $game->final_score_home : $game->final_score_away;
                $opponentScore = $game->isHomeTeam($team) ? $game->final_score_away : $game->final_score_home;
                
                $stats['points_for'] += $teamScore;
                $stats['points_against'] += $opponentScore;
                
                if ($teamScore > $opponentScore) {
                    $stats['wins']++;
                } else {
                    $stats['losses']++;
                }

                // Aggregate team stats from game actions
                $gameStats = $this->calculateTeamStatsFromActions($game->gameActions);
                $stats['total_rebounds'] += $gameStats['total_rebounds'];
                $stats['assists'] += $gameStats['assists'];
                $stats['steals'] += $gameStats['steals'];
                $stats['blocks'] += $gameStats['blocks'];
                $stats['turnovers'] += $gameStats['turnovers'];
                $stats['personal_fouls'] += $gameStats['personal_fouls'];
                $stats['field_goals_made'] += $gameStats['field_goals_made'];
                $stats['field_goals_attempted'] += $gameStats['field_goals_attempted'];
                $stats['three_points_made'] += $gameStats['three_points_made'];
                $stats['three_points_attempted'] += $gameStats['three_points_attempted'];
                $stats['free_throws_made'] += $gameStats['free_throws_made'];
                $stats['free_throws_attempted'] += $gameStats['free_throws_attempted'];
            }

            // Calculate percentages and averages
            $stats = $this->calculateAdvancedTeamStats($stats);

            return $stats;
        });
    }

    public function getCurrentGameStats(Game $game): array
    {
        if (!$game->liveGame) {
            return [];
        }

        $homeStats = $this->getTeamGameStats($game->homeTeam, $game);
        $awayStats = $this->getTeamGameStats($game->awayTeam, $game);

        return [
            'home' => array_merge($homeStats, [
                'current_score' => $game->liveGame->current_score_home,
                'fouls' => $game->liveGame->fouls_home_total,
                'timeouts_remaining' => $game->liveGame->timeouts_home_remaining,
            ]),
            'away' => array_merge($awayStats, [
                'current_score' => $game->liveGame->current_score_away,
                'fouls' => $game->liveGame->fouls_away_total,
                'timeouts_remaining' => $game->liveGame->timeouts_away_remaining,
            ]),
            'game_info' => [
                'period' => $game->liveGame->current_period,
                'time_remaining' => $game->liveGame->period_time_remaining,
                'is_running' => $game->liveGame->period_is_running,
                'phase' => $game->liveGame->game_phase,
            ]
        ];
    }

    private function calculatePlayerStatsFromActions($actions): array
    {
        $stats = [
            'total_points' => 0,
            'field_goals_made' => 0,
            'field_goals_attempted' => 0,
            'three_points_made' => 0,
            'three_points_attempted' => 0,
            'free_throws_made' => 0,
            'free_throws_attempted' => 0,
            'rebounds_offensive' => 0,
            'rebounds_defensive' => 0,
            'total_rebounds' => 0,
            'assists' => 0,
            'steals' => 0,
            'blocks' => 0,
            'turnovers' => 0,
            'personal_fouls' => 0,
            'technical_fouls' => 0,
            'minutes_played' => 0,
        ];

        foreach ($actions as $action) {
            switch ($action->action_type) {
                case 'field_goal_made':
                    $stats['field_goals_made']++;
                    $stats['total_points'] += 2;
                    break;
                case 'field_goal_missed':
                    $stats['field_goals_attempted']++;
                    break;
                case 'three_point_made':
                    $stats['three_points_made']++;
                    $stats['total_points'] += 3;
                    break;
                case 'three_point_missed':
                    $stats['three_points_attempted']++;
                    break;
                case 'free_throw_made':
                    $stats['free_throws_made']++;
                    $stats['total_points'] += 1;
                    break;
                case 'free_throw_missed':
                    $stats['free_throws_attempted']++;
                    break;
                case 'rebound_offensive':
                    $stats['rebounds_offensive']++;
                    $stats['total_rebounds']++;
                    break;
                case 'rebound_defensive':
                    $stats['rebounds_defensive']++;
                    $stats['total_rebounds']++;
                    break;
                case 'assist':
                    $stats['assists']++;
                    break;
                case 'steal':
                    $stats['steals']++;
                    break;
                case 'block':
                    $stats['blocks']++;
                    break;
                case 'turnover':
                    $stats['turnovers']++;
                    break;
                case 'foul_personal':
                    $stats['personal_fouls']++;
                    break;
                case 'foul_technical':
                    $stats['technical_fouls']++;
                    break;
            }
        }

        // Adjust attempted stats to include made shots
        $stats['field_goals_attempted'] += $stats['field_goals_made'];
        $stats['three_points_attempted'] += $stats['three_points_made'];
        $stats['free_throws_attempted'] += $stats['free_throws_made'];

        // Calculate shooting percentages
        $stats['field_goal_percentage'] = $stats['field_goals_attempted'] > 0 
            ? round(($stats['field_goals_made'] / $stats['field_goals_attempted']) * 100, 1) 
            : 0;
            
        $stats['three_point_percentage'] = $stats['three_points_attempted'] > 0 
            ? round(($stats['three_points_made'] / $stats['three_points_attempted']) * 100, 1) 
            : 0;
            
        $stats['free_throw_percentage'] = $stats['free_throws_attempted'] > 0 
            ? round(($stats['free_throws_made'] / $stats['free_throws_attempted']) * 100, 1) 
            : 0;

        // Calculate advanced stats
        $stats['true_shooting_percentage'] = $this->calculateTrueShootingPercentage(
            $stats['total_points'],
            $stats['field_goals_attempted'],
            $stats['free_throws_attempted']
        );

        $stats['player_efficiency_rating'] = $this->calculatePlayerEfficiencyRating($stats);

        return $stats;
    }

    private function calculateTeamStatsFromActions($actions): array
    {
        $stats = [
            'total_rebounds' => 0,
            'assists' => 0,
            'steals' => 0,
            'blocks' => 0,
            'turnovers' => 0,
            'personal_fouls' => 0,
            'field_goals_made' => 0,
            'field_goals_attempted' => 0,
            'three_points_made' => 0,
            'three_points_attempted' => 0,
            'free_throws_made' => 0,
            'free_throws_attempted' => 0,
        ];

        foreach ($actions as $action) {
            switch ($action->action_type) {
                case 'field_goal_made':
                    $stats['field_goals_made']++;
                    break;
                case 'field_goal_missed':
                    $stats['field_goals_attempted']++;
                    break;
                case 'three_point_made':
                    $stats['three_points_made']++;
                    break;
                case 'three_point_missed':
                    $stats['three_points_attempted']++;
                    break;
                case 'free_throw_made':
                    $stats['free_throws_made']++;
                    break;
                case 'free_throw_missed':
                    $stats['free_throws_attempted']++;
                    break;
                case 'rebound_offensive':
                case 'rebound_defensive':
                    $stats['total_rebounds']++;
                    break;
                case 'assist':
                    $stats['assists']++;
                    break;
                case 'steal':
                    $stats['steals']++;
                    break;
                case 'block':
                    $stats['blocks']++;
                    break;
                case 'turnover':
                    $stats['turnovers']++;
                    break;
                case 'foul_personal':
                    $stats['personal_fouls']++;
                    break;
            }
        }

        // Adjust attempted stats
        $stats['field_goals_attempted'] += $stats['field_goals_made'];
        $stats['three_points_attempted'] += $stats['three_points_made'];
        $stats['free_throws_attempted'] += $stats['free_throws_made'];

        return $stats;
    }

    private function calculateAdvancedTeamStats(array $stats): array
    {
        $gamesPlayed = $stats['games_played'];
        
        if ($gamesPlayed > 0) {
            // Averages per game
            $stats['avg_points_for'] = round($stats['points_for'] / $gamesPlayed, 1);
            $stats['avg_points_against'] = round($stats['points_against'] / $gamesPlayed, 1);
            $stats['avg_rebounds'] = round($stats['total_rebounds'] / $gamesPlayed, 1);
            $stats['avg_assists'] = round($stats['assists'] / $gamesPlayed, 1);

            // Win percentage
            $stats['win_percentage'] = round(($stats['wins'] / $gamesPlayed) * 100, 1);
            
            // Shooting percentages
            $stats['field_goal_percentage'] = $stats['field_goals_attempted'] > 0 
                ? round(($stats['field_goals_made'] / $stats['field_goals_attempted']) * 100, 1) 
                : 0;
                
            $stats['three_point_percentage'] = $stats['three_points_attempted'] > 0 
                ? round(($stats['three_points_made'] / $stats['three_points_attempted']) * 100, 1) 
                : 0;
                
            $stats['free_throw_percentage'] = $stats['free_throws_attempted'] > 0 
                ? round(($stats['free_throws_made'] / $stats['free_throws_attempted']) * 100, 1) 
                : 0;

            // Advanced metrics
            $stats['offensive_rating'] = $this->calculateOffensiveRating($stats);
            $stats['defensive_rating'] = $this->calculateDefensiveRating($stats);
            $stats['net_rating'] = $stats['offensive_rating'] - $stats['defensive_rating'];
        }

        return $stats;
    }

    private function calculateTrueShootingPercentage(int $points, int $fga, int $fta): float
    {
        $tsa = $fga + (0.44 * $fta); // True Shot Attempts
        
        return $tsa > 0 ? round(($points / (2 * $tsa)) * 100, 1) : 0;
    }

    private function calculatePlayerEfficiencyRating(array $stats): float
    {
        // Simplified PER calculation
        $per = ($stats['total_points'] + $stats['total_rebounds'] + $stats['assists'] 
                + $stats['steals'] + $stats['blocks'] - $stats['turnovers'] 
                - $stats['personal_fouls']);
                
        return round($per, 1);
    }

    private function calculateOffensiveRating(array $stats): float
    {
        $possessions = $this->estimatePossessions($stats);
        
        return $possessions > 0 ? round(($stats['points_for'] / $possessions) * 100, 1) : 0;
    }

    private function calculateDefensiveRating(array $stats): float
    {
        $possessions = $this->estimatePossessions($stats);
        
        return $possessions > 0 ? round(($stats['points_against'] / $possessions) * 100, 1) : 0;
    }

    private function estimatePossessions(array $stats): float
    {
        // Simplified possession estimation
        return $stats['field_goals_attempted'] + ($stats['turnovers'] * 0.8) + ($stats['free_throws_attempted'] * 0.44);
    }

    public function invalidatePlayerStats(Player $player): void
    {
        $pattern = $this->cachePrefix . "player:{$player->id}:*";
        $this->deleteCacheByPattern($pattern);
        
        // Also invalidate team stats
        if ($player->team) {
            $this->invalidateTeamStats($player->team);
        }
    }

    public function invalidateTeamStats(Team $team): void
    {
        $pattern = $this->cachePrefix . "team:{$team->id}:*";
        $this->deleteCacheByPattern($pattern);
    }

    public function invalidateGameStats(Game $game): void
    {
        $pattern = $this->cachePrefix . "*:game:{$game->id}";
        $this->deleteCacheByPattern($pattern);
    }

    private function deleteCacheByPattern(string $pattern): void
    {
        // This would be implemented based on your cache driver
        // For Redis, you could use SCAN and DEL commands
        // For file cache, you might need a different approach
        Cache::flush(); // Simplified for now
    }
}
```

---

## 🏆 League Management System

### League Models & Database Design

#### Leagues Migration

```php
<?php
// database/migrations/2024_02_20_000000_create_leagues_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leagues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            
            // Basic Information
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            
            // League Configuration
            $table->enum('type', ['regular', 'cup', 'playoff', 'tournament'])->default('regular');
            $table->enum('format', ['round_robin', 'single_elimination', 'double_elimination', 'swiss'])->default('round_robin');
            $table->enum('category', ['U8', 'U10', 'U12', 'U14', 'U16', 'U18', 'U20', 'Herren', 'Damen', 'Senioren']);
            $table->enum('gender', ['male', 'female', 'mixed']);
            
            // Season Information
            $table->string('season');
            $table->date('season_start');
            $table->date('season_end');
            $table->date('registration_deadline')->nullable();
            
            // League Rules
            $table->integer('max_teams')->default(16);
            $table->integer('min_teams')->default(4);
            $table->integer('games_per_matchday')->nullable();
            $table->json('scoring_system'); // Points for win/draw/loss
            $table->json('tiebreaker_rules'); // How to resolve ties
            $table->json('promotion_relegation')->nullable();
            
            // Settings
            $table->boolean('public_standings')->default(true);
            $table->boolean('allow_transfers')->default(true);
            $table->date('transfer_deadline')->nullable();
            $table->boolean('playoff_system')->default(false);
            $table->integer('playoff_teams')->nullable();
            
            // Status
            $table->enum('status', ['planning', 'registration', 'active', 'completed', 'cancelled']);
            $table->boolean('is_published')->default(false);
            $table->integer('current_matchday')->default(0);
            
            // Contact Information
            $table->string('commissioner_name')->nullable();
            $table->string('commissioner_email')->nullable();
            $table->string('commissioner_phone')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['season', 'category', 'gender']);
            $table->index(['status', 'is_published']);
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leagues');
    }
};
```

#### League Teams Migration

```php
<?php
// database/migrations/2024_02_21_000000_create_league_teams_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('league_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->constrained();
            
            // Registration
            $table->timestamp('registered_at');
            $table->foreignId('registered_by_user_id')->constrained('users');
            $table->enum('registration_status', ['pending', 'approved', 'rejected', 'withdrawn']);
            
            // League Position
            $table->integer('league_position')->nullable();
            $table->integer('group_number')->nullable(); // For group-based leagues
            
            // Statistics
            $table->integer('games_played')->default(0);
            $table->integer('wins')->default(0);
            $table->integer('draws')->default(0);
            $table->integer('losses')->default(0);
            $table->integer('points_for')->default(0);
            $table->integer('points_against')->default(0);
            $table->integer('league_points')->default(0); // League standings points
            
            // Additional Stats
            $table->integer('home_wins')->default(0);
            $table->integer('home_losses')->default(0);
            $table->integer('away_wins')->default(0);
            $table->integer('away_losses')->default(0);
            $table->integer('streak_current')->default(0); // Current win/loss streak
            $table->string('streak_type')->nullable(); // 'W' or 'L'
            
            // Penalties & Deductions
            $table->integer('point_deductions')->default(0);
            $table->text('deduction_reason')->nullable();
            $table->boolean('relegated')->default(false);
            $table->boolean('promoted')->default(false);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['league_id', 'league_position']);
            $table->index(['league_id', 'league_points']);
            $table->unique(['league_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('league_teams');
    }
};
```

### League Service Implementation

```php
<?php
// app/Services/LeagueService.php

namespace App\Services;

use App\Models\League;
use App\Models\Team;
use App\Models\Game;
use App\Models\LeagueTeam;
use App\Jobs\UpdateLeagueStandings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class LeagueService
{
    public function generateStandings(League $league): array
    {
        $cacheKey = "league_standings_{$league->id}";
        
        return Cache::remember($cacheKey, 3600, function () use ($league) {
            $standings = LeagueTeam::where('league_id', $league->id)
                ->with(['team'])
                ->orderBy('league_points', 'desc')
                ->orderByRaw('(points_for - points_against) DESC')
                ->orderBy('points_for', 'desc')
                ->get()
                ->map(function ($leagueTeam, $index) {
                    return [
                        'position' => $index + 1,
                        'team' => $leagueTeam->team,
                        'games_played' => $leagueTeam->games_played,
                        'wins' => $leagueTeam->wins,
                        'draws' => $leagueTeam->draws,
                        'losses' => $leagueTeam->losses,
                        'points_for' => $leagueTeam->points_for,
                        'points_against' => $leagueTeam->points_against,
                        'point_difference' => $leagueTeam->points_for - $leagueTeam->points_against,
                        'league_points' => $leagueTeam->league_points,
                        'form' => $this->getTeamForm($leagueTeam->team, $league),
                        'streak' => [
                            'type' => $leagueTeam->streak_type,
                            'count' => $leagueTeam->streak_current
                        ],
                        'home_record' => [
                            'wins' => $leagueTeam->home_wins,
                            'losses' => $leagueTeam->home_losses
                        ],
                        'away_record' => [
                            'wins' => $leagueTeam->away_wins,
                            'losses' => $leagueTeam->away_losses
                        ]
                    ];
                });

            return $standings->toArray();
        });
    }

    public function updateStandingsAfterGame(Game $game): void
    {
        if (!$game->isFinished() || !$game->tournament_id) {
            return;
        }

        $league = League::where('id', $game->tournament_id)->first();
        if (!$league) {
            return;
        }

        DB::transaction(function () use ($game, $league) {
            $homeTeam = LeagueTeam::where('league_id', $league->id)
                ->where('team_id', $game->home_team_id)
                ->first();
                
            $awayTeam = LeagueTeam::where('league_id', $league->id)
                ->where('team_id', $game->away_team_id)
                ->first();

            if (!$homeTeam || !$awayTeam) {
                return;
            }

            // Update game statistics
            $this->updateTeamStats($homeTeam, $game, true);
            $this->updateTeamStats($awayTeam, $game, false);

            // Update league points based on result
            $this->updateLeaguePoints($homeTeam, $awayTeam, $game, $league);

            // Update streaks
            $this->updateTeamStreaks($homeTeam, $awayTeam, $game);
        });

        // Clear standings cache
        Cache::forget("league_standings_{$league->id}");
        
        // Dispatch job to update related caches and rankings
        UpdateLeagueStandings::dispatch($league);
    }

    private function updateTeamStats(LeagueTeam $leagueTeam, Game $game, bool $isHome): void
    {
        $teamScore = $isHome ? $game->final_score_home : $game->final_score_away;
        $opponentScore = $isHome ? $game->final_score_away : $game->final_score_home;
        
        $leagueTeam->increment('games_played');
        $leagueTeam->increment('points_for', $teamScore);
        $leagueTeam->increment('points_against', $opponentScore);

        if ($teamScore > $opponentScore) {
            // Win
            $leagueTeam->increment('wins');
            if ($isHome) {
                $leagueTeam->increment('home_wins');
            } else {
                $leagueTeam->increment('away_wins');
            }
        } elseif ($teamScore < $opponentScore) {
            // Loss
            $leagueTeam->increment('losses');
            if ($isHome) {
                $leagueTeam->increment('home_losses');
            } else {
                $leagueTeam->increment('away_losses');
            }
        } else {
            // Draw (if applicable)
            $leagueTeam->increment('draws');
        }
    }

    private function updateLeaguePoints(LeagueTeam $homeTeam, LeagueTeam $awayTeam, Game $game, League $league): void
    {
        $scoringSystem = $league->scoring_system;
        $homeScore = $game->final_score_home;
        $awayScore = $game->final_score_away;

        if ($homeScore > $awayScore) {
            // Home team wins
            $homeTeam->increment('league_points', $scoringSystem['win'] ?? 2);
            $awayTeam->increment('league_points', $scoringSystem['loss'] ?? 0);
        } elseif ($homeScore < $awayScore) {
            // Away team wins
            $awayTeam->increment('league_points', $scoringSystem['win'] ?? 2);
            $homeTeam->increment('league_points', $scoringSystem['loss'] ?? 0);
        } else {
            // Draw
            $homeTeam->increment('league_points', $scoringSystem['draw'] ?? 1);
            $awayTeam->increment('league_points', $scoringSystem['draw'] ?? 1);
        }
    }

    private function updateTeamStreaks(LeagueTeam $homeTeam, LeagueTeam $awayTeam, Game $game): void
    {
        $homeWon = $game->final_score_home > $game->final_score_away;
        $awayWon = $game->final_score_away > $game->final_score_home;

        // Update home team streak
        if ($homeWon) {
            if ($homeTeam->streak_type === 'W') {
                $homeTeam->increment('streak_current');
            } else {
                $homeTeam->update(['streak_type' => 'W', 'streak_current' => 1]);
            }
        } else {
            if ($homeTeam->streak_type === 'L') {
                $homeTeam->increment('streak_current');
            } else {
                $homeTeam->update(['streak_type' => 'L', 'streak_current' => 1]);
            }
        }

        // Update away team streak
        if ($awayWon) {
            if ($awayTeam->streak_type === 'W') {
                $awayTeam->increment('streak_current');
            } else {
                $awayTeam->update(['streak_type' => 'W', 'streak_current' => 1]);
            }
        } else {
            if ($awayTeam->streak_type === 'L') {
                $awayTeam->increment('streak_current');
            } else {
                $awayTeam->update(['streak_type' => 'L', 'streak_current' => 1]);
            }
        }
    }

    private function getTeamForm(Team $team, League $league): array
    {
        $recentGames = Game::where(function ($query) use ($team) {
                $query->where('home_team_id', $team->id)
                      ->orWhere('away_team_id', $team->id);
            })
            ->where('tournament_id', $league->id)
            ->where('status', 'finished')
            ->orderBy('scheduled_at', 'desc')
            ->limit(5)
            ->get();

        return $recentGames->map(function ($game) use ($team) {
            $isHome = $game->home_team_id === $team->id;
            $teamScore = $isHome ? $game->final_score_home : $game->final_score_away;
            $opponentScore = $isHome ? $game->final_score_away : $game->final_score_home;

            if ($teamScore > $opponentScore) {
                return 'W';
            } elseif ($teamScore < $opponentScore) {
                return 'L';
            } else {
                return 'D';
            }
        })->reverse()->values()->toArray();
    }

    public function generateFixtures(League $league): array
    {
        $teams = $league->teams()->get();
        $teamCount = $teams->count();
        
        if ($teamCount < 2) {
            return [];
        }

        $fixtures = [];
        
        if ($league->format === 'round_robin') {
            $fixtures = $this->generateRoundRobinFixtures($teams, $league);
        }
        
        return $fixtures;
    }

    private function generateRoundRobinFixtures($teams, League $league): array
    {
        $fixtures = [];
        $teamArray = $teams->toArray();
        $teamCount = count($teamArray);
        
        // If odd number of teams, add bye
        if ($teamCount % 2 !== 0) {
            $teamArray[] = null; // Bye
            $teamCount++;
        }

        $rounds = $teamCount - 1;
        $matchesPerRound = $teamCount / 2;

        for ($round = 0; $round < $rounds; $round++) {
            $roundFixtures = [];
            
            for ($match = 0; $match < $matchesPerRound; $match++) {
                $home = ($round + $match) % ($teamCount - 1);
                $away = ($teamCount - 1 - $match + $round) % ($teamCount - 1);
                
                if ($match === 0) {
                    $away = $teamCount - 1;
                }

                $homeTeam = $teamArray[$home];
                $awayTeam = $teamArray[$away];

                // Skip if bye
                if (!$homeTeam || !$awayTeam) {
                    continue;
                }

                $roundFixtures[] = [
                    'round' => $round + 1,
                    'home_team' => $homeTeam,
                    'away_team' => $awayTeam,
                ];
            }
            
            $fixtures[] = $roundFixtures;
        }

        return $fixtures;
    }
}
```

---

## 📊 Advanced Analytics & Reporting

### Enhanced Player Performance Metrics

```php
<?php
// app/Services/AdvancedAnalyticsService.php

namespace App\Services;

use App\Models\Player;
use App\Models\Game;
use App\Models\GameAction;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AdvancedAnalyticsService
{
    public function calculatePlayerEfficiencyRating(Player $player, string $season): float
    {
        $cacheKey = "player_per_{$player->id}_{$season}";
        
        return Cache::remember($cacheKey, 1800, function () use ($player, $season) {
            $gameActions = GameAction::whereHas('game', function ($query) use ($season) {
                    $query->where('season', $season)->where('status', 'finished');
                })
                ->where('player_id', $player->id)
                ->get();

            if ($gameActions->isEmpty()) {
                return 0.0;
            }

            $stats = $this->aggregatePlayerStats($gameActions);
            
            // PER = (Points + Rebounds + Assists + Steals + Blocks - Turnovers - Missed FG - Missed FT) / Games Played
            $per = (
                $stats['total_points'] + 
                $stats['total_rebounds'] + 
                $stats['assists'] + 
                $stats['steals'] + 
                $stats['blocks'] - 
                $stats['turnovers'] - 
                ($stats['field_goals_attempted'] - $stats['field_goals_made']) -
                ($stats['free_throws_attempted'] - $stats['free_throws_made'])
            ) / max(1, $stats['games_played']);

            return round($per, 2);
        });
    }

    public function calculatePlusMinus(Player $player, Game $game): array
    {
        $playerActions = GameAction::where('game_id', $game->id)
            ->where('player_id', $player->id)
            ->orderBy('period')
            ->orderBy('time_remaining', 'desc')
            ->get();

        $teamScore = 0;
        $opponentScore = 0;
        $isOnCourt = false;
        
        // This would need more complex logic to track when player is on/off court
        // For now, we'll use a simplified calculation
        
        $playerOnCourtActions = $playerActions->whereBetween('created_at', [
            $game->actual_start_time,
            $game->actual_end_time
        ]);

        foreach ($playerOnCourtActions as $action) {
            if ($action->team_id === $player->team_id) {
                $teamScore += $action->points;
            } else {
                // This would be opponent actions while player was on court
                // Requires more complex tracking
            }
        }

        return [
            'plus_minus' => $teamScore - $opponentScore,
            'team_points_while_on_court' => $teamScore,
            'opponent_points_while_on_court' => $opponentScore,
        ];
    }

    public function generateShotChart(Player $player, string $season): array
    {
        $shots = GameAction::whereHas('game', function ($query) use ($season) {
                $query->where('season', $season)->where('status', 'finished');
            })
            ->where('player_id', $player->id)
            ->whereIn('action_type', [
                'field_goal_made', 'field_goal_missed',
                'three_point_made', 'three_point_missed'
            ])
            ->whereNotNull('shot_x')
            ->whereNotNull('shot_y')
            ->get();

        $shotZones = [
            'paint' => ['made' => 0, 'attempted' => 0],
            'mid_range' => ['made' => 0, 'attempted' => 0],
            'three_point' => ['made' => 0, 'attempted' => 0],
            'free_throw_line' => ['made' => 0, 'attempted' => 0],
        ];

        $shotData = [];

        foreach ($shots as $shot) {
            $zone = $this->determineShotZone($shot->shot_x, $shot->shot_y);
            $made = in_array($shot->action_type, ['field_goal_made', 'three_point_made']);
            
            $shotZones[$zone]['attempted']++;
            if ($made) {
                $shotZones[$zone]['made']++;
            }

            $shotData[] = [
                'x' => $shot->shot_x,
                'y' => $shot->shot_y,
                'made' => $made,
                'zone' => $zone,
                'distance' => $shot->shot_distance,
                'period' => $shot->period,
                'game_id' => $shot->game_id,
            ];
        }

        // Calculate percentages
        foreach ($shotZones as $zone => &$data) {
            $data['percentage'] = $data['attempted'] > 0 
                ? round(($data['made'] / $data['attempted']) * 100, 1) 
                : 0;
        }

        return [
            'shot_zones' => $shotZones,
            'shot_data' => $shotData,
            'total_shots' => $shots->count(),
            'shooting_percentage' => $shots->count() > 0 
                ? round(($shots->whereIn('action_type', ['field_goal_made', 'three_point_made'])->count() / $shots->count()) * 100, 1)
                : 0,
        ];
    }

    public function calculateTeamChemistry(Team $team, string $season): array
    {
        // Analyze assist networks, player combinations, etc.
        $assists = GameAction::whereHas('game', function ($query) use ($season, $team) {
                $query->where('season', $season)
                      ->where('status', 'finished')
                      ->where(function ($q) use ($team) {
                          $q->where('home_team_id', $team->id)
                            ->orWhere('away_team_id', $team->id);
                      });
            })
            ->where('action_type', 'assist')
            ->whereHas('player', function ($query) use ($team) {
                $query->where('team_id', $team->id);
            })
            ->with(['player', 'assistedByPlayer'])
            ->get();

        $assistNetwork = [];
        foreach ($assists as $assist) {
            $assisterId = $assist->player_id;
            $scorerId = $assist->assisted_by_player_id;
            
            if (!isset($assistNetwork[$assisterId])) {
                $assistNetwork[$assisterId] = [];
            }
            
            if (!isset($assistNetwork[$assisterId][$scorerId])) {
                $assistNetwork[$assisterId][$scorerId] = 0;
            }
            
            $assistNetwork[$assisterId][$scorerId]++;
        }

        return [
            'assist_network' => $assistNetwork,
            'total_assists' => $assists->count(),
            'chemistry_score' => $this->calculateChemistryScore($assistNetwork),
        ];
    }

    private function aggregatePlayerStats($gameActions): array
    {
        $stats = [
            'total_points' => 0,
            'field_goals_made' => 0,
            'field_goals_attempted' => 0,
            'three_points_made' => 0,
            'three_points_attempted' => 0,
            'free_throws_made' => 0,
            'free_throws_attempted' => 0,
            'total_rebounds' => 0,
            'assists' => 0,
            'steals' => 0,
            'blocks' => 0,
            'turnovers' => 0,
            'games_played' => $gameActions->groupBy('game_id')->count(),
        ];

        foreach ($gameActions as $action) {
            switch ($action->action_type) {
                case 'field_goal_made':
                    $stats['field_goals_made']++;
                    $stats['total_points'] += 2;
                    break;
                case 'field_goal_missed':
                    $stats['field_goals_attempted']++;
                    break;
                case 'three_point_made':
                    $stats['three_points_made']++;
                    $stats['total_points'] += 3;
                    break;
                case 'three_point_missed':
                    $stats['three_points_attempted']++;
                    break;
                case 'free_throw_made':
                    $stats['free_throws_made']++;
                    $stats['total_points'] += 1;
                    break;
                case 'free_throw_missed':
                    $stats['free_throws_attempted']++;
                    break;
                case 'rebound_offensive':
                case 'rebound_defensive':
                    $stats['total_rebounds']++;
                    break;
                case 'assist':
                    $stats['assists']++;
                    break;
                case 'steal':
                    $stats['steals']++;
                    break;
                case 'block':
                    $stats['blocks']++;
                    break;
                case 'turnover':
                    $stats['turnovers']++;
                    break;
            }
        }

        $stats['field_goals_attempted'] += $stats['field_goals_made'];
        $stats['three_points_attempted'] += $stats['three_points_made'];
        $stats['free_throws_attempted'] += $stats['free_throws_made'];

        return $stats;
    }

    private function determineShotZone(float $x, float $y): string
    {
        $distance = sqrt($x * $x + $y * $y);
        
        if ($distance <= 5) {
            return 'paint';
        } elseif ($distance <= 15) {
            return 'mid_range';
        } elseif ($distance >= 23.75) {
            return 'three_point';
        } else {
            return 'free_throw_line';
        }
    }

    private function calculateChemistryScore(array $assistNetwork): float
    {
        $totalConnections = 0;
        $strongConnections = 0;
        
        foreach ($assistNetwork as $assister => $receivers) {
            foreach ($receivers as $receiver => $count) {
                $totalConnections++;
                if ($count >= 5) { // Strong connection threshold
                    $strongConnections++;
                }
            }
        }
        
        return $totalConnections > 0 ? ($strongConnections / $totalConnections) * 100 : 0;
    }
}
```

---

## 📋 PDF & Excel Export System

### Export Service Implementation

```php
<?php
// app/Services/ReportExportService.php

namespace App\Services;

use App\Models\Team;
use App\Models\Player;
use App\Models\Game;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TeamStatsExport;
use App\Exports\PlayerStatsExport;
use App\Exports\GameReportExport;
use Illuminate\Http\Response;

class ReportExportService
{
    public function exportTeamStatsPDF(Team $team, string $season): Response
    {
        $statsService = app(StatisticsService::class);
        $teamStats = $statsService->getTeamSeasonStats($team, $season);
        $playerStats = $team->players->map(function ($player) use ($statsService, $season) {
            return [
                'player' => $player,
                'stats' => $statsService->getPlayerSeasonStats($player, $season)
            ];
        });

        $pdf = PDF::loadView('exports.team-stats-pdf', [
            'team' => $team,
            'season' => $season,
            'teamStats' => $teamStats,
            'playerStats' => $playerStats,
            'generatedAt' => now()->format('d.m.Y H:i')
        ]);

        $filename = "team-stats-{$team->slug}-{$season}.pdf";
        
        return $pdf->download($filename);
    }

    public function exportPlayerStatsPDF(Player $player, string $season): Response
    {
        $statsService = app(StatisticsService::class);
        $analyticsService = app(AdvancedAnalyticsService::class);
        
        $stats = $statsService->getPlayerSeasonStats($player, $season);
        $per = $analyticsService->calculatePlayerEfficiencyRating($player, $season);
        $shotChart = $analyticsService->generateShotChart($player, $season);

        $recentGames = Game::where(function ($query) use ($player) {
                $query->where('home_team_id', $player->team_id)
                      ->orWhere('away_team_id', $player->team_id);
            })
            ->where('season', $season)
            ->where('status', 'finished')
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('scheduled_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($game) use ($player, $statsService) {
                return [
                    'game' => $game,
                    'stats' => $statsService->getPlayerGameStats($player, $game)
                ];
            });

        $pdf = PDF::loadView('exports.player-stats-pdf', [
            'player' => $player,
            'season' => $season,
            'stats' => $stats,
            'per' => $per,
            'shotChart' => $shotChart,
            'recentGames' => $recentGames,
            'generatedAt' => now()->format('d.m.Y H:i')
        ]);

        $filename = "player-stats-{$player->slug}-{$season}.pdf";
        
        return $pdf->download($filename);
    }

    public function exportGameReportPDF(Game $game): Response
    {
        $game->load([
            'homeTeam.players',
            'awayTeam.players', 
            'gameActions.player',
            'liveGame'
        ]);

        $statsService = app(StatisticsService::class);
        $homeStats = $statsService->getTeamGameStats($game->homeTeam, $game);
        $awayStats = $statsService->getTeamGameStats($game->awayTeam, $game);

        $playerStats = [];
        foreach ([$game->homeTeam, $game->awayTeam] as $team) {
            foreach ($team->players as $player) {
                $playerStats[$team->id][$player->id] = $statsService->getPlayerGameStats($player, $game);
            }
        }

        $pdf = PDF::loadView('exports.game-report-pdf', [
            'game' => $game,
            'homeStats' => $homeStats,
            'awayStats' => $awayStats,
            'playerStats' => $playerStats,
            'generatedAt' => now()->format('d.m.Y H:i')
        ]);

        $filename = "game-report-{$game->id}-{$game->scheduled_at->format('Y-m-d')}.pdf";
        
        return $pdf->download($filename);
    }

    public function exportTeamStatsExcel(Team $team, string $season)
    {
        $filename = "team-stats-{$team->slug}-{$season}.xlsx";
        
        return Excel::download(new TeamStatsExport($team, $season), $filename);
    }

    public function exportPlayerStatsExcel(Team $team, string $season)
    {
        $filename = "player-stats-{$team->slug}-{$season}.xlsx";
        
        return Excel::download(new PlayerStatsExport($team, $season), $filename);
    }

    public function exportGameDataExcel(Game $game)
    {
        $filename = "game-data-{$game->id}.xlsx";
        
        return Excel::download(new GameReportExport($game), $filename);
    }
}
```

### Excel Export Classes

```php
<?php
// app/Exports/TeamStatsExport.php

namespace App\Exports;

use App\Models\Team;
use App\Services\StatisticsService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TeamStatsExport implements WithMultipleSheets
{
    private Team $team;
    private string $season;

    public function __construct(Team $team, string $season)
    {
        $this->team = $team;
        $this->season = $season;
    }

    public function sheets(): array
    {
        return [
            'Team Overview' => new TeamOverviewSheet($this->team, $this->season),
            'Player Stats' => new PlayerStatsSheet($this->team, $this->season),
            'Game Results' => new GameResultsSheet($this->team, $this->season),
        ];
    }
}

class PlayerStatsSheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    private Team $team;
    private string $season;

    public function __construct(Team $team, string $season)
    {
        $this->team = $team;
        $this->season = $season;
    }

    public function collection()
    {
        $statsService = app(StatisticsService::class);
        
        return $this->team->players->map(function ($player) use ($statsService) {
            return [
                'player' => $player,
                'stats' => $statsService->getPlayerSeasonStats($player, $this->season)
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Player Name',
            'Jersey Number',
            'Position', 
            'Games Played',
            'Points',
            'Avg Points',
            'Rebounds',
            'Avg Rebounds',
            'Assists',
            'Avg Assists',
            'Steals',
            'Blocks',
            'Turnovers',
            'FG%',
            '3P%',
            'FT%',
            'Minutes Played'
        ];
    }

    public function map($row): array
    {
        $player = $row['player'];
        $stats = $row['stats'];

        return [
            $player->full_name,
            $player->jersey_number,
            $player->position,
            $stats['games_played'] ?? 0,
            $stats['total_points'] ?? 0,
            $stats['avg_points'] ?? 0,
            $stats['total_rebounds'] ?? 0,
            $stats['avg_rebounds'] ?? 0,
            $stats['assists'] ?? 0,
            $stats['avg_assists'] ?? 0,
            $stats['steals'] ?? 0,
            $stats['blocks'] ?? 0,
            $stats['turnovers'] ?? 0,
            $stats['field_goal_percentage'] ?? 0,
            $stats['three_point_percentage'] ?? 0,
            $stats['free_throw_percentage'] ?? 0,
            $stats['minutes_played'] ?? 0,
        ];
    }

    public function title(): string
    {
        return 'Player Stats';
    }
}
```

---

Das vervollständigt die Phase 2 PRD mit den erweiterten Features für League Management, Advanced Analytics und Export-Funktionen, die in der Next_Development_Steps.md gefordert sind.