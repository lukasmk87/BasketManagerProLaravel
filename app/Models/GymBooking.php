<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class GymBooking extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'uuid',
        'gym_time_slot_id',
        'team_id',
        'game_id',
        'booked_by_user_id',
        'booking_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'status',
        'booking_type',
        'priority',
        'original_team_id',
        'substitute_team_id',
        'release_reason',
        'booking_notes',
        'released_at',
        'released_by_user_id',
        'requested_at',
        'confirmed_at',
        'confirmed_by_user_id',
        'cancelled_at',
        'cancelled_by_user_id',
        'cancellation_reason',
        'cost',
        'payment_required',
        'payment_status',
        'participants_count',
        'participant_list',
        'special_requirements',
        'notifications_sent',
        'metadata',
        'court_ids',
        'is_partial_court',
        'court_percentage',
    ];

    protected $casts = [
        'uuid' => 'string',
        'booking_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'duration_minutes' => 'integer',
        'released_at' => 'datetime',
        'requested_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'cost' => 'decimal:2',
        'payment_required' => 'boolean',
        'participants_count' => 'integer',
        'participant_list' => 'array',
        'priority' => 'integer',
        'notifications_sent' => 'array',
        'metadata' => 'array',
        'court_ids' => 'array',
        'is_partial_court' => 'boolean',
        'court_percentage' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($gymBooking) {
            if (empty($gymBooking->uuid)) {
                $gymBooking->uuid = (string) Str::uuid();
            }
        });
    }

    // ============================
    // RELATIONSHIPS
    // ============================

    public function gymTimeSlot(): BelongsTo
    {
        return $this->belongsTo(GymTimeSlot::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function bookedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booked_by_user_id');
    }

    public function originalTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'original_team_id');
    }

    public function substituteTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'substitute_team_id');
    }

    public function releasedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by_user_id');
    }

    public function confirmedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_user_id');
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }

    /**
     * Get the game associated with this booking.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(GymBookingRequest::class);
    }

    public function courts(): BelongsToMany
    {
        return $this->belongsToMany(GymHallCourt::class, 'gym_booking_courts')
            ->withTimestamps();
    }

    /**
     * Get the gym hall through the time slot.
     */
    public function gymHall(): HasOneThrough
    {
        return $this->hasOneThrough(
            GymHall::class,
            GymTimeSlot::class,
            'id',                // Foreign key on GymTimeSlot table
            'id', // Foreign key on GymHall table
            'gym_time_slot_id',  // Local key on GymBooking table
            'gym_hall_id'        // Local key on GymTimeSlot table
        );
    }

    public function pendingRequests(): HasMany
    {
        return $this->requests()->where('status', 'pending');
    }

    // ============================
    // SCOPES
    // ============================

    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('booking_date', '>=', now()->toDateString());
    }

    public function scopePast($query)
    {
        return $query->where('booking_date', '<', now()->toDateString());
    }

    public function scopeToday($query)
    {
        return $query->whereDate('booking_date', now());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('booking_date', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    public function scopeReleased($query)
    {
        return $query->where('status', 'released');
    }

    public function scopeAvailableForBooking($query)
    {
        return $query->where('status', 'released')
            ->where('booking_date', '>=', now()->toDateString());
    }

    public function scopeForCourt($query, $courtId)
    {
        return $query->whereHas('courts', function ($q) use ($courtId) {
            $q->where('gym_hall_courts.id', $courtId);
        });
    }

    public function scopeUsingCourts($query, array $courtIds)
    {
        return $query->whereHas('courts', function ($q) use ($courtIds) {
            $q->whereIn('gym_hall_courts.id', $courtIds);
        });
    }

    public function scopePartialCourt($query)
    {
        return $query->where('is_partial_court', true);
    }

    public function scopeFullCourt($query)
    {
        return $query->where('is_partial_court', false);
    }

    /**
     * Scope a query to only include game bookings.
     */
    public function scopeGames($query)
    {
        return $query->whereNotNull('game_id');
    }

    /**
     * Scope a query to only include training bookings.
     */
    public function scopeTrainings($query)
    {
        return $query->whereNull('game_id');
    }

    /**
     * Scope a query to order by priority (high priority first).
     */
    public function scopeByPriority($query)
    {
        return $query->orderByDesc('priority');
    }

    // ============================
    // ACCESSORS & MUTATORS
    // ============================

    public function getDateTimeAttribute(): string
    {
        return $this->booking_date->format('d.m.Y').' '.$this->start_time->format('H:i');
    }

    public function getTimeRangeAttribute(): string
    {
        return $this->start_time->format('H:i').' - '.$this->end_time->format('H:i');
    }

    public function getIsUpcomingAttribute(): bool
    {
        return $this->booking_date->gte(now()->toDateString());
    }

    public function getIsPastAttribute(): bool
    {
        return $this->booking_date->lt(now()->toDateString());
    }

    public function getIsTodayAttribute(): bool
    {
        return $this->booking_date->isToday();
    }

    public function getCanBeReleasedAttribute(): bool
    {
        return $this->status === 'reserved' &&
               $this->is_upcoming &&
               $this->gymTimeSlot->allows_substitution;
    }

    public function getCanBeCancelledAttribute(): bool
    {
        return in_array($this->status, ['reserved', 'confirmed', 'requested']) &&
               $this->is_upcoming;
    }

    public function getIsSubstituteBookingAttribute(): bool
    {
        return $this->booking_type === 'substitute' ||
               (! is_null($this->substitute_team_id) && $this->substitute_team_id !== $this->team_id);
    }

    public function getHasPendingRequestsAttribute(): bool
    {
        return $this->pendingRequests()->exists();
    }

    public function getHasCourtsAttribute(): bool
    {
        return $this->courts()->exists();
    }

    public function getCourtNamesAttribute(): string
    {
        if (! $this->has_courts) {
            return 'Alle Courts';
        }

        return $this->courts->pluck('court_name')->join(', ');
    }

    public function getCourtIdentifiersAttribute(): string
    {
        if (! $this->has_courts) {
            return 'Gesamt';
        }

        return $this->courts->pluck('court_identifier')->join(', ');
    }

    public function getDisplayCourtInfoAttribute(): string
    {
        if (! $this->has_courts) {
            return $this->gymHall?->name.' (Gesamt)';
        }

        return $this->gymHall?->name.' - '.$this->court_identifiers;
    }

    public function getIsMultiCourtBookingAttribute(): bool
    {
        return $this->courts()->count() > 1;
    }

    /**
     * Check if this booking is for a game.
     */
    public function getIsGameBookingAttribute(): bool
    {
        return $this->game_id !== null;
    }

    /**
     * Check if this booking is for training.
     */
    public function getIsTrainingBookingAttribute(): bool
    {
        return $this->game_id === null;
    }

    // ============================
    // HELPER METHODS
    // ============================

    public function releaseTime(User $releasedBy, ?string $reason = null): bool
    {
        if (! $this->can_be_released) {
            return false;
        }

        $this->update([
            'status' => 'released',
            'released_at' => now(),
            'released_by_user_id' => $releasedBy->id,
            'release_reason' => $reason,
            'original_team_id' => $this->team_id,
        ]);

        $this->notifyAvailableTeams();

        return true;
    }

    public function requestBooking(Team $requestingTeam, User $requestedBy, ?string $message = null, array $details = []): GymBookingRequest
    {
        return $this->requests()->create([
            'uuid' => Str::uuid(),
            'requesting_team_id' => $requestingTeam->id,
            'requested_by_user_id' => $requestedBy->id,
            'message' => $message,
            'purpose' => $details['purpose'] ?? null,
            'expected_participants' => $details['participants'] ?? null,
            'requested_equipment' => $details['equipment'] ?? null,
            'priority' => $details['priority'] ?? 'normal',
            'expires_at' => now()->addDays(2), // 2 Tage Zeit für Antwort
        ]);
    }

    public function confirmBooking(User $confirmedBy, ?Team $newTeam = null): bool
    {
        if (! in_array($this->status, ['released', 'requested'])) {
            return false;
        }

        $updateData = [
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'confirmed_by_user_id' => $confirmedBy->id,
        ];

        if ($newTeam) {
            $updateData['substitute_team_id'] = $newTeam->id;
            $updateData['team_id'] = $newTeam->id;
            $updateData['booking_type'] = 'substitute';
        }

        $this->update($updateData);

        // Alle offenen Anfragen ablehnen
        $this->pendingRequests()->update([
            'status' => 'rejected',
            'review_notes' => 'Automatically rejected - booking confirmed by other team',
            'reviewed_at' => now(),
        ]);

        return true;
    }

    public function cancelBooking(User $cancelledBy, ?string $reason = null): bool
    {
        if (! $this->can_be_cancelled) {
            return false;
        }

        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by_user_id' => $cancelledBy->id,
            'cancellation_reason' => $reason,
        ]);

        return true;
    }

    public function markAsCompleted(): void
    {
        if ($this->is_past && in_array($this->status, ['reserved', 'confirmed'])) {
            $this->update(['status' => 'completed']);
        }
    }

    public function markAsNoShow(): void
    {
        if ($this->is_past && in_array($this->status, ['reserved', 'confirmed'])) {
            $this->update(['status' => 'no_show']);
        }
    }

    public function getAvailableActions(User $user): array
    {
        $actions = [];

        // Check user permissions for this booking
        $userTeam = $user->teams()->where('team_id', $this->team_id)->first();
        $isTeamMember = ! is_null($userTeam);
        $isTrainerOrAssistant = $isTeamMember && in_array($userTeam->pivot->role ?? '', ['trainer', 'assistant_coach']);

        if ($this->can_be_released && $isTrainerOrAssistant) {
            $actions[] = 'release';
        }

        if ($this->can_be_cancelled && ($isTrainerOrAssistant || $user->id === $this->booked_by_user_id)) {
            $actions[] = 'cancel';
        }

        if ($this->status === 'released' && ! $isTeamMember) {
            $actions[] = 'request';
        }

        if ($this->has_pending_requests && $isTrainerOrAssistant) {
            $actions[] = 'review_requests';
        }

        return $actions;
    }

    public function notifyAvailableTeams(): void
    {
        // Get all teams in the same club except the original team
        $club = $this->gymTimeSlot->gymHall->club;
        $availableTeams = $club->teams()
            ->where('id', '!=', $this->original_team_id)
            ->where('is_active', true)
            ->get();

        foreach ($availableTeams as $team) {
            // Send notification to team coaches
            $coaches = $team->users()
                ->wherePivotIn('role', ['trainer', 'assistant_coach'])
                ->get();

            foreach ($coaches as $coach) {
                // Implement notification logic here
                // This could use Laravel's notification system
            }
        }

        // Update notification tracking
        $this->update([
            'notifications_sent' => array_merge($this->notifications_sent ?? [], [
                'release_notification' => [
                    'sent_at' => now(),
                    'teams_notified' => $availableTeams->pluck('id')->toArray(),
                ],
            ]),
        ]);
    }

    public function calculateCost(): float
    {
        $hourlyRate = $this->gymTimeSlot->cost_per_hour ?? $this->gymTimeSlot->gymHall->hourly_rate ?? 0;
        $hours = $this->duration_minutes / 60;

        // Adjust cost based on court percentage if partial court
        $courtMultiplier = $this->is_partial_court ? ($this->court_percentage / 100) : 1.0;

        return round($hourlyRate * $hours * $courtMultiplier, 2);
    }

    // ============================
    // COURT MANAGEMENT METHODS
    // ============================

    public function assignCourts(array $courtIds): bool
    {
        try {
            // Validate courts belong to the same gym hall
            $validCourts = GymHallCourt::whereIn('id', $courtIds)
                ->where('gym_hall_id', $this->gymTimeSlot->gym_hall_id)
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();

            if (count($validCourts) !== count($courtIds)) {
                return false; // Some courts are invalid
            }

            // Check for conflicts with existing bookings
            if ($this->hasCourtConflicts($courtIds)) {
                return false;
            }

            // Sync courts
            $this->courts()->sync($courtIds);

            // Update booking data
            $this->update([
                'court_ids' => $courtIds,
                'is_partial_court' => count($courtIds) < $this->gymTimeSlot->gymHall->court_count,
                'court_percentage' => (count($courtIds) / max($this->gymTimeSlot->gymHall->court_count, 1)) * 100,
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function hasCourtConflict(GymBooking $otherBooking): bool
    {
        if ($this->id === $otherBooking->id) {
            return false;
        }

        // Check date and time overlap
        if (! $this->hasTimeOverlap($otherBooking)) {
            return false;
        }

        // Check court overlap
        $myCourts = $this->courts->pluck('id')->toArray();
        $otherCourts = $otherBooking->courts->pluck('id')->toArray();

        return ! empty(array_intersect($myCourts, $otherCourts));
    }

    private function hasTimeOverlap(GymBooking $otherBooking): bool
    {
        if ($this->booking_date->ne($otherBooking->booking_date)) {
            return false;
        }

        $myStart = $this->start_time;
        $myEnd = $this->end_time;
        $otherStart = $otherBooking->start_time;
        $otherEnd = $otherBooking->end_time;

        return $myStart->lt($otherEnd) && $myEnd->gt($otherStart);
    }

    private function hasCourtConflicts(array $courtIds): bool
    {
        $conflictingBookings = GymBooking::whereHas('courts', function ($query) use ($courtIds) {
            $query->whereIn('gym_hall_courts.id', $courtIds);
        })
            ->where('booking_date', $this->booking_date)
            ->where('id', '!=', $this->id)
            ->where(function ($query) {
                $myStart = $this->start_time;
                $myEnd = $this->end_time;
                $query->where(function ($q) use ($myStart, $myEnd) {
                    $q->where('start_time', '<', $myEnd)
                        ->where('end_time', '>', $myStart);
                });
            })
            ->whereIn('status', ['reserved', 'confirmed'])
            ->exists();

        return $conflictingBookings;
    }

    public function getAvailableCourts(): \Illuminate\Database\Eloquent\Collection
    {
        $gymHall = $this->gymTimeSlot->gymHall;

        return $gymHall->getAvailableCourtsByTime(
            $this->booking_date->setTimeFrom($this->start_time),
            $this->duration_minutes
        );
    }

    public function canUseAllCourts(): bool
    {
        $gymHall = $this->gymTimeSlot->gymHall;

        return $gymHall->supports_parallel_bookings;
    }

    public function optimizeCourtSelection(): array
    {
        if (! $this->canUseAllCourts()) {
            return [];
        }

        $availableCourts = $this->getAvailableCourts();
        $preferredCourts = $this->gymTimeSlot->getPreferredCourts();

        if (empty($preferredCourts)) {
            // Return first available court if no preferences
            return $availableCourts->take(1)->pluck('id')->toArray();
        }

        // Filter by preferences and availability
        $optimalCourts = $availableCourts->filter(function ($court) use ($preferredCourts) {
            return in_array($court->id, $preferredCourts);
        });

        if ($optimalCourts->isEmpty()) {
            // Fall back to any available court
            return $availableCourts->take(1)->pluck('id')->toArray();
        }

        return $optimalCourts->pluck('id')->toArray();
    }

    // ============================
    // ACTIVITY LOG
    // ============================

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status', 'team_id', 'substitute_team_id', 'released_at',
                'confirmed_at', 'cancelled_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
