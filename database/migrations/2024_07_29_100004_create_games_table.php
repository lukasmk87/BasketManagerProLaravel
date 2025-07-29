<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Team information
            $table->foreignId('home_team_id')->constrained('teams')->onDelete('cascade');
            $table->foreignId('away_team_id')->constrained('teams')->onDelete('cascade');
            
            // Game scheduling
            $table->timestamp('scheduled_at');
            $table->timestamp('actual_start_time')->nullable();
            $table->timestamp('actual_end_time')->nullable();
            $table->string('venue')->nullable();
            $table->string('venue_address')->nullable();
            
            // Game type
            $table->enum('type', [
                'regular_season', 'playoff', 'championship', 
                'friendly', 'tournament', 'scrimmage'
            ])->default('regular_season');
            $table->string('season', 20);
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
            
            // Quarter/Period scores (stored as JSON for flexibility)
            $table->json('period_scores')->nullable(); // [{period: 1, home: 15, away: 12}, ...]
            
            // Game timing
            $table->integer('current_period')->default(1);
            $table->integer('total_periods')->default(4);
            $table->integer('period_length_minutes')->default(10); // Standard 10 min quarters
            $table->integer('time_remaining_seconds')->nullable(); // Seconds left in current period
            $table->boolean('clock_running')->default(false);
            
            // Overtime information
            $table->integer('overtime_periods')->default(0);
            $table->integer('overtime_length_minutes')->default(5);
            
            // Officials
            $table->json('referees')->nullable(); // Array of user IDs
            $table->json('scorekeepers')->nullable(); // Array of user IDs
            $table->json('timekeepers')->nullable(); // Array of user IDs
            
            // Game statistics
            $table->json('team_stats')->nullable(); // Detailed team statistics
            $table->json('player_stats')->nullable(); // Individual player statistics
            
            // Live game information
            $table->text('live_commentary')->nullable();
            $table->json('play_by_play')->nullable(); // Array of game events
            $table->json('substitutions')->nullable(); // Player substitutions log
            $table->json('timeouts')->nullable(); // Timeout usage tracking
            
            // Fouls and violations
            $table->json('team_fouls')->nullable(); // Team foul counts
            $table->json('technical_fouls')->nullable(); // Technical fouls log
            $table->json('ejections')->nullable(); // Player/coach ejections
            
            // Game result details
            $table->enum('result', ['home_win', 'away_win', 'tie', 'forfeit', 'cancelled'])->nullable();
            $table->string('winning_team_id')->nullable(); // For easier queries
            $table->integer('point_differential')->nullable();
            
            // Tournament information
            $table->string('tournament_id')->nullable();
            $table->string('tournament_round')->nullable();
            $table->integer('tournament_game_number')->nullable();
            
            // Weather and conditions (for outdoor games)
            $table->string('weather_conditions')->nullable();
            $table->integer('temperature')->nullable();
            $table->string('court_conditions')->nullable();
            
            // Media and streaming
            $table->boolean('is_streamed')->default(false);
            $table->string('stream_url')->nullable();
            $table->json('media_links')->nullable(); // Photos, videos, etc.
            
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
            $table->json('game_rules')->nullable(); // Special rules or modifications
            $table->boolean('allow_spectators')->default(true);
            $table->boolean('allow_media')->default(true);
            
            // Emergency information
            $table->json('emergency_contacts')->nullable();
            $table->string('medical_staff_present')->nullable();
            
            // GDPR and permissions
            $table->boolean('allow_recording')->default(true);
            $table->boolean('allow_photos')->default(true);
            $table->boolean('allow_streaming')->default(false);
            
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
            
            // Ensure teams don't play themselves
            $table->check('home_team_id != away_team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};