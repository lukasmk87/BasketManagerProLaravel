<?php

namespace App\Http\Requests\Api\V2\Clubs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClubRequest extends FormRequest
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
        $club = $this->route('club');
        
        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('clubs', 'name')->ignore($club?->id)],
            'short_name' => ['nullable', 'string', 'max:50'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', Rule::unique('clubs', 'slug')->ignore($club?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'url', 'max:500'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:50'],
            'founded_at' => ['nullable', 'date', 'before_or_equal:today'],
            'logo_url' => ['nullable', 'url', 'max:500'],
            'colors_primary' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'colors_secondary' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'league' => ['nullable', 'string', 'max:100'],
            'division' => ['nullable', 'string', 'max:100'],
            'season' => ['nullable', 'string', 'max:10'],
            
            // Leadership
            'president_name' => ['nullable', 'string', 'max:255'],
            'president_email' => ['nullable', 'email', 'max:255'],
            'vice_president_name' => ['nullable', 'string', 'max:255'],
            'secretary_name' => ['nullable', 'string', 'max:255'],
            'treasurer_name' => ['nullable', 'string', 'max:255'],
            
            // Facilities
            'facilities' => ['nullable', 'array'],
            'facilities.*' => ['string', 'max:255'],
            'has_indoor_courts' => ['boolean'],
            'has_outdoor_courts' => ['boolean'],
            'court_count' => ['nullable', 'integer', 'min:1', 'max:20'],
            'equipment_available' => ['nullable', 'array'],
            'equipment_available.*' => ['string', 'max:255'],
            
            // Membership
            'membership_fee_annual' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'membership_fee_monthly' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'accepts_new_members' => ['boolean'],
            'offers_youth_programs' => ['boolean'],
            'offers_adult_programs' => ['boolean'],
            'requires_approval' => ['boolean'],
            
            // Training
            'training_times' => ['nullable', 'array'],
            'training_times.*.day' => ['required_with:training_times', Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])],
            'training_times.*.start_time' => ['required_with:training_times', 'date_format:H:i'],
            'training_times.*.end_time' => ['required_with:training_times', 'date_format:H:i', 'after:training_times.*.start_time'],
            'training_times.*.program' => ['nullable', 'string', 'max:255'],
            'training_times.*.age_group' => ['nullable', 'string', 'max:100'],
            'training_times.*.level' => ['nullable', 'string', 'max:100'],
            
            // Contact
            'contact_person_name' => ['nullable', 'string', 'max:255'],
            'contact_person_phone' => ['nullable', 'string', 'max:50'],
            'contact_person_email' => ['nullable', 'email', 'max:255'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
            
            // Social Media
            'social_media_facebook' => ['nullable', 'url', 'max:500'],
            'social_media_instagram' => ['nullable', 'url', 'max:500'],
            'social_media_twitter' => ['nullable', 'url', 'max:500'],
            
            // Legal
            'privacy_policy_url' => ['nullable', 'url', 'max:500'],
            'terms_of_service_url' => ['nullable', 'url', 'max:500'],
            
            // Status
            'is_active' => ['boolean'],
            'is_verified' => ['boolean'],
            
            // Member management
            'user_id' => ['sometimes', 'exists:users,id'],
            'role' => ['sometimes', 'string', Rule::in(['admin', 'manager', 'member'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.max' => 'Der Club-Name darf maximal 255 Zeichen lang sein.',
            'name.unique' => 'Ein Club mit diesem Namen existiert bereits.',
            'slug.regex' => 'Der Slug darf nur Kleinbuchstaben, Zahlen und Bindestriche enthalten.',
            'slug.unique' => 'Ein Club mit diesem Slug existiert bereits.',
            'website.url' => 'Die Website muss eine gültige URL sein.',
            'email.email' => 'Die E-Mail-Adresse muss gültig sein.',
            'founded_at.before_or_equal' => 'Das Gründungsdatum darf nicht in der Zukunft liegen.',
            'logo_url.url' => 'Das Logo muss eine gültige URL sein.',
            'colors_primary.regex' => 'Die primäre Farbe muss ein gültiger Hex-Farbcode sein.',
            'colors_secondary.regex' => 'Die sekundäre Farbe muss ein gültiger Hex-Farbcode sein.',
            'president_email.email' => 'Die E-Mail-Adresse des Präsidenten muss gültig sein.',
            'court_count.min' => 'Es muss mindestens 1 Spielfeld vorhanden sein.',
            'court_count.max' => 'Es können maximal 20 Spielfelder angegeben werden.',
            'membership_fee_annual.numeric' => 'Die jährliche Mitgliedsgebühr muss eine Zahl sein.',
            'membership_fee_monthly.numeric' => 'Die monatliche Mitgliedsgebühr muss eine Zahl sein.',
            'training_times.*.day.in' => 'Der Trainingstag ist ungültig.',
            'training_times.*.start_time.date_format' => 'Die Startzeit muss im Format HH:MM angegeben werden.',
            'training_times.*.end_time.date_format' => 'Die Endzeit muss im Format HH:MM angegeben werden.',
            'training_times.*.end_time.after' => 'Die Endzeit muss nach der Startzeit liegen.',
            'contact_person_email.email' => 'Die E-Mail-Adresse der Kontaktperson muss gültig sein.',
            'social_media_facebook.url' => 'Die Facebook-URL muss gültig sein.',
            'social_media_instagram.url' => 'Die Instagram-URL muss gültig sein.',
            'social_media_twitter.url' => 'Die Twitter-URL muss gültig sein.',
            'privacy_policy_url.url' => 'Die Datenschutzrichtlinien-URL muss gültig sein.',
            'terms_of_service_url.url' => 'Die Nutzungsbedingungen-URL muss gültig sein.',
            'user_id.exists' => 'Der ausgewählte Benutzer existiert nicht.',
            'role.in' => 'Die Rolle ist ungültig.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'Club-Name',
            'short_name' => 'Kurzer Name',
            'slug' => 'Slug',
            'description' => 'Beschreibung',
            'website' => 'Website',
            'email' => 'E-Mail',
            'phone' => 'Telefon',
            'address' => 'Adresse',
            'city' => 'Stadt',
            'state' => 'Bundesland',
            'postal_code' => 'Postleitzahl',
            'country' => 'Land',
            'founded_at' => 'Gründungsdatum',
            'logo_url' => 'Logo-URL',
            'colors_primary' => 'Primäre Farbe',
            'colors_secondary' => 'Sekundäre Farbe',
            'league' => 'Liga',
            'division' => 'Division',
            'season' => 'Saison',
            'president_name' => 'Präsident',
            'president_email' => 'Präsidenten-E-Mail',
            'vice_president_name' => 'Vizepräsident',
            'secretary_name' => 'Sekretär',
            'treasurer_name' => 'Schatzmeister',
            'facilities' => 'Einrichtungen',
            'has_indoor_courts' => 'Innenhallen',
            'has_outdoor_courts' => 'Außenplätze',
            'court_count' => 'Anzahl Spielfelder',
            'equipment_available' => 'Verfügbare Ausrüstung',
            'membership_fee_annual' => 'Jährliche Mitgliedsgebühr',
            'membership_fee_monthly' => 'Monatliche Mitgliedsgebühr',
            'accepts_new_members' => 'Nimmt neue Mitglieder auf',
            'offers_youth_programs' => 'Bietet Jugendprogramme',
            'offers_adult_programs' => 'Bietet Erwachsenenprogramme',
            'requires_approval' => 'Benötigt Genehmigung',
            'training_times' => 'Trainingszeiten',
            'contact_person_name' => 'Kontaktperson',
            'contact_person_phone' => 'Kontaktperson Telefon',
            'contact_person_email' => 'Kontaktperson E-Mail',
            'emergency_contact_name' => 'Notfallkontakt',
            'emergency_contact_phone' => 'Notfallkontakt Telefon',
            'social_media_facebook' => 'Facebook',
            'social_media_instagram' => 'Instagram',
            'social_media_twitter' => 'Twitter',
            'privacy_policy_url' => 'Datenschutzrichtlinien',
            'terms_of_service_url' => 'Nutzungsbedingungen',
            'is_active' => 'Aktiv',
            'is_verified' => 'Verifiziert',
            'user_id' => 'Benutzer',
            'role' => 'Rolle',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string booleans to actual booleans if present
        $booleanFields = [
            'has_indoor_courts', 'has_outdoor_courts', 'accepts_new_members',
            'offers_youth_programs', 'offers_adult_programs', 'requires_approval',
            'is_active', 'is_verified'
        ];
        
        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $this->merge([$field => $this->boolean($field)]);
            }
        }
    }
}