<?php

namespace Tests\Unit\Services;

use Tests\BasketballTestCase;
use App\Services\PlayerService;
use App\Services\Statistics\StatisticsService;
use App\Models\Player;
use App\Models\User;
use App\Models\Team;
use App\Models\Club;
use App\Models\EmergencyContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Mockery;

class PlayerServiceTest extends BasketballTestCase
{
    use RefreshDatabase, WithFaker;

    private PlayerService $playerService;
    private StatisticsService $statisticsService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock StatisticsService
        $this->statisticsService = Mockery::mock(StatisticsService::class);
        $this->app->instance(StatisticsService::class, $this->statisticsService);
        
        $this->playerService = app(PlayerService::class);
    }

    /** @test */
    public function it_can_create_a_player_with_valid_data()
    {
        $user = User::factory()->create();
        $team = $this->testTeam;
        
        $playerData = [
            'user_id' => $user->id,
            'team_id' => $team->id,
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'full_name' => 'Max Mustermann',
            'jersey_number' => 42,
            'primary_position' => 'PG',
            'secondary_positions' => ['SG'],
            'is_starter' => true,
            'is_captain' => false,
            'status' => 'active',
            'height_cm' => 180,
            'weight_kg' => 75,
            'dominant_hand' => 'right',
            'years_experience' => 5,
            'emergency_contacts' => [
                [
                    'name' => 'Maria Mustermann',
                    'relationship' => 'mother',
                    'phone_primary' => '+49123456789',
                    'is_primary' => true,
                    'can_pickup' => true,
                    'medical_authority' => true,
                ]
            ]
        ];

        $player = $this->playerService->createPlayer($playerData);

        // Assert player was created correctly
        $this->assertInstanceOf(Player::class, $player);
        $this->assertEquals('Max Mustermann', $player->full_name);
        $this->assertEquals(42, $player->jersey_number);
        $this->assertEquals('PG', $player->primary_position);
        $this->assertEquals($team->id, $player->team_id);
        $this->assertEquals($user->id, $player->user_id);
        $this->assertTrue($player->is_starter);
        $this->assertFalse($player->is_captain);
        $this->assertEquals(180, $player->height_cm);
        $this->assertEquals(75, $player->weight_kg);

        // Assert database record
        $this->assertDatabaseHas('players', [
            'user_id' => $user->id,
            'team_id' => $team->id,
            'jersey_number' => 42,
            'primary_position' => 'PG',
        ]);

        // Assert team membership was created
        $this->assertDatabaseHas('team_user', [
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role' => 'player',
        ]);

        // Assert emergency contact was created
        $this->assertDatabaseHas('emergency_contacts', [
            'player_id' => $player->id,
            'name' => 'Maria Mustermann',
            'relationship' => 'mother',
        ]);
    }

    /** @test */
    public function it_can_create_player_without_team()
    {
        $user = User::factory()->create();
        
        $playerData = [
            'user_id' => $user->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'jersey_number' => null,
            'primary_position' => 'SF',
            'status' => 'active',
        ];

        $player = $this->playerService->createPlayer($playerData);

        $this->assertInstanceOf(Player::class, $player);
        $this->assertNull($player->team_id);
        $this->assertNull($player->jersey_number);
        $this->assertEquals('SF', $player->primary_position);
        $this->assertEquals($user->id, $player->user_id);
    }

    /** @test */
    public function it_can_update_player_information()
    {
        $player = $this->testPlayer;
        $originalPosition = $player->primary_position;
        
        $updateData = [
            'primary_position' => 'PG',
            'jersey_number' => 99,
            'is_starter' => true,
            'is_captain' => true,
            'height_cm' => 185,
            'weight_kg' => 80,
            'coach_notes' => 'Excellent ball handler',
        ];

        $updatedPlayer = $this->playerService->updatePlayer($player, $updateData);

        $this->assertInstanceOf(Player::class, $updatedPlayer);
        $this->assertEquals('PG', $updatedPlayer->primary_position);
        $this->assertEquals(99, $updatedPlayer->jersey_number);
        $this->assertTrue($updatedPlayer->is_starter);
        $this->assertTrue($updatedPlayer->is_captain);
        $this->assertEquals(185, $updatedPlayer->height_cm);
        $this->assertEquals(80, $updatedPlayer->weight_kg);
        $this->assertEquals('Excellent ball handler', $updatedPlayer->coach_notes);
        $this->assertNotEquals($originalPosition, $updatedPlayer->primary_position);

        $this->assertDatabaseHas('players', [
            'id' => $player->id,
            'primary_position' => 'PG',
            'jersey_number' => 99,
            'is_starter' => true,
            'is_captain' => true,
        ]);
    }

    /** @test */
    public function it_can_delete_player_with_game_history()
    {
        $player = $this->testPlayer;
        
        // Create a mock game action to simulate history
        DB::table('game_actions')->insert([
            'player_id' => $player->id,
            'game_id' => 1,
            'action_type' => 'shot',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->playerService->deletePlayer($player);

        $this->assertTrue($result);
        
        // Player should be soft deleted (status changed)
        $player->refresh();
        $this->assertEquals('inactive', $player->status);
        $this->assertNotNull($player->left_at);
        
        // Database record should still exist
        $this->assertDatabaseHas('players', [
            'id' => $player->id,
            'status' => 'inactive',
        ]);
    }

    /** @test */
    public function it_can_delete_player_without_game_history()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'team_id' => $this->testTeam->id,
        ]);
        
        // No game actions exist (no game history)
        $result = $this->playerService->deletePlayer($player);

        $this->assertTrue($result);
        
        // Player should be hard deleted
        $this->assertDatabaseMissing('players', ['id' => $player->id]);
    }

    /** @test */
    public function it_can_transfer_player_to_new_team()
    {
        $player = $this->testPlayer;
        $oldTeam = $player->team;
        $newTeam = Team::factory()->create([
            'club_id' => $this->testClub->id,
            'max_players' => 20,
            'min_players' => 1,
        ]);

        $result = $this->playerService->transferPlayer($player, $newTeam);

        $this->assertTrue($result);
        
        $player->refresh();
        $this->assertEquals($newTeam->id, $player->team_id);
        $this->assertFalse($player->is_starter); // Should reset starter status
        $this->assertFalse($player->is_captain); // Should reset captain status
        $this->assertEquals('active', $player->status);
        $this->assertNotNull($player->transfer_date);

        // Check team membership updates
        $this->assertDatabaseHas('team_user', [
            'team_id' => $newTeam->id,
            'user_id' => $player->user_id,
            'role' => 'player',
        ]);
    }

    /** @test */
    public function it_handles_jersey_number_conflicts_during_transfer()
    {
        $player = $this->testPlayer;
        $player->update(['jersey_number' => 10]);
        
        $newTeam = Team::factory()->create([
            'club_id' => $this->testClub->id,
            'max_players' => 20,
            'min_players' => 1,
        ]);

        // Create conflicting player in new team
        $conflictingPlayer = Player::factory()->create([
            'team_id' => $newTeam->id,
            'jersey_number' => 10,
            'status' => 'active',
        ]);

        $result = $this->playerService->transferPlayer($player, $newTeam);

        $this->assertTrue($result);
        
        $player->refresh();
        $this->assertEquals($newTeam->id, $player->team_id);
        $this->assertNull($player->jersey_number); // Should be cleared due to conflict
    }

    /** @test */
    public function it_prevents_duplicate_jersey_numbers_in_same_team()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $player1 = Player::factory()->create([
            'user_id' => $user1->id,
            'team_id' => $this->testTeam->id,
            'jersey_number' => 10,
            'status' => 'active',
        ]);

        $playerData = [
            'user_id' => $user2->id,
            'team_id' => $this->testTeam->id,
            'jersey_number' => 10, // Same number as player1
            'primary_position' => 'SG',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Trikotnummer 10 ist bereits vergeben.');

        $this->playerService->createPlayer($playerData);
    }

    /** @test */
    public function it_prevents_player_from_joining_multiple_teams_same_league()
    {
        $user = User::factory()->create();
        $team1 = $this->testTeam;
        
        // Create second team in same league and season
        $team2 = Team::factory()->create([
            'club_id' => $this->testClub->id,
            'season' => $team1->season,
            'league' => $team1->league,
            'is_active' => true,
        ]);

        // Create player in first team
        $player1 = Player::factory()->create([
            'user_id' => $user->id,
            'team_id' => $team1->id,
            'status' => 'active',
        ]);

        // Try to create same user as player in second team
        $playerData = [
            'user_id' => $user->id,
            'team_id' => $team2->id,
            'primary_position' => 'PG',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Spieler ist bereits in einem anderen aktiven Team in dieser Liga registriert.');

        $this->playerService->createPlayer($playerData);
    }

    /** @test */
    public function it_rejects_transfer_to_full_team()
    {
        $player = $this->testPlayer;
        $fullTeam = Team::factory()->create([
            'club_id' => $this->testClub->id,
            'max_players' => 1, // Only one player allowed
            'min_players' => 1,
        ]);

        // Fill the team to capacity
        $existingPlayer = Player::factory()->create([
            'team_id' => $fullTeam->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Ziel-Team kann keine neuen Spieler aufnehmen.');

        $this->playerService->transferPlayer($player, $fullTeam);
    }

    /** @test */
    public function it_can_get_player_statistics()
    {
        $player = $this->testPlayer;
        $season = '2024-25';
        
        // Update player with some statistics
        $player->update([
            'games_played' => 20,
            'games_started' => 15,
            'minutes_played' => 600,
            'points_scored' => 300,
            'field_goals_made' => 120,
            'field_goals_attempted' => 250,
            'three_pointers_made' => 30,
            'three_pointers_attempted' => 80,
            'free_throws_made' => 30,
            'free_throws_attempted' => 40,
            'rebounds_total' => 100,
            'assists' => 80,
            'steals' => 40,
            'blocks' => 20,
            'turnovers' => 60,
            'fouls_personal' => 50,
        ]);

        // Mock StatisticsService
        $this->statisticsService
            ->shouldReceive('getPlayerSeasonStats')
            ->once()
            ->with($player, $season)
            ->andReturn(['additional_stat' => 10]);

        $stats = $this->playerService->getPlayerStatistics($player, $season);

        $this->assertIsArray($stats);
        $this->assertEquals(20, $stats['games_played']);
        $this->assertEquals(15, $stats['games_started']);
        $this->assertEquals(300, $stats['points_scored']);
        $this->assertEquals(15.0, $stats['points_per_game']); // 300 / 20
        $this->assertEquals(5.0, $stats['rebounds_per_game']); // 100 / 20
        $this->assertEquals(4.0, $stats['assists_per_game']); // 80 / 20
        $this->assertEquals(30.0, $stats['minutes_per_game']); // 600 / 20
        $this->assertEquals(48.0, $stats['field_goal_percentage']); // 120 / 250 * 100
        $this->assertEquals(10, $stats['additional_stat']); // From mocked service
    }

    /** @test */
    public function it_can_update_player_statistics_from_game_data()
    {
        $player = $this->testPlayer;
        $player->update([
            'games_played' => 10,
            'games_started' => 5,
            'minutes_played' => 300,
            'points_scored' => 150,
            'field_goals_made' => 60,
            'field_goals_attempted' => 120,
            'three_pointers_made' => 15,
            'three_pointers_attempted' => 40,
            'free_throws_made' => 15,
            'free_throws_attempted' => 20,
            'rebounds_offensive' => 20,
            'rebounds_defensive' => 40,
            'assists' => 30,
            'steals' => 15,
            'blocks' => 5,
            'turnovers' => 25,
            'fouls_personal' => 20,
        ]);

        $gameStats = [
            'minutes_played' => 35,
            'game_started' => true,
            'field_goals_made' => 8,
            'field_goals_attempted' => 15,
            'three_pointers_made' => 3,
            'three_pointers_attempted' => 7,
            'free_throws_made' => 4,
            'free_throws_attempted' => 5,
            'rebounds_offensive' => 2,
            'rebounds_defensive' => 6,
            'assists' => 5,
            'steals' => 2,
            'blocks' => 1,
            'turnovers' => 3,
            'fouls_personal' => 2,
        ];

        // Mock StatisticsService
        $this->statisticsService
            ->shouldReceive('invalidatePlayerStats')
            ->once()
            ->with($player);

        $this->playerService->updatePlayerStatistics($player, $gameStats);

        $player->refresh();
        
        // Check updated values
        $this->assertEquals(11, $player->games_played); // 10 + 1
        $this->assertEquals(6, $player->games_started); // 5 + 1 (game_started = true)
        $this->assertEquals(335, $player->minutes_played); // 300 + 35
        $this->assertEquals(68, $player->field_goals_made); // 60 + 8
        $this->assertEquals(135, $player->field_goals_attempted); // 120 + 15
        $this->assertEquals(18, $player->three_pointers_made); // 15 + 3
        $this->assertEquals(47, $player->three_pointers_attempted); // 40 + 7
        $this->assertEquals(19, $player->free_throws_made); // 15 + 4
        $this->assertEquals(25, $player->free_throws_attended); // 20 + 5
        $this->assertEquals(22, $player->rebounds_offensive); // 20 + 2
        $this->assertEquals(46, $player->rebounds_defensive); // 40 + 6
        $this->assertEquals(68, $player->rebounds_total); // 22 + 46
        $this->assertEquals(35, $player->assists); // 30 + 5
        $this->assertEquals(17, $player->steals); // 15 + 2
        $this->assertEquals(6, $player->blocks); // 5 + 1
        $this->assertEquals(28, $player->turnovers); // 25 + 3
        $this->assertEquals(22, $player->fouls_personal); // 20 + 2
        
        // Check calculated points (2pts for FGs + 1pt bonus for 3pts + 1pt for FTs)
        $expectedPoints = 150 + (8 * 2) + 3 + 4; // 150 + 16 + 3 + 4 = 173
        $this->assertEquals(173, $player->points_scored);
    }

    /** @test */
    public function it_calculates_per_game_averages_correctly()
    {
        $player = $this->testPlayer;
        $player->update([
            'games_played' => 0, // No games played yet
            'points_scored' => 0,
            'rebounds_total' => 0,
            'assists' => 0,
            'minutes_played' => 0,
        ]);

        $stats = $this->playerService->getPlayerStatistics($player, '2024-25');

        // Should not have per-game stats when no games played
        $this->assertArrayNotHasKey('points_per_game', $stats);
        $this->assertArrayNotHasKey('rebounds_per_game', $stats);
        $this->assertArrayNotHasKey('assists_per_game', $stats);
        $this->assertArrayNotHasKey('minutes_per_game', $stats);
    }

    /** @test */
    public function it_calculates_shooting_percentages_correctly()
    {
        $player = $this->testPlayer;
        $player->update([
            'field_goals_made' => 45,
            'field_goals_attempted' => 100,
            'three_pointers_made' => 20,
            'three_pointers_attempted' => 50,
            'free_throws_made' => 80,
            'free_throws_attempted' => 100,
        ]);

        // Mock StatisticsService
        $this->statisticsService
            ->shouldReceive('getPlayerSeasonStats')
            ->once()
            ->andReturn([]);

        $stats = $this->playerService->getPlayerStatistics($player, '2024-25');

        $this->assertEquals(45.0, $stats['field_goal_percentage']); // 45/100 * 100
        $this->assertEquals(40.0, $stats['three_point_percentage']); // 20/50 * 100
        $this->assertEquals(80.0, $stats['free_throw_percentage']); // 80/100 * 100
    }

    /** @test */
    public function it_validates_team_roster_capacity()
    {
        $team = Team::factory()->create([
            'club_id' => $this->testClub->id,
            'max_players' => 1,
            'min_players' => 1,
        ]);

        // Fill team to capacity
        $existingPlayer = Player::factory()->create([
            'team_id' => $team->id,
        ]);

        $user = User::factory()->create();
        $playerData = [
            'user_id' => $user->id,
            'team_id' => $team->id,
            'primary_position' => 'PG',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Team kann keine neuen Spieler aufnehmen.');

        $this->playerService->createPlayer($playerData);
    }

    /** @test */
    public function it_validates_league_eligibility_rules()
    {
        $user = User::factory()->create();
        
        // Create team in specific league
        $team = Team::factory()->create([
            'club_id' => $this->testClub->id,
            'season' => '2024-25',
            'league' => 'Bundesliga',
            'is_active' => true,
        ]);

        // Create existing player for same user in same league
        $existingPlayer = Player::factory()->create([
            'user_id' => $user->id,
            'team_id' => $this->testTeam->id, // testTeam has same league
            'status' => 'active',
        ]);

        $playerData = [
            'user_id' => $user->id,
            'team_id' => $team->id,
            'primary_position' => 'PG',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Spieler ist bereits in einem anderen aktiven Team in dieser Liga registriert.');

        $this->playerService->createPlayer($playerData);
    }

    /** @test */
    public function it_handles_captain_and_starter_status()
    {
        $user = User::factory()->create();
        $playerData = [
            'user_id' => $user->id,
            'team_id' => $this->testTeam->id,
            'primary_position' => 'PG',
            'jersey_number' => 1,
            'is_captain' => true,
            'is_starter' => true,
        ];

        $player = $this->playerService->createPlayer($playerData);

        $this->assertTrue($player->is_captain);
        $this->assertTrue($player->is_starter);

        // Update to remove captain status
        $updatedPlayer = $this->playerService->updatePlayer($player, [
            'is_captain' => false,
            'is_starter' => false,
        ]);

        $this->assertFalse($updatedPlayer->is_captain);
        $this->assertFalse($updatedPlayer->is_starter);
    }

    /** @test */
    public function it_validates_player_eligibility_requirements()
    {
        $user = User::factory()->create();
        $playerData = [
            'user_id' => $user->id,
            'team_id' => $this->testTeam->id,
            'primary_position' => 'C',
            'jersey_number' => 50,
            'medical_clearance' => false,
            'academic_eligibility' => false,
            'status' => 'inactive',
        ];

        $player = $this->playerService->createPlayer($playerData);

        $this->assertFalse($player->medical_clearance);
        $this->assertFalse($player->academic_eligibility);
        $this->assertEquals('inactive', $player->status);
    }

    /** @test */
    public function it_can_manage_emergency_contacts()
    {
        $user = User::factory()->create();
        $contactsData = [
            [
                'name' => 'John Doe Sr.',
                'relationship' => 'father',
                'phone_primary' => '+49123456789',
                'phone_secondary' => '+49987654321',
                'email' => 'father@example.com',
                'is_primary' => true,
                'can_pickup' => true,
                'medical_authority' => true,
                'notes' => 'Primary emergency contact',
            ],
            [
                'name' => 'Jane Doe',
                'relationship' => 'mother',
                'phone_primary' => '+49111222333',
                'email' => 'mother@example.com',
                'is_primary' => false,
                'can_pickup' => true,
                'medical_authority' => true,
            ]
        ];

        $playerData = [
            'user_id' => $user->id,
            'team_id' => $this->testTeam->id,
            'primary_position' => 'SG',
            'jersey_number' => 2,
            'emergency_contacts' => $contactsData,
        ];

        $player = $this->playerService->createPlayer($playerData);

        // Assert emergency contacts were created
        $this->assertDatabaseHas('emergency_contacts', [
            'player_id' => $player->id,
            'name' => 'John Doe Sr.',
            'relationship' => 'father',
            'is_primary' => true,
        ]);

        $this->assertDatabaseHas('emergency_contacts', [
            'player_id' => $player->id,
            'name' => 'Jane Doe',
            'relationship' => 'mother',
            'is_primary' => false,
        ]);

        // Test updating emergency contacts
        $newContactsData = [
            [
                'name' => 'Updated Contact',
                'relationship' => 'guardian',
                'phone_primary' => '+49999888777',
                'is_primary' => true,
                'can_pickup' => true,
                'medical_authority' => true,
            ]
        ];

        $updatedPlayer = $this->playerService->updatePlayer($player, [
            'emergency_contacts' => $newContactsData,
        ]);

        // Old contacts should be removed, new one added
        $this->assertDatabaseMissing('emergency_contacts', [
            'player_id' => $player->id,
            'name' => 'John Doe Sr.',
        ]);

        $this->assertDatabaseHas('emergency_contacts', [
            'player_id' => $player->id,
            'name' => 'Updated Contact',
            'relationship' => 'guardian',
        ]);
    }

    /** @test */
    public function it_can_generate_comprehensive_player_report()
    {
        $player = $this->testPlayer;
        $season = '2024-25';
        
        // Setup player data
        $player->update([
            'jersey_number' => 23,
            'primary_position' => 'SF',
            'is_captain' => true,
            'is_starter' => true,
            'height_cm' => 200,
            'weight_kg' => 85,
            'years_experience' => 8,
            'coach_notes' => 'Excellent leadership',
            'shooting_rating' => 8.5,
            'defense_rating' => 7.0,
        ]);

        // Mock StatisticsService only
        $this->statisticsService
            ->shouldReceive('getPlayerSeasonStats')
            ->once()
            ->with($player, $season)
            ->andReturn(['season_stats' => 'mocked']);

        $report = $this->playerService->generatePlayerReport($player);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('player_info', $report);
        $this->assertArrayHasKey('user_info', $report);
        $this->assertArrayHasKey('team_info', $report);
        $this->assertArrayHasKey('contract_info', $report);
        $this->assertArrayHasKey('eligibility', $report);
        $this->assertArrayHasKey('statistics', $report);
        $this->assertArrayHasKey('development', $report);
        $this->assertArrayHasKey('emergency_contacts', $report);
        $this->assertArrayHasKey('generated_at', $report);

        // Check player info section
        $this->assertEquals($player->id, $report['player_info']['id']);
        $this->assertEquals(23, $report['player_info']['jersey_number']);
        $this->assertEquals('SF', $report['player_info']['primary_position']);
        $this->assertTrue($report['player_info']['is_captain']);
        $this->assertTrue($report['player_info']['is_starter']);
        $this->assertEquals(200, $report['player_info']['height_cm']);
        $this->assertEquals(85, $report['player_info']['weight_kg']);
        $this->assertEquals(8, $report['player_info']['years_experience']);

        // Check development section
        $this->assertEquals('Excellent leadership', $report['development']['coach_notes']);
        $this->assertEquals(8.5, $report['development']['ratings']['shooting']);
        $this->assertEquals(7.0, $report['development']['ratings']['defense']);
    }

    /** @test */
    public function it_includes_all_sections_in_player_report()
    {
        $player = $this->testPlayer;
        
        // Mock StatisticsService only
        $this->statisticsService
            ->shouldReceive('getPlayerSeasonStats')
            ->once()
            ->andReturn([]);

        $report = $this->playerService->generatePlayerReport($player);

        $requiredSections = [
            'player_info', 'user_info', 'team_info', 'contract_info',
            'eligibility', 'statistics', 'development', 'emergency_contacts',
            'generated_at'
        ];

        foreach ($requiredSections as $section) {
            $this->assertArrayHasKey($section, $report, "Report is missing section: {$section}");
        }

        // Basic checks without mocking Player methods
        $this->assertIsArray($report['eligibility']);
        $this->assertIsArray($report['emergency_contacts']);
    }

    /** @test */
    public function it_handles_statistical_edge_cases()
    {
        $player = $this->testPlayer;
        
        // Test division by zero scenarios
        $player->update([
            'games_played' => 0,
            'field_goals_attempted' => 0,
            'three_pointers_attempted' => 0,
            'free_throws_attempted' => 0,
        ]);

        // Mock StatisticsService
        $this->statisticsService
            ->shouldReceive('getPlayerSeasonStats')
            ->once()
            ->andReturn([]);

        $stats = $this->playerService->getPlayerStatistics($player, '2024-25');

        // Should handle zero division gracefully
        $this->assertIsArray($stats);
        $this->assertEquals(0, $stats['games_played']);
        
        // Per-game stats should not be calculated when games_played = 0
        $this->assertArrayNotHasKey('points_per_game', $stats);
        $this->assertArrayNotHasKey('rebounds_per_game', $stats);
    }

    /** @test */
    public function it_handles_database_transaction_failures()
    {
        $user = User::factory()->create();
        
        // Simulate database error during player creation
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();
        
        // Force an exception by using invalid data
        $playerData = [
            'user_id' => 999999, // Non-existent user ID
            'team_id' => $this->testTeam->id,
            'primary_position' => 'PG',
        ];

        $this->expectException(\Exception::class);

        $this->playerService->createPlayer($playerData);
    }

    /** @test */
    public function it_logs_operations_correctly()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Player created successfully', Mockery::type('array'));

        $user = User::factory()->create();
        $playerData = [
            'user_id' => $user->id,
            'team_id' => $this->testTeam->id,
            'primary_position' => 'PG',
            'jersey_number' => 15,
        ];

        $player = $this->playerService->createPlayer($playerData);

        $this->assertInstanceOf(Player::class, $player);
    }

    /** @test */
    public function it_validates_player_data_before_creation()
    {
        $invalidData = [
            'user_id' => 999999, // Non-existent user
            'team_id' => $this->testTeam->id,
            'primary_position' => '',  // Empty position
        ];

        $this->expectException(\Exception::class);

        $this->playerService->createPlayer($invalidData);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}