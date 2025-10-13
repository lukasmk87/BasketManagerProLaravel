<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class TenantPlanCustomization extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'subscription_plan_id',
        'custom_features',
        'disabled_features',
        'custom_limits',
        'notes',
        'effective_from',
        'effective_until',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'custom_features' => 'array',
        'disabled_features' => 'array',
        'custom_limits' => 'array',
        'effective_from' => 'datetime',
        'effective_until' => 'datetime',
    ];

    // ============================
    // RELATIONSHIPS
    // ============================

    /**
     * Get the tenant that owns the customization.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the subscription plan.
     */
    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    // ============================
    // SCOPES
    // ============================

    /**
     * Scope to only include active customizations.
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('effective_from')
              ->orWhere('effective_from', '<=', now());
        })->where(function ($q) {
            $q->whereNull('effective_until')
              ->orWhere('effective_until', '>=', now());
        });
    }

    // ============================
    // HELPER METHODS
    // ============================

    /**
     * Check if customization is currently active.
     */
    public function isActive(): bool
    {
        $now = now();

        if ($this->effective_from && $this->effective_from->isFuture()) {
            return false;
        }

        if ($this->effective_until && $this->effective_until->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Get all enabled features (plan features + custom - disabled).
     */
    public function getEnabledFeatures(): array
    {
        $planFeatures = $this->subscriptionPlan->features ?? [];
        $customFeatures = $this->custom_features ?? [];
        $disabledFeatures = $this->disabled_features ?? [];

        $allFeatures = array_unique(array_merge($planFeatures, $customFeatures));

        return array_diff($allFeatures, $disabledFeatures);
    }

    /**
     * Get effective limits (plan limits overridden by custom limits).
     */
    public function getEffectiveLimits(): array
    {
        $planLimits = $this->subscriptionPlan->limits ?? [];
        $customLimits = $this->custom_limits ?? [];

        return array_merge($planLimits, $customLimits);
    }

    /**
     * Check if a specific feature is enabled.
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->getEnabledFeatures());
    }

    /**
     * Get limit for a specific metric.
     */
    public function getLimit(string $metric): int|float
    {
        $limits = $this->getEffectiveLimits();
        return $limits[$metric] ?? 0;
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
            ->logOnly(['tenant_id', 'subscription_plan_id', 'custom_features', 'disabled_features', 'custom_limits'])
            ->logOnlyDirty()
            ->useLogName('tenant_customization')
            ->setDescriptionForEvent(fn(string $eventName) => "Tenant customization has been {$eventName}");
    }
}
