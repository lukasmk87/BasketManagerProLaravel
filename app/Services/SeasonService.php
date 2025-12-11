<?php

namespace App\Services;

use App\Models\BasketballTeam;
use App\Models\Club;
use App\Models\Player;
use App\Models\Season;
use App\Models\SeasonStatistic;
use App\Services\Statistics\StatisticsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SeasonService
{
    public function __construct(
        private TeamService $teamService,
        private PlayerService $playerService,
        private StatisticsService $statisticsService
    ) {}

    /**
     * Erstellt eine neue Saison für einen Club
     */
    public function createNewSeason(
        Club $club,
        string $name,
        Carbon $startDate,
        Carbon $endDate,
        ?string $description = null,
        array $settings = []
    ): Season {
        // Prüfe ob Saison mit diesem Namen bereits existiert
        $existing = Season::where('club_id', $club->id)
            ->where('name', $name)
            ->first();

        if ($existing) {
            throw new \Exception("Eine Saison mit dem Namen '{$name}' existiert bereits für diesen Club.");
        }

        $season = Season::create([
            'club_id' => $club->id,
            'name' => $name,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'draft',
            'is_current' => false,
            'description' => $description,
            'settings' => $settings,
        ]);

        Log::info('Neue Saison erstellt', [
            'season_id' => $season->id,
            'club_id' => $club->id,
            'name' => $name,
        ]);

        return $season;
    }

    /**
     * Ordnet Teams einer Saison zu
     */
    public function assignTeamsToSeason(Season $season, array $teamIds): int
    {
        $club = $season->club;

        // Nur Teams des gleichen Clubs erlauben
        $updated = BasketballTeam::where('club_id', $club->id)
            ->whereIn('id', $teamIds)
            ->update(['season_id' => $season->id]);

        Log::info('Teams der Saison zugeordnet', [
            'season_id' => $season->id,
            'team_ids' => $teamIds,
            'updated_count' => $updated,
        ]);

        return $updated;
    }

    /**
     * Schließt eine Saison ab und erstellt Statistik-Snapshots
     */
    public function completeSeason(Season $season, bool $createSnapshots = true): bool
    {
        if (! $season->isActive()) {
            throw new \Exception('Nur aktive Saisons können abgeschlossen werden.');
        }

        DB::beginTransaction();

        try {
            // Erstelle Statistik-Snapshots für alle Teams der Saison
            if ($createSnapshots) {
                $this->createSeasonStatisticsSnapshot($season);
            }

            // Archiviere alle Spieler-Zuordnungen (setze left_at)
            $this->archiveSeasonRosters($season);

            // Schließe Saison ab
            $season->complete();

            DB::commit();

            Log::info('Saison abgeschlossen', [
                'season_id' => $season->id,
                'season_name' => $season->name,
                'snapshots_created' => $createSnapshots,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Fehler beim Abschließen der Saison', [
                'season_id' => $season->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Startet eine neue Saison für einen Club (vollständiger Saisonwechsel)
     */
    public function startNewSeasonForClub(
        Club $club,
        string $newSeasonName,
        Carbon $startDate,
        Carbon $endDate,
        ?Season $previousSeason = null,
        bool $rolloverTeams = true,
        bool $rolloverRosters = true
    ): Season {
        DB::beginTransaction();

        try {
            // Hole vorherige Saison falls nicht übergeben
            if (! $previousSeason) {
                $previousSeason = $this->getActiveSeason($club);
            }

            // Schließe vorherige Saison ab falls aktiv
            if ($previousSeason && $previousSeason->isActive()) {
                $this->completeSeason($previousSeason);
            }

            // Erstelle neue Saison
            $newSeason = $this->createNewSeason($club, $newSeasonName, $startDate, $endDate);

            // Teams und Kader übertragen falls gewünscht
            if ($rolloverTeams && $previousSeason) {
                $this->rolloverTeams($previousSeason, $newSeason, $rolloverRosters);
            }

            // Aktiviere neue Saison
            $newSeason->activate();

            DB::commit();

            Log::info('Neue Saison gestartet', [
                'club_id' => $club->id,
                'new_season_id' => $newSeason->id,
                'previous_season_id' => $previousSeason?->id,
                'rollover_teams' => $rolloverTeams,
            ]);

            return $newSeason;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Fehler beim Starten der neuen Saison', [
                'club_id' => $club->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Kopiert alle Teams einer Saison in eine neue Saison
     */
    public function rolloverTeams(Season $oldSeason, Season $newSeason, bool $rolloverRosters = true): array
    {
        $copiedTeams = [];

        $teams = BasketballTeam::where('season_id', $oldSeason->id)->get();

        foreach ($teams as $oldTeam) {
            $newTeam = $this->copyTeamToNewSeason($oldTeam, $newSeason);
            $copiedTeams[] = $newTeam;

            // Kader übertragen falls gewünscht
            if ($rolloverRosters) {
                $this->rolloverRoster($oldTeam, $newTeam);
            }
        }

        Log::info('Teams in neue Saison kopiert', [
            'old_season_id' => $oldSeason->id,
            'new_season_id' => $newSeason->id,
            'teams_count' => count($copiedTeams),
        ]);

        return $copiedTeams;
    }

    /**
     * Kopiert ein Team in eine neue Saison
     */
    public function copyTeamToNewSeason(BasketballTeam $team, Season $newSeason): BasketballTeam
    {
        $newTeam = $team->replicate();
        $newTeam->uuid = (string) \Illuminate\Support\Str::uuid(); // Generate new UUID
        $newTeam->slug = \Illuminate\Support\Str::slug($team->name.'-'.$newSeason->name); // Generate new slug
        $newTeam->season_id = $newSeason->id;
        $newTeam->season = $newSeason->name; // Für Rückwärtskompatibilität
        $newTeam->season_start = $newSeason->start_date;
        $newTeam->season_end = $newSeason->end_date;
        $newTeam->created_at = now();
        $newTeam->updated_at = now();
        $newTeam->save();

        Log::info('Team in neue Saison kopiert', [
            'old_team_id' => $team->id,
            'new_team_id' => $newTeam->id,
            'season_id' => $newSeason->id,
        ]);

        return $newTeam;
    }

    /**
     * Überträgt Kader von einem Team zum anderen (automatischer Rollover)
     */
    public function rolloverRoster(BasketballTeam $oldTeam, BasketballTeam $newTeam): int
    {
        $transferredCount = 0;

        // Hole alle aktiven Spieler des alten Teams
        $players = $oldTeam->players()
            ->wherePivot('is_active', true)
            ->get();

        foreach ($players as $player) {
            $pivotData = $player->pivot;

            // Füge Spieler zum neuen Team hinzu
            $newTeam->players()->attach($player->id, [
                'jersey_number' => $pivotData->jersey_number,
                'primary_position' => $pivotData->primary_position,
                'secondary_positions' => $pivotData->secondary_positions,
                'is_active' => true,
                'is_starter' => $pivotData->is_starter,
                'is_captain' => $pivotData->is_captain,
                'joined_at' => $newTeam->season_start ?? now(),
                'contract_start' => $newTeam->season_start,
                'contract_end' => $newTeam->season_end,
                'is_registered' => false, // Muss neu registriert werden
                'notes' => "Automatisch übertragen aus Saison {$oldTeam->season}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Archiviere alte Zuordnung
            $oldTeam->players()->updateExistingPivot($player->id, [
                'is_active' => false,
                'left_at' => $oldTeam->season_end ?? now(),
            ]);

            $transferredCount++;
        }

        Log::info('Kader übertragen', [
            'old_team_id' => $oldTeam->id,
            'new_team_id' => $newTeam->id,
            'players_count' => $transferredCount,
        ]);

        return $transferredCount;
    }

    /**
     * Erstellt Statistik-Snapshots für alle Spieler einer Saison
     */
    public function createSeasonStatisticsSnapshot(Season $season): int
    {
        $snapshotCount = 0;

        $teams = BasketballTeam::where('season_id', $season->id)->get();

        foreach ($teams as $team) {
            $players = $team->players()->wherePivot('is_active', true)->get();

            foreach ($players as $player) {
                $this->snapshotPlayerStats($player, $season, $team);
                $snapshotCount++;
            }
        }

        Log::info('Statistik-Snapshots erstellt', [
            'season_id' => $season->id,
            'snapshots_count' => $snapshotCount,
        ]);

        return $snapshotCount;
    }

    /**
     * Erstellt einen Statistik-Snapshot für einen Spieler
     */
    private function snapshotPlayerStats(Player $player, Season $season, BasketballTeam $team): SeasonStatistic
    {
        // Hole Saison-Statistiken über StatisticsService
        $stats = $this->statisticsService->getPlayerSeasonStats($player, $season->name);

        // Berechne Prozentsätze
        $fgPct = $stats['field_goals_attempted'] > 0
            ? round(($stats['field_goals_made'] / $stats['field_goals_attempted']) * 100, 2)
            : 0;

        $threePct = $stats['three_pointers_attempted'] > 0
            ? round(($stats['three_pointers_made'] / $stats['three_pointers_attempted']) * 100, 2)
            : 0;

        $ftPct = $stats['free_throws_attempted'] > 0
            ? round(($stats['free_throws_made'] / $stats['free_throws_attempted']) * 100, 2)
            : 0;

        $astToRatio = $stats['turnovers'] > 0
            ? round($stats['assists'] / $stats['turnovers'], 2)
            : 0;

        return SeasonStatistic::create([
            'player_id' => $player->id,
            'season_id' => $season->id,
            'team_id' => $team->id,
            'club_id' => $season->club_id,
            'games_played' => $stats['games_played'] ?? 0,
            'games_started' => $stats['games_started'] ?? 0,
            'minutes_played' => $stats['minutes_played'] ?? 0,
            'points' => $stats['points'] ?? 0,
            'field_goals_made' => $stats['field_goals_made'] ?? 0,
            'field_goals_attempted' => $stats['field_goals_attempted'] ?? 0,
            'field_goal_percentage' => $fgPct,
            'three_pointers_made' => $stats['three_pointers_made'] ?? 0,
            'three_pointers_attempted' => $stats['three_pointers_attempted'] ?? 0,
            'three_point_percentage' => $threePct,
            'free_throws_made' => $stats['free_throws_made'] ?? 0,
            'free_throws_attempted' => $stats['free_throws_attempted'] ?? 0,
            'free_throw_percentage' => $ftPct,
            'rebounds_offensive' => $stats['rebounds_offensive'] ?? 0,
            'rebounds_defensive' => $stats['rebounds_defensive'] ?? 0,
            'rebounds_total' => $stats['rebounds_total'] ?? 0,
            'assists' => $stats['assists'] ?? 0,
            'turnovers' => $stats['turnovers'] ?? 0,
            'assist_turnover_ratio' => $astToRatio,
            'steals' => $stats['steals'] ?? 0,
            'blocks' => $stats['blocks'] ?? 0,
            'fouls_personal' => $stats['fouls_personal'] ?? 0,
            'fouls_technical' => $stats['fouls_technical'] ?? 0,
            'fouls_flagrant' => $stats['fouls_flagrant'] ?? 0,
            'advanced_stats' => $stats['advanced_stats'] ?? null,
            'game_highs' => $stats['game_highs'] ?? null,
            'snapshot_date' => now(),
        ]);
    }

    /**
     * Archiviert alle Spieler-Zuordnungen einer Saison
     */
    private function archiveSeasonRosters(Season $season): void
    {
        $teams = BasketballTeam::where('season_id', $season->id)->get();

        foreach ($teams as $team) {
            DB::table('player_team')
                ->where('team_id', $team->id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'left_at' => $season->end_date ?? now(),
                ]);
        }
    }

    /**
     * Holt die aktuelle aktive Saison für einen Club
     */
    public function getActiveSeason(Club $club): ?Season
    {
        return Season::where('club_id', $club->id)
            ->where('is_current', true)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Holt alle Saisons für einen Club
     */
    public function getAllSeasonsForClub(Club $club, bool $includeCompleted = true): \Illuminate\Database\Eloquent\Collection
    {
        $query = Season::where('club_id', $club->id)
            ->orderBy('start_date', 'desc');

        if (! $includeCompleted) {
            $query->where('status', '!=', 'completed');
        }

        return $query->get();
    }

    /**
     * Aktiviert eine Saison
     */
    public function activateSeason(Season $season): bool
    {
        if (! $season->canBeActivated()) {
            throw new \Exception("Saison kann nicht aktiviert werden. Status: {$season->status}");
        }

        return $season->activate();
    }

    /**
     * Holt Statistiken für eine Saison
     */
    public function getSeasonStatistics(Season $season): array
    {
        $teams = BasketballTeam::where('season_id', $season->id)->get();
        $totalGames = $season->games()->count();
        $totalPlayers = 0;

        foreach ($teams as $team) {
            $totalPlayers += $team->players()->wherePivot('is_active', true)->count();
        }

        return [
            'season' => $season,
            'teams_count' => $teams->count(),
            'players_count' => $totalPlayers,
            'games_count' => $totalGames,
            'is_active' => $season->isActive(),
            'is_completed' => $season->isCompleted(),
        ];
    }
}
