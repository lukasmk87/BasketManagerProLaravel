<?php

namespace App\Jobs;

use App\Models\FIBAIntegration;
use App\Models\Tenant;
use App\Services\Federation\FIBAApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job for syncing data with FIBA Europe API
 * Handles periodic synchronization of international player, competition, and club data
 */
class SyncFIBAData implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $tries = 3;
    public $backoff = [120, 600, 1800]; // 2 min, 10 min, 30 min
    public $timeout = 300; // 5 minutes

    private string $integrationId;
    private string $syncType;

    /**
     * Create a new job instance
     *
     * @param string $integrationId
     * @param string $syncType (full, incremental, validation)
     */
    public function __construct(string $integrationId, string $syncType = 'incremental')
    {
        $this->integrationId = $integrationId;
        $this->syncType = $syncType;
    }

    /**
     * Execute the job
     */
    public function handle(FIBAApiService $fibaService): void
    {
        $integration = FIBAIntegration::find($this->integrationId);
        
        if (!$integration) {
            Log::error('FIBA integration not found for sync', [
                'integration_id' => $this->integrationId,
            ]);
            return;
        }

        $tenant = $integration->tenant;
        
        if (!$tenant) {
            Log::error('Tenant not found for FIBA integration', [
                'integration_id' => $this->integrationId,
                'tenant_id' => $integration->tenant_id,
            ]);
            return;
        }

        Log::info('Starting FIBA data sync', [
            'integration_id' => $this->integrationId,
            'tenant_id' => $tenant->id,
            'entity_type' => $integration->entity_type,
            'fiba_type' => $integration->fiba_type,
            'sync_type' => $this->syncType,
            'attempt' => $this->attempts(),
        ]);

        // Mark as syncing
        $integration->markSyncing();

        try {
            switch ($integration->fiba_type) {
                case FIBAIntegration::FIBA_TYPE_PLAYER_PROFILE:
                    $this->syncPlayerProfile($integration, $fibaService, $tenant);
                    break;
                
                case FIBAIntegration::FIBA_TYPE_PLAYER_ELIGIBILITY:
                    $this->syncPlayerEligibility($integration, $fibaService, $tenant);
                    break;
                
                case FIBAIntegration::FIBA_TYPE_TEAM_REGISTRATION:
                    $this->syncTeamRegistration($integration, $fibaService, $tenant);
                    break;
                
                case FIBAIntegration::FIBA_TYPE_CLUB_LICENSE:
                    $this->syncClubInfo($integration, $fibaService, $tenant);
                    break;
                
                case FIBAIntegration::FIBA_TYPE_GAME_OFFICIAL:
                    $this->syncOfficialGameData($integration, $fibaService, $tenant);
                    break;
                
                default:
                    throw new \InvalidArgumentException("Unsupported FIBA type: {$integration->fiba_type}");
            }

            Log::info('FIBA data sync completed successfully', [
                'integration_id' => $this->integrationId,
                'tenant_id' => $tenant->id,
                'entity_type' => $integration->entity_type,
                'fiba_type' => $integration->fiba_type,
            ]);

        } catch (\Exception $e) {
            $this->handleSyncError($integration, $e);
        }
    }

    /**
     * Sync player profile data
     *
     * @param FIBAIntegration $integration
     * @param FIBAApiService $fibaService
     * @param Tenant $tenant
     * @return void
     */
    private function syncPlayerProfile(FIBAIntegration $integration, FIBAApiService $fibaService, Tenant $tenant): void
    {
        $fibaId = $integration->fiba_data['fiba_id'] ?? null;
        
        if (!$fibaId) {
            throw new \InvalidArgumentException('FIBA ID required for player profile sync');
        }

        // Get current player profile from FIBA
        $playerProfile = $fibaService->getPlayerProfile($fibaId, $tenant);
        
        if (!$playerProfile) {
            throw new \RuntimeException('Player profile not found in FIBA system');
        }

        $syncedData = array_merge($integration->fiba_data ?? [], [
            'player_data' => $playerProfile,
            'last_validated' => now()->toISOString(),
        ]);

        // Mark as synced
        $integration->markSynced($fibaId, $syncedData);
    }

    /**
     * Sync player eligibility data
     *
     * @param FIBAIntegration $integration
     * @param FIBAApiService $fibaService
     * @param Tenant $tenant
     * @return void
     */
    private function syncPlayerEligibility(FIBAIntegration $integration, FIBAApiService $fibaService, Tenant $tenant): void
    {
        $fibaId = $integration->fiba_data['fiba_id'] ?? null;
        $competitionId = $integration->fiba_data['competition_id'] ?? null;
        
        if (!$fibaId || !$competitionId) {
            throw new \InvalidArgumentException('FIBA ID and competition ID required for eligibility sync');
        }

        // Get player eligibility for competition
        $eligibility = $fibaService->getPlayerEligibility($fibaId, $competitionId, $tenant);

        $syncedData = array_merge($integration->fiba_data ?? [], [
            'eligibility' => $eligibility,
            'last_checked' => now()->toISOString(),
        ]);

        // Mark as synced
        $integration->markSynced($fibaId, $syncedData);
    }

    /**
     * Sync team registration data
     *
     * @param FIBAIntegration $integration
     * @param FIBAApiService $fibaService
     * @param Tenant $tenant
     * @return void
     */
    private function syncTeamRegistration(FIBAIntegration $integration, FIBAApiService $fibaService, Tenant $tenant): void
    {
        $teamData = $integration->fiba_data['team_data'] ?? [];
        $competitionId = $integration->fiba_data['competition_id'] ?? null;
        
        if (empty($teamData) || !$competitionId) {
            throw new \InvalidArgumentException('Team data and competition ID required for team registration sync');
        }

        // If team is not yet registered, register it
        if (!$integration->fiba_id) {
            $registration = $fibaService->registerTeamForCompetition($teamData, $competitionId, $tenant);
            
            if (!$registration['success']) {
                throw new \RuntimeException('Team registration failed: ' . $registration['error']);
            }
            
            $fibaTeamId = $registration['fiba_team_id'];
            $syncedData = array_merge($teamData, [
                'registration' => $registration,
                'registered_at' => now()->toISOString(),
            ]);
        } else {
            // Team already registered, just update sync timestamp
            $fibaTeamId = $integration->fiba_id;
            $syncedData = array_merge($integration->fiba_data ?? [], [
                'last_synced' => now()->toISOString(),
            ]);
        }

        // Mark as synced
        $integration->markSynced($fibaTeamId, $syncedData);
    }

    /**
     * Sync club information
     *
     * @param FIBAIntegration $integration
     * @param FIBAApiService $fibaService
     * @param Tenant $tenant
     * @return void
     */
    private function syncClubInfo(FIBAIntegration $integration, FIBAApiService $fibaService, Tenant $tenant): void
    {
        $clubId = $integration->fiba_data['club_id'] ?? null;
        
        if (!$clubId) {
            throw new \InvalidArgumentException('Club ID required for club info sync');
        }

        // Get current club information from FIBA
        $clubInfo = $fibaService->getClubInfo($clubId, $tenant);
        
        if (!$clubInfo) {
            throw new \RuntimeException('Club not found in FIBA system');
        }

        $syncedData = array_merge($integration->fiba_data ?? [], [
            'club_data' => $clubInfo,
            'last_updated' => now()->toISOString(),
        ]);

        // Mark as synced
        $integration->markSynced($clubId, $syncedData);
    }

    /**
     * Sync official game data
     *
     * @param FIBAIntegration $integration
     * @param FIBAApiService $fibaService
     * @param Tenant $tenant
     * @return void
     */
    private function syncOfficialGameData(FIBAIntegration $integration, FIBAApiService $fibaService, Tenant $tenant): void
    {
        $gameId = $integration->fiba_data['game_id'] ?? null;
        
        if (!$gameId) {
            throw new \InvalidArgumentException('Game ID required for official game data sync');
        }

        // Get official game data from FIBA
        $gameData = $fibaService->getOfficialGameData($gameId, $tenant);
        
        if (!$gameData) {
            throw new \RuntimeException('Official game data not found in FIBA system');
        }

        $syncedData = array_merge($integration->fiba_data ?? [], [
            'official_data' => $gameData,
            'last_fetched' => now()->toISOString(),
        ]);

        // Mark as synced
        $integration->markSynced($gameId, $syncedData);
    }

    /**
     * Handle sync error
     *
     * @param FIBAIntegration $integration
     * @param \Exception $e
     * @return void
     */
    private function handleSyncError(FIBAIntegration $integration, \Exception $e): void
    {
        Log::error('FIBA data sync failed', [
            'integration_id' => $this->integrationId,
            'tenant_id' => $integration->tenant_id,
            'entity_type' => $integration->entity_type,
            'fiba_type' => $integration->fiba_type,
            'attempt' => $this->attempts(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        $integration->markFailed($e->getMessage());

        // Re-throw to trigger Laravel's retry mechanism
        throw $e;
    }

    /**
     * Handle job failure
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        $integration = FIBAIntegration::find($this->integrationId);
        
        if ($integration) {
            $integration->markFailed($exception->getMessage());
        }

        Log::error('FIBA sync job failed permanently', [
            'integration_id' => $this->integrationId,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Get the unique ID for the job
     *
     * @return string
     */
    public function uniqueId(): string
    {
        return 'fiba_sync_' . $this->integrationId;
    }

    /**
     * Get the tags that should be applied to the job
     *
     * @return array
     */
    public function tags(): array
    {
        $integration = FIBAIntegration::find($this->integrationId);
        
        $tags = ['fiba', 'federation', 'sync'];
        
        if ($integration) {
            $tags[] = $integration->entity_type;
            $tags[] = $integration->fiba_type;
            $tags[] = 'tenant:' . $integration->tenant_id;
        }
        
        return $tags;
    }
}
