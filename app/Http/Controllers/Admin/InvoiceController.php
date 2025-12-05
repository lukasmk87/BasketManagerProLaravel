<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Services\Invoice\InvoicePdfService;
use App\Services\Invoice\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected InvoicePdfService $pdfService,
    ) {}

    /**
     * Display a listing of invoices.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'type', 'payment_method', 'search', 'year']);

        // Add tenant_id filter for non-super-admins
        if (!auth()->user()->isSuperAdmin()) {
            $filters['tenant_id'] = auth()->user()->tenant_id;
        }

        $invoices = $this->invoiceService->getInvoices($filters);
        $statistics = $this->invoiceService->getStatistics(
            $filters['tenant_id'] ?? null,
            $filters['type'] ?? null
        );

        return Inertia::render('Admin/Invoices/Index', [
            'invoices' => $invoices,
            'statistics' => $statistics,
            'filters' => $filters,
            'statuses' => Invoice::getStatuses(),
            'paymentMethods' => Invoice::getPaymentMethods(),
            'invoiceableTypes' => Invoice::getInvoiceableTypes(),
        ]);
    }

    /**
     * Show the form for creating a new invoice.
     */
    public function create(Request $request): Response
    {
        $type = $request->get('type', 'club');
        $invoiceableId = $request->get('invoiceable_id');

        $invoiceable = null;
        $invoiceables = [];

        if ($type === 'club') {
            if ($invoiceableId) {
                $invoiceable = Club::find($invoiceableId);
            }
            $invoiceables = Club::query()
                ->when(!auth()->user()->isSuperAdmin(), fn ($q) => $q->where('tenant_id', auth()->user()->tenant_id))
                ->active()
                ->get(['id', 'name', 'email']);
        } elseif ($type === 'tenant') {
            if ($invoiceableId) {
                $invoiceable = Tenant::find($invoiceableId);
            }
            if (auth()->user()->isSuperAdmin()) {
                $invoiceables = Tenant::where('is_active', true)->get(['id', 'name', 'billing_email']);
            }
        }

        return Inertia::render('Admin/Invoices/Create', [
            'type' => $type,
            'invoiceable' => $invoiceable,
            'invoiceables' => $invoiceables,
            'paymentMethods' => Invoice::getPaymentMethods(),
            'defaultTaxRate' => config('invoices.default_tax_rate', 19.00),
            'paymentTermsDays' => config('invoices.payment_terms_days', 14),
        ]);
    }

    /**
     * Store a newly created invoice.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'invoiceable_type' => 'required|in:club,tenant',
            'invoiceable_id' => 'required',
            'net_amount' => 'required|numeric|min:0.01',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'is_small_business' => 'boolean',
            'payment_method' => 'required|in:bank_transfer,stripe',
            'billing_period' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'line_items' => 'nullable|array',
            'line_items.*.description' => 'required|string|max:255',
            'line_items.*.quantity' => 'required|numeric|min:1',
            'line_items.*.unit_price' => 'required|numeric|min:0',
            'issue_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
        ]);

        // Get invoiceable
        $invoiceable = $validated['invoiceable_type'] === 'club'
            ? Club::findOrFail($validated['invoiceable_id'])
            : Tenant::findOrFail($validated['invoiceable_id']);

        // Prepare line items
        if (isset($validated['line_items'])) {
            foreach ($validated['line_items'] as &$item) {
                $item['total'] = $item['quantity'] * $item['unit_price'];
            }
        }

        $invoice = $this->invoiceService->create($invoiceable, $validated, auth()->user());

        return redirect()
            ->route('admin.invoices.show', $invoice)
            ->with('success', 'Rechnung erfolgreich erstellt.');
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice): Response
    {
        $invoice->load(['invoiceable', 'subscriptionPlan', 'creator', 'updater']);

        return Inertia::render('Admin/Invoices/Show', [
            'invoice' => $invoice,
            'formattedAmounts' => $invoice->formatted_amounts,
            'actions' => [
                'canEdit' => $invoice->canBeEdited(),
                'canSend' => $invoice->canBeSent(),
                'canMarkAsPaid' => $invoice->canBeMarkedAsPaid(),
                'canCancel' => $invoice->canBeCancelled(),
                'canSendReminder' => $invoice->canSendReminder(),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified invoice.
     */
    public function edit(Invoice $invoice): Response
    {
        if (!$invoice->canBeEdited()) {
            abort(403, 'Nur Entwürfe können bearbeitet werden.');
        }

        return Inertia::render('Admin/Invoices/Edit', [
            'invoice' => $invoice,
            'paymentMethods' => Invoice::getPaymentMethods(),
            'defaultTaxRate' => config('invoices.default_tax_rate', 19.00),
        ]);
    }

    /**
     * Update the specified invoice.
     */
    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'net_amount' => 'required|numeric|min:0.01',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'is_small_business' => 'boolean',
            'payment_method' => 'required|in:bank_transfer,stripe',
            'billing_period' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'line_items' => 'nullable|array',
            'issue_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
        ]);

        $this->invoiceService->update($invoice, $validated, auth()->user());

        return redirect()
            ->route('admin.invoices.show', $invoice)
            ->with('success', 'Rechnung erfolgreich aktualisiert.');
    }

    /**
     * Remove the specified invoice.
     */
    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->invoiceService->delete($invoice);

        return redirect()
            ->route('admin.invoices.index')
            ->with('success', 'Rechnung erfolgreich gelöscht.');
    }

    /**
     * Mark invoice as sent.
     */
    public function send(Request $request, Invoice $invoice): RedirectResponse
    {
        $sendEmail = $request->boolean('send_email', true);

        $this->invoiceService->markAsSent($invoice, $sendEmail);

        return redirect()
            ->back()
            ->with('success', 'Rechnung wurde versendet.');
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'payment_reference' => 'nullable|string|max:255',
            'payment_notes' => 'nullable|string|max:1000',
        ]);

        $this->invoiceService->markAsPaid(
            $invoice,
            $validated['payment_reference'] ?? null,
            $validated['payment_notes'] ?? null
        );

        return redirect()
            ->back()
            ->with('success', 'Rechnung wurde als bezahlt markiert.');
    }

    /**
     * Cancel invoice.
     */
    public function cancel(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $this->invoiceService->cancel($invoice, $validated['reason'] ?? null);

        return redirect()
            ->back()
            ->with('success', 'Rechnung wurde storniert.');
    }

    /**
     * Send payment reminder.
     */
    public function sendReminder(Invoice $invoice): RedirectResponse
    {
        $this->invoiceService->sendReminder($invoice);

        return redirect()
            ->back()
            ->with('success', 'Zahlungserinnerung wurde versendet.');
    }

    /**
     * Download invoice PDF.
     */
    public function downloadPdf(Invoice $invoice)
    {
        return $this->pdfService->download($invoice);
    }

    /**
     * Preview invoice PDF.
     */
    public function previewPdf(Invoice $invoice)
    {
        return $this->pdfService->stream($invoice);
    }
}
