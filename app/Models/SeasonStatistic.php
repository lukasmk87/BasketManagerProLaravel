<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeasonStatistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'season_id',
        'team_id',
        'club_id',
        'games_played',
        'games_started',
        'minutes_played',
        'points',
        'field_goals_made',
        'field_goals_attempted',
        'field_goal_percentage',
        'three_pointers_made',
        'three_pointers_attempted',
        'three_point_percentage',
        'free_throws_made',
        'free_throws_attempted',
        'free_throw_percentage',
        'rebounds_offensive',
        'rebounds_defensive',
        'rebounds_total',
        'assists',
        'turnovers',
        'assist_turnover_ratio',
        'steals',
        'blocks',
        'fouls_personal',
        'fouls_technical',
        'fouls_flagrant',
        'advanced_stats',
        'game_highs',
        'metadata',
        'snapshot_date',
    ];

    protected $casts = [
        'games_played' => 'integer',
        'games_started' => 'integer',
        'minutes_played' => 'integer',
        'points' => 'integer',
        'field_goals_made' => 'integer',
        'field_goals_attempted' => 'integer',
        'field_goal_percentage' => 'decimal:2',
        'three_pointers_made' => 'integer',
        'three_pointers_attempted' => 'integer',
        'three_point_percentage' => 'decimal:2',
        'free_throws_made' => 'integer',
        'free_throws_attempted' => 'integer',
        'free_throw_percentage' => 'decimal:2',
        'rebounds_offensive' => 'integer',
        'rebounds_defensive' => 'integer',
        'rebounds_total' => 'integer',
        'assists' => 'integer',
        'turnovers' => 'integer',
        'assist_turnover_ratio' => 'decimal:2',
        'steals' => 'integer',
        'blocks' => 'integer',
        'fouls_personal' => 'integer',
        'fouls_technical' => 'integer',
        'fouls_flagrant' => 'integer',
        'advanced_stats' => 'array',
        'game_highs' => 'array',
        'metadata' => 'array',
        'snapshot_date' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(BasketballTeam::class, 'team_id');
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Helper Methods
     */
    public function getPointsPerGameAttribute(): float
    {
        return $this->games_played > 0
            ? round($this->points / $this->games_played, 1)
            : 0.0;
    }

    public function getReboundsPerGameAttribute(): float
    {
        return $this->games_played > 0
            ? round($this->rebounds_total / $this->games_played, 1)
            : 0.0;
    }

    public function getAssistsPerGameAttribute(): float
    {
        return $this->games_played > 0
            ? round($this->assists / $this->games_played, 1)
            : 0.0;
    }

    public function getMinutesPerGameAttribute(): float
    {
        return $this->games_played > 0
            ? round($this->minutes_played / $this->games_played, 1)
            : 0.0;
    }

    /**
     * Gibt eine Zusammenfassung der Statistiken zurück
     */
    public function getSummary(): array
    {
        return [
            'player' => $this->player->full_name ?? 'Unknown',
            'season' => $this->season->name ?? 'Unknown',
            'team' => $this->team->name ?? 'Unknown',
            'games_played' => $this->games_played,
            'ppg' => $this->points_per_game,
            'rpg' => $this->rebounds_per_game,
            'apg' => $this->assists_per_game,
            'fg_pct' => $this->field_goal_percentage,
            'three_p_pct' => $this->three_point_percentage,
            'ft_pct' => $this->free_throw_percentage,
        ];
    }
}
