<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClubPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $planId = $this->route('plan')?->id ?? $this->route('plan');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('club_subscription_plans')
                    ->where('tenant_id', $this->input('tenant_id', $this->route('plan')?->tenant_id))
                    ->ignore($planId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'currency' => ['required', 'string', 'size:3', 'uppercase'],
            'billing_interval' => ['required', 'in:monthly,yearly'],
            'trial_period_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'max:50'],

            // Features array
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255'],

            // Limits array
            'limits' => ['required', 'array'],
            'limits.max_teams' => ['required', 'integer', 'min:-1'],
            'limits.max_players' => ['required', 'integer', 'min:-1'],
            'limits.max_storage_gb' => ['nullable', 'integer', 'min:-1'],
            'limits.max_games_per_month' => ['nullable', 'integer', 'min:-1'],
            'limits.max_training_sessions_per_month' => ['nullable', 'integer', 'min:-1'],
            'limits.max_api_calls_per_hour' => ['nullable', 'integer', 'min:-1'],
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
            'billing_interval' => 'Abrechnungsintervall',
            'trial_period_days' => 'Testzeitraum (Tage)',
            'features' => 'Features',
            'limits.max_teams' => 'Team-Limit',
            'limits.max_players' => 'Spieler-Limit',
            'limits.max_storage_gb' => 'Speicher-Limit',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'slug.unique' => 'Ein Plan mit diesem Slug existiert bereits für diesen Tenant.',
            'currency.size' => 'Währung muss ein 3-stelliger ISO-Code sein (z.B. EUR, USD).',
            'billing_interval.in' => 'Abrechnungsintervall muss monthly oder yearly sein.',
            'color.regex' => 'Farbe muss ein gültiger Hex-Code sein (z.B. #FF5733).',
            'limits.*.min' => 'Limit muss -1 (unbegrenzt) oder größer sein.',
        ];
    }
}
