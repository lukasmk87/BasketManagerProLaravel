<?php

namespace Tests\Feature;

use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the permission seeder creates exactly 136 permissions.
     *
     * @return void
     */
    public function test_seeder_creates_correct_number_of_permissions(): void
    {
        // Arrange & Act
        $this->seed(RoleAndPermissionSeeder::class);

        // Assert
        $this->assertDatabaseCount('permissions', 136);
        $this->assertEquals(136, Permission::count());
    }

    /**
     * Test that all 11 roles are created.
     *
     * @return void
     */
    public function test_seeder_creates_all_roles(): void
    {
        // Arrange & Act
        $this->seed(RoleAndPermissionSeeder::class);

        // Assert
        $this->assertDatabaseCount('roles', 11);

        $expectedRoles = [
            'super_admin',
            'admin',
            'club_admin',
            'trainer',
            'assistant_coach',
            'scorer',
            'player',
            'parent',
            'team_manager',
            'guest',
            'referee',
        ];

        foreach ($expectedRoles as $roleName) {
            $this->assertDatabaseHas('roles', [
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }
    }

    /**
     * Test that Super Admin has all 136 permissions.
     *
     * @return void
     */
    public function test_super_admin_has_all_permissions(): void
    {
        // Arrange & Act
        $this->seed(RoleAndPermissionSeeder::class);

        // Assert
        $superAdmin = Role::where('name', 'super_admin')->first();
        $this->assertNotNull($superAdmin);
        $this->assertEquals(136, $superAdmin->permissions->count());
        $this->assertTrue($superAdmin->hasAllPermissions(Permission::all()));
    }

    /**
     * Test that Admin has all permissions except impersonate.
     *
     * @return void
     */
    public function test_admin_has_expected_permissions(): void
    {
        // Arrange & Act
        $this->seed(RoleAndPermissionSeeder::class);

        // Assert
        $admin = Role::where('name', 'admin')->first();
        $this->assertNotNull($admin);

        // Admin should have 135 permissions (all except 'impersonate users')
        $this->assertEquals(135, $admin->permissions->count());

        // Verify admin does NOT have impersonate users permission
        $this->assertFalse($admin->hasPermissionTo('impersonate users'));
    }

    /**
     * Test that all documented permission categories exist.
     *
     * @return void
     */
    public function test_all_permission_categories_exist(): void
    {
        // Arrange & Act
        $this->seed(RoleAndPermissionSeeder::class);

        // Assert - Test key permissions from each category
        $expectedPermissions = [
            // User Management
            'view users', 'create users', 'edit users', 'delete users', 'impersonate users', 'manage user roles',

            // Club Management
            'view clubs', 'create clubs', 'edit clubs', 'delete clubs',

            // Team Management
            'view teams', 'create teams', 'edit teams', 'delete teams',

            // Player Management
            'view players', 'create players', 'edit players', 'delete players',

            // Game Management
            'view games', 'create games', 'score games', 'view live games',

            // Training Management
            'view training sessions', 'create training sessions', 'manage training drills',

            // Drill Management
            'view drills', 'create drills', 'review drills',

            // Emergency Contacts
            'view emergency contacts', 'generate emergency qr codes',

            // System Administration
            'access admin panel', 'manage system settings',

            // Media Management
            'upload media', 'manage media library',

            // Tournament Management
            'view tournaments', 'create tournaments', 'manage tournament brackets',

            // Financial Management
            'view financial data', 'manage budgets',

            // GDPR & Compliance
            'export user data', 'handle data deletion requests',

            // Video Management (NEW)
            'view videos', 'upload videos', 'annotate videos', 'manage video analysis',

            // Gym/Facility Management (NEW)
            'view gym halls', 'create gym halls', 'view bookings', 'manage bookings',

            // Federation Integration (NEW)
            'view federation data', 'sync federation data', 'manage dbb integration',

            // ML & Analytics (NEW)
            'view ml models', 'train ml models', 'view predictions',

            // Shot Charts (NEW)
            'view shot charts', 'edit shot charts', 'export shot charts',

            // Push Notifications (NEW)
            'manage push subscriptions', 'send push notifications',

            // API Management (NEW)
            'view api usage', 'manage api tokens', 'view api logs',

            // Security (NEW)
            'view security logs', 'manage 2fa settings',

            // User Preferences (NEW)
            'view user preferences', 'edit user preferences',

            // File Management (NEW)
            'upload files', 'manage file storage',

            // Import/Export (NEW)
            'import games', 'import users', 'import data',

            // PWA Management (NEW)
            'manage pwa settings', 'update service worker',
        ];

        foreach ($expectedPermissions as $permissionName) {
            $this->assertDatabaseHas('permissions', [
                'name' => $permissionName,
                'guard_name' => 'web',
            ], "Permission '{$permissionName}' should exist in database");
        }
    }

    /**
     * Test that Club Admin has expected permission count.
     *
     * @return void
     */
    public function test_club_admin_has_expected_permission_count(): void
    {
        // Arrange & Act
        $this->seed(RoleAndPermissionSeeder::class);

        // Assert
        $clubAdmin = Role::where('name', 'club_admin')->first();
        $this->assertNotNull($clubAdmin);

        // Club Admin should have approximately 80 permissions (club-scoped)
        $permissionCount = $clubAdmin->permissions->count();
        $this->assertGreaterThanOrEqual(75, $permissionCount);
        $this->assertLessThanOrEqual(85, $permissionCount);
    }

    /**
     * Test that Trainer has expected permissions.
     *
     * @return void
     */
    public function test_trainer_has_expected_permissions(): void
    {
        // Arrange & Act
        $this->seed(RoleAndPermissionSeeder::class);

        // Assert
        $trainer = Role::where('name', 'trainer')->first();
        $this->assertNotNull($trainer);

        // Trainer should have key permissions
        $this->assertTrue($trainer->hasPermissionTo('view players'));
        $this->assertTrue($trainer->hasPermissionTo('edit players'));
        $this->assertTrue($trainer->hasPermissionTo('score games'));
        $this->assertTrue($trainer->hasPermissionTo('manage training drills'));
        $this->assertTrue($trainer->hasPermissionTo('view videos'));
        $this->assertTrue($trainer->hasPermissionTo('view shot charts'));

        // Trainer should NOT have deletion permissions
        $this->assertFalse($trainer->hasPermissionTo('delete clubs'));
        $this->assertFalse($trainer->hasPermissionTo('delete users'));
    }

    /**
     * Test that Player has limited view-only permissions.
     *
     * @return void
     */
    public function test_player_has_limited_permissions(): void
    {
        // Arrange & Act
        $this->seed(RoleAndPermissionSeeder::class);

        // Assert
        $player = Role::where('name', 'player')->first();
        $this->assertNotNull($player);

        // Player should have view permissions
        $this->assertTrue($player->hasPermissionTo('view teams'));
        $this->assertTrue($player->hasPermissionTo('view players'));
        $this->assertTrue($player->hasPermissionTo('view games'));
        $this->assertTrue($player->hasPermissionTo('view videos'));

        // Player should NOT have creation/deletion permissions
        $this->assertFalse($player->hasPermissionTo('create teams'));
        $this->assertFalse($player->hasPermissionTo('delete players'));
        $this->assertFalse($player->hasPermissionTo('score games'));
    }

    /**
     * Test that Guest has minimal permissions.
     *
     * @return void
     */
    public function test_guest_has_minimal_permissions(): void
    {
        // Arrange & Act
        $this->seed(RoleAndPermissionSeeder::class);

        // Assert
        $guest = Role::where('name', 'guest')->first();
        $this->assertNotNull($guest);

        // Guest should have only 3 basic view permissions
        $this->assertEquals(3, $guest->permissions->count());
        $this->assertTrue($guest->hasPermissionTo('view teams'));
        $this->assertTrue($guest->hasPermissionTo('view games'));
        $this->assertTrue($guest->hasPermissionTo('view statistics'));

        // Guest should NOT have any management permissions
        $this->assertFalse($guest->hasPermissionTo('create teams'));
        $this->assertFalse($guest->hasPermissionTo('edit games'));
        $this->assertFalse($guest->hasPermissionTo('view players'));
    }
}
