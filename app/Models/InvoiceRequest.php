<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * InvoiceRequest Model
 *
 * Anfrage zur Umstellung auf Rechnungszahlung (Bank-Überweisung statt Stripe).
 * Muss von einem Admin genehmigt werden.
 *
 * @property int $id
 * @property string $tenant_id
 * @property string $requestable_type
 * @property string $requestable_id
 * @property string $status
 * @property string $billing_name
 * @property string $billing_email
 * @property array|null $billing_address
 * @property string|null $vat_number
 * @property string|null $notes
 * @property int|null $requested_by
 * @property int|null $processed_by
 * @property Carbon|null $processed_at
 * @property string|null $rejection_reason
 * @property string|null $admin_notes
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Tenant $tenant
 * @property-read Model $requestable
 * @property-read User|null $requester
 * @property-read User|null $processor
 */
class InvoiceRequest extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $table = 'invoice_requests';

    protected $fillable = [
        'tenant_id',
        'requestable_type',
        'requestable_id',
        'subscription_plan_type',
        'subscription_plan_id',
        'status',
        'billing_name',
        'billing_email',
        'billing_address',
        'vat_number',
        'billing_interval',
        'notes',
        'requested_by',
        'processed_by',
        'processed_at',
        'rejection_reason',
        'admin_notes',
        'invoice_id',
    ];

    protected $casts = [
        'billing_address' => 'array',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    // Requestable type constants
    public const TYPE_CLUB = 'App\\Models\\Club';

    public const TYPE_TENANT = 'App\\Models\\Tenant';

    /**
     * Get the tenant that owns this request.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the requestable entity (Club or Tenant).
     */
    public function requestable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who created this request.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user who processed this request.
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Get the subscription plan (polymorphic).
     */
    public function subscriptionPlan(): MorphTo
    {
        return $this->morphTo('subscriptionPlan', 'subscription_plan_type', 'subscription_plan_id');
    }

    /**
     * Get the invoice that was created from this request.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    // ==================== Scopes ====================

    /**
     * Scope: Filter to pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Filter to approved requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope: Filter to rejected requests.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope: Filter by requestable type.
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('requestable_type', $type);
    }

    /**
     * Scope: Filter to club requests.
     */
    public function scopeForClubs($query)
    {
        return $query->forType(self::TYPE_CLUB);
    }

    /**
     * Scope: Filter to tenant requests.
     */
    public function scopeForTenants($query)
    {
        return $query->forType(self::TYPE_TENANT);
    }

    // ==================== Helpers ====================

    /**
     * Check if this is a club request.
     */
    public function isClubRequest(): bool
    {
        return $this->requestable_type === self::TYPE_CLUB;
    }

    /**
     * Check if this is a tenant request.
     */
    public function isTenantRequest(): bool
    {
        return $this->requestable_type === self::TYPE_TENANT;
    }

    /**
     * Check if the request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if the request is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if the request can be processed.
     */
    public function canBeProcessed(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Get the status label in German.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Ausstehend',
            self::STATUS_APPROVED => 'Genehmigt',
            self::STATUS_REJECTED => 'Abgelehnt',
            default => $this->status,
        };
    }

    /**
     * Get the status color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_APPROVED => 'green',
            self::STATUS_REJECTED => 'red',
            default => 'gray',
        };
    }

    /**
     * Get the requestable type label.
     */
    public function getRequestableTypeLabelAttribute(): string
    {
        return match ($this->requestable_type) {
            self::TYPE_CLUB => 'Club',
            self::TYPE_TENANT => 'Tenant',
            default => 'Unbekannt',
        };
    }

    /**
     * Get all available statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Ausstehend',
            self::STATUS_APPROVED => 'Genehmigt',
            self::STATUS_REJECTED => 'Abgelehnt',
        ];
    }

    /**
     * Get all available requestable types.
     */
    public static function getRequestableTypes(): array
    {
        return [
            'club' => 'Club',
            'tenant' => 'Tenant',
        ];
    }
}
