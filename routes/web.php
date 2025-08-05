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
                'auth:sanctum',
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
        }
    });
}
