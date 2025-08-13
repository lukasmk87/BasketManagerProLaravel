<?php

namespace App\Jobs;

use App\Services\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Job for sending push notifications asynchronously
 * Handles basketball-specific notification types with retry logic
 */
class SendPushNotification implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min
    public $timeout = 120; // 2 minutes

    private string $notificationType;
    private array $notificationData;
    private array $userIds;
    private ?string $tenantId;

    /**
     * Create a new job instance
     *
     * @param string $notificationType
     * @param array $notificationData
     * @param array $userIds
     * @param string|null $tenantId
     */
    public function __construct(
        string $notificationType,
        array $notificationData,
        array $userIds,
        ?string $tenantId = null
    ) {
        $this->notificationType = $notificationType;
        $this->notificationData = $notificationData;
        $this->userIds = $userIds;
        $this->tenantId = $tenantId;
    }

    /**
     * Execute the job
     */
    public function handle(PushNotificationService $pushService): void
    {
        Log::info('Processing push notification job', [
            'type' => $this->notificationType,
            'user_count' => count($this->userIds),
            'tenant_id' => $this->tenantId,
            'attempt' => $this->attempts(),
        ]);

        try {
            $users = \App\Models\User::whereIn('id', $this->userIds)->get();
            
            if ($users->isEmpty()) {
                Log::warning('No users found for push notification', [
                    'type' => $this->notificationType,
                    'user_ids' => $this->userIds,
                ]);
                return;
            }

            $result = $this->sendNotificationByType($pushService, $users);

            Log::info('Push notification job completed', [
                'type' => $this->notificationType,
                'tenant_id' => $this->tenantId,
                'result' => $result,
                'attempt' => $this->attempts(),
            ]);

        } catch (\Exception $e) {
            $this->handleJobFailure($e);
        }
    }

    /**
     * Send notification based on type
     *
     * @param PushNotificationService $pushService
     * @param Collection $users
     * @return array
     */
    private function sendNotificationByType(PushNotificationService $pushService, Collection $users): array
    {
        switch ($this->notificationType) {
            case 'game_start':
                return $pushService->sendGameStartNotification(
                    $this->notificationData['game_id'],
                    $this->notificationData
                );

            case 'player_foul':
                return $pushService->sendPlayerFoulNotification(
                    $this->notificationData['player_id'],
                    $this->notificationData
                );

            case 'training_reminder':
                return $pushService->sendTrainingReminder(
                    $this->notificationData['training_id'],
                    $this->notificationData
                );

            case 'emergency':
                return $pushService->sendEmergencyNotification(
                    $this->notificationData,
                    $this->tenantId
                );

            case 'score_update':
                return $pushService->sendScoreUpdate(
                    $this->notificationData['game_id'],
                    $this->notificationData
                );

            case 'federation_sync':
                return $pushService->sendFederationSyncNotification(
                    $this->notificationData['federation_type'],
                    $this->notificationData
                );

            case 'custom':
                return $pushService->sendCustomNotification(
                    $users,
                    $this->notificationData['notification']
                );

            case 'test':
                $results = ['sent' => 0, 'failed' => 0, 'expired' => 0, 'errors' => []];
                foreach ($users as $user) {
                    $userResult = $pushService->sendTestNotification($user);
                    $results['sent'] += $userResult['sent'];
                    $results['failed'] += $userResult['failed'];
                    $results['expired'] += $userResult['expired'];
                    $results['errors'] = array_merge($results['errors'], $userResult['errors']);
                }
                return $results;

            default:
                throw new \InvalidArgumentException("Unknown notification type: {$this->notificationType}");
        }
    }

    /**
     * Handle job failure
     *
     * @param \Exception $e
     * @return void
     */
    private function handleJobFailure(\Exception $e): void
    {
        Log::error('Push notification job failed', [
            'type' => $this->notificationType,
            'tenant_id' => $this->tenantId,
            'user_count' => count($this->userIds),
            'attempt' => $this->attempts(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        // Re-throw to trigger Laravel's retry mechanism
        throw $e;
    }

    /**
     * Handle job permanent failure
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Push notification job failed permanently', [
            'type' => $this->notificationType,
            'tenant_id' => $this->tenantId,
            'user_count' => count($this->userIds),
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);

        // Could send admin notification about failed push notifications
        // Or store in failed notifications table for later analysis
    }

    /**
     * Get the unique ID for the job
     *
     * @return string
     */
    public function uniqueId(): string
    {
        return 'push_notification_' . $this->notificationType . '_' . md5(serialize($this->userIds));
    }

    /**
     * Get the tags that should be applied to the job
     *
     * @return array
     */
    public function tags(): array
    {
        $tags = ['push_notification', $this->notificationType];
        
        if ($this->tenantId) {
            $tags[] = 'tenant:' . $this->tenantId;
        }
        
        return $tags;
    }

    /**
     * Calculate the number of seconds to wait before retrying the job
     *
     * @return array
     */
    public function backoff(): array
    {
        return $this->backoff;
    }

    /**
     * Determine if the job should be retried based on the exception
     *
     * @param \Throwable $exception
     * @return bool
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(30); // Stop retrying after 30 minutes
    }
}