<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TeamController;
// use App\Http\Controllers\Api\PlayerController;
// use App\Http\Controllers\Api\GameController;
// use App\Http\Controllers\Api\GameStatisticController;
// use App\Http\Controllers\Api\TrainingController;
// use App\Http\Controllers\Api\DrillController;
// use App\Http\Controllers\Api\TournamentController;
// use App\Http\Controllers\Api\VideoFileController;
// use App\Http\Controllers\Api\VideoAnnotationController;
// use App\Http\Controllers\Api\MLAnalyticsController;

/*
|--------------------------------------------------------------------------
| API V4 Routes
|--------------------------------------------------------------------------
|
| Current API routes for version 4.0. This includes all modern features
| and enterprise capabilities.
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

// Authentication routes (V4 enhanced format)
Route::group(['prefix' => 'auth'], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware('auth:sanctum');
    Route::get('user', [AuthController::class, 'user'])->middleware('auth:sanctum');
    
    // V4 new authentication endpoints
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('verify-email', [AuthController::class, 'verifyEmail'])->middleware('auth:sanctum');
    Route::post('resend-verification', [AuthController::class, 'resendVerification'])->middleware('auth:sanctum');
});

// Protected API routes - Full V4 feature set
Route::middleware('auth:sanctum')->group(function () {
    
    // Team management (enhanced with V4 features)
    Route::apiResource('teams', TeamController::class);
    Route::get('teams/{team}/players', [TeamController::class, 'players']);
    Route::get('teams/{team}/games', [TeamController::class, 'games']);
    Route::get('teams/{team}/statistics', [TeamController::class, 'statistics']);
    Route::get('teams/{team}/analytics', [TeamController::class, 'analytics']); // V4 new
    Route::post('teams/{team}/invite-player', [TeamController::class, 'invitePlayer']); // V4 new
    Route::get('teams/{team}/performance-trends', [TeamController::class, 'performanceTrends']); // V4 new

    // Player management (enhanced with V4 features)
    Route::apiResource('players', PlayerController::class);
    Route::get('players/{player}/statistics', [PlayerController::class, 'statistics']);
    Route::get('players/{player}/games', [PlayerController::class, 'games']);
    Route::post('players/{player}/transfer', [PlayerController::class, 'transfer']);
    Route::get('players/{player}/analytics', [PlayerController::class, 'analytics']); // V4 new
    Route::get('players/{player}/injury-risk', [PlayerController::class, 'injuryRisk']); // V4 new
    Route::get('players/{player}/performance-predictions', [PlayerController::class, 'performancePredictions']); // V4 new

    // Game management (enhanced with V4 features)
    Route::apiResource('games', GameController::class);
    Route::get('games/{game}/statistics', [GameController::class, 'statistics']);
    Route::post('games/{game}/start', [GameController::class, 'start']);
    Route::post('games/{game}/finish', [GameController::class, 'finish']);
    Route::get('games/{game}/live-score', [GameController::class, 'liveScore']);
    Route::get('games/{game}/real-time-data', [GameController::class, 'realTimeData']); // V4 new
    Route::post('games/{game}/broadcast', [GameController::class, 'startBroadcast']); // V4 new

    // Game statistics (enhanced)
    Route::apiResource('game-statistics', GameStatisticController::class);
    Route::post('game-statistics/bulk', [GameStatisticController::class, 'bulkStore']);
    Route::get('game-statistics/{game}/player/{player}', [GameStatisticController::class, 'playerGameStats']);
    Route::get('game-statistics/{game}/advanced-metrics', [GameStatisticController::class, 'advancedMetrics']); // V4 new

    // Training management (enhanced)
    Route::apiResource('trainings', TrainingController::class);
    Route::get('trainings/{training}/drills', [TrainingController::class, 'drills']);
    Route::post('trainings/{training}/drills/{drill}/attach', [TrainingController::class, 'attachDrill']);
    Route::delete('trainings/{training}/drills/{drill}/detach', [TrainingController::class, 'detachDrill']);
    Route::get('teams/{team}/trainings', [TrainingController::class, 'teamTrainings']);
    Route::post('trainings/{training}/duplicate', [TrainingController::class, 'duplicate']);
    Route::post('trainings/{training}/ai-recommendations', [TrainingController::class, 'aiRecommendations']); // V4 new

    // Drill management (enhanced)
    Route::apiResource('drills', DrillController::class);
    Route::get('drills/search', [DrillController::class, 'search']);
    Route::get('drills/categories', [DrillController::class, 'categories']);
    Route::get('drills/category/{category}', [DrillController::class, 'byCategory']);
    Route::get('drills/ai-generated', [DrillController::class, 'aiGenerated']); // V4 new
    Route::post('drills/{drill}/effectiveness-rating', [DrillController::class, 'rateEffectiveness']); // V4 new

    // Tournament management (enhanced)
    Route::apiResource('tournaments', TournamentController::class);
    Route::post('tournaments/{tournament}/teams/{team}', [TournamentController::class, 'addTeam']);
    Route::delete('tournaments/{tournament}/teams/{team}', [TournamentController::class, 'removeTeam']);
    Route::post('tournaments/{tournament}/generate-bracket', [TournamentController::class, 'generateBracket']);
    Route::post('tournaments/{tournament}/advance-bracket', [TournamentController::class, 'advanceBracket']);
    Route::get('tournaments/{tournament}/bracket', [TournamentController::class, 'bracket']);
    Route::get('tournaments/{tournament}/standings', [TournamentController::class, 'standings']);
    Route::post('tournaments/{tournament}/start', [TournamentController::class, 'start']);
    Route::post('tournaments/{tournament}/finish', [TournamentController::class, 'finish']);
    Route::get('tournaments/{tournament}/predictions', [TournamentController::class, 'predictions']); // V4 new
    Route::post('tournaments/{tournament}/live-stream', [TournamentController::class, 'setupLiveStream']); // V4 new

    // Video analysis (enhanced)
    Route::apiResource('videos', VideoFileController::class);
    Route::post('videos/{video}/process', [VideoFileController::class, 'process']);
    Route::get('videos/{video}/download', [VideoFileController::class, 'download']);
    Route::get('videos/{video}/stream', [VideoFileController::class, 'stream']);
    Route::post('videos/{video}/analyze', [VideoFileController::class, 'analyze']);
    Route::post('videos/{video}/ai-analysis', [VideoFileController::class, 'aiAnalysis']); // V4 new
    Route::get('videos/{video}/highlights', [VideoFileController::class, 'autoHighlights']); // V4 new

    // Video annotations (enhanced)
    Route::apiResource('video-annotations', VideoAnnotationController::class);
    Route::get('videos/{video}/annotations', [VideoAnnotationController::class, 'videoAnnotations']);
    Route::post('videos/{video}/annotations/bulk', [VideoAnnotationController::class, 'bulkStore']);
    Route::post('videos/{video}/annotations/ai-generate', [VideoAnnotationController::class, 'aiGenerate']); // V4 new

    // ML Analytics Dashboard (enhanced)
    Route::group(['prefix' => 'ml-analytics'], function () {
        Route::get('dashboard-overview', [MLAnalyticsController::class, 'getDashboardOverview']);
        Route::get('performance-dashboard', [MLAnalyticsController::class, 'getPlayerPerformanceDashboard']);
        Route::get('injury-dashboard', [MLAnalyticsController::class, 'getInjuryRiskDashboard']);
        Route::get('experiment-dashboard', [MLAnalyticsController::class, 'getExperimentDashboard']);
        Route::get('predictions-timeline', [MLAnalyticsController::class, 'getPredictionsTimeline']);
        Route::get('model-accuracy', [MLAnalyticsController::class, 'getModelAccuracy']);
        Route::get('prediction-comparison', [MLAnalyticsController::class, 'getPredictionComparison']);
        
        // V4 new ML endpoints
        Route::get('team-chemistry-analysis', [MLAnalyticsController::class, 'teamChemistryAnalysis']);
        Route::get('opponent-scouting', [MLAnalyticsController::class, 'opponentScouting']);
        Route::post('custom-model/train', [MLAnalyticsController::class, 'trainCustomModel']);
        Route::get('prediction-explanations/{prediction}', [MLAnalyticsController::class, 'explainPrediction']);
    });

    // Live Scoring API (enhanced V4)
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
        
        // V4 enhanced live scoring
        Route::post('/broadcast-event', [\App\Http\Controllers\LiveScoringController::class, 'broadcastEvent']); // V4 new
        Route::get('/real-time-metrics', [\App\Http\Controllers\LiveScoringController::class, 'realTimeMetrics']); // V4 new
    });
    
    // Game Action Management (enhanced)
    Route::prefix('actions')->group(function () {
        Route::put('/{action}', [\App\Http\Controllers\LiveScoringController::class, 'correctAction']);
        Route::delete('/{action}', [\App\Http\Controllers\LiveScoringController::class, 'deleteAction']);
        Route::get('/{action}/impact-analysis', [\App\Http\Controllers\LiveScoringController::class, 'actionImpactAnalysis']); // V4 new
    });

    // V4 NEW FEATURES - Multi-tenant & Enterprise endpoints
    
    // Webhook Management
    Route::apiResource('webhooks', \App\Http\Controllers\Api\V4\WebhookController::class);
    Route::post('webhooks/{webhook}/test', [\App\Http\Controllers\Api\V4\WebhookController::class, 'test']);
    Route::get('webhooks/{webhook}/deliveries', [\App\Http\Controllers\Api\V4\WebhookController::class, 'deliveries']);
    Route::post('webhooks/{webhook}/deliveries/{delivery}/redeliver', [\App\Http\Controllers\Api\V4\WebhookController::class, 'redeliver']);

    // Subscription Management
    Route::get('subscriptions/current', [\App\Http\Controllers\Api\V4\SubscriptionController::class, 'current']);
    Route::post('subscriptions/upgrade', [\App\Http\Controllers\Api\V4\SubscriptionController::class, 'upgrade']);
    Route::post('subscriptions/cancel', [\App\Http\Controllers\Api\V4\SubscriptionController::class, 'cancel']);
    Route::get('subscriptions/usage', [\App\Http\Controllers\Api\V4\SubscriptionController::class, 'usage']);
    Route::get('subscriptions/billing-history', [\App\Http\Controllers\Api\V4\SubscriptionController::class, 'billingHistory']);

    // External Integrations
    Route::prefix('integrations')->group(function () {
        // DBB Integration
        Route::get('dbb/teams/search', [\App\Http\Controllers\Api\V4\IntegrationController::class, 'dbbTeamSearch']);
        Route::get('dbb/players/search', [\App\Http\Controllers\Api\V4\IntegrationController::class, 'dbbPlayerSearch']);
        Route::post('dbb/sync/team/{team}', [\App\Http\Controllers\Api\V4\IntegrationController::class, 'syncWithDbb']);
        
        // FIBA Integration
        Route::get('fiba/rankings', [\App\Http\Controllers\Api\V4\IntegrationController::class, 'fibaRankings']);
        Route::get('fiba/tournaments', [\App\Http\Controllers\Api\V4\IntegrationController::class, 'fibaTournaments']);
        Route::get('fiba/team/{team}/international-data', [\App\Http\Controllers\Api\V4\IntegrationController::class, 'fibaTeamData']);
    });

    // Push Notifications
    Route::prefix('notifications')->group(function () {
        Route::post('push/subscribe', [\App\Http\Controllers\Api\V4\NotificationController::class, 'subscribePush']);
        Route::delete('push/unsubscribe', [\App\Http\Controllers\Api\V4\NotificationController::class, 'unsubscribePush']);
        Route::get('preferences', [\App\Http\Controllers\Api\V4\NotificationController::class, 'getPreferences']);
        Route::put('preferences', [\App\Http\Controllers\Api\V4\NotificationController::class, 'updatePreferences']);
        Route::get('history', [\App\Http\Controllers\Api\V4\NotificationController::class, 'history']);
        Route::post('test', [\App\Http\Controllers\Api\V4\NotificationController::class, 'sendTest']);
    });

    // Advanced Analytics
    Route::prefix('analytics/advanced')->group(function () {
        Route::get('revenue-analytics', [\App\Http\Controllers\Api\V4\AdvancedAnalyticsController::class, 'revenueAnalytics']);
        Route::get('user-engagement', [\App\Http\Controllers\Api\V4\AdvancedAnalyticsController::class, 'userEngagement']);
        Route::get('feature-usage', [\App\Http\Controllers\Api\V4\AdvancedAnalyticsController::class, 'featureUsage']);
        Route::get('performance-metrics', [\App\Http\Controllers\Api\V4\AdvancedAnalyticsController::class, 'performanceMetrics']);
        Route::get('retention-analysis', [\App\Http\Controllers\Api\V4\AdvancedAnalyticsController::class, 'retentionAnalysis']);
        Route::post('custom-report', [\App\Http\Controllers\Api\V4\AdvancedAnalyticsController::class, 'generateCustomReport']);
    });
});

// Real-time Data Endpoints (enhanced)
Route::get('live-games', function () {
    return \App\Models\Game::live()
        ->with(['homeTeam', 'awayTeam', 'liveGame'])
        ->paginate(10);
})->middleware(['throttle:200,1']); // Higher rate limit for live data

Route::get('games/{game}/actions/recent', function (\App\Models\Game $game) {
    return $game->gameActions()
        ->with(['player', 'assistedByPlayer'])
        ->latest()
        ->limit(20)
        ->get();
})->middleware(['throttle:500,1']); // High frequency updates

// Public API Routes (V4 enhanced)
Route::prefix('public')->group(function () {
    // Public tournament info (enhanced)
    Route::get('tournaments/{tournament}', [TournamentController::class, 'show']);
    Route::get('tournaments/{tournament}/bracket', [TournamentController::class, 'bracket']);
    Route::get('tournaments/{tournament}/standings', [TournamentController::class, 'standings']);
    Route::get('tournaments/{tournament}/live-feed', [TournamentController::class, 'liveFeed']); // V4 new
    
    // Public game info (enhanced)
    Route::get('games/{game}', [GameController::class, 'show']);
    Route::get('games/{game}/live-score', [GameController::class, 'liveScore']);
    Route::get('games/{game}/live-feed', [GameController::class, 'liveFeed']); // V4 new
    
    // Public team info (enhanced)
    Route::get('teams/{team}', [TeamController::class, 'show']);
    Route::get('teams/{team}/players', [TeamController::class, 'players']);
    Route::get('teams/{team}/games', [TeamController::class, 'games']);
    Route::get('teams/{team}/public-stats', [TeamController::class, 'publicStats']); // V4 new

    // Public live data feed
    Route::get('games/{game}/live-data', [\App\Http\Controllers\LiveScoringController::class, 'getLiveData'])
        ->where('game', '[0-9]+');
    
    // V4 Public API endpoints
    Route::get('league/standings', [\App\Http\Controllers\Api\V4\PublicController::class, 'leagueStandings']);
    Route::get('league/top-performers', [\App\Http\Controllers\Api\V4\PublicController::class, 'topPerformers']);
    Route::get('schedule/upcoming', [\App\Http\Controllers\Api\V4\PublicController::class, 'upcomingGames']);
    Route::get('statistics/league-leaders', [\App\Http\Controllers\Api\V4\PublicController::class, 'leagueLeaders']);
});