<?php

namespace App\Services\Invoice;

use App\Mail\Invoice\CancellationMail;
use App\Mail\Invoice\InvoiceMail;
use App\Mail\Invoice\PaymentConfirmationMail;
use App\Mail\Invoice\ReminderMail;
use App\Mail\Invoice\SuspensionWarningMail;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Class InvoiceNotificationService
 *
 * Service für das Versenden von Rechnungs-Benachrichtigungen.
 */
class InvoiceNotificationService
{
    /**
     * Send the invoice email.
     */
    public function sendInvoice(Invoice $invoice, array $recipients): void
    {
        if (empty($recipients)) {
            Log::warning('No recipients for invoice email', [
                'invoice_id' => $invoice->id,
            ]);
            return;
        }

        $primaryRecipient = array_shift($recipients);
        $cc = $this->getCc($recipients);

        Mail::to($primaryRecipient)
            ->cc($cc)
            ->send(new InvoiceMail($invoice));

        Log::info('Invoice email sent', [
            'invoice_id' => $invoice->id,
            'recipients' => array_merge([$primaryRecipient], $cc),
        ]);
    }

    /**
     * Send a payment reminder.
     */
    public function sendReminder(Invoice $invoice, array $recipients): void
    {
        if (empty($recipients)) {
            Log::warning('No recipients for reminder email', [
                'invoice_id' => $invoice->id,
            ]);
            return;
        }

        $primaryRecipient = array_shift($recipients);
        $cc = $this->getCc($recipients);

        Mail::to($primaryRecipient)
            ->cc($cc)
            ->send(new ReminderMail($invoice));

        Log::info('Reminder email sent', [
            'invoice_id' => $invoice->id,
            'reminder_count' => $invoice->reminder_count,
            'recipients' => array_merge([$primaryRecipient], $cc),
        ]);
    }

    /**
     * Send payment confirmation.
     */
    public function sendPaymentConfirmation(Invoice $invoice, array $recipients): void
    {
        if (empty($recipients)) {
            Log::warning('No recipients for payment confirmation email', [
                'invoice_id' => $invoice->id,
            ]);
            return;
        }

        $primaryRecipient = array_shift($recipients);
        $cc = $this->getCc($recipients);

        Mail::to($primaryRecipient)
            ->cc($cc)
            ->send(new PaymentConfirmationMail($invoice));

        Log::info('Payment confirmation email sent', [
            'invoice_id' => $invoice->id,
            'recipients' => array_merge([$primaryRecipient], $cc),
        ]);
    }

    /**
     * Send cancellation notification.
     */
    public function sendCancellation(Invoice $invoice, array $recipients): void
    {
        if (empty($recipients)) {
            Log::warning('No recipients for cancellation email', [
                'invoice_id' => $invoice->id,
            ]);
            return;
        }

        $primaryRecipient = array_shift($recipients);
        $cc = $this->getCc($recipients);

        Mail::to($primaryRecipient)
            ->cc($cc)
            ->send(new CancellationMail($invoice));

        Log::info('Cancellation email sent', [
            'invoice_id' => $invoice->id,
            'recipients' => array_merge([$primaryRecipient], $cc),
        ]);
    }

    /**
     * Send suspension warning.
     */
    public function sendSuspensionWarning(Invoice $invoice, array $recipients): void
    {
        if (empty($recipients)) {
            Log::warning('No recipients for suspension warning email', [
                'invoice_id' => $invoice->id,
            ]);
            return;
        }

        $primaryRecipient = array_shift($recipients);
        $cc = $this->getCc($recipients);

        Mail::to($primaryRecipient)
            ->cc($cc)
            ->send(new SuspensionWarningMail($invoice));

        Log::info('Suspension warning email sent', [
            'invoice_id' => $invoice->id,
            'recipients' => array_merge([$primaryRecipient], $cc),
        ]);
    }

    /**
     * Get CC recipients, including configured defaults.
     */
    protected function getCc(array $additionalRecipients = []): array
    {
        $configCc = config('invoices.email.cc', []);
        $cc = array_merge($additionalRecipients, $configCc);

        return array_unique(array_filter($cc));
    }

    /**
     * Get BCC recipients from config.
     */
    protected function getBcc(): array
    {
        return config('invoices.email.bcc', []);
    }
}
