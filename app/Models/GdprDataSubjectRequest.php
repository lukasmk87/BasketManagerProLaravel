<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Carbon\Carbon;

class GdprDataSubjectRequest extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'request_id',
        'subject_type',
        'subject_id',
        'requested_by_user_id',
        'request_type',
        'request_description',
        'specific_data_requested',
        'received_at',
        'acknowledgment_sent_at',
        'deadline_date',
        'completed_at',
        'identity_verified',
        'identity_verified_at',
        'verified_by_user_id',
        'verification_documents',
        'verification_notes',
        'status',
        'response_summary',
        'data_provided',
        'actions_taken',
        'rejection_reason',
        'request_attachments',
        'response_files',
        'export_file_path',
        'communication_log',
        'last_contact_at',
        'requires_legal_review',
        'involves_third_parties',
        'third_party_details',
        'complexity_score',
        'appeal_filed',
        'appeal_filed_at',
        'appeal_details',
        'internal_notes',
        'assigned_to_user_id',
        'processing_log',
    ];

    protected $casts = [
        'specific_data_requested' => 'array',
        'received_at' => 'datetime',
        'acknowledgment_sent_at' => 'datetime',
        'deadline_date' => 'date',
        'completed_at' => 'datetime',
        'identity_verified' => 'boolean',
        'identity_verified_at' => 'datetime',
        'verification_documents' => 'array',
        'data_provided' => 'array',
        'actions_taken' => 'array',
        'request_attachments' => 'array',
        'response_files' => 'array',
        'communication_log' => 'array',
        'last_contact_at' => 'datetime',
        'requires_legal_review' => 'boolean',
        'involves_third_parties' => 'boolean',
        'third_party_details' => 'array',
        'complexity_score' => 'integer',
        'appeal_filed' => 'boolean',
        'appeal_filed_at' => 'datetime',
        'processing_log' => 'array',
    ];

    // Relationships
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->whereIn('status', ['received', 'verifying', 'processing']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('deadline_date', '<', now())
                     ->whereNotIn('status', ['completed', 'rejected']);
    }

    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->where('deadline_date', '<=', now()->addDays($days))
                     ->where('deadline_date', '>', now())
                     ->whereNotIn('status', ['completed', 'rejected']);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('request_type', $type);
    }

    public function scopeRequiringVerification($query)
    {
        return $query->where('identity_verified', false)
                     ->where('status', 'verifying');
    }

    public function scopeHighPriority($query)
    {
        return $query->where(function ($q) {
            $q->where('complexity_score', '>=', 4)
              ->orWhere('requires_legal_review', true)
              ->orWhere('involves_third_parties', true);
        });
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to_user_id', $userId);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to_user_id');
    }

    // Accessors
    public function isOverdue(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->deadline_date && $this->deadline_date->isPast() && 
                        !in_array($this->status, ['completed', 'rejected'])
        );
    }

    public function daysUntilDeadline(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->deadline_date) return null;
                return now()->diffInDays($this->deadline_date, false);
            }
        );
    }

    public function processingTime(): Attribute
    {
        return Attribute::make(
            get: function () {
                $endDate = $this->completed_at ?? now();
                return $this->received_at->diffInDays($endDate);
            }
        );
    }

    public function urgencyLevel(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->is_overdue) return 'critical';
                if ($this->days_until_deadline !== null && $this->days_until_deadline <= 3) return 'high';
                if ($this->days_until_deadline !== null && $this->days_until_deadline <= 7) return 'medium';
                return 'low';
            }
        );
    }

    public function requestTypeLabel(): Attribute
    {
        return Attribute::make(
            get: fn() => static::getRequestTypeLabels()[$this->request_type] ?? $this->request_type
        );
    }

    public function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn() => static::getStatusLabels()[$this->status] ?? $this->status
        );
    }

    // Helper Methods
    public static function generateRequestId(): string
    {
        $year = now()->year;
        $count = static::whereYear('created_at', $year)->count() + 1;
        return sprintf('DSR-%d-%03d', $year, $count);
    }

    public function sendAcknowledgment(?User $sentBy = null): void
    {
        if ($this->acknowledgment_sent_at) {
            throw new \Exception('Acknowledgment already sent');
        }

        $this->update(['acknowledgment_sent_at' => now()]);

        $this->logCommunication('acknowledgment_sent', [
            'sent_by' => $sentBy?->name ?? auth()->user()?->name,
            'sent_at' => now()->toISOString(),
        ]);
    }

    public function verifyIdentity(?User $verifiedBy = null, array $documents = [], string $notes = null): void
    {
        $this->update([
            'identity_verified' => true,
            'identity_verified_at' => now(),
            'verified_by_user_id' => $verifiedBy?->id ?? auth()->id(),
            'verification_documents' => array_merge($this->verification_documents ?? [], $documents),
            'verification_notes' => $this->verification_notes . "\n\n" . now()->toDateTimeString() . ': ' . ($notes ?? 'Identity verified'),
            'status' => $this->status === 'verifying' ? 'processing' : $this->status,
        ]);

        $this->logProcessingStep('identity_verified', [
            'verified_by' => $verifiedBy?->name ?? auth()->user()?->name,
            'documents_count' => count($documents),
            'notes' => $notes,
        ]);
    }

    public function assignTo(User $user, string $reason = null): void
    {
        $previousAssignee = $this->assignedTo?->name;

        $this->update(['assigned_to_user_id' => $user->id]);

        $this->logProcessingStep('assigned', [
            'assigned_to' => $user->name,
            'previous_assignee' => $previousAssignee,
            'reason' => $reason,
            'assigned_by' => auth()->user()?->name,
        ]);
    }

    public function markCompleted(string $summary, array $dataProvided = [], array $actionsTaken = [], ?User $completedBy = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'response_summary' => $summary,
            'data_provided' => array_merge($this->data_provided ?? [], $dataProvided),
            'actions_taken' => array_merge($this->actions_taken ?? [], $actionsTaken),
        ]);

        $this->logProcessingStep('completed', [
            'completed_by' => $completedBy?->name ?? auth()->user()?->name,
            'processing_days' => $this->processing_time,
            'data_categories_provided' => count($dataProvided),
            'actions_count' => count($actionsTaken),
        ]);
    }

    public function reject(string $reason, ?User $rejectedBy = null): void
    {
        $this->update([
            'status' => 'rejected',
            'completed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->logProcessingStep('rejected', [
            'rejected_by' => $rejectedBy?->name ?? auth()->user()?->name,
            'reason' => $reason,
            'processing_days' => $this->processing_time,
        ]);
    }

    public function putOnHold(string $reason): void
    {
        $this->update(['status' => 'on_hold']);

        $this->logProcessingStep('on_hold', [
            'reason' => $reason,
            'put_on_hold_by' => auth()->user()?->name,
        ]);
    }

    public function resume(string $reason = null): void
    {
        $this->update(['status' => 'processing']);

        $this->logProcessingStep('resumed', [
            'reason' => $reason,
            'resumed_by' => auth()->user()?->name,
        ]);
    }

    public function fileAppeal(string $appealDetails): void
    {
        $this->update([
            'appeal_filed' => true,
            'appeal_filed_at' => now(),
            'appeal_details' => $appealDetails,
        ]);

        $this->logProcessingStep('appeal_filed', [
            'appeal_details' => $appealDetails,
            'filed_by' => auth()->user()?->name,
        ]);
    }

    public function logCommunication(string $type, array $details): void
    {
        $communicationLog = $this->communication_log ?? [];
        $communicationLog[] = [
            'type' => $type,
            'timestamp' => now()->toISOString(),
            'details' => $details,
            'logged_by' => auth()->user()?->name,
        ];

        $this->update([
            'communication_log' => $communicationLog,
            'last_contact_at' => now(),
        ]);
    }

    public function logProcessingStep(string $step, array $details): void
    {
        $processingLog = $this->processing_log ?? [];
        $processingLog[] = [
            'step' => $step,
            'timestamp' => now()->toISOString(),
            'details' => $details,
            'user' => auth()->user()?->name,
        ];

        $this->update(['processing_log' => $processingLog]);
    }

    public function addAttachment(string $filePath, string $type, string $description = null): void
    {
        $attachments = $this->request_attachments ?? [];
        $attachments[] = [
            'file_path' => $filePath,
            'type' => $type,
            'description' => $description,
            'added_at' => now()->toISOString(),
            'added_by' => auth()->user()?->name,
        ];

        $this->update(['request_attachments' => $attachments]);
    }

    public function addResponseFile(string $filePath, string $type, string $description = null): void
    {
        $responseFiles = $this->response_files ?? [];
        $responseFiles[] = [
            'file_path' => $filePath,
            'type' => $type,
            'description' => $description,
            'added_at' => now()->toISOString(),
            'added_by' => auth()->user()?->name,
        ];

        $this->update(['response_files' => $responseFiles]);
    }

    public function generateStatusReport(): array
    {
        return [
            'request_id' => $this->request_id,
            'request_type' => $this->request_type_label,
            'status' => $this->status_label,
            'subject' => [
                'type' => $this->subject_type,
                'id' => $this->subject_id,
            ],
            'timeline' => [
                'received_at' => $this->received_at->toISOString(),
                'deadline_date' => $this->deadline_date->toISOString(),
                'days_until_deadline' => $this->days_until_deadline,
                'is_overdue' => $this->is_overdue,
                'urgency_level' => $this->urgency_level,
                'processing_days' => $this->processing_time,
            ],
            'verification' => [
                'identity_verified' => $this->identity_verified,
                'verified_at' => $this->identity_verified_at?->toISOString(),
                'verified_by' => $this->verifiedBy?->name,
            ],
            'processing' => [
                'assigned_to' => $this->assignedTo?->name,
                'complexity_score' => $this->complexity_score,
                'requires_legal_review' => $this->requires_legal_review,
                'involves_third_parties' => $this->involves_third_parties,
                'processing_steps' => count($this->processing_log ?? []),
                'communications' => count($this->communication_log ?? []),
            ],
            'completion' => [
                'completed_at' => $this->completed_at?->toISOString(),
                'response_summary' => $this->response_summary,
                'data_categories_provided' => count($this->data_provided ?? []),
                'actions_taken_count' => count($this->actions_taken ?? []),
                'rejection_reason' => $this->rejection_reason,
            ],
            'appeals' => [
                'appeal_filed' => $this->appeal_filed,
                'appeal_filed_at' => $this->appeal_filed_at?->toISOString(),
            ],
            'generated_at' => now()->toISOString(),
        ];
    }

    public static function getRequestTypeLabels(): array
    {
        return [
            'access' => 'Right of Access (Article 15)',
            'rectification' => 'Right to Rectification (Article 16)',
            'erasure' => 'Right to Erasure / Right to be Forgotten (Article 17)',
            'restrict' => 'Right to Restriction of Processing (Article 18)',
            'portability' => 'Right to Data Portability (Article 20)',
            'object' => 'Right to Object (Article 21)',
            'stop_automated' => 'Rights related to Automated Decision Making (Article 22)',
        ];
    }

    public static function getStatusLabels(): array
    {
        return [
            'received' => 'Received',
            'verifying' => 'Verifying Identity',
            'processing' => 'Processing',
            'on_hold' => 'On Hold',
            'completed' => 'Completed',
            'rejected' => 'Rejected',
            'partially_completed' => 'Partially Completed',
        ];
    }

    // Boot method to auto-generate request ID
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($request) {
            if (empty($request->request_id)) {
                $request->request_id = static::generateRequestId();
            }
            
            if (empty($request->received_at)) {
                $request->received_at = now();
            }
            
            if (empty($request->deadline_date)) {
                $request->deadline_date = now()->addDays(30); // 30 days as per GDPR
            }
        });
    }

    // Activity Log Configuration
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'request_type', 'status', 'identity_verified', 'assigned_to_user_id',
                'requires_legal_review', 'complexity_score', 'appeal_filed'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "GDPR request {$eventName}: {$this->request_id}")
            ->dontLogIfAttributesChangedOnly(['last_contact_at', 'internal_notes']);
    }
}