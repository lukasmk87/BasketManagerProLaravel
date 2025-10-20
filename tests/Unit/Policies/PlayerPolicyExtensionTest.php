<?php

namespace Tests\Unit\Policies;

use Tests\BasketballTestCase;
use App\Models\User;
use App\Models\Club;
use App\Models\Player;
use App\Models\PlayerRegistrationInvitation;
use App\Policies\PlayerPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PlayerPolicyExtensionTest extends BasketballTestCase
{
    use RefreshDatabase;

    private PlayerPolicy $policy;
    private User $superAdmin;
    private User $admin;
    private User $clubAdmin;
    private User $trainer;
    private User $player;
    private Club $club;
    private Club $otherClub;
    private Player $pendingPlayer;
    private PlayerRegistrationInvitation $invitation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new PlayerPolicy();

        // Create users with different roles
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->clubAdmin = User::factory()->create();
        $this->clubAdmin->assignRole('club_admin');

        $this->trainer = User::factory()->create();
        $this->trainer->assignRole('trainer');

        $this->player = User::factory()->create();
        $this->player->assignRole('player');

        // Create clubs
        $this->club = Club::factory()->create();
        $this->otherClub = Club::factory()->create();

        // Attach users to club
        $this->clubAdmin->clubs()->attach($this->club->id, ['role' => 'club_admin']);
        $this->trainer->clubs()->attach($this->club->id, ['role' => 'trainer']);

        // Create invitation and pending player
        $this->invitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->club->id,
        ]);

        $playerUser = User::factory()->create();
        $this->pendingPlayer = Player::factory()->create([
            'user_id' => $playerUser->id,
            'pending_team_assignment' => true,
            'registered_via_invitation_id' => $this->invitation->id,
        ]);
    }

    /** @test */
    public function viewPending_only_allows_users_with_assign_permission()
    {
        $this->assertTrue($this->policy->viewPending($this->superAdmin));
        $this->assertTrue($this->policy->viewPending($this->admin));
        $this->assertTrue($this->policy->viewPending($this->clubAdmin));
        $this->assertFalse($this->policy->viewPending($this->trainer));
        $this->assertFalse($this->policy->viewPending($this->player));
    }

    /** @test */
    public function viewPending_requires_club_admin_role_minimum()
    {
        $userWithoutRole = User::factory()->create();
        $userWithoutRole->givePermissionTo('assign pending players');

        // Even with permission, must have club_admin or higher role
        $this->assertFalse($this->policy->viewPending($userWithoutRole));
    }

    /** @test */
    public function super_admin_can_assign_any_pending_player()
    {
        $this->assertTrue($this->policy->assignToTeam($this->superAdmin, $this->pendingPlayer));
    }

    /** @test */
    public function admin_can_assign_any_pending_player()
    {
        $this->assertTrue($this->policy->assignToTeam($this->admin, $this->pendingPlayer));
    }

    /** @test */
    public function club_admin_can_assign_players_from_their_club()
    {
        $this->assertTrue($this->policy->assignToTeam($this->clubAdmin, $this->pendingPlayer));
    }

    /** @test */
    public function club_admin_cannot_assign_players_from_other_clubs()
    {
        $otherInvitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->otherClub->id,
        ]);

        $otherPlayerUser = User::factory()->create();
        $otherPlayer = Player::factory()->create([
            'user_id' => $otherPlayerUser->id,
            'pending_team_assignment' => true,
            'registered_via_invitation_id' => $otherInvitation->id,
        ]);

        $this->assertFalse($this->policy->assignToTeam($this->clubAdmin, $otherPlayer));
    }

    /** @test */
    public function trainer_cannot_assign_players()
    {
        $this->assertFalse($this->policy->assignToTeam($this->trainer, $this->pendingPlayer));
    }

    /** @test */
    public function regular_player_cannot_assign_players()
    {
        $this->assertFalse($this->policy->assignToTeam($this->player, $this->pendingPlayer));
    }

    /** @test */
    public function cannot_assign_player_without_pending_status()
    {
        $activatedPlayer = Player::factory()->create([
            'pending_team_assignment' => false,
            'status' => 'active',
        ]);

        $this->assertFalse($this->policy->assignToTeam($this->clubAdmin, $activatedPlayer));
    }

    /** @test */
    public function cannot_assign_player_without_invitation()
    {
        $playerUser = User::factory()->create();
        $playerWithoutInvitation = Player::factory()->create([
            'user_id' => $playerUser->id,
            'pending_team_assignment' => true,
            'registered_via_invitation_id' => null,
        ]);

        $this->assertFalse($this->policy->assignToTeam($this->clubAdmin, $playerWithoutInvitation));
    }

    /** @test */
    public function super_admin_can_assign_player_even_without_invitation()
    {
        $playerUser = User::factory()->create();
        $playerWithoutInvitation = Player::factory()->create([
            'user_id' => $playerUser->id,
            'pending_team_assignment' => true,
            'registered_via_invitation_id' => null,
        ]);

        // Super admin bypasses invitation check
        $this->assertTrue($this->policy->assignToTeam($this->superAdmin, $playerWithoutInvitation));
    }

    /** @test */
    public function multiple_club_admins_from_same_club_can_assign()
    {
        $secondClubAdmin = User::factory()->create();
        $secondClubAdmin->assignRole('club_admin');
        $secondClubAdmin->clubs()->attach($this->club->id, ['role' => 'club_admin']);

        $this->assertTrue($this->policy->assignToTeam($this->clubAdmin, $this->pendingPlayer));
        $this->assertTrue($this->policy->assignToTeam($secondClubAdmin, $this->pendingPlayer));
    }
}
