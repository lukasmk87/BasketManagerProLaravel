<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;

// Define supported locales
$supportedLocales = config('localization.supported_locales', ['de', 'en']);
$defaultLocale = config('localization.default_locale', 'de');

// Register routes for default locale (no prefix)
Route::get('/', function () {
    // Redirect authenticated users to dashboard
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    
    // Show landing page for guests
    return view('landing');
})->name('landing');

Route::get('/welcome', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('welcome');

Route::middleware([
    'auth:web',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Basketball Clubs Routes (explicit names to avoid conflicts)
    Route::get('clubs', [\App\Http\Controllers\ClubController::class, 'index'])->name('web.clubs.index');
    Route::get('clubs/create', [\App\Http\Controllers\ClubController::class, 'create'])->name('web.clubs.create');
    Route::post('clubs', [\App\Http\Controllers\ClubController::class, 'store'])->name('web.clubs.store');
    Route::get('clubs/{club}', [\App\Http\Controllers\ClubController::class, 'show'])->name('web.clubs.show');
    Route::get('clubs/{club}/edit', [\App\Http\Controllers\ClubController::class, 'edit'])->name('web.clubs.edit');
    Route::put('clubs/{club}', [\App\Http\Controllers\ClubController::class, 'update'])->name('web.clubs.update');
    Route::patch('clubs/{club}', [\App\Http\Controllers\ClubController::class, 'update'])->name('web.clubs.patch');
    Route::delete('clubs/{club}', [\App\Http\Controllers\ClubController::class, 'destroy'])->name('web.clubs.destroy');
    
    // Basketball Teams Routes (explicit names to avoid conflicts with Jetstream teams)
    Route::get('basketball-teams', [\App\Http\Controllers\TeamController::class, 'index'])->name('web.teams.index');
    Route::get('basketball-teams/create', [\App\Http\Controllers\TeamController::class, 'create'])->name('web.teams.create');
    Route::post('basketball-teams', [\App\Http\Controllers\TeamController::class, 'store'])->name('web.teams.store');
    Route::get('basketball-teams/{team}', [\App\Http\Controllers\TeamController::class, 'show'])->name('web.teams.show');
    Route::get('basketball-teams/{team}/edit', [\App\Http\Controllers\TeamController::class, 'edit'])->name('web.teams.edit');
    Route::put('basketball-teams/{team}', [\App\Http\Controllers\TeamController::class, 'update'])->name('web.teams.update');
    Route::patch('basketball-teams/{team}', [\App\Http\Controllers\TeamController::class, 'update'])->name('web.teams.patch');
    Route::delete('basketball-teams/{team}', [\App\Http\Controllers\TeamController::class, 'destroy'])->name('web.teams.destroy');
    
    // Basketball Team Players Routes (nested resource for managing players in teams)
    Route::prefix('basketball-teams/{team}/players')->name('web.teams.players.')->group(function () {
        Route::get('/', [\App\Http\Controllers\TeamController::class, 'players'])->name('index');
        Route::post('/', [\App\Http\Controllers\TeamController::class, 'attachPlayer'])->name('attach');
        Route::put('/{player}', [\App\Http\Controllers\TeamController::class, 'updatePlayer'])->name('update');
        Route::delete('/{player}', [\App\Http\Controllers\TeamController::class, 'detachPlayer'])->name('detach');
    });
    
    // Player Search API for team management
    Route::get('/api/players/search', [\App\Http\Controllers\PlayerController::class, 'search'])->name('players.search');
    
    // Note: Debug route removed - clubs are now provided via Inertia props
    
    // Test route with hardcoded clubs
    Route::get('/test/teams/create', function () {
        return Inertia::render('Teams/Create', [
            'clubs' => [
                ['id' => 1, 'name' => 'Test Club 1'],
                ['id' => 2, 'name' => 'Test Club 2'],
            ],
        ]);
    })->name('test.teams.create');
    
    // Players Routes
    // Basketball Players Routes (explicit names to avoid conflicts)
    Route::get('players', [\App\Http\Controllers\PlayerController::class, 'index'])->name('web.players.index');
    Route::get('players/create', [\App\Http\Controllers\PlayerController::class, 'create'])->name('web.players.create');
    Route::post('players', [\App\Http\Controllers\PlayerController::class, 'store'])->name('web.players.store');
    Route::get('players/{player}', [\App\Http\Controllers\PlayerController::class, 'show'])->name('web.players.show');
    Route::get('players/{player}/edit', [\App\Http\Controllers\PlayerController::class, 'edit'])->name('web.players.edit');
    Route::put('players/{player}', [\App\Http\Controllers\PlayerController::class, 'update'])->name('web.players.update');
    Route::patch('players/{player}', [\App\Http\Controllers\PlayerController::class, 'update'])->name('web.players.patch');
    Route::delete('players/{player}', [\App\Http\Controllers\PlayerController::class, 'destroy'])->name('web.players.destroy');
    
    // Basketball Games Routes (explicit names to avoid conflicts)
    Route::get('games', [\App\Http\Controllers\GameController::class, 'index'])->name('web.games.index');
    Route::get('games/create', [\App\Http\Controllers\GameController::class, 'create'])->name('web.games.create');
    Route::post('games', [\App\Http\Controllers\GameController::class, 'store'])->name('web.games.store');
    Route::get('games/{game}', [\App\Http\Controllers\GameController::class, 'show'])->name('web.games.show');
    Route::get('games/{game}/edit', [\App\Http\Controllers\GameController::class, 'edit'])->name('web.games.edit');
    Route::put('games/{game}', [\App\Http\Controllers\GameController::class, 'update'])->name('web.games.update');
    Route::patch('games/{game}', [\App\Http\Controllers\GameController::class, 'update'])->name('web.games.patch');
    Route::delete('games/{game}', [\App\Http\Controllers\GameController::class, 'destroy'])->name('web.games.destroy');
    
    // Training Routes
    Route::prefix('training')->name('training.')->group(function () {
        Route::get('/', [\App\Http\Controllers\TrainingController::class, 'index'])->name('index');
        Route::get('/sessions', [\App\Http\Controllers\TrainingController::class, 'sessions'])->name('sessions');
        Route::get('/drills', [\App\Http\Controllers\TrainingController::class, 'drills'])->name('drills');
        Route::get('/sessions/{session}', [\App\Http\Controllers\TrainingController::class, 'showSession'])->name('sessions.show');
    });
    
    // Statistics Routes
    Route::prefix('statistics')->name('statistics.')->group(function () {
        Route::get('/', [\App\Http\Controllers\StatisticsController::class, 'index'])->name('index');
        Route::get('/teams', [\App\Http\Controllers\StatisticsController::class, 'teams'])->name('teams');
        Route::get('/players', [\App\Http\Controllers\StatisticsController::class, 'players'])->name('players');
        Route::get('/games', [\App\Http\Controllers\StatisticsController::class, 'games'])->name('games');
    });
    
    // Live Scoring Routes
    Route::prefix('games')->name('games.')->group(function () {
        Route::get('/{game}/live-scoring', [\App\Http\Controllers\LiveScoringController::class, 'show'])
            ->name('live-scoring');
        
        Route::post('/{game}/start', [\App\Http\Controllers\LiveScoringController::class, 'startGame'])
            ->name('start');
        
        Route::post('/{game}/finish', [\App\Http\Controllers\LiveScoringController::class, 'finishGame'])
            ->name('finish');
        
        Route::post('/{game}/actions', [\App\Http\Controllers\LiveScoringController::class, 'addAction'])
            ->name('add-action');
        
        Route::put('/{game}/score', [\App\Http\Controllers\LiveScoringController::class, 'updateScore'])
            ->name('update-score');
        
        Route::post('/{game}/clock', [\App\Http\Controllers\LiveScoringController::class, 'controlClock'])
            ->name('control-clock');
        
        Route::post('/{game}/timeout', [\App\Http\Controllers\LiveScoringController::class, 'timeout'])
            ->name('timeout');
        
        Route::delete('/{game}/timeout', [\App\Http\Controllers\LiveScoringController::class, 'endTimeout'])
            ->name('end-timeout');
        
        Route::post('/{game}/substitution', [\App\Http\Controllers\LiveScoringController::class, 'substitution'])
            ->name('substitution');
        
        Route::post('/{game}/shot-clock/reset', [\App\Http\Controllers\LiveScoringController::class, 'resetShotClock'])
            ->name('reset-shot-clock');
        
        Route::put('/{game}/players-on-court', [\App\Http\Controllers\LiveScoringController::class, 'updatePlayersOnCourt'])
            ->name('update-players-on-court');
        
        Route::put('/actions/{action}', [\App\Http\Controllers\LiveScoringController::class, 'correctAction'])
            ->name('correct-action');
        
        Route::delete('/actions/{action}', [\App\Http\Controllers\LiveScoringController::class, 'deleteAction'])
            ->name('delete-action');
        
        Route::get('/{game}/live-data', [\App\Http\Controllers\LiveScoringController::class, 'getLiveData'])
            ->name('live-data');
        
        Route::get('/{game}/statistics', [\App\Http\Controllers\LiveScoringController::class, 'getGameStatistics'])
            ->name('statistics');
    });
    
    // Admin Panel Routes
    Route::prefix('admin')->name('admin.')->middleware('role:admin|super_admin')->group(function () {
        Route::get('/settings', [\App\Http\Controllers\AdminPanelController::class, 'settings'])->name('settings');
        Route::put('/settings', [\App\Http\Controllers\AdminPanelController::class, 'updateSettings'])->name('settings.update');
        Route::get('/users', [\App\Http\Controllers\AdminPanelController::class, 'users'])->name('users');
        Route::get('/users/{user}/edit', [\App\Http\Controllers\AdminPanelController::class, 'editUser'])->name('users.edit');
        Route::put('/users/{user}', [\App\Http\Controllers\AdminPanelController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{user}', [\App\Http\Controllers\AdminPanelController::class, 'destroyUser'])->name('users.destroy');
        Route::get('/system', [\App\Http\Controllers\AdminPanelController::class, 'system'])->name('system');
    });
    
    // Gym Management Routes
    Route::prefix('gym-management')->name('gym.')->group(function () {
        Route::get('/', [\App\Http\Controllers\GymManagementController::class, 'index'])->name('index');
        Route::get('/halls', [\App\Http\Controllers\GymManagementController::class, 'halls'])->name('halls');
        Route::get('/bookings', [\App\Http\Controllers\GymManagementController::class, 'bookings'])->name('bookings');
        Route::get('/requests', [\App\Http\Controllers\GymManagementController::class, 'requests'])->name('requests');
        Route::get('/create-hall', [\App\Http\Controllers\GymManagementController::class, 'create'])->name('create-hall');
    });
    
    // Export Routes
    Route::prefix('export')->name('export.')->group(function () {
        // Game Exports
        Route::get('/games/{game}/stats/pdf', [\App\Http\Controllers\ExportController::class, 'gameStatsPdf'])
            ->name('game-stats-pdf');
        Route::get('/games/{game}/stats/excel', [\App\Http\Controllers\ExportController::class, 'gameStatsExcel'])
            ->name('game-stats-excel');
        Route::get('/games/{game}/scoresheet', [\App\Http\Controllers\ExportController::class, 'gameScoresheet'])
            ->name('game-scoresheet');
        
        // Player Exports
        Route::get('/players/{player}/stats/pdf', [\App\Http\Controllers\ExportController::class, 'playerStatsPdf'])
            ->name('player-stats-pdf');
        Route::get('/players/{player}/stats/excel', [\App\Http\Controllers\ExportController::class, 'playerStatsExcel'])
            ->name('player-stats-excel');
        
        // Team Exports
        Route::get('/teams/{team}/stats/pdf', [\App\Http\Controllers\ExportController::class, 'teamStatsPdf'])
            ->name('team-stats-pdf');
        Route::get('/teams/{team}/stats/excel', [\App\Http\Controllers\ExportController::class, 'teamStatsExcel'])
            ->name('team-stats-excel');
        
        // Shot Chart Export
        Route::get('/players/{player}/games/{game}/shot-chart/csv', [\App\Http\Controllers\ExportController::class, 'shotChartCsv'])
            ->name('shot-chart-csv');
        
        // League Standings
        Route::get('/league/standings/pdf', [\App\Http\Controllers\ExportController::class, 'leagueStandingsPdf'])
            ->name('league-standings-pdf');
    });
});

// Include subscription routes
require __DIR__ . '/subscription.php';

// Include Jetstream Routes (Inertia stack)
if (file_exists(base_path('vendor/laravel/jetstream/routes/inertia.php'))) {
    require base_path('vendor/laravel/jetstream/routes/inertia.php');
}

// Register routes for all locales with prefix (including default locale for explicit redirect)
foreach ($supportedLocales as $locale) {
    Route::prefix($locale)->name($locale . '.')->group(function () use ($locale, $defaultLocale) {
        // For default locale, redirect to non-prefixed version
        if ($locale === $defaultLocale) {
            // Redirect /de to /
            Route::get('/', function () {
                return redirect('/', 301);
            })->name('welcome');
            
            // Redirect /de/dashboard to /dashboard
            Route::middleware([
                'auth:web',
                config('jetstream.auth_session'),
                'verified',
            ])->group(function () {
                Route::get('/dashboard', function () {
                    return redirect('/dashboard', 301);
                })->name('dashboard');
            });
        } else {
            // For other locales, render normally
            Route::get('/', function () {
                // Redirect authenticated users to dashboard
                if (Auth::check()) {
                    return redirect()->route('dashboard');
                }
                
                // Show landing page for guests
                return view('landing');
            })->name('landing');
            
            // Authenticated routes
            Route::middleware([
                'auth:web',
                config('jetstream.auth_session'),
                'verified',
            ])->group(function () {
                Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
            });
        }
    });
}
