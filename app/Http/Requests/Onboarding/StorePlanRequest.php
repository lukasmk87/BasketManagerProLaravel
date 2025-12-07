<?php

namespace App\Http\Requests\Onboarding;

use App\Models\ClubSubscriptionPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'plan_id' => [
                'required',
                'string',
                Rule::exists('club_subscription_plans', 'id')->where(function ($query) {
                    // Only allow publicly available plans (active + featured)
                    $query->where('is_active', true)
                          ->where('is_featured', true);
                }),
            ],
            'billing_interval' => ['nullable', 'string', Rule::in(['monthly', 'yearly'])],
            'payment_method' => ['required', 'string', Rule::in(['stripe', 'invoice'])],
            'billing_name' => ['required_if:payment_method,invoice', 'nullable', 'string', 'max:255'],
            'billing_email' => ['required_if:payment_method,invoice', 'nullable', 'email', 'max:255'],
            'billing_address' => ['required_if:payment_method,invoice', 'nullable', 'array'],
            'billing_address.street' => ['required_if:payment_method,invoice', 'nullable', 'string', 'max:255'],
            'billing_address.postal_code' => ['required_if:payment_method,invoice', 'nullable', 'string', 'max:20'],
            'billing_address.city' => ['required_if:payment_method,invoice', 'nullable', 'string', 'max:255'],
            'billing_address.country' => ['required_if:payment_method,invoice', 'nullable', 'string', 'max:2'],
            'vat_number' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'plan_id.required' => 'Bitte wähle einen Plan aus.',
            'plan_id.exists' => 'Der ausgewählte Plan ist nicht verfügbar.',
            'billing_interval.in' => 'Bitte wähle ein gültiges Abrechnungsintervall.',
            'payment_method.required' => 'Bitte wähle eine Zahlungsmethode.',
            'payment_method.in' => 'Ungültige Zahlungsmethode.',
            'billing_name.required_if' => 'Bitte gib einen Rechnungsnamen an.',
            'billing_email.required_if' => 'Bitte gib eine Rechnungs-E-Mail an.',
            'billing_email.email' => 'Bitte gib eine gültige E-Mail-Adresse an.',
            'billing_address.street.required_if' => 'Bitte gib eine Straße an.',
            'billing_address.postal_code.required_if' => 'Bitte gib eine Postleitzahl an.',
            'billing_address.city.required_if' => 'Bitte gib eine Stadt an.',
            'billing_address.country.required_if' => 'Bitte wähle ein Land.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'plan_id' => 'Plan',
            'billing_interval' => 'Abrechnungsintervall',
            'payment_method' => 'Zahlungsmethode',
            'billing_name' => 'Rechnungsname',
            'billing_email' => 'Rechnungs-E-Mail',
            'billing_address.street' => 'Straße',
            'billing_address.postal_code' => 'Postleitzahl',
            'billing_address.city' => 'Stadt',
            'billing_address.country' => 'Land',
            'vat_number' => 'USt-IdNr.',
        ];
    }
}
