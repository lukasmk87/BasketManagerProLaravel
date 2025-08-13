<?php

use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Subscription Routes
|--------------------------------------------------------------------------
|
| Routes for managing tenant subscriptions with Laravel Cashier and Stripe.
| These routes handle subscription creation, management, and billing.
|
*/

Route::middleware(['auth', 'tenant'])->group(function () {
    
    // Subscription management interface
    Route::get('/subscription', [SubscriptionController::class, 'index'])
        ->name('subscription.index');
    
    // Stripe configuration for frontend
    Route::get('/subscription/config', [SubscriptionController::class, 'config'])
        ->name('subscription.config');
    
    // Checkout and payment processing
    Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout'])
        ->name('subscription.checkout');
    
    // Success and cancel redirects
    Route::get('/subscription/success', [SubscriptionController::class, 'success'])
        ->name('subscription.success');
    
    Route::get('/subscription/cancel', [SubscriptionController::class, 'cancel'])
        ->name('subscription.cancel');
    
    // Subscription management actions
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancelSubscription'])
        ->name('subscription.cancel-subscription');
    
    Route::post('/subscription/resume', [SubscriptionController::class, 'resume'])
        ->name('subscription.resume');
    
    Route::post('/subscription/swap', [SubscriptionController::class, 'swap'])
        ->name('subscription.swap');
    
    // Payment method management
    Route::post('/subscription/payment-method', [SubscriptionController::class, 'updatePaymentMethod'])
        ->name('subscription.update-payment-method');
    
    // Invoice management
    Route::get('/subscription/invoices', [SubscriptionController::class, 'invoices'])
        ->name('subscription.invoices');
    
    Route::get('/subscription/invoice/{invoice}', [SubscriptionController::class, 'downloadInvoice'])
        ->name('subscription.invoice');
});

// Webhook routes (no authentication required)
Route::post('/stripe/webhook', [SubscriptionController::class, 'webhook'])
    ->name('cashier.webhook');