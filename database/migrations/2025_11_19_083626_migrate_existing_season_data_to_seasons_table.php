<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migriere bestehende Saisons aus Teams
        $this->migrateTeamSeasons();

        // Migriere bestehende Saisons aus Games
        $this->migrateGameSeasons();
    }

    /**
     * Migriert Saisons aus der teams Tabelle
     */
    private function migrateTeamSeasons(): void
    {
        // Hole alle eindeutigen (club_id, season) Paare aus teams
        $teams = DB::table('teams')
            ->select('club_id', 'season', 'season_start', 'season_end')
            ->whereNotNull('season')
            ->where('season', '!=', '')
            ->groupBy('club_id', 'season', 'season_start', 'season_end')
            ->get();

        foreach ($teams as $team) {
            // Prüfe ob Season bereits existiert
            $existingSeason = DB::table('seasons')
                ->where('club_id', $team->club_id)
                ->where('name', $team->season)
                ->first();

            if (!$existingSeason) {
                // Bestimme Status basierend auf Datum
                $status = $this->determineSeasonStatus($team->season_start, $team->season_end);
                $isCurrent = $status === 'active';

                // Erstelle Season-Eintrag
                $seasonId = DB::table('seasons')->insertGetId([
                    'club_id' => $team->club_id,
                    'name' => $team->season,
                    'start_date' => $team->season_start ?? $this->guessSeasonStart($team->season),
                    'end_date' => $team->season_end ?? $this->guessSeasonEnd($team->season),
                    'status' => $status,
                    'is_current' => $isCurrent,
                    'description' => 'Automatisch migrierte Saison',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $seasonId = $existingSeason->id;
            }

            // Update team mit season_id
            DB::table('teams')
                ->where('club_id', $team->club_id)
                ->where('season', $team->season)
                ->update(['season_id' => $seasonId]);
        }
    }

    /**
     * Migriert Saisons aus der games Tabelle
     */
    private function migrateGameSeasons(): void
    {
        // Hole alle Games mit season Feld
        $games = DB::table('games')
            ->select('id', 'season', 'scheduled_at', 'home_team_id')
            ->whereNotNull('season')
            ->where('season', '!=', '')
            ->get();

        foreach ($games as $game) {
            // Hole club_id vom Home-Team
            $team = DB::table('teams')->where('id', $game->home_team_id)->first();

            if (!$team) {
                continue; // Überspringe wenn Team nicht gefunden
            }

            // Finde entsprechende Season
            $season = DB::table('seasons')
                ->where('club_id', $team->club_id)
                ->where('name', $game->season)
                ->first();

            if ($season) {
                // Update game mit season_id
                DB::table('games')
                    ->where('id', $game->id)
                    ->update(['season_id' => $season->id]);
            }
        }
    }

    /**
     * Bestimmt den Status einer Saison basierend auf Datumsangaben
     */
    private function determineSeasonStatus($startDate, $endDate): string
    {
        if (!$startDate || !$endDate) {
            return 'draft';
        }

        $now = Carbon::now();
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($now->isBefore($start)) {
            return 'draft';
        } elseif ($now->between($start, $end)) {
            return 'active';
        } else {
            return 'completed';
        }
    }

    /**
     * Errät das Start-Datum einer Saison aus dem Namen (z.B. "2024/25" -> 2024-08-01)
     */
    private function guessSeasonStart(string $seasonName): string
    {
        // Extrahiere erstes Jahr aus "2024/25" Format
        if (preg_match('/(\d{4})/', $seasonName, $matches)) {
            $year = $matches[1];
            return "{$year}-08-01"; // Basketball-Saison startet typisch im August
        }

        return now()->toDateString();
    }

    /**
     * Errät das End-Datum einer Saison aus dem Namen (z.B. "2024/25" -> 2025-07-31)
     */
    private function guessSeasonEnd(string $seasonName): string
    {
        // Extrahiere Jahre aus "2024/25" Format
        if (preg_match('/(\d{4})\/(\d{2,4})/', $seasonName, $matches)) {
            $year2 = strlen($matches[2]) === 2 ? '20' . $matches[2] : $matches[2];
            return "{$year2}-07-31"; // Basketball-Saison endet typisch im Juli
        }

        return now()->addYear()->toDateString();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Setze season_id auf NULL zurück
        DB::table('teams')->update(['season_id' => null]);
        DB::table('games')->update(['season_id' => null]);

        // Optional: Lösche migrierte Seasons
        // DB::table('seasons')->where('description', 'Automatisch migrierte Saison')->delete();
    }
};
