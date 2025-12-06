<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EnterpriseLead;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EnterpriseLeadController extends Controller
{
    /**
     * Display a listing of enterprise leads.
     */
    public function index(Request $request): Response
    {
        $query = EnterpriseLead::with('assignedUser')
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by organization type
        if ($request->filled('type')) {
            $query->where('organization_type', $request->type);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('organization_name', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $leads = $query->paginate(20)->withQueryString();

        // Get statistics
        $stats = [
            'total' => EnterpriseLead::count(),
            'new' => EnterpriseLead::where('status', 'new')->count(),
            'contacted' => EnterpriseLead::where('status', 'contacted')->count(),
            'qualified' => EnterpriseLead::where('status', 'qualified')->count(),
            'proposal' => EnterpriseLead::where('status', 'proposal')->count(),
            'won' => EnterpriseLead::where('status', 'won')->count(),
            'lost' => EnterpriseLead::where('status', 'lost')->count(),
        ];

        // Get super admins for assignment
        $assignableUsers = User::role('super_admin')
            ->select('id', 'name', 'email')
            ->get();

        return Inertia::render('Admin/Enterprise/Index', [
            'leads' => $leads,
            'stats' => $stats,
            'statuses' => EnterpriseLead::STATUSES,
            'organizationTypes' => EnterpriseLead::ORGANIZATION_TYPES,
            'assignableUsers' => $assignableUsers,
            'filters' => $request->only(['status', 'type', 'search']),
        ]);
    }

    /**
     * Display the specified enterprise lead.
     */
    public function show(EnterpriseLead $enterpriseLead): Response
    {
        $enterpriseLead->load('assignedUser');

        // Get super admins for assignment
        $assignableUsers = User::role('super_admin')
            ->select('id', 'name', 'email')
            ->get();

        return Inertia::render('Admin/Enterprise/Show', [
            'lead' => $enterpriseLead,
            'statuses' => EnterpriseLead::STATUSES,
            'organizationTypes' => EnterpriseLead::ORGANIZATION_TYPES,
            'clubCountOptions' => EnterpriseLead::CLUB_COUNT_OPTIONS,
            'teamCountOptions' => EnterpriseLead::TEAM_COUNT_OPTIONS,
            'assignableUsers' => $assignableUsers,
        ]);
    }

    /**
     * Update the specified enterprise lead.
     */
    public function update(Request $request, EnterpriseLead $enterpriseLead): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'string', 'in:' . implode(',', array_keys(EnterpriseLead::STATUSES))],
            'assigned_to' => ['sometimes', 'nullable', 'exists:users,id'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:10000'],
        ]);

        // Track if status changed to 'contacted'
        $wasContacted = $enterpriseLead->status !== 'contacted' && ($validated['status'] ?? null) === 'contacted';

        $enterpriseLead->update($validated);

        // Set contacted_at if newly contacted
        if ($wasContacted && !$enterpriseLead->contacted_at) {
            $enterpriseLead->update(['contacted_at' => now()]);
        }

        return redirect()->back()->with('success', 'Lead erfolgreich aktualisiert.');
    }

    /**
     * Remove the specified enterprise lead.
     */
    public function destroy(EnterpriseLead $enterpriseLead): RedirectResponse
    {
        $enterpriseLead->delete();

        return redirect()->route('admin.enterprise-leads.index')
            ->with('success', 'Lead erfolgreich gelöscht.');
    }
}
