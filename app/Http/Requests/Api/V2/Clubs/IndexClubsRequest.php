<?php

namespace App\Http\Requests\Api\V2\Clubs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexClubsRequest extends FormRequest
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
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'verified' => ['nullable', Rule::in(['true', 'false'])],
            'league' => ['nullable', 'string', 'max:100'],
            'division' => ['nullable', 'string', 'max:100'],
            'season' => ['nullable', 'string', 'max:10'],
            'sort' => ['nullable', Rule::in(['name', 'created_at', 'founded_at'])],
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
            'status.in' => 'Der Status muss aktiv oder inaktiv sein.',
            'verified.in' => 'Der Verifizierungsstatus muss wahr oder falsch sein.',
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
            'status' => 'Status',
            'verified' => 'Verifiziert',
            'league' => 'Liga',
            'division' => 'Division',
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