<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClubSubscriptionPlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'formatted_price' => $this->formatted_price,
            'currency' => $this->currency,
            'billing_interval' => $this->billing_interval,
            'billing_interval_label' => $this->billing_interval === 'yearly' ? 'pro Jahr' : 'pro Monat',
            'trial_period_days' => $this->trial_period_days,
            'is_active' => (bool) $this->is_active,
            'is_default' => (bool) $this->is_default,
            'sort_order' => (int) $this->sort_order,
            'color' => $this->color,
            'icon' => $this->icon,

            // Stripe integration
            'stripe_product_id' => $this->stripe_product_id,
            'stripe_price_id_monthly' => $this->stripe_price_id_monthly,
            'stripe_price_id_yearly' => $this->stripe_price_id_yearly,
            'is_stripe_synced' => (bool) $this->is_stripe_synced,
            'last_stripe_sync_at' => $this->last_stripe_sync_at?->toISOString(),

            // Features and limits
            'features' => $this->features ?? [],
            'limits' => $this->limits ?? [],
            'formatted_limits' => $this->getFormattedLimits(),

            // Relationships (when loaded)
            'tenant' => $this->whenLoaded('tenant', function () {
                return [
                    'id' => $this->tenant->id,
                    'name' => $this->tenant->name,
                    'slug' => $this->tenant->slug,
                ];
            }),

            // Statistics (when counted)
            'clubs_count' => $this->whenCounted('clubs'),

            // Helper flags
            'is_free' => $this->price == 0,

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Get formatted limits for display.
     */
    protected function getFormattedLimits(): array
    {
        $limits = $this->limits ?? [];
        $formatted = [];

        $labels = [
            'max_teams' => 'Teams',
            'max_players' => 'Spieler',
            'max_storage_gb' => 'Speicher',
            'max_games_per_month' => 'Spiele/Monat',
            'max_training_sessions_per_month' => 'Trainings/Monat',
            'max_api_calls_per_hour' => 'API Calls/Stunde',
        ];

        foreach ($limits as $key => $value) {
            $formatted[$key] = [
                'value' => $value,
                'label' => $labels[$key] ?? $key,
                'formatted' => $value === -1 ? 'Unbegrenzt' : number_format($value, 0, ',', '.'),
                'unlimited' => $value === -1,
            ];
        }

        return $formatted;
    }
}
