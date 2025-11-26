<?php

namespace App\Services\Federation;

use App\Models\Tenant;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FIBA Europe API Integration Service
 * 
 * Provides integration with FIBA Europe's official systems for international
 * basketball data, player eligibility, and European competition management
 */
class FIBAApiService
{
    private string $baseUrl;
    private string $apiKey;
    private string $apiSecret;
    private int $timeout;
    private int $retries;

    public function __construct()
    {
        $this->baseUrl = config('services.fiba.base_url', 'https://api.fiba.basketball/v3');
        $this->apiKey = config('services.fiba.api_key') ?? '';
        $this->apiSecret = config('services.fiba.api_secret') ?? '';
        $this->timeout = config('services.fiba.timeout', 30);
        $this->retries = config('services.fiba.retries', 3);
    }

    /**
     * Get player international profile
     *
     * @param string $fibaId
     * @param Tenant $tenant
     * @return array|null
     */
    public function getPlayerProfile(string $fibaId, Tenant $tenant): ?array
    {
        $cacheKey = "fiba:player:{$fibaId}";
        
        return Cache::remember($cacheKey, 7200, function () use ($fibaId, $tenant) {
            try {
                $response = $this->makeRequest('GET', "/players/{$fibaId}", [], $tenant);
                
                if ($response->successful()) {
                    $playerData = $response->json();
                    
                    Log::info('FIBA player profile retrieved', [
                        'fiba_id' => $fibaId,
                        'tenant_id' => $tenant->id,
                        'player_name' => $playerData['player']['name'] ?? 'Unknown',
                    ]);
                    
                    return $this->formatPlayerProfile($playerData);
                }
                
                Log::warning('FIBA player not found', [
                    'fiba_id' => $fibaId,
                    'tenant_id' => $tenant->id,
                    'status' => $response->status(),
                ]);
                
                return null;
                
            } catch (\Exception $e) {
                Log::error('FIBA API player lookup failed', [
                    'fiba_id' => $fibaId,
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                ]);
                
                return null;
            }
        });
    }

