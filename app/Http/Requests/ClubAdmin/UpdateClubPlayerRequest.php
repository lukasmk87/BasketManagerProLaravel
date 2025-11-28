<?php

namespace App\Http\Requests\ClubAdmin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClubPlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $player = $this->route('player');
        $userId = $player?->user_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $userId],
            'birth_date' => ['nullable', 'date'],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'in:active,inactive,injured,suspended'],
            'team_id' => ['nullable', 'exists:basketball_teams,id'],
            'jersey_number' => ['nullable', 'integer', 'min:0', 'max:99'],
            'primary_position' => ['nullable', 'in:PG,SG,SF,PF,C'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Name',
            'email' => 'E-Mail',
            'birth_date' => 'Geburtsdatum',
            'phone' => 'Telefon',
            'status' => 'Status',
            'team_id' => 'Team',
            'jersey_number' => 'Trikotnummer',
            'primary_position' => 'Position',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Der Name ist erforderlich.',
            'email.required' => 'Die E-Mail-Adresse ist erforderlich.',
            'email.email' => 'Die E-Mail-Adresse muss gültig sein.',
            'email.unique' => 'Diese E-Mail-Adresse ist bereits vergeben.',
            'status.required' => 'Der Status ist erforderlich.',
            'status.in' => 'Der Status muss aktiv, inaktiv, verletzt oder gesperrt sein.',
            'team_id.exists' => 'Das ausgewählte Team existiert nicht.',
            'jersey_number.min' => 'Die Trikotnummer muss mindestens 0 sein.',
            'jersey_number.max' => 'Die Trikotnummer darf maximal 99 sein.',
            'primary_position.in' => 'Die Position muss PG, SG, SF, PF oder C sein.',
        ];
    }
}
