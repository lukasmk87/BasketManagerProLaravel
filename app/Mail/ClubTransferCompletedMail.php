<?php

namespace App\Mail;

use App\Models\ClubTransfer;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClubTransferCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public ClubTransfer $transfer,
        public User $admin
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Club-Transfer erfolgreich abgeschlossen - ' . $this->transfer->club->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.club-transfer-completed',
            with: [
                'transfer' => $this->transfer,
                'admin' => $this->admin,
                'club' => $this->transfer->club,
                'sourceTenant' => $this->transfer->sourceTenant,
                'targetTenant' => $this->transfer->targetTenant,
                'initiatedBy' => $this->transfer->initiatedBy,
                'duration' => $this->transfer->getFormattedDuration(),
                'canRollback' => $this->transfer->canBeRolledBack(),
                'rollbackExpiresAt' => $this->transfer->rollback_expires_at,
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
