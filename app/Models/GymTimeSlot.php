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
        'custom_times',
        'uses_custom_times',
        'time_slot_segments',
        'preferred_courts',
        'min_booking_duration_minutes',
        'booking_increment_minutes',
        'allows_partial_court',
        'supports_30_min_slots',
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
        'custom_times' => 'array',
        'uses_custom_times' => 'boolean',
        'time_slot_segments' => 'array',
        'preferred_courts' => 'array',
        'min_booking_duration_minutes' => 'integer',
        'booking_increment_minutes' => 'integer',
        'allows_partial_court' => 'boolean',
        'supports_30_min_slots' => 'boolean',
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

            // Only calculate duration for standard time slots, not custom times
            if (empty($gymTimeSlot->duration_minutes) && 
                !$gymTimeSlot->uses_custom_times && 
                $gymTimeSlot->start_time && 
                $gymTimeSlot->end_time) {
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

    public function teamAssignments(): HasMany
    {
        return $this->hasMany(GymTimeSlotTeamAssignment::class);
    }

    public function activeTeamAssignments(): HasMany
    {
        return $this->teamAssignments()->where('status', 'active');
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

    public function scopeSupports30MinSlots($query)
    {
        return $query->where('supports_30_min_slots', true);
    }

    public function scopeAllowsPartialCourt($query)
    {
        return $query->where('allows_partial_court', true);
    }

    public function scopeWithPreferredCourts($query, array $courtIds)
    {
        return $query->where(function($q) use ($courtIds) {
            $q->whereNull('preferred_courts')
              ->orWhereJsonContains('preferred_courts', $courtIds);
        });
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
    // 30-MIN SLOTS & FLEXIBLE BOOKING METHODS  
    // ============================

    public function getAvailableSegments(Carbon $date): array
    {
        $dayOfWeek = strtolower($date->format('l'));
        
        // Get times for this specific day (either custom or default)
        $times = $this->getTimesForDay($dayOfWeek);
        
        if (!$times || !$times['start_time'] || !$times['end_time']) {
            return [];
        }

        $startTime = Carbon::createFromTimeString($times['start_time']);
        $endTime = Carbon::createFromTimeString($times['end_time']);
        $increment = $this->booking_increment_minutes ?: 30;
        
        $segments = [];
        $current = $startTime->copy();

        while ($current->copy()->addMinutes($increment)->lte($endTime)) {
            $segmentStart = $current->copy();
            $segmentEnd = $current->copy()->addMinutes($increment);
            
            $segments[] = [
                'start_time' => $segmentStart->format('H:i'),
                'end_time' => $segmentEnd->format('H:i'),
                'duration_minutes' => $increment,
                'segment_id' => $segmentStart->format('Hi') . '-' . $segmentEnd->format('Hi'),
                'is_available' => $this->isSegmentAvailable($date, $segmentStart->format('H:i'), $increment)
            ];
            
            $current->addMinutes($increment);
        }

        return $segments;
    }

    public function canBookAtTime(Carbon $date, string $startTime, int $duration): bool
    {
        $dayOfWeek = strtolower($date->format('l'));
        
        // Check if this time slot is available for this day
        if (!$this->isAvailableForDate($date)) {
            return false;
        }

        // Get allowed times for this day
        $allowedTimes = $this->getTimesForDay($dayOfWeek);
        
        if (!$allowedTimes) {
            return false;
        }

        // Validate time is within allowed range
        $requestStart = Carbon::createFromTimeString($startTime);
        $requestEnd = $requestStart->copy()->addMinutes($duration);
        $allowedStart = Carbon::createFromTimeString($allowedTimes['start_time']);
        $allowedEnd = Carbon::createFromTimeString($allowedTimes['end_time']);

        if ($requestStart->lt($allowedStart) || $requestEnd->gt($allowedEnd)) {
            return false;
        }

        // Validate duration and increment
        $increment = $this->booking_increment_minutes ?: 30;
        $minDuration = $this->min_booking_duration_minutes ?: 30;
        
        if ($duration < $minDuration || $duration % $increment !== 0) {
            return false;
        }

        // Check if start time is on valid increment (00 or 30 minutes)
        if ($requestStart->minute % $increment !== 0) {
            return false;
        }

        return true;
    }

    public function getTimeGridForDay(string $dayOfWeek): array
    {
        $times = $this->getTimesForDay($dayOfWeek);
        
        if (!$times || !$times['start_time'] || !$times['end_time']) {
            return [];
        }

        $startTime = Carbon::createFromTimeString($times['start_time']);
        $endTime = Carbon::createFromTimeString($times['end_time']);
        $increment = $this->booking_increment_minutes ?: 30;
        
        $grid = [];
        $current = $startTime->copy();

        while ($current->copy()->addMinutes($increment)->lte($endTime)) {
            $grid[] = [
                'start_time' => $current->format('H:i'),
                'end_time' => $current->copy()->addMinutes($increment)->format('H:i'),
                'duration_minutes' => $increment,
                'time_key' => $current->format('Hi')
            ];
            
            $current->addMinutes($increment);
        }

        return $grid;
    }

    public function validateBookingTime(Carbon $date, string $startTime, string $endTime): array
    {
        $errors = [];
        $dayOfWeek = strtolower($date->format('l'));
        
        try {
            $start = Carbon::createFromTimeString($startTime);
            $end = Carbon::createFromTimeString($endTime);
            $duration = $start->diffInMinutes($end);
            
            // Check if booking is possible for this time slot
            if (!$this->canBookAtTime($date, $startTime, $duration)) {
                $errors[] = 'Buchung zu dieser Zeit nicht möglich für dieses Zeitfenster.';
            }
            
            // Additional custom time validations
            $allowedTimes = $this->getTimesForDay($dayOfWeek);
            if ($allowedTimes) {
                $allowedStart = Carbon::createFromTimeString($allowedTimes['start_time']);
                $allowedEnd = Carbon::createFromTimeString($allowedTimes['end_time']);
                
                if ($start->lt($allowedStart)) {
                    $errors[] = "Startzeit liegt vor der erlaubten Zeit ({$allowedTimes['start_time']}).";
                }
                
                if ($end->gt($allowedEnd)) {
                    $errors[] = "Endzeit liegt nach der erlaubten Zeit ({$allowedTimes['end_time']}).";
                }
            }
            
        } catch (\Exception $e) {
            $errors[] = 'Ungültiges Zeitformat.';
        }

        return $errors;
    }

    private function isSegmentAvailable(Carbon $date, string $startTime, int $duration): bool
    {
        $dateTimeString = $date->toDateString() . ' ' . $startTime;
        $segmentDateTime = Carbon::createFromFormat('Y-m-d H:i', $dateTimeString);
        
        // Check if segment conflicts with existing bookings
        $conflictingBookings = $this->bookings()
            ->whereDate('booking_date', $date)
            ->where(function ($query) use ($segmentDateTime, $duration) {
                $segmentEnd = $segmentDateTime->copy()->addMinutes($duration);
                $query->where(function ($q) use ($segmentDateTime, $segmentEnd) {
                    $q->where('start_time', '<', $segmentEnd->format('H:i:s'))
                      ->where('end_time', '>', $segmentDateTime->format('H:i:s'));
                });
            })
            ->whereIn('status', ['reserved', 'confirmed'])
            ->exists();

        return !$conflictingBookings;
    }

    // ============================
    // COURT PREFERENCE METHODS
    // ============================

    public function getPreferredCourts(): array
    {
        return $this->preferred_courts ?: [];
    }

    public function hasPreferredCourts(): bool
    {
        return !empty($this->preferred_courts);
    }

    public function addPreferredCourt(int $courtId): void
    {
        $preferredCourts = $this->getPreferredCourts();
        
        if (!in_array($courtId, $preferredCourts)) {
            $preferredCourts[] = $courtId;
            $this->update(['preferred_courts' => $preferredCourts]);
        }
    }

    public function removePreferredCourt(int $courtId): void
    {
        $preferredCourts = $this->getPreferredCourts();
        $filteredCourts = array_filter($preferredCourts, fn($id) => $id !== $courtId);
        
        $this->update(['preferred_courts' => array_values($filteredCourts)]);
    }

    public function isCourtPreferred(int $courtId): bool
    {
        return in_array($courtId, $this->getPreferredCourts());
    }

    // ============================
    // ENHANCED BOOKING METHODS
    // ============================

    public function createFlexibleBookingForDate(
        Carbon $date, 
        Team $team, 
        User $bookedBy, 
        string $startTime, 
        int $durationMinutes,
        array $courtIds = []
    ): GymBooking {
        $endTime = Carbon::createFromTimeString($startTime)->addMinutes($durationMinutes);

        $bookingData = [
            'uuid' => Str::uuid(),
            'team_id' => $team->id,
            'booked_by_user_id' => $bookedBy->id,
            'booking_date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime->format('H:i'),
            'duration_minutes' => $durationMinutes,
            'status' => 'reserved',
            'booking_type' => 'regular',
            'court_ids' => $courtIds,
            'is_partial_court' => count($courtIds) < $this->gymHall->court_count,
        ];

        $booking = $this->bookings()->create($bookingData);

        // Attach courts if specified
        if (!empty($courtIds)) {
            $booking->courts()->attach($courtIds);
        }

        return $booking;
    }

    // ============================
    // CUSTOM TIMES METHODS
    // ============================

    /**
     * Get times for a specific day of the week
     */
    public function getTimesForDay(string $dayOfWeek): ?array
    {
        if (!$this->uses_custom_times || !$this->custom_times) {
            return [
                'start_time' => $this->start_time?->format('H:i'),
                'end_time' => $this->end_time?->format('H:i'),
            ];
        }

        return $this->custom_times[$dayOfWeek] ?? null;
    }

    /**
     * Set times for a specific day of the week
     */
    public function setTimesForDay(string $dayOfWeek, ?string $startTime, ?string $endTime): void
    {
        $customTimes = $this->custom_times ?? [];
        
        if ($startTime && $endTime) {
            $customTimes[$dayOfWeek] = [
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];
        } else {
            unset($customTimes[$dayOfWeek]);
        }

        $this->update([
            'custom_times' => $customTimes,
            'uses_custom_times' => !empty($customTimes),
        ]);
    }

    /**
     * Get all available days with their times
     */
    public function getAllDayTimes(): array
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $result = [];

        foreach ($days as $day) {
            $times = $this->getTimesForDay($day);
            if ($times && $times['start_time'] && $times['end_time']) {
                $result[$day] = $times;
            }
        }

        return $result;
    }

    /**
     * Check if hall is available on specific day and time
     */
    public function isAvailableOnDayAndTime(string $dayOfWeek, string $time): bool
    {
        $times = $this->getTimesForDay($dayOfWeek);
        
        if (!$times || !$times['start_time'] || !$times['end_time']) {
            return false;
        }

        return $time >= $times['start_time'] && $time <= $times['end_time'];
    }

    /**
     * Set custom times for multiple days
     */
    public function setCustomTimes(array $dayTimes): void
    {
        $customTimes = [];
        
        foreach ($dayTimes as $day => $times) {
            if (!empty($times['start_time']) && !empty($times['end_time'])) {
                $customTimes[$day] = [
                    'start_time' => $times['start_time'],
                    'end_time' => $times['end_time'],
                ];
            }
        }

        $this->update([
            'custom_times' => $customTimes,
            'uses_custom_times' => !empty($customTimes),
        ]);
    }

    /**
     * Get formatted time range for a specific day
     */
    public function getFormattedTimeRangeForDay(string $dayOfWeek): ?string
    {
        $times = $this->getTimesForDay($dayOfWeek);
        
        if (!$times || !$times['start_time'] || !$times['end_time']) {
            return null;
        }

        return $times['start_time'] . ' - ' . $times['end_time'];
    }

    // ============================
    // VALIDATION & OVERLAP CHECKING
    // ============================

    /**
     * Check for overlapping time slots in the same hall
     */
    public static function hasOverlappingSlots(int $gymHallId, array $customTimes, $excludeSlotIds = null): array
    {
        $conflicts = [];
        
        $existingSlots = static::where('gym_hall_id', $gymHallId)
            ->when($excludeSlotIds, function ($query, $excludeSlotIds) {
                // Handle both single ID and array of IDs
                if (is_array($excludeSlotIds)) {
                    $query->whereNotIn('id', $excludeSlotIds);
                } else {
                    $query->where('id', '!=', $excludeSlotIds);
                }
            })
            ->get();

        foreach ($customTimes as $day => $newTimes) {
            if (!isset($newTimes['start_time']) || !isset($newTimes['end_time'])) {
                continue;
            }

            $newStart = Carbon::createFromTimeString($newTimes['start_time']);
            $newEnd = Carbon::createFromTimeString($newTimes['end_time']);

            foreach ($existingSlots as $existingSlot) {
                $existingTimes = $existingSlot->getTimesForDay($day);
                
                if (!$existingTimes || !$existingTimes['start_time'] || !$existingTimes['end_time']) {
                    continue;
                }

                $existingStart = Carbon::createFromTimeString($existingTimes['start_time']);
                $existingEnd = Carbon::createFromTimeString($existingTimes['end_time']);

                // Check for overlap
                if ($newStart->lt($existingEnd) && $newEnd->gt($existingStart)) {
                    $conflicts[] = [
                        'day' => $day,
                        'new_time' => $newTimes['start_time'] . ' - ' . $newTimes['end_time'],
                        'existing_time' => $existingTimes['start_time'] . ' - ' . $existingTimes['end_time'],
                        'existing_slot_id' => $existingSlot->id,
                        'existing_slot_title' => $existingSlot->title,
                    ];
                }
            }
        }

        return $conflicts;
    }

    /**
     * Validate custom times structure and ranges
     */
    public static function validateCustomTimes(array $customTimes): array
    {
        $errors = [];
        $validDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($customTimes as $day => $times) {
            if (!in_array($day, $validDays)) {
                $errors[] = "Ungültiger Wochentag: {$day}";
                continue;
            }

            if (!is_array($times) || !isset($times['start_time']) || !isset($times['end_time'])) {
                $errors[] = "Ungültige Zeitstruktur für {$day}";
                continue;
            }

            // Validate time format
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $times['start_time'])) {
                $errors[] = "Ungültiges Startzeit-Format für {$day}: {$times['start_time']}";
            }

            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $times['end_time'])) {
                $errors[] = "Ungültiges Endzeit-Format für {$day}: {$times['end_time']}";
            }

            // Check if start time is before end time
            try {
                $start = Carbon::createFromTimeString($times['start_time']);
                $end = Carbon::createFromTimeString($times['end_time']);

                if ($start->gte($end)) {
                    $errors[] = "Startzeit muss vor Endzeit liegen für {$day}";
                }

                // Check for reasonable opening hours (not longer than 18 hours)
                if ($start->diffInHours($end) > 18) {
                    $errors[] = "Öffnungszeiten für {$day} sind zu lang (maximal 18 Stunden)";
                }

                // Check for minimum duration (at least 30 minutes)
                if ($start->diffInMinutes($end) < 30) {
                    $errors[] = "Mindestöffnungszeit von 30 Minuten für {$day} unterschritten";
                }
                
            } catch (\Exception $e) {
                $errors[] = "Fehler beim Validieren der Zeiten für {$day}: " . $e->getMessage();
            }
        }

        return $errors;
    }

    /**
     * Get conflicting bookings for new time slots
     */
    public function getConflictingBookings(array $newCustomTimes): array
    {
        $conflicts = [];
        
        foreach ($newCustomTimes as $day => $times) {
            if (!isset($times['start_time']) || !isset($times['end_time'])) {
                continue;
            }

            $newStart = $times['start_time'];
            $newEnd = $times['end_time'];
            
            // Get current times for this day
            $currentTimes = $this->getTimesForDay($day);
            
            // Only check if times are being restricted (new times are more restrictive)
            if ($currentTimes && 
                ($newStart > $currentTimes['start_time'] || $newEnd < $currentTimes['end_time'])) {
                
                $dayNumber = $this->getDayNumberForName($day);
                
                // Find bookings that would be outside new time range
                $conflictingBookings = $this->bookings()
                    ->whereRaw('DAYOFWEEK(booking_date) - 1 = ?', [$dayNumber])
                    ->where(function ($query) use ($newStart, $newEnd) {
                        $query->where('start_time', '<', $newStart)
                              ->orWhere('end_time', '>', $newEnd);
                    })
                    ->whereIn('status', ['reserved', 'confirmed'])
                    ->with(['team'])
                    ->get();

                if ($conflictingBookings->count() > 0) {
                    $conflicts[$day] = $conflictingBookings->map(function ($booking) {
                        return [
                            'id' => $booking->id,
                            'date' => $booking->booking_date,
                            'time' => $booking->start_time . ' - ' . $booking->end_time,
                            'team' => $booking->team->name ?? 'Unbekannt',
                            'status' => $booking->status,
                        ];
                    })->toArray();
                }
            }
        }

        return $conflicts;
    }

    /**
     * Get day number for day name (0 = Monday, 6 = Sunday)
     */
    private function getDayNumberForName(string $dayName): int
    {
        $days = [
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            'sunday' => 0, // MySQL DAYOFWEEK returns 1 for Sunday, but we want 0
        ];

        return $days[$dayName] ?? 1;
    }

    // ============================
    // SEGMENT-BASED TEAM ASSIGNMENT METHODS
    // ============================

    public function getTeamAssignmentsForDay(string $dayOfWeek): array
    {
        return $this->activeTeamAssignments()
            ->where('day_of_week', $dayOfWeek)
            ->with(['team'])
            ->orderBy('start_time')
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'team_id' => $assignment->team_id,
                    'team_name' => $assignment->team->name,
                    'start_time' => $assignment->start_time->format('H:i'),
                    'end_time' => $assignment->end_time->format('H:i'),
                    'duration_minutes' => $assignment->duration_minutes,
                    'notes' => $assignment->notes,
                ];
            })
            ->toArray();
    }

    public function assignTeamToSegment(
        Team $team,
        string $dayOfWeek,
        string $startTime,
        string $endTime,
        User $assignedBy,
        string $notes = null,
        GymCourt $gymCourt = null
    ): GymTimeSlotTeamAssignment {
        $startCarbon = Carbon::createFromTimeString($startTime);
        $endCarbon = Carbon::createFromTimeString($endTime);
        $duration = $startCarbon->diffInMinutes($endCarbon);

        return $this->teamAssignments()->create([
            'uuid' => Str::uuid(),
            'team_id' => $team->id,
            'gym_court_id' => $gymCourt?->id,
            'day_of_week' => $dayOfWeek,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_minutes' => $duration,
            'status' => 'active',
            'notes' => $notes,
            'assigned_by' => $assignedBy->id,
            'assigned_at' => now(),
            'valid_from' => now()->toDateString(),
        ]);
    }

    public function removeTeamAssignment(int $assignmentId): bool
    {
        $assignment = $this->teamAssignments()->find($assignmentId);
        
        if ($assignment) {
            return $assignment->delete();
        }
        
        return false;
    }

    public function getAvailableSegmentsForDay(string $dayOfWeek, int $incrementMinutes = 30): array
    {
        $times = $this->getTimesForDay($dayOfWeek);
        
        if (!$times || !$times['start_time'] || !$times['end_time']) {
            return [];
        }

        $startTime = Carbon::createFromTimeString($times['start_time']);
        $endTime = Carbon::createFromTimeString($times['end_time']);
        
        $segments = [];
        $current = $startTime->copy();

        while ($current->copy()->addMinutes($incrementMinutes)->lte($endTime)) {
            $segmentStart = $current->copy();
            $segmentEnd = $current->copy()->addMinutes($incrementMinutes);
            
            $assignedTeams = $this->getTeamsAssignedToSegment(
                $dayOfWeek,
                $segmentStart->format('H:i'),
                $segmentEnd->format('H:i')
            );
            
            $segments[] = [
                'start_time' => $segmentStart->format('H:i'),
                'end_time' => $segmentEnd->format('H:i'),
                'duration_minutes' => $incrementMinutes,
                'segment_id' => $segmentStart->format('Hi') . '-' . $segmentEnd->format('Hi'),
                'is_available' => empty($assignedTeams),
                'assigned_teams' => $assignedTeams,
            ];
            
            $current->addMinutes($incrementMinutes);
        }

        return $segments;
    }

    public function getTeamsAssignedToSegment(string $dayOfWeek, string $startTime, string $endTime): array
    {
        return $this->activeTeamAssignments()
            ->where('day_of_week', $dayOfWeek)
            ->where(function($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime);
            })
            ->with(['team'])
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'team_id' => $assignment->team_id,
                    'team_name' => $assignment->team->name,
                    'start_time' => $assignment->start_time->format('H:i'),
                    'end_time' => $assignment->end_time->format('H:i'),
                ];
            })
            ->toArray();
    }

    public function canAssignTeamToSegment(
        int $teamId,
        string $dayOfWeek,
        string $startTime,
        string $endTime,
        int $gymCourtId = null
    ): array {
        $errors = [];

        if (GymTimeSlotTeamAssignment::hasConflictForTeam(
            $this->id,
            $teamId,
            $dayOfWeek,
            $startTime,
            $endTime
        )) {
            $errors[] = 'Team hat bereits eine Zuordnung zu dieser Zeit.';
        }

        $times = $this->getTimesForDay($dayOfWeek);
        if (!$times) {
            $errors[] = 'Keine Öffnungszeiten für diesen Tag definiert.';
        } else {
            if ($startTime < $times['start_time'] || $endTime > $times['end_time']) {
                $errors[] = 'Zeitfenster liegt außerhalb der Öffnungszeiten.';
            }
        }

        $startCarbon = Carbon::createFromTimeString($startTime);
        $endCarbon = Carbon::createFromTimeString($endTime);
        $duration = $startCarbon->diffInMinutes($endCarbon);

        if ($duration < 30) {
            $errors[] = 'Minimale Buchungsdauer von 30 Minuten unterschritten.';
        }

        if ($duration % 30 !== 0) {
            $errors[] = 'Buchungsdauer muss in 30-Minuten-Schritten erfolgen.';
        }

        // Check parallel bookings restrictions considering main court logic
        $gymHall = $this->gymHall;
        
        // Check if we're trying to book the main court
        $mainCourt = $gymHall->getMainCourt();
        $isBookingMainCourt = $mainCourt && $gymCourtId == $mainCourt->id;
        
        // Check if main court is already booked during this time
        $mainCourtIsBooked = $gymHall->hasMainCourtBooking($dayOfWeek, $startTime, $endTime);
        
        // Get effective parallel booking rules (considering main court)
        $effectiveParallelBookingsAllowed = $gymHall->allowsParallelBookingsForTime($dayOfWeek, $startTime, $endTime);
        
        if ($isBookingMainCourt) {
            // If trying to book main court, check if any other courts are occupied
            $otherCourtAssignments = $this->activeTeamAssignments()
                ->where('day_of_week', $dayOfWeek)
                ->where('team_id', '!=', $teamId)
                ->where('gym_court_id', '!=', $mainCourt->id)
                ->whereNotNull('gym_court_id')
                ->where(function($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
                })
                ->with(['team', 'gymCourt'])
                ->get();

            if ($otherCourtAssignments->count() > 0) {
                $occupiedCourts = $otherCourtAssignments->map(function($assignment) {
                    return $assignment->team->name . ' (' . $assignment->gymCourt->name . ')';
                })->join(', ');
                $errors[] = "Hauptplatz kann nicht gebucht werden - andere Felder sind bereits belegt: {$occupiedCourts}";
            }
        } elseif ($mainCourtIsBooked) {
            // If main court is booked, no other bookings allowed
            $errors[] = "Keine weiteren Buchungen möglich - der Hauptplatz ist zu dieser Zeit belegt.";
        } elseif (!$effectiveParallelBookingsAllowed) {
            // Standard parallel booking rules
            $existingAssignments = $this->activeTeamAssignments()
                ->where('day_of_week', $dayOfWeek)
                ->where('team_id', '!=', $teamId)
                ->where(function($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
                })
                ->with(['team'])
                ->get();

            if ($existingAssignments->count() > 0) {
                $teamNames = $existingAssignments->pluck('team.name')->join(', ');
                $errors[] = "Für diesen Tag sind keine Parallel-Buchungen erlaubt. Bereits belegt von: {$teamNames}";
            }
        } else {
            // Parallel bookings allowed - check effective capacity
            $effectiveMaxTeams = $gymHall->getEffectiveMaxParallelTeams($dayOfWeek, $startTime, $endTime);
            
            $overlappingAssignments = $this->activeTeamAssignments()
                ->where('day_of_week', $dayOfWeek)
                ->where('team_id', '!=', $teamId)
                ->where(function($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
                })
                ->count();

            if ($overlappingAssignments >= $effectiveMaxTeams) {
                $errors[] = "Maximale Anzahl paralleler Teams ({$effectiveMaxTeams}) für diesen Tag bereits erreicht.";
            }

            // If a court is specified, check for court conflicts
            if ($gymCourtId) {
                $courtConflict = GymTimeSlotTeamAssignment::hasConflictForCourt(
                    $this->id,
                    $gymCourtId,
                    $dayOfWeek,
                    $startTime,
                    $endTime
                );
                
                if ($courtConflict) {
                    $errors[] = 'Das ausgewählte Feld ist zu dieser Zeit bereits belegt.';
                }
            }
        }

        return $errors;
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