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
        Schema::create('player_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            
            // Team-specific player information
            $table->integer('jersey_number')->nullable(); // Can be different per team
            $table->enum('primary_position', ['PG', 'SG', 'SF', 'PF', 'C'])->nullable(); // Position in this team
            $table->json('secondary_positions')->nullable(); // Secondary positions in this team
            
            // Player status in this team
            $table->boolean('is_active')->default(true);
            $table->boolean('is_starter')->default(false);
            $table->boolean('is_captain')->default(false);
            $table->enum('status', ['active', 'inactive', 'injured', 'suspended', 'on_loan'])->default('active');
            
            // Team membership dates
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            
            // Contract/registration information per team
            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();
            $table->string('registration_number')->nullable(); // Team-specific registration
            $table->boolean('is_registered')->default(false);
            $table->timestamp('registered_at')->nullable();
            
            // Performance tracking per team
            $table->integer('games_played')->default(0);
            $table->integer('games_started')->default(0);
            $table->integer('minutes_played')->default(0);
            $table->integer('points_scored')->default(0);
            
            // Notes and metadata
            $table->text('notes')->nullable(); // Coach notes specific to this team
            $table->json('metadata')->nullable(); // Additional team-specific data
            
            $table->timestamps();
            
            // Unique constraint: each player can only be in each team once
            $table->unique(['player_id', 'team_id']);
            
            // Index for performance
            $table->index(['player_id', 'is_active']);
            $table->index(['team_id', 'is_active']);
            $table->index(['team_id', 'jersey_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_team');
    }
};
