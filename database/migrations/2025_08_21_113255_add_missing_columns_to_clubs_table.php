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
        Schema::table('clubs', function (Blueprint $table) {
            // Leadership information
            $table->string('president_name')->nullable();
            $table->string('president_email')->nullable();
            $table->string('vice_president_name')->nullable();
            $table->string('secretary_name')->nullable();
            $table->string('treasurer_name')->nullable();
            $table->string('contact_person_name')->nullable();
            $table->string('contact_person_phone')->nullable();
            $table->string('contact_person_email')->nullable();
            
            
            // Facility information
            $table->boolean('has_indoor_courts')->default(false);
            $table->boolean('has_outdoor_courts')->default(false);
            $table->integer('court_count')->default(1);
            $table->text('equipment_available')->nullable();
            $table->json('training_times')->nullable();
            
            // Program offerings
            $table->boolean('offers_youth_programs')->default(true);
            $table->boolean('offers_adult_programs')->default(true);
            $table->boolean('accepts_new_members')->default(true);
            $table->boolean('requires_approval')->default(false);
            
            // Financial information
            $table->decimal('membership_fee_annual', 10, 2)->nullable();
            $table->decimal('membership_fee_monthly', 10, 2)->nullable();
            
            // Social media
            $table->string('social_media_facebook')->nullable();
            $table->string('social_media_instagram')->nullable();
            $table->string('social_media_twitter')->nullable();
            
            // Legal
            $table->string('privacy_policy_url')->nullable();
            $table->string('terms_of_service_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            // Drop regular columns
            $table->dropColumn([
                'president_name', 'president_email', 'vice_president_name',
                'secretary_name', 'treasurer_name', 'contact_person_name',
                'contact_person_phone', 'contact_person_email',
                'has_indoor_courts', 'has_outdoor_courts', 'court_count',
                'equipment_available', 'training_times', 'offers_youth_programs',
                'offers_adult_programs', 'accepts_new_members', 'requires_approval',
                'membership_fee_annual', 'membership_fee_monthly',
                'social_media_facebook', 'social_media_instagram', 'social_media_twitter',
                'privacy_policy_url', 'terms_of_service_url'
            ]);
        });
    }
};
