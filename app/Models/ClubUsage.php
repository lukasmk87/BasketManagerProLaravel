<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class ClubUsage extends Model
{
    use HasFactory, BelongsToTenant;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'club_id',
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
     */
    protected $casts = [
        'usage_count' => 'integer',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'last_tracked_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the club that owns this usage record.
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Get the tenant that owns this usage record.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope a query to only include records for the current period.
     */
    public function scopeCurrentPeriod($query)
    {
        $startOfMonth = now()->startOfMonth();
        return $query->where('period_start', '>=', $startOfMonth);
    }

    /**
     * Scope a query to filter by metric.
     */
    public function scopeForMetric($query, string $metric)
    {
        return $query->where('metric', $metric);
    }

    /**
     * Scope a query to filter by club.
     */
    public function scopeForClub($query, int $clubId)
    {
        return $query->where('club_id', $clubId);
    }

    /**
     * Get usage percentage of limit.
     */
    public function getUsagePercentageAttribute(): float
    {
        $club = $this->club;
        if (!$club) {
            return 0;
        }

        $limit = $club->getLimit($this->metric);

        if ($limit === -1 || $limit === 0) {
            return 0; // Unlimited or no limit
        }

        return min(100, ($this->usage_count / $limit) * 100);
    }

    /**
     * Check if usage is over limit.
     */
    public function isOverLimit(): bool
    {
        return $this->usage_percentage > 100;
    }

    /**
     * Check if usage is near limit (>80%).
     */
    public function isNearLimit(): bool
    {
        return $this->usage_percentage > 80 && $this->usage_percentage <= 100;
    }

    /**
     * Get the remaining capacity.
     */
    public function getRemainingCapacityAttribute(): int
    {
        $club = $this->club;
        if (!$club) {
            return 0;
        }

        $limit = $club->getLimit($this->metric);

        if ($limit === -1) {
            return -1; // Unlimited
        }

        return max(0, $limit - $this->usage_count);
    }
}
