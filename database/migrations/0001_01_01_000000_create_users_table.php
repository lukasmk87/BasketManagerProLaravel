<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated Users Migration
 *
 * This migration consolidates the following original migrations:
 * - 0001_01_01_000000_create_users_table.php
 * - 2024_07_29_100008_extend_users_table.php
 * - 2025_08_29_131156_add_player_profile_active_to_users_table.php
 * - 2025_10_20_160621_add_account_status_to_users_table.php
 * - 2025_10_28_153059_add_locale_to_users_table.php
 * - 2025_11_26_121404_add_onboarding_completed_at_to_users_table.php
 * - 2025_12_08_225805_add_missing_columns_to_teams_and_users_tables.php (users part)
 * - 2025_12_09_014209_add_profile_completion_fields_to_users_table.php
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->nullable()->index();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('locale', 5)->nullable()->default('de');
            $table->timestamp('email_verified_at')->nullable();
            $table->enum('account_status', ['pending', 'active', 'suspended'])->default('active');
            $table->boolean('pending_verification')->default(false);
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->boolean('needs_profile_completion')->default(false);
            $table->timestamp('profile_completed_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->foreignId('current_team_id')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();

            // Basketball-specific user fields
            $table->date('date_of_birth')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable();
            $table->string('phone')->nullable();
            $table->string('nationality', 2)->default('DE');

            // Address information
            $table->string('address_street')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_state')->nullable();
            $table->string('address_zip')->nullable();
            $table->string('address_country')->default('DE');

            // Profile information
            $table->string('avatar_path')->nullable();
            $table->text('bio')->nullable();
            $table->json('social_links')->nullable();

            // Basketball experience and preferences
            $table->json('basketball_experience')->nullable();
            $table->json('preferred_positions')->nullable();
            $table->enum('skill_level', ['beginner', 'intermediate', 'advanced', 'professional'])->nullable();

            // User preferences and settings
            $table->json('preferences')->nullable();
            $table->json('notification_settings')->nullable();
            $table->json('privacy_settings')->nullable();

            // Localization
            $table->string('language', 5)->default('de');
            $table->string('timezone')->default('Europe/Berlin');
            $table->string('date_format')->default('d.m.Y');
            $table->string('time_format')->default('H:i');

            // Account status and verification
            $table->boolean('is_active')->default(true);
            $table->boolean('player_profile_active')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->enum('account_type', ['standard', 'premium', 'professional', 'organization'])->default('standard');

            // Two-Factor Authentication
            $table->boolean('two_factor_enabled')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();

            // Login tracking
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->integer('login_count')->default(0);

            // Parent-child relationships (for minors)
            $table->foreignId('parent_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_minor')->default(false);
            $table->date('guardian_consent_date')->nullable();

            // Emergency information
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relationship')->nullable();

            // Medical information
            $table->string('blood_type')->nullable();
            $table->json('medical_conditions')->nullable();
            $table->json('allergies')->nullable();
            $table->json('medications')->nullable();
            $table->boolean('medical_consent')->default(false);
            $table->date('medical_consent_date')->nullable();

            // GDPR and compliance
            $table->boolean('gdpr_consent')->default(false);
            $table->timestamp('gdpr_consent_at')->nullable();
            $table->boolean('marketing_consent')->default(false);
            $table->timestamp('marketing_consent_at')->nullable();
            $table->json('consent_history')->nullable();

            // Professional information
            $table->string('occupation')->nullable();
            $table->string('employer')->nullable();
            $table->string('education_level')->nullable();

            // Basketball-specific role information
            $table->json('coaching_certifications')->nullable();
            $table->json('referee_certifications')->nullable();
            $table->boolean('background_check_completed')->default(false);
            $table->date('background_check_date')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['is_active', 'is_verified']);
            $table->index('date_of_birth');
            $table->index(['phone', 'is_active']);
            $table->index('parent_id');
            $table->index('last_login_at');
            $table->index(['two_factor_enabled', 'is_active']);
            $table->index('account_type');
            $table->index(['is_minor', 'parent_id']);
            $table->index('background_check_completed');
            $table->index('account_status', 'idx_account_status');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
