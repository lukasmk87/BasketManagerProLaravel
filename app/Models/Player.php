<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Player extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, LogsActivity, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'team_id',
        'jersey_number',
        'primary_position',
        'secondary_positions',
        'height_cm',
        'weight_kg',
        'dominant_hand',
        'shoe_size',
        'started_playing',
        'years_experience',
        'previous_teams',
        'achievements',
        'shooting_rating',
        'defense_rating',
        'passing_rating',
        'rebounding_rating',
        'speed_rating',
        'overall_rating',
        'games_played',
        'games_started',
        'minutes_played',
        'points_scored',
        'field_goals_made',
        'field_goals_attempted',
        'three_pointers_made',
        'three_pointers_attempted',
        'free_throws_made',
        'free_throws_attempted',
        'rebounds_offensive',
        'rebounds_defensive',
        'rebounds_total',
        'assists',
        'steals',
        'blocks',
        'turnovers',
        'fouls_personal',
        'fouls_technical',
        'status',
        'is_starter',
        'is_captain',
        'is_rookie',
        'contract_start',
        'contract_end',
        'registration_number',
        'is_registered',
        'registered_at',
        'medical_conditions',
        'allergies',
        'medications',
        'blood_type',
        'last_medical_check',
        'medical_clearance',
        'medical_clearance_expires',
        'emergency_medical_contact',
        'emergency_medical_phone',
        'preferred_hospital',
        'medical_notes',
        'insurance_provider',
        'insurance_policy_number',
        'insurance_expires',
        'parent_user_id',
        'guardian_contacts',
        'training_focus_areas',
        'development_goals',
        'coach_notes',
        'preferences',
        'dietary_restrictions',
        'school_name',
        'grade_level',
        'gpa',
        'academic_eligibility',
        'social_media',
        'allow_photos',
        'allow_media_interviews',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'uuid' => 'string',
        'jersey_number' => 'integer',
        'secondary_positions' => 'array',
        'height_cm' => 'integer',
        'weight_kg' => 'decimal:2',
        'started_playing' => 'date',
        'years_experience' => 'integer',
        'previous_teams' => 'array',
        'achievements' => 'array',
        'shooting_rating' => 'decimal:1',
        'defense_rating' => 'decimal:1',
        'passing_rating' => 'decimal:1',
        'rebounding_rating' => 'decimal:1',
        'speed_rating' => 'decimal:1',
        'overall_rating' => 'decimal:1',
        'games_played' => 'integer',
        'games_started' => 'integer',
        'minutes_played' => 'integer',
        'points_scored' => 'integer',
        'field_goals_made' => 'integer',
        'field_goals_attempted' => 'integer',
        'three_pointers_made' => 'integer',
        'three_pointers_attempted' => 'integer',
        'free_throws_made' => 'integer',
        'free_throws_attempted' => 'integer',
        'rebounds_offensive' => 'integer',
        'rebounds_defensive' => 'integer',
        'rebounds_total' => 'integer',
        'assists' => 'integer',
        'steals' => 'integer',
        'blocks' => 'integer',
        'turnovers' => 'integer',
        'fouls_personal' => 'integer',
        'fouls_technical' => 'integer',
        'is_starter' => 'boolean',
        'is_captain' => 'boolean',
        'is_rookie' => 'boolean',
        'contract_start' => 'date',
        'contract_end' => 'date',
        'is_registered' => 'boolean',
        'registered_at' => 'datetime',
        'medical_conditions' => 'array',
        'allergies' => 'array',
        'medications' => 'array',
        'last_medical_check' => 'date',
        'medical_clearance' => 'boolean',
        'medical_clearance_expires' => 'datetime',
        'insurance_expires' => 'datetime',
        'guardian_contacts' => 'array',
        'training_focus_areas' => 'array',
        'development_goals' => 'array',
        'preferences' => 'array',
        'dietary_restrictions' => 'array',
        'gpa' => 'decimal:2',
        'academic_eligibility' => 'boolean',
        'social_media' => 'array',
        'allow_photos' => 'boolean',
        'allow_media_interviews' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($player) {
            if (empty($player->uuid)) {
                $player->uuid = (string) Str::uuid();
            }
        });
    }

    // ============================
    // RELATIONSHIPS
    // ============================

    /**
     * Get the user that owns this player profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team this player belongs to.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
    }

    /**
     * Get the parent/guardian user (for minors).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    /**
     * Get all game actions performed by this player.
     */
    public function gameActions(): HasMany
    {
        return $this->hasMany(GameAction::class);
    }

    // ============================
    // SCOPES
    // ============================

    /**
     * Scope a query to only include active players.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include starters.
     */
    public function scopeStarters($query)
    {
        return $query->where('is_starter', true);
    }

    /**
     * Scope a query to only include captains.
     */
    public function scopeCaptains($query)
    {
        return $query->where('is_captain', true);
    }

    /**
     * Scope a query to filter by position.
     */
    public function scopeByPosition($query, string $position)
    {
        return $query->where('primary_position', $position);
    }

    /**
     * Scope a query to filter by medical clearance.
     */
    public function scopeMedicallyCleared($query)
    {
        return $query->where('medical_clearance', true)
            ->where(function ($q) {
                $q->whereNull('medical_clearance_expires')
                  ->orWhere('medical_clearance_expires', '>', now());
            });
    }

    // ============================
    // ACCESSORS & MUTATORS
    // ============================

    /**
     * Get the player's full name.
     */
    public function getFullNameAttribute(): string
    {
        return $this->user->name;
    }

    /**
     * Get the player's display name with jersey number.
     */
    public function getDisplayNameAttribute(): string
    {
        $number = $this->jersey_number ? "#{$this->jersey_number}" : '';
        return trim("{$number} {$this->user->name}");
    }

    /**
     * Get the player's height in feet and inches.
     */
    public function getHeightFeetAttribute(): ?string
    {
        if (!$this->height_cm) {
            return null;
        }

        $inches = $this->height_cm / 2.54;
        $feet = floor($inches / 12);
        $remainingInches = round($inches % 12);

        return "{$feet}' {$remainingInches}\"";
    }

    /**
     * Get the player's field goal percentage.
     */
    public function getFieldGoalPercentageAttribute(): float
    {
        if ($this->field_goals_attempted === 0) {
            return 0.0;
        }

        return round(($this->field_goals_made / $this->field_goals_attempted) * 100, 1);
    }

    /**
     * Get the player's three-point percentage.
     */
    public function getThreePointPercentageAttribute(): float
    {
        if ($this->three_pointers_attempted === 0) {
            return 0.0;
        }

        return round(($this->three_pointers_made / $this->three_pointers_attempted) * 100, 1);
    }

    /**
     * Get the player's free throw percentage.
     */
    public function getFreeThrowPercentageAttribute(): float
    {
        if ($this->free_throws_attempted === 0) {
            return 0.0;
        }

        return round(($this->free_throws_made / $this->free_throws_attempted) * 100, 1);
    }

    /**
     * Get the player's points per game average.
     */
    public function getPointsPerGameAttribute(): float
    {
        if ($this->games_played === 0) {
            return 0.0;
        }

        return round($this->points_scored / $this->games_played, 1);
    }

    /**
     * Get the player's rebounds per game average.
     */
    public function getReboundsPerGameAttribute(): float
    {
        if ($this->games_played === 0) {
            return 0.0;
        }

        return round($this->rebounds_total / $this->games_played, 1);
    }

    /**
     * Get the player's assists per game average.
     */
    public function getAssistsPerGameAttribute(): float
    {
        if ($this->games_played === 0) {
            return 0.0;
        }

        return round($this->assists / $this->games_played, 1);
    }

    /**
     * Get the player's age.
     */
    public function getAgeAttribute(): ?int
    {
        return $this->user->birth_date ? 
            $this->user->birth_date->diffInYears(now()) : null;
    }

    /**
     * Get all positions (primary + secondary).
     */
    public function getAllPositionsAttribute(): array
    {
        $positions = $this->secondary_positions ?? [];
        if ($this->primary_position) {
            array_unshift($positions, $this->primary_position);
        }
        return array_unique($positions);
    }

    /**
     * Check if medical clearance is expired.
     */
    public function getMedicalClearanceExpiredAttribute(): bool
    {
        return $this->medical_clearance_expires && 
               $this->medical_clearance_expires->isPast();
    }

    /**
     * Check if insurance is expired.
     */
    public function getInsuranceExpiredAttribute(): bool
    {
        return $this->insurance_expires && 
               $this->insurance_expires->isPast();
    }

    // ============================
    // HELPER METHODS
    // ============================

    /**
     * Get comprehensive player statistics.
     */
    public function getStatistics(): array
    {
        return [
            'games_played' => $this->games_played,
            'games_started' => $this->games_started,
            'minutes_played' => $this->minutes_played,
            'points_per_game' => $this->points_per_game,
            'rebounds_per_game' => $this->rebounds_per_game,
            'assists_per_game' => $this->assists_per_game,
            'field_goal_percentage' => $this->field_goal_percentage,
            'three_point_percentage' => $this->three_point_percentage,
            'free_throw_percentage' => $this->free_throw_percentage,
            'total_points' => $this->points_scored,
            'total_rebounds' => $this->rebounds_total,
            'total_assists' => $this->assists,
            'steals' => $this->steals,
            'blocks' => $this->blocks,
            'turnovers' => $this->turnovers,
            'personal_fouls' => $this->fouls_personal,
            'technical_fouls' => $this->fouls_technical,
        ];
    }

    /**
     * Update player statistics after a game.
     */
    public function updateStatistics(array $gameStats): void
    {
        $statsToUpdate = [
            'games_played' => ($gameStats['minutes_played'] ?? 0) > 0 ? 1 : 0,
            'games_started' => $gameStats['started'] ?? 0,
            'minutes_played' => $gameStats['minutes_played'] ?? 0,
            'points_scored' => $gameStats['points'] ?? 0,
            'field_goals_made' => $gameStats['field_goals_made'] ?? 0,
            'field_goals_attempted' => $gameStats['field_goals_attempted'] ?? 0,
            'three_pointers_made' => $gameStats['three_pointers_made'] ?? 0,
            'three_pointers_attempted' => $gameStats['three_pointers_attempted'] ?? 0,
            'free_throws_made' => $gameStats['free_throws_made'] ?? 0,
            'free_throws_attempted' => $gameStats['free_throws_attempted'] ?? 0,
            'rebounds_offensive' => $gameStats['rebounds_offensive'] ?? 0,
            'rebounds_defensive' => $gameStats['rebounds_defensive'] ?? 0,
            'rebounds_total' => ($gameStats['rebounds_offensive'] ?? 0) + ($gameStats['rebounds_defensive'] ?? 0),
            'assists' => $gameStats['assists'] ?? 0,
            'steals' => $gameStats['steals'] ?? 0,
            'blocks' => $gameStats['blocks'] ?? 0,
            'turnovers' => $gameStats['turnovers'] ?? 0,
            'fouls_personal' => $gameStats['fouls_personal'] ?? 0,
            'fouls_technical' => $gameStats['fouls_technical'] ?? 0,
        ];

        foreach ($statsToUpdate as $stat => $value) {
            $this->increment($stat, $value);
        }
    }

    /**
     * Check if player can play (medical clearance, etc.).
     */
    public function canPlay(): bool
    {
        return $this->status === 'active' && 
               $this->medical_clearance && 
               !$this->medical_clearance_expired &&
               $this->academic_eligibility;
    }

    /**
     * Check if player is a minor.
     */
    public function isMinor(): bool
    {
        return $this->age && $this->age < 18;
    }

    /**
     * Get emergency contact information.
     */
    public function getEmergencyContacts(): array
    {
        $contacts = [];

        // Primary emergency contact
        if ($this->emergency_medical_contact) {
            $contacts[] = [
                'name' => $this->emergency_medical_contact,
                'phone' => $this->emergency_medical_phone,
                'relationship' => 'Emergency Contact',
                'priority' => 1,
            ];
        }

        // Parent/guardian contacts
        if ($this->parent) {
            $contacts[] = [
                'name' => $this->parent->name,
                'phone' => $this->parent->phone,
                'relationship' => 'Parent/Guardian',
                'priority' => 2,
            ];
        }

        // Guardian contacts from JSON
        if ($this->guardian_contacts) {
            foreach ($this->guardian_contacts as $index => $contact) {
                $contacts[] = array_merge($contact, ['priority' => 3 + $index]);
            }
        }

        return collect($contacts)->sortBy('priority')->values()->toArray();
    }

    // ============================
    // MEDIA LIBRARY
    // ============================

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile_photos')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png']);

        $this->addMediaCollection('medical_documents')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png']);

        $this->addMediaCollection('game_photos')
            ->acceptsMimeTypes(['image/jpeg', 'image/png']);
    }

    /**
     * Register media conversions.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->sharpen(10);

        $this->addMediaConversion('profile-medium')
            ->width(400)
            ->height(400)
            ->performOnCollections('profile_photos');
    }

    // ============================
    // ACTIVITY LOG
    // ============================

    /**
     * Get the activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'jersey_number', 'primary_position', 'status',
                'is_starter', 'is_captain', 'team_id'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}