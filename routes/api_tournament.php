<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TournamentController;
use App\Http\Controllers\Api\TournamentTeamController;
use App\Http\Controllers\Api\TournamentBracketController;
use App\Http\Controllers\Api\TournamentOfficialController;
use App\Http\Controllers\Api\TournamentAwardController;

/*
|--------------------------------------------------------------------------
| Tournament API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for the Tournament Management System.
| These routes are protected by authentication middleware.
|
*/

Route::middleware('auth:sanctum')->group(function () {
    
    // Tournament CRUD Routes
    Route::prefix('tournaments')->name('tournaments.')->group(function () {
        Route::get('/', [TournamentController::class, 'index'])->name('index');
        Route::post('/', [TournamentController::class, 'store'])->name('store');
        Route::get('/{tournament}', [TournamentController::class, 'show'])->name('show');
        Route::put('/{tournament}', [TournamentController::class, 'update'])->name('update');
        Route::delete('/{tournament}', [TournamentController::class, 'destroy'])->name('destroy');
        
        // Tournament Status Management
        Route::post('/{tournament}/open-registration', [TournamentController::class, 'openRegistration'])->name('open-registration');
        Route::post('/{tournament}/close-registration', [TournamentController::class, 'closeRegistration'])->name('close-registration');
        Route::post('/{tournament}/start', [TournamentController::class, 'start'])->name('start');
        Route::post('/{tournament}/complete', [TournamentController::class, 'complete'])->name('complete');
        
        // Bracket Management
        Route::post('/{tournament}/generate-brackets', [TournamentController::class, 'generateBrackets'])->name('generate-brackets');
        Route::post('/{tournament}/regenerate-brackets', [TournamentController::class, 'regenerateBrackets'])->name('regenerate-brackets');
        Route::put('/{tournament}/seeding', [TournamentController::class, 'updateSeeding'])->name('update-seeding');
        
        // Tournament Data and Analytics
        Route::get('/{tournament}/statistics', [TournamentController::class, 'statistics'])->name('statistics');
        Route::get('/{tournament}/analytics', [TournamentController::class, 'analytics'])->name('analytics');
        Route::get('/{tournament}/standings', [TournamentController::class, 'standings'])->name('standings');
        Route::get('/{tournament}/upcoming-games', [TournamentController::class, 'upcomingGames'])->name('upcoming-games');
        Route::get('/{tournament}/brackets', [TournamentController::class, 'brackets'])->name('brackets');
        Route::get('/{tournament}/awards', [TournamentController::class, 'awards'])->name('awards');
        
        // Tournament Teams Management
        Route::prefix('{tournament}/teams')->name('teams.')->group(function () {
            Route::get('/', [TournamentTeamController::class, 'index'])->name('index');
            Route::post('/', [TournamentTeamController::class, 'store'])->name('store');
            Route::get('/{tournamentTeam}', [TournamentTeamController::class, 'show'])->name('show');
            Route::put('/{tournamentTeam}', [TournamentTeamController::class, 'update'])->name('update');
            Route::delete('/{tournamentTeam}', [TournamentTeamController::class, 'destroy'])->name('destroy');
            
            // Team Registration Management
            Route::post('/{tournamentTeam}/approve', [TournamentTeamController::class, 'approve'])->name('approve');
            Route::post('/{tournamentTeam}/reject', [TournamentTeamController::class, 'reject'])->name('reject');
            Route::post('/{tournamentTeam}/withdraw', [TournamentTeamController::class, 'withdraw'])->name('withdraw');
            
            // Team Performance and Statistics
            Route::get('/{tournamentTeam}/performance', [TournamentTeamController::class, 'performance'])->name('performance');
            Route::get('/{tournamentTeam}/upcoming-games', [TournamentTeamController::class, 'upcomingGames'])->name('upcoming-games');
        });
        
        // Tournament Brackets Management
        Route::prefix('{tournament}/brackets')->name('brackets.')->group(function () {
            Route::get('/', [TournamentBracketController::class, 'index'])->name('index');
            Route::get('/upcoming', [TournamentBracketController::class, 'upcoming'])->name('upcoming');
            Route::get('/completed', [TournamentBracketController::class, 'completed'])->name('completed');
            Route::get('/round/{round}', [TournamentBracketController::class, 'byRound'])->name('by-round');
            
            Route::get('/{bracket}', [TournamentBracketController::class, 'show'])->name('show');
            Route::get('/{bracket}/progression', [TournamentBracketController::class, 'progression'])->name('progression');
            
            // Game Management
            Route::post('/{bracket}/schedule', [TournamentBracketController::class, 'schedule'])->name('schedule');
            Route::post('/{bracket}/start', [TournamentBracketController::class, 'start'])->name('start');
            Route::post('/{bracket}/record-result', [TournamentBracketController::class, 'recordResult'])->name('record-result');
            Route::post('/{bracket}/forfeit', [TournamentBracketController::class, 'forfeit'])->name('forfeit');
            
            // Officials Assignment
            Route::put('/{bracket}/officials', [TournamentBracketController::class, 'assignOfficials'])->name('assign-officials');
        });
        
        // Tournament Officials Management
        Route::prefix('{tournament}/officials')->name('officials.')->group(function () {
            Route::get('/', [TournamentOfficialController::class, 'index'])->name('index');
            Route::post('/', [TournamentOfficialController::class, 'store'])->name('store');
            Route::get('/{tournamentOfficial}', [TournamentOfficialController::class, 'show'])->name('show');
            Route::put('/{tournamentOfficial}', [TournamentOfficialController::class, 'update'])->name('update');
            Route::delete('/{tournamentOfficial}', [TournamentOfficialController::class, 'destroy'])->name('destroy');
            
            // Official Status Management
            Route::post('/{tournamentOfficial}/confirm', [TournamentOfficialController::class, 'confirm'])->name('confirm');
            Route::post('/{tournamentOfficial}/decline', [TournamentOfficialController::class, 'decline'])->name('decline');
            Route::post('/{tournamentOfficial}/cancel', [TournamentOfficialController::class, 'cancel'])->name('cancel');
            
            // Performance and Feedback
            Route::post('/{tournamentOfficial}/rate', [TournamentOfficialController::class, 'rate'])->name('rate');
            Route::post('/{tournamentOfficial}/feedback', [TournamentOfficialController::class, 'addFeedback'])->name('add-feedback');
            Route::get('/{tournamentOfficial}/performance', [TournamentOfficialController::class, 'performance'])->name('performance');
            
            // Assignment Management
            Route::get('/{tournamentOfficial}/assignments', [TournamentOfficialController::class, 'assignments'])->name('assignments');
            Route::post('/{tournamentOfficial}/assign-game', [TournamentOfficialController::class, 'assignToGame'])->name('assign-game');
            Route::delete('/{tournamentOfficial}/unassign-game/{bracket}', [TournamentOfficialController::class, 'unassignFromGame'])->name('unassign-game');
        });
        
        // Tournament Awards Management
        Route::prefix('{tournament}/awards')->name('awards.')->group(function () {
            Route::get('/', [TournamentAwardController::class, 'index'])->name('index');
            Route::post('/', [TournamentAwardController::class, 'store'])->name('store');
            Route::get('/{tournamentAward}', [TournamentAwardController::class, 'show'])->name('show');
            Route::put('/{tournamentAward}', [TournamentAwardController::class, 'update'])->name('update');
            Route::delete('/{tournamentAward}', [TournamentAwardController::class, 'destroy'])->name('destroy');
            
            // Award Assignment and Management
            Route::post('/{tournamentAward}/assign', [TournamentAwardController::class, 'assign'])->name('assign');
            Route::post('/{tournamentAward}/present', [TournamentAwardController::class, 'present'])->name('present');
            Route::post('/{tournamentAward}/feature', [TournamentAwardController::class, 'feature'])->name('feature');
            Route::delete('/{tournamentAward}/unfeature', [TournamentAwardController::class, 'unfeature'])->name('unfeature');
            
            // Award Categories and Templates
            Route::get('/categories/available', [TournamentAwardController::class, 'availableCategories'])->name('available-categories');
            Route::get('/templates', [TournamentAwardController::class, 'templates'])->name('templates');
            Route::post('/generate-automatic', [TournamentAwardController::class, 'generateAutomatic'])->name('generate-automatic');
        });
        
        // Public Tournament Data (No auth required for these)
        Route::middleware('guest')->prefix('public')->name('public.')->group(function () {
            Route::get('tournaments', [TournamentController::class, 'index'])->name('tournaments.index');
            Route::get('tournaments/{tournament}', [TournamentController::class, 'show'])->name('tournaments.show');
            Route::get('tournaments/{tournament}/standings', [TournamentController::class, 'standings'])->name('tournaments.standings');
            Route::get('tournaments/{tournament}/brackets', [TournamentController::class, 'brackets'])->name('tournaments.brackets');
            Route::get('tournaments/{tournament}/upcoming-games', [TournamentController::class, 'upcomingGames'])->name('tournaments.upcoming-games');
            Route::get('tournaments/{tournament}/awards', [TournamentController::class, 'awards'])->name('tournaments.awards');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Tournament Webhook Routes
|--------------------------------------------------------------------------
|
| These routes handle external webhooks and integrations for tournaments.
| They may have different authentication requirements.
|
*/

Route::prefix('tournaments/webhooks')->name('tournaments.webhooks.')->group(function () {
    // Game result webhooks from external systems
    Route::post('game-result', [TournamentBracketController::class, 'webhookGameResult'])->name('game-result');
    
    // Official confirmation webhooks
    Route::post('official-response/{token}', [TournamentOfficialController::class, 'webhookResponse'])->name('official-response');
    
    // Payment confirmation webhooks
    Route::post('payment-confirmed', [TournamentTeamController::class, 'webhookPaymentConfirmed'])->name('payment-confirmed');
});

/*
|--------------------------------------------------------------------------
| Tournament Utility Routes
|--------------------------------------------------------------------------
|
| These routes provide utility functions and data for tournaments.
|
*/

Route::middleware('auth:sanctum')->prefix('tournament-utils')->name('tournament-utils.')->group(function () {
    // Tournament Types and Configuration
    Route::get('types', function () {
        return response()->json([
            'data' => [
                'single_elimination' => 'Einfach-K.O.',
                'double_elimination' => 'Doppel-K.O.',
                'round_robin' => 'Jeder gegen Jeden',
                'swiss_system' => 'Schweizer System',
                'group_stage_knockout' => 'Gruppenphase + K.O.',
                'ladder' => 'Ladder-System',
            ],
        ]);
    })->name('types');
    
    Route::get('categories', function () {
        return response()->json([
            'data' => ['U8', 'U10', 'U12', 'U14', 'U16', 'U18', 'adult', 'mixed'],
        ]);
    })->name('categories');
    
    Route::get('genders', function () {
        return response()->json([
            'data' => ['male', 'female', 'mixed'],
        ]);
    })->name('genders');
    
    Route::get('statuses', function () {
        return response()->json([
            'data' => [
                'draft' => 'Entwurf',
                'registration_open' => 'Anmeldung offen',
                'registration_closed' => 'Anmeldung geschlossen',
                'in_progress' => 'Läuft',
                'completed' => 'Abgeschlossen',
                'cancelled' => 'Abgesagt',
            ],
        ]);
    })->name('statuses');
    
    // Bracket calculation utilities
    Route::post('calculate-brackets', function (Illuminate\Http\Request $request) {
        $request->validate([
            'team_count' => 'required|integer|min:2|max:128',
            'type' => 'required|in:single_elimination,double_elimination,round_robin,swiss_system',
        ]);
        
        $teamCount = $request->team_count;
        $type = $request->type;
        
        $calculations = match($type) {
            'single_elimination' => [
                'total_rounds' => (int) ceil(log($teamCount, 2)),
                'total_games' => $teamCount - 1,
                'bracket_size' => 2 ** ((int) ceil(log($teamCount, 2))),
                'bye_games' => (2 ** ((int) ceil(log($teamCount, 2)))) - $teamCount,
            ],
            'double_elimination' => [
                'winner_bracket_rounds' => (int) ceil(log($teamCount, 2)),
                'loser_bracket_rounds' => ((int) ceil(log($teamCount, 2))) * 2 - 2,
                'total_games' => ($teamCount * 2) - 2,
                'maximum_games_per_team' => ((int) ceil(log($teamCount, 2))) + ((int) ceil(log($teamCount, 2))) * 2 - 2,
            ],
            'round_robin' => [
                'total_rounds' => $teamCount - 1,
                'total_games' => ($teamCount * ($teamCount - 1)) / 2,
                'games_per_team' => $teamCount - 1,
            ],
            'swiss_system' => [
                'recommended_rounds' => (int) ceil(log($teamCount, 2)),
                'total_games' => ((int) ceil(log($teamCount, 2))) * ($teamCount / 2),
                'games_per_team' => (int) ceil(log($teamCount, 2)),
            ],
            default => [],
        };
        
        return response()->json(['data' => $calculations]);
    })->name('calculate-brackets');
    
    // Available venues (could be dynamic)
    Route::get('venues', function () {
        return response()->json([
            'data' => [
                'Sporthalle Nord',
                'Sporthalle Süd',
                'Basketball Arena',
                'Mehrzweckhalle',
                'Outdoor Court 1',
                'Outdoor Court 2',
            ],
        ]);
    })->name('venues');
});