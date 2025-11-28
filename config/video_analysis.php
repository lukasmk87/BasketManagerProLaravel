<?php

/**
 * Video Analysis Configuration
 *
 * Configuration for AI-powered video analysis services.
 * Extracted from AIVideoAnalysisService during REFACTOR-001.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Python Executable
    |--------------------------------------------------------------------------
    |
    | Path to the Python executable used for running AI analysis scripts.
    |
    */
    'python_executable' => env('PYTHON_EXECUTABLE', 'python3'),

    /*
    |--------------------------------------------------------------------------
    | Python Script Paths
    |--------------------------------------------------------------------------
    |
    | Paths to Python scripts for different analysis types.
    | All paths are relative to the base_path().
    |
    */
    'scripts' => [
        'player_detection' => 'python/basketball_ai/detect_players.py',
        'court_detection' => 'python/basketball_ai/detect_court.py',
        'ball_tracking' => 'python/basketball_ai/track_ball.py',
        'action_recognition' => 'python/basketball_ai/recognize_actions.py',
        'play_analysis' => 'python/basketball_ai/analyze_plays.py',
        'shot_analysis' => 'python/basketball_ai/analyze_shots.py',
        'comprehensive_analysis' => 'python/basketball_ai/comprehensive_analysis.py',
    ],

    /*
    |--------------------------------------------------------------------------
    | Analysis Capabilities
    |--------------------------------------------------------------------------
    |
    | Basketball-specific AI analysis capabilities and their configurations.
    |
    */
    'capabilities' => [
        'player_detection' => [
            'enabled' => true,
            'confidence_threshold' => 0.7,
            'max_players_per_frame' => 12,
            'tracking_enabled' => true,
        ],
        'court_detection' => [
            'enabled' => true,
            'confidence_threshold' => 0.8,
            'court_segmentation' => true,
            'boundary_detection' => true,
        ],
        'ball_detection' => [
            'enabled' => true,
            'confidence_threshold' => 0.6,
            'tracking_enabled' => true,
            'trajectory_analysis' => true,
        ],
        'action_recognition' => [
            'enabled' => true,
            'actions' => ['shot', 'pass', 'dribble', 'rebound', 'steal', 'block'],
            'confidence_threshold' => 0.75,
        ],
        'play_classification' => [
            'enabled' => true,
            'play_types' => ['offense', 'defense', 'transition', 'set_play', 'fast_break'],
            'confidence_threshold' => 0.7,
        ],
        'shot_analysis' => [
            'enabled' => true,
            'shot_detection' => true,
            'shot_outcome' => true,
            'shot_location' => true,
            'release_point_analysis' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Frame Extraction Strategies
    |--------------------------------------------------------------------------
    |
    | Different frame extraction strategies based on analysis type.
    | 'interval' is in seconds, 'max_frames' limits total extracted frames.
    |
    */
    'extraction_strategies' => [
        'comprehensive_game_analysis' => [
            'interval' => 30,       // Every 30 seconds
            'max_frames' => 200,
        ],
        'highlight_analysis' => [
            'interval' => 5,        // Every 5 seconds for highlights
            'max_frames' => 100,
        ],
        'training_analysis' => [
            'interval' => 20,       // Every 20 seconds
            'max_frames' => 150,
        ],
        'drill_analysis' => [
            'interval' => 10,       // Every 10 seconds for drills
            'max_frames' => 50,
        ],
        'player_performance_analysis' => [
            'interval' => 15,
            'max_frames' => 100,
        ],
        'tactical_analysis' => [
            'interval' => 10,
            'max_frames' => 150,
        ],
        'basic_basketball_analysis' => [
            'interval' => 60,       // Every minute
            'max_frames' => 50,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Execution Settings
    |--------------------------------------------------------------------------
    |
    | Settings for script execution and temporary file management.
    |
    */
    'execution' => [
        'timeout' => 1800,                          // 30 minutes in seconds
        'temp_directory' => 'temp/ai_analysis',     // Relative to storage/app/
        'max_timestamp' => 86400,                   // Max 24 hours for timestamp validation
    ],

    /*
    |--------------------------------------------------------------------------
    | Annotation Settings
    |--------------------------------------------------------------------------
    |
    | Settings for auto-generated annotations.
    |
    */
    'annotation' => [
        'min_confidence_threshold' => 0.8,
        'default_action_duration' => 3,     // seconds
        'default_shot_duration' => 5,       // seconds
        'system_user_id' => 1,              // User ID for system-generated annotations
    ],

    /*
    |--------------------------------------------------------------------------
    | Video Type to Analysis Type Mapping
    |--------------------------------------------------------------------------
    |
    | Maps video types to their default analysis strategies.
    |
    */
    'video_type_mapping' => [
        'full_game' => 'comprehensive_game_analysis',
        'game_highlights' => 'highlight_analysis',
        'training_session' => 'training_analysis',
        'drill_demo' => 'drill_analysis',
        'player_analysis' => 'player_performance_analysis',
        'tactical_analysis' => 'tactical_analysis',
    ],

    /*
    |--------------------------------------------------------------------------
    | Action to Play Type Mapping
    |--------------------------------------------------------------------------
    |
    | Maps detected actions to play types for annotation generation.
    |
    */
    'action_play_type_mapping' => [
        'shot' => 'shot',
        'pass' => 'pass',
        'dribble' => 'dribble',
        'rebound' => 'rebound',
        'steal' => 'defense',
        'block' => 'defense',
    ],

    /*
    |--------------------------------------------------------------------------
    | Shot Points Mapping
    |--------------------------------------------------------------------------
    |
    | Points awarded for different shot types.
    |
    */
    'shot_points' => [
        'three_point' => 3,
        'two_point' => 2,
        'free_throw' => 1,
    ],
];
