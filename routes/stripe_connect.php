<?php

use App\Http\Controllers\Stripe\StripeConnectController;
use App\Http\Controllers\Admin\StripeConnectAdminController;
use App\Http\Controllers\Webhooks\StripeConnectWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Stripe Connect Routes
|--------------------------------------------------------------------------
|
| Routes for Stripe Connect integration, including tenant onboarding,
| account management, and admin oversight.
|
*/

// Tenant-scoped Connect routes (requires auth and tenant context)
Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    Route::prefix('stripe-connect')->name('stripe-connect.')->group(function () {
        // Dashboard
        Route::get('/', [StripeConnectController::class, 'index'])
            ->name('index');

        // Onboarding
        Route::post('/onboard', [StripeConnectController::class, 'startOnboarding'])
            ->name('onboard');

        Route::get('/callback', [StripeConnectController::class, 'handleCallback'])
            ->name('callback');

        Route::get('/refresh', [StripeConnectController::class, 'refresh'])
            ->name('refresh');

        // Status & Management
        Route::get('/status', [StripeConnectController::class, 'status'])
            ->name('status');

        Route::post('/disconnect', [StripeConnectController::class, 'disconnect'])
            ->name('disconnect');

        Route::get('/dashboard', [StripeConnectController::class, 'dashboard'])
            ->name('dashboard');

        // Balance & Fees
        Route::get('/balance', [StripeConnectController::class, 'balance'])
            ->name('balance');

        Route::post('/preview-fees', [StripeConnectController::class, 'previewFees'])
            ->name('preview-fees');
    });
});

// Admin routes (Super Admin only)
Route::middleware(['auth', 'verified', 'role:super_admin'])
    ->prefix('admin/stripe-connect')
    ->name('admin.stripe-connect.')
    ->group(function () {
        Route::get('/', [StripeConnectAdminController::class, 'index'])
            ->name('index');

        Route::get('/tenant/{tenant}', [StripeConnectAdminController::class, 'show'])
            ->name('show');

        Route::get('/analytics', [StripeConnectAdminController::class, 'analytics'])
            ->name('analytics');

        Route::put('/platform-fee', [StripeConnectAdminController::class, 'updatePlatformFee'])
            ->name('platform-fee');

        Route::post('/tenant/{tenant}/refresh', [StripeConnectAdminController::class, 'refreshTenantStatus'])
            ->name('refresh-tenant');
    });

// Webhook endpoint (no auth required, verified by Stripe signature)
Route::post('/webhooks/stripe/connect', [StripeConnectWebhookController::class, 'handleWebhook'])
    ->name('webhooks.stripe.connect')
    ->withoutMiddleware(['auth', 'verified', 'web']);
