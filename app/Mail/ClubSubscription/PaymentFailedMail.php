<?php

namespace App\Mail\ClubSubscription;

use App\Models\Club;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentFailedMail extends Mailable implements ShouldQueue
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
        public string $failureReason,
        public ?int $gracePeriodDays = 3,
        public ?int $retryAttempts = null
    ) {
        $this->afterCommit();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('notifications.subjects.payment_failed', [
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
            markdown: 'emails.club-subscription.payment-failed',
            with: [
                'club' => $this->club,
                'invoiceNumber' => $this->invoiceData['number'] ?? 'N/A',
                'amount' => $this->invoiceData['amount'] ?? 0,
                'currency' => $this->invoiceData['currency'] ?? 'EUR',
                'attemptedAt' => $this->invoiceData['attempted_at'] ?? now(),
                'failureReason' => $this->failureReason,
                'failureReasonTranslated' => $this->translateFailureReason($this->failureReason),
                'gracePeriodDays' => $this->gracePeriodDays,
                'accessExpiresAt' => now()->addDays($this->gracePeriodDays ?? 3),
                'retryAttempts' => $this->retryAttempts,
                'planName' => $this->club->subscriptionPlan?->name ?? 'N/A',
                'updatePaymentMethodUrl' => route('club.billing.payment-methods', $this->club->id),
                'supportUrl' => route('support.contact'),
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
            'payment-failed',
            'club:' . $this->club->id,
            'tenant:' . $this->club->tenant_id,
            'priority:high',
        ];
    }

    /**
     * Translate Stripe failure reason to German.
     */
    private function translateFailureReason(string $reason): string
    {
        return __('notifications.payment_failed.failure_reasons.' . $reason, [],
            __('notifications.payment_failed.failure_reasons.default'));
    }
}
