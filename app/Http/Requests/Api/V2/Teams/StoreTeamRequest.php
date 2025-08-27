<?php

namespace App\Http\Requests\Api\V2\Teams;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamRequest extends FormRequest
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
            'short_name' => ['nullable', 'string', 'max:50'],
            'club_id' => ['required', 'exists:clubs,id'],
            'description' => ['nullable', 'string', 'max:1000'],
            'season' => ['required', 'string', 'max:10'],
            'league' => ['nullable', 'string', 'max:100'],
            'division' => ['nullable', 'string', 'max:100'],
            'gender' => ['required', Rule::in(['male', 'female', 'mixed'])],
            'age_group' => ['required', 'string', 'max:50'],
            'competitive_level' => ['nullable', Rule::in(['recreational', 'competitive', 'elite', 'professional'])],
            'max_players' => ['nullable', 'integer', 'min:5', 'max:50'],
            'min_age' => ['nullable', 'integer', 'min:5', 'max:100'],
            'max_age' => ['nullable', 'integer', 'min:5', 'max:100', 'gte:min_age'],
            'head_coach_id' => ['nullable', 'exists:users,id'],
            'assistant_coaches' => ['nullable', 'array'],
            'assistant_coaches.*' => ['exists:users,id', 'different:head_coach_id'],
            'is_active' => ['boolean'],
            'is_recruiting' => ['boolean'],
            'home_venue' => ['nullable', 'string', 'max:255'],
            'venue_details' => ['nullable', 'array'],
            'venue_details.address' => ['nullable', 'string', 'max:255'],
            'venue_details.city' => ['nullable', 'string', 'max:100'],
            'venue_details.state' => ['nullable', 'string', 'max:100'],
            'venue_details.postal_code' => ['nullable', 'string', 'max:20'],
            'venue_details.country' => ['nullable', 'string', 'max:50'],
            'venue_details.capacity' => ['nullable', 'integer', 'min:1'],
            'venue_details.court_type' => ['nullable', 'string', 'max:100'],
            'venue_details.parking_available' => ['nullable', 'boolean'],
            'venue_details.accessibility' => ['nullable', 'string', 'max:500'],
            'practice_times' => ['nullable', 'array'],
            'practice_times.*' => ['string', 'max:255'],
            'team_color_primary' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'team_color_secondary' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'team_logo_url' => ['nullable', 'url', 'max:500'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'registration_fee' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'monthly_fee' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'equipment_provided' => ['boolean'],
            'insurance_required' => ['boolean'],
            'medical_check_required' => ['boolean'],
            'requirements' => ['nullable', 'array'],
            'requirements.*' => ['string', 'max:255'],
            'additional_info' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Der Team-Name ist erforderlich.',
            'name.max' => 'Der Team-Name darf maximal 255 Zeichen lang sein.',
            'club_id.required' => 'Ein Club muss ausgewählt werden.',
            'club_id.exists' => 'Der ausgewählte Club existiert nicht.',
            'season.required' => 'Die Saison ist erforderlich.',
            'gender.required' => 'Das Geschlecht ist erforderlich.',
            'gender.in' => 'Das Geschlecht muss männlich, weiblich oder gemischt sein.',
            'age_group.required' => 'Die Altersgruppe ist erforderlich.',
            'competitive_level.in' => 'Das Leistungsniveau ist ungültig.',
            'max_players.min' => 'Ein Team muss mindestens 5 Spieler haben können.',
            'max_players.max' => 'Ein Team kann maximal 50 Spieler haben.',
            'min_age.min' => 'Das Mindestalter muss mindestens 5 Jahre betragen.',
            'max_age.gte' => 'Das Höchstalter muss größer oder gleich dem Mindestalter sein.',
            'head_coach_id.exists' => 'Der ausgewählte Cheftrainer existiert nicht.',
            'assistant_coaches.*.exists' => 'Einer der ausgewählten Co-Trainer existiert nicht.',
            'assistant_coaches.*.different' => 'Co-Trainer müssen sich vom Cheftrainer unterscheiden.',
            'team_color_primary.regex' => 'Die primäre Teamfarbe muss ein gültiger Hex-Farbcode sein.',
            'team_color_secondary.regex' => 'Die sekundäre Teamfarbe muss ein gültiger Hex-Farbcode sein.',
            'contact_email.email' => 'Die Kontakt-E-Mail muss eine gültige E-Mail-Adresse sein.',
            'registration_fee.numeric' => 'Die Anmeldegebühr muss eine Zahl sein.',
            'monthly_fee.numeric' => 'Die monatliche Gebühr muss eine Zahl sein.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'Team-Name',
            'short_name' => 'Kurzer Name',
            'club_id' => 'Club',
            'description' => 'Beschreibung',
            'season' => 'Saison',
            'league' => 'Liga',
            'division' => 'Division',
            'gender' => 'Geschlecht',
            'age_group' => 'Altersgruppe',
            'competitive_level' => 'Leistungsniveau',
            'max_players' => 'Maximale Spieleranzahl',
            'min_age' => 'Mindestalter',
            'max_age' => 'Höchstalter',
            'head_coach_id' => 'Cheftrainer',
            'assistant_coaches' => 'Co-Trainer',
            'is_active' => 'Aktiv',
            'is_recruiting' => 'Wirbt neue Spieler',
            'home_venue' => 'Heimspielstätte',
            'venue_details' => 'Spielstätten-Details',
            'practice_times' => 'Trainingszeiten',
            'team_color_primary' => 'Primäre Teamfarbe',
            'team_color_secondary' => 'Sekundäre Teamfarbe',
            'team_logo_url' => 'Team-Logo URL',
            'contact_email' => 'Kontakt-E-Mail',
            'contact_phone' => 'Kontakt-Telefon',
            'registration_fee' => 'Anmeldegebühr',
            'monthly_fee' => 'Monatliche Gebühr',
            'equipment_provided' => 'Ausrüstung gestellt',
            'insurance_required' => 'Versicherung erforderlich',
            'medical_check_required' => 'Ärztliche Untersuchung erforderlich',
            'requirements' => 'Anforderungen',
            'additional_info' => 'Zusätzliche Informationen',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string booleans to actual booleans
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'is_recruiting' => $this->boolean('is_recruiting', false),
            'equipment_provided' => $this->boolean('equipment_provided', false),
            'insurance_required' => $this->boolean('insurance_required', true),
            'medical_check_required' => $this->boolean('medical_check_required', true),
        ]);

        // Set default values
        if (!$this->has('competitive_level')) {
            $this->merge(['competitive_level' => 'recreational']);
        }

        if (!$this->has('max_players')) {
            $this->merge(['max_players' => 15]);
        }
    }
}