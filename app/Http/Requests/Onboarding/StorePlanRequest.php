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
        ];
    }
}
