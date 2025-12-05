<?php

namespace App\Mail\Invoice;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SuspensionWarningMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Wichtig: Drohende Sperrung wegen ausstehender Zahlung - " . app_name(),
            replyTo: config('invoices.email.reply_to', config('mail.from.address')),
        );
    }

    public function content(): Content
    {
        $suspensionDays = config('invoices.suspension.days_after_due', 30);
        $daysUntilSuspension = $suspensionDays - $this->invoice->daysOverdue();

        return new Content(
            view: 'emails.invoice.suspension-warning',
            with: [
                'invoice' => $this->invoice,
                'invoiceable' => $this->invoice->invoiceable,
                'formatted_amounts' => $this->invoice->formatted_amounts,
                'days_overdue' => $this->invoice->daysOverdue(),
                'days_until_suspension' => max(0, $daysUntilSuspension),
                'company' => [
                    'name' => config('invoices.company.name', app_name()),
                    'email' => config('invoices.company.email'),
                    'phone' => config('invoices.company.phone'),
                ],
                'bank' => [
                    'name' => config('invoices.bank.name'),
                    'iban' => config('invoices.bank.iban'),
                    'bic' => config('invoices.bank.bic'),
                ],
            ],
        );
    }
}
