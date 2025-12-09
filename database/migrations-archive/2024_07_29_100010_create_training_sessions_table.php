<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('trainer_id')->constrained('users');
            $table->foreignId('assistant_trainer_id')->nullable()->constrained('users');
            
            // Session Information
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('scheduled_at');
            $table->dateTime('actual_start_time')->nullable();
            $table->dateTime('actual_end_time')->nullable();
            $table->integer('planned_duration')->default(90); // minutes
            $table->integer('actual_duration')->nullable();
            
            // Location
            $table->string('venue');
            $table->text('venue_address')->nullable();
            $table->string('court_type')->nullable(); // indoor, outdoor, gym
            
            // Session Type and Focus
            $table->enum('session_type', [
                'training', 'scrimmage', 'conditioning', 'tactical', 
                'individual', 'team_building', 'recovery'
            ])->default('training');
            
            $table->json('focus_areas')->nullable(); // offense, defense, conditioning, etc.
            $table->enum('intensity_level', ['low', 'medium', 'high', 'maximum'])->default('medium');
            $table->integer('max_participants')->nullable();
            
            // Status
            $table->enum('status', [
                'scheduled', 'in_progress', 'completed', 'cancelled', 'postponed'
            ])->default('scheduled');
            
            // Weather (for outdoor sessions)
            $table->string('weather_conditions')->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->boolean('weather_appropriate')->default(true);
            
            // Equipment and Requirements
            $table->json('required_equipment')->nullable();
            $table->text('special_requirements')->nullable();
            $table->text('safety_notes')->nullable();
            
            // Evaluation
            $table->integer('overall_rating')->nullable(); // 1-10
            $table->text('trainer_notes')->nullable();
            $table->text('session_feedback')->nullable();
            $table->json('goals_achieved')->nullable();
            
            // Settings
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('allows_late_arrival')->default(false);
            $table->boolean('requires_medical_clearance')->default(false);
            $table->json('notification_settings')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['team_id', 'scheduled_at']);
            $table->index(['trainer_id', 'status']);
            $table->index(['scheduled_at', 'status']);
            $table->index('session_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_sessions');
    }
};