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
use Carbon\Carbon;

class GymTimeSlot extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid',
        'gym_hall_id',
        'team_id',
        'title',
        'description',
        'day_of_week',
        'start_time',
        'end_time',
        'duration_minutes',
        'recurrence_type',
        'valid_from',
        'valid_until',
        'status',
        'slot_type',
        'max_participants',
        'is_recurring',
        'allows_substitution',
        'excluded_dates',
        'assigned_by',
        'assigned_at',
        'special_instructions',
        'cost_per_hour',
        'required_equipment',
        'metadata',
    ];

    protected $casts = [
        'uuid' => 'string',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'duration_minutes' => 'integer',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'max_participants' => 'integer',
        'is_recurring' => 'boolean',
        'allows_substitution' => 'boolean',
        'excluded_dates' => 'array',
        'assigned_at' => 'datetime',
        'cost_per_hour' => 'decimal:2',
        'required_equipment' => 'array',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($gymTimeSlot) {
            if (empty($gymTimeSlot->uuid)) {
                $gymTimeSlot->uuid = (string) Str::uuid();
            }

            if (empty($gymTimeSlot->duration_minutes) && $gymTimeSlot->start_time && $gymTimeSlot->end_time) {
                $start = Carbon::createFromTimeString($gymTimeSlot->start_time);
                $end = Carbon::createFromTimeString($gymTimeSlot->end_time);
                $gymTimeSlot->duration_minutes = $end->diffInMinutes($start);
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

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(GymBooking::class);
    }

    public function activeBookings(): HasMany
    {
        return $this->bookings()->whereIn('status', ['reserved', 'confirmed']);
    }

    public function requests()
    {
        return $this->hasManyThrough(
            GymBookingRequest::class,
            GymBooking::class,
            'gym_time_slot_id',
            'gym_booking_id',
            'id',
            'id'
        );
    }

    // ============================
    // SCOPES
    // ============================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeOnDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    public function scopeInDateRange($query, Carbon $from, Carbon $until = null)
    {
        $query->where('valid_from', '<=', $from);
        
        if ($until) {
            $query->where(function ($q) use ($until) {
                $q->whereNull('valid_until')
                  ->orWhere('valid_until', '>=', $until);
            });
        }

        return $query;
    }

    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    public function scopeAllowsSubstitution($query)
    {
        return $query->where('allows_substitution', true);
    }

    // ============================
    // ACCESSORS & MUTATORS
    // ============================

    public function getDayNameAttribute(): string
    {
        return ucfirst($this->day_of_week);
    }

    public function getTimeRangeAttribute(): string
    {
        return $this->start_time->format('H:i') . ' - ' . $this->end_time->format('H:i');
    }

    public function getIsAssignedAttribute(): bool
    {
        return !is_null($this->team_id);
    }

    public function getCanBeSubstitutedAttribute(): bool
    {
        return $this->allows_substitution && $this->is_assigned;
    }

    public function getNextOccurrenceAttribute(): ?Carbon
    {
        if (!$this->is_recurring) {
            return null;
        }

        $now = now();
        $dayNumber = $this->getDayNumber();
        
        $nextDate = $now->copy()->startOfWeek()->addDays($dayNumber);
        
        if ($nextDate->isPast()) {
            $nextDate->addWeek();
        }

        if ($this->valid_until && $nextDate->gt($this->valid_until)) {
            return null;
        }

        return $nextDate;
    }

    // ============================
    // HELPER METHODS
    // ============================

    public function getDayNumber(): int
    {
        $days = [
            'monday' => 0,
            'tuesday' => 1,
            'wednesday' => 2,
            'thursday' => 3,
            'friday' => 4,
            'saturday' => 5,
            'sunday' => 6,
        ];

        return $days[$this->day_of_week] ?? 0;
    }

    public function assignToTeam(Team $team, User $assignedBy, string $reason = null): void
    {
        $this->update([
            'team_id' => $team->id,
            'assigned_by' => $assignedBy->id,
            'assigned_at' => now(),
            'metadata' => array_merge($this->metadata ?? [], [
                'assignment_reason' => $reason,
                'assigned_by_name' => $assignedBy->name,
            ])
        ]);
    }

    public function unassignFromTeam(User $unassignedBy, string $reason = null): void
    {
        $this->update([
            'team_id' => null,
            'metadata' => array_merge($this->metadata ?? [], [
                'last_unassigned_at' => now(),
                'unassignment_reason' => $reason,
                'unassigned_by' => $unassignedBy->id,
                'unassigned_by_name' => $unassignedBy->name,
            ])
        ]);
    }

    public function isAvailableForDate(Carbon $date): bool
    {
        if ($this->excluded_dates && in_array($date->toDateString(), $this->excluded_dates)) {
            return false;
        }

        if ($date->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && $date->gt($this->valid_until)) {
            return false;
        }

        if ($this->day_of_week !== strtolower($date->format('l'))) {
            return false;
        }

        return true;
    }

    public function hasBookingForDate(Carbon $date): bool
    {
        return $this->bookings()
            ->whereDate('booking_date', $date)
            ->whereIn('status', ['reserved', 'confirmed'])
            ->exists();
    }

    public function getBookingForDate(Carbon $date): ?GymBooking
    {
        return $this->bookings()
            ->whereDate('booking_date', $date)
            ->first();
    }

    public function createBookingForDate(Carbon $date, Team $team, User $bookedBy): GymBooking
    {
        return $this->bookings()->create([
            'uuid' => Str::uuid(),
            'team_id' => $team->id,
            'booked_by_user_id' => $bookedBy->id,
            'booking_date' => $date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'duration_minutes' => $this->duration_minutes,
            'status' => 'reserved',
            'booking_type' => 'regular',
        ]);
    }

    public function generateBookingsForPeriod(Carbon $startDate, Carbon $endDate): int
    {
        if (!$this->is_recurring || !$this->team_id) {
            return 0;
        }

        $created = 0;
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            if ($this->isAvailableForDate($current) && !$this->hasBookingForDate($current)) {
                $this->createBookingForDate($current, $this->team, $this->assignedByUser);
                $created++;
            }

            switch ($this->recurrence_type) {
                case 'weekly':
                    $current->addWeek();
                    break;
                case 'biweekly':
                    $current->addWeeks(2);
                    break;
                case 'monthly':
                    $current->addMonth();
                    break;
                default:
                    break 2; // Exit the while loop
            }
        }

        return $created;
    }

    public function excludeDate(Carbon $date, string $reason = null): void
    {
        $excludedDates = $this->excluded_dates ?? [];
        $dateString = $date->toDateString();

        if (!in_array($dateString, $excludedDates)) {
            $excludedDates[] = $dateString;
            $this->update([
                'excluded_dates' => $excludedDates,
                'metadata' => array_merge($this->metadata ?? [], [
                    'exclusions' => array_merge($this->metadata['exclusions'] ?? [], [
                        $dateString => [
                            'reason' => $reason,
                            'excluded_at' => now(),
                        ]
                    ])
                ])
            ]);
        }
    }

    public function includeDate(Carbon $date): void
    {
        $excludedDates = $this->excluded_dates ?? [];
        $dateString = $date->toDateString();

        if (($key = array_search($dateString, $excludedDates)) !== false) {
            unset($excludedDates[$key]);
            
            $metadata = $this->metadata ?? [];
            if (isset($metadata['exclusions'][$dateString])) {
                unset($metadata['exclusions'][$dateString]);
            }

            $this->update([
                'excluded_dates' => array_values($excludedDates),
                'metadata' => $metadata,
            ]);
        }
    }

    // ============================
    // ACTIVITY LOG
    // ============================

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'team_id', 'status', 'day_of_week', 'start_time', 'end_time',
                'allows_substitution'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}