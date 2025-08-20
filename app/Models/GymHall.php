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

class GymHall extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, LogsActivity, InteractsWithMedia;

    protected $fillable = [
        'uuid',
        'club_id',
        'name',
        'slug',
        'description',
        'address_street',
        'address_city',
        'address_zip',
        'address_country',
        'latitude',
        'longitude',
        'capacity',
        'facilities',
        'equipment',
        'opening_time',
        'closing_time',
        'operating_hours',
        'hourly_rate',
        'contact_name',
        'contact_phone',
        'contact_email',
        'is_active',
        'requires_key',
        'access_instructions',
        'special_rules',
        'metadata',
    ];

    protected $casts = [
        'uuid' => 'string',
        'capacity' => 'integer',
        'facilities' => 'array',
        'equipment' => 'array',
        'opening_time' => 'datetime:H:i',
        'closing_time' => 'datetime:H:i',
        'operating_hours' => 'array',
        'hourly_rate' => 'decimal:2',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_active' => 'boolean',
        'requires_key' => 'boolean',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($gymHall) {
            if (empty($gymHall->uuid)) {
                $gymHall->uuid = (string) Str::uuid();
            }
            if (empty($gymHall->slug) && !empty($gymHall->name)) {
                $gymHall->slug = Str::slug($gymHall->name);
            }
        });
    }

    // ============================
    // RELATIONSHIPS
    // ============================

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function timeSlots(): HasMany
    {
        return $this->hasMany(GymTimeSlot::class);
    }

    public function activeTimeSlots(): HasMany
    {
        return $this->timeSlots()->where('status', 'active');
    }

    public function bookings()
    {
        return $this->hasManyThrough(
            GymBooking::class,
            GymTimeSlot::class,
            'gym_hall_id',
            'gym_time_slot_id',
            'id',
            'id'
        );
    }

    // ============================
    // SCOPES
    // ============================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInClub($query, $clubId)
    {
        return $query->where('club_id', $clubId);
    }

    public function scopeWithCapacityMin($query, $minCapacity)
    {
        return $query->where('capacity', '>=', $minCapacity);
    }

    // ============================
    // ACCESSORS & MUTATORS
    // ============================

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address_street,
            $this->address_zip . ' ' . $this->address_city,
            $this->address_country,
        ]);

        return implode(', ', $parts);
    }

    public function getIsOpenAttribute(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        $currentDay = strtolower($now->format('l'));
        
        if ($this->operating_hours && isset($this->operating_hours[$currentDay])) {
            $hours = $this->operating_hours[$currentDay];
            if (!$hours['is_open']) {
                return false;
            }

            $openTime = \Carbon\Carbon::createFromTimeString($hours['open_time']);
            $closeTime = \Carbon\Carbon::createFromTimeString($hours['close_time']);
            
            return $now->between($openTime, $closeTime);
        }

        return false;
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    // ============================
    // HELPER METHODS
    // ============================

    public function isAvailableAt(\Carbon\Carbon $dateTime, int $durationMinutes = 120): bool
    {
        $endTime = $dateTime->copy()->addMinutes($durationMinutes);
        
        $conflictingBookings = $this->bookings()
            ->whereDate('booking_date', $dateTime->toDateString())
            ->where(function ($query) use ($dateTime, $endTime) {
                $query->where(function ($q) use ($dateTime, $endTime) {
                    // Booking starts before our end time and ends after our start time
                    $q->where('start_time', '<', $endTime->format('H:i:s'))
                      ->where('end_time', '>', $dateTime->format('H:i:s'));
                });
            })
            ->whereIn('status', ['reserved', 'confirmed'])
            ->exists();

        return !$conflictingBookings;
    }

    public function getAvailableTimeSlots(\Carbon\Carbon $date): array
    {
        $dayOfWeek = strtolower($date->format('l'));
        
        return $this->timeSlots()
            ->where('day_of_week', $dayOfWeek)
            ->where('status', 'active')
            ->where('valid_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('valid_until')
                      ->orWhere('valid_until', '>=', $date);
            })
            ->get()
            ->filter(function ($slot) use ($date) {
                // Check if date is excluded
                if ($slot->excluded_dates && in_array($date->toDateString(), $slot->excluded_dates)) {
                    return false;
                }
                
                return true;
            })
            ->toArray();
    }

    public function getTodaysSchedule(): array
    {
        return $this->getAvailableTimeSlots(now());
    }

    public function getWeeklySchedule(\Carbon\Carbon $startOfWeek = null): array
    {
        if (!$startOfWeek) {
            $startOfWeek = now()->startOfWeek();
        }

        $schedule = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $schedule[$date->format('Y-m-d')] = [
                'date' => $date,
                'day_name' => $date->format('l'),
                'slots' => $this->getAvailableTimeSlots($date)
            ];
        }

        return $schedule;
    }

    public function getUtilizationRate(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): float
    {
        $totalSlots = $this->timeSlots()
            ->where('status', 'active')
            ->count();

        if ($totalSlots === 0) {
            return 0.0;
        }

        $bookedSlots = $this->bookings()
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->whereIn('status', ['reserved', 'confirmed', 'completed'])
            ->count();

        return round(($bookedSlots / $totalSlots) * 100, 1);
    }

    // ============================
    // MEDIA LIBRARY
    // ============================

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png']);

        $this->addMediaCollection('documents')
            ->acceptsMimeTypes(['application/pdf']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(200)
            ->sharpen(10);
    }

    // ============================
    // ACTIVITY LOG
    // ============================

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'is_active', 'capacity', 'hourly_rate',
                'opening_time', 'closing_time'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // ============================
    // ROUTE MODEL BINDING
    // ============================

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}