<?php

namespace App\Services\Invoice;

use App\Contracts\Invoiceable;
use App\Models\Club;
use App\Models\Invoice;
use App\Models\InvoiceRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Invoice\Strategies\ClubInvoiceStrategy;
use App\Services\Invoice\Strategies\InvoiceStrategyInterface;
use App\Services\Invoice\Strategies\TenantInvoiceStrategy;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class InvoiceService
 *
 * Haupt-Service für die Rechnungsverwaltung.
 * Verwendet Strategy-Pattern für typ-spezifische Logik.
 */
class InvoiceService
{
    /**
     * Registered strategies.
     *
     * @var array<string, InvoiceStrategyInterface>
     */
    protected array $strategies = [];

    public function __construct(
        protected InvoicePdfService $pdfService,
        protected InvoiceNotificationService $notificationService,
    ) {
        $this->registerDefaultStrategies();
    }

    /**
     * Register default strategies.
     */
    protected function registerDefaultStrategies(): void
    {
        $this->registerStrategy(new ClubInvoiceStrategy());
        $this->registerStrategy(new TenantInvoiceStrategy());
    }

    /**
     * Register a strategy.
     */
    public function registerStrategy(InvoiceStrategyInterface $strategy): void
    {
        $this->strategies[$strategy->getInvoiceableType()] = $strategy;
    }

    /**
     * Get strategy for an invoiceable.
     */
    protected function getStrategy(Invoiceable $invoiceable): InvoiceStrategyInterface
    {
        $type = get_class($invoiceable);

        if (!isset($this->strategies[$type])) {
            throw new \InvalidArgumentException("No strategy registered for type: {$type}");
        }

        return $this->strategies[$type];
    }

    /**
     * Get strategy by invoice.
     */
    protected function getStrategyForInvoice(Invoice $invoice): InvoiceStrategyInterface
    {
        if (!isset($this->strategies[$invoice->invoiceable_type])) {
            throw new \InvalidArgumentException("No strategy registered for type: {$invoice->invoiceable_type}");
        }

        return $this->strategies[$invoice->invoiceable_type];
    }

    // ==================== Query Methods ====================

