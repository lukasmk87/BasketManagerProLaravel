<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\SubscriptionPlanController;
use App\Http\Controllers\Admin\TenantSubscriptionController;
use App\Http\Controllers\Admin\UsageLimitsController;
use App\Http\Controllers\Admin\ClubTransferController;

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

    // Club Transfer Management
    Route::get('club-transfers', [ClubTransferController::class, 'index'])->name('club-transfers.index');
    Route::get('club-transfers/{transfer}', [ClubTransferController::class, 'show'])->name('club-transfers.show');
    Route::post('clubs/{club}/transfer-preview', [ClubTransferController::class, 'preview'])->name('clubs.transfer.preview');
    Route::post('clubs/{club}/transfer', [ClubTransferController::class, 'store'])->name('clubs.transfer');
    Route::post('clubs/batch-transfer', [ClubTransferController::class, 'batchStore'])->name('clubs.batch-transfer');
    Route::post('club-transfers/{transfer}/rollback', [ClubTransferController::class, 'rollback'])->name('club-transfers.rollback');
    Route::delete('club-transfers/{transfer}', [ClubTransferController::class, 'destroy'])->name('club-transfers.destroy');

    // Club Plan Assignment
    Route::put('clubs/{club}/plan', [ClubTransferController::class, 'updatePlan'])->name('clubs.plan.update');
});
