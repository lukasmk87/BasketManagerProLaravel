<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SecurityEvent extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'event_id',
        'event_type',
        'severity',
        'status',
        'description',
        'event_data',
        'occurred_at',
        'source_ip',
        'user_agent',
        'request_uri',
        'request_method',
        'user_id',
        'session_id',
        'affected_resource',
        'request_headers',
        'request_payload',
        'detection_method',
        'detector_name',
        'confidence_score',
        'automated_actions',
        'assigned_to_user_id',
        'resolved_at',
        'resolution_notes',
        'requires_notification',
        'notified_users',
        'requires_investigation',
        'investigation_notes',
    ];

    protected $casts = [
        'event_data' => 'array',
        'occurred_at' => 'datetime',
        'resolved_at' => 'datetime',
        'request_headers' => 'array',
        'request_payload' => 'array',
        'automated_actions' => 'array',
        'notified_users' => 'array',
        'requires_notification' => 'boolean',
        'requires_investigation' => 'boolean',
        'confidence_score' => 'decimal:4',
    ];

    protected $hidden = [
        'request_headers',
        'request_payload',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeHigh($query)
    {
        return $query->whereIn('severity', ['critical', 'high']);
    }

    public function scopeUnresolved($query)
    {
        return $query->whereIn('status', ['active', 'investigating']);
    }

    public function scopeEmergencyRelated($query)
    {
        return $query->whereIn('event_type', [
            'emergency_access_misuse',
            'emergency_access_anomaly'
        ]);
    }

    public function scopeGdprRelated($query)
    {
        return $query->whereIn('event_type', [
            'gdpr_violation',
            'gdpr_compliance_violation'
        ]);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('occurred_at', '>=', now()->subHours($hours));
    }

    // Helper Methods
    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    public function isResolved(): bool
    {
        return in_array($this->status, ['resolved', 'false_positive']);
    }

    public function isEmergencyRelated(): bool
    {
        return in_array($this->event_type, [
            'emergency_access_misuse',
            'emergency_access_anomaly'
        ]);
    }

    public function isGdprRelated(): bool
    {
        return in_array($this->event_type, [
            'gdpr_violation',
            'gdpr_compliance_violation'
        ]);
    }

    public function requiresImmedateAction(): bool
    {
        return $this->severity === 'critical' && !$this->isResolved();
    }

    public function getTimeSinceOccurred(): string
    {
        return $this->occurred_at->diffForHumans();
    }

    public function getResolutionTime(): ?string
    {
        if (!$this->resolved_at || !$this->occurred_at) {
            return null;
        }

        return $this->occurred_at->diffForHumans($this->resolved_at, true);
    }

    public function markAsResolved(string $notes = null, User $resolvedBy = null): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolution_notes' => $notes,
            'assigned_to_user_id' => $resolvedBy?->id ?? auth()->id(),
        ]);
    }

    public function markAsFalsePositive(string $notes = null): void
    {
        $this->update([
            'status' => 'false_positive',
            'resolved_at' => now(),
            'resolution_notes' => $notes ?: 'Marked as false positive',
        ]);
    }

    public function assignTo(User $user): void
    {
        $this->update([
            'assigned_to_user_id' => $user->id,
            'status' => $this->status === 'active' ? 'investigating' : $this->status,
        ]);
    }

    public function generateEventId(): string
    {
        $prefix = match($this->event_type) {
            'emergency_access_misuse', 'emergency_access_anomaly' => 'EMG',
            'gdpr_violation', 'gdpr_compliance_violation' => 'GDP',
            'authentication_failure', 'authorization_violation' => 'AUTH',
            default => 'SEC'
        };

        return $prefix . '-' . date('Y') . '-' . str_pad(self::count() + 1, 6, '0', STR_PAD_LEFT);
    }

    public function getSeverityColor(): string
    {
        return match($this->severity) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'green',
            default => 'gray'
        };
    }

    public function getSeverityIcon(): string
    {
        return match($this->severity) {
            'critical' => '🚨',
            'high' => '⚠️',
            'medium' => '🔶',
            'low' => '🟢',
            default => '⚪'
        };
    }

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status', 'severity', 'assigned_to_user_id', 
                'resolved_at', 'resolution_notes'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Security event {$eventName}")
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }

    // Boot method for generating event_id
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (!$model->event_id) {
                $model->event_id = $model->generateEventId();
            }
            
            if (!$model->occurred_at) {
                $model->occurred_at = now();
            }
        });
    }
}
