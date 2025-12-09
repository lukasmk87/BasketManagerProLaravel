<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration makes Super Admins tenant-free by:
     * 1. Ensuring users.tenant_id is nullable
     * 2. Setting tenant_id = NULL for all existing Super Admin users
     */
    public function up(): void
    {
        // Check if tenant_id column exists
        if (Schema::hasColumn('users', 'tenant_id')) {
            // Step 1: Drop foreign key constraint if it exists
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
            });

            // Step 2: Make tenant_id nullable (keeping UUID type)
            // tenant_id is a UUID (char(36)), not bigint!
            DB::statement('ALTER TABLE users MODIFY tenant_id CHAR(36) NULL');

            // Step 3: Re-add foreign key constraint with onDelete('set null')
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('tenant_id')
                      ->references('id')
                      ->on('tenants')
                      ->onDelete('set null');
            });
        } else {
            // Column doesn't exist yet, add it as nullable UUID
            DB::statement('ALTER TABLE users ADD tenant_id CHAR(36) NULL AFTER id');

            Schema::table('users', function (Blueprint $table) {
                $table->foreign('tenant_id')
                      ->references('id')
                      ->on('tenants')
                      ->onDelete('set null');
                $table->index('tenant_id');
            });
        }

        // Update all existing Super Admin users to have tenant_id = NULL
        // Super Admins are system users and should not be bound to any tenant
        DB::statement("
            UPDATE users
            SET tenant_id = NULL
            WHERE id IN (
                SELECT model_id
                FROM model_has_roles
                WHERE role_id = (SELECT id FROM roles WHERE name = 'super_admin' LIMIT 1)
                  AND model_type = 'App\\\\Models\\\\User'
            )
        ");
    }

    /**
     * Reverse the migrations.
     *
     * WARNING: Reversing this migration will NOT restore previous tenant_id values
     * for Super Admins, as we don't store them. Super Admins will remain with tenant_id = NULL.
     */
    public function down(): void
    {
        // No rollback needed - tenant_id remains nullable
        // We cannot restore previous tenant_id values for Super Admins
        // as we didn't store them before the migration
    }
};
