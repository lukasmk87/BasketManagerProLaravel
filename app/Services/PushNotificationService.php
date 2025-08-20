<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

/**
 * Push Notification Service
 * 
 * Handles sending web push notifications for basketball-specific events
 * including game updates, player alerts, training reminders, and emergency notifications
 */
class PushNotificationService
{
    private WebPush $webPush;
    
    public function __construct()
    {
        $this->webPush = new WebPush([
            'VAPID' => [
                'subject' => config('app.url'),
                'publicKey' => config('webpush.vapid.public_key'),
                'privateKey' => config('webpush.vapid.private_key'),
            ]
        ]);
    }
    
    /**
     * Send game start notification to relevant users
     *
     * @param string $gameId
     * @param array $gameData
     * @return array
     */
    public function sendGameStartNotification(string $gameId, array $gameData): array
    {
        $notification = [
            'type' => 'game_start',
            'title' => 'Spiel beginnt',
            'body' => sprintf(
                '%s vs %s startet in 15 Minuten',
                $gameData['home_team'] ?? 'Heimteam',
                $gameData['away_team'] ?? 'Auswärtsteam'
            ),
            'icon' => '/images/notifications/game-start.png',
            'badge' => '/images/badge-game.png',
            'data' => [
                'game_id' => $gameId,
                'type' => 'game_start',
                'url' => "/games/{$gameId}/live",
                'actions' => [
                    ['action' => 'view-game', 'title' => 'Spiel ansehen'],
                    ['action' => 'start-scoring', 'title' => 'Live-Scoring starten']
                ]
            ],
            'vibrate' => [200, 100, 200],
            'requireInteraction' => true
        ];
        
        // Send to coaches, players, and referees involved in the game
        $recipients = $this->getGameParticipants($gameId, $gameData);
        
        return $this->sendToUsers($recipients, $notification);
    }
    
    /**
     * Send player foul notification
     *
     * @param string $playerId
     * @param array $foulData
     * @return array
     */
    public function sendPlayerFoulNotification(string $playerId, array $foulData): array
    {
        $notification = [
            'type' => 'player_foul',
            'title' => 'Spieler-Foul',
            'body' => sprintf(
                '%s - %s. Foul (%s)',
                $foulData['player_name'] ?? 'Spieler',
                $foulData['foul_count'] ?? '?',
                $foulData['foul_type'] ?? 'Persönliches Foul'
            ),
            'icon' => '/images/notifications/foul.png',
            'data' => [
                'player_id' => $playerId,
                'game_id' => $foulData['game_id'] ?? null,
                'type' => 'player_foul',
                'url' => "/players/{$playerId}",
                'foul_data' => $foulData
            ],
            'vibrate' => [100, 50, 100]
        ];
        
        // Send to coaches and team management
        $recipients = $this->getPlayerManagementUsers($playerId);
        
        return $this->sendToUsers($recipients, $notification);
    }
    
    /**
     * Send training reminder notification
     *
     * @param string $trainingId
     * @param array $trainingData
     * @return array
     */
    public function sendTrainingReminder(string $trainingId, array $trainingData): array
    {
        $notification = [
            'type' => 'training_reminder',
            'title' => 'Training Erinnerung',
            'body' => sprintf(
                'Training "%s" beginnt in %d Minuten',
                $trainingData['name'] ?? 'Trainingseinheit',
                $trainingData['minutes_until'] ?? 30
            ),
            'icon' => '/images/notifications/training.png',
            'data' => [
                'training_id' => $trainingId,
                'type' => 'training_reminder',
                'url' => "/training/{$trainingId}",
                'actions' => [
                    ['action' => 'view-training', 'title' => 'Training ansehen'],
                    ['action' => 'mark-attended', 'title' => 'Als teilgenommen markieren']
                ]
            ]
        ];
        
        // Send to players and coaches for this training
        $recipients = $this->getTrainingParticipants($trainingId, $trainingData);
        
        return $this->sendToUsers($recipients, $notification);
    }
    
