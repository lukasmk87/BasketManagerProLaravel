<?php

namespace App\Models\Concerns;

use App\Models\Invoice;
use App\Models\InvoiceRequest;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Trait HasInvoices
 *
 * Stellt Invoice-bezogene Relationen und Hilfsmethoden bereit
 * für Models, die das Invoiceable Interface implementieren.
 */
trait HasInvoices
{
    /**
     * Get all invoices for this entity.
     */
    public function invoices(): MorphMany
    {
        return $this->morphMany(Invoice::class, 'invoiceable');
    }

    /**
     * Get all invoice requests for this entity.
     */
    public function invoiceRequests(): MorphMany
    {
        return $this->morphMany(InvoiceRequest::class, 'requestable');
    }

    /**
     * Get pending invoices (sent or overdue).
     */
    public function pendingInvoices(): MorphMany
    {
        return $this->invoices()->pending();
    }

    /**
     * Get paid invoices.
     */
    public function paidInvoices(): MorphMany
    {
        return $this->invoices()->paid();
    }

    /**
     * Get draft invoices.
     */
    public function draftInvoices(): MorphMany
    {
        return $this->invoices()->draft();
    }

    /**
     * Get overdue invoices.
     */
    public function overdueInvoices(): MorphMany
    {
        return $this->invoices()->overdue();
    }

    /**
     * Get the total pending amount.
     */
    public function getPendingInvoiceAmount(): float
    {
        return (float) $this->pendingInvoices()->sum('gross_amount');
    }

    /**
     * Get the total paid amount.
     */
    public function getPaidInvoiceAmount(): float
    {
        return (float) $this->paidInvoices()->sum('gross_amount');
    }

    /**
     * Get the total overdue amount.
     */
    public function getOverdueInvoiceAmount(): float
    {
        return (float) $this->overdueInvoices()->sum('gross_amount');
    }

    /**
     * Check if there are any overdue invoices.
     */
    public function hasOverdueInvoices(): bool
    {
        return $this->overdueInvoices()->exists();
    }

    /**
     * Check if there are any pending invoice requests.
     */
    public function hasPendingInvoiceRequest(): bool
    {
        return $this->invoiceRequests()->where('status', 'pending')->exists();
    }

    /**
     * Get the latest invoice.
     */
    public function latestInvoice(): ?Invoice
    {
        return $this->invoices()->latest()->first();
    }

    /**
     * Get the latest paid invoice.
     */
    public function latestPaidInvoice(): ?Invoice
    {
        return $this->paidInvoices()->latest('paid_at')->first();
    }

    /**
     * Get invoices for a specific year.
     */
    public function invoicesForYear(int $year): MorphMany
    {
        return $this->invoices()->fromYear($year);
    }

    /**
     * Get invoice statistics.
     *
     * @return array{total: int, draft: int, sent: int, paid: int, overdue: int, cancelled: int, pending_amount: float, paid_amount: float}
     */
    public function getInvoiceStatistics(): array
    {
        $invoices = $this->invoices()->get();

        return [
            'total' => $invoices->count(),
            'draft' => $invoices->where('status', Invoice::STATUS_DRAFT)->count(),
            'sent' => $invoices->where('status', Invoice::STATUS_SENT)->count(),
            'paid' => $invoices->where('status', Invoice::STATUS_PAID)->count(),
            'overdue' => $invoices->where('status', Invoice::STATUS_OVERDUE)->count(),
            'cancelled' => $invoices->where('status', Invoice::STATUS_CANCELLED)->count(),
            'pending_amount' => (float) $invoices->whereIn('status', [Invoice::STATUS_SENT, Invoice::STATUS_OVERDUE])->sum('gross_amount'),
            'paid_amount' => (float) $invoices->where('status', Invoice::STATUS_PAID)->sum('gross_amount'),
        ];
    }

    /**
     * Create a new invoice for this entity.
     *
     * @param array{
     *     net_amount: float,
     *     tax_rate?: float,
     *     billing_period?: string,
     *     description?: string,
     *     line_items?: array,
     *     issue_date?: string,
     *     due_date?: string,
     *     payment_method?: string,
     * } $data
     */
    public function createInvoice(array $data): Invoice
    {
        $amounts = Invoice::calculateAmounts(
            $data['net_amount'],
            $data['tax_rate'] ?? config('invoices.default_tax_rate', 19.00)
        );

        return $this->invoices()->create([
            'tenant_id' => $this->getInvoiceableTenantId(),
            'subscription_plan_type' => $this->getSubscriptionPlan() ? get_class($this->getSubscriptionPlan()) : null,
            'subscription_plan_id' => $this->getSubscriptionPlan()?->id,
            'payment_method' => $data['payment_method'] ?? $this->getPreferredPaymentMethod(),
            'invoice_number' => Invoice::generateNumber($this->getInvoiceableTenantId()),
            'status' => Invoice::STATUS_DRAFT,
            'net_amount' => $amounts['net_amount'],
            'tax_rate' => $amounts['tax_rate'],
            'tax_amount' => $amounts['tax_amount'],
            'gross_amount' => $amounts['gross_amount'],
            'currency' => $data['currency'] ?? 'EUR',
            'billing_period' => $data['billing_period'] ?? null,
            'description' => $data['description'] ?? null,
            'line_items' => $data['line_items'] ?? null,
            'billing_name' => $this->getBillingName(),
            'billing_email' => $this->getBillingEmail(),
            'billing_address' => $this->getBillingAddress(),
            'vat_number' => $this->getVatNumber(),
            'issue_date' => $data['issue_date'] ?? now()->toDateString(),
            'due_date' => $data['due_date'] ?? now()->addDays(config('invoices.payment_terms_days', 14))->toDateString(),
            'created_by' => auth()->id(),
        ]);
    }
}
