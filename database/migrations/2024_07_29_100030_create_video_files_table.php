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
        Schema::create('video_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by_user_id')->constrained('users');
            $table->foreignId('team_id')->nullable()->constrained();
            $table->foreignId('game_id')->nullable()->constrained();
            $table->foreignId('training_session_id')->nullable()->constrained();
            
            // File Information
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('thumbnail_path')->nullable();
            $table->string('processed_path')->nullable(); // Optimized version
            
            // Video Properties
            $table->string('mime_type');
            $table->bigInteger('file_size'); // bytes
            $table->integer('duration'); // seconds
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->decimal('frame_rate', 5, 2)->nullable();
            $table->string('codec')->nullable();
            $table->integer('bitrate')->nullable();
            
            // Video Classification
            $table->enum('video_type', [
                'full_game', 'game_highlights', 'training_session', 'drill_demo',
                'player_analysis', 'tactical_analysis', 'scouting_report',
                'instructional', 'warm_up', 'cool_down', 'interview'
            ]);
            
            $table->enum('recording_angle', [
                'baseline', 'sideline', 'elevated', 'court_level', 'overhead',
                'corner', 'multiple_angles', 'mobile', 'fixed_camera'
            ])->nullable();
            
            $table->json('recording_equipment')->nullable(); // Camera specs, etc.
            $table->timestamp('recorded_at')->nullable();
            $table->string('recording_location')->nullable();
            
            // Processing Status
            $table->enum('processing_status', [
                'uploaded', 'queued', 'processing', 'completed', 'failed', 'archived'
            ])->default('uploaded');
            
            $table->json('processing_metadata')->nullable(); // FFmpeg output, etc.
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processing_completed_at')->nullable();
            $table->text('processing_error')->nullable();
            
            // AI Analysis
            $table->boolean('ai_analysis_enabled')->default(true);
            $table->enum('ai_analysis_status', [
                'pending', 'in_progress', 'completed', 'failed', 'disabled'
            ])->default('pending');
            
            $table->json('ai_analysis_results')->nullable(); // Detected plays, players, etc.
            $table->decimal('ai_confidence_score', 5, 4)->nullable(); // 0-1
            $table->timestamp('ai_analysis_completed_at')->nullable();
            
            // Access Control
            $table->enum('visibility', ['public', 'team_only', 'private', 'archived']);
            $table->boolean('downloadable')->default(false);
            $table->boolean('embeddable')->default(true);
            $table->json('sharing_permissions')->nullable();
            
            // Engagement Metrics
            $table->integer('view_count')->default(0);
            $table->integer('like_count')->default(0);
            $table->integer('share_count')->default(0);
            $table->integer('annotation_count')->default(0);
            $table->decimal('average_rating', 3, 2)->nullable();
            
            // Metadata and Tags
            $table->json('tags')->nullable();
            $table->json('custom_metadata')->nullable();
            $table->text('transcription')->nullable(); // AI-generated or manual
            $table->string('language', 5)->default('de');
            
            // Quality and Technical
            $table->enum('quality_rating', ['low', 'medium', 'high', 'excellent'])->nullable();
            $table->boolean('has_audio')->default(true);
            $table->boolean('has_subtitles')->default(false);
            $table->string('encoding_profile')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['team_id', 'video_type']);
            $table->index(['game_id', 'processing_status']);
            $table->index(['training_session_id', 'ai_analysis_status']);
            $table->index(['uploaded_by_user_id', 'visibility']);
            $table->index(['processing_status', 'created_at']);
            $table->index('recorded_at');
            $table->fullText(['title', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_files');
    }
};