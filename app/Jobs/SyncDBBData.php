<?php

namespace App\Jobs;

use App\Models\DBBIntegration;
use App\Models\Tenant;
use App\Services\Federation\DBBApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job for syncing data with DBB (Deutscher Basketball Bund) API
 * Handles periodic synchronization of player, team, and game data
 */
class SyncDBBData implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min
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
    public function handle(DBBApiService $dbbService): void
    {
        $integration = DBBIntegration::find($this->integrationId);
        
        if (!$integration) {
            Log::error('DBB integration not found for sync', [
                'integration_id' => $this->integrationId,
            ]);
            return;
        }

        $tenant = $integration->tenant;
        
        if (!$tenant) {
            Log::error('Tenant not found for DBB integration', [
                'integration_id' => $this->integrationId,
                'tenant_id' => $integration->tenant_id,
            ]);
            return;
        }

        Log::info('Starting DBB data sync', [
            'integration_id' => $this->integrationId,
            'tenant_id' => $tenant->id,
            'entity_type' => $integration->entity_type,
            'sync_type' => $this->syncType,
            'attempt' => $this->attempts(),
        ]);

        // Mark as syncing
        $integration->markSyncing();

        try {
            switch ($integration->entity_type) {
                case DBBIntegration::ENTITY_PLAYER:
                    $this->syncPlayerData($integration, $dbbService, $tenant);
                    break;
                
                case DBBIntegration::ENTITY_TEAM:
                    $this->syncTeamData($integration, $dbbService, $tenant);
                    break;
                
                case DBBIntegration::ENTITY_GAME:
                    $this->syncGameData($integration, $dbbService, $tenant);
                    break;
                
                default:
                    throw new \InvalidArgumentException("Unsupported entity type: {$integration->entity_type}");
            }

            Log::info('DBB data sync completed successfully', [
                'integration_id' => $this->integrationId,
                'tenant_id' => $tenant->id,
                'entity_type' => $integration->entity_type,
            ]);

        } catch (\Exception $e) {
            $this->handleSyncError($integration, $e);
        }
    }

    /**
     * Sync player data with DBB
     *
     * @param DBBIntegration $integration
     * @param DBBApiService $dbbService
     * @param Tenant $tenant
     * @return void
     */
    private function syncPlayerData(DBBIntegration $integration, DBBApiService $dbbService, Tenant $tenant): void
    {
        $licenseNumber = $integration->dbb_data['license_number'] ?? null;
        
        if (!$licenseNumber) {
            throw new \InvalidArgumentException('License number required for player sync');
        }

        // Get current player data from DBB
        $playerData = $dbbService->getPlayerByLicense($licenseNumber, $tenant);
        
        if (!$playerData) {
            throw new \RuntimeException('Player not found in DBB system');
        }

        // Validate player eligibility if league is specified
        $leagueId = $integration->dbb_data['league_id'] ?? null;
        $eligibility = null;
        
        if ($leagueId) {
            $eligibility = $dbbService->validatePlayerEligibility($licenseNumber, $leagueId, $tenant);
        }

        // Get transfer status
        $transferStatus = $dbbService->getPlayerTransferStatus($licenseNumber, $tenant);

        // Combine all data
        $syncedData = array_merge($integration->dbb_data ?? [], [
            'player_data' => $playerData,
            'eligibility' => $eligibility,
            'transfer_status' => $transferStatus,
            'last_validated' => now()->toISOString(),
        ]);

        // Mark as synced
        $integration->markSynced($licenseNumber, $syncedData);
    }

    /**
     * Sync team data with DBB
     *
     * @param DBBIntegration $integration
     * @param DBBApiService $dbbService
     * @param Tenant $tenant
     * @return void
     */
    private function syncTeamData(DBBIntegration $integration, DBBApiService $dbbService, Tenant $tenant): void
    {
        $teamData = $integration->dbb_data['team_data'] ?? [];
        
        if (empty($teamData)) {
            throw new \InvalidArgumentException('Team data required for team sync');
        }

        // If team is not yet registered, register it
        if (!$integration->dbb_id) {
            $registration = $dbbService->registerTeam($teamData, $tenant);
            
            if (!$registration['success']) {
                throw new \RuntimeException('Team registration failed: ' . $registration['error']);
            }
            
            $dbbTeamId = $registration['dbb_team_id'];
            $syncedData = array_merge($teamData, [
                'registration' => $registration,
                'registered_at' => now()->toISOString(),
            ]);
        } else {
            // Team already registered, just update sync timestamp
            $dbbTeamId = $integration->dbb_id;
            $syncedData = array_merge($integration->dbb_data ?? [], [
                'last_synced' => now()->toISOString(),
            ]);
        }

        // Mark as synced
        $integration->markSynced($dbbTeamId, $syncedData);
    }

    /**
     * Sync game data with DBB
     *
     * @param DBBIntegration $integration
     * @param DBBApiService $dbbService
     * @param Tenant $tenant
     * @return void
     */
    private function syncGameData(DBBIntegration $integration, DBBApiService $dbbService, Tenant $tenant): void
    {
        $gameData = $integration->dbb_data['game_data'] ?? [];
        
        if (empty($gameData) || !isset($gameData['dbb_game_id'])) {
            throw new \InvalidArgumentException('Game data with DBB game ID required for game sync');
        }

        // Submit game result to DBB
        $submission = $dbbService->submitGameResult($gameData, $tenant);
        
        if (!$submission['success']) {
            throw new \RuntimeException('Game result submission failed: ' . $submission['error']);
        }

        $syncedData = array_merge($integration->dbb_data ?? [], [
            'submission' => $submission,
            'submitted_at' => now()->toISOString(),
        ]);

        // Mark as synced
        $integration->markSynced($gameData['dbb_game_id'], $syncedData);
    }

    /**
     * Handle sync error
     *
     * @param DBBIntegration $integration
     * @param \Exception $e
     * @return void
     */
    private function handleSyncError(DBBIntegration $integration, \Exception $e): void
    {
        Log::error('DBB data sync failed', [
            'integration_id' => $this->integrationId,
            'tenant_id' => $integration->tenant_id,
            'entity_type' => $integration->entity_type,
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
        $integration = DBBIntegration::find($this->integrationId);
        
        if ($integration) {
            $integration->markFailed($exception->getMessage());
        }

        Log::error('DBB sync job failed permanently', [
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
        return 'dbb_sync_' . $this->integrationId;
    }

    /**
     * Get the tags that should be applied to the job
     *
     * @return array
     */
    public function tags(): array
    {
        $integration = DBBIntegration::find($this->integrationId);
        
        $tags = ['dbb', 'federation', 'sync'];
        
        if ($integration) {
            $tags[] = $integration->entity_type;
            $tags[] = 'tenant:' . $integration->tenant_id;
        }
        
        return $tags;
    }
}
