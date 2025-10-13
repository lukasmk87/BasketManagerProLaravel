<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole(['admin', 'super_admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tenantId = $this->route('tenant')->id;

        return [
            // Basic Info
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('tenants', 'slug')->ignore($tenantId), 'alpha_dash'],
            'domain' => ['nullable', 'string', 'max:255', Rule::unique('tenants', 'domain')->ignore($tenantId)],
            'subdomain' => ['nullable', 'string', 'max:255', Rule::unique('tenants', 'subdomain')->ignore($tenantId), 'alpha_dash'],

            // Billing Information
            'billing_email' => ['nullable', 'email', 'max:255'],
            'billing_name' => ['nullable', 'string', 'max:255'],
            'billing_address' => ['nullable', 'string', 'max:500'],
            'vat_number' => ['nullable', 'string', 'max:50'],
            'country_code' => ['nullable', 'string', 'size:2'],

            // Configuration
            'timezone' => ['nullable', 'string', 'max:50'],
            'locale' => ['nullable', 'string', 'max:5'],
            'currency' => ['nullable', 'string', 'size:3'],

            // Subscription (optional - can be updated via updateSubscription method)
            'subscription_tier' => ['nullable', Rule::in(['free', 'basic', 'professional', 'enterprise'])],
            'subscription_plan_id' => ['nullable', 'exists:subscription_plans,id'],
            'trial_ends_at' => ['nullable', 'date'],

            // Status
            'is_active' => ['boolean'],
            'is_suspended' => ['boolean'],
            'suspension_reason' => ['nullable', 'string', 'max:500'],

            // Limits (optional - can be updated via updateLimits method)
            'max_users' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'max_teams' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'max_storage_gb' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'max_api_calls_per_hour' => ['nullable', 'integer', 'min:0', 'max:1000000'],

            // Additional Settings
            'features' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
            'branding' => ['nullable', 'array'],
            'security_settings' => ['nullable', 'array'],
            'allowed_domains' => ['nullable', 'array'],
            'blocked_ips' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Der Tenant-Name ist erforderlich.',
            'slug.unique' => 'Dieser Slug ist bereits vergeben.',
            'slug.alpha_dash' => 'Der Slug darf nur Buchstaben, Zahlen, Bindestriche und Unterstriche enthalten.',
            'domain.unique' => 'Diese Domain ist bereits vergeben.',
            'subdomain.unique' => 'Diese Subdomain ist bereits vergeben.',
            'subdomain.alpha_dash' => 'Die Subdomain darf nur Buchstaben, Zahlen, Bindestriche und Unterstriche enthalten.',
            'billing_email.email' => 'Die Billing-E-Mail-Adresse muss gültig sein.',
            'country_code.size' => 'Der Ländercode muss genau 2 Zeichen lang sein.',
            'currency.size' => 'Die Währung muss genau 3 Zeichen lang sein (z.B. EUR, USD).',
            'subscription_tier.in' => 'Die Subscription-Stufe ist ungültig.',
            'subscription_plan_id.exists' => 'Der ausgewählte Subscription-Plan existiert nicht.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'Name',
            'slug' => 'Slug',
            'domain' => 'Domain',
            'subdomain' => 'Subdomain',
            'billing_email' => 'Billing E-Mail',
            'billing_name' => 'Billing Name',
            'billing_address' => 'Billing Adresse',
            'vat_number' => 'USt-IdNr.',
            'country_code' => 'Ländercode',
            'timezone' => 'Zeitzone',
            'locale' => 'Sprache',
            'currency' => 'Währung',
            'subscription_tier' => 'Subscription-Stufe',
            'subscription_plan_id' => 'Subscription-Plan',
            'trial_ends_at' => 'Trial-Ende',
            'is_active' => 'Aktiv',
            'is_suspended' => 'Gesperrt',
            'suspension_reason' => 'Sperrgrund',
            'max_users' => 'Max. Benutzer',
            'max_teams' => 'Max. Teams',
            'max_storage_gb' => 'Max. Speicher (GB)',
            'max_api_calls_per_hour' => 'Max. API-Calls pro Stunde',
        ];
    }
}
