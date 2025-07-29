<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGameScoreRequest extends FormRequest
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
            'team' => 'required|in:home,away',
            'points' => 'required|integer|min:1|max:3',
            'player_id' => 'required|exists:players,id',
            'action_type' => 'nullable|in:field_goal_made,three_point_made,free_throw_made',
            'reason' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'team.required' => 'Team muss angegeben werden.',
            'team.in' => 'Team muss "home" oder "away" sein.',
            'points.required' => 'Punkte müssen angegeben werden.',
            'points.integer' => 'Punkte müssen eine ganze Zahl sein.',
            'points.min' => 'Mindestens 1 Punkt erforderlich.',
            'points.max' => 'Maximal 3 Punkte pro Aktion möglich.',
            'player_id.required' => 'Spieler muss angegeben werden.',
            'player_id.exists' => 'Der angegebene Spieler existiert nicht.',
            'action_type.in' => 'Ungültiger Aktionstyp für Punktevergabe.',
            'reason.max' => 'Grund darf maximal 255 Zeichen haben.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate points based on action type if provided
            if ($this->action_type) {
                $expectedPoints = match ($this->action_type) {
                    'three_point_made' => 3,
                    'field_goal_made' => 2,
                    'free_throw_made' => 1,
                    default => 0,
                };

                if ($this->points !== $expectedPoints) {
                    $validator->errors()->add('points', "Punkte müssen {$expectedPoints} für {$this->action_type} sein.");
                }
            }
        });
    }
}