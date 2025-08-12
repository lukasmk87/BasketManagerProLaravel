<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_officials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained();
            
            // Official Information
            $table->enum('role', [
                'head_referee', 'assistant_referee', 'scorekeeper', 'timekeeper',
                'statistician', 'announcer', 'media_coordinator', 'tournament_director'
            ]);
            $table->string('certification_level')->nullable();
            $table->json('certifications')->nullable(); // List of certifications
            $table->integer('experience_years')->nullable();
            
            // Availability
            $table->json('available_dates')->nullable(); // Array of available dates
            $table->json('available_times')->nullable(); // Time preferences
            $table->json('unavailable_periods')->nullable();
            $table->integer('max_games_per_day')->default(4);
            
            // Assignment Status
            $table->enum('status', ['invited', 'confirmed', 'declined', 'cancelled'])->default('invited');
            $table->timestamp('response_deadline')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('status_notes')->nullable();
            
            // Game Assignments
            $table->integer('games_assigned')->default(0);
            $table->integer('games_completed')->default(0);
            $table->json('assigned_games')->nullable(); // Array of game IDs
            
            // Performance Tracking
            $table->decimal('performance_rating', 3, 2)->nullable(); // Average rating
            $table->integer('total_ratings')->default(0);
            $table->json('performance_notes')->nullable();
            $table->integer('technical_fouls_called')->default(0);
            $table->integer('ejections_made')->default(0);
            
            // Financial
            $table->decimal('rate_per_game', 6, 2)->nullable();
            $table->decimal('travel_allowance', 6, 2)->nullable();
            $table->decimal('total_earnings', 8, 2)->default(0);
            $table->boolean('payment_completed')->default(false);
            $table->string('payment_method')->nullable();
            
            // Contact and Logistics
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->text('dietary_restrictions')->nullable();
            $table->text('accommodation_needs')->nullable();
            $table->boolean('requires_transportation')->default(false);
            
            // Equipment
            $table->json('equipment_provided')->nullable(); // Uniforms, whistle, etc.
            $table->text('equipment_notes')->nullable();
            
            // Feedback and Evaluation
            $table->json('game_feedback')->nullable(); // Feedback per game
            $table->decimal('punctuality_rating', 3, 2)->nullable();
            $table->decimal('communication_rating', 3, 2)->nullable();
            $table->decimal('professionalism_rating', 3, 2)->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['tournament_id', 'role']);
            $table->index(['tournament_id', 'status']);
            $table->index(['user_id', 'role']);
            $table->index('performance_rating');
            $table->unique(['tournament_id', 'user_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_officials');
    }
};