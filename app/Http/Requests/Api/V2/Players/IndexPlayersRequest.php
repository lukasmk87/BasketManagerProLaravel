<?php

namespace App\Http\Requests\Api\V2\Players;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPlayersRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:255'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'club_id' => ['nullable', 'integer', 'exists:clubs,id'],
            'position' => ['nullable', Rule::in(['PG', 'SG', 'SF', 'PF', 'C'])],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'injured', 'suspended', 'transferred'])],
            'is_captain' => ['nullable', Rule::in(['true', 'false'])],
            'is_starter' => ['nullable', Rule::in(['true', 'false'])],
            'min_age' => ['nullable', 'integer', 'min:5', 'max:100'],
            'max_age' => ['nullable', 'integer', 'min:5', 'max:100', 'gte:min_age'],
            'season' => ['nullable', 'string', 'max:10'],
            'sort' => ['nullable', Rule::in(['name', 'jersey_number', 'created_at', 'points_scored', 'games_played'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'search.max' => 'Der Suchbegriff darf maximal 255 Zeichen lang sein.',
            'team_id.exists' => 'Das ausgewählte Team existiert nicht.',
            'club_id.exists' => 'Der ausgewählte Club existiert nicht.',
            'position.in' => 'Die Position ist ungültig.',
            'status.in' => 'Der Status ist ungültig.',
            'is_captain.in' => 'Der Kapitän-Status muss wahr oder falsch sein.',
            'is_starter.in' => 'Der Starter-Status muss wahr oder falsch sein.',
            'min_age.min' => 'Das Mindestalter muss mindestens 5 Jahre betragen.',
            'min_age.max' => 'Das Mindestalter darf maximal 100 Jahre betragen.',
            'max_age.min' => 'Das Höchstalter muss mindestens 5 Jahre betragen.',
            'max_age.max' => 'Das Höchstalter darf maximal 100 Jahre betragen.',
            'max_age.gte' => 'Das Höchstalter muss größer oder gleich dem Mindestalter sein.',
            'sort.in' => 'Das Sortierfeld ist ungültig.',
            'direction.in' => 'Die Sortierrichtung muss aufsteigend oder absteigend sein.',
            'per_page.min' => 'Es muss mindestens 1 Eintrag pro Seite angezeigt werden.',
            'per_page.max' => 'Es können maximal 100 Einträge pro Seite angezeigt werden.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'search' => 'Suchbegriff',
            'team_id' => 'Team',
            'club_id' => 'Club',
            'position' => 'Position',
            'status' => 'Status',
            'is_captain' => 'Kapitän',
            'is_starter' => 'Stammspieler',
            'min_age' => 'Mindestalter',
            'max_age' => 'Höchstalter',
            'season' => 'Saison',
            'sort' => 'Sortierung',
            'direction' => 'Sortierrichtung',
            'per_page' => 'Einträge pro Seite',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        if (!$this->has('per_page')) {
            $this->merge(['per_page' => 15]);
        }

        if (!$this->has('direction')) {
            $this->merge(['direction' => 'asc']);
        }
    }
}