<?php

use App\Http\Controllers\SeasonController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Season Management Routes
|--------------------------------------------------------------------------
|
| Routes für das Saison-Management-System
| Alle Routes erfordern Authentifizierung und Club-Admin Berechtigung
|
*/

Route::middleware(['auth:sanctum'])->group(function () {
    // Season Management für Clubs
    Route::prefix('club/{club}')->group(function () {

        // View-Only Routes (Trainer, Assistant Coach können zugreifen)
        Route::middleware(['permission:view seasons'])->group(function () {
            // Alle Saisons auflisten
            Route::get('/seasons', [SeasonController::class, 'index'])->name('club.seasons.index');

            // Aktuelle Saison
            Route::get('/seasons/current', [SeasonController::class, 'current'])->name('club.seasons.current');

            // Einzelne Saison anzeigen
            Route::get('/seasons/{season}', [SeasonController::class, 'show'])->name('club.seasons.show');
        });

        // Management Routes (Nur Club Admin+)
        Route::middleware(['permission:manage seasons'])->group(function () {
            // Neue Saison erstellen
            Route::post('/seasons', [SeasonController::class, 'store'])->name('club.seasons.store');

            // Saison aktualisieren
            Route::put('/seasons/{season}', [SeasonController::class, 'update'])->name('club.seasons.update');

            // Saison löschen
            Route::delete('/seasons/{season}', [SeasonController::class, 'destroy'])->name('club.seasons.destroy');
        });

        // Lifecycle Management Routes (Nur Club Admin+)
        Route::middleware(['permission:complete seasons'])->group(function () {
            // Saison abschließen
            Route::post('/seasons/{season}/complete', [SeasonController::class, 'complete'])->name('club.seasons.complete');
        });

        Route::middleware(['permission:activate seasons'])->group(function () {
            // Saison aktivieren
            Route::post('/seasons/{season}/activate', [SeasonController::class, 'activate'])->name('club.seasons.activate');
        });

        // Season Wizard (Nur Club Admin+)
        Route::middleware(['permission:start new season'])->group(function () {
            // Neue Saison starten (vollständiger Saisonwechsel)
            Route::post('/seasons/start-new', [SeasonController::class, 'startNew'])->name('club.seasons.start-new');
        });
    });
});
