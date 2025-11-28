<?php

namespace Tests\Unit\Services\Club;

use App\Models\Club;
use App\Models\User;
use App\Services\Club\ClubMembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubMembershipServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClubMembershipService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ClubMembershipService();
    }

    public function test_adds_new_member_to_club(): void
    {
        $club = Club::factory()->create();
        $user = User::factory()->create();

        $this->service->addMemberToClub($club, $user, 'member');

        $this->assertDatabaseHas('club_user', [
            'club_id' => $club->id,
            'user_id' => $user->id,
            'role' => 'member',
            'is_active' => true,
        ]);
    }

    public function test_updates_existing_membership_role(): void
    {
        $club = Club::factory()->create();
        $user = User::factory()->create();

        // First add as member
        $this->service->addMemberToClub($club, $user, 'member');

        // Then upgrade to admin
        $this->service->addMemberToClub($club, $user, 'admin');

        $this->assertDatabaseHas('club_user', [
            'club_id' => $club->id,
            'user_id' => $user->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Should only have one membership record
        $this->assertEquals(1, $club->users()->where('user_id', $user->id)->count());
    }

    public function test_removes_member_from_club(): void
    {
        $club = Club::factory()->create();
        $user = User::factory()->create();

        // Add member first
        $this->service->addMemberToClub($club, $user, 'member');

        // Then remove
        $this->service->removeMemberFromClub($club, $user);

        $this->assertDatabaseMissing('club_user', [
            'club_id' => $club->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_throws_exception_when_removing_non_member(): void
    {
        $club = Club::factory()->create();
        $user = User::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Benutzer ist kein Mitglied dieses Clubs.');

        $this->service->removeMemberFromClub($club, $user);
    }

    public function test_adds_admin_role_to_club(): void
    {
        $club = Club::factory()->create();
        $user = User::factory()->create();

        $this->service->addMemberToClub($club, $user, 'admin');

        $membership = $club->users()->where('user_id', $user->id)->first();

        $this->assertNotNull($membership);
        $this->assertEquals('admin', $membership->pivot->role);
        $this->assertTrue((bool) $membership->pivot->is_active);
        $this->assertNotNull($membership->pivot->joined_at);
    }
}
