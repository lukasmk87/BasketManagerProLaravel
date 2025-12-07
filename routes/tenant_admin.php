<?php

use App\Http\Controllers\TenantAdmin\VoucherController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Admin Routes
|--------------------------------------------------------------------------
|
| Routes for tenant-level administration.
| Accessible by users with tenant_admin role.
|
*/

Route::middleware(['auth', 'verified', 'tenant'])
    ->prefix('{locale}/tenant-admin')
    ->name('tenant-admin.')
    ->group(function () {
        // Voucher Management
        Route::resource('vouchers', VoucherController::class);
        Route::post('vouchers/{voucher}/toggle-active', [VoucherController::class, 'toggleActive'])
            ->name('vouchers.toggle-active');
    });
