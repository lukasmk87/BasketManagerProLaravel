<?php

namespace App\Http\Requests\Api\V2\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['nullable', 'string', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:50'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other', 'prefer_not_to_say'])],
            
            // Address
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:50'],
            
            // Preferences
            'language' => ['nullable', 'string', Rule::in(['de', 'en'])],
            'timezone' => ['nullable', 'string', 'timezone'],
            'date_format' => ['nullable', 'string', Rule::in(['d.m.Y', 'm/d/Y', 'Y-m-d'])],
            'time_format' => ['nullable', 'string', Rule::in(['H:i', 'h:i A'])],
            
            // Status
            'is_active' => ['boolean'],
            'email_verified_at' => ['nullable', 'date'],
            
            // Profile
            'avatar_url' => ['nullable', 'url', 'max:500'],
            
            // Emergency Contact
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:100'],
            
            // Medical Information
            'medical_notes' => ['nullable', 'string', 'max:2000'],
            'allergies' => ['nullable', 'array'],
            'allergies.*' => ['string', 'max:255'],
            'medications' => ['nullable', 'array'],
            'medications.*' => ['string', 'max:255'],
            
            // Consent
            'consent_marketing' => ['boolean'],
            'consent_data_processing' => ['boolean'],
            'consent_medical_info_sharing' => ['boolean'],
            
            // Roles
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            
            // Player Data (if user has player role)
            'player_data' => ['nullable', 'array'],
            'player_data.team_id' => ['nullable', 'exists:teams,id'],
            'player_data.jersey_number' => ['nullable', 'integer', 'min:0', 'max:99'],
            'player_data.primary_position' => ['nullable', Rule::in(['PG', 'SG', 'SF', 'PF', 'C'])],
            'player_data.secondary_positions' => ['nullable', 'array'],
            'player_data.secondary_positions.*' => [Rule::in(['PG', 'SG', 'SF', 'PF', 'C'])],
            'player_data.height_cm' => ['nullable', 'integer', 'min:120', 'max:250'],
            'player_data.weight_kg' => ['nullable', 'numeric', 'min:30', 'max:200'],
            'player_data.dominant_hand' => ['nullable', Rule::in(['left', 'right', 'ambidextrous'])],
            'player_data.years_experience' => ['nullable', 'integer', 'min:0', 'max:50'],
            'player_data.previous_teams' => ['nullable', 'array'],
            'player_data.previous_teams.*' => ['string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Der Name ist erforderlich.',
            'name.max' => 'Der Name darf maximal 255 Zeichen lang sein.',
            'email.required' => 'Die E-Mail-Adresse ist erforderlich.',
            'email.email' => 'Die E-Mail-Adresse muss gültig sein.',
            'email.unique' => 'Diese E-Mail-Adresse wird bereits verwendet.',
            'password.min' => 'Das Passwort muss mindestens 8 Zeichen lang sein.',
            'birth_date.before' => 'Das Geburtsdatum muss in der Vergangenheit liegen.',
            'gender.in' => 'Das Geschlecht ist ungültig.',
            'language.in' => 'Die Sprache ist ungültig.',
            'timezone.timezone' => 'Die Zeitzone ist ungültig.',
            'date_format.in' => 'Das Datumsformat ist ungültig.',
            'time_format.in' => 'Das Zeitformat ist ungültig.',
            'avatar_url.url' => 'Die Avatar-URL muss gültig sein.',
            'roles.*.exists' => 'Eine der ausgewählten Rollen existiert nicht.',
            'player_data.team_id.exists' => 'Das ausgewählte Team existiert nicht.',
            'player_data.jersey_number.min' => 'Die Trikotnummer muss mindestens 0 sein.',
            'player_data.jersey_number.max' => 'Die Trikotnummer darf maximal 99 sein.',
            'player_data.primary_position.in' => 'Die Hauptposition ist ungültig.',
            'player_data.secondary_positions.*.in' => 'Eine der Nebenpositionen ist ungültig.',
            'player_data.height_cm.min' => 'Die Körpergröße muss mindestens 120 cm betragen.',
            'player_data.height_cm.max' => 'Die Körpergröße darf maximal 250 cm betragen.',
            'player_data.weight_kg.min' => 'Das Gewicht muss mindestens 30 kg betragen.',
            'player_data.weight_kg.max' => 'Das Gewicht darf maximal 200 kg betragen.',
            'player_data.dominant_hand.in' => 'Die dominante Hand ist ungültig.',
            'player_data.years_experience.min' => 'Die Erfahrung muss mindestens 0 Jahre betragen.',
            'player_data.years_experience.max' => 'Die Erfahrung darf maximal 50 Jahre betragen.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'Name',
            'email' => 'E-Mail-Adresse',
            'password' => 'Passwort',
            'phone' => 'Telefon',
            'birth_date' => 'Geburtsdatum',
            'gender' => 'Geschlecht',
            'address' => 'Adresse',
            'city' => 'Stadt',
            'state' => 'Bundesland',
            'postal_code' => 'Postleitzahl',
            'country' => 'Land',
            'language' => 'Sprache',
            'timezone' => 'Zeitzone',
            'date_format' => 'Datumsformat',
            'time_format' => 'Zeitformat',
            'is_active' => 'Aktiv',
            'email_verified_at' => 'E-Mail verifiziert am',
            'avatar_url' => 'Avatar-URL',
            'emergency_contact_name' => 'Notfallkontakt Name',
            'emergency_contact_phone' => 'Notfallkontakt Telefon',
            'emergency_contact_relationship' => 'Notfallkontakt Beziehung',
            'medical_notes' => 'Medizinische Notizen',
            'allergies' => 'Allergien',
            'medications' => 'Medikamente',
            'consent_marketing' => 'Marketing-Einwilligung',
            'consent_data_processing' => 'Datenverarbeitungs-Einwilligung',
            'consent_medical_info_sharing' => 'Medizinische Informationen teilen',
            'roles' => 'Rollen',
            'player_data' => 'Spielerdaten',
            'player_data.team_id' => 'Team',
            'player_data.jersey_number' => 'Trikotnummer',
            'player_data.primary_position' => 'Hauptposition',
            'player_data.secondary_positions' => 'Nebenpositionen',
            'player_data.height_cm' => 'Körpergröße (cm)',
            'player_data.weight_kg' => 'Gewicht (kg)',
            'player_data.dominant_hand' => 'Dominante Hand',
            'player_data.years_experience' => 'Jahre Erfahrung',
            'player_data.previous_teams' => 'Frühere Teams',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string booleans to actual booleans
        $booleanFields = [
            'is_active', 'consent_marketing', 'consent_data_processing', 'consent_medical_info_sharing'
        ];
        
        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $this->merge([$field => $this->boolean($field)]);
            }
        }

        // Set default values
        $defaults = [
            'language' => 'de',
            'timezone' => 'Europe/Berlin',
            'date_format' => 'd.m.Y',
            'time_format' => 'H:i',
            'country' => 'DE',
            'is_active' => true,
            'consent_marketing' => false,
            'consent_data_processing' => true,
            'consent_medical_info_sharing' => false,
        ];

        foreach ($defaults as $field => $defaultValue) {
            if (!$this->has($field)) {
                $this->merge([$field => $defaultValue]);
            }
        }

        // Handle email verification
        if ($this->has('email_verified_at') && $this->input('email_verified_at') === 'now') {
            $this->merge(['email_verified_at' => now()]);
        }
    }
}