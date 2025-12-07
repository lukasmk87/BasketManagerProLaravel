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
        Schema::table('tenants', function (Blueprint $table) {
            // Stripe Connect Account
            $table->string('stripe_connect_account_id')->nullable()->after('stripe_id');
            $table->enum('stripe_connect_status', [
                'not_connected',
                'pending',
                'active',
                'restricted',
                'rejected'
            ])->default('not_connected')->after('stripe_connect_account_id');

            // Onboarding Status
            $table->boolean('stripe_connect_charges_enabled')->default(false)->after('stripe_connect_status');
            $table->boolean('stripe_connect_payouts_enabled')->default(false)->after('stripe_connect_charges_enabled');
            $table->boolean('stripe_connect_details_submitted')->default(false)->after('stripe_connect_payouts_enabled');

            // Timestamps
            $table->timestamp('stripe_connect_connected_at')->nullable()->after('stripe_connect_details_submitted');
            $table->timestamp('stripe_connect_last_webhook_at')->nullable()->after('stripe_connect_connected_at');

            // Indexes
            $table->index('stripe_connect_account_id', 'idx_tenants_connect_account');
            $table->index('stripe_connect_status', 'idx_tenants_connect_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex('idx_tenants_connect_account');
            $table->dropIndex('idx_tenants_connect_status');

            $table->dropColumn([
                'stripe_connect_account_id',
                'stripe_connect_status',
                'stripe_connect_charges_enabled',
                'stripe_connect_payouts_enabled',
                'stripe_connect_details_submitted',
                'stripe_connect_connected_at',
                'stripe_connect_last_webhook_at',
            ]);
        });
    }
};
