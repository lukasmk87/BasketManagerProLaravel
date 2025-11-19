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
        Schema::table('games', function (Blueprint $table) {
            // Füge season_id als nullable hinzu für Datenmigration
            $table->foreignId('season_id')->nullable()->after('season')->constrained('seasons')->onDelete('cascade');

            // Index für Performance
            $table->index(['season_id', 'type']);
            $table->index(['season_id', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropForeign(['season_id']);
            $table->dropIndex(['season_id', 'type']);
            $table->dropIndex(['season_id', 'scheduled_at']);
            $table->dropColumn('season_id');
        });
    }
};
