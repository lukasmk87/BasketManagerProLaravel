<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantUsage extends Model
{
    use HasFactory, BelongsToTenant;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'metric',
        'usage_count',
        'period_start',
        'period_end',
        'last_tracked_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'last_tracked_at' => 'datetime',
        'metadata' => 'array',
        'usage_count' => 'integer',
    ];

    /**
     * Get the tenant that owns the usage record.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope to get current period usage.
     */
    public function scopeCurrentPeriod($query)
    {
        return $query->where('period_start', '>=', now()->startOfMonth());
    }

    /**
     * Scope to filter by metric.
     */
    public function scopeForMetric($query, string $metric)
    {
        return $query->where('metric', $metric);
    }

    /**
     * Get usage percentage relative to limit.
     */
    public function getUsagePercentageAttribute(): float
    {
        $tenant = $this->tenant;
        $tierConfig = config("tenants.tiers.{$tenant->subscription_tier}");
        
        if (!$tierConfig || !isset($tierConfig['limits'][$this->metric])) {
            return 0;
        }

        $limit = $tierConfig['limits'][$this->metric];
        
        if ($limit === -1) { // Unlimited
            return 0;
        }

        return $limit > 0 ? min(100, ($this->usage_count / $limit) * 100) : 0;
    }

    /**
     * Check if usage is over limit.
     */
    public function isOverLimit(): bool
    {
        return $this->getUsagePercentageAttribute() > 100;
    }

    /**
     * Check if usage is near limit (over 80%).
     */
    public function isNearLimit(): bool
    {
        return $this->getUsagePercentageAttribute() > 80;
    }
}
