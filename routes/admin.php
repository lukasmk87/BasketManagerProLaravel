<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\SubscriptionPlanController;
use App\Http\Controllers\Admin\TenantSubscriptionController;
use App\Http\Controllers\Admin\UsageLimitsController;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Admin routes for subscription and tenant management.
| Only accessible by Super Admins or users with 'manage-subscriptions' permission.
|
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Subscription Plans Management
    Route::resource('plans', SubscriptionPlanController::class);
    Route::post('plans/{plan}/clone', [SubscriptionPlanController::class, 'clone'])->name('plans.clone');

    // Tenant Subscription Management
    Route::get('tenants', [TenantSubscriptionController::class, 'index'])->name('tenants.index');
    Route::get('tenants/{tenant}', [TenantSubscriptionController::class, 'show'])->name('tenants.show');
    Route::put('tenants/{tenant}/subscription', [TenantSubscriptionController::class, 'updateSubscription'])->name('tenants.subscription.update');
    Route::put('tenants/{tenant}/limits', [TenantSubscriptionController::class, 'updateLimits'])->name('tenants.limits.update');
    Route::post('tenants/{tenant}/customization', [TenantSubscriptionController::class, 'createCustomization'])->name('tenants.customization.create');

    // Usage & Limits
    Route::get('usage/limits/{tenant}', [UsageLimitsController::class, 'getLimits'])->name('usage.limits');
    Route::get('usage/stats', [UsageLimitsController::class, 'getStats'])->name('usage.stats');
});
