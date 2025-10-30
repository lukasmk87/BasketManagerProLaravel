<?php

namespace Tests\Unit\Mail\ClubSubscription;

use App\Mail\ClubSubscription\PaymentSuccessfulMail;
use App\Models\Club;
use App\Models\ClubSubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for PaymentSuccessfulMail.
 *
 * @see \App\Mail\ClubSubscription\PaymentSuccessfulMail
 */
class PaymentSuccessfulMailTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_generates_correct_subject_with_club_name()
    {
        $club = Club::factory()->create(['name' => 'Test Basketball Club']);

        $mail = new PaymentSuccessfulMail($club, [
            'number' => 'INV-001',
            'amount' => 99.00,
            'currency' => 'EUR',
            'paid_at' => now(),
        ]);

        $envelope = $mail->envelope();

        $this->assertStringContainsString('Test Basketball Club', $envelope->subject);
        $this->assertStringContainsString('Zahlung erfolgreich', $envelope->subject);
    }

    /** @test */
    public function it_includes_all_invoice_data_in_content()
    {
        $club = Club::factory()->create();
        $plan = ClubSubscriptionPlan::factory()->create();
        $club->club_subscription_plan_id = $plan->id;
        $club->save();

        $invoiceData = [
            'number' => 'INV-2024-001',
            'amount' => 149.00,
            'currency' => 'EUR',
            'paid_at' => now(),
            'next_billing_date' => now()->addMonth(),
            'billing_interval' => 'monthly',
        ];

        $mail = new PaymentSuccessfulMail($club, $invoiceData, 'https://example.com/invoice.pdf');

        $content = $mail->content();

        $this->assertEquals('emails.club-subscription.payment-successful', $content->markdown);
        $this->assertArrayHasKey('club', $content->with);
        $this->assertArrayHasKey('invoiceNumber', $content->with);
        $this->assertArrayHasKey('amount', $content->with);
        $this->assertArrayHasKey('currency', $content->with);
        $this->assertArrayHasKey('paidAt', $content->with);
        $this->assertArrayHasKey('nextBillingDate', $content->with);
        $this->assertArrayHasKey('planName', $content->with);
        $this->assertArrayHasKey('pdfUrl', $content->with);

        $this->assertEquals('INV-2024-001', $content->with['invoiceNumber']);
        $this->assertEquals(149.00, $content->with['amount']);
        $this->assertEquals('EUR', $content->with['currency']);
        $this->assertEquals('https://example.com/invoice.pdf', $content->with['pdfUrl']);
    }

    /** @test */
    public function it_handles_missing_optional_fields_with_defaults()
    {
        $club = Club::factory()->create();

        $mail = new PaymentSuccessfulMail($club, []);

        $content = $mail->content();

        $this->assertEquals('N/A', $content->with['invoiceNumber']);
        $this->assertEquals(0, $content->with['amount']);
        $this->assertEquals('EUR', $content->with['currency']);
        $this->assertNull($content->with['nextBillingDate']);
    }

    /** @test */
    public function it_includes_all_required_tags()
    {
        $club = Club::factory()->create([
            'id' => 123,
            'tenant_id' => 456,
        ]);

        $mail = new PaymentSuccessfulMail($club, [
            'number' => 'INV-001',
            'amount' => 99.00,
            'currency' => 'EUR',
            'paid_at' => now(),
        ]);

        $tags = $mail->tags();

        $this->assertCount(4, $tags);
        $this->assertContains('club-subscription', $tags);
        $this->assertContains('payment-successful', $tags);
        $this->assertContains('club:123', $tags);
        $this->assertContains('tenant:456', $tags);
    }

    /** @test */
    public function it_returns_empty_attachments_array()
    {
        $club = Club::factory()->create();

        $mail = new PaymentSuccessfulMail($club, [
            'number' => 'INV-001',
            'amount' => 99.00,
            'currency' => 'EUR',
            'paid_at' => now(),
        ]);

        $attachments = $mail->attachments();

        $this->assertIsArray($attachments);
        $this->assertEmpty($attachments);
    }

    /** @test */
    public function it_has_correct_queue_configuration()
    {
        $club = Club::factory()->create();

        $mail = new PaymentSuccessfulMail($club, [
            'number' => 'INV-001',
            'amount' => 99.00,
            'currency' => 'EUR',
            'paid_at' => now(),
        ]);

        $this->assertEquals(3, $mail->tries);
        $this->assertEquals(60, $mail->backoff);
    }
}
