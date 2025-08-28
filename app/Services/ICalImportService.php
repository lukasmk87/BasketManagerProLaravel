<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Sabre\VObject;

class ICalImportService
{
    /**
     * Parse an iCAL file and extract game information.
     */
    public function parseICalFile(string $icalContent): Collection
    {
        try {
            $calendar = VObject\Reader::read($icalContent);
            $games = collect();

            foreach ($calendar->VEVENT as $event) {
                $gameData = $this->parseEvent($event);
                if ($gameData) {
                    $games->push($gameData);
                }
            }

            return $games;
        } catch (\Exception $e) {
            Log::error('Error parsing iCAL file: '.$e->getMessage());
            throw new \Exception('Fehler beim Lesen der iCAL-Datei: '.$e->getMessage());
        }
    }

    /**
     * Parse a single VEVENT from the iCAL.
     */
    private function parseEvent($event): ?array
    {
        try {
            $summary = (string) $event->SUMMARY;
            $location = (string) ($event->LOCATION ?? '');
            $uid = (string) $event->UID;
            $dtStart = $event->DTSTART->getDateTime();
            $dtEnd = $event->DTEND->getDateTime();

            // Parse teams and venue code from summary
            // Format: "Gütersloher TV 4-SV Brackwede 2, 502A160 (SpNr. 0)"
            $gameInfo = $this->parseGameSummary($summary);
            if (! $gameInfo) {
                return null;
            }

            // Parse location details
            $locationInfo = $this->parseLocation($location);

            return [
                'external_game_id' => $uid,
                'home_team_raw' => $gameInfo['home_team'],
                'away_team_raw' => $gameInfo['away_team'],
                'venue_code' => $gameInfo['venue_code'],
                'game_number' => $gameInfo['game_number'],
                'scheduled_at' => Carbon::createFromFormat('Y-m-d H:i:s', $dtStart->format('Y-m-d H:i:s')),
                'duration_minutes' => $dtStart->diff($dtEnd)->i + ($dtStart->diff($dtEnd)->h * 60),
                'venue' => $locationInfo['venue'],
                'venue_address' => $locationInfo['address'],
                'import_metadata' => [
                    'original_summary' => $summary,
                    'original_location' => $location,
                    'parsed_at' => now()->toISOString(),
                    'duration_minutes' => $dtStart->diff($dtEnd)->i + ($dtStart->diff($dtEnd)->h * 60),
                ],
                'import_source' => 'ical',
            ];
        } catch (\Exception $e) {
            Log::warning('Error parsing iCAL event: '.$e->getMessage(), [
                'event_summary' => $summary ?? 'unknown',
                'event_uid' => $uid ?? 'unknown',
            ]);

            return null;
        }
    }

