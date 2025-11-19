<?php

namespace App\Http\Controllers;

use App\Models\Club;
use App\Models\Season;
use App\Services\SeasonService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Barryvdh\DomPDF\Facade\Pdf;

class SeasonController extends Controller
{
    public function __construct(
        private SeasonService $seasonService
    ) {}

    /**
     * Liste alle Saisons für einen Club auf
     *
     * GET /club/{club}/seasons
     */
    public function index(Club $club, Request $request): JsonResponse
    {
        $this->authorize('viewAny', Season::class);

        $includeCompleted = $request->boolean('include_completed', true);

        $seasons = $this->seasonService->getAllSeasonsForClub($club, $includeCompleted);

        return response()->json([
            'success' => true,
            'data' => $seasons,
        ]);
    }

    /**
     * Zeige eine einzelne Saison mit Statistiken
     *
     * GET /club/{club}/seasons/{season}
     */
    public function show(Club $club, Season $season): JsonResponse
    {
        $this->authorize('view', $season);

        // Prüfe ob Saison zum Club gehört
        if ($season->club_id !== $club->id) {
            return response()->json([
                'success' => false,
                'message' => 'Saison gehört nicht zu diesem Club.',
            ], 403);
        }

        $statistics = $this->seasonService->getSeasonStatistics($season);

        return response()->json([
            'success' => true,
            'data' => array_merge([
                'season' => $season->load(['teams', 'games']),
            ], $statistics),
        ]);
    }

