<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mark existing users who already have clubs as onboarded.
     */
    public function up(): void
    {
        // Mark all users who are members of at least one club as onboarded
        DB::statement("
            UPDATE users
            SET onboarding_completed_at = NOW()
            WHERE id IN (
                SELECT DISTINCT user_id FROM club_user
            )
            AND onboarding_completed_at IS NULL
        ");

        // Also mark super_admins and admins as onboarded (they skip onboarding)
        DB::statement("
            UPDATE users
            SET onboarding_completed_at = NOW()
            WHERE id IN (
                SELECT model_id FROM model_has_roles
                WHERE model_type = 'App\\\\Models\\\\User'
                AND role_id IN (
                    SELECT id FROM roles WHERE name IN ('super_admin', 'admin')
                )
            )
            AND onboarding_completed_at IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not reversible - we can't determine which users
        // were marked as onboarded by this migration vs. by actual onboarding
    }
};
