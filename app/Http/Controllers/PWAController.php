<?php

namespace App\Http\Controllers;

use App\Services\PWAService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * PWA Controller
 * 
 * Handles Progressive Web App requests including service worker delivery,
 * offline page rendering, and PWA status endpoints
 */
class PWAController extends Controller
{
    private PWAService $pwaService;

    public function __construct(PWAService $pwaService)
    {
        $this->pwaService = $pwaService;
    }

    /**
     * Serve the service worker with tenant-specific configuration
     *
     * @param Request $request
     * @return Response
     */
    public function serviceWorker(Request $request): Response
    {
        $tenantId = $request->user()?->tenant_id ?? 
                   session('tenant_id') ?? 
                   $request->header('X-Tenant-ID');

        $serviceWorkerContent = $this->pwaService->generateServiceWorker($tenantId);

        return response($serviceWorkerContent)
            ->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Serve the PWA manifest with tenant-specific branding
     *
     * @param Request $request
     * @return Response
     */
    public function manifest(Request $request): Response
    {
        $tenantId = $request->user()?->tenant_id ?? 
                   session('tenant_id') ?? 
                   $request->header('X-Tenant-ID');

        $manifest = $this->generateTenantManifest($tenantId);

        return response()->json($manifest)
            ->header('Content-Type', 'application/manifest+json')
            ->header('Cache-Control', 'public, max-age=3600'); // Cache for 1 hour
    }

    /**
     * Show the offline page
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function offline(Request $request)
    {
        $tenantId = $request->user()?->tenant_id ?? 
                   session('tenant_id') ?? 
                   $request->header('X-Tenant-ID');

        $tenant = null;
        if ($tenantId) {
            $tenant = \App\Models\Tenant::find($tenantId);
        }

        return view('offline', [
            'tenant' => $tenant,
            'features' => $this->getOfflineFeatures($tenant)
        ]);
    }

    /**
     * Get PWA installation status
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function status(Request $request)
    {
        $tenantId = $request->user()?->tenant_id ?? 
                   session('tenant_id') ?? 
                   $request->header('X-Tenant-ID');

        $status = $this->pwaService->getInstallationStatus($tenantId);

        return response()->json([
            'pwa_ready' => $this->isPWAReady($status),
            'details' => $status,
            'service_worker_version' => $this->pwaService->getServiceWorkerVersion(),
            'tenant_id' => $tenantId
        ]);
    }

    /**
     * Queue offline data for background sync
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function queueOfflineData(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:game_stats,player_data,training_data,federation_sync',
            'data' => 'required|array'
        ]);

        $tenantId = $request->user()?->tenant_id ?? 
                   session('tenant_id') ?? 
                   $request->header('X-Tenant-ID');

        $success = $this->pwaService->queueOfflineData(
            $request->input('type'),
            $request->input('data'),
            $tenantId
        );

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Data queued for sync when online'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to queue offline data'
        ], 500);
    }

    /**
     * Process offline data queue
     *
     * @param Request $request
     * @param string $type
     * @return \Illuminate\Http\JsonResponse
     */
    public function processOfflineQueue(Request $request, string $type)
    {
        $tenantId = $request->user()?->tenant_id ?? 
                   session('tenant_id') ?? 
                   $request->header('X-Tenant-ID');

        $result = $this->pwaService->processOfflineQueue($type, $tenantId);

        return response()->json([
            'success' => true,
            'processed' => count($result['processed']),
            'failed' => count($result['failed']),
            'remaining' => $result['remaining']
        ]);
    }

    /**
     * Clear PWA caches
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearCaches(Request $request)
    {
        $tenantId = $request->user()?->tenant_id ?? 
                   session('tenant_id') ?? 
                   $request->header('X-Tenant-ID');

        $success = $this->pwaService->clearCaches($tenantId);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Caches cleared successfully' : 'Failed to clear caches'
        ], $success ? 200 : 500);
    }

    /**
     * Update service worker version (force refresh)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateServiceWorker(Request $request)
    {
        $newVersion = $this->pwaService->updateServiceWorkerVersion();

        return response()->json([
            'success' => true,
            'new_version' => $newVersion,
            'message' => 'Service worker version updated'
        ]);
    }

    /**
     * Handle push notification subscription
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function subscribePushNotifications(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|url',
            'keys' => 'required|array',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string'
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        try {
            // Store push subscription in database
            $user->pushSubscriptions()->updateOrCreate(
                ['endpoint' => $request->input('endpoint')],
                [
                    'p256dh_key' => $request->input('keys.p256dh'),
                    'auth_token' => $request->input('keys.auth'),
                    'user_agent' => $request->userAgent(),
                    'is_active' => true
                ]
            );

            Log::info('PWA: Push notification subscription created', [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'endpoint' => $request->input('endpoint')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Push notification subscription saved'
            ]);
        } catch (\Exception $e) {
            Log::error('PWA: Failed to save push subscription', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save subscription'
            ], 500);
        }
    }

    /**
     * Generate tenant-specific manifest
     *
     * @param string|null $tenantId
     * @return array
     */
    private function generateTenantManifest(?string $tenantId): array
    {
        $defaultManifest = [
            'name' => 'BasketManager Pro',
            'short_name' => 'BasketManager',
            'description' => 'Professional Basketball Club Management System',
            'start_url' => '/',
            'display' => 'standalone',
            'background_color' => '#1a202c',
            'theme_color' => '#2d3748',
            'orientation' => 'portrait-primary'
        ];

        if (!$tenantId) {
            return $defaultManifest;
        }

        try {
            $tenant = \App\Models\Tenant::find($tenantId);
            if (!$tenant) {
                return $defaultManifest;
            }

            $branding = $tenant->branding ?? [];
            
            return array_merge($defaultManifest, [
                'name' => $branding['app_name'] ?? ($tenant->name . ' - BasketManager Pro'),
                'short_name' => $branding['short_name'] ?? $tenant->name,
                'description' => $branding['description'] ?? "Basketball management for {$tenant->name}",
                'theme_color' => $branding['primary_color'] ?? '#2d3748',
                'background_color' => $branding['background_color'] ?? '#1a202c',
                'start_url' => $tenant->getUrl() ?: '/',
                'scope' => $tenant->getUrl() ?: '/',
                'icons' => $this->getTenantIcons($tenant)
            ]);
        } catch (\Exception $e) {
            Log::error('PWA: Failed to generate tenant manifest', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
            
            return $defaultManifest;
        }
    }

    /**
     * Get tenant-specific icons
     *
     * @param \App\Models\Tenant $tenant
     * @return array
     */
    private function getTenantIcons(\App\Models\Tenant $tenant): array
    {
        $defaultIcons = [
            ['src' => '/images/logo-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
            ['src' => '/images/logo-512.png', 'sizes' => '512x512', 'type' => 'image/png']
        ];

        $branding = $tenant->branding ?? [];
        if (empty($branding['icons'])) {
            return $defaultIcons;
        }

        return $branding['icons'];
    }

    /**
     * Get offline features for tenant
     *
     * @param \App\Models\Tenant|null $tenant
     * @return array
     */
    private function getOfflineFeatures(?\App\Models\Tenant $tenant): array
    {
        $defaultFeatures = [
            'Spielstatistiken einsehen',
            'Spielerprofil verwalten', 
            'Trainingseinheiten planen',
            'Taktische Übungen durchführen'
        ];

        if (!$tenant) {
            return $defaultFeatures;
        }

        $features = $defaultFeatures;

        if ($tenant->hasFeature('live_scoring')) {
            $features[] = 'Live-Scoring (offline erfassung)';
        }

        if ($tenant->hasFeature('advanced_stats')) {
            $features[] = 'Erweiterte Statistiken';
        }

        if ($tenant->hasFeature('video_analysis')) {
            $features[] = 'Video-Analyse (cached videos)';
        }

        if ($tenant->hasFeature('player_tracking')) {
            $features[] = 'Spieler-Tracking';
        }

        return $features;
    }

    /**
     * Check if PWA is ready for installation
     *
     * @param array $status
     * @return bool
     */
    private function isPWAReady(array $status): bool
    {
        return $status['service_worker_registered'] &&
               $status['manifest_valid'] &&
               $status['offline_page_available'];
    }
}