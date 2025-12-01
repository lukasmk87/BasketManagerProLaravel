<?php

namespace Tests\Unit\Services\User;

use App\Models\Club;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Services\User\UserRoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserRoleServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserRoleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserRoleService();
        $this->createRoles();
    }

    private function createRoles(): void
    {
        $roles = [
            'super_admin', 'admin', 'club_admin', 'trainer',
            'team_manager', 'scorer', 'referee', 'player', 'parent', 'guest'
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }
    }

    // ============================================
    // Basic Role Check Tests
    // ============================================

    public function test_is_coach_returns_true_for_trainer(): void
    {
        $user = User::factory()->create();
        $user->assignRole('trainer');

        $this->assertTrue($this->service->isCoach($user));
    }

    public function test_is_coach_returns_true_for_club_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('club_admin');

        $this->assertTrue($this->service->isCoach($user));
    }

    public function test_is_coach_returns_true_for_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->assertTrue($this->service->isCoach($user));
    }

    public function test_is_coach_returns_false_for_player(): void
    {
        $user = User::factory()->create();
        $user->assignRole('player');

        $this->assertFalse($this->service->isCoach($user));
    }

    public function test_is_admin_returns_true_for_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->assertTrue($this->service->isAdmin($user));
    }

    public function test_is_admin_returns_false_for_non_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('trainer');

        $this->assertFalse($this->service->isAdmin($user));
    }

    public function test_is_super_admin_returns_true_for_super_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->assertTrue($this->service->isSuperAdmin($user));
    }

    public function test_is_club_admin_returns_true_for_club_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('club_admin');

        $this->assertTrue($this->service->isClubAdmin($user));
    }

    public function test_is_parent_returns_true_for_parent_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('parent');

        $this->assertTrue($this->service->isParent($user));
    }

    public function test_is_parent_returns_true_for_user_with_children(): void
    {
        $parent = User::factory()->create();
        User::factory()->create(['parent_id' => $parent->id]);

        $this->assertTrue($this->service->isParent($parent));
    }

    public function test_is_trainer_returns_true_for_trainer(): void
    {
        $user = User::factory()->create();
        $user->assignRole('trainer');

        $this->assertTrue($this->service->isTrainer($user));
    }

    public function test_is_referee_returns_true_for_referee(): void
    {
        $user = User::factory()->create();
        $user->assignRole('referee');

        $this->assertTrue($this->service->isReferee($user));
    }

    public function test_is_scorer_returns_true_for_scorer(): void
    {
        $user = User::factory()->create();
        $user->assignRole('scorer');

        $this->assertTrue($this->service->isScorer($user));
    }

    public function test_is_team_manager_returns_true_for_team_manager(): void
    {
        $user = User::factory()->create();
        $user->assignRole('team_manager');

        $this->assertTrue($this->service->isTeamManager($user));
    }

    public function test_is_guest_returns_true_for_guest(): void
    {
        $user = User::factory()->create();
        $user->assignRole('guest');

        $this->assertTrue($this->service->isGuest($user));
    }

    // ============================================
    // Player Check Tests
    // ============================================

    public function test_is_player_returns_true_for_active_player(): void
    {
        $user = User::factory()->create(['player_profile_active' => true]);
        Player::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($this->service->isPlayer($user));
    }

    public function test_is_player_returns_false_for_inactive_profile(): void
    {
        $user = User::factory()->create(['player_profile_active' => false]);
        Player::factory()->create(['user_id' => $user->id]);

        $this->assertFalse($this->service->isPlayer($user));
    }

    public function test_is_player_returns_false_without_player_profile(): void
    {
        $user = User::factory()->create(['player_profile_active' => true]);

        $this->assertFalse($this->service->isPlayer($user));
    }

    // ============================================
    // Administered Clubs Tests
    // ============================================

    public function test_get_administered_clubs_returns_all_for_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Club::factory()->count(3)->create();

        $clubs = $this->service->getAdministeredClubs($admin, false);

        $this->assertCount(3, $clubs);
    }

    public function test_get_administered_clubs_returns_all_for_super_admin(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        Club::factory()->count(3)->create();

        $clubs = $this->service->getAdministeredClubs($superAdmin, false);

        $this->assertCount(3, $clubs);
    }

    public function test_get_administered_clubs_returns_only_owned_clubs_for_club_admin(): void
    {
        $clubAdmin = User::factory()->create();
        $clubAdmin->assignRole('club_admin');

        $ownedClub = Club::factory()->create();
        $otherClub = Club::factory()->create();

        $clubAdmin->clubs()->attach($ownedClub->id, ['role' => 'admin', 'joined_at' => now()]);

        $clubs = $this->service->getAdministeredClubs($clubAdmin, false);

        $this->assertCount(1, $clubs);
        $this->assertEquals($ownedClub->id, $clubs->first()->id);
    }

    public function test_get_administered_club_ids_returns_all_ids_for_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $clubs = Club::factory()->count(3)->create();

        $ids = $this->service->getAdministeredClubIds($admin);

        $this->assertCount(3, $ids);
        foreach ($clubs as $club) {
            $this->assertContains($club->id, $ids);
        }
    }

    // ============================================
    // Team Access Tests
    // ============================================

    public function test_has_team_access_returns_true_for_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);

        $this->assertTrue($this->service->hasTeamAccess($admin, $team));
    }

    public function test_has_team_access_returns_true_for_super_admin(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);

        $this->assertTrue($this->service->hasTeamAccess($superAdmin, $team));
    }

    public function test_has_team_access_returns_true_for_head_coach(): void
    {
        $coach = User::factory()->create();
        $coach->assignRole('trainer');

        $club = Club::factory()->create();
        $team = Team::factory()->create([
            'club_id' => $club->id,
            'head_coach_id' => $coach->id,
        ]);

        $this->assertTrue($this->service->hasTeamAccess($coach, $team));
    }

    public function test_has_team_access_returns_true_for_assistant_coach(): void
    {
        $assistantCoach = User::factory()->create();
        $assistantCoach->assignRole('trainer');

        $club = Club::factory()->create();
        $team = Team::factory()->create([
            'club_id' => $club->id,
            'assistant_coaches' => [$assistantCoach->id],
        ]);

        $this->assertTrue($this->service->hasTeamAccess($assistantCoach, $team));
    }

    public function test_has_team_access_returns_true_for_club_admin_of_club(): void
    {
        $clubAdmin = User::factory()->create();
        $clubAdmin->assignRole('club_admin');

        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);

        $clubAdmin->clubs()->attach($club->id, ['role' => 'admin', 'joined_at' => now()]);

        $this->assertTrue($this->service->hasTeamAccess($clubAdmin, $team));
    }

    public function test_has_team_access_returns_false_for_unrelated_user(): void
    {
        $user = User::factory()->create();
        $user->assignRole('player');

        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);

        $this->assertFalse($this->service->hasTeamAccess($user, $team));
    }

    // ============================================
    // Can Coach Team Tests
    // ============================================

    public function test_can_coach_team_returns_true_for_coach_with_access(): void
    {
        $coach = User::factory()->create();
        $coach->assignRole('trainer');

        $club = Club::factory()->create();
        $team = Team::factory()->create([
            'club_id' => $club->id,
            'head_coach_id' => $coach->id,
        ]);

        $this->assertTrue($this->service->canCoachTeam($coach, $team));
    }

    public function test_can_coach_team_returns_false_for_non_coach(): void
    {
        $user = User::factory()->create();
        $user->assignRole('player');

        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);

        $this->assertFalse($this->service->canCoachTeam($user, $team));
    }

    // ============================================
    // Primary Team Tests
    // ============================================

    public function test_get_primary_team_returns_player_team(): void
    {
        $club = Club::factory()->create();
        $team = Team::factory()->create(['club_id' => $club->id]);

        $user = User::factory()->create(['player_profile_active' => true]);
        Player::factory()->create([
            'user_id' => $user->id,
            'team_id' => $team->id,
        ]);

        $primaryTeam = $this->service->getPrimaryTeam($user);

        $this->assertNotNull($primaryTeam);
        $this->assertEquals($team->id, $primaryTeam->id);
    }

    public function test_get_primary_team_returns_coached_team_for_coach(): void
    {
        $coach = User::factory()->create();
        $coach->assignRole('trainer');

        $club = Club::factory()->create();
        $team = Team::factory()->create([
            'club_id' => $club->id,
            'head_coach_id' => $coach->id,
        ]);

        $primaryTeam = $this->service->getPrimaryTeam($coach);

        $this->assertNotNull($primaryTeam);
        $this->assertEquals($team->id, $primaryTeam->id);
    }

    public function test_get_primary_team_returns_null_for_user_without_team(): void
    {
        $user = User::factory()->create();

        $primaryTeam = $this->service->getPrimaryTeam($user);

        $this->assertNull($primaryTeam);
    }

    // ============================================
    // Accessible Teams Tests
    // ============================================

    public function test_get_accessible_teams_returns_all_for_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $club = Club::factory()->create();
        Team::factory()->count(3)->create(['club_id' => $club->id]);

        $teams = $this->service->getAccessibleTeams($admin);

        $this->assertCount(3, $teams);
    }

    public function test_get_accessible_teams_returns_coached_teams_for_trainer(): void
    {
        $coach = User::factory()->create();
        $coach->assignRole('trainer');

        $club = Club::factory()->create();
        $coachedTeam = Team::factory()->create([
            'club_id' => $club->id,
            'head_coach_id' => $coach->id,
        ]);
        Team::factory()->create(['club_id' => $club->id]); // Other team

        $teams = $this->service->getAccessibleTeams($coach);

        $this->assertCount(1, $teams);
        $this->assertEquals($coachedTeam->id, $teams->first()->id);
    }

    // ============================================
    // Role Hierarchy Tests
    // ============================================

    public function test_get_highest_role_returns_highest_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole(['trainer', 'player', 'guest']);

        $highestRole = $this->service->getHighestRole($user);

        $this->assertEquals('trainer', $highestRole);
    }

    public function test_get_highest_role_returns_super_admin_as_highest(): void
    {
        $user = User::factory()->create();
        $user->assignRole(['super_admin', 'admin', 'trainer']);

        $highestRole = $this->service->getHighestRole($user);

        $this->assertEquals('super_admin', $highestRole);
    }

    public function test_has_role_or_higher_returns_true_for_higher_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertTrue($this->service->hasRoleOrHigher($admin, 'trainer'));
        $this->assertTrue($this->service->hasRoleOrHigher($admin, 'player'));
        $this->assertTrue($this->service->hasRoleOrHigher($admin, 'guest'));
    }

    public function test_has_role_or_higher_returns_false_for_lower_role(): void
    {
        $trainer = User::factory()->create();
        $trainer->assignRole('trainer');

        $this->assertFalse($this->service->hasRoleOrHigher($trainer, 'admin'));
        $this->assertFalse($this->service->hasRoleOrHigher($trainer, 'super_admin'));
    }

    public function test_has_role_or_higher_returns_true_for_same_role(): void
    {
        $trainer = User::factory()->create();
        $trainer->assignRole('trainer');

        $this->assertTrue($this->service->hasRoleOrHigher($trainer, 'trainer'));
    }

    // ============================================
    // Role Names Tests
    // ============================================

    public function test_get_role_names_returns_all_roles(): void
    {
        $user = User::factory()->create();
        $user->assignRole(['trainer', 'player']);

        $roleNames = $this->service->getRoleNames($user);

        $this->assertCount(2, $roleNames);
        $this->assertContains('trainer', $roleNames);
        $this->assertContains('player', $roleNames);
    }

    // ============================================
    // Staff and Official Tests
    // ============================================

    public function test_is_staff_returns_true_for_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->assertTrue($this->service->isStaff($user));
    }

    public function test_is_staff_returns_true_for_trainer(): void
    {
        $user = User::factory()->create();
        $user->assignRole('trainer');

        $this->assertTrue($this->service->isStaff($user));
    }

    public function test_is_staff_returns_false_for_player(): void
    {
        $user = User::factory()->create();
        $user->assignRole('player');

        $this->assertFalse($this->service->isStaff($user));
    }

    public function test_is_official_returns_true_for_referee(): void
    {
        $user = User::factory()->create();
        $user->assignRole('referee');

        $this->assertTrue($this->service->isOfficial($user));
    }

    public function test_is_official_returns_true_for_scorer(): void
    {
        $user = User::factory()->create();
        $user->assignRole('scorer');

        $this->assertTrue($this->service->isOfficial($user));
    }

    public function test_is_official_returns_false_for_trainer(): void
    {
        $user = User::factory()->create();
        $user->assignRole('trainer');

        $this->assertFalse($this->service->isOfficial($user));
    }

    // ============================================
    // Club Access Tests
    // ============================================

    public function test_has_club_access_returns_true_for_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $club = Club::factory()->create();

        $this->assertTrue($this->service->hasClubAccess($admin, $club));
    }

    public function test_has_club_access_returns_true_for_member(): void
    {
        $user = User::factory()->create();
        $club = Club::factory()->create();

        $user->clubs()->attach($club->id, ['role' => 'member', 'joined_at' => now()]);

        $this->assertTrue($this->service->hasClubAccess($user, $club));
    }

    public function test_has_club_access_returns_false_for_non_member(): void
    {
        $user = User::factory()->create();
        $club = Club::factory()->create();

        $this->assertFalse($this->service->hasClubAccess($user, $club));
    }

    public function test_get_accessible_clubs_returns_all_for_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Club::factory()->count(3)->create();

        $clubs = $this->service->getAccessibleClubs($admin);

        $this->assertCount(3, $clubs);
    }

    public function test_get_accessible_clubs_returns_member_clubs_only(): void
    {
        $user = User::factory()->create();

        $memberClub = Club::factory()->create();
        Club::factory()->create(); // Other club

        $user->clubs()->attach($memberClub->id, ['role' => 'member', 'joined_at' => now()]);

        $clubs = $this->service->getAccessibleClubs($user);

        $this->assertCount(1, $clubs);
        $this->assertEquals($memberClub->id, $clubs->first()->id);
    }
}
