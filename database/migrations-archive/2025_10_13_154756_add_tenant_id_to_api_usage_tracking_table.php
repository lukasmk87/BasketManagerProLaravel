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
        Schema::table('api_usage_tracking', function (Blueprint $table) {
            // Add tenant_id column for multi-tenant support
            $table->uuid('tenant_id')->nullable()->after('id');

            // Add foreign key constraint
            $table->foreign('tenant_id')
                  ->references('id')
                  ->on('tenants')
                  ->onDelete('cascade');

            // Add index for performance
            $table->index(['tenant_id', 'window_start', 'window_type'], 'tenant_window_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_usage_tracking', function (Blueprint $table) {
            // Drop foreign key and index first
            $table->dropForeign(['tenant_id']);
            $table->dropIndex('tenant_window_idx');

            // Drop the column
            $table->dropColumn('tenant_id');
        });
    }
};
