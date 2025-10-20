<?php

namespace Tests\Unit\Services;

use Tests\BasketballTestCase;
use App\Services\PlayerRegistrationService;
use App\Services\QRCodeService;
use App\Models\PlayerRegistrationInvitation;
use App\Models\Player;
use App\Models\User;
use App\Models\Club;
use App\Models\BasketballTeam;
use App\Notifications\PlayerRegisteredNotification;
use App\Notifications\PlayerAssignedNotification;
use App\Notifications\RegistrationWelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;
use Mockery;

class PlayerRegistrationServiceTest extends BasketballTestCase
{
    use RefreshDatabase, WithFaker;

    private PlayerRegistrationService $registrationService;
    private QRCodeService $qrCodeService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock QRCodeService
        $this->qrCodeService = Mockery::mock(QRCodeService::class);
        $this->app->instance(QRCodeService::class, $this->qrCodeService);

        $this->registrationService = app(PlayerRegistrationService::class);
    }

    /** @test */
    public function it_can_create_invitation_with_qr_code()
    {
        $user = $this->trainerUser;
        $club = $this->testClub;

        // Mock QR code generation
        $this->qrCodeService
            ->shouldReceive('generatePlayerRegistrationQR')
            ->once()
            ->with(Mockery::type(PlayerRegistrationInvitation::class), [
                'size' => 300,
                'format' => 'png',
            ])
            ->andReturn([
                'file_path' => 'qr-codes/test-qr-code.png',
                'metadata' => ['size' => 300, 'format' => 'png'],
            ]);

        $invitation = $this->registrationService->createInvitation($user->id, $club->id, [
            'target_team_id' => $this->testTeam->id,
            'expires_at' => now()->addDays(30),
            'max_registrations' => 50,
            'qr_size' => 300,
        ]);

        $this->assertInstanceOf(PlayerRegistrationInvitation::class, $invitation);
        $this->assertEquals($user->id, $invitation->created_by_user_id);
        $this->assertEquals($club->id, $invitation->club_id);
        $this->assertEquals($this->testTeam->id, $invitation->target_team_id);
        $this->assertEquals(50, $invitation->max_registrations);
        $this->assertNotNull($invitation->qr_code_path);
        $this->assertEquals('qr-codes/test-qr-code.png', $invitation->qr_code_path);
        $this->assertNotNull($invitation->invitation_token);
        $this->assertEquals(32, strlen($invitation->invitation_token));

        $this->assertDatabaseHas('player_registration_invitations', [
            'id' => $invitation->id,
            'club_id' => $club->id,
            'created_by_user_id' => $user->id,
        ]);
    }

    /** @test */
    public function it_creates_invitation_with_different_qr_sizes()
    {
        $user = $this->trainerUser;
        $club = $this->testClub;

        // Test different QR sizes
        $sizes = [100, 300, 500, 1000];

        foreach ($sizes as $size) {
            $this->qrCodeService
                ->shouldReceive('generatePlayerRegistrationQR')
                ->once()
                ->with(
                    Mockery::type(PlayerRegistrationInvitation::class),
                    ['size' => $size, 'format' => 'png']
                )
                ->andReturn([
                    'file_path' => "qr-codes/test-qr-{$size}.png",
                    'metadata' => ['size' => $size],
                ]);

            $invitation = $this->registrationService->createInvitation($user->id, $club->id, [
                'qr_size' => $size,
            ]);

            $this->assertNotNull($invitation->qr_code_path);
        }
    }

    /** @test */
    public function it_generates_unique_invitation_tokens()
    {
        $user = $this->trainerUser;
        $club = $this->testClub;

        $this->qrCodeService
            ->shouldReceive('generatePlayerRegistrationQR')
            ->times(3)
            ->andReturn([
                'file_path' => 'qr-codes/test.png',
                'metadata' => [],
            ]);

        $tokens = [];
        for ($i = 0; $i < 3; $i++) {
            $invitation = $this->registrationService->createInvitation($user->id, $club->id);
            $tokens[] = $invitation->invitation_token;
        }

        // All tokens should be unique
        $this->assertCount(3, array_unique($tokens));
    }

    /** @test */
    public function it_validates_valid_token()
    {
        $invitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->testClub->id,
            'is_active' => true,
            'expires_at' => now()->addDays(30),
            'max_registrations' => 50,
            'registered_count' => 0,
        ]);

        $result = $this->registrationService->validateToken($invitation->invitation_token);

        $this->assertTrue($result['valid']);
        $this->assertInstanceOf(PlayerRegistrationInvitation::class, $result['invitation']);
        $this->assertNull($result['error']);
    }

    /** @test */
    public function it_rejects_invalid_token()
    {
        $result = $this->registrationService->validateToken('invalid-token-123456789012345');

        $this->assertFalse($result['valid']);
        $this->assertNull($result['invitation']);
        $this->assertEquals('Invalid invitation token', $result['error']);
    }

    /** @test */
    public function it_rejects_inactive_invitation()
    {
        $invitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->testClub->id,
            'is_active' => false,
            'expires_at' => now()->addDays(30),
        ]);

        $result = $this->registrationService->validateToken($invitation->invitation_token);

        $this->assertFalse($result['valid']);
        $this->assertEquals('This invitation has been deactivated', $result['error']);
    }

    /** @test */
    public function it_rejects_expired_invitation()
    {
        $invitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->testClub->id,
            'is_active' => true,
            'expires_at' => now()->subDays(1), // Expired yesterday
        ]);

        $result = $this->registrationService->validateToken($invitation->invitation_token);

        $this->assertFalse($result['valid']);
        $this->assertEquals('This invitation has expired', $result['error']);
    }

    /** @test */
    public function it_rejects_full_invitation()
    {
        $invitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->testClub->id,
            'is_active' => true,
            'expires_at' => now()->addDays(30),
            'max_registrations' => 10,
            'registered_count' => 10, // At limit
        ]);

        $result = $this->registrationService->validateToken($invitation->invitation_token);

        $this->assertFalse($result['valid']);
        $this->assertEquals('This invitation has reached its registration limit', $result['error']);
    }

    /** @test */
    public function it_can_register_player_successfully()
    {
        Notification::fake();

        $invitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->testClub->id,
            'created_by_user_id' => $this->trainerUser->id,
            'is_active' => true,
            'expires_at' => now()->addDays(30),
            'max_registrations' => 50,
            'registered_count' => 0,
        ]);

        $playerData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+49123456789',
            'birth_date' => '2000-01-15',
            'height_cm' => 185,
            'weight_kg' => 80,
        ];

        $result = $this->registrationService->registerPlayer($invitation->invitation_token, $playerData);

        $this->assertTrue($result['success']);
        $this->assertInstanceOf(User::class, $result['user']);
        $this->assertInstanceOf(Player::class, $result['player']);
        $this->assertNull($result['error']);

        // Check user was created correctly
        $user = $result['user'];
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john.doe@example.com', $user->email);
        $this->assertEquals('pending', $user->account_status);
        $this->assertTrue($user->hasRole('player'));

        // Check player was created correctly
        $player = $result['player'];
        $this->assertEquals($user->id, $player->user_id);
        $this->assertTrue($player->pending_team_assignment);
        $this->assertEquals('pending_assignment', $player->status);
        $this->assertEquals($invitation->id, $player->registered_via_invitation_id);
        $this->assertEquals(185, $player->height_cm);
        $this->assertEquals(80, $player->weight_kg);
        $this->assertNotNull($player->registration_completed_at);

        // Check invitation counter was incremented
        $invitation->refresh();
        $this->assertEquals(1, $invitation->registered_count);

        // Check notifications were sent
        Notification::assertSentTo($user, RegistrationWelcomeNotification::class);
        Notification::assertSentTo($this->trainerUser, PlayerRegisteredNotification::class);
    }

    /** @test */
    public function it_generates_random_password_for_new_player()
    {
        $invitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->testClub->id,
            'is_active' => true,
            'expires_at' => now()->addDays(30),
        ]);

        $playerData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'phone' => '+49987654321',
            'birth_date' => '1995-05-20',
        ];

        $result = $this->registrationService->registerPlayer($invitation->invitation_token, $playerData);

        $this->assertTrue($result['success']);

        // Password should be hashed
        $this->assertNotNull($result['user']->password);
        $this->assertNotEquals('', $result['user']->password);

        // Should be a bcrypt hash
        $this->assertTrue(Hash::needsRehash($result['user']->password) === false);
    }

    /** @test */
    public function it_handles_optional_fields_correctly()
    {
        $invitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->testClub->id,
            'is_active' => true,
            'expires_at' => now()->addDays(30),
        ]);

        // Minimal required data only
        $playerData = [
            'first_name' => 'Min',
            'last_name' => 'Required',
            'email' => 'min.required@example.com',
            'phone' => '+49111222333',
            'birth_date' => '1998-03-10',
        ];

        $result = $this->registrationService->registerPlayer($invitation->invitation_token, $playerData);

        $this->assertTrue($result['success']);
        $this->assertNull($result['player']->height_cm);
        $this->assertNull($result['player']->weight_kg);
    }

    /** @test */
    public function it_prevents_duplicate_email_registration()
    {
        // Create existing user
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $invitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->testClub->id,
            'is_active' => true,
            'expires_at' => now()->addDays(30),
        ]);

        $playerData = [
            'first_name' => 'Duplicate',
            'last_name' => 'User',
            'email' => 'existing@example.com',  // Duplicate
            'phone' => '+49123456789',
            'birth_date' => '2000-01-01',
        ];

        $result = $this->registrationService->registerPlayer($invitation->invitation_token, $playerData);

        $this->assertFalse($result['success']);
        $this->assertNull($result['user']);
        $this->assertNull($result['player']);
        $this->assertEquals('A user with this email already exists', $result['error']);
    }

    /** @test */
    public function it_rolls_back_transaction_on_registration_error()
    {
        $invitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->testClub->id,
            'is_active' => true,
            'expires_at' => now()->addDays(30),
        ]);

        // Invalid data that should cause an error
        $playerData = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => '+49123456789',
            // Missing required birth_date
        ];

        $userCountBefore = User::count();
        $playerCountBefore = Player::count();

        $result = $this->registrationService->registerPlayer($invitation->invitation_token, $playerData);

        $this->assertFalse($result['success']);

        // Counts should remain unchanged (transaction rolled back)
        $this->assertEquals($userCountBefore, User::count());
        $this->assertEquals($playerCountBefore, Player::count());
    }

    /** @test */
    public function it_can_assign_player_to_team()
    {
        Notification::fake();

        // Create pending player
        $user = User::factory()->create(['account_status' => 'pending']);
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'pending_team_assignment' => true,
            'status' => 'pending_assignment',
        ]);

        $teamData = [
            'jersey_number' => 23,
            'primary_position' => 'SF',
        ];

        $result = $this->registrationService->assignPlayerToTeam(
            $player->id,
            $this->testTeam->id,
            $this->clubAdminUser->id,
            $teamData
        );

        $this->assertTrue($result['success']);
        $this->assertInstanceOf(Player::class, $result['player']);
        $this->assertNull($result['error']);

        // Check player status updated
        $player->refresh();
        $this->assertFalse($player->pending_team_assignment);
        $this->assertEquals('active', $player->status);

        // Check user account activated
        $user->refresh();
        $this->assertEquals('active', $user->account_status);

        // Check team membership created
        $this->assertDatabaseHas('player_team', [
            'player_id' => $player->id,
            'team_id' => $this->testTeam->id,
            'jersey_number' => 23,
            'primary_position' => 'SF',
            'is_active' => true,
        ]);

        // Check notification sent
        Notification::assertSentTo($user, PlayerAssignedNotification::class);
    }

    /** @test */
    public function it_prevents_assigning_non_pending_player()
    {
        $player = Player::factory()->create([
            'pending_team_assignment' => false,  // Not pending
            'status' => 'active',
        ]);

        $result = $this->registrationService->assignPlayerToTeam(
            $player->id,
            $this->testTeam->id,
            $this->clubAdminUser->id
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('Player is not pending team assignment', $result['error']);
    }

    /** @test */
    public function it_handles_invalid_player_id()
    {
        $result = $this->registrationService->assignPlayerToTeam(
            999999,  // Non-existent player
            $this->testTeam->id,
            $this->clubAdminUser->id
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('Player not found', $result['error']);
    }

    /** @test */
    public function it_handles_invalid_team_id()
    {
        $player = Player::factory()->create([
            'pending_team_assignment' => true,
        ]);

        $result = $this->registrationService->assignPlayerToTeam(
            $player->id,
            999999,  // Non-existent team
            $this->clubAdminUser->id
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('Team not found', $result['error']);
    }

    /** @test */
    public function it_can_deactivate_invitation()
    {
        $invitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->testClub->id,
            'is_active' => true,
        ]);

        $result = $this->registrationService->deactivateInvitation($invitation->id);

        $this->assertTrue($result);

        $invitation->refresh();
        $this->assertFalse($invitation->is_active);
        $this->assertNotNull($invitation->deactivated_at);
    }

    /** @test */
    public function it_handles_invalid_invitation_id_on_deactivate()
    {
        $result = $this->registrationService->deactivateInvitation(999999);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_get_invitation_statistics()
    {
        $invitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->testClub->id,
            'max_registrations' => 50,
            'registered_count' => 10,
        ]);

        // Create some registered players
        Player::factory()->count(5)->create([
            'registered_via_invitation_id' => $invitation->id,
            'pending_team_assignment' => true,
        ]);

        Player::factory()->count(5)->create([
            'registered_via_invitation_id' => $invitation->id,
            'pending_team_assignment' => false,
        ]);

        $stats = $this->registrationService->getInvitationStats($invitation->id);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('registered_count', $stats);
        $this->assertArrayHasKey('pending_assignment', $stats);
        $this->assertArrayHasKey('assigned_count', $stats);
        $this->assertArrayHasKey('remaining_slots', $stats);
    }

    /** @test */
    public function it_returns_empty_array_for_invalid_invitation_stats()
    {
        $stats = $this->registrationService->getInvitationStats(999999);

        $this->assertIsArray($stats);
        $this->assertEmpty($stats);
    }

    /** @test */
    public function it_can_get_pending_players_for_club()
    {
        $invitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $this->testClub->id,
        ]);

        // Create pending players for this club
        $pendingPlayers = Player::factory()->count(3)->create([
            'registered_via_invitation_id' => $invitation->id,
            'pending_team_assignment' => true,
        ]);

        // Create assigned player (should not be included)
        Player::factory()->create([
            'registered_via_invitation_id' => $invitation->id,
            'pending_team_assignment' => false,
        ]);

        // Create pending player for different club (should not be included)
        $otherClub = Club::factory()->create();
        $otherInvitation = PlayerRegistrationInvitation::factory()->create([
            'club_id' => $otherClub->id,
        ]);
        Player::factory()->create([
            'registered_via_invitation_id' => $otherInvitation->id,
            'pending_team_assignment' => true,
        ]);

        $result = $this->registrationService->getPendingPlayers($this->testClub->id);

        $this->assertCount(3, $result);
        $this->assertTrue($result->every(fn($p) => $p->pending_team_assignment === true));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
