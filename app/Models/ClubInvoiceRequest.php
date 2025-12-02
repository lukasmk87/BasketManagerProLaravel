<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ClubInvoiceRequest Model
 *
 * Represents a request from a club admin to pay via invoice instead of Stripe.
 * Must be approved by a super admin before the invoice is created.
 *
 * @property int $id
 * @property string $tenant_id
 * @property int $club_id
 * @property string $club_subscription_plan_id
 * @property string $billing_name
 * @property string $billing_email
 * @property array|null $billing_address
 * @property string|null $vat_number
 * @property string $billing_interval
 * @property string $status
 * @property string|null $admin_notes
 * @property string|null $rejection_reason
 * @property int|null $processed_by
 * @property Carbon|null $processed_at
 * @property int|null $invoice_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Tenant $tenant
 * @property-read Club $club
 * @property-read ClubSubscriptionPlan $subscriptionPlan
 * @property-read User|null $processor
 * @property-read ClubInvoice|null $invoice
 */
class ClubInvoiceRequest extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'club_invoice_requests';

    protected $fillable = [
        'tenant_id',
        'club_id',
        'club_subscription_plan_id',
        'billing_name',
        'billing_email',
        'billing_address',
        'vat_number',
        'billing_interval',
        'status',
        'admin_notes',
        'rejection_reason',
        'processed_by',
        'processed_at',
        'invoice_id',
    ];

    protected $casts = [
        'billing_address' => 'array',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    // Billing interval constants
    public const INTERVAL_MONTHLY = 'monthly';
    public const INTERVAL_YEARLY = 'yearly';

    /**
     * Get the tenant that owns this request.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the club this request belongs to.
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Get the subscription plan for this request.
     */
    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(ClubSubscriptionPlan::class, 'club_subscription_plan_id');
    }

    /**
     * Get the admin who processed this request.
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Get the invoice created from this request.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ClubInvoice::class, 'invoice_id');
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

    // ==================== Helpers ====================

    /**
     * Check if this request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if this request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if this request is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if this request can be processed.
     */
    public function canBeProcessed(): bool
    {
        return $this->isPending();
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
     * Get the billing interval label in German.
     */
    public function getBillingIntervalLabelAttribute(): string
    {
        return match ($this->billing_interval) {
            self::INTERVAL_MONTHLY => 'Monatlich',
            self::INTERVAL_YEARLY => 'Jährlich',
            default => $this->billing_interval,
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
     * Get all available billing intervals.
     */
    public static function getBillingIntervals(): array
    {
        return [
            self::INTERVAL_MONTHLY => 'Monatlich',
            self::INTERVAL_YEARLY => 'Jährlich',
        ];
    }
}
