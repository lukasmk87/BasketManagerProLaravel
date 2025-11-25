<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * SubscriptionMRRSnapshot Model
 *
 * Stores daily/monthly snapshots of MRR (Monthly Recurring Revenue) for historical tracking.
 * Enables trend analysis, growth calculations, and reporting without expensive recalculations.
 *
 * @property int $id
 * @property string $tenant_id
 * @property Carbon $snapshot_date
 * @property string $snapshot_type ('daily' or 'monthly')
 * @property float $club_mrr
 * @property int $club_count
 * @property float $tenant_mrr
 * @property float $total_mrr
 * @property float $mrr_growth
 * @property float $mrr_growth_rate
 * @property float $new_business_mrr
 * @property float $expansion_mrr
 * @property float $contraction_mrr
 * @property float $churned_mrr
 * @property array|null $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Tenant $tenant
 */
class SubscriptionMRRSnapshot extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'subscription_mrr_snapshots';

    protected $fillable = [
        'tenant_id',
        'snapshot_date',
        'snapshot_type',
        'club_mrr',
        'club_count',
        'tenant_mrr',
        'total_mrr',
        'mrr_growth',
        'mrr_growth_rate',
        'new_business_mrr',
        'expansion_mrr',
        'contraction_mrr',
        'churned_mrr',
        'metadata',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'club_mrr' => 'decimal:2',
        'tenant_mrr' => 'decimal:2',
        'total_mrr' => 'decimal:2',
        'mrr_growth' => 'decimal:2',
        'mrr_growth_rate' => 'decimal:2',
        'new_business_mrr' => 'decimal:2',
        'expansion_mrr' => 'decimal:2',
        'contraction_mrr' => 'decimal:2',
        'churned_mrr' => 'decimal:2',
        'club_count' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns this MRR snapshot.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope: Filter to daily snapshots.
     */
    public function scopeDaily($query)
    {
        return $query->where('snapshot_type', 'daily');
    }

    /**
     * Scope: Filter to monthly snapshots.
     */
    public function scopeMonthly($query)
    {
        return $query->where('snapshot_type', 'monthly');
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
        return $query->whereBetween('snapshot_date', [$startDate, $endDate]);
    }

    /**
     * Scope: Get latest snapshot for a tenant.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $tenantId
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLatestForTenant($query, string $tenantId, string $type = 'daily')
    {
        return $query->where('tenant_id', $tenantId)
            ->where('snapshot_type', $type)
            ->latest('snapshot_date');
    }

    /**
     * Get the net new MRR (new + expansion - contraction - churned).
     *
     * @return float
     */
    public function getNetNewMRRAttribute(): float
    {
        return $this->new_business_mrr
            + $this->expansion_mrr
            - $this->contraction_mrr
            - $this->churned_mrr;
    }

    /**
     * Check if MRR is growing (positive growth rate).
     *
     * @return bool
     */
    public function isGrowing(): bool
    {
        return $this->mrr_growth_rate > 0;
    }

    /**
     * Check if MRR is declining (negative growth rate).
     *
     * @return bool
     */
    public function isDeclining(): bool
    {
        return $this->mrr_growth_rate < 0;
    }

    /**
     * Get formatted MRR growth rate as percentage string.
     *
     * @return string
     */
    public function getFormattedGrowthRateAttribute(): string
    {
        $sign = $this->mrr_growth_rate >= 0 ? '+' : '';
        return $sign . number_format($this->mrr_growth_rate, 2) . '%';
    }

    /**
     * Get formatted total MRR with currency.
     *
     * @param string $currency
     * @return string
     */
    public function getFormattedMRR(string $currency = 'EUR'): string
    {
        return number_format($this->total_mrr, 2) . ' ' . $currency;
    }
}
