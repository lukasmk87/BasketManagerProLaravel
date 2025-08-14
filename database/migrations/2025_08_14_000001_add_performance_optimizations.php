<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - Database Performance Optimizations
     */
    public function up(): void
    {
        // === GAME ACTIONS TABLE OPTIMIZATIONS ===
        Schema::table('game_actions', function (Blueprint $table) {
            // Shot Chart & Analytics optimizations
            $table->index(['action_type', 'is_successful'], 'idx_action_success'); // Shot success analysis
            $table->index(['game_id', 'action_type', 'period'], 'idx_game_action_period'); // Period-specific stats
            $table->index(['player_id', 'game_id', 'action_type'], 'idx_player_game_action'); // Player performance
            $table->index(['team_id', 'game_id', 'period'], 'idx_team_game_period'); // Team stats by period
            
            // Shot location optimizations
            $table->index(['shot_zone', 'is_successful'], 'idx_shot_zone_success'); // Zone shooting %
            $table->index(['shot_distance', 'is_successful'], 'idx_shot_distance_success'); // Distance analysis
            
            // Time-based optimizations
            $table->index(['recorded_at', 'game_id'], 'idx_recorded_game'); // Recent actions
            $table->index(['period', 'game_clock_seconds'], 'idx_period_time'); // Game flow analysis
            
            // Foul analysis
            $table->index(['foul_type', 'player_id'], 'idx_foul_player'); // Player foul patterns
            
            // Multi-column composite indexes for complex queries
            $table->index(['game_id', 'team_id', 'action_type', 'is_successful'], 'idx_game_team_action_result');
            $table->index(['player_id', 'action_type', 'recorded_at'], 'idx_player_action_time');
        });

        // === GAMES TABLE OPTIMIZATIONS ===
        Schema::table('games', function (Blueprint $table) {
            // Game scheduling and status
            $table->index(['status', 'scheduled_at'], 'idx_status_scheduled'); // Live/upcoming games
            $table->index(['season', 'status'], 'idx_season_status'); // Season analysis
            $table->index(['home_team_id', 'scheduled_at'], 'idx_home_team_date'); // Team schedule
            $table->index(['away_team_id', 'scheduled_at'], 'idx_away_team_date'); // Team schedule
            
            // Score analysis
            $table->index(['home_team_score', 'away_team_score'], 'idx_game_scores'); // Score analysis
            
            // Multi-team queries
            $table->index(['home_team_id', 'away_team_id', 'scheduled_at'], 'idx_teams_date');
        });

        // === PLAYERS TABLE OPTIMIZATIONS ===
        Schema::table('players', function (Blueprint $table) {
            // Player search and filtering
            $table->index(['team_id', 'status'], 'idx_team_status'); // Active players per team
            $table->index(['primary_position', 'status'], 'idx_position_status'); // Position-based queries
            $table->index(['jersey_number', 'team_id'], 'idx_jersey_team'); // Unique jersey per team
            
            // Performance analysis
            $table->index(['height_cm', 'weight_kg'], 'idx_physical_stats'); // Physical analysis
        });

        // === TEAMS TABLE OPTIMIZATIONS ===
        Schema::table('teams', function (Blueprint $table) {
            $table->index(['club_id', 'season'], 'idx_club_season'); // Club team management
            $table->index(['league', 'division'], 'idx_league_division'); // League standings
            $table->index(['status', 'season'], 'idx_status_season'); // Active teams
        });

        // === ML MODELS TABLE OPTIMIZATIONS ===
        Schema::table('ml_models', function (Blueprint $table) {
            $table->index(['type', 'status'], 'idx_model_type_status'); // Model selection
            $table->index(['accuracy'], 'idx_accuracy'); // Best models first
            $table->index(['last_trained_at', 'status'], 'idx_trained_status'); // Recent models
        });

        // === API USAGE TRACKING OPTIMIZATIONS ===
        Schema::table('api_usage_tracking', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'idx_user_usage_time'); // User activity
            $table->index(['endpoint', 'created_at'], 'idx_endpoint_usage_time'); // Endpoint popularity
            $table->index(['response_status', 'created_at'], 'idx_status_time'); // Error tracking
            
            // Rate limiting optimization  
            $table->index(['ip_address', 'endpoint', 'created_at'], 'idx_ip_endpoint_time');
        });

        // === EMERGENCY CONTACTS OPTIMIZATIONS ===
        Schema::table('emergency_contacts', function (Blueprint $table) {
            $table->index(['user_id', 'priority_order'], 'idx_user_priority'); // Primary contacts first
            $table->index(['primary_phone', 'phone_verified'], 'idx_phone_verified'); // Contact verification
        });

        // === TENANTS TABLE OPTIMIZATIONS ===
        Schema::table('tenants', function (Blueprint $table) {
            $table->index(['subscription_status', 'trial_ends_at'], 'idx_subscription_trial'); // Subscription management
            $table->index(['payment_status', 'updated_at'], 'idx_payment_status_time'); // Payment tracking
        });

        // === CREATE MATERIALIZED VIEWS FOR PERFORMANCE ===
        
        // Player Statistics View
        DB::statement("
            CREATE OR REPLACE VIEW player_game_statistics AS
            SELECT 
                ga.player_id,
                ga.game_id,
                g.scheduled_at,
                g.season,
                SUM(CASE WHEN ga.action_type LIKE '%_made' THEN ga.points ELSE 0 END) as points,
                SUM(CASE WHEN ga.action_type IN ('rebound_offensive', 'rebound_defensive') THEN 1 ELSE 0 END) as rebounds,
                SUM(CASE WHEN ga.action_type = 'assist' THEN 1 ELSE 0 END) as assists,
                SUM(CASE WHEN ga.action_type = 'steal' THEN 1 ELSE 0 END) as steals,
                SUM(CASE WHEN ga.action_type = 'block' THEN 1 ELSE 0 END) as blocks,
                SUM(CASE WHEN ga.action_type = 'turnover' THEN 1 ELSE 0 END) as turnovers,
                SUM(CASE WHEN ga.action_type LIKE 'field_goal_%' THEN 1 ELSE 0 END) as field_goal_attempts,
                SUM(CASE WHEN ga.action_type = 'field_goal_made' THEN 1 ELSE 0 END) as field_goal_made,
                SUM(CASE WHEN ga.action_type LIKE 'three_point_%' THEN 1 ELSE 0 END) as three_point_attempts,
                SUM(CASE WHEN ga.action_type = 'three_point_made' THEN 1 ELSE 0 END) as three_point_made,
                SUM(CASE WHEN ga.action_type LIKE 'free_throw_%' THEN 1 ELSE 0 END) as free_throw_attempts,
                SUM(CASE WHEN ga.action_type = 'free_throw_made' THEN 1 ELSE 0 END) as free_throw_made,
                COUNT(*) as total_actions
            FROM game_actions ga
            JOIN games g ON ga.game_id = g.id
            WHERE g.status = 'finished'
            GROUP BY ga.player_id, ga.game_id, g.scheduled_at, g.season
        ");

        // Team Performance View
        DB::statement("
            CREATE OR REPLACE VIEW team_game_statistics AS
            SELECT 
                ga.team_id,
                ga.game_id,
                g.scheduled_at,
                g.season,
                SUM(CASE WHEN ga.action_type LIKE '%_made' THEN ga.points ELSE 0 END) as points_scored,
                SUM(CASE WHEN ga.action_type IN ('rebound_offensive', 'rebound_defensive') THEN 1 ELSE 0 END) as total_rebounds,
                SUM(CASE WHEN ga.action_type = 'rebound_offensive' THEN 1 ELSE 0 END) as offensive_rebounds,
                SUM(CASE WHEN ga.action_type = 'assist' THEN 1 ELSE 0 END) as assists,
                SUM(CASE WHEN ga.action_type = 'steal' THEN 1 ELSE 0 END) as steals,
                SUM(CASE WHEN ga.action_type = 'block' THEN 1 ELSE 0 END) as blocks,
                SUM(CASE WHEN ga.action_type = 'turnover' THEN 1 ELSE 0 END) as turnovers,
                SUM(CASE WHEN ga.action_type LIKE 'field_goal_%' THEN 1 ELSE 0 END) as field_goal_attempts,
                SUM(CASE WHEN ga.action_type = 'field_goal_made' THEN 1 ELSE 0 END) as field_goal_made,
                SUM(CASE WHEN ga.action_type LIKE 'three_point_%' THEN 1 ELSE 0 END) as three_point_attempts,
                SUM(CASE WHEN ga.action_type = 'three_point_made' THEN 1 ELSE 0 END) as three_point_made,
                CASE 
                    WHEN SUM(CASE WHEN ga.action_type LIKE 'field_goal_%' THEN 1 ELSE 0 END) > 0 
                    THEN ROUND(SUM(CASE WHEN ga.action_type = 'field_goal_made' THEN 1 ELSE 0 END) * 100.0 / 
                              SUM(CASE WHEN ga.action_type LIKE 'field_goal_%' THEN 1 ELSE 0 END), 2)
                    ELSE 0 
                END as field_goal_percentage
            FROM game_actions ga
            JOIN games g ON ga.game_id = g.id
            WHERE g.status = 'finished'
            GROUP BY ga.team_id, ga.game_id, g.scheduled_at, g.season
        ");

        // Shot Chart Aggregation View
        DB::statement("
            CREATE OR REPLACE VIEW shot_chart_summary AS
            SELECT 
                player_id,
                team_id,
                shot_zone,
                shot_distance,
                COUNT(*) as attempts,
                SUM(CASE WHEN is_successful = 1 THEN 1 ELSE 0 END) as made,
                ROUND(AVG(CASE WHEN is_successful = 1 THEN 100.0 ELSE 0.0 END), 2) as percentage,
                AVG(shot_distance) as avg_distance
            FROM game_actions
            WHERE action_type LIKE '%_point_%' 
              AND shot_x IS NOT NULL 
              AND shot_y IS NOT NULL
            GROUP BY player_id, team_id, shot_zone, shot_distance
            HAVING COUNT(*) >= 5
        ");

        // === CREATE STORED PROCEDURES FOR COMMON QUERIES ===
        
        // Player Season Statistics
        DB::statement("
            DROP PROCEDURE IF EXISTS GetPlayerSeasonStats;
        ");
        
        DB::statement("
            CREATE PROCEDURE GetPlayerSeasonStats(IN playerId INT, IN seasonYear VARCHAR(10))
            BEGIN
                SELECT 
                    p.id,
                    p.first_name,
                    p.last_name,
                    COUNT(DISTINCT pgs.game_id) as games_played,
                    ROUND(AVG(pgs.points), 2) as avg_points,
                    ROUND(AVG(pgs.rebounds), 2) as avg_rebounds,
                    ROUND(AVG(pgs.assists), 2) as avg_assists,
                    SUM(pgs.field_goal_made) as total_fg_made,
                    SUM(pgs.field_goal_attempts) as total_fg_attempts,
                    CASE 
                        WHEN SUM(pgs.field_goal_attempts) > 0 
                        THEN ROUND(SUM(pgs.field_goal_made) * 100.0 / SUM(pgs.field_goal_attempts), 2)
                        ELSE 0 
                    END as field_goal_percentage
                FROM players p
                LEFT JOIN player_game_statistics pgs ON p.id = pgs.player_id AND pgs.season = seasonYear
                WHERE p.id = playerId
                GROUP BY p.id, p.first_name, p.last_name;
            END
        ");

        // Team Head-to-Head Statistics
        DB::statement("
            DROP PROCEDURE IF EXISTS GetTeamHeadToHead;
        ");

        DB::statement("
            CREATE PROCEDURE GetTeamHeadToHead(IN team1Id INT, IN team2Id INT)
            BEGIN
                SELECT 
                    COUNT(*) as total_games,
                    SUM(CASE 
                        WHEN (g.home_team_id = team1Id AND g.home_team_score > g.away_team_score) OR
                             (g.away_team_id = team1Id AND g.away_team_score > g.home_team_score)
                        THEN 1 ELSE 0 
                    END) as team1_wins,
                    SUM(CASE 
                        WHEN (g.home_team_id = team2Id AND g.home_team_score > g.away_team_score) OR
                             (g.away_team_id = team2Id AND g.away_team_score > g.home_team_score)
                        THEN 1 ELSE 0 
                    END) as team2_wins,
                    AVG(CASE WHEN g.home_team_id = team1Id THEN g.home_team_score ELSE g.away_team_score END) as team1_avg_score,
                    AVG(CASE WHEN g.home_team_id = team2Id THEN g.home_team_score ELSE g.away_team_score END) as team2_avg_score
                FROM games g
                WHERE ((g.home_team_id = team1Id AND g.away_team_id = team2Id) OR 
                       (g.home_team_id = team2Id AND g.away_team_id = team1Id))
                  AND g.status = 'finished';
            END
        ");

        // === PARTITIONING SETUP FOR LARGE TABLES ===
        // Note: Partitioning removed due to foreign key constraints compatibility issues
        // Can be implemented later if needed with proper foreign key handling
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        // Drop stored procedures
        DB::statement("DROP PROCEDURE IF EXISTS GetPlayerSeasonStats");
        DB::statement("DROP PROCEDURE IF EXISTS GetTeamHeadToHead");

        // Drop views
        DB::statement("DROP VIEW IF EXISTS player_game_statistics");
        DB::statement("DROP VIEW IF EXISTS team_game_statistics");
        DB::statement("DROP VIEW IF EXISTS shot_chart_summary");

        // Remove partitioning (MySQL 8.0+) - not implemented in up() method
        // DB::statement("ALTER TABLE game_actions REMOVE PARTITIONING");

        // Drop indexes from game_actions
        Schema::table('game_actions', function (Blueprint $table) {
            $table->dropIndex('idx_action_success');
            $table->dropIndex('idx_game_action_period');
            $table->dropIndex('idx_player_game_action');
            $table->dropIndex('idx_team_game_period');
            $table->dropIndex('idx_shot_zone_success');
            $table->dropIndex('idx_shot_distance_success');
            $table->dropIndex('idx_recorded_game');
            $table->dropIndex('idx_period_time');
            $table->dropIndex('idx_foul_player');
            $table->dropIndex('idx_game_team_action_result');
            $table->dropIndex('idx_player_action_time');
        });

        // Drop indexes from games
        Schema::table('games', function (Blueprint $table) {
            $table->dropIndex('idx_status_scheduled');
            $table->dropIndex('idx_season_status');
            $table->dropIndex('idx_home_team_date');
            $table->dropIndex('idx_away_team_date');
            $table->dropIndex('idx_game_scores');
            $table->dropIndex('idx_teams_date');
        });

        // Drop indexes from players
        Schema::table('players', function (Blueprint $table) {
            $table->dropIndex('idx_team_status');
            $table->dropIndex('idx_position_status');
            $table->dropIndex('idx_jersey_team');
            $table->dropIndex('idx_physical_stats');
        });

        // Drop indexes from teams
        Schema::table('teams', function (Blueprint $table) {
            $table->dropIndex('idx_club_season');
            $table->dropIndex('idx_league_division');
            $table->dropIndex('idx_status_season');
        });

        // Drop indexes from ml_models
        Schema::table('ml_models', function (Blueprint $table) {
            $table->dropIndex('idx_model_type_status');
            $table->dropIndex('idx_accuracy');
            $table->dropIndex('idx_trained_status');
        });

        // Drop indexes from api_usage_tracking
        Schema::table('api_usage_tracking', function (Blueprint $table) {
            $table->dropIndex('idx_user_usage_time');
            $table->dropIndex('idx_endpoint_usage_time');
            $table->dropIndex('idx_status_time');
            $table->dropIndex('idx_ip_endpoint_time');
        });

        // Drop indexes from emergency_contacts
        Schema::table('emergency_contacts', function (Blueprint $table) {
            $table->dropIndex('idx_user_priority');
            $table->dropIndex('idx_phone_verified');
        });

        // Drop indexes from tenants
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex('idx_subscription_trial');
            $table->dropIndex('idx_payment_status_time');
        });
    }
};