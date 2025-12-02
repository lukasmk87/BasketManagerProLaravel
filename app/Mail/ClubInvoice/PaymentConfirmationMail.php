<?php

namespace App\Mail\ClubInvoice;

use App\Models\ClubInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentConfirmationMail extends Mailable
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
            subject: "Zahlungsbestätigung für Rechnung {$this->invoice->invoice_number}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.club-invoice.payment-confirmation',
            with: [
                'invoice' => $this->invoice,
                'club' => $this->invoice->club,
                'invoiceNumber' => $this->invoice->invoice_number,
                'totalAmount' => $this->invoice->total_amount,
                'paidAt' => $this->invoice->paid_at,
                'paymentMethod' => $this->invoice->payment_method,
                'transactionId' => $this->invoice->transaction_id,
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
