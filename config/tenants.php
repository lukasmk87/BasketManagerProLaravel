<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tenant Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all configuration related to the multi-tenant
    | architecture of BasketManager Pro.
    |
    */

    'base_domain' => env('APP_BASE_DOMAIN', 'basketmanager-pro.com'),

    /*
    |--------------------------------------------------------------------------
    | Subscription Tiers
    |--------------------------------------------------------------------------
    |
    | Define the available subscription tiers and their features/limits.
    |
    */
    'tiers' => [
        'free' => [
            'name' => 'Free',
            'price' => 0,
            'currency' => 'EUR',
            'trial_days' => 14,
            'features' => [
                'basic_team_management',
                'basic_player_profiles',
                'game_scheduling',
                'basic_statistics',
                'mobile_web_access',
            ],
            'limits' => [
                'users' => 10,
                'teams' => 2,
                'players' => 30,
                'storage_gb' => 5,
                'api_calls_per_hour' => 100,
                'games_per_month' => 20,
                'training_sessions_per_month' => 50,
            ],
        ],
        
        'basic' => [
            'name' => 'Basic',
            'price' => 49,
            'currency' => 'EUR',
            'billing_period' => 'monthly',
            'features' => [
                'basic_team_management',
                'basic_player_profiles',
                'game_scheduling',
                'basic_statistics',
                'mobile_web_access',
                'advanced_statistics',
                'live_scoring',
                'training_management',
                'emergency_contacts',
                'email_notifications',
                'basic_analytics',
            ],
            'limits' => [
                'users' => 50,
                'teams' => 5,
                'players' => 100,
                'storage_gb' => 20,
                'api_calls_per_hour' => 1000,
                'games_per_month' => 100,
                'training_sessions_per_month' => 200,
            ],
        ],
        
        'professional' => [
            'name' => 'Professional',
            'price' => 149,
            'currency' => 'EUR',
            'billing_period' => 'monthly',
            'features' => [
                'basic_team_management',
                'basic_player_profiles',
                'game_scheduling',
                'basic_statistics',
                'mobile_web_access',
                'advanced_statistics',
                'live_scoring',
                'training_management',
                'emergency_contacts',
                'email_notifications',
                'basic_analytics',
                'tournament_management',
                'video_analysis',
                'ai_insights',
                'custom_reports',
                'api_access',
                'push_notifications',
                'advanced_analytics',
                'data_export',
                'custom_branding',
            ],
            'limits' => [
                'users' => 200,
                'teams' => 20,
                'players' => 500,
                'storage_gb' => 100,
                'api_calls_per_hour' => 5000,
                'games_per_month' => 500,
                'training_sessions_per_month' => 1000,
                'video_storage_gb' => 50,
            ],
        ],
        
        'enterprise' => [
            'name' => 'Enterprise',
            'price' => 499,
            'currency' => 'EUR',
            'billing_period' => 'monthly',
            'custom_pricing' => true,
            'features' => [
                'basic_team_management',
                'basic_player_profiles',
                'game_scheduling',
                'basic_statistics',
                'mobile_web_access',
                'advanced_statistics',
                'live_scoring',
                'training_management',
                'emergency_contacts',
                'email_notifications',
                'basic_analytics',
                'tournament_management',
                'video_analysis',
                'ai_insights',
                'custom_reports',
                'api_access',
                'push_notifications',
                'advanced_analytics',
                'data_export',
                'custom_branding',
                'federation_integration',
                'white_label',
                'dedicated_support',
                'sla_guarantee',
                'custom_integrations',
                'unlimited_api',
                'multi_club_management',
                'advanced_security',
                'audit_logs',
                'compliance_tools',
            ],
            'limits' => [
                'users' => -1, // Unlimited
                'teams' => -1,
                'players' => -1,
                'storage_gb' => 1000,
                'api_calls_per_hour' => -1,
                'games_per_month' => -1,
                'training_sessions_per_month' => -1,
                'video_storage_gb' => 500,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting settings for tenants.
    |
    */
    'rate_limits' => [
        'default' => 1000, // requests per hour
        'burst' => 100, // requests per minute
        'api' => [
            'free' => 100,
            'basic' => 1000,
            'professional' => 5000,
            'enterprise' => -1, // unlimited
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Isolation
    |--------------------------------------------------------------------------
    |
    | Configure how tenants are isolated from each other.
    |
    */
    'isolation' => [
        'strategy' => env('TENANT_ISOLATION_STRATEGY', 'single_database'), // single_database, separate_database, separate_schema
        'cache_prefix' => true,
        'queue_prefix' => true,
        'session_prefix' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Resolution
    |--------------------------------------------------------------------------
    |
    | Configure how tenants are resolved from requests.
    |
    */
    'resolution' => [
        'methods' => ['domain', 'subdomain', 'header', 'session'],
        'header_name' => 'X-Tenant-ID',
        'session_key' => 'tenant_id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Defaults
    |--------------------------------------------------------------------------
    |
    | Default values for new tenants.
    |
    */
    'defaults' => [
        'timezone' => 'Europe/Berlin',
        'locale' => 'de',
        'currency' => 'EUR',
        'country_code' => 'DE',
        'subscription_tier' => 'free',
        'trial_days' => 14,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Features
    |--------------------------------------------------------------------------
    |
    | All available features that can be enabled/disabled per tenant.
    |
    */
    'features' => [
        'basic_team_management' => 'Basic Team Management',
        'basic_player_profiles' => 'Basic Player Profiles',
        'game_scheduling' => 'Game Scheduling',
        'basic_statistics' => 'Basic Statistics',
        'mobile_web_access' => 'Mobile Web Access',
        'advanced_statistics' => 'Advanced Statistics & Analytics',
        'live_scoring' => 'Live Game Scoring',
        'training_management' => 'Training Session Management',
        'emergency_contacts' => 'Emergency Contact System',
        'email_notifications' => 'Email Notifications',
        'push_notifications' => 'Push Notifications',
        'basic_analytics' => 'Basic Analytics Dashboard',
        'advanced_analytics' => 'Advanced Analytics & AI Insights',
        'tournament_management' => 'Tournament Management',
        'video_analysis' => 'Video Analysis & Highlights',
        'ai_insights' => 'AI-Powered Insights',
        'custom_reports' => 'Custom Report Generation',
        'api_access' => 'API Access',
        'data_export' => 'Data Export (CSV, PDF)',
        'custom_branding' => 'Custom Branding & White Label',
        'federation_integration' => 'Basketball Federation Integration',
        'white_label' => 'Complete White Label Solution',
        'dedicated_support' => 'Dedicated Support Manager',
        'sla_guarantee' => '99.9% SLA Guarantee',
        'custom_integrations' => 'Custom Third-party Integrations',
        'unlimited_api' => 'Unlimited API Access',
        'multi_club_management' => 'Multi-Club Management',
        'advanced_security' => 'Advanced Security Features',
        'audit_logs' => 'Comprehensive Audit Logs',
        'compliance_tools' => 'GDPR Compliance Tools',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Onboarding
    |--------------------------------------------------------------------------
    |
    | Configuration for the tenant onboarding process.
    |
    */
    'onboarding' => [
        'steps' => [
            'account_setup',
            'club_information',
            'team_setup',
            'invite_users',
            'import_data',
            'customize_branding',
            'payment_setup',
        ],
        'demo_data' => true, // Create demo data for new tenants
        'welcome_email' => true,
        'onboarding_tour' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Maintenance
    |--------------------------------------------------------------------------
    |
    | Configuration for tenant maintenance and cleanup.
    |
    */
    'maintenance' => [
        'inactive_days' => 90, // Days before marking tenant as inactive
        'deletion_days' => 365, // Days before deleting inactive tenant
        'archive_deleted' => true, // Archive tenant data before deletion
        'cleanup_schedule' => 'daily', // How often to run cleanup
    ],
];