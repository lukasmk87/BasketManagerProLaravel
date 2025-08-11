<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class TournamentBracket extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'tournament_id',
        'game_id',
        'bracket_type',
        'round',
        'round_name',
        'position_in_round',
        'total_rounds',
        'team1_id',
        'team2_id',
        'winner_team_id',
        'loser_team_id',
        'winner_advances_to',
        'loser_advances_to',
        'scheduled_at',
        'venue',
        'court',
        'primary_referee_id',
        'secondary_referee_id',
        'scorekeeper',
        'status',
        'team1_seed',
        'team2_seed',
        'matchup_description',
        'team1_score',
        'team2_score',
        'score_by_period',
        'overtime',
        'overtime_periods',
        'game_notes',
        'forfeit_team_id',
        'forfeit_reason',
        'actual_start_time',
        'actual_end_time',
        'actual_duration',
        'group_name',
        'group_round',
        'swiss_round',
        'swiss_rating_change',
    ];

    protected $casts = [
        'round' => 'integer',
        'position_in_round' => 'integer',
        'total_rounds' => 'integer',
        'scheduled_at' => 'datetime',
        'team1_seed' => 'integer',
        'team2_seed' => 'integer',
        'team1_score' => 'integer',
        'team2_score' => 'integer',
        'score_by_period' => 'array',
        'overtime' => 'boolean',
        'overtime_periods' => 'integer',
        'actual_start_time' => 'datetime',
        'actual_end_time' => 'datetime',
        'actual_duration' => 'integer',
        'group_round' => 'integer',
        'swiss_round' => 'integer',
        'swiss_rating_change' => 'decimal:2',
    ];

    // Relationships
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function team1(): BelongsTo
    {
        return $this->belongsTo(TournamentTeam::class, 'team1_id');
    }

    public function team2(): BelongsTo
    {
        return $this->belongsTo(TournamentTeam::class, 'team2_id');
    }

    public function winnerTeam(): BelongsTo
    {
        return $this->belongsTo(TournamentTeam::class, 'winner_team_id');
    }

    public function loserTeam(): BelongsTo
    {
        return $this->belongsTo(TournamentTeam::class, 'loser_team_id');
    }

    public function winnerAdvancesTo(): BelongsTo
    {
        return $this->belongsTo(TournamentBracket::class, 'winner_advances_to');
    }

    public function loserAdvancesTo(): BelongsTo
    {
        return $this->belongsTo(TournamentBracket::class, 'loser_advances_to');
    }

    public function primaryReferee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_referee_id');
    }

    public function secondaryReferee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'secondary_referee_id');
    }

    public function forfeitTeam(): BelongsTo
    {
        return $this->belongsTo(TournamentTeam::class, 'forfeit_team_id');
    }

    public function feedsInto(): HasMany
    {
        return $this->hasMany(TournamentBracket::class, 'winner_advances_to');
    }

    public function feedsIntoLoser(): HasMany
    {
        return $this->hasMany(TournamentBracket::class, 'loser_advances_to');
    }

    // Scopes
    public function scopeByBracketType($query, string $type)
    {
        return $query->where('bracket_type', $type);
    }

    public function scopeByRound($query, int $round)
    {
        return $query->where('round', $round);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeByGroup($query, string $groupName)
    {
        return $query->where('group_name', $groupName);
    }

    public function scopeMainBracket($query)
    {
        return $query->where('bracket_type', 'main');
    }

    public function scopeConsolationBracket($query)
    {
        return $query->where('bracket_type', 'consolation');
    }

    public function scopeReady($query)
    {
        return $query->whereNotNull('team1_id')
                    ->whereNotNull('team2_id');
    }

    // Accessors
    public function isPending(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === 'pending',
        );
    }

    public function isScheduled(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === 'scheduled',
        );
    }

    public function isInProgress(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === 'in_progress',
        );
    }

    public function isCompleted(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === 'completed',
        );
    }

    public function isBye(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === 'bye',
        );
    }

    public function hasBothTeams(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->team1_id && $this->team2_id,
        );
    }

    public function canStart(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->has_both_teams && 
                        $this->status === 'scheduled' &&
                        $this->scheduled_at <= now(),
        );
    }

    public function canComplete(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === 'in_progress' &&
                        $this->team1_score !== null &&
                        $this->team2_score !== null,
        );
    }

    public function winnerScore(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->is_completed) return null;
                return max($this->team1_score ?? 0, $this->team2_score ?? 0);
            },
        );
    }

    public function loserScore(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->is_completed) return null;
                return min($this->team1_score ?? 0, $this->team2_score ?? 0);
            },
        );
    }

    public function marginOfVictory(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->is_completed) return null;
                return abs(($this->team1_score ?? 0) - ($this->team2_score ?? 0));
            },
        );
    }

    public function statusDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match($this->status) {
                    'pending' => 'Ausstehend',
                    'scheduled' => 'Angesetzt',
                    'in_progress' => 'Läuft',
                    'completed' => 'Abgeschlossen',
                    'bye' => 'Freilos',
                    'forfeit' => 'Kampflos',
                    'cancelled' => 'Abgesagt',
                    default => $this->status,
                };
            },
        );
    }

    public function bracketTypeDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match($this->bracket_type) {
                    'main' => 'Hauptrunde',
                    'consolation' => 'Trostspiele',
                    'third_place' => 'Spiel um Platz 3',
                    default => $this->bracket_type,
                };
            },
        );
    }

    // Business Logic Methods
    public function setTeams(TournamentTeam $team1, TournamentTeam $team2 = null): void
    {
        $this->update([
            'team1_id' => $team1->id,
            'team2_id' => $team2?->id,
            'team1_seed' => $team1->seed,
            'team2_seed' => $team2?->seed,
        ]);

        if (!$team2) {
            $this->setBye();
        }
    }

    public function setBye(): void
    {
        $this->update([
            'status' => 'bye',
            'winner_team_id' => $this->team1_id,
        ]);

        $this->advanceWinner();
    }

    public function schedule(
        \DateTime $dateTime, 
        string $venue = null, 
        string $court = null
    ): void {
        $this->update([
            'scheduled_at' => $dateTime,
            'venue' => $venue ?? $this->tournament->primary_venue,
            'court' => $court,
            'status' => 'scheduled',
        ]);
    }

    public function start(): void
    {
        $this->update([
            'status' => 'in_progress',
            'actual_start_time' => now(),
        ]);
    }

    public function complete(
        int $team1Score, 
        int $team2Score, 
        array $scoreByPeriod = null,
        bool $overtime = false,
        int $overtimePeriods = 0
    ): void {
        $winnerId = $team1Score > $team2Score ? $this->team1_id : $this->team2_id;
        $loserId = $team1Score > $team2Score ? $this->team2_id : $this->team1_id;

        $this->update([
            'status' => 'completed',
            'team1_score' => $team1Score,
            'team2_score' => $team2Score,
            'score_by_period' => $scoreByPeriod,
            'overtime' => $overtime,
            'overtime_periods' => $overtimePeriods,
            'winner_team_id' => $winnerId,
            'loser_team_id' => $loserId,
            'actual_end_time' => now(),
            'actual_duration' => $this->actual_start_time ? 
                                $this->actual_start_time->diffInMinutes(now()) : null,
        ]);

        // Update team statistics
        $this->updateTeamStats();
        $this->advanceWinner();
        $this->advanceLoser();
    }

    public function forfeit(TournamentTeam $forfeitTeam, string $reason = null): void
    {
        $winnerId = $forfeitTeam->id === $this->team1_id ? $this->team2_id : $this->team1_id;

        $this->update([
            'status' => 'forfeit',
            'winner_team_id' => $winnerId,
            'loser_team_id' => $forfeitTeam->id,
            'forfeit_team_id' => $forfeitTeam->id,
            'forfeit_reason' => $reason,
        ]);

        $this->updateTeamStats();
        $this->advanceWinner();
        $this->advanceLoser();
    }

    protected function updateTeamStats(): void
    {
        if (!$this->is_completed && $this->status !== 'forfeit') return;

        $team1 = $this->team1;
        $team2 = $this->team2;

        if ($team1 && $team2) {
            if ($this->status === 'forfeit') {
                // Forfeit handling
                if ($this->winner_team_id === $team1->id) {
                    $team1->recordWin(0, 0); // Forfeit win
                    $team2->recordLoss(0, 0); // Forfeit loss
                } else {
                    $team2->recordWin(0, 0); // Forfeit win
                    $team1->recordLoss(0, 0); // Forfeit loss
                }
            } else {
                // Normal game completion
                if ($this->winner_team_id === $team1->id) {
                    $team1->recordWin($this->team1_score, $this->team2_score);
                    $team2->recordLoss($this->team2_score, $this->team1_score);
                } else {
                    $team2->recordWin($this->team2_score, $this->team1_score);
                    $team1->recordLoss($this->team1_score, $this->team2_score);
                }
            }
        }
    }

    protected function advanceWinner(): void
    {
        if ($this->winner_team_id && $this->winner_advances_to) {
            $nextBracket = $this->winnerAdvancesTo;
            
            if (!$nextBracket->team1_id) {
                $nextBracket->update(['team1_id' => $this->winner_team_id]);
            } elseif (!$nextBracket->team2_id) {
                $nextBracket->update(['team2_id' => $this->winner_team_id]);
            }

            // Check if next bracket is ready
            if ($nextBracket->has_both_teams) {
                // Can be scheduled
                $nextBracket->update(['status' => 'pending']);
            }
        }
    }

    protected function advanceLoser(): void
    {
        if ($this->loser_team_id && $this->loser_advances_to) {
            $nextBracket = $this->loserAdvancesTo;
            
            if (!$nextBracket->team1_id) {
                $nextBracket->update(['team1_id' => $this->loser_team_id]);
            } elseif (!$nextBracket->team2_id) {
                $nextBracket->update(['team2_id' => $this->loser_team_id]);
            }

            // Check if next bracket is ready
            if ($nextBracket->has_both_teams) {
                $nextBracket->update(['status' => 'pending']);
            }
        }
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