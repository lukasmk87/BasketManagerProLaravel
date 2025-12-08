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
        'hall_number',
        'slug',
        'hall_type',
        'court_count',
        'court_configuration',
        'supports_parallel_bookings',
        'min_booking_duration_minutes',
        'booking_increment_minutes',
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
        'court_count' => 'integer',
        'court_configuration' => 'array',
        'supports_parallel_bookings' => 'boolean',
        'min_booking_duration_minutes' => 'integer',
        'booking_increment_minutes' => 'integer',
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

    public function courts(): HasMany
    {
        return $this->hasMany(GymCourt::class);
    }

    public function activeCourts(): HasMany
    {
        return $this->courts()->where('is_active', true);
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

    public function scopeSupportsParallelBookings($query)
    {
        return $query->where('supports_parallel_bookings', true);
    }

    public function scopeOfType($query, $hallType)
    {
        return $query->where('hall_type', $hallType);
    }

    public function scopeWithMinCourts($query, $minCourts)
    {
        return $query->where('court_count', '>=', $minCourts);
    }

    // ============================
    // ACCESSORS & MUTATORS
    // ============================

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

    public function getIsMultiCourtAttribute(): bool
    {
        return $this->court_count > 1;
    }

    public function getSupportsFlexibleBookingsAttribute(): bool
    {
        return $this->supports_parallel_bookings && $this->is_multi_court;
    }

    /**
     * Check if parallel bookings are supported for a specific day.
     */
    public function supportsParallelBookingsForDay(string $dayOfWeek): bool
    {
        // Convert day name to lowercase for consistency
        $dayOfWeek = strtolower($dayOfWeek);
        
        // If operating_hours has day-specific parallel booking settings, use those
        if ($this->operating_hours && isset($this->operating_hours[$dayOfWeek])) {
            $daySettings = $this->operating_hours[$dayOfWeek];
            
            // Check if parallel bookings are explicitly set for this day
            if (isset($daySettings['supports_parallel_bookings'])) {
                return $daySettings['supports_parallel_bookings'];
            }
        }
        
        // Fall back to global setting
        return $this->supports_parallel_bookings;
    }

    /**
     * Remove day-specific parallel booking restrictions and use global setting.
     */
    public function normalizeOperatingHoursParallelBookings(): bool
    {
        if (!$this->operating_hours) {
            return true; // Nothing to normalize
        }

        $operatingHours = $this->operating_hours;
        $wasModified = false;

        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
            if (isset($operatingHours[$day]['supports_parallel_bookings'])) {
                unset($operatingHours[$day]['supports_parallel_bookings']);
                $wasModified = true;
            }
        }

        if ($wasModified) {
            $this->operating_hours = $operatingHours;
            return $this->save();
        }

        return true;
    }

    /**
     * Set parallel booking support for a specific day.
     */
    public function setParallelBookingsForDay(string $dayOfWeek, bool $enabled): bool
    {
        $dayOfWeek = strtolower($dayOfWeek);
        $operatingHours = $this->operating_hours ?: [];

        if (!isset($operatingHours[$dayOfWeek])) {
            $operatingHours[$dayOfWeek] = [];
        }

        $operatingHours[$dayOfWeek]['supports_parallel_bookings'] = $enabled;
        $this->operating_hours = $operatingHours;

        return $this->save();
    }

    /**
     * Get maximum number of parallel teams for a specific day.
     */
    public function getMaxParallelTeamsForDay(string $dayOfWeek): int
    {
        if (!$this->supportsParallelBookingsForDay($dayOfWeek)) {
            return 1;
        }
        
        // With parallel bookings enabled, maximum teams equals number of courts
        return max(1, $this->court_count);
    }

    /**
     * Get the main court for this hall.
     */
    public function getMainCourt(): ?GymCourt
    {
        return $this->courts()->mainCourt()->first();
    }

    /**
     * Check if the main court has any bookings during the specified time.
     */
    public function hasMainCourtBooking(string $dayOfWeek, string $startTime, string $endTime): bool
    {
        $mainCourt = $this->getMainCourt();
        
        if (!$mainCourt) {
            return false;
        }

        return $mainCourt->hasBookingDuringTime($dayOfWeek, $startTime, $endTime);
    }

    /**
     * Get effective maximum parallel teams considering main court status.
     */
    public function getEffectiveMaxParallelTeams(string $dayOfWeek, string $startTime, string $endTime): int
    {
        $baseMax = $this->getMaxParallelTeamsForDay($dayOfWeek);
        
        // If main court is booked during this time, only allow 1 team total
        if ($this->hasMainCourtBooking($dayOfWeek, $startTime, $endTime)) {
            return 1;
        }
        
        return $baseMax;
    }

    /**
     * Check if parallel bookings are effectively allowed for a specific time.
     * Takes into account both day settings and main court status.
     */
    public function allowsParallelBookingsForTime(string $dayOfWeek, string $startTime, string $endTime): bool
    {
        // First check if parallel bookings are allowed for this day
        if (!$this->supportsParallelBookingsForDay($dayOfWeek)) {
            return false;
        }
        
        // If main court is booked, no parallel bookings allowed
        if ($this->hasMainCourtBooking($dayOfWeek, $startTime, $endTime)) {
            return false;
        }
        
        return true;
    }

    public function getHallTypeDisplayAttribute(): string
    {
        return match($this->hall_type) {
            'single' => 'Einfachhalle',
            'double' => 'Doppelhalle',
            'triple' => 'Dreifachhalle',
            'multi' => 'Mehrfachhalle',
            default => ucfirst($this->hall_type)
        };
    }

    public function getMinBookingDurationAttribute(): int
    {
        return $this->min_booking_duration_minutes ?: 30;
    }

    public function getBookingIncrementAttribute(): int
    {
        return $this->booking_increment_minutes ?: 30;
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
    // COURT MANAGEMENT METHODS
    // ============================

    public function getAvailableCourtsByTime(\Carbon\Carbon $dateTime, int $duration = 30): \Illuminate\Database\Eloquent\Collection
    {
        return $this->activeCourts()
            ->get()
            ->filter(function ($court) use ($dateTime, $duration) {
                return $court->isAvailableAt($dateTime, $duration);
            });
    }

    public function generateTimeGrid(\Carbon\Carbon $date, int $slotDuration = 30): array
    {
        $dayOfWeek = strtolower($date->format('l'));
        
        // Get operating hours for the day
        $operatingHours = $this->operating_hours[$dayOfWeek] ?? null;
        
        if (!$operatingHours || !$operatingHours['is_open']) {
            return [];
        }

        $startTime = \Carbon\Carbon::createFromTimeString($operatingHours['open_time']);
        $endTime = \Carbon\Carbon::createFromTimeString($operatingHours['close_time']);
        
        $timeGrid = [];
        $current = $startTime->copy();

        while ($current->copy()->addMinutes($slotDuration)->lte($endTime)) {
            $slotStart = $current->copy();
            $slotEnd = $current->copy()->addMinutes($slotDuration);
            
            $slotData = [
                'start_time' => $slotStart->format('H:i'),
                'end_time' => $slotEnd->format('H:i'),
                'duration' => $slotDuration,
                'courts' => []
            ];

            // Check availability for each court
            foreach ($this->activeCourts()->orderBy('sort_order')->get() as $court) {
                $slotDateTime = $date->copy()->setTimeFrom($slotStart);
                $isAvailable = $court->isAvailableAt($slotDateTime, $slotDuration);
                
                $slotData['courts'][] = [
                    'court_id' => $court->id,
                    'court_identifier' => $court->court_identifier,
                    'court_name' => $court->name,
                    'court_number' => $court->court_number,
                    'color_code' => $court->color_code,
                    'is_available' => $isAvailable,
                    'bookings' => []  // TODO: Implement getConflictingBookings method
                ];
            }

            $timeGrid[] = $slotData;
            $current->addMinutes($slotDuration);
        }

        return $timeGrid;
    }

    public function canAccommodateTeams(int $teamCount, \Carbon\Carbon $dateTime, int $duration = 30): bool
    {
        if (!$this->supports_parallel_bookings) {
            return $teamCount <= 1;
        }

        $availableCourts = $this->getAvailableCourtsByTime($dateTime, $duration);
        return $availableCourts->count() >= $teamCount;
    }

    public function findOptimalCourtsForTeams(int $teamCount, \Carbon\Carbon $dateTime, int $duration = 30): \Illuminate\Database\Eloquent\Collection
    {
        $availableCourts = $this->getAvailableCourtsByTime($dateTime, $duration);
        
        if ($availableCourts->count() < $teamCount) {
            return collect();
        }

        // Return courts ordered by preference (sort order)
        return $availableCourts->sortBy('sort_order')->take($teamCount);
    }

    public function validateBookingTime(\Carbon\Carbon $dateTime, int $duration): array
    {
        $errors = [];
        
        // Check if duration matches booking increment
        if ($duration % $this->booking_increment !== 0) {
            $errors[] = "Buchungsdauer muss ein Vielfaches von {$this->booking_increment} Minuten sein.";
        }
        
        // Check minimum duration
        if ($duration < $this->min_booking_duration) {
            $errors[] = "Mindestbuchungsdauer beträgt {$this->min_booking_duration} Minuten.";
        }
        
        // Check if time is on valid increment (00 or 30 minutes)
        $minutes = $dateTime->minute;
        if ($minutes % $this->booking_increment !== 0) {
            $errors[] = "Startzeit muss auf einem {$this->booking_increment}-Minuten-Raster liegen.";
        }
        
        // Check if within operating hours
        $dayOfWeek = strtolower($dateTime->format('l'));
        $operatingHours = $this->operating_hours[$dayOfWeek] ?? null;
        
        if (!$operatingHours || !$operatingHours['is_open']) {
            $errors[] = "Halle ist an diesem Tag nicht geöffnet.";
        } elseif (isset($operatingHours['open_time'], $operatingHours['close_time'])) {
            $openTime = \Carbon\Carbon::createFromTimeString($operatingHours['open_time']);
            $closeTime = \Carbon\Carbon::createFromTimeString($operatingHours['close_time']);
            $endTime = $dateTime->copy()->addMinutes($duration);
            
            if ($dateTime->format('H:i') < $openTime->format('H:i')) {
                $errors[] = "Startzeit liegt vor Öffnungszeit ({$openTime->format('H:i')}).";
            }
            
            if ($endTime->format('H:i') > $closeTime->format('H:i')) {
                $errors[] = "Endzeit liegt nach Schließzeit ({$closeTime->format('H:i')}).";
            }
        }
        
        return $errors;
    }

    public function initializeDefaultCourts(): void
    {
        if ($this->courts()->count() > 0) {
            return; // Already has courts
        }

        $courtsToCreate = match($this->hall_type) {
            'single' => [
                ['identifier' => '1', 'name' => 'Hauptfeld', 'color' => '#3B82F6']
            ],
            'double' => [
                ['identifier' => 'A', 'name' => 'Feld A', 'color' => '#3B82F6'],
                ['identifier' => 'B', 'name' => 'Feld B', 'color' => '#10B981']
            ],
            'triple' => [
                ['identifier' => 'A', 'name' => 'Feld A', 'color' => '#3B82F6'],
                ['identifier' => 'B', 'name' => 'Feld B', 'color' => '#10B981'],
                ['identifier' => 'C', 'name' => 'Feld C', 'color' => '#F59E0B']
            ],
            'multi' => array_map(function($i) {
                $colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#F97316'];
                return [
                    'identifier' => (string)($i + 1),
                    'name' => 'Feld ' . ($i + 1),
                    'color' => $colors[$i % count($colors)]
                ];
            }, range(0, $this->court_count - 1)),
            default => []
        };

        foreach ($courtsToCreate as $index => $courtData) {
            $this->courts()->create([
                'uuid' => Str::uuid(),
                'name' => $courtData['name'],
                'court_number' => $index + 1,
                'is_active' => true,
                'sort_order' => $index + 1,
                'metadata' => [
                    'identifier' => $courtData['identifier'],
                    'color_code' => $courtData['color'],
                    'court_type' => 'full',
                    'max_capacity' => $this->capacity
                ]
            ]);
        }
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
    // ACCESSORS
    // ============================
    
    public function getFullAddressAttribute(): string
    {
        $addressParts = [];
        
        if ($this->address_street) {
            $addressParts[] = $this->address_street;
        }
        
        if ($this->address_zip && $this->address_city) {
            $addressParts[] = $this->address_zip . ' ' . $this->address_city;
        } elseif ($this->address_city) {
            $addressParts[] = $this->address_city;
        }
        
        if ($this->address_country && $this->address_country !== 'Deutschland') {
            $addressParts[] = $this->address_country;
        }
        
        return implode(', ', $addressParts) ?: 'Keine Adresse angegeben';
    }

    // ============================
    // ROUTE MODEL BINDING
    // ============================

    // Using default 'id' for route model binding to match frontend implementation
}