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
     * Fixes role inconsistency: 'coach' → 'trainer' to match Spatie roles
     */
    public function up(): void
    {
        // Skip for SQLite (test environment)
        if (DB::getDriverName() === 'sqlite') {
            // For SQLite, just update the values (no enum constraint)
            DB::statement("UPDATE club_user SET role = 'trainer' WHERE role = 'coach'");
            return;
        }

        // MySQL-specific ENUM modification
        // Step 1: Modify the enum to ADD 'trainer' (temporarily allow both 'coach' and 'trainer')
        DB::statement("ALTER TABLE club_user MODIFY COLUMN role ENUM(
            'owner', 'admin', 'manager', 'coach', 'trainer', 'assistant_coach',
            'player', 'parent', 'volunteer', 'sponsor', 'member'
        ) NOT NULL DEFAULT 'member'");

        // Step 2: Update existing 'coach' values to 'trainer'
        DB::statement("UPDATE club_user SET role = 'trainer' WHERE role = 'coach'");

        // Step 3: Remove 'coach' from enum (only 'trainer' remains)
        DB::statement("ALTER TABLE club_user MODIFY COLUMN role ENUM(
            'owner', 'admin', 'manager', 'trainer', 'assistant_coach',
            'player', 'parent', 'volunteer', 'sponsor', 'member'
        ) NOT NULL DEFAULT 'member'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Skip for SQLite (test environment)
        if (DB::getDriverName() === 'sqlite') {
            // For SQLite, just update the values back
            DB::statement("UPDATE club_user SET role = 'coach' WHERE role = 'trainer'");
            return;
        }

        // MySQL-specific ENUM modification
        // Step 1: Update 'trainer' back to 'coach'
        DB::statement("UPDATE club_user SET role = 'coach' WHERE role = 'trainer'");

        // Step 2: Restore original enum with 'coach'
        DB::statement("ALTER TABLE club_user MODIFY COLUMN role ENUM(
            'owner', 'admin', 'manager', 'coach', 'assistant_coach',
            'player', 'parent', 'volunteer', 'sponsor', 'member'
        ) NOT NULL DEFAULT 'member'");
    }
};
