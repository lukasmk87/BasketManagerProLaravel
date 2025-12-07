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
        Schema::table('invoice_requests', function (Blueprint $table) {
            $table->string('subscription_plan_type')->nullable();
            $table->uuid('subscription_plan_id')->nullable();
            $table->index(['subscription_plan_type', 'subscription_plan_id'], 'invoice_req_sub_plan_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_requests', function (Blueprint $table) {
            $table->dropIndex('invoice_req_sub_plan_idx');
            $table->dropColumn(['subscription_plan_type', 'subscription_plan_id']);
        });
    }
};
