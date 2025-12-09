<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class ProfileCompletionService
{
    /**
     * Check if user needs to complete their profile.
     */
    public function needsCompletion(User $user): bool
    {
        return $user->needs_profile_completion && $user->profile_completed_at === null;
    }

    /**
     * Get required fields based on user's club role.
     *
     * @return array<string>
     */
    public function getRequiredFields(User $user): array
    {
        $baseFields = ['phone', 'date_of_birth'];

        // Get the club role from the invitation
        $clubRole = $user->clubs()
            ->wherePivotNotNull('registered_via_invitation_id')
            ->first()?->pivot?->role;

        // Players need emergency contact information
        if ($clubRole === 'player') {
            return array_merge($baseFields, [
                'emergency_contact_name',
                'emergency_contact_phone',
            ]);
        }

        return $baseFields;
    }

    /**
     * Validate if all required fields are filled.
     *
     * @return array<string> Missing field names
     */
    public function validateCompletion(User $user): array
    {
        $missing = [];
        $requiredFields = $this->getRequiredFields($user);

        foreach ($requiredFields as $field) {
            if (empty($user->{$field})) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    /**
     * Mark profile as complete and set onboarding_completed_at.
     */
    public function markComplete(User $user): void
    {
        $user->update([
            'profile_completed_at' => now(),
            'needs_profile_completion' => false,
            'onboarding_completed_at' => now(),
        ]);

        Log::info('Profile completion finished for invited user', [
            'user_id' => $user->id,
            'club_id' => $user->clubs()->first()?->id,
        ]);
    }

    /**
     * Get the club the user was invited to.
     */
    public function getInvitedClub(User $user): ?\App\Models\Club
    {
        return $user->clubs()
            ->wherePivotNotNull('registered_via_invitation_id')
            ->first();
    }

    /**
     * Get the user's club role from the invitation.
     */
    public function getClubRole(User $user): ?string
    {
        return $user->clubs()
            ->wherePivotNotNull('registered_via_invitation_id')
            ->first()?->pivot?->role;
    }
}
