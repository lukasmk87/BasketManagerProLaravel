<?php

namespace Tests\Feature;

use Tests\BasketballTestCase;
use App\Models\User;
use App\Models\Club;
use App\Models\BasketballTeam;
use App\Models\PlayerRegistrationInvitation;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;

class PlayerRegistrationControllerTest extends BasketballTestCase
{
    use RefreshDatabase, WithFaker;

    private User $trainer;
    private User $clubAdmin;
    private User $player;
    private Club $club;
    private BasketballTeam $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trainer = $this->trainerUser;
        $this->clubAdmin = $this->clubAdminUser;
        $this->player = $this->playerUser;
        $this->club = $this->testClub;
        $this->team = $this->testTeam;
    }

    // ========================================
    // TRAINER ROUTES TESTS
    // ========================================

    /** @test */
    public function trainer_can_view_invitations_index()
    {
        $this->actingAs($this->trainer);

        $response = $this->get(route('trainer.invitations.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page->component('Trainer/PlayerInvitations/Index'));
    }

    /** @test */
    public function trainer_sees_only_invitations_for_their_teams()
    {
        // Create invitation for trainer's team
        $ownInvitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->club->id,
            'target_team_id' => $this->team->id,
        ]);

        // Create invitation for other club
        $otherClub = Club::factory()->create();
        $otherInvitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $otherClub->id,
        ]);

        $this->actingAs($this->trainer);

        $response = $this->get(route('trainer.invitations.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) =>
            $page->has('invitations.data', 1)  // Only one invitation
        );
    }

    /** @test */
    public function club_admin_can_see_all_club_invitations()
    {
        // Create multiple invitations for this club
        PlayerRegistrationInvitation::factory()->count(3)->create([
            'club_id' => $this->club->id,
        ]);

        $this->actingAs($this->clubAdmin);

        $response = $this->get(route('trainer.invitations.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) =>
            $page->has('invitations.data', 3)
        );
    }

    /** @test */
    public function player_cannot_access_trainer_routes()
    {
        $this->actingAs($this->player);

        $response = $this->get(route('trainer.invitations.index'));

        $response->assertStatus(403);  // Forbidden
    }

    /** @test */
    public function trainer_can_view_create_form()
    {
        $this->actingAs($this->trainer);

        $response = $this->get(route('trainer.invitations.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) =>
            $page->component('Trainer/PlayerInvitations/Create')
                ->has('clubs')
                ->has('teams')
        );
    }

    /** @test */
    public function trainer_can_create_new_invitation()
    {
        $this->actingAs($this->trainer);

        $data = [
            'club_id' => $this->club->id,
            'target_team_id' => $this->team->id,
            'expires_at' => now()->addDays(30)->toDateTimeString(),
            'max_registrations' => 50,
            'qr_size' => 300,
        ];

        $response = $this->post(route('trainer.invitations.store'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('player_registration_invitations', [
            'club_id' => $this->club->id,
            'target_team_id' => $this->team->id,
            'created_by_user_id' => $this->trainer->id,
            'max_registrations' => 50,
        ]);
    }

    /** @test */
    public function trainer_can_create_club_wide_invitation()
    {
        $this->actingAs($this->trainer);

        $data = [
            'club_id' => $this->club->id,
            'target_team_id' => null,  // No specific team
            'expires_at' => now()->addDays(30)->toDateTimeString(),
            'max_registrations' => 100,
        ];

        $response = $this->post(route('trainer.invitations.store'), $data);

        $response->assertRedirect();

        $this->assertDatabaseHas('player_registration_invitations', [
            'club_id' => $this->club->id,
            'target_team_id' => null,
        ]);
    }

    /** @test */
    public function invitation_creation_validates_club_ownership()
    {
        $otherClub = Club::factory()->create();

        $this->actingAs($this->trainer);

        $data = [
            'club_id' => $otherClub->id,  // Not trainer's club
            'expires_at' => now()->addDays(30)->toDateTimeString(),
        ];

        $response = $this->post(route('trainer.invitations.store'), $data);

        $response->assertStatus(302);  // Redirect back with errors
        $response->assertSessionHasErrors();
    }

    /** @test */
    public function invitation_creation_validates_team_belongs_to_club()
    {
        $otherClub = Club::factory()->create();
        $otherTeam = BasketballTeam::factory()->create([
            'club_id' => $otherClub->id,
        ]);

        $this->actingAs($this->trainer);

        $data = [
            'club_id' => $this->club->id,
            'target_team_id' => $otherTeam->id,  // Wrong club
            'expires_at' => now()->addDays(30)->toDateTimeString(),
        ];

        $response = $this->post(route('trainer.invitations.store'), $data);

        $response->assertSessionHasErrors('target_team_id');
    }

    /** @test */
    public function trainer_can_view_invitation_details()
    {
        $invitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->club->id,
            'target_team_id' => $this->team->id,
        ]);

        $this->actingAs($this->trainer);

        $response = $this->get(route('trainer.invitations.show', $invitation));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) =>
            $page->component('Trainer/PlayerInvitations/Show')
                ->has('invitation')
                ->has('statistics')
                ->has('registeredPlayers')
        );
    }

    /** @test */
    public function trainer_can_deactivate_invitation()
    {
        $invitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->club->id,
            'created_by_user_id' => $this->trainer->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->trainer);

        $response = $this->delete(route('trainer.invitations.destroy', $invitation));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $invitation->refresh();
        $this->assertFalse($invitation->is_active);
    }

    /** @test */
    public function trainer_can_download_qr_code_png()
    {
        $invitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->club->id,
            'qr_code_path' => 'qr-codes/test.png',
        ]);

        $this->actingAs($this->trainer);

        $response = $this->get(route('trainer.invitations.downloadQR', [
            'invitation' => $invitation,
            'format' => 'png',
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'image/png');
    }

    // ========================================
    // PUBLIC ROUTES TESTS
    // ========================================

    /** @test */
    public function valid_token_shows_registration_form()
    {
        $invitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->club->id,
            'is_active' => true,
            'expires_at' => now()->addDays(30),
        ]);

        $response = $this->get(route('public.player.register', $invitation->invitation_token));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) =>
            $page->component('Public/PlayerRegistration')
                ->has('invitation')
        );
    }

    /** @test */
    public function invalid_token_shows_error_page()
    {
        $response = $this->get(route('public.player.register', 'invalid-token-1234567890123456'));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) =>
            $page->component('Public/RegistrationError')
        );
    }

    /** @test */
    public function expired_token_shows_error_page()
    {
        $invitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->club->id,
            'is_active' => true,
            'expires_at' => now()->subDays(1),  // Expired
        ]);

        $response = $this->get(route('public.player.register', $invitation->invitation_token));

        $response->assertInertia(fn($page) =>
            $page->component('Public/RegistrationError')
        );
    }

    /** @test */
    public function public_can_submit_registration()
    {
        Notification::fake();

        $invitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->club->id,
            'created_by_user_id' => $this->trainer->id,
            'is_active' => true,
            'expires_at' => now()->addDays(30),
        ]);

        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+49123456789',
            'birth_date' => '2000-01-15',
            'gdpr_consent' => 'yes',
        ];

        $response = $this->post(route('public.player.register.submit', $invitation->invitation_token), $data);

        $response->assertRedirect(route('public.player.success', $invitation->invitation_token));

        // Check user and player were created
        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
            'account_status' => 'pending',
        ]);

        $user = User::where('email', 'john.doe@example.com')->first();

        $this->assertDatabaseHas('players', [
            'user_id' => $user->id,
            'pending_team_assignment' => true,
            'registered_via_invitation_id' => $invitation->id,
        ]);
    }

    /** @test */
    public function registration_with_optional_fields()
    {
        $invitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->club->id,
            'is_active' => true,
            'expires_at' => now()->addDays(30),
        ]);

        $data = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'phone' => '+49987654321',
            'birth_date' => '1998-05-20',
            'position' => 'PG',
            'height' => 175,
            'experience' => 'Played for 5 years',
            'street' => 'Main Street 123',
            'postal_code' => '12345',
            'city' => 'Berlin',
            'gdpr_consent' => 'yes',
            'newsletter_consent' => true,
        ];

        $response = $this->post(route('public.player.register.submit', $invitation->invitation_token), $data);

        $response->assertRedirect();

        $user = User::where('email', 'jane.smith@example.com')->first();
        $player = Player::where('user_id', $user->id)->first();

        $this->assertEquals(175, $player->height_cm);
        $this->assertEquals('PG', $player->position);
    }

    /** @test */
    public function registration_prevents_duplicate_email()
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $invitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->club->id,
            'is_active' => true,
            'expires_at' => now()->addDays(30),
        ]);

        $data = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'existing@example.com',  // Duplicate
            'phone' => '+49123456789',
            'birth_date' => '2000-01-01',
            'gdpr_consent' => 'yes',
        ];

        $response = $this->post(route('public.player.register.submit', $invitation->invitation_token), $data);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function registration_rate_limited()
    {
        $invitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->club->id,
            'is_active' => true,
            'expires_at' => now()->addDays(30),
        ]);

        $data = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => '+49123456789',
            'birth_date' => '2000-01-01',
            'gdpr_consent' => 'yes',
        ];

        // Make 6 requests (rate limit is 5/minute)
        for ($i = 1; $i <= 6; $i++) {
            $data['email'] = "test{$i}@example.com";
            $response = $this->post(route('public.player.register.submit', $invitation->invitation_token), $data);

            if ($i <= 5) {
                $response->assertStatus(302);  // Redirect (success or validation)
            } else {
                $response->assertStatus(429);  // Too Many Requests
            }
        }
    }

    /** @test */
    public function success_page_displays_correctly()
    {
        $invitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->club->id,
        ]);

        $response = $this->get(route('public.player.success', $invitation->invitation_token));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) =>
            $page->component('Public/RegistrationSuccess')
                ->has('clubName')
        );
    }
}
