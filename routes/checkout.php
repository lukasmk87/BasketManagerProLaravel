<?php

use App\Http\Controllers\CheckoutController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Checkout Routes
|--------------------------------------------------------------------------
|
| Routes for Stripe checkout flows including subscriptions, payment methods,
| and one-time payments with German compliance
|
*/

Route::middleware(['auth', 'tenant', 'feature:checkout'])->group(function () {
    
    // Subscription Checkout
    Route::get('/checkout/subscription', [CheckoutController::class, 'subscription'])
        ->name('checkout.subscription');
    
    Route::post('/checkout/subscription/session', [CheckoutController::class, 'createSubscriptionSession'])
        ->name('checkout.subscription.session');
    
    Route::get('/checkout/subscription/success', [CheckoutController::class, 'subscriptionSuccess'])
        ->name('checkout.subscription.success');
    
    Route::get('/checkout/subscription/cancel', [CheckoutController::class, 'subscriptionCancel'])
        ->name('checkout.subscription.cancel');
    
    // Payment Methods Management
    Route::get('/checkout/payment-methods', [CheckoutController::class, 'paymentMethods'])
        ->name('checkout.payment-methods');
    
    Route::post('/checkout/payment-methods/session', [CheckoutController::class, 'createSetupSession'])
        ->name('checkout.payment-methods.session');
    
    Route::get('/checkout/payment-methods/success', [CheckoutController::class, 'paymentMethodSuccess'])
        ->name('checkout.payment-methods.success');
    
    Route::delete('/checkout/payment-methods/{payment_method}', [CheckoutController::class, 'removePaymentMethod'])
        ->name('checkout.payment-methods.remove');
    
    Route::patch('/checkout/payment-methods/{payment_method}/default', [CheckoutController::class, 'setDefaultPaymentMethod'])
        ->name('checkout.payment-methods.default');
    
});

// Public routes (no authentication required)
Route::get('/pricing', function () {
    return view('pricing');
})->name('pricing');

Route::get('/demo', function () {
    return view('demo');
})->name('demo');