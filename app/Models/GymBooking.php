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

class GymBooking extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid',
        'gym_time_slot_id',
        'team_id',
        'booked_by_user_id',
        'booking_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'status',
        'booking_type',
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
        'notifications_sent' => 'array',
        'metadata' => 'array',
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

    public function requests(): HasMany
    {
        return $this->hasMany(GymBookingRequest::class);
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
            now()->endOfWeek()
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

    // ============================
    // ACCESSORS & MUTATORS
    // ============================

    public function getDateTimeAttribute(): string
    {
        return $this->booking_date->format('d.m.Y') . ' ' . $this->start_time->format('H:i');
    }

    public function getTimeRangeAttribute(): string
    {
        return $this->start_time->format('H:i') . ' - ' . $this->end_time->format('H:i');
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
               (!is_null($this->substitute_team_id) && $this->substitute_team_id !== $this->team_id);
    }

    public function getHasPendingRequestsAttribute(): bool
    {
        return $this->pendingRequests()->exists();
    }

    // ============================
    // HELPER METHODS
    // ============================

    public function releaseTime(User $releasedBy, string $reason = null): bool
    {
        if (!$this->can_be_released) {
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

    public function requestBooking(Team $requestingTeam, User $requestedBy, string $message = null, array $details = []): GymBookingRequest
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

    public function confirmBooking(User $confirmedBy, Team $newTeam = null): bool
    {
        if (!in_array($this->status, ['released', 'requested'])) {
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

    public function cancelBooking(User $cancelledBy, string $reason = null): bool
    {
        if (!$this->can_be_cancelled) {
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
        $isTeamMember = !is_null($userTeam);
        $isTrainerOrAssistant = $isTeamMember && in_array($userTeam->pivot->role ?? '', ['trainer', 'assistant_trainer']);

        if ($this->can_be_released && $isTrainerOrAssistant) {
            $actions[] = 'release';
        }

        if ($this->can_be_cancelled && ($isTrainerOrAssistant || $user->id === $this->booked_by_user_id)) {
            $actions[] = 'cancel';
        }

        if ($this->status === 'released' && !$isTeamMember) {
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
                ->wherePivotIn('role', ['trainer', 'assistant_trainer'])
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
                ]
            ])
        ]);
    }

    public function calculateCost(): float
    {
        $hourlyRate = $this->gymTimeSlot->cost_per_hour ?? $this->gymTimeSlot->gymHall->hourly_rate ?? 0;
        $hours = $this->duration_minutes / 60;
        
        return round($hourlyRate * $hours, 2);
    }

    // ============================
    // ACTIVITY LOG
    // ============================

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status', 'team_id', 'substitute_team_id', 'released_at',
                'confirmed_at', 'cancelled_at'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}