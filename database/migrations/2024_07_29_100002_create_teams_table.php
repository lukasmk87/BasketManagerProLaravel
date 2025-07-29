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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('club_id')->constrained()->onDelete('cascade');
            
            $table->string('name');
            $table->string('short_name', 10)->nullable(); 
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            
            // Team classification
            $table->enum('gender', ['male', 'female', 'mixed'])->default('mixed');
            $table->enum('age_group', [
                'u8', 'u10', 'u12', 'u14', 'u16', 'u18', 'u20', 
                'senior', 'masters', 'veterans'
            ])->default('senior');
            $table->string('division')->nullable();
            $table->string('league')->nullable();
            
            // Season information
            $table->string('season', 20);
            $table->date('season_start')->nullable();
            $table->date('season_end')->nullable();
            
            // Team colors
            $table->string('primary_color', 7)->nullable();
            $table->string('secondary_color', 7)->nullable();
            $table->string('jersey_home_color', 7)->nullable();
            $table->string('jersey_away_color', 7)->nullable();
            
            // Team settings
            $table->integer('max_players')->default(15);
            $table->integer('min_players')->default(8);
            $table->json('training_schedule')->nullable();
            $table->json('practice_times')->nullable();
            
            // Coach assignments
            $table->foreignId('head_coach_id')->nullable()->constrained('users')->onDelete('set null');
            $table->json('assistant_coaches')->nullable(); // Array of user IDs
            
            // Team statistics
            $table->integer('games_played')->default(0);
            $table->integer('games_won')->default(0);
            $table->integer('games_lost')->default(0);
            $table->integer('games_tied')->default(0);
            $table->integer('points_scored')->default(0);
            $table->integer('points_allowed')->default(0);
            
            // Team preferences
            $table->json('preferences')->nullable();
            $table->json('settings')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_recruiting')->default(false);
            $table->enum('status', ['active', 'inactive', 'suspended', 'disbanded'])->default('active');
            
            // Home venue information
            $table->string('home_venue')->nullable();
            $table->string('home_venue_address')->nullable();
            $table->json('venue_details')->nullable();
            
            // Registration and certification
            $table->string('registration_number')->nullable();
            $table->boolean('is_certified')->default(false);
            $table->timestamp('certified_at')->nullable();
            
            // Emergency contacts
            $table->json('emergency_contacts')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['club_id', 'season']);
            $table->index(['is_active', 'status']);
            $table->index(['gender', 'age_group']);
            $table->index('league');
            $table->index('division');
            $table->index('head_coach_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};