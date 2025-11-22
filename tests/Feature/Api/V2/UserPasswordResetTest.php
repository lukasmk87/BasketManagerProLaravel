<?php

namespace Tests\Feature\Api\V2;

use App\Models\Club;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $admin;
    private User $clubAdmin;
    private User $otherClubAdmin;
    private User $player;
    private User $trainer;
    private Club $club;
    private Club $otherClub;

    protected function setUp(): void
    {
        parent::setUp();

        // Fake notifications to prevent actual emails from being sent
        Notification::fake();

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

        // Attach users to clubs
        $this->clubAdmin->clubs()->attach($this->club->id, ['role' => 'admin']);
        $this->otherClubAdmin->clubs()->attach($this->otherClub->id, ['role' => 'admin']);
        $this->player->clubs()->attach($this->club->id, ['role' => 'player']);
        $this->trainer->clubs()->attach($this->club->id, ['role' => 'trainer']);
    }

    /** @test */
    public function super_admin_can_send_password_reset_to_any_user()
    {
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v2/users/{$this->player->id}/send-password-reset");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Passwort-Reset-Link wurde erfolgreich an ' . $this->player->email . ' gesendet.',
                'email' => $this->player->email,
            ]);

        // Verify notification was sent
        Notification::assertSentTo($this->player, ResetPassword::class);
    }

    /** @test */
    public function admin_can_send_password_reset_to_any_user()
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v2/users/{$this->player->id}/send-password-reset");

        $response->assertStatus(200);

        Notification::assertSentTo($this->player, ResetPassword::class);
    }

    /** @test */
    public function club_admin_can_send_password_reset_to_users_in_their_club()
    {
        $response = $this->actingAs($this->clubAdmin, 'sanctum')
            ->postJson("/api/v2/users/{$this->player->id}/send-password-reset");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Passwort-Reset-Link wurde erfolgreich an ' . $this->player->email . ' gesendet.',
            ]);

        Notification::assertSentTo($this->player, ResetPassword::class);
    }

    /** @test */
    public function club_admin_cannot_send_password_reset_to_users_in_different_club()
    {
        $otherPlayer = User::factory()->create();
        $otherPlayer->assignRole('player');
        $otherPlayer->clubs()->attach($this->otherClub->id, ['role' => 'player']);

        $response = $this->actingAs($this->clubAdmin, 'sanctum')
            ->postJson("/api/v2/users/{$otherPlayer->id}/send-password-reset");

        $response->assertStatus(403); // Forbidden

        Notification::assertNotSentTo($otherPlayer, ResetPassword::class);
    }

    /** @test */
    public function club_admin_cannot_send_password_reset_to_super_admins()
    {
        $response = $this->actingAs($this->clubAdmin, 'sanctum')
            ->postJson("/api/v2/users/{$this->superAdmin->id}/send-password-reset");

        $response->assertStatus(403);

        Notification::assertNotSentTo($this->superAdmin, ResetPassword::class);
    }

    /** @test */
    public function club_admin_cannot_send_password_reset_to_admins()
    {
        $response = $this->actingAs($this->clubAdmin, 'sanctum')
            ->postJson("/api/v2/users/{$this->admin->id}/send-password-reset");

        $response->assertStatus(403);

        Notification::assertNotSentTo($this->admin, ResetPassword::class);
    }

    /** @test */
    public function club_admin_cannot_send_password_reset_to_other_club_admins()
    {
        $response = $this->actingAs($this->clubAdmin, 'sanctum')
            ->postJson("/api/v2/users/{$this->otherClubAdmin->id}/send-password-reset");

        $response->assertStatus(403);

        Notification::assertNotSentTo($this->otherClubAdmin, ResetPassword::class);
    }

    /** @test */
    public function club_admin_can_send_password_reset_with_club_id_parameter()
    {
        $response = $this->actingAs($this->clubAdmin, 'sanctum')
            ->postJson("/api/v2/users/{$this->player->id}/send-password-reset", [
                'club_id' => $this->club->id,
            ]);

        $response->assertStatus(200);

        Notification::assertSentTo($this->player, ResetPassword::class);
    }

    /** @test */
    public function club_admin_cannot_send_password_reset_with_wrong_club_id()
    {
        // Player is in the clubAdmin's club, but request has wrong club_id
        $response = $this->actingAs($this->clubAdmin, 'sanctum')
            ->postJson("/api/v2/users/{$this->player->id}/send-password-reset", [
                'club_id' => $this->otherClub->id,
            ]);

        $response->assertStatus(403);

        Notification::assertNotSentTo($this->player, ResetPassword::class);
    }

    /** @test */
    public function unauthenticated_user_cannot_send_password_reset()
    {
        $response = $this->postJson("/api/v2/users/{$this->player->id}/send-password-reset");

        $response->assertStatus(401); // Unauthenticated

        Notification::assertNotSentTo($this->player, ResetPassword::class);
    }

    /** @test */
    public function regular_user_cannot_send_password_reset_to_others()
    {
        $otherPlayer = User::factory()->create();
        $otherPlayer->assignRole('player');
        $otherPlayer->clubs()->attach($this->club->id, ['role' => 'player']);

        $response = $this->actingAs($this->player, 'sanctum')
            ->postJson("/api/v2/users/{$otherPlayer->id}/send-password-reset");

        $response->assertStatus(403);

        Notification::assertNotSentTo($otherPlayer, ResetPassword::class);
    }

    /** @test */
    public function trainer_cannot_send_password_reset()
    {
        $response = $this->actingAs($this->trainer, 'sanctum')
            ->postJson("/api/v2/users/{$this->player->id}/send-password-reset");

        $response->assertStatus(403);

        Notification::assertNotSentTo($this->player, ResetPassword::class);
    }

    /** @test */
    public function endpoint_returns_error_on_exception()
    {
        // This test ensures error handling works
        // We'll use a non-existent user ID to trigger an error path

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson("/api/v2/users/99999/send-password-reset");

        $response->assertStatus(404); // Not Found (Laravel's default for missing model)
    }
}
