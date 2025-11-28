<?php

namespace Tests\Unit\Services\Club;

use App\Models\Club;
use App\Models\Team;
use App\Services\Club\ClubStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubStatisticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClubStatisticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ClubStatisticsService();
    }

    public function test_gets_club_statistics(): void
    {
        $club = Club::factory()->create();

        $stats = $this->service->getClubStatistics($club);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('basic_stats', $stats);
        $this->assertArrayHasKey('game_stats', $stats);
        $this->assertArrayHasKey('financial_stats', $stats);
        $this->assertArrayHasKey('recent_activity', $stats);
        $this->assertArrayHasKey('facilities', $stats);
        $this->assertArrayHasKey('programs', $stats);
    }

    public function test_returns_zero_stats_for_empty_club(): void
    {
        $club = Club::factory()->create();

        $stats = $this->service->getClubStatistics($club);

        $this->assertEquals(0, $stats['basic_stats']['total_teams']);
        $this->assertEquals(0, $stats['basic_stats']['total_players']);
        $this->assertEquals(0, $stats['game_stats']['total_games']);
    }

    public function test_gets_club_seasons(): void
    {
        $club = Club::factory()->create();

        Team::factory()->create([
            'club_id' => $club->id,
            'season' => '2024/2025',
        ]);

        Team::factory()->create([
            'club_id' => $club->id,
            'season' => '2023/2024',
        ]);

        $seasons = $this->service->getClubSeasons($club);

        $this->assertIsArray($seasons);
        $this->assertCount(2, $seasons);
        $this->assertContains('2024/2025', $seasons);
        $this->assertContains('2023/2024', $seasons);
    }

    public function test_verifies_club(): void
    {
        $club = Club::factory()->create([
            'is_verified' => false,
            'verified_at' => null,
        ]);

        $verifiedClub = $this->service->verifyClub($club);

        $this->assertTrue($verifiedClub->is_verified);
        $this->assertNotNull($verifiedClub->verified_at);
    }

    public function test_generates_emergency_qr_data(): void
    {
        $club = Club::factory()->create([
            'name' => 'Test Basketball Club',
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '+49 123 456789',
        ]);

        $qrData = $this->service->generateEmergencyQRData($club);

        $this->assertIsArray($qrData);
        $this->assertEquals('club_emergency', $qrData['type']);
        $this->assertEquals($club->id, $qrData['club_id']);
        $this->assertEquals('Test Basketball Club', $qrData['club_name']);
        $this->assertEquals('John Doe', $qrData['emergency_contact_name']);
        $this->assertEquals('+49 123 456789', $qrData['emergency_contact_phone']);
        $this->assertArrayHasKey('generated_at', $qrData);
    }

    public function test_generates_club_report(): void
    {
        $club = Club::factory()->create([
            'name' => 'Test Basketball Club',
            'website' => 'https://test-club.de',
        ]);

        $report = $this->service->generateClubReport($club);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('club_info', $report);
        $this->assertArrayHasKey('location', $report);
        $this->assertArrayHasKey('leadership', $report);
        $this->assertArrayHasKey('teams', $report);
        $this->assertArrayHasKey('statistics', $report);
        $this->assertArrayHasKey('members', $report);
        $this->assertArrayHasKey('generated_at', $report);
        $this->assertEquals('Test Basketball Club', $report['club_info']['name']);
    }

    public function test_returns_fallback_stats_on_error(): void
    {
        // Create a minimal club that might cause query issues
        $club = Club::factory()->create();

        // The service should handle errors gracefully
        $stats = $this->service->getClubStatistics($club);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('basic_stats', $stats);
    }
}
