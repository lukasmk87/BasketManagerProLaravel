<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated Stripe Connect and Vouchers Tables Migration
 *
 * Includes: stripe_connect_settings, stripe_connect_transfers, vouchers, voucher_redemptions
 */
return new class extends Migration
{
    public function up(): void
    {
        // Stripe Connect Settings
        Schema::create('stripe_connect_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable();
            $table->decimal('application_fee_percent', 5, 2)->default(2.50);
            $table->decimal('application_fee_fixed', 10, 2)->default(0.00);
            $table->string('fee_currency', 3)->default('EUR');
            $table->enum('payout_schedule', ['daily', 'weekly', 'monthly', 'manual'])->default('daily');
            $table->unsignedTinyInteger('payout_delay_days')->default(7);
            $table->boolean('allow_direct_charges')->default(true);
            $table->boolean('allow_destination_charges')->default(true);
            $table->boolean('require_onboarding_complete')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique('tenant_id');
        });

        // Stripe Connect Transfers
        Schema::create('stripe_connect_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->unsignedBigInteger('club_id');
            $table->string('stripe_payment_intent_id');
            $table->string('stripe_transfer_id')->nullable();
            $table->string('stripe_charge_id')->nullable();
            $table->integer('gross_amount');
            $table->integer('application_fee_amount');
            $table->integer('net_amount');
            $table->string('currency', 3)->default('EUR');
            $table->enum('status', ['pending', 'succeeded', 'failed', 'refunded', 'partially_refunded'])->default('pending');
            $table->string('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('cascade');
            $table->index(['tenant_id', 'status'], 'idx_connect_transfers_tenant_status');
            $table->index('stripe_payment_intent_id', 'idx_connect_transfers_payment_intent');
            $table->index('stripe_transfer_id', 'idx_connect_transfers_transfer');
        });

        // Vouchers
        Schema::create('vouchers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['percent', 'fixed_amount', 'trial_extension']);
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->integer('trial_extension_days')->nullable();
            $table->integer('duration_months')->default(1);
            $table->integer('max_redemptions')->nullable();
            $table->integer('current_redemptions')->default(0);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->json('applicable_plan_ids')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active', 'valid_from', 'valid_until'], 'idx_voucher_search');
        });

        // Voucher Redemptions
        Schema::create('voucher_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('voucher_id')->constrained('vouchers')->cascadeOnDelete();
            $table->unsignedBigInteger('club_id');
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->enum('voucher_type', ['percent', 'fixed_amount', 'trial_extension']);
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->integer('trial_extension_days')->nullable();
            $table->integer('duration_months');
            $table->foreignUuid('applied_to_plan_id')->nullable()->constrained('club_subscription_plans')->nullOnDelete();
            $table->integer('months_applied')->default(0);
            $table->boolean('is_fully_applied')->default(false);
            $table->timestamp('first_applied_at')->nullable();
            $table->timestamp('last_applied_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->decimal('total_discount_amount', 10, 2)->default(0);
            $table->foreignId('redeemed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('club_id')->references('id')->on('clubs')->cascadeOnDelete();
            $table->unique(['voucher_id', 'club_id'], 'unique_voucher_club');
            $table->index(['club_id', 'is_fully_applied']);
            $table->index(['expires_at', 'is_fully_applied']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_redemptions');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('stripe_connect_transfers');
        Schema::dropIfExists('stripe_connect_settings');
    }
};
