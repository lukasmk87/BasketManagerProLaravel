<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated Tenants Migration
 *
 * This migration consolidates:
 * - 2025_08_13_093209_create_tenants_table.php
 * - 2025_08_13_131517_add_payment_status_to_tenants_table.php
 * - 2025_10_13_102110_add_subscription_plan_id_to_tenants_table.php
 * - 2025_11_03_152940_add_app_name_to_tenants_table.php
 * - 2025_12_05_100005_add_billing_contact_fields_to_tenants.php
 * - 2025_12_07_013624_add_stripe_connect_fields_to_tenants_table.php
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('app_name')->nullable();
            $table->string('slug')->unique()->index();
            $table->string('domain')->nullable()->unique()->index();
            $table->string('subdomain')->nullable()->unique()->index();

            // Contact & Billing
            $table->string('billing_email');
            $table->string('billing_name')->nullable();
            $table->text('billing_address')->nullable();
            $table->string('billing_contact_name')->nullable();
            $table->string('billing_contact_email')->nullable();
            $table->enum('preferred_payment_method', ['stripe', 'bank_transfer'])->default('stripe');
            $table->boolean('pays_via_invoice')->default(false);
            $table->string('vat_number')->nullable();
            $table->string('country_code', 2)->default('DE');
            $table->string('timezone')->default('Europe/Berlin');
            $table->string('locale')->default('de');
            $table->string('currency', 3)->default('EUR');

            // Subscription & Limits
            $table->string('subscription_tier')->default('free');
            $table->unsignedBigInteger('subscription_plan_id')->nullable();
            $table->string('subscription_status')->nullable();
            $table->enum('payment_status', ['paid', 'failed', 'pending'])->default('pending');
            $table->timestamp('payment_failed_at')->nullable();
            $table->timestamp('last_payment_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_suspended')->default(false);
            $table->text('suspension_reason')->nullable();

            // Stripe Integration
            $table->string('stripe_id')->nullable();
            $table->string('stripe_connect_account_id')->nullable();
            $table->enum('stripe_connect_status', [
                'not_connected', 'pending', 'active', 'restricted', 'rejected'
            ])->default('not_connected');
            $table->boolean('stripe_connect_charges_enabled')->default(false);
            $table->boolean('stripe_connect_payouts_enabled')->default(false);
            $table->boolean('stripe_connect_details_submitted')->default(false);
            $table->timestamp('stripe_connect_connected_at')->nullable();
            $table->timestamp('stripe_connect_last_webhook_at')->nullable();

            // Features & Settings
            $table->json('features')->nullable();
            $table->text('settings')->nullable();
            $table->json('branding')->nullable();
            $table->text('security_settings')->nullable();

            // Limits & Usage
            $table->integer('max_users')->default(10);
            $table->integer('max_teams')->default(5);
            $table->integer('max_storage_gb')->default(10);
            $table->integer('max_api_calls_per_hour')->default(1000);
            $table->integer('current_users_count')->default(0);
            $table->integer('current_teams_count')->default(0);
            $table->decimal('current_storage_gb', 10, 2)->default(0);

            // Database & Technical
            $table->string('database_name')->nullable();
            $table->string('database_host')->nullable();
            $table->string('database_port')->nullable();
            $table->text('database_password')->nullable();
            $table->string('schema_name')->nullable();

            // API & Integration
            $table->string('api_key')->nullable()->unique();
            $table->text('api_secret')->nullable();
            $table->text('webhook_url')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->json('allowed_domains')->nullable();
            $table->json('blocked_ips')->nullable();

            // Analytics & Tracking
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->integer('total_logins')->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->decimal('monthly_recurring_revenue', 10, 2)->default(0);

            // Compliance & Legal
            $table->boolean('gdpr_accepted')->default(false);
            $table->timestamp('gdpr_accepted_at')->nullable();
            $table->boolean('terms_accepted')->default(false);
            $table->timestamp('terms_accepted_at')->nullable();
            $table->json('data_retention_policy')->nullable();
            $table->boolean('data_processing_agreement_signed')->default(false);

            // Meta
            $table->uuid('created_by')->nullable();
            $table->uuid('onboarded_by')->nullable();
            $table->timestamp('onboarded_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('subscription_tier');
            $table->index('subscription_status');
            $table->index('payment_status');
            $table->index('created_at');
            $table->index(['is_active', 'subscription_tier']);
            $table->index(['domain', 'is_active']);
            $table->index(['subdomain', 'is_active']);
            $table->index('stripe_connect_account_id', 'idx_tenants_connect_account');
            $table->index('stripe_connect_status', 'idx_tenants_connect_status');
        });

        // Add foreign key for users.tenant_id
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
        });

        Schema::dropIfExists('tenants');
    }
};
