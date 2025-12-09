<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Makes season nullable to support Jetstream personal teams
     * which don't have a basketball season.
     */
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('season', 20)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // Note: This will fail if there are NULL values
            $table->string('season', 20)->nullable(false)->change();
        });
    }
};
