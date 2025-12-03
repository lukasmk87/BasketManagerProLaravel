<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePricingSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isSuperAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'display_mode' => ['required', 'string', 'in:gross,net'],
            'is_small_business' => ['required', 'boolean'],
            'default_tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'display_mode.required' => 'Der Preisanzeige-Modus ist erforderlich.',
            'display_mode.in' => 'Der Preisanzeige-Modus muss "gross" oder "net" sein.',
            'is_small_business.required' => 'Die Kleinunternehmer-Einstellung ist erforderlich.',
            'is_small_business.boolean' => 'Die Kleinunternehmer-Einstellung muss ein Wahrheitswert sein.',
            'default_tax_rate.required' => 'Der MwSt.-Satz ist erforderlich.',
            'default_tax_rate.numeric' => 'Der MwSt.-Satz muss eine Zahl sein.',
            'default_tax_rate.min' => 'Der MwSt.-Satz darf nicht negativ sein.',
            'default_tax_rate.max' => 'Der MwSt.-Satz darf maximal 100% betragen.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'display_mode' => 'Preisanzeige-Modus',
            'is_small_business' => 'Kleinunternehmer-Regelung',
            'default_tax_rate' => 'MwSt.-Satz',
        ];
    }
}
