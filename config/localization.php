<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Multi-Language Configuration for BasketManager Pro
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for multi-language support including
    | supported locales, URL routing, and localization preferences.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    |
    | List of locales supported by the application. The first locale in the
    | array will be used as the default locale if none is specified.
    |
    */
    'supported_locales' => explode(',', env('SUPPORTED_LOCALES', 'de,en')),

    /*
    |--------------------------------------------------------------------------
    | Default Locale
    |--------------------------------------------------------------------------
    |
    | The default locale for the application. This will be used when no
    | specific locale is requested or when a requested locale is not supported.
    |
    */
    'default_locale' => env('DEFAULT_LOCALE', 'de'),

    /*
    |--------------------------------------------------------------------------
    | URL Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for locale-based URL routing and behavior.
    |
    */
    'url' => [
        'hide_default_locale' => env('HIDE_DEFAULT_LOCALE_IN_URL', true),
        'prefix_segments' => ['dashboard', 'admin', 'api'], // Segments that should always have locale prefix
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale Detection
    |--------------------------------------------------------------------------
    |
    | Order of locale detection methods. First match wins.
    |
    */
    'detection_order' => [
        'url',        // URL segment (/de/dashboard)
        'session',    // Session storage
        'cookie',     // Cookie preference
        'user',       // User profile preference
        'default',    // Fallback to default (prioritized over browser)
        'browser',    // Browser Accept-Language header
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale Names
    |--------------------------------------------------------------------------
    |
    | Human-readable names for each supported locale.
    |
    */
    'locale_names' => [
        'de' => 'Deutsch',
        'en' => 'English',
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale Flags
    |--------------------------------------------------------------------------
    |
    | Flag icons for each supported locale (for UI display).
    |
    */
    'locale_flags' => [
        'de' => '🇩🇪',
        'en' => '🇬🇧',
    ],

    /*
    |--------------------------------------------------------------------------
    | Date & Time Formats
    |--------------------------------------------------------------------------
    |
    | Locale-specific date and time formats for display.
    |
    */
    'date_formats' => [
        'de' => [
            'date' => 'd.m.Y',
            'datetime' => 'd.m.Y H:i',
            'time' => 'H:i',
            'carbon_format' => 'DD.MM.YYYY',
        ],
        'en' => [
            'date' => 'm/d/Y',
            'datetime' => 'm/d/Y g:i A',
            'time' => 'g:i A',
            'carbon_format' => 'MM/DD/YYYY',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Number Formats
    |--------------------------------------------------------------------------
    |
    | Locale-specific number formatting.
    |
    */
    'number_formats' => [
        'de' => [
            'decimal_separator' => ',',
            'thousands_separator' => '.',
            'currency_symbol' => '€',
            'currency_position' => 'after', // before or after
        ],
        'en' => [
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'currency_symbol' => '€',
            'currency_position' => 'before',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Basketball Terminology
    |--------------------------------------------------------------------------
    |
    | Locale-specific basketball terms and positions.
    |
    */
    'basketball_terms' => [
        'positions' => [
            'de' => [
                'PG' => 'Aufbauspieler',
                'SG' => 'Shooting Guard',
                'SF' => 'Small Forward',
                'PF' => 'Power Forward',
                'C' => 'Center',
            ],
            'en' => [
                'PG' => 'Point Guard',
                'SG' => 'Shooting Guard',
                'SF' => 'Small Forward',
                'PF' => 'Power Forward',
                'C' => 'Center',
            ],
        ],
        'categories' => [
            'de' => [
                'U8' => 'U8 (unter 8 Jahre)',
                'U10' => 'U10 (unter 10 Jahre)',
                'U12' => 'U12 (unter 12 Jahre)',
                'U14' => 'U14 (unter 14 Jahre)',
                'U16' => 'U16 (unter 16 Jahre)',
                'U18' => 'U18 (unter 18 Jahre)',
                'U20' => 'U20 (unter 20 Jahre)',
                'Herren' => 'Herren',
                'Damen' => 'Damen',
                'Senioren' => 'Senioren',
                'Mixed' => 'Mixed',
            ],
            'en' => [
                'U8' => 'U8 (under 8 years)',
                'U10' => 'U10 (under 10 years)',
                'U12' => 'U12 (under 12 years)',
                'U14' => 'U14 (under 14 years)',
                'U16' => 'U16 (under 16 years)',
                'U18' => 'U18 (under 18 years)',
                'U20' => 'U20 (under 20 years)',
                'Herren' => 'Men',
                'Damen' => 'Women',
                'Senioren' => 'Seniors',
                'Mixed' => 'Mixed',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cookie Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for storing locale preference in cookies.
    |
    */
    'cookie' => [
        'name' => 'basketmanager_locale',
        'expire' => 365 * 24 * 60, // 1 year in minutes
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'http_only' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Translation File Types
    |--------------------------------------------------------------------------
    |
    | Types of translation files to use. 'php' uses traditional PHP arrays,
    | 'json' uses JSON files for string-key translations.
    |
    */
    'file_types' => ['php', 'json'],

    /*
    |--------------------------------------------------------------------------
    | Missing Translation Behavior
    |--------------------------------------------------------------------------
    |
    | What to do when a translation is missing.
    | Options: 'key' (return key), 'fallback' (use fallback locale), 'log' (log missing)
    |
    */
    'missing_translation' => [
        'behavior' => 'fallback',
        'log_missing' => env('APP_ENV') === 'local',
    ],

];