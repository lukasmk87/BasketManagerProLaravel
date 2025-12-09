<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated Training System Migration
 *
 * Includes: training_sessions, drills, training_drills, training_attendance,
 * player_training_performances, drill_ratings, drill_favorites, training_registrations
 */
return new class extends Migration
{
    public function up(): void
    {
        // Training Sessions
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->uuid('uuid')->unique();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('gym_hall_id')->nullable()->constrained('gym_halls')->onDelete('set null');
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('objectives')->nullable();
            $table->timestamp('scheduled_at');
            $table->integer('duration_minutes')->default(90);
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->enum('type', ['regular', 'skills', 'conditioning', 'scrimmage', 'video_review', 'other'])->default('regular');
            $table->integer('max_participants')->nullable();
            $table->json('focus_areas')->nullable();
            $table->json('equipment_needed')->nullable();
            $table->text('notes')->nullable();
            $table->text('post_training_notes')->nullable();
            $table->integer('intensity_level')->nullable();
            $table->boolean('is_mandatory')->default(false);
            $table->integer('registration_deadline_hours')->default(24);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'scheduled_at']);
            $table->index(['status', 'scheduled_at']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // Drills
        Schema::create('drills', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->uuid('uuid')->unique();
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->integer('duration_minutes')->default(15);
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced', 'expert'])->default('intermediate');
            $table->json('focus_areas')->nullable();
            $table->json('equipment_needed')->nullable();
            $table->integer('min_players')->nullable();
            $table->integer('max_players')->nullable();
            $table->json('variations')->nullable();
            $table->json('coaching_points')->nullable();
            $table->string('video_url')->nullable();
            $table->string('diagram_path')->nullable();
            $table->json('visual_data')->nullable();
            $table->boolean('is_public')->default(false);
            $table->decimal('average_rating', 3, 2)->nullable();
            $table->integer('times_used')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['difficulty', 'is_public']);
            $table->fullText(['name', 'description']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // Training-Drill pivot
        Schema::create('training_drills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')->constrained('training_sessions')->onDelete('cascade');
            $table->foreignId('drill_id')->constrained()->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->integer('duration_minutes')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['training_session_id', 'drill_id'], 'unique_training_drill');
        });

        // Training Attendance
        Schema::create('training_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')->constrained('training_sessions')->onDelete('cascade');
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['present', 'absent', 'late', 'excused', 'injured'])->default('present');
            $table->integer('minutes_attended')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamps();

            $table->unique(['training_session_id', 'player_id'], 'unique_training_attendance');
            $table->index(['player_id', 'status']);
        });

        // Player Training Performances
        Schema::create('player_training_performances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')->constrained('training_sessions')->onDelete('cascade');
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->foreignId('rated_by_user_id')->constrained('users')->onDelete('cascade');
            $table->integer('effort_rating')->nullable();
            $table->integer('skill_rating')->nullable();
            $table->integer('attitude_rating')->nullable();
            $table->integer('overall_rating')->nullable();
            $table->text('strengths')->nullable();
            $table->text('areas_to_improve')->nullable();
            $table->text('coach_notes')->nullable();
            $table->timestamps();

            $table->unique(['training_session_id', 'player_id'], 'unique_training_perf');
        });

        // Drill Ratings
        Schema::create('drill_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drill_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('rating');
            $table->text('review')->nullable();
            $table->timestamps();

            $table->unique(['drill_id', 'user_id']);
        });

        // Drill Favorites
        Schema::create('drill_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drill_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['drill_id', 'user_id']);
        });

        // Training Registrations
        Schema::create('training_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')->constrained('training_sessions')->onDelete('cascade');
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['registered', 'confirmed', 'declined', 'waitlisted'])->default('registered');
            $table->text('notes')->nullable();
            $table->timestamp('registered_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->unique(['training_session_id', 'player_id'], 'unique_training_registration');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_registrations');
        Schema::dropIfExists('drill_favorites');
        Schema::dropIfExists('drill_ratings');
        Schema::dropIfExists('player_training_performances');
        Schema::dropIfExists('training_attendance');
        Schema::dropIfExists('training_drills');
        Schema::dropIfExists('drills');
        Schema::dropIfExists('training_sessions');
    }
};
