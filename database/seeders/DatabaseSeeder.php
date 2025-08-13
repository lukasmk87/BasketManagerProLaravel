<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Temporarily set Carbon locale to English for database operations
        $originalLocale = Carbon::getLocale();
        Carbon::setLocale('en');
        
        try {
            // Create roles and permissions first
            $this->call(RoleAndPermissionSeeder::class);
            
            // Create tenants first (needed for multi-tenant setup)
            $this->call(TenantSeeder::class);
            
            // Basic test user
            User::firstOrCreate(
                ['email' => 'test@example.com'],
                [
                    'name' => 'Test User',
                    'password' => \Illuminate\Support\Facades\Hash::make('password'),
                ]
            );

            // Basketball-specific test users for manual testing
            $this->createBasketballTestUsers();
            
            // Create test basketball entities
            $this->createBasketballTestData();
            
        } finally {
            // Restore original Carbon locale
            Carbon::setLocale($originalLocale);
        }
    }

    /**
     * Create basketball-specific test users for manual testing.
     */
    private function createBasketballTestUsers(): void
    {
        // Admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@basketmanager.test'],
            [
                'name' => 'Test Admin',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'is_active' => true,
                'is_verified' => true,
                'language' => 'de',
            ]
        );
        $admin->assignRole('admin');

        // Club admin user
        $clubAdmin = User::firstOrCreate(
            ['email' => 'clubadmin@basketmanager.test'],
            [
                'name' => 'Test Club Admin',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'is_active' => true,
                'is_verified' => true,
                'language' => 'de',
            ]
        );
        $clubAdmin->assignRole('club_admin');

        // Trainer user
        $trainer = User::firstOrCreate(
            ['email' => 'trainer@basketmanager.test'],
            [
                'name' => 'Test Trainer',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'is_active' => true,
                'is_verified' => true,
                'language' => 'de',
            ]
        );
        $trainer->assignRole('trainer');

        // Player user
        $player = User::firstOrCreate(
            ['email' => 'player@basketmanager.test'],
            [
                'name' => 'Test Player',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'is_active' => true,
                'is_verified' => true,
                'language' => 'de',
                'date_of_birth' => now()->subYears(20),
            ]
        );
        $player->assignRole('player');
    }

    /**
     * Create test basketball entities (clubs, teams, players).
     */
    private function createBasketballTestData(): void
    {
        // Get our test users
        $admin = \App\Models\User::where('email', 'admin@basketmanager.test')->first();
        $clubAdmin = \App\Models\User::where('email', 'clubadmin@basketmanager.test')->first();
        $trainer = \App\Models\User::where('email', 'trainer@basketmanager.test')->first();
        $player = \App\Models\User::where('email', 'player@basketmanager.test')->first();

        if (!$admin || !$clubAdmin || !$trainer || !$player) {
            return; // Skip if users don't exist
        }

        // Create test club
        $testClub = \App\Models\Club::firstOrCreate(
            ['slug' => 'test-basketball-club'],
            [
                'name' => 'Test Basketball Club',
                'short_name' => 'TBC',
                'founded_at' => now()->subYears(24), // Founded in 2000
                'is_active' => true,
                'is_verified' => true,
                'address_street' => 'Teststraße 123',
                'address_city' => 'Teststadt',
                'address_zip' => '12345',
                'address_country' => 'DE',
            ]
        );

        // Create test team
        $testTeam = \App\Models\Team::firstOrCreate(
            ['slug' => 'test-team-senioren'],
            [
                'user_id' => $clubAdmin->id,  // Team owner (for Jetstream)
                'club_id' => $testClub->id,
                'name' => 'Test Team Senioren',
                'season' => '2024-25',
                'gender' => 'mixed',
                'age_group' => 'senior',
                'head_coach_id' => $trainer->id,
                'is_active' => true,
                'personal_team' => false,
            ]
        );

        // Create test player profile
        $testPlayer = \App\Models\Player::firstOrCreate(
            ['user_id' => $player->id],
            [
                'team_id' => $testTeam->id,
                'jersey_number' => 23,
                'primary_position' => 'SF',
                'status' => 'active',
                'is_starter' => true,
            ]
        );

        // Create club memberships (sync to avoid duplicates)
        $testClub->users()->syncWithoutDetaching([
            $clubAdmin->id => [
                'role' => 'admin',
                'joined_at' => now()->toDateString(),
                'is_active' => true,
            ],
            $trainer->id => [
                'role' => 'coach',
                'joined_at' => now()->toDateString(),
                'is_active' => true,
            ],
            $player->id => [
                'role' => 'player', 
                'joined_at' => now()->toDateString(),
                'is_active' => true,
            ]
        ]);
    }
}
