<?php

namespace Tests\Unit\Mail\ClubSubscription;

use App\Mail\ClubSubscription\PaymentFailedMail;
use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for PaymentFailedMail.
 *
 * @see \App\Mail\ClubSubscription\PaymentFailedMail
 */
class PaymentFailedMailTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_generates_correct_subject_with_club_name()
    {
        $club = Club::factory()->create(['name' => 'Test Basketball Club']);

        $mail = new PaymentFailedMail($club, [
            'number' => 'INV-001',
            'amount' => 99.00,
            'currency' => 'EUR',
            'attempted_at' => now(),
        ], 'insufficient_funds');

        $envelope = $mail->envelope();

        $this->assertStringContainsString('Test Basketball Club', $envelope->subject);
        $this->assertStringContainsString('Zahlung fehlgeschlagen', $envelope->subject);
        $this->assertStringContainsString('⚠️', $envelope->subject);
    }

    /** @test */
    public function it_includes_invoice_data_and_failure_reason_in_content()
    {
        $club = Club::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create();
        $club->club_subscription_plan_id = $plan->id;
        $club->save();

        $invoiceData = [
            'number' => 'INV-2024-002',
            'amount' => 149.00,
            'currency' => 'EUR',
            'attempted_at' => now(),
        ];

        $mail = new PaymentFailedMail($club, $invoiceData, 'card_declined', 5, 2);

        $content = $mail->content();

        $this->assertEquals('emails.club-subscription.payment-failed', $content->markdown);
        $this->assertArrayHasKey('club', $content->with);
        $this->assertArrayHasKey('invoiceNumber', $content->with);
        $this->assertArrayHasKey('amount', $content->with);
        $this->assertArrayHasKey('failureReason', $content->with);
        $this->assertArrayHasKey('failureReasonTranslated', $content->with);
        $this->assertArrayHasKey('gracePeriodDays', $content->with);
        $this->assertArrayHasKey('retryAttempts', $content->with);
        $this->assertArrayHasKey('accessExpiresAt', $content->with);

        $this->assertEquals('INV-2024-002', $content->with['invoiceNumber']);
        $this->assertEquals(149.00, $content->with['amount']);
        $this->assertEquals('card_declined', $content->with['failureReason']);
        $this->assertEquals(5, $content->with['gracePeriodDays']);
        $this->assertEquals(2, $content->with['retryAttempts']);
    }

    /** @test */
    public function it_translates_failure_reason_using_helper_method()
    {
        $club = Club::factory()->create();

        $mail = new PaymentFailedMail($club, [
            'number' => 'INV-001',
            'amount' => 99.00,
            'currency' => 'EUR',
            'attempted_at' => now(),
        ], 'insufficient_funds');

        $content = $mail->content();

        $this->assertStringContainsString('Unzureichende Deckung', $content->with['failureReasonTranslated']);
    }

    /** @test */
    public function it_calculates_access_expires_at_correctly()
    {
        $club = Club::factory()->create();

        $mail = new PaymentFailedMail($club, [
            'number' => 'INV-001',
            'amount' => 99.00,
            'currency' => 'EUR',
            'attempted_at' => now(),
        ], 'insufficient_funds', 7);

        $content = $mail->content();

        $expectedDate = now()->addDays(7);
        $actualDate = $content->with['accessExpiresAt'];

        $this->assertTrue($expectedDate->isSameDay($actualDate));
    }

    /** @test */
    public function it_includes_action_urls_in_content()
    {
        $club = Club::factory()->create(['id' => 123]);

        $mail = new PaymentFailedMail($club, [
            'number' => 'INV-001',
            'amount' => 99.00,
            'currency' => 'EUR',
            'attempted_at' => now(),
        ], 'card_declined');

        $content = $mail->content();

        $this->assertArrayHasKey('updatePaymentMethodUrl', $content->with);
        $this->assertArrayHasKey('supportUrl', $content->with);
    }

    /** @test */
    public function it_includes_priority_high_tag()
    {
        $club = Club::factory()->create([
            'id' => 123,
            'tenant_id' => 456,
        ]);

        $mail = new PaymentFailedMail($club, [
            'number' => 'INV-001',
            'amount' => 99.00,
            'currency' => 'EUR',
            'attempted_at' => now(),
        ], 'insufficient_funds');

        $tags = $mail->tags();

        $this->assertCount(5, $tags);
        $this->assertContains('club-subscription', $tags);
        $this->assertContains('payment-failed', $tags);
        $this->assertContains('club:123', $tags);
        $this->assertContains('tenant:456', $tags);
        $this->assertContains('priority:high', $tags);
    }

    /** @test */
    public function it_has_correct_queue_configuration()
    {
        $club = Club::factory()->create();

        $mail = new PaymentFailedMail($club, [
            'number' => 'INV-001',
            'amount' => 99.00,
            'currency' => 'EUR',
            'attempted_at' => now(),
        ], 'insufficient_funds');

        $this->assertEquals(3, $mail->tries);
        $this->assertEquals(60, $mail->backoff);
    }
}
