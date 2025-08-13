<?php

namespace App\Http\Controllers\Federation;

use App\Http\Controllers\Controller;
use App\Services\Federation\DBBApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Controller for DBB (Deutscher Basketball Bund) integration
 * Handles player registration, team management, and league compliance
 */
class DBBController extends Controller
{
    private DBBApiService $dbbService;

    public function __construct(DBBApiService $dbbService)
    {
        $this->dbbService = $dbbService;
        $this->middleware('auth');
        $this->middleware('tenant');
        $this->middleware('feature:dbb_integration');
    }

    /**
     * Get player information by license number
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPlayer(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'license_number' => 'required|string|regex:/^[0-9]{8,12}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Ungültige Lizenznummer format',
                'validation_errors' => $validator->errors(),
            ], 400);
        }

        $tenant = $request->get('tenant');
        $licenseNumber = $request->input('license_number');

        try {
            $playerData = $this->dbbService->getPlayerByLicense($licenseNumber, $tenant);

            if ($playerData) {
                return response()->json([
                    'success' => true,
                    'player' => $playerData,
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Spieler mit dieser Lizenznummer nicht gefunden',
            ], 404);

        } catch (\Exception $e) {
            Log::error('DBB player lookup failed in controller', [
                'license_number' => $licenseNumber,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Fehler beim Abrufen der Spielerdaten',
            ], 500);
        }
    }

    /**
     * Validate player eligibility for league/team
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validatePlayerEligibility(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'license_number' => 'required|string|regex:/^[0-9]{8,12}$/',
            'league_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Ungültige Eingabedaten',
                'validation_errors' => $validator->errors(),
            ], 400);
        }

        $tenant = $request->get('tenant');
        $licenseNumber = $request->input('license_number');
        $leagueId = $request->input('league_id');

        try {
            $validation = $this->dbbService->validatePlayerEligibility($licenseNumber, $leagueId, $tenant);

            return response()->json([
                'success' => true,
                'validation' => $validation,
            ]);

        } catch (\Exception $e) {
            Log::error('DBB player eligibility validation failed', [
                'license_number' => $licenseNumber,
                'league_id' => $leagueId,
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
     * Get available leagues
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getLeagues(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'region' => 'nullable|string|max:10',
            'age_group' => 'nullable|string|in:U8,U10,U12,U14,U16,U18,U20,Senior',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Ungültige Filterparameter',
                'validation_errors' => $validator->errors(),
            ], 400);
        }

        $tenant = $request->get('tenant');
        $region = $request->input('region');
        $ageGroup = $request->input('age_group');

        try {
            $leagues = $this->dbbService->getLeagues($region, $ageGroup, $tenant);

            return response()->json([
                'success' => true,
                'leagues' => $leagues,
                'count' => count($leagues),
            ]);

        } catch (\Exception $e) {
            Log::error('DBB leagues lookup failed', [
                'region' => $region,
                'age_group' => $ageGroup,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Fehler beim Abrufen der Liga-Daten',
            ], 500);
        }
    }

    /**
     * Register team with DBB
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function registerTeam(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'club_id' => 'required|string',
            'league_id' => 'required|string',
            'age_group' => 'required|string|in:U8,U10,U12,U14,U16,U18,U20,Senior',
            'contact_person' => 'required|string|max:100',
            'contact_email' => 'required|email',
            'contact_phone' => 'required|string|max:20',
            'home_venue' => 'required|string|max:200',
            'logo_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Ungültige Team-Daten',
                'validation_errors' => $validator->errors(),
            ], 400);
        }

        $tenant = $request->get('tenant');
        $teamData = $request->only([
            'name', 'club_id', 'league_id', 'age_group',
            'contact_person', 'contact_email', 'contact_phone',
            'home_venue', 'logo_url'
        ]);

        try {
            $registration = $this->dbbService->registerTeam($teamData, $tenant);

            if ($registration['success']) {
                Log::info('Team successfully registered with DBB', [
                    'tenant_id' => $tenant->id,
                    'team_name' => $teamData['name'],
                    'dbb_team_id' => $registration['dbb_team_id'],
                ]);

                return response()->json([
                    'success' => true,
                    'registration' => $registration,
                    'message' => 'Team erfolgreich beim DBB registriert',
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $registration['error'],
                'validation_errors' => $registration['validation_errors'] ?? [],
            ], 400);

        } catch (\Exception $e) {
            Log::error('DBB team registration failed in controller', [
                'tenant_id' => $tenant->id,
                'team_name' => $teamData['name'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Fehler bei der Team-Registrierung',
            ], 500);
        }
    }

    /**
     * Submit game result to DBB
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function submitGameResult(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'dbb_game_id' => 'required|string',
            'home_score' => 'required|integer|min:0',
            'away_score' => 'required|integer|min:0',
            'played_at' => 'required|date',
            'referee_signature' => 'nullable|string',
            'home_roster' => 'nullable|array',
            'away_roster' => 'nullable|array',
            'player_stats' => 'nullable|array',
            'technical_fouls' => 'nullable|array',
            'unsportsmanlike_fouls' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Ungültige Spiel-Daten',
                'validation_errors' => $validator->errors(),
            ], 400);
        }

        $tenant = $request->get('tenant');
        $gameData = $request->only([
            'dbb_game_id', 'home_score', 'away_score', 'played_at',
            'referee_signature', 'home_roster', 'away_roster',
            'player_stats', 'technical_fouls', 'unsportsmanlike_fouls', 'notes'
        ]);

        try {
            $submission = $this->dbbService->submitGameResult($gameData, $tenant);

            if ($submission['success']) {
                return response()->json([
                    'success' => true,
                    'submission' => $submission,
                    'message' => 'Spielergebnis erfolgreich an DBB übermittelt',
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $submission['error'],
                'validation_errors' => $submission['validation_errors'] ?? [],
            ], 400);

        } catch (\Exception $e) {
            Log::error('DBB game result submission failed', [
                'tenant_id' => $tenant->id,
                'game_id' => $gameData['dbb_game_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Fehler bei der Ergebnis-Übermittlung',
            ], 500);
        }
    }

    /**
     * Get player transfer status
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPlayerTransferStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'license_number' => 'required|string|regex:/^[0-9]{8,12}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Ungültige Lizenznummer',
                'validation_errors' => $validator->errors(),
            ], 400);
        }

        $tenant = $request->get('tenant');
        $licenseNumber = $request->input('license_number');

        try {
            $transferStatus = $this->dbbService->getPlayerTransferStatus($licenseNumber, $tenant);

            if ($transferStatus) {
                return response()->json([
                    'success' => true,
                    'transfer_status' => $transferStatus,
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Transfer-Status nicht verfügbar',
            ], 404);

        } catch (\Exception $e) {
            Log::error('DBB transfer status lookup failed', [
                'license_number' => $licenseNumber,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Fehler beim Abrufen des Transfer-Status',
            ], 500);
        }
    }

    /**
     * Get referee assignments
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRefereeAssignments(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'league_id' => 'required|string',
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Ungültige Parameter',
                'validation_errors' => $validator->errors(),
            ], 400);
        }

        $tenant = $request->get('tenant');
        $leagueId = $request->input('league_id');
        $date = $request->input('date');

        try {
            $assignments = $this->dbbService->getRefereeAssignments($leagueId, $date, $tenant);

            return response()->json([
                'success' => true,
                'assignments' => $assignments,
                'count' => count($assignments),
            ]);

        } catch (\Exception $e) {
            Log::error('DBB referee assignments lookup failed', [
                'league_id' => $leagueId,
                'date' => $date,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Fehler beim Abrufen der Schiedsrichter-Einteilungen',
            ], 500);
        }
    }

    /**
     * Get DBB API status
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getApiStatus(Request $request): JsonResponse
    {
        try {
            $status = $this->dbbService->getApiStatus();
            $available = $this->dbbService->isAvailable();

            return response()->json([
                'success' => true,
                'available' => $available,
                'status' => $status,
            ]);

        } catch (\Exception $e) {
            Log::error('DBB API status check failed', [
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
     * Test DBB API connection
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function testConnection(Request $request): JsonResponse
    {
        $tenant = $request->get('tenant');

        try {
            // Try to get leagues as a connection test
            $leagues = $this->dbbService->getLeagues(null, null, $tenant);
            $available = $this->dbbService->isAvailable();

            Log::info('DBB API connection test completed', [
                'tenant_id' => $tenant->id,
                'available' => $available,
                'leagues_count' => count($leagues),
            ]);

            return response()->json([
                'success' => true,
                'available' => $available,
                'connection_status' => $available ? 'connected' : 'disconnected',
                'test_data' => [
                    'leagues_available' => count($leagues),
                    'api_responsive' => $available,
                ],
                'message' => $available ? 
                    'DBB API Verbindung erfolgreich' : 
                    'DBB API nicht erreichbar',
            ]);

        } catch (\Exception $e) {
            Log::error('DBB API connection test failed', [
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