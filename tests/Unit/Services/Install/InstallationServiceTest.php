<?php

namespace Tests\Unit\Services\Install;

use App\Models\Club;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Install\InstallationService;
use App\Services\TenantLimitsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InstallationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InstallationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new InstallationService();

        // Create super_admin role (required for tests)
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    }

    /**
     * Test createSuperAdmin creates tenant with correct limits for professional tier
     */
    public function test_create_super_admin_creates_tenant_with_correct_limits_for_professional_tier(): void
    {
        $data = [
            'tenant_name' => 'Test Basketball Club',
            'subscription_tier' => 'professional',
            'admin_name' => 'Test Admin',
            'admin_email' => 'admin@test.com',
            'admin_password' => 'password123',
            'language' => 'de',
        ];

        $result = $this->service->createSuperAdmin($data);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('tenant', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('club', $result);

        $tenant = $result['tenant'];
        $this->assertEquals('Test Basketball Club', $tenant->name);
        $this->assertEquals('professional', $tenant->subscription_tier);

        // Check limits
        $this->assertEquals(200, $tenant->max_users);
        $this->assertEquals(50, $tenant->max_teams);
        $this->assertEquals(200, $tenant->max_storage_gb);
        $this->assertEquals(5000, $tenant->max_api_calls_per_hour);
    }

    /**
     * Test createSuperAdmin creates tenant with features in settings
     */
    public function test_create_super_admin_creates_tenant_with_features_in_settings(): void
    {
        $data = [
            'tenant_name' => 'Test Club',
            'subscription_tier' => 'professional',
            'admin_name' => 'Admin',
            'admin_email' => 'admin@test.com',
            'admin_password' => 'password',
            'language' => 'de',
        ];

        $result = $this->service->createSuperAdmin($data);
        $tenant = $result['tenant'];

        $this->assertIsArray($tenant->settings);
        $this->assertArrayHasKey('features', $tenant->settings);
        $this->assertIsArray($tenant->settings['features']);

        // Check for professional tier features
        $features = $tenant->settings['features'];
        $this->assertTrue($features['basic_stats']);
        $this->assertTrue($features['advanced_stats']);
        $this->assertTrue($features['live_scoring']);
        $this->assertTrue($features['video_analysis']);
        $this->assertTrue($features['tournament_management']);
    }

    /**
     * Test createSuperAdmin creates tenant with branding settings
     */
    public function test_create_super_admin_creates_tenant_with_branding_settings(): void
    {
        $data = [
            'tenant_name' => 'Test Club',
            'subscription_tier' => 'basic',
            'admin_name' => 'Admin',
            'admin_email' => 'admin@test.com',
            'admin_password' => 'password',
            'language' => 'de',
        ];

        $result = $this->service->createSuperAdmin($data);
        $tenant = $result['tenant'];

        $this->assertArrayHasKey('branding', $tenant->settings);
        $this->assertArrayHasKey('primary_color', $tenant->settings['branding']);
        $this->assertEquals('#4F46E5', $tenant->settings['branding']['primary_color']);
    }

    /**
     * Test createSuperAdmin creates tenant with contact settings
     */
    public function test_create_super_admin_creates_tenant_with_contact_settings(): void
    {
        $data = [
            'tenant_name' => 'Test Club',
            'subscription_tier' => 'basic',
            'admin_name' => 'Admin',
            'admin_email' => 'admin@test.com',
            'admin_password' => 'password',
            'language' => 'de',
        ];

        $result = $this->service->createSuperAdmin($data);
        $tenant = $result['tenant'];

        $this->assertArrayHasKey('contact', $tenant->settings);
        $this->assertArrayHasKey('support_email', $tenant->settings['contact']);
    }

    /**
     * Test createSuperAdmin sets billing_email correctly
     */
    public function test_create_super_admin_sets_billing_email_correctly(): void
    {
        $data = [
            'tenant_name' => 'Test Club',
            'subscription_tier' => 'basic',
            'admin_name' => 'Admin',
            'admin_email' => 'admin@example.com',
            'admin_password' => 'password',
            'language' => 'de',
        ];

        $result = $this->service->createSuperAdmin($data);
        $tenant = $result['tenant'];

        $this->assertEquals('admin@example.com', $tenant->billing_email);
    }

    /**
     * Test createSuperAdmin sets all required tenant fields
     */
    public function test_create_super_admin_sets_all_required_tenant_fields(): void
    {
        $data = [
            'tenant_name' => 'Test Club',
            'subscription_tier' => 'free',
            'admin_name' => 'Admin',
            'admin_email' => 'admin@test.com',
            'admin_password' => 'password',
            'language' => 'en',
        ];

        $result = $this->service->createSuperAdmin($data);
        $tenant = $result['tenant'];

        $this->assertNotNull($tenant->country_code);
        $this->assertNotNull($tenant->timezone);
        $this->assertNotNull($tenant->locale);
        $this->assertNotNull($tenant->currency);
        $this->assertTrue($tenant->is_active);
        $this->assertNotNull($tenant->trial_ends_at);
    }

    /**
     * Test createSuperAdmin creates user with correct attributes
     */
    public function test_create_super_admin_creates_user_with_correct_attributes(): void
    {
        $data = [
            'tenant_name' => 'Test Club',
            'subscription_tier' => 'basic',
            'admin_name' => 'John Doe',
            'admin_email' => 'john@test.com',
            'admin_password' => 'secret123',
            'language' => 'de',
        ];

        $result = $this->service->createSuperAdmin($data);
        $user = $result['user'];

        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@test.com', $user->email);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->is_verified);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('secret123', $user->password));
    }

    /**
     * Test createSuperAdmin links user to tenant
     */
    public function test_create_super_admin_links_user_to_tenant(): void
    {
        $data = [
            'tenant_name' => 'Test Club',
            'subscription_tier' => 'basic',
            'admin_name' => 'Admin',
            'admin_email' => 'admin@test.com',
            'admin_password' => 'password',
            'language' => 'de',
        ];

        $result = $this->service->createSuperAdmin($data);
        $user = $result['user'];
        $tenant = $result['tenant'];

        $this->assertEquals($tenant->id, $user->tenant_id);
    }

    /**
     * Test createSuperAdmin assigns super_admin role
     */
    public function test_create_super_admin_assigns_super_admin_role(): void
    {
        $data = [
            'tenant_name' => 'Test Club',
            'subscription_tier' => 'basic',
            'admin_name' => 'Admin',
            'admin_email' => 'admin@test.com',
            'admin_password' => 'password',
            'language' => 'de',
        ];

        $result = $this->service->createSuperAdmin($data);
        $user = $result['user'];

        $this->assertTrue($user->hasRole('super_admin'));
    }

    /**
     * Test createSuperAdmin creates default club
     */
    public function test_create_super_admin_creates_default_club(): void
    {
        $data = [
            'tenant_name' => 'Test Club',
            'subscription_tier' => 'basic',
            'admin_name' => 'Admin',
            'admin_email' => 'admin@test.com',
            'admin_password' => 'password',
            'language' => 'de',
        ];

        $result = $this->service->createSuperAdmin($data);
        $club = $result['club'];
        $tenant = $result['tenant'];

        $this->assertEquals('Test Club', $club->name);
        $this->assertEquals($tenant->id, $club->tenant_id);
    }

    /**
     * Test createSuperAdmin links user to club via pivot
     */
    public function test_create_super_admin_links_user_to_club_via_pivot(): void
    {
        $data = [
            'tenant_name' => 'Test Club',
            'subscription_tier' => 'basic',
            'admin_name' => 'Admin',
            'admin_email' => 'admin@test.com',
            'admin_password' => 'password',
            'language' => 'de',
        ];

        $result = $this->service->createSuperAdmin($data);
        $user = $result['user'];
        $club = $result['club'];

        $pivot = DB::table('club_user')
            ->where('user_id', $user->id)
            ->where('club_id', $club->id)
            ->first();

        $this->assertNotNull($pivot);
        $this->assertEquals('admin', $pivot->role);
    }

    /**
     * Test createSuperAdmin with different subscription tiers
     */
    public function test_create_super_admin_with_different_subscription_tiers(): void
    {
        $tiers = ['free', 'basic', 'professional', 'enterprise'];

        foreach ($tiers as $tier) {
            $data = [
                'tenant_name' => "Test Club {$tier}",
                'subscription_tier' => $tier,
                'admin_name' => 'Admin',
                'admin_email' => "admin-{$tier}@test.com",
                'admin_password' => 'password',
                'language' => 'de',
            ];

            $result = $this->service->createSuperAdmin($data);
            $tenant = $result['tenant'];

            $expectedLimits = TenantLimitsService::getLimits($tier);

            $this->assertEquals($expectedLimits['max_users'], $tenant->max_users);
            $this->assertEquals($expectedLimits['max_teams'], $tenant->max_teams);
            $this->assertEquals($expectedLimits['max_storage_gb'], $tenant->max_storage_gb);
            $this->assertEquals($expectedLimits['max_api_calls_per_hour'], $tenant->max_api_calls_per_hour);
        }
    }

    /**
     * Test createSuperAdmin rolls back on error
     */
    public function test_create_super_admin_rolls_back_on_error(): void
    {
        // Delete the role to cause an error
        Role::where('name', 'super_admin')->delete();

        $data = [
            'tenant_name' => 'Test Club',
            'subscription_tier' => 'basic',
            'admin_name' => 'Admin',
            'admin_email' => 'admin@test.com',
            'admin_password' => 'password',
            'language' => 'de',
        ];

        $result = $this->service->createSuperAdmin($data);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);

        // Ensure nothing was created
        $this->assertEquals(0, Tenant::count());
        $this->assertEquals(0, User::count());
        $this->assertEquals(0, Club::count());
    }
}
