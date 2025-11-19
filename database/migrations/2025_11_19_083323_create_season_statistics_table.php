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
        Schema::create('season_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('players')->onDelete('cascade');
            $table->foreignId('season_id')->constrained('seasons')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
            $table->foreignId('club_id')->constrained('clubs')->onDelete('cascade');

            // Basis-Statistiken
            $table->integer('games_played')->default(0);
            $table->integer('games_started')->default(0);
            $table->integer('minutes_played')->default(0);

            // Scoring
            $table->integer('points')->default(0);
            $table->integer('field_goals_made')->default(0);
            $table->integer('field_goals_attempted')->default(0);
            $table->decimal('field_goal_percentage', 5, 2)->default(0);
            $table->integer('three_pointers_made')->default(0);
            $table->integer('three_pointers_attempted')->default(0);
            $table->decimal('three_point_percentage', 5, 2)->default(0);
            $table->integer('free_throws_made')->default(0);
            $table->integer('free_throws_attempted')->default(0);
            $table->decimal('free_throw_percentage', 5, 2)->default(0);

            // Rebounds
            $table->integer('rebounds_offensive')->default(0);
            $table->integer('rebounds_defensive')->default(0);
            $table->integer('rebounds_total')->default(0);

            // Assists & Turnovers
            $table->integer('assists')->default(0);
            $table->integer('turnovers')->default(0);
            $table->decimal('assist_turnover_ratio', 5, 2)->default(0);

            // Defense
            $table->integer('steals')->default(0);
            $table->integer('blocks')->default(0);

            // Fouls
            $table->integer('fouls_personal')->default(0);
            $table->integer('fouls_technical')->default(0);
            $table->integer('fouls_flagrant')->default(0);

            // Erweiterte Metriken (JSON für Flexibilität)
            $table->json('advanced_stats')->nullable(); // PER, TS%, eFG%, etc.
            $table->json('game_highs')->nullable(); // Höchstwerte in der Saison
            $table->json('metadata')->nullable(); // Zusätzliche Daten

            // Snapshot-Informationen
            $table->timestamp('snapshot_date');
            $table->timestamps();

            // Indizes für Performance
            $table->index(['player_id', 'season_id']);
            $table->index(['team_id', 'season_id']);
            $table->index(['club_id', 'season_id']);
            $table->unique(['player_id', 'season_id', 'team_id']); // Ein Snapshot pro Spieler/Saison/Team
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('season_statistics');
    }
};
