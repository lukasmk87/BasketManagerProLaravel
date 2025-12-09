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
        Schema::create('clubs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('short_name', 10)->nullable();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            
            // Address information
            $table->string('address_street')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_state')->nullable();
            $table->string('address_zip')->nullable();
            $table->string('address_country')->default('DE');
            
            // Colors and branding
            $table->string('primary_color', 7)->default('#007bff');
            $table->string('secondary_color', 7)->default('#6c757d');
            $table->string('accent_color', 7)->nullable();
            
            // Settings
            $table->json('settings')->nullable();
            $table->json('preferences')->nullable();
            
            // Status and metadata
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('founded_at')->nullable();
            
            // Multi-language support
            $table->string('default_language', 5)->default('de');
            $table->json('supported_languages')->default('["de", "en"]');
            
            // Basketball specific
            $table->string('league')->nullable();
            $table->string('division')->nullable();
            $table->string('season')->nullable();
            $table->json('facilities')->nullable(); // Court information, etc.
            
            // Social media links
            $table->json('social_links')->nullable();
            
            // Financial information
            $table->decimal('membership_fee', 8, 2)->nullable();
            $table->string('currency', 3)->default('EUR');
            
            // Emergency contacts
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_email')->nullable();
            
            // GDPR and compliance
            $table->timestamp('privacy_policy_updated_at')->nullable();
            $table->timestamp('terms_updated_at')->nullable();
            $table->boolean('gdpr_compliant')->default(false);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['is_active', 'is_verified']);
            $table->index('league');
            $table->index('division');
            $table->index('season');
            $table->index('founded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clubs');
    }
};