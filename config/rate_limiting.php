<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enterprise Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the enterprise-grade rate limiting system with
    | tier-based limits, sliding windows, and cost-weighted requests.
    |
    */

    'enabled' => env('RATE_LIMITING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Subscription Tier Limits
    |--------------------------------------------------------------------------
    |
    | Default rate limits for each subscription tier. These can be overridden
    | at the subscription level or through rate limit exceptions.
    |
    */
    'tiers' => [
        'free' => [
            'requests_per_hour' => 1000,
            'burst_per_minute' => 100,
            'concurrent_requests' => 10,
            'cost_multiplier' => 1.0,
            'priority' => 'low',
            'features' => [
                'api_access' => true,
                'live_scoring' => false,
                'analytics' => false,
                'bulk_operations' => false,
                'webhook_subscriptions' => 1,
                'export_formats' => ['json'],
            ],
        ],

        'basic' => [
            'requests_per_hour' => 5000,
            'burst_per_minute' => 300,
            'concurrent_requests' => 25,
            'cost_multiplier' => 0.8,
            'priority' => 'normal',
            'features' => [
                'api_access' => true,
                'live_scoring' => true,
                'analytics' => 'basic',
                'bulk_operations' => false,
                'webhook_subscriptions' => 5,
                'export_formats' => ['json', 'csv'],
            ],
        ],

        'premium' => [
            'requests_per_hour' => 25000,
            'burst_per_minute' => 1500,
            'concurrent_requests' => 100,
            'cost_multiplier' => 0.6,
            'priority' => 'high',
            'features' => [
                'api_access' => true,
                'live_scoring' => true,
                'analytics' => 'advanced',
                'bulk_operations' => true,
                'webhook_subscriptions' => 25,
                'export_formats' => ['json', 'csv', 'xlsx', 'pdf'],
            ],
        ],

        'enterprise' => [
            'requests_per_hour' => 100000,
            'burst_per_minute' => 5000,
            'concurrent_requests' => 500,
            'cost_multiplier' => 0.4,
            'priority' => 'priority',
            'features' => [
                'api_access' => true,
                'live_scoring' => true,
                'analytics' => 'enterprise',
                'bulk_operations' => true,
                'webhook_subscriptions' => 100,
                'export_formats' => ['json', 'csv', 'xlsx', 'pdf', 'xml'],
                'white_label' => true,
                'sla_support' => true,
            ],
        ],

        'unlimited' => [
            'requests_per_hour' => PHP_INT_MAX,
            'burst_per_minute' => PHP_INT_MAX,
            'concurrent_requests' => 1000,
            'cost_multiplier' => 0.2,
            'priority' => 'highest',
            'features' => [
                'api_access' => true,
                'live_scoring' => true,
                'analytics' => 'unlimited',
                'bulk_operations' => true,
                'webhook_subscriptions' => PHP_INT_MAX,
                'export_formats' => ['json', 'csv', 'xlsx', 'pdf', 'xml', 'parquet'],
                'white_label' => true,
                'sla_support' => true,
                'dedicated_support' => true,
                'custom_integrations' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Endpoint Cost Weights
    |--------------------------------------------------------------------------
    |
    | Different endpoints have different costs based on their resource usage.
    | Higher values mean the endpoint consumes more of the rate limit.
    |
    */
    'endpoint_costs' => [
        // Authentication - Low cost
        'auth/*' => 0.5,
        'api/*/auth/*' => 0.5,

        // Standard CRUD operations
        'api/*/teams' => 1.0,
        'api/*/teams/*' => 1.0,
        'api/*/players' => 1.0,
        'api/*/players/*' => 1.0,
        'api/*/clubs' => 1.0,
        'api/*/clubs/*' => 1.0,
        'api/*/games' => 1.0,
        'api/*/games/*' => 1.0,

        // Live scoring - Higher cost due to real-time nature
        'api/*/games/*/live' => 3.0,
        'api/*/games/*/live/*' => 3.0,
        'api/*/live/*' => 3.0,

        // Analytics and reports - High cost due to processing
        'api/*/analytics' => 5.0,
        'api/*/analytics/*' => 5.0,
        'api/*/reports' => 5.0,
        'api/*/reports/*' => 5.0,
        'api/*/statistics/*' => 4.0,

        // Data export - Very high cost
        'api/*/export' => 8.0,
        'api/*/export/*' => 8.0,

        // Bulk operations - Very high cost
        'api/*/bulk' => 10.0,
        'api/*/bulk/*' => 10.0,
        'api/*/import' => 15.0,
        'api/*/import/*' => 15.0,

        // File uploads - High cost
        'api/*/upload' => 12.0,
        'api/*/upload/*' => 12.0,
        'api/*/media/*' => 6.0,

        // Admin operations - Moderate cost
        'api/*/admin' => 2.0,
        'api/*/admin/*' => 2.0,

        // Webhooks - Low cost for subscription, higher for delivery
        'api/*/webhooks' => 1.0,
        'api/*/webhooks/*' => 1.0,
        'webhooks/deliver' => 2.0,

        // Search operations - Moderate cost
        'api/*/search' => 3.0,
        'api/*/search/*' => 3.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Overage Billing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for billing users when they exceed their rate limits.
    |
    */
    'overage' => [
        'enabled' => env('OVERAGE_BILLING_ENABLED', false),
        'rates' => [
            'free' => 0.001,     // $0.001 per request
            'basic' => 0.0008,   // $0.0008 per request
            'premium' => 0.0006, // $0.0006 per request
            'enterprise' => 0.0004, // $0.0004 per request
            'unlimited' => 0.0002,  // $0.0002 per request
        ],
        'minimum_charge' => 0.01, // Minimum $0.01 charge
        'billing_threshold' => 5.00, // Bill when overage reaches $5.00
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Windows
    |--------------------------------------------------------------------------
    |
    | Configuration for sliding window calculations.
    |
    */
    'windows' => [
        'hourly' => [
            'duration' => 3600, // 1 hour in seconds
            'cleanup_interval' => 7200, // Clean up every 2 hours
        ],
        'minutely' => [
            'duration' => 60, // 1 minute in seconds
            'cleanup_interval' => 300, // Clean up every 5 minutes
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for rate limit caching.
    |
    */
    'cache' => [
        'store' => env('RATE_LIMIT_CACHE_STORE', 'redis'),
        'prefix' => 'rate_limit:',
        'ttl' => [
            'concurrent_requests' => 300, // 5 minutes
            'user_limits' => 900,        // 15 minutes
            'exceptions' => 1800,        // 30 minutes
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring and Alerting
    |--------------------------------------------------------------------------
    |
    | Configuration for rate limit monitoring and alerting.
    |
    */
    'monitoring' => [
        'enabled' => env('RATE_LIMIT_MONITORING_ENABLED', true),
        'alert_thresholds' => [
            'usage_percentage' => 80, // Alert when 80% of limit used
            'concurrent_percentage' => 90, // Alert when 90% of concurrent limit used
        ],
        'cleanup' => [
            'old_records_days' => 30, // Keep usage records for 30 days
            'run_cleanup_daily' => true,
            'expire_exceptions_daily' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Headers Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for rate limit response headers.
    |
    */
    'headers' => [
        'include_cost' => true,
        'include_tier' => true,
        'include_overage' => true,
        'include_retry_after' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Exception Handling
    |--------------------------------------------------------------------------
    |
    | Configuration for rate limit exceptions and bypasses.
    |
    */
    'exceptions' => [
        'max_duration_hours' => 8760, // 1 year maximum
        'default_duration_hours' => 24, // 24 hours default
        'auto_expire' => true,
        'alert_on_use' => true,
    ],
];