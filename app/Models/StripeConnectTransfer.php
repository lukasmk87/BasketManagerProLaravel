<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StripeConnectTransfer extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'stripe_connect_transfers';

    protected $fillable = [
        'tenant_id',
        'club_id',
        'stripe_payment_intent_id',
        'stripe_transfer_id',
        'stripe_charge_id',
        'gross_amount',
        'application_fee_amount',
        'net_amount',
        'currency',
        'status',
        'description',
        'metadata',
    ];

    protected $casts = [
        'gross_amount' => 'integer',
        'application_fee_amount' => 'integer',
        'net_amount' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant this transfer belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the club this transfer is from.
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Scope for pending transfers.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for successful transfers.
     */
    public function scopeSucceeded($query)
    {
        return $query->where('status', 'succeeded');
    }

    /**
     * Scope for failed transfers.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for refunded transfers.
     */
    public function scopeRefunded($query)
    {
        return $query->whereIn('status', ['refunded', 'partially_refunded']);
    }

    /**
     * Get formatted gross amount in EUR.
     */
    public function getFormattedGrossAmountAttribute(): string
    {
        return number_format($this->gross_amount / 100, 2, ',', '.').' '.strtoupper($this->currency);
    }

    /**
     * Get formatted application fee in EUR.
     */
    public function getFormattedFeeAttribute(): string
    {
        return number_format($this->application_fee_amount / 100, 2, ',', '.').' '.strtoupper($this->currency);
    }

    /**
     * Get formatted net amount in EUR.
     */
    public function getFormattedNetAmountAttribute(): string
    {
        return number_format($this->net_amount / 100, 2, ',', '.').' '.strtoupper($this->currency);
    }

    /**
     * Check if transfer is complete.
     */
    public function isComplete(): bool
    {
        return $this->status === 'succeeded';
    }

    /**
     * Check if transfer is refundable.
     */
    public function isRefundable(): bool
    {
        return $this->status === 'succeeded' && $this->stripe_transfer_id !== null;
    }
}
