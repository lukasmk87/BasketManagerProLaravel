<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\SubscriptionPlanController;
use App\Http\Controllers\Admin\TenantSubscriptionController;
use App\Http\Controllers\Admin\UsageLimitsController;
use App\Http\Controllers\Admin\ClubTransferController;
use App\Http\Controllers\Admin\ClubSubscriptionPlanController;
use App\Http\Controllers\Admin\ClubInvoiceController;
use App\Http\Controllers\Admin\ClubInvoiceRequestController;

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

    // Club Subscription Plans Management
    Route::resource('club-plans', ClubSubscriptionPlanController::class)->parameters(['club-plans' => 'plan']);
    Route::post('club-plans/{plan}/clone', [ClubSubscriptionPlanController::class, 'clone'])->name('club-plans.clone');

    // Club Invoices Management
    Route::resource('invoices', ClubInvoiceController::class);
    Route::post('invoices/{invoice}/send', [ClubInvoiceController::class, 'send'])->name('invoices.send');
    Route::post('invoices/{invoice}/mark-paid', [ClubInvoiceController::class, 'markAsPaid'])->name('invoices.mark-paid');
    Route::post('invoices/{invoice}/cancel', [ClubInvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::post('invoices/{invoice}/reminder', [ClubInvoiceController::class, 'sendReminder'])->name('invoices.reminder');
    Route::get('invoices/{invoice}/pdf', [ClubInvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::get('invoices/{invoice}/pdf/preview', [ClubInvoiceController::class, 'previewPdf'])->name('invoices.pdf.preview');

    // Club Invoice Requests Management
    Route::get('invoice-requests', [ClubInvoiceRequestController::class, 'index'])->name('invoice-requests.index');
    Route::get('invoice-requests/{invoiceRequest}', [ClubInvoiceRequestController::class, 'show'])->name('invoice-requests.show');
    Route::post('invoice-requests/{invoiceRequest}/approve', [ClubInvoiceRequestController::class, 'approve'])->name('invoice-requests.approve');
    Route::post('invoice-requests/{invoiceRequest}/reject', [ClubInvoiceRequestController::class, 'reject'])->name('invoice-requests.reject');
});
