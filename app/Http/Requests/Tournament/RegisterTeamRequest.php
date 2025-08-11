<?php

namespace App\Http\Requests\Tournament;

use Illuminate\Foundation\Http\FormRequest;

class RegisterTeamRequest extends FormRequest
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
            // Team Selection
            'team_id' => 'required|exists:teams,id',
            
            // Registration Information
            'registration_notes' => 'nullable|string|max:1000',
            
            // Contact Information
            'contact_person' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'required|string|max:20',
            'special_requirements' => 'nullable|string|max:1000',
            
            // Travel Information
            'travel_information' => 'nullable|array',
            'travel_information.needs_accommodation' => 'sometimes|boolean',
            'travel_information.accommodation_nights' => 'sometimes|integer|min:0|max:30',
            'travel_information.arrival_date' => 'sometimes|date',
            'travel_information.departure_date' => 'sometimes|date|after:travel_information.arrival_date',
            'travel_information.transportation_method' => 'sometimes|string|max:100',
            'travel_information.estimated_travel_time' => 'sometimes|integer|min:0|max:1440', // minutes
            'travel_information.special_needs' => 'sometimes|string|max:500',
            
            // Roster Information
            'roster_players' => 'nullable|array|max:25',
            'roster_players.*.player_id' => 'sometimes|exists:users,id',
            'roster_players.*.jersey_number' => 'sometimes|integer|min:0|max:99',
            'roster_players.*.position' => 'sometimes|string|in:PG,SG,SF,PF,C',
            'roster_players.*.is_captain' => 'sometimes|boolean',
            'roster_players.*.medical_clearance' => 'sometimes|boolean',
            
            // Emergency Contacts
            'emergency_contacts' => 'nullable|array|max:5',
            'emergency_contacts.*.name' => 'required_with:emergency_contacts|string|max:255',
            'emergency_contacts.*.relationship' => 'required_with:emergency_contacts|string|max:100',
            'emergency_contacts.*.phone' => 'required_with:emergency_contacts|string|max:20',
            'emergency_contacts.*.email' => 'sometimes|email|max:255',
            'emergency_contacts.*.is_primary' => 'sometimes|boolean',
            
            // Medical Information
            'medical_forms_complete' => 'boolean',
            'insurance_verified' => 'boolean',
            'medical_information' => 'nullable|array',
            'medical_information.team_doctor' => 'sometimes|string|max:255',
            'medical_information.team_doctor_phone' => 'sometimes|string|max:20',
            'medical_information.medical_insurance' => 'sometimes|string|max:255',
            'medical_information.special_medical_needs' => 'sometimes|string|max:1000',
            
            // Equipment and Logistics
            'equipment_needs' => 'nullable|array',
            'equipment_needs.uniform_colors' => 'sometimes|array|max:2',
            'equipment_needs.uniform_colors.*' => 'string|max:50',
            'equipment_needs.ball_preference' => 'sometimes|string|max:100',
            'equipment_needs.warmup_time_needed' => 'sometimes|integer|min:5|max:60',
            'equipment_needs.special_equipment' => 'sometimes|string|max:500',
            
            // Payment Information
            'payment_method' => 'nullable|string|in:bank_transfer,cash,credit_card,paypal',
            'payment_reference' => 'nullable|string|max:255',
            'agrees_to_terms' => 'required|boolean|accepted',
            
            // Additional Information
            'team_experience_level' => 'nullable|string|in:beginner,recreational,competitive,professional',
            'previous_tournaments' => 'nullable|array',
            'previous_tournaments.*.tournament_name' => 'sometimes|string|max:255',
            'previous_tournaments.*.year' => 'sometimes|integer|min:1990|max:' . (date('Y') + 1),
            'previous_tournaments.*.result' => 'sometimes|string|max:100',
            
            // Expectations and Goals
            'tournament_goals' => 'nullable|string|max:1000',
            'expected_placement' => 'nullable|string|in:champion,top_3,top_half,participation',
            
            // Media and Publicity
            'media_consent' => 'nullable|boolean',
            'photo_consent' => 'nullable|boolean',
            'interview_consent' => 'nullable|boolean',
            'social_media_handles' => 'nullable|array',
            'social_media_handles.*.platform' => 'sometimes|string|max:50',
            'social_media_handles.*.handle' => 'sometimes|string|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'team_id.required' => 'Ein Team muss ausgewählt werden.',
            'team_id.exists' => 'Das ausgewählte Team existiert nicht.',
            'contact_person.required' => 'Eine Kontaktperson ist erforderlich.',
            'contact_email.required' => 'Eine Kontakt-E-Mail ist erforderlich.',
            'contact_email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'contact_phone.required' => 'Eine Telefonnummer ist erforderlich.',
            'travel_information.arrival_date.date' => 'Bitte geben Sie ein gültiges Anreisedatum ein.',
            'travel_information.departure_date.after' => 'Das Abreisedatum muss nach dem Anreisedatum liegen.',
            'roster_players.max' => 'Maximal 25 Spieler können registriert werden.',
            'roster_players.*.player_id.exists' => 'Der angegebene Spieler existiert nicht.',
            'roster_players.*.jersey_number.min' => 'Trikotnummern müssen zwischen 0 und 99 liegen.',
            'roster_players.*.jersey_number.max' => 'Trikotnummern müssen zwischen 0 und 99 liegen.',
            'roster_players.*.position.in' => 'Ungültige Position angegeben.',
            'emergency_contacts.max' => 'Maximal 5 Notfallkontakte können angegeben werden.',
            'emergency_contacts.*.name.required_with' => 'Name des Notfallkontakts ist erforderlich.',
            'emergency_contacts.*.phone.required_with' => 'Telefonnummer des Notfallkontakts ist erforderlich.',
            'equipment_needs.uniform_colors.max' => 'Maximal 2 Trikotfarben können angegeben werden.',
            'equipment_needs.warmup_time_needed.min' => 'Aufwärmzeit muss mindestens 5 Minuten betragen.',
            'equipment_needs.warmup_time_needed.max' => 'Aufwärmzeit darf maximal 60 Minuten betragen.',
            'agrees_to_terms.required' => 'Die Teilnahmebedingungen müssen akzeptiert werden.',
            'agrees_to_terms.accepted' => 'Die Teilnahmebedingungen müssen akzeptiert werden.',
            'previous_tournaments.*.year.min' => 'Jahr muss zwischen 1990 und ' . (date('Y') + 1) . ' liegen.',
            'previous_tournaments.*.year.max' => 'Jahr muss zwischen 1990 und ' . (date('Y') + 1) . ' liegen.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'team_id' => 'Team',
            'registration_notes' => 'Anmerkungen',
            'contact_person' => 'Kontaktperson',
            'contact_email' => 'Kontakt-E-Mail',
            'contact_phone' => 'Kontakt-Telefon',
            'special_requirements' => 'Besondere Anforderungen',
            'travel_information.arrival_date' => 'Anreisedatum',
            'travel_information.departure_date' => 'Abreisedatum',
            'travel_information.transportation_method' => 'Transportmittel',
            'roster_players' => 'Spielerliste',
            'emergency_contacts' => 'Notfallkontakte',
            'medical_forms_complete' => 'Medizinische Unterlagen vollständig',
            'insurance_verified' => 'Versicherung verifiziert',
            'equipment_needs' => 'Ausrüstungsbedarf',
            'payment_method' => 'Zahlungsmethode',
            'agrees_to_terms' => 'Teilnahmebedingungen',
            'team_experience_level' => 'Team-Erfahrungslevel',
            'tournament_goals' => 'Turnier-Ziele',
            'expected_placement' => 'Erwartete Platzierung',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert boolean strings to actual booleans
        $booleanFields = [
            'medical_forms_complete', 'insurance_verified', 'agrees_to_terms',
            'media_consent', 'photo_consent', 'interview_consent'
        ];

        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false
                ]);
            }
        }

        // Process nested boolean fields in arrays
        if ($this->has('roster_players')) {
            $rosterPlayers = $this->input('roster_players');
            foreach ($rosterPlayers as $index => $player) {
                if (isset($player['is_captain'])) {
                    $rosterPlayers[$index]['is_captain'] = filter_var($player['is_captain'], FILTER_VALIDATE_BOOLEAN);
                }
                if (isset($player['medical_clearance'])) {
                    $rosterPlayers[$index]['medical_clearance'] = filter_var($player['medical_clearance'], FILTER_VALIDATE_BOOLEAN);
                }
            }
            $this->merge(['roster_players' => $rosterPlayers]);
        }

        if ($this->has('emergency_contacts')) {
            $emergencyContacts = $this->input('emergency_contacts');
            foreach ($emergencyContacts as $index => $contact) {
                if (isset($contact['is_primary'])) {
                    $emergencyContacts[$index]['is_primary'] = filter_var($contact['is_primary'], FILTER_VALIDATE_BOOLEAN);
                }
            }
            $this->merge(['emergency_contacts' => $emergencyContacts]);
        }

        // Set default contact information if not provided
        if (!$this->has('contact_person') && auth()->check()) {
            $this->merge(['contact_person' => auth()->user()->full_name]);
        }

        if (!$this->has('contact_email') && auth()->check()) {
            $this->merge(['contact_email' => auth()->user()->email]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $tournament = $this->route('tournament');
            
            // Validate tournament registration is open
            $this->validateRegistrationOpen($validator, $tournament);
            
            // Validate team eligibility
            $this->validateTeamEligibility($validator, $tournament);
            
            // Validate roster requirements
            $this->validateRosterRequirements($validator, $tournament);
            
            // Validate emergency contacts
            $this->validateEmergencyContacts($validator);
            
            // Validate jersey numbers
            $this->validateJerseyNumbers($validator);
        });
    }

    /**
     * Validate registration is open.
     */
    protected function validateRegistrationOpen($validator, $tournament): void
    {
        if (!$tournament->can_register) {
            $validator->errors()->add('team_id', 'Die Anmeldung für dieses Turnier ist nicht möglich.');
        }

        if ($tournament->registered_teams >= $tournament->max_teams) {
            $validator->errors()->add('team_id', 'Das Turnier ist bereits ausgebucht.');
        }
    }

    /**
     * Validate team eligibility.
     */
    protected function validateTeamEligibility($validator, $tournament): void
    {
        if (!$this->has('team_id')) return;

        $team = \App\Models\Team::find($this->team_id);
        if (!$team) return;

        // Check if team is already registered
        if ($tournament->teams()->where('team_id', $this->team_id)->exists()) {
            $validator->errors()->add('team_id', 'Dieses Team ist bereits für das Turnier angemeldet.');
        }

        // Check team category eligibility
        if ($tournament->category !== 'mixed' && $team->category !== $tournament->category) {
            $validator->errors()->add('team_id', 
                "Team-Kategorie ({$team->category}) entspricht nicht der Turnier-Kategorie ({$tournament->category}).");
        }

        // Check gender eligibility
        if ($tournament->gender !== 'mixed' && $team->gender !== $tournament->gender) {
            $validator->errors()->add('team_id', 
                "Team-Geschlecht ({$team->gender}) entspricht nicht der Turnier-Kategorie ({$tournament->gender}).");
        }

        // Check if user has permission to register this team
        if (!auth()->user()->can('register-team', $team)) {
            $validator->errors()->add('team_id', 'Sie haben keine Berechtigung, dieses Team anzumelden.');
        }
    }

    /**
     * Validate roster requirements.
     */
    protected function validateRosterRequirements($validator, $tournament): void
    {
        if (!$this->has('roster_players') || !is_array($this->roster_players)) {
            return;
        }

        $rosterPlayers = $this->roster_players;

        // Validate minimum roster size (tournament-specific)
        $minRoster = $tournament->game_rules['min_roster_size'] ?? 8;
        if (count($rosterPlayers) < $minRoster) {
            $validator->errors()->add('roster_players', 
                "Mindestens {$minRoster} Spieler müssen im Kader stehen.");
        }

        // Validate captain selection
        $captains = array_filter($rosterPlayers, fn($player) => $player['is_captain'] ?? false);
        if (count($captains) === 0) {
            $validator->errors()->add('roster_players', 'Ein Kapitän muss bestimmt werden.');
        } elseif (count($captains) > 2) {
            $validator->errors()->add('roster_players', 'Maximal 2 Kapitäne können bestimmt werden.');
        }

        // Validate medical clearances (if required)
        if ($tournament->requires_medical_clearance) {
            $unclearedPlayers = array_filter($rosterPlayers, fn($player) => !($player['medical_clearance'] ?? false));
            if (!empty($unclearedPlayers)) {
                $validator->errors()->add('roster_players', 
                    'Alle Spieler müssen eine medizinische Freigabe haben.');
            }
        }
    }

    /**
     * Validate emergency contacts.
     */
    protected function validateEmergencyContacts($validator): void
    {
        if (!$this->has('emergency_contacts') || !is_array($this->emergency_contacts)) {
            return;
        }

        $emergencyContacts = $this->emergency_contacts;
        $primaryContacts = array_filter($emergencyContacts, fn($contact) => $contact['is_primary'] ?? false);

        // Ensure exactly one primary contact
        if (count($primaryContacts) !== 1) {
            $validator->errors()->add('emergency_contacts', 
                'Genau ein Notfallkontakt muss als Hauptkontakt markiert werden.');
        }
    }

    /**
     * Validate jersey numbers uniqueness.
     */
    protected function validateJerseyNumbers($validator): void
    {
        if (!$this->has('roster_players') || !is_array($this->roster_players)) {
            return;
        }

        $jerseyNumbers = [];
        foreach ($this->roster_players as $index => $player) {
            if (isset($player['jersey_number'])) {
                $number = $player['jersey_number'];
                
                if (in_array($number, $jerseyNumbers)) {
                    $validator->errors()->add("roster_players.{$index}.jersey_number", 
                        "Trikotnummer {$number} wird bereits verwendet.");
                }
                
                $jerseyNumbers[] = $number;
            }
        }
    }
}