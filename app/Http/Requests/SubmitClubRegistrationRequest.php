<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitClubRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Public registration - no authentication required
     */
    public function authorize(): bool
    {
        return true; // Public endpoint, no auth required
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed', // Requires password_confirmation field
            ],
            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],
            'date_of_birth' => [
                'nullable',
                'date',
                'before:today',
            ],
            'gender' => [
                'nullable',
                'string',
                'in:male,female,other,prefer_not_to_say',
            ],
            'gdpr_consent' => [
                'required',
                'boolean',
                'accepted',
            ],
            'terms_consent' => [
                'required',
                'boolean',
                'accepted',
            ],
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
            'name' => 'Name',
            'email' => 'E-Mail-Adresse',
            'password' => 'Passwort',
            'password_confirmation' => 'Passwort-Bestätigung',
            'phone' => 'Telefonnummer',
            'date_of_birth' => 'Geburtsdatum',
            'gender' => 'Geschlecht',
            'gdpr_consent' => 'Datenschutz-Zustimmung',
            'terms_consent' => 'AGB-Zustimmung',
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
            'email.unique' => 'Diese E-Mail-Adresse ist bereits registriert.',
            'password.min' => 'Das Passwort muss mindestens :min Zeichen lang sein.',
            'password.confirmed' => 'Die Passwort-Bestätigung stimmt nicht überein.',
            'date_of_birth.before' => 'Das Geburtsdatum muss in der Vergangenheit liegen.',
            'gdpr_consent.accepted' => 'Sie müssen der Datenschutzerklärung zustimmen.',
            'terms_consent.accepted' => 'Sie müssen den Nutzungsbedingungen zustimmen.',
        ];
    }
}
