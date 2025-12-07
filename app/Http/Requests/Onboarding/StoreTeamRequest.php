<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'age_group' => [
                'required',
                'string',
                Rule::in(['u8', 'u10', 'u12', 'u14', 'u16', 'u18', 'u20', 'senior:male', 'senior:female', 'senior:mixed']),
            ],
            'gender' => ['nullable', 'string', Rule::in(['male', 'female', 'mixed'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Bitte gib einen Namen für dein Team ein.',
            'name.max' => 'Der Team-Name darf maximal 255 Zeichen lang sein.',
            'age_group.required' => 'Bitte wähle eine Altersgruppe für dein Team.',
            'age_group.in' => 'Bitte wähle eine gültige Altersgruppe.',
            'gender.in' => 'Bitte wähle ein gültiges Geschlecht.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'Team-Name',
            'age_group' => 'Altersgruppe',
            'gender' => 'Geschlecht',
        ];
    }
}
