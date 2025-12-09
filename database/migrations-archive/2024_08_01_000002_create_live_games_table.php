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
            $table->json('period_scores')->nullable(); // Scores by period
            
            // Team Status
            $table->integer('fouls_home_period')->default(0);
            $table->integer('fouls_away_period')->default(0);
            $table->integer('fouls_home_total')->default(0);
            $table->integer('fouls_away_total')->default(0);
            $table->integer('timeouts_home_remaining')->default(5);
            $table->integer('timeouts_away_remaining')->default(5);
            
            // Current Players on Court
            $table->json('players_on_court_home')->nullable(); // Array of player IDs
            $table->json('players_on_court_away')->nullable();
            
            // Game Flow Control
            $table->enum('game_phase', [
                'pregame', 'period', 'halftime', 'overtime', 
                'timeout', 'break', 'postgame'
            ])->default('pregame');
            
            $table->boolean('is_in_timeout')->default(false);
            $table->string('timeout_team')->nullable(); // 'home', 'away', 'official'
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
            
            // Indexes
            $table->index(['game_id', 'is_being_broadcasted']);
            $table->index('last_update_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_games');
    }
};