<?php

namespace App\Http\Requests\Drill;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateDrillRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:drills,name',
            'description' => 'required|string|max:2000',
            'objectives' => 'required|string|max:1000',
            'instructions' => 'required|string|max:3000',
            'category' => [
                'required',
                Rule::in([
                    'ball_handling', 'shooting', 'passing', 'defense', 'rebounding',
                    'conditioning', 'agility', 'footwork', 'team_offense', 'team_defense',
                    'transition', 'set_plays', 'scrimmage', 'warm_up', 'cool_down'
                ])
            ],
            'sub_category' => [
                'nullable',
                Rule::in([
                    'fundamental', 'advanced', 'position_specific', 'game_situation',
                    'individual', 'small_group', 'team', 'competitive'
                ])
            ],
            'difficulty_level' => 'required|in:beginner,intermediate,advanced,expert',
            'age_group' => 'required|in:U8,U10,U12,U14,U16,U18,adult,all',
            'min_players' => 'required|integer|min:1|max:15',
            'max_players' => 'nullable|integer|min:1|max:30|gte:min_players',
            'optimal_players' => 'nullable|integer|min:1|max:30|gte:min_players',
            'estimated_duration' => 'required|integer|min:1|max:120',
            'space_required' => 'nullable|numeric|min:0',
            'required_equipment' => 'nullable|array',
            'required_equipment.*' => 'string|max:100',
            'optional_equipment' => 'nullable|array',
            'optional_equipment.*' => 'string|max:100',
            'requires_full_court' => 'boolean',
            'requires_half_court' => 'boolean',
            'variations' => 'nullable|string|max:2000',
            'progressions' => 'nullable|string|max:2000',
            'regressions' => 'nullable|string|max:2000',
            'coaching_points' => 'nullable|array',
            'coaching_points.*' => 'string|max:200',
            'measurable_outcomes' => 'nullable|array',
            'measurable_outcomes.*' => 'string|max:200',
            'success_criteria' => 'nullable|array',
            'success_criteria.*' => 'string|max:200',
            'is_competitive' => 'boolean',
            'scoring_system' => 'nullable|string|max:1000',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'search_keywords' => 'nullable|string|max:500',
            'source' => 'nullable|string|max:255',
            'author' => 'nullable|string|max:255',
            'is_public' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Der Name des Drills ist erforderlich.',
            'name.unique' => 'Ein Drill mit diesem Namen existiert bereits.',
            'description.required' => 'Eine Beschreibung ist erforderlich.',
            'objectives.required' => 'Die Ziele des Drills müssen angegeben werden.',
            'instructions.required' => 'Anweisungen sind erforderlich.',
            'category.required' => 'Eine Kategorie muss ausgewählt werden.',
            'difficulty_level.required' => 'Das Schwierigkeitslevel muss ausgewählt werden.',
            'age_group.required' => 'Die Altersgruppe muss ausgewählt werden.',
            'min_players.required' => 'Die Mindestanzahl Spieler ist erforderlich.',
            'min_players.min' => 'Mindestens 1 Spieler ist erforderlich.',
            'max_players.gte' => 'Die Maximalanzahl muss größer oder gleich der Mindestanzahl sein.',
            'optimal_players.gte' => 'Die optimale Anzahl muss größer oder gleich der Mindestanzahl sein.',
            'estimated_duration.required' => 'Die geschätzte Dauer ist erforderlich.',
            'estimated_duration.min' => 'Die Mindestdauer beträgt 1 Minute.',
            'estimated_duration.max' => 'Die maximale Dauer beträgt 2 Stunden.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'Name',
            'description' => 'Beschreibung',
            'objectives' => 'Ziele',
            'instructions' => 'Anweisungen',
            'category' => 'Kategorie',
            'sub_category' => 'Unterkategorie',
            'difficulty_level' => 'Schwierigkeitslevel',
            'age_group' => 'Altersgruppe',
            'min_players' => 'Mindestanzahl Spieler',
            'max_players' => 'Maximalanzahl Spieler',
            'optimal_players' => 'Optimale Anzahl Spieler',
            'estimated_duration' => 'Geschätzte Dauer',
            'space_required' => 'Benötigter Platz',
            'required_equipment' => 'Benötigte Ausrüstung',
            'optional_equipment' => 'Optionale Ausrüstung',
            'variations' => 'Variationen',
            'progressions' => 'Steigerungen',
            'regressions' => 'Vereinfachungen',
            'coaching_points' => 'Trainer-Tipps',
            'measurable_outcomes' => 'Messbare Ergebnisse',
            'success_criteria' => 'Erfolgskriterien',
            'scoring_system' => 'Bewertungssystem',
            'tags' => 'Tags',
            'search_keywords' => 'Suchbegriffe',
            'source' => 'Quelle',
            'author' => 'Autor',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert boolean strings to actual booleans
        $booleanFields = ['requires_full_court', 'requires_half_court', 'is_competitive', 'is_public'];

        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false
                ]);
            }
        }

        // Convert string arrays to actual arrays
        $arrayFields = [
            'required_equipment', 'optional_equipment', 'coaching_points',
            'measurable_outcomes', 'success_criteria', 'tags'
        ];

        foreach ($arrayFields as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $this->merge([
                    $field => array_filter(explode(',', $this->input($field)))
                ]);
            }
        }

        // Ensure optimal_players doesn't exceed max_players
        if ($this->has('max_players') && $this->has('optimal_players')) {
            if ($this->optimal_players > $this->max_players) {
                $this->merge(['optimal_players' => $this->max_players]);
            }
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate court requirements logic
            if ($this->requires_full_court && $this->requires_half_court) {
                $validator->errors()->add('requires_half_court', 'Ein Drill kann nicht gleichzeitig Vollfeld und Halbfeld benötigen.');
            }

            // Validate competitive drill has scoring system
            if ($this->is_competitive && empty($this->scoring_system)) {
                $validator->errors()->add('scoring_system', 'Wettkampf-Drills benötigen ein Bewertungssystem.');
            }
        });
    }
}