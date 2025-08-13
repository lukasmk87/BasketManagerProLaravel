<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Club;
use App\Models\Team;
use App\Models\Player;
use App\Models\Game;
use App\Models\TrainingSession;
use App\Models\Tournament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MultiTenantSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected User $userA;
    protected User $userB;
    protected User $maliciousUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupSecurityTestData();
    }

    protected function setupSecurityTestData(): void
    {
        // Create two isolated tenants
        $this->tenantA = Tenant::factory()->create([
            'name' => 'Lakers Basketball Club',
            'slug' => 'lakers',
            'domain' => 'lakers.basketmanager-pro.com',
            'is_active' => true,
        ]);

        $this->tenantB = Tenant::factory()->create([
            'name' => 'Warriors Basketball Club',
            'slug' => 'warriors',
            'domain' => 'warriors.basketmanager-pro.com',
            'is_active' => true,
        ]);

        // Create users for each tenant
        $this->userA = User::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'email' => 'admin@lakers.test',
        ]);

        $this->userB = User::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'email' => 'admin@warriors.test',
        ]);

        // Create a malicious user trying to access other tenant's data
        $this->maliciousUser = User::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'email' => 'hacker@lakers.test',
        ]);

        // Create test data for each tenant
        $this->createTenantTestData();
    }

    protected function createTenantTestData(): void
    {
        // Tenant A data
        $clubA = Club::factory()->create(['tenant_id' => $this->tenantA->id]);
        $teamA = Team::factory()->create(['tenant_id' => $this->tenantA->id, 'club_id' => $clubA->id]);
        Player::factory()->create(['tenant_id' => $this->tenantA->id, 'team_id' => $teamA->id]);
        Game::factory()->create(['tenant_id' => $this->tenantA->id, 'home_team_id' => $teamA->id]);
        
        // Tenant B data
        $clubB = Club::factory()->create(['tenant_id' => $this->tenantB->id]);
        $teamB = Team::factory()->create(['tenant_id' => $this->tenantB->id, 'club_id' => $clubB->id]);
        Player::factory()->create(['tenant_id' => $this->tenantB->id, 'team_id' => $teamB->id]);
        Game::factory()->create(['tenant_id' => $this->tenantB->id, 'home_team_id' => $teamB->id]);
    }

    /** @test */
    public function row_level_security_prevents_cross_tenant_data_access()
    {
        // Enable Row Level Security policies
        Artisan::call('setup:rls');
        
        // Test direct database queries respect tenant isolation
        $tenantAClubs = DB::table('clubs')
            ->where('tenant_id', $this->tenantA->id)
            ->get();
        
        $tenantBClubs = DB::table('clubs')
            ->where('tenant_id', $this->tenantB->id)
            ->get();
        
        $this->assertCount(1, $tenantAClubs);
        $this->assertCount(1, $tenantBClubs);
        
        // Ensure RLS policies are enforced at database level
        DB::statement('SET basketmanager.current_tenant_id = ?', [$this->tenantA->id]);
        
        $accessibleClubs = DB::select('SELECT * FROM clubs');
        $this->assertCount(1, $accessibleClubs);
        $this->assertEquals($this->tenantA->id, $accessibleClubs[0]->tenant_id);
    }

    /** @test */
    public function direct_id_manipulation_attacks_are_prevented()
    {
        Sanctum::actingAs($this->userA);
        
        $teamB = Team::where('tenant_id', $this->tenantB->id)->first();
        
        // Try to access tenant B's team directly by ID manipulation
        $response = $this->withHeaders(['Host' => $this->tenantA->domain])
                         ->getJson("/api/v4/teams/{$teamB->id}");
        
        $response->assertNotFound();
    }

    /** @test */
    public function sql_injection_attempts_are_blocked()
    {
        Sanctum::actingAs($this->userA);
        
        // Test SQL injection in search parameter
        $maliciousInput = "'; DROP TABLE teams; --";
        
        $response = $this->withHeaders(['Host' => $this->tenantA->domain])
                         ->getJson("/api/v4/teams?search=" . urlencode($maliciousInput));
        
        $response->assertOk();
        
        // Verify teams table still exists and contains data
        $this->assertDatabaseHas('teams', ['tenant_id' => $this->tenantA->id]);
    }

    /** @test */
    public function mass_assignment_vulnerabilities_are_prevented()
    {
        Sanctum::actingAs($this->userA);
        
        // Try to set tenant_id directly to access other tenant's data
        $maliciousData = [
            'name' => 'Hacked Team',
            'tenant_id' => $this->tenantB->id, // Attempt to switch tenant
        ];
        
        $response = $this->withHeaders(['Host' => $this->tenantA->domain])
                         ->postJson('/api/v4/teams', $maliciousData);
        
        $response->assertStatus(201); // Should succeed but with correct tenant
        
        $createdTeam = Team::where('name', 'Hacked Team')->first();
        $this->assertEquals($this->tenantA->id, $createdTeam->tenant_id);
        $this->assertNotEquals($this->tenantB->id, $createdTeam->tenant_id);
    }

    /** @test */
    public function session_hijacking_with_wrong_tenant_domain_fails()
    {
        Sanctum::actingAs($this->userA);
        
        // User A tries to access tenant B's domain with their valid session
        $response = $this->withHeaders(['Host' => $this->tenantB->domain])
                         ->getJson('/api/v4/teams');
        
        $response->assertStatus(403); // Should be forbidden
    }

    /** @test */
    public function api_token_cross_tenant_usage_is_blocked()
    {
        $tokenA = $this->userA->createToken('test-token')->plainTextToken;
        
        // Try to use tenant A's token to access tenant B's data
        $response = $this->withHeaders([
            'Host' => $this->tenantB->domain,
            'Authorization' => 'Bearer ' . $tokenA
        ])->getJson('/api/v4/teams');
        
        $response->assertUnauthorized();
    }

    /** @test */
    public function tenant_specific_file_access_is_enforced()
    {
        Sanctum::actingAs($this->userA);
        
        // Mock file paths that might contain tenant info
        $tenantAFile = "tenant_{$this->tenantA->id}/documents/private.pdf";
        $tenantBFile = "tenant_{$this->tenantB->id}/documents/private.pdf";
        
        // User A should not be able to access tenant B's files via path manipulation
        $response = $this->withHeaders(['Host' => $this->tenantA->domain])
                         ->getJson("/api/v4/files/" . urlencode($tenantBFile));
        
        $response->assertNotFound();
    }

    /** @test */
    public function database_transactions_maintain_tenant_isolation()
    {
        app()->instance('tenant', $this->tenantA);
        
        try {
            DB::transaction(function () {
                // Create team for tenant A
                $team = Team::create([
                    'name' => 'Test Team',
                    'club_id' => Club::where('tenant_id', $this->tenantA->id)->first()->id,
                    'season' => '2024-25',
                ]);
                
                // Verify tenant_id is automatically set
                $this->assertEquals($this->tenantA->id, $team->tenant_id);
                
                // Try to create player for different tenant (should fail)
                $player = Player::create([
                    'user_id' => $this->userB->id, // User from tenant B
                    'team_id' => $team->id,
                    'jersey_number' => 23,
                ]);
                
                // Player should still belong to tenant A due to team relationship
                $this->assertEquals($this->tenantA->id, $player->tenant_id);
            });
        } catch (\Exception $e) {
            // Transaction should complete without errors
            $this->fail('Transaction failed with tenant isolation');
        }
    }

    /** @test */
    public function sensitive_routes_require_proper_tenant_context()
    {
        Sanctum::actingAs($this->userA);
        
        // Routes that should be protected
        $sensitiveRoutes = [
            '/api/v4/statistics/financial',
            '/api/v4/admin/tenant-settings',
            '/api/v4/billing/invoices',
            '/api/v4/exports/all-data',
        ];
        
        foreach ($sensitiveRoutes as $route) {
            // Without proper tenant context
            $response = $this->getJson($route);
            $response->assertStatus(404); // Should not be accessible without tenant
            
            // With correct tenant context
            $response = $this->withHeaders(['Host' => $this->tenantA->domain])
                             ->getJson($route);
            // Should either work or give proper authorization error (not 404)
            $this->assertNotEquals(404, $response->getStatusCode());
        }
    }

    /** @test */
    public function tenant_scoped_eloquent_queries_are_automatic()
    {
        app()->instance('tenant', $this->tenantA);
        
        // All Eloquent queries should be automatically scoped to tenant
        $teams = Team::all();
        $players = Player::all();
        $games = Game::all();
        
        // Verify all returned data belongs to current tenant
        foreach ($teams as $team) {
            $this->assertEquals($this->tenantA->id, $team->tenant_id);
        }
        
        foreach ($players as $player) {
            $this->assertEquals($this->tenantA->id, $player->tenant_id);
        }
        
        foreach ($games as $game) {
            $this->assertEquals($this->tenantA->id, $game->tenant_id);
        }
    }

    /** @test */
    public function cache_poisoning_attacks_are_prevented()
    {
        Sanctum::actingAs($this->userA);
        
        // Make request that would be cached
        $response = $this->withHeaders(['Host' => $this->tenantA->domain])
                         ->getJson('/api/v4/teams');
        $response->assertOk();
        
        // Switch to tenant B and make similar request
        Sanctum::actingAs($this->userB);
        $response = $this->withHeaders(['Host' => $this->tenantB->domain])
                         ->getJson('/api/v4/teams');
        $response->assertOk();
        
        // Verify responses contain different data (cache is properly isolated)
        $teamsA = $this->withHeaders(['Host' => $this->tenantA->domain])
                       ->actingAs($this->userA)
                       ->getJson('/api/v4/teams')
                       ->json('data');
        
        $teamsB = $this->withHeaders(['Host' => $this->tenantB->domain])
                       ->actingAs($this->userB)
                       ->getJson('/api/v4/teams')
                       ->json('data');
        
        $teamAIds = collect($teamsA)->pluck('id')->toArray();
        $teamBIds = collect($teamsB)->pluck('id')->toArray();
        
        $this->assertEmpty(array_intersect($teamAIds, $teamBIds));
    }

    /** @test */
    public function file_upload_directory_traversal_is_blocked()
    {
        Sanctum::actingAs($this->userA);
        
        // Attempt directory traversal in file upload
        $maliciousFilename = "../../../tenant_{$this->tenantB->id}/secret.txt";
        
        $response = $this->withHeaders(['Host' => $this->tenantA->domain])
                         ->postJson('/api/v4/files/upload', [
                             'filename' => $maliciousFilename,
                             'content' => 'malicious content',
                         ]);
        
        // Should either reject the filename or sanitize it
        if ($response->isSuccessful()) {
            $uploadedPath = $response->json('path');
            $this->assertStringContains("tenant_{$this->tenantA->id}", $uploadedPath);
            $this->assertStringNotContains("tenant_{$this->tenantB->id}", $uploadedPath);
        }
    }

    /** @test */
    public function tenant_database_connection_switching_is_secure()
    {
        // Test that tenant context switching doesn't allow unauthorized access
        app()->instance('tenant', $this->tenantA);
        
        $teamsA = Team::count();
        
        // Malicious attempt to switch tenant context mid-request
        app()->instance('tenant', $this->tenantB);
        
        $teamsAfterSwitch = Team::count();
        
        // The global scope should prevent seeing other tenant's data
        $this->assertNotEquals($teamsA, $teamsAfterSwitch);
    }

    /** @test */
    public function webhook_signature_verification_prevents_spoofing()
    {
        // Test webhook endpoint security
        $maliciousPayload = [
            'type' => 'invoice.payment_succeeded',
            'data' => [
                'object' => [
                    'customer' => 'cus_fake',
                    'metadata' => [
                        'tenant_id' => $this->tenantB->id, // Try to affect wrong tenant
                    ],
                ],
            ],
        ];
        
        // Without proper signature
        $response = $this->postJson('/webhooks/stripe', $maliciousPayload);
        $response->assertStatus(400); // Should reject invalid signature
        
        // With invalid signature
        $response = $this->postJson('/webhooks/stripe', $maliciousPayload, [
            'Stripe-Signature' => 'invalid_signature',
        ]);
        $response->assertStatus(400); // Should reject invalid signature
    }

    /** @test */
    public function tenant_user_role_escalation_is_prevented()
    {
        Sanctum::actingAs($this->maliciousUser);
        
        // Try to escalate to admin role
        $response = $this->withHeaders(['Host' => $this->tenantA->domain])
                         ->putJson("/api/v4/users/{$this->maliciousUser->id}", [
                             'roles' => ['admin'],
                         ]);
        
        // Should either fail or require proper authorization
        if ($response->isSuccessful()) {
            $this->maliciousUser->refresh();
            $this->assertFalse($this->maliciousUser->hasRole('admin'));
        } else {
            $response->assertStatus(403);
        }
    }

    /** @test */
    public function concurrent_tenant_requests_maintain_isolation()
    {
        // Simulate concurrent requests from different tenants
        $requests = [];
        
        // Tenant A request
        $requests[] = function () {
            return $this->withHeaders(['Host' => $this->tenantA->domain])
                        ->actingAs($this->userA)
                        ->getJson('/api/v4/teams');
        };
        
        // Tenant B request
        $requests[] = function () {
            return $this->withHeaders(['Host' => $this->tenantB->domain])
                        ->actingAs($this->userB)
                        ->getJson('/api/v4/teams');
        };
        
        // Execute requests
        $responses = [];
        foreach ($requests as $request) {
            $responses[] = $request();
        }
        
        // Verify both requests succeeded with proper isolation
        foreach ($responses as $response) {
            $response->assertOk();
        }
        
        // Verify data isolation
        $dataA = $responses[0]->json('data');
        $dataB = $responses[1]->json('data');
        
        $idsA = collect($dataA)->pluck('id')->toArray();
        $idsB = collect($dataB)->pluck('id')->toArray();
        
        $this->assertEmpty(array_intersect($idsA, $idsB));
    }
}