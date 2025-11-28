<?php

namespace App\Services\Club;

use App\Models\Club;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClubMembershipService
{
    /**
     * Add a member to the club.
     */
    public function addMemberToClub(Club $club, User $user, string $role): void
    {
        DB::beginTransaction();

        try {
            // Check if user is already a member
            $existingMembership = $club->users()->where('user_id', $user->id)->first();

            if ($existingMembership) {
                // Update existing membership role
                $club->users()->updateExistingPivot($user->id, [
                    'role' => $role,
                    'is_active' => true,
                    'updated_at' => now()
                ]);

                Log::info("Club membership role updated", [
                    'club_id' => $club->id,
                    'user_id' => $user->id,
                    'old_role' => $existingMembership->pivot->role,
                    'new_role' => $role
                ]);
            } else {
                // Add new membership
                $club->users()->attach($user->id, [
                    'role' => $role,
                    'joined_at' => now(),
                    'is_active' => true
                ]);

                Log::info("User added to club", [
                    'club_id' => $club->id,
                    'user_id' => $user->id,
                    'role' => $role
                ]);
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to add member to club", [
                'club_id' => $club->id,
                'user_id' => $user->id,
                'role' => $role,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Remove a member from the club.
     */
    public function removeMemberFromClub(Club $club, User $user): void
    {
        DB::beginTransaction();

        try {
            // Check if user is a member
            $membership = $club->users()->where('user_id', $user->id)->first();

            if (!$membership) {
                throw new \InvalidArgumentException('Benutzer ist kein Mitglied dieses Clubs.');
            }

            // Check if user has active roles in teams
            $activeTeamRoles = $user->teams()
                ->where('club_id', $club->id)
                ->wherePivotIn('role', ['head_coach', 'assistant_coach'])
                ->count();

            if ($activeTeamRoles > 0) {
                Log::warning("Removing club member with active team roles", [
                    'club_id' => $club->id,
                    'user_id' => $user->id,
                    'active_team_roles' => $activeTeamRoles
                ]);
            }

            // Remove club membership
            $club->users()->detach($user->id);

            // Deactivate player records if any
            $user->players()->each(function ($player) use ($club) {
                $player->teams()
                    ->where('club_id', $club->id)
                    ->updateExistingPivot($player->teams()->where('club_id', $club->id)->pluck('teams.id'), [
                        'status' => 'inactive',
                        'is_active' => false,
                        'left_at' => now()
                    ]);
            });

            DB::commit();

            Log::info("User removed from club", [
                'club_id' => $club->id,
                'user_id' => $user->id,
                'role' => $membership->pivot->role
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to remove member from club", [
                'club_id' => $club->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
