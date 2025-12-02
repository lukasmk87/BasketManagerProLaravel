<?php

namespace App\Services\Invoice;

use App\Mail\ClubInvoice\CancellationMail;
use App\Mail\ClubInvoice\InvoiceMail;
use App\Mail\ClubInvoice\PaymentConfirmationMail;
use App\Mail\ClubInvoice\ReminderMail;
use App\Mail\ClubInvoice\SuspensionWarningMail;
use App\Models\ClubInvoice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ClubInvoiceNotificationService
{
    /**
     * Send invoice to the club.
     */
    public function sendInvoice(ClubInvoice $invoice): void
    {
        try {
            $pdfService = app(ClubInvoicePdfService::class);
            $pdfContent = $pdfService->getContent($invoice);

            Mail::to($invoice->billing_email)
                ->cc($this->getCcRecipients())
                ->bcc($this->getBccRecipients())
                ->send(new InvoiceMail($invoice, $pdfContent));

            Log::info("Invoice sent successfully", [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'recipient' => $invoice->billing_email,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send invoice", [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send payment reminder.
     */
    public function sendReminder(ClubInvoice $invoice, int $reminderLevel): void
    {
        try {
            $pdfService = app(ClubInvoicePdfService::class);
            $pdfContent = $pdfService->getContent($invoice);

            Mail::to($invoice->billing_email)
                ->cc($this->getCcRecipients())
                ->send(new ReminderMail($invoice, $reminderLevel, $pdfContent));

            Log::info("Payment reminder sent", [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'reminder_level' => $reminderLevel,
                'recipient' => $invoice->billing_email,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send reminder", [
                'invoice_id' => $invoice->id,
                'reminder_level' => $reminderLevel,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send payment confirmation.
     */
    public function sendPaymentConfirmation(ClubInvoice $invoice): void
    {
        try {
            Mail::to($invoice->billing_email)
                ->send(new PaymentConfirmationMail($invoice));

            Log::info("Payment confirmation sent", [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'recipient' => $invoice->billing_email,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send payment confirmation", [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - payment confirmation is not critical
        }
    }

    /**
     * Send invoice cancellation notification.
     */
    public function sendCancellation(ClubInvoice $invoice): void
    {
        try {
            Mail::to($invoice->billing_email)
                ->send(new CancellationMail($invoice));

            Log::info("Cancellation notification sent", [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'recipient' => $invoice->billing_email,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send cancellation notification", [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - cancellation notification is not critical
        }
    }

    /**
     * Send suspension warning.
     */
    public function sendSuspensionWarning(ClubInvoice $invoice): void
    {
        try {
            Mail::to($invoice->billing_email)
                ->cc($this->getCcRecipients())
                ->send(new SuspensionWarningMail($invoice));

            Log::info("Suspension warning sent", [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'recipient' => $invoice->billing_email,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send suspension warning", [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get CC recipients from config.
     */
    protected function getCcRecipients(): array
    {
        $cc = config('invoices.email.cc');
        return $cc ? array_map('trim', explode(',', $cc)) : [];
    }

    /**
     * Get BCC recipients from config.
     */
    protected function getBccRecipients(): array
    {
        $bcc = config('invoices.email.bcc');
        return $bcc ? array_map('trim', explode(',', $bcc)) : [];
    }
}
