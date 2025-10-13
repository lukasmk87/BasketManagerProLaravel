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
            'price' => $this->price,
            'formatted_price' => $this->formatted_price,
            'currency' => $this->currency,
            'billing_period' => $this->billing_period,
            'billing_period_label' => $this->billing_period_label,
            'trial_days' => $this->trial_days,
            'is_active' => $this->is_active,
            'is_custom' => $this->is_custom,
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,

            // Stripe integration
            'stripe_price_id' => $this->stripe_price_id,
            'stripe_product_id' => $this->stripe_product_id,

            // Features and limits
            'features' => $this->getFeaturesWithNames(),
            'limits' => $this->getFormattedLimits(),
            'raw_features' => $this->features,
            'raw_limits' => $this->limits,

            // Statistics (when loaded)
            'tenants_count' => $this->whenCounted('tenants'),
            'active_tenants_count' => $this->when(
                $this->relationLoaded('tenants'),
                fn() => $this->active_tenant_count
            ),
            'monthly_revenue' => $this->when(
                $this->relationLoaded('tenants'),
                fn() => $this->monthly_revenue
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
