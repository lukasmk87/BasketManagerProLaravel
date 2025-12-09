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
        Schema::create('game_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->foreignId('player_id')->constrained();
            $table->foreignId('team_id')->constrained('teams');
            
            // Action Details
            $table->enum('action_type', [
                // Scoring
                'field_goal_made', 'field_goal_missed',
                'three_point_made', 'three_point_missed',
                'free_throw_made', 'free_throw_missed',
                
                // Rebounds
                'rebound_offensive', 'rebound_defensive',
                
                // Assists & Plays
                'assist', 'steal', 'block', 'turnover',
                
                // Fouls
                'foul_personal', 'foul_technical', 'foul_flagrant',
                'foul_unsportsmanlike', 'foul_offensive',
                
                // Substitutions
                'substitution_in', 'substitution_out',
                
                // Timeouts
                'timeout_team', 'timeout_official',
                
                // Other
                'jump_ball_won', 'jump_ball_lost',
                'ejection', 'injury_timeout'
            ]);
            
            // Game Time
            $table->integer('period'); // Quarter/Period number
            $table->time('time_remaining'); // Time left in period (MM:SS)
            $table->integer('game_clock_seconds')->nullable(); // Total seconds elapsed
            $table->integer('shot_clock_remaining')->nullable();
            
            // Points and Impact
            $table->integer('points')->default(0);
            $table->boolean('is_successful')->nullable();
            $table->boolean('is_assisted')->default(false);
            $table->foreignId('assisted_by_player_id')->nullable()->constrained('players');
            
            // Shot Chart Data
            $table->decimal('shot_x', 5, 2)->nullable(); // Court X coordinate
            $table->decimal('shot_y', 5, 2)->nullable(); // Court Y coordinate
            $table->decimal('shot_distance', 4, 1)->nullable(); // Distance in feet/meters
            $table->string('shot_zone')->nullable(); // Paint, Mid-range, Three-point, etc.
            
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
            $table->index(['shot_x', 'shot_y']); // For shot charts
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_actions');
    }
};