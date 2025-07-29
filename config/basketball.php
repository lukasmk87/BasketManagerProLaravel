<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Basketball Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for storing the basketball-specific configuration options
    | for BasketManager Pro. You may configure various aspects of basketball
    | management including seasons, player limits, game settings, etc.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Season Configuration
    |--------------------------------------------------------------------------
    */
    'season' => [
        'current' => env('DEFAULT_SEASON', '2024-25'),
        'format' => 'YYYY-YY', // e.g., 2024-25
        'start_month' => 9, // September
        'end_month' => 6,   // June
    ],

    /*
    |--------------------------------------------------------------------------
    | Team Configuration
    |--------------------------------------------------------------------------
    */
    'team' => [
        'max_players' => env('MAX_PLAYERS_PER_TEAM', 15),
        'max_roster_size' => env('MAX_ROSTER_SIZE', 20),
        'categories' => [
            'U8' => ['min_age' => 6, 'max_age' => 8],
            'U10' => ['min_age' => 8, 'max_age' => 10],
            'U12' => ['min_age' => 10, 'max_age' => 12],
            'U14' => ['min_age' => 12, 'max_age' => 14],
            'U16' => ['min_age' => 14, 'max_age' => 16],
            'U18' => ['min_age' => 16, 'max_age' => 18],
            'U20' => ['min_age' => 18, 'max_age' => 20],
            'Herren' => ['min_age' => 16, 'max_age' => null],
            'Damen' => ['min_age' => 16, 'max_age' => null],
            'Senioren' => ['min_age' => 35, 'max_age' => null],
            'Mixed' => ['min_age' => 16, 'max_age' => null],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Player Configuration
    |--------------------------------------------------------------------------
    */
    'player' => [
        'positions' => ['PG', 'SG', 'SF', 'PF', 'C'],
        'jersey_numbers' => [
            'min' => 0,
            'max' => 99,
        ],
        'required_fields' => [
            'first_name', 'last_name', 'birth_date', 'gender'
        ],
        'consent_types' => [
            'medical_consent',
            'photo_consent', 
            'data_processing_consent'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Game Configuration
    |--------------------------------------------------------------------------
    */
    'game' => [
        'duration_minutes' => env('GAME_DURATION_MINUTES', 40),
        'quarters' => 4,
        'quarter_duration' => 10, // minutes
        'overtime_duration' => 5, // minutes
        'shot_clock' => 24, // seconds
        'action_types' => [
            'field_goal_made', 'field_goal_missed',
            'three_point_made', 'three_point_missed',
            'free_throw_made', 'free_throw_missed',
            'rebound_offensive', 'rebound_defensive',
            'assist', 'steal', 'block', 'turnover',
            'foul_personal', 'foul_technical', 'foul_flagrant'
        ],
        'statuses' => ['scheduled', 'live', 'finished', 'cancelled', 'postponed'],
        'types' => ['regular', 'playoff', 'friendly', 'tournament', 'cup'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Emergency Access Configuration
    |--------------------------------------------------------------------------
    */
    'emergency' => [
        'access_duration_hours' => env('EMERGENCY_ACCESS_DURATION', 8760), // 1 year
        'qr_code_expiry_hours' => env('QR_CODE_EXPIRY_HOURS', 8760),
        'allowed_relationships' => [
            'parent', 'mother', 'father', 'guardian', 
            'sibling', 'grandparent', 'partner', 'friend', 'other'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Media Configuration
    |--------------------------------------------------------------------------
    */
    'media' => [
        'profile_photos' => [
            'disk' => env('PROFILE_PHOTOS_DISK', 'public'),
            'max_size' => 2048, // KB
            'allowed_types' => ['jpeg', 'jpg', 'png'],
            'dimensions' => ['width' => 400, 'height' => 400],
        ],
        'team_logos' => [
            'disk' => env('TEAM_LOGOS_DISK', 'public'),
            'max_size' => 1024, // KB
            'allowed_types' => ['jpeg', 'jpg', 'png', 'svg'],
            'dimensions' => ['width' => 200, 'height' => 200],
        ],
        'documents' => [
            'disk' => env('DOCUMENTS_DISK', 'private'),
            'max_size' => 5120, // KB
            'allowed_types' => ['pdf', 'doc', 'docx'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    */
    'api' => [
        'rate_limit' => env('API_RATE_LIMIT', 60),
        'rate_limit_window' => env('API_RATE_LIMIT_WINDOW', 1), // minutes
        'version' => 'v2',
        'prefix' => 'api/v2',
    ],

    /*
    |--------------------------------------------------------------------------
    | Statistics Configuration
    |--------------------------------------------------------------------------
    */
    'statistics' => [
        'cache_duration' => 3600, // seconds (1 hour)
        'efficiency_formula' => '(points + rebounds + assists + steals + blocks) - ((field_goals_attempted - field_goals_made) + (free_throws_attempted - free_throws_made) + turnovers)',
        'advanced_stats' => [
            'player_efficiency_rating' => true,
            'true_shooting_percentage' => true,
            'usage_rate' => true,
            'plus_minus' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Configuration
    |--------------------------------------------------------------------------
    */
    'export' => [
        'formats' => ['pdf', 'excel', 'csv'],
        'timeout' => 300, // seconds
        'memory_limit' => '256M',
    ],

];