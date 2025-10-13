<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SubscriptionPlan extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'billing_period',
        'stripe_price_id',
        'stripe_product_id',
        'trial_days',
        'is_active',
        'is_custom',
        'is_featured',
        'sort_order',
        'features',
        'limits',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'price' => 'decimal:2',
        'trial_days' => 'integer',
        'is_active' => 'boolean',
        'is_custom' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'features' => 'array',
        'limits' => 'array',
        'metadata' => 'array',
    ];

    /**
     * The attributes that should be hidden.
     */
    protected $hidden = [];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ============================
    // RELATIONSHIPS
    // ============================

    /**
     * Get tenants using this plan.
     */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'subscription_plan_id');
    }

    /**
     * Get customizations for this plan.
     */
    public function customizations(): HasMany
    {
        return $this->hasMany(TenantPlanCustomization::class);
    }

    // ============================
    // SCOPES
    // ============================

    /**
     * Scope to only include active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to only include featured plans.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    // ============================
    // ACCESSORS & MUTATORS
    // ============================

    /**
     * Get the formatted price with currency.
     */
    public function getFormattedPriceAttribute(): string
    {
        if ($this->price == 0) {
            return 'Kostenlos';
        }

        return number_format($this->price, 2, ',', '.') . ' ' . $this->currency;
    }

    /**
     * Get the billing period label.
     */
    public function getBillingPeriodLabelAttribute(): string
    {
        return match($this->billing_period) {
            'monthly' => 'pro Monat',
            'yearly' => 'pro Jahr',
            'quarterly' => 'pro Quartal',
            default => $this->billing_period,
        };
    }

    // ============================
    // HELPER METHODS
    // ============================

    /**
     * Check if plan has a specific feature.
     */
    public function hasFeature(string $feature): bool
    {
        $features = $this->features ?? [];
        return in_array($feature, $features);
    }

    /**
     * Get limit for a specific metric.
     */
    public function getLimit(string $metric): int|float
    {
        $limits = $this->limits ?? [];
        return $limits[$metric] ?? 0;
    }

    /**
     * Check if plan has unlimited access for a metric.
     */
    public function hasUnlimitedAccess(string $metric): bool
    {
        return $this->getLimit($metric) === -1;
    }

    /**
     * Get all features with their display names.
     */
    public function getFeaturesWithNames(): array
    {
        $features = $this->features ?? [];
        $featureNames = config('tenants.features', []);

        return collect($features)->map(function ($feature) use ($featureNames) {
            return [
                'slug' => $feature,
                'name' => $featureNames[$feature] ?? $feature,
            ];
        })->toArray();
    }

    /**
     * Get all limits with formatted values.
     */
    public function getFormattedLimits(): array
    {
        $limits = $this->limits ?? [];
        $formatted = [];

        foreach ($limits as $metric => $value) {
            $formatted[$metric] = [
                'value' => $value,
                'formatted' => $value === -1 ? 'Unbegrenzt' : number_format($value, 0, ',', '.'),
                'unlimited' => $value === -1,
            ];
        }

        return $formatted;
    }

    /**
     * Check if plan is free.
     */
    public function isFree(): bool
    {
        return $this->price == 0 || $this->slug === 'free';
    }

    /**
     * Get active tenant count for this plan.
     */
    public function getActiveTenantCountAttribute(): int
    {
        return $this->tenants()->where('is_active', true)->count();
    }

    /**
     * Get total revenue for this plan (monthly).
     */
    public function getMonthlyRevenueAttribute(): float
    {
        if ($this->billing_period === 'yearly') {
            return round($this->price / 12 * $this->active_tenant_count, 2);
        }

        return round($this->price * $this->active_tenant_count, 2);
    }

    /**
     * Clone this plan to create a new one.
     */
    public function clonePlan(string $newName, string $newSlug): self
    {
        $clone = $this->replicate();
        $clone->name = $newName;
        $clone->slug = $newSlug;
        $clone->is_custom = true;
        $clone->stripe_price_id = null;
        $clone->stripe_product_id = null;
        $clone->save();

        return $clone;
    }

    // ============================
    // ACTIVITY LOG
    // ============================

    /**
     * Get activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'price', 'is_active', 'features', 'limits'])
            ->logOnlyDirty()
            ->useLogName('subscription_plan')
            ->setDescriptionForEvent(fn(string $eventName) => "Subscription plan has been {$eventName}");
    }
}
