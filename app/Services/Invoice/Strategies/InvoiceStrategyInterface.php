<?php

namespace App\Services\Invoice\Strategies;

use App\Contracts\Invoiceable;
use App\Models\Invoice;

/**
 * Interface InvoiceStrategyInterface
 *
 * Definiert die Methoden, die eine Invoice-Strategy implementieren muss.
 * Ermöglicht typ-spezifische Logik für Club- und Tenant-Rechnungen.
 */
interface InvoiceStrategyInterface
{
    /**
     * Get the invoiceable type this strategy handles.
     *
     * @return string The full class name (e.g., 'App\Models\Club')
     */
    public function getInvoiceableType(): string;

    /**
     * Get the invoice number prefix for this type.
     */
    public function getNumberPrefix(): string;

    /**
     * Validate invoice creation for this invoiceable.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateCreation(Invoiceable $invoiceable, array $data): void;

    /**
     * Hook called after an invoice is created.
     */
    public function afterCreate(Invoice $invoice): void;

    /**
     * Hook called after an invoice is sent.
     */
    public function afterSend(Invoice $invoice): void;

    /**
     * Hook called after an invoice is paid.
     */
    public function afterPayment(Invoice $invoice): void;

    /**
     * Hook called when an invoice becomes overdue.
     */
    public function afterOverdue(Invoice $invoice): void;

    /**
     * Hook called after an invoice is cancelled.
     */
    public function afterCancel(Invoice $invoice): void;

    /**
     * Get the notification recipients for an invoice.
     *
     * @return array Array of email addresses
     */
    public function getNotificationRecipients(Invoice $invoice): array;

    /**
     * Get additional data for the invoice PDF.
     */
    public function getPdfData(Invoice $invoice): array;

    /**
     * Get additional data for invoice emails.
     */
    public function getEmailData(Invoice $invoice): array;

    /**
     * Create subscription-related line items.
     */
    public function createSubscriptionLineItems(Invoiceable $invoiceable, string $billingPeriod): array;
}
