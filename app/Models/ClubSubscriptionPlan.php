<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ClubSubscriptionPlan extends Model
{
    use HasFactory, HasUuids, SoftDeletes, BelongsToTenant;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'billing_interval',
        'features',
        'limits',
        'is_active',
        'is_default',
        'is_featured',
        'sort_order',
        'color',
        'icon',
        // Stripe integration fields
        'stripe_product_id',
        'stripe_price_id_monthly',
        'stripe_price_id_yearly',
        'is_stripe_synced',
        'last_stripe_sync_at',
        'trial_period_days',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'limits' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        // Stripe integration casts
        'is_stripe_synced' => 'boolean',
        'last_stripe_sync_at' => 'datetime',
        'trial_period_days' => 'integer',
    ];

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array
     */
    public function uniqueIds()
    {
        return ['id'];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($plan) {
            if (empty($plan->slug)) {
                $plan->slug = Str::slug($plan->name);
            }
        });
    }

    // =============================
    // RELATIONSHIPS
    // =============================

    /**
     * Get the tenant that owns this plan.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get all clubs using this plan.
     */
    public function clubs(): HasMany
    {
        return $this->hasMany(Club::class, 'club_subscription_plan_id');
    }

    /**
     * Get active clubs using this plan.
     */
    public function activeClubs(): HasMany
    {
        return $this->clubs()->where('is_active', true);
    }

    // =============================
    // SCOPES
    // =============================

    /**
     * Scope a query to only include active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include default plans.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope a query to plans for a specific tenant.
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query to only include featured plans.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include non-featured plans.
     */
    public function scopeNotFeatured($query)
    {
        return $query->where('is_featured', false);
    }

    /**
     * Scope a query to only include publicly available plans (active + featured).
     * These are shown on the landing page and available for new club registration.
     */
    public function scopePubliclyAvailable($query)
    {
        return $query->where('is_active', true)
                     ->where('is_featured', true);
    }

    // =============================
    // FEATURE & LIMIT METHODS
    // =============================

    /**
     * Check if plan has a specific feature.
     */
    public function hasFeature(string $feature): bool
    {
        $planFeatures = $this->features ?? [];
        return in_array($feature, $planFeatures);
    }

    /**
     * Get limit for a specific metric.
     *
     * @param string $metric
     * @return int Returns -1 for unlimited
     */
    public function getLimit(string $metric): int
    {
        $limits = $this->limits ?? [];
        return $limits[$metric] ?? -1; // -1 = unlimited
    }

    /**
     * Check if plan features are subset of tenant features.
     */
    public function isWithinTenantLimits(): bool
    {
        $tenant = $this->tenant;
        if (!$tenant) {
            return false;
        }

        // Check all plan features are available in tenant
        foreach ($this->features ?? [] as $feature) {
            if (!$tenant->hasFeature($feature)) {
                return false; // Plan has feature that tenant doesn't have
            }
        }

        // Check all plan limits are within tenant limits
        $tenantLimits = $tenant->getTierLimits();
        foreach ($this->limits ?? [] as $metric => $limit) {
            $tenantLimit = $tenantLimits[$metric] ?? -1;

            // Skip if tenant has unlimited
            if ($tenantLimit === -1) {
                continue;
            }

            // Plan limit must be <= tenant limit
            if ($limit > $tenantLimit && $limit !== -1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate plan data against tenant capabilities.
     *
     * @param array $planData
     * @param Tenant $tenant
     * @return array Array of errors (empty if valid)
     */
    public static function validateAgainstTenant(array $planData, Tenant $tenant): array
    {
        $errors = [];

        // Validate features
        $planFeatures = $planData['features'] ?? [];
        foreach ($planFeatures as $feature) {
            if (!$tenant->hasFeature($feature)) {
                $errors['features'][] = "Feature '{$feature}' not available in tenant tier '{$tenant->subscription_tier}'";
            }
        }

        // Validate limits
        $planLimits = $planData['limits'] ?? [];
        $tenantLimits = $tenant->getTierLimits();

        foreach ($planLimits as $metric => $limit) {
            $tenantLimit = $tenantLimits[$metric] ?? -1;

            if ($tenantLimit !== -1 && $limit > $tenantLimit && $limit !== -1) {
                $errors['limits'][] = "Limit '{$metric}' ({$limit}) exceeds tenant limit ({$tenantLimit})";
            }
        }

        return $errors;
    }

    // =============================
    // HELPER METHODS & ACCESSORS
    // =============================

    /**
     * Get the number of clubs using this plan.
     */
    public function getClubsCountAttribute(): int
    {
        return $this->clubs()->count();
    }

    /**
     * Get the number of active clubs using this plan.
     */
    public function getActiveClubsCountAttribute(): int
    {
        return $this->activeClubs()->count();
    }

    /**
     * Get formatted price with currency.
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2, ',', '.') . ' ' . $this->currency;
    }

    /**
     * Get all features as array.
     */
    public function getFeaturesList(): array
    {
        return $this->features ?? [];
    }

    /**
     * Get all limits as array.
     */
    public function getLimitsList(): array
    {
        return $this->limits ?? [];
    }

    // =============================
    // STRIPE INTEGRATION METHODS
    // =============================

    /**
     * Check if plan is synced with Stripe.
     */
    public function isSyncedWithStripe(): bool
    {
        return $this->is_stripe_synced
            && $this->stripe_product_id
            && ($this->stripe_price_id_monthly || $this->stripe_price_id_yearly);
    }

    /**
     * Check if plan needs Stripe sync.
     */
    public function needsStripeSync(): bool
    {
        // If never synced, needs sync
        if (!$this->is_stripe_synced) {
            return true;
        }

        // If synced but missing critical Stripe IDs, needs sync
        if (!$this->stripe_product_id || (!$this->stripe_price_id_monthly && !$this->stripe_price_id_yearly)) {
            return true;
        }

        // If plan was updated after last sync, needs sync
        if ($this->last_stripe_sync_at && $this->updated_at > $this->last_stripe_sync_at) {
            return true;
        }

        return false;
    }

    /**
     * Mark plan as synced with Stripe.
     */
    public function markAsSynced(): void
    {
        $this->update([
            'is_stripe_synced' => true,
            'last_stripe_sync_at' => now(),
        ]);
    }

    /**
     * Mark plan as needing sync.
     */
    public function markAsNeedingSync(): void
    {
        $this->update([
            'is_stripe_synced' => false,
        ]);
    }

    /**
     * Get Stripe Price ID for billing interval.
     */
    public function getStripePriceId(string $interval = 'monthly'): ?string
    {
        return $interval === 'yearly'
            ? $this->stripe_price_id_yearly
            : $this->stripe_price_id_monthly;
    }

    /**
     * Check if plan has trial period.
     */
    public function hasTrialPeriod(): bool
    {
        return $this->trial_period_days > 0;
    }

    /**
     * Check if plan is publicly available (active + featured).
     */
    public function isPubliclyAvailable(): bool
    {
        return $this->is_active && $this->is_featured;
    }
}
