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
        'sort_order',
        'hourly_rate',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'uuid' => 'string',
        'is_active' => 'boolean',
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
    // ACTIVITY LOG
    // ============================

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'court_number', 'is_active', 'hourly_rate'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
