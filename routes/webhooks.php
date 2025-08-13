<?php

use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
|
| Routes for handling external webhooks from payment providers, APIs, etc.
| These routes typically don't require authentication or CSRF protection
|
*/

// Stripe webhook endpoint
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handleWebhook'])
    ->name('webhooks.stripe')
    ->withoutMiddleware(['auth', 'verified', 'throttle']);

// Add webhook verification middleware if needed
Route::middleware(['webhook.verify'])->group(function () {
    // Additional secured webhook endpoints can be added here
});

// Health check endpoint for webhook monitoring
Route::get('/webhooks/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'service' => 'BasketManager Pro Webhooks',
    ]);
})->name('webhooks.health');