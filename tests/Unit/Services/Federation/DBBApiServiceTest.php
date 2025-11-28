<?php

namespace Tests\Unit\Services\Federation;

use App\Models\Tenant;
use App\Services\Federation\DBBApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class DBBApiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DBBApiService $service;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.dbb.base_url' => 'https://api.basketball-bund.de/v2',
            'services.dbb.api_key' => 'test-api-key',
            'services.dbb.api_secret' => 'test-api-secret',
            'services.dbb.timeout' => 30,
            'services.dbb.retries' => 3,
        ]);

        $this->service = new DBBApiService();
        $this->tenant = Tenant::factory()->create();

        Cache::flush();
    }

    /** @test */
    public function get_player_by_license_returns_player_data_when_found(): void
    {
        Http::fake([
            '*/players/DBB12345' => Http::response([
                'license_number' => 'DBB12345',
                'first_name' => 'Max',
                'last_name' => 'Mustermann',
                'date_of_birth' => '1995-05-15',
                'nationality' => 'DE',
                'current_club' => 'BC Test Berlin',
                'position' => 'Guard',
                'status' => 'active',
            ], 200),
        ]);

        $result = $this->service->getPlayerByLicense('DBB12345', $this->tenant);

        $this->assertNotNull($result);
        $this->assertEquals('DBB12345', $result['license_number']);
        $this->assertEquals('Max', $result['first_name']);
        $this->assertEquals('Mustermann', $result['last_name']);
        $this->assertEquals('Guard', $result['position']);
    }

    /** @test */
    public function get_player_by_license_returns_null_when_not_found(): void
    {
        Http::fake([
            '*/players/*' => Http::response(['error' => 'Player not found'], 404),
        ]);

        $result = $this->service->getPlayerByLicense('INVALID123', $this->tenant);

        $this->assertNull($result);
    }

    /** @test */
    public function get_player_by_license_caches_result(): void
    {
        Http::fake([
            '*/players/DBB12345' => Http::response([
                'license_number' => 'DBB12345',
                'first_name' => 'Max',
                'last_name' => 'Mustermann',
            ], 200),
        ]);

        // First call
        $result1 = $this->service->getPlayerByLicense('DBB12345', $this->tenant);

        // Second call should use cache
        $result2 = $this->service->getPlayerByLicense('DBB12345', $this->tenant);

        // Only one HTTP request should have been made
        Http::assertSentCount(1);
        $this->assertEquals($result1, $result2);
    }

    /** @test */
    public function validate_player_eligibility_returns_eligible_status(): void
    {
        Http::fake([
            '*/players/validate' => Http::response([
                'eligible' => true,
                'reason' => null,
                'restrictions' => [],
                'valid_until' => '2025-06-30',
            ], 200),
        ]);

        $result = $this->service->validatePlayerEligibility('DBB12345', 'LEAGUE001', $this->tenant);

        $this->assertTrue($result['eligible']);
        $this->assertEmpty($result['restrictions']);
        $this->assertEquals('2025-06-30', $result['valid_until']);
    }

    /** @test */
    public function validate_player_eligibility_returns_ineligible_with_reason(): void
    {
        Http::fake([
            '*/players/validate' => Http::response([
                'eligible' => false,
                'reason' => 'Age restriction - too old for U18 league',
                'restrictions' => ['age_limit'],
            ], 200),
        ]);

        $result = $this->service->validatePlayerEligibility('DBB12345', 'U18LEAGUE', $this->tenant);

        $this->assertFalse($result['eligible']);
        $this->assertEquals('Age restriction - too old for U18 league', $result['reason']);
        $this->assertContains('age_limit', $result['restrictions']);
    }

    /** @test */
    public function validate_player_eligibility_handles_api_failure(): void
    {
        Http::fake([
            '*/players/validate' => Http::response([], 500),
        ]);

        $result = $this->service->validatePlayerEligibility('DBB12345', 'LEAGUE001', $this->tenant);

        $this->assertFalse($result['eligible']);
        // When retry mechanism exhausts all retries, an exception is thrown
        // which is caught and returns 'Validation service unavailable'
        $this->assertEquals('Validation service unavailable', $result['reason']);
    }

    /** @test */
    public function get_leagues_returns_league_list(): void
    {
        Http::fake([
            '*/leagues*' => Http::response([
                'data' => [
                    ['id' => 'BBL', 'name' => 'Bundesliga', 'level' => 1],
                    ['id' => 'REGIO', 'name' => 'Regionalliga', 'level' => 2],
                    ['id' => 'LANDES', 'name' => 'Landesliga', 'level' => 3],
                ],
            ], 200),
        ]);

        $result = $this->service->getLeagues(null, null, $this->tenant);

        $this->assertCount(3, $result);
        $this->assertEquals('BBL', $result[0]['id']);
        $this->assertEquals('Bundesliga', $result[0]['name']);
    }

    /** @test */
    public function get_leagues_filters_by_region(): void
    {
        Http::fake([
            '*/leagues*region=nord*' => Http::response([
                'data' => [
                    ['id' => 'NORD1', 'name' => 'Nordliga 1', 'region' => 'nord'],
                ],
            ], 200),
        ]);

        $result = $this->service->getLeagues('nord', null, $this->tenant);

        $this->assertNotEmpty($result);
    }

    /** @test */
    public function register_team_returns_success_response(): void
    {
        Http::fake([
            '*/teams/register' => Http::response([
                'team_id' => 'DBB-TEAM-001',
                'registration_number' => 'REG-2024-001',
                'status' => 'active',
                'valid_from' => '2024-09-01',
                'valid_until' => '2025-06-30',
            ], 200),
        ]);

        $teamData = [
            'name' => 'Test Team Berlin U18',
            'club_id' => 'CLUB001',
            'league_id' => 'U18LEAGUE',
            'age_group' => 'u18',
            'contact_person' => 'John Doe',
            'contact_email' => 'john@test.com',
            'contact_phone' => '+49123456789',
            'home_venue' => 'Sporthalle Berlin',
        ];

        $result = $this->service->registerTeam($teamData, $this->tenant);

        $this->assertTrue($result['success']);
        $this->assertEquals('DBB-TEAM-001', $result['dbb_team_id']);
        $this->assertEquals('REG-2024-001', $result['registration_number']);
    }

    /** @test */
    public function register_team_handles_validation_errors(): void
    {
        Http::fake([
            '*/teams/register' => Http::response([
                'message' => 'Validation failed',
                'errors' => ['club_id' => 'Invalid club ID'],
            ], 422),
        ]);

        $teamData = [
            'name' => 'Test Team',
            'club_id' => 'INVALID',
            'league_id' => 'LEAGUE001',
            'age_group' => 'senior',
            'contact_person' => 'John Doe',
            'contact_email' => 'john@test.com',
            'contact_phone' => '+49123456789',
            'home_venue' => 'Test Venue',
        ];

        $result = $this->service->registerTeam($teamData, $this->tenant);

        $this->assertFalse($result['success']);
        // HTTP 422 with retry mechanism causes exception after retries exhausted
        $this->assertEquals('Registration service unavailable', $result['error']);
    }

    /** @test */
    public function submit_game_result_returns_success(): void
    {
        Http::fake([
            '*/games/results' => Http::response([
                'submission_id' => 'SUB-2024-001',
                'status' => 'submitted',
                'verified' => false,
            ], 200),
        ]);

        $gameData = [
            'dbb_game_id' => 'GAME001',
            'home_score' => 85,
            'away_score' => 78,
            'played_at' => '2024-11-26T15:00:00Z',
        ];

        $result = $this->service->submitGameResult($gameData, $this->tenant);

        $this->assertTrue($result['success']);
        $this->assertEquals('SUB-2024-001', $result['submission_id']);
        $this->assertEquals('submitted', $result['status']);
    }

    /** @test */
    public function get_player_transfer_status_returns_transfer_info(): void
    {
        Http::fake([
            '*/players/*/transfers*' => Http::response([
                'current_club' => 'BC Test Berlin',
                'pending_transfers' => [],
                'transfer_window_open' => true,
                'restrictions' => [],
            ], 200),
        ]);

        $result = $this->service->getPlayerTransferStatus('DBB12345', $this->tenant);

        $this->assertNotNull($result);
        $this->assertEquals('BC Test Berlin', $result['current_club']);
        $this->assertTrue($result['transfer_window_open']);
    }

    /** @test */
    public function get_referee_assignments_returns_assignments(): void
    {
        Http::fake([
            '*/referees/assignments*' => Http::response([
                'data' => [
                    ['game_id' => 'GAME001', 'referee' => 'Hans Schmidt', 'role' => 'main'],
                    ['game_id' => 'GAME001', 'referee' => 'Peter Mueller', 'role' => 'assistant'],
                ],
            ], 200),
        ]);

        $result = $this->service->getRefereeAssignments('LEAGUE001', '2024-11-26', $this->tenant);

        $this->assertCount(2, $result);
        $this->assertEquals('Hans Schmidt', $result[0]['referee']);
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
                'version' => '2.1.0',
                'uptime' => '99.9%',
            ], 200),
        ]);

        $result = $this->service->getApiStatus();

        $this->assertEquals('operational', $result['status']);
        $this->assertEquals('2.1.0', $result['version']);
    }

    /** @test */
    public function get_api_status_handles_unavailable_api(): void
    {
        Http::fake([
            '*/status' => Http::response([], 503),
        ]);

        $result = $this->service->getApiStatus();

        $this->assertEquals('unavailable', $result['status']);
        $this->assertEquals('API not responding', $result['message']);
    }
}
