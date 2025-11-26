<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

class StoreClubRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,svg', 'max:2048'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Bitte gib einen Namen für deinen Club ein.',
            'name.max' => 'Der Club-Name darf maximal 255 Zeichen lang sein.',
            'city.required' => 'Bitte gib die Stadt deines Clubs ein.',
            'city.max' => 'Der Stadtname darf maximal 255 Zeichen lang sein.',
            'description.max' => 'Die Beschreibung darf maximal 1000 Zeichen lang sein.',
            'logo.image' => 'Das Logo muss ein Bild sein.',
            'logo.mimes' => 'Das Logo muss im Format JPEG, PNG, JPG oder SVG sein.',
            'logo.max' => 'Das Logo darf maximal 2MB groß sein.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'Club-Name',
            'city' => 'Stadt',
            'description' => 'Beschreibung',
            'logo' => 'Logo',
        ];
    }
}
