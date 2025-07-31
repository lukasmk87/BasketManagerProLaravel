<?php

namespace Tests;

use App\Models\User;
use App\Models\Club;
use App\Models\Team;
use App\Models\Player;
use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

abstract class BasketballTestCase extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $adminUser;
    protected User $clubAdminUser;
    protected User $trainerUser;
    protected User $playerUser;
    protected Club $testClub;
    protected Team $testTeam;
    protected Player $testPlayer;

    /**
     * Setup the test environment for basketball testing.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles and permissions
        $this->setupRolesAndPermissions();
        
        // Create test users with different roles
        $this->createTestUsers();
        
        // Create test basketball entities
        $this->createTestBasketballEntities();
    }

    /**
     * Create roles and permissions for basketball system.
     */
    protected function setupRolesAndPermissions(): void
    {
        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $clubAdminRole = Role::create(['name' => 'club-admin']);
        $trainerRole = Role::create(['name' => 'trainer']);
        $playerRole = Role::create(['name' => 'player']);
        $parentRole = Role::create(['name' => 'parent']);

        // Create permissions
        $permissions = [
            // User management
            'manage-users',
            'view-users',
            'create-users',
            'update-users',
            'delete-users',
            
            // Club management
            'manage-clubs',
            'view-clubs',
            'create-clubs',
            'update-clubs',
            'delete-clubs',
            
            // Team management
            'manage-teams',
            'view-teams',
            'create-teams',
            'update-teams',
            'delete-teams',
            
            // Player management
            'manage-players',
            'view-players',
            'create-players',
            'update-players',
            'delete-players',
            
            // Game management
            'manage-games',
            'view-games',
            'create-games',
            'update-games',
            'delete-games',
            'score-games',
            
            // Statistics
            'view-statistics',
            'manage-statistics',
            
            // Emergency system
            'manage-emergency-contacts',
            'view-emergency-contacts',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Assign permissions to roles
        $adminRole->givePermissionTo(Permission::all());
        
        $clubAdminRole->givePermissionTo([
            'view-clubs', 'update-clubs',
            'manage-teams', 'view-teams', 'create-teams', 'update-teams',
            'manage-players', 'view-players', 'create-players', 'update-players',
            'manage-games', 'view-games', 'create-games', 'update-games',
            'view-statistics',
            'manage-emergency-contacts', 'view-emergency-contacts',
        ]);
        
        $trainerRole->givePermissionTo([
            'view-teams', 'update-teams',
            'view-players', 'update-players',
            'view-games', 'update-games', 'score-games',
            'view-statistics',
        ]);
        
        $playerRole->givePermissionTo([
            'view-teams',
            'view-players',
            'view-games',
            'view-statistics',
        ]);
    }

    /**
     * Create test users with different roles.
     */
    protected function createTestUsers(): void
    {
        // Admin user
        $this->adminUser = User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'admin@basketmanager.test',
            'password' => Hash::make('password'),
            'is_active' => true,
            'is_verified' => true,
            'language' => 'de',
        ]);
        $this->adminUser->assignRole('admin');

        // Club admin user
        $this->clubAdminUser = User::factory()->create([
            'name' => 'Test Club Admin',
            'email' => 'clubadmin@basketmanager.test',
            'password' => Hash::make('password'),
            'is_active' => true,
            'is_verified' => true,
            'language' => 'de',
        ]);
        $this->clubAdminUser->assignRole('club-admin');

        // Trainer user
        $this->trainerUser = User::factory()->create([
            'name' => 'Test Trainer',
            'email' => 'trainer@basketmanager.test',
            'password' => Hash::make('password'),
            'is_active' => true,
            'is_verified' => true,
            'language' => 'de',
        ]);
        $this->trainerUser->assignRole('trainer');

        // Player user
        $this->playerUser = User::factory()->create([
            'name' => 'Test Player',
            'email' => 'player@basketmanager.test',
            'password' => Hash::make('password'),
            'is_active' => true,
            'is_verified' => true,
            'language' => 'de',
            'date_of_birth' => now()->subYears(20),
        ]);
        $this->playerUser->assignRole('player');
    }

    /**
     * Create test basketball entities.
     */
    protected function createTestBasketballEntities(): void
    {
        // Create test club
        $this->testClub = Club::factory()->create([
            'name' => 'Test Basketball Club',
            'short_name' => 'TBC',
            'slug' => 'test-basketball-club',
            'founded_year' => 2000,
            'is_active' => true,
            'is_verified' => true,
        ]);

        // Assign club admin to club
        $this->testClub->users()->attach($this->clubAdminUser, [
            'role' => 'admin',
            'joined_at' => now(),
            'is_active' => true,
        ]);

        // Create test team
        $this->testTeam = Team::factory()->create([
            'club_id' => $this->testClub->id,
            'name' => 'Test Team',
            'slug' => 'test-team',
            'season' => '2024-25',
            'gender' => 'mixed',
            'age_group' => 'senior',
            'head_coach_id' => $this->trainerUser->id,
            'is_active' => true,
        ]);

        // Assign trainer to team
        $this->testTeam->users()->attach($this->trainerUser, [
            'role' => 'head_coach',
            'joined_at' => now(),
            'is_active' => true,
        ]);

        // Create test player
        $this->testPlayer = Player::factory()->create([
            'user_id' => $this->playerUser->id,
            'team_id' => $this->testTeam->id,
            'jersey_number' => 23,
            'position' => 'SF',
            'is_active' => true,
        ]);

        // Assign player to team
        $this->testTeam->users()->attach($this->playerUser, [
            'role' => 'player',
            'joined_at' => now(),
            'is_active' => true,
            'jersey_number' => 23,
            'is_starter' => true,
        ]);
    }

    /**
     * Create a test game between two teams.
     */
    protected function createTestGame(Team $homeTeam = null, Team $awayTeam = null): Game
    {
        $homeTeam = $homeTeam ?? $this->testTeam;
        $awayTeam = $awayTeam ?? Team::factory()->create([
            'club_id' => $this->testClub->id,
            'name' => 'Away Team',
            'season' => '2024-25',
        ]);

        return Game::factory()->create([
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'scheduled_at' => now()->addDays(7),
            'status' => 'scheduled',
            'season' => '2024-25',
        ]);
    }

    /**
     * Authenticate as admin user.
     */
    protected function actingAsAdmin(): self
    {
        $this->actingAs($this->adminUser);
        return $this;
    }

    /**
     * Authenticate as club admin user.
     */
    protected function actingAsClubAdmin(): self
    {
        $this->actingAs($this->clubAdminUser);
        return $this;
    }

    /**
     * Authenticate as trainer user.
     */
    protected function actingAsTrainer(): self
    {
        $this->actingAs($this->trainerUser);
        return $this;
    }

    /**
     * Authenticate as player user.
     */
    protected function actingAsPlayer(): self
    {
        $this->actingAs($this->playerUser);
        return $this;
    }

    /**
     * Create additional test players for the team.
     */
    protected function createTeamRoster(Team $team = null, int $count = 10): array
    {
        $team = $team ?? $this->testTeam;
        $players = [];

        for ($i = 1; $i <= $count; $i++) {
            $user = User::factory()->create([
                'name' => "Player {$i}",
                'email' => "player{$i}@basketmanager.test",
            ]);
            $user->assignRole('player');

            $player = Player::factory()->create([
                'user_id' => $user->id,
                'team_id' => $team->id,
                'jersey_number' => $i,
                'position' => $this->getRandomPosition(),
            ]);

            $team->users()->attach($user, [
                'role' => 'player',
                'joined_at' => now()->subDays(rand(1, 365)),
                'is_active' => true,
                'jersey_number' => $i,
                'is_starter' => $i <= 5,
            ]);

            $players[] = $player;
        }

        return $players;
    }

    /**
     * Get a random basketball position.
     */
    protected function getRandomPosition(): string
    {
        $positions = ['PG', 'SG', 'SF', 'PF', 'C'];
        return $positions[array_rand($positions)];
    }

    /**
     * Assert that a user has specific basketball role.
     */
    protected function assertUserHasBasketballRole(User $user, string $role): void
    {
        $this->assertTrue($user->hasRole($role), "User does not have role: {$role}");
    }

    /**
     * Assert that a user can access specific basketball resource.
     */
    protected function assertUserCanAccess(User $user, string $permission): void
    {
        $this->assertTrue($user->can($permission), "User cannot access: {$permission}");
    }

    /**
     * Create sample statistics data for testing.
     */
    protected function createSampleStatistics(Player $player = null): array
    {
        $player = $player ?? $this->testPlayer;

        return [
            'games_played' => $this->faker->numberBetween(5, 30),
            'points' => $this->faker->numberBetween(50, 500),
            'rebounds' => $this->faker->numberBetween(20, 200),
            'assists' => $this->faker->numberBetween(10, 150),
            'steals' => $this->faker->numberBetween(5, 50),
            'blocks' => $this->faker->numberBetween(2, 30),
            'field_goals_made' => $this->faker->numberBetween(20, 200),
            'field_goals_attempted' => $this->faker->numberBetween(40, 400),
            'three_pointers_made' => $this->faker->numberBetween(5, 80),
            'three_pointers_attempted' => $this->faker->numberBetween(15, 200),
            'free_throws_made' => $this->faker->numberBetween(10, 100),
            'free_throws_attempted' => $this->faker->numberBetween(15, 120),
            'fouls' => $this->faker->numberBetween(10, 100),
            'turnovers' => $this->faker->numberBetween(15, 80),
        ];
    }

    /**
     * Assert basketball statistics are calculated correctly.
     */
    protected function assertStatisticsCorrect(array $stats, array $expected): void
    {
        foreach ($expected as $key => $value) {
            $this->assertEquals(
                $value, 
                $stats[$key], 
                "Statistic {$key} does not match expected value"
            );
        }
    }
}