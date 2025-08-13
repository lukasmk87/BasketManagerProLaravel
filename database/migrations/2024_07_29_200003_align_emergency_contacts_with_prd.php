<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emergency_contacts', function (Blueprint $table) {
            // Add player_id column to link to players instead of users directly
            $table->foreignId('player_id')->nullable()->after('user_id')->constrained('players')->onDelete('cascade');
            
            // Add PRD-specified fields that are missing
            $table->string('contact_name')->nullable()->after('name'); // Will replace 'name'
            $table->string('phone_number')->nullable()->after('primary_phone'); // Will replace 'primary_phone'
            $table->string('secondary_phone')->nullable()->after('phone_number');
            
            // Add encrypted fields support markers (Laravel handles encryption via casts)
            $table->boolean('encrypted_fields')->default(true)->after('notes');
            
            // Add missing PRD fields
            $table->boolean('has_medical_training')->default(false)->after('can_authorize_medical_treatment');
            $table->boolean('available_24_7')->default(false)->after('availability_schedule');
            $table->string('alternate_contact_info')->nullable()->after('work_phone');
            
            // Add location fields for distance calculations
            $table->decimal('latitude', 10, 8)->nullable()->after('address_country');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->integer('distance_to_venue_km')->nullable()->after('longitude');
            
            // Add authorization fields
            $table->boolean('emergency_pickup_authorized')->default(false)->after('can_pickup_player');
            $table->boolean('medical_decisions_authorized')->default(false)->after('can_authorize_medical_treatment');
            $table->text('authorization_notes')->nullable()->after('medical_decisions_authorized');
            $table->date('authorization_expires_at')->nullable()->after('authorization_notes');
            
            // Add GDPR compliance fields that match PRD
            $table->foreignId('consent_given_by_user_id')->nullable()->after('gdpr_consent_at')->constrained('users');
            $table->text('consent_details')->nullable()->after('consent_given_by_user_id');
            $table->timestamp('last_verified_at')->nullable()->after('consent_details');
            
            // Add indexes for new fields
            $table->index(['player_id', 'is_primary']);
            $table->index(['player_id', 'priority_order']);
            $table->index(['consent_given', 'is_active']);
        });

        // Data migration: Copy name to contact_name and primary_phone to phone_number
        DB::statement('UPDATE emergency_contacts SET contact_name = name WHERE contact_name IS NULL');
        DB::statement('UPDATE emergency_contacts SET phone_number = primary_phone WHERE phone_number IS NULL');
        
        // Make required fields not nullable after data migration
        Schema::table('emergency_contacts', function (Blueprint $table) {
            $table->string('contact_name')->nullable(false)->change();
            $table->string('phone_number')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('emergency_contacts', function (Blueprint $table) {
            $table->dropForeign(['player_id']);
            $table->dropColumn([
                'player_id',
                'contact_name',
                'phone_number',
                'encrypted_fields',
                'has_medical_training',
                'available_24_7',
                'alternate_contact_info',
                'latitude',
                'longitude',
                'distance_to_venue_km',
                'emergency_pickup_authorized',
                'medical_decisions_authorized',
                'authorization_notes',
                'authorization_expires_at',
                'consent_given_by_user_id',
                'consent_details',
                'last_verified_at'
            ]);
            
            $table->dropIndex(['player_id', 'is_primary']);
            $table->dropIndex(['player_id', 'priority_order']);
            $table->dropIndex(['consent_given', 'is_active']);
        });
    }
};