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
     * This migration adds the missing tenant_id column to club_usages table
     * which is required by the ClubUsage model (uses BelongsToTenant concern).
     */
    public function up(): void
    {
        // First, drop the foreign key constraint on club_id if it exists
        Schema::table('club_usages', function (Blueprint $table) {
            $table->dropForeign(['club_id']);
        });

        // Drop the unique index using raw SQL to avoid Laravel's constraint detection
        DB::statement('ALTER TABLE club_usages DROP INDEX club_usages_club_id_metric_period_start_unique');

        // Now add tenant_id and recreate constraints
        Schema::table('club_usages', function (Blueprint $table) {
            // Add tenant_id column
            $table->uuid('tenant_id')->nullable()->after('id')->index();
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');

            // Re-create foreign key on club_id
            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('cascade');

            // Create new unique constraint with tenant_id included
            $table->unique(['club_id', 'tenant_id', 'metric', 'period_start'], 'club_usages_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop all foreign keys first
        Schema::table('club_usages', function (Blueprint $table) {
            $table->dropForeign(['club_id']);
            $table->dropForeign(['tenant_id']);
            $table->dropUnique('club_usages_unique');
        });

        // Drop tenant_id column
        Schema::table('club_usages', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        // Restore original constraints
        Schema::table('club_usages', function (Blueprint $table) {
            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('cascade');
            $table->unique(['club_id', 'metric', 'period_start']);
        });
    }
};
