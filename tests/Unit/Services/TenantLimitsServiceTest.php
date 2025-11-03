<?php

namespace Tests\Unit\Services;

use App\Services\TenantLimitsService;
use Tests\TestCase;

class TenantLimitsServiceTest extends TestCase
{
    /**
     * Test getLimits returns correct structure for free tier
     */
    public function test_get_limits_returns_correct_structure_for_free_tier(): void
    {
        $limits = TenantLimitsService::getLimits(TenantLimitsService::TIER_FREE);

        $this->assertIsArray($limits);
        $this->assertArrayHasKey('max_users', $limits);
        $this->assertArrayHasKey('max_teams', $limits);
        $this->assertArrayHasKey('max_storage_gb', $limits);
        $this->assertArrayHasKey('max_api_calls_per_hour', $limits);
        $this->assertArrayHasKey('features', $limits);

        $this->assertEquals(10, $limits['max_users']);
        $this->assertEquals(5, $limits['max_teams']);
        $this->assertEquals(5, $limits['max_storage_gb']);
        $this->assertEquals(100, $limits['max_api_calls_per_hour']);
        $this->assertIsArray($limits['features']);
    }

    /**
     * Test getLimits returns correct structure for basic tier
     */
    public function test_get_limits_returns_correct_structure_for_basic_tier(): void
    {
        $limits = TenantLimitsService::getLimits(TenantLimitsService::TIER_BASIC);

        $this->assertEquals(50, $limits['max_users']);
        $this->assertEquals(20, $limits['max_teams']);
        $this->assertEquals(50, $limits['max_storage_gb']);
        $this->assertEquals(1000, $limits['max_api_calls_per_hour']);
        $this->assertArrayHasKey('basic_stats', $limits['features']);
        $this->assertArrayHasKey('advanced_stats', $limits['features']);
    }

    /**
     * Test getLimits returns correct structure for professional tier
     */
    public function test_get_limits_returns_correct_structure_for_professional_tier(): void
    {
        $limits = TenantLimitsService::getLimits(TenantLimitsService::TIER_PROFESSIONAL);

        $this->assertEquals(200, $limits['max_users']);
        $this->assertEquals(50, $limits['max_teams']);
        $this->assertEquals(200, $limits['max_storage_gb']);
        $this->assertEquals(5000, $limits['max_api_calls_per_hour']);
        $this->assertArrayHasKey('live_scoring', $limits['features']);
        $this->assertArrayHasKey('video_analysis', $limits['features']);
        $this->assertArrayHasKey('tournament_management', $limits['features']);
    }

    /**
     * Test getLimits returns correct structure for enterprise tier
     */
    public function test_get_limits_returns_correct_structure_for_enterprise_tier(): void
    {
        $limits = TenantLimitsService::getLimits(TenantLimitsService::TIER_ENTERPRISE);

        $this->assertEquals(TenantLimitsService::UNLIMITED, $limits['max_users']);
        $this->assertEquals(TenantLimitsService::UNLIMITED, $limits['max_teams']);
        $this->assertEquals(TenantLimitsService::UNLIMITED, $limits['max_storage_gb']);
        $this->assertEquals(TenantLimitsService::UNLIMITED, $limits['max_api_calls_per_hour']);
        $this->assertArrayHasKey('white_label', $limits['features']);
        $this->assertArrayHasKey('custom_domain', $limits['features']);
        $this->assertArrayHasKey('priority_support', $limits['features']);
        $this->assertArrayHasKey('sla_guarantee', $limits['features']);
    }

    /**
     * Test getLimits defaults to professional for invalid tier
     */
    public function test_get_limits_defaults_to_professional_for_invalid_tier(): void
    {
        $limits = TenantLimitsService::getLimits('invalid_tier');

        $this->assertEquals(200, $limits['max_users']);
        $this->assertEquals(50, $limits['max_teams']);
        $this->assertEquals(200, $limits['max_storage_gb']);
        $this->assertEquals(5000, $limits['max_api_calls_per_hour']);
    }

    /**
     * Test getAvailableTiers returns all tiers
     */
    public function test_get_available_tiers_returns_all_tiers(): void
    {
        $tiers = TenantLimitsService::getAvailableTiers();

        $this->assertIsArray($tiers);
        $this->assertCount(4, $tiers);
        $this->assertContains(TenantLimitsService::TIER_FREE, $tiers);
        $this->assertContains(TenantLimitsService::TIER_BASIC, $tiers);
        $this->assertContains(TenantLimitsService::TIER_PROFESSIONAL, $tiers);
        $this->assertContains(TenantLimitsService::TIER_ENTERPRISE, $tiers);
    }

    /**
     * Test isValidTier validates tiers correctly
     */
    public function test_is_valid_tier_validates_tiers_correctly(): void
    {
        $this->assertTrue(TenantLimitsService::isValidTier('free'));
        $this->assertTrue(TenantLimitsService::isValidTier('basic'));
        $this->assertTrue(TenantLimitsService::isValidTier('professional'));
        $this->assertTrue(TenantLimitsService::isValidTier('enterprise'));
        $this->assertFalse(TenantLimitsService::isValidTier('invalid'));
        $this->assertFalse(TenantLimitsService::isValidTier(''));
    }

