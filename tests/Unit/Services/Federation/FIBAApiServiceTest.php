<?php

namespace Tests\Unit\Services\Federation;

use App\Models\Tenant;
use App\Services\Federation\FIBAApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class FIBAApiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FIBAApiService $service;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.fiba.base_url' => 'https://api.fiba.basketball/v3',
            'services.fiba.api_key' => 'test-fiba-api-key',
            'services.fiba.api_secret' => 'test-fiba-api-secret',
            'services.fiba.timeout' => 30,
            'services.fiba.retries' => 3,
        ]);

        $this->service = new FIBAApiService();
        $this->tenant = Tenant::factory()->create();

        Cache::flush();
    }

    /** @test */
    public function get_player_profile_returns_player_data_when_found(): void
    {
        Http::fake([
            '*/players/FIBA12345' => Http::response([
                'player' => [
                    'id' => 'FIBA12345',
                    'name' => 'Dirk Nowitzki',
                    'first_name' => 'Dirk',
                    'last_name' => 'Nowitzki',
                    'date_of_birth' => '1978-06-19',
                    'nationality' => 'DE',
                    'height' => 213,
                    'weight' => 111,
                    'position' => 'Power Forward',
                    'current_club' => 'Dallas Mavericks',
                    'national_team_caps' => 153,
                    'status' => 'retired',
                ],
            ], 200),
        ]);

        $result = $this->service->getPlayerProfile('FIBA12345', $this->tenant);

        $this->assertNotNull($result);
        $this->assertEquals('FIBA12345', $result['fiba_id']);
        $this->assertEquals('Dirk Nowitzki', $result['name']);
        $this->assertEquals('Dirk', $result['first_name']);
        $this->assertEquals('DE', $result['nationality']);
        $this->assertEquals(213, $result['height']);
        $this->assertEquals('Power Forward', $result['position']);
        $this->assertEquals(153, $result['national_team_caps']);
    }

    /** @test */
    public function get_player_profile_returns_null_when_not_found(): void
    {
        Http::fake([
            '*/players/*' => Http::response(['error' => 'Player not found'], 404),
        ]);

        $result = $this->service->getPlayerProfile('INVALID123', $this->tenant);

        $this->assertNull($result);
    }

    /** @test */
    public function get_player_profile_caches_result(): void
    {
        Http::fake([
            '*/players/FIBA12345' => Http::response([
                'player' => [
                    'id' => 'FIBA12345',
                    'name' => 'Test Player',
                    'nationality' => 'DE',
                ],
            ], 200),
        ]);

        // First call
        $result1 = $this->service->getPlayerProfile('FIBA12345', $this->tenant);

        // Second call should use cache
        $result2 = $this->service->getPlayerProfile('FIBA12345', $this->tenant);

        // Only one HTTP request should have been made
        Http::assertSentCount(1);
        $this->assertEquals($result1, $result2);
    }

    /** @test */
    public function search_players_returns_results(): void
    {
        Http::fake([
            '*/players/search*' => Http::response([
                'players' => [
                    ['id' => 'FIBA001', 'name' => 'Player One', 'nationality' => 'DE'],
                    ['id' => 'FIBA002', 'name' => 'Player Two', 'nationality' => 'DE'],
                ],
            ], 200),
        ]);

        $result = $this->service->searchPlayers(['nationality' => 'DE'], $this->tenant);

        $this->assertCount(2, $result);
        $this->assertEquals('FIBA001', $result[0]['id']);
        $this->assertEquals('Player One', $result[0]['name']);
    }

    /** @test */
    public function search_players_returns_empty_array_on_error(): void
    {
        Http::fake([
            '*/players/search*' => Http::response([], 500),
        ]);

        $result = $this->service->searchPlayers(['name' => 'Test'], $this->tenant);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /** @test */
    public function get_player_eligibility_returns_eligible_status(): void
    {
        Http::fake([
            '*/players/*/eligibility*' => Http::response([
                'eligible' => true,
                'nationality' => 'DE',
                'passport_countries' => ['DE'],
                'eligibility_rules' => ['FIBA Rule 3-2'],
                'restrictions' => [],
                'naturalization_status' => null,
                'previous_representations' => ['Germany'],
            ], 200),
        ]);

        $result = $this->service->getPlayerEligibility('FIBA12345', 'EUROBASKET2024', $this->tenant);

        $this->assertTrue($result['eligible']);
        $this->assertEquals('DE', $result['nationality']);
        $this->assertContains('DE', $result['passport_countries']);
        $this->assertEmpty($result['restrictions']);
    }

    /** @test */
    public function get_player_eligibility_returns_ineligible_on_failure(): void
    {
        Http::fake([
            '*/players/*/eligibility*' => Http::response([], 500),
        ]);

        $result = $this->service->getPlayerEligibility('FIBA12345', 'EUROBASKET2024', $this->tenant);

        $this->assertFalse($result['eligible']);
        $this->assertEquals('Eligibility service unavailable', $result['reason']);
    }

    /** @test */
    public function get_competitions_returns_competition_list(): void
    {
        Http::fake([
            '*/competitions*' => Http::response([
                'competitions' => [
                    ['id' => 'EUROBASKET', 'name' => 'EuroBasket', 'category' => 'men'],
                    ['id' => 'EUROLEAGUE', 'name' => 'EuroLeague', 'category' => 'men'],
                    ['id' => 'EUROCUP', 'name' => 'EuroCup', 'category' => 'men'],
                ],
            ], 200),
        ]);

        $result = $this->service->getCompetitions('men', null, $this->tenant);

        $this->assertCount(3, $result);
        $this->assertEquals('EUROBASKET', $result[0]['id']);
        $this->assertEquals('EuroBasket', $result[0]['name']);
    }

    /** @test */
    public function get_competitions_caches_results(): void
    {
        Http::fake([
            '*/competitions*' => Http::response([
                'competitions' => [
                    ['id' => 'EUROBASKET', 'name' => 'EuroBasket'],
                ],
            ], 200),
        ]);

        // First call
        $result1 = $this->service->getCompetitions('men', 'professional', $this->tenant);

        // Second call should use cache
        $result2 = $this->service->getCompetitions('men', 'professional', $this->tenant);

        Http::assertSentCount(1);
        $this->assertEquals($result1, $result2);
    }

    /** @test */
    public function get_club_info_returns_club_data(): void
    {
        Http::fake([
            '*/clubs/CLUB001' => Http::response([
                'club' => [
                    'id' => 'CLUB001',
                    'name' => 'Alba Berlin',
                    'country' => 'Germany',
                    'federation' => 'DBB',
                    'established' => 1991,
                    'venue' => 'Mercedes-Benz Arena',
                    'competitions' => ['EuroLeague', 'BBL'],
                    'licenses' => ['A-License'],
                    'status' => 'active',
                ],
            ], 200),
        ]);

        $result = $this->service->getClubInfo('CLUB001', $this->tenant);

        $this->assertNotNull($result);
        $this->assertEquals('CLUB001', $result['id']);
        $this->assertEquals('Alba Berlin', $result['name']);
        $this->assertEquals('Germany', $result['country']);
        $this->assertEquals('Mercedes-Benz Arena', $result['venue']);
        $this->assertContains('EuroLeague', $result['competitions']);
    }

    /** @test */
    public function get_club_info_returns_null_when_not_found(): void
    {
        Http::fake([
            '*/clubs/*' => Http::response(['error' => 'Club not found'], 404),
        ]);

        $result = $this->service->getClubInfo('INVALID_CLUB', $this->tenant);

        $this->assertNull($result);
    }

    /** @test */
    public function register_team_for_competition_returns_success(): void
    {
        Http::fake([
            '*/teams/register' => Http::response([
                'team_id' => 'FIBA-TEAM-001',
                'registration_number' => 'REG-2024-EU001',
                'status' => 'registered',
                'competition' => 'EuroCup',
                'deadlines' => [
                    'roster' => '2024-09-15',
                    'documents' => '2024-09-01',
                ],
            ], 200),
        ]);

        $teamData = [
            'club_id' => 'CLUB001',
            'name' => 'Alba Berlin First Team',
            'category' => 'men',
            'coach_name' => 'Israel González',
            'coach_license' => 'FIBA-COACH-001',
            'coach_nationality' => 'ES',
            'home_venue' => 'Mercedes-Benz Arena',
            'contact_person' => 'Team Manager',
            'contact_email' => 'team@albaberlin.de',
        ];

        $result = $this->service->registerTeamForCompetition($teamData, 'EUROCUP2024', $this->tenant);

        $this->assertTrue($result['success']);
        $this->assertEquals('FIBA-TEAM-001', $result['fiba_team_id']);
        $this->assertEquals('REG-2024-EU001', $result['registration_number']);
        $this->assertEquals('EuroCup', $result['competition']);
    }

    /** @test */
    public function register_team_for_competition_handles_validation_errors(): void
    {
        Http::fake([
            '*/teams/register' => Http::response([
                'message' => 'Validation failed',
                'errors' => ['club_id' => 'Club not found in FIBA database'],
            ], 422),
        ]);

        $teamData = [
            'club_id' => 'INVALID_CLUB',
            'name' => 'Test Team',
            'category' => 'men',
            'coach_name' => 'Test Coach',
            'home_venue' => 'Test Venue',
            'contact_person' => 'Test Person',
            'contact_email' => 'test@test.com',
        ];

        $result = $this->service->registerTeamForCompetition($teamData, 'EUROCUP2024', $this->tenant);

        $this->assertFalse($result['success']);
        // HTTP 422 with retry mechanism causes exception after retries exhausted
        $this->assertEquals('Registration service unavailable', $result['error']);
    }

    /** @test */
    public function get_official_game_data_returns_game_info(): void
    {
        Http::fake([
            '*/games/*/official' => Http::response([
                'game' => [
                    'id' => 'GAME-EU-001',
                    'competition' => 'EuroLeague',
                    'round' => 'Regular Season Round 15',
                    'home_team' => ['id' => 'ALB', 'name' => 'Alba Berlin'],
                    'away_team' => ['id' => 'RMA', 'name' => 'Real Madrid'],
                    'date_time' => '2024-12-15T20:00:00Z',
                    'venue' => 'Mercedes-Benz Arena',
                    'officials' => [
                        ['name' => 'Referee One', 'role' => 'referee'],
                        ['name' => 'Referee Two', 'role' => 'umpire'],
                    ],
                    'result' => ['home_score' => 85, 'away_score' => 92],
                    'status' => 'finished',
                ],
            ], 200),
        ]);

        $result = $this->service->getOfficialGameData('GAME-EU-001', $this->tenant);

        $this->assertNotNull($result);
        $this->assertEquals('GAME-EU-001', $result['game_id']);
        $this->assertEquals('EuroLeague', $result['competition']);
        $this->assertEquals('Alba Berlin', $result['home_team']['name']);
        $this->assertEquals('Real Madrid', $result['away_team']['name']);
        $this->assertEquals('finished', $result['status']);
    }

    /** @test */
    public function get_official_game_data_returns_null_on_error(): void
    {
        Http::fake([
            '*/games/*/official' => Http::response(['error' => 'Game not found'], 404),
        ]);

        $result = $this->service->getOfficialGameData('INVALID_GAME', $this->tenant);

        $this->assertNull($result);
    }

    /** @test */
    public function get_referee_info_returns_referee_data(): void
    {
        Http::fake([
            '*/referees/REF001' => Http::response([
                'referee' => [
                    'id' => 'REF001',
                    'name' => 'Anne Panther',
                    'nationality' => 'DE',
                    'certifications' => ['FIBA', 'EuroLeague'],
                    'level' => 'FIBA Licensed',
                    'active_competitions' => ['EuroLeague', 'EuroCup'],
                    'assignments' => [],
                    'status' => 'active',
                ],
            ], 200),
        ]);

        $result = $this->service->getRefereeInfo('REF001', $this->tenant);

        $this->assertNotNull($result);
        $this->assertEquals('REF001', $result['id']);
        $this->assertEquals('Anne Panther', $result['name']);
        $this->assertEquals('DE', $result['nationality']);
        $this->assertContains('FIBA', $result['certifications']);
        $this->assertEquals('active', $result['status']);
    }

    /** @test */
    public function get_referee_info_returns_null_when_not_found(): void
    {
        Http::fake([
            '*/referees/*' => Http::response(['error' => 'Referee not found'], 404),
        ]);

        $result = $this->service->getRefereeInfo('INVALID_REF', $this->tenant);

        $this->assertNull($result);
    }

    /** @test */
    public function get_competition_standings_returns_standings(): void
    {
        Http::fake([
            '*/competitions/*/standings*' => Http::response([
                'standings' => [
                    ['position' => 1, 'team' => 'Real Madrid', 'wins' => 15, 'losses' => 3],
                    ['position' => 2, 'team' => 'Fenerbahce', 'wins' => 14, 'losses' => 4],
                    ['position' => 3, 'team' => 'Alba Berlin', 'wins' => 12, 'losses' => 6],
                ],
            ], 200),
        ]);

        $result = $this->service->getCompetitionStandings('EUROLEAGUE2024', null, $this->tenant);

        $this->assertCount(3, $result);
        $this->assertEquals(1, $result[0]['position']);
        $this->assertEquals('Real Madrid', $result[0]['team']);
    }

    /** @test */
    public function get_competition_standings_caches_results(): void
    {
        Http::fake([
            '*/competitions/*/standings*' => Http::response([
                'standings' => [
                    ['position' => 1, 'team' => 'Test Team'],
                ],
            ], 200),
        ]);

        // First call
        $result1 = $this->service->getCompetitionStandings('EUROLEAGUE2024', 'group', $this->tenant);

        // Second call should use cache
        $result2 = $this->service->getCompetitionStandings('EUROLEAGUE2024', 'group', $this->tenant);

        Http::assertSentCount(1);
        $this->assertEquals($result1, $result2);
    }

    /** @test */
    public function is_available_returns_true_when_api_responds(): void
    {
        Http::fake([
            '*/health' => Http::response(['status' => 'ok'], 200),
        ]);

        $result = $this->service->isAvailable();

        $this->assertTrue($result);
    }

    /** @test */
    public function is_available_returns_false_when_api_unavailable(): void
    {
        Http::fake([
            '*/health' => Http::response([], 503),
        ]);

        $result = $this->service->isAvailable();

        $this->assertFalse($result);
    }

    /** @test */
    public function get_api_status_returns_status_info(): void
    {
        Http::fake([
            '*/status' => Http::response([
                'status' => 'operational',
                'version' => '3.2.0',
                'regions' => ['europe', 'americas', 'asia', 'africa', 'oceania'],
            ], 200),
        ]);

        $result = $this->service->getApiStatus();

        $this->assertEquals('operational', $result['status']);
        $this->assertEquals('3.2.0', $result['version']);
    }

    /** @test */
    public function get_api_status_handles_unavailable_api(): void
    {
        Http::fake([
            '*/status' => Http::response([], 503),
        ]);

        $result = $this->service->getApiStatus();

        $this->assertEquals('unavailable', $result['status']);
        $this->assertEquals('FIBA API not responding', $result['message']);
    }
}
