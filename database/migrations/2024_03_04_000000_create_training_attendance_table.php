<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('player_id')->constrained();
            $table->foreignId('recorded_by_user_id')->constrained('users');
            
            // Attendance Status
            $table->enum('status', ['present', 'absent', 'late', 'excused', 'injured', 'unknown']);
            $table->timestamp('arrival_time')->nullable();
            $table->timestamp('departure_time')->nullable();
            $table->integer('minutes_late')->nullable();
            
            // Participation Information
            $table->boolean('full_participation')->default(true);
            $table->text('participation_notes')->nullable();
            $table->enum('participation_level', ['full', 'limited', 'observation_only', 'none'])->default('full');
            
            // Medical/Injury Information
            $table->boolean('has_injury_concern')->default(false);
            $table->text('injury_notes')->nullable();
            $table->boolean('medical_clearance')->default(true);
            
            // Performance and Attitude
            $table->integer('effort_rating')->nullable(); // 1-10
            $table->integer('attitude_rating')->nullable(); // 1-10
            $table->text('coach_notes')->nullable();
            
            // Administrative
            $table->text('absence_reason')->nullable();
            $table->boolean('excused_absence')->default(false);
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['training_session_id', 'status']);
            $table->index(['player_id', 'status']);
            $table->unique(['training_session_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_attendance');
    }
};