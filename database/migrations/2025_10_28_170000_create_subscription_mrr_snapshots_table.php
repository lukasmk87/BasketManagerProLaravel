<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Create subscription_mrr_snapshots table for storing daily/monthly MRR snapshots.
     * This enables historical MRR tracking and trend analysis without expensive recalculations.
     */
    public function up(): void
    {
        Schema::create('subscription_mrr_snapshots', function (Blueprint $table) {
            $table->id();
            $table->char('tenant_id', 36);
            $table->date('snapshot_date');
            $table->enum('snapshot_type', ['daily', 'monthly'])->default('daily');

            // Club-level MRR (from club subscriptions)
            $table->decimal('club_mrr', 10, 2)->default(0)->comment('Total club subscriptions MRR');
            $table->integer('club_count')->default(0)->comment('Active club subscriptions count');

            // Tenant-level MRR (from tenant own subscription via Cashier)
            $table->decimal('tenant_mrr', 10, 2)->default(0)->comment('Tenant own subscription MRR');

            // Combined totals
            $table->decimal('total_mrr', 10, 2)->default(0)->comment('Combined MRR (club + tenant)');

            // Growth metrics (compared to previous snapshot)
            $table->decimal('mrr_growth', 10, 2)->default(0)->comment('Change from previous snapshot');
            $table->decimal('mrr_growth_rate', 5, 2)->default(0)->comment('Percentage growth');

            // MRR breakdown (following SaaS metrics best practices)
            $table->decimal('new_business_mrr', 10, 2)->default(0)->comment('MRR from new subscriptions');
            $table->decimal('expansion_mrr', 10, 2)->default(0)->comment('MRR from upgrades');
            $table->decimal('contraction_mrr', 10, 2)->default(0)->comment('MRR lost from downgrades');
            $table->decimal('churned_mrr', 10, 2)->default(0)->comment('MRR lost from cancellations');

            // Additional metadata
            $table->json('metadata')->nullable()->comment('Additional snapshot data (plan breakdown, etc.)');
            $table->timestamps();

            // Indexes for performance
            $table->unique(['tenant_id', 'snapshot_date', 'snapshot_type'], 'unique_tenant_snapshot');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index('snapshot_date');
            $table->index(['tenant_id', 'snapshot_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_mrr_snapshots');
    }
};
