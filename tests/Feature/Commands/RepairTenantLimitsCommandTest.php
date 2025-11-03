<?php

namespace Tests\Feature\Commands;

use App\Models\Tenant;
use App\Services\TenantLimitsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Repair Tenant Limits Command Tests
 *
 * Tests the tenant:repair-limits artisan command to ensure it correctly
 * updates existing tenants with proper subscription limits and features.
 */
class RepairTenantLimitsCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test command repairs tenant with missing limits
     */
    public function test_command_repairs_tenant_with_missing_limits(): void
    {
        // Create a tenant with incorrect/missing limits (simulating old installation wizard)
        $tenant = Tenant::create([
            'name' => 'Broken Tenant',
            'slug' => 'broken-tenant',
            'domain' => 'broken.test',
            'subscription_tier' => 'professional',
            'max_users' => 10,  // Wrong (should be 200)
            'max_teams' => 5,   // Wrong (should be 50)
            'max_storage_gb' => 5,  // Wrong (should be 200)
            'max_api_calls_per_hour' => 100,  // Wrong (should be 5000)
            'settings' => [
                'language' => 'de',
            ],
        ]);

        $this->artisan('tenant:repair-limits --force')
            ->assertExitCode(0);

        $tenant->refresh();

        $this->assertEquals(200, $tenant->max_users);
        $this->assertEquals(50, $tenant->max_teams);
        $this->assertEquals(200, $tenant->max_storage_gb);
        $this->assertEquals(5000, $tenant->max_api_calls_per_hour);
    }

    /**
     * Test command adds missing features to settings
     */
    public function test_command_adds_missing_features_to_settings(): void
    {
        $tenant = Tenant::create([
            'name' => 'No Features Tenant',
            'slug' => 'no-features',
            'domain' => 'nofeatures.test',
            'subscription_tier' => 'professional',
            'max_users' => 200,
            'max_teams' => 50,
            'max_storage_gb' => 200,
            'max_api_calls_per_hour' => 5000,
            'settings' => [
                'language' => 'de',
                // Missing 'features' key
            ],
        ]);

        $this->artisan('tenant:repair-limits --force')
            ->assertExitCode(0);

        $tenant->refresh();

        $this->assertArrayHasKey('features', $tenant->settings);
        $this->assertTrue($tenant->settings['features']['live_scoring']);
        $this->assertTrue($tenant->settings['features']['video_analysis']);
    }

    /**
     * Test command adds missing branding settings
     */
    public function test_command_adds_missing_branding_settings(): void
    {
        $tenant = Tenant::create([
            'name' => 'No Branding Tenant',
            'slug' => 'no-branding',
            'domain' => 'nobranding.test',
            'subscription_tier' => 'basic',
            'max_users' => 50,
            'max_teams' => 20,
            'max_storage_gb' => 50,
            'max_api_calls_per_hour' => 1000,
            'settings' => [
                'language' => 'de',
            ],
        ]);

        $this->artisan('tenant:repair-limits --force')
            ->assertExitCode(0);

        $tenant->refresh();

        $this->assertArrayHasKey('branding', $tenant->settings);
        $this->assertArrayHasKey('primary_color', $tenant->settings['branding']);
    }

    /**
     * Test command adds missing contact settings
     */
    public function test_command_adds_missing_contact_settings(): void
    {
        $tenant = Tenant::create([
            'name' => 'No Contact Tenant',
            'slug' => 'no-contact',
            'domain' => 'nocontact.test',
            'billing_email' => 'billing@test.com',
            'subscription_tier' => 'basic',
            'max_users' => 50,
            'max_teams' => 20,
            'max_storage_gb' => 50,
            'max_api_calls_per_hour' => 1000,
            'settings' => [
                'language' => 'de',
            ],
        ]);

        $this->artisan('tenant:repair-limits --force')
            ->assertExitCode(0);

        $tenant->refresh();

        $this->assertArrayHasKey('contact', $tenant->settings);
        $this->assertArrayHasKey('support_email', $tenant->settings['contact']);
    }

    /**
     * Test command adds missing required fields
     */
    public function test_command_adds_missing_required_fields(): void
    {
        $tenant = Tenant::create([
            'name' => 'Missing Fields Tenant',
            'slug' => 'missing-fields',
            'domain' => 'missing.test',
            'subscription_tier' => 'professional',
            'max_users' => 200,
            'max_teams' => 50,
            'max_storage_gb' => 200,
            'max_api_calls_per_hour' => 5000,
            // Missing: billing_email, country_code, locale, currency, is_active
            'settings' => [
                'language' => 'de',
            ],
        ]);

        $this->artisan('tenant:repair-limits --force')
            ->assertExitCode(0);

        $tenant->refresh();

        $this->assertNotNull($tenant->billing_email);
        $this->assertNotNull($tenant->country_code);
        $this->assertNotNull($tenant->locale);
        $this->assertNotNull($tenant->currency);
        $this->assertTrue($tenant->is_active);
    }

    /**
     * Test command skips tenants that are already correct
     */
    public function test_command_skips_tenants_that_are_already_correct(): void
    {
        $limits = TenantLimitsService::getLimits('professional');

        $tenant = Tenant::create([
            'name' => 'Correct Tenant',
            'slug' => 'correct-tenant',
            'domain' => 'correct.test',
            'billing_email' => 'billing@correct.test',
            'country_code' => 'DE',
            'timezone' => 'Europe/Berlin',
            'locale' => 'de',
            'currency' => 'EUR',
            'subscription_tier' => 'professional',
            'is_active' => true,
            'max_users' => $limits['max_users'],
            'max_teams' => $limits['max_teams'],
            'max_storage_gb' => $limits['max_storage_gb'],
            'max_api_calls_per_hour' => $limits['max_api_calls_per_hour'],
            'settings' => [
                'language' => 'de',
                'features' => $limits['features'],
                'branding' => ['primary_color' => '#4F46E5'],
                'contact' => ['support_email' => 'support@correct.test'],
            ],
        ]);

        $this->artisan('tenant:repair-limits --force')
            ->expectsOutput('  ✅ Already correct, skipping')
            ->assertExitCode(0);
    }

    /**
     * Test command dry-run mode does not modify database
     */
    public function test_command_dry_run_mode_does_not_modify_database(): void
    {
        $tenant = Tenant::create([
            'name' => 'Dry Run Tenant',
            'slug' => 'dry-run',
            'domain' => 'dryrun.test',
            'subscription_tier' => 'professional',
            'max_users' => 10,  // Wrong
            'max_teams' => 5,   // Wrong
            'max_storage_gb' => 5,  // Wrong
            'max_api_calls_per_hour' => 100,  // Wrong
            'settings' => ['language' => 'de'],
        ]);

        $this->artisan('tenant:repair-limits --dry-run')
            ->assertExitCode(0);

        $tenant->refresh();

        // Values should remain unchanged
        $this->assertEquals(10, $tenant->max_users);
        $this->assertEquals(5, $tenant->max_teams);
    }

    /**
     * Test command can repair specific tenant by ID
     */
    public function test_command_can_repair_specific_tenant_by_id(): void
    {
        $tenant1 = Tenant::create([
            'name' => 'Tenant 1',
            'slug' => 'tenant-1',
            'domain' => 'tenant1.test',
            'subscription_tier' => 'professional',
            'max_users' => 10,
            'settings' => ['language' => 'de'],
        ]);

        $tenant2 = Tenant::create([
            'name' => 'Tenant 2',
            'slug' => 'tenant-2',
            'domain' => 'tenant2.test',
            'subscription_tier' => 'basic',
            'max_users' => 10,
            'settings' => ['language' => 'de'],
        ]);

        $this->artisan("tenant:repair-limits --tenant={$tenant1->id} --force")
            ->assertExitCode(0);

        $tenant1->refresh();
        $tenant2->refresh();

        // Only tenant1 should be repaired
        $this->assertEquals(200, $tenant1->max_users);
        $this->assertEquals(10, $tenant2->max_users); // Unchanged
    }

    /**
     * Test command repairs multiple tenants with different tiers
     */
    public function test_command_repairs_multiple_tenants_with_different_tiers(): void
    {
        $freeTenant = Tenant::create([
            'name' => 'Free Tenant',
            'slug' => 'free-tenant',
            'domain' => 'free.test',
            'subscription_tier' => 'free',
            'max_users' => 1,  // Wrong
            'settings' => ['language' => 'de'],
        ]);

        $proTenant = Tenant::create([
            'name' => 'Pro Tenant',
            'slug' => 'pro-tenant',
            'domain' => 'pro.test',
            'subscription_tier' => 'professional',
            'max_users' => 10,  // Wrong
            'settings' => ['language' => 'de'],
        ]);

        $entTenant = Tenant::create([
            'name' => 'Enterprise Tenant',
            'slug' => 'ent-tenant',
            'domain' => 'ent.test',
            'subscription_tier' => 'enterprise',
            'max_users' => 100,  // Wrong
            'settings' => ['language' => 'de'],
        ]);

        $this->artisan('tenant:repair-limits --force')
            ->assertExitCode(0);

        $freeTenant->refresh();
        $proTenant->refresh();
        $entTenant->refresh();

        $this->assertEquals(10, $freeTenant->max_users);
        $this->assertEquals(200, $proTenant->max_users);
        $this->assertEquals(-1, $entTenant->max_users); // Unlimited
    }

    /**
     * Test command shows summary after execution
     */
    public function test_command_shows_summary_after_execution(): void
    {
        Tenant::create([
            'name' => 'Broken Tenant 1',
            'slug' => 'broken-1',
            'domain' => 'broken1.test',
            'subscription_tier' => 'professional',
            'max_users' => 10,
            'settings' => ['language' => 'de'],
        ]);

        Tenant::create([
            'name' => 'Broken Tenant 2',
            'slug' => 'broken-2',
            'domain' => 'broken2.test',
            'subscription_tier' => 'basic',
            'max_users' => 10,
            'settings' => ['language' => 'de'],
        ]);

        $this->artisan('tenant:repair-limits --force')
            ->expectsOutput('📊 Summary:')
            ->assertExitCode(0);
    }

    /**
     * Test command handles non-existent tenant ID gracefully
     */
    public function test_command_handles_non_existent_tenant_id_gracefully(): void
    {
        $this->artisan('tenant:repair-limits --tenant=99999 --force')
            ->expectsOutput('❌ Tenant not found: 99999')
            ->assertExitCode(0);
    }

    /**
     * Test command preserves existing custom settings
     */
    public function test_command_preserves_existing_custom_settings(): void
    {
        $tenant = Tenant::create([
            'name' => 'Custom Settings Tenant',
            'slug' => 'custom-settings',
            'domain' => 'custom.test',
            'subscription_tier' => 'professional',
            'max_users' => 10,  // Wrong, will be fixed
            'settings' => [
                'language' => 'en',
                'custom_field' => 'custom_value',
                'branding' => [
                    'logo_url' => 'https://custom-logo.com/logo.png',
                ],
            ],
        ]);

        $this->artisan('tenant:repair-limits --force')
            ->assertExitCode(0);

        $tenant->refresh();

        // New features should be added
        $this->assertArrayHasKey('features', $tenant->settings);

        // Custom settings should be preserved
        $this->assertEquals('en', $tenant->settings['language']);
        $this->assertEquals('custom_value', $tenant->settings['custom_field']);
        $this->assertEquals('https://custom-logo.com/logo.png', $tenant->settings['branding']['logo_url']);
    }
}
