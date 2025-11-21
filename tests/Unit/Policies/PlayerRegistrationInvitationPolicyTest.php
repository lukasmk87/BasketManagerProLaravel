<?php

namespace Tests\Unit\Policies;

use Tests\BasketballTestCase;
use App\Models\User;
use App\Models\Club;
use App\Models\BasketballTeam;
use App\Models\PlayerRegistrationInvitation;
use App\Policies\PlayerRegistrationInvitationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PlayerRegistrationInvitationPolicyTest extends BasketballTestCase
{
    use RefreshDatabase;

    private PlayerRegistrationInvitationPolicy $policy;
    private User $superAdmin;
    private User $admin;
    private User $clubAdmin;
    private User $trainer;
    private User $player;
    private Club $club;
    private Club $otherClub;
    private BasketballTeam $team;
    private PlayerRegistrationInvitation $invitation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new PlayerRegistrationInvitationPolicy();

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

        // Create clubs and teams
        $this->club = Club::factory()->create();
        $this->otherClub = Club::factory()->create();

        $this->team = BasketballTeam::factory()->create([
            'club_id' => $this->club->id,
        ]);

        // Attach clubAdmin and trainer to club
        $this->clubAdmin->clubs()->attach($this->club->id, ['role' => 'admin']);
        $this->trainer->clubs()->attach($this->club->id, ['role' => 'trainer']);

        // Create sample invitation
        $this->invitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->club->id,
            'created_by_user_id' => $this->trainer->id,
            'target_team_id' => $this->team->id,
        ]);
    }

    /** @test */
    public function viewAny_allows_users_with_create_or_manage_permission()
    {
        $this->assertTrue($this->policy->viewAny($this->superAdmin));
        $this->assertTrue($this->policy->viewAny($this->admin));
        $this->assertTrue($this->policy->viewAny($this->clubAdmin));
        $this->assertTrue($this->policy->viewAny($this->trainer));
        $this->assertFalse($this->policy->viewAny($this->player));
    }

    /** @test */
    public function super_admin_can_view_any_invitation()
    {
        $this->assertTrue($this->policy->view($this->superAdmin, $this->invitation));
    }

    /** @test */
    public function admin_can_view_any_invitation()
    {
        $this->assertTrue($this->policy->view($this->admin, $this->invitation));
    }

    /** @test */
    public function club_admin_can_view_invitations_in_their_club()
    {
        $this->assertTrue($this->policy->view($this->clubAdmin, $this->invitation));
    }

    /** @test */
    public function club_admin_cannot_view_invitations_in_other_clubs()
    {
        $otherInvitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->otherClub->id,
        ]);

        $this->assertFalse($this->policy->view($this->clubAdmin, $otherInvitation));
    }

    /** @test */
    public function trainer_can_view_invitations_for_teams_they_coach()
    {
        $this->assertTrue($this->policy->view($this->trainer, $this->invitation));
    }

    /** @test */
    public function trainer_cannot_view_invitations_for_teams_they_dont_coach()
    {
        $otherTeam = BasketballTeam::factory()->create([
            'club_id' => $this->otherClub->id,
        ]);

        $otherInvitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->otherClub->id,
            'target_team_id' => $otherTeam->id,
        ]);

        $this->assertFalse($this->policy->view($this->trainer, $otherInvitation));
    }

    /** @test */
    public function trainer_can_view_club_wide_invitations_in_their_club()
    {
        $clubWideInvitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->club->id,
            'target_team_id' => null,  // No specific team
        ]);

        $this->assertTrue($this->policy->view($this->trainer, $clubWideInvitation));
    }

    /** @test */
    public function player_cannot_view_invitations()
    {
        $this->assertFalse($this->policy->view($this->player, $this->invitation));
    }

    /** @test */
    public function users_with_create_permission_can_create()
    {
        $this->assertTrue($this->policy->create($this->superAdmin));
        $this->assertTrue($this->policy->create($this->admin));
        $this->assertTrue($this->policy->create($this->clubAdmin));
        $this->assertTrue($this->policy->create($this->trainer));
        $this->assertFalse($this->policy->create($this->player));
    }

    /** @test */
    public function super_admin_can_create_for_any_club()
    {
        $this->assertTrue($this->policy->createForClub($this->superAdmin, $this->club->id));
        $this->assertTrue($this->policy->createForClub($this->superAdmin, $this->otherClub->id));
    }

    /** @test */
    public function admin_can_create_for_any_club()
    {
        $this->assertTrue($this->policy->createForClub($this->admin, $this->club->id));
        $this->assertTrue($this->policy->createForClub($this->admin, $this->otherClub->id));
    }

    /** @test */
    public function club_admin_can_create_for_their_club()
    {
        $this->assertTrue($this->policy->createForClub($this->clubAdmin, $this->club->id));
    }

    /** @test */
    public function club_admin_cannot_create_for_other_clubs()
    {
        $this->assertFalse($this->policy->createForClub($this->clubAdmin, $this->otherClub->id));
    }

    /** @test */
    public function trainer_can_create_for_their_club()
    {
        $this->assertTrue($this->policy->createForClub($this->trainer, $this->club->id));
    }

    /** @test */
    public function trainer_cannot_create_for_other_clubs()
    {
        $this->assertFalse($this->policy->createForClub($this->trainer, $this->otherClub->id));
    }

    /** @test */
    public function creator_can_update_their_invitation()
    {
        $this->assertTrue($this->policy->update($this->trainer, $this->invitation));
    }

    /** @test */
    public function club_admin_can_update_invitations_in_their_club()
    {
        $this->assertTrue($this->policy->update($this->clubAdmin, $this->invitation));
    }

    /** @test */
    public function super_admin_can_update_any_invitation()
    {
        $this->assertTrue($this->policy->update($this->superAdmin, $this->invitation));
    }

    /** @test */
    public function other_trainers_cannot_update_invitation()
    {
        $otherTrainer = User::factory()->create();
        $otherTrainer->assignRole('trainer');
        $otherTrainer->clubs()->attach($this->club->id, ['role' => 'trainer']);

        $this->assertFalse($this->policy->update($otherTrainer, $this->invitation));
    }

    /** @test */
    public function creator_can_delete_their_invitation()
    {
        $this->assertTrue($this->policy->delete($this->trainer, $this->invitation));
    }

    /** @test */
    public function club_admin_can_delete_invitations_in_their_club()
    {
        $this->assertTrue($this->policy->delete($this->clubAdmin, $this->invitation));
    }

    /** @test */
    public function super_admin_can_delete_any_invitation()
    {
        $this->assertTrue($this->policy->delete($this->superAdmin, $this->invitation));
    }

    /** @test */
    public function other_trainers_cannot_delete_invitation()
    {
        $otherTrainer = User::factory()->create();
        $otherTrainer->assignRole('trainer');
        $otherTrainer->clubs()->attach($this->club->id, ['role' => 'trainer']);

        $this->assertFalse($this->policy->delete($otherTrainer, $this->invitation));
    }

    /** @test */
    public function authorized_users_can_extend_invitation()
    {
        $this->assertTrue($this->policy->extend($this->trainer, $this->invitation));
        $this->assertTrue($this->policy->extend($this->clubAdmin, $this->invitation));
        $this->assertTrue($this->policy->extend($this->superAdmin, $this->invitation));
    }

    /** @test */
    public function unauthorized_users_cannot_extend_invitation()
    {
        $this->assertFalse($this->policy->extend($this->player, $this->invitation));

        $otherTrainer = User::factory()->create();
        $otherTrainer->assignRole('trainer');
        $this->assertFalse($this->policy->extend($otherTrainer, $this->invitation));
    }

    /** @test */
    public function authorized_users_can_download_qr()
    {
        $this->assertTrue($this->policy->downloadQR($this->trainer, $this->invitation));
        $this->assertTrue($this->policy->downloadQR($this->clubAdmin, $this->invitation));
        $this->assertTrue($this->policy->downloadQR($this->superAdmin, $this->invitation));
    }

    /** @test */
    public function authorized_users_can_view_statistics()
    {
        $this->assertTrue($this->policy->viewStatistics($this->trainer, $this->invitation));
        $this->assertTrue($this->policy->viewStatistics($this->clubAdmin, $this->invitation));
        $this->assertTrue($this->policy->viewStatistics($this->superAdmin, $this->invitation));
    }

    /** @test */
    public function authorized_users_can_view_registered_players()
    {
        $this->assertTrue($this->policy->viewRegisteredPlayers($this->trainer, $this->invitation));
        $this->assertTrue($this->policy->viewRegisteredPlayers($this->clubAdmin, $this->invitation));
        $this->assertTrue($this->policy->viewRegisteredPlayers($this->superAdmin, $this->invitation));
    }
}
