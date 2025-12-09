<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated Subscription Plans Migration
 *
 * This migration consolidates:
 * - 2025_10_13_102021_create_subscription_plans_table.php
 * - 2025_10_13_150958_add_deleted_at_to_subscription_plans_table.php
 * - 2025_10_14_130034_create_club_subscription_plans_table.php
 * - 2025_10_27_164007_add_stripe_fields_to_club_subscription_plans_table.php
 * - 2025_12_06_200556_add_featured_to_club_subscription_plans_table.php
 */
return new class extends Migration
{
    public function up(): void
    {
        // System-wide subscription plans (for tenants)
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->string('billing_period')->default('monthly');
            $table->string('stripe_price_id')->nullable();
            $table->string('stripe_product_id')->nullable();
            $table->integer('trial_days')->default(14);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_custom')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->json('features')->nullable();
            $table->json('limits')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index('is_active');
            $table->index('sort_order');
        });

        // Add foreign key to tenants
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreign('subscription_plan_id')
                ->references('id')
                ->on('subscription_plans')
                ->onDelete('set null');
        });

        // Club-level subscription plans (per tenant)
        Schema::create('club_subscription_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');

            // Plan details
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->enum('billing_interval', ['monthly', 'yearly'])->default('monthly');

            // Features & Limits
            $table->json('features')->nullable();
            $table->json('limits')->nullable();

            // Metadata
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->string('color', 7)->nullable();
            $table->string('icon', 50)->nullable();

            // Stripe Integration
            $table->string('stripe_product_id')->nullable();
            $table->string('stripe_price_id_monthly')->nullable();
            $table->string('stripe_price_id_yearly')->nullable();
            $table->boolean('is_stripe_synced')->default(false);
            $table->timestamp('last_stripe_sync_at')->nullable();
            $table->integer('trial_period_days')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique(['tenant_id', 'slug'], 'unique_tenant_slug');
            $table->index(['tenant_id', 'is_active'], 'idx_tenant_active');
            $table->index('is_default', 'idx_is_default');
            $table->index(['tenant_id', 'is_featured', 'is_active'], 'idx_tenant_featured_active');
            $table->index('stripe_product_id', 'idx_club_plans_stripe_product');
            $table->index('is_stripe_synced', 'idx_club_plans_stripe_synced');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_subscription_plans');

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['subscription_plan_id']);
        });

        Schema::dropIfExists('subscription_plans');
    }
};
