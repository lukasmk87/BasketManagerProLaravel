<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantLimitsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization removed - route already protected by AdminMiddleware
        // which checks for super_admin/admin role OR manage-subscriptions permission
        // This prevents 403 errors when permission cache is stale during installation
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'max_users' => ['required', 'integer', 'min:-1', 'max:100000'],
            'max_teams' => ['required', 'integer', 'min:-1', 'max:10000'],
            'max_storage_gb' => ['required', 'numeric', 'min:-1', 'max:10000'],
            'max_api_calls_per_hour' => ['required', 'integer', 'min:-1', 'max:1000000'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'max_users' => 'Maximale Benutzer',
            'max_teams' => 'Maximale Teams',
            'max_storage_gb' => 'Maximaler Speicher (GB)',
            'max_api_calls_per_hour' => 'Maximale API-Aufrufe pro Stunde',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            '*.min' => ':attribute muss -1 (unbegrenzt) oder größer sein.',
            '*.max' => ':attribute darf nicht größer als :max sein.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert empty strings to null
        $this->merge([
            'max_users' => $this->max_users === '' ? null : $this->max_users,
            'max_teams' => $this->max_teams === '' ? null : $this->max_teams,
            'max_storage_gb' => $this->max_storage_gb === '' ? null : $this->max_storage_gb,
            'max_api_calls_per_hour' => $this->max_api_calls_per_hour === '' ? null : $this->max_api_calls_per_hour,
        ]);
    }
}
