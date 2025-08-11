<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->constrained();
            
            // Registration Information
            $table->timestamp('registered_at');
            $table->foreignId('registered_by_user_id')->constrained('users');
            $table->text('registration_notes')->nullable();
            
            // Status
            $table->enum('status', [
                'pending', 'approved', 'rejected', 'withdrawn', 'disqualified'
            ])->default('pending');
            $table->text('status_reason')->nullable();
            $table->timestamp('status_updated_at')->nullable();
            
            // Tournament Position
            $table->integer('seed')->nullable(); // Seeding position
            $table->string('group_name')->nullable(); // For group stage tournaments
            $table->integer('group_position')->nullable();
            
            // Performance Tracking
            $table->integer('games_played')->default(0);
            $table->integer('wins')->default(0);
            $table->integer('losses')->default(0);
            $table->integer('draws')->default(0); // For round robin tournaments
            $table->integer('points_for')->default(0);
            $table->integer('points_against')->default(0);
            $table->integer('tournament_points')->default(0); // Points in tournament standings
            $table->decimal('point_differential', 8, 2)->default(0);
            
            // Final Results
            $table->integer('final_position')->nullable();
            $table->enum('elimination_round', [
                'group_stage', 'round_of_32', 'round_of_16', 'quarterfinal',
                'semifinal', 'final', 'winner', 'third_place'
            ])->nullable();
            $table->timestamp('eliminated_at')->nullable();
            
            // Financial
            $table->boolean('entry_fee_paid')->default(false);
            $table->timestamp('payment_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->decimal('prize_money', 8, 2)->nullable();
            
            // Contact and Logistics
            $table->string('contact_person');
            $table->string('contact_email');
            $table->string('contact_phone');
            $table->text('special_requirements')->nullable();
            $table->json('travel_information')->nullable();
            
            // Player Roster Information
            $table->json('roster_players')->nullable(); // Array of player IDs
            $table->json('emergency_contacts')->nullable();
            $table->boolean('medical_forms_complete')->default(false);
            $table->boolean('insurance_verified')->default(false);
            
            // Awards and Recognition
            $table->json('individual_awards')->nullable(); // MVP, Best Player, etc.
            $table->json('team_awards')->nullable(); // Sportsmanship, etc.
            
            $table->timestamps();
            
            // Indexes
            $table->index(['tournament_id', 'status']);
            $table->index(['tournament_id', 'seed']);
            $table->index(['tournament_id', 'group_name']);
            $table->unique(['tournament_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_teams');
    }
};