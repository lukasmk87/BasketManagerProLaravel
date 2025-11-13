<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds 42 missing permissions to bring total from 94 to 136.
     */
    public function up(): void
    {
        // Reset cached permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define the 42 missing permissions
        $missingPermissions = [
            // Video Management (6 permissions)
            'view videos',
            'upload videos',
            'edit videos',
            'delete videos',
            'annotate videos',
            'manage video analysis',

            // Gym/Facility Management (7 permissions)
            'view gym halls',
            'create gym halls',
            'edit gym halls',
            'delete gym halls',
            'view bookings',
            'create bookings',
            'manage bookings',

            // Federation Integration (4 permissions)
            'view federation data',
            'sync federation data',
            'manage dbb integration',
            'manage fiba integration',

            // Machine Learning & Analytics (4 permissions)
            'view ml models',
            'train ml models',
            'view predictions',
            'manage ml datasets',

            // Shot Charts & Advanced Stats (3 permissions)
            'view shot charts',
            'edit shot charts',
            'export shot charts',

            // Push Notifications (3 permissions)
            'manage push subscriptions',
            'send push notifications',
            'view notification analytics',

            // API Management (3 permissions)
            'view api usage',
            'manage api tokens',
            'view api logs',

            // Security Management (3 permissions)
            'view security logs',
            'manage 2fa settings',
            'manage security policies',

            // User Preferences (2 permissions)
            'view user preferences',
            'edit user preferences',

            // File Management (2 permissions)
            'upload files',
            'manage file storage',

            // Import/Export Features (3 permissions)
            'import games',
            'import users',
            'import data',

            // PWA Management (2 permissions)
            'manage pwa settings',
            'update service worker',
        ];

        // Create all missing permissions
        foreach ($missingPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        // Assign permissions to roles
        $this->assignPermissionsToRoles($missingPermissions);

        // Ensure Super Admin has ALL permissions (failsafe)
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(Permission::all());
        }

        // Clear permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Assign new permissions to appropriate roles.
     */
    protected function assignPermissionsToRoles(array $newPermissions): void
    {
        // Admin gets almost all permissions (except some advanced features reserved for super_admin)
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo([
                // Video Management
                'view videos', 'upload videos', 'edit videos', 'delete videos',
                'annotate videos', 'manage video analysis',

                // Gym/Facility Management
                'view gym halls', 'create gym halls', 'edit gym halls', 'delete gym halls',
                'view bookings', 'create bookings', 'manage bookings',

                // Federation Integration
                'view federation data', 'sync federation data',
                'manage dbb integration', 'manage fiba integration',

                // ML & Analytics
                'view ml models', 'train ml models', 'view predictions', 'manage ml datasets',

                // Shot Charts
                'view shot charts', 'edit shot charts', 'export shot charts',

                // Push Notifications
                'manage push subscriptions', 'send push notifications', 'view notification analytics',

                // API Management
                'view api usage', 'manage api tokens', 'view api logs',

                // Security
                'view security logs', 'manage 2fa settings', 'manage security policies',

                // User Preferences
                'view user preferences', 'edit user preferences',

                // File Management
                'upload files', 'manage file storage',

                // Import/Export
                'import games', 'import users', 'import data',

                // PWA
                'manage pwa settings', 'update service worker',
            ]);
        }

        // Club Admin gets club-scoped permissions
        $clubAdmin = Role::where('name', 'club_admin')->first();
        if ($clubAdmin) {
            $clubAdmin->givePermissionTo([
                // Video Management (limited)
                'view videos', 'upload videos', 'annotate videos',

                // Gym/Facility Management
                'view gym halls', 'create gym halls', 'edit gym halls',
                'view bookings', 'create bookings', 'manage bookings',

                // Federation Integration (view only)
                'view federation data',

                // Shot Charts
                'view shot charts', 'export shot charts',

                // Push Notifications
                'send push notifications', 'view notification analytics',

                // File Management
                'upload files',

                // Import/Export
                'import games', 'import users',
            ]);
        }

        // Trainer gets team-scoped permissions
        $trainer = Role::where('name', 'trainer')->first();
        if ($trainer) {
            $trainer->givePermissionTo([
                // Video Management
                'view videos', 'upload videos', 'annotate videos',

                // Gym/Facility Management (limited)
                'view gym halls', 'view bookings',

                // Shot Charts
                'view shot charts', 'edit shot charts',

                // File Management
                'upload files',

                // User Preferences
                'view user preferences', 'edit user preferences',
            ]);
        }

        // Assistant Coach gets limited permissions
        $assistantCoach = Role::where('name', 'assistant_coach')->first();
        if ($assistantCoach) {
            $assistantCoach->givePermissionTo([
                'view videos',
                'view gym halls',
                'view shot charts',
                'view user preferences',
            ]);
        }

        // Player gets view permissions
        $player = Role::where('name', 'player')->first();
        if ($player) {
            $player->givePermissionTo([
                'view videos',
                'view gym halls',
                'view bookings',
                'view shot charts',
                'view user preferences',
                'edit user preferences',
            ]);
        }

        // Parent gets minimal view permissions
        $parent = Role::where('name', 'parent')->first();
        if ($parent) {
            $parent->givePermissionTo([
                'view videos',
                'view user preferences',
                'edit user preferences',
            ]);
        }

        // Team Manager gets management permissions
        $teamManager = Role::where('name', 'team_manager')->first();
        if ($teamManager) {
            $teamManager->givePermissionTo([
                'view videos',
                'upload videos',
                'view gym halls',
                'view bookings',
                'create bookings',
                'view shot charts',
                'upload files',
            ]);
        }

        // Guest gets no new permissions (view only existing)
        // Scorer gets no new permissions (focused on game scoring)
        // Referee gets no new permissions (focused on game officiating)
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset cached permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define the permissions to remove
        $permissionsToRemove = [
            // Video Management
            'view videos', 'upload videos', 'edit videos', 'delete videos',
            'annotate videos', 'manage video analysis',

            // Gym/Facility Management
            'view gym halls', 'create gym halls', 'edit gym halls', 'delete gym halls',
            'view bookings', 'create bookings', 'manage bookings',

            // Federation Integration
            'view federation data', 'sync federation data',
            'manage dbb integration', 'manage fiba integration',

            // ML & Analytics
            'view ml models', 'train ml models', 'view predictions', 'manage ml datasets',

            // Shot Charts
            'view shot charts', 'edit shot charts', 'export shot charts',

            // Push Notifications
            'manage push subscriptions', 'send push notifications', 'view notification analytics',

            // API Management
            'view api usage', 'manage api tokens', 'view api logs',

            // Security
            'view security logs', 'manage 2fa settings', 'manage security policies',

            // User Preferences
            'view user preferences', 'edit user preferences',

            // File Management
            'upload files', 'manage file storage',

            // Import/Export
            'import games', 'import users', 'import data',

            // PWA
            'manage pwa settings', 'update service worker',
        ];

        // Remove permissions
        foreach ($permissionsToRemove as $permissionName) {
            $permission = Permission::where('name', $permissionName)->first();
            if ($permission) {
                $permission->delete();
            }
        }

        // Clear permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
