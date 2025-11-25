<?php

namespace Tests\Feature;

use App\Models\BasketballTeam;
use App\Models\Club;
use App\Models\Game;
use App\Models\Player;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * PERF-001/002: Performance Tests for Dashboard and ClubAdminPanel Controllers.
 *
 * TDD Tests - These tests define the expected query counts BEFORE optimization.
 * After optimization, all tests should pass.
 *
 * @see SECURITY_AND_PERFORMANCE_FIXES.md PERF-001, PERF-002
 */
class PERF001002PerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Club $club;
    protected User $clubAdmin;
    protected User $trainer;
    protected BasketballTeam $team;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure installation marker exists to prevent installation wizard
        if (!file_exists(storage_path('installed'))) {
            file_put_contents(storage_path('installed'), date('Y-m-d H:i:s'));
        }

        $this->setupTestData();
    }

    protected function setupTestData(): void
    {
        // Create roles if they don't exist
        $this->createRolesIfNeeded();

        // Create tenant and club
        $this->tenant = Tenant::factory()->create();
        $this->club = Club::factory()->create(['tenant_id' => $this->tenant->id]);

        // Create club admin user
        $this->clubAdmin = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->clubAdmin->assignRole('club_admin');
        $this->club->users()->attach($this->clubAdmin->id, [
            'role' => 'admin',
            'joined_at' => now(),
            'is_active' => true,
        ]);

        // Create trainer user
        $this->trainer = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->trainer->assignRole('trainer');

        // Create team with trainer as head coach
        $this->team = BasketballTeam::factory()->create([
            'club_id' => $this->club->id,
            'tenant_id' => $this->tenant->id,
            'head_coach_id' => $this->trainer->id,
        ]);

        // Create 10 players for the team
        $players = Player::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        foreach ($players as $player) {
            $this->team->players()->attach($player->id, [
                'jersey_number' => rand(1, 99),
                'primary_position' => 'PG',
                'status' => 'active',
                'is_active' => true,
            ]);
        }

        // Create 5 additional users as club members with roles
        for ($i = 0; $i < 5; $i++) {
            $member = User::factory()->create(['tenant_id' => $this->tenant->id]);
            $member->assignRole('player');
            $this->club->users()->attach($member->id, [
                'role' => 'member',
                'joined_at' => now()->subDays($i),
                'is_active' => true,
            ]);
        }
    }

    protected function createRolesIfNeeded(): void
    {
        $roles = ['super_admin', 'admin', 'club_admin', 'trainer', 'player', 'team_manager'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    // ========================================
    // PERF-001: DashboardController Tests
    // ========================================

    /** @test */
    public function dashboard_club_admin_queries_should_be_under_30(): void
    {
        DB::enableQueryLog();

        $response = $this->actingAs($this->clubAdmin)
            ->get(route('dashboard'));

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $response->assertStatus(200);

        // Target: Less than 30 queries for club admin dashboard
        $this->assertLessThan(
            30,
            $queryCount,
            "Club admin dashboard executed {$queryCount} queries, should be under 30. " .
            "N+1 query issue detected."
        );
    }

    /** @test */
    public function dashboard_trainer_queries_should_be_under_25(): void
    {
        DB::enableQueryLog();

        $response = $this->actingAs($this->trainer)
            ->get(route('dashboard'));

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $response->assertStatus(200);

        // Target: Less than 25 queries for trainer dashboard
        $this->assertLessThan(
            25,
            $queryCount,
            "Trainer dashboard executed {$queryCount} queries, should be under 25. " .
            "N+1 query issue detected."
        );
    }

    /** @test */
    public function dashboard_should_use_with_count_instead_of_loading_relations(): void
    {
        DB::enableQueryLog();

        $this->actingAs($this->clubAdmin)->get(route('dashboard'));

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Check that we're NOT loading all players (N+1 problem)
        $playerQueries = collect($queries)->filter(function ($query) {
            return str_contains($query['query'], 'players') &&
                   !str_contains($query['query'], 'count(*)');
        });

        // Should not have separate query for each team's players
        $this->assertLessThan(
            3,
            $playerQueries->count(),
            "Found {$playerQueries->count()} separate player queries. " .
            "Should use withCount() instead of loading all players."
        );
    }

    // ========================================
    // PERF-002: ClubAdminPanelController Tests
    // ========================================

    /** @test */
    public function club_admin_members_queries_should_be_under_15(): void
    {
        DB::enableQueryLog();

        $response = $this->actingAs($this->clubAdmin)
            ->get(route('club-admin.members'));

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $response->assertStatus(200);

        // Target: Less than 15 queries for members list
        // This was 50+ queries before optimization due to roles not being eager-loaded
        $this->assertLessThan(
            15,
            $queryCount,
            "Club admin members page executed {$queryCount} queries, should be under 15. " .
            "Check if roles are being eager-loaded with column selection."
        );
    }

    /** @test */
    public function club_admin_teams_queries_should_be_under_20(): void
    {
        DB::enableQueryLog();

        $response = $this->actingAs($this->clubAdmin)
            ->get(route('club-admin.teams'));

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $response->assertStatus(200);

        // Target: Less than 20 queries for teams list
        $this->assertLessThan(
            20,
            $queryCount,
            "Club admin teams page executed {$queryCount} queries, should be under 20. " .
            "Check if players are being loaded unnecessarily."
        );
    }

    /** @test */
    public function club_admin_players_queries_should_be_under_20(): void
    {
        DB::enableQueryLog();

        $response = $this->actingAs($this->clubAdmin)
            ->get(route('club-admin.players'));

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $response->assertStatus(200);

        // Target: Less than 20 queries for players list
        $this->assertLessThan(
            20,
            $queryCount,
            "Club admin players page executed {$queryCount} queries, should be under 20."
        );
    }

    /** @test */
    public function members_page_should_eager_load_roles_with_selection(): void
    {
        DB::enableQueryLog();

        $this->actingAs($this->clubAdmin)->get(route('club-admin.members'));

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Count role queries - should be just 1 eager-loaded query, not N queries
        $roleQueries = collect($queries)->filter(function ($query) {
            return str_contains($query['query'], 'roles') ||
                   str_contains($query['query'], 'model_has_roles');
        });

        // Should have at most 2-3 role-related queries (eager load), not N queries
        $this->assertLessThan(
            5,
            $roleQueries->count(),
            "Found {$roleQueries->count()} role queries. " .
            "Should use ->with('roles:id,name') for eager loading."
        );
    }

    /** @test */
    public function teams_page_should_not_load_full_players(): void
    {
        DB::enableQueryLog();

        $this->actingAs($this->clubAdmin)->get(route('club-admin.teams'));

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Check if we're doing a SELECT * on players table
        $fullPlayerQueries = collect($queries)->filter(function ($query) {
            return str_contains($query['query'], 'select * from') &&
                   str_contains($query['query'], 'players');
        });

        $this->assertCount(
            0,
            $fullPlayerQueries,
            "Found full player queries (SELECT *). " .
            "Should use withCount('players') instead of loading full player objects."
        );
    }

    // ========================================
    // Combined Performance Tests
    // ========================================

    /** @test */
    public function dashboard_response_time_should_be_acceptable(): void
    {
        $startTime = microtime(true);

        $this->actingAs($this->clubAdmin)->get(route('dashboard'));

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // in milliseconds

        // Target: Dashboard should load in under 500ms (without external services)
        $this->assertLessThan(
            500,
            $executionTime,
            "Dashboard took {$executionTime}ms to load, should be under 500ms."
        );
    }

    /** @test */
    public function club_admin_dashboard_method_should_use_with_count(): void
    {
        DB::enableQueryLog();

        $this->actingAs($this->clubAdmin)->get(route('dashboard'));

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Look for count queries which indicate proper withCount() usage
        $countQueries = collect($queries)->filter(function ($query) {
            return str_contains($query['query'], 'count(*)') ||
                   str_contains($query['query'], 'count(distinct');
        });

        // Should have at least some count queries (from withCount)
        $this->assertGreaterThan(
            0,
            $countQueries->count(),
            "No count queries found. Dashboard should use withCount() for teams_count, players_count."
        );
    }
}
