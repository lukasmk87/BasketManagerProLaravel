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
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();

            // Scoping
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('club_id')->nullable()->index();

            // Feature identification
            $table->string('feature_key')->index(); // e.g., 'club_subscriptions_enabled'
            $table->string('feature_name')->nullable(); // Human-readable name

            // Flag status
            $table->boolean('is_enabled')->default(false)->index();
            $table->boolean('is_beta_opt_in')->default(false);

            // Rollout configuration (optional overrides)
            $table->integer('rollout_percentage')->nullable(); // 0-100, overrides config
            $table->json('rollout_rules')->nullable(); // Custom rollout rules (for future use)

            // Metadata
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Additional configuration
            $table->timestamp('enabled_at')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->unsignedBigInteger('enabled_by')->nullable(); // User ID who enabled it
            $table->unsignedBigInteger('disabled_by')->nullable(); // User ID who disabled it

            $table->timestamps();

            // Foreign keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            $table->foreign('club_id')
                ->references('id')
                ->on('clubs')
                ->onDelete('cascade');

            // Unique constraint: one flag per tenant/club/feature combination
            $table->unique(['tenant_id', 'club_id', 'feature_key'], 'feature_flags_unique');

            // Composite indexes for common queries
            $table->index(['feature_key', 'is_enabled']);
            $table->index(['tenant_id', 'feature_key']);
            $table->index(['club_id', 'feature_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_flags');
    }
};
