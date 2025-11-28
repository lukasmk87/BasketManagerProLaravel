<?php

namespace App\Http\Requests\ClubAdmin;

use Illuminate\Foundation\Http\FormRequest;

class StoreClubMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
            'club_role' => ['required', 'in:member,player,trainer,assistant_coach,team_manager,scorer,volunteer'],
            'is_active' => ['boolean'],
            'send_credentials_email' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Name',
            'email' => 'E-Mail',
            'password' => 'Passwort',
            'phone' => 'Telefon',
            'club_role' => 'Club-Rolle',
            'is_active' => 'Aktiv',
            'send_credentials_email' => 'Zugangsdaten per E-Mail senden',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Der Name ist erforderlich.',
            'email.required' => 'Die E-Mail-Adresse ist erforderlich.',
            'email.email' => 'Die E-Mail-Adresse muss gültig sein.',
            'email.unique' => 'Diese E-Mail-Adresse ist bereits vergeben.',
            'password.required' => 'Das Passwort ist erforderlich.',
            'password.min' => 'Das Passwort muss mindestens 8 Zeichen lang sein.',
            'password.confirmed' => 'Die Passwörter stimmen nicht überein.',
            'club_role.required' => 'Die Club-Rolle ist erforderlich.',
            'club_role.in' => 'Die ausgewählte Club-Rolle ist ungültig.',
        ];
    }
}