    /**
     * Parse the game summary to extract teams, venue code, and game number.
     * Format: "Gütersloher TV 4-SV Brackwede 2, 502A160 (SpNr. 0)"
     * Handle teams with hyphens like "DJK Grün-Weiss Rheda 2-Gütersloher TV 4"
     */
    private function parseGameSummary(string $summary): ?array
    {
        // Remove extra whitespace
        $summary = trim($summary);

        // Extract game number if present: (SpNr. X)
        $gameNumber = null;
        if (preg_match('/\(SpNr\.\s*(\d+)\)/', $summary, $matches)) {
            $gameNumber = $matches[1];
            $summary = preg_replace('/\s*\(SpNr\.\s*\d+\)/', '', $summary);
        }

        // Extract venue code after comma: pattern like "502A160"
        $venueCode = null;
        if (preg_match('/,\s*([A-Z0-9]+)\s*$/', $summary, $matches)) {
            $venueCode = $matches[1];
            $summary = preg_replace('/,\s*[A-Z0-9]+\s*$/', '', $summary);
        }

        // Try to intelligently split teams with multiple patterns
        $patterns = [
            // Priority 1: Number followed by dash and capital letter (most reliable)
            // Handles: "Rheda 2-Gütersloher TV 4", "TV 4-SV Brackwede 2"
            '/^(.+?\d)\s*-\s*([A-Z].+)$/i',

            // Priority 2: Look for known team names that appear frequently
            // Handles "Something-Gütersloher TV 4", "Something-Bad Oeynhausen Baskets 2", etc.
            '/^(.*)\s*-\s*((?:Gütersloher|Bad\s+Oeynhausen|Löhne)\s+.+)$/i',

            // Priority 3: Common team prefixes after dash with greedy matching
            // Changed from lazy (.+?) to greedy (.*) to handle "FC Rot-Weiß Kirchlengern"
            '/^(.*)\s*-\s*((?:SV|TV|TG|FC|BC|TSV|TSVE|DJK|TuSpo)\s+.+)$/i',

            // Priority 4: Team name ending with number
            // Handles: "Something-Team Name 2"
            '/^(.+?)\s*-\s*(.+?\s+\d+)$/i',

            // Priority 5: Use last dash as fallback
            '/^(.+)-(.+)$/',
        ];

        foreach ($patterns as $index => $pattern) {
            if (preg_match($pattern, trim($summary), $matches)) {
                $homeTeam = trim($matches[1]);
                $awayTeam = trim($matches[2]);

                // Additional validation: Check if we split at a color combination
                // This prevents splitting "FC Rot-Weiß" into "FC Rot" and "Weiß"
                if (preg_match('/(Rot|Grün|Blau|Gelb|Schwarz|Weiß|Weiss)$/i', $homeTeam) &&
                    preg_match('/^(Rot|Grün|Blau|Gelb|Schwarz|Weiß|Weiss)/i', $awayTeam)) {
                    // We likely split at a color combination, try next pattern
                    continue;
                }

                // Validate both teams have content
                if (! empty($homeTeam) && ! empty($awayTeam)) {
                    // Log if we used a fallback pattern for debugging
                    if ($index >= 3) {
                        Log::warning('Used fallback pattern for team splitting', [
                            'original_summary' => $summary,
                            'home_team' => $homeTeam,
                            'away_team' => $awayTeam,
                            'pattern_index' => $index,
                            'pattern_used' => $pattern,
                        ]);
                    }

                    return [
                        'home_team' => $homeTeam,
                        'away_team' => $awayTeam,
                        'venue_code' => $venueCode,
                        'game_number' => $gameNumber,
                    ];
                }
            }
        }

        // If no pattern matched, log the failure for debugging
        Log::error('Failed to parse team names from game summary', [
            'original_summary' => $summary,
            'cleaned_summary' => trim($summary),
        ]);

        return null;
    }

    /**
     * Parse location information to extract venue name and address.
     */
    private function parseLocation(string $location): array
    {
        if (empty($location)) {
            return ['venue' => null, 'address' => null];
        }

        // Try to split by comma - first part is often venue, rest is address
        $parts = array_map('trim', explode(',', $location));

        if (count($parts) == 1) {
            // Only address provided
            return [
                'venue' => null,
                'address' => $parts[0],
            ];
        }

        // Multiple parts - assume first is venue name if it doesn't contain numbers
        $firstPart = $parts[0];
        if (! preg_match('/\d/', $firstPart)) {
            // First part doesn't contain numbers, likely venue name
            return [
                'venue' => $firstPart,
                'address' => implode(', ', array_slice($parts, 1)),
            ];
        }

        // All parts seem to be address
        return [
            'venue' => null,
            'address' => $location,
        ];
    }

