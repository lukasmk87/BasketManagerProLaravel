<?php

namespace Tests\Unit\Policies;

use App\Models\Club;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    private UserPolicy $policy;
    private User $superAdmin;
    private User $admin;
    private User $clubAdmin;
    private User $otherClubAdmin;
    private User $trainer;
    private User $player;
    private Club $club;
    private Club $otherClub;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new UserPolicy();

        // Create roles
        $superAdminRole = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $clubAdminRole = Role::create(['name' => 'club_admin', 'guard_name' => 'web']);
        $trainerRole = Role::create(['name' => 'trainer', 'guard_name' => 'web']);
        $playerRole = Role::create(['name' => 'player', 'guard_name' => 'web']);

        // Create permissions
        $editUsersPermission = Permission::create(['name' => 'edit users', 'guard_name' => 'web']);
        $viewUsersPermission = Permission::create(['name' => 'view users', 'guard_name' => 'web']);

        // Assign permissions to roles
        $superAdminRole->syncPermissions([$editUsersPermission, $viewUsersPermission]);
        $adminRole->syncPermissions([$editUsersPermission, $viewUsersPermission]);
        $clubAdminRole->syncPermissions([$editUsersPermission, $viewUsersPermission]);

        // Create users with different roles
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->clubAdmin = User::factory()->create();
        $this->clubAdmin->assignRole('club_admin');

        $this->otherClubAdmin = User::factory()->create();
        $this->otherClubAdmin->assignRole('club_admin');

        $this->trainer = User::factory()->create();
        $this->trainer->assignRole('trainer');

        $this->player = User::factory()->create();
        $this->player->assignRole('player');

        // Create clubs
        $this->club = Club::factory()->create();
        $this->otherClub = Club::factory()->create();

        // Attach clubAdmin to club with 'admin' pivot role
        $this->clubAdmin->clubs()->attach($this->club->id, ['role' => 'admin']);

        // Attach otherClubAdmin to otherClub
        $this->otherClubAdmin->clubs()->attach($this->otherClub->id, ['role' => 'admin']);

        // Attach player and trainer to club
        $this->player->clubs()->attach($this->club->id, ['role' => 'player']);
        $this->trainer->clubs()->attach($this->club->id, ['role' => 'trainer']);
    }

    /** @test */
    public function super_admin_can_edit_any_user()
    {
        $result = $this->policy->update($this->superAdmin, $this->player);

        $this->assertTrue($result);
    }

    /** @test */
    public function admin_can_edit_any_user_except_super_admin()
    {
        $result = $this->policy->update($this->admin, $this->player);
        $this->assertTrue($result);

        $result = $this->policy->update($this->admin, $this->clubAdmin);
        $this->assertTrue($result);
    }

    /** @test */
    public function club_admin_can_edit_users_in_their_club()
    {
        $result = $this->policy->update($this->clubAdmin, $this->player);

        $this->assertTrue($result);
    }

    /** @test */
    public function club_admin_cannot_edit_users_in_different_club()
    {
        // Create a player in a different club
        $otherPlayer = User::factory()->create();
        $otherPlayer->assignRole('player');
        $otherPlayer->clubs()->attach($this->otherClub->id, ['role' => 'player']);

        $result = $this->policy->update($this->clubAdmin, $otherPlayer);

        $this->assertFalse($result);
    }

    /** @test */
    public function club_admin_cannot_edit_super_admins()
    {
        $result = $this->policy->update($this->clubAdmin, $this->superAdmin);

        $this->assertFalse($result);
    }

    /** @test */
    public function club_admin_cannot_edit_admins()
    {
        $result = $this->policy->update($this->clubAdmin, $this->admin);

        $this->assertFalse($result);
    }

    /** @test */
    public function club_admin_cannot_edit_other_club_admins()
    {
        $result = $this->policy->update($this->clubAdmin, $this->otherClubAdmin);

        $this->assertFalse($result);
    }

    /** @test */
    public function club_admin_can_edit_users_with_specific_club_id_filter()
    {
        // Mock request with club_id parameter
        $request = Request::create('/', 'GET', ['club_id' => $this->club->id]);
        app()->instance('request', $request);

        $result = $this->policy->update($this->clubAdmin, $this->player);

        $this->assertTrue($result);
    }

    /** @test */
    public function club_admin_cannot_edit_users_with_wrong_club_id_filter()
    {
        // Mock request with wrong club_id parameter
        $request = Request::create('/', 'GET', ['club_id' => $this->otherClub->id]);
        app()->instance('request', $request);

        $result = $this->policy->update($this->clubAdmin, $this->player);

        $this->assertFalse($result, 'Club admin should not be able to edit users when club_id filter is for a club they do not administer');
    }

    /** @test */
    public function club_admin_cannot_edit_user_not_in_filtered_club()
    {
        // Create a player who is in clubAdmin's club but filter by different club
        // This tests the case where player IS in clubAdmin's club, but not in the filtered club

        // Add player to both clubs
        $this->player->clubs()->attach($this->otherClub->id, ['role' => 'player']);

        // Mock request filtering by otherClub
        $request = Request::create('/', 'GET', ['club_id' => $this->otherClub->id]);
        app()->instance('request', $request);

        $result = $this->policy->update($this->clubAdmin, $this->player);

        $this->assertFalse($result, 'Club admin should not be able to edit users when filtered club is not one they administer');
    }

    /** @test */
    public function club_admin_can_view_users_in_their_club()
    {
        $result = $this->policy->view($this->clubAdmin, $this->player);

        $this->assertTrue($result);
    }

    /** @test */
    public function club_admin_cannot_view_users_in_different_club()
    {
        $otherPlayer = User::factory()->create();
        $otherPlayer->assignRole('player');
        $otherPlayer->clubs()->attach($this->otherClub->id, ['role' => 'player']);

        $result = $this->policy->view($this->clubAdmin, $otherPlayer);

        $this->assertFalse($result);
    }

    /** @test */
    public function club_admin_can_view_sensitive_info_for_users_in_their_club()
    {
        $result = $this->policy->viewSensitiveInfo($this->clubAdmin, $this->player);

        $this->assertTrue($result);
    }

    /** @test */
    public function club_admin_cannot_view_sensitive_info_for_users_in_different_club()
    {
        $otherPlayer = User::factory()->create();
        $otherPlayer->assignRole('player');
        $otherPlayer->clubs()->attach($this->otherClub->id, ['role' => 'player']);

        $result = $this->policy->viewSensitiveInfo($this->clubAdmin, $otherPlayer);

        $this->assertFalse($result);
    }

    /** @test */
    public function users_can_always_view_and_edit_their_own_profile()
    {
        $result = $this->policy->view($this->player, $this->player);
        $this->assertTrue($result);

        $result = $this->policy->update($this->player, $this->player);
        $this->assertTrue($result);

        $result = $this->policy->viewSensitiveInfo($this->player, $this->player);
        $this->assertTrue($result);
    }

    /** @test */
    public function club_admin_can_view_users_with_specific_club_id_filter()
    {
        // Mock request with club_id parameter
        $request = Request::create('/', 'GET', ['club_id' => $this->club->id]);
        app()->instance('request', $request);

        $result = $this->policy->view($this->clubAdmin, $this->player);

        $this->assertTrue($result);
    }

    /** @test */
    public function club_admin_can_view_sensitive_info_with_specific_club_id_filter()
    {
        // Mock request with club_id parameter
        $request = Request::create('/', 'GET', ['club_id' => $this->club->id]);
        app()->instance('request', $request);

        $result = $this->policy->viewSensitiveInfo($this->clubAdmin, $this->player);

        $this->assertTrue($result);
    }
}
