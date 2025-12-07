<?php

namespace App\Http\Requests\TenantAdmin;

use App\Models\Voucher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVoucherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole('tenant_admin');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $voucher = $this->route('voucher');

        return [
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('vouchers', 'code')->ignore($voucher->id),
            ],
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => ['sometimes', 'required', Rule::in([
                Voucher::TYPE_PERCENT,
                Voucher::TYPE_FIXED_AMOUNT,
                Voucher::TYPE_TRIAL_EXTENSION,
            ])],
            'discount_percent' => 'required_if:type,percent|nullable|numeric|min:0.01|max:100',
            'discount_amount' => 'required_if:type,fixed_amount|nullable|numeric|min:0.01|max:1000',
            'trial_extension_days' => 'required_if:type,trial_extension|nullable|integer|min:1|max:90',
            'duration_months' => 'sometimes|required|integer|min:1|max:12',
            'max_redemptions' => 'nullable|integer|min:1|max:1000',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'applicable_plan_ids' => 'nullable|array',
            'applicable_plan_ids.*' => [
                'uuid',
                Rule::exists('club_subscription_plans', 'id'),
            ],
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
            'discount_amount.required_if' => 'Bitte gib einen Rabattbetrag an.',
            'trial_extension_days.required_if' => 'Bitte gib die Anzahl der Trial-Tage an.',
            'valid_until.after_or_equal' => 'Das Enddatum muss nach dem Startdatum liegen.',
            'code.unique' => 'Dieser Voucher-Code existiert bereits.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure code is uppercase
        if ($this->code) {
            $this->merge(['code' => strtoupper($this->code)]);
        }
    }
}
