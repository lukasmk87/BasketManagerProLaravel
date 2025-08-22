<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Carbon\Carbon;

class GymTimeSlotTeamAssignment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'uuid',
        'gym_time_slot_id',
        'team_id',
        'day_of_week',
        'start_time',
        'end_time',
        'duration_minutes',
        'status',
        'notes',
        'assigned_by',
        'assigned_at',
        'valid_from',
        'valid_until',
        'metadata',
    ];

    protected $casts = [
        'uuid' => 'string',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'duration_minutes' => 'integer',
        'assigned_at' => 'datetime',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($assignment) {
            if (empty($assignment->uuid)) {
                $assignment->uuid = (string) Str::uuid();
            }

            if (empty($assignment->duration_minutes) && $assignment->start_time && $assignment->end_time) {
                $start = Carbon::createFromTimeString($assignment->start_time);
                $end = Carbon::createFromTimeString($assignment->end_time);
                $assignment->duration_minutes = $end->diffInMinutes($start);
            }

            if (empty($assignment->assigned_at)) {
                $assignment->assigned_at = now();
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

    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
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

    public function scopeInTimeRange($query, string $startTime, string $endTime)
    {
        return $query->where(function($q) use ($startTime, $endTime) {
            $q->where('start_time', '<', $endTime)
              ->where('end_time', '>', $startTime);
        });
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

    // ============================
    // HELPER METHODS
    // ============================

    public function getTimeRangeAttribute(): string
    {
        return $this->start_time->format('H:i') . ' - ' . $this->end_time->format('H:i');
    }

    public function getDayNameAttribute(): string
    {
        $dayNames = [
            'monday' => 'Montag',
            'tuesday' => 'Dienstag',
            'wednesday' => 'Mittwoch',
            'thursday' => 'Donnerstag',
            'friday' => 'Freitag',
            'saturday' => 'Samstag',
            'sunday' => 'Sonntag',
        ];

        return $dayNames[$this->day_of_week] ?? ucfirst($this->day_of_week);
    }

    public function overlapsWithTime(string $startTime, string $endTime): bool
    {
        $thisStart = $this->start_time->format('H:i');
        $thisEnd = $this->end_time->format('H:i');
        
        return $thisStart < $endTime && $thisEnd > $startTime;
    }

    public static function hasConflictForTeam(
        int $gymTimeSlotId,
        int $teamId,
        string $dayOfWeek,
        string $startTime,
        string $endTime,
        $excludeId = null
    ): bool {
        $query = static::where('gym_time_slot_id', $gymTimeSlotId)
            ->where('team_id', $teamId)
            ->where('day_of_week', $dayOfWeek)
            ->where('status', 'active')
            ->where(function($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime);
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public static function getConflictsForTimeSlot(
        int $gymTimeSlotId,
        string $dayOfWeek,
        string $startTime,
        string $endTime,
        $excludeId = null
    ): array {
        $query = static::where('gym_time_slot_id', $gymTimeSlotId)
            ->where('day_of_week', $dayOfWeek)
            ->where('status', 'active')
            ->where(function($q) use ($startTime, $endTime) {
                $q->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime);
            })
            ->with(['team']);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->get()->map(function ($assignment) {
            return [
                'id' => $assignment->id,
                'team_name' => $assignment->team->name,
                'time_range' => $assignment->time_range,
                'start_time' => $assignment->start_time->format('H:i'),
                'end_time' => $assignment->end_time->format('H:i'),
            ];
        })->toArray();
    }

    // ============================
    // ACTIVITY LOG
    // ============================

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'team_id', 'day_of_week', 'start_time', 'end_time', 'status'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
