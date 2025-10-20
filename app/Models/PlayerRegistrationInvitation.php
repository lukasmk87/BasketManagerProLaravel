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

class PlayerRegistrationInvitation extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'uuid',
        'invitation_token',
        'club_id',
        'created_by_user_id',
        'target_team_id',
        'qr_code_path',
        'qr_code_metadata',
        'expires_at',
        'max_registrations',
        'current_registrations',
        'is_active',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'uuid' => 'string',
        'invitation_token' => 'string',
        'expires_at' => 'datetime',
        'max_registrations' => 'integer',
        'current_registrations' => 'integer',
        'is_active' => 'boolean',
        'qr_code_metadata' => 'array',
        'settings' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invitation) {
            if (empty($invitation->uuid)) {
                $invitation->uuid = (string) Str::uuid();
            }
            if (empty($invitation->invitation_token)) {
                $invitation->invitation_token = bin2hex(random_bytes(16)); // 32 characters
            }
        });
    }

    // ============================
    // RELATIONSHIPS
    // ============================

    /**
     * Get the club that owns this invitation.
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Get the user who created this invitation.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the suggested target team (optional).
     */
    public function targetTeam(): BelongsTo
    {
        return $this->belongsTo(BasketballTeam::class, 'target_team_id');
    }

    /**
     * Get all players registered via this invitation.
     */
    public function registeredPlayers(): HasMany
    {
        return $this->hasMany(Player::class, 'registered_via_invitation_id');
    }

    // ============================
    // SCOPES
    // ============================

    /**
     * Scope a query to only include active invitations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('expires_at', '>', now());
    }

    /**
     * Scope a query to only include expired invitations.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
            ->orWhere('is_active', false);
    }

    /**
     * Scope a query to filter by club.
     */
    public function scopeByClub($query, int $clubId)
    {
        return $query->where('club_id', $clubId);
    }

    /**
     * Scope a query to filter by creator.
     */
    public function scopeByCreator($query, int $userId)
    {
        return $query->where('created_by_user_id', $userId);
    }

    /**
     * Scope a query to only include invitations that haven't reached their limit.
     */
    public function scopeAvailable($query)
    {
        return $query->whereColumn('current_registrations', '<', 'max_registrations');
    }

    // ============================
    // ACCESSORS & MUTATORS
    // ============================

    /**
     * Get the full registration URL.
     */
    public function getRegistrationUrlAttribute(): string
    {
        return route('public.player.register', ['token' => $this->invitation_token]);
    }

    /**
     * Get days until expiration.
     */
    public function getDaysUntilExpiryAttribute(): int
    {
        if ($this->expires_at->isPast()) {
            return 0;
        }
        return (int) now()->diffInDays($this->expires_at, false);
    }

    /**
     * Check if invitation is expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at->isPast() || !$this->is_active;
    }

    /**
     * Check if registration limit is reached.
     */
    public function getHasReachedLimitAttribute(): bool
    {
        return $this->current_registrations >= $this->max_registrations;
    }

    /**
     * Get remaining registrations.
     */
    public function getRemainingRegistrationsAttribute(): int
    {
        return max(0, $this->max_registrations - $this->current_registrations);
    }

    /**
     * Get registration progress percentage.
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->max_registrations === 0) {
            return 0.0;
        }
        return round(($this->current_registrations / $this->max_registrations) * 100, 1);
    }

    /**
     * Check if invitation can be used.
     */
    public function getIsUsableAttribute(): bool
    {
        return $this->is_active
            && !$this->is_expired
            && !$this->has_reached_limit;
    }

    // ============================
    // HELPER METHODS
    // ============================

    /**
     * Increment the registration count.
     */
    public function incrementRegistrations(): void
    {
        $this->increment('current_registrations');
    }

    /**
     * Deactivate this invitation.
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Reactivate this invitation if not expired.
     */
    public function activate(): bool
    {
        if ($this->expires_at->isPast()) {
            return false;
        }
        return $this->update(['is_active' => true]);
    }

    /**
     * Extend the expiration date.
     */
    public function extend(int $days): bool
    {
        $newExpiry = $this->expires_at->addDays($days);
        return $this->update(['expires_at' => $newExpiry]);
    }

    /**
     * Get statistics for this invitation.
     */
    public function getStatistics(): array
    {
        return [
            'total_registrations' => $this->current_registrations,
            'max_registrations' => $this->max_registrations,
            'remaining' => $this->remaining_registrations,
            'progress_percentage' => $this->progress_percentage,
            'days_until_expiry' => $this->days_until_expiry,
            'is_active' => $this->is_active,
            'is_expired' => $this->is_expired,
            'is_usable' => $this->is_usable,
            'created_at' => $this->created_at->toISOString(),
            'expires_at' => $this->expires_at->toISOString(),
        ];
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
                'club_id',
                'target_team_id',
                'is_active',
                'expires_at',
                'max_registrations',
                'current_registrations',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
