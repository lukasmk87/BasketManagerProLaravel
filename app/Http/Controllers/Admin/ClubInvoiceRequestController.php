<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClubInvoiceRequest;
use App\Services\Invoice\ClubInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClubInvoiceRequestController extends Controller
{
    public function __construct(
        protected ClubInvoiceService $invoiceService
    ) {}

    /**
     * Display a listing of invoice requests.
     */
    public function index(Request $request): Response
    {
        $status = $request->query('status', 'pending');

        $query = ClubInvoiceRequest::with(['club', 'subscriptionPlan', 'processor'])
            ->orderByDesc('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $requests = $query->paginate(15);

        $pendingCount = ClubInvoiceRequest::pending()->count();

        return Inertia::render('Admin/InvoiceRequests/Index', [
            'requests' => $requests,
            'pendingCount' => $pendingCount,
            'currentStatus' => $status,
            'statuses' => ClubInvoiceRequest::getStatuses(),
        ]);
    }

    /**
     * Display the specified request.
     */
    public function show(ClubInvoiceRequest $invoiceRequest): Response
    {
        $invoiceRequest->load(['club', 'subscriptionPlan', 'processor', 'invoice']);

        return Inertia::render('Admin/InvoiceRequests/Show', [
            'invoiceRequest' => $invoiceRequest,
            'canProcess' => $invoiceRequest->canBeProcessed(),
        ]);
    }

    /**
     * Approve the invoice request.
     */
    public function approve(Request $request, ClubInvoiceRequest $invoiceRequest): RedirectResponse
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        try {
            if ($request->admin_notes) {
                $invoiceRequest->update(['admin_notes' => $request->admin_notes]);
            }

            $invoice = $this->invoiceService->approveRequest($invoiceRequest, $request->user());

            return redirect()
                ->route('admin.invoices.show', $invoice)
                ->with('success', 'Anfrage wurde genehmigt und Rechnung erstellt.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Reject the invoice request.
     */
    public function reject(Request $request, ClubInvoiceRequest $invoiceRequest): RedirectResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        try {
            $this->invoiceService->rejectRequest(
                $invoiceRequest,
                $request->rejection_reason,
                $request->user()
            );

            return redirect()
                ->route('admin.invoice-requests.index')
                ->with('success', 'Anfrage wurde abgelehnt.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }
}
