<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migriert bestehende Trainer-Zuordnungen von der team_user Pivot-Tabelle
     * in die neue dedizierte team_coaches Tabelle.
     */
    public function up(): void
    {
        // Nur ausführen wenn team_coaches Tabelle existiert
        if (!Schema::hasTable('team_coaches')) {
            return;
        }

        // Migriere alle Head Coaches und Assistant Coaches von team_user zu team_coaches
        DB::statement("
            INSERT INTO team_coaches (
                team_id,
                user_id,
                role,
                coaching_license,
                coaching_certifications,
                coaching_specialties,
                joined_at,
                is_active,
                created_at,
                updated_at
            )
            SELECT
                team_id,
                user_id,
                role,
                coaching_license,
                coaching_certifications,
                coaching_specialties,
                joined_at,
                is_active,
                created_at,
                updated_at
            FROM team_user
            WHERE role IN ('head_coach', 'assistant_coach')
            ON DUPLICATE KEY UPDATE
                coaching_license = VALUES(coaching_license),
                coaching_certifications = VALUES(coaching_certifications),
                coaching_specialties = VALUES(coaching_specialties),
                is_active = VALUES(is_active),
                updated_at = VALUES(updated_at)
        ");

        // Logge die Anzahl der migrierten Einträge
        $migratedCount = DB::table('team_coaches')->count();
        \Log::info("Team Coaches Migration: {$migratedCount} Trainer-Zuordnungen migriert.");
    }

    /**
     * Reverse the migrations.
     *
     * Diese Migration ist NICHT reversibel, da wir die Daten nicht löschen wollen.
     * Die team_user Einträge bleiben erhalten für Rückwärtskompatibilität.
     */
    public function down(): void
    {
        // Optional: Coaches aus team_coaches löschen die von dieser Migration kamen
        // Aber wir lassen sie bewusst drin für Daten-Integrität
        \Log::warning('Team Coaches Migration Rollback: Keine Aktion erforderlich. Daten bleiben in team_coaches.');
    }
};
