<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default API Version
    |--------------------------------------------------------------------------
    |
    | This is the default API version that will be used when no version is
    | explicitly requested by the client.
    |
    */
    'default_version' => '4.0',

    /*
    |--------------------------------------------------------------------------
    | Supported API Versions
    |--------------------------------------------------------------------------
    |
    | Define all supported API versions with their specific configurations,
    | features, and compatibility settings.
    |
    */
    'versions' => [
        '1.0' => [
            'enabled' => true,
            'deprecated' => true,
            'sunset_date' => '2025-12-31',
            'deprecation_message' => 'API v1.0 is deprecated. Please migrate to v4.0.',
            'documentation_url' => '/api/v1/documentation',
            'openapi_spec' => 'storage/api-docs/v1/openapi.json',
            'features' => [
                'live_scoring',
                'basic_statistics',
                'team_management',
                'player_management'
            ],
            'deprecated_endpoints' => [
                '/api/v1/stats/legacy',
                '/api/v1/players/export/xml'
            ],
            'middleware' => [
                'throttle:1000,1', // 1000 requests per hour for v1
            ],
            'rate_limits' => [
                'default' => 1000,
                'authenticated' => 5000,
                'premium' => 10000
            ]
        ],

        '2.0' => [
            'enabled' => true, // Re-enabled for gym management features
            'deprecated' => false,
            'sunset_date' => null,
            'deprecation_message' => null,
            'documentation_url' => '/api/v2/documentation',
            'openapi_spec' => 'storage/api-docs/v2/openapi.json',
            'features' => [
                'live_scoring',
                'advanced_statistics',
                'team_management',
                'player_management',
                'gym_management',
                'shot_charts'
            ],
            'middleware' => [
                'throttle:3000,1', // 3000 requests per hour for v2
            ],
            'rate_limits' => [
                'default' => 3000,
                'authenticated' => 10000,
                'premium' => 25000
            ]
        ],

        '3.0' => [
            'enabled' => true,
            'deprecated' => false,
            'sunset_date' => null,
            'deprecation_message' => null,
            'documentation_url' => '/api/v3/documentation',
            'openapi_spec' => 'storage/api-docs/v3/openapi.json',
            'features' => [
                'live_scoring',
                'advanced_statistics',
                'team_management',
                'player_management',
                'tournament_management',
                'video_analysis',
                'ml_analytics'
            ],
            'deprecated_endpoints' => [],
            'middleware' => [
                'throttle:5000,1', // 5000 requests per hour for v3
            ],
            'rate_limits' => [
                'default' => 5000,
                'authenticated' => 15000,
                'premium' => 50000,
                'enterprise' => 100000
            ]
        ],

        '4.0' => [
            'enabled' => true,
            'deprecated' => false,
            'sunset_date' => null,
            'deprecation_message' => null,
            'documentation_url' => '/api/v4/documentation',
            'openapi_spec' => 'storage/api-docs/openapi.json',
            'features' => [
                'live_scoring',
                'advanced_statistics',
                'team_management',
                'player_management',
                'tournament_management',
                'video_analysis',
                'ml_analytics',
                'multi_tenant',
                'webhook_subscriptions',
                'external_integrations',
                'real_time_updates',
                'advanced_analytics',
                'pwa_support',
                'push_notifications'
            ],
            'deprecated_endpoints' => [],
            'new_endpoints' => [
                '/api/v4/webhooks',
                '/api/v4/subscriptions',
                '/api/v4/integrations',
                '/api/v4/notifications/push',
                '/api/v4/analytics/advanced'
            ],
            'middleware' => [
                'throttle:10000,1', // 10000 requests per hour for v4
            ],
            'rate_limits' => [
                'default' => 10000,
                'authenticated' => 25000,
                'premium' => 100000,
                'enterprise' => 500000
            ],
            'service_bindings' => [
                // Version-specific service bindings if needed
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Version Migration Information
    |--------------------------------------------------------------------------
    |
    | Define migration paths and breaking changes between versions.
    |
    */
    'migrations' => [
        '1.0_to_4.0' => [
            'breaking_changes' => [
                'Response format changed from XML to JSON',
                'Authentication moved from Basic Auth to Bearer tokens',
                'Pagination format standardized',
                'Error response structure updated'
            ],
            'new_features' => [
                'Multi-tenant support',
                'Webhook subscriptions',
                'Real-time updates via WebSockets',
                'Advanced ML analytics',
                'Push notifications'
            ],
            'deprecated_features' => [
                'XML response format',
                'Legacy authentication methods',
                'Old pagination system'
            ],
            'migration_guide' => '/docs/migration/v1-to-v4'
        ],
        
        '3.0_to_4.0' => [
            'breaking_changes' => [
                'Tenant ID now required in headers for multi-tenant endpoints',
                'Some endpoint URLs restructured for consistency'
            ],
            'new_features' => [
                'Multi-tenant architecture',
                'Enhanced webhook system',
                'Advanced push notifications',
                'Improved real-time capabilities'
            ],
            'deprecated_features' => [],
            'migration_guide' => '/docs/migration/v3-to-v4'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Version Changelogs
    |--------------------------------------------------------------------------
    |
    | Detailed changelog for each version.
    |
    */
    'changelogs' => [
        '4.0' => [
            'release_date' => '2024-12-01',
            'changes' => [
                'Added multi-tenant support',
                'Implemented webhook subscription system',
                'Enhanced real-time capabilities',
                'Added advanced ML analytics endpoints',
                'Implemented push notification system',
                'Added external API integrations (DBB, FIBA)',
                'Enhanced rate limiting with tier-based limits',
                'Added PWA support features'
            ]
        ],
        '3.0' => [
            'release_date' => '2024-06-01',
            'changes' => [
                'Added video analysis capabilities',
                'Implemented ML analytics dashboard',
                'Enhanced tournament management',
                'Added advanced statistics endpoints',
                'Improved authentication system'
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for different user tiers and API versions.
    |
    */
    'rate_limiting' => [
        'enabled' => true,
        'default' => 60,        // Default rate limit per minute
        'authenticated' => 1000, // Rate limit for authenticated users per hour
        
        // Tier-based rate limits (requests per hour)
        'tiers' => [
            'free' => 1000,
            'basic' => 5000,
            'premium' => 25000,
            'enterprise' => 100000,
            'unlimited' => null
        ],

        // Rate limit headers
        'headers' => [
            'limit' => 'X-RateLimit-Limit',
            'remaining' => 'X-RateLimit-Remaining',
            'reset' => 'X-RateLimit-Reset',
            'retry_after' => 'Retry-After'
        ],

        // Rate limit by endpoint type
        'endpoint_limits' => [
            'public' => 100,      // Public endpoints (per minute)
            'authenticated' => 1000, // Authenticated endpoints (per hour)
            'premium' => 5000,    // Premium features (per hour)
            'bulk' => 10          // Bulk operations (per minute)
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | CORS Configuration
    |--------------------------------------------------------------------------
    |
    | Configure Cross-Origin Resource Sharing settings.
    |
    */
    'cors' => [
        'enabled' => true,
        'origins' => ['*'],
        'methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'headers' => [
            'Content-Type',
            'Authorization',
            'Accept',
            'API-Version',
            'X-API-Version',
            'X-Tenant-ID',
            'X-Requested-With'
        ],
        'exposed_headers' => [
            'X-API-Version',
            'X-Supported-Versions',
            'X-RateLimit-Limit',
            'X-RateLimit-Remaining',
            'X-RateLimit-Reset'
        ],
        'max_age' => 86400
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Configuration
    |--------------------------------------------------------------------------
    |
    | Configure API response settings and formats.
    |
    */
    'response' => [
        'default_format' => 'json',
        'supported_formats' => ['json'],
        'pretty_print' => env('API_PRETTY_PRINT', false),
        'include_meta' => true,
        'include_links' => true,
        
        // Standard response wrapper
        'wrap_responses' => true,
        'wrapper_key' => 'data',
        
        // Error response format
        'error_format' => [
            'error' => true,
            'message' => '',
            'code' => 0,
            'details' => [],
            'trace' => null // Only in debug mode
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Documentation Configuration
    |--------------------------------------------------------------------------
    |
    | Configure API documentation settings.
    |
    */
    'documentation' => [
        'enabled' => true,
        'route_prefix' => 'documentation',
        'title' => 'BasketManager Pro API',
        'description' => 'Comprehensive Basketball Club Management API',
        'version' => '4.0',
        'contact' => [
            'name' => 'API Support',
            'email' => 'api@basketmanager.pro',
            'url' => 'https://basketmanager.pro/support'
        ],
        'license' => [
            'name' => 'MIT',
            'url' => 'https://opensource.org/licenses/MIT'
        ],
        'servers' => [
            [
                'url' => env('APP_URL', 'http://localhost') . '/api',
                'description' => 'Production API Server'
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Configure API security settings.
    |
    */
    'security' => [
        'require_https' => env('API_REQUIRE_HTTPS', false),
        'api_key_header' => 'X-API-Key',
        'tenant_header' => 'X-Tenant-ID',
        'request_id_header' => 'X-Request-ID',
        
        // Request signing
        'signature' => [
            'enabled' => false,
            'algorithm' => 'sha256',
            'header' => 'X-Signature',
            'timestamp_header' => 'X-Timestamp',
            'tolerance' => 300 // 5 minutes
        ]
    ]
];
