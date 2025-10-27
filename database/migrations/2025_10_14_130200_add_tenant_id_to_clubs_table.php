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
            // Add tenant_id foreign key
            $table->foreignUuid('tenant_id')
                ->nullable() // Nullable initially to allow data migration
                ->after('club_subscription_plan_id')
                ->constrained('tenants')
                ->onDelete('cascade');

            // Add index for tenant_id for performance
            $table->index('tenant_id', 'idx_clubs_tenant');

            // Add composite index for tenant_id + is_active for common queries
            $table->index(['tenant_id', 'is_active'], 'idx_clubs_tenant_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_clubs_tenant_active');
            $table->dropIndex('idx_clubs_tenant');

            // Drop foreign key and column
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};
