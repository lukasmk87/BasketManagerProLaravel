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

    // Tenant Management (CRUD)
    Route::get('tenants', [TenantSubscriptionController::class, 'index'])->name('tenants.index');
    Route::get('tenants/create', [TenantSubscriptionController::class, 'create'])->name('tenants.create');
    Route::post('tenants', [TenantSubscriptionController::class, 'store'])->name('tenants.store');
    Route::get('tenants/{tenant}', [TenantSubscriptionController::class, 'show'])->name('tenants.show');
    Route::get('tenants/{tenant}/edit', [TenantSubscriptionController::class, 'edit'])->name('tenants.edit');
    Route::put('tenants/{tenant}', [TenantSubscriptionController::class, 'update'])->name('tenants.update');
    Route::delete('tenants/{tenant}', [TenantSubscriptionController::class, 'destroy'])->name('tenants.destroy');

    // Tenant Selection for Super Admin Filtering
    Route::post('select-tenant/{tenant}', [TenantSubscriptionController::class, 'selectTenant'])->name('select-tenant');
    Route::delete('clear-tenant', [TenantSubscriptionController::class, 'clearTenantSelection'])->name('clear-tenant');

    // Tenant Subscription & Limits Management
    Route::put('tenants/{tenant}/subscription', [TenantSubscriptionController::class, 'updateSubscription'])->name('tenants.subscription.update');
    Route::put('tenants/{tenant}/limits', [TenantSubscriptionController::class, 'updateLimits'])->name('tenants.limits.update');
    Route::post('tenants/{tenant}/customization', [TenantSubscriptionController::class, 'createCustomization'])->name('tenants.customization.create');

    // Usage & Limits
    Route::get('usage/limits/{tenant}', [UsageLimitsController::class, 'getLimits'])->name('usage.limits');
    Route::get('usage/stats', [UsageLimitsController::class, 'getStats'])->name('usage.stats');
});
