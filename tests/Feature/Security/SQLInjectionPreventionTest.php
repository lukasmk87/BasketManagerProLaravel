<?php

namespace Tests\Feature\Security;

use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use App\Models\ClubUsage;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SEC-005: SQL Injection Prevention Tests
 *
 * Tests that the increment/decrement security fix is in place.
 * The fix uses Eloquent's increment() method with (int) casting
 * instead of vulnerable DB::raw() expressions.
 *
 * Security issue: DB::raw("usage_count + $amount") was vulnerable to SQL injection
 * Fix: Using increment('usage_count', (int)$amount) which is parameterized
 */
class SQLInjectionPreventionTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Club $club;
    protected ClubSubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tenant
        $this->tenant = Tenant::factory()->create([
            'subscription_tier' => 'professional',
        ]);

        // Set tenant context
        app()->instance('tenant', $this->tenant);

        // Create subscription plan
        $this->plan = ClubSubscriptionPlan::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Plan',
            'limits' => [
                'max_teams' => 100,
                'max_players' => 1000,
            ],
        ]);

        // Create club
        $this->club = Club::factory()->create([
            'tenant_id' => $this->tenant->id,
            'club_subscription_plan_id' => $this->plan->id,
        ]);
    }

    /** @test */
    public function eloquent_increment_safely_casts_string_to_integer(): void
    {
        // Create a record
        $usage = ClubUsage::create([
            'club_id' => $this->club->id,
            'tenant_id' => $this->tenant->id,
            'metric' => 'max_teams',
            'usage_count' => 10,
            'period_start' => now()->startOfMonth(),
        ]);

        // Eloquent's increment with string should work via casting
        // This is the secure pattern used in ClubUsageTrackingService
        $usage->increment('usage_count', (int)'5');

        $this->assertEquals(15, $usage->fresh()->usage_count);
    }

    /** @test */
    public function increment_with_sql_injection_string_is_safely_cast(): void
    {
        // Create a record
        $usage = ClubUsage::create([
            'club_id' => $this->club->id,
            'tenant_id' => $this->tenant->id,
            'metric' => 'max_teams',
            'usage_count' => 10,
            'period_start' => now()->startOfMonth(),
        ]);

        // SQL injection attempt in the amount - gets cast to int (1)
        $maliciousAmount = '1; DROP TABLE club_usages;--';
        $usage->increment('usage_count', (int)$maliciousAmount);

        // Should be 11 (10 + 1), not SQL injected
        $this->assertEquals(11, $usage->fresh()->usage_count);

        // Table should still exist
        $this->assertDatabaseHas('club_usages', [
            'club_id' => $this->club->id,
        ]);
    }

    /** @test */
    public function increment_with_negative_injection_is_safely_cast(): void
    {
        $usage = ClubUsage::create([
            'club_id' => $this->club->id,
            'tenant_id' => $this->tenant->id,
            'metric' => 'max_teams',
            'usage_count' => 10,
            'period_start' => now()->startOfMonth(),
        ]);

        // Attempt negative injection
        $maliciousAmount = '-99 OR 1=1';
        $usage->increment('usage_count', (int)$maliciousAmount);

        // (int)'-99 OR 1=1' = -99
        // increment with negative effectively decrements
        $this->assertEquals(-89, $usage->fresh()->usage_count);
    }

    /** @test */
    public function increment_with_float_is_truncated(): void
    {
        $usage = ClubUsage::create([
            'club_id' => $this->club->id,
            'tenant_id' => $this->tenant->id,
            'metric' => 'max_teams',
            'usage_count' => 10,
            'period_start' => now()->startOfMonth(),
        ]);

        // Float should be truncated to int
        $usage->increment('usage_count', (int)5.9);

        $this->assertEquals(15, $usage->fresh()->usage_count);
    }

    /** @test */
    public function increment_with_null_is_zero(): void
    {
        $usage = ClubUsage::create([
            'club_id' => $this->club->id,
            'tenant_id' => $this->tenant->id,
            'metric' => 'max_teams',
            'usage_count' => 10,
            'period_start' => now()->startOfMonth(),
        ]);

        // Null cast to int is 0
        $usage->increment('usage_count', (int)null);

        $this->assertEquals(10, $usage->fresh()->usage_count);
    }

    /** @test */
    public function increment_with_large_number_works(): void
    {
        $usage = ClubUsage::create([
            'club_id' => $this->club->id,
            'tenant_id' => $this->tenant->id,
            'metric' => 'max_teams',
            'usage_count' => 10,
            'period_start' => now()->startOfMonth(),
        ]);

        // Large number should work (not overflow into SQL injection)
        $usage->increment('usage_count', (int)999999);

        $this->assertEquals(1000009, $usage->fresh()->usage_count);
    }

    /** @test */
    public function decrement_with_sql_injection_string_is_safely_cast(): void
    {
        $usage = ClubUsage::create([
            'club_id' => $this->club->id,
            'tenant_id' => $this->tenant->id,
            'metric' => 'max_teams',
            'usage_count' => 10,
            'period_start' => now()->startOfMonth(),
        ]);

        // SQL injection in decrement
        $maliciousAmount = '5; DELETE FROM club_usages;--';
        $usage->decrement('usage_count', (int)$maliciousAmount);

        // Should be 5 (10 - 5), table should exist
        $this->assertEquals(5, $usage->fresh()->usage_count);

        $this->assertDatabaseHas('club_usages', [
            'club_id' => $this->club->id,
        ]);
    }

    /** @test */
    public function increment_is_parameterized_not_raw_sql(): void
    {
        // This test verifies that Eloquent's increment uses parameterized queries
        // The actual SQL generated is: UPDATE ... SET usage_count = usage_count + ?
        // where ? is a bound parameter, not concatenated string

        $usage = ClubUsage::create([
            'club_id' => $this->club->id,
            'tenant_id' => $this->tenant->id,
            'metric' => 'test_secure',
            'usage_count' => 0,
            'period_start' => now()->startOfMonth(),
        ]);

        // Even with special characters, parameterized query is safe
        $specialChars = "1'; SELECT * FROM users; --";
        $usage->increment('usage_count', (int)$specialChars);

        // (int)"1'; SELECT..." = 1
        $this->assertEquals(1, $usage->fresh()->usage_count);
    }

    /** @test */
    public function multiple_increments_are_all_safe(): void
    {
        $usage = ClubUsage::create([
            'club_id' => $this->club->id,
            'tenant_id' => $this->tenant->id,
            'metric' => 'max_teams',
            'usage_count' => 0,
            'period_start' => now()->startOfMonth(),
        ]);

        // Multiple increments with various malicious inputs
        $usage->increment('usage_count', (int)'3');
        $usage->increment('usage_count', (int)'2; TRUNCATE club_usages;');
        $usage->increment('usage_count', (int)1.5);
        $usage->increment('usage_count', (int)"4' OR '1'='1");

        // Should be 3 + 2 + 1 + 4 = 10
        $this->assertEquals(10, $usage->fresh()->usage_count);
    }

    /** @test */
    public function verify_service_code_uses_int_cast(): void
    {
        // Read the actual service code to verify the fix is in place
        $clubUsageServicePath = app_path('Services/ClubUsageTrackingService.php');
        $featureGateServicePath = app_path('Services/FeatureGateService.php');

        // Check ClubUsageTrackingService
        $clubUsageCode = file_get_contents($clubUsageServicePath);
        $this->assertStringContainsString('(int)$amount', $clubUsageCode,
            'ClubUsageTrackingService should cast amount to int');
        $this->assertStringContainsString("->increment('usage_count'", $clubUsageCode,
            'ClubUsageTrackingService should use increment() method');

        // Check FeatureGateService
        $featureGateCode = file_get_contents($featureGateServicePath);
        $this->assertStringContainsString('(int)$amount', $featureGateCode,
            'FeatureGateService should cast amount to int');
        $this->assertStringContainsString("->increment('usage_count'", $featureGateCode,
            'FeatureGateService should use increment() method');
    }

    /** @test */
    public function verify_no_db_raw_with_amount_in_services(): void
    {
        // Verify that DB::raw is NOT used with $amount (the vulnerable pattern)
        $clubUsageServicePath = app_path('Services/ClubUsageTrackingService.php');
        $featureGateServicePath = app_path('Services/FeatureGateService.php');

        $clubUsageCode = file_get_contents($clubUsageServicePath);
        $featureGateCode = file_get_contents($featureGateServicePath);

        // The old vulnerable pattern was: DB::raw("usage_count + $amount")
        // This should NOT exist in the code anymore
        $this->assertStringNotContainsString('DB::raw("usage_count + $amount")', $clubUsageCode,
            'ClubUsageTrackingService should not use vulnerable DB::raw pattern');
        $this->assertStringNotContainsString('DB::raw("usage_count + $amount")', $featureGateCode,
            'FeatureGateService should not use vulnerable DB::raw pattern');
    }
}
