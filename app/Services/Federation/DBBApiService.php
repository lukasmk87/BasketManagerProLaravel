<?php

namespace App\Services\Federation;

use App\Models\Tenant;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Deutscher Basketball Bund (DBB) API Integration Service
 * 
 * Provides integration with the German Basketball Federation's official systems
 * for player registration, league data, and compliance requirements
 */
class DBBApiService
{
    private string $baseUrl;
    private string $apiKey;
    private string $apiSecret;
    private int $timeout;
    private int $retries;

    public function __construct()
    {
        $this->baseUrl = config('services.dbb.base_url', 'https://api.basketball-bund.de/v2');
        $this->apiKey = config('services.dbb.api_key');
        $this->apiSecret = config('services.dbb.api_secret');
        $this->timeout = config('services.dbb.timeout', 30);
        $this->retries = config('services.dbb.retries', 3);
    }

    /**
     * Get player information by license number
     *
     * @param string $licenseNumber
     * @param Tenant $tenant
     * @return array|null
     */
    public function getPlayerByLicense(string $licenseNumber, Tenant $tenant): ?array
    {
        $cacheKey = "dbb:player:{$licenseNumber}";
        
        return Cache::remember($cacheKey, 3600, function () use ($licenseNumber, $tenant) {
            try {
                $response = $this->makeRequest('GET', "/players/{$licenseNumber}", [], $tenant);
                
                if ($response->successful()) {
                    $playerData = $response->json();
                    
                    Log::info('DBB player data retrieved', [
                        'license_number' => $licenseNumber,
                        'tenant_id' => $tenant->id,
                        'player_name' => $playerData['name'] ?? 'Unknown',
                    ]);
                    
                    return $this->formatPlayerData($playerData);
                }
                
                Log::warning('DBB player not found', [
                    'license_number' => $licenseNumber,
                    'tenant_id' => $tenant->id,
                    'status' => $response->status(),
                ]);
                
                return null;
                
            } catch (\Exception $e) {
                Log::error('DBB API player lookup failed', [
                    'license_number' => $licenseNumber,
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                ]);
                
                return null;
            }
        });
    }

