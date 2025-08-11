<?php

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
use Laravel\Scout\Searchable;

class Tournament extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia, LogsActivity, Searchable;

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
                        'status', 'status_reason', 'status_updated_at', 'seed',
                        'group_name', 'group_position', 'games_played', 'wins',
                        'losses', 'draws', 'points_for', 'points_against',
                        'tournament_points', 'point_differential', 'final_position',
                        'elimination_round', 'eliminated_at', 'entry_fee_paid',
                        'payment_date', 'payment_method', 'prize_money',
                        'contact_person', 'contact_email', 'contact_phone',
                        'special_requirements', 'travel_information', 'roster_players',
                        'emergency_contacts', 'medical_forms_complete',
                        'insurance_verified', 'individual_awards', 'team_awards'
                    ])
                    ->withTimestamps();
    }

    public function tournamentTeams(): HasMany
    {
        return $this->hasMany(TournamentTeam::class);
    }

    public function brackets(): HasMany
    {
        return $this->hasMany(TournamentBracket::class);
    }

    public function games(): HasMany
    {
        return $this->hasMany(TournamentGame::class);
    }

    public function officials(): HasMany
    {
        return $this->hasMany(TournamentOfficial::class);
    }

    public function awards(): HasMany
    {
        return $this->hasMany(TournamentAward::class);
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByGender($query, string $gender)
    {
        return $query->where('gender', $gender);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
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

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
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

    public function canRegister(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->is_registration_open && 
                        $this->registered_teams < $this->max_teams,
        );
    }

    public function isUpcoming(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->start_date > now(),
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

    public function duration(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->start_date->diffInDays($this->end_date) + 1,
        );
    }

    public function durationText(): Attribute
    {
        return Attribute::make(
            get: function () {
                $days = $this->duration;
                if ($days === 1) {
                    return '1 Tag';
                } else {
                    return "{$days} Tage";
                }
            },
        );
    }

    public function registrationProgress(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->max_teams > 0 
                        ? ($this->registered_teams / $this->max_teams) * 100 
                        : 0,
        );
    }

    public function completionProgress(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->total_games > 0 
                        ? ($this->completed_games / $this->total_games) * 100 
                        : 0,
        );
    }

    public function typeDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match($this->type) {
                    'single_elimination' => 'Einfach-K.O.',
                    'double_elimination' => 'Doppel-K.O.',
                    'round_robin' => 'Jeder gegen Jeden',
                    'swiss_system' => 'Schweizer System',
                    'group_stage_knockout' => 'Gruppenphase + K.O.',
                    'ladder' => 'Ladder-System',
                    default => $this->type,
                };
            },
        );
    }

    public function categoryDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match($this->category) {
                    'adult' => 'Erwachsene',
                    'mixed' => 'Gemischt',
                    default => $this->category,
                };
            },
        );
    }

    public function genderDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match($this->gender) {
                    'male' => 'Herren',
                    'female' => 'Damen',
                    'mixed' => 'Mixed',
                    default => $this->gender,
                };
            },
        );
    }

    public function statusDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match($this->status) {
                    'draft' => 'Entwurf',
                    'registration_open' => 'Anmeldung offen',
                    'registration_closed' => 'Anmeldung geschlossen',
                    'in_progress' => 'Läuft',
                    'completed' => 'Abgeschlossen',
                    'cancelled' => 'Abgesagt',
                    default => $this->status,
                };
            },
        );
    }

    // Business Logic Methods
    public function generateBrackets(): bool
    {
        // Will be implemented in TournamentService
        return app(TournamentService::class)->generateBrackets($this);
    }

    public function canGenerateBrackets(): bool
    {
        return $this->registered_teams >= $this->min_teams &&
               $this->brackets()->count() === 0 &&
               in_array($this->status, ['registration_closed', 'in_progress']);
    }

    public function canStart(): bool
    {
        return $this->status === 'registration_closed' &&
               $this->registered_teams >= $this->min_teams &&
               $this->brackets()->exists();
    }

    public function canComplete(): bool
    {
        return $this->status === 'in_progress' &&
               $this->completion_progress >= 100;
    }

    public function getChampion()
    {
        return $this->tournamentTeams()
                   ->where('final_position', 1)
                   ->first();
    }

    public function getRunnerUp()
    {
        return $this->tournamentTeams()
                   ->where('final_position', 2)
                   ->first();
    }

    public function getTopTeams(int $limit = 3)
    {
        return $this->tournamentTeams()
                   ->whereNotNull('final_position')
                   ->orderBy('final_position')
                   ->limit($limit)
                   ->get();
    }

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
                         ->logFillable()
                         ->logOnlyDirty()
                         ->dontSubmitEmptyLogs();
    }

    // Scout Search
    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'gender' => $this->gender,
            'type' => $this->type,
            'primary_venue' => $this->primary_venue,
            'venue_address' => $this->venue_address,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->is_public;
    }
}