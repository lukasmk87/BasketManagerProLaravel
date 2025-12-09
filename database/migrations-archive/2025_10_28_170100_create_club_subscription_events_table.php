<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Create club_subscription_events table for comprehensive subscription lifecycle audit trail.
     * Tracks all subscription changes for churn analysis, revenue attribution, and debugging.
     */
    public function up(): void
    {
        Schema::create('club_subscription_events', function (Blueprint $table) {
            $table->id();
            $table->char('tenant_id', 36);
            $table->unsignedBigInteger('club_id');

            // Event classification
            $table->enum('event_type', [
                'subscription_created',
                'subscription_canceled',
                'subscription_renewed',
                'plan_upgraded',
                'plan_downgraded',
                'trial_started',
                'trial_converted',
                'trial_expired',
                'payment_succeeded',
                'payment_failed',
                'payment_recovered',
            ]);

            // Stripe references
            $table->string('stripe_subscription_id')->nullable();
            $table->string('stripe_event_id')->nullable()->comment('Stripe webhook event ID for traceability');

            // Plan information (for upgrades/downgrades)
            $table->char('old_plan_id', 36)->nullable();
            $table->char('new_plan_id', 36)->nullable();

            // Revenue impact
            $table->decimal('mrr_change', 10, 2)->default(0)->comment('Change in MRR from this event');

            // Churn tracking (for cancellations and failures)
            $table->enum('cancellation_reason', [
                'voluntary',
                'payment_failed',
                'trial_expired',
                'downgrade_to_free',
                'other',
            ])->nullable();
            $table->text('cancellation_feedback')->nullable()->comment('User-provided cancellation reason');

            // Metadata for additional context
            $table->json('metadata')->nullable();

            // Timestamps
            $table->timestamp('event_date');
            $table->timestamps();

            // Foreign keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('club_id')->references('id')->on('clubs')->cascadeOnDelete();

            // Indexes for efficient querying
            $table->index(['club_id', 'event_date']);
            $table->index(['tenant_id', 'event_date']);
            $table->index('event_type');
            $table->index('event_date');
            $table->index('stripe_event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_subscription_events');
    }
};
