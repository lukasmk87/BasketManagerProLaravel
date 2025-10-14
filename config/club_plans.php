<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Club Subscription Plans
    |--------------------------------------------------------------------------
    |
    | Default plans that can be created for new tenants.
    |
    */
    'default_plans' => [
        [
            'name' => 'Free Club',
            'slug' => 'free-club',
            'description' => 'Basis-Funktionen für kleinere Clubs',
            'price' => 0,
            'billing_interval' => 'monthly',
            'color' => '#6c757d',
            'icon' => 'shield',
            'is_default' => true,
            'features' => [
                'basic_team_management',
                'basic_player_profiles',
                'game_scheduling',
            ],
            'limits' => [
                'max_teams' => 2,
                'max_players' => 30,
                'max_storage_gb' => 5,
                'max_games_per_month' => 20,
                'max_training_sessions_per_month' => 50,
            ],
            'sort_order' => 1,
        ],
        [
            'name' => 'Standard Club',
            'slug' => 'standard-club',
            'description' => 'Erweiterte Funktionen für aktive Clubs',
            'price' => 49,
            'billing_interval' => 'monthly',
            'color' => '#007bff',
            'icon' => 'star',
            'features' => [
                'basic_team_management',
                'basic_player_profiles',
                'game_scheduling',
                'live_scoring',
                'training_management',
                'basic_statistics',
            ],
            'limits' => [
                'max_teams' => 10,
                'max_players' => 150,
                'max_storage_gb' => 25,
                'max_games_per_month' => 100,
                'max_training_sessions_per_month' => 200,
            ],
            'sort_order' => 2,
        ],
        [
            'name' => 'Premium Club',
            'slug' => 'premium-club',
            'description' => 'Alle Funktionen für professionelle Clubs',
            'price' => 149,
            'billing_interval' => 'monthly',
            'color' => '#ffc107',
            'icon' => 'crown',
            'features' => [
                'basic_team_management',
                'basic_player_profiles',
                'game_scheduling',
                'live_scoring',
                'training_management',
                'basic_statistics',
                'advanced_statistics',
                'video_analysis',
                'tournament_management',
                'custom_reports',
            ],
            'limits' => [
                'max_teams' => 50,
                'max_players' => 500,
                'max_storage_gb' => 100,
                'max_games_per_month' => -1, // Unlimited
                'max_training_sessions_per_month' => -1,
            ],
            'sort_order' => 3,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Features
    |--------------------------------------------------------------------------
    |
    | All features that can be assigned to club plans.
    |
    */
    'available_features' => [
        'basic_team_management' => 'Basic Team Management',
        'basic_player_profiles' => 'Basic Player Profiles',
        'game_scheduling' => 'Game Scheduling',
        'basic_statistics' => 'Basic Statistics',
        'live_scoring' => 'Live Game Scoring',
        'training_management' => 'Training Management',
        'advanced_statistics' => 'Advanced Statistics',
        'tournament_management' => 'Tournament Management',
        'video_analysis' => 'Video Analysis',
        'custom_reports' => 'Custom Reports',
        'api_access' => 'API Access',
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Limit Metrics
    |--------------------------------------------------------------------------
    |
    | All limit types that can be configured for club plans.
    |
    */
    'available_limits' => [
        'max_teams' => 'Maximum Teams',
        'max_players' => 'Maximum Players',
        'max_storage_gb' => 'Storage (GB)',
        'max_games_per_month' => 'Games per Month',
        'max_training_sessions_per_month' => 'Training Sessions per Month',
        'max_api_calls_per_hour' => 'API Calls per Hour',
    ],
];
