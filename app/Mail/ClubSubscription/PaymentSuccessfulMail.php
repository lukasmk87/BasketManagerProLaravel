<?php

namespace App\Mail\ClubSubscription;

use App\Models\Club;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentSuccessfulMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Club $club,
        public array $invoiceData,
        public ?string $pdfUrl = null
    ) {
        $this->afterCommit();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('notifications.subjects.payment_succeeded', [
                'club_name' => $this->club->name
            ]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.club-subscription.payment-successful',
            with: [
                'club' => $this->club,
                'invoiceNumber' => $this->invoiceData['number'] ?? 'N/A',
                'amount' => $this->invoiceData['amount'] ?? 0,
                'currency' => $this->invoiceData['currency'] ?? 'EUR',
                'paidAt' => $this->invoiceData['paid_at'] ?? now(),
                'nextBillingDate' => $this->invoiceData['next_billing_date'] ?? null,
                'planName' => $this->club->subscriptionPlan?->name ?? 'N/A',
                'billingInterval' => $this->invoiceData['billing_interval'] ?? 'monthly',
                'pdfUrl' => $this->pdfUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'club-subscription',
            'payment-successful',
            'club:' . $this->club->id,
            'tenant:' . $this->club->tenant_id,
        ];
    }
}
