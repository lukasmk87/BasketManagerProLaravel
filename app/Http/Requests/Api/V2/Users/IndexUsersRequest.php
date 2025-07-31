<?php

namespace App\Http\Requests\Api\V2\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexUsersRequest extends FormRequest
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
            'role' => ['nullable', 'string', 'exists:roles,name'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'sort' => ['nullable', Rule::in(['name', 'email', 'created_at', 'last_login_at'])],
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
            'role.exists' => 'Die ausgewählte Rolle existiert nicht.',
            'status.in' => 'Der Status muss aktiv oder inaktiv sein.',
            'team_id.exists' => 'Das ausgewählte Team existiert nicht.',
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
            'role' => 'Rolle',
            'status' => 'Status',
            'team_id' => 'Team',
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