    /**
     * Send emergency notification to all relevant users
     *
     * @param array $emergencyData
     * @param string|null $tenantId
     * @return array
     */
    public function sendEmergencyNotification(array $emergencyData, ?string $tenantId = null): array
    {
        $notification = [
            'type' => 'emergency',
            'title' => '🚨 NOTFALL',
            'body' => $emergencyData['message'] ?? 'Notfall-Situation - Bitte sofort prüfen',
            'icon' => '/images/notifications/emergency.png',
            'badge' => '/images/badge-emergency.png',
            'data' => [
                'type' => 'emergency',
                'emergency_type' => $emergencyData['type'] ?? 'general',
                'location' => $emergencyData['location'] ?? null,
                'contact' => $emergencyData['contact'] ?? null,
                'url' => '/emergency'
            ],
            'vibrate' => [300, 100, 300, 100, 300],
            'requireInteraction' => true,
            'silent' => false
        ];
        
        // Send to all users in tenant (or specific recipients)
        $recipients = $emergencyData['recipients'] ?? $this->getAllTenantUsers($tenantId);
        
        return $this->sendToUsers($recipients, $notification);
    }
    
    /**
     * Send score update notification
     *
     * @param string $gameId
     * @param array $scoreData
     * @return array
     */
    public function sendScoreUpdate(string $gameId, array $scoreData): array
    {
        $notification = [
            'type' => 'score_update',
            'title' => 'Spielstand Update',
            'body' => sprintf(
                '%s %d : %d %s (%s)',
                $scoreData['home_team'] ?? 'Heim',
                $scoreData['home_score'] ?? 0,
                $scoreData['away_score'] ?? 0,
                $scoreData['away_team'] ?? 'Auswärts',
                $scoreData['quarter'] ?? 'Q?'
            ),
            'icon' => '/images/notifications/score.png',
            'data' => [
                'game_id' => $gameId,
                'type' => 'score_update',
                'url' => "/games/{$gameId}/live",
                'score_data' => $scoreData
            ],
            'vibrate' => [50, 30, 50]
        ];
        
        // Send to team followers and interested parties
        $recipients = $this->getGameFollowers($gameId);
        
        return $this->sendToUsers($recipients, $notification);
    }
    
    /**
     * Send federation sync notification (DBB/FIBA updates)
     *
     * @param string $federationType
     * @param array $syncData
     * @return array
     */
    public function sendFederationSyncNotification(string $federationType, array $syncData): array
    {
        $federationNames = [
            'dbb' => 'Deutscher Basketball Bund',
            'fiba' => 'FIBA Europe'
        ];
        
        $notification = [
            'type' => 'federation_sync',
            'title' => 'Verband Update',
            'body' => sprintf(
                '%s Daten wurden aktualisiert: %s',
                $federationNames[$federationType] ?? $federationType,
                $syncData['message'] ?? 'Neue Informationen verfügbar'
            ),
            'icon' => '/images/notifications/federation.png',
            'data' => [
                'type' => 'federation_sync',
                'federation_type' => $federationType,
                'sync_data' => $syncData,
                'url' => "/federation/{$federationType}"
            ]
        ];
        
        // Send to club administrators and coaches
        $recipients = $this->getFederationAdministrators();
        
        return $this->sendToUsers($recipients, $notification);
    }
    
    /**
     * Send custom notification to specific users
     *
     * @param array|Collection $users
     * @param array $notification
     * @return array
     */
    public function sendCustomNotification($users, array $notification): array
    {
        return $this->sendToUsers($users, $notification);
    }
    
