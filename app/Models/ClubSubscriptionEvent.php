<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * ClubSubscriptionEvent Model
 *
 * Comprehensive audit trail for all club subscription lifecycle events.
 * Tracks subscriptions, cancellations, upgrades, downgrades, trials, and payments
 * for churn analysis, revenue attribution, and troubleshooting.
 *
 * @property int $id
 * @property string $tenant_id
 * @property int $club_id
 * @property string $event_type
 * @property string|null $stripe_subscription_id
 * @property string|null $stripe_event_id
 * @property string|null $old_plan_id
 * @property string|null $new_plan_id
 * @property float $mrr_change
 * @property string|null $cancellation_reason
 * @property string|null $cancellation_feedback
 * @property array|null $metadata
 * @property Carbon $event_date
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Tenant $tenant
 * @property-read Club $club
 */
class ClubSubscriptionEvent extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'club_subscription_events';

    protected $fillable = [
        'tenant_id',
        'club_id',
        'event_type',
        'stripe_subscription_id',
        'stripe_event_id',
        'old_plan_id',
        'new_plan_id',
        'mrr_change',
        'cancellation_reason',
        'cancellation_feedback',
        'metadata',
        'event_date',
    ];

    protected $casts = [
        'mrr_change' => 'decimal:2',
        'metadata' => 'array',
        'event_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Event type constants
    public const TYPE_SUBSCRIPTION_CREATED = 'subscription_created';
    public const TYPE_SUBSCRIPTION_CANCELED = 'subscription_canceled';
    public const TYPE_SUBSCRIPTION_RENEWED = 'subscription_renewed';
    public const TYPE_PLAN_UPGRADED = 'plan_upgraded';
    public const TYPE_PLAN_DOWNGRADED = 'plan_downgraded';
    public const TYPE_TRIAL_STARTED = 'trial_started';
    public const TYPE_TRIAL_CONVERTED = 'trial_converted';
    public const TYPE_TRIAL_EXPIRED = 'trial_expired';
    public const TYPE_PAYMENT_SUCCEEDED = 'payment_succeeded';
    public const TYPE_PAYMENT_FAILED = 'payment_failed';
    public const TYPE_PAYMENT_RECOVERED = 'payment_recovered';

    // Cancellation reason constants
    public const REASON_VOLUNTARY = 'voluntary';
    public const REASON_PAYMENT_FAILED = 'payment_failed';
    public const REASON_TRIAL_EXPIRED = 'trial_expired';
    public const REASON_DOWNGRADE_TO_FREE = 'downgrade_to_free';
    public const REASON_OTHER = 'other';

    /**
     * Get the tenant that owns this event.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the club that owns this event.
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Scope: Filter by event type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|array $types
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $types)
    {
        if (is_array($types)) {
            return $query->whereIn('event_type', $types);
        }
        return $query->where('event_type', $types);
    }

    /**
     * Scope: Filter to subscription lifecycle events.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLifecycleEvents($query)
    {
        return $query->whereIn('event_type', [
            self::TYPE_SUBSCRIPTION_CREATED,
            self::TYPE_SUBSCRIPTION_CANCELED,
            self::TYPE_SUBSCRIPTION_RENEWED,
        ]);
    }

    /**
     * Scope: Filter to plan change events.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePlanChanges($query)
    {
        return $query->whereIn('event_type', [
            self::TYPE_PLAN_UPGRADED,
            self::TYPE_PLAN_DOWNGRADED,
        ]);
    }

    /**
     * Scope: Filter to trial events.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTrialEvents($query)
    {
        return $query->whereIn('event_type', [
            self::TYPE_TRIAL_STARTED,
            self::TYPE_TRIAL_CONVERTED,
            self::TYPE_TRIAL_EXPIRED,
        ]);
    }

    /**
     * Scope: Filter to payment events.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePaymentEvents($query)
    {
        return $query->whereIn('event_type', [
            self::TYPE_PAYMENT_SUCCEEDED,
            self::TYPE_PAYMENT_FAILED,
            self::TYPE_PAYMENT_RECOVERED,
        ]);
    }

    /**
     * Scope: Filter to churn events (cancellations and expirations).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeChurnEvents($query)
    {
        return $query->whereIn('event_type', [
            self::TYPE_SUBSCRIPTION_CANCELED,
            self::TYPE_TRIAL_EXPIRED,
        ]);
    }

    /**
     * Scope: Filter by date range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('event_date', [$startDate, $endDate]);
    }

    /**
     * Scope: Filter by month.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $year
     * @param int $month
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInMonth($query, int $year, int $month)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        return $query->whereBetween('event_date', [$startDate, $endDate]);
    }

    /**
     * Check if this event represents a churn.
     *
     * @return bool
     */
    public function isChurn(): bool
    {
        return in_array($this->event_type, [
            self::TYPE_SUBSCRIPTION_CANCELED,
            self::TYPE_TRIAL_EXPIRED,
        ]);
    }

    /**
     * Check if this is a voluntary churn (user initiated).
     *
     * @return bool
     */
    public function isVoluntaryChurn(): bool
    {
        return $this->isChurn() && $this->cancellation_reason === self::REASON_VOLUNTARY;
    }

    /**
     * Check if this is an involuntary churn (payment failure).
     *
     * @return bool
     */
    public function isInvoluntaryChurn(): bool
    {
        return $this->isChurn() && $this->cancellation_reason === self::REASON_PAYMENT_FAILED;
    }

    /**
     * Check if this event represents revenue growth.
     *
     * @return bool
     */
    public function isRevenuePositive(): bool
    {
        return $this->mrr_change > 0;
    }

    /**
     * Check if this event represents revenue loss.
     *
     * @return bool
     */
    public function isRevenueNegative(): bool
    {
        return $this->mrr_change < 0;
    }

    /**
     * Get formatted MRR change with currency.
     *
     * @param string $currency
     * @return string
     */
    public function getFormattedMRRChange(string $currency = 'EUR'): string
    {
        $sign = $this->mrr_change >= 0 ? '+' : '';
        return $sign . number_format($this->mrr_change, 2) . ' ' . $currency;
    }
}
