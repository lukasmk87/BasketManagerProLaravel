<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage-subscriptions');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $planId = $this->route('plan')->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('subscription_plans')->ignore($planId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['sometimes', 'numeric', 'min:0', 'max:999999.99'],
            'currency' => ['sometimes', 'string', 'size:3', 'uppercase'],
            'billing_period' => ['sometimes', 'in:monthly,yearly,quarterly'],
            'stripe_price_id' => ['nullable', 'string', 'max:255'],
            'stripe_product_id' => ['nullable', 'string', 'max:255'],
            'trial_days' => ['sometimes', 'integer', 'min:0', 'max:365'],
            'is_active' => ['boolean'],
            'is_custom' => ['boolean'],
            'is_featured' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],

            // Features array
            'features' => ['sometimes', 'array'],
            'features.*' => ['string', 'max:255'],

            // Limits array
            'limits' => ['sometimes', 'array'],
            'limits.users' => ['sometimes', 'integer', 'min:-1'],
            'limits.teams' => ['sometimes', 'integer', 'min:-1'],
            'limits.players' => ['sometimes', 'integer', 'min:-1'],
            'limits.storage_gb' => ['sometimes', 'integer', 'min:-1'],
            'limits.api_calls_per_hour' => ['sometimes', 'integer', 'min:-1'],
            'limits.games_per_month' => ['nullable', 'integer', 'min:-1'],
            'limits.training_sessions_per_month' => ['nullable', 'integer', 'min:-1'],

            // Optional metadata
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'Plan-Name',
            'slug' => 'URL-Slug',
            'description' => 'Beschreibung',
            'price' => 'Preis',
            'currency' => 'Währung',
            'billing_period' => 'Abrechnungszeitraum',
            'trial_days' => 'Testzeitraum (Tage)',
            'features' => 'Features',
            'limits.users' => 'Benutzer-Limit',
            'limits.teams' => 'Team-Limit',
            'limits.players' => 'Spieler-Limit',
            'limits.storage_gb' => 'Speicher-Limit',
            'limits.api_calls_per_hour' => 'API-Aufrufe-Limit',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'slug.unique' => 'Ein Plan mit diesem Slug existiert bereits.',
            'currency.size' => 'Währung muss ein 3-stelliger ISO-Code sein (z.B. EUR, USD).',
            'billing_period.in' => 'Abrechnungszeitraum muss monthly, yearly oder quarterly sein.',
            'limits.*.min' => 'Limit muss -1 (unbegrenzt) oder größer sein.',
        ];
    }
}
