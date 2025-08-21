<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Carbon\Carbon;

class GymHallCourt extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid',
        'gym_hall_id',
        'court_identifier',
        'court_name',
        'court_type',
        'max_capacity',
        'equipment',
        'color_code',
        'width_meters',
        'length_meters',
        'is_active',
        'description',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'uuid' => 'string',
        'max_capacity' => 'integer',
        'equipment' => 'array',
        'width_meters' => 'decimal:2',
        'length_meters' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
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

    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(GymBooking::class, 'gym_booking_courts')
            ->withTimestamps();
    }

    public function activeBookings(): BelongsToMany
    {
        return $this->bookings()
            ->whereIn('status', ['reserved', 'confirmed']);
    }

    // ============================
    // SCOPES
    // ============================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInHall($query, $hallId)
    {
        return $query->where('gym_hall_id', $hallId);
    }

    public function scopeOrderedBySortOrder($query)
    {
        return $query->orderBy('sort_order')->orderBy('court_identifier');
    }

    public function scopeOfType($query, $courtType)
    {
        return $query->where('court_type', $courtType);
    }

    // ============================
    // ACCESSORS & MUTATORS
    // ============================

    public function getFullNameAttribute(): string
    {
        return "{$this->court_identifier} - {$this->court_name}";
    }

    public function getDimensionsAttribute(): ?string
    {
        if ($this->width_meters && $this->length_meters) {
            return "{$this->width_meters}m x {$this->length_meters}m";
        }
        return null;
    }

    public function getAreaAttribute(): ?float
    {
        if ($this->width_meters && $this->length_meters) {
            return $this->width_meters * $this->length_meters;
        }
        return null;
    }

    // ============================
    // AVAILABILITY METHODS
    // ============================

    public function isAvailableAt(Carbon $dateTime, int $durationMinutes = 30): bool
    {
        if (!$this->is_active) {
            return false;
        }

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

    public function getBookingsForDate(Carbon $date): \Illuminate\Database\Eloquent\Collection
    {
        return $this->bookings()
            ->whereDate('booking_date', $date)
            ->whereIn('status', ['reserved', 'confirmed'])
            ->orderBy('start_time')
            ->get();
    }

    public function getAvailableTimeSlots(Carbon $date, int $slotDurationMinutes = 30): array
    {
        if (!$this->gymHall || !$this->is_active) {
            return [];
        }

        $dayOfWeek = strtolower($date->format('l'));
        
        // Get hall's operating hours for the day
        $operatingHours = $this->gymHall->operating_hours[$dayOfWeek] ?? null;
        
        if (!$operatingHours || !$operatingHours['is_open']) {
            return [];
        }

        $startTime = Carbon::createFromTimeString($operatingHours['open_time']);
        $endTime = Carbon::createFromTimeString($operatingHours['close_time']);
        
        $availableSlots = [];
        $current = $startTime->copy();

        while ($current->addMinutes($slotDurationMinutes)->lte($endTime)) {
            $slotStart = $current->copy()->subMinutes($slotDurationMinutes);
            $slotDateTime = $date->copy()->setTimeFrom($slotStart);
            
            if ($this->isAvailableAt($slotDateTime, $slotDurationMinutes)) {
                $availableSlots[] = [
                    'start_time' => $slotStart->format('H:i'),
                    'end_time' => $current->format('H:i'),
                    'duration_minutes' => $slotDurationMinutes,
                    'datetime' => $slotDateTime,
                ];
            }
        }

        return $availableSlots;
    }

    public function getUtilizationRate(Carbon $startDate, Carbon $endDate): float
    {
        if (!$this->is_active) {
            return 0.0;
        }

        $totalSlots = $this->calculateTotalAvailableSlots($startDate, $endDate);
        
        if ($totalSlots === 0) {
            return 0.0;
        }

        $bookedMinutes = $this->bookings()
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->whereIn('status', ['reserved', 'confirmed', 'completed'])
            ->sum('duration_minutes');

        $totalMinutes = $totalSlots * 30; // 30-minute slots
        
        return round(($bookedMinutes / $totalMinutes) * 100, 1);
    }

    private function calculateTotalAvailableSlots(Carbon $startDate, Carbon $endDate): int
    {
        $totalSlots = 0;
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $availableSlots = $this->getAvailableTimeSlots($current);
            $totalSlots += count($availableSlots);
            $current->addDay();
        }

        return $totalSlots;
    }

    // ============================
    // BOOKING METHODS
    // ============================

    public function hasConflictWith(Carbon $dateTime, int $durationMinutes, array $excludeBookingIds = []): bool
    {
        $endTime = $dateTime->copy()->addMinutes($durationMinutes);
        
        $query = $this->bookings()
            ->whereDate('booking_date', $dateTime->toDateString())
            ->where(function ($query) use ($dateTime, $endTime) {
                $query->where(function ($q) use ($dateTime, $endTime) {
                    $q->where('start_time', '<', $endTime->format('H:i:s'))
                      ->where('end_time', '>', $dateTime->format('H:i:s'));
                });
            })
            ->whereIn('status', ['reserved', 'confirmed']);

        if (!empty($excludeBookingIds)) {
            $query->whereNotIn('id', $excludeBookingIds);
        }

        return $query->exists();
    }

    public function getConflictingBookings(Carbon $dateTime, int $durationMinutes): \Illuminate\Database\Eloquent\Collection
    {
        $endTime = $dateTime->copy()->addMinutes($durationMinutes);
        
        return $this->bookings()
            ->whereDate('booking_date', $dateTime->toDateString())
            ->where(function ($query) use ($dateTime, $endTime) {
                $query->where(function ($q) use ($dateTime, $endTime) {
                    $q->where('start_time', '<', $endTime->format('H:i:s'))
                      ->where('end_time', '>', $dateTime->format('H:i:s'));
                });
            })
            ->whereIn('status', ['reserved', 'confirmed'])
            ->with(['team'])
            ->get();
    }

    // ============================
    // HELPER METHODS
    // ============================

    public function getDisplayColor(): string
    {
        return $this->color_code ?: '#3B82F6';
    }

    public function canAccommodateCapacity(int $requiredCapacity): bool
    {
        return $this->max_capacity === null || $this->max_capacity >= $requiredCapacity;
    }

    public function hasEquipment(string $equipmentName): bool
    {
        return in_array($equipmentName, $this->equipment ?? []);
    }

    public function getFormattedEquipment(): string
    {
        return implode(', ', $this->equipment ?? []);
    }

    // ============================
    // ACTIVITY LOG
    // ============================

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'court_name', 'court_identifier', 'is_active', 'max_capacity',
                'court_type', 'color_code'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // ============================
    // ROUTE MODEL BINDING
    // ============================

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