    /**
     * Test getTierDisplayName returns correct display names
     */
    public function test_get_tier_display_name_returns_correct_display_names(): void
    {
        $this->assertEquals('Free', TenantLimitsService::getTierDisplayName('free'));
        $this->assertEquals('Basic', TenantLimitsService::getTierDisplayName('basic'));
        $this->assertEquals('Professional', TenantLimitsService::getTierDisplayName('professional'));
        $this->assertEquals('Enterprise', TenantLimitsService::getTierDisplayName('enterprise'));
        $this->assertEquals('Invalid', TenantLimitsService::getTierDisplayName('invalid'));
    }

    /**
     * Test getFormattedLimits returns formatted strings
     */
    public function test_get_formatted_limits_returns_formatted_strings(): void
    {
        $formatted = TenantLimitsService::getFormattedLimits('professional');

        $this->assertIsArray($formatted);
        $this->assertArrayHasKey('users', $formatted);
        $this->assertArrayHasKey('teams', $formatted);
        $this->assertArrayHasKey('storage', $formatted);
        $this->assertArrayHasKey('api_calls', $formatted);
        $this->assertArrayHasKey('features', $formatted);

        $this->assertEquals('200', $formatted['users']);
        $this->assertEquals('50', $formatted['teams']);
        $this->assertEquals('200 GB', $formatted['storage']);
        $this->assertEquals('5,000/hour', $formatted['api_calls']);
        $this->assertIsInt($formatted['features']);
    }

    /**
     * Test getFormattedLimits shows unlimited for enterprise
     */
    public function test_get_formatted_limits_shows_unlimited_for_enterprise(): void
    {
        $formatted = TenantLimitsService::getFormattedLimits('enterprise');

        $this->assertEquals('Unlimited', $formatted['users']);
        $this->assertEquals('Unlimited', $formatted['teams']);
        $this->assertEquals('Unlimited', $formatted['storage']);
        $this->assertEquals('Unlimited', $formatted['api_calls']);
    }

    /**
     * Test hasFeature checks features correctly
     */
    public function test_has_feature_checks_features_correctly(): void
    {
        $this->assertTrue(TenantLimitsService::hasFeature('free', 'basic_stats'));
        $this->assertFalse(TenantLimitsService::hasFeature('free', 'live_scoring'));

        $this->assertTrue(TenantLimitsService::hasFeature('professional', 'live_scoring'));
        $this->assertTrue(TenantLimitsService::hasFeature('professional', 'video_analysis'));
        $this->assertFalse(TenantLimitsService::hasFeature('professional', 'white_label'));

        $this->assertTrue(TenantLimitsService::hasFeature('enterprise', 'white_label'));
        $this->assertTrue(TenantLimitsService::hasFeature('enterprise', 'custom_domain'));
    }

    /**
     * Test getFeatures returns all features for tier
     */
    public function test_get_features_returns_all_features_for_tier(): void
    {
        $features = TenantLimitsService::getFeatures('professional');

        $this->assertIsArray($features);
        $this->assertArrayHasKey('basic_stats', $features);
        $this->assertArrayHasKey('advanced_stats', $features);
        $this->assertArrayHasKey('live_scoring', $features);
        $this->assertArrayHasKey('video_analysis', $features);
        $this->assertTrue($features['basic_stats']);
        $this->assertTrue($features['live_scoring']);
    }

    /**
     * Test feature progression across tiers
     */
    public function test_feature_progression_across_tiers(): void
    {
        $freeLimits = TenantLimitsService::getLimits('free');
        $basicLimits = TenantLimitsService::getLimits('basic');
        $professionalLimits = TenantLimitsService::getLimits('professional');
        $enterpriseLimits = TenantLimitsService::getLimits('enterprise');

        // Free has least features
        $this->assertLessThan(count($basicLimits['features']), count($freeLimits['features']));

        // Basic has more than free
        $this->assertLessThan(count($professionalLimits['features']), count($basicLimits['features']));

        // Professional has more than basic
        $this->assertLessThan(count($enterpriseLimits['features']), count($professionalLimits['features']));

        // All basic stats available in all tiers
        $this->assertTrue($freeLimits['features']['basic_stats']);
        $this->assertTrue($basicLimits['features']['basic_stats']);
        $this->assertTrue($professionalLimits['features']['basic_stats']);
        $this->assertTrue($enterpriseLimits['features']['basic_stats']);
    }

    /**
     * Test limits progression across tiers
     */
    public function test_limits_progression_across_tiers(): void
    {
        $freeLimits = TenantLimitsService::getLimits('free');
        $basicLimits = TenantLimitsService::getLimits('basic');
        $professionalLimits = TenantLimitsService::getLimits('professional');

        // Users progression
        $this->assertLessThan($basicLimits['max_users'], $freeLimits['max_users']);
        $this->assertLessThan($professionalLimits['max_users'], $basicLimits['max_users']);

        // Teams progression
        $this->assertLessThan($basicLimits['max_teams'], $freeLimits['max_teams']);
        $this->assertLessThan($professionalLimits['max_teams'], $basicLimits['max_teams']);

        // Storage progression
        $this->assertLessThan($basicLimits['max_storage_gb'], $freeLimits['max_storage_gb']);
        $this->assertLessThan($professionalLimits['max_storage_gb'], $basicLimits['max_storage_gb']);

        // API calls progression
        $this->assertLessThan($basicLimits['max_api_calls_per_hour'], $freeLimits['max_api_calls_per_hour']);
        $this->assertLessThan($professionalLimits['max_api_calls_per_hour'], $basicLimits['max_api_calls_per_hour']);
    }
}
