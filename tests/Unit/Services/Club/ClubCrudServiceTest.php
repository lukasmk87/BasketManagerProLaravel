<?php

namespace Tests\Unit\Services\Club;

use App\Models\Club;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Club\ClubCrudService;
use App\Services\Club\ClubMembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubCrudServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClubCrudService $service;
    private ClubMembershipService $membershipService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->membershipService = new ClubMembershipService();
        $this->service = new ClubCrudService($this->membershipService);
    }

    public function test_creates_club_with_required_fields(): void
    {
        $tenant = Tenant::factory()->create();

        $data = [
            'name' => 'Test Basketball Club',
            'tenant_id' => $tenant->id,
        ];

        $club = $this->service->createClub($data);

        $this->assertInstanceOf(Club::class, $club);
        $this->assertEquals('Test Basketball Club', $club->name);
        $this->assertEquals($tenant->id, $club->tenant_id);
        $this->assertNotNull($club->slug);
    }

    public function test_generates_unique_slug(): void
    {
        $tenant = Tenant::factory()->create();

        $club1 = $this->service->createClub([
            'name' => 'Test Club',
            'tenant_id' => $tenant->id,
        ]);

        $club2 = $this->service->createClub([
            'name' => 'Test Club',
            'tenant_id' => $tenant->id,
        ]);

        $this->assertNotEquals($club1->slug, $club2->slug);
        $this->assertEquals('test-club', $club1->slug);
        $this->assertEquals('test-club-1', $club2->slug);
    }

    public function test_creates_club_with_all_fields(): void
    {
        $tenant = Tenant::factory()->create();

        $data = [
            'name' => 'Full Basketball Club',
            'tenant_id' => $tenant->id,
            'short_name' => 'FBC',
            'description' => 'A great basketball club',
            'website' => 'https://fbc.de',
            'email' => 'info@fbc.de',
            'phone' => '+49 123 456789',
            'address_street' => 'Main Street 1',
            'address_city' => 'Berlin',
            'address_zip' => '10115',
            'address_country' => 'DE',
            'primary_color' => '#FF0000',
            'secondary_color' => '#0000FF',
            'has_indoor_courts' => true,
            'court_count' => 3,
        ];

        $club = $this->service->createClub($data);

        $this->assertEquals('Full Basketball Club', $club->name);
        $this->assertEquals('FBC', $club->short_name);
        $this->assertEquals('A great basketball club', $club->description);
        $this->assertEquals('https://fbc.de', $club->website);
        $this->assertEquals('#FF0000', $club->primary_color);
        $this->assertTrue($club->has_indoor_courts);
        $this->assertEquals(3, $club->court_count);
    }

    public function test_updates_club(): void
    {
        $club = Club::factory()->create([
            'name' => 'Original Name',
        ]);

        $updatedClub = $this->service->updateClub($club, [
            'name' => 'Updated Name',
            'description' => 'New description',
        ]);

        $this->assertEquals('Updated Name', $updatedClub->name);
        $this->assertEquals('New description', $updatedClub->description);
    }

    public function test_updates_slug_when_name_changes(): void
    {
        $club = Club::factory()->create([
            'name' => 'Original Club',
            'slug' => 'original-club',
        ]);

        $updatedClub = $this->service->updateClub($club, [
            'name' => 'New Club Name',
        ]);

        $this->assertEquals('new-club-name', $updatedClub->slug);
    }

    public function test_deletes_club_without_active_teams(): void
    {
        $club = Club::factory()->create();

        $result = $this->service->deleteClub($club);

        $this->assertTrue($result);
        $this->assertSoftDeleted('clubs', ['id' => $club->id]);
    }

    public function test_throws_exception_when_deleting_club_with_active_teams(): void
    {
        $club = Club::factory()->create();

        // Create an active team
        \App\Models\Team::factory()->create([
            'club_id' => $club->id,
            'is_active' => true,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Club kann nicht gelöscht werden, da noch aktive Teams vorhanden sind.');

        $this->service->deleteClub($club);
    }

    public function test_throws_exception_without_tenant_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->createClub([
            'name' => 'Test Club',
        ]);
    }

    public function test_adds_current_user_as_admin(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user);

        $club = $this->service->createClub([
            'name' => 'Test Club',
            'tenant_id' => $tenant->id,
            'add_current_user_as_admin' => true,
        ]);

        $this->assertDatabaseHas('club_user', [
            'club_id' => $club->id,
            'user_id' => $user->id,
            'role' => 'admin',
        ]);
    }
}
