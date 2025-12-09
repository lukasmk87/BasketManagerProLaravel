<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated Teams Migration
 *
 * This migration consolidates:
 * - 2024_07_29_100002_create_teams_table.php
 * - 2025_08_05_122942_add_user_id_to_teams_table.php
 * - 2025_08_27_130000_remove_training_schedule_from_teams_table.php (training_schedule NOT included)
 * - 2025_11_19_083451_add_season_id_to_teams_table.php
 * - 2025_11_26_114329_make_club_id_nullable_on_teams_table.php
 * - 2025_11_26_115534_make_season_nullable_on_teams_table.php
 * - 2025_12_08_225805_add_missing_columns_to_teams_and_users_tables.php
 *
 * Note: training_schedule column is NOT included (was added then removed)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->boolean('personal_team')->default(false);
            $table->uuid('uuid')->unique();
            $table->foreignId('club_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('season_id')->nullable()->constrained('seasons')->onDelete('cascade');

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

            // Season information (string kept for backwards compat, nullable for personal teams)
            $table->string('season', 20)->nullable();
            $table->date('season_start')->nullable();
            $table->date('season_end')->nullable();

            // Team colors
            $table->string('primary_color', 7)->nullable();
            $table->string('secondary_color', 7)->nullable();
            $table->string('jersey_home_color', 7)->nullable();
            $table->string('jersey_away_color', 7)->nullable();

            // Team settings (training_schedule removed)
            $table->integer('max_players')->default(15);
            $table->integer('min_players')->default(8);
            $table->json('practice_times')->nullable();

            // Coach assignments
            $table->foreignId('head_coach_id')->nullable()->constrained('users')->onDelete('set null');
            $table->json('assistant_coaches')->nullable();

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
            $table->decimal('win_percentage', 5, 2)->nullable()->default(0);
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
            $table->index(['club_id', 'season_id']);
            $table->index(['is_active', 'status']);
            $table->index(['gender', 'age_group']);
            $table->index('league');
            $table->index('division');
            $table->index('head_coach_id');
            $table->index('user_id');
        });

        // Add tenant foreign key
        Schema::table('teams', function (Blueprint $table) {
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // Club-User relationship (team management)
        Schema::create('club_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['admin', 'trainer', 'manager', 'member'])->default('member');
            $table->boolean('is_primary')->default(false);
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->foreignId('registered_via_invitation_id')->nullable();
            $table->timestamps();

            $table->unique(['club_id', 'user_id']);
            $table->index(['user_id', 'role']);
        });

        // Team-User relationship
        Schema::create('team_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('role')->default('member');
            $table->timestamps();

            $table->unique(['team_id', 'user_id']);
        });

        // Team coaches (dedicated table)
        Schema::create('team_coaches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['head_coach', 'assistant_coach', 'volunteer'])->default('assistant_coach');
            $table->boolean('is_primary')->default(false);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('certifications')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['team_id', 'user_id', 'role']);
            $table->index(['team_id', 'role']);
            $table->index(['user_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_coaches');
        Schema::dropIfExists('team_user');
        Schema::dropIfExists('club_user');
        Schema::dropIfExists('teams');
    }
};
