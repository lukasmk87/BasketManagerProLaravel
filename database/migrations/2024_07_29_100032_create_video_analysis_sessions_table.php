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
        Schema::create('video_analysis_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_file_id')->constrained()->onDelete('cascade');
            $table->foreignId('analyst_user_id')->constrained('users');
            $table->foreignId('team_id')->nullable()->constrained();
            
            // Session Information
            $table->string('session_name');
            $table->text('session_description')->nullable();
            $table->text('analysis_objectives')->nullable();
            
            // Session Type and Focus
            $table->enum('analysis_type', [
                'player_performance', 'team_tactics', 'opponent_scouting',
                'drill_effectiveness', 'game_breakdown', 'skill_development',
                'injury_analysis', 'referee_decisions', 'custom_analysis'
            ]);
            
            $table->json('focus_areas')->nullable(); // What to analyze
            $table->json('analysis_criteria')->nullable(); // Specific metrics to track
            
            // Session Status and Progress
            $table->enum('status', [
                'planned', 'in_progress', 'paused', 'completed', 'cancelled'
            ])->default('planned');
            
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('total_duration')->nullable(); // minutes spent analyzing
            
            // Participants and Collaboration
            $table->json('invited_users')->nullable(); // User IDs who can participate
            $table->json('participant_roles')->nullable(); // Role assignments
            $table->boolean('allow_collaborative_editing')->default(true);
            $table->json('active_participants')->nullable(); // Currently active users
            
            // Analysis Results
            $table->json('key_findings')->nullable();
            $table->json('recommendations')->nullable();
            $table->json('action_items')->nullable();
            $table->text('summary_notes')->nullable();
            $table->json('statistical_insights')->nullable();
            
            // Basketball-specific Analysis
            $table->json('tactical_observations')->nullable();
            $table->json('player_evaluations')->nullable();
            $table->json('play_breakdowns')->nullable();
            $table->json('improvement_areas')->nullable();
            $table->json('strengths_identified')->nullable();
            
            // Session Configuration
            $table->json('analysis_settings')->nullable(); // Display preferences, filters
            $table->boolean('auto_save_enabled')->default(true);
            $table->integer('auto_save_interval')->default(30); // seconds
            $table->json('video_playback_settings')->nullable();
            
            // Presentation and Sharing
            $table->boolean('presentation_ready')->default(false);
            $table->string('presentation_template')->nullable();
            $table->json('presentation_slides')->nullable();
            $table->boolean('is_shareable')->default(false);
            $table->json('sharing_settings')->nullable();
            $table->string('export_format')->nullable(); // PDF, PowerPoint, etc.
            
            // Integration with Training System
            $table->json('linked_training_sessions')->nullable();
            $table->json('suggested_drills')->nullable();
            $table->json('training_recommendations')->nullable();
            $table->boolean('create_training_plan')->default(false);
            
            // Quality Metrics
            $table->integer('annotation_count')->default(0);
            $table->decimal('analysis_completeness', 5, 2)->default(0.00); // Percentage
            $table->decimal('confidence_rating', 3, 2)->nullable(); // Self-assessed confidence
            $table->enum('priority_level', ['low', 'medium', 'high', 'urgent'])->default('medium');
            
            // Follow-up and Review
            $table->json('follow_up_actions')->nullable();
            $table->timestamp('next_review_date')->nullable();
            $table->foreignId('reviewer_user_id')->nullable()->constrained('users');
            $table->enum('review_status', ['pending', 'approved', 'needs_revision'])->nullable();
            $table->text('review_comments')->nullable();
            
            // Version Control and History
            $table->integer('version_number')->default(1);
            $table->foreignId('previous_version_id')->nullable()->constrained('video_analysis_sessions');
            $table->json('version_changes')->nullable();
            $table->boolean('is_current_version')->default(true);
            
            // Timestamps and Audit
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for Performance
            $table->index(['video_file_id', 'status']);
            $table->index(['analyst_user_id', 'analysis_type']);
            $table->index(['team_id', 'created_at']);
            $table->index(['status', 'priority_level']);
            $table->index(['analysis_type', 'completed_at']);
            $table->index('started_at');
            $table->index('is_current_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_analysis_sessions');
    }
};