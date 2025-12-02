<?php

namespace App\Mail\ClubInvoice;

use App\Models\ClubInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CancellationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public ClubInvoice $invoice
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Stornierung der Rechnung {$this->invoice->invoice_number}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.club-invoice.cancellation',
            with: [
                'invoice' => $this->invoice,
                'club' => $this->invoice->club,
                'invoiceNumber' => $this->invoice->invoice_number,
                'totalAmount' => $this->invoice->total_amount,
                'issueDate' => $this->invoice->issue_date,
                'cancelledAt' => $this->invoice->cancelled_at ?? now(),
                'cancellationReason' => $this->invoice->cancellation_reason,
                'billingName' => $this->invoice->billing_name,
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
