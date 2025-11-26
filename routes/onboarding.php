<?php

use App\Http\Controllers\OnboardingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Onboarding Routes
|--------------------------------------------------------------------------
|
| These routes handle the onboarding wizard for new users.
| Users must complete onboarding (Club, Team, Plan) before accessing the app.
|
*/

Route::middleware(['auth:web', config('jetstream.auth_session'), 'verified'])
    ->prefix('onboarding')
    ->name('onboarding.')
    ->group(function () {
        // Main wizard page
        Route::get('/', [OnboardingController::class, 'index'])->name('index');

        // Step 1: Create Club
        Route::post('/club', [OnboardingController::class, 'storeClub'])->name('club.store');

        // Step 2: Create Team
        Route::post('/team', [OnboardingController::class, 'storeTeam'])->name('team.store');

        // Step 3: Select Plan
        Route::post('/plan', [OnboardingController::class, 'storePlan'])->name('plan.store');

        // Completion page
        Route::get('/complete', [OnboardingController::class, 'complete'])->name('complete');
    });
