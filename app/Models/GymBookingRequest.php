<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Carbon\Carbon;

class GymBookingRequest extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid',
        'gym_booking_id',
        'requesting_team_id',
        'requested_by_user_id',
        'message',
        'purpose',
        'expected_participants',
        'requested_equipment',
        'priority',
        'status',
        'expires_at',
        'reviewed_by_user_id',
        'reviewed_at',
        'review_notes',
        'rejection_reason',
        'auto_approved',
        'approval_conditions',
        'notifications_sent',
        'metadata',
    ];

    protected $casts = [
        'uuid' => 'string',
        'expected_participants' => 'integer',
        'requested_equipment' => 'array',
        'expires_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'auto_approved' => 'boolean',
        'approval_conditions' => 'array',
        'notifications_sent' => 'array',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($gymBookingRequest) {
            if (empty($gymBookingRequest->uuid)) {
                $gymBookingRequest->uuid = (string) Str::uuid();
            }

            // Set default expiration if not provided
            if (empty($gymBookingRequest->expires_at)) {
                $gymBookingRequest->expires_at = now()->addDays(2);
            }
        });
    }

    // ============================
    // RELATIONSHIPS
    // ============================

    public function gymBooking(): BelongsTo
    {
        return $this->belongsTo(GymBooking::class);
    }

    public function requestingTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'requesting_team_id');
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    /**
     * Get the team alias (for compatibility).
     */
    public function team(): BelongsTo
    {
        return $this->requestingTeam();
    }

    /**
     * Get the time slot through the gym booking.
     */
    public function timeSlot(): HasOneThrough
    {
        return $this->hasOneThrough(
            GymTimeSlot::class,
            GymBooking::class,
            'id',              // Foreign key on GymBooking table
            'id',              // Foreign key on GymTimeSlot table  
            'gym_booking_id',  // Local key on GymBookingRequest table
            'gym_time_slot_id' // Local key on GymBooking table
        );
    }

    /**
     * Get the gym hall through the booking and time slot.
     */
    public function gymHall()
    {
        return $this->gymBooking->gymTimeSlot->gymHall ?? null;
    }

    // ============================
    // SCOPES
    // ============================

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeForTeam($query, $teamId)
    {
        return $query->where('requesting_team_id', $teamId);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
                    ->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'pending')
                    ->where('expires_at', '>=', now());
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    // ============================
    // ACCESSORS & MUTATORS
    // ============================

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast() && $this->status === 'pending';
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending' && !$this->is_expired;
    }

    public function getIsApprovedAttribute(): bool
    {
        return $this->status === 'approved';
    }

    public function getIsRejectedAttribute(): bool
    {
        return $this->status === 'rejected';
    }

    public function getCanBeReviewedAttribute(): bool
    {
        return $this->status === 'pending' && !$this->is_expired;
    }

    public function getTimeUntilExpirationAttribute(): ?string
    {
        if (!$this->expires_at || $this->is_expired || $this->status !== 'pending') {
            return null;
        }

        return $this->expires_at->diffForHumans();
    }

    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            'low' => 'Niedrig',
            'normal' => 'Normal',
            'high' => 'Hoch',
            'urgent' => 'Dringend',
            default => 'Normal'
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => $this->is_expired ? 'Abgelaufen' : 'Wartend',
            'approved' => 'Genehmigt',
            'rejected' => 'Abgelehnt',
            'cancelled' => 'Storniert',
            'expired' => 'Abgelaufen',
            default => 'Unbekannt'
        };
    }

    // ============================
    // HELPER METHODS
    // ============================

    public function approve(User $reviewedBy, string $reviewNotes = null, array $conditions = []): bool
    {
        if (!$this->can_be_reviewed) {
            return false;
        }

        $this->update([
            'status' => 'approved',
            'reviewed_by_user_id' => $reviewedBy->id,
            'reviewed_at' => now(),
            'review_notes' => $reviewNotes,
            'approval_conditions' => $conditions,
        ]);

        // Confirm the booking for the requesting team
        $this->gymBooking->confirmBooking($reviewedBy, $this->requestingTeam);

        $this->notifyRequestingTeam('approved');

        return true;
    }

    public function reject(User $reviewedBy, string $rejectionReason, string $reviewNotes = null): bool
    {
        if (!$this->can_be_reviewed) {
            return false;
        }

        $this->update([
            'status' => 'rejected',
            'reviewed_by_user_id' => $reviewedBy->id,
            'reviewed_at' => now(),
            'rejection_reason' => $rejectionReason,
            'review_notes' => $reviewNotes,
        ]);

        $this->notifyRequestingTeam('rejected');

        return true;
    }

    public function cancel(User $cancelledBy = null): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $this->update([
            'status' => 'cancelled',
            'reviewed_by_user_id' => $cancelledBy?->id,
            'reviewed_at' => now(),
            'review_notes' => 'Cancelled by requesting team',
        ]);

        return true;
    }

    public function expire(): bool
    {
        if ($this->status !== 'pending' || !$this->is_expired) {
            return false;
        }

        $this->update([
            'status' => 'expired',
            'reviewed_at' => now(),
            'review_notes' => 'Automatically expired due to timeout',
        ]);

        $this->notifyRequestingTeam('expired');

        return true;
    }

    public function extendExpiration(int $days = 2): void
    {
        if ($this->status === 'pending') {
            $this->update([
                'expires_at' => now()->addDays($days),
                'metadata' => array_merge($this->metadata ?? [], [
                    'expiration_extended' => [
                        'extended_at' => now(),
                        'extended_by_days' => $days,
                        'previous_expiration' => $this->expires_at,
                    ]
                ])
            ]);
        }
    }

    public function canBeApprovedBy(User $user): bool
    {
        if (!$this->can_be_reviewed) {
            return false;
        }

        // Check if user is trainer or assistant trainer of the original team
        $originalTeam = $this->gymBooking->originalTeam ?? $this->gymBooking->team;
        
        return $originalTeam->users()
            ->wherePivotIn('role', ['trainer', 'assistant_trainer'])
            ->where('user_id', $user->id)
            ->exists();
    }

    public function getRequiredApprovers(): array
    {
        $originalTeam = $this->gymBooking->originalTeam ?? $this->gymBooking->team;
        
        return $originalTeam->users()
            ->wherePivotIn('role', ['trainer', 'assistant_trainer'])
            ->pluck('users.id', 'users.name')
            ->toArray();
    }

    public function notifyRequestingTeam(string $decision): void
    {
        $teamMembers = $this->requestingTeam->users()
            ->wherePivotIn('role', ['trainer', 'assistant_trainer'])
            ->get();

        foreach ($teamMembers as $member) {
            // Implement notification logic here
            // This could use Laravel's notification system
        }

        // Track notification
        $notifications = $this->notifications_sent ?? [];
        $notifications[$decision . '_notification'] = [
            'sent_at' => now(),
            'decision' => $decision,
            'notified_users' => $teamMembers->pluck('id')->toArray(),
        ];

        $this->update(['notifications_sent' => $notifications]);
    }

    public function notifyOriginalTeam(): void
    {
        $originalTeam = $this->gymBooking->originalTeam ?? $this->gymBooking->team;
        $teamMembers = $originalTeam->users()
            ->wherePivotIn('role', ['trainer', 'assistant_trainer'])
            ->get();

        foreach ($teamMembers as $member) {
            // Implement notification logic here
        }

        // Track notification
        $notifications = $this->notifications_sent ?? [];
        $notifications['request_notification'] = [
            'sent_at' => now(),
            'notified_users' => $teamMembers->pluck('id')->toArray(),
        ];

        $this->update(['notifications_sent' => $notifications]);
    }

    public function getConflictingRequests()
    {
        return static::where('gym_booking_id', $this->gym_booking_id)
                    ->where('id', '!=', $this->id)
                    ->where('status', 'pending')
                    ->get();
    }

    public function hasConflicts(): bool
    {
        return $this->getConflictingRequests()->isNotEmpty();
    }

    // ============================
    // STATIC METHODS
    // ============================

    public static function expireOldRequests(): int
    {
        $expired = static::expired()->get();
        $count = $expired->count();

        foreach ($expired as $request) {
            $request->expire();
        }

        return $count;
    }

    public static function getRequestsForTeam(Team $team, $status = null)
    {
        $query = static::where('requesting_team_id', $team->id);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->with(['gymBooking.gymTimeSlot.gymHall', 'requestedByUser'])
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    public static function getPendingRequestsForReview(Team $team)
    {
        return static::whereHas('gymBooking', function ($query) use ($team) {
                        $query->where('team_id', $team->id)
                              ->orWhere('original_team_id', $team->id);
                    })
                    ->pending()
                    ->with(['requestingTeam', 'requestedByUser', 'gymBooking.gymTimeSlot.gymHall'])
                    ->orderBy('priority', 'desc')
                    ->orderBy('created_at', 'asc')
                    ->get();
    }

    // ============================
    // ACTIVITY LOG
    // ============================

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status', 'reviewed_by_user_id', 'reviewed_at',
                'rejection_reason', 'approval_conditions'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}