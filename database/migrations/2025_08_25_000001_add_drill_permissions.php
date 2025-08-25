<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create drill-specific permissions
        $permissions = [
            'view drills',
            'create drills',
            'edit drills',
            'delete drills',
            'review drills',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // Assign permissions to roles
        $this->assignPermissionsToRoles();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove drill-specific permissions
        $permissions = [
            'view drills',
            'create drills', 
            'edit drills',
            'delete drills',
            'review drills',
        ];

        foreach ($permissions as $permission) {
            $perm = Permission::where('name', $permission)->first();
            if ($perm) {
                $perm->delete();
            }
        }
    }

    /**
     * Assign drill permissions to roles.
     */
    private function assignPermissionsToRoles(): void
    {
        // Super Admin - All drill permissions
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo([
                'view drills', 'create drills', 'edit drills', 'delete drills', 'review drills'
            ]);
        }

        // Admin - All drill permissions  
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo([
                'view drills', 'create drills', 'edit drills', 'delete drills', 'review drills'
            ]);
        }

        // Club Admin - All drill permissions
        $clubAdmin = Role::where('name', 'club_admin')->first();
        if ($clubAdmin) {
            $clubAdmin->givePermissionTo([
                'view drills', 'create drills', 'edit drills', 'delete drills', 'review drills'
            ]);
        }

        // Trainer - Most drill permissions (no review)
        $trainer = Role::where('name', 'trainer')->first();
        if ($trainer) {
            $trainer->givePermissionTo([
                'view drills', 'create drills', 'edit drills', 'delete drills'
            ]);
        }
    }
};