<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Define supported locales
$supportedLocales = config('localization.supported_locales', ['de', 'en']);
$defaultLocale = config('localization.default_locale', 'de');

// Register routes for default locale (no prefix)
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('welcome');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});

// Register routes for other locales with prefix
foreach ($supportedLocales as $locale) {
    if ($locale !== $defaultLocale) {
        Route::prefix($locale)->name($locale . '.')->group(function () {
            // Welcome page
            Route::get('/', function () {
                return Inertia::render('Welcome', [
                    'canLogin' => Route::has('login'),
                    'canRegister' => Route::has('register'),
                    'laravelVersion' => Application::VERSION,
                    'phpVersion' => PHP_VERSION,
                ]);
            })->name('welcome');
            
            // Authenticated routes
            Route::middleware([
                'auth:sanctum',
                config('jetstream.auth_session'),
                'verified',
            ])->group(function () {
                Route::get('/dashboard', function () {
                    return Inertia::render('Dashboard');
                })->name('dashboard');
            });
        });
    }
}
