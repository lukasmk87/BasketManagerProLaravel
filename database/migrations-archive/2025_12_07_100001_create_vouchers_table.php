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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Scoping: NULL = system-wide voucher (Super Admin)
            $table->foreignUuid('tenant_id')
                ->nullable()
                ->constrained('tenants')
                ->cascadeOnDelete();

            // Identification
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();

            // Voucher Type
            $table->enum('type', ['percent', 'fixed_amount', 'trial_extension']);

            // Discount Values (depending on type)
            $table->decimal('discount_percent', 5, 2)->nullable()
                ->comment('For percent type: 10.00 = 10%');
            $table->decimal('discount_amount', 10, 2)->nullable()
                ->comment('For fixed_amount type: Monthly discount in EUR');
            $table->integer('trial_extension_days')->nullable()
                ->comment('For trial_extension type: Extra days to add');

            // Duration & Limits
            $table->integer('duration_months')->default(1)
                ->comment('How many billing cycles the discount applies');
            $table->integer('max_redemptions')->nullable()
                ->comment('NULL = unlimited redemptions');
            $table->integer('current_redemptions')->default(0);

            // Validity Period
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();

            // Plan Restrictions (JSON array of plan IDs)
            $table->json('applicable_plan_ids')->nullable()
                ->comment('NULL = all plans, otherwise array of ClubSubscriptionPlan UUIDs');

            // Status
            $table->boolean('is_active')->default(true);

            // Metadata
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'is_active', 'valid_from', 'valid_until'], 'idx_voucher_search');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
