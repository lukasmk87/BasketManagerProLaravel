<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V2\Clubs\StoreClubRequest;
use App\Http\Requests\Api\V2\Clubs\UpdateClubRequest;
use App\Http\Requests\Api\V2\Clubs\IndexClubsRequest;
use App\Http\Resources\ClubResource;
use App\Models\Club;
use App\Services\ClubService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClubController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected ClubService $clubService
    ) {}
    /**
     * Display a listing of clubs.
     */
    public function index(IndexClubsRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Club::class);

        $clubs = Club::query()
            ->with(['teams:id,club_id,name,season', 'users:id,name'])
            ->withCount(['teams', 'players'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('short_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $active = $request->status === 'active';
                $query->where('is_active', $active);
            })
            ->when($request->filled('verified'), function ($query) use ($request) {
                $verified = $request->verified === 'true';
                $query->where('is_verified', $verified);
            })
            ->when($request->filled('league'), function ($query) use ($request) {
                $query->where('league', $request->league);
            })
            ->when($request->filled('division'), function ($query) use ($request) {
                $query->where('division', $request->division);
            })
            ->when($request->filled('season'), function ($query) use ($request) {
                $query->where('season', $request->season);
            })
            ->when($request->filled('sort'), function ($query) use ($request) {
                $sortField = $request->sort;
                $sortDirection = $request->filled('direction') && $request->direction === 'desc' ? 'desc' : 'asc';
                
                $allowedSortFields = ['name', 'created_at', 'founded_at'];
                if (in_array($sortField, $allowedSortFields)) {
                    $query->orderBy($sortField, $sortDirection);
                }
            })
            ->latest()
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        return ClubResource::collection($clubs);
    }

    /**
     * Store a newly created club.
     */
    public function store(StoreClubRequest $request): ClubResource
    {
        $this->authorize('create', Club::class);

        $clubData = $request->validated();

        // Use ClubService to properly handle tenant_id and club creation
        $club = $this->clubService->createClub($clubData);

        return new ClubResource($club->load(['teams', 'users']));
    }

    /**
     * Display the specified club.
     */
    public function show(Club $club): ClubResource
    {
        $this->authorize('view', $club);

        $club->load([
            'teams.headCoach:id,name',
            'teams' => function ($query) {
                $query->withCount(['players', 'games']);
            },
            'users' => function ($query) {
                $query->withPivot('role', 'joined_at', 'is_active');
            },
        ]);

        return new ClubResource($club);
    }

    /**
     * Update the specified club.
     */
    public function update(UpdateClubRequest $request, Club $club): ClubResource
    {
        $this->authorize('update', $club);

        $club->update($request->validated());

        return new ClubResource($club->load(['teams', 'users']));
    }

    /**
     * Remove the specified club.
     */
    public function destroy(Club $club): JsonResponse
    {
        $this->authorize('delete', $club);

        // Check if club has active teams
        if ($club->teams()->active()->count() > 0) {
            return response()->json([
                'message' => 'Club kann nicht gelöscht werden, da noch aktive Teams vorhanden sind.',
            ], 422);
        }

        $club->delete();

        return response()->json([
            'message' => 'Club erfolgreich gelöscht.',
        ]);
    }

    /**
     * Get club statistics.
     */
    public function statistics(Club $club): JsonResponse
    {
        $this->authorize('view', $club);

        $statistics = $club->getStatistics();

        return response()->json($statistics);
    }

    /**
     * Get club teams.
     */
    public function teams(Club $club): JsonResponse
    {
        $this->authorize('view', $club);

        $teams = $club->teams()
            ->with(['headCoach:id,name', 'players:id,team_id,first_name,last_name'])
            ->withCount(['players', 'games'])
            ->get();

        return response()->json([
            'teams' => $teams,
        ]);
    }

    /**
     * Get club players.
     */
    public function players(Club $club): JsonResponse
    {
        $this->authorize('view', $club);

        $players = $club->players()
            ->with(['user:id,name,email', 'team:id,name'])
            ->where('status', 'active')
            ->get();

        return response()->json([
            'players' => $players,
        ]);
    }

    /**
     * Add member to club.
     */
    public function addMember(Club $club, StoreClubRequest $request): JsonResponse
    {
        $this->authorize('update', $club);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string|in:admin,manager,member',
        ]);

        $user = \App\Models\User::findOrFail($validated['user_id']);
        $club->addMember($user, $validated['role']);

        return response()->json([
            'message' => 'Mitglied erfolgreich hinzugefügt.',
        ]);
    }

    /**
     * Remove member from club.
     */
    public function removeMember(Club $club, \App\Models\User $user): JsonResponse
    {
        $this->authorize('update', $club);

        $club->removeMember($user);

        return response()->json([
            'message' => 'Mitglied erfolgreich entfernt.',
        ]);
    }

    /**
     * Verify club.
     */
    public function verify(Club $club): ClubResource
    {
        $this->authorize('update', $club);

        $club->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        return new ClubResource($club);
    }

    /**
     * Generate emergency QR code data for club.
     */
    public function emergencyQR(Club $club): JsonResponse
    {
        $this->authorize('view', $club);

        $qrData = $club->generateEmergencyQRData();

        return response()->json([
            'qr_data' => $qrData,
            'url' => route('emergency.club', ['club' => $club->slug]),
        ]);
    }

    /**
     * Get club's available seasons.
     */
    public function seasons(Club $club): JsonResponse
    {
        $this->authorize('view', $club);

        $seasons = $club->teams()
            ->select('season')
            ->distinct()
            ->orderBy('season', 'desc')
            ->pluck('season');

        return response()->json([
            'seasons' => $seasons,
        ]);
    }
}