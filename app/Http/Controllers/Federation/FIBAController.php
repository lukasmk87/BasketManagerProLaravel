<?php

namespace App\Http\Controllers\Federation;

use App\Http\Controllers\Controller;
use App\Services\Federation\FIBAApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Controller for FIBA Europe integration
 * Handles international player data, competitions, and European basketball compliance
 */
class FIBAController extends Controller
{
    private FIBAApiService $fibaService;

    public function __construct(FIBAApiService $fibaService)
    {
        $this->fibaService = $fibaService;
        $this->middleware('auth');
        $this->middleware('tenant');
        $this->middleware('feature:fiba_integration');
    }

    /**
     * Get player international profile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPlayerProfile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fiba_id' => 'required|string|regex:/^[A-Z0-9]{6,12}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Ungültige FIBA ID Format',
                'validation_errors' => $validator->errors(),
            ], 400);
        }

        $tenant = $request->get('tenant');
        $fibaId = $request->input('fiba_id');

        try {
            $playerProfile = $this->fibaService->getPlayerProfile($fibaId, $tenant);

            if ($playerProfile) {
                return response()->json([
                    'success' => true,
                    'player' => $playerProfile,
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Spieler mit dieser FIBA ID nicht gefunden',
            ], 404);

        } catch (\Exception $e) {
            Log::error('FIBA player profile lookup failed', [
                'fiba_id' => $fibaId,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Fehler beim Abrufen des Spielerprofils',
            ], 500);
        }
    }

    /**
     * Search players by criteria
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchPlayers(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|min:2|max:100',
            'nationality' => 'nullable|string|size:3', // ISO 3-letter code
            'birth_year' => 'nullable|integer|min:1950|max:' . (date('Y') - 10),
            'position' => 'nullable|string|in:PG,SG,SF,PF,C,G,F',
            'club' => 'nullable|string|max:100',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Ungültige Suchkriterien',
                'validation_errors' => $validator->errors(),
            ], 400);
        }

        $tenant = $request->get('tenant');
        $criteria = $request->only(['name', 'nationality', 'birth_year', 'position', 'club', 'limit']);

        try {
            $players = $this->fibaService->searchPlayers($criteria, $tenant);

            return response()->json([
                'success' => true,
                'players' => $players,
                'count' => count($players),
                'criteria' => $criteria,
            ]);

        } catch (\Exception $e) {
            Log::error('FIBA player search failed', [
                'criteria' => $criteria,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Fehler bei der Spielersuche',
            ], 500);
        }
    }

    /**
     * Get player eligibility for international competitions
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPlayerEligibility(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fiba_id' => 'required|string|regex:/^[A-Z0-9]{6,12}$/',
            'competition_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Ungültige Eingabedaten',
                'validation_errors' => $validator->errors(),
            ], 400);
        }

        $tenant = $request->get('tenant');
        $fibaId = $request->input('fiba_id');
        $competitionId = $request->input('competition_id');

        try {
            $eligibility = $this->fibaService->getPlayerEligibility($fibaId, $competitionId, $tenant);

            return response()->json([
                'success' => true,
                'eligibility' => $eligibility,
            ]);

        } catch (\Exception $e) {
            Log::error('FIBA player eligibility check failed', [
                'fiba_id' => $fibaId,
                'competition_id' => $competitionId,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Fehler bei der Spielberechtigung-Prüfung',
            ], 500);
        }
    }

    /**
     * Get European competitions and tournaments
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCompetitions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category' => 'nullable|string|in:men,women,youth',
            'level' => 'nullable|string|in:professional,amateur,youth',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Ungültige Filterparameter',
                'validation_errors' => $validator->errors(),
            ], 400);
        }

        $tenant = $request->get('tenant');
        $category = $request->input('category');
        $level = $request->input('level');

        try {
            $competitions = $this->fibaService->getCompetitions($category, $level, $tenant);

            return response()->json([
                'success' => true,
                'competitions' => $competitions,
                'count' => count($competitions),
            ]);

        } catch (\Exception $e) {
            Log::error('FIBA competitions lookup failed', [
                'category' => $category,
                'level' => $level,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Fehler beim Abrufen der Wettbewerb-Daten',
            ], 500);
        }
    }

    /**
     * Get club information and registration status
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getClubInfo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'club_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Ungültige Club ID',
                'validation_errors' => $validator->errors(),
            ], 400);
        }

        $tenant = $request->get('tenant');
        $clubId = $request->input('club_id');

        try {
            $clubInfo = $this->fibaService->getClubInfo($clubId, $tenant);

            if ($clubInfo) {
                return response()->json([
                    'success' => true,
                    'club' => $clubInfo,
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Verein mit dieser ID nicht gefunden',
            ], 404);

        } catch (\Exception $e) {
            Log::error('FIBA club info lookup failed', [
                'club_id' => $clubId,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Fehler beim Abrufen der Vereinsdaten',
            ], 500);
        }
    }

    /**
     * Register team for European competition
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function registerTeamForCompetition(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'competition_id' => 'required|string',
            'club_id' => 'required|string',
            'name' => 'required|string|max:100',
            'category' => 'required|string|in:men,women,youth',
            'age_group' => 'nullable|string|max:10',
            'coach_name' => 'required|string|max:100',
            'coach_license' => 'nullable|string|max:50',
            'coach_nationality' => 'nullable|string|size:3',
            'home_venue' => 'required|string|max:200',
            'contact_person' => 'required|string|max:100',
            'contact_email' => 'required|email',
            'roster' => 'nullable|array',
            'roster.*.fiba_id' => 'required|string',
            'roster.*.name' => 'required|string',
            'roster.*.position' => 'required|string|in:PG,SG,SF,PF,C,G,F',
            'roster.*.jersey_number' => 'required|integer|min:0|max:99',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Ungültige Team-Daten',
                'validation_errors' => $validator->errors(),
            ], 400);
        }

        $tenant = $request->get('tenant');
        $competitionId = $request->input('competition_id');
        $teamData = $request->only([
            'club_id', 'name', 'category', 'age_group',
            'coach_name', 'coach_license', 'coach_nationality',
            'home_venue', 'contact_person', 'contact_email', 'roster'
        ]);

        try {
            $registration = $this->fibaService->registerTeamForCompetition($teamData, $competitionId, $tenant);

            if ($registration['success']) {
                Log::info('Team successfully registered for FIBA competition', [
                    'tenant_id' => $tenant->id,
                    'team_name' => $teamData['name'],
                    'competition_id' => $competitionId,
                    'fiba_team_id' => $registration['fiba_team_id'],
                ]);

                return response()->json([
                    'success' => true,
                    'registration' => $registration,
                    'message' => 'Team erfolgreich für FIBA Wettbewerb registriert',
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $registration['error'],
                'validation_errors' => $registration['validation_errors'] ?? [],
            ], 400);

        } catch (\Exception $e) {
            Log::error('FIBA team registration failed', [
                'tenant_id' => $tenant->id,
                'team_name' => $teamData['name'],
                'competition_id' => $competitionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Fehler bei der Team-Registrierung',
            ], 500);
        }
    }

    /**
     * Get official game data and statistics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getOfficialGameData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'game_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Ungültige Spiel ID',
                'validation_errors' => $validator->errors(),
            ], 400);
        }

        $tenant = $request->get('tenant');
        $gameId = $request->input('game_id');

        try {
            $gameData = $this->fibaService->getOfficialGameData($gameId, $tenant);

            if ($gameData) {
                return response()->json([
                    'success' => true,
                    'game' => $gameData,
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Spiel mit dieser ID nicht gefunden',
            ], 404);

        } catch (\Exception $e) {
            Log::error('FIBA official game data lookup failed', [
                'game_id' => $gameId,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Fehler beim Abrufen der offiziellen Spieldaten',
            ], 500);
        }
    }

    /**
     * Get referee information and certifications
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRefereeInfo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'referee_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Ungültige Schiedsrichter ID',
                'validation_errors' => $validator->errors(),
            ], 400);
        }

        $tenant = $request->get('tenant');
        $refereeId = $request->input('referee_id');

        try {
            $refereeInfo = $this->fibaService->getRefereeInfo($refereeId, $tenant);

            if ($refereeInfo) {
                return response()->json([
                    'success' => true,
                    'referee' => $refereeInfo,
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Schiedsrichter mit dieser ID nicht gefunden',
            ], 404);

        } catch (\Exception $e) {
            Log::error('FIBA referee info lookup failed', [
                'referee_id' => $refereeId,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Fehler beim Abrufen der Schiedsrichter-Daten',
            ], 500);
        }
    }

    /**
     * Get competition standings and rankings
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCompetitionStandings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'competition_id' => 'required|string',
            'phase' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Ungültige Parameter',
                'validation_errors' => $validator->errors(),
            ], 400);
        }

        $tenant = $request->get('tenant');
        $competitionId = $request->input('competition_id');
        $phase = $request->input('phase');

        try {
            $standings = $this->fibaService->getCompetitionStandings($competitionId, $phase, $tenant);

            return response()->json([
                'success' => true,
                'standings' => $standings,
                'competition_id' => $competitionId,
                'phase' => $phase,
            ]);

        } catch (\Exception $e) {
            Log::error('FIBA competition standings lookup failed', [
                'competition_id' => $competitionId,
                'phase' => $phase,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Fehler beim Abrufen der Tabellenstände',
            ], 500);
        }
    }

    /**
     * Get FIBA API status
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getApiStatus(Request $request): JsonResponse
    {
        try {
            $status = $this->fibaService->getApiStatus();
            $available = $this->fibaService->isAvailable();

            return response()->json([
                'success' => true,
                'available' => $available,
                'status' => $status,
            ]);

        } catch (\Exception $e) {
            Log::error('FIBA API status check failed', [
                'tenant_id' => $request->get('tenant')->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'available' => false,
                'error' => 'Status-Prüfung fehlgeschlagen',
            ], 500);
        }
    }

    /**
     * Test FIBA API connection
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function testConnection(Request $request): JsonResponse
    {
        $tenant = $request->get('tenant');

        try {
            // Try to get competitions as a connection test
            $competitions = $this->fibaService->getCompetitions(null, null, $tenant);
            $available = $this->fibaService->isAvailable();

            Log::info('FIBA API connection test completed', [
                'tenant_id' => $tenant->id,
                'available' => $available,
                'competitions_count' => count($competitions),
            ]);

            return response()->json([
                'success' => true,
                'available' => $available,
                'connection_status' => $available ? 'connected' : 'disconnected',
                'test_data' => [
                    'competitions_available' => count($competitions),
                    'api_responsive' => $available,
                ],
                'message' => $available ? 
                    'FIBA API Verbindung erfolgreich' : 
                    'FIBA API nicht erreichbar',
            ]);

        } catch (\Exception $e) {
            Log::error('FIBA API connection test failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'available' => false,
                'connection_status' => 'error',
                'error' => 'Verbindungstest fehlgeschlagen',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}