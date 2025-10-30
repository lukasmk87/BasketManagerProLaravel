<?php

namespace Tests\Unit\Services;

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
use App\Services\ClubSubscriptionNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Tests for ClubSubscriptionNotificationService.
 *
 * Tests cover:
 * - Notification sending with preference checking
 * - Rate limiting for churn alerts and analytics
 * - Recipient resolution (club vs tenant admins)
 * - Mail queuing and error handling
 * - Notification logging
 *
 * @see \App\Services\ClubSubscriptionNotificationService
 */
class ClubSubscriptionNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClubSubscriptionNotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ClubSubscriptionNotificationService::class);
    }

    // ============================================================
    // Core Notification Sending Tests
    // ============================================================

    /** @test */
    public function it_sends_notification_when_preference_enabled()
    {
        Mail::fake();

        $club = Club::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('admin');

        // Create enabled preference
        NotificationPreference::factory()->create([
            'user_id' => $user->id,
            'event_type' => ClubSubscriptionNotificationService::PAYMENT_SUCCEEDED,
            'is_enabled' => true,
        ]);

        $this->service->sendPaymentSuccessful($club, [
            'number' => 'INV-001',
            'amount' => 99.00,
            'currency' => 'EUR',
            'paid_at' => now(),
        ]);

        Mail::assertQueued(PaymentSuccessfulMail::class);
        $this->assertDatabaseHas('notification_logs', [
            'event_type' => ClubSubscriptionNotificationService::PAYMENT_SUCCEEDED,
            'status' => 'sent',
        ]);
    }

    /** @test */
    public function it_skips_notification_when_user_preference_disabled()
    {
        Mail::fake();

        $club = Club::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('admin');

        // Create disabled preference
        NotificationPreference::factory()->create([
            'user_id' => $user->id,
            'event_type' => ClubSubscriptionNotificationService::PAYMENT_SUCCEEDED,
            'is_enabled' => false,
        ]);

        $this->service->sendPaymentSuccessful($club, [
            'number' => 'INV-001',
            'amount' => 99.00,
            'currency' => 'EUR',
            'paid_at' => now(),
        ]);

        Mail::assertNothingQueued();
    }

    /** @test */
    public function it_creates_notification_log_when_sending()
    {
        Mail::fake();

        $club = Club::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->service->sendPaymentSuccessful($club, [
            'number' => 'INV-001',
            'amount' => 99.00,
            'currency' => 'EUR',
            'paid_at' => now(),
        ]);

        $this->assertDatabaseHas('notification_logs', [
            'event_type' => ClubSubscriptionNotificationService::PAYMENT_SUCCEEDED,
            'notifiable_type' => Club::class,
            'notifiable_id' => $club->id,
            'status' => 'sent',
        ]);
    }

    /** @test */
    public function it_marks_log_as_failed_on_exception()
    {
        Mail::fake();
        Mail::shouldReceive('to')->andThrow(new \Exception('Mail service error'));

        $club = Club::factory()->create();

        try {
            $this->service->sendPaymentSuccessful($club, []);
        } catch (\Exception $e) {
            // Expected exception
        }

        $this->assertDatabaseHas('notification_logs', [
            'event_type' => ClubSubscriptionNotificationService::PAYMENT_SUCCEEDED,
            'status' => 'failed',
        ]);
    }

    /** @test */
    public function it_can_send_returns_true_when_no_preference_exists()
    {
        $user = User::factory()->create();

        $canSend = $this->service->canSend(
            $user,
            ClubSubscriptionNotificationService::PAYMENT_SUCCEEDED
        );

        $this->assertTrue($canSend);
    }

    /** @test */
    public function it_can_send_returns_true_when_preference_enabled()
    {
        $user = User::factory()->create();

        NotificationPreference::factory()->create([
            'user_id' => $user->id,
            'event_type' => ClubSubscriptionNotificationService::PAYMENT_SUCCEEDED,
            'is_enabled' => true,
        ]);

        $canSend = $this->service->canSend(
            $user,
            ClubSubscriptionNotificationService::PAYMENT_SUCCEEDED
        );

        $this->assertTrue($canSend);
    }

    /** @test */
    public function it_can_send_returns_false_when_preference_disabled()
    {
        $user = User::factory()->create();

        NotificationPreference::factory()->create([
            'user_id' => $user->id,
            'event_type' => ClubSubscriptionNotificationService::PAYMENT_SUCCEEDED,
            'is_enabled' => false,
        ]);

        $canSend = $this->service->canSend(
            $user,
            ClubSubscriptionNotificationService::PAYMENT_SUCCEEDED
        );

        $this->assertFalse($canSend);
    }

    /** @test */
    public function it_extracts_subject_from_mail_envelope()
    {
        Mail::fake();

        $club = Club::factory()->create(['name' => 'Test Club']);
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->service->sendPaymentSuccessful($club, [
            'number' => 'INV-001',
            'amount' => 99.00,
            'currency' => 'EUR',
            'paid_at' => now(),
        ]);

        $log = NotificationLog::where('event_type', ClubSubscriptionNotificationService::PAYMENT_SUCCEEDED)->first();
        $this->assertStringContainsString('Test Club', $log->metadata['subject'] ?? '');
    }

    // ============================================================
    // Preference Management Tests
    // ============================================================

    /** @test */
    public function it_retrieves_user_preferences()
    {
        $user = User::factory()->create();

        NotificationPreference::factory()->create([
            'user_id' => $user->id,
            'event_type' => ClubSubscriptionNotificationService::PAYMENT_SUCCEEDED,
            'is_enabled' => true,
        ]);

        $preferences = $this->service->getPreferences($user);

        $this->assertCount(1, $preferences);
        $this->assertEquals(ClubSubscriptionNotificationService::PAYMENT_SUCCEEDED, $preferences->first()->event_type);
    }

    /** @test */
    public function it_filters_preferences_by_notifiable_when_provided()
    {
        $user = User::factory()->create();
        $club = Club::factory()->create();

        NotificationPreference::factory()->create([
            'user_id' => $user->id,
            'event_type' => ClubSubscriptionNotificationService::PAYMENT_SUCCEEDED,
            'notifiable_type' => Club::class,
            'notifiable_id' => $club->id,
            'is_enabled' => true,
        ]);

        NotificationPreference::factory()->create([
            'user_id' => $user->id,
            'event_type' => ClubSubscriptionNotificationService::PAYMENT_SUCCEEDED,
            'notifiable_type' => null,
            'notifiable_id' => null,
            'is_enabled' => true,
        ]);

        $preferences = $this->service->getPreferences($user, $club);

        $this->assertCount(1, $preferences);
        $this->assertEquals($club->id, $preferences->first()->notifiable_id);
    }

    /** @test */
    public function it_creates_new_preference()
    {
        $user = User::factory()->create();

        $preference = $this->service->updatePreference(
            $user,
            ClubSubscriptionNotificationService::PAYMENT_SUCCEEDED,
            true
        );

        $this->assertInstanceOf(NotificationPreference::class, $preference);
        $this->assertTrue($preference->is_enabled);
        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $user->id,
            'event_type' => ClubSubscriptionNotificationService::PAYMENT_SUCCEEDED,
            'is_enabled' => true,
        ]);
    }

    /** @test */
    public function it_updates_existing_preference()
    {
        $user = User::factory()->create();

        NotificationPreference::factory()->create([
            'user_id' => $user->id,
            'event_type' => ClubSubscriptionNotificationService::PAYMENT_SUCCEEDED,
            'is_enabled' => true,
        ]);

        $preference = $this->service->updatePreference(
            $user,
            ClubSubscriptionNotificationService::PAYMENT_SUCCEEDED,
            false
        );

        $this->assertFalse($preference->is_enabled);
        $this->assertEquals(1, NotificationPreference::where('user_id', $user->id)->count());
    }

    // ============================================================
    // Payment Notification Tests
    // ============================================================

    /** @test */
    public function it_sends_payment_successful_notification_to_club_admins()
    {
        Mail::fake();

        $club = Club::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $invoiceData = [
            'number' => 'INV-2024-001',
            'amount' => 149.00,
            'currency' => 'EUR',
            'paid_at' => now(),
            'next_billing_date' => now()->addMonth(),
        ];

        $this->service->sendPaymentSuccessful($club, $invoiceData);

        Mail::assertQueued(PaymentSuccessfulMail::class, function ($mail) use ($club, $invoiceData) {
            return $mail->club->id === $club->id
                && $mail->invoiceData['number'] === $invoiceData['number']
                && $mail->invoiceData['amount'] === $invoiceData['amount'];
        });
    }

    /** @test */
    public function it_sends_payment_failed_notification_to_club_admins()
    {
        Mail::fake();

        $club = Club::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $invoiceData = [
            'number' => 'INV-2024-002',
            'amount' => 149.00,
            'currency' => 'EUR',
            'attempted_at' => now(),
        ];

        $this->service->sendPaymentFailed($club, $invoiceData, 'insufficient_funds');

        Mail::assertQueued(PaymentFailedMail::class, function ($mail) use ($club) {
            return $mail->club->id === $club->id
                && $mail->failureReason === 'insufficient_funds';
        });
    }

    // ============================================================
    // Subscription Lifecycle Tests
    // ============================================================

    /** @test */
    public function it_sends_subscription_welcome_notification_to_club_admins()
    {
        Mail::fake();

        $club = Club::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->service->sendSubscriptionWelcome($club, $plan, true, 14);

        Mail::assertQueued(SubscriptionWelcomeMail::class, function ($mail) use ($club, $plan) {
            return $mail->club->id === $club->id
                && $mail->plan->id === $plan->id
                && $mail->isTrialActive === true
                && $mail->trialDaysRemaining === 14;
        });
    }

    /** @test */
    public function it_sends_subscription_welcome_without_trial_info()
    {
        Mail::fake();

        $club = Club::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->service->sendSubscriptionWelcome($club, $plan);

        Mail::assertQueued(SubscriptionWelcomeMail::class, function ($mail) {
            return $mail->isTrialActive === false
                && $mail->trialDaysRemaining === null;
        });
    }

    /** @test */
    public function it_sends_subscription_canceled_notification_to_club_admins()
    {
        Mail::fake();

        $club = Club::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $accessUntil = now()->addDays(7);

        $this->service->sendSubscriptionCanceled($club, 'voluntary', $accessUntil);

        Mail::assertQueued(SubscriptionCanceledMail::class, function ($mail) use ($club, $accessUntil) {
            return $mail->club->id === $club->id
                && $mail->cancellationReason === 'voluntary'
                && $mail->accessUntil->equalTo($accessUntil)
                && $mail->immediatelyCanceled === false;
        });
    }

    /** @test */
    public function it_handles_immediate_cancellation()
    {
        Mail::fake();

        $club = Club::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->service->sendSubscriptionCanceled($club, 'payment_failure', null, true);

        Mail::assertQueued(SubscriptionCanceledMail::class, function ($mail) {
            return $mail->immediatelyCanceled === true
                && $mail->accessUntil === null;
        });
    }

    // ============================================================
    // Tenant Admin Notification Tests
    // ============================================================

    /** @test */
    public function it_sends_high_churn_alert_to_tenant_admins()
    {
        Mail::fake();

        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('admin');

        $churnData = [
            'period' => '2024-10',
            'churn_rate' => 8.5,
            'customers_start' => 100,
            'customers_end' => 92,
            'churned_customers' => 8,
            'voluntary_churn' => 5,
            'involuntary_churn' => 3,
        ];

        $this->service->sendHighChurnAlert($tenant, $churnData);

        Mail::assertQueued(HighChurnAlertMail::class, function ($mail) use ($tenant, $churnData) {
            return $mail->tenant->id === $tenant->id
                && $mail->churnData['churn_rate'] === $churnData['churn_rate'];
        });
    }

    /** @test */
    public function it_respects_rate_limiting_for_high_churn_alerts()
    {
        Mail::fake();
        Cache::flush();

        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('admin');

        $churnData = [
            'period' => '2024-10',
            'churn_rate' => 8.5,
            'customers_start' => 100,
            'customers_end' => 92,
            'churned_customers' => 8,
            'voluntary_churn' => 5,
            'involuntary_churn' => 3,
        ];

        // First call should send
        $this->service->sendHighChurnAlert($tenant, $churnData);
        Mail::assertQueued(HighChurnAlertMail::class, 1);

        Mail::fake(); // Reset

        // Second call within 24h should be rate limited
        $this->service->sendHighChurnAlert($tenant, $churnData);
        Mail::assertNothingQueued();
    }

    /** @test */
    public function it_sends_analytics_report_to_tenant_admins()
    {
        Mail::fake();

        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('admin');

        $reportData = [
            'date' => now()->format('Y-m-d'),
            'mrr' => ['total' => 5000, 'growth_rate_3m' => 15.5],
            'churn' => ['monthly_rate' => 3.2],
            'ltv' => ['average' => 1200],
            'health' => ['active_subscriptions' => 50],
        ];

        $this->service->sendAnalyticsReport($tenant, $reportData);

        Mail::assertQueued(SubscriptionAnalyticsReportMail::class, function ($mail) use ($tenant, $reportData) {
            return $mail->tenant->id === $tenant->id
                && $mail->reportData['date'] === $reportData['date'];
        });
    }

    /** @test */
    public function it_respects_rate_limiting_for_analytics_reports()
    {
        Mail::fake();
        Cache::flush();

        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id]);
        $admin->assignRole('admin');

        $reportData = [
            'date' => now()->format('Y-m-d'),
            'mrr' => ['total' => 5000],
            'churn' => ['monthly_rate' => 3.2],
            'ltv' => ['average' => 1200],
            'health' => ['active_subscriptions' => 50],
        ];

        // First call should send
        $this->service->sendAnalyticsReport($tenant, $reportData);
        Mail::assertQueued(SubscriptionAnalyticsReportMail::class, 1);

        Mail::fake(); // Reset

        // Second call within 24h should be rate limited
        $this->service->sendAnalyticsReport($tenant, $reportData);
        Mail::assertNothingQueued();
    }

    // ============================================================
    // Recipient Resolution Tests
    // ============================================================

    /** @test */
    public function it_resolves_club_admins_as_recipients_for_club_notifications()
    {
        Mail::fake();

        $tenant = Tenant::factory()->create();
        $club = Club::factory()->create(['tenant_id' => $tenant->id]);

        // Create club admin
        $clubAdmin = User::factory()->create(['tenant_id' => $tenant->id]);
        $clubAdmin->assignRole('club_admin');

        // Create regular user (should not receive)
        $regularUser = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->service->sendPaymentSuccessful($club, [
            'number' => 'INV-001',
            'amount' => 99.00,
            'currency' => 'EUR',
            'paid_at' => now(),
        ]);

        // At least one email should be queued (to admin/super_admin)
        Mail::assertQueued(PaymentSuccessfulMail::class);
    }

    /** @test */
    public function it_resolves_tenant_admins_as_recipients_for_tenant_notifications()
    {
        Mail::fake();

        $tenant = Tenant::factory()->create();

        // Create tenant admin
        $tenantAdmin = User::factory()->create(['tenant_id' => $tenant->id]);
        $tenantAdmin->assignRole('admin');

        $churnData = [
            'period' => '2024-10',
            'churn_rate' => 8.5,
            'customers_start' => 100,
            'customers_end' => 92,
            'churned_customers' => 8,
            'voluntary_churn' => 5,
            'involuntary_churn' => 3,
        ];

        $this->service->sendHighChurnAlert($tenant, $churnData);

        Mail::assertQueued(HighChurnAlertMail::class);
    }
}
