<?php

namespace App\Services\Stripe;

use App\Models\Club;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Stripe\Collection as StripeCollection;
use Stripe\Exception\StripeException;
use Stripe\Invoice;

/**
 * Service for managing Stripe invoices for club subscriptions.
 *
 * Provides methods to retrieve, list, and download invoices for clubs
 * with active Stripe subscriptions.
 */
class ClubInvoiceService
{
    public function __construct(
        private StripeClientManager $clientManager
    ) {}

    /**
     * Get all invoices for a club.
     *
     * @param  Club  $club
     * @param  array  $options  Optional filters: limit, starting_after, ending_before, status
     * @return Collection Collection of formatted invoice data
     *
     * @throws \Exception
     */
    public function getInvoices(Club $club, array $options = []): Collection
    {
        if (! $club->stripe_customer_id) {
            throw new \Exception('Club has no Stripe customer');
        }

        $client = $this->clientManager->getCurrentTenantClient();

        $params = [
            'customer' => $club->stripe_customer_id,
            'limit' => $options['limit'] ?? 100,
        ];

        // Optional filters
        if (isset($options['starting_after'])) {
            $params['starting_after'] = $options['starting_after'];
        }

        if (isset($options['ending_before'])) {
            $params['ending_before'] = $options['ending_before'];
        }

        if (isset($options['status'])) {
            $params['status'] = $options['status'];
        }

        try {
            $invoices = $client->invoices->all($params);

            Log::info('Club invoices retrieved', [
                'club_id' => $club->id,
                'club_name' => $club->name,
                'invoice_count' => count($invoices->data),
                'tenant_id' => $club->tenant_id,
            ]);

            return collect($invoices->data)->map(function ($invoice) {
                return $this->formatInvoice($invoice);
            });
        } catch (StripeException $e) {
            Log::error('Failed to retrieve club invoices', [
                'club_id' => $club->id,
                'stripe_customer_id' => $club->stripe_customer_id,
                'tenant_id' => $club->tenant_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get a single invoice by ID.
     *
     * @param  Club  $club
     * @param  string  $invoiceId
     * @return array Formatted invoice data
     *
     * @throws \Exception
     */
    public function getInvoice(Club $club, string $invoiceId): array
    {
        if (! $club->stripe_customer_id) {
            throw new \Exception('Club has no Stripe customer');
        }

        $client = $this->clientManager->getCurrentTenantClient();

        try {
            $invoice = $client->invoices->retrieve($invoiceId);

            // Verify invoice belongs to this club
            if ($invoice->customer !== $club->stripe_customer_id) {
                throw new \Exception('Invoice does not belong to this club');
            }

            Log::info('Club invoice retrieved', [
                'club_id' => $club->id,
                'invoice_id' => $invoiceId,
                'amount' => $invoice->amount_due / 100,
                'tenant_id' => $club->tenant_id,
            ]);

            return $this->formatInvoice($invoice);
        } catch (StripeException $e) {
            Log::error('Failed to retrieve club invoice', [
                'club_id' => $club->id,
                'invoice_id' => $invoiceId,
                'tenant_id' => $club->tenant_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get upcoming invoice preview for club.
     *
     * Shows what the next invoice will look like based on current subscription.
     *
     * @param  Club  $club
     * @param  array  $options  Optional: subscription, subscription_items, coupon
     * @return array|null Formatted upcoming invoice data or null if no subscription
     *
     * @throws \Exception
     */
    public function getUpcomingInvoice(Club $club, array $options = []): ?array
    {
        if (! $club->stripe_customer_id) {
            throw new \Exception('Club has no Stripe customer');
        }

        if (! $club->stripe_subscription_id) {
            Log::info('Club has no subscription for upcoming invoice', [
                'club_id' => $club->id,
                'tenant_id' => $club->tenant_id,
            ]);

            return null;
        }

        $client = $this->clientManager->getCurrentTenantClient();

        $params = [
            'customer' => $club->stripe_customer_id,
        ];

        // Add subscription if provided (useful for plan swap previews)
        if (isset($options['subscription'])) {
            $params['subscription'] = $options['subscription'];
        }

        // Add subscription items if provided (for plan changes)
        if (isset($options['subscription_items'])) {
            $params['subscription_items'] = $options['subscription_items'];
        }

        // Add coupon if provided
        if (isset($options['coupon'])) {
            $params['coupon'] = $options['coupon'];
        }

        // Add proration date if provided
        if (isset($options['subscription_proration_date'])) {
            $params['subscription_proration_date'] = $options['subscription_proration_date'];
        }

        try {
            $invoice = $client->invoices->upcoming($params);

            Log::info('Club upcoming invoice retrieved', [
                'club_id' => $club->id,
                'amount_due' => $invoice->amount_due / 100,
                'period_start' => $invoice->period_start,
                'period_end' => $invoice->period_end,
                'tenant_id' => $club->tenant_id,
            ]);

            return $this->formatInvoice($invoice);
        } catch (StripeException $e) {
            Log::error('Failed to retrieve upcoming invoice', [
                'club_id' => $club->id,
                'stripe_customer_id' => $club->stripe_customer_id,
                'tenant_id' => $club->tenant_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Download invoice PDF.
     *
     * Returns the URL to download the invoice PDF from Stripe.
     *
     * @param  Club  $club
     * @param  string  $invoiceId
     * @return string PDF download URL
     *
     * @throws \Exception
     */
    public function getInvoicePdfUrl(Club $club, string $invoiceId): string
    {
        $invoice = $this->getInvoice($club, $invoiceId);

        if (! isset($invoice['invoice_pdf'])) {
            throw new \Exception('Invoice PDF not available');
        }

        Log::info('Club invoice PDF URL retrieved', [
            'club_id' => $club->id,
            'invoice_id' => $invoiceId,
            'tenant_id' => $club->tenant_id,
        ]);

        return $invoice['invoice_pdf'];
    }

    /**
     * Get payment intent for an invoice (useful for 3D Secure authentication).
     *
     * @param  Club  $club
     * @param  string  $invoiceId
     * @return string|null Payment intent ID or null if not applicable
     *
     * @throws \Exception
     */
    public function getInvoicePaymentIntent(Club $club, string $invoiceId): ?string
    {
        $invoice = $this->getInvoice($club, $invoiceId);

        return $invoice['payment_intent'] ?? null;
    }

    /**
     * Pay an invoice manually (for failed payments).
     *
     * @param  Club  $club
     * @param  string  $invoiceId
     * @param  array  $options  Optional: payment_method, off_session
     * @return array Updated invoice data
     *
     * @throws \Exception
     */
    public function payInvoice(Club $club, string $invoiceId, array $options = []): array
    {
        if (! $club->stripe_customer_id) {
            throw new \Exception('Club has no Stripe customer');
        }

        $client = $this->clientManager->getCurrentTenantClient();

        try {
            // First, retrieve the invoice to verify ownership
            $invoice = $client->invoices->retrieve($invoiceId);

            if ($invoice->customer !== $club->stripe_customer_id) {
                throw new \Exception('Invoice does not belong to this club');
            }

            // Pay the invoice
            $params = [];
            if (isset($options['payment_method'])) {
                $params['payment_method'] = $options['payment_method'];
            }

            if (isset($options['off_session'])) {
                $params['off_session'] = $options['off_session'];
            }

            $invoice = $client->invoices->pay($invoiceId, $params);

            Log::info('Club invoice paid', [
                'club_id' => $club->id,
                'invoice_id' => $invoiceId,
                'amount_paid' => $invoice->amount_paid / 100,
                'tenant_id' => $club->tenant_id,
            ]);

            return $this->formatInvoice($invoice);
        } catch (StripeException $e) {
            Log::error('Failed to pay club invoice', [
                'club_id' => $club->id,
                'invoice_id' => $invoiceId,
                'tenant_id' => $club->tenant_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Format invoice data for consistent API responses.
     *
     * @param  Invoice  $invoice
     * @return array Formatted invoice data
     */
    protected function formatInvoice(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'object' => $invoice->object,
            'amount_due' => $invoice->amount_due / 100,
            'amount_paid' => $invoice->amount_paid / 100,
            'amount_remaining' => $invoice->amount_remaining / 100,
            'currency' => strtoupper($invoice->currency),
            'status' => $invoice->status,
            'paid' => $invoice->paid,
            'attempted' => $invoice->attempted,
            'number' => $invoice->number,
            'invoice_pdf' => $invoice->invoice_pdf,
            'hosted_invoice_url' => $invoice->hosted_invoice_url,
            'created' => $invoice->created,
            'due_date' => $invoice->due_date,
            'period_start' => $invoice->period_start,
            'period_end' => $invoice->period_end,
            'subscription' => $invoice->subscription,
            'customer' => $invoice->customer,
            'payment_intent' => $invoice->payment_intent,
            'description' => $invoice->description,
            'lines' => $this->formatInvoiceLines($invoice->lines),
            'subtotal' => $invoice->subtotal / 100,
            'total' => $invoice->total / 100,
            'tax' => $invoice->tax ? $invoice->tax / 100 : 0,
            'discount' => $invoice->discount,
            'billing_reason' => $invoice->billing_reason,
            'collection_method' => $invoice->collection_method,
            'charge' => $invoice->charge,
        ];
    }

    /**
     * Format invoice line items.
     *
     * @param  StripeCollection  $lines
     * @return array Formatted line items
     */
    protected function formatInvoiceLines(StripeCollection $lines): array
    {
        return collect($lines->data)->map(function ($line) {
            return [
                'id' => $line->id,
                'amount' => $line->amount / 100,
                'currency' => strtoupper($line->currency),
                'description' => $line->description,
                'quantity' => $line->quantity,
                'period' => [
                    'start' => $line->period->start,
                    'end' => $line->period->end,
                ],
                'proration' => $line->proration,
                'plan' => $line->plan ? [
                    'id' => $line->plan->id,
                    'amount' => $line->plan->amount / 100,
                    'currency' => strtoupper($line->plan->currency),
                    'interval' => $line->plan->interval,
                    'product' => $line->plan->product,
                ] : null,
            ];
        })->toArray();
    }
}
