<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class TournamentTeam extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'tournament_id',
        'team_id',
        'registered_at',
        'registered_by_user_id',
        'registration_notes',
        'status',
        'status_reason',
        'status_updated_at',
        'seed',
        'group_name',
        'group_position',
        'games_played',
        'wins',
        'losses',
        'draws',
        'points_for',
        'points_against',
        'tournament_points',
        'point_differential',
        'final_position',
        'elimination_round',
        'eliminated_at',
        'entry_fee_paid',
        'payment_date',
        'payment_method',
        'prize_money',
        'contact_person',
        'contact_email',
        'contact_phone',
        'special_requirements',
        'travel_information',
        'roster_players',
        'emergency_contacts',
        'medical_forms_complete',
        'insurance_verified',
        'individual_awards',
        'team_awards',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'status_updated_at' => 'datetime',
        'eliminated_at' => 'datetime',
        'payment_date' => 'datetime',
        'games_played' => 'integer',
        'wins' => 'integer',
        'losses' => 'integer',
        'draws' => 'integer',
        'points_for' => 'integer',
        'points_against' => 'integer',
        'tournament_points' => 'integer',
        'point_differential' => 'decimal:2',
        'final_position' => 'integer',
        'entry_fee_paid' => 'boolean',
        'prize_money' => 'decimal:2',
        'travel_information' => 'array',
        'roster_players' => 'array',
        'emergency_contacts' => 'array',
        'medical_forms_complete' => 'boolean',
        'insurance_verified' => 'boolean',
        'individual_awards' => 'array',
        'team_awards' => 'array',
    ];

    // Relationships
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }

    public function homeBrackets(): HasMany
    {
        return $this->hasMany(TournamentBracket::class, 'team1_id');
    }

    public function awayBrackets(): HasMany
    {
        return $this->hasMany(TournamentBracket::class, 'team2_id');
    }

    public function winnerBrackets(): HasMany
    {
        return $this->hasMany(TournamentBracket::class, 'winner_team_id');
    }

    public function loserBrackets(): HasMany
    {
        return $this->hasMany(TournamentBracket::class, 'loser_team_id');
    }

    public function awards(): HasMany
    {
        return $this->hasMany(TournamentAward::class, 'recipient_team_id');
    }

    // Scopes
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByGroup($query, string $groupName)
    {
        return $query->where('group_name', $groupName);
    }

    public function scopeByElimination($query, string $round)
    {
        return $query->where('elimination_round', $round);
    }

    public function scopeStillActive($query)
    {
        return $query->whereNull('eliminated_at')
                    ->whereNotIn('status', ['withdrawn', 'disqualified']);
    }

    public function scopeEliminated($query)
    {
        return $query->whereNotNull('eliminated_at');
    }

    // Accessors
    public function isApproved(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === 'approved',
        );
    }

    public function isPending(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === 'pending',
        );
    }

    public function isEliminated(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->eliminated_at !== null,
        );
    }

    public function isStillActive(): Attribute
    {
        return Attribute::make(
            get: fn() => !$this->is_eliminated && 
                        !in_array($this->status, ['withdrawn', 'disqualified']),
        );
    }

    public function winPercentage(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->games_played === 0) return 0;
                return ($this->wins / $this->games_played) * 100;
            },
        );
    }

    public function averagePointsFor(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->games_played === 0) return 0;
                return $this->points_for / $this->games_played;
            },
        );
    }

    public function averagePointsAgainst(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->games_played === 0) return 0;
                return $this->points_against / $this->games_played;
            },
        );
    }

    public function statusDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match($this->status) {
                    'pending' => 'Ausstehend',
                    'approved' => 'Zugelassen',
                    'rejected' => 'Abgelehnt',
                    'withdrawn' => 'Zurückgezogen',
                    'disqualified' => 'Disqualifiziert',
                    default => $this->status,
                };
            },
        );
    }

    public function eliminationRoundDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->elimination_round) return null;
                
                return match($this->elimination_round) {
                    'group_stage' => 'Gruppenphase',
                    'round_of_32' => 'Runde der letzten 32',
                    'round_of_16' => 'Achtelfinale',
                    'quarterfinal' => 'Viertelfinale',
                    'semifinal' => 'Halbfinale',
                    'final' => 'Finale',
                    'third_place' => 'Spiel um Platz 3',
                    'winner' => 'Sieger',
                    default => $this->elimination_round,
                };
            },
        );
    }

    // Business Logic Methods
    public function recordWin(int $pointsFor, int $pointsAgainst, int $tournamentPoints = 2): void
    {
        $this->increment('wins');
        $this->increment('games_played');
        $this->increment('points_for', $pointsFor);
        $this->increment('points_against', $pointsAgainst);
        $this->increment('tournament_points', $tournamentPoints);
        
        $this->point_differential = $this->points_for - $this->points_against;
        $this->save();
    }

    public function recordLoss(int $pointsFor, int $pointsAgainst, int $tournamentPoints = 0): void
    {
        $this->increment('losses');
        $this->increment('games_played');
        $this->increment('points_for', $pointsFor);
        $this->increment('points_against', $pointsAgainst);
        $this->increment('tournament_points', $tournamentPoints);
        
        $this->point_differential = $this->points_for - $this->points_against;
        $this->save();
    }

    public function recordDraw(int $pointsFor, int $pointsAgainst, int $tournamentPoints = 1): void
    {
        $this->increment('draws');
        $this->increment('games_played');
        $this->increment('points_for', $pointsFor);
        $this->increment('points_against', $pointsAgainst);
        $this->increment('tournament_points', $tournamentPoints);
        
        $this->point_differential = $this->points_for - $this->points_against;
        $this->save();
    }

    public function eliminate(string $round): void
    {
        $this->update([
            'elimination_round' => $round,
            'eliminated_at' => now(),
        ]);
    }

    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'status_updated_at' => now(),
        ]);
    }

    public function reject(string $reason = null): void
    {
        $this->update([
            'status' => 'rejected',
            'status_reason' => $reason,
            'status_updated_at' => now(),
        ]);
    }

    public function withdraw(string $reason = null): void
    {
        $this->update([
            'status' => 'withdrawn',
            'status_reason' => $reason,
            'status_updated_at' => now(),
        ]);
    }

    public function disqualify(string $reason = null): void
    {
        $this->update([
            'status' => 'disqualified',
            'status_reason' => $reason,
            'status_updated_at' => now(),
            'eliminated_at' => now(),
        ]);
    }

    public function canAdvance(): bool
    {
        return $this->is_still_active && $this->is_approved;
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