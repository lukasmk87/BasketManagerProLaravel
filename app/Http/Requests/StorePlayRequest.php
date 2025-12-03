<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Play::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'court_type' => ['required', Rule::in(['half_horizontal', 'full', 'half_vertical'])],
            'play_data' => ['required', 'array'],
            'play_data.version' => ['required', 'string'],
            'play_data.court' => ['required', 'array'],
            'play_data.elements' => ['required', 'array'],
            'animation_data' => ['nullable', 'array'],
            'category' => ['required', Rule::in([
                'offense', 'defense', 'press_break', 'inbound',
                'fast_break', 'zone', 'man_to_man', 'transition', 'special',
            ])],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'is_public' => ['boolean'],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Der Name ist erforderlich.',
            'play_data.required' => 'Die Spielzug-Daten sind erforderlich.',
            'court_type.in' => 'Ungültiger Spielfeld-Typ.',
            'category.in' => 'Ungültige Kategorie.',
        ];
    }
}
