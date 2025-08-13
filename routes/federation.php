<?php

use App\Http\Controllers\Federation\DBBController;
use App\Http\Controllers\Federation\FIBAController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Federation Integration Routes
|--------------------------------------------------------------------------
|
| Routes for integrating with basketball federations like DBB (German)
| and FIBA Europe for official player data and compliance
|
*/

Route::middleware(['auth', 'tenant', 'feature:federation_integration'])->group(function () {
    
    // DBB (Deutscher Basketball Bund) Integration
    Route::prefix('federation/dbb')->name('federation.dbb.')->group(function () {
        
        // Player Management
        Route::get('/player', [DBBController::class, 'getPlayer'])
            ->name('player.get');
        
        Route::post('/player/validate', [DBBController::class, 'validatePlayerEligibility'])
            ->name('player.validate');
        
        Route::get('/player/transfer-status', [DBBController::class, 'getPlayerTransferStatus'])
            ->name('player.transfer-status');
        
        // League Management
        Route::get('/leagues', [DBBController::class, 'getLeagues'])
            ->name('leagues.index');
        
        // Team Management
        Route::post('/team/register', [DBBController::class, 'registerTeam'])
            ->name('team.register');
        
        // Game Management
        Route::post('/game/submit-result', [DBBController::class, 'submitGameResult'])
            ->name('game.submit-result');
        
        // Referee Management
        Route::get('/referees/assignments', [DBBController::class, 'getRefereeAssignments'])
            ->name('referees.assignments');
        
        // System Status
        Route::get('/status', [DBBController::class, 'getApiStatus'])
            ->name('status');
        
        Route::get('/test-connection', [DBBController::class, 'testConnection'])
            ->name('test-connection');
    });
    
    // FIBA Europe Integration
    Route::prefix('federation/fiba')->name('federation.fiba.')->group(function () {
        
        // Player Management
        Route::get('/player/profile', [FIBAController::class, 'getPlayerProfile'])
            ->name('player.profile');
        
        Route::get('/player/search', [FIBAController::class, 'searchPlayers'])
            ->name('player.search');
        
        Route::get('/player/eligibility', [FIBAController::class, 'getPlayerEligibility'])
            ->name('player.eligibility');
        
        // Competition Management
        Route::get('/competitions', [FIBAController::class, 'getCompetitions'])
            ->name('competitions.index');
        
        Route::get('/competitions/{competition_id}/standings', [FIBAController::class, 'getCompetitionStandings'])
            ->name('competitions.standings');
        
        // Club Management
        Route::get('/club', [FIBAController::class, 'getClubInfo'])
            ->name('club.info');
        
        // Team Management
        Route::post('/team/register-competition', [FIBAController::class, 'registerTeamForCompetition'])
            ->name('team.register-competition');
        
        // Game Management
        Route::get('/game/official-data', [FIBAController::class, 'getOfficialGameData'])
            ->name('game.official-data');
        
        // Referee Management
        Route::get('/referee', [FIBAController::class, 'getRefereeInfo'])
            ->name('referee.info');
        
        // System Status
        Route::get('/status', [FIBAController::class, 'getApiStatus'])
            ->name('status');
        
        Route::get('/test-connection', [FIBAController::class, 'testConnection'])
            ->name('test-connection');
    });
    
});

// Public routes for webhook endpoints (no authentication required)
Route::prefix('federation/webhooks')->name('federation.webhooks.')->group(function () {
    
    // DBB webhook endpoints
    Route::post('/dbb/player-update', function () {
        // Handle player data updates from DBB
        return response()->json(['status' => 'received']);
    })->name('dbb.player-update');
    
    Route::post('/dbb/team-status', function () {
        // Handle team status updates from DBB
        return response()->json(['status' => 'received']);
    })->name('dbb.team-status');
    
    Route::post('/dbb/game-schedule', function () {
        // Handle game schedule updates from DBB
        return response()->json(['status' => 'received']);
    })->name('dbb.game-schedule');
    
});