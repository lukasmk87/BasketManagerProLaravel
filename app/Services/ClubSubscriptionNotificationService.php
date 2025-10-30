<?php

namespace App\Services;

use App\Mail\ClubSubscription\HighChurnAlertMail;
use App\Mail\ClubSubscription\PaymentFailedMail;
use App\Mail\ClubSubscription\PaymentSuccessfulMail;
use App\Mail\ClubSubscription\SubscriptionAnalyticsReportMail;
use App\Mail\ClubSubscription\SubscriptionCanceledMail;
use App\Mail\ClubSubscription\SubscriptionWelcomeMail;
use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use App\Models\NotificationLog;
use App\Models\NotificationPreference;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ClubSubscriptionNotificationService
{
    /**
     * Event type constants for notification preferences.
     */
    public const PAYMENT_SUCCEEDED = 'payment_succeeded';
    public const PAYMENT_FAILED = 'payment_failed';
    public const SUBSCRIPTION_WELCOME = 'subscription_welcome';
    public const SUBSCRIPTION_CANCELED = 'subscription_canceled';
    public const HIGH_CHURN_ALERT = 'high_churn_alert';
    public const ANALYTICS_REPORT = 'analytics_report';
    public const INVOICE_CREATED = 'invoice_created';
    public const INVOICE_FINALIZED = 'invoice_finalized';
    public const TRIAL_ENDING_SOON = 'trial_ending_soon';
    public const SUBSCRIPTION_RENEWED = 'subscription_renewed';

    /**
     * Rate limit periods in seconds.
     */
    private const RATE_LIMIT_HIGH_CHURN = 86400; // 24 hours
    private const RATE_LIMIT_ANALYTICS = 86400; // 24 hours

    /**
     * Send a notification with preference checking and logging.
     *
     * @param Mailable $mail The mail instance to send
     * @param mixed $notifiable The notifiable entity (Club, Tenant, etc.)
     * @param User $recipient The user receiving the notification
     * @param string $eventType The event type constant
     * @param array $metadata Additional metadata to store in log
     * @return NotificationLog|null The created notification log or null if not sent
     */
    public function send(
        Mailable $mail,
        $notifiable,
        User $recipient,
        string $eventType,
        array $metadata = []
    ): ?NotificationLog {
        // Check if user has enabled this notification
        if (!$this->canSend($recipient, 'email', $eventType, $notifiable)) {
            Log::info('Notification skipped due to user preference', [
                'user_id' => $recipient->id,
                'event_type' => $eventType,
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id,
            ]);

            return null;
        }

        // Create notification log
        $log = $this->createNotificationLog(
            $mail,
            $notifiable,
            $recipient,
            $eventType,
            $metadata
        );

        // Queue the mail
        try {
            $this->queueMail($mail, $recipient);

            Log::info('Notification queued successfully', [
                'log_id' => $log->id,
                'recipient_email' => $recipient->email,
                'event_type' => $eventType,
            ]);
        } catch (\Exception $e) {
            $log->markAsFailed($e->getMessage());

            Log::error('Failed to queue notification', [
                'log_id' => $log->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $log;
    }

    /**
     * Check if a notification can be sent to the user.
     *
     * @param User $user
     * @param string $channel
     * @param string $eventType
     * @param mixed $notifiable
     * @return bool
     */
    public function canSend(User $user, string $channel, string $eventType, $notifiable): bool
    {
        // Look for existing preference
        $preference = NotificationPreference::query()
            ->forUser($user)
            ->channel($channel)
            ->eventType($eventType)
            ->forNotifiable($notifiable)
            ->first();

        // If no preference exists, default to enabled
        if (!$preference) {
            return true;
        }

        return $preference->isEnabled();
    }

    /**
     * Send payment successful notification to club admins.
     *
     * @param Club $club
     * @param array $invoiceData
     * @return void
     */
    public function sendPaymentSuccessful(Club $club, array $invoiceData): void
    {
        // Resolve recipients (club admins)
        $recipients = $this->resolveRecipients($club, self::PAYMENT_SUCCEEDED);

        foreach ($recipients as $recipient) {
            $mail = new PaymentSuccessfulMail(
                $club,
                $invoiceData,
                $invoiceData['pdf_url'] ?? null
            );

            $this->send(
                $mail,
                $club,
                $recipient,
                self::PAYMENT_SUCCEEDED,
                ['invoice_number' => $invoiceData['number'] ?? null]
            );
        }
    }

    /**
     * Send payment failed notification to club admins.
     *
     * @param Club $club
     * @param array $invoiceData
     * @param string $failureReason
     * @return void
     */
    public function sendPaymentFailed(Club $club, array $invoiceData, string $failureReason): void
    {
        // Resolve recipients (club admins)
        $recipients = $this->resolveRecipients($club, self::PAYMENT_FAILED);

        foreach ($recipients as $recipient) {
            $mail = new PaymentFailedMail(
                $club,
                $invoiceData,
                $failureReason,
                $invoiceData['grace_period_days'] ?? 3,
                $invoiceData['retry_attempts'] ?? null
            );

            $this->send(
                $mail,
                $club,
                $recipient,
                self::PAYMENT_FAILED,
                [
                    'invoice_number' => $invoiceData['number'] ?? null,
                    'failure_reason' => $failureReason,
                ]
            );
        }
    }

    /**
     * Send subscription welcome notification to club admins.
     *
     * @param Club $club
     * @param ClubSubscriptionPlan $plan
     * @return void
     */
    public function sendSubscriptionWelcome(Club $club, ClubSubscriptionPlan $plan): void
    {
        // Resolve recipients (club admins)
        $recipients = $this->resolveRecipients($club, self::SUBSCRIPTION_WELCOME);

        $isTrialActive = $club->isOnTrial();
        $trialDaysRemaining = $isTrialActive ? $club->trialDaysRemaining() : null;

        foreach ($recipients as $recipient) {
            $mail = new SubscriptionWelcomeMail(
                $club,
                $plan,
                $isTrialActive,
                $trialDaysRemaining
            );

            $this->send(
                $mail,
                $club,
                $recipient,
                self::SUBSCRIPTION_WELCOME,
                [
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                    'is_trial' => $isTrialActive,
                ]
            );
        }
    }

    /**
     * Send subscription canceled notification to club admins.
     *
     * @param Club $club
     * @param string $cancellationReason
     * @param Carbon|null $accessUntil
     * @return void
     */
    public function sendSubscriptionCanceled(
        Club $club,
        string $cancellationReason,
        ?Carbon $accessUntil = null
    ): void {
        // Resolve recipients (club admins)
        $recipients = $this->resolveRecipients($club, self::SUBSCRIPTION_CANCELED);

        $immediatelyCanceled = $accessUntil === null || $accessUntil->isPast();

        foreach ($recipients as $recipient) {
            $mail = new SubscriptionCanceledMail(
                $club,
                $cancellationReason,
                $accessUntil,
                $immediatelyCanceled
            );

            $this->send(
                $mail,
                $club,
                $recipient,
                self::SUBSCRIPTION_CANCELED,
                [
                    'cancellation_reason' => $cancellationReason,
                    'immediately_canceled' => $immediatelyCanceled,
                ]
            );
        }
    }

    /**
     * Send high churn alert to tenant admins.
     *
     * @param Tenant $tenant
     * @param array $churnData
     * @return void
     */
    public function sendHighChurnAlert(Tenant $tenant, array $churnData): void
    {
        // Check rate limit
        if ($this->shouldRateLimit(self::HIGH_CHURN_ALERT, $tenant)) {
            Log::info('High churn alert skipped due to rate limiting', [
                'tenant_id' => $tenant->id,
            ]);
            return;
        }

        // Resolve recipients (tenant admins)
        $recipients = $this->resolveRecipients($tenant, self::HIGH_CHURN_ALERT);

        foreach ($recipients as $recipient) {
            $mail = new HighChurnAlertMail($tenant, $churnData);

            $log = $this->send(
                $mail,
                $tenant,
                $recipient,
                self::HIGH_CHURN_ALERT,
                [
                    'churn_rate' => $churnData['churn_rate'] ?? null,
                    'period' => $churnData['period'] ?? null,
                ]
            );

            // Update last sent timestamp for rate limiting
            if ($log) {
                Cache::put(
                    "notification:last_sent:{$tenant->id}:" . self::HIGH_CHURN_ALERT,
                    now(),
                    self::RATE_LIMIT_HIGH_CHURN
                );
            }
        }
    }

    /**
     * Send analytics report to tenant admins.
     *
     * @param Tenant $tenant
     * @param array $reportData
     * @param string $reportPeriod
     * @return void
     */
    public function sendAnalyticsReport(Tenant $tenant, array $reportData, string $reportPeriod = 'monthly'): void
    {
        // Check rate limit
        if ($this->shouldRateLimit(self::ANALYTICS_REPORT, $tenant)) {
            Log::info('Analytics report skipped due to rate limiting', [
                'tenant_id' => $tenant->id,
                'period' => $reportPeriod,
            ]);
            return;
        }

        // Resolve recipients (tenant admins)
        $recipients = $this->resolveRecipients($tenant, self::ANALYTICS_REPORT);

        foreach ($recipients as $recipient) {
            $mail = new SubscriptionAnalyticsReportMail($tenant, $reportData, $reportPeriod);

            $log = $this->send(
                $mail,
                $tenant,
                $recipient,
                self::ANALYTICS_REPORT,
                [
                    'period' => $reportPeriod,
                    'report_date' => $reportData['date'] ?? null,
                ]
            );

            // Update last sent timestamp for rate limiting
            if ($log) {
                Cache::put(
                    "notification:last_sent:{$tenant->id}:" . self::ANALYTICS_REPORT . ":{$reportPeriod}",
                    now(),
                    self::RATE_LIMIT_ANALYTICS
                );
            }
        }
    }

    /**
     * Get notification preferences for a user.
     *
     * @param User $user
     * @param mixed|null $notifiable
     * @return Collection
     */
    public function getPreferences(User $user, $notifiable = null): Collection
    {
        $query = NotificationPreference::query()->forUser($user);

        if ($notifiable) {
            $query->forNotifiable($notifiable);
        }

        return $query->get();
    }

    /**
     * Update a notification preference for a user.
     *
     * @param User $user
     * @param string $channel
     * @param string $eventType
     * @param mixed $notifiable
     * @param bool $enabled
     * @return NotificationPreference
     */
    public function updatePreference(
        User $user,
        string $channel,
        string $eventType,
        $notifiable,
        bool $enabled
    ): NotificationPreference {
        $preference = NotificationPreference::query()
            ->forUser($user)
            ->channel($channel)
            ->eventType($eventType)
            ->forNotifiable($notifiable)
            ->firstOrNew([
                'user_id' => $user->id,
                'channel' => $channel,
                'event_type' => $eventType,
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id,
            ]);

        $preference->is_enabled = $enabled;
        $preference->save();

        return $preference;
    }

    /**
     * Resolve the recipients for a notification.
     *
     * @param mixed $notifiable
     * @param string $eventType
     * @return Collection
     */
    protected function resolveRecipients($notifiable, string $eventType): Collection
    {
        if ($notifiable instanceof Club) {
            // For club notifications, send to club admins
            return $this->getClubAdmins($notifiable);
        }

        if ($notifiable instanceof Tenant) {
            // For tenant notifications, send to tenant admins
            return $this->getTenantAdmins($notifiable);
        }

        // Default: empty collection
        Log::warning('Could not resolve recipients for notification', [
            'notifiable_type' => get_class($notifiable),
            'event_type' => $eventType,
        ]);

        return collect();
    }

    /**
     * Get club admins for a club.
     *
     * @param Club $club
     * @return Collection
     */
    protected function getClubAdmins(Club $club): Collection
    {
        // Get users with club_admin role who administer this club
        return User::query()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'club_admin');
            })
            ->where(function ($query) use ($club) {
                // Users who administer this club
                $query->whereHas('administeredClubs', function ($q) use ($club) {
                    $q->where('clubs.id', $club->id);
                });
            })
            ->orWhere(function ($query) {
                // Or super admins / admins
                $query->whereHas('roles', function ($q) {
                    $q->whereIn('name', ['super_admin', 'admin']);
                });
            })
            ->get();
    }

    /**
     * Get tenant admins for a tenant.
     *
     * @param Tenant $tenant
     * @return Collection
     */
    protected function getTenantAdmins(Tenant $tenant): Collection
    {
        // Get users with admin or super_admin role for this tenant
        return User::query()
            ->where('tenant_id', $tenant->id)
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['admin', 'super_admin']);
            })
            ->get();
    }

    /**
     * Check if rate limiting should prevent sending this notification.
     *
     * @param string $eventType
     * @param mixed $notifiable
     * @return bool
     */
    protected function shouldRateLimit(string $eventType, $notifiable): bool
    {
        $lastSent = $this->getLastSent($eventType, $notifiable);

        if (!$lastSent) {
            return false;
        }

        $rateLimit = match ($eventType) {
            self::HIGH_CHURN_ALERT => self::RATE_LIMIT_HIGH_CHURN,
            self::ANALYTICS_REPORT => self::RATE_LIMIT_ANALYTICS,
            default => 0,
        };

        if ($rateLimit === 0) {
            return false;
        }

        return $lastSent->addSeconds($rateLimit)->isFuture();
    }

    /**
     * Get the last time this notification was sent.
     *
     * @param string $eventType
     * @param mixed $notifiable
     * @return Carbon|null
     */
    protected function getLastSent(string $eventType, $notifiable): ?Carbon
    {
        $cacheKey = "notification:last_sent:{$notifiable->id}:{$eventType}";

        return Cache::get($cacheKey);
    }

    /**
     * Create a notification log entry.
     *
     * @param Mailable $mail
     * @param mixed $notifiable
     * @param User $recipient
     * @param string $eventType
     * @param array $metadata
     * @return NotificationLog
     */
    protected function createNotificationLog(
        Mailable $mail,
        $notifiable,
        User $recipient,
        string $eventType,
        array $metadata = []
    ): NotificationLog {
        return NotificationLog::create([
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->id,
            'notification_type' => get_class($mail),
            'channel' => 'email',
            'recipient_email' => $recipient->email,
            'recipient_user_id' => $recipient->id,
            'subject' => $this->extractSubject($mail),
            'body_preview' => null, // Could be populated if needed
            'status' => 'queued',
            'queued_at' => now(),
            'metadata' => array_merge($metadata, [
                'event_type' => $eventType,
            ]),
        ]);
    }

    /**
     * Queue a mail for sending.
     *
     * @param Mailable $mail
     * @param User $recipient
     * @return void
     */
    protected function queueMail(Mailable $mail, User $recipient): void
    {
        Mail::to($recipient->email)->queue($mail);
    }

    /**
     * Extract subject from mail instance.
     *
     * @param Mailable $mail
     * @return string|null
     */
    protected function extractSubject(Mailable $mail): ?string
    {
        try {
            $envelope = $mail->envelope();
            return $envelope->subject ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
