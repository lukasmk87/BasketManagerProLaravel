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
        Schema::table('users', function (Blueprint $table) {
            // Basketball-specific user fields
            $table->date('date_of_birth')->nullable()->after('email_verified_at');
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable()->after('date_of_birth');
            $table->string('phone')->nullable()->after('gender');
            $table->string('nationality', 2)->default('DE')->after('phone');
            
            // Address information
            $table->string('address_street')->nullable()->after('nationality');
            $table->string('address_city')->nullable()->after('address_street');
            $table->string('address_state')->nullable()->after('address_city');
            $table->string('address_zip')->nullable()->after('address_state');
            $table->string('address_country')->default('DE')->after('address_zip');
            
            // Profile information
            $table->string('avatar_path')->nullable()->after('address_country');
            $table->text('bio')->nullable()->after('avatar_path');
            $table->json('social_links')->nullable()->after('bio');
            
            // Basketball experience and preferences
            $table->json('basketball_experience')->nullable()->after('social_links');
            $table->json('preferred_positions')->nullable()->after('basketball_experience');
            $table->enum('skill_level', ['beginner', 'intermediate', 'advanced', 'professional'])->nullable()->after('preferred_positions');
            
            // User preferences and settings
            $table->json('preferences')->nullable()->after('skill_level');
            $table->json('notification_settings')->nullable()->after('preferences');
            $table->json('privacy_settings')->nullable()->after('notification_settings');
            
            // Localization
            $table->string('language', 5)->default('de')->after('privacy_settings');
            $table->string('timezone')->default('Europe/Berlin')->after('language');
            $table->string('date_format')->default('d.m.Y')->after('timezone');
            $table->string('time_format')->default('H:i')->after('date_format');
            
            // Account status and verification
            $table->boolean('is_active')->default(true)->after('time_format');
            $table->boolean('is_verified')->default(false)->after('is_active');
            $table->timestamp('verified_at')->nullable()->after('is_verified');
            $table->enum('account_type', ['standard', 'premium', 'professional', 'organization'])->default('standard')->after('verified_at');
            
            // Two-Factor Authentication
            $table->boolean('two_factor_enabled')->default(false)->after('account_type');
            $table->text('two_factor_secret')->nullable()->after('two_factor_enabled');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            
            // Login tracking
            $table->timestamp('last_login_at')->nullable()->after('two_factor_confirmed_at');
            $table->string('last_login_ip')->nullable()->after('last_login_at');
            $table->integer('login_count')->default(0)->after('last_login_ip');
            
            // Parent-child relationships (for minors)
            $table->foreignId('parent_id')->nullable()->constrained('users')->onDelete('set null')->after('login_count');
            $table->boolean('is_minor')->default(false)->after('parent_id');
            $table->date('guardian_consent_date')->nullable()->after('is_minor');
            
            // Emergency information
            $table->string('emergency_contact_name')->nullable()->after('guardian_consent_date');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            $table->string('emergency_contact_relationship')->nullable()->after('emergency_contact_phone');
            
            // Medical information
            $table->string('blood_type')->nullable()->after('emergency_contact_relationship');
            $table->json('medical_conditions')->nullable()->after('blood_type');
            $table->json('allergies')->nullable()->after('medical_conditions');
            $table->json('medications')->nullable()->after('allergies');
            $table->boolean('medical_consent')->default(false)->after('medications');
            $table->date('medical_consent_date')->nullable()->after('medical_consent');
            
            // GDPR and compliance
            $table->boolean('gdpr_consent')->default(false)->after('medical_consent_date');
            $table->timestamp('gdpr_consent_at')->nullable()->after('gdpr_consent');
            $table->boolean('marketing_consent')->default(false)->after('gdpr_consent_at');
            $table->timestamp('marketing_consent_at')->nullable()->after('marketing_consent');
            $table->json('consent_history')->nullable()->after('marketing_consent_at');
            
            // Professional information
            $table->string('occupation')->nullable()->after('consent_history');
            $table->string('employer')->nullable()->after('occupation');
            $table->string('education_level')->nullable()->after('employer');
            
            // Basketball-specific role information
            $table->json('coaching_certifications')->nullable()->after('education_level');
            $table->json('referee_certifications')->nullable()->after('coaching_certifications');
            $table->boolean('background_check_completed')->default(false)->after('referee_certifications');
            $table->date('background_check_date')->nullable()->after('background_check_completed');
            
            // Soft deletes
            $table->softDeletes()->after('updated_at');
            
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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'is_verified']);
            $table->dropIndex(['date_of_birth']);
            $table->dropIndex(['phone', 'is_active']);
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['last_login_at']);
            $table->dropIndex(['two_factor_enabled', 'is_active']);
            $table->dropIndex(['account_type']);
            $table->dropIndex(['is_minor', 'parent_id']);
            $table->dropIndex(['background_check_completed']);
            
            $table->dropSoftDeletes();
            $table->dropForeign(['parent_id']);
            
            $table->dropColumn([
                'date_of_birth', 'gender', 'phone', 'nationality',
                'address_street', 'address_city', 'address_state', 'address_zip', 'address_country',
                'avatar_path', 'bio', 'social_links',
                'basketball_experience', 'preferred_positions', 'skill_level',
                'preferences', 'notification_settings', 'privacy_settings',
                'language', 'timezone', 'date_format', 'time_format',
                'is_active', 'is_verified', 'verified_at', 'account_type',
                'two_factor_enabled', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
                'last_login_at', 'last_login_ip', 'login_count',
                'parent_id', 'is_minor', 'guardian_consent_date',
                'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship',
                'blood_type', 'medical_conditions', 'allergies', 'medications', 'medical_consent', 'medical_consent_date',
                'gdpr_consent', 'gdpr_consent_at', 'marketing_consent', 'marketing_consent_at', 'consent_history',
                'occupation', 'employer', 'education_level',
                'coaching_certifications', 'referee_certifications', 'background_check_completed', 'background_check_date'
            ]);
        });
    }
};