    /**
     * Send notification to users
     *
     * @param array|Collection $users
     * @param array $notification
     * @return array
     */
    private function sendToUsers($users, array $notification): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'expired' => 0,
            'errors' => []
        ];
        
        $userCollection = $users instanceof Collection ? $users : collect($users);
        
        foreach ($userCollection as $user) {
            $userResult = $this->sendToUser($user, $notification);
            
            $results['sent'] += $userResult['sent'];
            $results['failed'] += $userResult['failed'];
            $results['expired'] += $userResult['expired'];
            
            if (!empty($userResult['errors'])) {
                $results['errors'] = array_merge($results['errors'], $userResult['errors']);
            }
        }
        
        Log::info('Push notification batch sent', [
            'notification_type' => $notification['type'] ?? 'unknown',
            'recipients' => $userCollection->count(),
            'results' => $results
        ]);
        
        return $results;
    }
    
    /**
     * Send notification to a single user
     *
     * @param User $user
     * @param array $notification
     * @return array
     */
    private function sendToUser(User $user, array $notification): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'expired' => 0,
            'errors' => []
        ];
        
        $subscriptions = $user->pushSubscriptions()->active()->get();
        
        if ($subscriptions->isEmpty()) {
            return $results;
        }
        
        foreach ($subscriptions as $subscription) {
            try {
                $webPushSubscription = Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'keys' => [
                        'p256dh' => $subscription->p256dh_key,
                        'auth' => $subscription->auth_token,
                    ]
                ]);
                
                $payload = json_encode($notification);
                
                $result = $this->webPush->sendOneNotification(
                    $webPushSubscription,
                    $payload
                );
                
                if ($result->isSuccess()) {
                    $results['sent']++;
                    $subscription->markAsUsed();
                } else {
                    $results['failed']++;
                    
                    // Check if subscription is expired
                    if ($result->isSubscriptionExpired()) {
                        $results['expired']++;
                        $subscription->markAsInactive();
                    }
                    
                    $results['errors'][] = [
                        'user_id' => $user->id,
                        'subscription_id' => $subscription->id,
                        'error' => $result->getReason()
                    ];
                }
                
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage()
                ];
                
                Log::error('Push notification failed', [
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $results;
    }
    
    /**
     * Get users participating in a game
     *
     * @param string $gameId
     * @param array $gameData
     * @return Collection
     */
    private function getGameParticipants(string $gameId, array $gameData): Collection
    {
        // This would query the actual game participants
        // For now, return empty collection as placeholder
        return collect();
    }
    
    /**
     * Get users who manage a specific player
     *
     * @param string $playerId
     * @return Collection
     */
    private function getPlayerManagementUsers(string $playerId): Collection
    {
        // This would query coaches and managers for the player's team
        // For now, return empty collection as placeholder
        return collect();
    }
    
    /**
     * Get users participating in a training session
     *
     * @param string $trainingId
     * @param array $trainingData
     * @return Collection
     */
    private function getTrainingParticipants(string $trainingId, array $trainingData): Collection
    {
        // This would query players and coaches for the training session
        // For now, return empty collection as placeholder
        return collect();
    }
    
    /**
     * Get users following a specific game
     *
     * @param string $gameId
     * @return Collection
     */
    private function getGameFollowers(string $gameId): Collection
    {
        // This would query users who follow the teams in this game
        // For now, return empty collection as placeholder
        return collect();
    }
    
    /**
     * Get federation administrators
     *
     * @return Collection
     */
    private function getFederationAdministrators(): Collection
    {
        return User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin', 'club_admin', 'federation_admin']);
        })->get();
    }
    
    /**
     * Get all users in a tenant
     *
     * @param string|null $tenantId
     * @return Collection
     */
    private function getAllTenantUsers(?string $tenantId): Collection
    {
        if (!$tenantId) {
            return collect();
        }
        
        return User::where('tenant_id', $tenantId)->get();
    }

    /**
     * Get club teams except the original team
     *
     * @param string|null $clubId
     * @param string|null $originalTeamId
     * @return Collection
     */
    private function getClubTeamsExceptOriginal(?string $clubId, ?string $originalTeamId): Collection
    {
        if (!$clubId) {
            return collect();
        }

        $query = User::whereHas('clubs', function ($q) use ($clubId) {
            $q->where('club_id', $clubId);
        })->whereHas('teams', function ($q) use ($originalTeamId) {
            if ($originalTeamId) {
                $q->where('team_id', '!=', $originalTeamId);
            }
        });

        return $query->get();
    }

    /**
     * Get team trainers/coaches
     *
     * @param string|null $teamId
     * @return Collection
     */
    private function getTeamTrainers(?string $teamId): Collection
    {
        if (!$teamId) {
            return collect();
        }

        return User::whereHas('teams', function ($query) use ($teamId) {
            $query->where('team_id', $teamId)
                  ->wherePivotIn('role', ['trainer', 'assistant_trainer']);
        })->get();
    }

    /**
     * Get team members
     *
     * @param string|null $teamId
     * @return Collection
     */
    private function getTeamMembers(?string $teamId): Collection
    {
        if (!$teamId) {
            return collect();
        }

        return User::whereHas('teams', function ($query) use ($teamId) {
            $query->where('team_id', $teamId);
        })->get();
    }

    /**
     * Get club administrators
     *
     * @param string|null $clubId
     * @return Collection
     */
    private function getClubAdministrators(?string $clubId): Collection
    {
        if (!$clubId) {
            return collect();
        }

        return User::whereHas('clubs', function ($query) use ($clubId) {
            $query->where('club_id', $clubId)
                  ->wherePivotIn('role', ['admin', 'owner']);
        })->get();
    }
    
    /**
     * Schedule delayed notification
     *
     * @param array $notification
     * @param array|Collection $users
     * @param \DateTime $sendAt
     * @return bool
     */
    public function scheduleNotification(array $notification, $users, \DateTime $sendAt): bool
    {
        // This would integrate with Laravel's job queue to schedule notifications
        // For now, just log the scheduling
        Log::info('Push notification scheduled', [
            'notification_type' => $notification['type'] ?? 'unknown',
            'recipients' => is_array($users) ? count($users) : $users->count(),
            'send_at' => $sendAt->format('Y-m-d H:i:s')
        ]);
        
        return true;
    }
    
    /**
     * Get notification statistics for tenant
     *
     * @param string $tenantId
     * @param int $days
     * @return array
     */
    public function getNotificationStats(string $tenantId, int $days = 30): array
    {
        // This would query notification logs and provide statistics
        return [
            'total_sent' => 0,
            'total_failed' => 0,
            'types' => [],
            'by_day' => [],
            'subscription_count' => PushSubscription::whereHas('user', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })->active()->count()
        ];
    }
    
    /**
     * Send gym time released notification
     *
     * @param string $bookingId
     * @param array $bookingData
     * @return array
     */
    public function sendGymTimeReleasedNotification(string $bookingId, array $bookingData): array
    {
        $notification = [
            'type' => 'gym_time_released',
            'title' => '🏀 Hallenzeit freigegeben',
            'body' => sprintf(
                '%s am %s um %s ist jetzt verfügbar',
                $bookingData['gym_hall_name'] ?? 'Sporthalle',
                $bookingData['date'] ?? 'Unbekanntes Datum',
                $bookingData['start_time'] ?? 'Unbekannte Zeit'
            ),
            'icon' => '/images/notifications/gym-released.png',
            'badge' => '/images/badge-gym.png',
            'data' => [
                'booking_id' => $bookingId,
                'type' => 'gym_time_released',
                'url' => "/gym/bookings/available",
                'booking_data' => $bookingData,
                'actions' => [
                    ['action' => 'view-available', 'title' => 'Verfügbare Zeiten ansehen'],
                    ['action' => 'request-booking', 'title' => 'Zeit anfragen']
                ]
            ],
            'vibrate' => [200, 100, 200],
            'requireInteraction' => true
        ];
        
        // Send to all teams in the same club except the releasing team
        $recipients = $this->getClubTeamsExceptOriginal($bookingData['club_id'] ?? null, $bookingData['original_team_id'] ?? null);
        
        return $this->sendToUsers($recipients, $notification);
    }

    /**
     * Send gym booking request notification
     *
     * @param string $requestId
     * @param array $requestData
     * @return array
     */
    public function sendGymBookingRequestNotification(string $requestId, array $requestData): array
    {
        $notification = [
            'type' => 'gym_booking_requested',
            'title' => '📋 Neue Buchungsanfrage',
            'body' => sprintf(
                '%s möchte %s am %s buchen',
                $requestData['requesting_team_name'] ?? 'Ein Team',
                $requestData['gym_hall_name'] ?? 'eine Sporthalle',
                $requestData['date'] ?? 'einem Termin'
            ),
            'icon' => '/images/notifications/booking-request.png',
            'data' => [
                'request_id' => $requestId,
                'type' => 'gym_booking_requested',
                'url' => "/gym/requests",
                'request_data' => $requestData,
                'actions' => [
                    ['action' => 'approve-request', 'title' => 'Genehmigen'],
                    ['action' => 'reject-request', 'title' => 'Ablehnen']
                ]
            ],
            'vibrate' => [300, 100, 300],
            'requireInteraction' => true
        ];
        
        // Send to trainers/coaches of the original team
        $recipients = $this->getTeamTrainers($requestData['original_team_id'] ?? null);
        
        return $this->sendToUsers($recipients, $notification);
    }

    /**
     * Send gym booking approved notification
     *
     * @param string $requestId
     * @param array $requestData
     * @return array
     */
    public function sendGymBookingApprovedNotification(string $requestId, array $requestData): array
    {
        $notification = [
            'type' => 'gym_booking_approved',
            'title' => '✅ Buchung genehmigt',
            'body' => sprintf(
                'Ihre Anfrage für %s am %s wurde genehmigt',
                $requestData['gym_hall_name'] ?? 'die Sporthalle',
                $requestData['date'] ?? 'den gewünschten Termin'
            ),
            'icon' => '/images/notifications/booking-approved.png',
            'data' => [
                'request_id' => $requestId,
                'booking_id' => $requestData['booking_id'] ?? null,
                'type' => 'gym_booking_approved',
                'url' => "/gym/my-bookings",
                'request_data' => $requestData
            ],
            'vibrate' => [200, 100, 200, 100, 200]
        ];
        
        // Send to requesting team members
        $recipients = $this->getTeamMembers($requestData['requesting_team_id'] ?? null);
        
        return $this->sendToUsers($recipients, $notification);
    }

    /**
     * Send gym booking rejected notification
     *
     * @param string $requestId
     * @param array $requestData
     * @return array
     */
    public function sendGymBookingRejectedNotification(string $requestId, array $requestData): array
    {
        $notification = [
            'type' => 'gym_booking_rejected',
            'title' => '❌ Buchung abgelehnt',
            'body' => sprintf(
                'Ihre Anfrage für %s am %s wurde abgelehnt',
                $requestData['gym_hall_name'] ?? 'die Sporthalle',
                $requestData['date'] ?? 'den gewünschten Termin'
            ),
            'icon' => '/images/notifications/booking-rejected.png',
            'data' => [
                'request_id' => $requestId,
                'type' => 'gym_booking_rejected',
                'url' => "/gym/available-times",
                'request_data' => $requestData,
                'rejection_reason' => $requestData['rejection_reason'] ?? null
            ],
            'vibrate' => [100, 50, 100]
        ];
        
        // Send to requesting team members
        $recipients = $this->getTeamMembers($requestData['requesting_team_id'] ?? null);
        
        return $this->sendToUsers($recipients, $notification);
    }

    /**
     * Send gym booking reminder notification
     *
     * @param string $bookingId
     * @param array $bookingData
     * @return array
     */
    public function sendGymBookingReminderNotification(string $bookingId, array $bookingData): array
    {
        $minutesUntil = $bookingData['minutes_until'] ?? 30;
        
        $notification = [
            'type' => 'gym_booking_reminder',
            'title' => '⏰ Hallenzeit-Erinnerung',
            'body' => sprintf(
                'Ihr Training in %s beginnt in %d Minuten',
                $bookingData['gym_hall_name'] ?? 'der Sporthalle',
                $minutesUntil
            ),
            'icon' => '/images/notifications/reminder.png',
            'data' => [
                'booking_id' => $bookingId,
                'type' => 'gym_booking_reminder',
                'url' => "/gym/halls/{$bookingData['gym_hall_id']}",
                'booking_data' => $bookingData,
                'actions' => [
                    ['action' => 'view-hall', 'title' => 'Halle ansehen'],
                    ['action' => 'start-session', 'title' => 'Training starten']
                ]
            ],
            'vibrate' => [100, 50, 100, 50, 100]
        ];
        
        // Send to team members
        $recipients = $this->getTeamMembers($bookingData['team_id'] ?? null);
        
        return $this->sendToUsers($recipients, $notification);
    }

    /**
     * Send gym schedule conflict notification
     *
     * @param array $conflictData
     * @return array
     */
    public function sendGymScheduleConflictNotification(array $conflictData): array
    {
        $notification = [
            'type' => 'gym_schedule_conflict',
            'title' => '⚠️ Hallenplan-Konflikt',
            'body' => sprintf(
                'Konflikt erkannt: %s am %s',
                $conflictData['gym_hall_name'] ?? 'Sporthalle',
                $conflictData['date'] ?? 'einem Termin'
            ),
            'icon' => '/images/notifications/conflict.png',
            'badge' => '/images/badge-warning.png',
            'data' => [
                'type' => 'gym_schedule_conflict',
                'url' => "/gym/conflicts",
                'conflict_data' => $conflictData
            ],
            'vibrate' => [300, 100, 300, 100, 300],
            'requireInteraction' => true
        ];
        
        // Send to club administrators
        $recipients = $this->getClubAdministrators($conflictData['club_id'] ?? null);
        
        return $this->sendToUsers($recipients, $notification);
    }

    /**
     * Test notification delivery to a user
     *
     * @param User $user
     * @return array
     */
    public function sendTestNotification(User $user): array
    {
        $notification = [
            'type' => 'test',
            'title' => 'Test Benachrichtigung',
            'body' => 'Dies ist eine Test-Benachrichtigung von BasketManager Pro',
            'icon' => '/images/logo-192.png',
            'data' => [
                'type' => 'test',
                'timestamp' => now()->toISOString()
            ]
        ];
        
        return $this->sendToUser($user, $notification);
    }
    
    /**
     * Validate VAPID configuration
     *
     * @return bool
     */
    public function validateVapidConfig(): bool
    {
        $publicKey = config('webpush.vapid.public_key');
        $privateKey = config('webpush.vapid.private_key');
        
        return !empty($publicKey) && !empty($privateKey);
    }
}