    /**
     * Erstelle eine neue Saison
     *
     * POST /club/{club}/seasons
     */
    public function store(Club $club, Request $request): JsonResponse
    {
        $this->authorize('create', Season::class);

        $validated = $request->validate([
            'name' => 'required|string|max:20',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'description' => 'nullable|string|max:1000',
            'settings' => 'nullable|array',
        ]);

        try {
            $season = $this->seasonService->createNewSeason(
                $club,
                $validated['name'],
                Carbon::parse($validated['start_date']),
                Carbon::parse($validated['end_date']),
                $validated['description'] ?? null,
                $validated['settings'] ?? []
            );

            return response()->json([
                'success' => true,
                'message' => 'Saison erfolgreich erstellt.',
                'data' => $season,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Schließe eine Saison ab
     *
     * POST /club/{club}/seasons/{season}/complete
     */
    public function complete(Club $club, Season $season, Request $request): JsonResponse
    {
        $this->authorize('complete', $season);

        // Prüfe ob Saison zum Club gehört
        if ($season->club_id !== $club->id) {
            return response()->json([
                'success' => false,
                'message' => 'Saison gehört nicht zu diesem Club.',
            ], 403);
        }

        $validated = $request->validate([
            'create_snapshots' => 'boolean',
        ]);

        try {
            $this->seasonService->completeSeason(
                $season,
                $validated['create_snapshots'] ?? true
            );

            return response()->json([
                'success' => true,
                'message' => 'Saison erfolgreich abgeschlossen.',
                'data' => $season->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Starte eine neue Saison (vollständiger Saisonwechsel)
     *
     * POST /club/{club}/seasons/start-new
     */
    public function startNew(Club $club, Request $request): JsonResponse
    {
        $this->authorize('startNew', Season::class);

        $validated = $request->validate([
            'name' => 'required|string|max:20',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'description' => 'nullable|string|max:1000',
            'rollover_teams' => 'boolean',
            'rollover_rosters' => 'boolean',
        ]);

        try {
            $newSeason = $this->seasonService->startNewSeasonForClub(
                $club,
                $validated['name'],
                Carbon::parse($validated['start_date']),
                Carbon::parse($validated['end_date']),
                null, // Findet automatisch die vorherige Saison
                $validated['rollover_teams'] ?? true,
                $validated['rollover_rosters'] ?? true
            );

            return response()->json([
                'success' => true,
                'message' => 'Neue Saison erfolgreich gestartet.',
                'data' => $newSeason->load(['teams']),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Fehler beim Starten neuer Saison', [
                'club_id' => $club->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Aktiviere eine Saison
     *
     * POST /club/{club}/seasons/{season}/activate
     */
    public function activate(Club $club, Season $season): JsonResponse
    {
        $this->authorize('activate', $season);

        // Prüfe ob Saison zum Club gehört
        if ($season->club_id !== $club->id) {
            return response()->json([
                'success' => false,
                'message' => 'Saison gehört nicht zu diesem Club.',
            ], 403);
        }

        try {
            $this->seasonService->activateSeason($season);

            return response()->json([
                'success' => true,
                'message' => 'Saison erfolgreich aktiviert.',
                'data' => $season->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Hole die aktuelle aktive Saison
     *
     * GET /club/{club}/seasons/current
     */
    public function current(Club $club): JsonResponse
    {
        $this->authorize('viewAny', Season::class);

        $season = $this->seasonService->getActiveSeason($club);

        if (!$season) {
            return response()->json([
                'success' => false,
                'message' => 'Keine aktive Saison gefunden.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $season->load(['teams', 'games']),
        ]);
    }

    /**
     * Aktualisiere eine Saison
     *
     * PUT /club/{club}/seasons/{season}
     */
    public function update(Club $club, Season $season, Request $request): JsonResponse
    {
        $this->authorize('update', $season);

        // Prüfe ob Saison zum Club gehört
        if ($season->club_id !== $club->id) {
            return response()->json([
                'success' => false,
                'message' => 'Saison gehört nicht zu diesem Club.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:20',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'description' => 'nullable|string|max:1000',
            'settings' => 'nullable|array',
        ]);

        $season->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Saison erfolgreich aktualisiert.',
            'data' => $season,
        ]);
    }

    /**
     * Lösche eine Saison (soft delete)
     *
     * DELETE /club/{club}/seasons/{season}
     */
    public function destroy(Club $club, Season $season): JsonResponse
    {
        $this->authorize('delete', $season);

        // Prüfe ob Saison zum Club gehört
        if ($season->club_id !== $club->id) {
            return response()->json([
                'success' => false,
                'message' => 'Saison gehört nicht zu diesem Club.',
            ], 403);
        }

        // Verhindere Löschen der aktiven Saison
        if ($season->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Aktive Saisons können nicht gelöscht werden.',
            ], 422);
        }

        $season->delete();

        return response()->json([
            'success' => true,
            'message' => 'Saison erfolgreich gelöscht.',
        ]);
    }

    /**
     * Vergleiche mehrere Saisons
     *
     * GET /club/{club}/seasons/compare
     */
    public function compare(Club $club, Request $request): Response
    {
        $this->authorize('compareSeasons', Season::class);

        $validated = $request->validate([
            'seasons' => 'sometimes|array|max:4',
            'seasons.*' => 'integer|exists:seasons,id',
        ]);

        // Get all available seasons for this club
        $availableSeasons = Season::where('club_id', $club->id)
            ->with(['teams', 'games'])
            ->orderBy('start_date', 'desc')
            ->get();

        $selectedSeasons = $validated['seasons'] ?? [];

        // If seasons were provided, verify they all belong to this club
        if (!empty($selectedSeasons)) {
            $seasonsToCompare = Season::whereIn('id', $selectedSeasons)
                ->where('club_id', $club->id)
                ->with(['teams', 'games'])
                ->get();

            if ($seasonsToCompare->count() !== count($selectedSeasons)) {
                abort(403, 'Einige Saisons gehören nicht zu diesem Club.');
            }
        }

        return Inertia::render('ClubAdmin/Seasons/Compare', [
            'club' => $club,
            'availableSeasons' => $availableSeasons,
            'initialSelectedSeasons' => $selectedSeasons,
            'permissions' => [
                'exportSeasons' => auth()->user()->can('exportStatistics', Season::class),
                'manageSeasons' => auth()->user()->can('update', $club),
            ],
        ]);
    }

    /**
     * Exportiere Saison-Statistiken
     *
     * POST /club/{club}/seasons/{season}/export
     */
    public function export(Club $club, Season $season, Request $request)
    {
        $this->authorize('exportStatistics', $season);

        // Prüfe ob Saison zum Club gehört
        if ($season->club_id !== $club->id) {
            abort(403, 'Saison gehört nicht zu diesem Club.');
        }

        $validated = $request->validate([
            'format' => 'required|in:pdf,csv',
            'include_games' => 'boolean',
            'include_players' => 'boolean',
        ]);

        $statistics = $this->seasonService->getSeasonStatistics($season);

        // Add additional data
        $exportData = [
            'season' => $season->load(['teams', 'games']),
            'statistics' => $statistics,
            'club' => $club,
            'include_games' => $validated['include_games'] ?? true,
            'include_players' => $validated['include_players'] ?? true,
            'generated_at' => now()->format('d.m.Y H:i'),
        ];

        if ($validated['format'] === 'pdf') {
            // Generate PDF
            $pdf = PDF::loadView('seasons.export-pdf', $exportData);

            $filename = 'saison-' . str_slug($season->name) . '-statistiken.pdf';

            return $pdf->download($filename);
        }

        // CSV Export
        $filename = 'saison-' . str_slug($season->name) . '-statistiken.csv';

        return response()->streamDownload(function () use ($season, $statistics) {
            $file = fopen('php://output', 'w');

            // BOM for proper UTF-8 encoding in Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Write headers
            fputcsv($file, ['Metrik', 'Wert'], ';');

            // Basic season info
            fputcsv($file, ['Saison', $season->name], ';');
            fputcsv($file, ['Start', $season->start_date->format('d.m.Y')], ';');
            fputcsv($file, ['Ende', $season->end_date->format('d.m.Y')], ';');
            fputcsv($file, ['Status', $season->status], ';');
            fputcsv($file, [''], ';'); // Empty row

            // Statistics
            fputcsv($file, ['Teams', $season->teams_count ?? 0], ';');
            fputcsv($file, ['Spiele', $season->games_count ?? 0], ';');
            fputcsv($file, ['Spieler', $season->players_count ?? 0], ';');

            if (isset($statistics['avg_score'])) {
                fputcsv($file, ['Durchschnittliche Punkte', number_format($statistics['avg_score'], 1, ',', '.')], ';');
            }
            if (isset($statistics['avg_assists'])) {
                fputcsv($file, ['Durchschnittliche Assists', number_format($statistics['avg_assists'], 1, ',', '.')], ';');
            }
            if (isset($statistics['avg_rebounds'])) {
                fputcsv($file, ['Durchschnittliche Rebounds', number_format($statistics['avg_rebounds'], 1, ',', '.')], ';');
            }

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
