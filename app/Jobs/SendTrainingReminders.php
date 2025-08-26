<?php

namespace App\Jobs;

use App\Models\TrainingSession;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Job for sending training session reminders
 * Sends notifications to players and coaches before training sessions
 */
class SendTrainingReminders implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min
    public $timeout = 120; // 2 minutes

    private TrainingSession $trainingSession;

    /**
     * Create a new job instance
     *
     * @param TrainingSession $trainingSession
     */
    public function __construct(TrainingSession $trainingSession)
    {
        $this->trainingSession = $trainingSession;
    }

    /**
     * Execute the job
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            // Check if session is still scheduled (not cancelled or completed)
            $session = $this->trainingSession->fresh();
            
            if (!$session || $session->status !== 'scheduled') {
                Log::info("Training session {$session->id} is no longer scheduled, skipping reminder");
                return;
            }

            // Check if session is still in the future
            if ($session->scheduled_at <= now()) {
                Log::info("Training session {$session->id} has already started or passed, skipping reminder");
                return;
            }

            // Calculate time until session
            $timeUntil = now()->diffInHours($session->scheduled_at);
            $reminderType = $this->determineReminderType($timeUntil);

            // Get recipients
            $recipients = $this->getRecipients($session);

            if (empty($recipients)) {
                Log::warning("No recipients found for training session {$session->id} reminder");
                return;
            }

            // Prepare notification data
            $notificationData = [
                'title' => $this->getNotificationTitle($reminderType),
                'message' => $this->getNotificationMessage($session, $reminderType),
                'session_id' => $session->id,
                'session_title' => $session->title,
                'scheduled_at' => $session->scheduled_at->toISOString(),
                'venue' => $session->venue,
                'venue_address' => $session->venue_address,
                'team_name' => $session->team->name,
                'reminder_type' => $reminderType,
                'action_url' => route('training.sessions.show', $session),
            ];

            // Send notifications
            foreach ($recipients as $recipient) {
                $this->sendNotification($recipient, $notificationData);
            }

            Log::info("Successfully sent {$reminderType} reminders for training session {$session->id} to " . count($recipients) . " recipients");

        } catch (\Exception $e) {
            Log::error("Failed to send training reminders for session {$this->trainingSession->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("SendTrainingReminders job failed for session {$this->trainingSession->id}: " . $exception->getMessage());
    }

    /**
     * Get notification recipients
     *
     * @param TrainingSession $session
     * @return array
     */
    private function getRecipients(TrainingSession $session): array
    {
        $recipients = [];

        // Add team players
        $activePlayers = $session->team->activePlayers()->with('user')->get();
        foreach ($activePlayers as $player) {
            if ($player->user && $player->user->notification_preferences['training_reminders'] ?? true) {
                $recipients[] = [
                    'type' => 'player',
                    'user_id' => $player->user_id,
                    'player_id' => $player->id,
                    'name' => $player->user->name,
                    'email' => $player->user->email,
                ];
            }
        }

        // Add coaches
        if ($session->trainer_id) {
            $trainer = \App\Models\User::find($session->trainer_id);
            if ($trainer && ($trainer->notification_preferences['training_reminders'] ?? true)) {
                $recipients[] = [
                    'type' => 'coach',
                    'user_id' => $trainer->id,
                    'name' => $trainer->name,
                    'email' => $trainer->email,
                ];
            }
        }

        if ($session->assistant_trainer_id) {
            $assistantTrainer = \App\Models\User::find($session->assistant_trainer_id);
            if ($assistantTrainer && ($assistantTrainer->notification_preferences['training_reminders'] ?? true)) {
                $recipients[] = [
                    'type' => 'coach',
                    'user_id' => $assistantTrainer->id,
                    'name' => $assistantTrainer->name,
                    'email' => $assistantTrainer->email,
                ];
            }
        }

        return $recipients;
    }

    /**
     * Determine reminder type based on time until session
     *
     * @param int $hoursUntil
     * @return string
     */
    private function determineReminderType(int $hoursUntil): string
    {
        if ($hoursUntil >= 12) {
            return '24_hour';
        } elseif ($hoursUntil >= 1) {
            return '2_hour';
        } else {
            return 'last_minute';
        }
    }

    /**
     * Get notification title based on reminder type
     *
     * @param string $reminderType
     * @return string
     */
    private function getNotificationTitle(string $reminderType): string
    {
        return match ($reminderType) {
            '24_hour' => 'Training morgen',
            '2_hour' => 'Training in 2 Stunden',
            'last_minute' => 'Training startet bald',
            default => 'Training-Erinnerung'
        };
    }

    /**
     * Get notification message
     *
     * @param TrainingSession $session
     * @param string $reminderType
     * @return string
     */
    private function getNotificationMessage(TrainingSession $session, string $reminderType): string
    {
        $timeText = match ($reminderType) {
            '24_hour' => 'morgen um ' . $session->scheduled_at->format('H:i'),
            '2_hour' => 'in 2 Stunden',
            'last_minute' => 'in wenigen Minuten',
            default => 'am ' . $session->scheduled_at->format('d.m.Y \u\m H:i')
        };

        return "Das Training '{$session->title}' findet {$timeText} in {$session->venue} statt.";
    }

    /**
     * Send notification to recipient
     *
     * @param array $recipient
     * @param array $notificationData
     * @return void
     */
    private function sendNotification(array $recipient, array $notificationData): void
    {
        try {
            // Create database notification
            \App\Models\User::find($recipient['user_id'])?->notifications()->create([
                'type' => 'training_reminder',
                'data' => array_merge($notificationData, [
                    'recipient_type' => $recipient['type']
                ]),
                'created_at' => now(),
            ]);

            // Send push notification if enabled
            if (class_exists('\App\Services\PushNotificationService')) {
                \App\Jobs\SendPushNotification::dispatch(
                    'training_reminder',
                    $notificationData,
                    [$recipient['user_id']],
                    $this->trainingSession->team->club->tenant_id ?? null
                );
            }

            // Send email notification for important reminders
            if (in_array($notificationData['reminder_type'], ['24_hour']) && config('mail.enabled', false)) {
                \Illuminate\Support\Facades\Mail::to($recipient['email'])->queue(
                    new \App\Mail\TrainingReminderMail($this->trainingSession, $recipient, $notificationData)
                );
            }

        } catch (\Exception $e) {
            Log::warning("Failed to send notification to {$recipient['email']}: " . $e->getMessage());
        }
    }
}