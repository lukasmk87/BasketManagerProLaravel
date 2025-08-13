<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\Concerns\BelongsToTenant;

class EmergencyIncident extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, BelongsToTenant;

    protected $fillable = [
        'incident_id',
        'player_id',
        'team_id',
        'game_id',
        'training_session_id',
        'incident_type',
        'severity',
        'description',
        'occurred_at',
        'location',
        'coordinates',
        'reported_by_user_id',
        'reported_at',
        'contacts_notified',
        'response_actions',
        'personnel_involved',
        'medical_attention_required',
        'ambulance_called',
        'hospital_name',
        'medical_notes',
        'vital_signs',
        'status',
        'resolution_notes',
        'resolved_at',
        'resolved_by_user_id',
        'photos',
        'documents',
        'witness_statements',
        'insurance_claim_filed',
        'insurance_claim_number',
        'legal_action_required',
        'legal_notes',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'reported_at' => 'datetime',
        'resolved_at' => 'datetime',
        'coordinates' => 'array',
        'contacts_notified' => 'array',
        'response_actions' => 'array',
        'personnel_involved' => 'array',
        'vital_signs' => 'array',
        'photos' => 'array',
        'documents' => 'array',
        'witness_statements' => 'array',
        'medical_attention_required' => 'boolean',
        'ambulance_called' => 'boolean',
        'insurance_claim_filed' => 'boolean',
        'legal_action_required' => 'boolean',
        // Encrypted fields for sensitive data
        'medical_notes' => 'encrypted',
        'legal_notes' => 'encrypted',
    ];

    protected $hidden = [
        'medical_notes',
        'legal_notes',
        'vital_signs',
    ];

    // Relationships
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function trainingSession(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeMedical($query)
    {
        return $query->whereIn('incident_type', ['injury', 'medical_emergency']);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('occurred_at', '>=', now()->subDays($days));
    }

    // Accessors & Mutators
    public function incidentSeverityLevel(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match($this->severity) {
                    'critical' => 4,
                    'severe' => 3,
                    'moderate' => 2,
                    'minor' => 1,
                    default => 0
                };
            }
        );
    }

    public function isResolved(): Attribute
    {
        return Attribute::make(
            get: fn() => in_array($this->status, ['resolved', 'closed'])
        );
    }

    public function responseTime(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->resolved_at) {
                    return null;
                }
                return $this->occurred_at->diffInMinutes($this->resolved_at);
            }
        );
    }

    public function requiresFollowUp(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->severity === 'critical' || 
                       $this->ambulance_called || 
                       $this->legal_action_required ||
                       $this->insurance_claim_filed;
            }
        );
    }

    // Helper Methods
    public function generateIncidentId(): string
    {
        $year = now()->year;
        $count = static::whereYear('created_at', $year)->count() + 1;
        return sprintf('EMG-%d-%03d', $year, $count);
    }

    public function addResponseAction(string $action, ?User $user = null): void
    {
        $actions = $this->response_actions ?? [];
        $actions[] = [
            'action' => $action,
            'timestamp' => now(),
            'user_id' => $user?->id,
            'user_name' => $user?->name,
        ];

        $this->update(['response_actions' => $actions]);
    }

    public function notifyEmergencyContact(EmergencyContact $contact, string $method = 'phone'): void
    {
        $notified = $this->contacts_notified ?? [];
        $notified[] = [
            'contact_id' => $contact->id,
            'contact_name' => $contact->contact_name,
            'contact_phone' => $contact->phone_number,
            'method' => $method,
            'notified_at' => now(),
        ];

        $this->update(['contacts_notified' => $notified]);
        
        // Log the contact attempt
        $contact->logContactAttempt('emergency_incident', [
            'incident_id' => $this->incident_id,
            'severity' => $this->severity,
            'method' => $method,
        ]);
    }

    public function addWitnessStatement(string $statement, ?User $witness = null): void
    {
        $statements = $this->witness_statements ?? [];
        $statements[] = [
            'statement' => $statement,
            'witness_id' => $witness?->id,
            'witness_name' => $witness?->name,
            'recorded_at' => now(),
        ];

        $this->update(['witness_statements' => $statements]);
    }

    public function recordVitalSigns(array $vitals): void
    {
        $vitalSigns = $this->vital_signs ?? [];
        $vitalSigns[] = array_merge($vitals, [
            'recorded_at' => now(),
            'recorded_by' => auth()->user()?->name,
        ]);

        $this->update(['vital_signs' => $vitalSigns]);
    }

    public function attachPhoto(string $photoPath, ?string $description = null): void
    {
        $photos = $this->photos ?? [];
        $photos[] = [
            'path' => $photoPath,
            'description' => $description,
            'uploaded_at' => now(),
            'uploaded_by' => auth()->user()?->name,
        ];

        $this->update(['photos' => $photos]);
    }

    public function attachDocument(string $documentPath, string $type, ?string $description = null): void
    {
        $documents = $this->documents ?? [];
        $documents[] = [
            'path' => $documentPath,
            'type' => $type,
            'description' => $description,
            'uploaded_at' => now(),
            'uploaded_by' => auth()->user()?->name,
        ];

        $this->update(['documents' => $documents]);
    }

    public function markAsResolved(string $resolutionNotes, ?User $resolvedBy = null): void
    {
        $this->update([
            'status' => 'resolved',
            'resolution_notes' => $resolutionNotes,
            'resolved_at' => now(),
            'resolved_by_user_id' => $resolvedBy?->id ?? auth()->id(),
        ]);
    }

    public function escalateSeverity(string $newSeverity, string $reason): void
    {
        $oldSeverity = $this->severity;
        $this->update(['severity' => $newSeverity]);

        $this->addResponseAction(
            "Severity escalated from {$oldSeverity} to {$newSeverity}. Reason: {$reason}",
            auth()->user()
        );
    }

    public function getIncidentSummary(): array
    {
        return [
            'incident_id' => $this->incident_id,
            'player_name' => $this->player->full_name,
            'team_name' => $this->team->name,
            'incident_type' => $this->incident_type,
            'severity' => $this->severity,
            'occurred_at' => $this->occurred_at->toDateTimeString(),
            'location' => $this->location,
            'status' => $this->status,
            'reported_by' => $this->reportedBy->name,
            'contacts_notified_count' => count($this->contacts_notified ?? []),
            'response_time_minutes' => $this->response_time,
            'requires_follow_up' => $this->requires_follow_up,
        ];
    }

    // Activity Log Configuration
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'incident_id', 'incident_type', 'severity', 'status',
                'medical_attention_required', 'ambulance_called',
                'insurance_claim_filed', 'legal_action_required'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Emergency incident {$eventName}: {$this->incident_id}")
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }

    // Boot method to auto-generate incident ID
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($incident) {
            if (empty($incident->incident_id)) {
                $incident->incident_id = $incident->generateIncidentId();
            }
            if (empty($incident->reported_at)) {
                $incident->reported_at = now();
            }
        });
    }
}