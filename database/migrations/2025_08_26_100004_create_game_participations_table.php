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
        Schema::create('game_participations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->onDelete('cascade');
            $table->foreignId('player_id')->constrained()->onDelete('cascade');
            
            // Rolle im Spiel
            $table->enum('role', [
                'starter',        // Startspieler
                'substitute',     // Ersatzspieler
                'reserve',        // Reserve
                'captain',        // Kapitän
                'vice_captain'    // Vize-Kapitän
            ])->default('substitute');
            
            // Spielstatus
            $table->enum('participation_status', [
                'selected',       // Ausgewählt für Kader
                'playing',        // Spielt aktiv
                'benched',        // Auf der Bank
                'injured',        // Verletzt während des Spiels
                'ejected',        // Rausgeschmissen
                'substituted_in', // Eingewechselt
                'substituted_out' // Ausgewechselt
            ])->default('selected');
            
            // Trikot-Nummer für das Spiel
            $table->integer('jersey_number')->nullable();
            
            // Position im Spiel
            $table->enum('playing_position', [
                'PG',  // Point Guard
                'SG',  // Shooting Guard  
                'SF',  // Small Forward
                'PF',  // Power Forward
                'C',   // Center
                'G',   // Guard (allgemein)
                'F',   // Forward (allgemein)
                'UTIL' // Utility Player
            ])->nullable();
            
            // Zeitstempel für Spielaktionen
            $table->timestamp('entered_game_at')->nullable();
            $table->timestamp('left_game_at')->nullable();
            $table->integer('minutes_played')->default(0);
            
            // Notizen
            $table->text('coach_notes')->nullable();
            $table->text('performance_notes')->nullable();
            
            // Wer hat die Auswahl getroffen
            $table->foreignId('selected_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('selected_at')->nullable();
            
            $table->timestamps();
            
            // Unique constraint
            $table->unique(['game_id', 'player_id'], 'unique_game_participation');
            
            // Indexes
            $table->index(['game_id', 'role']);
            $table->index(['game_id', 'participation_status']);
            $table->index('jersey_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_participations');
    }
};