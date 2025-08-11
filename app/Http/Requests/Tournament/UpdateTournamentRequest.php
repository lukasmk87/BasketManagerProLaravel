<?php

namespace App\Http\Requests\Tournament;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTournamentRequest extends FormRequest
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
        $tournament = $this->route('tournament');
        
        return [
            // Basic Information
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('tournaments', 'name')->ignore($tournament->id)
            ],
            'description' => 'sometimes|nullable|string|max:2000',
            'logo_path' => 'sometimes|nullable|string|max:255',
            'club_id' => 'sometimes|nullable|exists:clubs,id',
            
            // Tournament Configuration (restricted after registration opens)
            'type' => [
                'sometimes',
                Rule::in(['single_elimination', 'double_elimination', 'round_robin', 'swiss_system', 'group_stage_knockout', 'ladder'])
            ],
            'category' => [
                'sometimes',
                Rule::in(['U8', 'U10', 'U12', 'U14', 'U16', 'U18', 'adult', 'mixed'])
            ],
            'gender' => [
                'sometimes',
                Rule::in(['male', 'female', 'mixed'])
            ],
            
            // Schedule (some restrictions based on status)
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'registration_start' => 'sometimes|date',
            'registration_end' => 'sometimes|date|after:registration_start',
            'daily_start_time' => 'sometimes|date_format:H:i',
            'daily_end_time' => 'sometimes|date_format:H:i|after:daily_start_time',
            
            // Team Limits (restricted after registration opens)
            'min_teams' => 'sometimes|integer|min:2|max:128',
            'max_teams' => 'sometimes|integer|min:2|max:128|gte:min_teams',
            'entry_fee' => 'sometimes|nullable|numeric|min:0|max:9999.99',
            'currency' => 'sometimes|string|size:3|in:EUR,USD,GBP,CHF',
            
            // Venue Information
            'primary_venue' => 'sometimes|string|max:255',
            'venue_address' => 'sometimes|nullable|string|max:500',
            'additional_venues' => 'sometimes|nullable|array',
            'additional_venues.*' => 'string|max:255',
            'available_courts' => 'sometimes|integer|min:1|max:20',
            
            // Game Rules and Settings
            'game_duration' => 'sometimes|integer|min:20|max:60',
            'periods' => 'sometimes|integer|min:2|max:8',
            'period_length' => 'sometimes|integer|min:5|max:20',
            'overtime_enabled' => 'sometimes|boolean',
            'overtime_length' => 'sometimes|nullable|integer|min:3|max:10',
            'shot_clock_enabled' => 'sometimes|boolean',
            'shot_clock_seconds' => 'sometimes|nullable|integer|min:12|max:35',
            'game_rules' => 'sometimes|nullable|array',
            
            // Tournament Structure
            'groups_count' => 'sometimes|nullable|integer|min:2|max:8',
            'seeding_rules' => 'sometimes|nullable|array',
            'third_place_game' => 'sometimes|boolean',
            'advancement_rules' => 'sometimes|nullable|array',
            
            // Prizes and Awards
            'prizes' => 'sometimes|nullable|array',
            'awards' => 'sometimes|nullable|array',
            'total_prize_money' => 'sometimes|nullable|numeric|min:0|max:999999.99',
            
            // Settings
            'is_public' => 'sometimes|boolean',
            'requires_approval' => 'sometimes|boolean',
            'allows_spectators' => 'sometimes|boolean',
            'spectator_fee' => 'sometimes|nullable|numeric|min:0|max:999.99',
            'photography_allowed' => 'sometimes|boolean',
            
            // Media and Streaming
            'livestream_enabled' => 'sometimes|boolean',
            'livestream_url' => 'sometimes|nullable|url|max:255',
            'social_media_links' => 'sometimes|nullable|array',
            'social_media_links.*.platform' => 'required_with:social_media_links|string|max:50',
            'social_media_links.*.url' => 'required_with:social_media_links|url|max:255',
            
            // Contact Information
            'contact_email' => 'sometimes|nullable|email|max:255',
            'contact_phone' => 'sometimes|nullable|string|max:20',
            'special_instructions' => 'sometimes|nullable|string|max:1000',
            'covid_requirements' => 'sometimes|nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Ein Turnier mit diesem Namen existiert bereits.',
            'type.in' => 'Der gewählte Turniertyp ist ungültig.',
            'category.in' => 'Die gewählte Kategorie ist ungültig.',
            'gender.in' => 'Die gewählte Geschlechter-Kategorie ist ungültig.',
            'end_date.after_or_equal' => 'Das Enddatum muss nach dem Startdatum liegen.',
            'registration_end.after' => 'Das Anmeldeende muss nach dem Anmeldebeginn liegen.',
            'daily_end_time.after' => 'Die tägliche Endzeit muss nach der Startzeit liegen.',
            'max_teams.gte' => 'Die maximale Teamanzahl muss größer oder gleich der Mindestanzahl sein.',
            'entry_fee.min' => 'Die Startgebühr kann nicht negativ sein.',
            'currency.size' => 'Die Währung muss aus 3 Buchstaben bestehen.',
            'available_courts.min' => 'Mindestens 1 Spielfeld ist erforderlich.',
            'game_duration.min' => 'Die Spieldauer muss mindestens 20 Minuten betragen.',
            'game_duration.max' => 'Die Spieldauer darf maximal 60 Minuten betragen.',
            'periods.min' => 'Mindestens 2 Spielabschnitte sind erforderlich.',
            'period_length.min' => 'Spielabschnitte müssen mindestens 5 Minuten lang sein.',
            'overtime_length.min' => 'Verlängerungen müssen mindestens 3 Minuten lang sein.',
            'shot_clock_seconds.min' => 'Die Wurfuhr muss mindestens 12 Sekunden betragen.',
            'groups_count.min' => 'Mindestens 2 Gruppen sind erforderlich.',
            'total_prize_money.min' => 'Das Preisgeld kann nicht negativ sein.',
            'spectator_fee.min' => 'Die Zuschauergebühr kann nicht negativ sein.',
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

        // Clean up conditional fields
        if ($this->has('overtime_enabled') && !$this->overtime_enabled) {
            $this->merge(['overtime_length' => null]);
        }

        if ($this->has('shot_clock_enabled') && !$this->shot_clock_enabled) {
            $this->merge(['shot_clock_seconds' => null]);
        }

        if ($this->has('livestream_enabled') && !$this->livestream_enabled) {
            $this->merge(['livestream_url' => null]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $tournament = $this->route('tournament');
            
            // Check if tournament status allows certain changes
            $this->validateStatusRestrictions($validator, $tournament);
            
            // Validate critical field changes
            $this->validateCriticalFieldChanges($validator, $tournament);
            
            // Validate date changes
            $this->validateDateChanges($validator, $tournament);
            
            // Validate capacity changes
            $this->validateCapacityChanges($validator, $tournament);
        });
    }

    /**
     * Validate status-based restrictions.
     */
    protected function validateStatusRestrictions($validator, $tournament): void
    {
        $restrictedStatuses = ['in_progress', 'completed'];
        
        if (in_array($tournament->status, $restrictedStatuses)) {
            $restrictedFields = [
                'type', 'category', 'gender', 'min_teams', 'max_teams',
                'start_date', 'end_date', 'game_duration', 'periods', 'period_length'
            ];
            
            foreach ($restrictedFields as $field) {
                if ($this->has($field) && $this->input($field) !== $tournament->{$field}) {
                    $validator->errors()->add($field, 
                        "Dieses Feld kann nicht geändert werden wenn das Turnier den Status '{$tournament->status}' hat.");
                }
            }
        }
        
        // Registration fields can't be changed after registration closes
        if (in_array($tournament->status, ['registration_closed', 'in_progress', 'completed'])) {
            $registrationFields = ['registration_start', 'registration_end', 'requires_approval'];
            
            foreach ($registrationFields as $field) {
                if ($this->has($field) && $this->input($field) !== $tournament->{$field}) {
                    $validator->errors()->add($field, 
                        'Anmeldeeinstellungen können nach Anmeldeschluss nicht mehr geändert werden.');
                }
            }
        }
    }

    /**
     * Validate critical field changes.
     */
    protected function validateCriticalFieldChanges($validator, $tournament): void
    {
        // Can't change tournament type if brackets exist
        if ($this->has('type') && $this->type !== $tournament->type) {
            if ($tournament->brackets()->exists()) {
                $validator->errors()->add('type', 
                    'Turniertyp kann nicht geändert werden wenn bereits Brackets existieren.');
            }
        }
        
        // Can't reduce max_teams below current registered teams
        if ($this->has('max_teams') && $this->max_teams < $tournament->registered_teams) {
            $validator->errors()->add('max_teams', 
                "Die maximale Teamanzahl kann nicht unter {$tournament->registered_teams} reduziert werden (bereits angemeldete Teams).");
        }
        
        // Can't reduce min_teams below current registered teams
        if ($this->has('min_teams') && $this->min_teams > $tournament->registered_teams) {
            $validator->errors()->add('min_teams', 
                "Die Mindestteamanzahl kann nicht über {$tournament->registered_teams} erhöht werden (bereits angemeldete Teams).");
        }
    }

    /**
     * Validate date changes.
     */
    protected function validateDateChanges($validator, $tournament): void
    {
        // Can't change start date if tournament has started
        if ($this->has('start_date') && $tournament->status === 'in_progress') {
            $validator->errors()->add('start_date', 
                'Startdatum kann nicht geändert werden wenn das Turnier bereits läuft.');
        }
        
        // Can't change registration end date to past if registration is still open
        if ($this->has('registration_end') && $tournament->status === 'registration_open') {
            $newRegEnd = new \DateTime($this->registration_end);
            if ($newRegEnd < new \DateTime()) {
                $validator->errors()->add('registration_end', 
                    'Anmeldeende kann nicht in die Vergangenheit gesetzt werden.');
            }
        }
        
        // Validate schedule consistency
        if ($this->has('start_date') && $this->has('registration_end')) {
            $startDate = new \DateTime($this->start_date);
            $regEndDate = new \DateTime($this->registration_end);
            
            if ($regEndDate >= $startDate) {
                $validator->errors()->add('registration_end', 
                    'Anmeldeende muss vor dem Turnierbeginn liegen.');
            }
        }
    }

    /**
     * Validate capacity changes.
     */
    protected function validateCapacityChanges($validator, $tournament): void
    {
        // Validate court availability for scheduled games
        if ($this->has('available_courts') && $this->available_courts < $tournament->available_courts) {
            $scheduledGames = $tournament->brackets()
                                       ->where('status', 'scheduled')
                                       ->whereNotNull('court')
                                       ->max('court');
            
            if ($scheduledGames && $scheduledGames > $this->available_courts) {
                $validator->errors()->add('available_courts', 
                    'Anzahl der Spielfelder kann nicht reduziert werden da bereits Spiele auf Feld ' . $scheduledGames . ' angesetzt sind.');
            }
        }
    }
}