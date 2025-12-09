<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated Games Migration
 *
 * This migration consolidates:
 * - 2024_07_29_100004_create_games_table.php
 * - 2024_08_01_000001_create_game_actions_table.php
 * - 2024_08_01_000002_create_live_games_table.php
 * - 2025_08_26_100003_create_game_registrations_table.php
 * - 2025_08_26_100004_create_game_participations_table.php
 * - 2025_08_26_100005_add_booking_deadline_to_games_table.php
 * - 2025_08_28_105341_add_external_team_support_to_games_table.php
 * - 2025_11_19_083507_add_season_id_to_games_table.php
 * - 2025_12_08_102549_add_gym_hall_id_to_games_table.php
 * - 2025_12_08_152442_make_home_team_id_nullable_in_games_table.php
 *
 * Note: home_team_id and away_team_id are nullable (for external teams)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->uuid('uuid')->unique();
            $table->foreignId('season_id')->nullable()->constrained('seasons')->onDelete('cascade');

            // Team information (nullable for external teams)
            $table->foreignId('home_team_id')->nullable()->constrained('teams')->onDelete('cascade');
            $table->foreignId('away_team_id')->nullable()->constrained('teams')->onDelete('cascade');
            $table->string('home_team_name')->nullable();
            $table->string('away_team_name')->nullable();

            // Game scheduling
            $table->timestamp('scheduled_at');
            $table->timestamp('actual_start_time')->nullable();
            $table->timestamp('actual_end_time')->nullable();
            $table->string('venue')->nullable();
            $table->string('venue_address')->nullable();
            $table->string('venue_code', 50)->nullable();
            $table->foreignId('gym_hall_id')->nullable();

            // Import source tracking
            $table->enum('import_source', ['manual', 'ical', 'api'])->default('manual');
            $table->string('external_game_id')->nullable();
            $table->json('import_metadata')->nullable();
            $table->string('external_url')->nullable();
            $table->boolean('is_home_game')->nullable();

            // Game type
            $table->enum('type', [
                'regular_season', 'playoff', 'championship',
                'friendly', 'tournament', 'scrimmage'
            ])->default('regular_season');
            $table->string('season', 20)->nullable();
            $table->string('league')->nullable();
            $table->string('division')->nullable();

            // Game status
            $table->enum('status', [
                'scheduled', 'live', 'halftime', 'overtime',
                'finished', 'cancelled', 'postponed', 'forfeited'
            ])->default('scheduled');

            // Scoring
            $table->integer('home_team_score')->default(0);
            $table->integer('away_team_score')->default(0);
            $table->json('period_scores')->nullable();

            // Game timing
            $table->integer('current_period')->default(1);
            $table->integer('total_periods')->default(4);
            $table->integer('period_length_minutes')->default(10);
            $table->integer('time_remaining_seconds')->nullable();
            $table->boolean('clock_running')->default(false);

            // Overtime information
            $table->integer('overtime_periods')->default(0);
            $table->integer('overtime_length_minutes')->default(5);

            // Officials
            $table->json('referees')->nullable();
            $table->json('scorekeepers')->nullable();
            $table->json('timekeepers')->nullable();

            // Game statistics
            $table->json('team_stats')->nullable();
            $table->json('player_stats')->nullable();

            // Live game information
            $table->text('live_commentary')->nullable();
            $table->json('play_by_play')->nullable();
            $table->json('substitutions')->nullable();
            $table->json('timeouts')->nullable();

            // Fouls and violations
            $table->json('team_fouls')->nullable();
            $table->json('technical_fouls')->nullable();
            $table->json('ejections')->nullable();

            // Game result details
            $table->enum('result', ['home_win', 'away_win', 'tie', 'forfeit', 'cancelled'])->nullable();
            $table->string('winning_team_id')->nullable();
            $table->integer('point_differential')->nullable();

            // Tournament information
            $table->string('tournament_id')->nullable();
            $table->string('tournament_round')->nullable();
            $table->integer('tournament_game_number')->nullable();

            // Weather and conditions
            $table->string('weather_conditions')->nullable();
            $table->integer('temperature')->nullable();
            $table->string('court_conditions')->nullable();

            // Media and streaming
            $table->boolean('is_streamed')->default(false);
            $table->string('stream_url')->nullable();
            $table->json('media_links')->nullable();

            // Game notes and reports
            $table->text('pre_game_notes')->nullable();
            $table->text('post_game_notes')->nullable();
            $table->text('referee_report')->nullable();
            $table->text('incident_report')->nullable();

            // Attendance and fan information
            $table->integer('attendance')->nullable();
            $table->integer('capacity')->nullable();
            $table->json('ticket_prices')->nullable();

            // Game settings and rules
            $table->json('game_rules')->nullable();
            $table->boolean('allow_spectators')->default(true);
            $table->boolean('allow_media')->default(true);

            // Emergency information
            $table->json('emergency_contacts')->nullable();
            $table->string('medical_staff_present')->nullable();

            // GDPR and permissions
            $table->boolean('allow_recording')->default(true);
            $table->boolean('allow_photos')->default(true);
            $table->boolean('allow_streaming')->default(false);

            // Registration settings
            $table->integer('registration_deadline_hours')->default(24);
            $table->integer('max_roster_size')->default(12);
            $table->integer('min_roster_size')->default(8);
            $table->boolean('allow_player_registrations')->default(true);
            $table->boolean('auto_confirm_registrations')->default(false);
            $table->integer('lineup_deadline_hours')->default(2);

            // Data integrity
            $table->boolean('stats_verified')->default(false);
            $table->timestamp('stats_verified_at')->nullable();
            $table->foreignId('stats_verified_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['home_team_id', 'away_team_id']);
            $table->index(['scheduled_at', 'status']);
            $table->index(['season', 'type']);
            $table->index(['league', 'division']);
            $table->index('status');
            $table->index(['tournament_id', 'tournament_round']);
            $table->index('winning_team_id');
            $table->index(['is_streamed', 'scheduled_at']);
            $table->index(['import_source', 'external_game_id']);
            $table->index('venue_code');
            $table->index(['away_team_name', 'scheduled_at']);

            // FK to tenants
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // Game Actions (live scoring events)
        Schema::create('game_actions', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->foreignId('player_id')->constrained();
            $table->foreignId('team_id')->constrained('teams');

            $table->enum('action_type', [
                'field_goal_made', 'field_goal_missed',
                'three_point_made', 'three_point_missed',
                'free_throw_made', 'free_throw_missed',
                'rebound_offensive', 'rebound_defensive',
                'assist', 'steal', 'block', 'turnover',
                'foul_personal', 'foul_technical', 'foul_flagrant',
                'foul_unsportsmanlike', 'foul_offensive',
                'substitution_in', 'substitution_out',
                'timeout_team', 'timeout_official',
                'jump_ball_won', 'jump_ball_lost',
                'ejection', 'injury_timeout'
            ]);

            // Game Time
            $table->integer('period');
            $table->time('time_remaining');
            $table->integer('game_clock_seconds')->nullable();
            $table->integer('shot_clock_remaining')->nullable();

            // Points and Impact
            $table->integer('points')->default(0);
            $table->boolean('is_successful')->nullable();
            $table->boolean('is_assisted')->default(false);
            $table->foreignId('assisted_by_player_id')->nullable()->constrained('players');

            // Shot Chart Data
            $table->decimal('shot_x', 5, 2)->nullable();
            $table->decimal('shot_y', 5, 2)->nullable();
            $table->decimal('shot_distance', 4, 1)->nullable();
            $table->string('shot_zone')->nullable();

            // Foul Details
            $table->enum('foul_type', [
                'shooting', 'non_shooting', 'technical', 'flagrant_1',
                'flagrant_2', 'unsportsmanlike', 'offensive'
            ])->nullable();
            $table->boolean('foul_results_in_free_throws')->default(false);
            $table->integer('free_throws_awarded')->default(0);

            // Substitution Details
            $table->foreignId('substituted_player_id')->nullable()->constrained('players');
            $table->string('substitution_reason')->nullable();

            // Context and Notes
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->json('additional_data')->nullable();

            // Scorer Information
            $table->foreignId('recorded_by_user_id')->constrained('users');
            $table->ipAddress('recorded_from_ip')->nullable();
            $table->timestamp('recorded_at');

            // Review and Corrections
            $table->boolean('is_reviewed')->default(false);
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->boolean('is_corrected')->default(false);
            $table->foreignId('corrected_by_user_id')->nullable()->constrained('users');
            $table->text('correction_reason')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['game_id', 'period', 'time_remaining']);
            $table->index(['player_id', 'action_type']);
            $table->index(['team_id', 'action_type']);
            $table->index(['game_id', 'recorded_at']);
            $table->index(['shot_x', 'shot_y']);

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // Live Games (real-time game state)
        Schema::create('live_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->unique()->constrained()->onDelete('cascade');

            // Current Game State
            $table->integer('current_period')->default(1);
            $table->time('period_time_remaining')->default('10:00');
            $table->integer('period_time_elapsed_seconds')->default(0);
            $table->boolean('period_is_running')->default(false);
            $table->timestamp('period_started_at')->nullable();
            $table->timestamp('period_paused_at')->nullable();

            // Shot Clock
            $table->integer('shot_clock_remaining')->default(24);
            $table->boolean('shot_clock_is_running')->default(false);
            $table->timestamp('shot_clock_started_at')->nullable();

            // Current Scores
            $table->integer('current_score_home')->default(0);
            $table->integer('current_score_away')->default(0);
            $table->json('period_scores')->nullable();

            // Team Status
            $table->integer('fouls_home_period')->default(0);
            $table->integer('fouls_away_period')->default(0);
            $table->integer('fouls_home_total')->default(0);
            $table->integer('fouls_away_total')->default(0);
            $table->integer('timeouts_home_remaining')->default(5);
            $table->integer('timeouts_away_remaining')->default(5);

            // Current Players on Court
            $table->json('players_on_court_home')->nullable();
            $table->json('players_on_court_away')->nullable();

            // Game Flow Control
            $table->enum('game_phase', [
                'pregame', 'period', 'halftime', 'overtime',
                'timeout', 'break', 'postgame'
            ])->default('pregame');

            $table->boolean('is_in_timeout')->default(false);
            $table->string('timeout_team')->nullable();
            $table->timestamp('timeout_started_at')->nullable();
            $table->integer('timeout_duration_seconds')->default(60);

            // Last Action Reference
            $table->foreignId('last_action_id')->nullable()->constrained('game_actions');
            $table->timestamp('last_action_at')->nullable();

            // Broadcasting
            $table->integer('viewers_count')->default(0);
            $table->boolean('is_being_broadcasted')->default(false);
            $table->json('broadcast_settings')->nullable();

            // Performance Tracking
            $table->integer('actions_count')->default(0);
            $table->timestamp('last_update_at')->nullable();

            $table->timestamps();

            $table->index(['game_id', 'is_being_broadcasted']);
            $table->index('last_update_at');
        });

        // Game Registrations (player registration for games)
        Schema::create('game_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['registered', 'confirmed', 'declined', 'absent'])->default('registered');
            $table->text('notes')->nullable();
            $table->timestamp('registered_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by_user_id')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['game_id', 'player_id']);
            $table->index(['game_id', 'team_id', 'status']);
        });

        // Game Participations (actual participation in games)
        Schema::create('game_participations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->integer('jersey_number')->nullable();
            $table->boolean('is_starter')->default(false);
            $table->integer('minutes_played')->default(0);
            $table->json('stats')->nullable();
            $table->timestamps();

            $table->unique(['game_id', 'player_id']);
            $table->index(['game_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_participations');
        Schema::dropIfExists('game_registrations');
        Schema::dropIfExists('live_games');
        Schema::dropIfExists('game_actions');
        Schema::dropIfExists('games');
    }
};
