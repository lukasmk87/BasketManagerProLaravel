<?php

namespace App\Mail\ClubSubscription;

use App\Models\Club;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionCanceledMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60;

    public function __construct(
        public Club $club,
        public string $cancellationReason,
        public ?Carbon $accessUntil = null,
        public bool $immediatelyCanceled = false
    ) {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('Abonnement gekündigt - %s', $this->club->name),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.club-subscription.subscription-canceled',
            with: [
                'club' => $this->club,
                'planName' => $this->club->subscriptionPlan?->name ?? 'N/A',
                'cancellationReason' => $this->cancellationReason,
                'cancellationReasonTranslated' => $this->translateReason($this->cancellationReason),
                'accessUntil' => $this->accessUntil,
                'immediatelyCanceled' => $this->immediatelyCanceled,
                'daysRemaining' => $this->accessUntil ? now()->diffInDays($this->accessUntil) : 0,
                'resubscribeUrl' => route('club.subscription.index', $this->club->id),
                'exportDataUrl' => route('club.data.export', $this->club->id),
                'feedbackUrl' => route('feedback.cancellation', $this->club->id),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    public function tags(): array
    {
        return ['club-subscription', 'canceled', 'club:' . $this->club->id, 'tenant:' . $this->club->tenant_id];
    }

    private function translateReason(string $reason): string
    {
        return match($reason) {
            'voluntary' => 'Freiwillige Kündigung',
            'payment_failed' => 'Zahlungsfehler',
            'trial_expired' => 'Testzeitraum abgelaufen',
            'downgrade_to_free' => 'Wechsel zum kostenlosen Plan',
            default => 'Andere',
        };
    }
}
