<?php

namespace App\Http\Requests\ClubAdmin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClubSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'website' => ['nullable', 'url', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'twitter_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Clubname',
            'short_name' => 'Kurzname',
            'description' => 'Beschreibung',
            'website' => 'Website',
            'email' => 'E-Mail',
            'phone' => 'Telefon',
            'address' => 'Adresse',
            'city' => 'Stadt',
            'postal_code' => 'PLZ',
            'country' => 'Land',
            'facebook_url' => 'Facebook URL',
            'twitter_url' => 'Twitter URL',
            'instagram_url' => 'Instagram URL',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Der Clubname ist erforderlich.',
            'name.max' => 'Der Clubname darf maximal 255 Zeichen lang sein.',
            'website.url' => 'Die Website muss eine gültige URL sein.',
            'email.email' => 'Die E-Mail-Adresse muss gültig sein.',
        ];
    }
}
