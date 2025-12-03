<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('play'));
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'court_type' => ['sometimes', Rule::in(['half_horizontal', 'full', 'half_vertical'])],
            'play_data' => ['sometimes', 'array'],
            'play_data.version' => ['required_with:play_data', 'string'],
            'play_data.court' => ['required_with:play_data', 'array'],
            'play_data.elements' => ['required_with:play_data', 'array'],
            'animation_data' => ['nullable', 'array'],
            'category' => ['sometimes', Rule::in([
                'offense', 'defense', 'press_break', 'inbound',
                'fast_break', 'zone', 'man_to_man', 'transition', 'special',
            ])],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'is_public' => ['boolean'],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
        ];
    }
}
