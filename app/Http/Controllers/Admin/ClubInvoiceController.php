<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateClubInvoiceRequest;
use App\Http\Requests\Admin\MarkInvoicePaidRequest;
use App\Http\Requests\Admin\UpdateClubInvoiceRequest;
use App\Models\Club;
use App\Models\ClubInvoice;
use App\Models\ClubSubscriptionPlan;
use App\Services\Invoice\ClubInvoicePdfService;
use App\Services\Invoice\ClubInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClubInvoiceController extends Controller
{
    public function __construct(
        protected ClubInvoiceService $invoiceService,
        protected ClubInvoicePdfService $pdfService
    ) {}

    /**
     * Display a listing of invoices.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'tenant_id', 'club_id', 'start_date', 'end_date', 'search']);

        $invoices = $this->invoiceService->getInvoices($filters, 15);
        $statistics = $this->invoiceService->getStatistics($filters['tenant_id'] ?? null);

        return Inertia::render('Admin/Invoices/Index', [
            'invoices' => $invoices,
            'statistics' => $statistics,
            'filters' => $filters,
            'statuses' => ClubInvoice::getStatuses(),
        ]);
    }

    /**
     * Show the form for creating a new invoice.
     */
    public function create(Request $request): Response
    {
        $clubId = $request->query('club_id');
        $club = $clubId ? Club::with('tenant')->find($clubId) : null;

        $clubs = Club::with('tenant')
            ->where('payment_method_type', 'invoice')
            ->orWhere('id', $clubId)
            ->orderBy('name')
            ->get();

        $plans = ClubSubscriptionPlan::where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Invoices/Create', [
            'club' => $club,
            'clubs' => $clubs,
            'plans' => $plans,
            'paymentTermsDays' => config('invoices.payment_terms_days', 14),
        ]);
    }

    /**
     * Store a newly created invoice.
     */
    public function store(CreateClubInvoiceRequest $request): RedirectResponse
    {
        $club = Club::findOrFail($request->club_id);

        $invoice = $this->invoiceService->create(
            $club,
            $request->validated(),
            $request->user()
        );

        return redirect()
            ->route('admin.invoices.show', $invoice)
            ->with('success', 'Rechnung wurde erstellt.');
    }

    /**
     * Display the specified invoice.
     */
    public function show(ClubInvoice $invoice): Response
    {
        $invoice->load(['club', 'subscriptionPlan', 'creator', 'updater']);

        return Inertia::render('Admin/Invoices/Show', [
            'invoice' => $invoice,
            'formatted' => $invoice->formatted_amounts,
            'canEdit' => $invoice->canBeEdited(),
            'canSend' => $invoice->canBeSent(),
            'canMarkPaid' => $invoice->canBeMarkedAsPaid(),
            'canSendReminder' => $invoice->canSendReminder(),
            'canCancel' => $invoice->canBeCancelled(),
        ]);
    }

    /**
     * Show the form for editing the invoice.
     */
    public function edit(ClubInvoice $invoice): Response
    {
        if (!$invoice->canBeEdited()) {
            return redirect()
                ->route('admin.invoices.show', $invoice)
                ->with('error', 'Diese Rechnung kann nicht mehr bearbeitet werden.');
        }

        $invoice->load(['club', 'subscriptionPlan']);

        $plans = ClubSubscriptionPlan::where('is_active', true)
            ->orWhere('id', $invoice->club_subscription_plan_id)
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Invoices/Edit', [
            'invoice' => $invoice,
            'plans' => $plans,
        ]);
    }

    /**
     * Update the specified invoice.
     */
    public function update(UpdateClubInvoiceRequest $request, ClubInvoice $invoice): RedirectResponse
    {
        $this->invoiceService->update(
            $invoice,
            $request->validated(),
            $request->user()
        );

        return redirect()
            ->route('admin.invoices.show', $invoice)
            ->with('success', 'Rechnung wurde aktualisiert.');
    }

    /**
     * Remove the specified invoice.
     */
    public function destroy(ClubInvoice $invoice): RedirectResponse
    {
        try {
            $this->invoiceService->delete($invoice);
            return redirect()
                ->route('admin.invoices.index')
                ->with('success', 'Rechnung wurde gelöscht.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Send the invoice to the club.
     */
    public function send(ClubInvoice $invoice): RedirectResponse
    {
        try {
            $this->invoiceService->markAsSent($invoice);
            return redirect()
                ->back()
                ->with('success', 'Rechnung wurde versendet.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(MarkInvoicePaidRequest $request, ClubInvoice $invoice): RedirectResponse
    {
        try {
            $this->invoiceService->markAsPaid(
                $invoice,
                $request->validated(),
                $request->user()
            );
            return redirect()
                ->back()
                ->with('success', 'Rechnung wurde als bezahlt markiert.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel the invoice.
     */
    public function cancel(Request $request, ClubInvoice $invoice): RedirectResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->invoiceService->cancel(
                $invoice,
                $request->reason,
                $request->user()
            );
            return redirect()
                ->back()
                ->with('success', 'Rechnung wurde storniert.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Send payment reminder.
     */
    public function sendReminder(ClubInvoice $invoice): RedirectResponse
    {
        try {
            $this->invoiceService->sendReminder($invoice);
            return redirect()
                ->back()
                ->with('success', 'Zahlungserinnerung wurde versendet.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Download invoice PDF.
     */
    public function downloadPdf(ClubInvoice $invoice)
    {
        return $this->pdfService->download($invoice);
    }

    /**
     * Preview invoice PDF in browser.
     */
    public function previewPdf(ClubInvoice $invoice)
    {
        return $this->pdfService->stream($invoice);
    }
}
