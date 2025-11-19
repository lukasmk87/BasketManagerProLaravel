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
        Schema::table('teams', function (Blueprint $table) {
            // Füge season_id als nullable hinzu für Datenmigration
            $table->foreignId('season_id')->nullable()->after('season')->constrained('seasons')->onDelete('cascade');

            // Index für Performance
            $table->index(['club_id', 'season_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropForeign(['season_id']);
            $table->dropIndex(['club_id', 'season_id']);
            $table->dropColumn('season_id');
        });
    }
};
