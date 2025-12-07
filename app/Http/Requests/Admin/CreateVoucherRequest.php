<?php

namespace App\Http\Requests\Admin;

use App\Models\Voucher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateVoucherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole('super_admin');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'tenant_id' => 'nullable|uuid|exists:tenants,id',
            'code' => 'nullable|string|max:50|unique:vouchers,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => ['required', Rule::in([
                Voucher::TYPE_PERCENT,
                Voucher::TYPE_FIXED_AMOUNT,
                Voucher::TYPE_TRIAL_EXTENSION,
            ])],
            'discount_percent' => 'required_if:type,percent|nullable|numeric|min:0.01|max:100',
            'discount_amount' => 'required_if:type,fixed_amount|nullable|numeric|min:0.01',
            'trial_extension_days' => 'required_if:type,trial_extension|nullable|integer|min:1|max:365',
            'duration_months' => 'required|integer|min:1|max:36',
            'max_redemptions' => 'nullable|integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'applicable_plan_ids' => 'nullable|array',
            'applicable_plan_ids.*' => 'uuid|exists:club_subscription_plans,id',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'discount_percent.required_if' => 'Bitte gib einen Prozentsatz an.',
            'discount_percent.min' => 'Der Prozentsatz muss mindestens 0.01% sein.',
            'discount_percent.max' => 'Der Prozentsatz darf maximal 100% sein.',
            'discount_amount.required_if' => 'Bitte gib einen Rabattbetrag an.',
            'discount_amount.min' => 'Der Rabattbetrag muss mindestens 0.01 EUR sein.',
            'trial_extension_days.required_if' => 'Bitte gib die Anzahl der Trial-Tage an.',
            'trial_extension_days.min' => 'Die Trial-Verlängerung muss mindestens 1 Tag sein.',
            'trial_extension_days.max' => 'Die Trial-Verlängerung darf maximal 365 Tage sein.',
            'duration_months.required' => 'Bitte gib die Dauer in Monaten an.',
            'duration_months.min' => 'Die Dauer muss mindestens 1 Monat sein.',
            'duration_months.max' => 'Die Dauer darf maximal 36 Monate sein.',
            'valid_until.after_or_equal' => 'Das Enddatum muss nach dem Startdatum liegen.',
            'code.unique' => 'Dieser Voucher-Code existiert bereits.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'tenant_id' => 'Tenant',
            'code' => 'Voucher-Code',
            'name' => 'Name',
            'description' => 'Beschreibung',
            'type' => 'Voucher-Typ',
            'discount_percent' => 'Rabatt-Prozent',
            'discount_amount' => 'Rabattbetrag',
            'trial_extension_days' => 'Trial-Verlängerung (Tage)',
            'duration_months' => 'Dauer (Monate)',
            'max_redemptions' => 'Max. Einlösungen',
            'valid_from' => 'Gültig ab',
            'valid_until' => 'Gültig bis',
            'applicable_plan_ids' => 'Anwendbare Pläne',
            'is_active' => 'Aktiv',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert empty strings to null
        if ($this->code === '') {
            $this->merge(['code' => null]);
        }

        // Ensure code is uppercase
        if ($this->code) {
            $this->merge(['code' => strtoupper($this->code)]);
        }
    }
}