    /**
     * Get paginated invoices with filters.
     */
    public function getInvoices(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Invoice::query()
            ->with(['invoiceable', 'subscriptionPlan', 'creator'])
            ->latest('issue_date');

        // Filter by tenant
        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        // Filter by invoiceable type
        if (isset($filters['type'])) {
            $query->forType($filters['type']);
        }

        // Filter by status
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by payment method
        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        // Filter by date range
        if (isset($filters['from_date'])) {
            $query->where('issue_date', '>=', $filters['from_date']);
        }
        if (isset($filters['to_date'])) {
            $query->where('issue_date', '<=', $filters['to_date']);
        }

        // Filter by year
        if (isset($filters['year'])) {
            $query->fromYear($filters['year']);
        }

        // Search
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('billing_name', 'like', "%{$search}%")
                    ->orWhere('billing_email', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Get invoice statistics.
     */
    public function getStatistics(?string $tenantId = null, ?string $type = null): array
    {
        $query = Invoice::query();

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($type) {
            $query->forType($type);
        }

        $invoices = $query->get();

        return [
            'total' => $invoices->count(),
            'draft' => $invoices->where('status', Invoice::STATUS_DRAFT)->count(),
            'sent' => $invoices->where('status', Invoice::STATUS_SENT)->count(),
            'paid' => $invoices->where('status', Invoice::STATUS_PAID)->count(),
            'overdue' => $invoices->where('status', Invoice::STATUS_OVERDUE)->count(),
            'cancelled' => $invoices->where('status', Invoice::STATUS_CANCELLED)->count(),
            'pending_amount' => (float) $invoices->whereIn('status', [Invoice::STATUS_SENT, Invoice::STATUS_OVERDUE])->sum('gross_amount'),
            'overdue_amount' => (float) $invoices->where('status', Invoice::STATUS_OVERDUE)->sum('gross_amount'),
            'paid_amount' => (float) $invoices->where('status', Invoice::STATUS_PAID)->sum('gross_amount'),
        ];
    }

    // ==================== CRUD Methods ====================

    /**
     * Create a new invoice for an invoiceable.
     */
    public function create(Invoiceable $invoiceable, array $data, ?User $creator = null): Invoice
    {
        $strategy = $this->getStrategy($invoiceable);

        // Validate
        $strategy->validateCreation($invoiceable, $data);

        // Calculate amounts
        $taxRate = $data['tax_rate'] ?? config('invoices.default_tax_rate', 19.00);
        $isSmallBusiness = $data['is_small_business'] ?? false;
        $amounts = Invoice::calculateAmounts($data['net_amount'], $taxRate, $isSmallBusiness);

        // Create invoice
        $invoice = DB::transaction(function () use ($invoiceable, $data, $amounts, $strategy, $creator, $isSmallBusiness) {
            $invoice = Invoice::create([
                'tenant_id' => $invoiceable->getInvoiceableTenantId(),
                'invoiceable_type' => get_class($invoiceable),
                'invoiceable_id' => $invoiceable->getKey(),
                'subscription_plan_type' => $invoiceable->getSubscriptionPlan() ? get_class($invoiceable->getSubscriptionPlan()) : null,
                'subscription_plan_id' => $invoiceable->getSubscriptionPlan()?->getKey(),
                'payment_method' => $data['payment_method'] ?? $invoiceable->getPreferredPaymentMethod(),
                'invoice_number' => Invoice::generateNumber($invoiceable->getInvoiceableTenantId(), get_class($invoiceable)),
                'status' => Invoice::STATUS_DRAFT,
                'net_amount' => $amounts['net_amount'],
                'tax_rate' => $amounts['tax_rate'],
                'tax_amount' => $amounts['tax_amount'],
                'gross_amount' => $amounts['gross_amount'],
                'is_small_business' => $isSmallBusiness,
                'currency' => $data['currency'] ?? 'EUR',
                'billing_period' => $data['billing_period'] ?? null,
                'description' => $data['description'] ?? null,
                'line_items' => $data['line_items'] ?? $strategy->createSubscriptionLineItems($invoiceable, $data['billing_period'] ?? 'monthly'),
                'billing_name' => $invoiceable->getBillingName(),
                'billing_email' => $invoiceable->getBillingEmail(),
                'billing_address' => $invoiceable->getBillingAddress(),
                'vat_number' => $invoiceable->getVatNumber(),
                'issue_date' => $data['issue_date'] ?? now()->toDateString(),
                'due_date' => $data['due_date'] ?? now()->addDays(config('invoices.payment_terms_days', 14))->toDateString(),
                'created_by' => $creator?->id ?? auth()->id(),
            ]);

            return $invoice;
        });

        // After create hook
        $strategy->afterCreate($invoice);

        Log::info('Invoice created', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'invoiceable_type' => $invoice->invoiceable_type,
            'invoiceable_id' => $invoice->invoiceable_id,
            'amount' => $invoice->gross_amount,
        ]);

        return $invoice;
    }

    /**
     * Create an invoice for a subscription period.
     */
    public function createForSubscription(Invoiceable $invoiceable, string $billingPeriod, ?User $creator = null): Invoice
    {
        $strategy = $this->getStrategy($invoiceable);
        $lineItems = $strategy->createSubscriptionLineItems($invoiceable, $billingPeriod);

        if (empty($lineItems)) {
            throw new \RuntimeException('Keine Rechnungsposten für diese Subscription gefunden.');
        }

        $netAmount = array_sum(array_column($lineItems, 'total'));

        return $this->create($invoiceable, [
            'net_amount' => $netAmount,
            'billing_period' => $billingPeriod,
            'line_items' => $lineItems,
            'description' => "Subscription - {$billingPeriod}",
        ], $creator);
    }

    /**
     * Update an invoice (only drafts can be updated).
     */
    public function update(Invoice $invoice, array $data, ?User $updater = null): Invoice
    {
        if (!$invoice->canBeEdited()) {
            throw new \RuntimeException('Nur Entwürfe können bearbeitet werden.');
        }

        // Recalculate amounts if net_amount changed
        if (isset($data['net_amount'])) {
            $taxRate = $data['tax_rate'] ?? $invoice->tax_rate;
            $isSmallBusiness = $data['is_small_business'] ?? $invoice->is_small_business;
            $amounts = Invoice::calculateAmounts($data['net_amount'], $taxRate, $isSmallBusiness);
            $data = array_merge($data, $amounts);
        }

        $data['updated_by'] = $updater?->id ?? auth()->id();

        $invoice->update($data);

        // Regenerate PDF if exists
        if ($invoice->pdf_path) {
            $this->pdfService->regenerate($invoice);
        }

        return $invoice->fresh();
    }

    /**
     * Delete an invoice (only drafts can be deleted).
     */
    public function delete(Invoice $invoice): bool
    {
        if (!$invoice->canBeEdited()) {
            throw new \RuntimeException('Nur Entwürfe können gelöscht werden.');
        }

        // Delete PDF if exists
        if ($invoice->pdf_path) {
            $this->pdfService->delete($invoice);
        }

        return $invoice->delete();
    }

    // ==================== Status Methods ====================

    /**
     * Mark an invoice as sent.
     */
    public function markAsSent(Invoice $invoice, bool $sendEmail = true): Invoice
    {
        if (!$invoice->canBeSent()) {
            throw new \RuntimeException('Diese Rechnung kann nicht versendet werden.');
        }

        $invoice->update([
            'status' => Invoice::STATUS_SENT,
            'updated_by' => auth()->id(),
        ]);

        // Generate PDF if not exists
        if (!$invoice->pdf_path) {
            $this->pdfService->generate($invoice);
        }

        // Get strategy
        $strategy = $this->getStrategyForInvoice($invoice);
        $strategy->afterSend($invoice);

        // Send email
        if ($sendEmail) {
            $recipients = $strategy->getNotificationRecipients($invoice);
            $this->notificationService->sendInvoice($invoice, $recipients);
        }

        Log::info('Invoice sent', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
        ]);

        return $invoice->fresh();
    }

