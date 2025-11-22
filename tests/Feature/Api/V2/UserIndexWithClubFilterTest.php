<?php

namespace Tests\Feature\Api\V2;

use App\Models\Club;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserIndexWithClubFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $clubAdmin;
    private Club $club;
    private Club $otherClub;
    private User $playerInClub;
    private User $playerInOtherClub;
    private User $playerInBothClubs;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $superAdminRole = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $clubAdminRole = Role::create(['name' => 'club_admin', 'guard_name' => 'web']);
        $playerRole = Role::create(['name' => 'player', 'guard_name' => 'web']);
        $trainerRole = Role::create(['name' => 'trainer', 'guard_name' => 'web']);

        // Create permissions
        $viewUsersPermission = Permission::create(['name' => 'view users', 'guard_name' => 'web']);

        // Assign permissions to roles
        $superAdminRole->syncPermissions([$viewUsersPermission]);
        $clubAdminRole->syncPermissions([$viewUsersPermission]);

        // Create users with different roles
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');

        $this->clubAdmin = User::factory()->create();
        $this->clubAdmin->assignRole('club_admin');

        // Create clubs
        $this->club = Club::factory()->create(['name' => 'Test Club']);
        $this->otherClub = Club::factory()->create(['name' => 'Other Club']);

        // Attach clubAdmin to club
        $this->clubAdmin->clubs()->attach($this->club->id, ['role' => 'admin']);

        // Create players
        $this->playerInClub = User::factory()->create(['name' => 'Player in Test Club']);
        $this->playerInClub->assignRole('player');
        $this->playerInClub->clubs()->attach($this->club->id, ['role' => 'player']);

        $this->playerInOtherClub = User::factory()->create(['name' => 'Player in Other Club']);
        $this->playerInOtherClub->assignRole('player');
        $this->playerInOtherClub->clubs()->attach($this->otherClub->id, ['role' => 'player']);

        $this->playerInBothClubs = User::factory()->create(['name' => 'Player in Both Clubs']);
        $this->playerInBothClubs->assignRole('player');
        $this->playerInBothClubs->clubs()->attach($this->club->id, ['role' => 'player']);
        $this->playerInBothClubs->clubs()->attach($this->otherClub->id, ['role' => 'player']);
    }

    /** @test */
    public function can_filter_users_by_club_id()
    {
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson("/api/v2/users?club_id={$this->club->id}");

        $response->assertStatus(200);

        $userIds = collect($response->json('data'))->pluck('id')->toArray();

        // Should include users in the club
        $this->assertContains($this->playerInClub->id, $userIds);
        $this->assertContains($this->playerInBothClubs->id, $userIds);

        // Should NOT include users not in the club
        $this->assertNotContains($this->playerInOtherClub->id, $userIds);
    }

    /** @test */
    public function can_filter_users_by_other_club_id()
    {
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson("/api/v2/users?club_id={$this->otherClub->id}");

        $response->assertStatus(200);

        $userIds = collect($response->json('data'))->pluck('id')->toArray();

        // Should include users in the other club
        $this->assertContains($this->playerInOtherClub->id, $userIds);
        $this->assertContains($this->playerInBothClubs->id, $userIds);

        // Should NOT include users not in the other club
        $this->assertNotContains($this->playerInClub->id, $userIds);
    }

    /** @test */
    public function without_club_filter_returns_all_users()
    {
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v2/users');

        $response->assertStatus(200);

        $userIds = collect($response->json('data'))->pluck('id')->toArray();

        // Should include all users
        $this->assertContains($this->playerInClub->id, $userIds);
        $this->assertContains($this->playerInOtherClub->id, $userIds);
        $this->assertContains($this->playerInBothClubs->id, $userIds);
    }

    /** @test */
    public function club_filter_can_be_combined_with_other_filters()
    {
        // Add another player with different role
        $trainer = User::factory()->create(['name' => 'Trainer in Club']);
        $trainer->assignRole('trainer');
        $trainer->clubs()->attach($this->club->id, ['role' => 'trainer']);

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson("/api/v2/users?club_id={$this->club->id}&role=player");

        $response->assertStatus(200);

        $userIds = collect($response->json('data'))->pluck('id')->toArray();

        // Should include only players in the club
        $this->assertContains($this->playerInClub->id, $userIds);
        $this->assertContains($this->playerInBothClubs->id, $userIds);

        // Should NOT include trainer (different role) or players from other clubs
        $this->assertNotContains($trainer->id, $userIds);
        $this->assertNotContains($this->playerInOtherClub->id, $userIds);
    }

    /** @test */
    public function club_filter_with_search()
    {
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson("/api/v2/users?club_id={$this->club->id}&search=Both");

        $response->assertStatus(200);

        $userIds = collect($response->json('data'))->pluck('id')->toArray();

        // Should only include the player matching search in the club
        $this->assertContains($this->playerInBothClubs->id, $userIds);
        $this->assertNotContains($this->playerInClub->id, $userIds);
        $this->assertNotContains($this->playerInOtherClub->id, $userIds);
    }

    /** @test */
    public function invalid_club_id_returns_empty_result()
    {
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v2/users?club_id=99999');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEmpty($data, 'Should return empty array for non-existent club');
    }

    /** @test */
    public function club_filter_validation()
    {
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/api/v2/users?club_id=invalid');

        $response->assertStatus(422); // Validation error

        $response->assertJsonValidationErrors(['club_id']);
    }

    /** @test */
    public function users_include_clubs_in_response()
    {
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson("/api/v2/users?club_id={$this->club->id}");

        $response->assertStatus(200);

        // Check that the first user has clubs in the response
        $firstUser = $response->json('data.0');
        $this->assertArrayHasKey('clubs', $firstUser, 'User response should include clubs relationship');
    }
}
