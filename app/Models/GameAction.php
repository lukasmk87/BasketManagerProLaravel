<?php

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
                $periodLength = $this->game->period_length_minutes * 60; // Convert to seconds
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