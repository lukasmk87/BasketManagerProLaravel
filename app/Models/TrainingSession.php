<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Carbon\Carbon;

class TrainingSession extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'team_id',
        'trainer_id',
        'assistant_trainer_id',
        'title',
        'description',
        'scheduled_at',
        'actual_start_time',
        'actual_end_time',
        'planned_duration',
        'actual_duration',
        'venue',
        'venue_address',
        'court_type',
        'session_type',
        'focus_areas',
        'intensity_level',
        'max_participants',
        'booking_deadline_hours',
        'allow_registrations',
        'enable_waitlist',
        'status',
        'weather_conditions',
        'temperature',
        'weather_appropriate',
        'required_equipment',
        'special_requirements',
        'safety_notes',
        'overall_rating',
        'trainer_notes',
        'session_feedback',
        'goals_achieved',
        'is_mandatory',
        'allows_late_arrival',
        'requires_medical_clearance',
        'notification_settings',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'actual_start_time' => 'datetime',
        'actual_end_time' => 'datetime',
        'planned_duration' => 'integer',
        'actual_duration' => 'integer',
        'focus_areas' => 'array',
        'max_participants' => 'integer',
        'booking_deadline_hours' => 'integer',
        'allow_registrations' => 'boolean',
        'enable_waitlist' => 'boolean',
        'temperature' => 'decimal:1',
        'weather_appropriate' => 'boolean',
        'required_equipment' => 'array',
        'overall_rating' => 'integer',
        'goals_achieved' => 'array',
        'is_mandatory' => 'boolean',
        'allows_late_arrival' => 'boolean',
        'requires_medical_clearance' => 'boolean',
        'notification_settings' => 'array',
    ];

    // Relationships
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function assistantTrainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assistant_trainer_id');
    }

    public function drills(): BelongsToMany
    {
        return $this->belongsToMany(Drill::class, 'training_drills')
                    ->withPivot([
                        'order_in_session', 'planned_duration', 'actual_duration',
                        'participants_count', 'participating_players',
                        'specific_instructions', 'modifications',
                        'success_metrics', 'drill_rating', 'performance_notes',
                        'trainer_observations', 'status', 'skip_reason',
                        'goals_achieved', 'player_difficulty_rating',
                        'player_enjoyment_rating', 'player_feedback',
                        'start_time', 'end_time'
                    ])
                    ->withTimestamps()
                    ->orderBy('training_drills.order_in_session');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(TrainingAttendance::class);
    }

    public function playerPerformances(): HasMany
    {
        return $this->hasMany(PlayerTrainingPerformance::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(TrainingRegistration::class);
    }

    public function plays(): BelongsToMany
    {
        return $this->belongsToMany(Play::class, 'training_session_plays')
            ->withPivot(['order', 'notes'])
            ->withTimestamps()
            ->orderByPivot('order');
    }

    // Scopes
    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>', now())
                    ->where('status', 'scheduled');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('scheduled_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeByTrainer($query, int $trainerId)
    {
        return $query->where('trainer_id', $trainerId)
                    ->orWhere('assistant_trainer_id', $trainerId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('session_type', $type);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Accessors
    public function duration(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->actual_duration ?? $this->planned_duration,
        );
    }

    public function isCompleted(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === 'completed',
        );
    }

    public function isUpcoming(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->scheduled_at > now() && $this->status === 'scheduled',
        );
    }

    public function isInProgress(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status === 'in_progress',
        );
    }

    public function attendanceRate(): Attribute
    {
        return Attribute::make(
            get: function () {
                $totalPlayers = $this->team->activePlayers()->count();
                $attendees = $this->attendance()->where('status', 'present')->count();
                
                return $totalPlayers > 0 ? round(($attendees / $totalPlayers) * 100, 1) : 0;
            },
        );
    }

    public function averageDrillRating(): Attribute
    {
        return Attribute::make(
            get: function () {
                $ratings = $this->drills()
                    ->wherePivotNotNull('drill_rating')
                    ->get()
                    ->pluck('pivot.drill_rating');
                    
                return $ratings->count() > 0 ? round($ratings->average(), 1) : null;
            },
        );
    }

    // Helper Methods
    public function canStart(): bool
    {
        return $this->status === 'scheduled' && 
               $this->scheduled_at <= now()->addMinutes(15);
    }

    public function canComplete(): bool
    {
        return $this->status === 'in_progress';
    }

    public function start(): void
    {
        if (!$this->canStart()) {
            throw new \Exception('Training session cannot be started');
        }

        $this->update([
            'status' => 'in_progress',
            'actual_start_time' => now(),
        ]);
    }

    public function complete(): void
    {
        if (!$this->canComplete()) {
            throw new \Exception('Training session cannot be completed');
        }

        $this->update([
            'status' => 'completed',
            'actual_end_time' => now(),
            'actual_duration' => $this->actual_start_time 
                ? $this->actual_start_time->diffInMinutes(now()) 
                : null,
        ]);
    }

    public function addDrill(Drill $drill, array $pivotData = []): void
    {
        $defaultOrder = $this->drills()->count() + 1;
        
        $this->drills()->attach($drill->id, array_merge([
            'order_in_session' => $defaultOrder,
            'planned_duration' => $drill->estimated_duration,
            'status' => 'planned',
        ], $pivotData));
    }

    public function removeDrill(Drill $drill): void
    {
        $this->drills()->detach($drill->id);
        
        // Reorder remaining drills
        $this->reorderDrills();
    }

    public function reorderDrills(): void
    {
        $drills = $this->drills()->orderBy('training_drills.order_in_session')->get();
        
        foreach ($drills as $index => $drill) {
            $this->drills()->updateExistingPivot($drill->id, [
                'order_in_session' => $index + 1
            ]);
        }
    }

    public function calculateTotalPlannedDuration(): int
    {
        return $this->drills()->sum('training_drills.planned_duration');
    }

    public function calculateTotalActualDuration(): int
    {
        return $this->drills()
            ->wherePivotNotNull('actual_duration')
            ->sum('training_drills.actual_duration');
    }

    // Booking-related methods
    public function getRegistrationDeadline(): Carbon
    {
        return $this->scheduled_at->subHours($this->booking_deadline_hours ?? 2);
    }

    public function isRegistrationOpen(): bool
    {
        if (!$this->allow_registrations) {
            return false;
        }

        if ($this->status !== 'scheduled') {
            return false;
        }

        return now()->isBefore($this->getRegistrationDeadline());
    }

    public function hasCapacity(?int $additionalParticipants = 1): bool
    {
        if (!$this->max_participants) {
            return true; // No limit set
        }

        $currentRegistrations = $this->registrations()
            ->whereIn('status', ['registered', 'confirmed'])
            ->count();

        return ($currentRegistrations + $additionalParticipants) <= $this->max_participants;
    }

    public function getAvailableSpots(): int
    {
        if (!$this->max_participants) {
            return 999; // No limit
        }

        $currentRegistrations = $this->registrations()
            ->whereIn('status', ['registered', 'confirmed'])
            ->count();

        return max(0, $this->max_participants - $currentRegistrations);
    }

    public function getWaitlistCount(): int
    {
        return $this->registrations()->where('status', 'waitlist')->count();
    }

    public function getTotalRegistrations(): int
    {
        return $this->registrations()
            ->whereIn('status', ['registered', 'confirmed', 'waitlist'])
            ->count();
    }

    public function getConfirmedParticipants(): int
    {
        return $this->registrations()->where('status', 'confirmed')->count();
    }

    public function getPendingRegistrations(): int
    {
        return $this->registrations()->where('status', 'registered')->count();
    }

    public function isPlayerRegistered(int $playerId): bool
    {
        return $this->registrations()
            ->where('player_id', $playerId)
            ->whereIn('status', ['registered', 'confirmed', 'waitlist'])
            ->exists();
    }

    public function getPlayerRegistration(int $playerId): ?TrainingRegistration
    {
        return $this->registrations()
            ->where('player_id', $playerId)
            ->first();
    }

    public function registerPlayer(int $playerId, ?string $notes = null): TrainingRegistration
    {
        return TrainingRegistration::createRegistration($this->id, $playerId, $notes);
    }

    public function getRegistrationSummary(): array
    {
        return [
            'total_registrations' => $this->getTotalRegistrations(),
            'confirmed_participants' => $this->getConfirmedParticipants(),
            'pending_registrations' => $this->getPendingRegistrations(),
            'waitlist_count' => $this->getWaitlistCount(),
            'available_spots' => $this->getAvailableSpots(),
            'has_capacity' => $this->hasCapacity(),
            'registration_open' => $this->isRegistrationOpen(),
            'registration_deadline' => $this->getRegistrationDeadline()->format('d.m.Y H:i'),
            'hours_until_deadline' => now()->diffInHours($this->getRegistrationDeadline(), false),
            'max_participants' => $this->max_participants,
            'allow_registrations' => $this->allow_registrations,
            'enable_waitlist' => $this->enable_waitlist,
        ];
    }

    public function getParticipationStats(): array
    {
        $attendance = $this->attendance()->with('player')->get();
        
        return [
            'total_invited' => $this->team->activePlayers()->count(),
            'present' => $attendance->where('status', 'present')->count(),
            'absent' => $attendance->where('status', 'absent')->count(),
            'late' => $attendance->where('status', 'late')->count(),
            'excused' => $attendance->where('status', 'excused')->count(),
            'attendance_rate' => $this->attendance_rate,
        ];
    }

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status', 'actual_start_time', 'actual_end_time',
                'overall_rating', 'trainer_notes'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}