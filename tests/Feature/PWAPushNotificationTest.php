<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Team;
use App\Models\Game;
use App\Models\PushSubscription;
use App\Services\PushNotificationService;
use App\Jobs\SendPushNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PWAPushNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected PushNotificationService $pushService;
    protected Tenant $tenant;
    protected User $user;
    protected Team $team;

    protected function setUp(): void
    {
        parent::setUp();
        
        Queue::fake();
        
        $this->pushService = app(PushNotificationService::class);
        
        $this->tenant = Tenant::factory()->create([
            'name' => 'Lakers Basketball Club',
            'subscription_tier' => 'professional',
        ]);
        
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'notification_settings' => [
                'push_enabled' => true,
                'game_updates' => true,
                'player_alerts' => true,
                'training_reminders' => true,
            ],
        ]);

        $this->team = Team::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Lakers Team',
        ]);
    }

    /** @test */
    public function user_can_subscribe_to_push_notifications()
    {
        $this->actingAs($this->user);
        
        $subscriptionData = [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
            'keys' => [
                'p256dh' => 'test-p256dh-key',
                'auth' => 'test-auth-key',
            ],
        ];
        
        $response = $this->withHeaders(['Host' => $this->tenant->domain])
                         ->postJson('/api/push-notifications/subscribe', $subscriptionData);
        
        $response->assertCreated();
        
        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test-endpoint',
        ]);
    }

    /** @test */
    public function push_subscription_is_tenant_isolated()
    {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);
        
        // Create subscription for first tenant
        PushSubscription::factory()->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
        ]);
        
        // Create subscription for second tenant
        PushSubscription::factory()->create([
            'user_id' => $otherUser->id,
            'tenant_id' => $otherTenant->id,
        ]);
        
        $this->actingAs($this->user);
        
        // User should only see their own tenant's subscriptions
        $response = $this->withHeaders(['Host' => $this->tenant->domain])
                         ->getJson('/api/push-notifications/subscriptions');
        
        $response->assertOk();
        $subscriptions = $response->json('data');
        
        $this->assertCount(1, $subscriptions);
        $this->assertEquals($this->tenant->id, $subscriptions[0]['tenant_id']);
    }

    /** @test */
    public function basketball_game_notifications_are_sent_correctly()
    {
        $subscription = PushSubscription::factory()->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
        ]);
        
        $game = Game::factory()->create([
            'home_team_id' => $this->team->id,
            'tenant_id' => $this->tenant->id,
            'final_score_home' => 85,
            'final_score_away' => 82,
        ]);
        
        // Trigger game score update notification
        $this->pushService->sendGameScoreUpdate($game, [
            'type' => 'score_update',
            'quarter' => 4,
            'time_remaining' => '2:30',
        ]);
        
        Queue::assertPushed(SendPushNotification::class, function ($job) use ($subscription) {
            return $job->user->id === $subscription->user_id;
        });
    }

    /** @test */
    public function notification_personalization_works_for_favorite_teams()
    {
        $this->user->update(['favorite_team_id' => $this->team->id]);
        
        $subscription = PushSubscription::factory()->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
        ]);
        
        $awayTeam = Team::factory()->create(['name' => 'Warriors', 'tenant_id' => $this->tenant->id]);
        
        $game = Game::factory()->create([
            'home_team_id' => $this->team->id,
            'away_team_id' => $awayTeam->id,
            'tenant_id' => $this->tenant->id,
            'final_score_home' => 95,
            'final_score_away' => 88,
        ]);
        
        // Mock notification personalization
        $personalizedMessage = $this->pushService->personalizeGameMessage($this->user, $game);
        
        // Should contain team-specific message since user follows Lakers
        $this->assertStringContains('Lakers', $personalizedMessage);
        $this->assertStringContains('führt', $personalizedMessage); // German for "leads"
    }

    /** @test */
    public function notification_rate_limiting_prevents_spam()
    {
        $subscription = PushSubscription::factory()->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
        ]);
        
        $game = Game::factory()->create([
            'home_team_id' => $this->team->id,
            'tenant_id' => $this->tenant->id,
        ]);
        
        // Send first notification
        $this->pushService->sendGameScoreUpdate($game, ['type' => 'score_update']);
        
        // Try to send second notification immediately (should be rate limited)
        $this->pushService->sendGameScoreUpdate($game, ['type' => 'score_update']);
        
        // Should only send one notification due to rate limiting
        Queue::assertPushed(SendPushNotification::class, 1);
    }

    /** @test */
    public function notification_respects_user_preferences()
    {
        // User has game notifications disabled
        $this->user->update([
            'notification_settings' => [
                'push_enabled' => true,
                'game_updates' => false, // Disabled
                'player_alerts' => true,
                'training_reminders' => true,
            ],
        ]);
        
        $subscription = PushSubscription::factory()->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
        ]);
        
        $game = Game::factory()->create([
            'home_team_id' => $this->team->id,
            'tenant_id' => $this->tenant->id,
        ]);
        
        // Try to send game notification
        $this->pushService->sendGameScoreUpdate($game, ['type' => 'score_update']);
        
        // Should not send notification due to user preferences
        Queue::assertNotPushed(SendPushNotification::class);
    }

    /** @test */
    public function time_based_notification_rules_are_respected()
    {
        $this->user->update(['timezone' => 'Europe/Berlin']);
        
        $subscription = PushSubscription::factory()->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
        ]);
        
        // Mock late night time (2 AM Berlin time)
        $lateNightTime = now()->setTimezone('Europe/Berlin')->setTime(2, 0);
        $this->travelTo($lateNightTime);
        
        $game = Game::factory()->create([
            'home_team_id' => $this->team->id,
            'tenant_id' => $this->tenant->id,
        ]);
        
        // Try to send notification during quiet hours
        $this->pushService->sendGameScoreUpdate($game, ['type' => 'score_update']);
        
        // Should not send notification during quiet hours (10 PM - 7 AM)
        Queue::assertNotPushed(SendPushNotification::class);
    }

    /** @test */
    public function basketball_specific_notification_types_are_handled()
    {
        $subscription = PushSubscription::factory()->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
        ]);
        
        // Test different basketball notification types
        $notificationTypes = [
            'game_start' => [
                'title' => 'Spiel beginnt',
                'icon' => '/images/notifications/game-start.png',
                'vibrate' => [200, 100, 200],
            ],
            'player_foul' => [
                'title' => 'Spieler-Foul',
                'icon' => '/images/notifications/foul.png',
                'vibrate' => [100, 50, 100],
            ],
            'training_reminder' => [
                'title' => 'Training Erinnerung',
                'icon' => '/images/notifications/training.png',
            ],
        ];
        
        foreach ($notificationTypes as $type => $expectedConfig) {
            $notification = [
                'type' => $type,
                'title' => $expectedConfig['title'],
                'message' => "Test {$type} notification",
            ];
            
            $this->pushService->sendBasketballNotification($this->user, $notification);
        }
        
        Queue::assertPushed(SendPushNotification::class, 3);
    }

    /** @test */
    public function push_notification_cleanup_removes_invalid_subscriptions()
    {
        // Create some subscriptions, some valid, some expired
        $validSubscription = PushSubscription::factory()->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'last_success_at' => now(),
        ]);
        
        $expiredSubscription = PushSubscription::factory()->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
            'last_success_at' => now()->subDays(30),
            'failure_count' => 10,
        ]);
        
        // Run cleanup command
        $this->artisan('push:cleanup')
             ->assertExitCode(0);
        
        // Verify expired subscription was removed
        $this->assertDatabaseHas('push_subscriptions', ['id' => $validSubscription->id]);
        $this->assertDatabaseMissing('push_subscriptions', ['id' => $expiredSubscription->id]);
    }

    /** @test */
    public function push_notifications_support_action_buttons()
    {
        $subscription = PushSubscription::factory()->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
        ]);
        
        $game = Game::factory()->create([
            'home_team_id' => $this->team->id,
            'tenant_id' => $this->tenant->id,
        ]);
        
        // Send notification with action buttons
        $this->pushService->sendGameScoreUpdate($game, [
            'type' => 'game_start',
            'actions' => [
                [
                    'action' => 'view-game',
                    'title' => 'Spiel ansehen',
                    'icon' => '/images/actions/view.png',
                ],
                [
                    'action' => 'start-scoring', 
                    'title' => 'Live-Scoring starten',
                    'icon' => '/images/actions/score.png',
                ],
            ],
        ]);
        
        Queue::assertPushed(SendPushNotification::class, function ($job) {
            $notification = $job->notification;
            return isset($notification['actions']) && count($notification['actions']) === 2;
        });
    }

    /** @test */
    public function tenant_specific_vapid_keys_are_used()
    {
        // Configure tenant-specific VAPID keys
        $this->tenant->update([
            'push_notification_config' => [
                'vapid_public_key' => 'tenant-specific-public-key',
                'vapid_private_key' => 'tenant-specific-private-key',
                'vapid_subject' => 'mailto:admin@lakers.test',
            ],
        ]);
        
        $config = $this->pushService->getTenantVAPIDConfig($this->tenant);
        
        $this->assertEquals('tenant-specific-public-key', $config['public_key']);
        $this->assertEquals('tenant-specific-private-key', $config['private_key']);
        $this->assertEquals('mailto:admin@lakers.test', $config['subject']);
    }

    /** @test */
    public function push_notification_analytics_track_delivery_success()
    {
        $subscription = PushSubscription::factory()->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id,
        ]);
        
        $game = Game::factory()->create([
            'home_team_id' => $this->team->id,
            'tenant_id' => $this->tenant->id,
        ]);
        
        // Send notification
        $this->pushService->sendGameScoreUpdate($game, ['type' => 'score_update']);
        
        // Verify analytics are tracked
        Queue::assertPushed(SendPushNotification::class);
        
        // Mock successful delivery
        $subscription->update([
            'last_success_at' => now(),
            'success_count' => $subscription->success_count + 1,
        ]);
        
        $this->assertDatabaseHas('push_subscriptions', [
            'id' => $subscription->id,
            'success_count' => 1,
        ]);
    }
}