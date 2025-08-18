<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TeamController;

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
    
    // Team management (enhanced with V4 features)
    Route::apiResource('teams', TeamController::class);
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