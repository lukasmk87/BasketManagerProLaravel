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
                // Super-Admin and Admin users see all clubs
                return $query;
            }, function ($query) use ($user) {
                // Club-Admin and other users see only their clubs
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
            'description' => 'nullable|string|max:1000',
            'website' => 'nullable|url|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            
            // Detailed address fields
            'address_street' => 'nullable|string|max:255',
            'address_city' => 'nullable|string|max:255',
            'address_state' => 'nullable|string|max:255',
            'address_zip' => 'nullable|string|max:20',
            'address_country' => 'nullable|string|max:2',
            
            // Basketball-specific fields
            'facilities' => 'nullable|json',
            
            // Club colors for branding
            'primary_color' => 'nullable|string|regex:/^#[A-Fa-f0-9]{6}$/',
            'secondary_color' => 'nullable|string|regex:/^#[A-Fa-f0-9]{6}$/',
            'accent_color' => 'nullable|string|regex:/^#[A-Fa-f0-9]{6}$/',
            
            // Status fields
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            
            // Emergency contacts
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',
            'emergency_contact_email' => 'nullable|email|max:255',
            
            // Financial information
            'membership_fee' => 'nullable|numeric|min:0|max:9999.99',
            'currency' => 'nullable|string|max:3',
            
            // Social media links
            'social_links' => 'nullable|json',
            
            // Language settings
            'default_language' => 'nullable|string|max:5',
            'supported_languages' => 'nullable|json',
            
            // Additional settings
            'settings' => 'nullable|json',
            'preferences' => 'nullable|json',
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
            'description' => 'nullable|string|max:1000',
            'website' => 'nullable|url|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            
            // Detailed address fields
            'address_street' => 'nullable|string|max:255',
            'address_city' => 'nullable|string|max:255',
            'address_state' => 'nullable|string|max:255',
            'address_zip' => 'nullable|string|max:20',
            'address_country' => 'nullable|string|max:2',
            
            // Basketball-specific fields
            'facilities' => 'nullable|json',
            
            // Club colors for branding
            'primary_color' => 'nullable|string|regex:/^#[A-Fa-f0-9]{6}$/',
            'secondary_color' => 'nullable|string|regex:/^#[A-Fa-f0-9]{6}$/',
            'accent_color' => 'nullable|string|regex:/^#[A-Fa-f0-9]{6}$/',
            
            // Status fields
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            
            // Emergency contacts
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:50',
            'emergency_contact_email' => 'nullable|email|max:255',
            
            // Financial information
            'membership_fee' => 'nullable|numeric|min:0|max:9999.99',
            'currency' => 'nullable|string|max:3',
            
            // Social media links
            'social_links' => 'nullable|json',
            
            // Language settings
            'default_language' => 'nullable|string|max:5',
            'supported_languages' => 'nullable|json',
            
            // Additional settings
            'settings' => 'nullable|json',
            'preferences' => 'nullable|json',
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