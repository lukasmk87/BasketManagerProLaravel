<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'stripe' => [
        'model' => App\Models\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
        'prices' => [
            'basic_monthly' => env('STRIPE_PRICE_BASIC_MONTHLY'),
            'basic_yearly' => env('STRIPE_PRICE_BASIC_YEARLY'),
            'professional_monthly' => env('STRIPE_PRICE_PROFESSIONAL_MONTHLY'),
            'professional_yearly' => env('STRIPE_PRICE_PROFESSIONAL_YEARLY'),
            'enterprise_monthly' => env('STRIPE_PRICE_ENTERPRISE_MONTHLY'),
            'enterprise_yearly' => env('STRIPE_PRICE_ENTERPRISE_YEARLY'),
        ],
    ],

    'dbb' => [
        'base_url' => env('DBB_API_BASE_URL', 'https://api.basketball-bund.de/v2'),
        'api_key' => env('DBB_API_KEY'),
        'api_secret' => env('DBB_API_SECRET'),
        'timeout' => env('DBB_API_TIMEOUT', 30),
        'retries' => env('DBB_API_RETRIES', 3),
        'cache_ttl' => env('DBB_CACHE_TTL', 3600),
    ],

    'fiba' => [
        'base_url' => env('FIBA_API_BASE_URL', 'https://api.fiba.basketball/v3'),
        'api_key' => env('FIBA_API_KEY'),
        'api_secret' => env('FIBA_API_SECRET'),
        'timeout' => env('FIBA_API_TIMEOUT', 30),
        'retries' => env('FIBA_API_RETRIES', 3),
        'cache_ttl' => env('FIBA_CACHE_TTL', 7200),
    ],

];
