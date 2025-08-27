<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class GymCourt extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'uuid',
        'gym_hall_id',
        'name',
        'court_number',
        'is_active',
        'is_main_court',
        'sort_order',
        'hourly_rate',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'uuid' => 'string',
        'is_active' => 'boolean',
        'is_main_court' => 'boolean',
        'sort_order' => 'integer',
        'hourly_rate' => 'decimal:2',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($court) {
            if (empty($court->uuid)) {
                $court->uuid = (string) Str::uuid();
            }
        });
    }

    // ============================
    // RELATIONSHIPS
    // ============================

    public function gymHall(): BelongsTo
    {
        return $this->belongsTo(GymHall::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(GymBooking::class);
    }

    // ============================
    // SCOPES
    // ============================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForHall($query, $hallId)
    {
        return $query->where('gym_hall_id', $hallId);
    }

    public function scopeOrderedByNumber($query)
    {
        return $query->orderBy('court_number');
    }

    public function scopeOrderedBySortOrder($query)
    {
        return $query->orderBy('sort_order');
    }

    // ============================
    // HELPER METHODS
    // ============================

    public function getFullNameAttribute(): string
    {
        return $this->name ?: "Platz {$this->court_number}";
    }

    public function getEffectiveHourlyRateAttribute(): float
    {
        return $this->hourly_rate ?: $this->gymHall->hourly_rate ?: 0;
    }

    public function getCourtIdentifierAttribute(): string
    {
        return $this->metadata['identifier'] ?? $this->court_number;
    }

    public function getColorCodeAttribute(): string
    {
        return $this->metadata['color_code'] ?? '#3B82F6';
    }

    public function getCourtTypeAttribute(): string
    {
        return $this->metadata['court_type'] ?? 'full';
    }

    public function getMaxCapacityAttribute(): ?int
    {
        return $this->metadata['max_capacity'] ?? null;
    }

    public function isAvailableAt(\Carbon\Carbon $dateTime, int $durationMinutes = 30): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $endTime = $dateTime->copy()->addMinutes($durationMinutes);

        // Check for overlapping bookings
        $overlappingBookings = $this->bookings()
            ->where('status', 'confirmed')
            ->where(function ($query) use ($dateTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $dateTime);
            })
            ->exists();

        return !$overlappingBookings;
    }

    // ============================
    // MAIN COURT METHODS
    // ============================

    /**
     * Set this court as the main court for the hall.
     * This will automatically unset any other main court in the same hall.
     */
    public function setAsMainCourt(): bool
    {
        // First, unset any existing main court in this hall
        self::where('gym_hall_id', $this->gym_hall_id)
            ->where('id', '!=', $this->id)
            ->update(['is_main_court' => false]);

        // Set this court as main court
        return $this->update(['is_main_court' => true]);
    }

    /**
     * Unset this court as main court.
     */
    public function unsetAsMainCourt(): bool
    {
        return $this->update(['is_main_court' => false]);
    }

    /**
     * Check if this court has any active bookings during the specified time.
     */
    public function hasBookingDuringTime(string $dayOfWeek, string $startTime, string $endTime): bool
    {
        return $this->teamAssignments()
            ->where('day_of_week', $dayOfWeek)
            ->where('status', 'active')
            ->where(function($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime);
            })
            ->exists();
    }

    // ============================
    // SCOPES
    // ============================

    /**
     * Scope to get only main courts.
     */
    public function scopeMainCourt($query)
    {
        return $query->where('is_main_court', true);
    }

    /**
     * Scope to get regular courts (not main courts).
     */
    public function scopeRegularCourt($query)
    {
        return $query->where('is_main_court', false);
    }

    // ============================
    // ACTIVITY LOG
    // ============================

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'court_number', 'is_active', 'is_main_court', 'hourly_rate'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
