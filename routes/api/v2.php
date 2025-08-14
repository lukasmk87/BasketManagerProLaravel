<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V2\{
    ClubController,
    TeamController,
    PlayerController,
    UserController,
    EmergencyContactController
};
use App\Http\Controllers\Api\ShotChartController;

/*
|--------------------------------------------------------------------------
| API V2 Routes
|--------------------------------------------------------------------------
|
| Modern API routes for version 2.0 with full Basketball features,
| enhanced statistics, and comprehensive data management.
|
*/

// Authentication required for all V2 routes
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | Core Resource Management
    |--------------------------------------------------------------------------
    */
    
    // User Management
    Route::apiResource('users', UserController::class);
    
    // Club Management
    Route::apiResource('clubs', ClubController::class);
    Route::get('clubs/{club}/teams', [ClubController::class, 'teams']);
    Route::get('clubs/{club}/players', [ClubController::class, 'players']);
    Route::get('clubs/{club}/statistics', [ClubController::class, 'statistics']);
    
    // Team Management
    Route::apiResource('teams', TeamController::class);
    Route::get('teams/{team}/players', [TeamController::class, 'players']);
    Route::post('teams/{team}/players', [TeamController::class, 'addPlayer']);
    Route::delete('teams/{team}/players/{player}', [TeamController::class, 'removePlayer']);
    Route::get('teams/{team}/games', [TeamController::class, 'games']);
    Route::get('teams/{team}/statistics', [TeamController::class, 'statistics']);
    
    // Player Management
    Route::apiResource('players', PlayerController::class);
    Route::get('players/{player}/games', [PlayerController::class, 'games']);
    Route::get('players/{player}/statistics', [PlayerController::class, 'statistics']);
    Route::get('players/{player}/career-stats', [PlayerController::class, 'careerStats']);
    
    /*
    |--------------------------------------------------------------------------
    | Shot Chart & Analytics APIs
    |--------------------------------------------------------------------------
    */
    
    // Game Shot Charts
    Route::prefix('games/{game}')->group(function () {
        Route::get('shot-chart', [ShotChartController::class, 'getGameShotChart'])
            ->name('api.games.shot-chart');
        
        // Additional game analytics could go here
        Route::get('heat-map', [ShotChartController::class, 'getGameShotChart'])
            ->defaults('view_mode', 'heatmap')
            ->name('api.games.heat-map');
    });
    
    // Player Shot Charts
    Route::prefix('players/{player}')->group(function () {
        Route::get('shot-chart', [ShotChartController::class, 'getPlayerShotChart'])
            ->name('api.players.shot-chart');
        
        Route::get('heat-map', [ShotChartController::class, 'getPlayerShotChart'])
            ->defaults('view_mode', 'heatmap')
            ->name('api.players.heat-map');
        
        // Player shooting zones analysis
        Route::get('shooting-zones', [ShotChartController::class, 'getPlayerShotChart'])
            ->defaults('include_zones', true)
            ->name('api.players.shooting-zones');
    });
    
    // Team Shot Charts
    Route::prefix('teams/{team}')->group(function () {
        Route::get('shot-chart', [ShotChartController::class, 'getTeamShotChart'])
            ->name('api.teams.shot-chart');
        
        Route::get('heat-map', [ShotChartController::class, 'getTeamShotChart'])
            ->defaults('view_mode', 'heatmap')
            ->name('api.teams.heat-map');
        
        // Team shooting analysis
        Route::get('shooting-analysis', [ShotChartController::class, 'getTeamShotChart'])
            ->defaults('include_analysis', true)
            ->name('api.teams.shooting-analysis');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Emergency Contact Management
    |--------------------------------------------------------------------------
    */
    
    Route::apiResource('emergency-contacts', EmergencyContactController::class);
    
    /*
    |--------------------------------------------------------------------------
    | Additional Basketball Analytics
    |--------------------------------------------------------------------------
    */
    
    // Advanced Statistics Endpoints
    Route::prefix('analytics')->name('api.analytics.')->group(function () {
        
        // Shooting efficiency analysis
        Route::get('shooting-efficiency/{model}/{id}', function($model, $id) {
            // This could be expanded into a dedicated controller
            return response()->json(['message' => 'Advanced analytics coming soon']);
        })->where(['model' => 'player|team|game', 'id' => '[0-9]+']);
        
        // Performance trends
        Route::get('performance-trends/{model}/{id}', function($model, $id) {
            return response()->json(['message' => 'Performance trends coming soon']);
        })->where(['model' => 'player|team', 'id' => '[0-9]+']);
        
        // Shot prediction models
        Route::get('shot-predictions/{player}', function($player) {
            return response()->json(['message' => 'Shot predictions coming soon']);
        });
    });
    
    /*
    |--------------------------------------------------------------------------
    | Live Data & Real-time Updates
    |--------------------------------------------------------------------------
    */
    
    // Real-time shot chart updates
    Route::prefix('live')->name('api.live.')->group(function () {
        
        Route::get('shot-chart/{game}', function($game) {
            // This could stream real-time shot data
            return response()->json(['message' => 'Live shot chart updates coming soon']);
        });
        
        Route::get('player-stats/{player}', function($player) {
            return response()->json(['message' => 'Live player stats coming soon']);
        });
    });
});

/*
|--------------------------------------------------------------------------
| Public V2 Routes (No authentication required)
|--------------------------------------------------------------------------
*/

Route::prefix('public')->name('api.public.')->group(function () {
    
    // Public shot charts for sharing
    Route::get('shot-chart/game/{game}/share/{token}', function($game, $token) {
        // This would allow sharing shot charts publicly with a token
        return response()->json(['message' => 'Public shot chart sharing coming soon']);
    });
    
    // Public player highlights
    Route::get('player/{player}/highlights', function($player) {
        return response()->json(['message' => 'Public player highlights coming soon']);
    });
});

/*
|--------------------------------------------------------------------------
| Export & Integration Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'tenant'])->prefix('export')->name('api.export.')->group(function () {
    
    // Export shot chart data
    Route::get('shot-chart/{model}/{id}', function($model, $id) {
        // This could export shot chart data in various formats
        return response()->json(['message' => 'Shot chart export coming soon']);
    })->where(['model' => 'player|team|game', 'id' => '[0-9]+']);
    
    // Export analytics reports
    Route::get('analytics-report/{model}/{id}', function($model, $id) {
        return response()->json(['message' => 'Analytics report export coming soon']);
    })->where(['model' => 'player|team|game', 'id' => '[0-9]+']);
});