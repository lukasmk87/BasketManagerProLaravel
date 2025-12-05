<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoiceRequest;
use App\Services\Invoice\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceRequestController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    /**
     * Display a listing of invoice requests.
     */
    public function index(Request $request): Response
    {
        $status = $request->query('status', 'pending');
        $type = $request->query('type'); // 'club', 'tenant', or null for all

        $query = InvoiceRequest::with(['requestable', 'subscriptionPlan', 'processor'])
            ->orderByDesc('created_at');

        // Filter by status
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Filter by type
        if ($type === 'club') {
            $query->forClubs();
        } elseif ($type === 'tenant') {
            $query->forTenants();
        }

        // Filter by tenant for non-super-admins
        if (!auth()->user()->isSuperAdmin()) {
            $query->where('tenant_id', auth()->user()->tenant_id);
        }

        $requests = $query->paginate(15);

        $pendingCount = InvoiceRequest::pending()
            ->when(!auth()->user()->isSuperAdmin(), fn ($q) => $q->where('tenant_id', auth()->user()->tenant_id))
            ->count();

        return Inertia::render('Admin/InvoiceRequests/Index', [
            'requests' => $requests,
            'pendingCount' => $pendingCount,
            'currentStatus' => $status,
            'currentType' => $type,
            'statuses' => InvoiceRequest::getStatuses(),
            'requestableTypes' => InvoiceRequest::getRequestableTypes(),
        ]);
    }

    /**
     * Display the specified request.
     */
    public function show(InvoiceRequest $invoiceRequest): Response
    {
        $invoiceRequest->load(['requestable', 'subscriptionPlan', 'processor', 'invoice']);

        return Inertia::render('Admin/InvoiceRequests/Show', [
            'invoiceRequest' => $invoiceRequest,
            'canProcess' => $invoiceRequest->canBeProcessed(),
        ]);
    }

    /**
     * Approve the invoice request.
     */
    public function approve(Request $request, InvoiceRequest $invoiceRequest): RedirectResponse
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
    public function reject(Request $request, InvoiceRequest $invoiceRequest): RedirectResponse
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
