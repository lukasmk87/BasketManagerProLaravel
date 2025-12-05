<?php

namespace App\Contracts;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface Invoiceable
 *
 * Definiert die Methoden, die eine Entität implementieren muss,
 * um Rechnungen empfangen zu können (z.B. Club, Tenant).
 */
interface Invoiceable
{
    /**
     * Get the billing name for invoices.
     */
    public function getBillingName(): string;

    /**
     * Get the billing email for invoices.
     */
    public function getBillingEmail(): string;

    /**
     * Get the billing address for invoices.
     *
     * @return array{street?: string, city?: string, zip?: string, country?: string}|null
     */
    public function getBillingAddress(): ?array;

    /**
     * Get the VAT number for invoices.
     */
    public function getVatNumber(): ?string;

    /**
     * Get the current subscription plan.
     */
    public function getSubscriptionPlan(): ?Model;

    /**
     * Get the Stripe customer ID.
     */
    public function getStripeCustomerId(): ?string;

    /**
     * Get the tenant ID this invoiceable belongs to.
     * For Tenants, this returns their own ID.
     * For Clubs, this returns the parent tenant's ID.
     */
    public function getInvoiceableTenantId(): string;

    /**
     * Get the preferred payment method for this invoiceable.
     *
     * @return string 'stripe' or 'bank_transfer'
     */
    public function getPreferredPaymentMethod(): string;

    /**
     * Check if this invoiceable pays via invoice (bank transfer).
     */
    public function paysViaInvoice(): bool;

    /**
     * Called when an invoice is paid.
     * Can be used to activate subscriptions, update status, etc.
     */
    public function onInvoicePaid(Invoice $invoice): void;

    /**
     * Called when an invoice becomes overdue.
     * Can be used to send warnings, suspend access, etc.
     */
    public function onInvoiceOverdue(Invoice $invoice): void;

    /**
     * Get the display name for this invoiceable type.
     * Used in UI and notifications.
     */
    public function getInvoiceableTypeName(): string;
}
