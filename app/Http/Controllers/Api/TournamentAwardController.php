<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\TournamentAward;
use App\Services\TournamentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TournamentAwardController extends Controller
{
    protected TournamentService $tournamentService;

    public function __construct(TournamentService $tournamentService)
    {
        $this->tournamentService = $tournamentService;
    }

    /**
     * Get all awards for a tournament.
     */
    public function index(Tournament $tournament, Request $request): JsonResponse
    {
        $query = $tournament->awards()->with(['recipientTeam.team', 'recipientPlayer', 'recipientCoach', 'selectedBy']);

        // Filters
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        if ($request->has('presented')) {
            $query->where('presented', $request->boolean('presented'));
        }

        if ($request->has('featured')) {
            $query->featured();
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'selected_at');
        $sortOrder = $request->get('sort_order', 'desc');

        if (in_array($sortBy, ['selected_at', 'presentation_date', 'award_name', 'statistical_value'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $awards = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $awards->items(),
            'meta' => [
                'current_page' => $awards->currentPage(),
                'last_page' => $awards->lastPage(),
                'per_page' => $awards->perPage(),
                'total' => $awards->total(),
            ],
        ]);
    }

    /**
     * Create a new tournament award.
     */
    public function store(Tournament $tournament, Request $request): JsonResponse
    {
        try {
            $this->authorize('update', $tournament);

            $validated = $request->validate([
                'award_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'award_type' => 'required|in:team_award,individual_award,special_recognition,statistical_award,sportsmanship_award',
                'award_category' => 'required|string|max:100',
                'criteria' => 'nullable|array',
                'award_format' => 'nullable|in:trophy,medal,certificate,plaque,other',
                'award_sponsor' => 'nullable|string|max:255',
                'award_value' => 'nullable|numeric|min:0',
            ]);

            $award = $tournament->awards()->create($validated);

            return response()->json([
                'message' => 'Auszeichnung erfolgreich erstellt',
                'data' => $award->load(['recipientTeam', 'recipientPlayer', 'recipientCoach', 'selectedBy']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Erstellen der Auszeichnung',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get specific tournament award.
     */
    public function show(Tournament $tournament, TournamentAward $tournamentAward): JsonResponse
    {
        if ($tournamentAward->tournament_id !== $tournament->id) {
            return response()->json(['message' => 'Auszeichnung gehört nicht zu diesem Turnier'], 404);
        }

        return response()->json([
            'data' => $tournamentAward->load(['recipientTeam.team', 'recipientPlayer', 'recipientCoach', 'selectedBy']),
        ]);
    }

    /**
     * Update tournament award.
     */
    public function update(Tournament $tournament, TournamentAward $tournamentAward, Request $request): JsonResponse
    {
        try {
            if ($tournamentAward->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Auszeichnung gehört nicht zu diesem Turnier'], 404);
            }

            $this->authorize('update', $tournament);

            $validated = $request->validate([
                'award_name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'award_type' => 'sometimes|in:team_award,individual_award,special_recognition,statistical_award,sportsmanship_award',
                'award_category' => 'sometimes|string|max:100',
                'criteria' => 'nullable|array',
                'award_format' => 'nullable|in:trophy,medal,certificate,plaque,other',
                'award_sponsor' => 'nullable|string|max:255',
                'award_value' => 'nullable|numeric|min:0',
                'engraving_text' => 'nullable|string',
                'press_release' => 'nullable|string',
            ]);

            $tournamentAward->update($validated);

            return response()->json([
                'message' => 'Auszeichnung erfolgreich aktualisiert',
                'data' => $tournamentAward->load(['recipientTeam', 'recipientPlayer', 'recipientCoach', 'selectedBy']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Aktualisieren der Auszeichnung',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete tournament award.
     */
    public function destroy(Tournament $tournament, TournamentAward $tournamentAward): JsonResponse
    {
        try {
            if ($tournamentAward->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Auszeichnung gehört nicht zu diesem Turnier'], 404);
            }

            $this->authorize('update', $tournament);

            $tournamentAward->delete();

            return response()->json([
                'message' => 'Auszeichnung erfolgreich gelöscht',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Löschen der Auszeichnung',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Assign award to recipient.
     */
    public function assign(Tournament $tournament, TournamentAward $tournamentAward, Request $request): JsonResponse
    {
        try {
            if ($tournamentAward->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Auszeichnung gehört nicht zu diesem Turnier'], 404);
            }

            $this->authorize('update', $tournament);

            $validated = $request->validate([
                'recipient_team_id' => 'nullable|exists:tournament_teams,id',
                'recipient_player_id' => 'nullable|exists:users,id',
                'recipient_coach_id' => 'nullable|exists:users,id',
                'recipient_name' => 'nullable|string|max:255',
                'statistics' => 'nullable|array',
                'statistical_value' => 'nullable|numeric',
                'statistical_unit' => 'nullable|string|max:50',
            ]);

            $team = $validated['recipient_team_id'] ? \App\Models\TournamentTeam::find($validated['recipient_team_id']) : null;
            $player = $validated['recipient_player_id'] ? \App\Models\User::find($validated['recipient_player_id']) : null;
            $coach = $validated['recipient_coach_id'] ? \App\Models\User::find($validated['recipient_coach_id']) : null;

            $tournamentAward->selectRecipient($team, $player, $coach, $validated['recipient_name'] ?? null, $request->user());

            if (isset($validated['statistics'])) {
                $tournamentAward->recordStatistics(
                    $validated['statistics'],
                    $validated['statistical_value'] ?? null,
                    $validated['statistical_unit'] ?? null
                );
            }

            return response()->json([
                'message' => 'Empfänger erfolgreich zugewiesen',
                'data' => $tournamentAward->fresh(['recipientTeam', 'recipientPlayer', 'recipientCoach', 'selectedBy']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Zuweisen des Empfängers',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Mark award as presented.
     */
    public function present(Tournament $tournament, TournamentAward $tournamentAward, Request $request): JsonResponse
    {
        try {
            if ($tournamentAward->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Auszeichnung gehört nicht zu diesem Turnier'], 404);
            }

            $this->authorize('update', $tournament);

            if (!$tournamentAward->canBePresented()) {
                return response()->json(['message' => 'Auszeichnung kann nicht übergeben werden (kein Empfänger festgelegt)'], 422);
            }

            $validated = $request->validate([
                'presentation_date' => 'nullable|date',
                'presentation_ceremony' => 'nullable|string|max:255',
                'presentation_notes' => 'nullable|string',
            ]);

            $tournamentAward->present(
                isset($validated['presentation_date']) ? new \DateTime($validated['presentation_date']) : null,
                $validated['presentation_ceremony'] ?? null,
                $validated['presentation_notes'] ?? null
            );

            return response()->json([
                'message' => 'Auszeichnung als übergeben markiert',
                'data' => $tournamentAward->fresh(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Markieren der Übergabe',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Feature award on website.
     */
    public function feature(Tournament $tournament, TournamentAward $tournamentAward): JsonResponse
    {
        try {
            if ($tournamentAward->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Auszeichnung gehört nicht zu diesem Turnier'], 404);
            }

            $this->authorize('update', $tournament);

            $tournamentAward->featureOnWebsite();

            return response()->json([
                'message' => 'Auszeichnung hervorgehoben',
                'data' => $tournamentAward->fresh(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Hervorheben der Auszeichnung',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove feature from award.
     */
    public function unfeature(Tournament $tournament, TournamentAward $tournamentAward): JsonResponse
    {
        try {
            if ($tournamentAward->tournament_id !== $tournament->id) {
                return response()->json(['message' => 'Auszeichnung gehört nicht zu diesem Turnier'], 404);
            }

            $this->authorize('update', $tournament);

            $tournamentAward->unfeatured();

            return response()->json([
                'message' => 'Auszeichnung nicht mehr hervorgehoben',
                'data' => $tournamentAward->fresh(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Entfernen der Hervorhebung',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get available award categories.
     */
    public function availableCategories(): JsonResponse
    {
        return response()->json([
            'data' => [
                'champion' => 'Meister',
                'runner_up' => 'Zweiter Platz',
                'third_place' => 'Dritter Platz',
                'mvp' => 'Wertvollster Spieler (MVP)',
                'best_player' => 'Bester Spieler',
                'top_scorer' => 'Topscorer',
                'best_defense' => 'Beste Verteidigung',
                'most_rebounds' => 'Meiste Rebounds',
                'most_assists' => 'Meiste Assists',
                'most_steals' => 'Meiste Steals',
                'most_blocks' => 'Meiste Blocks',
                'best_three_point_shooter' => 'Bester Dreipunkte-Schütze',
                'best_free_throw' => 'Bester Freiwurf-Schütze',
                'best_coach' => 'Bester Trainer',
                'sportsmanship' => 'Fair Play',
                'most_improved' => 'Stärkste Verbesserung',
                'rookie_of_tournament' => 'Newcomer des Turniers',
                'all_tournament_team' => 'All-Tournament-Team',
            ],
        ]);
    }

    /**
     * Get award templates.
     */
    public function templates(): JsonResponse
    {
        return response()->json([
            'data' => [
                [
                    'name' => 'Tournament Champion',
                    'award_type' => 'team_award',
                    'award_category' => 'champion',
                    'award_format' => 'trophy',
                    'description' => 'Auszeichnung für den Turniersieger',
                ],
                [
                    'name' => 'Tournament MVP',
                    'award_type' => 'individual_award',
                    'award_category' => 'mvp',
                    'award_format' => 'trophy',
                    'description' => 'Wertvollster Spieler des Turniers',
                ],
                [
                    'name' => 'Top Scorer',
                    'award_type' => 'statistical_award',
                    'award_category' => 'top_scorer',
                    'award_format' => 'medal',
                    'description' => 'Bester Scorer des Turniers',
                ],
                [
                    'name' => 'Fair Play Award',
                    'award_type' => 'sportsmanship_award',
                    'award_category' => 'sportsmanship',
                    'award_format' => 'certificate',
                    'description' => 'Auszeichnung für faires Spiel',
                ],
            ],
        ]);
    }

    /**
     * Automatically generate awards based on tournament statistics.
     */
    public function generateAutomatic(Tournament $tournament): JsonResponse
    {
        try {
            $this->authorize('update', $tournament);

            // TODO: Implement automatic award generation based on tournament statistics
            // This would analyze tournament data and create awards automatically

            return response()->json([
                'message' => 'Automatische Auszeichnungen werden generiert (in Entwicklung)',
                'data' => [],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Generieren automatischer Auszeichnungen',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
