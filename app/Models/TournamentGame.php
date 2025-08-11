<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class TournamentGame extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'tournament_id',
        'tournament_bracket_id',
        'base_game_id',
        'tournament_game_number',
        'importance_level',
        'is_featured_game',
        'special_rules',
        'mercy_rule_enabled',
        'mercy_rule_points',
        'mercy_rule_period',
        'livestream_scheduled',
        'livestream_url',
        'recording_enabled',
        'media_assignments',
        'head_referee_id',
        'assistant_referee_id',
        'scorekeeper_id',
        'timekeeper_id',
        'officials_fee',
        'detailed_stats_enabled',
        'stat_categories',
        'stats_recorder_id',
        'expected_spectators',
        'actual_spectators',
        'ticket_sales',
        'tickets_sold',
        'atmosphere_rating',
        'atmosphere_notes',
        'special_events',
        'game_recap',
        'player_of_game',
        'key_moments',
        'game_rating',
        'advancement_implications',
        'elimination_game',
        'championship_implications',
    ];

    protected $casts = [
        'importance_level' => 'integer',
        'is_featured_game' => 'boolean',
        'special_rules' => 'array',
        'mercy_rule_enabled' => 'boolean',
        'mercy_rule_points' => 'integer',
        'mercy_rule_period' => 'integer',
        'livestream_scheduled' => 'boolean',
        'recording_enabled' => 'boolean',
        'media_assignments' => 'array',
        'officials_fee' => 'decimal:2',
        'detailed_stats_enabled' => 'boolean',
        'stat_categories' => 'array',
        'expected_spectators' => 'integer',
        'actual_spectators' => 'integer',
        'ticket_sales' => 'decimal:2',
        'tickets_sold' => 'integer',
        'atmosphere_rating' => 'integer',
        'special_events' => 'array',
        'player_of_game' => 'array',
        'key_moments' => 'array',
        'game_rating' => 'decimal:2',
        'advancement_implications' => 'array',
        'elimination_game' => 'boolean',
        'championship_implications' => 'boolean',
    ];

    // Relationships
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function bracket(): BelongsTo
    {
        return $this->belongsTo(TournamentBracket::class, 'tournament_bracket_id');
    }

    public function baseGame(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'base_game_id');
    }

    public function headReferee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_referee_id');
    }

    public function assistantReferee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assistant_referee_id');
    }

    public function scorekeeper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scorekeeper_id');
    }

    public function timekeeper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'timekeeper_id');
    }

    public function statsRecorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stats_recorder_id');
    }

    // Scopes
    public function scopeFeatured($query)
    {
        return $query->where('is_featured_game', true);
    }

    public function scopeEliminationGames($query)
    {
        return $query->where('elimination_game', true);
    }

    public function scopeChampionshipImplications($query)
    {
        return $query->where('championship_implications', true);
    }

    public function scopeByImportance($query, int $level)
    {
        return $query->where('importance_level', $level);
    }

    public function scopeHighImportance($query)
    {
        return $query->where('importance_level', '>=', 4);
    }

    public function scopeWithLivestream($query)
    {
        return $query->where('livestream_scheduled', true);
    }

    public function scopeWithRecording($query)
    {
        return $query->where('recording_enabled', true);
    }

    // Accessors
    public function isFeatured(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->is_featured_game,
        );
    }

    public function isEliminationGame(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->elimination_game,
        );
    }

    public function hasChampionshipImplications(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->championship_implications,
        );
    }

    public function isHighImportance(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->importance_level >= 4,
        );
    }

    public function hasLivestream(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->livestream_scheduled && !empty($this->livestream_url),
        );
    }

    public function hasRecording(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->recording_enabled,
        );
    }

    public function spectatorAttendanceRate(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->expected_spectators || $this->expected_spectators === 0) {
                    return null;
                }
                return ($this->actual_spectators / $this->expected_spectators) * 100;
            },
        );
    }

    public function averageTicketPrice(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->tickets_sold || $this->tickets_sold === 0) {
                    return 0;
                }
                return $this->ticket_sales / $this->tickets_sold;
            },
        );
    }

    public function importanceLevelDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match($this->importance_level) {
                    1 => 'Niedrig',
                    2 => 'Normal',
                    3 => 'Wichtig',
                    4 => 'Sehr wichtig',
                    5 => 'Finale/Entscheidung',
                    default => 'Unbekannt',
                };
            },
        );
    }

    public function atmosphereRatingDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->atmosphere_rating) return null;
                
                return match(true) {
                    $this->atmosphere_rating <= 2 => 'Schwach',
                    $this->atmosphere_rating <= 4 => 'Mäßig',
                    $this->atmosphere_rating <= 6 => 'Gut',
                    $this->atmosphere_rating <= 8 => 'Sehr gut',
                    $this->atmosphere_rating <= 10 => 'Ausgezeichnet',
                    default => 'Unbewertet',
                };
            },
        );
    }

    public function gameRatingDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->game_rating) return null;
                
                return match(true) {
                    $this->game_rating <= 2 => 'Sehr schwach',
                    $this->game_rating <= 4 => 'Schwach',
                    $this->game_rating <= 6 => 'Durchschnittlich',
                    $this->game_rating <= 8 => 'Gut',
                    $this->game_rating <= 10 => 'Hervorragend',
                    default => 'Unbewertet',
                };
            },
        );
    }

    // Business Logic Methods
    public function assignOfficials(
        User $headReferee = null,
        User $assistantReferee = null,
        User $scorekeeper = null,
        User $timekeeper = null,
        float $fee = null
    ): void {
        $this->update([
            'head_referee_id' => $headReferee?->id,
            'assistant_referee_id' => $assistantReferee?->id,
            'scorekeeper_id' => $scorekeeper?->id,
            'timekeeper_id' => $timekeeper?->id,
            'officials_fee' => $fee,
        ]);
    }

    public function scheduleForLivestream(string $url): void
    {
        $this->update([
            'livestream_scheduled' => true,
            'livestream_url' => $url,
        ]);
    }

    public function enableRecording(): void
    {
        $this->update([
            'recording_enabled' => true,
        ]);
    }

    public function recordSpectatorData(int $actualSpectators, float $ticketSales = null, int $ticketsSold = null): void
    {
        $this->update([
            'actual_spectators' => $actualSpectators,
            'ticket_sales' => $ticketSales ?? $this->ticket_sales,
            'tickets_sold' => $ticketsSold ?? $this->tickets_sold,
        ]);
    }

    public function rateAtmosphere(int $rating, string $notes = null): void
    {
        $this->update([
            'atmosphere_rating' => max(1, min(10, $rating)),
            'atmosphere_notes' => $notes,
        ]);
    }

    public function rateGame(float $rating): void
    {
        $this->update([
            'game_rating' => max(1, min(10, $rating)),
        ]);
    }

    public function addKeyMoment(string $description, int $minute, string $type = 'general'): void
    {
        $moments = $this->key_moments ?? [];
        $moments[] = [
            'minute' => $minute,
            'type' => $type,
            'description' => $description,
            'timestamp' => now()->toISOString(),
        ];
        
        $this->update(['key_moments' => $moments]);
    }

    public function setPlayerOfGame(array $players): void
    {
        $this->update(['player_of_game' => $players]);
    }

    public function writeRecap(string $recap): void
    {
        $this->update(['game_recap' => $recap]);
    }

    public function markAsElimination(): void
    {
        $this->update(['elimination_game' => true]);
    }

    public function markWithChampionshipImplications(): void
    {
        $this->update(['championship_implications' => true]);
    }

    public function setImportanceLevel(int $level): void
    {
        $this->update(['importance_level' => max(1, min(5, $level))]);
    }

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
                         ->logFillable()
                         ->logOnlyDirty()
                         ->dontSubmitEmptyLogs();
    }
}