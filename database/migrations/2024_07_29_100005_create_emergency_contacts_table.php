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
        Schema::create('emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Contact information
            $table->string('name');
            $table->string('relationship'); // Parent, Spouse, Guardian, etc.
            $table->string('primary_phone');
            $table->string('secondary_phone')->nullable();
            $table->string('email')->nullable();
            
            // Address information
            $table->string('address_street')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_state')->nullable();
            $table->string('address_zip')->nullable();
            $table->string('address_country')->default('DE');
            
            // Contact preferences
            $table->enum('preferred_contact_method', ['phone', 'sms', 'email'])->default('phone');
            $table->string('language', 5)->default('de');
            
            // Priority and status
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('priority_order')->default(1); // 1 = highest priority
            
            // Availability information
            $table->json('availability_schedule')->nullable(); // When they can be reached
            $table->string('work_phone')->nullable();
            $table->string('work_hours')->nullable();
            
            // Medical authorization
            $table->boolean('can_authorize_medical_treatment')->default(false);
            $table->boolean('has_medical_power_of_attorney')->default(false);
            $table->text('medical_authorization_notes')->nullable();
            
            // Legal information (for minors)
            $table->boolean('is_legal_guardian')->default(false);
            $table->boolean('can_make_decisions')->default(false);
            $table->string('legal_relationship')->nullable();
            
            // Pickup authorization (for minors)
            $table->boolean('can_pickup_player')->default(false);
            $table->text('pickup_notes')->nullable();
            
            // QR Code information for emergency access
            $table->string('qr_code_token')->unique()->nullable();
            $table->timestamp('qr_code_generated_at')->nullable();
            $table->timestamp('qr_code_expires_at')->nullable();
            $table->boolean('qr_code_active')->default(false);
            $table->integer('qr_code_access_count')->default(0);
            $table->timestamp('qr_code_last_accessed')->nullable();
            
            // Emergency instructions
            $table->text('emergency_instructions')->nullable();
            $table->text('medical_notes')->nullable();
            $table->json('special_considerations')->nullable();
            
            // Contact verification
            $table->boolean('phone_verified')->default(false);
            $table->boolean('email_verified')->default(false);
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamp('verification_sent_at')->nullable();
            
            // Usage tracking
            $table->integer('contact_attempts')->default(0);
            $table->timestamp('last_contacted_at')->nullable();
            $table->json('contact_log')->nullable(); // Log of when/how they were contacted
            
            // Insurance information
            $table->string('insurance_provider')->nullable();
            $table->string('insurance_policy_number')->nullable();
            $table->string('insurance_group_number')->nullable();
            
            // Doctor information
            $table->string('family_doctor_name')->nullable();
            $table->string('family_doctor_phone')->nullable();
            $table->string('pediatrician_name')->nullable();
            $table->string('pediatrician_phone')->nullable();
            
            // Additional emergency contacts (relatives, friends)
            $table->json('additional_contacts')->nullable();
            
            // Consent and permissions
            $table->boolean('consent_to_contact')->default(true);
            $table->boolean('consent_to_share_medical_info')->default(false);
            $table->timestamp('consent_given_at')->nullable();
            $table->timestamp('consent_expires_at')->nullable();
            
            // GDPR compliance
            $table->boolean('gdpr_consent')->default(false);
            $table->timestamp('gdpr_consent_at')->nullable();
            $table->json('data_processing_consent')->nullable();
            
            // System fields
            $table->json('metadata')->nullable(); // Additional flexible data
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['user_id', 'is_primary']);
            $table->index(['user_id', 'priority_order']);
            $table->index('is_active');
            $table->index('qr_code_token');
            $table->index(['qr_code_active', 'qr_code_expires_at']);
            $table->index('relationship');
            $table->index(['phone_verified', 'email_verified']);
            $table->index('can_authorize_medical_treatment');
            $table->index('is_legal_guardian');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emergency_contacts');
    }
};