<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Carbon\Carbon;

class SubmitPlayerRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Public route - no authentication required
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Personal Information
            'first_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[\p{L}\s\-]+$/u', // Letters, spaces, hyphens only
            ],
            'last_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[\p{L}\s\-]+$/u',
            ],
            'birth_date' => [
                'required',
                'date',
                'before:today',
                'after:1900-01-01',
            ],

            // Contact Information
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'phone' => [
                'required',
                'string',
                'regex:/^[\+]?[0-9\s\-\(\)]{7,20}$/',
            ],

            // Address (optional)
            'street' => [
                'nullable',
                'string',
                'max:255',
            ],
            'postal_code' => [
                'nullable',
                'string',
                'max:10',
            ],
            'city' => [
                'nullable',
                'string',
                'max:255',
            ],
            'country' => [
                'nullable',
                'string',
                'max:255',
            ],

            // Basketball Information (optional)
            'position' => [
                'nullable',
                'in:PG,SG,SF,PF,C',
            ],
            'height' => [
                'nullable',
                'integer',
                'min:100',
                'max:250',
            ],
            'weight' => [
                'nullable',
                'integer',
                'min:30',
                'max:200',
            ],
            'experience' => [
                'nullable',
                'string',
                'max:1000',
            ],

            // GDPR Consent (required)
            'gdpr_consent' => [
                'required',
                'accepted',
            ],

            // Optional: Newsletter consent
            'newsletter_consent' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Check minimum age (6 years)
            if ($this->filled('birth_date')) {
                $birthDate = Carbon::parse($this->birth_date);
                $age = $birthDate->diffInYears(now());

                if ($age < 6) {
                    $validator->errors()->add(
                        'birth_date',
                        'Spieler müssen mindestens 6 Jahre alt sein.'
                    );
                }

                // Check maximum age (reasonable limit)
                if ($age > 100) {
                    $validator->errors()->add(
                        'birth_date',
                        'Bitte überprüfen Sie das Geburtsdatum.'
                    );
                }
            }

            // Validate email is not in use by deleted users
            if ($this->filled('email')) {
                $emailExists = \App\Models\User::withTrashed()
                    ->where('email', $this->email)
                    ->exists();

                if ($emailExists) {
                    $activeUser = \App\Models\User::where('email', $this->email)->first();

                    if (!$activeUser) {
                        $validator->errors()->add(
                            'email',
                            'Diese E-Mail-Adresse wurde bereits verwendet. Bitte kontaktieren Sie den Club-Administrator.'
                        );
                    }
                }
            }

            // Validate phone format more strictly
            if ($this->filled('phone')) {
                $phone = preg_replace('/[\s\-\(\)]/', '', $this->phone);

                if (strlen($phone) < 7 || strlen($phone) > 15) {
                    $validator->errors()->add(
                        'phone',
                        'Bitte geben Sie eine gültige Telefonnummer ein (7-15 Ziffern).'
                    );
                }
            }

            // If address fields are partially filled, require all address fields
            $addressFields = ['street', 'postal_code', 'city'];
            $filledAddressFields = array_filter($addressFields, function ($field) {
                return $this->filled($field);
            });

            if (count($filledAddressFields) > 0 && count($filledAddressFields) < 3) {
                $validator->errors()->add(
                    'street',
                    'Bitte füllen Sie alle Adressfelder aus (Straße, PLZ, Ort) oder lassen Sie alle leer.'
                );
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Personal Information
            'first_name.required' => 'Bitte geben Sie Ihren Vornamen an.',
            'first_name.max' => 'Der Vorname darf maximal :max Zeichen lang sein.',
            'first_name.regex' => 'Der Vorname darf nur Buchstaben, Leerzeichen und Bindestriche enthalten.',

            'last_name.required' => 'Bitte geben Sie Ihren Nachnamen an.',
            'last_name.max' => 'Der Nachname darf maximal :max Zeichen lang sein.',
            'last_name.regex' => 'Der Nachname darf nur Buchstaben, Leerzeichen und Bindestriche enthalten.',

            'birth_date.required' => 'Bitte geben Sie Ihr Geburtsdatum an.',
            'birth_date.date' => 'Bitte geben Sie ein gültiges Datum an.',
            'birth_date.before' => 'Das Geburtsdatum muss in der Vergangenheit liegen.',
            'birth_date.after' => 'Bitte überprüfen Sie Ihr Geburtsdatum.',

            // Contact Information
            'email.required' => 'Bitte geben Sie Ihre E-Mail-Adresse an.',
            'email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse an.',
            'email.unique' => 'Diese E-Mail-Adresse wird bereits verwendet.',

            'phone.required' => 'Bitte geben Sie Ihre Telefonnummer an.',
            'phone.regex' => 'Bitte geben Sie eine gültige Telefonnummer an (z.B. +49 123 456789).',

            // Basketball Information
            'position.in' => 'Bitte wählen Sie eine gültige Position (PG, SG, SF, PF, C).',

            'height.integer' => 'Die Körpergröße muss eine Zahl sein.',
            'height.min' => 'Die Körpergröße muss mindestens :min cm betragen.',
            'height.max' => 'Die Körpergröße darf maximal :max cm betragen.',

            'weight.integer' => 'Das Gewicht muss eine Zahl sein.',
            'weight.min' => 'Das Gewicht muss mindestens :min kg betragen.',
            'weight.max' => 'Das Gewicht darf maximal :max kg betragen.',

            'experience.max' => 'Die Beschreibung Ihrer Erfahrung darf maximal :max Zeichen lang sein.',

            // GDPR
            'gdpr_consent.required' => 'Bitte akzeptieren Sie die Datenschutzerklärung.',
            'gdpr_consent.accepted' => 'Sie müssen der Datenschutzerklärung zustimmen, um sich zu registrieren.',
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
            'first_name' => 'Vorname',
            'last_name' => 'Nachname',
            'birth_date' => 'Geburtsdatum',
            'email' => 'E-Mail-Adresse',
            'phone' => 'Telefonnummer',
            'street' => 'Straße',
            'postal_code' => 'Postleitzahl',
            'city' => 'Ort',
            'country' => 'Land',
            'position' => 'Position',
            'height' => 'Körpergröße',
            'weight' => 'Gewicht',
            'experience' => 'Erfahrung',
            'gdpr_consent' => 'Datenschutzerklärung',
            'newsletter_consent' => 'Newsletter',
        ];
    }
}
