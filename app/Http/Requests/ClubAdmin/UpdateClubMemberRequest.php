<?php

namespace App\Http\Requests\ClubAdmin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClubMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $userId],
            'phone' => ['nullable', 'string', 'max:20'],
            'club_role' => ['required', 'in:member,player,trainer,assistant_coach,team_manager,scorer,volunteer'],
            'is_active' => ['boolean'],
            'membership_is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Name',
            'email' => 'E-Mail',
            'phone' => 'Telefon',
            'club_role' => 'Club-Rolle',
            'is_active' => 'Benutzer aktiv',
            'membership_is_active' => 'Mitgliedschaft aktiv',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Der Name ist erforderlich.',
            'email.required' => 'Die E-Mail-Adresse ist erforderlich.',
            'email.email' => 'Die E-Mail-Adresse muss gültig sein.',
            'email.unique' => 'Diese E-Mail-Adresse ist bereits vergeben.',
            'club_role.required' => 'Die Club-Rolle ist erforderlich.',
            'club_role.in' => 'Die ausgewählte Club-Rolle ist ungültig.',
        ];
    }
}
