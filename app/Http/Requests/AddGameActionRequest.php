<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddGameActionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'player_id' => 'required|exists:players,id',
            'team_id' => 'required|exists:teams,id',
            'action_type' => [
                'required',
                'in:field_goal_made,field_goal_missed,three_point_made,three_point_missed,' .
                'free_throw_made,free_throw_missed,rebound_offensive,rebound_defensive,' .
                'assist,steal,block,turnover,foul_personal,foul_technical,foul_flagrant,' .
                'foul_unsportsmanlike,foul_offensive,substitution_in,substitution_out,' .
                'timeout_team,timeout_official,jump_ball_won,jump_ball_lost,ejection,injury_timeout'
            ],
            'points' => 'integer|min:0|max:3',
            'is_successful' => 'boolean',
            'is_assisted' => 'boolean',
            'assisted_by_player_id' => 'nullable|exists:players,id',
            
            // Shot chart data
            'shot_x' => 'nullable|numeric|min:0|max:100',
            'shot_y' => 'nullable|numeric|min:0|max:100',
            'shot_distance' => 'nullable|numeric|min:0|max:30',
            'shot_zone' => 'nullable|string|in:paint,mid_range,three_point,free_throw',
            
            // Foul details
            'foul_type' => 'nullable|in:shooting,non_shooting,technical,flagrant_1,flagrant_2,unsportsmanlike,offensive',
            'foul_results_in_free_throws' => 'boolean',
            'free_throws_awarded' => 'integer|min:0|max:3',
            
            // Substitution details
            'substituted_player_id' => 'nullable|exists:players,id',
            'substitution_reason' => 'nullable|string|max:255',
            
            // Additional context
            'description' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'additional_data' => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'player_id.required' => 'Ein Spieler muss ausgewählt werden.',
            'player_id.exists' => 'Der ausgewählte Spieler existiert nicht.',
            'team_id.required' => 'Ein Team muss ausgewählt werden.',
            'team_id.exists' => 'Das ausgewählte Team existiert nicht.',
            'action_type.required' => 'Ein Aktionstyp muss ausgewählt werden.',
            'action_type.in' => 'Der ausgewählte Aktionstyp ist ungültig.',
            'points.integer' => 'Punkte müssen eine ganze Zahl sein.',
            'points.min' => 'Punkte können nicht negativ sein.',
            'points.max' => 'Maximal 3 Punkte pro Aktion möglich.',
            'shot_x.numeric' => 'X-Koordinate muss eine Zahl sein.',
            'shot_y.numeric' => 'Y-Koordinate muss eine Zahl sein.',
            'shot_distance.numeric' => 'Wurfdistanz muss eine Zahl sein.',
            'foul_type.in' => 'Ungültiger Foul-Typ.',
            'free_throws_awarded.max' => 'Maximal 3 Freiwürfe pro Foul möglich.',
            'substituted_player_id.exists' => 'Der ausgewechselte Spieler existiert nicht.',
            'description.max' => 'Beschreibung darf maximal 500 Zeichen haben.',
            'notes.max' => 'Notizen dürfen maximal 1000 Zeichen haben.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate shot coordinates for shot attempts
            if (in_array($this->action_type, ['field_goal_made', 'field_goal_missed', 'three_point_made', 'three_point_missed'])) {
                if (!$this->shot_x || !$this->shot_y) {
                    $validator->errors()->add('shot_coordinates', 'Wurfkoordinaten sind für Schussversuche erforderlich.');
                }
            }

            // Validate assisted shots
            if ($this->is_assisted && !$this->assisted_by_player_id) {
                $validator->errors()->add('assisted_by_player_id', 'Assistgeber muss bei assistierten Würfen angegeben werden.');
            }

            // Validate foul details
            if (str_starts_with($this->action_type, 'foul_')) {
                if (!$this->foul_type) {
                    $validator->errors()->add('foul_type', 'Foul-Typ ist für Fouls erforderlich.');
                }
            }

            // Validate substitution details
            if (in_array($this->action_type, ['substitution_in', 'substitution_out'])) {
                if (!$this->substituted_player_id) {
                    $validator->errors()->add('substituted_player_id', 'Ausgewechselter Spieler ist für Auswechslungen erforderlich.');
                }
            }

            // Validate points based on action type
            $expectedPoints = match ($this->action_type) {
                'three_point_made' => 3,
                'field_goal_made' => 2,
                'free_throw_made' => 1,
                default => 0,
            };

            if ($this->points !== $expectedPoints) {
                $validator->errors()->add('points', "Punkte müssen {$expectedPoints} für diese Aktion sein.");
            }
        });
    }
}