<?php

namespace App\Mail;

use App\Models\ClubTransfer;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClubTransferFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public ClubTransfer $transfer,
        public User $admin,
        public ?\Throwable $exception = null
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Club-Transfer fehlgeschlagen - ' . $this->transfer->club->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.club-transfer.transfer-failed',
            with: [
                'transfer' => $this->transfer,
                'admin' => $this->admin,
                'club' => $this->transfer->club,
                'sourceTenant' => $this->transfer->sourceTenant,
                'targetTenant' => $this->transfer->targetTenant,
                'initiatedBy' => $this->transfer->initiatedBy,
                'errorMessage' => $this->transfer->getMetadata('failure_reason'),
                'exception' => $this->exception,
                'canRetry' => true,
                'metadata' => $this->transfer->metadata,
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
}
