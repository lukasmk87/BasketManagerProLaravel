<?php

use App\Http\Controllers\Api\SubscriptionHealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health Check API Routes
|--------------------------------------------------------------------------
|
| API endpoints for subscription system health monitoring.
| These endpoints are designed for monitoring dashboards and external tools.
|
| Authentication: Requires authentication (Sanctum) for most endpoints.
| The /status endpoint is public for basic uptime checks.
|
*/

// Public status endpoint (no auth required) - for uptime monitoring
Route::get('/health/status', [SubscriptionHealthController::class, 'status'])
    ->name('health.status');

// Authenticated health check endpoints
Route::middleware(['auth:sanctum'])->group(function () {
    // Overall subscription health
    Route::get('/health/subscriptions', [SubscriptionHealthController::class, 'index'])
        ->name('health.subscriptions');

    // Detailed metrics
    Route::get('/health/subscriptions/metrics', [SubscriptionHealthController::class, 'metrics'])
        ->name('health.subscriptions.metrics');

    // Specific metric endpoints
    Route::get('/health/subscriptions/payments', [SubscriptionHealthController::class, 'payments'])
        ->name('health.subscriptions.payments');

    Route::get('/health/subscriptions/churn', [SubscriptionHealthController::class, 'churn'])
        ->name('health.subscriptions.churn');

    Route::get('/health/stripe', [SubscriptionHealthController::class, 'stripe'])
        ->name('health.stripe');

    Route::get('/health/webhooks', [SubscriptionHealthController::class, 'webhooks'])
        ->name('health.webhooks');

    Route::get('/health/queue', [SubscriptionHealthController::class, 'queue'])
        ->name('health.queue');

    Route::get('/health/mrr', [SubscriptionHealthController::class, 'mrr'])
        ->name('health.mrr');

    // Tenant-specific health
    Route::get('/health/tenant/{tenant}', [SubscriptionHealthController::class, 'tenant'])
        ->name('health.tenant');

    // Cache management (admin only)
    Route::delete('/health/cache', [SubscriptionHealthController::class, 'clearCache'])
        ->middleware('can:manage-system')
        ->name('health.cache.clear');
});
