<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TeamController;

// Apply ForceJsonResponse middleware to all routes in this file
Route::middleware([\App\Http\Middleware\ForceJsonResponse::class])->group(function () {

// Debug route without middleware
Route::get('debug/test', function () {
    return response()->json([
        'message' => 'API is working without middleware!',
        'version' => '4.0',
        'timestamp' => now()->toISOString(),
        'route' => '/api/v4/debug/test'
    ]);
});

// Debug route to test authentication
Route::get('debug/auth-test', function (Request $request) {
    return response()->json([
        'message' => 'Testing auth middleware',
        'user' => auth('sanctum')->check() ? auth('sanctum')->user() : null,
        'auth_check' => auth('sanctum')->check(),
        'session_check' => auth('web')->check(),
        'headers' => $request->headers->all(),
        'timestamp' => now()->toISOString(),
        'route' => '/api/v4/debug/auth-test'
    ]);
})->middleware('auth:sanctum');

// Debug route to check team without auth
Route::get('debug/teams-no-auth', function () {
    $teams = \App\Models\Team::with('club')->take(5)->get();
    return response()->json([
        'message' => 'Teams without auth',
        'teams' => $teams->map(function($team) {
            return [
                'id' => $team->id,
                'name' => $team->name,
                'club' => $team->club?->name,
                'created_at' => $team->created_at,
            ];
        }),
        'total_teams' => \App\Models\Team::count(),
        'timestamp' => now()->toISOString(),
    ]);
});

// Debug route for session and auth status
Route::get('debug/session-auth', function (Request $request) {
    $sessionData = [];
    try {
        $sessionData = [
            'session_id' => $request->session()->getId(),
            'session_token' => $request->session()->token(),
            'has_session' => $request->hasSession(),
            'session_started' => $request->session()->isStarted(),
        ];
    } catch (\Exception $e) {
        $sessionData = ['error' => $e->getMessage()];
    }

    return response()->json([
        'message' => 'Session and Auth Debug Info',
        'timestamp' => now()->toISOString(),
        'request_info' => [
            'host' => $request->getHost(),
            'scheme' => $request->getScheme(),
            'url' => $request->url(),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'referer' => $request->header('Referer'),
        ],
        'cookies' => [
            'all_cookies' => $request->cookies->all(),
            'laravel_session' => $request->cookie('laravel_session'),
            'xsrf_token' => $request->cookie('XSRF-TOKEN'),
        ],
        'headers' => [
            'accept' => $request->header('Accept'),
            'content_type' => $request->header('Content-Type'),
            'x_requested_with' => $request->header('X-Requested-With'),
            'authorization' => $request->header('Authorization') ? 'Present' : 'Missing',
            'x_csrf_token' => $request->header('X-CSRF-TOKEN') ? 'Present' : 'Missing',
        ],
        'session' => $sessionData,
        'auth_guards' => [
            'web' => [
                'check' => auth('web')->check(),
                'user_id' => auth('web')->id(),
                'user_email' => auth('web')->user()?->email,
            ],
            'sanctum' => [
                'check' => auth('sanctum')->check(),
                'user_id' => auth('sanctum')->id(),
                'user_email' => auth('sanctum')->user()?->email,
            ],
        ],
        'sanctum_config' => [
            'stateful_domains' => config('sanctum.stateful'),
            'guard' => config('sanctum.guard'),
        ],
        'is_stateful_request' => method_exists(\Laravel\Sanctum\Sanctum::class, 'actingAs') ? 
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::fromFrontend($request) : 'Cannot determine',
    ]);
});

/*
|--------------------------------------------------------------------------
| API V4 Routes - Teams Only (Temporary)
|--------------------------------------------------------------------------
|
| Temporary file to test only the TeamController functionality
|
*/

// Modern user endpoint with enhanced data
Route::get('/user', function (Request $request) {
    $user = $request->user();
    return response()->json([
        'data' => $user,
        'meta' => [
            'api_version' => '4.0',
            'tenant_id' => $request->header('X-Tenant-ID'),
            'permissions' => $user ? $user->getAllPermissions()->pluck('name') : [],
            'subscription_tier' => $user?->subscription_tier ?? 'free'
        ]
    ]);
})->middleware('auth:sanctum');

// Protected API routes - Teams only
Route::middleware('auth:sanctum')->group(function () {
    
    // Team management (enhanced with V4 features) - Use explicit ID binding for API
    Route::apiResource('teams', TeamController::class)->parameters([
        'teams' => 'team:id'
    ]);
    Route::get('teams/{team:id}/players', [TeamController::class, 'players']);
    Route::get('teams/{team:id}/games', [TeamController::class, 'games']);
    Route::get('teams/{team:id}/statistics', [TeamController::class, 'statistics']);
    Route::get('teams/{team:id}/analytics', [TeamController::class, 'analytics']); // V4 new
    Route::post('teams/{team:id}/invite-player', [TeamController::class, 'invitePlayer']); // V4 new
    Route::get('teams/{team:id}/performance-trends', [TeamController::class, 'performanceTrends']); // V4 new
});

// Public API Routes - Teams
Route::prefix('public')->group(function () {
    // Test route for debugging
    Route::get('teams/test', function () {
        return response()->json([
            'message' => 'API is working!',
            'version' => '4.0',
            'timestamp' => now()->toISOString(),
            'route' => '/api/v4/public/teams/test'
        ]);
    });
    
    // Public team info (enhanced)
    Route::get('teams/{team:id}', [TeamController::class, 'show']);
    Route::get('teams/{team:id}/players', [TeamController::class, 'players']);
    Route::get('teams/{team:id}/games', [TeamController::class, 'games']);
    Route::get('teams/{team:id}/public-stats', [TeamController::class, 'publicStats']); // V4 new
});

}); // End ForceJsonResponse middleware group