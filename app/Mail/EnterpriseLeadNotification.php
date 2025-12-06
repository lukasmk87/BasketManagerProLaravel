<?php

namespace App\Mail;

use App\Models\EnterpriseLead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EnterpriseLeadNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public EnterpriseLead $lead
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Neue Enterprise-Anfrage: ' . $this->lead->organization_name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.enterprise.lead-notification',
            with: [
                'lead' => $this->lead,
                'organizationType' => $this->lead->getOrganizationTypeLabel(),
                'clubCount' => EnterpriseLead::CLUB_COUNT_OPTIONS[$this->lead->club_count] ?? $this->lead->club_count,
                'teamCount' => EnterpriseLead::TEAM_COUNT_OPTIONS[$this->lead->team_count] ?? $this->lead->team_count,
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