    /**
     * Search players by criteria
     *
     * @param array $criteria
     * @param Tenant $tenant
     * @return array
     */
    public function searchPlayers(array $criteria, Tenant $tenant): array
    {
        try {
            $params = [
                'name' => $criteria['name'] ?? null,
                'nationality' => $criteria['nationality'] ?? null,
                'birth_year' => $criteria['birth_year'] ?? null,
                'position' => $criteria['position'] ?? null,
                'club' => $criteria['club'] ?? null,
                'limit' => $criteria['limit'] ?? 50,
            ];
            
            // Remove null values
            $params = array_filter($params, function ($value) {
                return $value !== null;
            });
            
            $response = $this->makeRequest('GET', '/players/search', $params, $tenant);
            
            if ($response->successful()) {
                $searchResults = $response->json();
                
                Log::info('FIBA player search completed', [
                    'tenant_id' => $tenant->id,
                    'criteria' => $criteria,
                    'results_count' => count($searchResults['players'] ?? []),
                ]);
                
                return $searchResults['players'] ?? [];
            }
            
            return [];
            
        } catch (\Exception $e) {
            Log::error('FIBA player search failed', [
                'tenant_id' => $tenant->id,
                'criteria' => $criteria,
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }

    /**
     * Get player eligibility for international competitions
     *
     * @param string $fibaId
     * @param string $competitionId
     * @param Tenant $tenant
     * @return array
     */
    public function getPlayerEligibility(string $fibaId, string $competitionId, Tenant $tenant): array
    {
        try {
            $response = $this->makeRequest('GET', "/players/{$fibaId}/eligibility", [
                'competition_id' => $competitionId,
                'season' => $this->getCurrentSeason(),
            ], $tenant);
            
            if ($response->successful()) {
                $eligibility = $response->json();
                
                Log::info('FIBA player eligibility checked', [
                    'fiba_id' => $fibaId,
                    'competition_id' => $competitionId,
                    'tenant_id' => $tenant->id,
                    'eligible' => $eligibility['eligible'] ?? false,
                ]);
                
                return [
                    'eligible' => $eligibility['eligible'] ?? false,
                    'nationality' => $eligibility['nationality'] ?? null,
                    'passport_countries' => $eligibility['passport_countries'] ?? [],
                    'eligibility_rules' => $eligibility['eligibility_rules'] ?? [],
                    'restrictions' => $eligibility['restrictions'] ?? [],
                    'naturalization_status' => $eligibility['naturalization_status'] ?? null,
                    'previous_representations' => $eligibility['previous_representations'] ?? [],
                ];
            }
            
            return [
                'eligible' => false,
                'reason' => 'Eligibility check failed',
            ];
            
        } catch (\Exception $e) {
            Log::error('FIBA eligibility check failed', [
                'fiba_id' => $fibaId,
                'competition_id' => $competitionId,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'eligible' => false,
                'reason' => 'Eligibility service unavailable',
            ];
        }
    }

    /**
     * Get European competitions and tournaments
     *
     * @param string|null $category
     * @param string|null $level
     * @param Tenant $tenant
     * @return array
     */
    public function getCompetitions(?string $category = null, ?string $level = null, Tenant $tenant): array
    {
        $cacheKey = "fiba:competitions:" . md5($category . $level);
        
        return Cache::remember($cacheKey, 14400, function () use ($category, $level, $tenant) {
            try {
                $params = [
                    'region' => 'europe',
                    'season' => $this->getCurrentSeason(),
                ];
                
                if ($category) {
                    $params['category'] = $category; // men, women, youth
                }
                
                if ($level) {
                    $params['level'] = $level; // professional, amateur, youth
                }
                
                $response = $this->makeRequest('GET', '/competitions', $params, $tenant);
                
                if ($response->successful()) {
                    $competitions = $response->json();
                    
                    Log::info('FIBA competitions retrieved', [
                        'tenant_id' => $tenant->id,
                        'category' => $category,
                        'level' => $level,
                        'count' => count($competitions['competitions'] ?? []),
                    ]);
                    
                    return $competitions['competitions'] ?? [];
                }
                
                return [];
                
            } catch (\Exception $e) {
                Log::error('FIBA competitions lookup failed', [
                    'tenant_id' => $tenant->id,
                    'category' => $category,
                    'level' => $level,
                    'error' => $e->getMessage(),
                ]);
                
                return [];
            }
        });
    }

    /**
     * Get club information and registration status
     *
     * @param string $clubId
     * @param Tenant $tenant
     * @return array|null
     */
    public function getClubInfo(string $clubId, Tenant $tenant): ?array
    {
        $cacheKey = "fiba:club:{$clubId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($clubId, $tenant) {
            try {
                $response = $this->makeRequest('GET', "/clubs/{$clubId}", [], $tenant);
                
                if ($response->successful()) {
                    $clubData = $response->json();
                    
                    Log::info('FIBA club info retrieved', [
                        'club_id' => $clubId,
                        'tenant_id' => $tenant->id,
                        'club_name' => $clubData['club']['name'] ?? 'Unknown',
                    ]);
                    
                    return [
                        'id' => $clubData['club']['id'] ?? null,
                        'name' => $clubData['club']['name'] ?? null,
                        'country' => $clubData['club']['country'] ?? null,
                        'federation' => $clubData['club']['federation'] ?? null,
                        'established' => $clubData['club']['established'] ?? null,
                        'venue' => $clubData['club']['venue'] ?? null,
                        'competitions' => $clubData['club']['competitions'] ?? [],
                        'licenses' => $clubData['club']['licenses'] ?? [],
                        'status' => $clubData['club']['status'] ?? 'unknown',
                    ];
                }
                
                return null;
                
            } catch (\Exception $e) {
                Log::error('FIBA club lookup failed', [
                    'club_id' => $clubId,
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                ]);
                
                return null;
            }
        });
    }

    /**
     * Register team for European competition
     *
     * @param array $teamData
     * @param string $competitionId
     * @param Tenant $tenant
     * @return array
     */
    public function registerTeamForCompetition(array $teamData, string $competitionId, Tenant $tenant): array
    {
        try {
            $response = $this->makeRequest('POST', '/teams/register', [
                'competition_id' => $competitionId,
                'club_id' => $teamData['club_id'],
                'team_name' => $teamData['name'],
                'category' => $teamData['category'], // men, women, youth
                'age_group' => $teamData['age_group'] ?? null,
                'season' => $this->getCurrentSeason(),
                'coach' => [
                    'name' => $teamData['coach_name'],
                    'license' => $teamData['coach_license'] ?? null,
                    'nationality' => $teamData['coach_nationality'] ?? null,
                ],
                'home_venue' => $teamData['home_venue'],
                'contact_person' => $teamData['contact_person'],
                'contact_email' => $teamData['contact_email'],
                'roster' => $teamData['roster'] ?? [],
            ], $tenant);
            
            if ($response->successful()) {
                $registration = $response->json();
                
                Log::info('FIBA team registration successful', [
                    'tenant_id' => $tenant->id,
                    'team_name' => $teamData['name'],
                    'competition_id' => $competitionId,
                    'fiba_team_id' => $registration['team_id'] ?? null,
                ]);
                
                return [
                    'success' => true,
                    'fiba_team_id' => $registration['team_id'],
                    'registration_number' => $registration['registration_number'],
                    'status' => $registration['status'],
                    'competition' => $registration['competition'],
                    'deadlines' => $registration['deadlines'] ?? [],
                ];
            }
            
            $error = $response->json();
            
            Log::warning('FIBA team registration failed', [
                'tenant_id' => $tenant->id,
                'team_name' => $teamData['name'],
                'competition_id' => $competitionId,
                'error' => $error['message'] ?? 'Unknown error',
                'status' => $response->status(),
            ]);
            
            return [
                'success' => false,
                'error' => $error['message'] ?? 'Registration failed',
                'validation_errors' => $error['errors'] ?? [],
            ];
            
        } catch (\Exception $e) {
            Log::error('FIBA team registration error', [
                'tenant_id' => $tenant->id,
                'team_name' => $teamData['name'],
                'competition_id' => $competitionId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => 'Registration service unavailable',
                'validation_errors' => [],
            ];
        }
    }

    /**
     * Get official game data and statistics
     *
     * @param string $gameId
     * @param Tenant $tenant
     * @return array|null
     */
    public function getOfficialGameData(string $gameId, Tenant $tenant): ?array
    {
        try {
            $response = $this->makeRequest('GET', "/games/{$gameId}/official", [], $tenant);
            
            if ($response->successful()) {
                $gameData = $response->json();
                
                Log::info('FIBA official game data retrieved', [
                    'game_id' => $gameId,
                    'tenant_id' => $tenant->id,
                    'competition' => $gameData['game']['competition'] ?? 'Unknown',
                ]);
                
                return [
                    'game_id' => $gameData['game']['id'] ?? null,
                    'competition' => $gameData['game']['competition'] ?? null,
                    'round' => $gameData['game']['round'] ?? null,
                    'home_team' => $gameData['game']['home_team'] ?? null,
                    'away_team' => $gameData['game']['away_team'] ?? null,
                    'date_time' => $gameData['game']['date_time'] ?? null,
                    'venue' => $gameData['game']['venue'] ?? null,
                    'officials' => $gameData['game']['officials'] ?? [],
                    'result' => $gameData['game']['result'] ?? null,
                    'statistics' => $gameData['game']['statistics'] ?? [],
                    'status' => $gameData['game']['status'] ?? 'unknown',
                ];
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('FIBA official game data lookup failed', [
                'game_id' => $gameId,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    /**
     * Get referee certifications and assignments
     *
     * @param string $refereeId
     * @param Tenant $tenant
     * @return array|null
     */
    public function getRefereeInfo(string $refereeId, Tenant $tenant): ?array
    {
        try {
            $response = $this->makeRequest('GET', "/referees/{$refereeId}", [], $tenant);
            
            if ($response->successful()) {
                $refereeData = $response->json();
                
                return [
                    'id' => $refereeData['referee']['id'] ?? null,
                    'name' => $refereeData['referee']['name'] ?? null,
                    'nationality' => $refereeData['referee']['nationality'] ?? null,
                    'certifications' => $refereeData['referee']['certifications'] ?? [],
                    'level' => $refereeData['referee']['level'] ?? null,
                    'active_competitions' => $refereeData['referee']['active_competitions'] ?? [],
                    'assignments' => $refereeData['referee']['assignments'] ?? [],
                    'status' => $refereeData['referee']['status'] ?? 'unknown',
                ];
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('FIBA referee info lookup failed', [
                'referee_id' => $refereeId,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    /**
     * Get competition standings and rankings
     *
     * @param string $competitionId
     * @param string|null $phase
     * @param Tenant $tenant
     * @return array
     */
    public function getCompetitionStandings(string $competitionId, ?string $phase = null, Tenant $tenant): array
    {
        $cacheKey = "fiba:standings:{$competitionId}:" . ($phase ?? 'all');
        
        return Cache::remember($cacheKey, 1800, function () use ($competitionId, $phase, $tenant) {
            try {
                $params = [];
                if ($phase) {
                    $params['phase'] = $phase;
                }
                
                $response = $this->makeRequest('GET', "/competitions/{$competitionId}/standings", $params, $tenant);
                
                if ($response->successful()) {
                    $standings = $response->json();
                    
                    return $standings['standings'] ?? [];
                }
                
                return [];
                
            } catch (\Exception $e) {
                Log::error('FIBA standings lookup failed', [
                    'competition_id' => $competitionId,
                    'phase' => $phase,
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                ]);
                
                return [];
            }
        });
    }

    /**
     * Make authenticated request to FIBA API
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @param Tenant $tenant
     * @return Response
     */
    private function makeRequest(string $method, string $endpoint, array $data = [], Tenant $tenant): Response
    {
        $url = $this->baseUrl . $endpoint;
        
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-FIBA-API-Key' => $this->apiKey,
            'X-FIBA-Tenant-ID' => $tenant->id,
            'User-Agent' => 'BasketManager-Pro/1.0',
        ];
        
        // Add authentication
        $timestamp = time();
        $signature = $this->generateSignature($method, $endpoint, $data, $timestamp);
        
        $headers['X-FIBA-Timestamp'] = $timestamp;
        $headers['X-FIBA-Signature'] = $signature;
        
        Log::debug('FIBA API request', [
            'method' => $method,
            'url' => $url,
            'tenant_id' => $tenant->id,
            'has_data' => !empty($data),
        ]);
        
        $http = Http::withHeaders($headers)
            ->timeout($this->timeout)
            ->retry($this->retries, 1000);

        return match ($method) {
            'GET' => $http->get($url, $data),
            'POST' => $http->post($url, $data),
            'PUT' => $http->put($url, $data),
            'DELETE' => $http->delete($url),
            default => $http->get($url, $data),
        };
    }

    /**
     * Generate authentication signature for FIBA API
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @param int $timestamp
     * @return string
     */
    private function generateSignature(string $method, string $endpoint, array $data, int $timestamp): string
    {
        $payload = $method . '|' . $endpoint . '|' . json_encode($data) . '|' . $timestamp;
        
        return hash_hmac('sha256', $payload, $this->apiSecret);
    }

    /**
     * Format player profile from FIBA API response
     *
     * @param array $rawData
     * @return array
     */
    private function formatPlayerProfile(array $rawData): array
    {
        $player = $rawData['player'] ?? [];
        
        return [
            'fiba_id' => $player['id'] ?? null,
            'name' => $player['name'] ?? null,
            'first_name' => $player['first_name'] ?? null,
            'last_name' => $player['last_name'] ?? null,
            'date_of_birth' => $player['date_of_birth'] ?? null,
            'nationality' => $player['nationality'] ?? null,
            'height' => $player['height'] ?? null,
            'weight' => $player['weight'] ?? null,
            'position' => $player['position'] ?? null,
            'current_club' => $player['current_club'] ?? null,
            'national_team_caps' => $player['national_team_caps'] ?? 0,
            'career_highlights' => $player['career_highlights'] ?? [],
            'international_experience' => $player['international_experience'] ?? [],
            'status' => $player['status'] ?? 'active',
            'photo_url' => $player['photo_url'] ?? null,
        ];
    }

    /**
     * Get current basketball season for FIBA
     *
     * @return string
     */
    private function getCurrentSeason(): string
    {
        $year = date('Y');
        $month = date('n');
        
        // FIBA season runs from October to September
        if ($month >= 10) {
            return $year . '-' . ($year + 1);
        } else {
            return ($year - 1) . '-' . $year;
        }
    }

    /**
     * Check if FIBA API is available
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)
                ->get($this->baseUrl . '/health');
                
            return $response->successful();
            
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get FIBA API status and information
     *
     * @return array
     */
    public function getApiStatus(): array
    {
        try {
            $response = Http::timeout(10)
                ->get($this->baseUrl . '/status');
                
            if ($response->successful()) {
                return $response->json();
            }
            
            return [
                'status' => 'unavailable',
                'message' => 'FIBA API not responding',
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
}