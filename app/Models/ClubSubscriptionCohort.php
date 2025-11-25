<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * ClubSubscriptionCohort Model
 *
 * Stores pre-computed cohort analysis data for subscription retention tracking.
 * A cohort represents all clubs that started their subscription in the same month.
 * Tracks retention rates and lifetime value over time.
 *
 * @property int $id
 * @property string $tenant_id
 * @property Carbon $cohort_month
 * @property int $cohort_size
 * @property float $retention_month_1
 * @property float $retention_month_2
 * @property float $retention_month_3
 * @property float $retention_month_6
 * @property float $retention_month_12
 * @property float $cumulative_revenue
 * @property float $avg_ltv
 * @property Carbon|null $last_calculated_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Tenant $tenant
 */
class ClubSubscriptionCohort extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'club_subscription_cohorts';

    protected $fillable = [
        'tenant_id',
        'cohort_month',
        'cohort_size',
        'retention_month_1',
        'retention_month_2',
        'retention_month_3',
        'retention_month_6',
        'retention_month_12',
        'cumulative_revenue',
        'avg_ltv',
        'last_calculated_at',
    ];

    protected $casts = [
        'cohort_month' => 'date',
        'cohort_size' => 'integer',
        'retention_month_1' => 'decimal:2',
        'retention_month_2' => 'decimal:2',
        'retention_month_3' => 'decimal:2',
        'retention_month_6' => 'decimal:2',
        'retention_month_12' => 'decimal:2',
        'cumulative_revenue' => 'decimal:2',
        'avg_ltv' => 'decimal:2',
        'last_calculated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns this cohort.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope: Filter by year.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $year
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByYear($query, int $year)
    {
        $startDate = Carbon::create($year, 1, 1)->startOfYear();
        $endDate = $startDate->copy()->endOfYear();
        return $query->whereBetween('cohort_month', [$startDate, $endDate]);
    }

    /**
     * Scope: Get recent cohorts (last N months).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $months
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $months = 12)
    {
        $startDate = now()->subMonths($months)->startOfMonth();
        return $query->where('cohort_month', '>=', $startDate)
            ->orderBy('cohort_month', 'desc');
    }

    /**
     * Scope: Get cohorts that need recalculation (older than threshold).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $daysOld
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNeedsRecalculation($query, int $daysOld = 7)
    {
        $threshold = now()->subDays($daysOld);
        return $query->where(function ($q) use ($threshold) {
            $q->whereNull('last_calculated_at')
                ->orWhere('last_calculated_at', '<', $threshold);
        });
    }

    /**
     * Get retention rate for a specific month.
     *
     * @param int $month
     * @return float|null
     */
    public function getRetentionForMonth(int $month): ?float
    {
        return match ($month) {
            1 => $this->retention_month_1,
            2 => $this->retention_month_2,
            3 => $this->retention_month_3,
            6 => $this->retention_month_6,
            12 => $this->retention_month_12,
            default => null,
        };
    }

    /**
     * Get retention data as array for all tracked months.
     *
     * @return array
     */
    public function getRetentionDataAttribute(): array
    {
        return [
            1 => $this->retention_month_1,
            2 => $this->retention_month_2,
            3 => $this->retention_month_3,
            6 => $this->retention_month_6,
            12 => $this->retention_month_12,
        ];
    }

    /**
     * Calculate retention drop from month 1 to month 12.
     *
     * @return float
     */
    public function getRetentionDropAttribute(): float
    {
        return $this->retention_month_1 - $this->retention_month_12;
    }

    /**
     * Get cohort age in months from creation.
     *
     * @return int
     */
    public function getAgeInMonthsAttribute(): int
    {
        return $this->cohort_month->diffInMonths(now());
    }

    /**
     * Check if cohort is mature enough for 12-month analysis.
     *
     * @return bool
     */
    public function isMature(): bool
    {
        return $this->age_in_months >= 12;
    }

    /**
     * Check if cohort data is stale and needs recalculation.
     *
     * @param int $daysThreshold
     * @return bool
     */
    public function isStale(int $daysThreshold = 7): bool
    {
        if (!$this->last_calculated_at) {
            return true;
        }
        return $this->last_calculated_at->lt(now()->subDays($daysThreshold));
    }

    /**
     * Get formatted cohort month (e.g., "January 2025").
     *
     * @return string
     */
    public function getFormattedCohortMonthAttribute(): string
    {
        return $this->cohort_month->format('F Y');
    }

    /**
     * Get formatted average LTV with currency.
     *
     * @param string $currency
     * @return string
     */
    public function getFormattedLTV(string $currency = 'EUR'): string
    {
        return number_format($this->avg_ltv, 2) . ' ' . $currency;
    }

    /**
     * Get formatted cumulative revenue with currency.
     *
     * @param string $currency
     * @return string
     */
    public function getFormattedRevenue(string $currency = 'EUR'): string
    {
        return number_format($this->cumulative_revenue, 2) . ' ' . $currency;
    }

    /**
     * Get retention trend (improving, declining, stable).
     *
     * @return string
     */
    public function getRetentionTrendAttribute(): string
    {
        if ($this->age_in_months < 3) {
            return 'too_early';
        }

        $drop = $this->retention_drop;

        if ($drop < 10) {
            return 'excellent'; // Less than 10% drop
        } elseif ($drop < 25) {
            return 'good'; // 10-25% drop
        } elseif ($drop < 50) {
            return 'moderate'; // 25-50% drop
        } else {
            return 'poor'; // More than 50% drop
        }
    }
}
