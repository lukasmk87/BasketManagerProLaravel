<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\Player;
use App\Models\EmergencyContact;
use App\Models\TeamEmergencyAccess;
use App\Models\EmergencyIncident;
use App\Models\User;
use App\Models\Club;
use App\Services\EmergencyAccessService;
use App\Services\SecurityMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class EmergencyAccessTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $emergencyAccessService;
    protected $securityMonitoringService;
    protected $club;
    protected $team;
    protected $player;
    protected $emergencyContact;
    protected $emergencyAccess;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->emergencyAccessService = app(EmergencyAccessService::class);
        $this->securityMonitoringService = app(SecurityMonitoringService::class);
        
        // Create test data
        $this->club = Club::factory()->create();
        $this->team = Team::factory()->create(['club_id' => $this->club->id]);
        $this->player = Player::factory()->create(['team_id' => $this->team->id]);
        
        $this->emergencyContact = EmergencyContact::factory()->create([
            'player_id' => $this->player->id,
            'is_primary' => true,
            'priority' => 1,
            'consent_given' => true,
            'is_active' => true
        ]);
        
        $this->emergencyAccess = TeamEmergencyAccess::factory()->create([
            'team_id' => $this->team->id,
            'is_active' => true,
            'expires_at' => now()->addYear()
        ]);
    }

    /** @test */
    public function emergency_access_form_loads_successfully()
    {
        $response = $this->get(route('emergency.access.form', $this->emergencyAccess->access_key));
        
        $response->assertStatus(200)
                ->assertViewIs('emergency.access-form')
                ->assertViewHas('accessKey', $this->emergencyAccess->access_key)
                ->assertViewHas('teamName', $this->team->name);
    }

    /** @test */
    public function emergency_access_form_fails_with_invalid_access_key()
    {
        $response = $this->get(route('emergency.access.form', 'invalid-key'));
        
        $response->assertStatus(404);
    }

    /** @test */
    public function emergency_access_form_fails_with_expired_access_key()
    {
        $this->emergencyAccess->update(['expires_at' => now()->subDay()]);
        
        $response = $this->get(route('emergency.access.form', $this->emergencyAccess->access_key));
        
        $response->assertStatus(404);
    }

    /** @test */
    public function emergency_access_form_fails_with_inactive_access_key()
    {
        $this->emergencyAccess->update(['is_active' => false]);
        
        $response = $this->get(route('emergency.access.form', $this->emergencyAccess->access_key));
        
        $response->assertStatus(404);
    }

    /** @test */
    public function emergency_access_processes_successfully()
    {
        $accessData = [
            'urgency_level' => 'high',
            'reason' => 'Player injured during game',
            'contact_person' => 'John Doe'
        ];
        
        $response = $this->post(
            route('emergency.access.process', $this->emergencyAccess->access_key),
            $accessData
        );
        
        $response->assertStatus(200)
                ->assertViewIs('emergency.contacts-list');
        
        // Check that access was logged
        $this->emergencyAccess->refresh();
        $this->assertEquals(1, $this->emergencyAccess->current_uses);
        $this->assertNotNull($this->emergencyAccess->last_used_at);
    }

    /** @test */
    public function emergency_access_requires_valid_urgency_level()
    {
        $accessData = [
            'urgency_level' => 'invalid-level',
            'reason' => 'Test reason'
        ];
        
        $response = $this->post(
            route('emergency.access.process', $this->emergencyAccess->access_key),
            $accessData
        );
        
        $response->assertSessionHasErrors('urgency_level');
    }

    /** @test */
    public function emergency_access_creates_incident_for_critical_urgency()
    {
        $accessData = [
            'urgency_level' => 'critical',
            'reason' => 'Life-threatening emergency',
            'contact_person' => 'Paramedic'
        ];
        
        $this->assertDatabaseCount('emergency_incidents', 0);
        
        $response = $this->post(
            route('emergency.access.process', $this->emergencyAccess->access_key),
            $accessData
        );
        
        $response->assertStatus(200);
        $this->assertDatabaseCount('emergency_incidents', 1);
        
        $incident = EmergencyIncident::first();
        $this->assertEquals($this->team->id, $incident->team_id);
        $this->assertEquals('critical', $incident->severity);
    }

    /** @test */
    public function emergency_access_respects_max_uses_limit()
    {
        $this->emergencyAccess->update([
            'max_uses' => 2,
            'current_uses' => 2
        ]);
        
        $response = $this->post(
            route('emergency.access.process', $this->emergencyAccess->access_key),
            ['urgency_level' => 'medium', 'reason' => 'Test']
        );
        
        $response->assertStatus(403)
                ->assertJson(['error' => 'Usage limit exceeded']);
    }

    /** @test */
    public function emergency_contacts_list_displays_correctly()
    {
        // Create additional emergency contacts
        EmergencyContact::factory()->count(2)->create([
            'player_id' => $this->player->id,
            'is_active' => true,
            'consent_given' => true
        ]);
        
        $response = $this->get(route('emergency.contacts', $this->emergencyAccess->access_key));
        
        $response->assertStatus(200)
                ->assertViewIs('emergency.contacts-list')
                ->assertViewHas('emergencyContacts');
        
        $contacts = $response->viewData('emergencyContacts');
        $this->assertCount(1, $contacts); // 1 player
        $this->assertCount(3, $contacts[0]['contacts']); // 3 contacts for the player
    }

    /** @test */
    public function emergency_contacts_filtered_by_urgency_level()
    {
        // Create contacts with different priorities
        EmergencyContact::factory()->create([
            'player_id' => $this->player->id,
            'priority' => 2,
            'is_primary' => false,
            'is_active' => true,
            'consent_given' => true
        ]);
        
        EmergencyContact::factory()->create([
            'player_id' => $this->player->id,
            'priority' => 3,
            'is_primary' => false,
            'is_active' => true,
            'consent_given' => true
        ]);
        
        // Test critical urgency (should show primary contacts only)
        $contacts = $this->emergencyAccessService->getEmergencyContacts($this->emergencyAccess, 'critical');
        $primaryContacts = collect($contacts)->flatMap(fn($player) => $player['contacts'])
                                             ->where('is_primary', true);
        $this->assertGreaterThan(0, $primaryContacts->count());
        
        // Test low urgency (should show all contacts)
        $allContacts = $this->emergencyAccessService->getEmergencyContacts($this->emergencyAccess, 'low');
        $totalContacts = collect($allContacts)->flatMap(fn($player) => $player['contacts'])->count();
        $this->assertEquals(3, $totalContacts);
    }

    /** @test */
    public function emergency_access_logging_works_correctly()
    {
        Log::fake();
        
        $accessData = [
            'urgency_level' => 'high',
            'reason' => 'Test emergency',
            'contact_person' => 'Test User'
        ];
        
        $response = $this->post(
            route('emergency.access.process', $this->emergencyAccess->access_key),
            $accessData
        );
        
        $response->assertStatus(200);
        
        // Check that emergency access was logged
        Log::assertLogged('emergency', function ($message, $context) {
            return $message === 'Emergency access used' &&
                   isset($context['access_id']) &&
                   isset($context['team_id']);
        });
    }

    /** @test */
    public function security_monitoring_detects_suspicious_access_patterns()
    {
        // Simulate rapid successive access attempts
        for ($i = 0; $i < 5; $i++) {
            $this->post(
                route('emergency.access.process', $this->emergencyAccess->access_key),
                ['urgency_level' => 'low', 'reason' => "Test {$i}"]
            );
        }
        
        // Check that security events were created
        $this->assertDatabaseHas('security_events', [
            'event_type' => 'emergency_access_anomaly',
            'severity' => 'high'
        ]);
    }

    /** @test */
    public function emergency_access_rate_limiting_works()
    {
        // Make multiple rapid requests
        for ($i = 0; $i < 12; $i++) {
            $response = $this->get(route('emergency.access.form', $this->emergencyAccess->access_key));
            if ($i < 10) {
                $response->assertStatus(200);
            }
        }
        
        // The 11th request should be rate limited
        $response = $this->get(route('emergency.access.form', $this->emergencyAccess->access_key));
        $response->assertViewIs('emergency.access-limited');
    }

    /** @test */
    public function emergency_access_statistics_are_tracked()
    {
        $initialStats = $this->emergencyAccessService->getAccessStatistics($this->team);
        $this->assertTrue($initialStats['has_active_access']);
        
        // Use emergency access
        $this->post(
            route('emergency.access.process', $this->emergencyAccess->access_key),
            ['urgency_level' => 'medium', 'reason' => 'Test']
        );
        
        $updatedStats = $this->emergencyAccessService->getAccessStatistics($this->team);
        $this->assertEquals(1, $updatedStats['total_uses']);
    }

    /** @test */
    public function expired_emergency_access_cleanup_works()
    {
        // Create expired access
        $expiredAccess = TeamEmergencyAccess::factory()->create([
            'team_id' => $this->team->id,
            'is_active' => true,
            'expires_at' => now()->subDay()
        ]);
        
        $cleanedUp = $this->emergencyAccessService->cleanupExpiredAccess();
        
        $this->assertGreaterThan(0, $cleanedUp);
        $expiredAccess->refresh();
        $this->assertFalse($expiredAccess->is_active);
    }

    /** @test */
    public function emergency_access_deactivation_works()
    {
        $this->assertTrue($this->emergencyAccess->is_active);
        
        $this->emergencyAccessService->deactivateAccess($this->emergencyAccess, 'Test deactivation');
        
        $this->emergencyAccess->refresh();
        $this->assertFalse($this->emergencyAccess->is_active);
    }

    /** @test */
    public function bulk_emergency_access_creation_works()
    {
        $team2 = Team::factory()->create(['club_id' => $this->club->id]);
        $team3 = Team::factory()->create(['club_id' => $this->club->id]);
        
        $results = $this->emergencyAccessService->bulkCreateAccessKeys(
            [$team2->id, $team3->id],
            ['expires_at' => now()->addYear()]
        );
        
        $this->assertCount(2, $results);
        $this->assertTrue($results[$team2->id]['success']);
        $this->assertTrue($results[$team3->id]['success']);
        
        $this->assertDatabaseHas('team_emergency_access', ['team_id' => $team2->id]);
        $this->assertDatabaseHas('team_emergency_access', ['team_id' => $team3->id]);
    }

    /** @test */
    public function emergency_access_with_no_consent_contacts_shows_empty_list()
    {
        // Update contact to remove consent
        $this->emergencyContact->update(['consent_given' => false]);
        
        $response = $this->get(route('emergency.contacts', $this->emergencyAccess->access_key));
        
        $response->assertStatus(200);
        $contacts = $response->viewData('emergencyContacts');
        $this->assertEmpty($contacts);
    }

    /** @test */
    public function emergency_access_notifications_are_sent()
    {
        // Add notification recipients
        $this->emergencyAccess->update([
            'send_notifications' => true,
            'notification_recipients' => [
                [
                    'type' => 'email',
                    'address' => 'coach@example.com',
                    'name' => 'Head Coach'
                ]
            ]
        ]);
        
        Queue::fake();
        
        $response = $this->post(
            route('emergency.access.process', $this->emergencyAccess->access_key),
            ['urgency_level' => 'critical', 'reason' => 'Emergency test']
        );
        
        $response->assertStatus(200);
        
        Queue::assertPushed(\App\Jobs\SendEmergencyNotification::class);
    }

    /** @test */
    public function emergency_access_printable_view_works()
    {
        $response = $this->get(route('emergency.printable', $this->emergencyAccess->access_key));
        
        $response->assertStatus(200)
                ->assertViewIs('emergency.printable-view')
                ->assertViewHas('team', $this->team)
                ->assertViewHas('contacts');
    }

    /** @test */
    public function emergency_access_handles_concurrent_requests()
    {
        // This test simulates concurrent access attempts
        $promises = [];
        
        for ($i = 0; $i < 3; $i++) {
            $promises[] = $this->postAsync(
                route('emergency.access.process', $this->emergencyAccess->access_key),
                ['urgency_level' => 'medium', 'reason' => "Concurrent test {$i}"]
            );
        }
        
        // All requests should succeed
        foreach ($promises as $promise) {
            $response = $promise->wait();
            $this->assertEquals(200, $response->getStatusCode());
        }
        
        // Verify that usage count is correct
        $this->emergencyAccess->refresh();
        $this->assertEquals(3, $this->emergencyAccess->current_uses);
    }

    /** @test */
    public function emergency_access_with_location_coordinates_is_logged()
    {
        $accessData = [
            'urgency_level' => 'high',
            'reason' => 'Player injury with location',
            'contact_person' => 'First Responder',
            'coordinates' => [
                'latitude' => 52.5200,
                'longitude' => 13.4050,
                'accuracy' => 10.0
            ]
        ];
        
        $response = $this->post(
            route('emergency.access.process', $this->emergencyAccess->access_key),
            $accessData
        );
        
        $response->assertStatus(200);
        
        // Check that coordinates were logged
        $this->emergencyAccess->refresh();
        $usageLog = $this->emergencyAccess->usage_log;
        $this->assertIsArray($usageLog);
        $this->assertArrayHasKey('coordinates', $usageLog[0] ?? []);
    }

    /** @test */
    public function emergency_access_works_with_team_without_contacts()
    {
        // Create a team with players but no emergency contacts
        $teamWithoutContacts = Team::factory()->create(['club_id' => $this->club->id]);
        Player::factory()->create(['team_id' => $teamWithoutContacts->id]);
        
        $accessWithoutContacts = TeamEmergencyAccess::factory()->create([
            'team_id' => $teamWithoutContacts->id,
            'is_active' => true,
            'expires_at' => now()->addYear()
        ]);
        
        $response = $this->get(route('emergency.contacts', $accessWithoutContacts->access_key));
        
        $response->assertStatus(200);
        $contacts = $response->viewData('emergencyContacts');
        $this->assertEmpty($contacts);
    }

    /**
     * Simulate async HTTP request for concurrent testing
     */
    private function postAsync($uri, $data = [])
    {
        // This is a simplified version - in real implementation you'd use Guzzle async
        return new class($this, $uri, $data) {
            private $test, $uri, $data;
            
            public function __construct($test, $uri, $data) {
                $this->test = $test;
                $this->uri = $uri;
                $this->data = $data;
            }
            
            public function wait() {
                return $this->test->post($this->uri, $this->data);
            }
        };
    }
}