<?php

use App\Http\Controllers\Stripe\ClubCheckoutController;
use App\Http\Controllers\Webhooks\ClubSubscriptionWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Club Checkout Routes
|--------------------------------------------------------------------------
|
| Routes for club-level Stripe subscription checkout and management.
| Separate from tenant-level subscriptions to allow multiple clubs
| per tenant with individual billing.
|
*/

// Authenticated routes - Club subscription management
Route::middleware(['auth', 'verified', 'tenant'])->group(function () {
    // Subscription overview page
    Route::get('/club/{club}/subscription', [ClubCheckoutController::class, 'index'])
        ->name('club.subscription.index');

    // Initiate checkout for club subscription
    Route::post('/club/{club}/checkout', [ClubCheckoutController::class, 'checkout'])
        ->name('club.checkout');

    // Success page after checkout completion
    Route::get('/club/{club}/checkout/success', [ClubCheckoutController::class, 'success'])
        ->name('club.checkout.success');

    // Cancel page if checkout is abandoned
    Route::get('/club/{club}/checkout/cancel', [ClubCheckoutController::class, 'cancel'])
        ->name('club.checkout.cancel');

    // Billing portal for subscription management
    Route::post('/club/{club}/billing-portal', [ClubCheckoutController::class, 'billingPortal'])
        ->name('club.billing-portal');
});

// Webhook routes (no authentication required)
Route::post('/webhooks/stripe/club-subscriptions', [ClubSubscriptionWebhookController::class, 'handleWebhook'])
    ->name('webhooks.stripe.club-subscriptions')
    ->withoutMiddleware(['auth', 'verified', 'tenant']);
