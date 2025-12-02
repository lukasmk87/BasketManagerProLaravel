<?php

namespace App\Services\Invoice;

use App\Models\Club;
use App\Models\ClubInvoice;
use App\Models\ClubInvoiceRequest;
use App\Models\ClubSubscriptionEvent;
use App\Models\ClubSubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClubInvoiceService
{
    public function __construct(
        protected ClubInvoicePdfService $pdfService,
        protected ClubInvoiceNotificationService $notificationService
    ) {}

    /**
     * Get paginated list of invoices with filters.
     */
    public function getInvoices(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ClubInvoice::with(['club', 'subscriptionPlan', 'creator'])
            ->orderByDesc('issue_date');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (!empty($filters['club_id'])) {
            $query->where('club_id', $filters['club_id']);
        }

        if (!empty($filters['start_date'])) {
            $query->where('issue_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('issue_date', '<=', $filters['end_date']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('billing_name', 'like', "%{$search}%")
                    ->orWhereHas('club', function ($clubQuery) use ($search) {
                        $clubQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Get invoice statistics.
     */
    public function getStatistics(?string $tenantId = null): array
    {
        $query = ClubInvoice::query();

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $total = $query->count();
        $draft = (clone $query)->draft()->count();
        $sent = (clone $query)->sent()->count();
        $paid = (clone $query)->paid()->count();
        $overdue = (clone $query)->overdue()->count();

        $thisMonth = (clone $query)
            ->whereMonth('issue_date', now()->month)
            ->whereYear('issue_date', now()->year);

        $paidThisMonth = (clone $thisMonth)->paid()->sum('gross_amount');
        $pendingAmount = (clone $query)->pending()->sum('gross_amount');
        $overdueAmount = (clone $query)->overdue()->sum('gross_amount');

        return [
            'total' => $total,
            'draft' => $draft,
            'sent' => $sent,
            'paid' => $paid,
            'overdue' => $overdue,
            'paid_this_month' => $paidThisMonth,
            'pending_amount' => $pendingAmount,
            'overdue_amount' => $overdueAmount,
        ];
    }

    /**
     * Create a new invoice.
     */
    public function create(Club $club, array $data, ?User $creator = null): ClubInvoice
    {
        return DB::transaction(function () use ($club, $data, $creator) {
            $tenant = $club->tenant;

            // Calculate amounts
            $amounts = ClubInvoice::calculateAmounts(
                $data['net_amount'],
                $data['tax_rate'] ?? config('invoices.default_tax_rate', 19.00)
            );

            // Generate invoice number
            $invoiceNumber = ClubInvoice::generateNumber($tenant);

            // Set dates
            $issueDate = isset($data['issue_date'])
                ? Carbon::parse($data['issue_date'])
                : now();

            $dueDate = isset($data['due_date'])
                ? Carbon::parse($data['due_date'])
                : $issueDate->copy()->addDays(config('invoices.payment_terms_days', 14));

            $invoice = ClubInvoice::create([
                'tenant_id' => $tenant->id,
                'club_id' => $club->id,
                'club_subscription_plan_id' => $data['club_subscription_plan_id'] ?? null,
                'invoice_number' => $invoiceNumber,
                'status' => ClubInvoice::STATUS_DRAFT,
                'net_amount' => $amounts['net_amount'],
                'tax_rate' => $amounts['tax_rate'],
                'tax_amount' => $amounts['tax_amount'],
                'gross_amount' => $amounts['gross_amount'],
                'currency' => $data['currency'] ?? 'EUR',
                'billing_period' => $data['billing_period'] ?? null,
                'description' => $data['description'] ?? null,
                'line_items' => $data['line_items'] ?? null,
                'billing_name' => $data['billing_name'] ?? $club->invoice_billing_name ?? $club->name,
                'billing_email' => $data['billing_email'] ?? $club->billing_email ?? $club->email,
                'billing_address' => $data['billing_address'] ?? $club->billing_address,
                'vat_number' => $data['vat_number'] ?? $club->invoice_vat_number,
                'issue_date' => $issueDate,
                'due_date' => $dueDate,
                'created_by' => $creator?->id,
                'updated_by' => $creator?->id,
            ]);

            // Generate PDF
            $this->pdfService->generate($invoice);

            // Log event
            $this->logInvoiceEvent($invoice, ClubSubscriptionEvent::TYPE_INVOICE_CREATED ?? 'invoice_created');

            return $invoice->fresh(['club', 'subscriptionPlan', 'creator']);
        });
    }

    /**
     * Create an invoice for a subscription.
     */
    public function createForSubscription(
        Club $club,
        ClubSubscriptionPlan $plan,
        string $billingPeriod,
        string $billingInterval = 'monthly'
    ): ClubInvoice {
        // Calculate price based on billing interval
        $price = $billingInterval === 'yearly'
            ? $plan->price * 12 * 0.9 // 10% discount for yearly
            : $plan->price;

        $lineItems = [
            [
                'description' => "Subscription: {$plan->name} ({$billingInterval})",
                'quantity' => 1,
                'unit_price' => $price,
                'total' => $price,
            ],
        ];

        return $this->create($club, [
            'club_subscription_plan_id' => $plan->id,
            'net_amount' => $price,
            'billing_period' => $billingPeriod,
            'description' => "Subscription für {$plan->name}",
            'line_items' => $lineItems,
        ]);
    }

    /**
     * Update an invoice (only drafts can be updated).
     */
    public function update(ClubInvoice $invoice, array $data, ?User $updater = null): ClubInvoice
    {
        if (!$invoice->canBeEdited()) {
            throw new \Exception('Diese Rechnung kann nicht mehr bearbeitet werden.');
        }

        return DB::transaction(function () use ($invoice, $data, $updater) {
            // Recalculate amounts if net_amount changed
            if (isset($data['net_amount'])) {
                $amounts = ClubInvoice::calculateAmounts(
                    $data['net_amount'],
                    $data['tax_rate'] ?? $invoice->tax_rate
                );
                $data = array_merge($data, $amounts);
            }

            $data['updated_by'] = $updater?->id;

            $invoice->update($data);

            // Regenerate PDF if content changed
            $this->pdfService->generate($invoice);

            return $invoice->fresh(['club', 'subscriptionPlan', 'creator']);
        });
    }

    /**
     * Mark invoice as sent.
     */
    public function markAsSent(ClubInvoice $invoice, bool $sendEmail = true): ClubInvoice
    {
        if (!$invoice->canBeSent()) {
            throw new \Exception('Diese Rechnung kann nicht versendet werden.');
        }

        return DB::transaction(function () use ($invoice, $sendEmail) {
            $invoice->update([
                'status' => ClubInvoice::STATUS_SENT,
            ]);

            // Update club subscription status if needed
            $club = $invoice->club;
            if ($club->paysViaInvoice() && $invoice->club_subscription_plan_id) {
                $club->update([
                    'subscription_status' => 'pending_payment',
                ]);
            }

            // Send email with invoice
            if ($sendEmail) {
                $this->notificationService->sendInvoice($invoice);
            }

            // Log event
            $this->logInvoiceEvent($invoice, 'invoice_sent');

            return $invoice->fresh();
        });
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(ClubInvoice $invoice, array $paymentData = [], ?User $updater = null): ClubInvoice
    {
        if (!$invoice->canBeMarkedAsPaid()) {
            throw new \Exception('Diese Rechnung kann nicht als bezahlt markiert werden.');
        }

        return DB::transaction(function () use ($invoice, $paymentData, $updater) {
            $invoice->update([
                'status' => ClubInvoice::STATUS_PAID,
                'paid_at' => $paymentData['paid_at'] ?? now(),
                'payment_reference' => $paymentData['payment_reference'] ?? null,
                'payment_notes' => $paymentData['payment_notes'] ?? null,
                'updated_by' => $updater?->id,
            ]);

            // Activate subscription if this is a subscription invoice
            if ($invoice->club_subscription_plan_id) {
                $this->activateSubscriptionOnPayment($invoice);
            }

            // Send payment confirmation
            $this->notificationService->sendPaymentConfirmation($invoice);

            // Log event
            $this->logInvoiceEvent($invoice, 'invoice_paid', [
                'amount' => $invoice->gross_amount,
            ]);

            return $invoice->fresh();
        });
    }

    /**
     * Mark invoice as overdue.
     */
    public function markAsOverdue(ClubInvoice $invoice): ClubInvoice
    {
        if ($invoice->status !== ClubInvoice::STATUS_SENT) {
            return $invoice;
        }

        $invoice->update([
            'status' => ClubInvoice::STATUS_OVERDUE,
        ]);

        // Log event
        $this->logInvoiceEvent($invoice, 'invoice_overdue');

        return $invoice->fresh();
    }

    /**
     * Cancel an invoice.
     */
    public function cancel(ClubInvoice $invoice, string $reason, ?User $updater = null): ClubInvoice
    {
        if (!$invoice->canBeCancelled()) {
            throw new \Exception('Diese Rechnung kann nicht storniert werden.');
        }

        return DB::transaction(function () use ($invoice, $reason, $updater) {
            $invoice->update([
                'status' => ClubInvoice::STATUS_CANCELLED,
                'payment_notes' => "Storniert: {$reason}",
                'updated_by' => $updater?->id,
            ]);

            // Send cancellation notification
            $this->notificationService->sendCancellation($invoice);

            // Log event
            $this->logInvoiceEvent($invoice, 'invoice_cancelled', [
                'reason' => $reason,
            ]);

            return $invoice->fresh();
        });
    }

    /**
     * Send payment reminder.
     */
    public function sendReminder(ClubInvoice $invoice): ClubInvoice
    {
        if (!$invoice->canSendReminder()) {
            throw new \Exception('Für diese Rechnung kann keine Mahnung gesendet werden.');
        }

        $reminderLevel = $invoice->reminder_count + 1;

        $invoice->update([
            'reminder_count' => $reminderLevel,
            'last_reminder_sent_at' => now(),
        ]);

        $this->notificationService->sendReminder($invoice, $reminderLevel);

        // Log event
        $this->logInvoiceEvent($invoice, 'invoice_reminder_sent', [
            'reminder_level' => $reminderLevel,
        ]);

        return $invoice->fresh();
    }

    /**
     * Activate subscription after invoice payment.
     */
    public function activateSubscriptionOnPayment(ClubInvoice $invoice): void
    {
        $club = $invoice->club;
        $plan = $invoice->subscriptionPlan;

        if (!$plan) {
            return;
        }

        // Calculate billing period based on plan interval
        $billingInterval = $this->determineBillingInterval($invoice);
        $periodEnd = $billingInterval === 'yearly'
            ? now()->addYear()
            : now()->addMonth();

        $club->update([
            'club_subscription_plan_id' => $plan->id,
            'subscription_status' => 'active',
            'subscription_started_at' => now(),
            'subscription_current_period_start' => now(),
            'subscription_current_period_end' => $periodEnd,
            'payment_method_type' => 'invoice',
        ]);

        // Create subscription event
        ClubSubscriptionEvent::create([
            'tenant_id' => $club->tenant_id,
            'club_id' => $club->id,
            'event_type' => ClubSubscriptionEvent::TYPE_SUBSCRIPTION_CREATED,
            'new_plan_id' => $plan->id,
            'mrr_change' => $plan->price,
            'metadata' => [
                'payment_method' => 'invoice',
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
            ],
            'event_date' => now(),
        ]);

        Log::info("Subscription activated via invoice payment", [
            'club_id' => $club->id,
            'invoice_id' => $invoice->id,
            'plan_id' => $plan->id,
        ]);
    }

    /**
     * Process overdue invoices (scheduled task).
     */
    public function processOverdueInvoices(): array
    {
        $results = [
            'marked_overdue' => 0,
            'reminders_sent' => 0,
            'subscriptions_suspended' => 0,
        ];

        // Mark sent invoices as overdue
        $overdueInvoices = ClubInvoice::sent()
            ->where('due_date', '<', now())
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $this->markAsOverdue($invoice);
            $results['marked_overdue']++;
        }

        // Send reminders for overdue invoices
        if (config('invoices.reminders.enabled', true)) {
            $results['reminders_sent'] = $this->processReminders();
        }

        // Suspend subscriptions for long-overdue invoices
        if (config('invoices.suspension.enabled', true)) {
            $results['subscriptions_suspended'] = $this->processSuspensions();
        }

        return $results;
    }

    /**
     * Process automatic reminders.
     */
    protected function processReminders(): int
    {
        $intervals = config('invoices.reminders.intervals', [7, 14, 21]);
        $maxReminders = config('invoices.reminders.max_reminders', 3);
        $sent = 0;

        $overdueInvoices = ClubInvoice::overdue()
            ->where('reminder_count', '<', $maxReminders)
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $daysOverdue = $invoice->daysOverdue();
            $nextReminderDay = $intervals[$invoice->reminder_count] ?? null;

            if ($nextReminderDay && $daysOverdue >= $nextReminderDay) {
                try {
                    $this->sendReminder($invoice);
                    $sent++;
                } catch (\Exception $e) {
                    Log::error("Failed to send reminder for invoice {$invoice->id}: {$e->getMessage()}");
                }
            }
        }

        return $sent;
    }

    /**
     * Process subscription suspensions.
     */
    protected function processSuspensions(): int
    {
        $suspendAfterDays = config('invoices.suspension.days_after_due', 30);
        $suspended = 0;

        $longOverdueInvoices = ClubInvoice::overdue()
            ->whereDate('due_date', '<=', now()->subDays($suspendAfterDays))
            ->whereHas('club', function ($query) {
                $query->where('subscription_status', '!=', 'suspended');
            })
            ->get();

        foreach ($longOverdueInvoices as $invoice) {
            $club = $invoice->club;

            $club->update([
                'subscription_status' => 'suspended',
            ]);

            // Send suspension warning
            $this->notificationService->sendSuspensionWarning($invoice);

            // Log event
            ClubSubscriptionEvent::create([
                'tenant_id' => $club->tenant_id,
                'club_id' => $club->id,
                'event_type' => ClubSubscriptionEvent::TYPE_SUBSCRIPTION_CANCELED,
                'cancellation_reason' => ClubSubscriptionEvent::REASON_PAYMENT_FAILED,
                'cancellation_feedback' => "Subscription suspended due to unpaid invoice {$invoice->invoice_number}",
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'days_overdue' => $invoice->daysOverdue(),
                ],
                'event_date' => now(),
            ]);

            $suspended++;
        }

        return $suspended;
    }

    /**
     * Approve an invoice request.
     */
    public function approveRequest(ClubInvoiceRequest $request, ?User $admin = null): ClubInvoice
    {
        if (!$request->canBeProcessed()) {
            throw new \Exception('Diese Anfrage kann nicht mehr bearbeitet werden.');
        }

        return DB::transaction(function () use ($request, $admin) {
            $club = $request->club;
            $plan = $request->subscriptionPlan;

            // Create billing period string
            $billingPeriod = $this->createBillingPeriodString($request->billing_interval);

            // Create invoice
            $invoice = $this->createForSubscription(
                $club,
                $plan,
                $billingPeriod,
                $request->billing_interval
            );

            // Update invoice with request data
            $invoice->update([
                'billing_name' => $request->billing_name,
                'billing_email' => $request->billing_email,
                'billing_address' => $request->billing_address,
                'vat_number' => $request->vat_number,
            ]);

            // Regenerate PDF
            $this->pdfService->generate($invoice);

            // Update request
            $request->update([
                'status' => ClubInvoiceRequest::STATUS_APPROVED,
                'processed_by' => $admin?->id,
                'processed_at' => now(),
                'invoice_id' => $invoice->id,
            ]);

            // Update club to invoice payment
            $club->update([
                'payment_method_type' => 'invoice',
                'invoice_billing_name' => $request->billing_name,
                'invoice_vat_number' => $request->vat_number,
                'billing_email' => $request->billing_email,
                'billing_address' => $request->billing_address,
            ]);

            return $invoice;
        });
    }

    /**
     * Reject an invoice request.
     */
    public function rejectRequest(ClubInvoiceRequest $request, string $reason, ?User $admin = null): ClubInvoiceRequest
    {
        if (!$request->canBeProcessed()) {
            throw new \Exception('Diese Anfrage kann nicht mehr bearbeitet werden.');
        }

        $request->update([
            'status' => ClubInvoiceRequest::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'processed_by' => $admin?->id,
            'processed_at' => now(),
        ]);

        // TODO: Send rejection notification to club admin

        return $request->fresh();
    }

    /**
     * Delete an invoice (soft delete, only drafts).
     */
    public function delete(ClubInvoice $invoice): bool
    {
        if (!$invoice->canBeEdited()) {
            throw new \Exception('Nur Entwürfe können gelöscht werden.');
        }

        return $invoice->delete();
    }

    /**
     * Log an invoice event.
     */
    protected function logInvoiceEvent(ClubInvoice $invoice, string $eventType, array $metadata = []): void
    {
        ClubSubscriptionEvent::create([
            'tenant_id' => $invoice->tenant_id,
            'club_id' => $invoice->club_id,
            'event_type' => $eventType,
            'metadata' => array_merge([
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'amount' => $invoice->gross_amount,
            ], $metadata),
            'event_date' => now(),
        ]);
    }

    /**
     * Determine billing interval from invoice.
     */
    protected function determineBillingInterval(ClubInvoice $invoice): string
    {
        // Try to determine from line items or description
        if ($invoice->line_items) {
            $description = $invoice->line_items[0]['description'] ?? '';
            if (str_contains(strtolower($description), 'yearly') || str_contains(strtolower($description), 'jährlich')) {
                return 'yearly';
            }
        }

        return 'monthly';
    }

    /**
     * Create billing period string.
     */
    protected function createBillingPeriodString(string $interval): string
    {
        $start = now();
        $end = $interval === 'yearly' ? now()->addYear() : now()->addMonth();

        return $start->format('d.m.Y') . ' - ' . $end->format('d.m.Y');
    }
}
