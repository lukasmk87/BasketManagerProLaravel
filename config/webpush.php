<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | Web Push Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for web push notifications in BasketManager Pro
    | Basketball-specific notification settings and VAPID configuration
    |
    */
    
    'vapid' => [
        'subject' => env('VAPID_SUBJECT', config('app.url')),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
        'pem_file' => env('VAPID_PEM_FILE'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Default Notification Settings
    |--------------------------------------------------------------------------
    */
    
    'defaults' => [
        'ttl' => 3600, // Time to live in seconds (1 hour)
        'urgency' => 'normal', // normal, low, high
        'topic' => null,
        'icon' => '/images/logo-192.png',
        'badge' => '/images/badge-72.png',
        'vibrate' => [100, 50, 100],
        'require_interaction' => false,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Basketball-Specific Notification Types
    |--------------------------------------------------------------------------
    */
    
    'basketball_types' => [
        'game_start' => [
            'icon' => '/images/notifications/game-start.png',
            'badge' => '/images/badge-game.png',
            'vibrate' => [200, 100, 200],
            'require_interaction' => true,
            'urgency' => 'high',
            'ttl' => 1800, // 30 minutes
        ],
        
        'player_foul' => [
            'icon' => '/images/notifications/foul.png',
            'vibrate' => [100, 50, 100],
            'urgency' => 'normal',
            'ttl' => 900, // 15 minutes
        ],
        
        'training_reminder' => [
            'icon' => '/images/notifications/training.png',
            'vibrate' => [150, 100, 150],
            'urgency' => 'normal',
            'ttl' => 3600, // 1 hour
        ],
        
        'emergency' => [
            'icon' => '/images/notifications/emergency.png',
            'badge' => '/images/badge-emergency.png',
            'vibrate' => [300, 100, 300, 100, 300],
            'require_interaction' => true,
            'urgency' => 'high',
            'ttl' => 86400, // 24 hours
            'silent' => false,
        ],
        
        'score_update' => [
            'icon' => '/images/notifications/score.png',
            'vibrate' => [50, 30, 50],
            'urgency' => 'low',
            'ttl' => 300, // 5 minutes
        ],
        
        'federation_sync' => [
            'icon' => '/images/notifications/federation.png',
            'urgency' => 'low',
            'ttl' => 7200, // 2 hours
        ],
        
        'test' => [
            'icon' => '/images/logo-192.png',
            'urgency' => 'low',
            'ttl' => 300, // 5 minutes
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Subscription Limits
    |--------------------------------------------------------------------------
    */
    
    'subscription_limits' => [
        'per_user' => 5, // Maximum subscriptions per user
        'cleanup_after_days' => 90, // Clean up subscriptions not used for X days
        'max_batch_size' => 1000, // Maximum notifications to send in one batch
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Basketball Game Notification Rules
    |--------------------------------------------------------------------------
    */
    
    'game_notifications' => [
        'score_update_threshold' => 5, // Only notify for score differences >= 5
        'foul_notification_start' => 3, // Start notifying from 3rd foul
        'quarter_end_notification' => true,
        'game_start_advance_minutes' => 15, // Notify 15 minutes before game
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Training Notification Rules
    |--------------------------------------------------------------------------
    */
    
    'training_notifications' => [
        'default_reminder_minutes' => [30, 120], // 30 min and 2 hours before
        'max_reminder_hours' => 24, // Don't remind more than 24 hours in advance
        'attendance_reminder_enabled' => true,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Federation Notification Rules
    |--------------------------------------------------------------------------
    */
    
    'federation_notifications' => [
        'dbb_enabled' => true,
        'fiba_enabled' => true,
        'sync_success_notify' => true,
        'sync_failure_notify' => true,
        'admin_only' => true, // Only notify admins about federation updates
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Emergency Notification Rules
    |--------------------------------------------------------------------------
    */
    
    'emergency_notifications' => [
        'severity_levels' => ['low', 'medium', 'high', 'critical'],
        'critical_override_quiet_hours' => true,
        'max_recipients_per_emergency' => 500,
        'require_admin_approval' => ['high', 'critical'],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    
    'rate_limits' => [
        'per_user_per_hour' => 10, // Max notifications per user per hour
        'per_tenant_per_hour' => 1000, // Max notifications per tenant per hour
        'emergency_exempt' => true, // Emergency notifications bypass rate limits
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Quiet Hours
    |--------------------------------------------------------------------------
    */
    
    'quiet_hours' => [
        'enabled' => true,
        'start' => '22:00', // 10 PM
        'end' => '07:00', // 7 AM
        'timezone' => 'Europe/Berlin',
        'exceptions' => ['emergency'], // Notification types that ignore quiet hours
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Localization
    |--------------------------------------------------------------------------
    */
    
    'localization' => [
        'default_locale' => 'de',
        'supported_locales' => ['de', 'en'],
        'fallback_locale' => 'en',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Analytics and Tracking
    |--------------------------------------------------------------------------
    */
    
    'analytics' => [
        'track_delivery' => true,
        'track_clicks' => true,
        'track_dismissals' => true,
        'retention_days' => 30,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Debug and Testing
    |--------------------------------------------------------------------------
    */
    
    'debug' => [
        'log_all_notifications' => env('WEBPUSH_DEBUG', false),
        'dry_run_mode' => env('WEBPUSH_DRY_RUN', false),
        'test_endpoint_override' => env('WEBPUSH_TEST_ENDPOINT'),
    ],
];