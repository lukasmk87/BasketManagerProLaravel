<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Club;
use App\Models\Team;
use App\Models\Player;
use App\Models\Game;
use App\Services\FeatureGateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MultiTenantApiTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected User $userA;
    protected User $userB;
    protected Club $clubA;
    protected Club $clubB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupMultiTenantTestData();
    }

    protected function setupMultiTenantTestData(): void
    {
        // Create two separate tenants
        $this->tenantA = Tenant::factory()->create([
            'name' => 'Lakers Basketball Club',
            'slug' => 'lakers',
            'domain' => 'lakers.basketmanager-pro.com',
            'subscription_tier' => 'professional',
            'is_active' => true,
        ]);

        $this->tenantB = Tenant::factory()->create([
            'name' => 'Warriors Basketball Club', 
            'slug' => 'warriors',
            'domain' => 'warriors.basketmanager-pro.com',
            'subscription_tier' => 'basic',
            'is_active' => true,
        ]);

        // Create users for each tenant
        $this->userA = User::factory()->create([
            'name' => 'Lakers Admin',
            'email' => 'admin@lakers.test',
            'tenant_id' => $this->tenantA->id,
        ]);

        $this->userB = User::factory()->create([
            'name' => 'Warriors Admin',
            'email' => 'admin@warriors.test', 
            'tenant_id' => $this->tenantB->id,
        ]);

        // Create clubs for each tenant
        $this->clubA = Club::factory()->create([
            'name' => 'Lakers Basketball Club',
            'tenant_id' => $this->tenantA->id,
        ]);

        $this->clubB = Club::factory()->create([
            'name' => 'Warriors Basketball Club',
            'tenant_id' => $this->tenantB->id,
        ]);
    }

    /** @test */
    public function tenant_can_only_access_own_data_via_api()
    {
        Sanctum::actingAs($this->userA);
        
        // Simulate tenant A accessing via domain
        $this->withHeaders(['Host' => $this->tenantA->domain])
             ->getJson('/api/v4/clubs')
             ->assertOk()
             ->assertJsonCount(1, 'data')
             ->assertJsonFragment(['name' => $this->clubA->name]);

        // Tenant A should not see tenant B's data
        $response = $this->withHeaders(['Host' => $this->tenantA->domain])
                         ->getJson('/api/v4/clubs');
        
        $responseData = $response->json('data');
        $this->assertNotContains($this->clubB->name, collect($responseData)->pluck('name'));
    }

    /** @test */
    public function tenant_isolation_prevents_cross_tenant_data_access()
    {
        $teamA = Team::factory()->create([
            'club_id' => $this->clubA->id,
            'tenant_id' => $this->tenantA->id,
            'name' => 'Lakers Team',
        ]);

        $teamB = Team::factory()->create([
            'club_id' => $this->clubB->id, 
            'tenant_id' => $this->tenantB->id,
            'name' => 'Warriors Team',
        ]);

        // User A should only see tenant A teams
        Sanctum::actingAs($this->userA);
        $response = $this->withHeaders(['Host' => $this->tenantA->domain])
                         ->getJson('/api/v4/teams');

        $response->assertOk();
        $teamNames = collect($response->json('data'))->pluck('name')->toArray();
        
        $this->assertContains('Lakers Team', $teamNames);
        $this->assertNotContains('Warriors Team', $teamNames);

        // User B should only see tenant B teams  
        Sanctum::actingAs($this->userB);
        $response = $this->withHeaders(['Host' => $this->tenantB->domain])
                         ->getJson('/api/v4/teams');

        $response->assertOk();
        $teamNames = collect($response->json('data'))->pluck('name')->toArray();
        
        $this->assertContains('Warriors Team', $teamNames);
        $this->assertNotContains('Lakers Team', $teamNames);
    }

    /** @test */
    public function feature_gates_enforce_subscription_tier_limits()
    {
        // Professional tier should have video analysis
        Sanctum::actingAs($this->userA);
        $this->withHeaders(['Host' => $this->tenantA->domain])
             ->getJson('/api/v4/features/video-analysis')
             ->assertOk()
             ->assertJson(['available' => true]);

        // Basic tier should not have video analysis  
        Sanctum::actingAs($this->userB);
        $this->withHeaders(['Host' => $this->tenantB->domain])
             ->getJson('/api/v4/features/video-analysis') 
             ->assertStatus(403)
             ->assertJson(['message' => 'Feature not available in your subscription tier']);
    }

    /** @test */
    public function api_rate_limits_respect_tenant_tiers()
    {
        // Professional tier gets 5000 requests per hour
        // Basic tier gets 1000 requests per hour
        
        $featureGate = app(FeatureGateService::class);
        $featureGate->setTenant($this->tenantA);
        
        // Professional tier limits
        $this->assertTrue($featureGate->canUse('api_calls_per_hour', 4999));
        $this->assertFalse($featureGate->canUse('api_calls_per_hour', 5001));

        $featureGate->setTenant($this->tenantB);
        
        // Basic tier limits  
        $this->assertTrue($featureGate->canUse('api_calls_per_hour', 999));
        $this->assertFalse($featureGate->canUse('api_calls_per_hour', 1001));
    }

    /** @test */
    public function tenant_specific_players_are_isolated()
    {
        $playerA = Player::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'user_id' => $this->userA->id,
        ]);

        $playerB = Player::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'user_id' => $this->userB->id,
        ]);

        // Tenant A user accessing players
        Sanctum::actingAs($this->userA);
        $response = $this->withHeaders(['Host' => $this->tenantA->domain])
                         ->getJson('/api/v4/players');

        $response->assertOk();
        $playerIds = collect($response->json('data'))->pluck('id')->toArray();
        
        $this->assertContains($playerA->id, $playerIds);
        $this->assertNotContains($playerB->id, $playerIds);
    }

    /** @test */
    public function tenant_cannot_access_other_tenant_player_details()
    {
        $playerB = Player::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'user_id' => $this->userB->id,
        ]);

        // Tenant A user trying to access tenant B player
        Sanctum::actingAs($this->userA);
        $this->withHeaders(['Host' => $this->tenantA->domain])
             ->getJson("/api/v4/players/{$playerB->id}")
             ->assertNotFound();
    }

    /** @test */
    public function tenant_games_are_properly_isolated()
    {
        $teamA = Team::factory()->create([
            'club_id' => $this->clubA->id,
            'tenant_id' => $this->tenantA->id,
        ]);

        $teamB = Team::factory()->create([
            'club_id' => $this->clubB->id,
            'tenant_id' => $this->tenantB->id,
        ]);

        $gameA = Game::factory()->create([
            'home_team_id' => $teamA->id,
            'away_team_id' => $teamA->id,
            'tenant_id' => $this->tenantA->id,
        ]);

        $gameB = Game::factory()->create([
            'home_team_id' => $teamB->id,
            'away_team_id' => $teamB->id,
            'tenant_id' => $this->tenantB->id,
        ]);

        // Tenant A should only see their games
        Sanctum::actingAs($this->userA);
        $response = $this->withHeaders(['Host' => $this->tenantA->domain])
                         ->getJson('/api/v4/games');

        $response->assertOk();
        $gameIds = collect($response->json('data'))->pluck('id')->toArray();
        
        $this->assertContains($gameA->id, $gameIds);
        $this->assertNotContains($gameB->id, $gameIds);
    }

    /** @test */
    public function api_versioning_works_across_tenants()
    {
        Sanctum::actingAs($this->userA);
        
        // Test API v4 access
        $this->withHeaders([
            'Host' => $this->tenantA->domain,
            'Accept-Version' => '4.0'
        ])->getJson('/api/v4/clubs')
          ->assertOk()
          ->assertJsonStructure([
              'data' => ['*' => ['id', 'name', 'created_at']],
              'meta' => ['current_page', 'per_page']
          ]);

        // Test API v2 access for backward compatibility
        $this->withHeaders([
            'Host' => $this->tenantA->domain,
            'Accept-Version' => '2.0'
        ])->getJson('/api/v2/clubs')
          ->assertOk();
    }

    /** @test */
    public function tenant_statistics_are_isolated()
    {
        $teamA = Team::factory()->create([
            'club_id' => $this->clubA->id,
            'tenant_id' => $this->tenantA->id,
        ]);

        $playerA = Player::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'team_id' => $teamA->id,
        ]);

        // Access tenant-specific statistics
        Sanctum::actingAs($this->userA);
        $this->withHeaders(['Host' => $this->tenantA->domain])
             ->getJson('/api/v4/statistics/teams')
             ->assertOk()
             ->assertJsonStructure([
                 'data' => ['*' => ['team_id', 'games_played', 'wins', 'losses']]
             ]);

        // Verify no cross-tenant data leakage in statistics
        $response = $this->withHeaders(['Host' => $this->tenantA->domain])
                         ->getJson('/api/v4/statistics/teams');
        
        $teamIds = collect($response->json('data'))->pluck('team_id')->toArray();
        $this->assertContains($teamA->id, $teamIds);
    }

    /** @test */
    public function tenant_specific_api_tokens_work_correctly()
    {
        // Create API token for tenant A user
        $tokenA = $this->userA->createToken('test-token', ['read-teams', 'read-players']);
        
        // Use token to access tenant A data
        $this->withHeaders([
            'Host' => $this->tenantA->domain,
            'Authorization' => 'Bearer ' . $tokenA->plainTextToken
        ])->getJson('/api/v4/teams')
          ->assertOk();

        // Token should not work for tenant B domain
        $this->withHeaders([
            'Host' => $this->tenantB->domain,
            'Authorization' => 'Bearer ' . $tokenA->plainTextToken
        ])->getJson('/api/v4/teams')
          ->assertUnauthorized();
    }

    /** @test */
    public function tenant_context_is_maintained_throughout_request()
    {
        Sanctum::actingAs($this->userA);
        
        // Make request to nested resource that should maintain tenant context
        $teamA = Team::factory()->create([
            'club_id' => $this->clubA->id,
            'tenant_id' => $this->tenantA->id,
        ]);

        $playerA = Player::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'team_id' => $teamA->id,
        ]);

        // Access player through team endpoint to ensure tenant context
        $this->withHeaders(['Host' => $this->tenantA->domain])
             ->getJson("/api/v4/teams/{$teamA->id}/players")
             ->assertOk()
             ->assertJsonCount(1, 'data')
             ->assertJsonFragment(['id' => $playerA->id]);
    }

    /** @test */
    public function invalid_tenant_domain_returns_404()
    {
        Sanctum::actingAs($this->userA);
        
        $this->withHeaders(['Host' => 'nonexistent.basketmanager-pro.com'])
             ->getJson('/api/v4/clubs')
             ->assertNotFound();
    }

    /** @test */
    public function suspended_tenant_cannot_access_api()
    {
        // Suspend tenant A
        $this->tenantA->update(['is_active' => false]);
        
        Sanctum::actingAs($this->userA);
        
        $this->withHeaders(['Host' => $this->tenantA->domain])
             ->getJson('/api/v4/clubs')
             ->assertStatus(403)
             ->assertJson(['message' => 'Tenant account is suspended']);
    }

    /** @test */
    public function tenant_usage_tracking_works_correctly()
    {
        $featureGate = app(FeatureGateService::class);
        $featureGate->setTenant($this->tenantA);
        
        // Track some API usage
        $featureGate->trackUsage('api_calls_per_hour', 5);
        $featureGate->trackUsage('api_calls_per_hour', 3);
        
        // Verify usage is tracked correctly
        $this->assertEquals(8, $featureGate->getCurrentUsage('api_calls_per_hour'));
        
        // Usage should not affect other tenants
        $featureGate->setTenant($this->tenantB);
        $this->assertEquals(0, $featureGate->getCurrentUsage('api_calls_per_hour'));
    }
}