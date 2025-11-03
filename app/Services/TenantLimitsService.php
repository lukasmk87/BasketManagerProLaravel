<?php

namespace App\Services;

/**
 * Tenant Limits Service
 *
 * Centralized service for managing subscription tier limits and features.
 * Used by both the Installation Wizard and Tenant Initialization Command.
 *
 * @package App\Services
 */
class TenantLimitsService
{
    /**
     * Available subscription tiers
     */
    public const TIER_FREE = 'free';
    public const TIER_BASIC = 'basic';
    public const TIER_PROFESSIONAL = 'professional';
    public const TIER_ENTERPRISE = 'enterprise';

    /**
     * Unlimited value constant
     */
    public const UNLIMITED = -1;

    /**
     * Get subscription limits for a given tier
     *
     * @param string $tier Subscription tier (free, basic, professional, enterprise)
     * @return array{max_users: int, max_teams: int, max_storage_gb: int, max_api_calls_per_hour: int, features: array<string, bool>}
     */
    public static function getLimits(string $tier): array
    {
        $limits = [
            self::TIER_FREE => [
                'max_users' => 10,
                'max_teams' => 5,
                'max_storage_gb' => 5,
                'max_api_calls_per_hour' => 100,
                'features' => [
                    'basic_stats' => true,
                    'team_management' => true,
                    'player_roster' => true,
                ],
            ],
            self::TIER_BASIC => [
                'max_users' => 50,
                'max_teams' => 20,
                'max_storage_gb' => 50,
                'max_api_calls_per_hour' => 1000,
                'features' => [
                    'basic_stats' => true,
                    'advanced_stats' => true,
                    'team_management' => true,
                    'player_roster' => true,
                    'training_management' => true,
                    'game_scheduling' => true,
                ],
            ],
            self::TIER_PROFESSIONAL => [
                'max_users' => 200,
                'max_teams' => 50,
                'max_storage_gb' => 200,
                'max_api_calls_per_hour' => 5000,
                'features' => [
                    'basic_stats' => true,
                    'advanced_stats' => true,
                    'team_management' => true,
                    'player_roster' => true,
                    'training_management' => true,
                    'game_scheduling' => true,
                    'live_scoring' => true,
                    'video_analysis' => true,
                    'tournament_management' => true,
                    'api_access' => true,
                ],
            ],
            self::TIER_ENTERPRISE => [
                'max_users' => self::UNLIMITED,
                'max_teams' => self::UNLIMITED,
                'max_storage_gb' => self::UNLIMITED,
                'max_api_calls_per_hour' => self::UNLIMITED,
                'features' => [
                    'basic_stats' => true,
                    'advanced_stats' => true,
                    'team_management' => true,
                    'player_roster' => true,
                    'training_management' => true,
                    'game_scheduling' => true,
                    'live_scoring' => true,
                    'video_analysis' => true,
                    'tournament_management' => true,
                    'api_access' => true,
                    'white_label' => true,
                    'custom_domain' => true,
                    'priority_support' => true,
                    'sla_guarantee' => true,
                ],
            ],
        ];

        // Default to professional tier if invalid tier provided
        return $limits[$tier] ?? $limits[self::TIER_PROFESSIONAL];
    }

    /**
     * Get all available tiers
     *
     * @return array<string>
     */
    public static function getAvailableTiers(): array
    {
        return [
            self::TIER_FREE,
            self::TIER_BASIC,
            self::TIER_PROFESSIONAL,
            self::TIER_ENTERPRISE,
        ];
    }

    /**
     * Check if a tier is valid
     *
     * @param string $tier
     * @return bool
     */
    public static function isValidTier(string $tier): bool
    {
        return in_array($tier, self::getAvailableTiers(), true);
    }

    /**
     * Get human-readable tier name
     *
     * @param string $tier
     * @return string
     */
    public static function getTierDisplayName(string $tier): string
    {
        $names = [
            self::TIER_FREE => 'Free',
            self::TIER_BASIC => 'Basic',
            self::TIER_PROFESSIONAL => 'Professional',
            self::TIER_ENTERPRISE => 'Enterprise',
        ];

        return $names[$tier] ?? ucfirst($tier);
    }

    /**
     * Get limits formatted for display
     *
     * @param string $tier
     * @return array{users: string, teams: string, storage: string, api_calls: string, features: int}
     */
    public static function getFormattedLimits(string $tier): array
    {
        $limits = self::getLimits($tier);

        return [
            'users' => $limits['max_users'] === self::UNLIMITED ? 'Unlimited' : (string) $limits['max_users'],
            'teams' => $limits['max_teams'] === self::UNLIMITED ? 'Unlimited' : (string) $limits['max_teams'],
            'storage' => $limits['max_storage_gb'] === self::UNLIMITED ? 'Unlimited' : $limits['max_storage_gb'] . ' GB',
            'api_calls' => $limits['max_api_calls_per_hour'] === self::UNLIMITED ? 'Unlimited' : number_format($limits['max_api_calls_per_hour']) . '/hour',
            'features' => count($limits['features']),
        ];
    }

    /**
     * Check if a feature is available for a given tier
     *
     * @param string $tier
     * @param string $feature
     * @return bool
     */
    public static function hasFeature(string $tier, string $feature): bool
    {
        $limits = self::getLimits($tier);
        return $limits['features'][$feature] ?? false;
    }

    /**
     * Get all features for a given tier
     *
     * @param string $tier
     * @return array<string, bool>
     */
    public static function getFeatures(string $tier): array
    {
        $limits = self::getLimits($tier);
        return $limits['features'];
    }
}
