<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | This file configures feature flags for the application.
    | Feature flags can be used to enable/disable features without deployment.
    |
    | Flags can be controlled:
    | - Globally via this config file
    | - Per-tenant via database (feature_flags table)
    | - Per-club via database (feature_flags table)
    | - Via environment variables (FEATURE_FLAG_NAME=true/false)
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Master Feature Toggles
    |--------------------------------------------------------------------------
    |
    | These flags control major features and can be used for gradual rollout.
    |
    */
    'flags' => [
        // Club Subscription System
        'club_subscriptions_enabled' => [
            'enabled' => env('FEATURE_CLUB_SUBSCRIPTIONS_ENABLED', false),
            'name' => 'Club Subscriptions',
            'description' => 'Enable multi-club subscription system with Stripe integration',
            'category' => 'subscriptions',
            'rollout_percentage' => env('FEATURE_CLUB_SUBSCRIPTIONS_ROLLOUT', 100), // 0-100
            'requires' => [], // Other features this depends on
            'beta' => false,
        ],

        'club_subscriptions_checkout' => [
            'enabled' => env('FEATURE_CLUB_SUBSCRIPTIONS_CHECKOUT_ENABLED', false),
            'name' => 'Subscription Checkout',
            'description' => 'Allow clubs to purchase subscriptions via Stripe Checkout',
            'category' => 'subscriptions',
            'rollout_percentage' => env('FEATURE_CLUB_SUBSCRIPTIONS_CHECKOUT_ROLLOUT', 100),
            'requires' => ['club_subscriptions_enabled'],
            'beta' => false,
        ],

        'club_subscriptions_billing_portal' => [
            'enabled' => env('FEATURE_CLUB_SUBSCRIPTIONS_BILLING_PORTAL_ENABLED', false),
            'name' => 'Billing Portal',
            'description' => 'Allow clubs to manage billing via Stripe Billing Portal',
            'category' => 'subscriptions',
            'rollout_percentage' => env('FEATURE_CLUB_SUBSCRIPTIONS_BILLING_PORTAL_ROLLOUT', 100),
            'requires' => ['club_subscriptions_enabled'],
            'beta' => false,
        ],

        'club_subscriptions_plan_swap' => [
            'enabled' => env('FEATURE_CLUB_SUBSCRIPTIONS_PLAN_SWAP_ENABLED', false),
            'name' => 'Plan Swap',
            'description' => 'Allow clubs to upgrade/downgrade subscription plans',
            'category' => 'subscriptions',
            'rollout_percentage' => env('FEATURE_CLUB_SUBSCRIPTIONS_PLAN_SWAP_ROLLOUT', 100),
            'requires' => ['club_subscriptions_enabled'],
            'beta' => false,
        ],

        'club_subscriptions_analytics' => [
            'enabled' => env('FEATURE_CLUB_SUBSCRIPTIONS_ANALYTICS_ENABLED', false),
            'name' => 'Subscription Analytics',
            'description' => 'Enable subscription analytics dashboard (MRR, Churn, LTV)',
            'category' => 'subscriptions',
            'rollout_percentage' => env('FEATURE_CLUB_SUBSCRIPTIONS_ANALYTICS_ROLLOUT', 100),
            'requires' => ['club_subscriptions_enabled'],
            'beta' => true,
        ],

        'club_subscriptions_notifications' => [
            'enabled' => env('FEATURE_CLUB_SUBSCRIPTIONS_NOTIFICATIONS_ENABLED', false),
            'name' => 'Subscription Notifications',
            'description' => 'Email notifications for subscription events',
            'category' => 'subscriptions',
            'rollout_percentage' => env('FEATURE_CLUB_SUBSCRIPTIONS_NOTIFICATIONS_ROLLOUT', 100),
            'requires' => ['club_subscriptions_enabled'],
            'beta' => false,
        ],

        // Other Features (for future expansion)
        'advanced_analytics' => [
            'enabled' => env('FEATURE_ADVANCED_ANALYTICS_ENABLED', false),
            'name' => 'Advanced Analytics',
            'description' => 'Advanced analytics and reporting features',
            'category' => 'analytics',
            'rollout_percentage' => 0,
            'requires' => [],
            'beta' => true,
        ],

        'video_analysis' => [
            'enabled' => env('FEATURE_VIDEO_ANALYSIS_ENABLED', false),
            'name' => 'Video Analysis',
            'description' => 'AI-powered video analysis features',
            'category' => 'features',
            'rollout_percentage' => 0,
            'requires' => [],
            'beta' => true,
        ],

        'api_access' => [
            'enabled' => env('FEATURE_API_ACCESS_ENABLED', false),
            'name' => 'API Access',
            'description' => 'REST API access for integrations',
            'category' => 'integrations',
            'rollout_percentage' => 0,
            'requires' => [],
            'beta' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Categories
    |--------------------------------------------------------------------------
    |
    | Organize features into categories for better management.
    |
    */
    'categories' => [
        'subscriptions' => 'Subscription System',
        'analytics' => 'Analytics & Reporting',
        'features' => 'Advanced Features',
        'integrations' => 'Third-party Integrations',
        'experimental' => 'Experimental Features',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rollout Strategy
    |--------------------------------------------------------------------------
    |
    | Configure how features are rolled out to users.
    |
    */
    'rollout' => [
        // Rollout method: 'percentage', 'whitelist', 'gradual'
        'method' => env('FEATURE_ROLLOUT_METHOD', 'percentage'),

        // For 'whitelist' method: comma-separated tenant IDs
        'whitelist_tenants' => array_filter(explode(',', env('FEATURE_ROLLOUT_WHITELIST_TENANTS', ''))),

        // For 'gradual' method: increase rollout percentage by this amount per day
        'gradual_increment' => env('FEATURE_ROLLOUT_GRADUAL_INCREMENT', 10), // 10% per day = 10 days to 100%

        // Beta features require opt-in
        'beta_opt_in_required' => env('FEATURE_BETA_OPT_IN_REQUIRED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flag Persistence
    |--------------------------------------------------------------------------
    |
    | Store feature flag states in database for per-tenant/club control.
    |
    */
    'persistence' => [
        'enabled' => env('FEATURE_FLAG_PERSISTENCE_ENABLED', true),
        'cache_ttl' => env('FEATURE_FLAG_CACHE_TTL', 3600), // Cache for 1 hour
        'log_changes' => env('FEATURE_FLAG_LOG_CHANGES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Mode
    |--------------------------------------------------------------------------
    |
    | In development, all features can be enabled by setting this to true.
    |
    */
    'development_mode' => env('FEATURE_FLAG_DEVELOPMENT_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | A/B Testing
    |--------------------------------------------------------------------------
    |
    | Enable A/B testing for features (future implementation).
    |
    */
    'ab_testing' => [
        'enabled' => env('FEATURE_AB_TESTING_ENABLED', false),
        'track_metrics' => true,
    ],
];
