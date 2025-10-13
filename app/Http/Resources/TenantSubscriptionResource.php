<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantSubscriptionResource extends JsonResource
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

            // Domain and subdomain
            'domain' => $this->domain,
            'subdomain' => $this->subdomain,
            'url' => $this->getUrl(),

            // Subscription information
            'subscription_tier' => $this->subscription_tier,
            'subscription_plan' => new SubscriptionPlanResource($this->whenLoaded('subscriptionPlan')),
            'subscription_plan_id' => $this->subscription_plan_id,

            // Customization
            'customization' => $this->whenLoaded('activeCustomization', function () {
                if (!$this->activeCustomization) {
                    return null;
                }

                return [
                    'id' => $this->activeCustomization->id,
                    'custom_features' => $this->activeCustomization->custom_features,
                    'disabled_features' => $this->activeCustomization->disabled_features,
                    'custom_limits' => $this->activeCustomization->custom_limits,
                    'notes' => $this->activeCustomization->notes,
                    'effective_from' => $this->activeCustomization->effective_from?->toISOString(),
                    'effective_until' => $this->activeCustomization->effective_until?->toISOString(),
                ];
            }),

            // Status
            'is_active' => $this->is_active,
            'is_suspended' => $this->is_suspended,
            'suspension_reason' => $this->when($this->is_suspended, fn() => $this->suspension_reason),

            // Trial information
            'trial_ends_at' => $this->trial_ends_at?->toISOString(),
            'is_trial_expired' => $this->isTrialExpired(),
            'has_active_subscription' => $this->hasActiveSubscription(),

            // Current usage counts
            'current_counts' => [
                'users' => $this->current_users_count ?? $this->whenCounted('users'),
                'teams' => $this->current_teams_count ?? $this->whenCounted('teams'),
                'players' => $this->whenCounted('players'),
                'games' => $this->whenCounted('games'),
                'storage_gb' => round($this->current_storage_gb ?? 0, 2),
            ],

            // Maximum limits
            'max_limits' => [
                'users' => $this->max_users,
                'teams' => $this->max_teams,
                'storage_gb' => $this->max_storage_gb,
                'api_calls_per_hour' => $this->max_api_calls_per_hour,
            ],

            // Billing information
            'billing_email' => $this->billing_email,
            'billing_name' => $this->billing_name,
            'country_code' => $this->country_code,
            'currency' => $this->currency,

            // Revenue
            'revenue' => [
                'total' => round($this->total_revenue ?? 0, 2),
                'mrr' => round($this->monthly_recurring_revenue ?? 0, 2),
            ],

            // Payment status
            'payment_status' => $this->payment_status,
            'last_payment_at' => $this->last_payment_at?->toISOString(),
            'payment_failed_at' => $this->when($this->payment_failed_at, fn() => $this->payment_failed_at->toISOString()),

            // Activity
            'last_login_at' => $this->last_login_at?->toISOString(),
            'last_activity_at' => $this->last_activity_at?->toISOString(),
            'total_logins' => $this->total_logins,

            // Compliance
            'gdpr_accepted' => $this->gdpr_accepted,
            'gdpr_accepted_at' => $this->when($this->gdpr_accepted, fn() => $this->gdpr_accepted_at?->toISOString()),
            'terms_accepted' => $this->terms_accepted,
            'terms_accepted_at' => $this->when($this->terms_accepted, fn() => $this->terms_accepted_at?->toISOString()),

            // Settings
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'features' => $this->features,

            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'onboarded_at' => $this->onboarded_at?->toISOString(),
        ];
    }
}
