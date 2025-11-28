<?php

namespace App\Services\Club;

use App\Models\Club;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ClubCrudService
{
    public function __construct(
        private ClubMembershipService $membershipService
    ) {}

    /**
     * Create a new club.
     */
    public function createClub(array $data): Club
    {
        DB::beginTransaction();

        try {
            // Generate unique slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['name']);
            }

            // Set tenant_id from current tenant context if not provided
            if (empty($data['tenant_id'])) {
                // Super Admins MUST explicitly specify tenant_id (no auto-assignment)
                if (auth()->check() && auth()->user()->hasRole('super_admin')) {
                    throw new \InvalidArgumentException(
                        'Super Admins must explicitly specify tenant_id when creating clubs. ' .
                        'Please provide tenant_id in the data array.'
                    );
                }

                // For regular users, try to get tenant from app container
                $tenant = app()->bound('tenant') ? app('tenant') : null;

                if ($tenant) {
                    $data['tenant_id'] = $tenant->id;
                } else {
                    throw new \InvalidArgumentException('Tenant ID is required. Either provide tenant_id or ensure tenant context is set.');
                }
            }

            // Create club record
            $club = Club::create([
                'tenant_id' => $data['tenant_id'],
                'name' => $data['name'],
                'short_name' => $data['short_name'] ?? null,
                'slug' => $data['slug'],
                'description' => $data['description'] ?? null,
                'website' => $data['website'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address_street' => $data['address_street'] ?? null,
                'address_city' => $data['address_city'] ?? null,
                'address_state' => $data['address_state'] ?? null,
                'address_zip' => $data['address_zip'] ?? null,
                'address_country' => $data['address_country'] ?? 'DE',
                'founded_at' => $data['founded_at'] ?? null,
                'logo_path' => $data['logo_path'] ?? null,
                'primary_color' => $data['colors_primary'] ?? $data['primary_color'] ?? '#000000',
                'secondary_color' => $data['colors_secondary'] ?? $data['secondary_color'] ?? '#ffffff',
                'president_name' => $data['president_name'] ?? null,
                'president_email' => $data['president_email'] ?? null,
                'vice_president_name' => $data['vice_president_name'] ?? null,
                'secretary_name' => $data['secretary_name'] ?? null,
                'treasurer_name' => $data['treasurer_name'] ?? null,
                'facilities' => $data['facilities'] ?? null,
                'membership_fee_annual' => $data['membership_fee_annual'] ?? null,
                'membership_fee_monthly' => $data['membership_fee_monthly'] ?? null,
                'accepts_new_members' => $data['accepts_new_members'] ?? true,
                'offers_youth_programs' => $data['offers_youth_programs'] ?? true,
                'offers_adult_programs' => $data['offers_adult_programs'] ?? true,
                'has_indoor_courts' => $data['has_indoor_courts'] ?? false,
                'has_outdoor_courts' => $data['has_outdoor_courts'] ?? false,
                'court_count' => $data['court_count'] ?? 1,
                'equipment_available' => $data['equipment_available'] ?? null,
                'training_times' => $data['training_times'] ?? null,
                'contact_person_name' => $data['contact_person_name'] ?? null,
                'contact_person_phone' => $data['contact_person_phone'] ?? null,
                'contact_person_email' => $data['contact_person_email'] ?? null,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                'social_media_facebook' => $data['social_media_facebook'] ?? null,
                'social_media_instagram' => $data['social_media_instagram'] ?? null,
                'social_media_twitter' => $data['social_media_twitter'] ?? null,
                'privacy_policy_url' => $data['privacy_policy_url'] ?? null,
                'terms_of_service_url' => $data['terms_of_service_url'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'is_verified' => $data['is_verified'] ?? false,
                'requires_approval' => $data['requires_approval'] ?? false,
            ]);

            // Add current user as club admin if specified
            if (!empty($data['add_current_user_as_admin']) && $data['add_current_user_as_admin']) {
                $currentUser = auth()->user();
                if ($currentUser) {
                    $this->membershipService->addMemberToClub($club, $currentUser, 'admin');
                }
            }

            DB::commit();

            Log::info("Club created successfully", [
                'club_id' => $club->id,
                'club_name' => $club->name,
                'slug' => $club->slug
            ]);

            return $club->fresh(['teams', 'users']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create club", [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing club.
     */
    public function updateClub(Club $club, array $data): Club
    {
        DB::beginTransaction();

        try {
            // Handle slug generation if name changed
            if (isset($data['name']) && $data['name'] !== $club->name && empty($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $club->id);
            }

            $club->update($data);

            DB::commit();

            Log::info("Club updated successfully", [
                'club_id' => $club->id,
                'club_name' => $club->name,
                'updated_fields' => array_keys($data)
            ]);

            return $club->fresh(['teams', 'users']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update club", [
                'club_id' => $club->id,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Delete a club.
     */
    public function deleteClub(Club $club): bool
    {
        DB::beginTransaction();

        try {
            // Check if club has active teams
            $activeTeams = $club->teams()->active()->count();
            if ($activeTeams > 0) {
                throw new \InvalidArgumentException('Club kann nicht gelöscht werden, da noch aktive Teams vorhanden sind.');
            }

            // Check if club has active players
            $activePlayers = \App\Models\Player::query()
                ->join('player_team', 'player_team.player_id', '=', 'players.id')
                ->join('teams', 'teams.id', '=', 'player_team.team_id')
                ->where('teams.club_id', $club->id)
                ->where('player_team.is_active', true)
                ->where('player_team.status', 'active')
                ->whereNull('players.deleted_at')
                ->whereNull('teams.deleted_at')
                ->count();
            if ($activePlayers > 0) {
                throw new \InvalidArgumentException('Club kann nicht gelöscht werden, da noch aktive Spieler vorhanden sind.');
            }

            // Remove all club memberships
            $club->users()->detach();

            // Soft delete inactive teams
            $club->teams()->delete();

            // Soft delete the club
            $club->delete();

            DB::commit();

            Log::info("Club deleted successfully", [
                'club_id' => $club->id,
                'club_name' => $club->name
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to delete club", [
                'club_id' => $club->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Upload and attach logo to club.
     */
    public function uploadClubLogo(Club $club, $logo): Club
    {
        try {
            // Delete old logo if exists
            if ($club->hasMedia('logo')) {
                $club->clearMediaCollection('logo');
            }

            // Upload new logo using Spatie Media Library
            $club->addMedia($logo)
                ->usingName($club->name . ' Logo')
                ->usingFileName(time() . '_' . $logo->getClientOriginalName())
                ->toMediaCollection('logo');

            Log::info("Club logo uploaded successfully", [
                'club_id' => $club->id,
                'club_name' => $club->name,
            ]);

            return $club->fresh();

        } catch (\Exception $e) {
            Log::error("Failed to upload club logo", [
                'club_id' => $club->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Delete club logo.
     */
    public function deleteClubLogo(Club $club): Club
    {
        try {
            if ($club->hasMedia('logo')) {
                $club->clearMediaCollection('logo');
            }

            // Also clear logo_path if it exists
            if ($club->logo_path) {
                $club->update(['logo_path' => null]);
            }

            Log::info("Club logo deleted successfully", [
                'club_id' => $club->id,
                'club_name' => $club->name,
            ]);

            return $club->fresh();

        } catch (\Exception $e) {
            Log::error("Failed to delete club logo", [
                'club_id' => $club->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate a unique slug for the club.
     */
    private function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (true) {
            $query = Club::where('slug', $slug);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                break;
            }

            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