    /**
     * Import games for a specific team, matching team names.
     */
    public function importGamesForTeam(Collection $parsedGames, Team $team, string $gameType = 'regular_season'): array
    {
        $importedCount = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($parsedGames as $gameData) {
            try {
                // Determine if this is a home or away game for the team
                $isHomeGame = $this->isTeamMatch($team->name, $gameData['home_team_raw']);
                $isAwayGame = $this->isTeamMatch($team->name, $gameData['away_team_raw']);

                if (! $isHomeGame && ! $isAwayGame) {
                    $skippedCount++;

                    continue;
                }

                // Check for existing game
                if ($this->gameExists($team->id, $gameData['external_game_id'], $gameData['scheduled_at'])) {
                    $skippedCount++;

                    continue;
                }

                // Create game data
                $createData = [
                    'external_game_id' => $gameData['external_game_id'],
                    'scheduled_at' => $gameData['scheduled_at'],
                    'venue' => $gameData['venue'],
                    'venue_address' => $gameData['venue_address'],
                    'venue_code' => $gameData['venue_code'],
                    'import_source' => 'ical',
                    'import_metadata' => $gameData['import_metadata'],
                    'type' => $gameType,
                    'season' => $this->determineSeason($gameData['scheduled_at']),
                    'status' => 'scheduled',
                    'is_home_game' => $isHomeGame,
                ];

                if ($isHomeGame) {
                    $createData['home_team_id'] = $team->id;
                    $createData['away_team_name'] = $gameData['away_team_raw'];
                } else {
                    $createData['away_team_id'] = $team->id;
                    $createData['home_team_name'] = $gameData['home_team_raw'];
                }

                Game::create($createData);
                $importedCount++;

            } catch (\Exception $e) {
                $errors[] = "Fehler beim Importieren von Spiel {$gameData['home_team_raw']} vs {$gameData['away_team_raw']}: ".$e->getMessage();
                Log::error('Game import error', [
                    'game_data' => $gameData,
                    'team_id' => $team->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'imported' => $importedCount,
            'skipped' => $skippedCount,
            'errors' => $errors,
            'total' => $parsedGames->count(),
        ];
    }

    /**
     * Check if a team name matches the team in our system.
     */
    private function isTeamMatch(string $systemTeamName, string $icalTeamName): bool
    {
        // Exact match
        if (strtolower($systemTeamName) === strtolower($icalTeamName)) {
            return true;
        }

        // Remove common variations and try again
        $cleanSystemName = $this->cleanTeamName($systemTeamName);
        $cleanIcalName = $this->cleanTeamName($icalTeamName);

        return strtolower($cleanSystemName) === strtolower($cleanIcalName);
    }

    /**
     * Clean team name for matching purposes.
     */
    private function cleanTeamName(string $teamName): string
    {
        // Remove common abbreviations and normalize
        $cleaned = $teamName;
        $cleaned = str_replace(['e.V.', 'e.V', 'e. V.'], '', $cleaned);
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        $cleaned = trim($cleaned);

        return $cleaned;
    }

    /**
     * Check if a game already exists.
     */
    private function gameExists(int $teamId, string $externalGameId, Carbon $scheduledAt): bool
    {
        return Game::where(function ($query) use ($teamId) {
            $query->where('home_team_id', $teamId)
                ->orWhere('away_team_id', $teamId);
        })
            ->where(function ($query) use ($externalGameId, $scheduledAt) {
                $query->where('external_game_id', $externalGameId)
                    ->orWhere('scheduled_at', $scheduledAt);
            })
            ->exists();
    }

    /**
     * Determine season based on game date.
     */
    private function determineSeason(Carbon $gameDate): string
    {
        $year = $gameDate->year;
        $month = $gameDate->month;

        // Basketball season typically runs from September to March
        // Games from Sep-Dec belong to the season starting that year
        // Games from Jan-Mar belong to the season that started previous year
        if ($month >= 9) {
            return $year.'/'.($year + 1);
        } else {
            return ($year - 1).'/'.$year;
        }
    }

    /**
     * Preview games before import - returns processed game data without saving.
     */
    public function previewGamesForTeam(Collection $parsedGames, Team $team, string $gameType = 'regular_season'): Collection
    {
        return $parsedGames->map(function ($gameData) use ($team, $gameType) {
            $isHomeGame = $this->isTeamMatch($team->name, $gameData['home_team_raw']);
            $isAwayGame = $this->isTeamMatch($team->name, $gameData['away_team_raw']);

            if (! $isHomeGame && ! $isAwayGame) {
                return null;
            }

            $exists = $this->gameExists($team->id, $gameData['external_game_id'], $gameData['scheduled_at']);

            return [
                'home_team_display' => $isHomeGame ? $team->name : $gameData['home_team_raw'],
                'away_team_display' => $isAwayGame ? $team->name : $gameData['away_team_raw'],
                'scheduled_at' => $gameData['scheduled_at'],
                'venue' => $gameData['venue'],
                'venue_address' => $gameData['venue_address'],
                'venue_code' => $gameData['venue_code'],
                'is_home_game' => $isHomeGame,
                'already_exists' => $exists,
                'season' => $this->determineSeason($gameData['scheduled_at']),
                'game_type' => $gameType,
                'can_import' => ! $exists,
                'external_game_id' => $gameData['external_game_id'],
            ];
        })->filter();
    }

    /**
     * Get teams mentioned in the parsed games.
     */
    public function getTeamsFromGames(Collection $parsedGames): Collection
    {
        $teams = collect();

        foreach ($parsedGames as $game) {
            $teams->push($game['home_team_raw']);
            $teams->push($game['away_team_raw']);
        }

        return $teams->unique()->sort()->values();
    }

    /**
     * Import games with explicit team mapping.
     */
    public function importGamesWithTeamMapping(Collection $parsedGames, array $teamMapping, int $selectedTeamId, string $gameType = 'regular_season'): array
    {
        $importedCount = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($parsedGames as $gameData) {
            try {
                // Check if this game involves the selected team using the mapping
                $homeTeamMapped = $teamMapping[$gameData['home_team_raw']] ?? null;
                $awayTeamMapped = $teamMapping[$gameData['away_team_raw']] ?? null;

                // Game must involve the selected team (either as home or away)
                $involvesSelectedTeam = ($homeTeamMapped == $selectedTeamId || $awayTeamMapped == $selectedTeamId);

                if (! $involvesSelectedTeam) {
                    $skippedCount++;

                    continue;
                }

                // Allow external teams: if opponent is not mapped, treat as external team

                // Check for existing game
                if ($this->gameExists($selectedTeamId, $gameData['external_game_id'], $gameData['scheduled_at'])) {
                    $skippedCount++;

                    continue;
                }

                $isHomeGame = ($homeTeamMapped == $selectedTeamId);

                // Create game data
                $createData = [
                    'external_game_id' => $gameData['external_game_id'],
                    'scheduled_at' => $gameData['scheduled_at'],
                    'venue' => $gameData['venue'],
                    'venue_address' => $gameData['venue_address'],
                    'venue_code' => $gameData['venue_code'],
                    'import_source' => 'ical',
                    'import_metadata' => array_merge($gameData['import_metadata'], [
                        'team_mapping' => $teamMapping,
                        'selected_team_id' => $selectedTeamId,
                    ]),
                    'type' => $gameType,
                    'season' => $this->determineSeason($gameData['scheduled_at']),
                    'status' => 'scheduled',
                    'is_home_game' => $isHomeGame,
                ];

                if ($isHomeGame) {
                    $createData['home_team_id'] = $selectedTeamId;
                    if ($awayTeamMapped && $awayTeamMapped != $selectedTeamId) {
                        $createData['away_team_id'] = $awayTeamMapped;
                    } else {
                        $createData['away_team_name'] = $gameData['away_team_raw'];
                    }
                } else {
                    $createData['away_team_id'] = $selectedTeamId;
                    if ($homeTeamMapped && $homeTeamMapped != $selectedTeamId) {
                        $createData['home_team_id'] = $homeTeamMapped;
                    } else {
                        $createData['home_team_name'] = $gameData['home_team_raw'];
                    }
                }

                Game::create($createData);
                $importedCount++;

            } catch (\Exception $e) {
                $errors[] = "Fehler beim Importieren von Spiel {$gameData['home_team_raw']} vs {$gameData['away_team_raw']}: ".$e->getMessage();
                Log::error('Game import with mapping error', [
                    'game_data' => $gameData,
                    'team_mapping' => $teamMapping,
                    'selected_team_id' => $selectedTeamId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'imported' => $importedCount,
            'skipped' => $skippedCount,
            'errors' => $errors,
            'total' => $parsedGames->count(),
        ];
    }

    /**
     * Preview games with explicit team mapping.
     */
    public function previewGamesWithTeamMapping(Collection $parsedGames, array $teamMapping, int $selectedTeamId, string $gameType = 'regular_season'): Collection
    {
        return $parsedGames->map(function ($gameData) use ($teamMapping, $selectedTeamId, $gameType) {
            $homeTeamMapped = $teamMapping[$gameData['home_team_raw']] ?? null;
            $awayTeamMapped = $teamMapping[$gameData['away_team_raw']] ?? null;

            // Game must involve the selected team (either as home or away)
            $involvesSelectedTeam = ($homeTeamMapped == $selectedTeamId || $awayTeamMapped == $selectedTeamId);

            if (! $involvesSelectedTeam) {
                return null;
            }

            // Allow external teams: if opponent is not mapped, treat as external team
            $isHomeGame = ($homeTeamMapped == $selectedTeamId);
            $exists = $this->gameExists($selectedTeamId, $gameData['external_game_id'], $gameData['scheduled_at']);

            // Get team names for display
            $selectedTeam = Team::find($selectedTeamId);
            $homeTeamDisplay = $isHomeGame ? $selectedTeam->name :
                ($homeTeamMapped && $homeTeamMapped != $selectedTeamId ? Team::find($homeTeamMapped)->name : $gameData['home_team_raw']);
            $awayTeamDisplay = ! $isHomeGame ? $selectedTeam->name :
                ($awayTeamMapped && $awayTeamMapped != $selectedTeamId ? Team::find($awayTeamMapped)->name : $gameData['away_team_raw']);

            return [
                'home_team_display' => $homeTeamDisplay,
                'away_team_display' => $awayTeamDisplay,
                'scheduled_at' => $gameData['scheduled_at'],
                'venue' => $gameData['venue'],
                'venue_address' => $gameData['venue_address'],
                'venue_code' => $gameData['venue_code'],
                'is_home_game' => $isHomeGame,
                'already_exists' => $exists,
                'season' => $this->determineSeason($gameData['scheduled_at']),
                'game_type' => $gameType,
                'can_import' => ! $exists,
                'external_game_id' => $gameData['external_game_id'],
            ];
        })->filter();
    }
}
