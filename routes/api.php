<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public API Routes (no auth required)
Route::prefix('v1')->group(function () {
    // Public game data
    Route::get('/games/live', function () {
        return \App\Models\Game::live()
            ->with(['homeTeam', 'awayTeam', 'liveGame'])
            ->get();
    });
    
    Route::get('/games/{game}/live-data', [\App\Http\Controllers\LiveScoringController::class, 'getLiveData'])
        ->where('game', '[0-9]+');
});

// Authenticated API Routes
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    
    // Live Scoring API
    Route::prefix('games/{game}')->group(function () {
        Route::post('/start', [\App\Http\Controllers\LiveScoringController::class, 'startGame']);
        Route::post('/finish', [\App\Http\Controllers\LiveScoringController::class, 'finishGame']);
        Route::post('/actions', [\App\Http\Controllers\LiveScoringController::class, 'addAction']);
        Route::put('/score', [\App\Http\Controllers\LiveScoringController::class, 'updateScore']);
        Route::post('/clock', [\App\Http\Controllers\LiveScoringController::class, 'controlClock']);
        Route::post('/timeout', [\App\Http\Controllers\LiveScoringController::class, 'timeout']);
        Route::delete('/timeout', [\App\Http\Controllers\LiveScoringController::class, 'endTimeout']);
        Route::post('/substitution', [\App\Http\Controllers\LiveScoringController::class, 'substitution']);
        Route::post('/shot-clock/reset', [\App\Http\Controllers\LiveScoringController::class, 'resetShotClock']);
        Route::put('/players-on-court', [\App\Http\Controllers\LiveScoringController::class, 'updatePlayersOnCourt']);
        Route::get('/statistics', [\App\Http\Controllers\LiveScoringController::class, 'getGameStatistics']);
    });
    
    // Game Action Management
    Route::prefix('actions')->group(function () {
        Route::put('/{action}', [\App\Http\Controllers\LiveScoringController::class, 'correctAction']);
        Route::delete('/{action}', [\App\Http\Controllers\LiveScoringController::class, 'deleteAction']);
    });
    
    // Real-time Data Endpoints
    Route::get('/live-games', function () {
        return \App\Models\Game::live()
            ->with(['homeTeam', 'awayTeam', 'liveGame'])
            ->paginate(10);
    });
    
    Route::get('/games/{game}/actions/recent', function (\App\Models\Game $game) {
        return $game->gameActions()
            ->with(['player', 'assistedByPlayer'])
            ->latest()
            ->limit(20)
            ->get();
    });
});
