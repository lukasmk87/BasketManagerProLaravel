<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => (int) $this->price, // Cast to integer (cents) for JSON serialization
            'formatted_price' => $this->formatted_price,
            'currency' => $this->currency,
            'billing_period' => $this->billing_period,
            'billing_period_label' => $this->billing_period_label,
            'trial_days' => $this->trial_days,
            'is_active' => (bool) $this->is_active, // Explicit boolean cast
            'is_custom' => (bool) $this->is_custom,
            'is_featured' => (bool) $this->is_featured,
            'sort_order' => (int) $this->sort_order,

            // Stripe integration
            'stripe_price_id' => $this->stripe_price_id,
            'stripe_product_id' => $this->stripe_product_id,

            // Features and limits
            'features' => collect($this->features ?? [])->map(function ($feature) {
                return config('tenants.features')[$feature] ?? $feature;
            })->toArray(),
            'features_with_slugs' => $this->getFeaturesWithNames(),
            'limits' => $this->limits ?? [],
            'formatted_limits' => $this->getFormattedLimits(),
            'raw_features' => $this->features,

            // Statistics (when loaded)
            'tenants_count' => $this->whenCounted('tenants'),
            'active_tenants_count' => $this->when(
                $this->relationLoaded('tenants'),
                fn() => (int) $this->active_tenant_count
            ),
            'monthly_revenue' => $this->when(
                $this->relationLoaded('tenants'),
                fn() => (int) $this->monthly_revenue // Cast to integer (cents)
            ),

            // Metadata
            'metadata' => $this->metadata,
            'is_free' => $this->isFree(),

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->when($this->deleted_at, fn() => $this->deleted_at->toISOString()),
        ];
    }
}
