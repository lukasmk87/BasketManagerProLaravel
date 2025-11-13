<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions first
        $this->createPermissions();

        // Create roles and assign permissions
        $this->createRoles();
    }

    /**
     * Create all permissions for the basketball management system.
     */
    protected function createPermissions(): void
    {
        $permissions = [
            // User Management
            'view users',
            'create users',
            'edit users',
            'delete users',
            'impersonate users',
            'manage user roles',

            // Club Management
            'view clubs',
            'create clubs',
            'edit clubs',
            'delete clubs',
            'manage club settings',
            'manage club members',
            'view club statistics',

            // Team Management
            'view teams',
            'create teams',
            'edit teams',
            'delete teams',
            'manage team rosters',
            'assign team coaches',
            'view team statistics',
            'manage team settings',

            // Player Management
            'view players',
            'create players',
            'edit players',
            'delete players',
            'view player statistics',
            'edit player statistics',
            'manage player contracts',
            'view player medical info',
            'edit player medical info',

            // Game Management
            'view games',
            'create games',
            'edit games',
            'delete games',
            'score games',
            'view live games',
            'manage game officials',
            'publish game results',

            // Statistics & Analytics
            'view statistics',
            'export statistics',
            'generate reports',
            'view analytics dashboard',
            'manage statistics settings',

            // Training Management
            'view training sessions',
            'create training sessions',
            'edit training sessions',
            'delete training sessions',
            'manage training drills',
            'view training statistics',
            
            // Drill Management
            'view drills',
            'create drills',
            'edit drills',
            'delete drills',
            'review drills',

            // Emergency Contacts
            'view emergency contacts',
            'edit emergency contacts',
            'generate emergency qr codes',
            'access emergency information',

            // Communication
            'send notifications',
            'manage announcements',
            'access messaging system',

            // System Administration
            'access admin panel',
            'manage system settings',
            'view activity logs',
            'manage backups',
            'manage integrations',
            'view system statistics',

            // Media Management
            'upload media',
            'manage media library',
            'delete media',

            // Tournament Management
            'view tournaments',
            'create tournaments',
            'edit tournaments',
            'delete tournaments',
            'manage tournament brackets',

            // Financial Management
            'view financial data',
            'manage budgets',
            'generate financial reports',

            // GDPR & Compliance
            'export user data',
            'manage consent records',
            'handle data deletion requests',

            // Legal Pages Management
            'manage legal pages',

            // Landing Page Management
            'manage landing page',

            // Subscription & Tenant Management
            'manage-subscriptions',
            'view tenant subscriptions',
            'edit tenant subscriptions',
            'manage subscription plans',

            // Club Subscription Plans
            'view club subscription plans',
            'create club subscription plans',
            'update club subscription plans',
            'delete club subscription plans',

            // Player Registration Invitations
            'create player invitations',
            'manage player invitations',
            'assign pending players',

            // Video Management
            'view videos',
            'upload videos',
            'edit videos',
            'delete videos',
            'annotate videos',
            'manage video analysis',

            // Gym/Facility Management
            'view gym halls',
            'create gym halls',
            'edit gym halls',
            'delete gym halls',
            'view bookings',
            'create bookings',
            'manage bookings',

            // Federation Integration
            'view federation data',
            'sync federation data',
            'manage dbb integration',
            'manage fiba integration',

            // Machine Learning & Analytics
            'view ml models',
            'train ml models',
            'view predictions',
            'manage ml datasets',

            // Shot Charts & Advanced Stats
            'view shot charts',
            'edit shot charts',
            'export shot charts',

            // Push Notifications
            'manage push subscriptions',
            'send push notifications',
            'view notification analytics',

            // API Management
            'view api usage',
            'manage api tokens',
            'view api logs',

            // Security Management
            'view security logs',
            'manage 2fa settings',
            'manage security policies',

            // User Preferences
            'view user preferences',
            'edit user preferences',

            // File Management
            'upload files',
            'manage file storage',

            // Import/Export Features
            'import games',
            'import users',
            'import data',

            // PWA Management
            'manage pwa settings',
            'update service worker',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }
    }

    /**
     * Create roles and assign permissions.
     */
    protected function createRoles(): void
    {
        // Super Admin Role
        $superAdmin = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web'
        ]);
        $superAdmin->givePermissionTo(Permission::all());

        // System Administrator Role
        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web'
        ]);
        $admin->givePermissionTo([
            // User Management
            'view users', 'create users', 'edit users', 'delete users', 'manage user roles',
            
            // Club Management (all clubs)
            'view clubs', 'create clubs', 'edit clubs', 'delete clubs', 'manage club settings',
            'manage club members', 'view club statistics',
            
            // Team Management (all teams)
            'view teams', 'create teams', 'edit teams', 'delete teams', 'manage team rosters',
            'assign team coaches', 'view team statistics', 'manage team settings',
            
            // Player Management (all players)
            'view players', 'create players', 'edit players', 'delete players',
            'view player statistics', 'edit player statistics', 'manage player contracts',
            'view player medical info', 'edit player medical info',
            
            // Game Management
            'view games', 'create games', 'edit games', 'delete games', 'score games',
            'view live games', 'manage game officials', 'publish game results',
            
            // Statistics & Analytics
            'view statistics', 'export statistics', 'generate reports',
            'view analytics dashboard', 'manage statistics settings',
            
            // Training
            'view training sessions', 'create training sessions', 'edit training sessions',
            'delete training sessions', 'manage training drills', 'view training statistics',
            
            // Drills
            'view drills', 'create drills', 'edit drills', 'delete drills', 'review drills',
            
            // Emergency
            'view emergency contacts', 'edit emergency contacts', 'generate emergency qr codes',
            'access emergency information',
            
            // System
            'access admin panel', 'manage system settings', 'view activity logs',
            'manage backups', 'manage integrations', 'view system statistics',
            
            // Communication
            'send notifications', 'manage announcements', 'access messaging system',
            
            // Media
            'upload media', 'manage media library', 'delete media',
            
            // Tournament
            'view tournaments', 'create tournaments', 'edit tournaments',
            'delete tournaments', 'manage tournament brackets',
            
            // Financial
            'view financial data', 'manage budgets', 'generate financial reports',
            
            // GDPR
            'export user data', 'manage consent records', 'handle data deletion requests',

            // Legal Pages
            'manage legal pages',

            // Landing Page
            'manage landing page',

            // Subscription Management
            'manage-subscriptions', 'view tenant subscriptions', 'edit tenant subscriptions', 'manage subscription plans',

            // Club Subscription Plans
            'view club subscription plans', 'create club subscription plans', 'update club subscription plans', 'delete club subscription plans',

            // Player Registration Invitations
            'create player invitations', 'manage player invitations', 'assign pending players',

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

        // Club Administrator Role
        $clubAdmin = Role::firstOrCreate([
            'name' => 'club_admin',
            'guard_name' => 'web'
        ]);
        $clubAdmin->syncPermissions([
            // User Management (limited - can view/create/edit users in their clubs, but NOT delete)
            'view users', 'create users', 'edit users',
            
            // Club Management (own club only)
            'view clubs', 'edit clubs', 'manage club settings', 'manage club members',
            'view club statistics',
            
            // Team Management (club teams)
            'view teams', 'create teams', 'edit teams', 'manage team rosters',
            'assign team coaches', 'view team statistics', 'manage team settings',
            
            // Player Management (club players)
            'view players', 'create players', 'edit players',
            'view player statistics', 'manage player contracts',
            'view player medical info', 'edit player medical info',
            
            // Game Management
            'view games', 'create games', 'edit games', 'view live games',
            'manage game officials', 'publish game results',
            
            // Statistics
            'view statistics', 'export statistics', 'generate reports',
            'view analytics dashboard',
            
            // Training
            'view training sessions', 'create training sessions', 'edit training sessions',
            'delete training sessions', 'manage training drills', 'view training statistics',
            
            // Drills
            'view drills', 'create drills', 'edit drills', 'delete drills', 'review drills',
            
            // Emergency
            'view emergency contacts', 'edit emergency contacts', 'generate emergency qr codes',
            
            // Communication
            'send notifications', 'manage announcements', 'access messaging system',
            
            // Media
            'upload media', 'manage media library',
            
            // Tournament
            'view tournaments', 'create tournaments', 'edit tournaments',
            
            // Financial
            'view financial data', 'manage budgets', 'generate financial reports',

            // Landing Page (own tenant only)
            'manage landing page',

            // Club Subscription Plans (limited - only for their tenant)
            'view club subscription plans', 'create club subscription plans',

            // Player Registration Invitations
            'create player invitations', 'manage player invitations', 'assign pending players',

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

        // Head Coach/Trainer Role
        $trainer = Role::firstOrCreate([
            'name' => 'trainer',
            'guard_name' => 'web'
        ]);
        $trainer->givePermissionTo([
            // Team Management (assigned teams)
            'view teams', 'edit teams', 'manage team rosters', 'view team statistics',

            // Player Management (team players)
            'view players', 'create players', 'edit players',
            'view player statistics', 'edit player statistics',
            'view player medical info', 'edit player medical info',

            // Game Management
            'view games', 'create games', 'edit games', 'score games',
            'view live games', 'publish game results',

            // Statistics
            'view statistics', 'export statistics', 'generate reports',

            // Training
            'view training sessions', 'create training sessions', 'edit training sessions',
            'delete training sessions', 'manage training drills', 'view training statistics',

            // Drills
            'view drills', 'create drills', 'edit drills', 'delete drills', 'review drills',

            // Emergency
            'view emergency contacts', 'edit emergency contacts',

            // Communication
            'send notifications', 'access messaging system',

            // Media
            'upload media',

            // Player Registration Invitations (only create, not manage or assign)
            'create player invitations',

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

        // Assistant Coach Role
        $assistantCoach = Role::firstOrCreate([
            'name' => 'assistant_coach',
            'guard_name' => 'web'
        ]);
        $assistantCoach->givePermissionTo([
            // Team Management (limited)
            'view teams', 'view team statistics',
            
            // Player Management (limited)
            'view players', 'view player statistics', 'view player medical info',
            
            // Game Management (limited)
            'view games', 'score games', 'view live games',
            
            // Statistics
            'view statistics', 'export statistics',
            
            // Training
            'view training sessions', 'create training sessions', 'edit training sessions',
            'manage training drills', 'view training statistics',
            
            // Drills
            'view drills', 'create drills', 'edit drills', 'delete drills',
            
            // Emergency
            'view emergency contacts',
            
            // Communication
            'access messaging system',
            
            // Media
            'upload media',

            // Video Management (view only)
            'view videos',

            // Gym/Facility Management (view only)
            'view gym halls',

            // Shot Charts (view only)
            'view shot charts',

            // User Preferences
            'view user preferences',
        ]);

        // Scorer/Statistician Role
        $scorer = Role::firstOrCreate([
            'name' => 'scorer',
            'guard_name' => 'web'
        ]);
        $scorer->givePermissionTo([
            // Game Management (scoring only)
            'view games', 'score games', 'view live games',
            
            // Statistics
            'view statistics', 'export statistics',
            
            // Limited player info for scoring
            'view players', 'view player statistics',
        ]);

        // Player Role
        $player = Role::firstOrCreate([
            'name' => 'player',
            'guard_name' => 'web'
        ]);
        $player->givePermissionTo([
            // View own team info
            'view teams', 'view team statistics',
            
            // View players (teammates)
            'view players',
            
            // View own statistics
            'view player statistics',
            
            // View games
            'view games', 'view live games',
            
            // View statistics
            'view statistics',
            
            // View training
            'view training sessions', 'view training statistics',
            
            // Communication
            'access messaging system',

            // Video Management (view only)
            'view videos',

            // Gym/Facility Management
            'view gym halls', 'view bookings',

            // Shot Charts
            'view shot charts',

            // User Preferences
            'view user preferences', 'edit user preferences',
        ]);

        // Parent/Guardian Role
        $parent = Role::firstOrCreate([
            'name' => 'parent',
            'guard_name' => 'web'
        ]);
        $parent->givePermissionTo([
            // View child's team
            'view teams',
            
            // View players (child's teammates)
            'view players',
            
            // View child's statistics
            'view player statistics',
            
            // View games
            'view games',
            
            // Emergency contacts
            'view emergency contacts', 'edit emergency contacts',
            
            // Communication
            'access messaging system',

            // Video Management (view only)
            'view videos',

            // User Preferences
            'view user preferences', 'edit user preferences',
        ]);

        // Team Manager Role
        $teamManager = Role::firstOrCreate([
            'name' => 'team_manager',
            'guard_name' => 'web'
        ]);
        $teamManager->givePermissionTo([
            // Team Management
            'view teams', 'edit teams', 'manage team rosters', 'view team statistics',
            
            // Player Management
            'view players', 'edit players', 'view player statistics',
            
            // Game Management
            'view games', 'create games', 'edit games', 'view live games',
            
            // Training
            'view training sessions', 'create training sessions', 'edit training sessions',
            
            // Emergency
            'view emergency contacts', 'edit emergency contacts',
            
            // Communication
            'send notifications', 'access messaging system',
            
            // Media
            'upload media',

            // Video Management
            'view videos', 'upload videos',

            // Gym/Facility Management
            'view gym halls', 'view bookings', 'create bookings',

            // Shot Charts
            'view shot charts',

            // File Management
            'upload files',
        ]);

        // Guest/Fan Role
        $guest = Role::firstOrCreate([
            'name' => 'guest',
            'guard_name' => 'web'
        ]);
        $guest->givePermissionTo([
            // Limited view access
            'view teams',
            'view games',
            'view statistics',
        ]);

        // Referee Role
        $referee = Role::firstOrCreate([
            'name' => 'referee',
            'guard_name' => 'web'
        ]);
        $referee->givePermissionTo([
            // Game Management
            'view games', 'score games', 'view live games',
            
            // Player info for game management
            'view players',
            
            // Statistics
            'view statistics',
        ]);
    }
}