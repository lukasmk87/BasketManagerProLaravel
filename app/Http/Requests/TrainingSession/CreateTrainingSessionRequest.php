<?php

namespace App\Http\Requests\TrainingSession;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTrainingSessionRequest extends FormRequest
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
            'team_id' => 'required|exists:teams,id',
            'trainer_id' => 'required|exists:users,id',
            'assistant_trainer_id' => 'nullable|exists:users,id|different:trainer_id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'scheduled_at' => 'required|date|after:now',
            'planned_duration' => 'required|integer|min:15|max:300',
            'venue' => 'required|string|max:255',
            'venue_address' => 'nullable|string|max:500',
            'court_type' => 'nullable|in:indoor,outdoor,gym',
            'session_type' => [
                'required',
                Rule::in(['training', 'scrimmage', 'conditioning', 'tactical', 'individual', 'team_building', 'recovery'])
            ],
            'focus_areas' => 'nullable|array',
            'focus_areas.*' => 'string|max:100',
            'intensity_level' => 'required|in:low,medium,high,maximum',
            'max_participants' => 'nullable|integer|min:1|max:50',
            'weather_conditions' => 'nullable|string|max:100',
            'temperature' => 'nullable|numeric|between:-20,50',
            'weather_appropriate' => 'boolean',
            'required_equipment' => 'nullable|array',
            'required_equipment.*' => 'string|max:100',
            'special_requirements' => 'nullable|string|max:1000',
            'safety_notes' => 'nullable|string|max:1000',
            'is_mandatory' => 'boolean',
            'allows_late_arrival' => 'boolean',
            'requires_medical_clearance' => 'boolean',
            'notification_settings' => 'nullable|array',
            'notification_settings.send_reminders' => 'boolean',
            'notification_settings.reminder_times' => 'nullable|array',
            'notification_settings.reminder_times.*' => 'integer|min:1',
            'auto_add_drills' => 'boolean',
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
            'trainer_id.required' => 'Ein Trainer muss ausgewählt werden.',
            'trainer_id.exists' => 'Der ausgewählte Trainer existiert nicht.',
            'assistant_trainer_id.different' => 'Der Co-Trainer muss sich vom Haupttrainer unterscheiden.',
            'title.required' => 'Ein Titel ist erforderlich.',
            'title.max' => 'Der Titel darf maximal 255 Zeichen lang sein.',
            'scheduled_at.required' => 'Ein Termin muss festgelegt werden.',
            'scheduled_at.after' => 'Der Termin muss in der Zukunft liegen.',
            'planned_duration.required' => 'Die geplante Dauer ist erforderlich.',
            'planned_duration.min' => 'Die Mindestdauer beträgt 15 Minuten.',
            'planned_duration.max' => 'Die maximale Dauer beträgt 5 Stunden.',
            'venue.required' => 'Ein Veranstaltungsort ist erforderlich.',
            'session_type.required' => 'Der Trainingstyp muss ausgewählt werden.',
            'intensity_level.required' => 'Das Intensitätslevel muss ausgewählt werden.',
            'temperature.between' => 'Die Temperatur muss zwischen -20°C und 50°C liegen.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'team_id' => 'Team',
            'trainer_id' => 'Trainer',
            'assistant_trainer_id' => 'Co-Trainer',
            'title' => 'Titel',
            'description' => 'Beschreibung',
            'scheduled_at' => 'Termin',
            'planned_duration' => 'Geplante Dauer',
            'venue' => 'Veranstaltungsort',
            'venue_address' => 'Adresse',
            'court_type' => 'Platztyp',
            'session_type' => 'Trainingstyp',
            'focus_areas' => 'Schwerpunkte',
            'intensity_level' => 'Intensitätslevel',
            'max_participants' => 'Maximale Teilnehmer',
            'weather_conditions' => 'Wetterbedingungen',
            'temperature' => 'Temperatur',
            'required_equipment' => 'Benötigte Ausrüstung',
            'special_requirements' => 'Besondere Anforderungen',
            'safety_notes' => 'Sicherheitshinweise',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert boolean strings to actual booleans
        $booleanFields = [
            'weather_appropriate', 'is_mandatory', 'allows_late_arrival', 
            'requires_medical_clearance', 'auto_add_drills'
        ];

        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false
                ]);
            }
        }

        // Ensure focus_areas is an array
        if ($this->has('focus_areas') && is_string($this->input('focus_areas'))) {
            $this->merge([
                'focus_areas' => explode(',', $this->input('focus_areas'))
            ]);
        }

        // Ensure required_equipment is an array
        if ($this->has('required_equipment') && is_string($this->input('required_equipment'))) {
            $this->merge([
                'required_equipment' => explode(',', $this->input('required_equipment'))
            ]);
        }
    }
}