<?php

use App\Http\Controllers\Api\PlayController;
use App\Http\Controllers\Api\PlaybookController;
use App\Http\Controllers\Api\PlayTemplateController;
use App\Http\Controllers\Api\PlayFavoriteController;
use App\Http\Controllers\Api\TacticCategoryController;
use App\Http\Controllers\Api\DrillController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tactic Board API Routes
|--------------------------------------------------------------------------
|
| Routes for managing plays and playbooks in the tactic board feature.
|
*/

Route::middleware(['auth:sanctum'])->group(function () {

    // Plays
    Route::prefix('plays')->name('plays.')->group(function () {
        Route::get('/', [PlayController::class, 'index'])->name('index');
        Route::post('/', [PlayController::class, 'store'])->name('store');
        Route::get('/categories', [PlayController::class, 'categories'])->name('categories');
        Route::get('/{play}', [PlayController::class, 'show'])->name('show');
        Route::put('/{play}', [PlayController::class, 'update'])->name('update');
        Route::delete('/{play}', [PlayController::class, 'destroy'])->name('destroy');

        // Actions
        Route::post('/{play}/duplicate', [PlayController::class, 'duplicate'])->name('duplicate');
        Route::post('/{play}/publish', [PlayController::class, 'publish'])->name('publish');
        Route::post('/{play}/archive', [PlayController::class, 'archive'])->name('archive');
        Route::post('/{play}/thumbnail', [PlayController::class, 'saveThumbnail'])->name('thumbnail');

        // Export
        Route::post('/{play}/export/png', [PlayController::class, 'exportPng'])->name('export.png');
        Route::get('/{play}/export/pdf', [PlayController::class, 'exportPdf'])->name('export.pdf');
        Route::post('/{play}/export/gif', [PlayController::class, 'exportGif'])->name('export.gif');

        // Connections
        Route::post('/{play}/attach-to-drill', [PlayController::class, 'attachToDrill'])->name('attach-drill');
        Route::post('/{play}/attach-to-session', [PlayController::class, 'attachToSession'])->name('attach-session');
    });

    // Playbooks
    Route::prefix('playbooks')->name('playbooks.')->group(function () {
        Route::get('/', [PlaybookController::class, 'index'])->name('index');
        Route::post('/', [PlaybookController::class, 'store'])->name('store');
        Route::get('/categories', [PlaybookController::class, 'categories'])->name('categories');
        Route::get('/{playbook}', [PlaybookController::class, 'show'])->name('show');
        Route::put('/{playbook}', [PlaybookController::class, 'update'])->name('update');
        Route::delete('/{playbook}', [PlaybookController::class, 'destroy'])->name('destroy');

        // Actions
        Route::post('/{playbook}/duplicate', [PlaybookController::class, 'duplicate'])->name('duplicate');
        Route::post('/{playbook}/set-default', [PlaybookController::class, 'setDefault'])->name('set-default');
        Route::get('/{playbook}/statistics', [PlaybookController::class, 'statistics'])->name('statistics');

        // Plays Management
        Route::post('/{playbook}/plays', [PlaybookController::class, 'addPlay'])->name('add-play');
        Route::delete('/{playbook}/plays/{play}', [PlaybookController::class, 'removePlay'])->name('remove-play');
        Route::put('/{playbook}/plays/reorder', [PlaybookController::class, 'reorderPlays'])->name('reorder-plays');

        // Export
        Route::get('/{playbook}/export/pdf', [PlaybookController::class, 'exportPdf'])->name('export.pdf');

        // Game Preparation
        Route::post('/{playbook}/attach-to-game', [PlaybookController::class, 'attachToGame'])->name('attach-game');
    });

    // Templates
    Route::prefix('templates')->name('templates.')->group(function () {
        Route::get('/', [PlayTemplateController::class, 'index'])->name('index');
        Route::get('/featured', [PlayTemplateController::class, 'featured'])->name('featured');
        Route::get('/by-category', [PlayTemplateController::class, 'byCategory'])->name('by-category');
        Route::get('/stats', [PlayTemplateController::class, 'stats'])->name('stats');
        Route::post('/{play}/use', [PlayTemplateController::class, 'createFromTemplate'])->name('use');
        Route::post('/{play}/toggle-featured', [PlayTemplateController::class, 'toggleFeatured'])->name('toggle-featured');
        Route::put('/{play}/order', [PlayTemplateController::class, 'updateOrder'])->name('update-order');
    });

    // Favorites
    Route::prefix('favorites')->name('favorites.')->group(function () {
        Route::get('/', [PlayFavoriteController::class, 'index'])->name('index');
        Route::get('/quick-access', [PlayFavoriteController::class, 'quickAccess'])->name('quick-access');
        Route::get('/library', [PlayFavoriteController::class, 'library'])->name('library');
        Route::get('/stats', [PlayFavoriteController::class, 'stats'])->name('stats');
        Route::get('/type/{type}', [PlayFavoriteController::class, 'byType'])->name('by-type');
        Route::post('/plays/{play}/toggle', [PlayFavoriteController::class, 'toggle'])->name('toggle');
        Route::get('/plays/{play}/check', [PlayFavoriteController::class, 'check'])->name('check');
        Route::put('/{favorite}', [PlayFavoriteController::class, 'update'])->name('update');
        Route::delete('/{favorite}', [PlayFavoriteController::class, 'destroy'])->name('destroy');
        Route::post('/{favorite}/toggle-quick-access', [PlayFavoriteController::class, 'toggleQuickAccess'])->name('toggle-quick-access');
    });

    // Tactic Categories
    Route::prefix('tactic-categories')->name('tactic-categories.')->group(function () {
        Route::get('/', [TacticCategoryController::class, 'index'])->name('index');
        Route::get('/plays', [TacticCategoryController::class, 'forPlays'])->name('plays');
        Route::get('/drills', [TacticCategoryController::class, 'forDrills'])->name('drills');
        Route::get('/stats', [TacticCategoryController::class, 'stats'])->name('stats');
        Route::post('/', [TacticCategoryController::class, 'store'])->name('store');
        Route::get('/{category}', [TacticCategoryController::class, 'show'])->name('show');
        Route::put('/{category}', [TacticCategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [TacticCategoryController::class, 'destroy'])->name('destroy');
        Route::post('/reorder', [TacticCategoryController::class, 'reorder'])->name('reorder');
    });

    // Drills (Visual Editor Extensions)
    Route::prefix('drills')->name('drills.')->group(function () {
        Route::get('/', [DrillController::class, 'index'])->name('index');
        Route::post('/', [DrillController::class, 'store'])->name('store');
        Route::get('/{drill}', [DrillController::class, 'show'])->name('show');
        Route::put('/{drill}', [DrillController::class, 'update'])->name('update');
        Route::delete('/{drill}', [DrillController::class, 'destroy'])->name('destroy');

        // Visual editing actions
        Route::post('/{drill}/duplicate', [DrillController::class, 'duplicate'])->name('duplicate');
        Route::post('/{drill}/thumbnail', [DrillController::class, 'saveThumbnail'])->name('thumbnail');
        Route::post('/{drill}/publish', [DrillController::class, 'publish'])->name('publish');
        Route::post('/{drill}/archive', [DrillController::class, 'archive'])->name('archive');

        // Export
        Route::post('/{drill}/export/png', [DrillController::class, 'exportPng'])->name('export.png');
        Route::get('/{drill}/export/pdf', [DrillController::class, 'exportPdf'])->name('export.pdf');
    });
});
