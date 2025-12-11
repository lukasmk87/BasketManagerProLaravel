<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserProfileController extends Controller
{
    /**
     * Update user's personal data
     */
    public function updatePersonalData(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'phone' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other', 'prefer_not_to_say'])],
            'address_street' => ['nullable', 'string', 'max:255'],
            'address_city' => ['nullable', 'string', 'max:100'],
            'address_state' => ['nullable', 'string', 'max:100'],
            'address_zip' => ['nullable', 'string', 'max:20'],
            'address_country' => ['nullable', 'string', 'size:2'],
            'nationality' => ['nullable', 'string', 'size:2'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'employer' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'updatePersonalData')->withInput();
        }

        $user->update($validator->validated());

        return back()->with('success', 'Persönliche Daten erfolgreich aktualisiert.');
    }

    /**
     * Update user's basketball data
     */
    public function updateBasketballData(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'basketball_experience' => ['nullable', 'array'],
            'basketball_experience.years' => ['nullable', 'integer', 'min:0', 'max:100'],
            'basketball_experience.level_description' => ['nullable', 'string', 'max:1000'],
            'preferred_positions' => ['nullable', 'array'],
            'preferred_positions.*' => [Rule::in(['PG', 'SG', 'SF', 'PF', 'C'])],
            'skill_level' => ['nullable', Rule::in(['beginner', 'intermediate', 'advanced', 'professional'])],
            'player_profile_active' => ['boolean'],
            'coaching_certifications' => ['nullable', 'array'],
            'coaching_certifications.*.name' => ['required_with:coaching_certifications', 'string', 'max:255'],
            'coaching_certifications.*.year' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'coaching_certifications.*.issuer' => ['nullable', 'string', 'max:255'],
            'referee_certifications' => ['nullable', 'array'],
            'referee_certifications.*.name' => ['required_with:referee_certifications', 'string', 'max:255'],
            'referee_certifications.*.year' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'referee_certifications.*.issuer' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'updateBasketballData')->withInput();
        }

        $validated = $validator->validated();

        // Convert arrays to JSON for storage
        $user->update([
            'basketball_experience' => isset($validated['basketball_experience']) ? json_encode($validated['basketball_experience']) : null,
            'preferred_positions' => isset($validated['preferred_positions']) ? json_encode($validated['preferred_positions']) : null,
            'skill_level' => $validated['skill_level'] ?? null,
            'player_profile_active' => $validated['player_profile_active'] ?? false,
            'coaching_certifications' => isset($validated['coaching_certifications']) ? json_encode(array_filter($validated['coaching_certifications'], fn ($cert) => ! empty($cert['name']))) : null,
            'referee_certifications' => isset($validated['referee_certifications']) ? json_encode(array_filter($validated['referee_certifications'], fn ($cert) => ! empty($cert['name']))) : null,
        ]);

        return back()->with('success', 'Basketball-Daten erfolgreich aktualisiert.');
    }

    /**
     * Update user's emergency and medical data
     */
    public function updateEmergencyMedical(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:100'],
            'blood_type' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', '0+', '0-'])],
            'medical_conditions' => ['nullable', 'array'],
            'medical_conditions.*.name' => ['required_with:medical_conditions', 'string', 'max:255'],
            'medical_conditions.*.notes' => ['nullable', 'string', 'max:1000'],
            'allergies' => ['nullable', 'array'],
            'allergies.*.name' => ['required_with:allergies', 'string', 'max:255'],
            'allergies.*.severity' => ['nullable', Rule::in(['mild', 'moderate', 'severe', 'life_threatening'])],
            'allergies.*.notes' => ['nullable', 'string', 'max:1000'],
            'medications' => ['nullable', 'array'],
            'medications.*.name' => ['required_with:medications', 'string', 'max:255'],
            'medications.*.dosage' => ['nullable', 'string', 'max:100'],
            'medications.*.frequency' => ['nullable', 'string', 'max:100'],
            'medical_consent' => ['boolean'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'updateEmergencyMedical')->withInput();
        }

        $validated = $validator->validated();

        // Convert arrays to JSON for storage and filter out empty entries
        $user->update([
            'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
            'emergency_contact_relationship' => $validated['emergency_contact_relationship'] ?? null,
            'blood_type' => $validated['blood_type'] ?? null,
            'medical_conditions' => isset($validated['medical_conditions']) ? json_encode(array_filter($validated['medical_conditions'], fn ($cond) => ! empty($cond['name']))) : null,
            'allergies' => isset($validated['allergies']) ? json_encode(array_filter($validated['allergies'], fn ($allergy) => ! empty($allergy['name']))) : null,
            'medications' => isset($validated['medications']) ? json_encode(array_filter($validated['medications'], fn ($med) => ! empty($med['name']))) : null,
            'medical_consent' => $validated['medical_consent'] ?? false,
            'medical_consent_date' => ($validated['medical_consent'] ?? false) ? Carbon::now() : null,
        ]);

        return back()->with('success', 'Notfall- und medizinische Daten erfolgreich aktualisiert.');
    }

    /**
     * Update user's preferences
     */
    public function updatePreferences(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'language' => ['nullable', 'string', 'max:5'],
            'locale' => ['nullable', 'string', 'max:5'],
            'timezone' => ['nullable', 'string', 'max:100', 'timezone'],
            'date_format' => ['nullable', 'string', 'max:20'],
            'time_format' => ['nullable', 'string', 'max:20'],
            'theme' => ['nullable', 'string', Rule::in(['light', 'dark', 'system'])],
            'notification_settings' => ['nullable', 'array'],
            'notification_settings.email_notifications' => ['boolean'],
            'notification_settings.push_notifications' => ['boolean'],
            'notification_settings.game_reminders' => ['boolean'],
            'notification_settings.training_reminders' => ['boolean'],
            'notification_settings.team_announcements' => ['boolean'],
            'privacy_settings' => ['nullable', 'array'],
            'privacy_settings.profile_visible' => ['boolean'],
            'privacy_settings.show_email' => ['boolean'],
            'privacy_settings.show_phone' => ['boolean'],
            'privacy_settings.show_statistics' => ['boolean'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'updatePreferences')->withInput();
        }

        $validated = $validator->validated();

        // Handle theme in preferences JSON
        $preferences = $user->preferences ?? [];
        if (isset($validated['theme'])) {
            $preferences['theme'] = $validated['theme'];
        }

        // Convert arrays to JSON for storage
        $user->update([
            'language' => $validated['language'] ?? null,
            'locale' => $validated['locale'] ?? null,
            'timezone' => $validated['timezone'] ?? null,
            'date_format' => $validated['date_format'] ?? null,
            'time_format' => $validated['time_format'] ?? null,
            'preferences' => $preferences,
            'notification_settings' => isset($validated['notification_settings']) ? json_encode($validated['notification_settings']) : null,
            'privacy_settings' => isset($validated['privacy_settings']) ? json_encode($validated['privacy_settings']) : null,
        ]);

        // Update session locale if changed
        if (isset($validated['locale'])) {
            session(['locale' => $validated['locale']]);
        }

        return back()->with('success', 'Einstellungen erfolgreich aktualisiert.');
    }
}
