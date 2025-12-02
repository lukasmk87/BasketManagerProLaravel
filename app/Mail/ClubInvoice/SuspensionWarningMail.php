<?php

namespace App\Mail\ClubInvoice;

use App\Models\ClubInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SuspensionWarningMail extends Mailable
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
            subject: "Dringend: Kontosperrung droht - Rechnung {$this->invoice->invoice_number}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $suspensionDays = config('invoices.suspension.days_after_due', 30);
        $daysOverdue = $this->invoice->due_date?->diffInDays(now()) ?? 0;
        $daysUntilSuspension = max(0, $suspensionDays - $daysOverdue);

        return new Content(
            markdown: 'emails.club-invoice.suspension-warning',
            with: [
                'invoice' => $this->invoice,
                'club' => $this->invoice->club,
                'invoiceNumber' => $this->invoice->invoice_number,
                'totalAmount' => $this->invoice->total_amount,
                'dueDate' => $this->invoice->due_date,
                'daysOverdue' => $daysOverdue,
                'daysUntilSuspension' => $daysUntilSuspension,
                'suspensionDate' => $this->invoice->due_date?->addDays($suspensionDays),
                'billingName' => $this->invoice->billing_name,
                'bankDetails' => [
                    'name' => config('invoices.bank.name'),
                    'iban' => config('invoices.bank.iban'),
                    'bic' => config('invoices.bank.bic'),
                    'account_holder' => config('invoices.bank.account_holder'),
                ],
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
