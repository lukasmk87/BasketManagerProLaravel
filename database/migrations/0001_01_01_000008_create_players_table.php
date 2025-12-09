<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated Players Migration
 *
 * This migration consolidates:
 * - 2024_07_29_100003_create_players_table.php
 * - 2024_07_29_100005_create_emergency_contacts_table.php
 * - 2025_08_15_165746_create_player_team_table.php
 * - 2025_08_15_165921_remove_team_id_from_players_table.php (columns removed, not included)
 * - 2025_10_20_160514_create_player_registration_invitations_table.php
 * - 2025_10_20_160552_add_pending_assignment_to_players_table.php
 * - 2025_12_08_100001_create_player_absences_table.php
 *
 * Note: team_id, jersey_number, primary_position, secondary_positions, is_starter, is_captain,
 *       contract_*, registration_*, games_played, games_started, minutes_played, points_scored
 *       are NOT in players table - they are in player_team pivot table
 */
return new class extends Migration
{
    public function up(): void
    {
        // Player registration invitations (must come before players for FK)
        Schema::create('player_registration_invitations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('invitation_token', 32)->unique();
            $table->foreignId('club_id')->constrained('clubs')->onDelete('cascade');
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('target_team_id')->nullable()->constrained('teams')->onDelete('set null');
            $table->string('qr_code_path')->nullable();
            $table->json('qr_code_metadata')->nullable();
            $table->timestamp('expires_at');
            $table->unsignedInteger('max_registrations')->default(50);
            $table->unsignedInteger('current_registrations')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'expires_at'], 'idx_player_reg_active_expires');
        });

        // Players table (without team-specific columns that are now in pivot)
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Physical attributes
            $table->integer('height_cm')->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->enum('dominant_hand', ['left', 'right', 'ambidextrous'])->default('right');
            $table->string('shoe_size')->nullable();

            // Basketball experience
            $table->date('started_playing')->nullable();
            $table->integer('years_experience')->default(0);
            $table->json('previous_teams')->nullable();
            $table->json('achievements')->nullable();

            // Skills and ratings (1-10 scale)
            $table->decimal('shooting_rating', 3, 1)->nullable();
            $table->decimal('defense_rating', 3, 1)->nullable();
            $table->decimal('passing_rating', 3, 1)->nullable();
            $table->decimal('rebounding_rating', 3, 1)->nullable();
            $table->decimal('speed_rating', 3, 1)->nullable();
            $table->decimal('overall_rating', 3, 1)->nullable();

            // Statistics (aggregate - detailed stats in player_team)
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
            $table->boolean('pending_team_assignment')->default(false);
            $table->foreignId('registered_via_invitation_id')->nullable()
                ->constrained('player_registration_invitations')->onDelete('set null');
            $table->timestamp('registration_completed_at')->nullable();
            $table->boolean('is_rookie')->default(false);

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
            $table->index('status');
            $table->index('parent_user_id');
            $table->index('medical_clearance');
            $table->index(['pending_team_assignment', 'created_at'], 'idx_pending_players');

            // FK to tenants
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // Player-Team pivot table (contains team-specific player data)
        Schema::create('player_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->constrained()->onDelete('cascade');

            // Team-specific player information
            $table->integer('jersey_number')->nullable();
            $table->enum('primary_position', ['PG', 'SG', 'SF', 'PF', 'C'])->nullable();
            $table->json('secondary_positions')->nullable();

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
            $table->string('registration_number')->nullable();
            $table->boolean('is_registered')->default(false);
            $table->timestamp('registered_at')->nullable();

            // Performance tracking per team
            $table->integer('games_played')->default(0);
            $table->integer('games_started')->default(0);
            $table->integer('minutes_played')->default(0);
            $table->integer('points_scored')->default(0);

            // Notes and metadata
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique(['player_id', 'team_id']);
            $table->index(['player_id', 'is_active']);
            $table->index(['team_id', 'is_active']);
            $table->index(['team_id', 'jersey_number']);
        });

        // Emergency contacts table
        Schema::create('emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->morphs('contactable');
            $table->string('name');
            $table->string('relationship');
            $table->string('phone');
            $table->string('alternate_phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->json('additional_info')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['contactable_type', 'contactable_id', 'is_primary'], 'idx_ec_contactable_primary');
        });

        // Player absences table
        Schema::create('player_absences', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['vacation', 'illness', 'injury', 'personal', 'other'])->default('other');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('notes')->nullable();
            $table->string('reason', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'player_id']);
            $table->index(['player_id', 'start_date', 'end_date']);
            $table->index(['start_date', 'end_date']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_absences');
        Schema::dropIfExists('emergency_contacts');
        Schema::dropIfExists('player_team');
        Schema::dropIfExists('players');
        Schema::dropIfExists('player_registration_invitations');
    }
};
