<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\PWAService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PWAInstallationTest extends TestCase
{
    use RefreshDatabase;

    protected PWAService $pwaService;
    protected Tenant $tenant;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->pwaService = app(PWAService::class);
        
        $this->tenant = Tenant::factory()->create([
            'name' => 'Lakers Basketball Club',
            'subscription_tier' => 'professional',
        ]);
        
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    /** @test */
    public function manifest_json_is_accessible_and_valid()
    {
        $response = $this->get('/manifest.json');
        
        $response->assertOk();
        $response->assertJsonStructure([
            'name',
            'short_name', 
            'description',
            'start_url',
            'display',
            'background_color',
            'theme_color',
            'icons' => [
                '*' => ['src', 'sizes', 'type']
            ],
            'shortcuts' => [
                '*' => ['name', 'url', 'icons']
            ]
        ]);
        
        $manifest = $response->json();
        $this->assertEquals('BasketManager Pro', $manifest['name']);
        $this->assertEquals('standalone', $manifest['display']);
        $this->assertEquals('/', $manifest['start_url']);
        
        // Verify basketball-specific shortcuts
        $shortcutNames = collect($manifest['shortcuts'])->pluck('name')->toArray();
        $this->assertContains('Dashboard', $shortcutNames);
        $this->assertContains('Live Game', $shortcutNames);
        $this->assertContains('Players', $shortcutNames);
        $this->assertContains('Training', $shortcutNames);
    }

    /** @test */
    public function service_worker_is_accessible()
    {
        $response = $this->get('/sw.js');
        
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/javascript');
        
        $content = $response->getContent();
        
        // Verify service worker contains basketball-specific functionality
        $this->assertStringContains('basketball-game-stats', $content);
        $this->assertStringContains('basketball-players', $content);
        $this->assertStringContains('basketball-teams', $content);
        $this->assertStringContains('basketball-training', $content);
        
        // Verify caching strategies are defined
        $this->assertStringContains('CACHE_STRATEGIES', $content);
        $this->assertStringContains('cache-first', $content);
        $this->assertStringContains('network-first', $content);
    }

    /** @test */
    public function tenant_specific_service_worker_configuration_is_generated()
    {
        app()->instance('tenant', $this->tenant);
        
        $serviceWorker = $this->pwaService->generateServiceWorker($this->tenant->id);
        
        // Verify tenant-specific configuration is injected
        $this->assertStringContains($this->tenant->id, $serviceWorker);
        $this->assertStringContains('TENANT_CONFIG', $serviceWorker);
        
        // Verify professional tier features are enabled
        $this->assertStringContains('live_scoring', $serviceWorker);
        $this->assertStringContains('advanced_stats', $serviceWorker);
        $this->assertStringContains('video_analysis', $serviceWorker);
    }

    /** @test */
    public function pwa_installation_status_is_correctly_reported()
    {
        $status = $this->pwaService->getInstallationStatus($this->tenant->id);
        
        $this->assertArrayHasKey('service_worker_registered', $status);
        $this->assertArrayHasKey('manifest_valid', $status);
        $this->assertArrayHasKey('offline_page_available', $status);
        $this->assertArrayHasKey('push_notifications_supported', $status);
        $this->assertArrayHasKey('cache_api_available', $status);
        $this->assertArrayHasKey('background_sync_supported', $status);
        
        // Manifest should be valid (file exists)
        $this->assertTrue($status['manifest_valid']);
    }

    /** @test */
    public function offline_page_is_accessible()
    {
        $response = $this->get('/offline');
        
        $response->assertOk();
        $response->assertViewIs('offline');
        
        // Verify offline page contains basketball-specific content
        $content = $response->getContent();
        $this->assertStringContains('BasketManager', $content);
        $this->assertStringContains('offline', $content);
    }

    /** @test */
    public function pwa_controller_endpoints_work_correctly()
    {
        $this->actingAs($this->user);
        
        // Test PWA status endpoint
        $response = $this->withHeaders(['Host' => $this->tenant->domain])
                         ->getJson('/api/pwa/status');
        
        $response->assertOk();
        $response->assertJsonStructure([
            'installed',
            'service_worker_registered',
            'tenant_config'
        ]);
        
        // Test PWA configuration endpoint
        $response = $this->withHeaders(['Host' => $this->tenant->domain])
                         ->getJson('/api/pwa/config');
        
        $response->assertOk();
        $response->assertJsonStructure([
            'tenant_id',
            'cache_strategy',
            'basketball_features',
            'push_notifications'
        ]);
    }

    /** @test */
    public function basketball_specific_caching_strategies_are_implemented()
    {
        $serviceWorker = File::get(public_path('sw.js'));
        
        // Verify basketball-specific cache keys exist
        $this->assertStringContains('BASKETBALL_CACHE_KEYS', $serviceWorker);
        $this->assertStringContains('GAME_STATS', $serviceWorker);
        $this->assertStringContains('PLAYER_PROFILES', $serviceWorker);
        $this->assertStringContains('TEAM_ROSTERS', $serviceWorker);
        $this->assertStringContains('TRAINING_DRILLS', $serviceWorker);
        $this->assertStringContains('MATCH_SCHEDULES', $serviceWorker);
        
        // Verify basketball-specific handlers exist
        $this->assertStringContains('handleLiveScoring', $serviceWorker);
        $this->assertStringContains('isBasketballData', $serviceWorker);
        $this->assertStringContains('getBasketballCacheKey', $serviceWorker);
    }

    /** @test */
    public function offline_data_queueing_works_correctly()
    {
        $this->actingAs($this->user);
        app()->instance('tenant', $this->tenant);
        
        // Queue some offline basketball data
        $gameStats = [
            'game_id' => 1,
            'player_id' => 1,
            'points' => 15,
            'rebounds' => 8,
            'assists' => 5,
        ];
        
        $result = $this->pwaService->queueOfflineData('game_stats', $gameStats, $this->tenant->id);
        $this->assertTrue($result);
        
        // Process the offline queue
        $processResult = $this->pwaService->processOfflineQueue('game_stats', $this->tenant->id);
        
        $this->assertArrayHasKey('processed', $processResult);
        $this->assertArrayHasKey('failed', $processResult);
        $this->assertArrayHasKey('remaining', $processResult);
    }

    /** @test */
    public function pwa_service_worker_version_management_works()
    {
        $oldVersion = $this->pwaService->getServiceWorkerVersion();
        
        // Update service worker version
        $newVersion = $this->pwaService->updateServiceWorkerVersion();
        
        $this->assertNotEquals($oldVersion, $newVersion);
        $this->assertEquals($newVersion, $this->pwaService->getServiceWorkerVersion());
    }

    /** @test */
    public function pwa_cache_clearing_works_correctly()
    {
        app()->instance('tenant', $this->tenant);
        
        // Clear caches for specific tenant
        $result = $this->pwaService->clearCaches($this->tenant->id);
        $this->assertTrue($result);
        
        // Clear all caches
        $result = $this->pwaService->clearCaches();
        $this->assertTrue($result);
    }

    /** @test */
    public function pwa_icons_are_available_in_all_required_sizes()
    {
        $manifest = json_decode(File::get(public_path('manifest.json')), true);
        
        $requiredSizes = ['72x72', '96x96', '128x128', '144x144', '152x152', '192x192', '384x384', '512x512'];
        
        $availableSizes = collect($manifest['icons'])->pluck('sizes')->toArray();
        
        foreach ($requiredSizes as $size) {
            $this->assertContains($size, $availableSizes, "Missing icon size: {$size}");
        }
        
        // Verify maskable icons are present
        $maskableIcons = collect($manifest['icons'])
            ->where('purpose', 'any maskable')
            ->pluck('sizes')
            ->toArray();
        
        $this->assertContains('192x192', $maskableIcons);
        $this->assertContains('512x512', $maskableIcons);
    }

    /** @test */
    public function basketball_app_shortcuts_are_correctly_configured()
    {
        $manifest = json_decode(File::get(public_path('manifest.json')), true);
        
        $shortcuts = collect($manifest['shortcuts']);
        
        // Verify basketball-specific shortcuts
        $dashboardShortcut = $shortcuts->where('name', 'Dashboard')->first();
        $this->assertEquals('/dashboard', $dashboardShortcut['url']);
        
        $liveGameShortcut = $shortcuts->where('name', 'Live Game')->first();
        $this->assertEquals('/games/live', $liveGameShortcut['url']);
        
        $playersShortcut = $shortcuts->where('name', 'Players')->first();
        $this->assertEquals('/players', $playersShortcut['url']);
        
        $trainingShortcut = $shortcuts->where('name', 'Training')->first();
        $this->assertEquals('/training', $trainingShortcut['url']);
        
        // Verify all shortcuts have icons
        foreach ($shortcuts as $shortcut) {
            $this->assertArrayHasKey('icons', $shortcut);
            $this->assertNotEmpty($shortcut['icons']);
        }
    }

    /** @test */
    public function pwa_background_sync_tags_are_implemented()
    {
        $serviceWorker = File::get(public_path('sw.js'));
        
        // Verify basketball-specific sync tags
        $this->assertStringContains('game-stats-sync', $serviceWorker);
        $this->assertStringContains('player-data-sync', $serviceWorker);
        $this->assertStringContains('training-data-sync', $serviceWorker);
        
        // Verify sync handlers exist
        $this->assertStringContains('syncGameStats', $serviceWorker);
        $this->assertStringContains('syncPlayerData', $serviceWorker);
        $this->assertStringContains('syncTrainingData', $serviceWorker);
    }

    /** @test */
    public function pwa_push_notification_structure_is_correct()
    {
        $serviceWorker = File::get(public_path('sw.js'));
        
        // Verify push event listener exists
        $this->assertStringContains("addEventListener('push'", $serviceWorker);
        
        // Verify basketball-specific notification options
        $this->assertStringContains('BasketManager Pro', $serviceWorker);
        $this->assertStringContains('logo-192.png', $serviceWorker);
        $this->assertStringContains('badge-72.png', $serviceWorker);
        
        // Verify notification actions are configured
        $this->assertStringContains('actions', $serviceWorker);
        $this->assertStringContains('explore', $serviceWorker);
        $this->assertStringContains('close', $serviceWorker);
    }

    /** @test */
    public function pwa_tenant_config_respects_subscription_tiers()
    {
        // Test professional tier
        $professionalConfig = $this->pwaService->getTenantConfiguration($this->tenant->id);
        
        $this->assertEquals($this->tenant->id, $professionalConfig['tenant_id']);
        $this->assertTrue($professionalConfig['basketball_features']['live_scoring']);
        $this->assertTrue($professionalConfig['basketball_features']['advanced_stats']);
        $this->assertTrue($professionalConfig['basketball_features']['video_analysis']);
        
        // Test basic tier
        $basicTenant = Tenant::factory()->create([
            'subscription_tier' => 'basic',
        ]);
        
        $basicConfig = $this->pwaService->getTenantConfiguration($basicTenant->id);
        
        $this->assertTrue($basicConfig['basketball_features']['live_scoring']);
        $this->assertFalse($basicConfig['basketball_features']['video_analysis']); // Not in basic tier
    }
}