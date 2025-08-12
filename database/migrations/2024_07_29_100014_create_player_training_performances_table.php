<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_training_performances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('player_id')->constrained();
            $table->foreignId('drill_id')->nullable()->constrained();
            $table->foreignId('evaluated_by_user_id')->constrained('users');
            
            // Performance Metrics
            $table->json('skill_ratings')->nullable(); // Different basketball skills (1-10)
            $table->integer('overall_performance')->nullable(); // 1-10
            $table->integer('effort_level')->nullable(); // 1-10
            $table->integer('focus_level')->nullable(); // 1-10
            $table->integer('attitude_rating')->nullable(); // 1-10
            
            // Specific Measurements
            $table->json('quantitative_metrics')->nullable(); // Shots made, time, etc.
            $table->json('improvement_areas')->nullable(); // Areas needing work
            $table->json('strengths_demonstrated')->nullable(); // What went well
            
            // Training Goals
            $table->json('goals_for_session')->nullable(); // Individual goals
            $table->json('goals_achieved')->nullable(); // Which goals were met
            $table->decimal('goal_achievement_percentage', 5, 2)->nullable();
            
            // Detailed Evaluation
            $table->text('performance_notes')->nullable();
            $table->text('improvement_suggestions')->nullable();
            $table->text('next_session_focus')->nullable();
            
            // Physical Condition
            $table->enum('energy_level', ['low', 'medium', 'high', 'excellent'])->nullable();
            $table->boolean('showed_fatigue')->default(false);
            $table->text('physical_observations')->nullable();
            
            // Behavioral Observations
            $table->enum('leadership_shown', ['none', 'some', 'good', 'excellent'])->nullable();
            $table->enum('teamwork_rating', ['poor', 'average', 'good', 'excellent'])->nullable();
            $table->boolean('coachable')->default(true);
            $table->text('behavioral_notes')->nullable();
            
            // Development Tracking
            $table->json('skills_improved')->nullable(); // Skills that showed improvement
            $table->json('skills_regressed')->nullable(); // Skills that declined
            $table->decimal('overall_progress_rating', 3, 2)->nullable(); // -5 to +5
            
            $table->timestamps();
            
            // Indexes
            $table->index(['training_session_id', 'player_id']);
            $table->index(['player_id', 'overall_performance']);
            $table->index(['drill_id', 'player_id']);
            $table->index('evaluated_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_training_performances');
    }
};