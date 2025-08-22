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
    Route::apiResource('clubs', ClubController::class)->names('api.v2.clubs');
    Route::get('clubs/{club}/teams', [ClubController::class, 'teams']);
    Route::get('clubs/{club}/players', [ClubController::class, 'players']);
    Route::get('clubs/{club}/statistics', [ClubController::class, 'statistics']);
    
    // Team Management
    Route::apiResource('teams', TeamController::class)->names('api.v2.teams');
    Route::get('teams/{team}/players', [TeamController::class, 'players']);
    Route::post('teams/{team}/players', [TeamController::class, 'addPlayer']);
    Route::delete('teams/{team}/players/{player}', [TeamController::class, 'removePlayer']);
    Route::get('teams/{team}/games', [TeamController::class, 'games']);
    Route::get('teams/{team}/statistics', [TeamController::class, 'statistics']);
    
    // Player Management
    Route::apiResource('players', PlayerController::class)->names('api.v2.players');
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
    
    /*
    |--------------------------------------------------------------------------
    | Gym Management Dashboard Endpoints
    |--------------------------------------------------------------------------
    */
    
    Route::prefix('gym-management')->name('api.gym.')->middleware('auth:sanctum')->group(function () {
        // Dashboard specific endpoints
        Route::get('stats', [\App\Http\Controllers\Api\GymHallController::class, 'getStats'])
             ->name('stats');
             
        Route::get('weekly-bookings', [\App\Http\Controllers\Api\GymHallController::class, 'getWeeklyBookings'])
             ->name('weekly-bookings');
             
        Route::get('recent-activities', [\App\Http\Controllers\Api\GymHallController::class, 'getRecentActivities'])
             ->name('recent-activities');
             
        Route::get('pending-requests', [\App\Http\Controllers\Api\GymHallController::class, 'getPendingRequests'])
             ->name('pending-requests');
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

/*
|--------------------------------------------------------------------------
| Gym Hall & Time Management Routes
|--------------------------------------------------------------------------
|
| Routes for managing gym halls, time slots, bookings, and schedule
| management for basketball teams and training sessions.
|
*/

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    
    // Gym Hall Management
    Route::apiResource('gym-halls', \App\Http\Controllers\Api\GymHallController::class);
    Route::get('gym-halls/{gymHall}/availability', [\App\Http\Controllers\Api\GymHallController::class, 'availability']);
    Route::get('gym-halls/{gymHall}/schedule', [\App\Http\Controllers\Api\GymHallController::class, 'schedule']);
    Route::get('gym-halls/{gymHall}/statistics', [\App\Http\Controllers\Api\GymHallController::class, 'statistics']);
    Route::post('gym-halls/{gymHall}/initialize-courts', [\App\Http\Controllers\Api\GymHallController::class, 'initializeCourts']);
    Route::get('gym-halls/{gymHall}/availability-with-courts', [\App\Http\Controllers\Api\GymHallController::class, 'availabilityWithCourts']);
    Route::put('gym-halls/{gymHall}/court-settings', [\App\Http\Controllers\Api\GymHallController::class, 'updateCourtSettings']);
    
    // Hall Time Slots Management (Custom Times)
    Route::get('gym-halls/{hallId}/time-slots', [\App\Http\Controllers\GymManagementController::class, 'getHallTimeSlots']);
    Route::put('gym-halls/{hallId}/time-slots', [\App\Http\Controllers\GymManagementController::class, 'updateHallTimeSlots']);
    Route::put('time-slots/{slotId}/custom-times', [\App\Http\Controllers\GymManagementController::class, 'updateTimeSlotCustomTimes']);
    
    // Team Segment Assignments (30-min slots)
    Route::get('time-slots/{timeSlotId}/segments', [\App\Http\Controllers\GymManagementController::class, 'getTimeSlotSegments']);
    Route::post('time-slots/assign-team-segment', [\App\Http\Controllers\GymManagementController::class, 'assignTeamToSegment']);
    Route::delete('team-assignments/{assignmentId}', [\App\Http\Controllers\GymManagementController::class, 'removeTeamSegmentAssignment']);
    Route::get('time-slots/{timeSlotId}/team-assignments', [\App\Http\Controllers\GymManagementController::class, 'getTimeSlotTeamAssignments']);
    
    // Time Slot Management
    Route::apiResource('gym-time-slots', \App\Http\Controllers\Api\GymTimeSlotController::class);
    Route::post('gym-time-slots/{timeSlot}/assign', [\App\Http\Controllers\Api\GymTimeSlotController::class, 'assignToTeam']);
    Route::delete('gym-time-slots/{timeSlot}/unassign', [\App\Http\Controllers\Api\GymTimeSlotController::class, 'unassignFromTeam']);
    Route::post('gym-time-slots/{timeSlot}/generate-bookings', [\App\Http\Controllers\Api\GymTimeSlotController::class, 'generateBookings']);
    
    // Booking Management
    Route::apiResource('gym-bookings', \App\Http\Controllers\Api\GymBookingController::class);
    Route::post('gym-bookings/{booking}/release', [\App\Http\Controllers\Api\GymBookingController::class, 'release']);
    Route::post('gym-bookings/{booking}/cancel', [\App\Http\Controllers\Api\GymBookingController::class, 'cancel']);
    Route::post('gym-bookings/{booking}/request', [\App\Http\Controllers\Api\GymBookingController::class, 'requestBooking']);
    Route::get('gym-bookings/available-for-team/{team}', [\App\Http\Controllers\Api\GymBookingController::class, 'availableForTeam']);
    
    // Booking Request Management
    Route::apiResource('gym-booking-requests', \App\Http\Controllers\Api\GymBookingRequestController::class);
    Route::post('gym-booking-requests/{request}/approve', [\App\Http\Controllers\Api\GymBookingRequestController::class, 'approve']);
    Route::post('gym-booking-requests/{request}/reject', [\App\Http\Controllers\Api\GymBookingRequestController::class, 'reject']);
    Route::get('gym-booking-requests/for-team/{team}', [\App\Http\Controllers\Api\GymBookingRequestController::class, 'forTeam']);
    Route::get('gym-booking-requests/by-team/{team}', [\App\Http\Controllers\Api\GymBookingRequestController::class, 'byTeam']);
    
    // Club-wide Gym Schedule Management
    Route::prefix('clubs/{club}/gym-management')->name('api.clubs.gym.')->group(function () {
        Route::get('schedule', [\App\Http\Controllers\Api\GymScheduleController::class, 'clubSchedule']);
        Route::get('utilization', [\App\Http\Controllers\Api\GymScheduleController::class, 'utilization']);
        Route::get('conflicts', [\App\Http\Controllers\Api\GymScheduleController::class, 'conflicts']);
        Route::get('available-times', [\App\Http\Controllers\Api\GymScheduleController::class, 'availableTimes']);
    });
    
    // Team-specific Gym Management
    Route::prefix('teams/{team}/gym-management')->name('api.teams.gym.')->group(function () {
        Route::get('bookings', [\App\Http\Controllers\Api\GymScheduleController::class, 'teamBookings']);
        Route::get('requests', [\App\Http\Controllers\Api\GymScheduleController::class, 'teamRequests']);
        Route::get('available-times', [\App\Http\Controllers\Api\GymScheduleController::class, 'teamAvailableTimes']);
        Route::get('statistics', [\App\Http\Controllers\Api\GymScheduleController::class, 'teamStatistics']);
    });
    
    /*
    |--------------------------------------------------------------------------
    | Gym Management Admin Routes
    |--------------------------------------------------------------------------
    */
    
    Route::middleware('role:admin,club_admin')->prefix('admin/gym-management')->name('api.admin.gym.')->group(function () {
        Route::post('process-expired-requests', [\App\Http\Controllers\Api\GymAdminController::class, 'processExpiredRequests']);
        Route::post('process-past-bookings', [\App\Http\Controllers\Api\GymAdminController::class, 'processPastBookings']);
        Route::get('system-statistics', [\App\Http\Controllers\Api\GymAdminController::class, 'systemStatistics']);
        Route::get('hall-utilization-report', [\App\Http\Controllers\Api\GymAdminController::class, 'utilizationReport']);
    });
});