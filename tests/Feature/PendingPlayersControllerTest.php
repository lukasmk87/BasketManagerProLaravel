<?php

namespace Tests\Feature;

use Tests\BasketballTestCase;
use App\Models\User;
use App\Models\Club;
use App\Models\BasketballTeam;
use App\Models\Player;
use App\Models\PlayerRegistrationInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;

class PendingPlayersControllerTest extends BasketballTestCase
{
    use RefreshDatabase, WithFaker;

    private User $clubAdmin;
    private User $trainer;
    private User $player;
    private Club $club;
    private BasketballTeam $team;
    private PlayerRegistrationInvitation $invitation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clubAdmin = $this->clubAdminUser;
        $this->trainer = $this->trainerUser;
        $this->player = $this->playerUser;
        $this->club = $this->testClub;
        $this->team = $this->testTeam;

        $this->invitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->club->id,
        ]);
    }

    /** @test */
    public function club_admin_can_view_pending_players_index()
    {
        $this->actingAs($this->clubAdmin);

        $response = $this->get(route('club-admin.pending-players.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) =>
            $page->component('ClubAdmin/PendingPlayers/Index')
                ->has('pendingPlayers')
                ->has('teams')
        );
    }

    /** @test */
    public function club_admin_sees_only_pending_players_from_their_clubs()
    {
        // Create pending players for this club
        $player1 = $this->createPendingPlayer($this->invitation);
        $player2 = $this->createPendingPlayer($this->invitation);

        // Create pending player for other club
        $otherClub = Club::factory()->create();
        $otherInvitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $otherClub->id,
        ]);
        $otherPlayer = $this->createPendingPlayer($otherInvitation);

        $this->actingAs($this->clubAdmin);

        $response = $this->get(route('club-admin.pending-players.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) =>
            $page->has('pendingPlayers.data', 2)  // Only 2 from this club
        );
    }

    /** @test */
    public function club_filter_works_correctly()
    {
        $player = $this->createPendingPlayer($this->invitation);

        $this->actingAs($this->clubAdmin);

        $response = $this->get(route('club-admin.pending-players.index', [
            'club_id' => $this->club->id,
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) =>
            $page->has('pendingPlayers.data', 1)
        );
    }

    /** @test */
    public function search_by_name_works()
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'pending_team_assignment' => true,
            'registered_via_invitation_id' => $this->invitation->id,
        ]);

        $this->actingAs($this->clubAdmin);

        $response = $this->get(route('club-admin.pending-players.index', [
            'search' => 'John',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) =>
            $page->has('pendingPlayers.data', 1)
        );
    }

    /** @test */
    public function search_by_email_works()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'pending_team_assignment' => true,
            'registered_via_invitation_id' => $this->invitation->id,
        ]);

        $this->actingAs($this->clubAdmin);

        $response = $this->get(route('club-admin.pending-players.index', [
            'search' => 'test@example',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) =>
            $page->has('pendingPlayers.data', 1)
        );
    }

    /** @test */
    public function club_admin_can_assign_single_player_to_team()
    {
        Notification::fake();

        $player = $this->createPendingPlayer($this->invitation);

        $this->actingAs($this->clubAdmin);

        $response = $this->post(route('club-admin.pending-players.assign'), [
            'player_id' => $player->id,
            'team_id' => $this->team->id,
            'team_data' => [
                'jersey_number' => 23,
                'position' => 'SF',
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check player was assigned
        $player->refresh();
        $this->assertFalse($player->pending_team_assignment);
        $this->assertEquals('active', $player->status);

        // Check user account was activated
        $player->user->refresh();
        $this->assertEquals('active', $player->user->account_status);

        // Check team membership
        $this->assertDatabaseHas('player_team', [
            'player_id' => $player->id,
            'team_id' => $this->team->id,
            'jersey_number' => 23,
            'primary_position' => 'SF',
        ]);
    }

    /** @test */
    public function assignment_validates_jersey_number_uniqueness()
    {
        // Create existing player with jersey number 23
        $existingUser = User::factory()->create();
        $existingPlayer = Player::factory()->create([
            'user_id' => $existingUser->id,
        ]);
        $existingPlayer->teams()->attach($this->team->id, [
            'jersey_number' => 23,
            'is_active' => true,
        ]);

        $pendingPlayer = $this->createPendingPlayer($this->invitation);

        $this->actingAs($this->clubAdmin);

        $response = $this->post(route('club-admin.pending-players.assign'), [
            'player_id' => $pendingPlayer->id,
            'team_id' => $this->team->id,
            'team_data' => [
                'jersey_number' => 23,  // Duplicate
            ],
        ]);

        $response->assertSessionHasErrors('jersey_number');
    }

    /** @test */
    public function assignment_validates_team_belongs_to_club()
    {
        $otherClub = Club::factory()->create();
        $otherTeam = BasketballTeam::factory()->create([
            'club_id' => $otherClub->id,
        ]);

        $player = $this->createPendingPlayer($this->invitation);

        $this->actingAs($this->clubAdmin);

        $response = $this->post(route('club-admin.pending-players.assign'), [
            'player_id' => $player->id,
            'team_id' => $otherTeam->id,  // Wrong club
        ]);

        $response->assertSessionHasErrors('team_id');
    }

    /** @test */
    public function assignment_validates_team_not_full()
    {
        $fullTeam = BasketballTeam::factory()->create([
            'club_id' => $this->club->id,
            'max_players' => 1,
        ]);

        // Fill team to capacity
        $existingUser = User::factory()->create();
        $existingPlayer = Player::factory()->create([
            'user_id' => $existingUser->id,
        ]);
        $existingPlayer->teams()->attach($fullTeam->id, ['is_active' => true]);

        $pendingPlayer = $this->createPendingPlayer($this->invitation);

        $this->actingAs($this->clubAdmin);

        $response = $this->post(route('club-admin.pending-players.assign'), [
            'player_id' => $pendingPlayer->id,
            'team_id' => $fullTeam->id,
        ]);

        $response->assertSessionHasErrors('team_id');
    }

    /** @test */
    public function club_admin_can_reject_player()
    {
        $player = $this->createPendingPlayer($this->invitation);

        $this->actingAs($this->clubAdmin);

        $response = $this->delete(route('club-admin.pending-players.reject', $player));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Player and user should be soft deleted
        $this->assertSoftDeleted('players', ['id' => $player->id]);
        $this->assertSoftDeleted('users', ['id' => $player->user_id]);
    }

    /** @test */
    public function club_admin_can_bulk_assign_players()
    {
        $player1 = $this->createPendingPlayer($this->invitation);
        $player2 = $this->createPendingPlayer($this->invitation);

        $this->actingAs($this->clubAdmin);

        $response = $this->post(route('club-admin.pending-players.bulk-assign'), [
            'assignments' => [
                [
                    'player_id' => $player1->id,
                    'team_id' => $this->team->id,
                    'team_data' => ['jersey_number' => 10],
                ],
                [
                    'player_id' => $player2->id,
                    'team_id' => $this->team->id,
                    'team_data' => ['jersey_number' => 20],
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Both players should be assigned
        $player1->refresh();
        $player2->refresh();

        $this->assertFalse($player1->pending_team_assignment);
        $this->assertFalse($player2->pending_team_assignment);
    }

    /** @test */
    public function bulk_assign_handles_partial_failures()
    {
        $player1 = $this->createPendingPlayer($this->invitation);

        // Create player from other club (should fail permission check)
        $otherClub = Club::factory()->create();
        $otherInvitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $otherClub->id,
        ]);
        $player2 = $this->createPendingPlayer($otherInvitation);

        $this->actingAs($this->clubAdmin);

        $response = $this->post(route('club-admin.pending-players.bulk-assign'), [
            'assignments' => [
                [
                    'player_id' => $player1->id,
                    'team_id' => $this->team->id,
                ],
                [
                    'player_id' => $player2->id,  // Should fail
                    'team_id' => $this->team->id,
                ],
            ],
        ]);

        $response->assertRedirect();

        // Player1 should be assigned, player2 not
        $player1->refresh();
        $player2->refresh();

        $this->assertFalse($player1->pending_team_assignment);
        $this->assertTrue($player2->pending_team_assignment);
    }

    /** @test */
    public function trainer_cannot_access_pending_players()
    {
        $this->actingAs($this->trainer);

        $response = $this->get(route('club-admin.pending-players.index'));

        $response->assertStatus(403);  // Forbidden
    }

    /** @test */
    public function trainer_cannot_assign_players()
    {
        $player = $this->createPendingPlayer($this->invitation);

        $this->actingAs($this->trainer);

        $response = $this->post(route('club-admin.pending-players.assign'), [
            'player_id' => $player->id,
            'team_id' => $this->team->id,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function club_admin_from_other_club_cannot_assign_players()
    {
        $otherClub = Club::factory()->create();
        $otherClubAdmin = User::factory()->create();
        $otherClubAdmin->assignRole('club_admin');
        $otherClubAdmin->clubs()->attach($otherClub->id, ['role' => 'admin']);

        $player = $this->createPendingPlayer($this->invitation);

        $this->actingAs($otherClubAdmin);

        $response = $this->post(route('club-admin.pending-players.assign'), [
            'player_id' => $player->id,
            'team_id' => $this->team->id,
        ]);

        $response->assertSessionHasErrors();
    }

    /** @test */
    public function empty_state_when_no_pending_players()
    {
        $this->actingAs($this->clubAdmin);

        $response = $this->get(route('club-admin.pending-players.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) =>
            $page->has('pendingPlayers.data', 0)
        );
    }

    /**
     * Helper method to create a pending player
     */
    private function createPendingPlayer(PlayerRegistrationInvitation $invitation): Player
    {
        $user = User::factory()->create(['account_status' => 'pending']);
        return Player::factory()->create([
            'user_id' => $user->id,
            'pending_team_assignment' => true,
            'status' => 'pending_assignment',
            'registered_via_invitation_id' => $invitation->id,
        ]);
    }
}
