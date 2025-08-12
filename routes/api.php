<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\PlayerController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\GameStatisticController;
use App\Http\Controllers\Api\TrainingController;
use App\Http\Controllers\Api\DrillController;
use App\Http\Controllers\Api\TournamentController;
use App\Http\Controllers\Api\VideoFileController;
use App\Http\Controllers\Api\VideoAnnotationController;
use App\Http\Controllers\Api\MLAnalyticsController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Authentication routes
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware('auth:sanctum');
    Route::get('user', [AuthController::class, 'user'])->middleware('auth:sanctum');
});

// Protected API routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Team management
    Route::apiResource('teams', TeamController::class);
    Route::get('teams/{team}/players', [TeamController::class, 'players']);
    Route::get('teams/{team}/games', [TeamController::class, 'games']);
    Route::get('teams/{team}/statistics', [TeamController::class, 'statistics']);

    // Player management
    Route::apiResource('players', PlayerController::class);
    Route::get('players/{player}/statistics', [PlayerController::class, 'statistics']);
    Route::get('players/{player}/games', [PlayerController::class, 'games']);
    Route::post('players/{player}/transfer', [PlayerController::class, 'transfer']);

    // Game management
    Route::apiResource('games', GameController::class);
    Route::get('games/{game}/statistics', [GameController::class, 'statistics']);
    Route::post('games/{game}/start', [GameController::class, 'start']);
    Route::post('games/{game}/finish', [GameController::class, 'finish']);
    Route::get('games/{game}/live-score', [GameController::class, 'liveScore']);

    // Game statistics
    Route::apiResource('game-statistics', GameStatisticController::class);
    Route::post('game-statistics/bulk', [GameStatisticController::class, 'bulkStore']);
    Route::get('game-statistics/{game}/player/{player}', [GameStatisticController::class, 'playerGameStats']);

    // Training management
    Route::apiResource('trainings', TrainingController::class);
    Route::get('trainings/{training}/drills', [TrainingController::class, 'drills']);
    Route::post('trainings/{training}/drills/{drill}/attach', [TrainingController::class, 'attachDrill']);
    Route::delete('trainings/{training}/drills/{drill}/detach', [TrainingController::class, 'detachDrill']);
    Route::get('teams/{team}/trainings', [TrainingController::class, 'teamTrainings']);
    Route::post('trainings/{training}/duplicate', [TrainingController::class, 'duplicate']);

    // Drill management
    Route::apiResource('drills', DrillController::class);
    Route::get('drills/search', [DrillController::class, 'search']);
    Route::get('drills/categories', [DrillController::class, 'categories']);
    Route::get('drills/category/{category}', [DrillController::class, 'byCategory']);

    // Tournament management
    Route::apiResource('tournaments', TournamentController::class);
    Route::post('tournaments/{tournament}/teams/{team}', [TournamentController::class, 'addTeam']);
    Route::delete('tournaments/{tournament}/teams/{team}', [TournamentController::class, 'removeTeam']);
    Route::post('tournaments/{tournament}/generate-bracket', [TournamentController::class, 'generateBracket']);
    Route::post('tournaments/{tournament}/advance-bracket', [TournamentController::class, 'advanceBracket']);
    Route::get('tournaments/{tournament}/bracket', [TournamentController::class, 'bracket']);
    Route::get('tournaments/{tournament}/standings', [TournamentController::class, 'standings']);
    Route::post('tournaments/{tournament}/start', [TournamentController::class, 'start']);
    Route::post('tournaments/{tournament}/finish', [TournamentController::class, 'finish']);

    // Video analysis
    Route::apiResource('videos', VideoFileController::class);
    Route::post('videos/{video}/process', [VideoFileController::class, 'process']);
    Route::get('videos/{video}/download', [VideoFileController::class, 'download']);
    Route::get('videos/{video}/stream', [VideoFileController::class, 'stream']);
    Route::post('videos/{video}/analyze', [VideoFileController::class, 'analyze']);

    // Video annotations
    Route::apiResource('video-annotations', VideoAnnotationController::class);
    Route::get('videos/{video}/annotations', [VideoAnnotationController::class, 'videoAnnotations']);
    Route::post('videos/{video}/annotations/bulk', [VideoAnnotationController::class, 'bulkStore']);

    // ML Analytics Dashboard
    Route::group(['prefix' => 'ml-analytics'], function () {
        Route::get('dashboard-overview', [MLAnalyticsController::class, 'getDashboardOverview']);
        Route::get('performance-dashboard', [MLAnalyticsController::class, 'getPlayerPerformanceDashboard']);
        Route::get('injury-dashboard', [MLAnalyticsController::class, 'getInjuryRiskDashboard']);
        Route::get('experiment-dashboard', [MLAnalyticsController::class, 'getExperimentDashboard']);
        Route::get('predictions-timeline', [MLAnalyticsController::class, 'getPredictionsTimeline']);
        Route::get('model-accuracy', [MLAnalyticsController::class, 'getModelAccuracy']);
        Route::get('prediction-comparison', [MLAnalyticsController::class, 'getPredictionComparison']);
    });

    // Live Scoring API (preserved from original)
    Route::prefix('v1/games/{game}')->group(function () {
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
    
    // Game Action Management (preserved from original)
    Route::prefix('v1/actions')->group(function () {
        Route::put('/{action}', [\App\Http\Controllers\LiveScoringController::class, 'correctAction']);
        Route::delete('/{action}', [\App\Http\Controllers\LiveScoringController::class, 'deleteAction']);
    });
    
    // Real-time Data Endpoints (preserved from original)
    Route::get('v1/live-games', function () {
        return \App\Models\Game::live()
            ->with(['homeTeam', 'awayTeam', 'liveGame'])
            ->paginate(10);
    });
    
    Route::get('v1/games/{game}/actions/recent', function (\App\Models\Game $game) {
        return $game->gameActions()
            ->with(['player', 'assistedByPlayer'])
            ->latest()
            ->limit(20)
            ->get();
    });
});

// Public API Routes (no auth required)
Route::prefix('v1')->group(function () {
    // Public game data (preserved from original)
    Route::get('/games/live', function () {
        return \App\Models\Game::live()
            ->with(['homeTeam', 'awayTeam', 'liveGame'])
            ->get();
    });
    
    Route::get('/games/{game}/live-data', [\App\Http\Controllers\LiveScoringController::class, 'getLiveData'])
        ->where('game', '[0-9]+');
});

// Public routes (no authentication required)
Route::group(['prefix' => 'public'], function () {
    // Public tournament info
    Route::get('tournaments/{tournament}', [TournamentController::class, 'show']);
    Route::get('tournaments/{tournament}/bracket', [TournamentController::class, 'bracket']);
    Route::get('tournaments/{tournament}/standings', [TournamentController::class, 'standings']);
    
    // Public game info
    Route::get('games/{game}', [GameController::class, 'show']);
    Route::get('games/{game}/live-score', [GameController::class, 'liveScore']);
    
    // Public team info
    Route::get('teams/{team}', [TeamController::class, 'show']);
    Route::get('teams/{team}/players', [TeamController::class, 'players']);
    Route::get('teams/{team}/games', [TeamController::class, 'games']);
});
