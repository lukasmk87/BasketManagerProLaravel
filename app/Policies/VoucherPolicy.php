<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Voucher;

class VoucherPolicy
{
    /**
     * Super Admin can do everything.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any vouchers.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('tenant_admin');
    }

    /**
     * Determine whether the user can view the voucher.
     * Tenant Admin can only view their tenant's vouchers.
     */
    public function view(User $user, Voucher $voucher): bool
    {
        // System-wide vouchers are visible to tenant admins (read-only)
        if ($voucher->isSystemWide()) {
            return $user->hasRole('tenant_admin');
        }

        // Tenant-specific vouchers require matching tenant
        return $user->hasRole('tenant_admin') &&
            $this->userBelongsToVoucherTenant($user, $voucher);
    }

    /**
     * Determine whether the user can create vouchers.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('tenant_admin');
    }

    /**
     * Determine whether the user can update the voucher.
     * Tenant Admin can only update their tenant's vouchers, not system-wide ones.
     */
    public function update(User $user, Voucher $voucher): bool
    {
        // System-wide vouchers cannot be updated by tenant admins
        if ($voucher->isSystemWide()) {
            return false;
        }

        return $user->hasRole('tenant_admin') &&
            $this->userBelongsToVoucherTenant($user, $voucher);
    }

    /**
     * Determine whether the user can delete the voucher.
     * Tenant Admin can only delete their tenant's vouchers.
     */
    public function delete(User $user, Voucher $voucher): bool
    {
        // System-wide vouchers cannot be deleted by tenant admins
        if ($voucher->isSystemWide()) {
            return false;
        }

        return $user->hasRole('tenant_admin') &&
            $this->userBelongsToVoucherTenant($user, $voucher);
    }

    /**
     * Determine whether the user can restore the voucher.
     */
    public function restore(User $user, Voucher $voucher): bool
    {
        return $this->update($user, $voucher);
    }

    /**
     * Determine whether the user can permanently delete the voucher.
     */
    public function forceDelete(User $user, Voucher $voucher): bool
    {
        // Only super admin can force delete (handled by before())
        return false;
    }

    /**
     * Check if user belongs to the voucher's tenant.
     */
    private function userBelongsToVoucherTenant(User $user, Voucher $voucher): bool
    {
        if (! $voucher->tenant_id) {
            return false;
        }

        // Check via tenant_user pivot table
        return $user->tenants()
            ->where('tenants.id', $voucher->tenant_id)
            ->exists();
    }
}
