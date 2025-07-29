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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('set null');
            
            // Basic player information
            $table->integer('jersey_number')->nullable();
            $table->enum('primary_position', ['PG', 'SG', 'SF', 'PF', 'C'])->nullable();
            $table->json('secondary_positions')->nullable(); // Array of positions
            
            // Physical attributes
            $table->integer('height_cm')->nullable(); // Height in centimeters
            $table->decimal('weight_kg', 5, 2)->nullable(); // Weight in kilograms
            $table->enum('dominant_hand', ['left', 'right', 'ambidextrous'])->default('right');
            $table->string('shoe_size')->nullable();
            
            // Basketball experience
            $table->date('started_playing')->nullable();
            $table->integer('years_experience')->default(0);
            $table->json('previous_teams')->nullable(); // Array of team history
            $table->json('achievements')->nullable(); // Awards, recognitions, etc.
            
            // Skills and ratings (1-10 scale)
            $table->decimal('shooting_rating', 3, 1)->nullable();
            $table->decimal('defense_rating', 3, 1)->nullable();
            $table->decimal('passing_rating', 3, 1)->nullable();
            $table->decimal('rebounding_rating', 3, 1)->nullable();
            $table->decimal('speed_rating', 3, 1)->nullable();
            $table->decimal('overall_rating', 3, 1)->nullable();
            
            // Season statistics
            $table->integer('games_played')->default(0);
            $table->integer('games_started')->default(0);
            $table->integer('minutes_played')->default(0);
            $table->integer('points_scored')->default(0);
            $table->integer('field_goals_made')->default(0);
            $table->integer('field_goals_attempted')->default(0);
            $table->integer('three_pointers_made')->default(0);
            $table->integer('three_pointers_attempted')->default(0);
            $table->integer('free_throws_made')->default(0);
            $table->integer('free_throws_attempted')->default(0);
            $table->integer('rebounds_offensive')->default(0);
            $table->integer('rebounds_defensive')->default(0);
            $table->integer('rebounds_total')->default(0);
            $table->integer('assists')->default(0);
            $table->integer('steals')->default(0);
            $table->integer('blocks')->default(0);
            $table->integer('turnovers')->default(0);
            $table->integer('fouls_personal')->default(0);
            $table->integer('fouls_technical')->default(0);
            
            // Player status
            $table->enum('status', ['active', 'inactive', 'injured', 'suspended', 'retired'])->default('active');
            $table->boolean('is_starter')->default(false);
            $table->boolean('is_captain')->default(false);
            $table->boolean('is_rookie')->default(false);
            
            // Contract and registration
            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();
            $table->string('registration_number')->nullable();
            $table->boolean('is_registered')->default(false);
            $table->timestamp('registered_at')->nullable();
            
            // Medical information
            $table->json('medical_conditions')->nullable();
            $table->json('allergies')->nullable();
            $table->json('medications')->nullable();
            $table->string('blood_type')->nullable();
            $table->date('last_medical_check')->nullable();
            $table->boolean('medical_clearance')->default(false);
            $table->timestamp('medical_clearance_expires')->nullable();
            
            // Emergency medical information
            $table->string('emergency_medical_contact')->nullable();
            $table->string('emergency_medical_phone')->nullable();
            $table->string('preferred_hospital')->nullable();
            $table->text('medical_notes')->nullable();
            
            // Insurance information
            $table->string('insurance_provider')->nullable();
            $table->string('insurance_policy_number')->nullable();
            $table->timestamp('insurance_expires')->nullable();
            
            // Parent/Guardian information (for minors)
            $table->foreignId('parent_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->json('guardian_contacts')->nullable();
            
            // Training and development
            $table->json('training_focus_areas')->nullable();
            $table->json('development_goals')->nullable();
            $table->text('coach_notes')->nullable();
            
            // Player preferences
            $table->json('preferences')->nullable();
            $table->json('dietary_restrictions')->nullable();
            
            // Academic information (for student athletes)
            $table->string('school_name')->nullable();
            $table->string('grade_level')->nullable();
            $table->decimal('gpa', 3, 2)->nullable();
            $table->boolean('academic_eligibility')->default(true);
            
            // Social media and contact preferences
            $table->json('social_media')->nullable();
            $table->boolean('allow_photos')->default(true);
            $table->boolean('allow_media_interviews')->default(false);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['team_id', 'status']);
            $table->index(['jersey_number', 'team_id']);
            $table->index('primary_position');
            $table->index(['is_starter', 'is_captain']);
            $table->index('status');
            $table->index('parent_user_id');
            $table->index(['contract_start', 'contract_end']);
            $table->index('medical_clearance');
            
            // Unique constraints
            $table->unique(['team_id', 'jersey_number'], 'unique_jersey_per_team');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};