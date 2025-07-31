<?php

namespace Tests\Unit\Services;

use Tests\BasketballTestCase;
use App\Services\TeamService;
use App\Models\Team;
use App\Models\Club;
use App\Models\Player;
use App\Models\User;
use App\Http\Requests\TeamRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class TeamServiceTest extends BasketballTestCase
{
    use RefreshDatabase, WithFaker;

    private TeamService $teamService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teamService = app(TeamService::class);
    }

    /** @test */
    public function it_can_create_a_team_with_valid_data()
    {
        $clubId = $this->testClub->id;
        $coachId = $this->trainerUser->id;
        
        $teamData = [
            'club_id' => $clubId,
            'name' => 'Test Basketball Team',
            'short_name' => 'TBT',
            'season' => '2024-25',
            'gender' => 'mixed',
            'age_group' => 'senior',
            'league' => 'Regionalliga',
            'head_coach_id' => $coachId,
            'max_players' => 15,
            'min_players' => 8,
            'is_active' => true,
        ];

        $team = $this->teamService->createTeam($teamData);

        $this->assertInstanceOf(Team::class, $team);
        $this->assertEquals('Test Basketball Team', $team->name);
        $this->assertEquals('TBT', $team->short_name);
        $this->assertEquals($clubId, $team->club_id);
        $this->assertEquals($coachId, $team->head_coach_id);
        $this->assertEquals('2024-25', $team->season);
        $this->assertTrue($team->is_active);
        $this->assertDatabaseHas('teams', [
            'name' => 'Test Basketball Team',
            'club_id' => $clubId,
        ]);
    }

    /** @test */
    public function it_can_update_team_information()
    {
        $team = $this->testTeam;
        $originalName = $team->name;

        $updateData = [
            'name' => 'Updated Team Name',
            'league' => 'Bundesliga',
            'max_players' => 20,
        ];

        $updatedTeam = $this->teamService->updateTeam($team->id, $updateData);

        $this->assertInstanceOf(Team::class, $updatedTeam);
        $this->assertEquals('Updated Team Name', $updatedTeam->name);
        $this->assertEquals('Bundesliga', $updatedTeam->league);
        $this->assertEquals(20, $updatedTeam->max_players);
        $this->assertNotEquals($originalName, $updatedTeam->name);
        
        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'name' => 'Updated Team Name',
            'league' => 'Bundesliga',
        ]);
    }

    /** @test */
    public function it_can_delete_a_team()
    {
        $team = Team::factory()->create([
            'club_id' => $this->testClub->id,
        ]);

        $result = $this->teamService->deleteTeam($team->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('teams', ['id' => $team->id]);
    }

    /** @test */
    public function it_can_add_player_to_team()
    {
        $player = Player::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $result = $this->teamService->addPlayerToTeam($this->testTeam->id, $player->id, [
            'jersey_number' => 10,
            'position' => 'PG',
            'is_starter' => true,
        ]);

        $this->assertTrue($result);
        $this->assertDatabaseHas('team_user', [
            'team_id' => $this->testTeam->id,
            'user_id' => $player->user_id,
            'role' => 'player',
            'jersey_number' => 10,
            'is_starter' => true,
        ]);

        // Update player record
        $player->refresh();
        $this->assertEquals($this->testTeam->id, $player->team_id);
        $this->assertEquals(10, $player->jersey_number);
        $this->assertEquals('PG', $player->position);
        $this->assertTrue($player->is_starter);
    }

    /** @test */
    public function it_can_remove_player_from_team()
    {
        // Create a player and add to team
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'team_id' => $this->testTeam->id,
        ]);

        $this->testTeam->users()->attach($user, [
            'role' => 'player',
            'joined_at' => now(),
            'is_active' => true,
            'jersey_number' => 15,
        ]);

        $result = $this->teamService->removePlayerFromTeam($this->testTeam->id, $player->id);

        $this->assertTrue($result);
        
        // Check team_user relationship is deactivated
        $this->assertDatabaseHas('team_user', [
            'team_id' => $this->testTeam->id,
            'user_id' => $user->id,
            'is_active' => false,
            'left_at' => now()->toDateString(),
        ]);

        // Check player is deactivated
        $player->refresh();
        $this->assertFalse($player->is_active);
    }

    /** @test */
    public function it_can_get_team_statistics()
    {
        $team = $this->testTeam;
        
        // Update team with some statistics
        $team->update([
            'games_played' => 20,
            'games_won' => 15,
            'games_lost' => 5,
            'points_scored' => 1600,
            'points_allowed' => 1400,
        ]);

        $stats = $this->teamService->getTeamStatistics($team->id);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('basic_stats', $stats);
        $this->assertArrayHasKey('roster_stats', $stats);
        $this->assertArrayHasKey('season_stats', $stats);

        // Check basic stats
        $this->assertEquals(20, $stats['basic_stats']['games_played']);
        $this->assertEquals(15, $stats['basic_stats']['games_won']);
        $this->assertEquals(5, $stats['basic_stats']['games_lost']);
        $this->assertEquals(75.0, $stats['basic_stats']['win_percentage']);
        $this->assertEquals(80.0, $stats['basic_stats']['avg_points_scored']);
        $this->assertEquals(70.0, $stats['basic_stats']['avg_points_allowed']);
    }

    /** @test */
    public function it_can_update_team_statistics()
    {
        $team = $this->testTeam;
        
        $statsData = [
            'games_played' => 25,
            'games_won' => 18,
            'games_lost' => 7,
            'points_scored' => 2000,
            'points_allowed' => 1800,
        ];

        $result = $this->teamService->updateTeamStatistics($team->id, $statsData);

        $this->assertTrue($result);
        
        $team->refresh();
        $this->assertEquals(25, $team->games_played);
        $this->assertEquals(18, $team->games_won);
        $this->assertEquals(7, $team->games_lost);
        $this->assertEquals(2000, $team->points_scored);
        $this->assertEquals(1800, $team->points_allowed);
    }

    /** @test */
    public function it_can_generate_team_report()
    {
        $team = $this->testTeam;
        
        // Add some players to the team
        $this->createTeamRoster($team, 5);
        
        // Update team stats
        $team->update([
            'games_played' => 15,
            'games_won' => 10,
            'games_lost' => 5,
        ]);

        $report = $this->teamService->generateTeamReport($team->id);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('team_info', $report);
        $this->assertArrayHasKey('statistics', $report);
        $this->assertArrayHasKey('roster', $report);
        $this->assertArrayHasKey('performance', $report);

        // Check team info
        $this->assertEquals($team->name, $report['team_info']['name']);
        $this->assertEquals($team->season, $report['team_info']['season']);

        // Check roster info
        $this->assertGreaterThan(0, count($report['roster']));
        
        // Check performance metrics
        $this->assertArrayHasKey('win_percentage', $report['performance']);
        $this->assertEquals(66.7, $report['performance']['win_percentage']);
    }

    /** @test */
    public function it_can_get_teams_by_club()
    {
        $club = $this->testClub;
        
        // Create additional teams for the club
        Team::factory()->count(3)->create(['club_id' => $club->id]);

        $teams = $this->teamService->getTeamsByClub($club->id);

        $this->assertInstanceOf(Collection::class, $teams);
        $this->assertGreaterThanOrEqual(4, $teams->count()); // 3 new + 1 existing testTeam
        
        // Ensure all teams belong to the club
        $teams->each(function ($team) use ($club) {
            $this->assertEquals($club->id, $team->club_id);
        });
    }

    /** @test */
    public function it_can_get_active_teams_only()
    {
        $club = $this->testClub;
        
        // Create active and inactive teams
        Team::factory()->count(2)->create([
            'club_id' => $club->id,
            'is_active' => true,
        ]);
        
        Team::factory()->count(1)->create([
            'club_id' => $club->id,
            'is_active' => false,
        ]);

        $activeTeams = $this->teamService->getActiveTeams($club->id);

        $this->assertInstanceOf(Collection::class, $activeTeams);
        
        // All returned teams should be active
        $activeTeams->each(function ($team) {
            $this->assertTrue($team->is_active);
        });
    }

    /** @test */
    public function it_prevents_duplicate_jersey_numbers_in_same_team()
    {
        $player1 = Player::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);
        
        $player2 = Player::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        // Add first player with jersey number 10
        $this->teamService->addPlayerToTeam($this->testTeam->id, $player1->id, [
            'jersey_number' => 10,
            'position' => 'PG',
        ]);

        // Try to add second player with same jersey number
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Jersey number 10 is already taken');

        $this->teamService->addPlayerToTeam($this->testTeam->id, $player2->id, [
            'jersey_number' => 10,
            'position' => 'SG',
        ]);
    }

    /** @test */
    public function it_can_check_team_roster_size_limits()
    {
        $team = $this->testTeam;
        $team->update(['max_players' => 3]); // Set low limit for testing

        // Add players up to the limit
        for ($i = 1; $i <= 3; $i++) {
            $player = Player::factory()->create([
                'user_id' => User::factory()->create()->id,
            ]);
            
            $this->teamService->addPlayerToTeam($team->id, $player->id, [
                'jersey_number' => $i,
                'position' => 'PG',
            ]);
        }

        // Try to add one more player (should fail)
        $extraPlayer = Player::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Team roster is full');

        $this->teamService->addPlayerToTeam($team->id, $extraPlayer->id, [
            'jersey_number' => 4,
            'position' => 'SG',
        ]);
    }

    /** @test */
    public function it_validates_team_data_before_creation()
    {
        $invalidData = [
            'name' => '', // Empty name should fail
            'club_id' => 999999, // Non-existent club
            'season' => 'invalid-season-format',
        ];

        $this->expectException(\Exception::class);

        $this->teamService->createTeam($invalidData);
    }

    /** @test */
    public function it_can_search_teams_by_name()
    {
        // Create teams with specific names
        Team::factory()->create([
            'club_id' => $this->testClub->id,
            'name' => 'Lakers Basketball Team',
        ]);
        
        Team::factory()->create([
            'club_id' => $this->testClub->id,
            'name' => 'Warriors Squad',
        ]);

        $results = $this->teamService->searchTeams('Lakers');

        $this->assertInstanceOf(Collection::class, $results);
        $this->assertEquals(1, $results->count());
        $this->assertStringContainsString('Lakers', $results->first()->name);
    }

    /** @test */
    public function it_can_get_team_training_schedule()
    {
        $team = $this->testTeam;
        $schedule = [
            [
                'day' => 'monday',
                'start_time' => '18:00',
                'end_time' => '20:00',
                'venue' => 'Main Hall',
            ],
            [
                'day' => 'wednesday',
                'start_time' => '19:00',
                'end_time' => '21:00',
                'venue' => 'Gym 2',
            ],
        ];

        $team->update(['training_schedule' => json_encode($schedule)]);

        $retrievedSchedule = $this->teamService->getTrainingSchedule($team->id);

        $this->assertIsArray($retrievedSchedule);
        $this->assertCount(2, $retrievedSchedule);
        $this->assertEquals('monday', $retrievedSchedule[0]['day']);
        $this->assertEquals('18:00', $retrievedSchedule[0]['start_time']);
    }
}