<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\HasLocalePreference;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory; 
    use HasLocalePreference;
    use HasProfilePhoto;
    use HasRoles;
    use HasTeams;
    use LogsActivity;
    use Notifiable;
    use SoftDeletes;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'birth_date',
        'gender',
        'bio',
        'timezone',
        'language',
        'avatar_path',
        'preferences',
        'notification_settings',
        'player_profile_active',
        'coaching_certifications',
        'last_login_at',
        'last_login_ip',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'preferences' => 'array',
            'notification_settings' => 'array',
            'coaching_certifications' => 'array',
            'player_profile_active' => 'boolean',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    // ============================
    // RELATIONSHIPS
    // ============================

    /**
     * Get the player profile associated with the user.
     */
    public function playerProfile(): HasOne
    {
        return $this->hasOne(Player::class);
    }

    /**
     * Get the teams coached by this user as head coach.
     */
    public function coachedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'head_coach_id');
    }

    /**
     * Get the teams coached by this user as assistant coach.
     */
    public function assistantCoachedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'assistant_coach_id');
    }

    /**
     * Get all teams coached by this user (head + assistant).
     */
    public function allCoachedTeams(): HasMany
    {
        return $this->coachedTeams()->union($this->assistantCoachedTeams());
    }

    /**
     * Get the social accounts linked to this user.
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * Get the clubs this user is a member of.
     */
    public function clubs(): BelongsToMany
    {
        return $this->belongsToMany(Club::class, 'club_members')
                    ->withPivot('role', 'joined_at', 'is_active')
                    ->withTimestamps();
    }

    // ============================
    // SCOPES
    // ============================

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include coaches.
     */
    public function scopeCoaches($query)
    {
        return $query->whereHas('roles', function ($q) {
            $q->whereIn('name', ['trainer', 'club_admin', 'admin']);
        });
    }

    /**
     * Scope a query to only include players.
     */
    public function scopePlayers($query)
    {
        return $query->where('player_profile_active', true)
                    ->whereHas('playerProfile');
    }

    /**
     * Scope a query by locale preference.
     */
    public function scopeByLocale($query, string $locale)
    {
        return $query->where('language', $locale);
    }

    // ============================
    // ACCESSORS & MUTATORS
    // ============================

    /**
     * Get the user's age.
     */
    public function age(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->birth_date?->age
        );
    }

    /**
     * Get the user's full name.
     */
    public function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->name
        );
    }

    /**
     * Get the user's avatar URL with fallback.
     */
    public function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->avatar_path 
                ? asset('storage/' . $this->avatar_path)
                : 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF'
        );
    }

    // ============================
    // HELPER METHODS
    // ============================

    /**
     * Check if the user is a coach.
     */
    public function isCoach(): bool
    {
        return $this->hasAnyRole(['trainer', 'club_admin', 'admin']);
    }

    /**
     * Check if the user is a player.
     */
    public function isPlayer(): bool
    {
        return $this->player_profile_active && $this->playerProfile()->exists();
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if the user is a club admin.
     */
    public function isClubAdmin(): bool
    {
        return $this->hasRole('club_admin');
    }

    /**
     * Check if the user has access to a specific team.
     */
    public function hasTeamAccess(Team $team, array $permissions = []): bool
    {
        // Admin has access to everything
        if ($this->hasRole('admin')) {
            return true;
        }

        // Club admin has access to all teams in their clubs
        if ($this->hasRole('club_admin')) {
            return $this->clubs()->where('clubs.id', $team->club_id)->exists();
        }

        // Coach has access to their teams
        if ($team->head_coach_id === $this->id || $team->assistant_coach_id === $this->id) {
            return true;
        }

        // Player has access to their own team
        if ($this->playerProfile && $this->playerProfile->team_id === $team->id) {
            return true;
        }

        return false;
    }

    /**
     * Check if the user can coach a specific team.
     */
    public function canCoachTeam(Team $team): bool
    {
        return $this->isCoach() && $this->hasTeamAccess($team);
    }

    /**
     * Get the user's primary team (as player or coach).
     */
    public function getPrimaryTeam(): ?Team
    {
        // If user is a player, return their team
        if ($this->isPlayer()) {
            return $this->playerProfile->team;
        }

        // If user is a coach, return their first coached team
        if ($this->isCoach()) {
            return $this->coachedTeams()->first() ?? $this->assistantCoachedTeams()->first();
        }

        return null;
    }

    /**
     * Get basketball-specific user statistics.
     */
    public function getBasketballStats(): array
    {
        $stats = [
            'is_player' => $this->isPlayer(),
            'is_coach' => $this->isCoach(),
            'teams_count' => 0,
            'coached_teams_count' => 0,
            'seasons_active' => [],
        ];

        if ($this->isPlayer() && $this->playerProfile) {
            $stats['current_team'] = $this->playerProfile->team?->name;
            $stats['position'] = $this->playerProfile->position;
            $stats['jersey_number'] = $this->playerProfile->jersey_number;
        }

        if ($this->isCoach()) {
            $stats['coached_teams_count'] = $this->coachedTeams()->count() + $this->assistantCoachedTeams()->count();
        }

        return $stats;
    }

    /**
     * Update user's last login information.
     */
    public function updateLastLogin(string $ipAddress): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress,
        ]);
    }

    /**
     * Check if user has all required consents (for minors).
     */
    public function hasRequiredConsents(): bool
    {
        if (!$this->isPlayer() || !$this->playerProfile) {
            return true;
        }

        return $this->playerProfile->hasValidConsents();
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
            ->logOnly(['name', 'email', 'is_active', 'language', 'player_profile_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
