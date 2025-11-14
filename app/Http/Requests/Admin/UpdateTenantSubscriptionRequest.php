<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantSubscriptionRequest extends FormRequest
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
            'subscription_plan_id' => ['required', 'exists:subscription_plans,id'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'subscription_plan_id' => 'Subscription-Plan',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'subscription_plan_id.required' => 'Bitte wählen Sie einen Subscription-Plan aus.',
            'subscription_plan_id.exists' => 'Der ausgewählte Subscription-Plan existiert nicht.',
        ];
    }
}
