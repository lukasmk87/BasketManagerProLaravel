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
        Schema::table('club_subscription_plans', function (Blueprint $table) {
            // Stripe Product & Prices
            $table->string('stripe_product_id')->nullable()->after('icon');
            $table->string('stripe_price_id_monthly')->nullable()->after('stripe_product_id');
            $table->string('stripe_price_id_yearly')->nullable()->after('stripe_price_id_monthly');

            // Sync Status
            $table->boolean('is_stripe_synced')->default(false)->after('stripe_price_id_yearly');
            $table->timestamp('last_stripe_sync_at')->nullable()->after('is_stripe_synced');

            // Trial Settings
            $table->integer('trial_period_days')->default(0)->after('last_stripe_sync_at')
                ->comment('Number of trial days (0 = no trial)');

            // Indexes for better query performance
            $table->index('stripe_product_id', 'idx_club_plans_stripe_product');
            $table->index('is_stripe_synced', 'idx_club_plans_stripe_synced');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_subscription_plans', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_club_plans_stripe_product');
            $table->dropIndex('idx_club_plans_stripe_synced');

            // Drop columns
            $table->dropColumn([
                'stripe_product_id',
                'stripe_price_id_monthly',
                'stripe_price_id_yearly',
                'is_stripe_synced',
                'last_stripe_sync_at',
                'trial_period_days',
            ]);
        });
    }
};