    /**
     * Validate player eligibility for team/league
     *
     * @param string $licenseNumber
     * @param string $leagueId
     * @param Tenant $tenant
     * @return array
     */
    public function validatePlayerEligibility(string $licenseNumber, string $leagueId, Tenant $tenant): array
    {
        try {
            $response = $this->makeRequest('POST', '/players/validate', [
                'license_number' => $licenseNumber,
                'league_id' => $leagueId,
                'season' => $this->getCurrentSeason(),
            ], $tenant);
            
            if ($response->successful()) {
                $validation = $response->json();
                
                Log::info('DBB player eligibility validated', [
                    'license_number' => $licenseNumber,
                    'league_id' => $leagueId,
                    'tenant_id' => $tenant->id,
                    'eligible' => $validation['eligible'] ?? false,
                ]);
                
                return [
                    'eligible' => $validation['eligible'] ?? false,
                    'reason' => $validation['reason'] ?? null,
                    'restrictions' => $validation['restrictions'] ?? [],
                    'valid_until' => $validation['valid_until'] ?? null,
                    'suspension_status' => $validation['suspension_status'] ?? null,
                ];
            }
            
            return [
                'eligible' => false,
                'reason' => 'API validation failed',
                'restrictions' => [],
            ];
            
        } catch (\Exception $e) {
            Log::error('DBB eligibility validation failed', [
                'license_number' => $licenseNumber,
                'league_id' => $leagueId,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'eligible' => false,
                'reason' => 'Validation service unavailable',
                'restrictions' => [],
            ];
        }
    }

    /**
     * Get league information and structure
     *
     * @param string|null $region
     * @param string|null $ageGroup
     * @param Tenant $tenant
     * @return array
     */
    public function getLeagues(?string $region = null, ?string $ageGroup = null, Tenant $tenant): array
    {
        $cacheKey = "dbb:leagues:" . md5($region . $ageGroup);
        
        return Cache::remember($cacheKey, 7200, function () use ($region, $ageGroup, $tenant) {
            try {
                $params = [
                    'season' => $this->getCurrentSeason(),
                ];
                
                if ($region) {
                    $params['region'] = $region;
                }
                
                if ($ageGroup) {
                    $params['age_group'] = $ageGroup;
                }
                
                $response = $this->makeRequest('GET', '/leagues', $params, $tenant);
                
                if ($response->successful()) {
                    $leagues = $response->json();
                    
                    Log::info('DBB leagues retrieved', [
                        'tenant_id' => $tenant->id,
                        'region' => $region,
                        'age_group' => $ageGroup,
                        'count' => count($leagues['data'] ?? []),
                    ]);
                    
                    return $leagues['data'] ?? [];
                }
                
                return [];
                
            } catch (\Exception $e) {
                Log::error('DBB leagues lookup failed', [
                    'tenant_id' => $tenant->id,
                    'region' => $region,
                    'age_group' => $ageGroup,
                    'error' => $e->getMessage(),
                ]);
                
                return [];
            }
        });
    }

    /**
     * Register team with DBB
     *
     * @param array $teamData
     * @param Tenant $tenant
     * @return array
     */
    public function registerTeam(array $teamData, Tenant $tenant): array
    {
        try {
            $response = $this->makeRequest('POST', '/teams/register', [
                'team_name' => $teamData['name'],
                'club_id' => $teamData['club_id'],
                'league_id' => $teamData['league_id'],
                'age_group' => $teamData['age_group'],
                'season' => $this->getCurrentSeason(),
                'contact_person' => $teamData['contact_person'],
                'contact_email' => $teamData['contact_email'],
                'contact_phone' => $teamData['contact_phone'],
                'home_venue' => $teamData['home_venue'],
                'team_logo_url' => $teamData['logo_url'] ?? null,
            ], $tenant);
            
            if ($response->successful()) {
                $registration = $response->json();
                
                Log::info('DBB team registration successful', [
                    'tenant_id' => $tenant->id,
                    'team_name' => $teamData['name'],
                    'dbb_team_id' => $registration['team_id'] ?? null,
                ]);
                
                return [
                    'success' => true,
                    'dbb_team_id' => $registration['team_id'],
                    'registration_number' => $registration['registration_number'],
                    'status' => $registration['status'],
                    'valid_from' => $registration['valid_from'],
                    'valid_until' => $registration['valid_until'],
                ];
            }
            
            $error = $response->json();
            
            Log::warning('DBB team registration failed', [
                'tenant_id' => $tenant->id,
                'team_name' => $teamData['name'],
                'error' => $error['message'] ?? 'Unknown error',
                'status' => $response->status(),
            ]);
            
            return [
                'success' => false,
                'error' => $error['message'] ?? 'Registration failed',
                'validation_errors' => $error['errors'] ?? [],
            ];
            
        } catch (\Exception $e) {
            Log::error('DBB team registration error', [
                'tenant_id' => $tenant->id,
                'team_name' => $teamData['name'],
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
     * Submit game result to DBB
     *
     * @param array $gameData
     * @param Tenant $tenant
     * @return array
     */
    public function submitGameResult(array $gameData, Tenant $tenant): array
    {
        try {
            $response = $this->makeRequest('POST', '/games/results', [
                'game_id' => $gameData['dbb_game_id'],
                'home_team_score' => $gameData['home_score'],
                'away_team_score' => $gameData['away_score'],
                'game_date' => $gameData['played_at'],
                'referee_signature' => $gameData['referee_signature'] ?? null,
                'home_team_roster' => $gameData['home_roster'] ?? [],
                'away_team_roster' => $gameData['away_roster'] ?? [],
                'player_statistics' => $gameData['player_stats'] ?? [],
                'technical_fouls' => $gameData['technical_fouls'] ?? [],
                'unsportsmanlike_fouls' => $gameData['unsportsmanlike_fouls'] ?? [],
                'game_notes' => $gameData['notes'] ?? null,
            ], $tenant);
            
            if ($response->successful()) {
                $result = $response->json();
                
                Log::info('DBB game result submitted', [
                    'tenant_id' => $tenant->id,
                    'game_id' => $gameData['dbb_game_id'],
                    'home_score' => $gameData['home_score'],
                    'away_score' => $gameData['away_score'],
                ]);
                
                return [
                    'success' => true,
                    'submission_id' => $result['submission_id'],
                    'status' => $result['status'],
                    'verified' => $result['verified'] ?? false,
                ];
            }
            
            $error = $response->json();
            
            return [
                'success' => false,
                'error' => $error['message'] ?? 'Submission failed',
                'validation_errors' => $error['errors'] ?? [],
            ];
            
        } catch (\Exception $e) {
            Log::error('DBB game result submission failed', [
                'tenant_id' => $tenant->id,
                'game_id' => $gameData['dbb_game_id'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => 'Submission service unavailable',
                'validation_errors' => [],
            ];
        }
    }

    /**
     * Get player transfer status
     *
     * @param string $licenseNumber
     * @param Tenant $tenant
     * @return array|null
     */
    public function getPlayerTransferStatus(string $licenseNumber, Tenant $tenant): ?array
    {
        try {
            $response = $this->makeRequest('GET', "/players/{$licenseNumber}/transfers", [
                'season' => $this->getCurrentSeason(),
            ], $tenant);
            
            if ($response->successful()) {
                $transfers = $response->json();
                
                return [
                    'current_club' => $transfers['current_club'] ?? null,
                    'pending_transfers' => $transfers['pending_transfers'] ?? [],
                    'transfer_window_open' => $transfers['transfer_window_open'] ?? false,
                    'restrictions' => $transfers['restrictions'] ?? [],
                ];
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('DBB transfer status lookup failed', [
                'license_number' => $licenseNumber,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    /**
     * Get referee assignments for games
     *
     * @param string $leagueId
     * @param string $gameDate
     * @param Tenant $tenant
     * @return array
     */
    public function getRefereeAssignments(string $leagueId, string $gameDate, Tenant $tenant): array
    {
        try {
            $response = $this->makeRequest('GET', '/referees/assignments', [
                'league_id' => $leagueId,
                'date' => $gameDate,
            ], $tenant);
            
            if ($response->successful()) {
                $assignments = $response->json();
                
                return $assignments['data'] ?? [];
            }
            
            return [];
            
        } catch (\Exception $e) {
            Log::error('DBB referee assignments lookup failed', [
                'league_id' => $leagueId,
                'date' => $gameDate,
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }

    /**
     * Make authenticated request to DBB API
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
            'X-DBB-API-Key' => $this->apiKey,
            'X-DBB-Tenant-ID' => $tenant->id,
            'User-Agent' => 'BasketManager-Pro/1.0',
        ];
        
        // Add authentication signature
        $timestamp = time();
        $signature = $this->generateSignature($method, $endpoint, $data, $timestamp);
        
        $headers['X-DBB-Timestamp'] = $timestamp;
        $headers['X-DBB-Signature'] = $signature;
        
        Log::debug('DBB API request', [
            'method' => $method,
            'url' => $url,
            'tenant_id' => $tenant->id,
            'has_data' => !empty($data),
        ]);
        
        return Http::withHeaders($headers)
            ->timeout($this->timeout)
            ->retry($this->retries, 1000)
            ->when($method === 'GET', function ($http) use ($data) {
                return $http->get($data);
            })
            ->when($method === 'POST', function ($http) use ($url, $data) {
                return $http->post($url, $data);
            })
            ->when($method === 'PUT', function ($http) use ($url, $data) {
                return $http->put($url, $data);
            })
            ->when($method === 'DELETE', function ($http) use ($url) {
                return $http->delete($url);
            });
    }

    /**
     * Generate authentication signature for DBB API
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
     * Format player data from DBB API response
     *
     * @param array $rawData
     * @return array
     */
    private function formatPlayerData(array $rawData): array
    {
        return [
            'license_number' => $rawData['license_number'] ?? null,
            'first_name' => $rawData['first_name'] ?? null,
            'last_name' => $rawData['last_name'] ?? null,
            'date_of_birth' => $rawData['date_of_birth'] ?? null,
            'nationality' => $rawData['nationality'] ?? 'DE',
            'current_club' => $rawData['current_club'] ?? null,
            'position' => $rawData['position'] ?? null,
            'status' => $rawData['status'] ?? 'active',
            'suspension_status' => $rawData['suspension_status'] ?? null,
            'license_valid_until' => $rawData['license_valid_until'] ?? null,
            'photo_url' => $rawData['photo_url'] ?? null,
            'achievements' => $rawData['achievements'] ?? [],
            'career_statistics' => $rawData['career_statistics'] ?? [],
        ];
    }

    /**
     * Get current basketball season
     *
     * @return string
     */
    private function getCurrentSeason(): string
    {
        $year = date('Y');
        $month = date('n');
        
        // Basketball season runs from September to May
        if ($month >= 9) {
            return $year . '/' . ($year + 1);
        } else {
            return ($year - 1) . '/' . $year;
        }
    }

    /**
     * Check if DBB API is available
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
     * Get DBB API status and information
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
                'message' => 'API not responding',
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
}