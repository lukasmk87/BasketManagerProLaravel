<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/*
|--------------------------------------------------------------------------
| API Version Information Endpoint
|--------------------------------------------------------------------------
|
| Public endpoint that provides information about supported API versions,
| features, and migration guides.
|
*/
Route::get('/', function (Request $request) {
    // Try multiple methods to get the resolved version
    $currentVersion = $request->route()?->parameter('api_version') 
                   ?? $request->attributes->get('api_version') 
                   ?? $request->get('api_version')
                   ?? config('api.default_version');
    
    // Get only enabled versions for supported_versions
    $allVersions = config('api.versions', []);
    $supportedVersions = [];
    foreach ($allVersions as $version => $config) {
        if ($config['enabled'] ?? false) {
            $supportedVersions[] = $version;
        }
    }
    
    return response()->json([
        'api_name' => 'BasketManager Pro API',
        'current_version' => $currentVersion,
        'default_version' => config('api.default_version'),
        'supported_versions' => $supportedVersions,
        'documentation' => [
            'current' => config("api.versions.{$currentVersion}.documentation_url"),
            'all_versions' => collect($allVersions)
                ->filter(fn($config) => isset($config['documentation_url']) && ($config['enabled'] ?? false))
                ->map(fn($config, $version) => [
                    'version' => $version,
                    'url' => $config['documentation_url'],
                    'deprecated' => $config['deprecated'] ?? false
                ])
                ->values()
        ],
        'features' => config("api.versions.{$currentVersion}.features", []),
        'rate_limits' => config("api.versions.{$currentVersion}.rate_limits", []),
        'migration_guides' => [
            'v1_to_v4' => '/docs/migration/v1-to-v4',
            'v3_to_v4' => '/docs/migration/v3-to-v4'
        ],
        'status' => 'operational',
        'timestamp' => now()->toISOString()
    ]);
});


/*
|--------------------------------------------------------------------------
| Version-Specific Route Loading
|--------------------------------------------------------------------------
|
| Load routes based on the resolved API version. This allows for clean
| separation of version-specific functionality while maintaining
| backward compatibility.
|
*/

// Version 1.0 routes (legacy, deprecated)
Route::prefix('v1')->group(function () {
    require __DIR__ . '/api/v1.php';
});

// Version 2.0 routes (Shot Charts & Enhanced Features)
Route::prefix('v2')->group(function () {
    require __DIR__ . '/api/v2.php';
});

// Version 4.0 routes (current)
Route::prefix('v4')->group(function () {
    require __DIR__ . '/api/v4.php';
});

/*
|--------------------------------------------------------------------------
| Default Version Routes (No Prefix)
|--------------------------------------------------------------------------
|
| These routes use the default API version and are resolved through
| the API versioning middleware. Since version resolution happens at
| runtime, we just include both route files and let the middleware
| handle the version-specific logic in controllers.
|
*/
// No dynamic loading needed - middleware handles version resolution

/*
|--------------------------------------------------------------------------
| API Health & Status Endpoints
|--------------------------------------------------------------------------
|
| Health check and status endpoints that work across all API versions.
|
*/
Route::get('health', function () {
    return response()->json([
        'status' => 'healthy',
        'version' => config('api.default_version'),
        'timestamp' => now()->toISOString(),
        'services' => [
            'database' => 'operational',
            'cache' => 'operational',
            'storage' => 'operational'
        ]
    ]);
});

Route::get('status', function () {
    return response()->json([
        'api' => 'BasketManager Pro API',
        'status' => 'operational',
        'version' => config('api.default_version'),
        'uptime' => \Illuminate\Support\Facades\Cache::get('api_uptime', 0),
        'rate_limits' => config('api.rate_limiting.tiers'),
        'maintenance' => [
            'scheduled' => false,
            'message' => null
        ]
    ]);
});

/*
|--------------------------------------------------------------------------
| Admin Rate Limiting Routes
|--------------------------------------------------------------------------
|
| Administrative endpoints for managing rate limits, subscriptions,
| and monitoring API usage. Protected with admin authentication.
|
*/
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin/rate-limits')->group(function () {
    Route::get('dashboard', [App\Http\Controllers\Admin\RateLimitController::class, 'dashboard']);
    Route::get('user/{user}/status', [App\Http\Controllers\Admin\RateLimitController::class, 'userStatus']);
    Route::post('exceptions', [App\Http\Controllers\Admin\RateLimitController::class, 'createException']);
    Route::get('exceptions', [App\Http\Controllers\Admin\RateLimitController::class, 'exceptions']);
    Route::delete('exceptions/{exception}/revoke', [App\Http\Controllers\Admin\RateLimitController::class, 'revokeException']);
    Route::patch('exceptions/{exception}/extend', [App\Http\Controllers\Admin\RateLimitController::class, 'extendException']);
    Route::patch('user/{user}/subscription', [App\Http\Controllers\Admin\RateLimitController::class, 'updateSubscription']);
    Route::post('user/{user}/reset-quota', [App\Http\Controllers\Admin\RateLimitController::class, 'resetQuota']);
    Route::get('analytics', [App\Http\Controllers\Admin\RateLimitController::class, 'analytics']);
});
