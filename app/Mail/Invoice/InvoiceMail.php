<?php

namespace App\Mail\Invoice;

use App\Models\Invoice;
use App\Services\Invoice\InvoicePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
    ) {}

    public function envelope(): Envelope
    {
        $type = $this->invoice->isTenantInvoice() ? 'Tenant' : 'Club';

        return new Envelope(
            subject: "Rechnung {$this->invoice->invoice_number} - " . app_name(),
            replyTo: config('invoices.email.reply_to', config('mail.from.address')),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice.invoice',
            with: [
                'invoice' => $this->invoice,
                'invoiceable' => $this->invoice->invoiceable,
                'formatted_amounts' => $this->invoice->formatted_amounts,
                'company' => [
                    'name' => config('invoices.company.name', app_name()),
                    'email' => config('invoices.company.email'),
                ],
                'bank' => [
                    'name' => config('invoices.bank.name'),
                    'iban' => config('invoices.bank.iban'),
                    'bic' => config('invoices.bank.bic'),
                ],
            ],
        );
    }

    public function attachments(): array
    {
        $pdfService = app(InvoicePdfService::class);
        $pdfContent = $pdfService->getContent($this->invoice);

        if (!$pdfContent) {
            return [];
        }

        return [
            Attachment::fromData(
                fn () => $pdfContent,
                "rechnung_{$this->invoice->invoice_number}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}
