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
        Schema::create('video_annotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_file_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->foreignId('player_id')->nullable()->constrained('users');
            
            // Temporal Information
            $table->decimal('start_time', 8, 3); // seconds with millisecond precision
            $table->decimal('end_time', 8, 3)->nullable(); // null for point annotations
            $table->integer('frame_start')->nullable();
            $table->integer('frame_end')->nullable();
            
            // Annotation Type and Content
            $table->enum('annotation_type', [
                'play_action', 'player_highlight', 'tactical_note', 'mistake',
                'good_play', 'foul', 'timeout', 'substitution', 'coaching_point',
                'statistical_event', 'injury', 'technical_issue', 'custom'
            ]);
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('coaching_notes')->nullable();
            
            // Basketball-specific Data
            $table->enum('play_type', [
                'offense', 'defense', 'transition', 'set_play', 'fast_break',
                'rebound', 'shot', 'pass', 'dribble', 'screen', 'cut'
            ])->nullable();
            
            $table->enum('court_area', [
                'paint', 'three_point_line', 'free_throw_line', 'baseline',
                'sideline', 'center_court', 'backcourt', 'frontcourt'
            ])->nullable();
            
            $table->json('players_involved')->nullable(); // Array of player IDs
            $table->enum('outcome', ['successful', 'unsuccessful', 'neutral'])->nullable();
            $table->integer('points_scored')->nullable();
            
            // Visual Annotations
            $table->json('visual_markers')->nullable(); // Shapes, arrows, circles on video
            $table->json('coordinate_data')->nullable(); // X,Y coordinates for court positions
            $table->string('marker_color', 7)->default('#FF0000'); // Hex color
            $table->enum('marker_style', ['circle', 'rectangle', 'arrow', 'line', 'polygon'])->nullable();
            
            // AI/Manual Classification
            $table->boolean('is_ai_generated')->default(false);
            $table->decimal('ai_confidence', 5, 4)->nullable(); // 0-1
            $table->boolean('human_verified')->default(false);
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            
            // Status and Workflow
            $table->enum('status', ['draft', 'pending_review', 'approved', 'rejected']);
            $table->boolean('is_public')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->integer('priority')->default(0); // Higher = more important
            
            // Engagement and Learning
            $table->integer('view_count')->default(0);
            $table->integer('like_count')->default(0);
            $table->integer('educational_value')->default(0); // 1-10 rating
            $table->json('learning_objectives')->nullable();
            $table->json('skill_tags')->nullable(); // Related skills being demonstrated
            
            // Timestamps and Audit
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for Performance
            $table->index(['video_file_id', 'start_time']);
            $table->index(['video_file_id', 'annotation_type']);
            $table->index(['created_by_user_id', 'status']);
            $table->index(['player_id', 'play_type']);
            $table->index(['is_ai_generated', 'human_verified']);
            $table->index(['status', 'is_public']);
            $table->index('start_time');
            $table->index('court_area');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_annotations');
    }
};