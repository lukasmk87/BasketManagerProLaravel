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
        Schema::create('club_subscription_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Tenant relationship
            $table->foreignUuid('tenant_id')
                ->constrained('tenants')
                ->onDelete('cascade');

            // Plan details
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->enum('billing_interval', ['monthly', 'yearly'])->default('monthly');

            // Features & Limits (JSON)
            $table->json('features')->nullable()
                ->comment('Array of feature slugs');
            $table->json('limits')->nullable()
                ->comment('Object with metric => limit');

            // Metadata
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false)
                ->comment('Default plan for new clubs');
            $table->integer('sort_order')->default(0);
            $table->string('color', 7)->nullable()
                ->comment('Hex color for UI');
            $table->string('icon', 50)->nullable()
                ->comment('Icon identifier');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique(['tenant_id', 'slug'], 'unique_tenant_slug');
            $table->index(['tenant_id', 'is_active'], 'idx_tenant_active');
            $table->index('is_default', 'idx_is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_subscription_plans');
    }
};
