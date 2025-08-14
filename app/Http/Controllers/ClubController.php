<?php

namespace App\Http\Controllers;

use App\Models\Club;
use App\Services\ClubService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClubController extends Controller
{
    public function __construct(
        private ClubService $clubService
    ) {}

    /**
     * Display a listing of clubs.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Get clubs based on user permissions
        $clubs = Club::query()
            ->with(['teams', 'users'])
            ->withCount(['teams', 'users'])
            ->when($user->hasRole('admin') || $user->hasRole('super-admin'), function ($query) {
                // Admin users see all clubs
                return $query;
            }, function ($query) use ($user) {
                // Other users see only their clubs
                return $query->whereHas('users', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('Clubs/Index', [
            'clubs' => $clubs,
            'can' => [
                'create' => $user->can('create', Club::class),
            ],
        ]);
    }

    /**
     * Show the form for creating a new club.
     */
    public function create(): Response
    {
        $this->authorize('create', Club::class);

        return Inertia::render('Clubs/Create');
    }

    /**
     * Store a newly created club in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Club::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:10',
            'founded_year' => 'nullable|integer|min:1850|max:' . date('Y'),
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:2',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string|max:1000',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
        ]);

        $club = $this->clubService->createClub($validated);

        return redirect()->route('clubs.show', $club)
            ->with('success', 'Club wurde erfolgreich erstellt.');
    }

    /**
     * Display the specified club.
     */
    public function show(Club $club): Response
    {
        $this->authorize('view', $club);

        $club->load([
            'teams.players',
            'users' => function ($query) {
                $query->withPivot('role', 'joined_at');
            }
        ]);

        $clubStats = $this->clubService->getClubStatistics($club);

        return Inertia::render('Clubs/Show', [
            'club' => $club,
            'statistics' => $clubStats,
            'can' => [
                'update' => auth()->user()->can('update', $club),
                'delete' => auth()->user()->can('delete', $club),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified club.
     */
    public function edit(Club $club): Response
    {
        $this->authorize('update', $club);

        return Inertia::render('Clubs/Edit', [
            'club' => $club,
        ]);
    }

    /**
     * Update the specified club in storage.
     */
    public function update(Request $request, Club $club)
    {
        $this->authorize('update', $club);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:10',
            'founded_year' => 'nullable|integer|min:1850|max:' . date('Y'),
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:2',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string|max:1000',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
        ]);

        $this->clubService->updateClub($club, $validated);

        return redirect()->route('clubs.show', $club)
            ->with('success', 'Club wurde erfolgreich aktualisiert.');
    }

    /**
     * Remove the specified club from storage.
     */
    public function destroy(Club $club)
    {
        $this->authorize('delete', $club);

        $this->clubService->deleteClub($club);

        return redirect()->route('clubs.index')
            ->with('success', 'Club wurde erfolgreich gelöscht.');
    }
}