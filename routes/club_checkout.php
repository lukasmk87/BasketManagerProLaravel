<?php

use App\Http\Controllers\Stripe\ClubCheckoutController;
use App\Http\Controllers\Stripe\ClubBillingController;
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

    // Request invoice payment (instead of card)
    Route::post('/club/{club}/checkout/request-invoice', [ClubCheckoutController::class, 'requestInvoicePayment'])
        ->name('club.checkout.request-invoice');

    // ============================
    // BILLING MANAGEMENT (Phase 2)
    // ============================

    // Billing routes group
    Route::prefix('club/{club}/billing')->name('club.billing.')->group(function () {
        // Invoice endpoints
        Route::get('/invoices', [ClubBillingController::class, 'indexInvoices'])
            ->name('invoices.index');
        Route::get('/invoices/upcoming', [ClubBillingController::class, 'upcomingInvoice'])
            ->name('invoices.upcoming');
        Route::get('/invoices/{invoice}', [ClubBillingController::class, 'showInvoice'])
            ->name('invoices.show');
        Route::get('/invoices/{invoice}/pdf', [ClubBillingController::class, 'downloadInvoicePdf'])
            ->name('invoices.pdf');

        // Payment Method endpoints
        Route::get('/payment-methods', [ClubBillingController::class, 'indexPaymentMethods'])
            ->name('payment-methods.index');
        Route::post('/payment-methods/setup', [ClubBillingController::class, 'createSetupIntent'])
            ->name('payment-methods.setup');
        Route::post('/payment-methods/attach', [ClubBillingController::class, 'attachPaymentMethod'])
            ->name('payment-methods.attach');
        Route::delete('/payment-methods/{paymentMethod}', [ClubBillingController::class, 'detachPaymentMethod'])
            ->name('payment-methods.detach');
        Route::put('/payment-methods/{paymentMethod}', [ClubBillingController::class, 'updatePaymentMethod'])
            ->name('payment-methods.update');
        Route::post('/payment-methods/{paymentMethod}/default', [ClubBillingController::class, 'setDefaultPaymentMethod'])
            ->name('payment-methods.default');

        // Proration Preview & Plan Swap
        Route::post('/preview-plan-swap', [ClubBillingController::class, 'previewPlanSwap'])
            ->name('preview-plan-swap');
        Route::post('/swap-plan', [ClubBillingController::class, 'swapPlan'])
            ->name('swap-plan');
    });

    // Legacy club.subscription.swap route for backward compatibility
    Route::post('/club/{club}/subscription/swap', [ClubBillingController::class, 'swapPlan'])
        ->name('club.subscription.swap');
});

// Webhook routes (no authentication required)
Route::post('/webhooks/stripe/club-subscriptions', [ClubSubscriptionWebhookController::class, 'handleWebhook'])
    ->name('webhooks.stripe.club-subscriptions')
    ->withoutMiddleware(['auth', 'verified', 'tenant']);
