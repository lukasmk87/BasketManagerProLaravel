<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TeamController;

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
    Route::get('teams/{team}/players', [TeamController::class, 'players']);
    Route::get('teams/{team}/games', [TeamController::class, 'games']);
    Route::get('teams/{team}/statistics', [TeamController::class, 'statistics']);
    Route::get('teams/{team}/analytics', [TeamController::class, 'analytics']); // V4 new
    Route::post('teams/{team}/invite-player', [TeamController::class, 'invitePlayer']); // V4 new
    Route::get('teams/{team}/performance-trends', [TeamController::class, 'performanceTrends']); // V4 new
});

// Public API Routes - Teams
Route::prefix('public')->group(function () {
    // Public team info (enhanced)
    Route::get('teams/{team}', [TeamController::class, 'show']);
    Route::get('teams/{team}/players', [TeamController::class, 'players']);
    Route::get('teams/{team}/games', [TeamController::class, 'games']);
    Route::get('teams/{team}/public-stats', [TeamController::class, 'publicStats']); // V4 new
});