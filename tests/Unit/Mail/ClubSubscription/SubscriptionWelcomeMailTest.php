<?php

namespace Tests\Unit\Mail\ClubSubscription;

use App\Mail\ClubSubscription\SubscriptionWelcomeMail;
use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for SubscriptionWelcomeMail.
 *
 * @see \App\Mail\ClubSubscription\SubscriptionWelcomeMail
 */
class SubscriptionWelcomeMailTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_includes_app_name_and_club_name_in_subject()
    {
        $club = Club::factory()->create(['name' => 'Test Basketball Club']);
        $plan = ClubSubscriptionPlan::factory()->create();

        $mail = new SubscriptionWelcomeMail($club, $plan);

        $envelope = $mail->envelope();

        $this->assertStringContainsString('Test Basketball Club', $envelope->subject);
        $this->assertStringContainsString(config('app.name'), $envelope->subject);
        $this->assertStringContainsString('Willkommen', $envelope->subject);
        $this->assertStringContainsString('🎉', $envelope->subject);
    }

    /** @test */
    public function it_includes_plan_details_and_features_in_content()
    {
        $club = Club::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create([
            'name' => 'Premium Plan',
            'price' => 149.00,
            'currency' => 'EUR',
            'features' => ['live_scoring', 'advanced_stats'],
        ]);

        $mail = new SubscriptionWelcomeMail($club, $plan);

        $content = $mail->content();

        $this->assertEquals('emails.club-subscription.subscription-welcome', $content->markdown);
        $this->assertArrayHasKey('club', $content->with);
        $this->assertArrayHasKey('plan', $content->with);
        $this->assertArrayHasKey('planName', $content->with);
        $this->assertArrayHasKey('planPrice', $content->with);
        $this->assertArrayHasKey('planFeatures', $content->with);

        $this->assertEquals('Premium Plan', $content->with['planName']);
        $this->assertEquals(149.00, $content->with['planPrice']);
        $this->assertContains('live_scoring', $content->with['planFeatures']);
    }

    /** @test */
    public function it_includes_plan_limits_in_content()
    {
        $club = Club::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create([
            'max_teams' => 50,
            'max_players' => 500,
            'max_games' => 100,
            'max_training_sessions' => 200,
        ]);

        $mail = new SubscriptionWelcomeMail($club, $plan);

        $content = $mail->content();

        $this->assertArrayHasKey('planLimits', $content->with);
        $this->assertEquals(50, $content->with['planLimits']['max_teams']);
        $this->assertEquals(500, $content->with['planLimits']['max_players']);
        $this->assertEquals(100, $content->with['planLimits']['max_games']);
        $this->assertEquals(200, $content->with['planLimits']['max_training_sessions']);
    }

    /** @test */
    public function it_includes_trial_info_when_trial_is_active()
    {
        $club = Club::factory()->create([
            'subscription_trial_ends_at' => now()->addDays(14),
        ]);
        $plan = ClubSubscriptionPlan::factory()->create();

        $mail = new SubscriptionWelcomeMail($club, $plan, true, 14);

        $content = $mail->content();

        $this->assertTrue($content->with['isTrialActive']);
        $this->assertEquals(14, $content->with['trialDaysRemaining']);
        $this->assertNotNull($content->with['trialEndsAt']);
    }

    /** @test */
    public function it_does_not_include_trial_info_when_trial_is_not_active()
    {
        $club = Club::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create();

        $mail = new SubscriptionWelcomeMail($club, $plan);

        $content = $mail->content();

        $this->assertFalse($content->with['isTrialActive']);
        $this->assertNull($content->with['trialDaysRemaining']);
    }

    /** @test */
    public function it_includes_getting_started_steps_in_content()
    {
        $club = Club::factory()->create(['id' => 123]);
        $plan = ClubSubscriptionPlan::factory()->create();

        $mail = new SubscriptionWelcomeMail($club, $plan);

        $content = $mail->content();

        $this->assertArrayHasKey('gettingStartedSteps', $content->with);
        $this->assertCount(4, $content->with['gettingStartedSteps']);

        $steps = $content->with['gettingStartedSteps'];
        $this->assertArrayHasKey('title', $steps[0]);
        $this->assertArrayHasKey('description', $steps[0]);
        $this->assertArrayHasKey('url', $steps[0]);
        $this->assertArrayHasKey('icon', $steps[0]);

        // Verify translation keys are used
        $this->assertStringContainsString('Teams erstellen', $steps[0]['title']);
    }

    /** @test */
    public function it_includes_plan_id_in_tags()
    {
        $club = Club::factory()->create([
            'id' => 123,
            'tenant_id' => 456,
        ]);
        $plan = ClubSubscriptionPlan::factory()->create(['id' => 789]);

        $mail = new SubscriptionWelcomeMail($club, $plan);

        $tags = $mail->tags();

        $this->assertCount(5, $tags);
        $this->assertContains('club-subscription', $tags);
        $this->assertContains('welcome', $tags);
        $this->assertContains('club:123', $tags);
        $this->assertContains('tenant:456', $tags);
        $this->assertContains('plan:789', $tags);
    }
}
