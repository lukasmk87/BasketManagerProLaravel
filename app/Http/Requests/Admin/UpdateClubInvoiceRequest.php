<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClubInvoiceRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'club_subscription_plan_id' => ['nullable', 'exists:club_subscription_plans,id'],
            'net_amount' => ['sometimes', 'required', 'numeric', 'min:0.01', 'max:999999.99'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'billing_period' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'line_items' => ['nullable', 'array'],
            'line_items.*.description' => ['required_with:line_items', 'string', 'max:255'],
            'line_items.*.quantity' => ['required_with:line_items', 'integer', 'min:1'],
            'line_items.*.unit_price' => ['required_with:line_items', 'numeric', 'min:0'],
            'line_items.*.total' => ['required_with:line_items', 'numeric', 'min:0'],
            'billing_name' => ['nullable', 'string', 'max:255'],
            'billing_email' => ['nullable', 'email', 'max:255'],
            'billing_address' => ['nullable', 'array'],
            'billing_address.street' => ['nullable', 'string', 'max:255'],
            'billing_address.zip' => ['nullable', 'string', 'max:20'],
            'billing_address.city' => ['nullable', 'string', 'max:100'],
            'billing_address.country' => ['nullable', 'string', 'max:100'],
            'vat_number' => ['nullable', 'string', 'max:50'],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'club_subscription_plan_id' => 'Subscription-Plan',
            'net_amount' => 'Nettobetrag',
            'tax_rate' => 'Steuersatz',
            'billing_period' => 'Abrechnungszeitraum',
            'description' => 'Beschreibung',
            'line_items' => 'Rechnungsposten',
            'billing_name' => 'Rechnungsempfänger',
            'billing_email' => 'E-Mail-Adresse',
            'billing_address' => 'Rechnungsadresse',
            'vat_number' => 'USt-IdNr.',
            'issue_date' => 'Rechnungsdatum',
            'due_date' => 'Fälligkeitsdatum',
        ];
    }
}
