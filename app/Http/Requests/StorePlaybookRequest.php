<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlaybookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Playbook::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'team_id' => ['nullable', 'exists:basketball_teams,id'],
            'category' => ['required', Rule::in(['game', 'practice', 'situational'])],
            'is_default' => ['boolean'],
            'play_ids' => ['nullable', 'array'],
            'play_ids.*' => ['exists:plays,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Der Name ist erforderlich.',
            'category.in' => 'Ungültige Kategorie.',
            'team_id.exists' => 'Das ausgewählte Team existiert nicht.',
        ];
    }
}
