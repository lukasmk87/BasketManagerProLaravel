<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateCustomizationRequest extends FormRequest
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
        return [
            // Custom features to add
            'custom_features' => ['nullable', 'array'],
            'custom_features.*' => ['string', 'max:255'],

            // Features to disable
            'disabled_features' => ['nullable', 'array'],
            'disabled_features.*' => ['string', 'max:255'],

            // Custom limits (overrides plan limits)
            'custom_limits' => ['nullable', 'array'],
            'custom_limits.users' => ['nullable', 'integer', 'min:-1'],
            'custom_limits.teams' => ['nullable', 'integer', 'min:-1'],
            'custom_limits.players' => ['nullable', 'integer', 'min:-1'],
            'custom_limits.storage_gb' => ['nullable', 'integer', 'min:-1'],
            'custom_limits.api_calls_per_hour' => ['nullable', 'integer', 'min:-1'],
            'custom_limits.games_per_month' => ['nullable', 'integer', 'min:-1'],
            'custom_limits.training_sessions_per_month' => ['nullable', 'integer', 'min:-1'],

            // Notes and dates
            'notes' => ['nullable', 'string', 'max:1000'],
            'effective_from' => ['nullable', 'date', 'after_or_equal:today'],
            'effective_until' => ['nullable', 'date', 'after:effective_from'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'custom_features' => 'Zusätzliche Features',
            'disabled_features' => 'Deaktivierte Features',
            'custom_limits' => 'Angepasste Limits',
            'custom_limits.users' => 'Benutzer-Limit',
            'custom_limits.teams' => 'Team-Limit',
            'custom_limits.players' => 'Spieler-Limit',
            'custom_limits.storage_gb' => 'Speicher-Limit',
            'custom_limits.api_calls_per_hour' => 'API-Aufrufe-Limit',
            'notes' => 'Notizen',
            'effective_from' => 'Gültig ab',
            'effective_until' => 'Gültig bis',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'effective_from.after_or_equal' => 'Das Startdatum darf nicht in der Vergangenheit liegen.',
            'effective_until.after' => 'Das Enddatum muss nach dem Startdatum liegen.',
            'custom_limits.*.min' => 'Limit muss -1 (unbegrenzt) oder größer sein.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Remove empty arrays
        if (empty($this->custom_features)) {
            $this->merge(['custom_features' => []]);
        }

        if (empty($this->disabled_features)) {
            $this->merge(['disabled_features' => []]);
        }

        if (empty($this->custom_limits)) {
            $this->merge(['custom_limits' => []]);
        }
    }
}
