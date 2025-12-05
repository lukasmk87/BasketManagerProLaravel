<?php

namespace App\Policies;

use App\Models\EmergencyContact;
use App\Models\User;
use App\Policies\Concerns\AuthorizesUsers;
use Illuminate\Auth\Access\Response;

class EmergencyContactPolicy
{
    use AuthorizesUsers;
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view emergency contacts');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, EmergencyContact $emergencyContact): bool
    {
        // Users can view their own emergency contacts
        if ($user->id === $emergencyContact->user_id) {
            return true;
        }

        // Must have permission to view emergency contacts
        if (!$user->can('view emergency contacts')) {
            return false;
        }

        // Parents can view their children's emergency contacts
        if ($user->isParent()) {
            $childUserIds = $user->children()->pluck('id')->toArray();
            if (in_array($emergencyContact->user_id, $childUserIds)) {
                return true;
            }
        }

        // Coaches can view emergency contacts of their players
        if ($user->isCoach()) {
            $playerUser = $emergencyContact->user;
            if ($playerUser && $playerUser->isPlayer()) {
                $coachTeamIds = $user->coachedTeams()->pluck('id')->toArray();
                $playerTeamId = $playerUser->playerProfile?->team_id;
                if ($playerTeamId && in_array($playerTeamId, $coachTeamIds)) {
                    return true;
                }
            }
        }

        // Club admins can view emergency contacts of users in their clubs
        if ($user->hasRole('club_admin')) {
            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
            $contactUserClubIds = $emergencyContact->user->clubs()->pluck('clubs.id')->toArray();
            if (!empty(array_intersect($userClubIds, $contactUserClubIds))) {
                return true;
            }
        }

        return true; // For admins and super_admins
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Anyone can create their own emergency contacts
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EmergencyContact $emergencyContact): bool
    {
        // Users can update their own emergency contacts
        if ($user->id === $emergencyContact->user_id) {
            return true;
        }

        // Must have permission to edit emergency contacts
        if (!$user->can('edit emergency contacts')) {
            return false;
        }

        // Parents can edit their children's emergency contacts
        if ($user->isParent()) {
            $childUserIds = $user->children()->pluck('id')->toArray();
            if (in_array($emergencyContact->user_id, $childUserIds)) {
                return true;
            }
        }

        // Club admins can edit emergency contacts of users in their clubs
        if ($user->hasRole('club_admin')) {
            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
            $contactUserClubIds = $emergencyContact->user->clubs()->pluck('clubs.id')->toArray();
            if (!empty(array_intersect($userClubIds, $contactUserClubIds))) {
                return true;
            }
        }

        return true; // For admins and super_admins
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EmergencyContact $emergencyContact): bool
    {
        // Users can delete their own emergency contacts
        if ($user->id === $emergencyContact->user_id) {
            return true;
        }

        // Must have permission to edit emergency contacts
        if (!$user->can('edit emergency contacts')) {
            return false;
        }

        // Parents can delete their children's emergency contacts
        if ($user->isParent()) {
            $childUserIds = $user->children()->pluck('id')->toArray();
            if (in_array($emergencyContact->user_id, $childUserIds)) {
                return true;
            }
        }

        return true; // For admins, super_admins, and club_admins
    }

    /**
     * Determine whether the user can generate QR codes for emergency access.
     */
    public function generateQRCode(User $user, EmergencyContact $emergencyContact): bool
    {
        // Must have QR code generation permission
        if (!$user->can('generate emergency qr codes')) {
            return false;
        }

        // Users can generate QR codes for their own emergency contacts
        if ($user->id === $emergencyContact->user_id) {
            return true;
        }

        // Coaches can generate QR codes for their players' emergency contacts
        if ($user->isCoach()) {
            $playerUser = $emergencyContact->user;
            if ($playerUser && $playerUser->isPlayer()) {
                $coachTeamIds = $user->coachedTeams()->pluck('id')->toArray();
                $playerTeamId = $playerUser->playerProfile?->team_id;
                if ($playerTeamId && in_array($playerTeamId, $coachTeamIds)) {
                    return true;
                }
            }
        }

        // Club admins can generate QR codes for their club members
        if ($user->hasRole('club_admin')) {
            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
            $contactUserClubIds = $emergencyContact->user->clubs()->pluck('clubs.id')->toArray();
            if (!empty(array_intersect($userClubIds, $contactUserClubIds))) {
                return true;
            }
        }

        return true; // For admins and super_admins
    }

