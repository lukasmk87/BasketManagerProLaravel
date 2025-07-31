<?php

namespace App\Http\Requests\Api\V2\Players;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlayerRequest extends FormRequest
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
        $teamId = $this->input('team_id');
        
        return [
            'user_id' => ['required', 'exists:users,id'],
            'team_id' => ['nullable', 'exists:teams,id'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'full_name' => ['nullable', 'string', 'max:200'],
            'jersey_number' => [
                'nullable', 
                'integer', 
                'min:0', 
                'max:99',
                $teamId ? Rule::unique('players', 'jersey_number')->where('team_id', $teamId)->where('status', 'active') : ''
            ],
            'primary_position' => ['nullable', Rule::in(['PG', 'SG', 'SF', 'PF', 'C'])],
            'secondary_positions' => ['nullable', 'array'],
            'secondary_positions.*' => [Rule::in(['PG', 'SG', 'SF', 'PF', 'C'])],
            'is_starter' => ['boolean'],
            'is_captain' => ['boolean'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'injured', 'suspended', 'transferred'])],
            'joined_at' => ['nullable', 'date'],
            'contract_start_date' => ['nullable', 'date'],
            'contract_end_date' => ['nullable', 'date', 'after_or_equal:contract_start_date'],
            'salary' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'medical_clearance' => ['boolean'],
            'medical_clearance_date' => ['nullable', 'date', 'required_if:medical_clearance,true'],
            'medical_clearance_expires' => ['nullable', 'date', 'after:medical_clearance_date'],
            'insurance_company' => ['nullable', 'string', 'max:255'],
            'insurance_policy_number' => ['nullable', 'string', 'max:100'],
            'insurance_expires' => ['nullable', 'date', 'after:today'],
            'academic_eligibility' => ['boolean'],
            'grade_level' => ['nullable', 'string', 'max:50'],
            'gpa' => ['nullable', 'numeric', 'min:0', 'max:4.0'],
            'height_cm' => ['nullable', 'integer', 'min:120', 'max:250'],
            'weight_kg' => ['nullable', 'numeric', 'min:30', 'max:200'],
            'dominant_hand' => ['nullable', Rule::in(['left', 'right', 'ambidextrous'])],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:50'],
            'previous_teams' => ['nullable', 'array'],
            'previous_teams.*' => ['string', 'max:255'],
            'training_focus_areas' => ['nullable', 'array'],
            'training_focus_areas.*' => ['string', 'max:255'],
            'development_goals' => ['nullable', 'array'],
            'development_goals.*' => ['string', 'max:255'],
            'coach_notes' => ['nullable', 'string', 'max:2000'],
            'shooting_rating' => ['nullable', 'integer', 'min:1', 'max:10'],
            'defense_rating' => ['nullable', 'integer', 'min:1', 'max:10'],
            'passing_rating' => ['nullable', 'integer', 'min:1', 'max:10'],
            'rebounding_rating' => ['nullable', 'integer', 'min:1', 'max:10'],
            'speed_rating' => ['nullable', 'integer', 'min:1', 'max:10'],
            'overall_rating' => ['nullable', 'integer', 'min:1', 'max:10'],
            'achievements' => ['nullable', 'array'],
            'achievements.*' => ['string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],

            // Emergency contacts
            'emergency_contacts' => ['nullable', 'array', 'max:5'],
            'emergency_contacts.*.name' => ['required_with:emergency_contacts', 'string', 'max:255'],
            'emergency_contacts.*.relationship' => ['required_with:emergency_contacts', 'string', 'max:100'],
            'emergency_contacts.*.phone_primary' => ['required_with:emergency_contacts', 'string', 'max:50'],
            'emergency_contacts.*.phone_secondary' => ['nullable', 'string', 'max:50'],
            'emergency_contacts.*.email' => ['nullable', 'email', 'max:255'],
            'emergency_contacts.*.address' => ['nullable', 'string', 'max:500'],
            'emergency_contacts.*.is_primary' => ['boolean'],
            'emergency_contacts.*.can_pickup' => ['boolean'],
            'emergency_contacts.*.medical_authority' => ['boolean'],
            'emergency_contacts.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'Ein Benutzer muss ausgewählt werden.',
            'user_id.exists' => 'Der ausgewählte Benutzer existiert nicht.',
            'team_id.exists' => 'Das ausgewählte Team existiert nicht.',
            'jersey_number.unique' => 'Diese Trikotnummer ist bereits im Team vergeben.',
            'jersey_number.min' => 'Die Trikotnummer muss mindestens 0 sein.',
            'jersey_number.max' => 'Die Trikotnummer darf maximal 99 sein.',
            'primary_position.in' => 'Die Hauptposition ist ungültig.',
            'secondary_positions.*.in' => 'Eine der Nebenpositionen ist ungültig.',
            'status.in' => 'Der Status ist ungültig.',
            'contract_end_date.after_or_equal' => 'Das Vertragsende muss nach oder am Vertragsbeginn liegen.',
            'medical_clearance_date.required_if' => 'Ein Datum für die medizinische Freigabe ist erforderlich.',
            'medical_clearance_expires.after' => 'Das Ablaufdatum der medizinischen Freigabe muss in der Zukunft liegen.',
            'insurance_expires.after' => 'Das Versicherungsablaufdatum muss in der Zukunft liegen.',
            'gpa.max' => 'Der GPA darf maximal 4.0 betragen.',
            'height_cm.min' => 'Die Körpergröße muss mindestens 120 cm betragen.',
            'height_cm.max' => 'Die Körpergröße darf maximal 250 cm betragen.',
            'weight_kg.min' => 'Das Gewicht muss mindestens 30 kg betragen.',
            'weight_kg.max' => 'Das Gewicht darf maximal 200 kg betragen.',
            'dominant_hand.in' => 'Die dominante Hand ist ungültig.',
            'years_experience.min' => 'Die Erfahrung muss mindestens 0 Jahre betragen.',
            'years_experience.max' => 'Die Erfahrung darf maximal 50 Jahre betragen.',
            'shooting_rating.min' => 'Die Schussbewertung muss mindestens 1 betragen.',
            'shooting_rating.max' => 'Die Schussbewertung darf maximal 10 betragen.',
            'emergency_contacts.max' => 'Es können maximal 5 Notfallkontakte angegeben werden.',
            'emergency_contacts.*.name.required_with' => 'Der Name des Notfallkontakts ist erforderlich.',
            'emergency_contacts.*.relationship.required_with' => 'Die Beziehung zum Notfallkontakt ist erforderlich.',
            'emergency_contacts.*.phone_primary.required_with' => 'Die Haupttelefonnummer des Notfallkontakts ist erforderlich.',
            'emergency_contacts.*.email.email' => 'Die E-Mail-Adresse des Notfallkontakts muss gültig sein.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'user_id' => 'Benutzer',
            'team_id' => 'Team',
            'first_name' => 'Vorname',
            'last_name' => 'Nachname',
            'full_name' => 'Vollständiger Name',
            'jersey_number' => 'Trikotnummer',
            'primary_position' => 'Hauptposition',
            'secondary_positions' => 'Nebenpositionen',
            'is_starter' => 'Stammspieler',
            'is_captain' => 'Kapitän',
            'status' => 'Status',
            'joined_at' => 'Beitrittsdatum',
            'contract_start_date' => 'Vertragsbeginn',
            'contract_end_date' => 'Vertragsende',
            'salary' => 'Gehalt',
            'medical_clearance' => 'Medizinische Freigabe',
            'medical_clearance_date' => 'Datum der medizinischen Freigabe',
            'medical_clearance_expires' => 'Ablauf der medizinischen Freigabe',
            'insurance_company' => 'Versicherungsunternehmen',
            'insurance_policy_number' => 'Versicherungspolice',
            'insurance_expires' => 'Versicherungsablauf',
            'academic_eligibility' => 'Akademische Berechtigung',
            'grade_level' => 'Klassenstufe',
            'gpa' => 'GPA',
            'height_cm' => 'Körpergröße (cm)',
            'weight_kg' => 'Gewicht (kg)',
            'dominant_hand' => 'Dominante Hand',
            'years_experience' => 'Jahre Erfahrung',
            'previous_teams' => 'Frühere Teams',
            'training_focus_areas' => 'Trainingsfokus',
            'development_goals' => 'Entwicklungsziele',
            'coach_notes' => 'Trainernotizen',
            'shooting_rating' => 'Schussbewertung',
            'defense_rating' => 'Verteidigungsbewertung',
            'passing_rating' => 'Passbewertung',
            'rebounding_rating' => 'Rebound-Bewertung',
            'speed_rating' => 'Geschwindigkeitsbewertung',
            'overall_rating' => 'Gesamtbewertung',
            'achievements' => 'Erfolge',
            'notes' => 'Notizen',
            'emergency_contacts' => 'Notfallkontakte',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string booleans to actual booleans
        $booleanFields = [
            'is_starter', 'is_captain', 'medical_clearance', 'academic_eligibility'
        ];
        
        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $this->merge([$field => $this->boolean($field)]);
            }
        }

        // Handle emergency contacts boolean fields
        if ($this->has('emergency_contacts')) {
            $contacts = $this->input('emergency_contacts');
            foreach ($contacts as $index => $contact) {
                $contacts[$index]['is_primary'] = $this->boolean("emergency_contacts.{$index}.is_primary", false);
                $contacts[$index]['can_pickup'] = $this->boolean("emergency_contacts.{$index}.can_pickup", false);
                $contacts[$index]['medical_authority'] = $this->boolean("emergency_contacts.{$index}.medical_authority", false);
            }
            $this->merge(['emergency_contacts' => $contacts]);
        }

        // Set default status
        if (!$this->has('status')) {
            $this->merge(['status' => 'active']);
        }
    }
}