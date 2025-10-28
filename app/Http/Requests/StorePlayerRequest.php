<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\ClubUsageTrackingService;
use App\Models\Team;
use Illuminate\Validation\ValidationException;

class StorePlayerRequest extends FormRequest
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
            'user_id' => ['required', 'exists:users,id'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'in:male,female'],
            'nationality' => ['nullable', 'string', 'max:2'],
            'height_cm' => ['nullable', 'integer', 'min:100', 'max:250'],
            'weight_kg' => ['nullable', 'integer', 'min:30', 'max:200'],
            'preferred_position' => ['nullable', 'basketball_position'],
            'secondary_position' => ['nullable', 'basketball_position'],
            'dominant_hand' => ['nullable', 'in:left,right,both'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:100'],
            'medical_info' => ['nullable', 'string'],
            'photo_url' => ['nullable', 'url', 'max:500'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'settings' => ['nullable', 'array'],

            // Team assignments (for multi-team support)
            'teams' => ['nullable', 'array'],
            'teams.*' => ['exists:teams,id'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * This method checks usage limits for ALL clubs associated with the teams.
     * Since players can belong to multiple teams across different clubs, we need to
     * ensure each club has capacity.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $teamIds = $this->input('teams', []);

            if (empty($teamIds)) {
                return; // No teams specified, skip limit check
            }

            // Get all teams
            $teams = Team::with('club')->whereIn('id', $teamIds)->get();

            if ($teams->isEmpty()) {
                return;
            }

            // Get unique clubs
            $clubs = $teams->pluck('club')->filter()->unique('id');

            if ($clubs->isEmpty()) {
                return;
            }

            $usageTracker = app(ClubUsageTrackingService::class);
            $clubsExceedingLimit = [];

            // Check limit for each club
            foreach ($clubs as $club) {
                if (!$usageTracker->checkLimit($club, 'max_players', 1)) {
                    $limit = $club->getLimit('max_players');
                    $currentUsage = $usageTracker->getCurrentUsage($club, 'max_players');
                    $percentage = $usageTracker->getUsagePercentage($club, 'max_players');

                    $clubsExceedingLimit[] = sprintf(
                        '%s (%d/%d players - %d%%)',
                        $club->name,
                        $currentUsage,
                        $limit,
                        (int) $percentage
                    );
                }
            }

            if (!empty($clubsExceedingLimit)) {
                $message = count($clubsExceedingLimit) === 1
                    ? 'The following club has reached its player limit: '
                    : 'The following clubs have reached their player limits: ';

                $message .= implode(', ', $clubsExceedingLimit);
                $message .= '. Please upgrade the subscription plan(s) to add more players.';

                $validator->errors()->add('teams', $message);
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
            'user_id' => 'user account',
            'first_name' => 'first name',
            'last_name' => 'last name',
            'date_of_birth' => 'date of birth',
            'gender' => 'gender',
            'nationality' => 'nationality',
            'height_cm' => 'height',
            'weight_kg' => 'weight',
            'preferred_position' => 'preferred position',
            'secondary_position' => 'secondary position',
            'dominant_hand' => 'dominant hand',
            'phone' => 'phone number',
            'email' => 'email address',
            'address' => 'address',
            'city' => 'city',
            'postal_code' => 'postal code',
            'country' => 'country',
            'emergency_contact_name' => 'emergency contact name',
            'emergency_contact_phone' => 'emergency contact phone',
            'emergency_contact_relation' => 'emergency contact relation',
            'medical_info' => 'medical information',
            'photo_url' => 'photo URL',
            'bio' => 'biography',
            'teams' => 'teams',
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
            'user_id.required' => 'A user account is required for the player.',
            'user_id.exists' => 'The selected user account does not exist.',
            'first_name.required' => 'The player\'s first name is required.',
            'last_name.required' => 'The player\'s last name is required.',
            'date_of_birth.required' => 'The player\'s date of birth is required.',
            'date_of_birth.before' => 'The date of birth must be in the past.',
            'gender.required' => 'Please specify the player\'s gender.',
            'gender.in' => 'Gender must be either male or female.',
            'height_cm.integer' => 'Height must be a number in centimeters.',
            'height_cm.min' => 'Height must be at least 100 cm.',
            'height_cm.max' => 'Height cannot exceed 250 cm.',
            'weight_kg.integer' => 'Weight must be a number in kilograms.',
            'weight_kg.min' => 'Weight must be at least 30 kg.',
            'weight_kg.max' => 'Weight cannot exceed 200 kg.',
            'preferred_position.basketball_position' => 'The preferred position must be one of: PG, SG, SF, PF, C.',
            'secondary_position.basketball_position' => 'The secondary position must be one of: PG, SG, SF, PF, C.',
            'dominant_hand.in' => 'Dominant hand must be left, right, or both.',
            'email.email' => 'Please provide a valid email address.',
            'teams.array' => 'Teams must be provided as a list.',
            'teams.*.exists' => 'One or more selected teams do not exist.',
        ];
    }
}
