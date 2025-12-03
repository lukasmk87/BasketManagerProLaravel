<?php

use App\Http\Controllers\Api\PlayController;
use App\Http\Controllers\Api\PlaybookController;
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
});
