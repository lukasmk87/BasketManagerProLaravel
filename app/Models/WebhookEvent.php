<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WebhookEvent Model
 * 
 * Tracks Stripe webhook events for audit trail and processing status
 * 
 * @property int $id
 * @property string $stripe_event_id
 * @property string $event_type
 * @property string|null $tenant_id
 * @property string $status
 * @property array $payload
 * @property bool $livemode
 * @property string|null $api_version
 * @property string|null $error_message
 * @property int $retry_count
 * @property \Carbon\Carbon|null $queued_at
 * @property \Carbon\Carbon|null $processing_started_at
 * @property \Carbon\Carbon|null $processing_completed_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class WebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'stripe_event_id',
        'event_type',
        'tenant_id',
        'status',
        'payload',
        'livemode',
        'api_version',
        'error_message',
        'retry_count',
        'queued_at',
        'processing_started_at',
        'processing_completed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'livemode' => 'boolean',
        'retry_count' => 'integer',
        'queued_at' => 'datetime',
        'processing_started_at' => 'datetime',
        'processing_completed_at' => 'datetime',
    ];

    /**
     * Possible webhook event statuses
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED = 'failed';

    /**
     * Get the tenant associated with this webhook event
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope to filter by event type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $eventType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope to filter by status
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by tenant
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $tenantId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter failed events
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope to filter processed events
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProcessed($query)
    {
        return $query->where('status', self::STATUS_PROCESSED);
    }

    /**
     * Scope to filter pending events
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_QUEUED]);
    }

    /**
     * Get processing duration in seconds
     *
     * @return float|null
     */
    public function getProcessingDurationAttribute(): ?float
    {
        if (!$this->processing_started_at || !$this->processing_completed_at) {
            return null;
        }

        return $this->processing_completed_at->diffInSeconds($this->processing_started_at, true);
    }

    /**
     * Check if event is retriable
     *
     * @return bool
     */
    public function isRetriable(): bool
    {
        return $this->status === self::STATUS_FAILED && $this->retry_count < 3;
    }

    /**
     * Check if event is completed (either processed or permanently failed)
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, [self::STATUS_PROCESSED, self::STATUS_FAILED]);
    }

    /**
     * Get formatted status for display
     *
     * @return string
     */
    public function getFormattedStatusAttribute(): string
    {
        $statusMap = [
            self::STATUS_PENDING => 'Ausstehend',
            self::STATUS_QUEUED => 'In Warteschlange',
            self::STATUS_PROCESSING => 'Wird verarbeitet',
            self::STATUS_PROCESSED => 'Verarbeitet',
            self::STATUS_FAILED => 'Fehlgeschlagen',
        ];

        return $statusMap[$this->status] ?? $this->status;
    }

    /**
     * Get event type display name
     *
     * @return string
     */
    public function getEventTypeDisplayAttribute(): string
    {
        $eventTypeMap = [
            'customer.subscription.created' => 'Abonnement erstellt',
            'customer.subscription.updated' => 'Abonnement aktualisiert',
            'customer.subscription.deleted' => 'Abonnement gekündigt',
            'invoice.payment_succeeded' => 'Zahlung erfolgreich',
            'invoice.payment_failed' => 'Zahlung fehlgeschlagen',
            'payment_method.attached' => 'Zahlungsmethode hinzugefügt',
            'customer.created' => 'Kunde erstellt',
            'customer.updated' => 'Kunde aktualisiert',
            'checkout.session.completed' => 'Checkout abgeschlossen',
            'setup_intent.succeeded' => 'Setup erfolgreich',
        ];

        return $eventTypeMap[$this->event_type] ?? $this->event_type;
    }

    /**
     * Get status badge color for UI
     *
     * @return string
     */
    public function getStatusColorAttribute(): string
    {
        $colorMap = [
            self::STATUS_PENDING => 'warning',
            self::STATUS_QUEUED => 'info',
            self::STATUS_PROCESSING => 'primary',
            self::STATUS_PROCESSED => 'success',
            self::STATUS_FAILED => 'danger',
        ];

        return $colorMap[$this->status] ?? 'secondary';
    }
}