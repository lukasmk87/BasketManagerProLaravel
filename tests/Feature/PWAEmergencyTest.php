<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\Player;
use App\Models\EmergencyContact;
use App\Models\TeamEmergencyAccess;
use App\Models\EmergencyIncident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PWAEmergencyTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $team;
    protected $player;
    protected $emergencyContact;
    protected $emergencyAccess;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->team = Team::factory()->create();
        $this->player = Player::factory()->create(['team_id' => $this->team->id]);
        
        $this->emergencyContact = EmergencyContact::factory()->create([
            'player_id' => $this->player->id,
            'is_primary' => true,
            'is_active' => true,
            'consent_given' => true
        ]);
        
        $this->emergencyAccess = TeamEmergencyAccess::factory()->create([
            'team_id' => $this->team->id,
            'is_active' => true,
            'expires_at' => now()->addYear()
        ]);
        
        Storage::fake('public');
    }

    /** @test */
    public function emergency_pwa_manifest_generates_correctly()
    {
        $response = $this->get(route('emergency.pwa.manifest', $this->emergencyAccess->access_key));
        
        $response->assertStatus(200)
                ->assertHeader('Content-Type', 'application/json')
                ->assertJsonStructure([
                    'name',
                    'short_name',
                    'description',
                    'start_url',
                    'display',
                    'background_color',
                    'theme_color',
                    'icons',
                    'emergency_contacts',
                    'emergency_instructions',
                    'offline_capabilities'
                ]);
        
        $manifest = $response->json();
        $this->assertStringContains($this->team->name, $manifest['name']);
        $this->assertEquals('standalone', $manifest['display']);
        $this->assertCount(1, $manifest['emergency_contacts']); // One player with contacts
    }

    /** @test */
    public function emergency_pwa_manifest_fails_with_invalid_access_key()
    {
        $response = $this->get(route('emergency.pwa.manifest', 'invalid-key'));
        
        $response->assertStatus(404)
                ->assertJson(['error' => 'Access not found']);
    }

    /** @test */
    public function emergency_service_worker_serves_correctly()
    {
        $response = $this->get(route('emergency.pwa.service-worker', $this->emergencyAccess->access_key));
        
        $response->assertStatus(200)
                ->assertHeader('Content-Type', 'application/javascript');
        
        $serviceWorkerCode = $response->getContent();
        $this->assertStringContains('basketball-emergency-v1', $serviceWorkerCode);
        $this->assertStringContains('ambulance: \'112\'', $serviceWorkerCode);
        $this->assertStringContains('install', $serviceWorkerCode);
    }

    /** @test */
    public function emergency_pwa_install_page_loads()
    {
        $response = $this->get(route('emergency.pwa.install', $this->emergencyAccess->access_key));
        
        $response->assertStatus(200)
                ->assertViewIs('emergency.pwa-install')
                ->assertViewHas('accessKey', $this->emergencyAccess->access_key)
                ->assertViewHas('team', $this->team);
    }

    /** @test */
    public function emergency_offline_interface_loads()
    {
        $response = $this->get(route('emergency.pwa.offline', $this->emergencyAccess->access_key));
        
        $response->assertStatus(200)
                ->assertViewIs('emergency.offline-interface')
                ->assertViewHas('accessKey', $this->emergencyAccess->access_key)
                ->assertViewHas('team', $this->team)
                ->assertViewHas('emergencyNumbers');
        
        $emergencyNumbers = $response->viewData('emergencyNumbers');
        $this->assertEquals('112', $emergencyNumbers['ambulance']);
        $this->assertEquals('110', $emergencyNumbers['police']);
    }

    /** @test */
    public function emergency_data_caching_works()
    {
        $response = $this->get(route('emergency.pwa.cache', $this->emergencyAccess->access_key));
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'version',
                        'generated_at',
                        'access_key',
                        'team',
                        'emergency_contacts',
                        'emergency_instructions',
                        'offline_capabilities'
                    ],
                    'cached_at',
                    'expires_at'
                ]);
        
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals($this->emergencyAccess->access_key, $data['data']['access_key']);
        $this->assertCount(1, $data['data']['emergency_contacts']);
        
        // Check that data is cached
        $cacheKey = "emergency_offline_data_{$this->emergencyAccess->access_key}";
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function emergency_incident_can_be_reported_via_api()
    {
        $incidentData = [
            'access_key' => $this->emergencyAccess->access_key,
            'incident_type' => 'injury',
            'severity' => 'moderate',
            'description' => 'Player fell and twisted ankle during game',
            'location' => 'Basketball Court A',
            'player_id' => $this->player->id,
            'coordinates' => [
                'latitude' => 52.5200,
                'longitude' => 13.4050
            ],
            'timestamp' => now()->toISOString()
        ];
        
        $response = $this->post(route('api.emergency.create-incident'), $incidentData);
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'incident_id',
                    'message',
                    'created_at'
                ]);
        
        $this->assertDatabaseHas('emergency_incidents', [
            'team_id' => $this->team->id,
            'player_id' => $this->player->id,
            'incident_type' => 'injury',
            'severity' => 'moderate'
        ]);
        
        $incident = EmergencyIncident::where('team_id', $this->team->id)->first();
        $this->assertStringStartsWith('EMG-', $incident->incident_id);
    }

    /** @test */
    public function emergency_incident_requires_valid_access_key()
    {
        $incidentData = [
            'access_key' => 'invalid-key',
            'incident_type' => 'injury',
            'severity' => 'moderate',
            'description' => 'Test incident',
            'location' => 'Test location',
            'timestamp' => now()->toISOString()
        ];
        
        $response = $this->post(route('api.emergency.create-incident'), $incidentData);
        
        $response->assertStatus(401)
                ->assertJson(['error' => 'Invalid or expired access key']);
    }

    /** @test */
    public function emergency_contact_usage_can_be_logged()
    {
        $usageData = [
            'access_key' => $this->emergencyAccess->access_key,
            'contact_id' => $this->emergencyContact->id,
            'action' => 'called',
            'timestamp' => now()->toISOString()
        ];
        
        $response = $this->post(route('api.emergency.log-contact-access'), $usageData);
        
        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Contact access logged'
                ]);
        
        // Check that usage was logged in access record
        $this->emergencyAccess->refresh();
        $usageLog = $this->emergencyAccess->usage_log;
        $this->assertIsArray($usageLog);
        $this->assertCount(1, $usageLog);
        $this->assertEquals('contact_accessed', $usageLog[0]['type']);
        $this->assertEquals($this->emergencyContact->id, $usageLog[0]['contact_id']);
    }

    /** @test */
    public function emergency_contact_usage_updates_contact_record()
    {
        $usageData = [
            'access_key' => $this->emergencyAccess->access_key,
            'contact_id' => $this->emergencyContact->id,
            'action' => 'called',
            'timestamp' => now()->toISOString()
        ];
        
        $this->assertNull($this->emergencyContact->last_contacted_at);
        
        $response = $this->post(route('api.emergency.log-contact-access'), $usageData);
        
        $response->assertStatus(200);
        
        $this->emergencyContact->refresh();
        $this->assertNotNull($this->emergencyContact->last_contacted_at);
        $this->assertEquals('attempted', $this->emergencyContact->last_contact_result);
    }

    /** @test */
    public function pwa_offline_data_includes_filtered_contacts()
    {
        // Create additional contacts with different priorities
        EmergencyContact::factory()->create([
            'player_id' => $this->player->id,
            'is_primary' => false,
            'priority' => 2,
            'is_active' => true,
            'consent_given' => true
        ]);
        
        EmergencyContact::factory()->create([
            'player_id' => $this->player->id,
            'is_primary' => false,
            'priority' => 3,
            'is_active' => false, // Inactive contact
            'consent_given' => true
        ]);
        
        EmergencyContact::factory()->create([
            'player_id' => $this->player->id,
            'is_primary' => false,
            'priority' => 2,
            'is_active' => true,
            'consent_given' => false // No consent
        ]);
        
        $response = $this->get(route('emergency.pwa.cache', $this->emergencyAccess->access_key));
        
        $data = $response->json()['data'];
        $playerContacts = $data['emergency_contacts'][0]['contacts'];
        
        // Should only include active contacts with consent
        $this->assertCount(2, $playerContacts);
        
        foreach ($playerContacts as $contact) {
            $this->assertArrayHasKey('name', $contact);
            $this->assertArrayHasKey('phone', $contact);
            $this->assertArrayHasKey('priority', $contact);
            $this->assertArrayHasKey('medical_training', $contact);
        }
    }

    /** @test */
    public function pwa_offline_data_includes_emergency_instructions()
    {
        $response = $this->get(route('emergency.pwa.cache', $this->emergencyAccess->access_key));
        
        $data = $response->json()['data'];
        $instructions = $data['emergency_instructions'];
        
        $this->assertIsArray($instructions);
        $this->assertArrayHasKey('emergency_numbers', $instructions);
        $this->assertArrayHasKey('team_specific', $instructions);
        $this->assertArrayHasKey('instructions', $instructions);
        
        $this->assertEquals('112', $instructions['emergency_numbers']['ambulance']);
        $this->assertIsArray($instructions['instructions']);
        $this->assertGreaterThan(0, count($instructions['instructions']));
    }

    /** @test */
    public function pwa_caching_respects_data_privacy()
    {
        $response = $this->get(route('emergency.pwa.cache', $this->emergencyAccess->access_key));
        
        $data = $response->json()['data'];
        $contacts = $data['emergency_contacts'][0]['contacts'];
        
        foreach ($contacts as $contact) {
            // Should not include sensitive encrypted fields in raw form
            $this->assertArrayNotHasKey('email', $contact);
            $this->assertArrayNotHasKey('address', $contact);
            $this->assertArrayNotHasKey('medical_notes', $contact);
            
            // Should include formatted phone number (display format)
            $this->assertArrayHasKey('phone', $contact);
            $this->assertIsString($contact['phone']);
        }
    }

    /** @test */
    public function pwa_manifest_includes_proper_icons()
    {
        $response = $this->get(route('emergency.pwa.manifest', $this->emergencyAccess->access_key));
        
        $manifest = $response->json();
        $this->assertArrayHasKey('icons', $manifest);
        $this->assertIsArray($manifest['icons']);
        
        $icons = $manifest['icons'];
        $this->assertCount(2, $icons);
        
        $icon192 = collect($icons)->firstWhere('sizes', '192x192');
        $this->assertNotNull($icon192);
        $this->assertEquals('image/png', $icon192['type']);
        $this->assertStringContains('emergency', $icon192['src']);
        
        $icon512 = collect($icons)->firstWhere('sizes', '512x512');
        $this->assertNotNull($icon512);
        $this->assertEquals('image/png', $icon512['type']);
    }

    /** @test */
    public function pwa_manifest_has_emergency_theme_colors()
    {
        $response = $this->get(route('emergency.pwa.manifest', $this->emergencyAccess->access_key));
        
        $manifest = $response->json();
        
        // Emergency theme should use red colors
        $this->assertEquals('#dc2626', $manifest['background_color']);
        $this->assertEquals('#991b1b', $manifest['theme_color']);
        $this->assertEquals('portrait-primary', $manifest['orientation']);
    }

    /** @test */
    public function pwa_service_worker_includes_emergency_numbers()
    {
        $response = $this->get(route('emergency.pwa.service-worker', $this->emergencyAccess->access_key));
        
        $serviceWorkerCode = $response->getContent();
        
        // Should include hardcoded emergency numbers for offline access
        $this->assertStringContains('ambulance: \'112\'', $serviceWorkerCode);
        $this->assertStringContains('fire: \'112\'', $serviceWorkerCode);
        $this->assertStringContains('police: \'110\'', $serviceWorkerCode);
        
        // Should include offline fallback logic
        $this->assertStringContains('Sie sind offline', $serviceWorkerCode);
        $this->assertStringContains('Notfallnummern sind weiterhin verfügbar', $serviceWorkerCode);
    }

    /** @test */
    public function pwa_service_worker_handles_background_sync()
    {
        $response = $this->get(route('emergency.pwa.service-worker', $this->emergencyAccess->access_key));
        
        $serviceWorkerCode = $response->getContent();
        
        // Should include background sync event listener
        $this->assertStringContains('addEventListener(\'sync\'', $serviceWorkerCode);
        $this->assertStringContains('emergency-incident-report', $serviceWorkerCode);
        
        // Should include IndexedDB handling
        $this->assertStringContains('indexedDB.open', $serviceWorkerCode);
        $this->assertStringContains('EmergencyDB', $serviceWorkerCode);
    }

    /** @test */
    public function pwa_offline_interface_caches_with_proper_headers()
    {
        $response = $this->get(route('emergency.pwa.offline', $this->emergencyAccess->access_key));
        
        $response->assertStatus(200)
                ->assertHeader('Cache-Control', 'public, max-age=31536000')
                ->assertHeader('Service-Worker-Allowed', '/');
    }

    /** @test */
    public function emergency_incident_api_validates_input_properly()
    {
        $incidentData = [
            'access_key' => $this->emergencyAccess->access_key,
            'incident_type' => 'invalid_type', // Invalid
            'severity' => 'super_critical', // Invalid
            'description' => '', // Required but empty
            'location' => str_repeat('a', 501), // Too long
            'timestamp' => 'invalid-date'
        ];
        
        $response = $this->post(route('api.emergency.create-incident'), $incidentData);
        
        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'incident_type',
                    'severity', 
                    'description',
                    'location',
                    'timestamp'
                ]);
    }

    /** @test */
    public function emergency_contact_logging_validates_contact_belongs_to_team()
    {
        // Create contact for different team
        $otherTeam = Team::factory()->create();
        $otherPlayer = Player::factory()->create(['team_id' => $otherTeam->id]);
        $otherContact = EmergencyContact::factory()->create(['player_id' => $otherPlayer->id]);
        
        $usageData = [
            'access_key' => $this->emergencyAccess->access_key,
            'contact_id' => $otherContact->id, // Different team's contact
            'action' => 'called',
            'timestamp' => now()->toISOString()
        ];
        
        $response = $this->post(route('api.emergency.log-contact-access'), $usageData);
        
        $response->assertStatus(200); // Returns success but doesn't log
        
        // Should not update usage log since contact doesn't belong to this team
        $this->emergencyAccess->refresh();
        $this->assertEmpty($this->emergencyAccess->usage_log ?? []);
    }

    /** @test */
    public function pwa_handles_expired_emergency_access()
    {
        $this->emergencyAccess->update(['expires_at' => now()->subDay()]);
        
        $response = $this->get(route('emergency.pwa.manifest', $this->emergencyAccess->access_key));
        
        $response->assertStatus(404)
                ->assertJson(['error' => 'Access not found']);
    }

    /** @test */
    public function pwa_emergency_data_cache_expires_correctly()
    {
        // Cache data
        $response = $this->get(route('emergency.pwa.cache', $this->emergencyAccess->access_key));
        $response->assertStatus(200);
        
        $cacheKey = "emergency_offline_data_{$this->emergencyAccess->access_key}";
        $this->assertTrue(Cache::has($cacheKey));
        
        // Fast forward 25 hours (cache expires after 24 hours)
        $this->travel(25)->hours();
        
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function pwa_emergency_works_with_multiple_contacts_per_player()
    {
        // Create multiple contacts for the same player
        EmergencyContact::factory()->count(3)->create([
            'player_id' => $this->player->id,
            'is_active' => true,
            'consent_given' => true
        ]);
        
        $response = $this->get(route('emergency.pwa.cache', $this->emergencyAccess->access_key));
        
        $data = $response->json()['data'];
        $playerData = $data['emergency_contacts'][0];
        
        $this->assertEquals($this->player->id, $playerData['player_id']);
        $this->assertCount(4, $playerData['contacts']); // Original + 3 new
        
        // Contacts should be sorted by priority
        $priorities = array_column($playerData['contacts'], 'priority');
        $this->assertEquals($priorities, array_values(array_sort($priorities)));
    }

    /** @test */
    public function pwa_emergency_handles_team_with_no_contacts()
    {
        // Remove all emergency contacts
        EmergencyContact::where('player_id', $this->player->id)->delete();
        
        $response = $this->get(route('emergency.pwa.cache', $this->emergencyAccess->access_key));
        
        $data = $response->json()['data'];
        $this->assertEmpty($data['emergency_contacts']);
        
        // Should still include emergency numbers and instructions
        $this->assertArrayHasKey('emergency_instructions', $data);
        $this->assertEquals('112', $data['emergency_instructions']['emergency_numbers']['ambulance']);
    }

    /** @test */
    public function pwa_emergency_incident_creates_proper_id_sequence()
    {
        // Create multiple incidents to test ID generation
        $incidentData = [
            'access_key' => $this->emergencyAccess->access_key,
            'incident_type' => 'injury',
            'severity' => 'moderate',
            'description' => 'Test incident',
            'location' => 'Test location',
            'timestamp' => now()->toISOString()
        ];
        
        $response1 = $this->post(route('api.emergency.create-incident'), $incidentData);
        $response2 = $this->post(route('api.emergency.create-incident'), $incidentData);
        
        $incident1Id = $response1->json()['incident_id'];
        $incident2Id = $response2->json()['incident_id'];
        
        $this->assertStringStartsWith('EMG-' . date('Y'), $incident1Id);
        $this->assertStringStartsWith('EMG-' . date('Y'), $incident2Id);
        $this->assertNotEquals($incident1Id, $incident2Id);
        
        // Extract sequence numbers
        $seq1 = (int) substr($incident1Id, -4);
        $seq2 = (int) substr($incident2Id, -4);
        $this->assertEquals($seq1 + 1, $seq2);
    }
}