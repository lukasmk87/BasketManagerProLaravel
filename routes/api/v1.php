<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\PlayerController;
use App\Http\Controllers\Api\GameController;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
|
| Legacy API routes for version 1.0. These routes are deprecated
| and maintained for backward compatibility only.
|
*/

// Legacy user endpoint (deprecated format)
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Authentication routes (V1 format)
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware('auth:sanctum');
    Route::get('user', [AuthController::class, 'user'])->middleware('auth:sanctum');
});

// Protected API routes - Basic functionality only
Route::middleware('auth:sanctum')->group(function () {
    
    // Team management (basic endpoints only)
    Route::apiResource('teams', TeamController::class)->only(['index', 'show', 'store', 'update'])->names('api.v1.teams');
    Route::get('teams/{team}/players', [TeamController::class, 'players']);
    Route::get('teams/{team}/games', [TeamController::class, 'games']);

    // Player management (basic endpoints only)
    Route::apiResource('players', PlayerController::class)->only(['index', 'show', 'store', 'update']);
    Route::get('players/{player}/games', [PlayerController::class, 'games']);

    // Game management (basic endpoints only)
    Route::apiResource('games', GameController::class)->only(['index', 'show', 'store', 'update']);
    Route::get('games/{game}/live-score', [GameController::class, 'liveScore']);

    // Live Scoring API (legacy endpoints)
    Route::prefix('games/{game}')->group(function () {
        Route::post('/start', [\App\Http\Controllers\LiveScoringController::class, 'startGame']);
        Route::post('/finish', [\App\Http\Controllers\LiveScoringController::class, 'finishGame']);
        Route::post('/actions', [\App\Http\Controllers\LiveScoringController::class, 'addAction']);
        Route::put('/score', [\App\Http\Controllers\LiveScoringController::class, 'updateScore']);
        Route::get('/statistics', [\App\Http\Controllers\LiveScoringController::class, 'getGameStatistics']);
    });
});

// Public routes (V1 format - limited)
Route::group(['prefix' => 'public'], function () {
    // Public game info
    Route::get('games/{game}', [GameController::class, 'show']);
    Route::get('games/{game}/live-score', [GameController::class, 'liveScore']);
    
    // Public team info
    Route::get('teams/{team}', [TeamController::class, 'show']);
    Route::get('teams/{team}/players', [TeamController::class, 'players']);
});

// Legacy endpoints (deprecated but maintained for compatibility)
Route::group(['prefix' => 'legacy', 'middleware' => 'throttle:100,1'], function () {
    // These endpoints are deprecated in v1.0 and will be removed in v2.0
    Route::get('/stats/legacy', function() {
        return response()->json([
            'error' => 'This endpoint is deprecated',
            'message' => 'Please use /api/v4/statistics instead',
            'deprecated_since' => '1.0',
            'removal_date' => '2025-12-31'
        ], 410);
    });
    
    Route::get('/players/export/xml', function() {
        return response()->json([
            'error' => 'XML export is no longer supported',
            'message' => 'Please use /api/v4/players/export?format=json',
            'deprecated_since' => '1.0',
            'removal_date' => '2025-12-31'
        ], 410);
    });
});