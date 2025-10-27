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
        Schema::table('clubs', function (Blueprint $table) {
            // Stripe Customer & Subscription
            $table->string('stripe_customer_id')->nullable()->after('club_subscription_plan_id');
            $table->string('stripe_subscription_id')->nullable()->after('stripe_customer_id');

            // Subscription Status
            $table->enum('subscription_status', [
                'active',
                'trial',
                'past_due',
                'canceled',
                'incomplete',
                'incomplete_expired',
                'trialing',
                'unpaid'
            ])->default('incomplete')->after('stripe_subscription_id');

            // Subscription Timestamps
            $table->timestamp('subscription_started_at')->nullable()->after('subscription_status');
            $table->timestamp('subscription_trial_ends_at')->nullable()->after('subscription_started_at');
            $table->timestamp('subscription_ends_at')->nullable()->after('subscription_trial_ends_at');
            $table->timestamp('subscription_current_period_start')->nullable()->after('subscription_ends_at');
            $table->timestamp('subscription_current_period_end')->nullable()->after('subscription_current_period_start');

            // Billing Info
            $table->string('billing_email')->nullable()->after('subscription_current_period_end');
            $table->json('billing_address')->nullable()->after('billing_email');
            $table->string('payment_method_id')->nullable()->after('billing_address');

            // Indexes for better query performance
            $table->index('stripe_customer_id', 'idx_clubs_stripe_customer');
            $table->index('stripe_subscription_id', 'idx_clubs_stripe_subscription');
            $table->index('subscription_status', 'idx_clubs_subscription_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_clubs_stripe_customer');
            $table->dropIndex('idx_clubs_stripe_subscription');
            $table->dropIndex('idx_clubs_subscription_status');

            // Drop columns
            $table->dropColumn([
                'stripe_customer_id',
                'stripe_subscription_id',
                'subscription_status',
                'subscription_started_at',
                'subscription_trial_ends_at',
                'subscription_ends_at',
                'subscription_current_period_start',
                'subscription_current_period_end',
                'billing_email',
                'billing_address',
                'payment_method_id',
            ]);
        });
    }
};
