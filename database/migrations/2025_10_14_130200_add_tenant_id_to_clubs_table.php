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
            // Check if column exists before adding
            if (!Schema::hasColumn('clubs', 'tenant_id')) {
                // Add tenant_id foreign key (creates index automatically)
                $table->foreignUuid('tenant_id')
                    ->nullable() // Nullable initially to allow data migration
                    ->constrained('tenants')
                    ->onDelete('cascade');
            }
        });

        // Add composite index for tenant_id + is_active for common queries (if not exists)
        // Use try-catch to handle index already exists error
        try {
            Schema::table('clubs', function (Blueprint $table) {
                $table->index(['tenant_id', 'is_active'], 'idx_clubs_tenant_active');
            });
        } catch (\Exception $e) {
            // Index already exists, silently continue
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clubs', function (Blueprint $table) {
            // Drop composite index if exists
            try {
                $table->dropIndex('idx_clubs_tenant_active');
            } catch (\Exception $e) {
                // Index doesn't exist, continue
            }

            // Drop foreign key and column
            if (Schema::hasColumn('clubs', 'tenant_id')) {
                $table->dropForeign(['tenant_id']);
                $table->dropColumn('tenant_id');
            }
        });
    }
};
