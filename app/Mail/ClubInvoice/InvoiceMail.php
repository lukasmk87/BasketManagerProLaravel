<?php

namespace App\Mail\ClubInvoice;

use App\Models\ClubInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public ClubInvoice $invoice,
        public string $pdfContent
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Rechnung {$this->invoice->invoice_number}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.club-invoice.invoice',
            with: [
                'invoice' => $this->invoice,
                'club' => $this->invoice->club,
                'invoiceNumber' => $this->invoice->invoice_number,
                'totalAmount' => $this->invoice->total_amount,
                'netAmount' => $this->invoice->net_amount,
                'taxAmount' => $this->invoice->tax_amount,
                'taxRate' => $this->invoice->tax_rate,
                'issueDate' => $this->invoice->issue_date,
                'dueDate' => $this->invoice->due_date,
                'billingName' => $this->invoice->billing_name,
                'billingAddress' => $this->invoice->billing_address,
                'description' => $this->invoice->description,
                'lineItems' => $this->invoice->line_items,
                'bankDetails' => [
                    'name' => config('invoices.bank.name'),
                    'iban' => config('invoices.bank.iban'),
                    'bic' => config('invoices.bank.bic'),
                    'account_holder' => config('invoices.bank.account_holder'),
                ],
                'paymentTermsDays' => config('invoices.payment_terms_days', 14),
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
        return [
            Attachment::fromData(fn () => $this->pdfContent, "Rechnung_{$this->invoice->invoice_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
