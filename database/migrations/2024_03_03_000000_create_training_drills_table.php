<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_drills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('drill_id')->constrained();
            
            // Order and Timing
            $table->integer('order_in_session')->default(1);
            $table->integer('planned_duration')->default(10); // minutes
            $table->integer('actual_duration')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            
            // Drill Configuration
            $table->integer('participants_count')->nullable();
            $table->json('participating_players')->nullable(); // player IDs
            $table->text('specific_instructions')->nullable();
            $table->text('modifications')->nullable(); // Changes from original drill
            
            // Performance Metrics
            $table->json('success_metrics')->nullable(); // Actual measurements
            $table->integer('drill_rating')->nullable(); // 1-10 how well it went
            $table->text('performance_notes')->nullable();
            $table->text('trainer_observations')->nullable();
            
            // Completion Status
            $table->enum('status', ['planned', 'in_progress', 'completed', 'skipped', 'modified']);
            $table->text('skip_reason')->nullable();
            $table->boolean('goals_achieved')->default(false);
            
            // Player Feedback
            $table->decimal('player_difficulty_rating', 3, 2)->nullable(); // 1-10
            $table->decimal('player_enjoyment_rating', 3, 2)->nullable(); // 1-10
            $table->text('player_feedback')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['training_session_id', 'order_in_session']);
            $table->index(['drill_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_drills');
    }
};