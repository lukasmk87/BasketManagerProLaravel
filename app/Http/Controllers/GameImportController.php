<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Club;
use App\Services\ICalImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class GameImportController extends Controller
{
    public function __construct(
        private ICalImportService $importService
    ) {}

    /**
     * Show the import form.
     */
    public function index(): Response
    {
        $user = Auth::user();
        
        // Get available teams based on user role
        $teams = collect();
        
        if ($user->hasRole(['super_admin', 'admin'])) {
            // Admins see all teams
            $teams = Team::with('club')->where('is_active', true)->get();
        } elseif ($user->hasRole('club_admin')) {
            // Club admins see only their club's teams
            $clubIds = $user->clubs()->pluck('clubs.id');
            $teams = Team::with('club')
                ->whereIn('club_id', $clubIds)
                ->where('is_active', true)
                ->get();
        } elseif ($user->hasRole(['trainer', 'head_coach'])) {
            // Trainers see only their teams
            $teams = Team::with('club')
                ->where('is_active', true)
                ->where(function ($query) use ($user) {
                    $query->where('head_coach_id', $user->id)
                          ->orWhereJsonContains('assistant_coaches', $user->id);
                })
                ->get();
        }

        return Inertia::render('Games/Import/Index', [
            'teams' => $teams,
            'canImportForAllTeams' => $user->hasRole(['super_admin', 'admin', 'club_admin']),
        ]);
    }

    /**
     * Analyze iCAL file and extract teams for mapping.
     */
    public function analyzeICal(Request $request)
    {
        $request->validate([
            'ical_file' => 'required|file|mimes:ics|max:2048',
            'team_id' => 'required|exists:teams,id',
        ]);

        try {
            $user = Auth::user();
            $team = Team::findOrFail($request->team_id);

            // Check permissions
            $this->checkTeamAccess($user, $team);

            // Read and parse the iCAL file
            $file = $request->file('ical_file');
            $icalContent = file_get_contents($file->getRealPath());
            
            $parsedGames = $this->importService->parseICalFile($icalContent);
            
            if ($parsedGames->isEmpty()) {
                return back()->withErrors(['ical_file' => 'Keine Spiele in der iCAL-Datei gefunden.']);
            }

            // Extract all teams from the iCAL
            $icalTeams = $this->importService->getTeamsFromGames($parsedGames);
            
            if ($icalTeams->isEmpty()) {
                return back()->withErrors(['ical_file' => 'Keine Teams in der iCAL-Datei gefunden.']);
            }

            // Get available teams for mapping
            $availableTeams = $this->getAvailableTeams($user);

            // Store parsed data in session for later import
            session([
                'parsed_games_' . $team->id => $parsedGames->toArray(),
                'import_file_name' => $file->getClientOriginalName(),
                'selected_team_id' => $team->id,
            ]);

            return Inertia::render('Games/Import/TeamMapping', [
                'selectedTeam' => $team->load('club'),
                'icalTeams' => $icalTeams,
                'availableTeams' => $availableTeams,
                'fileName' => $file->getClientOriginalName(),
                'totalGames' => $parsedGames->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('iCAL analyze error', [
                'user_id' => Auth::id(),
                'team_id' => $request->team_id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['ical_file' => 'Fehler beim Verarbeiten der iCAL-Datei: ' . $e->getMessage()]);
        }
    }

    /**
     * Save team mapping and show preview.
     */
    public function mapTeams(Request $request)
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id',
            'team_mapping' => 'required|array',
            'team_mapping.*' => 'nullable|exists:teams,id',
        ]);

        try {
            $user = Auth::user();
            $team = Team::findOrFail($request->team_id);

            // Check permissions
            $this->checkTeamAccess($user, $team);

            // Get parsed games from session
            $sessionKey = 'parsed_games_' . $team->id;
            $parsedGamesArray = session($sessionKey);
            
            if (!$parsedGamesArray) {
                return back()->withErrors(['general' => 'Session abgelaufen. Bitte laden Sie die Datei erneut hoch.']);
            }

            $parsedGames = collect($parsedGamesArray);
            $teamMapping = array_filter($request->team_mapping, function($value) {
                return $value !== null;
            });

            // Generate preview with team mapping
            $preview = $this->importService->previewGamesWithTeamMapping($parsedGames, $teamMapping, $team->id);
            
            if ($preview->isEmpty()) {
                return back()->withErrors(['general' => 'Keine importierbaren Spiele für das ausgewählte Team mit der gewählten Zuordnung gefunden.']);
            }

            // Store team mapping in session
            session([
                'team_mapping_' . $team->id => $teamMapping,
            ]);

            return Inertia::render('Games/Import/Preview', [
                'team' => $team->load('club'),
                'preview' => $preview,
                'fileName' => session('import_file_name'),
                'totalGames' => $parsedGames->count(),
                'matchingGames' => $preview->count(),
                'newGames' => $preview->where('can_import', true)->count(),
                'existingGames' => $preview->where('already_exists', true)->count(),
                'usingTeamMapping' => true,
            ]);

        } catch (\Exception $e) {
            Log::error('Team mapping error', [
                'user_id' => Auth::id(),
                'team_id' => $request->team_id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['general' => 'Fehler beim Verarbeiten der Team-Zuordnung: ' . $e->getMessage()]);
        }
    }

    /**
     * Upload and preview iCAL file (legacy method for backward compatibility).
     */
    public function previewICal(Request $request)
    {
        // Redirect to the new analyze flow
        return $this->analyzeICal($request);
    }

    /**
     * Import the games after preview confirmation.
     */
    public function importICal(Request $request)
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id',
            'confirmed' => 'required|boolean',
        ]);

        if (!$request->confirmed) {
            return back()->withErrors(['confirmed' => 'Import muss bestätigt werden.']);
        }

        try {
            $user = Auth::user();
            $team = Team::findOrFail($request->team_id);

            // Check permissions
            $this->checkTeamAccess($user, $team);

            // Get parsed games from session
            $sessionKey = 'parsed_games_' . $team->id;
            $parsedGamesArray = session($sessionKey);
            
            if (!$parsedGamesArray) {
                return back()->withErrors(['general' => 'Session abgelaufen. Bitte laden Sie die Datei erneut hoch.']);
            }

            $parsedGames = collect($parsedGamesArray);

            // Check if we have team mapping or use legacy method
            $teamMappingKey = 'team_mapping_' . $team->id;
            $teamMapping = session($teamMappingKey);

            if ($teamMapping) {
                // Use new team mapping method
                $result = $this->importService->importGamesWithTeamMapping($parsedGames, $teamMapping, $team->id);
            } else {
                // Fallback to legacy method
                $result = $this->importService->importGamesForTeam($parsedGames, $team);
            }

            // Clear session data
            session()->forget([$sessionKey, 'import_file_name', $teamMappingKey, 'selected_team_id']);

            $message = "Import abgeschlossen: {$result['imported']} Spiele importiert";
            if ($result['skipped'] > 0) {
                $message .= ", {$result['skipped']} übersprungen";
            }
            if (!empty($result['errors'])) {
                $message .= " (mit Fehlern)";
            }

            return redirect()->route('games.import.index')
                ->with('success', $message)
                ->with('importResult', $result);

        } catch (\Exception $e) {
            Log::error('iCAL import error', [
                'user_id' => Auth::id(),
                'team_id' => $request->team_id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['general' => 'Fehler beim Import: ' . $e->getMessage()]);
        }
    }

    /**
     * Show team selection for club admins.
     */
    public function selectTeam(): Response
    {
        $user = Auth::user();
        
        if (!$user->hasRole('club_admin')) {
            abort(403, 'Nur Club-Administratoren können Teams auswählen.');
        }

        $clubIds = $user->clubs()->pluck('clubs.id');
        $teams = Team::with('club')
            ->whereIn('club_id', $clubIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Games/Import/SelectTeam', [
            'teams' => $teams,
        ]);
    }

    /**
     * Quick import for specific team (trainer workflow).
     */
    public function quickImport(Request $request, Team $team)
    {
        $user = Auth::user();
        $this->checkTeamAccess($user, $team);

        $request->validate([
            'ical_file' => 'required|file|mimes:ics|max:2048',
        ]);

        try {
            // Read and parse the iCAL file
            $file = $request->file('ical_file');
            $icalContent = file_get_contents($file->getRealPath());
            
            $parsedGames = $this->importService->parseICalFile($icalContent);
            
            if ($parsedGames->isEmpty()) {
                return back()->withErrors(['ical_file' => 'Keine Spiele in der iCAL-Datei gefunden.']);
            }

            // Direct import without preview for trainers
            $result = $this->importService->importGamesForTeam($parsedGames, $team);

            $message = "Import abgeschlossen: {$result['imported']} Spiele importiert";
            if ($result['skipped'] > 0) {
                $message .= ", {$result['skipped']} übersprungen";
            }

            return back()
                ->with('success', $message)
                ->with('importResult', $result);

        } catch (\Exception $e) {
            Log::error('Quick iCAL import error', [
                'user_id' => Auth::id(),
                'team_id' => $team->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['ical_file' => 'Fehler beim Import: ' . $e->getMessage()]);
        }
    }

    /**
     * Show import history for a team.
     */
    public function history(Request $request): Response
    {
        $user = Auth::user();
        
        $query = \App\Models\Game::query()
            ->with(['homeTeam.club', 'awayTeam.club'])
            ->where('import_source', '!=', 'manual');

        // Filter by user permissions
        if (!$user->hasRole(['super_admin', 'admin'])) {
            if ($user->hasRole('club_admin')) {
                $clubIds = $user->clubs()->pluck('clubs.id');
                $query->whereHas('homeTeam', function ($q) use ($clubIds) {
                    $q->whereIn('club_id', $clubIds);
                })->orWhereHas('awayTeam', function ($q) use ($clubIds) {
                    $q->whereIn('club_id', $clubIds);
                });
            } else {
                // Trainers
                $query->where(function ($q) use ($user) {
                    $q->whereHas('homeTeam', function ($subQ) use ($user) {
                        $subQ->where('head_coach_id', $user->id)
                             ->orWhereJsonContains('assistant_coaches', $user->id);
                    })->orWhereHas('awayTeam', function ($subQ) use ($user) {
                        $subQ->where('head_coach_id', $user->id)
                             ->orWhereJsonContains('assistant_coaches', $user->id);
                    });
                });
            }
        }

        // Apply filters
        if ($request->filled('team_id')) {
            $query->forTeam($request->team_id);
        }

        if ($request->filled('import_source')) {
            $query->fromSource($request->import_source);
        }

        if ($request->filled('date_from')) {
            $query->where('scheduled_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('scheduled_at', '<=', $request->date_to);
        }

        $importedGames = $query->orderBy('scheduled_at', 'desc')->paginate(25);

        // Get teams for filter dropdown
        $teams = collect();
        if ($user->hasRole(['super_admin', 'admin'])) {
            $teams = Team::with('club')->where('is_active', true)->get();
        } elseif ($user->hasRole('club_admin')) {
            $clubIds = $user->clubs()->pluck('clubs.id');
            $teams = Team::with('club')->whereIn('club_id', $clubIds)->where('is_active', true)->get();
        } else {
            $teams = Team::with('club')
                ->where('is_active', true)
                ->where(function ($query) use ($user) {
                    $query->where('head_coach_id', $user->id)
                          ->orWhereJsonContains('assistant_coaches', $user->id);
                })
                ->get();
        }

        return Inertia::render('Games/Import/History', [
            'importedGames' => $importedGames,
            'teams' => $teams,
            'filters' => $request->only(['team_id', 'import_source', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Cancel/clear current import session.
     */
    public function cancel(Request $request)
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id',
        ]);

        $team = Team::findOrFail($request->team_id);
        $sessionKey = 'parsed_games_' . $team->id;
        
        session()->forget([$sessionKey, 'import_file_name']);

        return redirect()->route('games.import.index')
            ->with('info', 'Import abgebrochen.');
    }

    /**
     * Check if user has access to import for the specified team.
     */
    private function checkTeamAccess($user, Team $team): void
    {
        if ($user->hasRole(['super_admin', 'admin'])) {
            return; // Full access
        }

        if ($user->hasRole('club_admin')) {
            $clubIds = $user->clubs()->pluck('clubs.id');
            if (!$clubIds->contains($team->club_id)) {
                abort(403, 'Keine Berechtigung für dieses Team.');
            }
            return;
        }

        if ($user->hasRole(['trainer', 'head_coach'])) {
            if ($team->head_coach_id !== $user->id && 
                !in_array($user->id, $team->assistant_coaches ?? [])) {
                abort(403, 'Keine Berechtigung für dieses Team.');
            }
            return;
        }

        abort(403, 'Keine Import-Berechtigung.');
    }

    /**
     * Get available teams for the user based on their role.
     */
    private function getAvailableTeams($user)
    {
        if ($user->hasRole(['super_admin', 'admin'])) {
            // Admins see all teams
            return Team::with('club')->where('is_active', true)->orderBy('name')->get();
        } elseif ($user->hasRole('club_admin')) {
            // Club admins see only their club's teams
            $clubIds = $user->clubs()->pluck('clubs.id');
            return Team::with('club')
                ->whereIn('club_id', $clubIds)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        } elseif ($user->hasRole(['trainer', 'head_coach'])) {
            // Trainers see only their teams
            return Team::with('club')
                ->where('is_active', true)
                ->where(function ($query) use ($user) {
                    $query->where('head_coach_id', $user->id)
                          ->orWhereJsonContains('assistant_coaches', $user->id);
                })
                ->orderBy('name')
                ->get();
        }

        return collect();
    }
}