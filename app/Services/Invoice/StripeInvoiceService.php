<?php

namespace App\Services\Invoice;

use App\Contracts\Invoiceable;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\Invoice as StripeInvoice;
use Stripe\InvoiceItem;
use Stripe\StripeClient;

/**
 * Class StripeInvoiceService
 *
 * Service für die Stripe Invoice-Integration.
 */
class StripeInvoiceService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('cashier.secret'));
    }

    /**
     * Create a Stripe invoice for an invoiceable.
     */
    public function createStripeInvoice(Invoiceable $invoiceable, array $lineItems, array $options = []): StripeInvoice
    {
        $customerId = $invoiceable->getStripeCustomerId();

        if (!$customerId) {
            throw new \RuntimeException('Invoiceable hat keine Stripe Customer ID.');
        }

        try {
            // Create invoice items
            foreach ($lineItems as $item) {
                InvoiceItem::create([
                    'customer' => $customerId,
                    'amount' => (int) ($item['total'] * 100), // Convert to cents
                    'currency' => strtolower($options['currency'] ?? 'eur'),
                    'description' => $item['description'],
                ]);
            }

            // Create invoice
            $invoice = StripeInvoice::create([
                'customer' => $customerId,
                'collection_method' => 'send_invoice',
                'days_until_due' => $options['days_until_due'] ?? config('invoices.payment_terms_days', 14),
                'auto_advance' => $options['auto_finalize'] ?? config('invoices.stripe.auto_finalize', true),
                'metadata' => [
                    'invoiceable_type' => get_class($invoiceable),
                    'invoiceable_id' => $invoiceable->getKey(),
                    'tenant_id' => $invoiceable->getInvoiceableTenantId(),
                ],
            ]);

            Log::info('Stripe invoice created', [
                'stripe_invoice_id' => $invoice->id,
                'customer_id' => $customerId,
                'amount' => $invoice->amount_due,
            ]);

            return $invoice;

        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe invoice', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create a local invoice from a Stripe invoice.
     */
    public function createFromStripeInvoice(StripeInvoice $stripeInvoice, Invoiceable $invoiceable): Invoice
    {
        $lineItems = [];
        foreach ($stripeInvoice->lines->data as $line) {
            $lineItems[] = [
                'description' => $line->description,
                'quantity' => $line->quantity ?? 1,
                'unit_price' => $line->unit_amount / 100,
                'total' => $line->amount / 100,
            ];
        }

        $netAmount = $stripeInvoice->subtotal / 100;
        $taxAmount = ($stripeInvoice->tax ?? 0) / 100;
        $grossAmount = $stripeInvoice->amount_due / 100;

        return Invoice::create([
            'tenant_id' => $invoiceable->getInvoiceableTenantId(),
            'invoiceable_type' => get_class($invoiceable),
            'invoiceable_id' => $invoiceable->getKey(),
            'subscription_plan_type' => $invoiceable->getSubscriptionPlan() ? get_class($invoiceable->getSubscriptionPlan()) : null,
            'subscription_plan_id' => $invoiceable->getSubscriptionPlan()?->getKey(),
            'payment_method' => Invoice::PAYMENT_METHOD_STRIPE,
            'stripe_invoice_id' => $stripeInvoice->id,
            'stripe_payment_intent_id' => $stripeInvoice->payment_intent,
            'stripe_hosted_invoice_url' => $stripeInvoice->hosted_invoice_url,
            'stripe_invoice_pdf' => $stripeInvoice->invoice_pdf,
            'invoice_number' => $stripeInvoice->number ?? Invoice::generateNumber($invoiceable->getInvoiceableTenantId(), get_class($invoiceable)),
            'status' => $this->mapStripeStatus($stripeInvoice->status),
            'net_amount' => $netAmount,
            'tax_rate' => $taxAmount > 0 ? ($taxAmount / $netAmount) * 100 : 0,
            'tax_amount' => $taxAmount,
            'gross_amount' => $grossAmount,
            'currency' => strtoupper($stripeInvoice->currency),
            'billing_period' => $stripeInvoice->lines->data[0]->period->start
                ? date('Y-m', $stripeInvoice->lines->data[0]->period->start)
                : null,
            'line_items' => $lineItems,
            'billing_name' => $invoiceable->getBillingName(),
            'billing_email' => $stripeInvoice->customer_email ?? $invoiceable->getBillingEmail(),
            'billing_address' => $invoiceable->getBillingAddress(),
            'vat_number' => $invoiceable->getVatNumber(),
            'issue_date' => $stripeInvoice->created
                ? date('Y-m-d', $stripeInvoice->created)
                : now()->toDateString(),
            'due_date' => $stripeInvoice->due_date
                ? date('Y-m-d', $stripeInvoice->due_date)
                : now()->addDays(14)->toDateString(),
            'paid_at' => $stripeInvoice->status === 'paid'
                ? ($stripeInvoice->status_transitions->paid_at
                    ? date('Y-m-d', $stripeInvoice->status_transitions->paid_at)
                    : now()->toDateString())
                : null,
        ]);
    }

    /**
     * Sync invoice status from Stripe.
     */
    public function syncFromStripe(Invoice $invoice): Invoice
    {
        if (!$invoice->stripe_invoice_id) {
            return $invoice;
        }

        try {
            $stripeInvoice = StripeInvoice::retrieve($invoice->stripe_invoice_id);

            $invoice->update([
                'status' => $this->mapStripeStatus($stripeInvoice->status),
                'stripe_hosted_invoice_url' => $stripeInvoice->hosted_invoice_url,
                'stripe_invoice_pdf' => $stripeInvoice->invoice_pdf,
                'paid_at' => $stripeInvoice->status === 'paid'
                    ? ($stripeInvoice->status_transitions->paid_at
                        ? date('Y-m-d', $stripeInvoice->status_transitions->paid_at)
                        : now()->toDateString())
                    : null,
            ]);

            Log::info('Invoice synced from Stripe', [
                'invoice_id' => $invoice->id,
                'stripe_invoice_id' => $invoice->stripe_invoice_id,
                'status' => $invoice->status,
            ]);

        } catch (ApiErrorException $e) {
            Log::error('Failed to sync invoice from Stripe', [
                'invoice_id' => $invoice->id,
                'stripe_invoice_id' => $invoice->stripe_invoice_id,
                'error' => $e->getMessage(),
            ]);
        }

        return $invoice->fresh();
    }

    /**
     * Finalize a Stripe invoice.
     */
    public function finalizeStripeInvoice(Invoice $invoice): StripeInvoice
    {
        if (!$invoice->stripe_invoice_id) {
            throw new \RuntimeException('Invoice has no Stripe invoice ID.');
        }

        try {
            $stripeInvoice = StripeInvoice::retrieve($invoice->stripe_invoice_id);
            $stripeInvoice = $stripeInvoice->finalizeInvoice();

            $invoice->update([
                'status' => $this->mapStripeStatus($stripeInvoice->status),
                'stripe_hosted_invoice_url' => $stripeInvoice->hosted_invoice_url,
                'stripe_invoice_pdf' => $stripeInvoice->invoice_pdf,
            ]);

            Log::info('Stripe invoice finalized', [
                'invoice_id' => $invoice->id,
                'stripe_invoice_id' => $invoice->stripe_invoice_id,
            ]);

            return $stripeInvoice;

        } catch (ApiErrorException $e) {
            Log::error('Failed to finalize Stripe invoice', [
                'invoice_id' => $invoice->id,
                'stripe_invoice_id' => $invoice->stripe_invoice_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send a Stripe invoice.
     */
    public function sendStripeInvoice(Invoice $invoice): StripeInvoice
    {
        if (!$invoice->stripe_invoice_id) {
            throw new \RuntimeException('Invoice has no Stripe invoice ID.');
        }

        try {
            $stripeInvoice = StripeInvoice::retrieve($invoice->stripe_invoice_id);
            $stripeInvoice = $stripeInvoice->sendInvoice();

            $invoice->update([
                'status' => Invoice::STATUS_SENT,
            ]);

            Log::info('Stripe invoice sent', [
                'invoice_id' => $invoice->id,
                'stripe_invoice_id' => $invoice->stripe_invoice_id,
            ]);

            return $stripeInvoice;

        } catch (ApiErrorException $e) {
            Log::error('Failed to send Stripe invoice', [
                'invoice_id' => $invoice->id,
                'stripe_invoice_id' => $invoice->stripe_invoice_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Void a Stripe invoice.
     */
    public function voidStripeInvoice(Invoice $invoice): StripeInvoice
    {
        if (!$invoice->stripe_invoice_id) {
            throw new \RuntimeException('Invoice has no Stripe invoice ID.');
        }

        try {
            $stripeInvoice = StripeInvoice::retrieve($invoice->stripe_invoice_id);
            $stripeInvoice = $stripeInvoice->voidInvoice();

            $invoice->update([
                'status' => Invoice::STATUS_CANCELLED,
            ]);

            Log::info('Stripe invoice voided', [
                'invoice_id' => $invoice->id,
                'stripe_invoice_id' => $invoice->stripe_invoice_id,
            ]);

            return $stripeInvoice;

        } catch (ApiErrorException $e) {
            Log::error('Failed to void Stripe invoice', [
                'invoice_id' => $invoice->id,
                'stripe_invoice_id' => $invoice->stripe_invoice_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle Stripe invoice webhook event.
     */
    public function handleWebhook(string $eventType, array $data): void
    {
        $stripeInvoice = $data['object'] ?? null;

        if (!$stripeInvoice) {
            return;
        }

        $stripeInvoiceId = $stripeInvoice['id'] ?? null;
        if (!$stripeInvoiceId) {
            return;
        }

        // Find local invoice
        $invoice = Invoice::where('stripe_invoice_id', $stripeInvoiceId)->first();

        if (!$invoice && isset($stripeInvoice['metadata'])) {
            // Try to create from metadata
            $invoiceableType = $stripeInvoice['metadata']['invoiceable_type'] ?? null;
            $invoiceableId = $stripeInvoice['metadata']['invoiceable_id'] ?? null;

            if ($invoiceableType && $invoiceableId) {
                $invoiceable = $invoiceableType::find($invoiceableId);
                if ($invoiceable && $invoiceable instanceof Invoiceable) {
                    $fullStripeInvoice = StripeInvoice::retrieve($stripeInvoiceId);
                    $invoice = $this->createFromStripeInvoice($fullStripeInvoice, $invoiceable);
                }
            }
        }

        if (!$invoice) {
            Log::warning('Invoice not found for Stripe webhook', [
                'stripe_invoice_id' => $stripeInvoiceId,
                'event_type' => $eventType,
            ]);
            return;
        }

        // Handle event
        switch ($eventType) {
            case 'invoice.paid':
                $invoice->update([
                    'status' => Invoice::STATUS_PAID,
                    'paid_at' => now(),
                ]);
                $invoice->invoiceable?->onInvoicePaid($invoice);
                break;

            case 'invoice.payment_failed':
                $invoice->update([
                    'status' => Invoice::STATUS_OVERDUE,
                ]);
                $invoice->invoiceable?->onInvoiceOverdue($invoice);
                break;

            case 'invoice.finalized':
                $invoice->update([
                    'status' => Invoice::STATUS_SENT,
                    'stripe_hosted_invoice_url' => $stripeInvoice['hosted_invoice_url'] ?? null,
                    'stripe_invoice_pdf' => $stripeInvoice['invoice_pdf'] ?? null,
                ]);
                break;

            case 'invoice.voided':
                $invoice->update([
                    'status' => Invoice::STATUS_CANCELLED,
                ]);
                break;
        }

        Log::info('Stripe invoice webhook processed', [
            'invoice_id' => $invoice->id,
            'event_type' => $eventType,
        ]);
    }

    /**
     * Map Stripe invoice status to local status.
     */
    protected function mapStripeStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'draft' => Invoice::STATUS_DRAFT,
            'open' => Invoice::STATUS_SENT,
            'paid' => Invoice::STATUS_PAID,
            'uncollectible' => Invoice::STATUS_OVERDUE,
            'void' => Invoice::STATUS_CANCELLED,
            default => Invoice::STATUS_DRAFT,
        };
    }

    /**
     * Get Stripe invoices for an invoiceable.
     */
    public function getStripeInvoices(Invoiceable $invoiceable, int $limit = 10): array
    {
        $customerId = $invoiceable->getStripeCustomerId();

        if (!$customerId) {
            return [];
        }

        try {
            $invoices = StripeInvoice::all([
                'customer' => $customerId,
                'limit' => $limit,
            ]);

            return $invoices->data;

        } catch (ApiErrorException $e) {
            Log::error('Failed to fetch Stripe invoices', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get upcoming Stripe invoice for an invoiceable.
     */
    public function getUpcomingInvoice(Invoiceable $invoiceable): ?StripeInvoice
    {
        $customerId = $invoiceable->getStripeCustomerId();

        if (!$customerId) {
            return null;
        }

        try {
            return StripeInvoice::upcoming([
                'customer' => $customerId,
            ]);

        } catch (ApiErrorException $e) {
            // No upcoming invoice is a normal case
            if ($e->getStripeCode() !== 'invoice_upcoming_none') {
                Log::error('Failed to fetch upcoming Stripe invoice', [
                    'customer_id' => $customerId,
                    'error' => $e->getMessage(),
                ]);
            }
            return null;
        }
    }
}
