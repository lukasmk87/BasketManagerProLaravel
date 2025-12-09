<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Makes club_id nullable to support Jetstream personal teams
     * which don't belong to any basketball club.
     */
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['club_id']);
        });

        Schema::table('teams', function (Blueprint $table) {
            // Make club_id nullable
            $table->unsignedBigInteger('club_id')->nullable()->change();

            // Re-add the foreign key constraint
            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // Drop the foreign key
            $table->dropForeign(['club_id']);
        });

        Schema::table('teams', function (Blueprint $table) {
            // Make club_id NOT NULL again (requires no null values exist)
            $table->unsignedBigInteger('club_id')->nullable(false)->change();

            // Re-add the foreign key constraint
            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('cascade');
        });
    }
};
