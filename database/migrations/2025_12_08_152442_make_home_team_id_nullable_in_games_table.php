<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Makes home_team_id nullable to support away games against external teams.
     * This mirrors the change made for away_team_id in add_external_team_support_to_games_table.
     */
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // Make home_team_id nullable for external teams (when importing away games)
            $table->foreignId('home_team_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // Make home_team_id required again
            $table->foreignId('home_team_id')->nullable(false)->change();
        });
    }
};
