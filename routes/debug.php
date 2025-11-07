<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;

/**
 * Temporary debug routes to diagnose Super Admin 403 error
 * DELETE THIS FILE AFTER DEBUGGING
 */

Route::middleware(['web', 'auth'])->prefix('debug')->group(function () {

    Route::get('/user-info', function () {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        return response()->json([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'tenant_id' => $user->tenant_id,
            'roles' => $user->roles->pluck('name'),
            'permissions_count' => $user->getAllPermissions()->count(),
            'has_super_admin_role' => $user->hasRole('super_admin'),
            'has_access_admin_panel' => $user->hasPermissionTo('access admin panel'),
            'can_access_admin_panel_gate' => Gate::allows('access admin panel'),
            'email_verified' => $user->hasVerifiedEmail(),
        ]);
    });

    Route::get('/test-authorize', function () {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        try {
            Gate::authorize('access admin panel');
            return response()->json([
                'success' => true,
                'message' => 'Authorization successful!',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'roles' => $user->roles->pluck('name'),
                ]
            ]);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Authorization failed',
                'message' => $e->getMessage(),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'roles' => $user->roles->pluck('name'),
                ]
            ], 403);
        }
    });

    Route::get('/test-settings-access', function () {
        return app(\App\Http\Controllers\AdminPanelController::class)->settings(request());
    })->middleware(['role:admin|super_admin']);

});
