<?php

namespace App\Mail\ClubInvoice;

use App\Models\ClubInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public ClubInvoice $invoice,
        public int $reminderLevel,
        public string $pdfContent
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match ($this->reminderLevel) {
            1 => "Zahlungserinnerung: Rechnung {$this->invoice->invoice_number}",
            2 => "2. Mahnung: Rechnung {$this->invoice->invoice_number}",
            default => "Letzte Mahnung: Rechnung {$this->invoice->invoice_number}",
        };

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.club-invoice.reminder',
            with: [
                'invoice' => $this->invoice,
                'club' => $this->invoice->club,
                'reminderLevel' => $this->reminderLevel,
                'invoiceNumber' => $this->invoice->invoice_number,
                'totalAmount' => $this->invoice->total_amount,
                'dueDate' => $this->invoice->due_date,
                'daysOverdue' => $this->invoice->due_date?->diffInDays(now()),
                'billingName' => $this->invoice->billing_name,
                'bankDetails' => [
                    'name' => config('invoices.bank.name'),
                    'iban' => config('invoices.bank.iban'),
                    'bic' => config('invoices.bank.bic'),
                    'account_holder' => config('invoices.bank.account_holder'),
                ],
                'suspensionDays' => config('invoices.suspension.days_after_due', 30),
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
