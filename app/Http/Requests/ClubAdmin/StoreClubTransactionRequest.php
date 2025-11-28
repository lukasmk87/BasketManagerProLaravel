<?php

namespace App\Http\Requests\ClubAdmin;

use App\Models\ClubTransaction;
use Illuminate\Foundation\Http\FormRequest;

class StoreClubTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categories = implode(',', array_keys(ClubTransaction::getCategories()));

        return [
            'type' => ['required', 'in:income,expense'],
            'category' => ['required', 'in:' . $categories],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'currency' => ['nullable', 'string', 'size:3'],
            'description' => ['nullable', 'string', 'max:1000'],
            'transaction_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'Typ',
            'category' => 'Kategorie',
            'amount' => 'Betrag',
            'currency' => 'Währung',
            'description' => 'Beschreibung',
            'transaction_date' => 'Datum',
            'reference_number' => 'Referenznummer',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Der Transaktionstyp ist erforderlich.',
            'type.in' => 'Der Transaktionstyp muss Einnahme oder Ausgabe sein.',
            'category.required' => 'Die Kategorie ist erforderlich.',
            'category.in' => 'Die ausgewählte Kategorie ist ungültig.',
            'amount.required' => 'Der Betrag ist erforderlich.',
            'amount.numeric' => 'Der Betrag muss eine Zahl sein.',
            'amount.min' => 'Der Betrag muss mindestens 0,01 betragen.',
            'amount.max' => 'Der Betrag darf maximal 999.999,99 betragen.',
            'currency.size' => 'Die Währung muss ein 3-stelliger Code sein (z.B. EUR).',
            'transaction_date.required' => 'Das Transaktionsdatum ist erforderlich.',
            'transaction_date.date' => 'Das Transaktionsdatum muss ein gültiges Datum sein.',
        ];
    }
}
