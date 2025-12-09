<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get database driver
        $driver = DB::connection()->getDriverName();

        // Skip this migration for SQLite as it has limitations with indexed column drops
        // This is a schema cleanup migration from a previous refactor and not critical for new installations
        if ($driver === 'sqlite') {
            return;
        }

        Schema::table('clubs', function (Blueprint $table) {
            $table->dropColumn(['league', 'division', 'season']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get database driver
        $driver = DB::connection()->getDriverName();

        // Skip this migration rollback for SQLite
        if ($driver === 'sqlite') {
            return;
        }

        Schema::table('clubs', function (Blueprint $table) {
            $table->string('league')->nullable()->after('emergency_contact_email');
            $table->string('division')->nullable()->after('league');
            $table->string('season', 9)->nullable()->after('division');
        });
    }
};