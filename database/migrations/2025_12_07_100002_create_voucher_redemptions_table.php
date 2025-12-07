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
        Schema::create('voucher_redemptions', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('voucher_id')
                ->constrained('vouchers')
                ->cascadeOnDelete();
            // clubs uses bigint id, not UUID
            $table->unsignedBigInteger('club_id');
            $table->foreign('club_id')
                ->references('id')
                ->on('clubs')
                ->cascadeOnDelete();
            $table->foreignUuid('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            // Snapshot of voucher at redemption time
            $table->enum('voucher_type', ['percent', 'fixed_amount', 'trial_extension']);
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->integer('trial_extension_days')->nullable();
            $table->integer('duration_months');

            // Applied to which plan
            $table->foreignUuid('applied_to_plan_id')
                ->nullable()
                ->constrained('club_subscription_plans')
                ->nullOnDelete();

            // Tracking
            $table->integer('months_applied')->default(0)
                ->comment('How many billing cycles discount was applied');
            $table->boolean('is_fully_applied')->default(false)
                ->comment('True when all discount months have been used');
            $table->timestamp('first_applied_at')->nullable();
            $table->timestamp('last_applied_at')->nullable();
            $table->timestamp('expires_at')->nullable()
                ->comment('When the discount period ends');

            // Total savings tracking
            $table->decimal('total_discount_amount', 10, 2)->default(0)
                ->comment('Cumulative discount given via this redemption');

            // User who redeemed
            $table->foreignId('redeemed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Indexes
            $table->unique(['voucher_id', 'club_id'], 'unique_voucher_club');
            $table->index(['club_id', 'is_fully_applied']);
            $table->index(['expires_at', 'is_fully_applied']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_redemptions');
    }
};
