<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\ClubTransfer;
use App\Models\Tenant;
use App\Services\ClubTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ClubTransferController extends Controller
{
    public function __construct(
        protected ClubTransferService $transferService
    ) {
        // Ensure only super admins can access
        $this->middleware('admin');
    }

    /**
     * Display transfer history.
     */
    public function index(Request $request)
    {
        // Only super admins can view transfers
        Gate::authorize('viewAny', ClubTransfer::class);

        $query = ClubTransfer::query()
            ->with(['club', 'sourceTenant', 'targetTenant', 'initiatedBy'])
            ->latest();

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by source tenant
        if ($request->has('source_tenant_id')) {
            $query->where('source_tenant_id', $request->source_tenant_id);
        }

        // Filter by target tenant
        if ($request->has('target_tenant_id')) {
            $query->where('target_tenant_id', $request->target_tenant_id);
        }

        // Search by club name
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('club', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $transfers = $query->paginate(20);

        return Inertia::render('Admin/ClubTransfer/Index', [
            'transfers' => $transfers,
            'filters' => $request->only(['status', 'source_tenant_id', 'target_tenant_id', 'search']),
            'tenants' => Tenant::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    /**
     * Display transfer details.
     */
    public function show(ClubTransfer $transfer)
    {
        Gate::authorize('view', $transfer);

        $transfer->load([
            'club',
            'sourceTenant',
            'targetTenant',
            'initiatedBy',
            'logs' => function ($query) {
                $query->orderBy('created_at', 'asc');
            },
            'rollbackData',
        ]);

        return Inertia::render('Admin/ClubTransfer/Show', [
            'transfer' => [
                'id' => $transfer->id,
                'club' => $transfer->club,
                'source_tenant' => $transfer->sourceTenant,
                'target_tenant' => $transfer->targetTenant,
                'initiated_by' => $transfer->initiatedBy,
                'status' => $transfer->status,
                'started_at' => $transfer->started_at,
                'completed_at' => $transfer->completed_at,
                'failed_at' => $transfer->failed_at,
                'rolled_back_at' => $transfer->rolled_back_at,
                'can_rollback' => $transfer->canBeRolledBack(),
                'rollback_expires_at' => $transfer->rollback_expires_at,
                'metadata' => $transfer->metadata,
                'duration' => $transfer->getFormattedDuration(),
                'logs' => $transfer->logs->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'step' => $log->step,
                        'status' => $log->status,
                        'message' => $log->message,
                        'data' => $log->data,
                        'duration' => $log->getFormattedDuration(),
                        'created_at' => $log->created_at,
                    ];
                }),
                'created_at' => $transfer->created_at,
                'updated_at' => $transfer->updated_at,
            ],
        ]);
    }

    /**
     * Preview transfer impact.
     */
    public function preview(Request $request, Club $club)
    {
        Gate::authorize('create', ClubTransfer::class);

        $request->validate([
            'target_tenant_id' => 'required|uuid|exists:tenants,id',
        ]);

        $targetTenant = Tenant::findOrFail($request->target_tenant_id);

        try {
            $preview = $this->transferService->previewTransfer($club, $targetTenant);

            return response()->json([
                'success' => true,
                'data' => $preview,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Initiate a single club transfer.
     */
    public function store(Request $request, Club $club)
    {
        Gate::authorize('create', ClubTransfer::class);

        $request->validate([
            'target_tenant_id' => 'required|uuid|exists:tenants,id',
            'confirmed' => 'required|boolean|accepted',
        ]);

        $targetTenant = Tenant::findOrFail($request->target_tenant_id);

        try {
            $transfer = $this->transferService->transferClub(
                $club,
                $targetTenant,
                auth()->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Club-Transfer wurde initiiert',
                'data' => [
                    'transfer_id' => $transfer->id,
                    'status' => $transfer->status,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transfer-Initiierung fehlgeschlagen: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Initiate batch club transfer.
     */
    public function batchStore(Request $request)
    {
        Gate::authorize('create', ClubTransfer::class);

        $request->validate([
            'club_ids' => 'required|array|min:1',
            'club_ids.*' => 'required|uuid|exists:clubs,id',
            'target_tenant_id' => 'required|uuid|exists:tenants,id',
            'confirmed' => 'required|boolean|accepted',
        ]);

        $targetTenant = Tenant::findOrFail($request->target_tenant_id);

        try {
            $transfers = $this->transferService->batchTransfer(
                $request->club_ids,
                $targetTenant,
                auth()->user()
            );

            return response()->json([
                'success' => true,
                'message' => count($transfers) . ' Club-Transfers wurden initiiert',
                'data' => [
                    'transfer_ids' => collect($transfers)->pluck('id'),
                    'count' => count($transfers),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Batch-Transfer-Initiierung fehlgeschlagen: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Rollback a transfer.
     */
    public function rollback(ClubTransfer $transfer)
    {
        Gate::authorize('rollback', $transfer);

        if (!$transfer->canBeRolledBack()) {
            return response()->json([
                'success' => false,
                'message' => 'Transfer kann nicht rückgängig gemacht werden (entweder abgelaufen oder bereits zurückgesetzt)',
            ], 422);
        }

        try {
            $this->transferService->rollbackTransfer($transfer);

            return response()->json([
                'success' => true,
                'message' => 'Transfer wurde erfolgreich rückgängig gemacht',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Rollback fehlgeschlagen: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete a transfer record (soft delete).
     */
    public function destroy(ClubTransfer $transfer)
    {
        Gate::authorize('delete', $transfer);

        // Only allow deletion of old transfers (> 30 days)
        if ($transfer->created_at->greaterThan(now()->subDays(30))) {
            return response()->json([
                'success' => false,
                'message' => 'Transfer-Datensätze können erst nach 30 Tagen gelöscht werden',
            ], 422);
        }

        $transfer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transfer-Datensatz wurde gelöscht',
        ]);
    }
}
