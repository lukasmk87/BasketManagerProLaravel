<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tier',
        'plan_name',
        'monthly_price',
        'annual_price',
        'status',
        'started_at',
        'expires_at',
        'next_billing_at',
        'billing_cycle',
        'api_requests_limit',
        'burst_limit',
        'concurrent_requests_limit',
        'feature_limits',
        'stripe_subscription_id',
        'stripe_customer_id',
        'payment_method',
        'last_payment_at',
        'current_overage_cost',
        'overage_rates',
        'overage_allowed',
        'pending_tier_change',
        'tier_change_date',
        'admin_notes',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'next_billing_at' => 'datetime',
        'last_payment_at' => 'datetime',
        'tier_change_date' => 'datetime',
        'monthly_price' => 'decimal:2',
        'annual_price' => 'decimal:2',
        'current_overage_cost' => 'decimal:2',
        'feature_limits' => 'array',
        'overage_rates' => 'array',
        'overage_allowed' => 'boolean',
        'metadata' => 'array',
    ];

    // ============================
    // RELATIONSHIPS
    // ============================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ============================
    // SCOPES
    // ============================

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where(function($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeByTier($query, string $tier)
    {
        return $query->where('tier', $tier);
    }

    public function scopeDueForBilling($query)
    {
        return $query->where('status', 'active')
                    ->where('next_billing_at', '<=', now());
    }

    // ============================
    // ACCESSORS & MUTATORS
    // ============================

    public function isActive(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === 'active' && 
                         (!$this->expires_at || $this->expires_at->isFuture())
        );
    }

    public function isExpired(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->expires_at && $this->expires_at->isPast()
        );
    }

    public function daysUntilExpiry(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->expires_at ? now()->diffInDays($this->expires_at, false) : null
        );
    }

    public function currentPrice(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->billing_cycle === 'annual' ? $this->annual_price : $this->monthly_price
        );
    }

    // ============================
    // HELPER METHODS
    // ============================

    /**
     * Check if subscription allows a specific feature
     */
    public function hasFeature(string $feature): bool
    {
        $limits = $this->feature_limits ?? [];
        return isset($limits[$feature]) && $limits[$feature] !== false;
    }

    /**
     * Get limit for a specific feature
     */
    public function getFeatureLimit(string $feature, $default = null)
    {
        $limits = $this->feature_limits ?? [];
        return $limits[$feature] ?? $default;
    }

    /**
     * Get API rate limits based on tier
     */
    public function getApiLimits(): array
    {
        $tierLimits = config("api.rate_limiting.tiers.{$this->tier}", []);
        
        return [
            'requests_per_hour' => $this->api_requests_limit ?? $tierLimits['requests_per_hour'] ?? 1000,
            'burst_per_minute' => $this->burst_limit ?? $tierLimits['burst_per_minute'] ?? 100,
            'concurrent_requests' => $this->concurrent_requests_limit ?? $tierLimits['concurrent_requests'] ?? 10,
        ];
    }

    /**
     * Calculate overage cost for usage above limits
     */
    public function calculateOverageCost(int $excessRequests): float
    {
        if (!$this->overage_allowed) {
            return 0.0;
        }

        $rates = $this->overage_rates ?? config('api.rate_limiting.overage_rates', []);
        $costPerRequest = $rates['per_request'] ?? 0.001; // Default: $0.001 per request

        return $excessRequests * $costPerRequest;
    }

    /**
     * Check if subscription needs to be renewed soon
     */
    public function needsRenewal(int $daysBefore = 7): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->diffInDays(now()) <= $daysBefore;
    }

    /**
     * Upgrade subscription to a higher tier
     */
    public function upgradeTo(string $newTier, bool $immediate = false): bool
    {
        $tierHierarchy = ['free', 'basic', 'premium', 'enterprise', 'unlimited'];
        $currentIndex = array_search($this->tier, $tierHierarchy);
        $newIndex = array_search($newTier, $tierHierarchy);

        if ($newIndex <= $currentIndex) {
            return false; // Not an upgrade
        }

        if ($immediate) {
            $this->tier = $newTier;
            $this->save();
        } else {
            $this->pending_tier_change = $newTier;
            $this->tier_change_date = $this->next_billing_at ?? now()->addMonth();
            $this->save();
        }

        return true;
    }

    /**
     * Downgrade subscription to a lower tier
     */
    public function downgradeTo(string $newTier, bool $immediate = false): bool
    {
        $tierHierarchy = ['free', 'basic', 'premium', 'enterprise', 'unlimited'];
        $currentIndex = array_search($this->tier, $tierHierarchy);
        $newIndex = array_search($newTier, $tierHierarchy);

        if ($newIndex >= $currentIndex) {
            return false; // Not a downgrade
        }

        if ($immediate) {
            $this->tier = $newTier;
            $this->save();
        } else {
            $this->pending_tier_change = $newTier;
            $this->tier_change_date = $this->next_billing_at ?? now()->addMonth();
            $this->save();
        }

        return true;
    }

    /**
     * Extend subscription by given period
     */
    public function extend(string $period = '1 month'): bool
    {
        $currentExpiry = $this->expires_at ?? now();
        $this->expires_at = Carbon::parse($currentExpiry)->add(\DateInterval::createFromDateString($period));
        
        return $this->save();
    }

    /**
     * Cancel subscription (mark for cancellation at period end)
     */
    public function cancel(bool $immediate = false): bool
    {
        if ($immediate) {
            $this->status = 'cancelled';
            $this->expires_at = now();
        } else {
            $this->status = 'cancelled';
            // Keep expires_at as is, so it expires at the end of billing period
        }

        return $this->save();
    }

    /**
     * Reactivate a cancelled subscription
     */
    public function reactivate(): bool
    {
        if ($this->status !== 'cancelled') {
            return false;
        }

        $this->status = 'active';
        
        // Extend expiry if it's in the past
        if ($this->expires_at && $this->expires_at->isPast()) {
            $this->expires_at = now()->addMonth();
        }

        return $this->save();
    }

    /**
     * Get subscription usage statistics
     */
    public function getUsageStats(string $period = 'current_month'): array
    {
        $query = ApiUsageTracking::where('user_id', $this->user_id);
        
        switch ($period) {
            case 'current_month':
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
                break;
            case 'last_30_days':
                $query->where('created_at', '>=', now()->subDays(30));
                break;
            case 'current_week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
        }

        return [
            'total_requests' => $query->sum('request_count'),
            'total_cost' => $query->sum('billable_cost'),
            'unique_endpoints' => $query->distinct('endpoint')->count(),
            'avg_response_time' => $query->avg('response_time_ms'),
            'error_rate' => $query->where('response_status', '>=', 400)->count() / max($query->count(), 1),
        ];
    }
}