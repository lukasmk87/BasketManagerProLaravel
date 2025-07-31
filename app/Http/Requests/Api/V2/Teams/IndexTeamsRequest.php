<?php

namespace App\Http\Requests\Api\V2\Teams;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTeamsRequest extends FormRequest
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
            'club_id' => ['nullable', 'integer', 'exists:clubs,id'],
            'season' => ['nullable', 'string', 'max:10'],
            'league' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'mixed'])],
            'age_group' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'recruiting' => ['nullable', Rule::in(['true', 'false'])],
            'sort' => ['nullable', Rule::in(['name', 'season', 'created_at', 'games_played', 'win_percentage'])],
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
            'club_id.exists' => 'Der ausgewählte Club existiert nicht.',
            'gender.in' => 'Das Geschlecht muss männlich, weiblich oder gemischt sein.',
            'status.in' => 'Der Status muss aktiv oder inaktiv sein.',
            'recruiting.in' => 'Der Recruiting-Status muster wahr oder falsch sein.',
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
            'club_id' => 'Club',
            'season' => 'Saison',
            'league' => 'Liga',
            'gender' => 'Geschlecht',
            'age_group' => 'Altersgruppe',
            'status' => 'Status',
            'recruiting' => 'Recruiting',
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