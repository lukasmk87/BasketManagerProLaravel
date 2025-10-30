<?php

namespace Tests\Unit\Mail\ClubSubscription;

use App\Mail\ClubSubscription\SubscriptionCanceledMail;
use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for SubscriptionCanceledMail.
 *
 * @see \App\Mail\ClubSubscription\SubscriptionCanceledMail
 */
class SubscriptionCanceledMailTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_generates_correct_subject_with_club_name()
    {
        $club = Club::factory()->create(['name' => 'Test Basketball Club']);

        $mail = new SubscriptionCanceledMail($club, 'voluntary');

        $envelope = $mail->envelope();

        $this->assertStringContainsString('Test Basketball Club', $envelope->subject);
        $this->assertStringContainsString('gekündigt', $envelope->subject);
    }

    /** @test */
    public function it_includes_cancellation_details_in_content()
    {
        $club = Club::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create(['name' => 'Premium Plan']);
        $club->club_subscription_plan_id = $plan->id;
        $club->save();

        $accessUntil = now()->addDays(7);

        $mail = new SubscriptionCanceledMail($club, 'voluntary', $accessUntil);

        $content = $mail->content();

        $this->assertEquals('emails.club-subscription.subscription-canceled', $content->markdown);
        $this->assertArrayHasKey('club', $content->with);
        $this->assertArrayHasKey('planName', $content->with);
        $this->assertArrayHasKey('cancellationReason', $content->with);
        $this->assertArrayHasKey('cancellationReasonTranslated', $content->with);
        $this->assertArrayHasKey('accessUntil', $content->with);
        $this->assertArrayHasKey('immediatelyCanceled', $content->with);
        $this->assertArrayHasKey('daysRemaining', $content->with);

        $this->assertEquals('Premium Plan', $content->with['planName']);
        $this->assertEquals('voluntary', $content->with['cancellationReason']);
        $this->assertFalse($content->with['immediatelyCanceled']);
    }

    /** @test */
    public function it_translates_cancellation_reason()
    {
        $club = Club::factory()->create();

        $mail = new SubscriptionCanceledMail($club, 'voluntary');

        $content = $mail->content();

        $this->assertStringContainsString('Freiwillige', $content->with['cancellationReasonTranslated']);
    }

    /** @test */
    public function it_calculates_days_remaining_correctly_when_access_until_provided()
    {
        $club = Club::factory()->create();
        $accessUntil = now()->addDays(10);

        $mail = new SubscriptionCanceledMail($club, 'voluntary', $accessUntil);

        $content = $mail->content();

        $this->assertEquals(10, $content->with['daysRemaining']);
    }

    /** @test */
    public function it_handles_immediate_cancellation()
    {
        $club = Club::factory()->create();

        $mail = new SubscriptionCanceledMail($club, 'payment_failure', null, true);

        $content = $mail->content();

        $this->assertTrue($content->with['immediatelyCanceled']);
        $this->assertNull($content->with['accessUntil']);
        $this->assertEquals(0, $content->with['daysRemaining']);
    }

    /** @test */
    public function it_includes_action_urls_in_content()
    {
        $club = Club::factory()->create(['id' => 123]);

        $mail = new SubscriptionCanceledMail($club, 'voluntary');

        $content = $mail->content();

        $this->assertArrayHasKey('resubscribeUrl', $content->with);
        $this->assertArrayHasKey('exportDataUrl', $content->with);
        $this->assertArrayHasKey('feedbackUrl', $content->with);
    }

    /** @test */
    public function it_has_correct_queue_configuration()
    {
        $club = Club::factory()->create();

        $mail = new SubscriptionCanceledMail($club, 'voluntary');

        $this->assertEquals(3, $mail->tries);
        $this->assertEquals(60, $mail->backoff);
    }
}
