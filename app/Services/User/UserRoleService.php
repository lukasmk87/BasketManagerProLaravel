<?php

namespace App\Services\User;

use App\Models\Club;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service für User-Rollenverwaltung und Berechtigungsprüfungen.
 *
 * Extrahiert aus dem User Model zur Reduzierung der Model-Komplexität.
 * Bietet zentrale Methoden für Rollenprüfungen und Zugriffskontrolle.
 */
class UserRoleService
{
    /**
     * Prüft ob der User ein Coach ist.
     */
    public function isCoach(User $user): bool
    {
        return $user->hasAnyRole(['trainer', 'club_admin', 'admin']);
    }

    /**
     * Prüft ob der User ein aktiver Spieler ist.
     */
    public function isPlayer(User $user): bool
    {
        return $user->player_profile_active && $user->playerProfile()->exists();
    }

    /**
     * Prüft ob der User ein Admin ist.
     */
    public function isAdmin(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Prüft ob der User ein Super Admin ist.
     */
    public function isSuperAdmin(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Prüft ob der User ein Club Admin ist.
     */
    public function isClubAdmin(User $user): bool
    {
        return $user->hasRole('club_admin');
    }

    /**
     * Prüft ob der User ein Parent ist.
     */
    public function isParent(User $user): bool
    {
        return $user->hasRole('parent') || $user->children()->exists();
    }

    /**
     * Prüft ob der User ein Trainer ist.
     */
    public function isTrainer(User $user): bool
    {
        return $user->hasRole('trainer');
    }

    /**
     * Prüft ob der User ein Schiedsrichter ist.
     */
    public function isReferee(User $user): bool
    {
        return $user->hasRole('referee');
    }

    /**
     * Prüft ob der User ein Scorer ist.
     */
    public function isScorer(User $user): bool
    {
        return $user->hasRole('scorer');
    }

    /**
     * Prüft ob der User ein Team Manager ist.
     */
    public function isTeamManager(User $user): bool
    {
        return $user->hasRole('team_manager');
    }

    /**
     * Prüft ob der User ein Gast ist.
     */
    public function isGuest(User $user): bool
    {
        return $user->hasRole('guest');
    }

    /**
     * Liefert alle Clubs, die der User administriert.
     *
     * Rollenbasierte Hierarchie:
     * - Super Admin / Admin: Alle Clubs
     * - Club Admin: Clubs mit pivot role 'admin' oder 'owner'
     *
     * @param bool $asQuery Wenn true, wird Query Builder zurückgegeben
     * @return Builder|Collection
     */
    public function getAdministeredClubs(User $user, bool $asQuery = true): Builder|Collection
    {
        // Super Admin und Admin haben Zugriff auf alle Clubs
        if ($user->hasRole(['super_admin', 'admin'])) {
            if ($asQuery) {
                return Club::query();
            }
            return Club::all();
        }

        // Club Admin hat Zugriff auf Clubs mit pivot role 'admin' oder 'owner'
        $query = $user->clubs()->wherePivotIn('role', ['admin', 'owner']);

        if ($asQuery) {
            return $query;
        }

        return $query->get();
    }

    /**
     * Liefert die IDs aller administrierten Clubs.
     *
     * @return array<int>
     */
    public function getAdministeredClubIds(User $user): array
    {
        // Super Admin und Admin haben Zugriff auf alle Clubs
        if ($user->hasRole(['super_admin', 'admin'])) {
            return Club::pluck('id')->toArray();
        }

        // Club Admin hat Zugriff auf Clubs mit pivot role 'admin' oder 'owner'
        return $user->clubs()
            ->wherePivotIn('role', ['admin', 'owner'])
            ->pluck('clubs.id')
            ->toArray();
    }

    /**
     * Prüft ob der User Zugriff auf ein Team hat.
     */
    public function hasTeamAccess(User $user, Team $team, array $permissions = []): bool
    {
        // Admin hat Zugriff auf alles
        if ($user->hasRole('admin')) {
            return true;
        }

        // Super Admin hat Zugriff auf alles
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Club Admin hat Zugriff auf alle Teams in ihren Clubs
        if ($user->hasRole('club_admin')) {
            return $user->clubs()->where('clubs.id', $team->club_id)->exists();
        }

        // Coach hat Zugriff auf seine Teams
        if ($team->head_coach_id === $user->id || in_array($user->id, $team->assistant_coaches ?? [])) {
            return true;
        }

        // Spieler hat Zugriff auf sein eigenes Team
        if ($user->playerProfile && $user->playerProfile->team_id === $team->id) {
            return true;
        }

        // Team Manager hat Zugriff auf zugewiesene Teams
        if ($user->hasRole('team_manager')) {
            // Prüfe ob der User diesem Team als Manager zugewiesen ist
            return $team->managers()->where('users.id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Prüft ob der User ein Team coachen kann.
     */
    public function canCoachTeam(User $user, Team $team): bool
    {
        return $this->isCoach($user) && $this->hasTeamAccess($user, $team);
    }

    /**
     * Liefert das primäre Team des Users (als Spieler oder Coach).
     */
    public function getPrimaryTeam(User $user): ?Team
    {
        // Wenn User ein Spieler ist, sein Team zurückgeben
        if ($this->isPlayer($user)) {
            return $user->playerProfile->team;
        }

        // Wenn User ein Coach ist, erstes gecoachtes Team zurückgeben
        if ($this->isCoach($user)) {
            return $user->coachedTeams()->first() ?? $user->assistantCoachedTeams()->first();
        }

        return null;
    }

    /**
     * Liefert alle Teams, auf die der User Zugriff hat.
     *
     * @return Collection<Team>
     */
    public function getAccessibleTeams(User $user): Collection
    {
        // Super Admin und Admin haben Zugriff auf alle Teams
        if ($user->hasRole(['super_admin', 'admin'])) {
            return Team::all();
        }

        $teamIds = collect();

        // Club Admin: Teams aller administrierten Clubs
        if ($user->hasRole('club_admin')) {
            $clubIds = $this->getAdministeredClubIds($user);
            $teamIds = $teamIds->merge(Team::whereIn('club_id', $clubIds)->pluck('id'));
        }

        // Coach: Gecoachte Teams
        if ($this->isCoach($user)) {
            $teamIds = $teamIds->merge($user->coachedTeams()->pluck('id'));
            $teamIds = $teamIds->merge($user->assistantCoachedTeams()->pluck('id'));
        }

        // Player: Eigenes Team
        if ($this->isPlayer($user) && $user->playerProfile?->team_id) {
            $teamIds->push($user->playerProfile->team_id);
        }

        return Team::whereIn('id', $teamIds->unique())->get();
    }

    /**
     * Liefert alle Clubs, auf die der User Zugriff hat.
     *
     * @return Collection<Club>
     */
    public function getAccessibleClubs(User $user): Collection
    {
        // Super Admin und Admin haben Zugriff auf alle Clubs
        if ($user->hasRole(['super_admin', 'admin'])) {
            return Club::all();
        }

        // User-Clubs aus der pivot-Tabelle
        return $user->clubs;
    }

    /**
     * Prüft ob der User Zugriff auf einen Club hat.
     */
    public function hasClubAccess(User $user, Club $club): bool
    {
        // Super Admin und Admin haben Zugriff auf alle Clubs
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        // Prüfe ob der User Mitglied des Clubs ist
        return $user->clubs()->where('clubs.id', $club->id)->exists();
    }

    /**
     * Liefert die höchste Rolle des Users.
     */
    public function getHighestRole(User $user): ?string
    {
        $roleHierarchy = [
            'super_admin' => 10,
            'admin' => 9,
            'club_admin' => 8,
            'trainer' => 7,
            'team_manager' => 6,
            'scorer' => 5,
            'referee' => 4,
            'player' => 3,
            'parent' => 2,
            'guest' => 1,
        ];

        $userRoles = $user->roles->pluck('name')->toArray();
        $highestRole = null;
        $highestLevel = 0;

        foreach ($userRoles as $role) {
            $level = $roleHierarchy[$role] ?? 0;
            if ($level > $highestLevel) {
                $highestLevel = $level;
                $highestRole = $role;
            }
        }

        return $highestRole;
    }

    /**
     * Prüft ob der User eine bestimmte Rolle oder höher hat.
     */
    public function hasRoleOrHigher(User $user, string $minimumRole): bool
    {
        $roleHierarchy = [
            'guest' => 1,
            'parent' => 2,
            'player' => 3,
            'referee' => 4,
            'scorer' => 5,
            'team_manager' => 6,
            'trainer' => 7,
            'club_admin' => 8,
            'admin' => 9,
            'super_admin' => 10,
        ];

        $minimumLevel = $roleHierarchy[$minimumRole] ?? 0;
        $highestRole = $this->getHighestRole($user);
        $userLevel = $roleHierarchy[$highestRole] ?? 0;

        return $userLevel >= $minimumLevel;
    }

    /**
     * Liefert alle Rollen des Users als Array.
     *
     * @return array<string>
     */
    public function getRoleNames(User $user): array
    {
        return $user->roles->pluck('name')->toArray();
    }

    /**
     * Prüft ob der User Staff-Mitglied ist (Admin, Club Admin, Trainer, Team Manager).
     */
    public function isStaff(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'club_admin', 'trainer', 'team_manager']);
    }

    /**
     * Prüft ob der User ein Officials ist (Schiedsrichter, Scorer).
     */
    public function isOfficial(User $user): bool
    {
        return $user->hasAnyRole(['referee', 'scorer']);
    }
}