    /**
     * Determine whether the user can access emergency information via QR code.
     */
    public function accessViaQRCode(User $user, EmergencyContact $emergencyContact): bool
    {
        // Must have emergency access permission
        if (!$user->can('access emergency information')) {
            return false;
        }

        // Emergency personnel can access via QR code
        if ($user->hasAnyRole(['tenant_admin', 'super_admin', 'club_admin'])) {
            return true;
        }

        // Coaches can access emergency info for their players
        if ($user->isCoach()) {
            $playerUser = $emergencyContact->user;
            if ($playerUser && $playerUser->isPlayer()) {
                $coachTeamIds = $user->coachedTeams()->pluck('id')->toArray();
                $playerTeamId = $playerUser->playerProfile?->team_id;
                if ($playerTeamId && in_array($playerTeamId, $coachTeamIds)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Determine whether the user can set emergency contact as primary.
     */
    public function setPrimary(User $user, EmergencyContact $emergencyContact): bool
    {
        // Must be able to update the emergency contact
        return $this->update($user, $emergencyContact);
    }

    /**
     * Determine whether the user can activate/deactivate the emergency contact.
     */
    public function changeStatus(User $user, EmergencyContact $emergencyContact): bool
    {
        // Must be able to update the emergency contact
        return $this->update($user, $emergencyContact);
    }

    /**
     * Determine whether the user can view emergency contact usage logs.
     */
    public function viewUsageLogs(User $user, EmergencyContact $emergencyContact): bool
    {
        // Users can view usage logs of their own emergency contacts
        if ($user->id === $emergencyContact->user_id) {
            return true;
        }

        // Must have activity log permission
        if (!$user->can('view activity logs')) {
            return false;
        }

        // Must be able to view the emergency contact
        return $this->view($user, $emergencyContact);
    }

    /**
     * Determine whether the user can export emergency contact data.
     */
    public function exportData(User $user, EmergencyContact $emergencyContact): bool
    {
        // Users can export their own emergency contact data (GDPR)
        if ($user->id === $emergencyContact->user_id) {
            return true;
        }

        // Must have export permission
        if (!$user->can('export user data')) {
            return false;
        }

        // Must be able to view the emergency contact
        return $this->view($user, $emergencyContact);
    }

    /**
     * Determine whether the user can send emergency notifications.
     */
    public function sendEmergencyNotification(User $user, EmergencyContact $emergencyContact): bool
    {
        // Must have notification permission
        if (!$user->can('send notifications')) {
            return false;
        }

        // Coaches can send emergency notifications for their players
        if ($user->isCoach()) {
            $playerUser = $emergencyContact->user;
            if ($playerUser && $playerUser->isPlayer()) {
                $coachTeamIds = $user->coachedTeams()->pluck('id')->toArray();
                $playerTeamId = $playerUser->playerProfile?->team_id;
                if ($playerTeamId && in_array($playerTeamId, $coachTeamIds)) {
                    return true;
                }
            }
        }

        // Club admins can send emergency notifications for their club members
        if ($user->hasRole('club_admin')) {
            $userClubIds = $user->clubs()->pluck('clubs.id')->toArray();
            $contactUserClubIds = $emergencyContact->user->clubs()->pluck('clubs.id')->toArray();
            if (!empty(array_intersect($userClubIds, $contactUserClubIds))) {
                return true;
            }
        }

        return true; // For admins and super_admins
    }

    /**
     * Determine whether the user can verify emergency contact information.
     */
    public function verify(User $user, EmergencyContact $emergencyContact): bool
    {
        // Must be able to edit the emergency contact
        if (!$this->update($user, $emergencyContact)) {
            return false;
        }

        // Only admins and club admins can verify emergency contacts
        return $user->hasAnyRole(['tenant_admin', 'super_admin', 'club_admin']);
    }

    /**
     * Determine whether the user can manage emergency contact relationships.
     */
    public function manageRelationships(User $user, EmergencyContact $emergencyContact): bool
    {
        // Users can manage relationships for their own emergency contacts
        if ($user->id === $emergencyContact->user_id) {
            return true;
        }

        // Parents can manage relationships for their children
        if ($user->isParent()) {
            $childUserIds = $user->children()->pluck('id')->toArray();
            if (in_array($emergencyContact->user_id, $childUserIds)) {
                return true;
            }
        }

        // Must have edit permission for others
        return $user->can('edit emergency contacts');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, EmergencyContact $emergencyContact): bool
    {
        return $this->delete($user, $emergencyContact);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, EmergencyContact $emergencyContact): bool
    {
        // Only super admins can permanently delete emergency contacts
        return $user->hasRole('super_admin');
    }

    /**
     * Determine whether the user can handle emergency situations.
     */
    public function handleEmergency(User $user, EmergencyContact $emergencyContact): bool
    {
        // Must have emergency access permission
        if (!$user->can('access emergency information')) {
            return false;
        }

        // Emergency responders and coaches have priority access
        if ($user->hasAnyRole(['tenant_admin', 'super_admin', 'club_admin', 'trainer'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update emergency protocols.
     */
    public function updateProtocols(User $user, EmergencyContact $emergencyContact): bool
    {
        // Only admins and club admins can update emergency protocols
        if (!$user->hasAnyRole(['tenant_admin', 'super_admin', 'club_admin'])) {
            return false;
        }

        // Must have edit permission
        return $user->can('edit emergency contacts');
    }
}