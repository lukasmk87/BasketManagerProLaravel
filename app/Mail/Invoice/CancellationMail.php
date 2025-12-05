<?php

namespace App\Mail\Invoice;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CancellationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Stornierung: Rechnung {$this->invoice->invoice_number} - " . app_name(),
            replyTo: config('invoices.email.reply_to', config('mail.from.address')),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice.cancellation',
            with: [
                'invoice' => $this->invoice,
                'invoiceable' => $this->invoice->invoiceable,
                'formatted_amounts' => $this->invoice->formatted_amounts,
                'cancellation_reason' => $this->invoice->payment_notes,
                'company' => [
                    'name' => config('invoices.company.name', app_name()),
                    'email' => config('invoices.company.email'),
                ],
            ],
        );
    }
}
