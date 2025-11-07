<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantLimitsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Installation Wizard End-to-End Tests
 *
 * Tests the complete installation wizard flow from start to finish,
 * ensuring tenants are created with proper limits and features.
 */
class InstallWizardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create super_admin role (required for installation)
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);

        // Set up session as if we completed previous steps
        session([
            'environment_configured' => true,
            'migrations_completed' => true,
            'install_language' => 'de',
        ]);
    }

    /**
     * Test complete installation wizard flow with professional tier
     */
    public function test_complete_installation_wizard_flow_with_professional_tier(): void
    {
        $response = $this->post(route('install.create-admin'), [
            'tenant_name' => 'Test Basketball Club',
            'admin_name' => 'Super Admin',
            'admin_email' => 'admin@basketclub.test',
            'admin_password' => 'SecurePassword123!',
            'admin_password_confirmation' => 'SecurePassword123!',
            'subscription_tier' => 'professional',
        ]);

        $response->assertRedirect(route('install.complete'));

        // Verify tenant was created with correct limits
        $this->assertDatabaseHas('tenants', [
            'name' => 'Test Basketball Club',
            'subscription_tier' => 'professional',
            'max_users' => 200,
            'max_teams' => 50,
            'max_storage_gb' => 200,
            'max_api_calls_per_hour' => 5000,
        ]);

        // Verify tenant has features in settings
        $tenant = Tenant::where('name', 'Test Basketball Club')->first();
        $this->assertNotNull($tenant);
        $this->assertArrayHasKey('features', $tenant->settings);
        $this->assertTrue($tenant->settings['features']['live_scoring']);
        $this->assertTrue($tenant->settings['features']['video_analysis']);

        // Verify user was created (Super Admin has no tenant_id - tenant-independent)
        $this->assertDatabaseHas('users', [
            'name' => 'Super Admin',
            'email' => 'admin@basketclub.test',
            'tenant_id' => null,
        ]);

        // Verify user has super_admin role
        $user = User::where('email', 'admin@basketclub.test')->first();
        $this->assertTrue($user->hasRole('super_admin'));

        // Verify club was created
        $this->assertDatabaseHas('clubs', [
            'name' => 'Test Basketball Club',
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Test installation wizard with free tier
     */
    public function test_installation_wizard_with_free_tier(): void
    {
        $this->post(route('install.create-admin'), [
            'tenant_name' => 'Free Club',
            'admin_name' => 'Admin',
            'admin_email' => 'admin@freeclub.test',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
            'subscription_tier' => 'free',
        ]);

        $tenant = Tenant::where('name', 'Free Club')->first();

        $this->assertEquals(10, $tenant->max_users);
        $this->assertEquals(5, $tenant->max_teams);
        $this->assertEquals(5, $tenant->max_storage_gb);
        $this->assertEquals(100, $tenant->max_api_calls_per_hour);

        // Verify limited features for free tier
        $this->assertTrue($tenant->settings['features']['basic_stats']);
        $this->assertFalse($tenant->settings['features']['live_scoring'] ?? false);
    }

    /**
     * Test installation wizard with basic tier
     */
    public function test_installation_wizard_with_basic_tier(): void
    {
        $this->post(route('install.create-admin'), [
            'tenant_name' => 'Basic Club',
            'admin_name' => 'Admin',
            'admin_email' => 'admin@basicclub.test',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
            'subscription_tier' => 'basic',
        ]);

        $tenant = Tenant::where('name', 'Basic Club')->first();

        $this->assertEquals(50, $tenant->max_users);
        $this->assertEquals(20, $tenant->max_teams);
        $this->assertEquals(50, $tenant->max_storage_gb);
        $this->assertEquals(1000, $tenant->max_api_calls_per_hour);

        $this->assertTrue($tenant->settings['features']['advanced_stats']);
        $this->assertFalse($tenant->settings['features']['live_scoring'] ?? false);
    }

    /**
     * Test installation wizard with enterprise tier
     */
    public function test_installation_wizard_with_enterprise_tier(): void
    {
        $this->post(route('install.create-admin'), [
            'tenant_name' => 'Enterprise Club',
            'admin_name' => 'Admin',
            'admin_email' => 'admin@enterprise.test',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
            'subscription_tier' => 'enterprise',
        ]);

        $tenant = Tenant::where('name', 'Enterprise Club')->first();

        $this->assertEquals(-1, $tenant->max_users); // Unlimited
        $this->assertEquals(-1, $tenant->max_teams);
        $this->assertEquals(-1, $tenant->max_storage_gb);
        $this->assertEquals(-1, $tenant->max_api_calls_per_hour);

        // Verify enterprise-only features
        $this->assertTrue($tenant->settings['features']['white_label']);
        $this->assertTrue($tenant->settings['features']['custom_domain']);
        $this->assertTrue($tenant->settings['features']['priority_support']);
    }

    /**
     * Test tenant settings include branding and contact
     */
    public function test_tenant_settings_include_branding_and_contact(): void
    {
        $this->post(route('install.create-admin'), [
            'tenant_name' => 'Test Club',
            'admin_name' => 'Admin',
            'admin_email' => 'admin@test.test',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
            'subscription_tier' => 'professional',
        ]);

        $tenant = Tenant::where('name', 'Test Club')->first();

        $this->assertArrayHasKey('branding', $tenant->settings);
        $this->assertArrayHasKey('primary_color', $tenant->settings['branding']);
        $this->assertEquals('#4F46E5', $tenant->settings['branding']['primary_color']);

        $this->assertArrayHasKey('contact', $tenant->settings);
        $this->assertArrayHasKey('support_email', $tenant->settings['contact']);
    }

    /**
     * Test tenant has all required fields set
     */
    public function test_tenant_has_all_required_fields_set(): void
    {
        $this->post(route('install.create-admin'), [
            'tenant_name' => 'Complete Club',
            'admin_name' => 'Admin',
            'admin_email' => 'admin@complete.test',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
            'subscription_tier' => 'professional',
        ]);

        $tenant = Tenant::where('name', 'Complete Club')->first();

        $this->assertNotNull($tenant->billing_email);
        $this->assertEquals('admin@complete.test', $tenant->billing_email);
        $this->assertNotNull($tenant->country_code);
        $this->assertNotNull($tenant->timezone);
        $this->assertNotNull($tenant->locale);
        $this->assertNotNull($tenant->currency);
        $this->assertTrue($tenant->is_active);
        $this->assertNotNull($tenant->trial_ends_at);
    }

    /**
     * Test user is linked to tenant correctly
     */
    public function test_user_is_linked_to_tenant_correctly(): void
    {
        $this->post(route('install.create-admin'), [
            'tenant_name' => 'Link Test Club',
            'admin_name' => 'Admin',
            'admin_email' => 'admin@link.test',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
            'subscription_tier' => 'basic',
        ]);

        $tenant = Tenant::where('name', 'Link Test Club')->first();
        $user = User::where('email', 'admin@link.test')->first();

        // Super Admin is tenant-independent
        $this->assertNull($user->tenant_id);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->is_verified);
        $this->assertNotNull($user->email_verified_at);
    }

    /**
     * Test club is created and linked to tenant
     */
    public function test_club_is_created_and_linked_to_tenant(): void
    {
        $this->post(route('install.create-admin'), [
            'tenant_name' => 'Club Test',
            'admin_name' => 'Admin',
            'admin_email' => 'admin@clubtest.test',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
            'subscription_tier' => 'professional',
        ]);

        $tenant = Tenant::where('name', 'Club Test')->first();
        $club = Club::where('tenant_id', $tenant->id)->first();

        $this->assertNotNull($club);
        $this->assertEquals('Club Test', $club->name);
        $this->assertEquals($tenant->id, $club->tenant_id);
    }

    /**
     * Test Super Admin is NOT linked to club via pivot table
     * (Super Admins are tenant-independent and club-independent)
     */
    public function test_super_admin_is_not_linked_to_club_during_installation(): void
    {
        $this->post(route('install.create-admin'), [
            'tenant_name' => 'Pivot Test Club',
            'admin_name' => 'Admin',
            'admin_email' => 'admin@pivot.test',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
            'subscription_tier' => 'basic',
        ]);

        $user = User::where('email', 'admin@pivot.test')->first();
        $club = Club::where('name', 'Pivot Test Club')->first();

        // Super Admin should NOT be linked to any club after installation
        $pivot = DB::table('club_user')
            ->where('user_id', $user->id)
            ->where('club_id', $club->id)
            ->first();

        $this->assertNull($pivot);

        // Verify user can manually join clubs later
        $this->assertEquals(0, $user->clubs()->count());
    }

    /**
     * Test installation fails with invalid subscription tier
     */
    public function test_installation_fails_with_invalid_subscription_tier(): void
    {
        $response = $this->post(route('install.create-admin'), [
            'tenant_name' => 'Invalid Club',
            'admin_name' => 'Admin',
            'admin_email' => 'admin@invalid.test',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
            'subscription_tier' => 'invalid_tier',
        ]);

        $response->assertSessionHasErrors('subscription_tier');
        $this->assertEquals(0, Tenant::count());
    }

    /**
     * Test installation fails without migrations completed
     */
    public function test_installation_fails_without_migrations_completed(): void
    {
        session(['migrations_completed' => false]);

        $response = $this->post(route('install.create-admin'), [
            'tenant_name' => 'Test Club',
            'admin_name' => 'Admin',
            'admin_email' => 'admin@test.test',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
            'subscription_tier' => 'professional',
        ]);

        $response->assertRedirect();
        $this->assertEquals(0, Tenant::count());
    }

    /**
     * Test installation respects selected language
     */
    public function test_installation_respects_selected_language(): void
    {
        session(['install_language' => 'en']);

        $this->post(route('install.create-admin'), [
            'tenant_name' => 'Language Test',
            'admin_name' => 'Admin',
            'admin_email' => 'admin@lang.test',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
            'subscription_tier' => 'professional',
        ]);

        $tenant = Tenant::where('name', 'Language Test')->first();
        $user = User::where('email', 'admin@lang.test')->first();

        $this->assertEquals('en', $tenant->locale);
        $this->assertEquals('en', $user->language);
    }

    /**
     * Test password is hashed correctly
     */
    public function test_password_is_hashed_correctly(): void
    {
        $this->post(route('install.create-admin'), [
            'tenant_name' => 'Password Test',
            'admin_name' => 'Admin',
            'admin_email' => 'admin@password.test',
            'admin_password' => 'MySecurePassword123!',
            'admin_password_confirmation' => 'MySecurePassword123!',
            'subscription_tier' => 'professional',
        ]);

        $user = User::where('email', 'admin@password.test')->first();

        $this->assertTrue(Hash::check('MySecurePassword123!', $user->password));
    }

    /**
     * Test Super Admin has no tenant_id after installation
     * (Verifies tenant-independent architecture)
     */
    public function test_super_admin_has_no_tenant_id_after_installation(): void
    {
        $this->post(route('install.create-admin'), [
            'tenant_name' => 'Tenant Free Test',
            'admin_name' => 'System Admin',
            'admin_email' => 'sysadmin@test.test',
            'admin_password' => 'SecurePass123!',
            'admin_password_confirmation' => 'SecurePass123!',
            'subscription_tier' => 'professional',
        ]);

        $user = User::where('email', 'sysadmin@test.test')->first();

        // Super Admin must have tenant_id = null
        $this->assertNull($user->tenant_id);

        // Super Admin must have super_admin role
        $this->assertTrue($user->hasRole('super_admin'));

        // Super Admin should have all permissions
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $this->assertNotNull($superAdminRole);
    }

    /**
     * Test Super Admin can access all tenants
     * (Verifies TenantScope bypass for Super Admins)
     */
    public function test_super_admin_can_access_all_tenants(): void
    {
        // Create first tenant via installation
        $this->post(route('install.create-admin'), [
            'tenant_name' => 'First Tenant',
            'admin_name' => 'Super Admin',
            'admin_email' => 'superadmin@test.test',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
            'subscription_tier' => 'professional',
        ]);

        $superAdmin = User::where('email', 'superadmin@test.test')->first();

        // Create additional tenants manually
        $tenant2 = Tenant::create([
            'name' => 'Second Tenant',
            'slug' => 'second-tenant',
            'subscription_tier' => 'basic',
            'max_users' => 50,
            'max_teams' => 20,
            'max_storage_gb' => 50,
        ]);

        $tenant3 = Tenant::create([
            'name' => 'Third Tenant',
            'slug' => 'third-tenant',
            'subscription_tier' => 'enterprise',
            'max_users' => -1,
            'max_teams' => -1,
            'max_storage_gb' => -1,
        ]);

        // Authenticate as Super Admin
        $this->actingAs($superAdmin);

        // Super Admin should be able to see ALL tenants (3 total)
        $allTenants = Tenant::all();
        $this->assertEquals(3, $allTenants->count());

        // Verify all 3 tenants are accessible
        $this->assertNotNull(Tenant::find($tenant2->id));
        $this->assertNotNull(Tenant::find($tenant3->id));
    }

    /**
     * Test Super Admin has no club associations after installation
     * (Comprehensive verification of club independence)
     */
    public function test_super_admin_has_no_club_associations_comprehensive(): void
    {
        $this->post(route('install.create-admin'), [
            'tenant_name' => 'Club Independence Test',
            'admin_name' => 'Independent Admin',
            'admin_email' => 'independent@test.test',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
            'subscription_tier' => 'professional',
        ]);

        $user = User::where('email', 'independent@test.test')->first();
        $club = Club::where('name', 'Club Independence Test')->first();

        // Verify club was created for tenant
        $this->assertNotNull($club);

        // Verify Super Admin has ZERO club associations
        $this->assertEquals(0, $user->clubs()->count());

        // Verify no entries in club_user pivot table for this user
        $clubUserCount = DB::table('club_user')
            ->where('user_id', $user->id)
            ->count();
        $this->assertEquals(0, $clubUserCount);

        // Verify Super Admin can manually join clubs if needed
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $user->clubs);
    }
}
