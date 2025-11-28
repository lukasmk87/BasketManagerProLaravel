<?php

namespace App\Http\Requests\ClubAdmin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClubTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'season' => ['required', 'string', 'max:20'],
            'league' => ['nullable', 'string', 'max:255'],
            'age_group' => ['nullable', 'string', 'max:50'],
            'gender' => ['required', 'in:male,female,mixed'],
            'head_coach_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Teamname',
            'season' => 'Saison',
            'league' => 'Liga',
            'age_group' => 'Altersklasse',
            'gender' => 'Geschlecht',
            'head_coach_id' => 'Cheftrainer',
            'is_active' => 'Aktiv',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Der Teamname ist erforderlich.',
            'name.max' => 'Der Teamname darf maximal 255 Zeichen lang sein.',
            'season.required' => 'Die Saison ist erforderlich.',
            'gender.required' => 'Das Geschlecht ist erforderlich.',
            'gender.in' => 'Das Geschlecht muss männlich, weiblich oder gemischt sein.',
            'head_coach_id.exists' => 'Der ausgewählte Trainer existiert nicht.',
        ];
    }
}
