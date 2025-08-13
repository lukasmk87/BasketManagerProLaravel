<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Production Environment Configuration
    |--------------------------------------------------------------------------
    */
    
    // Performance Optimization
    'cache' => [
        'default' => 'redis',
        'ttl' => 3600,
        'query_cache_enabled' => true,
    ],
    
    // Security Settings
    'security' => [
        'force_https' => true,
        'hsts_enabled' => true,
        'content_security_policy' => true,
        'rate_limiting_enabled' => true,
    ],
    
    // Multi-tenant Configuration
    'tenants' => [
        'domain_verification' => true,
        'ssl_required' => true,
        'row_level_security' => true,
    ],
    
    // Database Optimization
    'database' => [
        'connection_pooling' => true,
        'slow_query_logging' => true,
        'performance_monitoring' => true,
    ],
    
    // Monitoring & Logging
    'monitoring' => [
        'error_reporting' => 'sentry',
        'performance_tracking' => true,
        'health_checks' => true,
    ],
];