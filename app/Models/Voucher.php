<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Voucher Model
 *
 * Represents discount vouchers that can be redeemed by clubs.
 * Supports three types: percent discount, fixed amount discount, and trial extension.
 * Can be tenant-specific or system-wide (when tenant_id is NULL).
 *
 * @property string $id
 * @property string|null $tenant_id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string $type
 * @property float|null $discount_percent
 * @property float|null $discount_amount
 * @property int|null $trial_extension_days
 * @property int $duration_months
 * @property int|null $max_redemptions
 * @property int $current_redemptions
 * @property \Carbon\Carbon|null $valid_from
 * @property \Carbon\Carbon|null $valid_until
 * @property array|null $applicable_plan_ids
 * @property bool $is_active
 * @property int|null $created_by
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read Tenant|null $tenant
 * @property-read User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection|VoucherRedemption[] $redemptions
 */
class Voucher extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

    // NOTE: Intentionally NOT using BelongsToTenant trait - tenant_id can be NULL for system-wide vouchers!

    // Type Constants
    public const TYPE_PERCENT = 'percent';

    public const TYPE_FIXED_AMOUNT = 'fixed_amount';

    public const TYPE_TRIAL_EXTENSION = 'trial_extension';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'description',
        'type',
        'discount_percent',
        'discount_amount',
        'trial_extension_days',
        'duration_months',
        'max_redemptions',
        'current_redemptions',
        'valid_from',
        'valid_until',
        'applicable_plan_ids',
        'is_active',
        'created_by',
        'metadata',
    ];

    protected $casts = [
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'trial_extension_days' => 'integer',
        'duration_months' => 'integer',
        'max_redemptions' => 'integer',
        'current_redemptions' => 'integer',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'applicable_plan_ids' => 'array',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Boot method for code generation.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($voucher) {
            if (empty($voucher->code)) {
                $voucher->code = strtoupper(Str::random(8));
            } else {
                $voucher->code = strtoupper($voucher->code);
            }
        });
    }

    // ====== RELATIONSHIPS ======

    /**
     * Get the tenant that owns this voucher.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who created this voucher.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all redemptions for this voucher.
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(VoucherRedemption::class);
    }

    // ====== SCOPES ======

    /**
     * Scope to only active vouchers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to only vouchers within their validity period.
     */
    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('valid_from')
                ->orWhere('valid_from', '<=', now());
        })->where(function ($q) {
            $q->whereNull('valid_until')
                ->orWhere('valid_until', '>=', now());
        });
    }

    /**
     * Scope to vouchers that haven't reached their redemption limit.
     */
    public function scopeNotExhausted($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('max_redemptions')
                ->orWhereRaw('current_redemptions < max_redemptions');
        });
    }

    /**
     * Scope to vouchers available for a specific tenant (includes system-wide).
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where(function ($q) use ($tenantId) {
            $q->whereNull('tenant_id') // System-wide
                ->orWhere('tenant_id', $tenantId);
        });
    }

    /**
     * Scope to only system-wide vouchers.
     */
    public function scopeSystemWide($query)
    {
        return $query->whereNull('tenant_id');
    }

    /**
     * Scope to only tenant-specific vouchers.
     */
    public function scopeTenantSpecific($query)
    {
        return $query->whereNotNull('tenant_id');
    }

    // ====== VALIDATION METHODS ======

    /**
     * Check if the voucher is currently valid for use.
     */
    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }
        if ($this->valid_from && $this->valid_from->isFuture()) {
            return false;
        }
        if ($this->valid_until && $this->valid_until->isPast()) {
            return false;
        }
        if ($this->max_redemptions && $this->current_redemptions >= $this->max_redemptions) {
            return false;
        }

        return true;
    }

    /**
     * Check if the voucher is applicable to a specific plan.
     */
    public function isApplicableToPlan(ClubSubscriptionPlan $plan): bool
    {
        if (empty($this->applicable_plan_ids)) {
            return true; // Applicable to all plans
        }

        return in_array($plan->id, $this->applicable_plan_ids);
    }

    /**
     * Check if the voucher can be redeemed by a specific club.
     */
    public function canBeRedeemedByClub(Club $club): bool
    {
        // Check if already redeemed by this club
        return ! $this->redemptions()
            ->where('club_id', $club->id)
            ->exists();
    }

    /**
     * Check if this is a system-wide voucher.
     */
    public function isSystemWide(): bool
    {
        return is_null($this->tenant_id);
    }

    /**
     * Check if voucher has expired.
     */
    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    /**
     * Check if voucher redemption limit has been reached.
     */
    public function isExhausted(): bool
    {
        return $this->max_redemptions && $this->current_redemptions >= $this->max_redemptions;
    }

    // ====== HELPER METHODS ======

    /**
     * Get a formatted discount label.
     */
    public function getFormattedDiscount(): string
    {
        return match ($this->type) {
            self::TYPE_PERCENT => number_format($this->discount_percent, 0).'%',
            self::TYPE_FIXED_AMOUNT => number_format($this->discount_amount, 2).' EUR',
            self::TYPE_TRIAL_EXTENSION => "+{$this->trial_extension_days} Tage Trial",
            default => '-',
        };
    }

    /**
     * Get a label for the duration.
     */
    public function getDurationLabel(): string
    {
        if ($this->type === self::TYPE_TRIAL_EXTENSION) {
            return 'Einmalig';
        }

        return $this->duration_months === 1
            ? '1 Monat'
            : "{$this->duration_months} Monate";
    }

    /**
     * Get remaining redemptions count.
     */
    public function getRemainingRedemptions(): ?int
    {
        if (is_null($this->max_redemptions)) {
            return null;
        }

        return max(0, $this->max_redemptions - $this->current_redemptions);
    }

    /**
     * Increment the redemption counter.
     */
    public function incrementRedemptions(): void
    {
        $this->increment('current_redemptions');
    }

    /**
     * Get the type label in German.
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_PERCENT => 'Prozent-Rabatt',
            self::TYPE_FIXED_AMOUNT => 'Fixbetrag-Rabatt',
            self::TYPE_TRIAL_EXTENSION => 'Trial-Verlängerung',
            default => $this->type,
        };
    }

    /**
     * Get status label.
     */
    public function getStatusLabel(): string
    {
        if (! $this->is_active) {
            return 'Inaktiv';
        }
        if ($this->isExpired()) {
            return 'Abgelaufen';
        }
        if ($this->isExhausted()) {
            return 'Erschöpft';
        }

        return 'Aktiv';
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColor(): string
    {
        if (! $this->is_active) {
            return 'red';
        }
        if ($this->isExpired()) {
            return 'yellow';
        }
        if ($this->isExhausted()) {
            return 'orange';
        }

        return 'green';
    }

    // ====== ACTIVITY LOG ======

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'name', 'type', 'is_active', 'current_redemptions'])
            ->logOnlyDirty()
            ->useLogName('voucher');
    }
}