    /**
     * Mark an invoice as paid.
     */
    public function markAsPaid(Invoice $invoice, ?string $paymentReference = null, ?string $notes = null): Invoice
    {
        if (!$invoice->canBeMarkedAsPaid()) {
            throw new \RuntimeException('Diese Rechnung kann nicht als bezahlt markiert werden.');
        }

        $invoice->update([
            'status' => Invoice::STATUS_PAID,
            'paid_at' => now(),
            'payment_reference' => $paymentReference ?? $invoice->payment_reference,
            'payment_notes' => $notes,
            'updated_by' => auth()->id(),
        ]);

        // Get strategy and call afterPayment hook
        $strategy = $this->getStrategyForInvoice($invoice);
        $strategy->afterPayment($invoice);

        // Send confirmation email
        $recipients = $strategy->getNotificationRecipients($invoice);
        $this->notificationService->sendPaymentConfirmation($invoice, $recipients);

        Log::info('Invoice paid', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'payment_reference' => $paymentReference,
        ]);

        return $invoice->fresh();
    }

    /**
     * Mark an invoice as overdue.
     */
    public function markAsOverdue(Invoice $invoice): Invoice
    {
        if ($invoice->status !== Invoice::STATUS_SENT) {
            return $invoice;
        }

        $invoice->update([
            'status' => Invoice::STATUS_OVERDUE,
            'updated_by' => auth()->id(),
        ]);

        // Get strategy and call afterOverdue hook
        $strategy = $this->getStrategyForInvoice($invoice);
        $strategy->afterOverdue($invoice);

        Log::info('Invoice overdue', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
        ]);

        return $invoice->fresh();
    }

    /**
     * Cancel an invoice.
     */
    public function cancel(Invoice $invoice, ?string $reason = null): Invoice
    {
        if (!$invoice->canBeCancelled()) {
            throw new \RuntimeException('Diese Rechnung kann nicht storniert werden.');
        }

        $invoice->update([
            'status' => Invoice::STATUS_CANCELLED,
            'payment_notes' => $reason ? "Stornierungsgrund: {$reason}" : $invoice->payment_notes,
            'updated_by' => auth()->id(),
        ]);

        // Get strategy and call afterCancel hook
        $strategy = $this->getStrategyForInvoice($invoice);
        $strategy->afterCancel($invoice);

        // Send cancellation email
        $recipients = $strategy->getNotificationRecipients($invoice);
        $this->notificationService->sendCancellation($invoice, $recipients);

        Log::info('Invoice cancelled', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'reason' => $reason,
        ]);

        return $invoice->fresh();
    }

    // ==================== Reminder Methods ====================

    /**
     * Send a payment reminder.
     */
    public function sendReminder(Invoice $invoice): Invoice
    {
        if (!$invoice->canSendReminder()) {
            throw new \RuntimeException('Für diese Rechnung kann keine Mahnung versendet werden.');
        }

        $maxReminders = config('invoices.reminders.max_reminders', 3);
        if ($invoice->reminder_count >= $maxReminders) {
            throw new \RuntimeException("Maximale Anzahl von Mahnungen ({$maxReminders}) erreicht.");
        }

        $invoice->update([
            'reminder_count' => $invoice->reminder_count + 1,
            'last_reminder_sent_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        // Send reminder email
        $strategy = $this->getStrategyForInvoice($invoice);
        $recipients = $strategy->getNotificationRecipients($invoice);
        $this->notificationService->sendReminder($invoice, $recipients);

        Log::info('Invoice reminder sent', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'reminder_count' => $invoice->reminder_count,
        ]);

        return $invoice->fresh();
    }

    // ==================== Batch Processing ====================

    /**
     * Process overdue invoices.
     */
    public function processOverdueInvoices(): Collection
    {
        $processed = collect();

        // Find sent invoices that are past due date
        $overdueInvoices = Invoice::sent()
            ->where('due_date', '<', now())
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $this->markAsOverdue($invoice);
            $processed->push($invoice);
        }

        // Send reminders for overdue invoices
        if (config('invoices.reminders.enabled', true)) {
            $intervals = config('invoices.reminders.intervals', [7, 14, 21]);

            foreach ($intervals as $days) {
                $dueReminders = Invoice::overdue()
                    ->where('due_date', '<=', now()->subDays($days))
                    ->where(function (Builder $query) use ($days, $intervals) {
                        $previousInterval = collect($intervals)->filter(fn ($i) => $i < $days)->max() ?? 0;
                        $query->whereNull('last_reminder_sent_at')
                            ->orWhere('last_reminder_sent_at', '<=', now()->subDays($days - $previousInterval));
                    })
                    ->where('reminder_count', '<', config('invoices.reminders.max_reminders', 3))
                    ->get();

                foreach ($dueReminders as $invoice) {
                    try {
                        $this->sendReminder($invoice);
                    } catch (\Exception $e) {
                        Log::error('Failed to send reminder', [
                            'invoice_id' => $invoice->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        return $processed;
    }

    // ==================== Invoice Request Methods ====================

    /**
     * Approve an invoice request.
     */
    public function approveRequest(InvoiceRequest $request, ?User $processor = null): Invoice
    {
        if (!$request->canBeProcessed()) {
            throw new \RuntimeException('Diese Anfrage kann nicht mehr bearbeitet werden.');
        }

        return DB::transaction(function () use ($request, $processor) {
            // Update request status
            $request->update([
                'status' => InvoiceRequest::STATUS_APPROVED,
                'processed_by' => $processor?->id ?? auth()->id(),
                'processed_at' => now(),
            ]);

            // Update invoiceable to use invoice payment
            $invoiceable = $request->requestable;
            if ($invoiceable instanceof Club) {
                $invoiceable->update(['payment_method_type' => 'invoice']);
            } elseif ($invoiceable instanceof Tenant) {
                $invoiceable->update(['pays_via_invoice' => true]);
            }

            // Create first invoice
            $invoice = $this->createForSubscription($invoiceable, 'monthly', $processor);

            Log::info('Invoice request approved', [
                'request_id' => $request->id,
                'invoice_id' => $invoice->id,
            ]);

            return $invoice;
        });
    }

    /**
     * Reject an invoice request.
     */
    public function rejectRequest(InvoiceRequest $request, string $reason, ?User $processor = null): InvoiceRequest
    {
        if (!$request->canBeProcessed()) {
            throw new \RuntimeException('Diese Anfrage kann nicht mehr bearbeitet werden.');
        }

        $request->update([
            'status' => InvoiceRequest::STATUS_REJECTED,
            'processed_by' => $processor?->id ?? auth()->id(),
            'processed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        Log::info('Invoice request rejected', [
            'request_id' => $request->id,
            'reason' => $reason,
        ]);

        return $request->fresh();
    }
}
