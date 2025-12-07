<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * VoucherRedemption Model
 *
 * Tracks when a club redeems a voucher, including a snapshot of the voucher's
 * discount values at the time of redemption for audit purposes.
 *
 * @property int $id
 * @property string $voucher_id
 * @property int $club_id
 * @property string $tenant_id
 * @property string $voucher_type
 * @property float|null $discount_percent
 * @property float|null $discount_amount
 * @property int|null $trial_extension_days
 * @property int $duration_months
 * @property string|null $applied_to_plan_id
 * @property int $months_applied
 * @property bool $is_fully_applied
 * @property \Carbon\Carbon|null $first_applied_at
 * @property \Carbon\Carbon|null $last_applied_at
 * @property \Carbon\Carbon|null $expires_at
 * @property float $total_discount_amount
 * @property int|null $redeemed_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Voucher $voucher
 * @property-read Club $club
 * @property-read Tenant $tenant
 * @property-read ClubSubscriptionPlan|null $appliedPlan
 * @property-read User|null $redeemedByUser
 */
class VoucherRedemption extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'voucher_id',
        'club_id',
        'tenant_id',
        'voucher_type',
        'discount_percent',
        'discount_amount',
        'trial_extension_days',
        'duration_months',
        'applied_to_plan_id',
        'months_applied',
        'is_fully_applied',
        'first_applied_at',
        'last_applied_at',
        'expires_at',
        'total_discount_amount',
        'redeemed_by',
    ];

    protected $casts = [
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'trial_extension_days' => 'integer',
        'duration_months' => 'integer',
        'months_applied' => 'integer',
        'is_fully_applied' => 'boolean',
        'first_applied_at' => 'datetime',
        'last_applied_at' => 'datetime',
        'expires_at' => 'datetime',
        'total_discount_amount' => 'decimal:2',
    ];

    // ====== RELATIONSHIPS ======

    /**
     * Get the voucher that was redeemed.
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class)->withTrashed();
    }

    /**
     * Get the club that redeemed the voucher.
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Get the tenant.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the plan the voucher was applied to.
     */
    public function appliedPlan(): BelongsTo
    {
        return $this->belongsTo(ClubSubscriptionPlan::class, 'applied_to_plan_id');
    }

    /**
     * Get the user who redeemed the voucher.
     */
    public function redeemedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by');
    }

    // ====== SCOPES ======

    /**
     * Scope to only active (not fully applied) redemptions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_fully_applied', false);
    }

    /**
     * Scope to expired redemptions.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())
            ->where('is_fully_applied', false);
    }

    /**
     * Scope to completed redemptions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('is_fully_applied', true);
    }

    // ====== HELPER METHODS ======

    /**
     * Check if the redemption is still active.
     */
    public function isActive(): bool
    {
        if ($this->is_fully_applied) {
            return false;
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Get remaining months of discount.
     */
    public function getRemainingMonths(): int
    {
        return max(0, $this->duration_months - $this->months_applied);
    }

    /**
     * Calculate discount for a given amount.
     */
    public function calculateDiscountForAmount(float $amount): float
    {
        if ($this->is_fully_applied || ! $this->isActive()) {
            return 0;
        }

        return match ($this->voucher_type) {
            Voucher::TYPE_PERCENT => round($amount * ($this->discount_percent / 100), 2),
            Voucher::TYPE_FIXED_AMOUNT => min($this->discount_amount, $amount),
            default => 0,
        };
    }

    /**
     * Apply discount and update tracking fields.
     */
    public function applyDiscount(float $discountGiven): void
    {
        $this->increment('months_applied');
        $this->increment('total_discount_amount', $discountGiven);

        $this->update([
            'last_applied_at' => now(),
            'first_applied_at' => $this->first_applied_at ?? now(),
        ]);

        if ($this->months_applied >= $this->duration_months) {
            $this->update(['is_fully_applied' => true]);
        }
    }

    /**
     * Mark the redemption as fully applied.
     */
    public function markAsFullyApplied(): void
    {
        $this->update(['is_fully_applied' => true]);
    }

    /**
     * Get formatted discount label.
     */
    public function getFormattedDiscount(): string
    {
        return match ($this->voucher_type) {
            Voucher::TYPE_PERCENT => number_format($this->discount_percent, 0).'%',
            Voucher::TYPE_FIXED_AMOUNT => number_format($this->discount_amount, 2).' EUR',
            Voucher::TYPE_TRIAL_EXTENSION => "+{$this->trial_extension_days} Tage Trial",
            default => '-',
        };
    }

    /**
     * Get type label in German.
     */
    public function getTypeLabel(): string
    {
        return match ($this->voucher_type) {
            Voucher::TYPE_PERCENT => 'Prozent-Rabatt',
            Voucher::TYPE_FIXED_AMOUNT => 'Fixbetrag-Rabatt',
            Voucher::TYPE_TRIAL_EXTENSION => 'Trial-Verlängerung',
            default => $this->voucher_type,
        };
    }
}
