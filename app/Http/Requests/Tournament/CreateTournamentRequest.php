<?php

namespace App\Http\Requests\Tournament;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTournamentRequest extends FormRequest
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
            // Basic Information
            'name' => 'required|string|max:255|unique:tournaments,name',
            'description' => 'nullable|string|max:2000',
            'logo_path' => 'nullable|string|max:255',
            'club_id' => 'nullable|exists:clubs,id',
            
            // Tournament Configuration
            'type' => [
                'required',
                Rule::in(['single_elimination', 'double_elimination', 'round_robin', 'swiss_system', 'group_stage_knockout', 'ladder'])
            ],
            'category' => [
                'required',
                Rule::in(['U8', 'U10', 'U12', 'U14', 'U16', 'U18', 'adult', 'mixed'])
            ],
            'gender' => [
                'required',
                Rule::in(['male', 'female', 'mixed'])
            ],
            
            // Schedule
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'registration_start' => 'required|date|before_or_equal:start_date',
            'registration_end' => 'required|date|after:registration_start|before_or_equal:start_date',
            'daily_start_time' => 'required|date_format:H:i',
            'daily_end_time' => 'required|date_format:H:i|after:daily_start_time',
            
            // Team Limits
            'min_teams' => 'required|integer|min:2|max:128',
            'max_teams' => 'required|integer|min:2|max:128|gte:min_teams',
            'entry_fee' => 'nullable|numeric|min:0|max:9999.99',
            'currency' => 'required|string|size:3|in:EUR,USD,GBP,CHF',
            
            // Venue Information
            'primary_venue' => 'required|string|max:255',
            'venue_address' => 'nullable|string|max:500',
            'additional_venues' => 'nullable|array',
            'additional_venues.*' => 'string|max:255',
            'available_courts' => 'required|integer|min:1|max:20',
            
            // Game Rules and Settings
            'game_duration' => 'required|integer|min:20|max:60',
            'periods' => 'required|integer|min:2|max:8',
            'period_length' => 'required|integer|min:5|max:20',
            'overtime_enabled' => 'boolean',
            'overtime_length' => 'nullable|integer|min:3|max:10',
            'shot_clock_enabled' => 'boolean',
            'shot_clock_seconds' => 'nullable|integer|min:12|max:35',
            'game_rules' => 'nullable|array',
            
            // Tournament Structure
            'groups_count' => 'nullable|integer|min:2|max:8',
            'seeding_rules' => 'nullable|array',
            'third_place_game' => 'boolean',
            'advancement_rules' => 'nullable|array',
            
            // Prizes and Awards
            'prizes' => 'nullable|array',
            'awards' => 'nullable|array',
            'total_prize_money' => 'nullable|numeric|min:0|max:999999.99',
            
            // Settings
            'is_public' => 'boolean',
            'requires_approval' => 'boolean',
            'allows_spectators' => 'boolean',
            'spectator_fee' => 'nullable|numeric|min:0|max:999.99',
            'photography_allowed' => 'boolean',
            
            // Media and Streaming
            'livestream_enabled' => 'boolean',
            'livestream_url' => 'nullable|url|max:255',
            'social_media_links' => 'nullable|array',
            'social_media_links.*.platform' => 'required_with:social_media_links|string|max:50',
            'social_media_links.*.url' => 'required_with:social_media_links|url|max:255',
            
            // Contact Information
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'special_instructions' => 'nullable|string|max:1000',
            'covid_requirements' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Der Turniername ist erforderlich.',
            'name.unique' => 'Ein Turnier mit diesem Namen existiert bereits.',
            'type.required' => 'Der Turniertyp ist erforderlich.',
            'type.in' => 'Der gewählte Turniertyp ist ungültig.',
            'category.required' => 'Die Turnierkategorie ist erforderlich.',
            'gender.required' => 'Die Geschlechter-Kategorie ist erforderlich.',
            'start_date.required' => 'Das Startdatum ist erforderlich.',
            'start_date.after_or_equal' => 'Das Startdatum muss heute oder später sein.',
            'end_date.after_or_equal' => 'Das Enddatum muss nach dem Startdatum liegen.',
            'registration_start.before_or_equal' => 'Der Anmeldebeginn muss vor dem Turnierbeginn liegen.',
            'registration_end.after' => 'Das Anmeldeende muss nach dem Anmeldebeginn liegen.',
            'registration_end.before_or_equal' => 'Das Anmeldeende muss vor dem Turnierbeginn liegen.',
            'min_teams.required' => 'Die Mindestanzahl Teams ist erforderlich.',
            'min_teams.min' => 'Mindestens 2 Teams sind erforderlich.',
            'max_teams.gte' => 'Die maximale Teamanzahl muss größer oder gleich der Mindestanzahl sein.',
            'primary_venue.required' => 'Der Hauptveranstaltungsort ist erforderlich.',
            'game_duration.required' => 'Die Spieldauer ist erforderlich.',
            'periods.required' => 'Die Anzahl der Spielabschnitte ist erforderlich.',
            'period_length.required' => 'Die Länge der Spielabschnitte ist erforderlich.',
            'available_courts.min' => 'Mindestens 1 Spielfeld ist erforderlich.',
            'entry_fee.min' => 'Die Startgebühr kann nicht negativ sein.',
            'currency.size' => 'Die Währung muss aus 3 Buchstaben bestehen.',
            'livestream_url.url' => 'Die Livestream-URL muss eine gültige URL sein.',
            'contact_email.email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'Turniername',
            'description' => 'Beschreibung',
            'type' => 'Turniertyp',
            'category' => 'Kategorie',
            'gender' => 'Geschlecht',
            'start_date' => 'Startdatum',
            'end_date' => 'Enddatum',
            'registration_start' => 'Anmeldebeginn',
            'registration_end' => 'Anmeldeende',
            'daily_start_time' => 'Tägliche Startzeit',
            'daily_end_time' => 'Tägliche Endzeit',
            'min_teams' => 'Mindestanzahl Teams',
            'max_teams' => 'Maximale Teamanzahl',
            'entry_fee' => 'Startgebühr',
            'currency' => 'Währung',
            'primary_venue' => 'Hauptveranstaltungsort',
            'venue_address' => 'Venue-Adresse',
            'available_courts' => 'Verfügbare Spielfelder',
            'game_duration' => 'Spieldauer',
            'periods' => 'Spielabschnitte',
            'period_length' => 'Länge der Spielabschnitte',
            'overtime_length' => 'Länge der Verlängerung',
            'shot_clock_seconds' => 'Wurfuhr Sekunden',
            'groups_count' => 'Anzahl Gruppen',
            'total_prize_money' => 'Gesamtes Preisgeld',
            'spectator_fee' => 'Zuschauergebühr',
            'livestream_url' => 'Livestream-URL',
            'contact_email' => 'Kontakt-E-Mail',
            'contact_phone' => 'Kontakt-Telefon',
            'special_instructions' => 'Besondere Anweisungen',
            'covid_requirements' => 'Corona-Bestimmungen',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert boolean strings to actual booleans
        $booleanFields = [
            'overtime_enabled', 'shot_clock_enabled', 'third_place_game', 
            'is_public', 'requires_approval', 'allows_spectators', 
            'photography_allowed', 'livestream_enabled'
        ];

        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false
                ]);
            }
        }

        // Set overtime_length based on overtime_enabled
        if (!$this->overtime_enabled) {
            $this->merge(['overtime_length' => null]);
        }

        // Set shot_clock_seconds based on shot_clock_enabled
        if (!$this->shot_clock_enabled) {
            $this->merge(['shot_clock_seconds' => null]);
        }

        // Set default groups_count for certain tournament types
        if (in_array($this->type, ['round_robin', 'group_stage_knockout']) && !$this->has('groups_count')) {
            $this->merge(['groups_count' => 1]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate tournament type specific rules
            $this->validateTournamentTypeRules($validator);
            
            // Validate date logic
            $this->validateDateLogic($validator);
            
            // Validate capacity logic
            $this->validateCapacityLogic($validator);
        });
    }

    /**
     * Validate tournament type specific rules.
     */
    protected function validateTournamentTypeRules($validator): void
    {
        if ($this->type === 'group_stage_knockout' && !$this->groups_count) {
            $validator->errors()->add('groups_count', 'Gruppenphase-Turniere benötigen eine Gruppenanzahl.');
        }

        if ($this->type === 'round_robin' && $this->groups_count > 1 && $this->max_teams < $this->groups_count * 2) {
            $validator->errors()->add('max_teams', 'Zu wenige Teams für die gewählte Gruppenanzahl.');
        }

        // Single elimination specific validations
        if ($this->type === 'single_elimination') {
            $powerOf2 = 2 ** (int) ceil(log($this->max_teams, 2));
            if ($this->max_teams > 64) {
                $validator->errors()->add('max_teams', 'Single-Elimination-Turniere sind auf 64 Teams begrenzt.');
            }
        }
    }

    /**
     * Validate date logic.
     */
    protected function validateDateLogic($validator): void
    {
        if ($this->registration_start && $this->start_date) {
            $regStart = new \DateTime($this->registration_start);
            $tournamentStart = new \DateTime($this->start_date);
            
            if ($regStart->diff($tournamentStart)->days < 7) {
                $validator->errors()->add('registration_start', 
                    'Die Anmeldung sollte mindestens 7 Tage vor Turnierbeginn starten.');
            }
        }
    }

    /**
     * Validate capacity logic.
     */
    protected function validateCapacityLogic($validator): void
    {
        // Validate that venue capacity can handle the tournament
        if ($this->max_teams && $this->available_courts) {
            $maxGamesPerDay = $this->available_courts * 8; // Assuming 8 games per court per day
            $estimatedGames = match($this->type) {
                'single_elimination' => $this->max_teams - 1,
                'round_robin' => ($this->max_teams * ($this->max_teams - 1)) / 2,
                'swiss_system' => $this->max_teams * (int) ceil(log($this->max_teams, 2)) / 2,
                default => $this->max_teams,
            };
            
            $tournamentDays = (new \DateTime($this->start_date))->diff(new \DateTime($this->end_date))->days + 1;
            $totalCapacity = $maxGamesPerDay * $tournamentDays;
            
            if ($estimatedGames > $totalCapacity) {
                $validator->errors()->add('max_teams', 
                    'Zu viele Teams für die verfügbare Venue-Kapazität und Turnierdauer.');
            }
        }
    }
}