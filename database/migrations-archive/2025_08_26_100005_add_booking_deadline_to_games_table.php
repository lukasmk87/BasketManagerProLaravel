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
            // Buchungsfrist in Stunden vor dem Spiel
            $table->integer('registration_deadline_hours')->default(24)->after('allow_streaming');
            
            // Kader-spezifische Einstellungen
            $table->integer('max_roster_size')->default(12)->after('registration_deadline_hours');
            $table->integer('min_roster_size')->default(8)->after('max_roster_size');
            
            // Ob Spieler-Registrierungen erlaubt sind
            $table->boolean('allow_player_registrations')->default(true)->after('min_roster_size');
            
            // Automatische Kader-Bestätigung
            $table->boolean('auto_confirm_registrations')->default(false)->after('allow_player_registrations');
            
            // Deadline für Trainer-Entscheidungen
            $table->integer('lineup_deadline_hours')->default(2)->after('auto_confirm_registrations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn([
                'registration_deadline_hours',
                'max_roster_size',
                'min_roster_size',
                'allow_player_registrations',
                'auto_confirm_registrations',
                'lineup_deadline_hours'
            ]);
        });
    }
};