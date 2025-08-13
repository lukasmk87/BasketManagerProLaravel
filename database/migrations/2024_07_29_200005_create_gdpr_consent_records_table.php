<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gdpr_consent_records', function (Blueprint $table) {
            $table->id();
            
            // Consent Subject
            $table->morphs('consentable'); // Player, User, etc.
            $table->foreignId('given_by_user_id')->nullable()->constrained('users'); // Guardian for minors
            
            // Consent Details
            $table->string('consent_type'); // emergency_contacts, statistics_sharing, etc.
            $table->text('consent_text'); // Exact consent text shown
            $table->string('consent_version'); // Version of terms
            $table->boolean('consent_given')->default(false);
            $table->timestamp('consent_given_at')->nullable();
            $table->timestamp('consent_withdrawn_at')->nullable();
            
            // Context
            $table->string('collection_method'); // website, paper_form, verbal, etc.
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('form_data')->nullable(); // Additional context
            
            // Processing Purposes
            $table->json('purposes'); // What the consent covers
            $table->json('data_categories'); // What data types
            $table->timestamp('expires_at')->nullable(); // If consent has expiry
            
            // Child Protection (under 16 years)
            $table->boolean('is_minor')->default(false);
            $table->date('subject_birth_date')->nullable();
            $table->string('guardian_relationship')->nullable();
            $table->boolean('parental_consent_verified')->default(false);
            
            // Evidence
            $table->json('evidence_files')->nullable(); // Signed forms, etc.
            $table->text('additional_notes')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->enum('status', ['active', 'withdrawn', 'expired', 'superseded'])->default('active');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['consentable_type', 'consentable_id']);
            $table->index(['consent_type', 'consent_given']);
            $table->index(['is_minor', 'parental_consent_verified']);
            $table->index('expires_at');
            $table->index(['status', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gdpr_consent_records');
    }
};