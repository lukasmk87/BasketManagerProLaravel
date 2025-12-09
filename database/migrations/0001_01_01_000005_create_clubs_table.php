<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated Clubs Migration
 *
 * This migration consolidates:
 * - 2024_07_29_100001_create_clubs_table.php
 * - 2025_08_18_102325_remove_league_division_from_clubs_table.php (removed columns not included)
 * - 2025_08_21_113255_add_missing_columns_to_clubs_table.php
 * - 2025_10_14_130122_add_club_subscription_plan_id_to_clubs_table.php
 * - 2025_10_14_130200_add_tenant_id_to_clubs_table.php
 * - 2025_10_27_163931_add_stripe_fields_to_clubs_table.php
 * - 2025_10_28_170300_add_analytics_fields_to_clubs_table.php
 * - 2025_12_02_192822_add_payment_method_type_to_clubs_table.php
 *
 * Note: league, division, season columns are NOT included (they were added then removed)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clubs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('club_subscription_plan_id')->nullable()->constrained('club_subscription_plans')->onDelete('set null');
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

            // Basketball specific (league, division, season removed)
            $table->json('facilities')->nullable();

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

            // Leadership information (from add_missing_columns)
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

            // Financial information (additional)
            $table->decimal('membership_fee_annual', 10, 2)->nullable();
            $table->decimal('membership_fee_monthly', 10, 2)->nullable();

            // Social media (individual)
            $table->string('social_media_facebook')->nullable();
            $table->string('social_media_instagram')->nullable();
            $table->string('social_media_twitter')->nullable();

            // Legal
            $table->string('privacy_policy_url')->nullable();
            $table->string('terms_of_service_url')->nullable();

            // Stripe Integration
            $table->string('stripe_customer_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();
            $table->enum('subscription_status', [
                'active', 'trial', 'past_due', 'canceled',
                'incomplete', 'incomplete_expired', 'trialing', 'unpaid'
            ])->default('incomplete');
            $table->timestamp('subscription_started_at')->nullable();
            $table->timestamp('subscription_trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->timestamp('subscription_current_period_start')->nullable();
            $table->timestamp('subscription_current_period_end')->nullable();
            $table->string('billing_email')->nullable();
            $table->json('billing_address')->nullable();
            $table->string('invoice_billing_name')->nullable();
            $table->string('invoice_vat_number')->nullable();
            $table->string('payment_method_id')->nullable();
            $table->enum('payment_method_type', ['stripe', 'invoice'])->default('stripe');

            // Analytics fields
            $table->decimal('lifetime_revenue', 10, 2)->default(0);
            $table->date('last_billing_date')->nullable();
            $table->decimal('mrr', 8, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['is_active', 'is_verified']);
            $table->index('founded_at');
            $table->index('club_subscription_plan_id', 'idx_subscription_plan');
            $table->index(['tenant_id', 'is_active'], 'idx_clubs_tenant_active');
            $table->index('stripe_customer_id', 'idx_clubs_stripe_customer');
            $table->index('stripe_subscription_id', 'idx_clubs_stripe_subscription');
            $table->index('subscription_status', 'idx_clubs_subscription_status');
            $table->index('mrr', 'idx_clubs_mrr');
            $table->index('last_billing_date', 'idx_clubs_last_billing');
            $table->index(['tenant_id', 'subscription_status'], 'idx_clubs_tenant_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clubs');
    }
};
