<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\ClubUsageTrackingService;
use App\Models\Club;
use Illuminate\Validation\ValidationException;

class StoreTeamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by controller policy checks
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:teams,slug'],
            'club_id' => ['required', 'exists:clubs,id'],
            'category' => ['required', 'basketball_category'],
            'age_group' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', 'in:male,female,mixed'],
            'season' => ['required', 'current_season'],
            'division' => ['nullable', 'string', 'max:100'],
            'league' => ['nullable', 'string', 'max:100'],
            'primary_color' => ['nullable', 'string', 'max:7'],
            'secondary_color' => ['nullable', 'string', 'max:7'],
            'home_venue' => ['nullable', 'string', 'max:255'],
            'training_location' => ['nullable', 'string', 'max:255'],
            'training_schedule' => ['nullable', 'array'],
            'max_players' => ['nullable', 'integer', 'min:1', 'max:99'],
            'is_active' => ['boolean'],
            'settings' => ['nullable', 'array'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * This method is called after validation rules are applied but before validation occurs.
     * We use it to add custom validation for club usage limits.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check club usage limit for max_teams
            $clubId = $this->input('club_id');

            if (!$clubId) {
                return; // Will be caught by required rule
            }

            $club = Club::find($clubId);

            if (!$club) {
                return; // Will be caught by exists rule
            }

            // Check if club can create another team
            $usageTracker = app(ClubUsageTrackingService::class);

            if (!$usageTracker->checkLimit($club, 'max_teams', 1)) {
                $limit = $club->getLimit('max_teams');
                $currentUsage = $usageTracker->getCurrentUsage($club, 'max_teams');
                $percentage = $usageTracker->getUsagePercentage($club, 'max_teams');

                $validator->errors()->add('club_id', sprintf(
                    'Your club has reached the maximum number of teams (%d/%d teams - %d%%). Please upgrade your subscription plan to create more teams.',
                    $currentUsage,
                    $limit,
                    (int) $percentage
                ));
            }
        });
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'team name',
            'short_name' => 'short name',
            'club_id' => 'club',
            'category' => 'category',
            'age_group' => 'age group',
            'season' => 'season',
            'division' => 'division',
            'league' => 'league',
            'primary_color' => 'primary color',
            'secondary_color' => 'secondary color',
            'home_venue' => 'home venue',
            'training_location' => 'training location',
            'training_schedule' => 'training schedule',
            'max_players' => 'maximum players',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The team name is required.',
            'name.max' => 'The team name may not be greater than 255 characters.',
            'club_id.required' => 'Please select a club for this team.',
            'club_id.exists' => 'The selected club does not exist.',
            'category.required' => 'Please select a category (e.g., U12, U14, Herren).',
            'category.basketball_category' => 'The selected category is not valid.',
            'season.required' => 'Please specify the season (e.g., 2024-25).',
            'season.current_season' => 'The season format must be YYYY-YY (e.g., 2024-25).',
            'gender.in' => 'Gender must be male, female, or mixed.',
            'max_players.integer' => 'Maximum players must be a number.',
            'max_players.min' => 'Maximum players must be at least 1.',
            'max_players.max' => 'Maximum players cannot exceed 99.',
        ];
    }
}
