<?php

use App\Http\Controllers\ProfileCompletionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Profile Completion Routes
|--------------------------------------------------------------------------
|
| These routes handle the profile completion flow for users who were
| invited to join a club. They need to complete their profile before
| accessing the rest of the application.
|
*/

Route::middleware(['auth:web', config('jetstream.auth_session'), 'verified'])
    ->prefix('profile-completion')
    ->name('profile-completion.')
    ->group(function () {
        Route::get('/', [ProfileCompletionController::class, 'index'])->name('index');
        Route::post('/', [ProfileCompletionController::class, 'store'])->name('store');
        Route::get('/complete', [ProfileCompletionController::class, 'complete'])->name('complete');
    });
