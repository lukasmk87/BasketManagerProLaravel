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
            $table->string('subscription_status')->nullable()->after('subscription_tier');
            $table->enum('payment_status', ['paid', 'failed', 'pending'])->default('pending')->after('subscription_status');
            $table->timestamp('payment_failed_at')->nullable()->after('payment_status');
            $table->timestamp('last_payment_at')->nullable()->after('payment_failed_at');
            
            // Add indexes for performance
            $table->index('subscription_status');
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_status',
                'payment_status', 
                'payment_failed_at',
                'last_payment_at'
            ]);
        });
    }
};
