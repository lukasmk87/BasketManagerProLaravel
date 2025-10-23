<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V2\Users\IndexUsersRequest;
use App\Http\Requests\Api\V2\Users\StoreUserRequest;
use App\Http\Requests\Api\V2\Users\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(IndexUsersRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with(['roles:id,name', 'playerProfile.team:id,name'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), function ($query) use ($request) {
                $query->whereHas('roles', function ($q) use ($request) {
                    $q->where('name', $request->role);
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $active = $request->status === 'active';
                $query->where('is_active', $active);
            })
            ->when($request->filled('team_id'), function ($query) use ($request) {
                $query->whereHas('playerProfile', function ($q) use ($request) {
                    $q->where('team_id', $request->team_id);
                });
            })
            ->when($request->filled('sort'), function ($query) use ($request) {
                $sortField = $request->sort;
                $sortDirection = $request->filled('direction') && $request->direction === 'desc' ? 'desc' : 'asc';

                $allowedSortFields = ['name', 'email', 'created_at', 'last_login_at'];
                if (in_array($sortField, $allowedSortFields)) {
                    $query->orderBy($sortField, $sortDirection);
                }
            })
            ->latest()
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        return UserResource::collection($users);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): UserResource
    {
        $this->authorize('create', User::class);

        $userData = $request->validated();

        // Generate password if not provided
        if (! isset($userData['password'])) {
            $userData['password'] = Hash::make(Str::random(12));
        } else {
            $userData['password'] = Hash::make($userData['password']);
        }

        $user = User::create($userData);

        // Assign roles if provided
        if (isset($userData['roles'])) {
            $user->syncRoles($userData['roles']);
        }

        // Create player profile if user has player role
        if ($user->hasRole('player') && isset($userData['player_data'])) {
            $user->playerProfile()->create($userData['player_data']);
        }

        return new UserResource($user->load(['roles', 'playerProfile.team']));
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);

        $user->load([
            'roles.permissions:id,name',
            'playerProfile.team.club',
            'coachedTeams.club',
            'socialAccounts:id,user_id,provider',
        ]);

        return new UserResource($user);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $this->authorize('update', $user);

        $userData = $request->validated();

        // Handle password update
        if (isset($userData['password'])) {
            $userData['password'] = Hash::make($userData['password']);
        }

        $user->update($userData);

        // Update roles if provided
        if (isset($userData['roles'])) {
            $user->syncRoles($userData['roles']);
        }

        // Update player profile if provided
        if (isset($userData['player_data']) && $user->playerProfile) {
            $user->playerProfile->update($userData['player_data']);
        }

        return new UserResource($user->load(['roles', 'playerProfile.team']));
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        // Use UserService for intelligent soft/hard delete
        $userService = app(\App\Services\UserService::class);

        try {
            $userService->deleteUser($user);

            return response()->json([
                'message' => 'Benutzer erfolgreich gelöscht.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Löschen des Benutzers.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activate a user.
     */
    public function activate(User $user): UserResource
    {
        $this->authorize('update', $user);

        $user->update(['is_active' => true]);

        return new UserResource($user);
    }

    /**
     * Deactivate a user.
     */
    public function deactivate(User $user): UserResource
    {
        $this->authorize('update', $user);

        $user->update(['is_active' => false]);

        return new UserResource($user);
    }

    /**
     * Get user statistics.
     */
    public function statistics(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        $stats = $user->getBasketballStats();

        return response()->json($stats);
    }

    /**
     * Get user's teams.
     */
    public function teams(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        $teams = collect();

        // Add coached teams
        if ($user->isCoach()) {
            $coachedTeams = $user->coachedTeams()->with('club')->get();
            $assistantCoachedTeams = $user->assistantCoachedTeams()->with('club')->get();
            $teams = $teams->merge($coachedTeams)->merge($assistantCoachedTeams);
        }

        // Add player team
        if ($user->isPlayer() && $user->playerProfile) {
            $playerTeam = $user->playerProfile->team()->with('club')->first();
            if ($playerTeam) {
                $teams->push($playerTeam);
            }
        }

        return response()->json([
            'teams' => $teams->unique('id')->values(),
        ]);
    }

    /**
     * Update user locale preferences.
     */
    public function updateLocale(User $user, UpdateUserRequest $request): UserResource
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'language' => 'required|string|in:de,en',
            'timezone' => 'required|string|timezone',
        ]);

        $user->update($validated);

        return new UserResource($user);
    }

    /**
     * Get user's activity log.
     */
    public function activities(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        $activities = $user->activities()
            ->latest()
            ->limit(50)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'properties' => $activity->properties,
                    'created_at' => $activity->created_at,
                ];
            });

        return response()->json([
            'activities' => $activities,
        ]);
    }
}
