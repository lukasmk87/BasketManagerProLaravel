<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stripe Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Stripe payment processing in BasketManager Pro.
    | This includes API keys, webhook settings, and multi-tenant support.
    |
    */

    'api_key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    
    /*
    |--------------------------------------------------------------------------
    | Stripe API Version
    |--------------------------------------------------------------------------
    |
    | The Stripe API version to use. This is pinned to ensure consistency
    | and prevent breaking changes from newer API versions.
    |
    */
    'api_version' => '2023-10-16',

    /*
    |--------------------------------------------------------------------------
    | Test Mode
    |--------------------------------------------------------------------------
    |
    | Whether Stripe is running in test mode. This affects which API keys
    | are used and enables test-specific features.
    |
    */
    'test_mode' => env('STRIPE_TEST_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for handling Stripe webhooks securely.
    |
    */
    'webhooks' => [
        'tolerance' => 300, // 5 minutes
        'signing_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'signing_secret_club' => env('STRIPE_WEBHOOK_SECRET_CLUB', env('STRIPE_WEBHOOK_SECRET')),
        'events' => [
            // Subscription events
            'customer.subscription.created',
            'customer.subscription.updated',
            'customer.subscription.deleted',
            'customer.subscription.trial_will_end',

            // Payment events
            'payment_intent.succeeded',
            'payment_intent.payment_failed',
            'payment_method.attached',
            'payment_method.detached',

            // Invoice events
            'invoice.created',
            'invoice.finalized',
            'invoice.paid',
            'invoice.payment_failed',
            'invoice.payment_action_required',
            'invoice.payment_succeeded',

            // Customer events
            'customer.created',
            'customer.updated',
            'customer.deleted',

            // Club Subscription events (Phase 2)
            'checkout.session.completed',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenant Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for multi-tenant Stripe integration where each tenant
    | can have their own Stripe account or shared platform account.
    |
    */
    'multi_tenant' => [
        'mode' => env('STRIPE_TENANT_MODE', 'shared'), // 'shared' or 'separate'
        
        // For shared mode - single Stripe account for all tenants
        'shared' => [
            'api_key' => env('STRIPE_KEY'),
            'secret' => env('STRIPE_SECRET'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],
        
        // For separate mode - each tenant has their own Stripe account
        'separate' => [
            'client_id' => env('STRIPE_CONNECT_CLIENT_ID'),
            'platform_fee_percentage' => env('STRIPE_PLATFORM_FEE', 2.5), // 2.5% platform fee
        ],
        
        // Customer ID prefix for tenant isolation in shared mode
        'customer_prefix' => true, // Prefixes customer IDs with tenant ID
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Methods
    |--------------------------------------------------------------------------
    |
    | Supported payment methods and their configurations.
    |
    */
    'payment_methods' => [
        'card' => [
            'enabled' => true,
            'capture_method' => 'automatic', // or 'manual'
        ],
        'sepa_debit' => [
            'enabled' => true,
            'mandate_options' => [
                'notification_method' => 'email',
            ],
        ],
        'sofort' => [
            'enabled' => true,
            'preferred_language' => 'de',
        ],
        'paypal' => [
            'enabled' => true,
        ],
        'klarna' => [
            'enabled' => true,
        ],
        'apple_pay' => [
            'enabled' => true,
        ],
        'google_pay' => [
            'enabled' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription Configuration
    |--------------------------------------------------------------------------
    |
    | Default settings for subscription management.
    |
    */
    'subscriptions' => [
        'default_payment_behavior' => 'default_incomplete',
        'prorate_upgrades' => true,
        'prorate_downgrades' => false,
        'trial_period_days' => 14,
        
        // Subscription tiers mapping to Stripe Price IDs
        'tiers' => [
            'free' => null, // No Stripe subscription for free tier
            'basic' => env('STRIPE_PRICE_BASIC'),
            'professional' => env('STRIPE_PRICE_PROFESSIONAL'),
            'enterprise' => env('STRIPE_PRICE_ENTERPRISE'),
        ],
        
        // Feature flags per tier (synced with tenant config)
        'features' => [
            'basic' => [
                'live_scoring',
                'training_management',
                'email_notifications',
            ],
            'professional' => [
                'live_scoring',
                'training_management',
                'email_notifications',
                'tournament_management',
                'video_analysis',
                'api_access',
                'push_notifications',
                'custom_reports',
            ],
            'enterprise' => [
                'live_scoring',
                'training_management',
                'email_notifications',
                'tournament_management',
                'video_analysis',
                'api_access',
                'push_notifications',
                'custom_reports',
                'federation_integration',
                'white_label',
                'dedicated_support',
                'unlimited_api',
                'multi_club_management',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Localization
    |--------------------------------------------------------------------------
    |
    | Localization settings for Stripe interactions.
    |
    */
    'locale' => env('STRIPE_LOCALE', 'de'),
    
    /*
    |--------------------------------------------------------------------------
    | Currency Settings
    |--------------------------------------------------------------------------
    |
    | Default currency and supported currencies.
    |
    */
    'currency' => [
        'default' => 'eur',
        'supported' => ['eur', 'usd', 'chf'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tax Configuration
    |--------------------------------------------------------------------------
    |
    | Tax handling for different regions.
    |
    */
    'tax' => [
        'enabled' => true,
        'rates' => [
            'de' => 19, // German VAT
            'at' => 20, // Austrian VAT
            'ch' => 7.7, // Swiss VAT
        ],
        'stripe_tax_rates' => [
            'de' => env('STRIPE_TAX_RATE_DE'),
            'at' => env('STRIPE_TAX_RATE_AT'),
            'ch' => env('STRIPE_TAX_RATE_CH'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Invoice Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for invoice generation and management.
    |
    */
    'invoices' => [
        'company_name' => env('STRIPE_COMPANY_NAME', 'BasketManager Pro'),
        'company_address' => env('STRIPE_COMPANY_ADDRESS'),
        'vat_number' => env('STRIPE_VAT_NUMBER'),
        'email' => env('STRIPE_INVOICE_EMAIL', 'invoices@basketmanager-pro.com'),
        
        // Invoice number formatting
        'number_format' => 'BMP-{year}-{month}-{number}',
        'starting_number' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security-related configuration for Stripe integration.
    |
    */
    'security' => [
        'radar_rules' => true, // Enable Stripe Radar
        'require_3d_secure' => 'automatic', // 'automatic', 'required', or 'disabled'
        'decline_charges' => [
            'avs_failure' => false,
            'cvc_failure' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Settings to optimize Stripe API performance.
    |
    */
    'performance' => [
        'api_timeout' => 30, // seconds
        'connect_timeout' => 10, // seconds
        'max_network_retries' => 2,
        'enable_telemetry' => env('STRIPE_TELEMETRY', true),
        'cache_customers' => true,
        'cache_ttl' => 3600, // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Settings
    |--------------------------------------------------------------------------
    |
    | Settings for development and testing.
    |
    */
    'development' => [
        'log_requests' => env('STRIPE_LOG_REQUESTS', false),
        'log_webhooks' => env('STRIPE_LOG_WEBHOOKS', true),
        'simulate_events' => env('STRIPE_SIMULATE_EVENTS', false),
    ],
];